@ECHO off
REM 您的网站地址, 结尾不带斜杠
SET SERVER_NAME=https://zbx1425.gitee.io/bcs-src

SETLOCAL ENABLEDELAYEDEXPANSION
ECHO BCS Metadata Generator by zbx1425.
ECHO.
IF NOT EXIST C:\php\php.exe (
	COLOR 0C
	ECHO PHP Not installed!
	ECHO The metadata generator requires PHP to run normally.
	PAUSE >NUL
	EXIT
)
IF NOT EXIST C:\php\php.ini COPY C:\php\php.ini-development C:\php\php.ini >NUL
FIND ";extension=mbstring" C:\php\php.ini 2>NUL >NUL
IF NOT ERRORLEVEL 1 (
	ECHO PHP mbsting extension not enabled. Setting up PHP...
	ERASE C:\php\php.ini.tmp 2>NUL >NUL
	FOR /F "eol=# tokens=*" %%i IN (C:\php\php.ini) DO (
		IF "%%i"=="" (
			ECHO.
		) ELSE (
			IF "%%i"==";extension=mbstring" (
				ECHO extension=mbstring
			) ELSE (
				ECHO %%i
			)
		)
	)>>C:\php\php.ini.tmp
	ERASE C:\php\php.ini >NUL
	RENAME C:\php\php.ini.tmp php.ini >NUL
	ECHO PHP configuration completed.
	ECHO.
)

C:\php\php.exe metadata-cli.php
ECHO Done.
PAUSE >NUL