package com.example.ia_java;

import java.util.HashMap;
import java.util.Map;

import org.opencv.core.Mat;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RestController;

@RestController
public class AlinearFotoController {

    private final FaceAligner faceAligner = new FaceAligner();

    @PostMapping("/alinear-foto")
    public Map<String, Object> alinearFoto(@RequestBody Map<String, String> body) throws Exception {

        String foto1 = body.get("foto_1").toString();
        String foto2 = body.get("foto_2").toString();

        if (foto1 == null) {
            throw new RuntimeException("Falta foto_1  en la petición.");
        }

        if (foto2 == null) {
            throw new RuntimeException("Falta foto_2 en la petición.");
        }

        foto1 = limpiarBase64(foto1);
        foto2 = limpiarBase64(foto2);

        Mat ref   = ImageUtils.base64ToMatDirect(foto1);
        Mat nueva = ImageUtils.base64ToMatDirect(foto2);
        Mat alineada = faceAligner.alinearConReferencia(ref, nueva);
        String base64Alineada = ImageUtils.matToBase64(alineada);

        Map<String, Object> resp = new HashMap<>();
        resp.put("foto_alineada", "data:image/png;base64," + base64Alineada);

        return resp;
    }

    private String limpiarBase64(String base64) {
        if (base64.contains(",")) {
            base64 = base64.substring(base64.indexOf(",") + 1);
        }
        return base64.replaceAll("\\s", "");
    }
}