#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_PREREQ([2.67])
AC_INIT([lconf-icinga-module], [1.4.0], [www.netways.org/projects/lconf-for-icinga])

# Checks for programs.
AC_PREFIX_DEFAULT(/usr/local/icinga-web/)

AC_PROG_INSTALL
AC_PROG_GREP
AC_PROG_SED

AC_ARG_VAR([PHP],[php cli binary])
ACLCONF_CHECK_BIN([PHP], [php])

ACLCONF_CHECK_PHP_MODULE([ldap])


# Checks for libraries.

# Checks for header files.

# Check for arguments 
AC_ARG_WITH([icinga_web_path],
    AS_HELP_STRING([--with-icinga-web-path=PATH], [absolute path to icinga-web]),
    icinga_web_path=$withval,
    icinga_web_path=/usr/local/icinga-web
)
AC_ARG_WITH([ldap_prefix],
    AS_HELP_STRING([--with-ldap-prefix=name], [prefix to use for your ldap instances (default lconf)]),
    ldap_prefix=$withval,
    ldap_prefix=lconf
)


AC_ARG_WITH([icinga_web_cache_clear_bin],
    AS_HELP_STRING([--with-icinga-web-cache-clear-bin=PATH], [location of icinga-webs clear cache script]),
    icinga_web_cache_bin=$withval,
    icinga_web_cache_bin=$icinga_web_path/bin/clearcache.sh
)

AC_ARG_WITH([lconf_path],
    AS_HELP_STRING([--with-lconf-path=PATH], [location of your lconf installation (containing the lconf_deploy.sh script)]),
    lconf_path=$withval,
    lconf_path=/usr/local/LConf
)

AC_ARG_WITH([db-name],
    AS_HELP_STRING([--with-db-name=NAME], [name of the icinga-web db]),
    db_name=$withval,
    db_name=icinga_web
)

AC_ARG_WITH([db-user],
    AS_HELP_STRING([--with-db-user=NAME], [name of the icinga-web db maintenance user (needs CREATE,ALTER table rights)]),
    db_user=$withval,
    db_user=icinga_web
)
AC_ARG_WITH([db-password],
    AS_HELP_STRING([--with-db-password=NAME], [name of the icinga-web db maintenance user passwd)]),
    db_pass=$withval,
    db_pass=icinga_web
)
AC_ARG_WITH([db-type],
    AS_HELP_STRING([--with-db-type=NAME], [name of the icinga-web dbms type (default mysql))]),
    db_type=$withval,
    db_type=mysql
)
AC_ARG_WITH([db-host],
    AS_HELP_STRING([--with-db-host=NAME], [name of the icinga-web db host)]),
    db_host=$withval,
    db_host=localhost
)
AC_ARG_WITH([db-port],
    AS_HELP_STRING([--with-db-port=NAME], [number of the icinga-web db port]),
    db_port=$withval,
    db_port=3306
)
AC_ARG_WITH([bin_user],
    AS_HELP_STRING([--with-bin-user=USER], [user for all other files (default root)]),
    bin_user=$withval,
    bin_user=root
)
AC_ARG_WITH([bin_group],
    AS_HELP_STRING([--with-bin-group=GROUP], [group for all other files (default bin)]),
    bin_group=$withval,
    bin_group=bin
)

ACLCONF_ESCAPE_DBNAME([db_name])
INSTALL_OPTS="-o $bin_user -g $bin_group"

AC_SUBST(icinga_web_path)
AC_SUBST(icinga_web_cache_bin)
AC_SUBST(lconf_path)
AC_SUBST(ldap_prefix)
AC_SUBST(db_type)
AC_SUBST(db_host)
AC_SUBST(db_port)
AC_SUBST(db_name)
AC_SUBST(db_user)
AC_SUBST(db_pass)
AC_SUBST(PHP)
AC_SUBST(INSTALL_OPTS)

AC_CONFIG_FILES([
    Makefile
    src/LConf/lib/ldapConfig/objectDefaultAttributes.ini
    src/LConf/lib/ldapConfig/staticObjects.ini
    src/LConf/config/module.xml
    src/LConf/config/access.xml
    src/LConf/lib/js/Components/Configuration.js
    etc/sql/credentials.sql
])

# Checks for library functions.

AC_OUTPUT
