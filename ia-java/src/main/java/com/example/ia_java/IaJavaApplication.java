package com.example.ia_java;

import nu.pattern.OpenCV;
import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;

@SpringBootApplication
public class IaJavaApplication {

    public static void main(String[] args) {
        OpenCV.loadLocally();
        System.out.println(">>> OpenCV cargado correctamente (loadLocally)");
        SpringApplication.run(IaJavaApplication.class, args);
    }
}
