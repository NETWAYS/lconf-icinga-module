INSERT INTO nsm_target (target_name,target_description,target_type) VALUES ('lconf.user','Allow access to the lconf module','credential');
INSERT INTO nsm_target (target_name,target_description,target_type) VALUES ('lconf.admin','Allow administration of lconf module','credential');
INSERT INTO lconf_filter (filter_id,user_id,filter_name,filter_json,filter_isglobal) VALUES (2,1,'ONLY Timeperiods','{\"AND\":[{\"filter_negated\":false,\"filter_attribute\":\"objectclass\",\"filter_type\":1,\"filter_value\":\"${schemaPrefix}Timeperiod\"}]}',1);
INSERT INTO lconf_filter (filter_id,user_id,filter_name,filter_json,filter_isglobal) VALUES (3,1,'ONLY Commands','{\"AND\":[{\"filter_negated\":false,\"filter_attribute\":\"objectclass\",\"filter_type\":1,\"filter_value\":\"${schemaPrefix}Command\"}]}',1);
INSERT INTO lconf_filter (filter_id,user_id,filter_name,filter_json,filter_isglobal) VALUES (4,1,'ONLY Contacts','{\"AND\":[{\"filter_negated\":false,\"filter_attribute\":\"objectclass\",\"filter_type\":1,\"filter_value\":\"${schemaPrefix}Contact\"}]}',1);
INSERT INTO lconf_filter (filter_id,user_id,filter_name,filter_json,filter_isglobal) VALUES (6,1,'ONLY Contactgroups','{\"AND\":[{\"filter_negated\":false,\"filter_attribute\":\"objectclass\",\"filter_type\":1,\"filter_value\":\"${schemaPrefix}Contactgroup\"}]}',1);
INSERT INTO lconf_filter (filter_id,user_id,filter_name,filter_json,filter_isglobal) VALUES (7,1,'ONLY Hosts','{\"AND\":[{\"filter_negated\":false,\"filter_attribute\":\"objectclass\",\"filter_type\":1,\"filter_value\":\"${schemaPrefix}Host\"}]}',1);
INSERT INTO lconf_filter (filter_id,user_id,filter_name,filter_json,filter_isglobal) VALUES (8,1,'ONLY Hostgroups','{\"AND\":[{\"filter_negated\":false,\"filter_attribute\":\"objectclass\",\"filter_type\":1,\"filter_value\":\"${schemaPrefix}Hostgroup\"}]}',1);
INSERT INTO lconf_filter (filter_id,user_id,filter_name,filter_json,filter_isglobal) VALUES (9,1,'ONLY Services','{\"AND\":[{\"filter_negated\":false,\"filter_attribute\":\"objectclass\",\"filter_type\":1,\"filter_value\":\"${schemaPrefix}Service\"}]}',1);
INSERT INTO lconf_filter (filter_id,user_id,filter_name,filter_json,filter_isglobal) VALUES (10,1,'ONLY Servicegroups','{\"AND\":[{\"filter_negated\":false,\"filter_attribute\":\"objectclass\",\"filter_type\":1,\"filter_value\":\"${schemaPrefix}Servicegroup\"}]}',1);