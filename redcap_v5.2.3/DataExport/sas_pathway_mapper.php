<?php

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Logging
log_event("","","MANAGE",$project_id,"project_id = $project_id","Download SAS Pathway Mapper file");

// Download as file
error_reporting(0);
header('Pragma: anytextexeptno-cache', true);
header("Content-type: application/bat");

header("Content-Disposition: attachment; filename=sas_pathway_mapper.bat");
?>
@echo off
REM -- Prepare the Command Processor --
SETLOCAL ENABLEEXTENSIONS
SETLOCAL DISABLEDELAYEDEXPANSION
for %%x in (*.SAS) do (
	type "%%x" | find /v "%%macro removeOldFile(bye);" > tmp1.dat
	call :sub "%%x"
	del "%%x"
	type tmp1.dat | find /v "infile '" > tmp2.dat
	echo data redcap.REDCAP; > tmp3.dat
	echo set REDCAP; >> tmp3.dat
	echo run; >> tmp3.dat
	echo proc format library=work.formats cntlout = redcap.formats; >> tmp3.dat
	echo run; >> tmp3.dat
    echo proc format library=redcap.formats cntlin=redcap.formats; >> tmp3.dat
	echo run; >> tmp3.dat
	copy /b tmp0.dat+tmp2.dat+tmp3.dat "%%x"
)
del tmp0.dat tmp1.dat tmp2.dat tmp3.dat
@ECHO off
cls
ECHO.
ECHO.
ECHO   STEP #1: Pathway mapping is COMPLETE!
ECHO.
ECHO.
ECHO   NOW... 
ECHO.
ECHO   STEP #2: Double-click the *.SAS file, which will open SAS.
ECHO.
ECHO   STEP #3: Once SAS has opened, choose Run (or Run-^>Submit) from the top menu options.
ECHO.
ECHO   (Press ENTER to close this window) 
set /p var=
goto :EOF
pause
:sub
	set fn0=%~n1
	set fn1=%fn0:_SAS_2=_DATA_NOHDRS_2%
	echo %%macro removeOldFile(bye); %%if %%sysfunc(exist(^&bye.)) %%then %%do; proc delete data=^&bye.; run; %%end; %%mend removeOldFile; > tmp0.dat
	echo libname redcap '%~dp0'; >> tmp0.dat
	echo %%removeOldFile(redcap.redcap); >> tmp0.dat
	echo data REDCAP; %%let _EFIERR_ = 0; >> tmp0.dat
	echo infile '%~dp0%fn1%.CSV' delimiter = ',' MISSOVER DSD lrecl=32767 firstobs=1 ; >> tmp0.dat
