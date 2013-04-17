\set icinga_web_owner 'icinga_web';

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;


--
-- Table structure for table lconf_connection
--

DROP TABLE IF EXISTS lconf_connection CASCADE;
DROP SEQUENCE IF EXISTS lconf_connection_connection_id_seq;

CREATE SEQUENCE lconf_connection_connection_id_seq
    START WITH 1
    INCREMENT BY 2 
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE lconf_connection_connection_id_seq OWNER TO :icinga_web_owner;

CREATE TABLE lconf_connection (
  "connection_id" integer NOT NULL PRIMARY KEY default nextval('lconf_connection_connection_id_seq'),
  "connection_name" varchar(32) NOT NULL,
  "connection_description" text,
  "owner" integer DEFAULT NULL,
  "connection_binddn" text NOT NULL,
  "connection_bindpass" varchar(64) DEFAULT NULL,
  "connection_host" varchar(64) NOT NULL,
  "connection_port" integer NOT NULL DEFAULT '389',
  "connection_basedn" text,
  "connection_tls" integer DEFAULT '0',
  "connection_ldaps" integer DEFAULT '0',
  CONSTRAINT "lconf_connection_owner_nsm_user_user_id" FOREIGN KEY ("owner") REFERENCES "nsm_user" ("user_id")
); 

ALTER TABLE public.lconf_connection OWNER TO :icinga_web_owner;


CREATE INDEX owner_idx_unique ON lconf_connection USING btree (owner);


--
-- Table structure for table "lconf_defaultconnection"
--

DROP TABLE IF EXISTS lconf_defaultconnection;
DROP SEQUENCE IF EXISTS lconf_defaultconnection_defaultconnection_id_seq;

CREATE SEQUENCE lconf_defaultconnection_defaultconnection_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE lconf_defaultconnection_defaultconnection_id_seq OWNER TO :icinga_web_owner;

CREATE TABLE lconf_defaultconnection (
  defaultconnection_id integer NOT NULL PRIMARY KEY default nextval('lconf_defaultconnection_defaultconnection_id_seq'),
  user_id integer NOT NULL,
  connection_id integer NOT NULL,
  CONSTRAINT lclc FOREIGN KEY (connection_id) REFERENCES lconf_connection ("connection_id") ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT lconf_defaultconnection_user_id_nsm_user_user_id FOREIGN KEY ("user_id") REFERENCES "nsm_user" ("user_id") ON DELETE CASCADE ON UPDATE CASCADE
);

ALTER TABLE public.lconf_defaultconnection OWNER TO :icinga_web_owner;

CREATE UNIQUE INDEX defaultconn_unique_idx_unique ON lconf_defaultconnection USING btree (user_id);
CREATE INDEX connection_id_idx_unique ON lconf_defaultconnection USING btree (connection_id);


--
-- Table structure for table lconf_filter
--

DROP TABLE IF EXISTS lconf_filter;
DROP SEQUENCE IF EXISTS lconf_filter_filter_id_seq;

CREATE SEQUENCE lconf_filter_filter_id_seq
    START WITH 1
    INCREMENT BY 11
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE lconf_filter_filter_id_seq OWNER TO :icinga_web_owner;

CREATE TABLE lconf_filter (
  filter_id integer NOT NULL PRIMARY KEY default nextval('lconf_filter_filter_id_seq'),
  user_id integer NOT NULL DEFAULT '-1',
  filter_name varchar(127) NOT NULL,
  filter_json text NOT NULL,
  filter_isglobal integer NOT NULL DEFAULT '0',
  CONSTRAINT "lconf_filter_user_id_nsm_user_user_id" FOREIGN KEY ("user_id") REFERENCES "nsm_user" ("user_id")
);

ALTER TABLE public.lconf_filter OWNER TO :icinga_web_owner;
CREATE INDEX user_id_idx_unique ON lconf_filter USING btree (user_id);


--
-- Table structure for table "lconf_principal"
--

DROP TABLE IF EXISTS lconf_principal CASCADE;
DROP SEQUENCE IF EXISTS lconf_principal_principal_id_seq;

CREATE SEQUENCE lconf_principal_principal_id_seq
    START WITH 1
    INCREMENT BY 2
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE lconf_principal_principal_id_seq OWNER TO :icinga_web_owner;

CREATE TABLE lconf_principal (
  principal_id integer NOT NULL PRIMARY KEY default nextval('lconf_principal_principal_id_seq'),
  principal_user_id integer DEFAULT NULL,
  principal_role_id integer DEFAULT NULL,
  connection_id integer DEFAULT NULL,
  CONSTRAINT "lconf_principal_connection_id_lconf_connection_connection_id" FOREIGN KEY ("connection_id") REFERENCES "lconf_connection" ("connection_id") ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT "lconf_principal_principal_role_id_nsm_role_role_id" FOREIGN KEY ("principal_role_id") REFERENCES "nsm_role" ("role_id"),
  CONSTRAINT "lconf_principal_principal_user_id_nsm_user_user_id" FOREIGN KEY ("principal_user_id") REFERENCES "nsm_user" ("user_id")
);

ALTER TABLE public.lconf_principal OWNER TO :icinga_web_owner;
CREATE INDEX principal_user_id_idx_unique ON lconf_principal USING btree (principal_user_id);
CREATE INDEX principal_role_id_idx_unique ON lconf_principal USING btree (principal_role_id);
CREATE INDEX connection_id_idx_2_unique ON lconf_principal USING btree (connection_id);

