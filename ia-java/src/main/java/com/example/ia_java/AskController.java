package com.example.ia_java;

import java.util.Map;
import org.springframework.web.bind.annotation.*;

@RestController
public class AskController {

    @PostMapping("/ask")
    public Map<String, Object> recibirDatos(@RequestBody Map<String, Object> datos) {

        try {
            String pregunta = (String) datos.get("pregunta");
            Map<String, Object> cuestionario = (Map<String, Object>) datos.get("cuestionario");
            Map<String, Object> datosFotos = (Map<String, Object>) datos.get("datos_fotos");

            System.out.println("Pregunta recibida: " + pregunta);
            System.out.println("Cuestionario: " + cuestionario);
            System.out.println("Datos de fotos: " + datosFotos);

            TextGenerator generator = new TextGenerator();

            String respuestaIA = generator.generarRespuestaChat(
                pregunta,
                datosFotos,
                cuestionario
            );

            System.out.println("Respuesta: " + respuestaIA);

            return Map.of(
                "status", "ok",
                "respuesta", respuestaIA
            );

        } catch (Exception e) {
            e.printStackTrace();
            return Map.of(
                "status", "error",
                "mensaje", e.getMessage()
            );
        }
    }
}