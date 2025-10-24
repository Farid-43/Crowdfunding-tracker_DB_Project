@echo off
echo ========================================
echo   CF Tracker - Database Setup Script
echo ========================================
echo.
echo This script will help you set up the database.
echo Make sure XAMPP MySQL is running!
echo.
pause

REM Set XAMPP MySQL path (adjust if your XAMPP is installed elsewhere)
set MYSQL_PATH=F:\xampp\mysql\bin\mysql.exe

REM Check if mysql.exe exists
if not exist "%MYSQL_PATH%" (
    echo.
    echo ERROR: MySQL not found at %MYSQL_PATH%
    echo.
    echo Please update the MYSQL_PATH in this script to match your XAMPP installation.
    echo Common locations:
    echo   C:\xampp\mysql\bin\mysql.exe
    echo   D:\xampp\mysql\bin\mysql.exe
    echo   F:\xampp\mysql\bin\mysql.exe
    echo.
    pause
    exit /b 1
)

echo.
echo Creating database CF_Tracker...
"%MYSQL_PATH%" -u root -e "CREATE DATABASE IF NOT EXISTS CF_Tracker;"
if %errorlevel% neq 0 (
    echo.
    echo ERROR: Could not create database!
    echo Make sure MySQL is running in XAMPP Control Panel.
    echo.
    pause
    exit /b 1
)

echo.
echo Importing schema...
"%MYSQL_PATH%" -u root CF_Tracker < database\schema.sql
if %errorlevel% neq 0 (
    echo.
    echo ERROR: Could not import schema!
    echo.
    pause
    exit /b 1
)

echo.
echo Importing sample data...
"%MYSQL_PATH%" -u root CF_Tracker < database\sample_data.sql
if %errorlevel% neq 0 (
    echo.
    echo ERROR: Could not import sample data!
    echo.
    pause
    exit /b 1
)

echo.
echo ========================================
echo   SUCCESS! Database setup complete!
echo ========================================
echo.
echo Next steps:
echo 1. Make sure XAMPP Apache is running
echo 2. Open browser and go to:
echo    http://localhost/Crowdfunding-tracker_DB_Project/
echo.
pause
