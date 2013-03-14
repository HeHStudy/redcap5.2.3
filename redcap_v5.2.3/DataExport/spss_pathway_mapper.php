<?php

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Logging
log_event("","","MANAGE",$project_id,"project_id = $project_id","Download SPSS Pathway Mapper file");

// Download as file
error_reporting(0);
header('Pragma: anytextexeptno-cache', true);
header("Content-type: application/bat");

header("Content-Disposition: attachment; filename=spss_pathway_mapper.bat");
?>
for %%x in (*.SPS) do (
	type "%%x" | find /v "FILE HANDLE data1 NAME=" > tmp.dat
	call :sub "%%x"
	del "%%x"
	copy /b insert.dat+tmp.dat "%%x"
)
del insert.dat tmp.dat	
@ECHO off
cls
ECHO.
ECHO.
ECHO   STEP #1: Pathway mapping is COMPLETE!
ECHO.
ECHO.
ECHO   NOW... 
ECHO.
ECHO   STEP #2: Double-click the *.SPS file, which will open SPSS.
ECHO.
ECHO   STEP #3: Once SPSS has opened, choose Run-^>All from the top menu options.
ECHO.
ECHO   (Press ENTER to close this window) 
set /p var=
goto :EOF
:sub
	set fn0=%~n1
	set fn1=%fn0:_SPSS_2=_DATA_NOHDRS_2%
	echo FILE HANDLE data1 NAME='%~dp0%fn1%.CSV'  LRECL=90000. > insert.dat