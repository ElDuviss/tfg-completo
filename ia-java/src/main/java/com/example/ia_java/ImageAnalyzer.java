package com.example.ia_java;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import org.opencv.core.Core;
import org.opencv.core.CvType;
import org.opencv.core.Mat;
import org.opencv.core.MatOfDouble;
import org.opencv.core.Rect;
import org.opencv.core.Scalar;
import org.opencv.core.Size;
import org.opencv.imgproc.Imgproc;

public class ImageAnalyzer {
    public Map<String, Object> analizarImagen(String base64) {

        Map<String, Object> r = new HashMap<>();

        try {

            Mat img = ImageUtils.base64ToMatDirect(base64);

            Rect face = FaceDetector.detectFace(img);

            if (face == null) {
                r.put("error", "No se detectó rostro");
                return r;
            }

            Rect hairRegion = obtenerRegionCabello(face, img);

            Mat hair = new Mat(img, hairRegion);

            Mat hairMask = generarMascaraCabello(hair);

            double densidad = calcularDensidad(hairMask);

            double brillo = calcularBrillo(hair, hairMask);

            double rojez = calcularRojez(hair, hairMask);

            double contraste = calcularContraste(hair, hairMask);

            String color = detectarColorCabello(hair, hairMask);

            r.put("densidad", densidad);
            r.put("brillo", brillo);
            r.put("rojez", rojez);
            r.put("contraste", contraste);
            r.put("color", color);

            return r;

        } catch (Exception e) {

            r.clear();
            r.put("error", e.getMessage());

            return r;
        }
    }

    private Rect obtenerRegionCabello(Rect face, Mat img) {

        int hairHeight = (int)(face.height * 0.8);

        int x = Math.max(face.x, 0);

        int y = Math.max(face.y - hairHeight, 0);

        int width = Math.min(face.width, img.cols() - x);

        int height = Math.min(hairHeight, img.rows() - y);

        return new Rect(x, y, width, height);
    }

    private Mat generarMascaraCabello(Mat hair) {

        Mat hsv = new Mat();

        Imgproc.cvtColor(hair, hsv, Imgproc.COLOR_BGR2HSV);

        Mat maskOscura = new Mat();

        Core.inRange(
                hsv,
                new Scalar(0, 20, 0),
                new Scalar(180, 255, 180),
                maskOscura
        );

        Mat kernel = Imgproc.getStructuringElement(
                Imgproc.MORPH_ELLIPSE,
                new Size(7,7)
        );

        Imgproc.morphologyEx(
                maskOscura,
                maskOscura,
                Imgproc.MORPH_CLOSE,
                kernel
        );

        Imgproc.morphologyEx(
                maskOscura,
                maskOscura,
                Imgproc.MORPH_OPEN,
                kernel
        );

        return maskOscura;
    }

    private double calcularDensidad(Mat mask) {

        double hairPixels = Core.countNonZero(mask);

        double totalPixels = mask.rows() * mask.cols();

        if (totalPixels == 0) {
            return 0;
        }

        return hairPixels / totalPixels;
    }

    private double calcularBrillo(Mat hair, Mat mask) {

        Mat gray = new Mat();

        Imgproc.cvtColor(hair, gray, Imgproc.COLOR_BGR2GRAY);

        return Core.mean(gray, mask).val[0];
    }

    private double calcularRojez(Mat hair, Mat mask) {

        List<Mat> canales = new ArrayList<>();

        Core.split(hair, canales);

        double rojo = Core.mean(canales.get(2), mask).val[0];

        double verde = Core.mean(canales.get(1), mask).val[0];

        return rojo - verde;
    }

    private double calcularContraste(Mat hair, Mat mask) {

        Mat gray = new Mat();

        Imgproc.cvtColor(hair, gray, Imgproc.COLOR_BGR2GRAY);

        Mat lap = new Mat();

        Imgproc.Laplacian(
                gray,
                lap,
                CvType.CV_64F
        );

        MatOfDouble mean = new MatOfDouble();

        MatOfDouble std = new MatOfDouble();

        Core.meanStdDev(
                lap,
                mean,
                std,
                mask
        );

        return std.get(0,0)[0];
    }

    private String detectarColorCabello(Mat hair, Mat mask) {

        Mat hsv = new Mat();

        Imgproc.cvtColor(
                hair,
                hsv,
                Imgproc.COLOR_BGR2HSV
        );

        Scalar media = Core.mean(
                hsv,
                mask
        );

        double H = media.val[0];
        double S = media.val[1];
        double V = media.val[2];

        if (S < 25) {
            if (V < 45)
                return "negro";
            if (V < 120)
                return "gris";
            return "blanco";
        }

        if (V < 45)
            return "negro";

        if (V < 75)
            return "castaño muy oscuro";

        if (V < 100)
            return "castaño oscuro";

        if (V < 140)
            return "castaño";

        if (V < 170 && H >= 10 && H <= 25)
            return "castaño claro";

        if ((H >= 0 && H <= 10) && S > 90)
            return "pelirrojo";

        if ((H > 10 && H <= 18) && S > 100)
            return "cobrizo";

        if (V > 200 && S < 40)
            return "rubio platino";

        if (V > 180 && S < 60)
            return "rubio ceniza";

        if (V > 170)
            return "rubio";

        if (H >= 20 && H < 35)
            return "amarillo";

        if (H >= 35 && H < 50)
            return "dorado";

        if (H >= 50 && H < 80)
            return "verde";

        if (H >= 80 && H < 100)
            return "turquesa";

        if (H >= 100 && H < 130)
            return "azul";

        if (H >= 130 && H < 145)
            return "violeta";

        if (H >= 145 && H < 165)
            return "morado";

        if (H >= 165 || H < 5)
            return "rosa";

        return "indeterminado";
    }
}