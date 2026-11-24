@echo off
cd /d "C:\Program Files\MariaDB 13.0"
start "MariaDB" /MIN "C:\Program Files\MariaDB 13.0\bin\mysqld.exe" --defaults-file="C:\Program Files\MariaDB 13.0\data\my.ini"
