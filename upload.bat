@echo off
cd /d "%~dp0"
python tools\sync.py up %*
echo.
pause
