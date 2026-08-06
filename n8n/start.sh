#!/bin/sh

echo "Importando workflows..."

n8n import:workflow --separate --input=/home/node/workflows

echo "Iniciando n8n..."

exec n8n start