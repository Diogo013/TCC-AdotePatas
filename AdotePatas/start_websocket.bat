@echo off
chcp 65001
title Servidor WebSocket - Adote Patas
echo ========================================
echo    SERVIDOR WEBSOCKET - ADOTE PATAS
echo ========================================
echo.
echo Iniciando servidor na porta 8080...
echo Pressione CTRL+C para parar o servidor
echo.
php websocket_server.php
pause