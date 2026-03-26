package com.example.ia_java;

import java.awt.image.BufferedImage;
import java.io.ByteArrayInputStream;
import java.util.Base64;

import javax.imageio.ImageIO;

import org.opencv.core.CvType;
import org.opencv.core.Mat;

public class ImageUtils {

    public static BufferedImage base64ToBufferedImage(String base64) throws Exception {
        byte[] bytes = Base64.getDecoder().decode(base64);
        return ImageIO.read(new ByteArrayInputStream(bytes));
    }

    public static Mat bufferedImageToMat(BufferedImage bi) {
        int width = bi.getWidth();
        int height = bi.getHeight();
        Mat mat = new Mat(height, width, CvType.CV_8UC3);
        int[] data = new int[width * height];
        bi.getRGB(0, 0, width, height, data, 0, width);
        byte[] bytes = new byte[width * height * 3];

        for (int i = 0; i < data.length; i++) {
            int argb = data[i];
            bytes[i * 3]     = (byte) ((argb >> 16) & 0xFF); // R
            bytes[i * 3 + 1] = (byte) ((argb >> 8) & 0xFF);  // G
            bytes[i * 3 + 2] = (byte) (argb & 0xFF);         // B
        }

        mat.put(0, 0, bytes);
        return mat;
    }
}
