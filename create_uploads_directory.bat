@echo off
echo ========================================
echo Creating GCash Payment Proofs Directory
echo ========================================
echo.

cd /d "D:\laragon\www\driving-school-system"

echo Creating uploads directory structure...
if not exist "uploads\" mkdir "uploads"
if not exist "uploads\payment_proofs\" mkdir "uploads\payment_proofs"

echo.
echo ✓ Directory created: uploads\payment_proofs\
echo.
echo ========================================
echo Setup Complete!
echo ========================================
echo.
echo Payment proof screenshots will be saved to:
echo D:\laragon\www\driving-school-system\uploads\payment_proofs\
echo.
pause
