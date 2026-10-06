@echo off
cd /d "%~dp0"
python tools\sync.py down %*
echo.
pause
