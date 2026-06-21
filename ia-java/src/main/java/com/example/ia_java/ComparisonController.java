package com.example.ia_java;

import java.awt.image.BufferedImage;
import java.io.ByteArrayInputStream;
import java.io.ByteArrayOutputStream;
import java.util.HashMap;
import java.util.Map;

import javax.imageio.ImageIO;

import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RestController;

@RestController
public class ComparisonController {

    @PostMapping("/comparacion")
    public Map<String, Object> comparar(@RequestBody Map<String, Object> datos) {

        String photoA_base64 = datos.get("photo_a").toString();
        String photoB_base64 = datos.get("photo_b").toString();
        String slug = datos.get("slug").toString();
        Object datosFotos = datos.get("datos_fotos");

        System.out.println("===== COMPARACIÓN RECIBIDA =====");
        System.out.println("Slug: " + slug);
        System.out.println("Photo A length: " + photoA_base64.length());
        System.out.println("Photo B length: " + photoB_base64.length());
        System.out.println("Datos_fotos: " + (datosFotos != null ? datosFotos.toString() : "NO RECIBIDO"));
        System.out.println("================================");

        String resultado;

        try {
            byte[] bytesA = java.util.Base64.getDecoder().decode(photoA_base64);
            byte[] bytesB = java.util.Base64.getDecoder().decode(photoB_base64);

            BufferedImage imgA = ImageIO.read(new ByteArrayInputStream(bytesA));
            BufferedImage imgB = ImageIO.read(new ByteArrayInputStream(bytesB));

            ByteArrayOutputStream pngA = new ByteArrayOutputStream();
            ByteArrayOutputStream pngB = new ByteArrayOutputStream();

            ImageIO.write(imgA, "png", pngA);
            ImageIO.write(imgB, "png", pngB);

            String pngA_base64 = java.util.Base64.getEncoder().encodeToString(pngA.toByteArray());
            String pngB_base64 = java.util.Base64.getEncoder().encodeToString(pngB.toByteArray());

            TextGenerator tg = new TextGenerator();
            resultado = tg.generarComparacionFotos(
                    slug,
                    pngA_base64,
                    pngB_base64,
                    datosFotos
            );

            System.out.println(resultado);

        } catch (Exception e) {
            e.printStackTrace();
            resultado = "Error procesando imágenes: " + e.getMessage();
        }

        Map<String, Object> response = new HashMap<>();
        response.put("status", "ok");
        response.put("texto", resultado);

        return response;
    }
}