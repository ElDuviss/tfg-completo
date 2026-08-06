#!/bin/sh

echo "Iniciando n8n..."

# Arranca n8n en segundo plano
n8n start &

# Espera a que termine de crear la BD
sleep 25

echo "Importando workflows..."

n8n import:workflow \
  --separate \
  --input=/home/node/workflows

echo "Workflows importados"

wait