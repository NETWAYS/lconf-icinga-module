DROP TRIGGER TRI_LCONF_CONNECTION;
DROP TRIGGER TRI_L_DEFAULTCONNECTION;
DROP TRIGGER TRI_LCONF_FILTER;
DROP TRIGGER TRI_LCONF_PRINCIPAL;
DROP SEQUENCE LCONF_CONNECTION_SEQ;
DROP SEQUENCE LCONF_DEFAULTCONNECTION_SEQ;
DROP SEQUENCE LCONF_FILTER_SEQ;
DROP SEQUENCE LCONF_PRINCIPAL_SEQ;
DROP TABLE "LCONF_CONNECTION" CASCADE CONSTRAINTS PURGE;
DROP TABLE "LCONF_DEFAULTCONNECTION" CASCADE CONSTRAINTS PURGE;
DROP TABLE "LCONF_FILTER" CASCADE CONSTRAINTS PURGE;
DROP TABLE "LCONF_PRINCIPAL" CASCADE CONSTRAINTS PURGE;


CREATE TABLE  "LCONF_CONNECTION" (
 "CONNECTION_ID" NUMBER(11) NOT NULL ENABLE,
 "CONNECTION_NAME" VARCHAR2(32) NOT NULL ENABLE,
 "CONNECTION_DESCRIPTION" VARCHAR2(256),
 "OWNER" NUMBER(11) DEFAULT 0,
 "CONNECTION_BINDDN" VARCHAR2(1024) NOT NULL ENABLE,
 "CONNECTION_BINDPASS" VARCHAR2(64) DEFAULT NULL,
 "CONNECTION_HOST" VARCHAR2(64) NOT NULL ENABLE,
 "CONNECTION_PORT" NUMBER(11) DEFAULT 389 NOT NULL ENABLE,
 "CONNECTION_BASEDN" VARCHAR2(1024),
 "CONNECTION_TLS" NUMBER(11) DEFAULT 0,
 "CONNECTION_LDAPS" NUMBER(11) DEFAULT 0,
 PRIMARY KEY ("CONNECTION_ID"),
 ---CONSTRAINT "lconf_connection_owner_nsm_user_user_id" FOREIGN KEY ("OWNER") REFERENCES "NSM_USER" ("USER_ID")
 CONSTRAINT "LCONF_CON_O_NSQM_USER_ID" FOREIGN KEY ("OWNER") REFERENCES "NSM_USER" ("USER_ID")
)
/
--- create index
CREATE INDEX "OWNER_IDX" ON "LCONF_CONNECTION" ("OWNER")
/

--- increment
CREATE SEQUENCE LCONF_CONNECTION_SEQ START WITH 1 INCREMENT BY 2 NOCACHE;

--- triger for increment
CREATE TRIGGER TRI_LCONF_CONNECTION BEFORE INSERT ON "LCONF_CONNECTION" FOR EACH ROW
 BEGIN
  SELECT LCONF_CONNECTION_SEQ.NEXTVAL INTO :NEW.CONNECTION_ID FROM DUAL;
 END;
/


CREATE TABLE  "LCONF_DEFAULTCONNECTION" (
 DEFAULTCONNECTION_ID NUMBER(11) NOT NULL ENABLE,
 USER_ID NUMBER(11) NOT NULL ENABLE,
 CONNECTION_ID NUMBER(11),
 PRIMARY KEY ("DEFAULTCONNECTION_ID"),
 UNIQUE ("USER_ID"),
 CONSTRAINT "LCLC" FOREIGN KEY ("CONNECTION_ID") REFERENCES "LCONF_CONNECTION" ("CONNECTION_ID") ON DELETE CASCADE,
 CONSTRAINT "LCONF_DEFCON_USER_NSM_USER_ID" FOREIGN KEY ("USER_ID") REFERENCES "NSM_USER" ("USER_ID") ON DELETE CASCADE
)
/

--- create index
CREATE INDEX "CONNECTION_ID_IDX" ON "LCONF_DEFAULTCONNECTION" ("CONNECTION_ID")
/

--- increment
CREATE SEQUENCE LCONF_DEFAULTCONNECTION_SEQ START WITH 1 INCREMENT BY 1 NOCACHE;

--- triger for increment
CREATE TRIGGER TRI_L_DEFAULTCONNECTION BEFORE INSERT ON "LCONF_DEFAULTCONNECTION" FOR EACH ROW
 BEGIN
  SELECT LCONF_DEFAULTCONNECTION_SEQ.NEXTVAL INTO :NEW.DEFAULTCONNECTION_ID FROM DUAL;
 END;
/


CREATE TABLE  "LCONF_FILTER" (
 FILTER_ID NUMBER(11) NOT NULL ENABLE,
 USER_ID NUMBER(11) DEFAULT -1 NOT NULL ENABLE,
 FILTER_NAME VARCHAR2(127) NOT NULL ENABLE,
 FILTER_JSON CLOB NOT NULL ENABLE,
 FILTER_ISGLOBAL NUMBER(11) DEFAULT 0 NOT NULL ENABLE,
 PRIMARY KEY ("FILTER_ID"),
 CONSTRAINT "LCONF_FIL_U_ID_NSM_USER_ID" FOREIGN KEY ("USER_ID") REFERENCES "NSM_USER" ("USER_ID")
)
/


--- create index
CREATE INDEX "USER_ID_IDX" ON "LCONF_FILTER" ("USER_ID")
/


--- increment
CREATE SEQUENCE LCONF_FILTER_SEQ START WITH 1 INCREMENT BY 11 NOCACHE;

---triger for increment
CREATE TRIGGER TRI_LCONF_FILTER BEFORE INSERT ON "LCONF_FILTER" FOR EACH ROW
 BEGIN
  SELECT LCONF_FILTER_SEQ.NEXTVAL INTO :NEW.FILTER_ID FROM DUAL;
 END;
/


CREATE TABLE  "LCONF_PRINCIPAL" (
 PRINCIPAL_ID NUMBER(11) NOT NULL ENABLE,
 PRINCIPAL_USER_ID NUMBER(11) DEFAULT NULL,
 PRINCIPAL_ROLE_ID NUMBER(11) DEFAULT NULL,
 CONNECTION_ID NUMBER(11) DEFAULT NULL,
 PRIMARY KEY ("PRINCIPAL_ID"),
 CONSTRAINT "LCONF_PRIN_CON_ID_LCONF_CON_ID" FOREIGN KEY ("CONNECTION_ID") REFERENCES "LCONF_CONNECTION" ("CONNECTION_ID") ON DELETE CASCADE,
 CONSTRAINT "LCONF_PRIN_PRIN_ROL_ROLEID" FOREIGN KEY ("PRINCIPAL_ROLE_ID") REFERENCES "NSM_ROLE" ("ROLE_ID"),
 CONSTRAINT "LCONF_PRIN_PRIN_PRIN_USER_ID" FOREIGN KEY ("PRINCIPAL_ROLE_ID") REFERENCES "NSM_USER" ("USER_ID")
)
/

--- create index
CREATE INDEX "PRINCIPAL_USER_ID_IDX" ON "LCONF_PRINCIPAL" ("PRINCIPAL_USER_ID")
/
CREATE INDEX "PRINCIPAL_ROLE_ID_IDX" ON "LCONF_PRINCIPAL" ("PRINCIPAL_ROLE_ID")
/
CREATE INDEX "CONNECTION_ID_IDX_2" ON "LCONF_PRINCIPAL" ("CONNECTION_ID")
/

--- increment
CREATE SEQUENCE LCONF_PRINCIPAL_SEQ START WITH 1 INCREMENT BY 2 NOCACHE;

---triger for increment
CREATE TRIGGER TRI_LCONF_PRINCIPAL BEFORE INSERT ON "LCONF_PRINCIPAL" FOR EACH ROW
 BEGIN
  SELECT LCONF_PRINCIPAL_SEQ.NEXTVAL INTO :NEW.PRINCIPAL_ID FROM DUAL;
 END;
/
