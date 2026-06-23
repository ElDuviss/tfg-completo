package com.example.ia_java;

import org.opencv.core.Core;
import org.opencv.core.CvType;
import org.opencv.core.Mat;
import org.opencv.core.Point;
import org.opencv.core.Rect;
import org.opencv.core.Size;
import org.opencv.imgproc.Imgproc;

public class FaceAligner {

    public FaceAligner() {
    }

    public Mat alinearConReferencia(Mat referencia, Mat nueva) throws Exception {

        Rect refFace = FaceDetector.detectFace(referencia);
        Rect newFace = FaceDetector.detectFace(nueva);

        if (refFace == null) {
            throw new Exception("No se detectó rostro en la imagen de referencia");
        }

        if (newFace == null) {
            throw new Exception("No se detectó rostro en la imagen nueva");
        }

        Point refCenter = new Point(
                refFace.x + refFace.width / 2.0,
                refFace.y + refFace.height / 2.0
        );

        Point newCenter = new Point(
                newFace.x + newFace.width / 2.0,
                newFace.y + newFace.height / 2.0
        );

        /*
         * Escala basada en ancho facial.
         * Más estable que usar área.
         */
        double scale =
                (double) refFace.width /
                (double) newFace.width;

        /*
         * Traslación para que los centros coincidan.
         */
        double tx =
                refCenter.x - (newCenter.x * scale);

        double ty =
                refCenter.y - (newCenter.y * scale);

        Mat transform = Mat.eye(2, 3, CvType.CV_64F);

        transform.put(0, 0, scale);
        transform.put(1, 1, scale);

        transform.put(0, 2, tx);
        transform.put(1, 2, ty);

        Mat aligned = new Mat();

        Imgproc.warpAffine(
                nueva,
                aligned,
                transform,
                referencia.size(),
                Imgproc.INTER_CUBIC,
                Core.BORDER_REPLICATE
        );

        mejorarNitidez(aligned);

        return aligned;
    }

    private void mejorarNitidez(Mat image) {

        Mat blur = new Mat();

        Imgproc.GaussianBlur(
                image,
                blur,
                new Size(0, 0),
                1.0
        );

        Core.addWeighted(
                image,
                1.15,
                blur,
                -0.15,
                0,
                image
        );
    }
}