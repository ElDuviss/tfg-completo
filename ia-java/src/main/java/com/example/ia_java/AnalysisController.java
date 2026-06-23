package com.example.ia_java;

import java.util.HashMap;
import java.util.Map;

import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RestController;

@RestController
public class AnalysisController {

    public static class AnalisisTemporal {
        public String slugAnalisis;
        public Map<String, Object> datosImagenes = new HashMap<>();
        public Map<String, Object> cuestionario = new HashMap<>();
    }

    private AnalisisTemporal sesion = new AnalisisTemporal();
    private Map<String, Map<String, Object>> featuresPorFoto = new HashMap<>();

    @PostMapping("/recibir")
    public Map<String, Object> recibirDatos(@RequestBody Map<String, Object> datos) {

        sesion = new AnalisisTemporal();
        sesion.slugAnalisis = datos.get("slug").toString();

        System.out.println("===== DATOS INICIALES =====");
        System.out.println("SLUG ANALISIS: " + sesion.slugAnalisis);
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
            return Map.of(
                "status", "ok",
                "msg", "Foto analizada y guardada",
                "faltan_fotos", 4 - featuresPorFoto.size()
            );
        }

        Map<String, Object> featuresGlobales = generarFeaturesGlobales(featuresPorFoto);

        System.out.println("===== FEATURES GLOBALES =====");
        System.out.println(featuresGlobales);
        System.out.println("=============================");

        return Map.of(
            "status", "ok",
            "features_globales", featuresGlobales
        );
    }

    @PostMapping("/recibirdatosimagenes")
    public Map<String, Object> recibirDatosImagenes(@RequestBody Map<String, Object> datos) {

        sesion.datosImagenes = (Map<String, Object>) datos.get("datos");

        return Map.of(
            "status", "ok",
            "msg", "Datos de imágenes almacenados correctamente"
        );
    }

    @PostMapping("/recibircuestionario")
    public Map<String, Object> recibirCuestionario(@RequestBody Map<String, Object> datos) {

        sesion.cuestionario = (Map<String, Object>) datos.get("cuestionario");

        System.out.println("===== ANALISIS COMPLETO =====");
        System.out.println("SLUG ANALISIS: " + sesion.slugAnalisis);
        System.out.println("DATOS IMAGENES: " + sesion.datosImagenes);
        System.out.println("CUESTIONARIO: " + sesion.cuestionario);
        System.out.println("================================");

        try {
            TextGenerator tg = new TextGenerator();

            String texto = tg.generarAnalisisApartado(
                    sesion.slugAnalisis,
                    sesion.datosImagenes,
                    sesion.cuestionario
            );

            System.out.println(texto);

            return Map.of(
                "status", "ok",
                "analisis_texto", texto
            );

        } catch (Exception e) {
            e.printStackTrace();
            return Map.of(
                "status", "error",
                "msg", e.getMessage()
            );
        }
    }

    private Map<String, Object> generarFeaturesGlobales(
        Map<String, Map<String, Object>> f) {

        Map<String, Object> r = new HashMap<>();

        double frontal =
                getDouble(f.get("foto-frontal").get("densidad"));

        double superior =
                getDouble(f.get("foto-superior").get("densidad"));

        double lateralDerecha =
                getDouble(f.get("foto-lateral-derecha").get("densidad"));

        double lateralIzquierda =
                getDouble(f.get("foto-lateral-izquierda").get("densidad"));

        double densidadMedia =
                (frontal + superior + lateralDerecha + lateralIzquierda) / 4.0;

        double densidadLaterales =
                (lateralDerecha + lateralIzquierda) / 2.0;

        double ratioEntradas =
                frontal / Math.max(densidadLaterales, 0.01);

        String entradas;

        if (ratioEntradas < 0.70) {
            entradas = "marcadas";
        } else if (ratioEntradas < 0.90) {
            entradas = "leves";
        } else {
            entradas = "no_visibles";
        }

        double ratioCoronilla =
                superior / Math.max(densidadMedia, 0.01);

        String coronilla;

        if (ratioCoronilla < 0.70) {
            coronilla = "alta";
        } else if (ratioCoronilla < 0.90) {
            coronilla = "media";
        } else {
            coronilla = "baja";
        }

        double contrasteMedio = media(
                f.get("foto-frontal").get("contraste"),
                f.get("foto-superior").get("contraste"),
                f.get("foto-lateral-derecha").get("contraste"),
                f.get("foto-lateral-izquierda").get("contraste")
        );

        String miniaturizacion;

        if (contrasteMedio < 12) {
            miniaturizacion = "alta";
        } else if (contrasteMedio < 25) {
            miniaturizacion = "moderada";
        } else {
            miniaturizacion = "baja";
        }

        double brilloMedio = media(
                f.get("foto-frontal").get("brillo"),
                f.get("foto-superior").get("brillo"),
                f.get("foto-lateral-derecha").get("brillo"),
                f.get("foto-lateral-izquierda").get("brillo")
        );

        String grasa;

        if (brilloMedio > 170) {
            grasa = "alta";
        } else if (brilloMedio > 120) {
            grasa = "media";
        } else {
            grasa = "baja";
        }

        double rojezMedia = media(
                f.get("foto-frontal").get("rojez"),
                f.get("foto-superior").get("rojez"),
                f.get("foto-lateral-derecha").get("rojez"),
                f.get("foto-lateral-izquierda").get("rojez")
        );

        String irritacion;

        if (rojezMedia > 35) {
            irritacion = "alta";
        } else if (rojezMedia > 15) {
            irritacion = "media";
        } else {
            irritacion = "baja";
        }

        String colorGlobal = determinarColorGlobal(
                (String) f.get("foto-frontal").get("color"),
                (String) f.get("foto-superior").get("color"),
                (String) f.get("foto-lateral-derecha").get("color"),
                (String) f.get("foto-lateral-izquierda").get("color")
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
