@ECHO off

SETLOCAL ENABLEDELAYEDEXPANSION
ECHO BCS Metadata Generator by zbx1425.
ECHO.
IF NOT EXIST C:\php\php.exe (
	COLOR 0C
	ECHO PHP is not installed.
	ECHO The metadata generator requires PHP to run normally.
    ECHO Get a copy at https://php.net and install it at C:\php.
	PAUSE >NUL
	EXIT
)
IF NOT EXIST C:\php\php.ini COPY C:\php\php.ini-development C:\php\php.ini >NUL
FIND ";extension=mbstring" C:\php\php.ini 2>NUL >NUL
SET TEXTLEVEL=%ERRORLEVEL%
FIND ";extension=gd2" C:\php\php.ini 2>NUL >NUL
SET /A TEXTLEVEL=%TEXTLEVEL%+%ERRORLEVEL%
IF %TEXTLEVEL% LSS 2 (
	ECHO Only %TEXTLEVEL% of 2 required extensions enabled. Setting up PHP...
	ERASE C:\php\php.ini.tmp 2>NUL >NUL
	FOR /F "eol=# tokens=*" %%i IN (C:\php\php.ini) DO (
		IF "%%i"=="" (
			ECHO.
		) ELSE (
			IF "%%i"==";extension=mbstring" (
				ECHO extension=mbstring
			) ELSE (
                IF "%%i"==";extension=gd2" (
                    ECHO extension=gd2
                ) ELSE (
                    ECHO %%i
                )
			)
		)
	)>>C:\php\php.ini.tmp
	ERASE C:\php\php.ini >NUL
	RENAME C:\php\php.ini.tmp php.ini >NUL
	ECHO PHP configuration completed.
	ECHO.
)

C:\php\php.exe metadata.php cli
ECHO Done.
PAUSE >NUL