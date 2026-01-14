@echo off
REM PHPMailer Installation Script for Windows
REM This script installs PHPMailer via Composer

echo.
echo ============================================
echo The Steeper Climb - PHPMailer Installation
echo ============================================
echo.

REM Check if composer is installed
where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] Composer is not installed or not in PATH
    echo.
    echo Please download Composer from: https://getcomposer.org/download/
    echo Then run the installer and restart your terminal.
    echo.
    pause
    exit /b 1
)

echo [OK] Composer found
echo.

REM Check if we're in the right directory
if not exist "composer.json" (
    echo [ERROR] composer.json not found
    echo Please run this script from the project root directory
    echo.
    pause
    exit /b 1
)

echo [OK] composer.json found
echo.

REM Run composer install
echo Running: composer install
echo.
composer install

REM Check if it worked
if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Composer install failed
    echo Check the error messages above
    echo.
    pause
    exit /b 1
)

REM Verify vendor folder
if exist "vendor\autoload.php" (
    echo.
    echo ============================================
    echo [SUCCESS] PHPMailer installed successfully!
    echo ============================================
    echo.
    echo You can now use the Mailer class to send emails
    echo.
    echo Files created:
    echo - vendor/
    echo - vendor/phpmailer/
    echo - vendor/autoload.php
    echo.
    echo Next steps:
    echo 1. Create a test organization from the Admin panel
    echo 2. Check that the welcome email is sent
    echo 3. Verify email in recipient's inbox
    echo.
) else (
    echo.
    echo [ERROR] Installation may have failed
    echo vendor/autoload.php not found
    echo.
    pause
    exit /b 1
)

pause
