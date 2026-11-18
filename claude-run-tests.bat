@echo off
REM Roda os testes do Provador e grava o resultado em storage\logs\claude-tests.log
REM (o Claude le esse arquivo para corrigir os erros).
cd /d "%~dp0"

set "PHP=php"
where php >nul 2>nul
if errorlevel 1 (
  for /d %%D in ("C:\laragon\bin\php\php-*") do set "PHP=%%D\php.exe"
)

echo Rodando testes... aguarde.
"%PHP%" artisan test > "storage\logs\claude-tests.log" 2>&1
echo. >> "storage\logs\claude-tests.log"
echo ===== FIM (codigo %errorlevel%) ===== >> "storage\logs\claude-tests.log"
type "storage\logs\claude-tests.log"
echo.
echo Pronto. Pode fechar esta janela.
pause
