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

        MatOfRect eyesMat = new MatOfRect();
        eyeDetector.detectMultiScale(gray, eyesMat);
        Rect[] eyes = eyesMat.toArray();

        if (eyes.length < 1) {
            eyesMat = new MatOfRect();
            eyeGlassesDetector.detectMultiScale(gray, eyesMat);
            eyes = eyesMat.toArray();
        }

        if (eyes.length == 0) return "sin-ojos";

        if (eyes.length == 1) {
            Rect e = eyes[0];
            double centerX = e.x + e.width / 2.0;

            if (centerX < faceMat.width() / 2.0) return "foto-lateral-izquierda";
            else return "foto-lateral-derecha";
        }

        return "foto-frontal";
    }
}