-- Add back-end structures for features to be utilized later
ALTER TABLE  `redcap_randomization` ADD  `group_by` ENUM(  'DAG',  'FIELD' ) NULL COMMENT  'Randomize by group?' AFTER  `stratified`;