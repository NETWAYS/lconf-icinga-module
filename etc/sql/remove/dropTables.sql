-- Removes all lconf tables in the correct order

DROP TABLE lconf_defaultconnection;
DROP TABLE lconf_filter;
DROP TABLE lconf_principal;
DROP TABLE lconf_connection;

DELETE FROM nsm_target WHERE target_name LIKE "lconf%";
