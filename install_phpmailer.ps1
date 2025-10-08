# PHPMailer Installer Script for Windows
# This script downloads PHPMailer and sets it up for your project

Write-Host "=======================================" -ForegroundColor Cyan
Write-Host "   PHPMailer Installation Script      " -ForegroundColor Cyan
Write-Host "=======================================" -ForegroundColor Cyan
Write-Host ""

$projectRoot = "D:\laragon\www\driving-school-system"
$phpmailerDir = Join-Path $projectRoot "phpmailer"
$tempZip = Join-Path $env:TEMP "phpmailer.zip"
$tempExtract = Join-Path $env:TEMP "phpmailer-extract"

Write-Host "[1/5] Checking project directory..." -ForegroundColor Yellow
if (!(Test-Path $projectRoot)) {
    Write-Host "ERROR: Project directory not found at $projectRoot" -ForegroundColor Red
    exit 1
}
Write-Host "✓ Project directory found" -ForegroundColor Green

Write-Host ""
Write-Host "[2/5] Creating phpmailer directory..." -ForegroundColor Yellow
if (!(Test-Path $phpmailerDir)) {
    New-Item -ItemType Directory -Path $phpmailerDir | Out-Null
    Write-Host "✓ Created directory: $phpmailerDir" -ForegroundColor Green
} else {
    Write-Host "✓ Directory already exists" -ForegroundColor Green
}

Write-Host ""
Write-Host "[3/5] Downloading PHPMailer from GitHub..." -ForegroundColor Yellow
try {
    $url = "https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip"
    Invoke-WebRequest -Uri $url -OutFile $tempZip -UseBasicParsing
    Write-Host "✓ Downloaded successfully" -ForegroundColor Green
} catch {
    Write-Host "ERROR: Failed to download PHPMailer" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[4/5] Extracting files..." -ForegroundColor Yellow
try {
    if (Test-Path $tempExtract) {
        Remove-Item -Path $tempExtract -Recurse -Force
    }
    Expand-Archive -Path $tempZip -DestinationPath $tempExtract -Force
    Write-Host "✓ Extracted successfully" -ForegroundColor Green
} catch {
    Write-Host "ERROR: Failed to extract PHPMailer" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[5/5] Copying required files..." -ForegroundColor Yellow
try {
    $sourceDir = Join-Path $tempExtract "PHPMailer-master\src"
    
    $filesToCopy = @(
        "PHPMailer.php",
        "SMTP.php",
        "Exception.php"
    )
    
    foreach ($file in $filesToCopy) {
        $source = Join-Path $sourceDir $file
        $destination = Join-Path $phpmailerDir $file
        
        if (Test-Path $source) {
            Copy-Item -Path $source -Destination $destination -Force
            Write-Host "  ✓ Copied $file" -ForegroundColor Green
        } else {
            Write-Host "  ✗ File not found: $file" -ForegroundColor Red
        }
    }
} catch {
    Write-Host "ERROR: Failed to copy files" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[CLEANUP] Removing temporary files..." -ForegroundColor Yellow
try {
    if (Test-Path $tempZip) {
        Remove-Item -Path $tempZip -Force
    }
    if (Test-Path $tempExtract) {
        Remove-Item -Path $tempExtract -Recurse -Force
    }
    Write-Host "✓ Cleanup completed" -ForegroundColor Green
} catch {
    Write-Host "WARNING: Could not clean up temporary files" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=======================================" -ForegroundColor Cyan
Write-Host "   Installation Complete! ✓           " -ForegroundColor Green
Write-Host "=======================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "PHPMailer has been installed to:" -ForegroundColor White
Write-Host "  $phpmailerDir" -ForegroundColor Cyan
Write-Host ""
Write-Host "Files installed:" -ForegroundColor White
Write-Host "  ✓ PHPMailer.php" -ForegroundColor Green
Write-Host "  ✓ SMTP.php" -ForegroundColor Green
Write-Host "  ✓ Exception.php" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "  1. Run the SQL script: add_password_resets_table.sql" -ForegroundColor White
Write-Host "  2. Test the forgot password feature" -ForegroundColor White
Write-Host "  3. Check your email for the reset link" -ForegroundColor White
Write-Host ""
Write-Host "Press any key to exit..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
