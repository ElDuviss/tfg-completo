package com.example.ia_java;

import java.io.File;
import java.io.FileOutputStream;
import java.io.InputStream;

import org.opencv.core.Mat;
import org.opencv.core.MatOfRect;
import org.opencv.core.Rect;
import org.opencv.imgproc.Imgproc;
import org.opencv.objdetect.CascadeClassifier;

public class EyeOrientationDetector {

    private static final CascadeClassifier eyeDetector;
    private static final CascadeClassifier eyeGlassesDetector;

    static {
        eyeDetector = new CascadeClassifier(extract("haarcascades/haarcascade_eye.xml"));
        eyeGlassesDetector = new CascadeClassifier(extract("haarcascades/haarcascade_eye_tree_eyeglasses.xml"));
    }

    private static String extract(String path) {
        try {
            InputStream is = EyeOrientationDetector.class.getClassLoader().getResourceAsStream(path);
            if (is == null) return "";
            File tmp = File.createTempFile("cascade_", ".xml");
            tmp.deleteOnExit();
            try (FileOutputStream os = new FileOutputStream(tmp)) { is.transferTo(os); }
            return tmp.getAbsolutePath();
        } catch (Exception e) { return ""; }
    }

    public static String detect(Mat faceMat) {

        Mat gray = new Mat();
        Imgproc.cvtColor(faceMat, gray, Imgproc.COLOR_BGR2GRAY);

        // Mejora el contraste
        Imgproc.equalizeHist(gray, gray);

        MatOfRect eyesMat = new MatOfRect();

        eyeDetector.detectMultiScale(
                gray,
                eyesMat,
                1.1,
                3,
                0,
                new org.opencv.core.Size(20,20),
                new org.opencv.core.Size()
        );

        Rect[] eyes = eyesMat.toArray();

        if (eyes.length == 0) {

            eyesMat = new MatOfRect();

            eyeGlassesDetector.detectMultiScale(
                    gray,
                    eyesMat,
                    1.1,
                    3,
                    0,
                    new org.opencv.core.Size(20,20),
                    new org.opencv.core.Size()
            );

            eyes = eyesMat.toArray();
        }

        // ---------- FOTO SUPERIOR ----------
        if (eyes.length == 0) {

            // La mitad superior suele contener mucho más pelo que piel
            Rect upperRect = new Rect(
                    0,
                    0,
                    faceMat.width(),
                    faceMat.height() / 2
            );

            Mat upper = new Mat(gray, upperRect);

            double media = org.opencv.core.Core.mean(upper).val[0];

            // Cabello oscuro + ausencia de ojos
            if (media < 90) {
                return "foto-superior";
            }

            // Imagen muy horizontal
            double ratio = (double) faceMat.width() / faceMat.height();

            if (ratio > 1.15) {
                return "foto-superior";
            }

            // Si no hay ojos, por defecto también asumimos superior
            return "foto-superior";
        }

        // ---------- FOTO LATERAL ----------
        if (eyes.length == 1) {

            Rect e = eyes[0];

            double centerX = e.x + e.width / 2.0;

            if (centerX < faceMat.width() / 2.0)
                return "foto-lateral-izquierda";

            return "foto-lateral-derecha";
        }

        // ---------- FOTO FRONTAL ----------
        return "foto-frontal";
    }
}