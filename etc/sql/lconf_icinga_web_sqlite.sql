--- LConf specific tables ---


CREATE TABLE lconf_connection (
	connection_id INTEGER PRIMARY KEY AUTOINCREMENT,
	connection_name VARCHAR(255),
	connection_description VARCHAR(512),
	owner INTEGER,
	connection_binddn VARCHAR(512) NOT NULL,
	connection_bindpass VARCHAR(512),
	connection_host VARCHAR(512) NOT NULL DEFAULT 'localhost',
	connection_port INTEGER NOT NULL DEFAULT 389,
        connection_basedn VARCHAR(512),
	connection_tls INTEGER DEFAULT 0,
	connection_ldaps INTEGER DEFAULT 0
);


CREATE TABLE lconf_defaultconnection (
  defaultconnection_id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  connection_id INTEGER NOT NULL,
);



CREATE TABLE lconf_filter (
	filter_id INTEGER PRIMARY KEY AUTOINCREMENT,
	user_id INTEGER DEFAULT '-1',
	filter_name VARCHAR(255) NOT NULL,
	filter_json VARCHAR(512) NOT NULL,
	filter_isglobal INTEGER DEFAULT '0' NOT NULL
);

CREATE TABLE lconf_principal (
	principal_id INTEGER PRIMARY KEY AUTOINCREMENT,
	principal_user_id INTEGER,
	principal_role_id INTEGER,
	connection_id INTEGER
);
