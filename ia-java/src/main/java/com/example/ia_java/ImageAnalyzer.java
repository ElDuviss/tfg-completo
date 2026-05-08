package com.example.ia_java;

import java.util.*;
import org.opencv.core.*;
import org.opencv.imgproc.Imgproc;

public class ImageAnalyzer {

    public Map<String, Object> analizarImagen(String base64) {
        Map<String, Object> r = new HashMap<>();

        Mat img;
        try {
            img = ImageUtils.base64ToMatDirect(base64);
        } catch (Exception e) {
            r.put("error", "No se pudo decodificar la imagen: " + e.getMessage());
            return r;
        }

        Mat gray = new Mat();
        Imgproc.cvtColor(img, gray, Imgproc.COLOR_BGR2GRAY);
        Imgproc.GaussianBlur(gray, gray, new Size(5,5), 0);

        double densidad = calcularDensidad(gray);
        double brillo = Core.mean(gray).val[0];
        double rojez = calcularRojez(img);
        double contraste = calcularContraste(gray);
        String color = detectarColorCabello(img);

        r.put("densidad", densidad);
        r.put("brillo", brillo);
        r.put("rojez", rojez);
        r.put("contraste", contraste);
        r.put("color", color);

        return r;
    }

    private double calcularDensidad(Mat gray) {
        Mat bin = new Mat();
        Imgproc.threshold(gray, bin, 90, 255, Imgproc.THRESH_BINARY_INV);
        double negros = Core.countNonZero(bin);
        double total = gray.rows() * gray.cols();
        return total == 0 ? 0 : negros / total;
    }

    private double calcularRojez(Mat img) {
        List<Mat> canales = new ArrayList<>();
        Core.split(img, canales);
        Mat r = canales.get(2);
        return Core.mean(r).val[0];
    }

    private double calcularContraste(Mat gray) {
        Mat lap = new Mat();
        Imgproc.Laplacian(gray, lap, CvType.CV_64F);
        MatOfDouble mean = new MatOfDouble();
        MatOfDouble std = new MatOfDouble();
        Core.meanStdDev(lap, mean, std);
        return std.get(0,0)[0];
    }

    private String detectarColorCabello(Mat img) {

        Mat hsv = new Mat();
        Imgproc.cvtColor(img, hsv, Imgproc.COLOR_BGR2HSV);

        List<Mat> canales = new ArrayList<>();
        Core.split(hsv, canales);
        Mat v = canales.get(2);

        Mat mask = new Mat();
        Imgproc.threshold(v, mask, 120, 255, Imgproc.THRESH_BINARY_INV);

        Scalar media = Core.mean(img, mask);

        double r = media.val[2];
        double g = media.val[1];
        double b = media.val[0];

        if (r < 80 && g < 70 && b < 70) return "negro";
        if (r < 120 && g < 100) return "castaño oscuro";
        if (r < 170 && g < 150) return "castaño claro";
        if (r > 180 && g > 160) return "rubio";
        if (r > 160 && g < 120) return "pelirrojo";

        Mat color = new Mat(1, 1, CvType.CV_8UC3, new Scalar(b, g, r));
        Mat colorHSV = new Mat();
        Imgproc.cvtColor(color, colorHSV, Imgproc.COLOR_BGR2HSV);

        double H = colorHSV.get(0, 0)[0];
        double S = colorHSV.get(0, 0)[1];
        double V = colorHSV.get(0, 0)[2];

        if (V < 40) return "negro";
        if (S < 30 && V > 180) return "blanco";
        if (S < 30 && V > 120) return "gris";

        if (H < 20 && V < 90) return "castaño oscuro";
        if (H < 25 && V < 130) return "castaño medio";
        if (H < 30 && V < 160) return "castaño claro";

        if (H < 22 && S > 80 && V > 120) return "castaño chocolate";
        if (H < 28 && S > 60 && V > 150) return "castaño miel";

        if (H >= 20 && H < 35 && V > 160 && S < 80) return "rubio ceniza";
        if (H >= 20 && H < 35 && V > 160 && S >= 80) return "rubio dorado";
        if (H >= 20 && H < 35 && V > 130) return "rubio";
        if (H >= 20 && H < 35 && V <= 130) return "rubio oscuro";

        if (H < 15 && S > 150) return "rojo intenso";
        if (H < 20 && S > 120) return "pelirrojo";
        if (H < 20 && S > 80) return "cobrizo";

        if (H >= 35 && H < 85) return "verde esmeralda";
        if (H >= 85 && H < 110) return "azul marino";
        if (H >= 110 && H < 130) return "azul pastel";
        if (H >= 130 && H < 150) return "morado";
        if (H >= 150 && H < 165) return "rosa fucsia";
        if (H >= 165 && H < 175) return "rosa pastel";

        return "indeterminado";
    }

}