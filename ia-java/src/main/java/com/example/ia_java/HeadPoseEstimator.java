package com.example.ia_java;

import org.opencv.core.Mat;
import org.opencv.core.Rect;

public class HeadPoseEstimator {

    public static HeadPose estimate(Mat fullFrame, Rect faceRect) {

        double yaw = 0;
        double pitch = 0;
        double roll = 0;

        double cx = faceRect.x + faceRect.width / 2.0;
        double cy = faceRect.y + faceRect.height / 2.0;

        double fx = fullFrame.width() / 2.0;
        double fy = fullFrame.height() / 2.0;

        double nx = (cx - fx) / fx;
        double ny = (cy - fy) / fy;

        yaw = nx * 45.0;

        pitch = -ny * 45.0;

        roll = 0;

        return new HeadPose(yaw, pitch, roll);
    }
}