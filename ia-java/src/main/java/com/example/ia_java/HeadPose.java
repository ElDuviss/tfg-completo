package com.example.ia_java;

public class HeadPose {
    private final double yaw;
    private final double pitch;
    private final double roll;

    public HeadPose(double yaw, double pitch, double roll) {
        this.yaw = yaw;
        this.pitch = pitch;
        this.roll = roll;
    }

    public double getYaw() {
        return yaw;
    }

    public double getPitch() {
        return pitch;
    }

    public double getRoll() {
        return roll;
    }
}
