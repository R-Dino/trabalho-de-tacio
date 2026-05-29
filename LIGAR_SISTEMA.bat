@echo off
title Servidor do Almoxarifado
color 0A
echo ========================================================
echo        SISTEMA DE ALMOXARIFADO - SERVIDOR DE TESTE
echo ========================================================
echo.
echo Iniciando o servidor na porta 8000...
echo.
echo NAVEGUE PARA: http://localhost:8000
echo.
echo Para desligar o servidor, basta fechar esta janela preta.
echo.
C:\xampp\php\php.exe -S localhost:8000
pause
