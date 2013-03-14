-- Convert global option for plotting service, if needed
set @enable_plotting = (select value from redcap_config where field_name = 'enable_plotting' limit 1);
update redcap_config set value = if(@enable_plotting=1,1,2) where field_name = 'enable_plotting' limit 1;
-- Add new global option
insert into redcap_config values ('enable_plotting_survey_results', '1');
-- Change default value
ALTER TABLE  `redcap_surveys` CHANGE  `min_responses_view_results`  `min_responses_view_results` INT( 5 ) NOT NULL DEFAULT  '10';