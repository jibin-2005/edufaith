@echo off
echo ========================================
echo MySQL/MariaDB Connection Fix Script
echo ========================================
echo.

echo Step 1: Stopping MySQL service...
cd C:\xampp
mysql_stop.bat
timeout /t 3

echo.
echo Step 2: Starting MySQL in safe mode...
cd C:\xampp\mysql\bin
start mysqld --skip-grant-tables

timeout /t 5

echo.
echo Step 3: Fixing user permissions...
mysql -u root -e "FLUSH PRIVILEGES; GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' IDENTIFIED BY '' WITH GRANT OPTION; GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' IDENTIFIED BY '' WITH GRANT OPTION; FLUSH PRIVILEGES;"

echo.
echo Step 4: Stopping MySQL...
taskkill /F /IM mysqld.exe

timeout /t 3

echo.
echo Step 5: Starting MySQL normally...
cd C:\xampp
mysql_start.bat

echo.
echo ========================================
echo Fix completed! Try logging in now.
echo ========================================
pause
