package com.example.ia_java;

import java.util.HashMap;
import java.util.Map;
import org.springframework.web.bind.annotation.*;

@RestController
public class AnalysisController {

    public static class AnalisisTemporal {
        public Map<String, Object> datosIniciales = new HashMap<>();
        public Map<String, Object> fotos = new HashMap<>();
        public Map<String, Object> cuestionario = new HashMap<>();
    }

    private AnalisisTemporal sesion = new AnalisisTemporal();

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

        sesion.fotos.put(slugFoto, base64);

        System.out.println("===== FOTO RECIBIDA =====");
        System.out.println("slug_foto: " + slugFoto);
        System.out.println("=========================");

        return Map.of("status", "ok", "msg", "Foto guardada");
    }

    @PostMapping("/recibircuestionario")
    public Map<String, Object> recibirCuestionario(@RequestBody Map<String, Object> datos) {

        sesion.cuestionario = (Map<String, Object>) datos.get("cuestionario");

        System.out.println("===== ANALISIS COMPLETO =====");
        System.out.println("DATOS INICIALES: " + sesion.datosIniciales);
        System.out.println("FOTOS: " + sesion.fotos.keySet());
        System.out.println("CUESTIONARIO: " + sesion.cuestionario);
        System.out.println("================================");

        return Map.of(
            "status", "ok",
            "msg", "Cuestionario guardado y análisis completo",
            "datos_iniciales", sesion.datosIniciales,
            "fotos", sesion.fotos,
            "cuestionario", sesion.cuestionario
        );
    }
}