package com.example.ia_java;

import java.util.HashMap;
import java.util.Map;

import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RestController;

@RestController
public class ComparisonController {

    @PostMapping("/comparacion")
    public Map<String, Object> comparar(@RequestBody Map<String, Object> datos) {
        Object datofotoNuevo = datos.get("datofoto_nuevo");
        Object datofotoAntiguo = datos.get("datofoto_antiguo");
        Object cuestionarioNuevo = datos.get("cuestionario_nuevo");
        Object cuestionarioAntiguo = datos.get("cuestionario_antiguo");

        System.out.println("===== COMPARACIÓN RECIBIDA =====");
        System.out.println("Datofoto nuevo: " + (datofotoNuevo != null ? datofotoNuevo.toString() : "NO RECIBIDO"));
        System.out.println("Datofoto antiguo: " + (datofotoAntiguo != null ? datofotoAntiguo.toString() : "NO RECIBIDO"));
        System.out.println("Cuestionario nuevo: " + (cuestionarioNuevo != null ? cuestionarioNuevo.toString() : "NO RECIBIDO"));
        System.out.println("Cuestionario antiguo: " + (cuestionarioAntiguo != null ? cuestionarioAntiguo.toString() : "NO RECIBIDO"));
        System.out.println("================================");

        String resultado;

        try {
            TextGenerator tg = new TextGenerator();
            resultado = tg.generarComparacionDatos(
                datofotoNuevo,
                datofotoAntiguo,
                cuestionarioNuevo,
                cuestionarioAntiguo
            );

            System.out.println("===== RESULTADO GENERADO =====");
            System.out.println(resultado);
            System.out.println("==============================");

        } catch (Exception e) {
            e.printStackTrace();
            resultado = "Error procesando datos: " + e.getMessage();
        }

        Map<String, Object> response = new HashMap<>();
        response.put("status", "ok");
        response.put("texto", resultado);

        return response;
    }
}