@echo off
rem La Sem Cakes - start the website on this computer and open it in the browser.
rem Keep this window open while you browse; close it to stop the site.
title La Sem Cakes - local website
cd /d "%~dp0"

set PORT=8081
set URL=http://localhost:%PORT%

rem Already running (for example from Claude's preview)? Just open it.
powershell -NoProfile -Command "try { Invoke-WebRequest -UseBasicParsing -TimeoutSec 2 '%URL%' | Out-Null; exit 0 } catch { exit 1 }"
if %errorlevel%==0 (
  echo The website is already running at %URL%
  start "" "%URL%"
  timeout /t 3 >nul
  exit /b
)

rem Find PHP: on the PATH, the WinGet install, or XAMPP.
set PHP=
where php >nul 2>nul && set PHP=php
if not defined PHP (
  for /d %%D in ("%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.*") do if exist "%%D\php.exe" set PHP="%%D\php.exe"
)
if not defined PHP if exist "C:\xampp\php\php.exe" set PHP="C:\xampp\php\php.exe"
if not defined PHP (
  echo PHP was not found. Install it with:  winget install PHP.PHP.8.4
  pause
  exit /b 1
)

echo Starting La Sem Cakes at %URL%
echo Keep this window open while you browse. Close it to stop the site.
start "" "%URL%"
%PHP% -d max_input_vars=5000 -S localhost:%PORT% -t website website/router.php
pause
