package com.example.ia_java;

import java.io.File;
import java.io.FileOutputStream;
import java.io.InputStream;
import java.util.ArrayList;
import java.util.List;

import org.opencv.core.Core;
import org.opencv.core.CvType;
import org.opencv.core.Mat;
import org.opencv.core.MatOfRect;
import org.opencv.core.Point;
import org.opencv.core.Rect;
import org.opencv.core.Scalar;
import org.opencv.core.Size;
import org.opencv.imgproc.Imgproc;
import org.opencv.objdetect.CascadeClassifier;

public class FaceAligner {

    private CascadeClassifier eyeDetector;

    public FaceAligner() {
        try {
            String eyeCascadePath = extract("haarcascades/haarcascade_eye.xml");
            eyeDetector = new CascadeClassifier(eyeCascadePath);

            if (eyeDetector.empty()) {
                throw new RuntimeException("❌ No se pudo cargar el cascade de ojos");
            }

            System.out.println("Detector de ojos cargado correctamente");

        } catch (Exception e) {
            e.printStackTrace();
            throw new RuntimeException("Error cargando detector de ojos", e);
        }
    }

    private String extract(String path) throws Exception {
        InputStream is = getClass().getClassLoader().getResourceAsStream(path);
        if (is == null) throw new RuntimeException("❌ No se encontró el recurso: " + path);

        File tmp = File.createTempFile("cascade_", ".xml");
        tmp.deleteOnExit();

        try (FileOutputStream os = new FileOutputStream(tmp)) {
            is.transferTo(os);
        }

        return tmp.getAbsolutePath();
    }

    public Mat alinearConReferencia(Mat ref, Mat nueva) throws Exception {

        // 1. Detectar rostro con tu DNN
        Rect refFace = FaceDetector.detectFace(ref);
        Rect newFace = FaceDetector.detectFace(nueva);

        if (refFace == null || newFace == null) {
            throw new Exception("❌ No se detectó rostro en FaceAligner");
        }

        double scale = (double) refFace.height / newFace.height;

        Mat newGray = new Mat();
        Imgproc.cvtColor(nueva, newGray, Imgproc.COLOR_BGR2GRAY);
        MatOfRect eyes = new MatOfRect();
        eyeDetector.detectMultiScale(newGray, eyes);

        double angle = 0;
        Rect[] arr = eyes.toArray();
        if (arr.length >= 2) {
            Point leftEye = new Point(arr[0].x + arr[0].width / 2.0, arr[0].y + arr[0].height / 2.0);
            Point rightEye = new Point(arr[1].x + arr[1].width / 2.0, arr[1].y + arr[1].height / 2.0);
            double dx = rightEye.x - leftEye.x;
            double dy = rightEye.y - leftEye.y;
            angle = Math.toDegrees(Math.atan2(dy, dx));
        }

        Point center = new Point(nueva.width() / 2.0, nueva.height() / 2.0);
        Mat rot = Imgproc.getRotationMatrix2D(center, angle, scale);

        Mat alignedFull = new Mat();
        Imgproc.warpAffine(nueva, alignedFull, rot, nueva.size(), Imgproc.INTER_CUBIC);

        int dx = (int) ((refFace.x + refFace.width / 2.0) - (newFace.x + newFace.width / 2.0));
        int dy = (int) ((refFace.y + refFace.height / 2.0) - (newFace.y + newFace.height / 2.0));

        Mat translation = Mat.eye(2, 3, CvType.CV_64F);
        translation.put(0, 2, dx);
        translation.put(1, 2, dy);

        Mat finalAligned = new Mat();
        Imgproc.warpAffine(alignedFull, finalAligned, translation, nueva.size(), Imgproc.INTER_CUBIC);

        Mat mask = new Mat();
        Core.inRange(finalAligned, new Scalar(0, 0, 0), new Scalar(0, 0, 0), mask);
        Core.bitwise_not(mask, mask);
        Rect roi = Imgproc.boundingRect(mask);

        Mat cropped = new Mat(finalAligned, roi);

        normalizarIluminacion(cropped);

        mejorarNitidez(cropped);

        return cropped;
    }

    private void normalizarIluminacion(Mat img) {
        Mat lab = new Mat();
        Imgproc.cvtColor(img, lab, Imgproc.COLOR_BGR2Lab);

        List<Mat> labPlanes = new ArrayList<>();
        Core.split(lab, labPlanes);

        Imgproc.equalizeHist(labPlanes.get(0), labPlanes.get(0));

        Core.merge(labPlanes, lab);
        Imgproc.cvtColor(lab, img, Imgproc.COLOR_Lab2BGR);

        Mat temp = new Mat();
        Imgproc.bilateralFilter(img, temp, 5, 75, 75);
        temp.copyTo(img);
    }

    private void mejorarNitidez(Mat img) {
        Mat blurred = new Mat();
        Imgproc.GaussianBlur(img, blurred, new Size(0, 0), 3);
        Core.addWeighted(img, 1.5, blurred, -0.5, 0, img);
    }
}