@echo off
REM ============================================================================
REM APPOINTMENT REMINDER EMAIL TEST SCRIPT
REM ============================================================================
REM This script manually runs the email reminder system for testing purposes
REM ============================================================================

echo.
echo ========================================
echo   APPOINTMENT REMINDER TEST
echo ========================================
echo.

cd /d "D:\laragon\www\driving-school-system"

echo Running email reminder script...
echo.

"C:\laragon\bin\php\php-8.4.3-Win32-vs16-x64\php.exe" send_appointment_reminder.php

echo.
echo ========================================
echo.
echo Check the output above for results.
echo.
echo To view the log file:
echo   logs\appointment_reminders.log
echo.
echo Press any key to exit...
pause > nul
