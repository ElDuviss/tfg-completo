package com.example.ia_java;

import java.io.InputStream;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.StandardCopyOption;

import org.opencv.core.Mat;
import org.opencv.core.Size;
import org.opencv.dnn.Dnn;
import org.opencv.dnn.Net;

public class HeadPoseEstimator {

    private static Net net;

    static {
        try {
            System.loadLibrary(org.opencv.core.Core.NATIVE_LIBRARY_NAME);

            // Cargar el modelo ONNX desde resources
            InputStream is = HeadPoseEstimator.class.getResourceAsStream("/models/headpose_advanced.onnx");
            if (is == null) {
                throw new RuntimeException("No se encontró el modelo headpose_advanced.onnx en resources/models");
            }

            Path temp = Files.createTempFile("headpose_advanced", ".onnx");
            Files.copy(is, temp, StandardCopyOption.REPLACE_EXISTING);
            net = Dnn.readNetFromONNX(temp.toString());
        } catch (Exception e) {
            e.printStackTrace();
            throw new RuntimeException("Error cargando el modelo de head pose", e);
        }
    }

    public static HeadPose estimate(Mat faceMat) {
        Mat inputBlob = Dnn.blobFromImage(
                faceMat,
                1.0 / 255.0,
                new Size(224, 224),
                new org.opencv.core.Scalar(0, 0, 0),
                true,
                false
        );

        net.setInput(inputBlob);
        Mat output = net.forward(); // asumimos salida [1,3] = [yaw, pitch, roll]

        double yaw   = output.get(0, 0)[0];
        double pitch = output.get(0, 1)[0];
        double roll  = output.get(0, 2)[0];

        return new HeadPose(yaw, pitch, roll);
    }
}
