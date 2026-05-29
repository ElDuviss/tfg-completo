package com.example.ia_java;

import org.opencv.core.Mat;
import org.opencv.core.Rect;
import org.opencv.core.Scalar;
import org.opencv.core.Size;
import org.opencv.dnn.Dnn;
import org.opencv.dnn.Net;

public class FaceDetector {

    private static Net net;

    static {
        try {
            String proto = "/app/models/deploy.prototxt";
            String model = "/app/models/res10_300x300_ssd_iter_140000_fp16.caffemodel";

            net = Dnn.readNetFromCaffe(proto, model);

            System.out.println("Detector de rostro DNN cargado correctamente");

        } catch (Exception e) {
            e.printStackTrace();
            throw new RuntimeException("Error cargando detector de rostro DNN", e);
        }
    }

    public static Rect detectFace(Mat frame) {

        Mat blob = Dnn.blobFromImage(
                frame,
                1.0,
                new Size(300, 300),
                new Scalar(104, 177, 123),
                false,
                false
        );

        net.setInput(blob);
        Mat detections = net.forward();

        detections = detections.reshape(1, (int) detections.size(2));

        for (int i = 0; i < detections.rows(); i++) {

            double confidence = detections.get(i, 2)[0];

            if (confidence > 0.5) {

                int x1 = (int) (detections.get(i, 3)[0] * frame.cols());
                int y1 = (int) (detections.get(i, 4)[0] * frame.rows());
                int x2 = (int) (detections.get(i, 5)[0] * frame.cols());
                int y2 = (int) (detections.get(i, 6)[0] * frame.rows());

                return new Rect(x1, y1, x2 - x1, y2 - y1);
            }
        }

        return null;
    }
}