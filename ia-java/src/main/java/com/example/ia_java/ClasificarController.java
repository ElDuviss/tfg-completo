package com.example.ia_java;

import java.util.HashMap;
import java.util.Map;
import org.opencv.core.Mat;
import org.opencv.core.Rect;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RestController;

@RestController
public class ClasificarController {

    @PostMapping("/clasificar")
    public Map<String, Object> clasificar(@RequestBody Map<String, Object> body) {

        Map<String, Object> resp = new HashMap<>();

        try {
            System.out.println("\n\n==============================");
            System.out.println("PETICIÓN RECIBIDA");
            System.out.println("==============================");

            String imagenBase64 = (String) body.get("imagen");
            String slugEsperado = (String) body.get("slug");

            System.out.println("Slug esperado = " + slugEsperado);

            if (imagenBase64 == null) {
                throw new Exception("No se recibió el campo 'imagen'");
            }

            String claseDetectada = detectarClase(imagenBase64);

            System.out.println("Clase detectada por IA = " + claseDetectada);

            boolean valida = false;

            if (!claseDetectada.equals("sin-rostro")) {
                valida = claseDetectada.equals(slugEsperado);
            }

            System.out.println("¿VALIDA? = " + valida);

            resp.put("valida", valida);
            resp.put("mensaje", valida ? "Foto correcta" : "Foto incorrecta");
            resp.put("clase", claseDetectada);
            resp.put("slug", slugEsperado);
            resp.put("ok", true);

        } catch (Exception e) {

            System.out.println("ERROR CONTROLADO:");
            e.printStackTrace();

            resp.put("ok", false);
            resp.put("valida", false);
            resp.put("mensaje", e.getMessage());
        }

        return resp;
    }

    private String detectarClase(String base64) throws Exception {

        System.out.println("\n--- DECODIFICANDO IMAGEN ---");

        var img = ImageUtils.base64ToBufferedImage(base64);
        var mat = ImageUtils.bufferedImageToMat(img);

        System.out.println("Imagen convertida a Mat: " + mat.size());

        var faceRect = FaceDetector.detectFace(mat);

        if (faceRect == null) {
            System.out.println("NO SE DETECTÓ ROSTRO");
            return "sin-rostro";
        }

        System.out.println("Rostro detectado en: x=" + faceRect.x + " y=" + faceRect.y +
                " w=" + faceRect.width + " h=" + faceRect.height);

        System.out.println("--- ESTIMANDO ORIENTACIÓN ---");

        Rect roi = new Rect(faceRect.x, faceRect.y, faceRect.width, faceRect.height);
        Mat faceMat = new Mat(mat, roi);

        String orientacion = EyeOrientationDetector.detect(faceMat);

        System.out.println("ORIENTACIÓN DETECTADA = " + orientacion);

        return orientacion;
    }

}