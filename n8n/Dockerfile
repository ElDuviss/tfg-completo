FROM docker.io/n8nio/n8n:latest

ENV N8N_HOST=0.0.0.0
ENV N8N_PORT=5678
ENV N8N_PROTOCOL=https
ENV N8N_SECURE_COOKIE=false

COPY --chown=node:node workflows.json /home/node/.n8n/workflows.json

EXPOSE 5678