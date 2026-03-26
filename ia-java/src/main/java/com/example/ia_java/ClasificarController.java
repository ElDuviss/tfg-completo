package com.example.ia_java;

import java.awt.image.BufferedImage;
import java.util.HashMap;
import java.util.Map;

import org.opencv.core.Mat;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RestController;

@RestController
public class ClasificarController {

    @PostMapping("/clasificar")
    public Map<String, Object> clasificar(@RequestBody Map<String, Object> body) {

        String imagenBase64 = (String) body.get("imagen");
        String slugEsperado = (String) body.get("slug");

        // IA REAL
        String claseDetectada = detectarClase(imagenBase64);

        Map<String, Object> resp = new HashMap<>();
        resp.put("clase", claseDetectada);
        resp.put("slug", slugEsperado); // ← DEVOLVEMOS EL SLUG PARA N8N

        return resp;
    }

    private String detectarClase(String base64) {
        try {
            // 1. Base64 → BufferedImage
            BufferedImage img = ImageUtils.base64ToBufferedImage(base64);

            // 2. BufferedImage → Mat (OpenCV)
            Mat mat = ImageUtils.bufferedImageToMat(img);

            // 3. Estimar orientación con el modelo ONNX avanzado
            HeadPose pose = HeadPoseEstimator.estimate(mat);

            double yaw = pose.getYaw();
            double pitch = pose.getPitch();

            // 4. Clasificación según orientación
            if (pitch < -15) {
                return "foto-superior";
            }

            if (yaw > 20) {
                return "foto-lateral-derecha";
            }

            if (yaw < -20) {
                return "foto-lateral-izquierda";
            }

            return "foto-frontal";

        } catch (Exception e) {
            e.printStackTrace();
            return "error";
        }
    }
}