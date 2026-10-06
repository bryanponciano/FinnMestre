@echo off
title Servidor Local - Financeiro FinMestre
color 0A

echo ============================================
echo        SERVIDOR LOCAL - FINMESTRE
echo ============================================
echo.
echo Iniciando servidor em: http://localhost:8000
echo.
echo Para acessar, abra seu navegador em:
echo    http://localhost:8000
echo.
echo Pressione Ctrl+C para parar o servidor
echo ============================================
echo.

cd /d "%~dp0financeiro"
start http://localhost:8000
php -S localhost:8000

pause
