#!/bin/bash
# Script de migración automática para Clever Cloud
# Se ejecuta después del deployment

echo "Iniciando migración de base de datos..."

# Esperar MySQL
sleep 5

# Ejecutar schema
mysql -h "$MYSQL_ADDON_HOST" -u "$MYSQL_ADDON_USER" -p"$MYSQL_ADDON_PASSWORD" "$MYSQL_ADDON_DB" < database/02_nueva_estructura.sql

echo "Migración completada"
