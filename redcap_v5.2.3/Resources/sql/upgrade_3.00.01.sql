-- Replace any errors in metadata multiple choice options that resulted from Excel 2003 limitations in macros from REDCap 2.X.X --
update redcap_metadata set element_enum = replace(element_enum,'\n',' \\n') where element_type in ('radio','select') and element_enum like '%\n%';
update redcap_metadata_temp set element_enum = replace(element_enum,'\n',' \\n') where element_type in ('radio','select') and element_enum like '%\n%';
update redcap_metadata_archive set element_enum = replace(element_enum,'\n',' \\n') where element_type in ('radio','select') and element_enum like '%\n%';
-- Add optional file upload location other than "redcap_edocs" --
INSERT INTO redcap_config VALUES ('edoc_path', '');