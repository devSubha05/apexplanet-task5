On Error Resume Next
Set WshShell = CreateObject("WScript.Shell")
WshShell.CurrentDirectory = "C:\Program Files\MariaDB 13.0"
WshShell.Run """C:\Program Files\MariaDB 13.0\bin\mysqld.exe"" --defaults-file=""C:\Program Files\MariaDB 13.0\data\my.ini""", 0, False
