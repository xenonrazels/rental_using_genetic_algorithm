@echo off
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :80') do (
    taskkill /F /PID %%a
)
pause
