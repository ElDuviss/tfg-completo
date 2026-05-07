package com.example.ia_java;

import java.util.HashMap;
import java.util.Map;
import org.springframework.web.bind.annotation.*;

@RestController
public class AnalysisController {

    public static class AnalisisTemporal {
        public Map<String, Object> datosIniciales = new HashMap<>();
        public Map<String, Object> cuestionario = new HashMap<>();
    }

    private AnalisisTemporal sesion = new AnalisisTemporal();
    private Map<String, Map<String, Object>> featuresPorFoto = new HashMap<>();

    @PostMapping("/recibir")
    public Map<String, Object> recibirDatos(@RequestBody Map<String, Object> datos) {

        sesion = new AnalisisTemporal();
        sesion.datosIniciales = datos;

        System.out.println("===== DATOS INICIALES =====");
        System.out.println(datos);
        System.out.println("===========================");

        return Map.of("status", "ok", "msg", "Datos iniciales guardados");
    }

    @PostMapping("/recibirfotos")
    public Map<String, Object> recibirFoto(@RequestBody Map<String, Object> datos) {

        String slugFoto = datos.get("slug_foto").toString();
        String base64 = datos.get("base64").toString();

        ImageAnalyzer analyzer = new ImageAnalyzer();
        Map<String, Object> features = analyzer.analizarImagen(base64);

        featuresPorFoto.put(slugFoto, features);

        if (featuresPorFoto.size() < 4) {
            System.out.println("===== FOTO RECIBIDA =====");
            System.out.println("Slug: " + slugFoto);
            System.out.println("Features: " + features);
            System.out.println("Faltan fotos: " + (4 - featuresPorFoto.size()));
            System.out.println("=========================");
            return Map.of(
                "status", "ok",
                "msg", "Foto analizada y guardada",
                "faltan_fotos", 4 - featuresPorFoto.size()
            );
        }

        System.out.println("===== FEATURES POR FOTO RECIBIDAS =====");
        System.out.println(featuresPorFoto.keySet());
        System.out.println("========================================");

        Map<String, Object> featuresGlobales = generarFeaturesGlobales(featuresPorFoto);

        System.out.println("===== FEATURES GLOBALES =====");
        System.out.println(featuresGlobales);
        System.out.println("=============================");

        return Map.of(
            "status", "ok",
            "features_globales", featuresGlobales
        );
    }

    @PostMapping("/recibircuestionario")
    public Map<String, Object> recibirCuestionario(@RequestBody Map<String, Object> datos) {

        sesion.cuestionario = (Map<String, Object>) datos.get("cuestionario");

        System.out.println("===== ANALISIS COMPLETO =====");
        System.out.println("DATOS INICIALES: " + sesion.datosIniciales);
        System.out.println("CUESTIONARIO: " + sesion.cuestionario);
        System.out.println("================================");

        return Map.of(
            "status", "ok",
            "msg", "Cuestionario guardado",
            "datos_iniciales", sesion.datosIniciales,
            "cuestionario", sesion.cuestionario
        );
    }

    private Map<String, Object> generarFeaturesGlobales(Map<String, Map<String, Object>> f) {
        Map<String, Object> r = new HashMap<>();

        if (!f.containsKey("foto-frontal") ||
            !f.containsKey("foto-lateral-derecha") ||
            !f.containsKey("foto-lateral-izquierda") ||
            !f.containsKey("foto-superior")) {

            System.out.println("ERROR: Faltan fotos. Recibidas: " + f.keySet());
            throw new RuntimeException("Faltan fotos para generar el análisis global");
        }

        double densidadMedia = media(
                f.get("foto-frontal").get("densidad"),
                f.get("foto-superior").get("densidad"),
                f.get("foto-lateral-derecha").get("densidad"),
                f.get("foto-lateral-izquierda").get("densidad")
        );

        double densidadFrontal = getDouble(f.get("foto-frontal").get("densidad"));
        double densidadLaterales = media(
                f.get("foto-lateral-derecha").get("densidad"),
                f.get("foto-lateral-izquierda").get("densidad")
        );

        String entradas = densidadFrontal < densidadLaterales * 0.75 ? "marcadas" :
                          densidadFrontal < densidadLaterales * 0.9 ? "leves" : "no_visibles";

        double densidadCoronilla = getDouble(f.get("foto-superior").get("densidad"));
        String coronilla = densidadCoronilla < 0.25 ? "despoblada" :
                           densidadCoronilla < 0.45 ? "media" : "normal";

        double contrasteMedio = media(
                f.get("foto-frontal").get("contraste"),
                f.get("foto-superior").get("contraste"),
                f.get("foto-lateral-derecha").get("contraste"),
                f.get("foto-lateral-izquierda").get("contraste")
        );

        String miniaturizacion = contrasteMedio < 10 ? "alta" :
                                 contrasteMedio < 20 ? "moderada" : "baja";

        double brilloMedio = media(
                f.get("foto-frontal").get("brillo"),
                f.get("foto-superior").get("brillo"),
                f.get("foto-lateral-derecha").get("brillo"),
                f.get("foto-lateral-izquierda").get("brillo")
        );

        String grasa = brilloMedio > 150 ? "alta" :
                       brilloMedio > 110 ? "media" : "baja";

        double rojezMedia = media(
                f.get("foto-frontal").get("rojez"),
                f.get("foto-superior").get("rojez"),
                f.get("foto-lateral-derecha").get("rojez"),
                f.get("foto-lateral-izquierda").get("rojez")
        );

        String irritacion = rojezMedia > 150 ? "alta" :
                            rojezMedia > 110 ? "media" : "baja";

        String colorFrontal = (String) f.get("foto-frontal").get("color");
        String colorSuperior = (String) f.get("foto-superior").get("color");
        String colorDerecha = (String) f.get("foto-lateral-derecha").get("color");
        String colorIzquierda = (String) f.get("foto-lateral-izquierda").get("color");

        String colorGlobal = determinarColorGlobal(
                colorFrontal, colorSuperior, colorDerecha, colorIzquierda
        );

        r.put("densidad_media", densidadMedia);
        r.put("entradas", entradas);
        r.put("coronilla", coronilla);
        r.put("miniaturizacion", miniaturizacion);
        r.put("grasa", grasa);
        r.put("irritacion", irritacion);
        r.put("color_cabello", colorGlobal);

        return r;
    }

    private String determinarColorGlobal(String... colores) {
        Map<String, Integer> contador = new HashMap<>();

        for (String c : colores) {
            if (c == null) continue;
            contador.put(c, contador.getOrDefault(c, 0) + 1);
        }

        String dominante = "indeterminado";
        int max = 0;

        for (Map.Entry<String, Integer> e : contador.entrySet()) {
            if (e.getValue() > max) {
                dominante = e.getKey();
                max = e.getValue();
            }
        }

        return dominante;
    }

    private double media(Object... valores) {
        double sum = 0;
        int count = 0;
        for (Object v : valores) {
            if (v instanceof Number) {
                sum += ((Number) v).doubleValue();
                count++;
            }
        }
        return count == 0 ? 0 : sum / count;
    }

    private double getDouble(Object o) {
        if (o instanceof Number) return ((Number) o).doubleValue();
        try { return Double.parseDouble(o.toString()); } catch (Exception e) { return 0; }
    }
}