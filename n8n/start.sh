#!/bin/sh

echo "Iniciando n8n..."

exec n8n start --host=0.0.0.0 --port=$PORT