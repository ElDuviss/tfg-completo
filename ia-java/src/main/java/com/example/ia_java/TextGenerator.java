package com.example.ia_java;

import java.io.IOException;
import java.net.URI;
import java.net.http.*;
import java.util.Map;

public class TextGenerator {

    private static final String API_KEY = System.getenv("GROQ_API_KEY");

    public String construirPrompt(String slug,
                                  Map<String, Object> datosImagenes,
                                  Map<String, Object> cuestionario) {

        String titulo = switch (slug) {
            case "baldness_analysis" -> "Análisis de calvicie y alopecia";
            case "tinte_analysis" -> "Análisis de tintes y coloración";
            case "dye_analysis" -> "Análisis de tinte para el pelo";
            case "health_analysis" -> "Análisis de salud del cuero cabelludo";
            default -> "Análisis capilar";
        };

        return """
        Eres un experto en salud capilar. Genera un análisis profesional SOLO sobre el siguiente apartado:

        APARTADO: %s

        === DATOS DE IMÁGENES ===
        %s

        === CUESTIONARIO ===
        %s

        Instrucciones:
        - Analiza únicamente el apartado indicado por el slug.
        - No hables de otros temas.
        - Usa los datos de imágenes y cuestionario para justificar el análisis.
        - Da recomendaciones específicas y prácticas.
        - Escribe en tono humano, profesional y fácil de entender.
        """.formatted(titulo, datosImagenes, cuestionario);
    }

    private String extraerContenido(String json) {
        try {
            // Buscar "content":"..."
            int index = json.indexOf("\"content\":");
            if (index == -1) return json;

            int start = json.indexOf("\"", index + 10) + 1;
            int end = json.indexOf("\"", start);

            return json.substring(start, end)
                    .replace("\\n", "\n")
                    .replace("\\\"", "\"");

        } catch (Exception e) {
            return json;
        }
    }

    public String generarTextoIA(String prompt) throws IOException, InterruptedException {

        String url = "https://api.groq.com/openai/v1/chat/completions";

        HttpClient client = HttpClient.newHttpClient();

        String safePrompt = prompt
                .replace("\\", "\\\\")
                .replace("\"", "\\\"")
                .replace("\n", "\\n");

        String body = """
        {
          "model": "meta-llama/llama-4-scout-17b-16e-instruct",
          "messages": [
            { "role": "system", "content": "Eres un experto en salud capilar y dermatología." },
            { "role": "user", "content": "%s" }
          ],
          "temperature": 0.7
        }
        """.formatted(safePrompt);

        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create(url))
                .header("Authorization", "Bearer " + API_KEY)
                .header("Content-Type", "application/json")
                .POST(HttpRequest.BodyPublishers.ofString(body))
                .build();

        HttpResponse<String> response =
                client.send(request, HttpResponse.BodyHandlers.ofString());

        return extraerContenido(response.body());
    }

    public String generarAnalisisApartado(String slug,
                                          Map<String, Object> datosImagenes,
                                          Map<String, Object> cuestionario)
            throws IOException, InterruptedException {

        String prompt = construirPrompt(slug, datosImagenes, cuestionario);
        return generarTextoIA(prompt);
    }
}