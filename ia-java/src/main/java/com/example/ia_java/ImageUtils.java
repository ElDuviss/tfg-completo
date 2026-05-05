package com.example.ia_java;

import java.awt.image.BufferedImage;
import java.io.ByteArrayInputStream;
import java.util.Base64;
import javax.imageio.ImageIO;
import org.opencv.core.CvType;
import org.opencv.core.Mat;
import org.opencv.imgcodecs.Imgcodecs;

public class ImageUtils {

    public static BufferedImage base64ToBufferedImage(String base64) throws Exception {

        if (base64 == null || base64.isEmpty()) {
            throw new Exception("La imagen base64 está vacía o es null");
        }

        if (base64.startsWith("imagen_")) {
            base64 = base64.substring(7);
        }

        if (base64.contains(",")) {
            base64 = base64.substring(base64.indexOf(",") + 1);
        }

        base64 = base64.replaceAll("\\s", "");

        byte[] bytes;
        try {
            bytes = Base64.getDecoder().decode(base64);
        } catch (IllegalArgumentException e) {
            throw new Exception("Base64 inválido", e);
        }

        BufferedImage img = ImageIO.read(new ByteArrayInputStream(bytes));

        if (img == null) {
            throw new Exception("ImageIO no pudo decodificar la imagen (formato inválido)");
        }

        return img;
    }

    public static Mat bufferedImageToMat(BufferedImage bi) throws Exception {

        if (bi == null) {
            throw new Exception("BufferedImage es null");
        }

        int width = bi.getWidth();
        int height = bi.getHeight();

        Mat mat = new Mat(height, width, CvType.CV_8UC3);

        int[] data = new int[width * height];
        bi.getRGB(0, 0, width, height, data, 0, width);

        byte[] bytes = new byte[width * height * 3];

        for (int i = 0; i < data.length; i++) {
            int argb = data[i];

            bytes[i * 3]     = (byte) (argb & 0xFF);
            bytes[i * 3 + 1] = (byte) ((argb >> 8) & 0xFF);
            bytes[i * 3 + 2] = (byte) ((argb >> 16) & 0xFF);
        }

        mat.put(0, 0, bytes);
        return mat;
    }

    public static Mat base64ToMatDirect(String base64) throws Exception {

        if (base64 == null || base64.isEmpty()) {
            throw new Exception("La imagen base64 está vacía o es null");
        }

        if (base64.contains(",")) {
            base64 = base64.substring(base64.indexOf(",") + 1);
        }

        byte[] bytes;
        try {
            bytes = Base64.getDecoder().decode(base64);
        } catch (IllegalArgumentException e) {
            throw new Exception("Base64 inválido", e);
        }

        Mat buf = new Mat(1, bytes.length, CvType.CV_8U);
        buf.put(0, 0, bytes);

        Mat img = Imgcodecs.imdecode(buf, Imgcodecs.IMREAD_COLOR);

        if (img.empty()) {
            throw new Exception("OpenCV no pudo decodificar la imagen");
        }

        return img;
    }
}
