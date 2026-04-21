FROM eclipse-temurin:21-jdk

WORKDIR /app

COPY target/ia-java-0.0.1-SNAPSHOT.jar app.jar

COPY docker/models /app/models

EXPOSE 8080

ENTRYPOINT ["java","-jar","app.jar"]