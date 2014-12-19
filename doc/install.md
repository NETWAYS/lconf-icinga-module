# LConf Icinga Web Installation

## Requirements

* php5 with ldap module
* Icinga Web >= 1.11
* ldap server (preferrably instrumented by LConf backend already)

* Mysql, PostgreSQL, SQLite3 or higher
* PHP 5.2+ including
    * php5-ldap
* LDAP Server (for example OpenLDAP), best being configured with LConf
* Icinga Web 1.11+

The following Software is not required for installing the lconf-module, but recommended for use:
* LConf Backend (see www.netways.org/projects/lconf)

## Installing and Updating the module

Call ./configure. `./configure --help` lists all available options.

Example for an Icinga Web 1.x installed from packages in `/usr/share/icinga-web`:

   ./configure --with-icinga-web-path=/usr/share/icinga-web

Install the module

    make install

### Initial DB Setup

Setup the database by using the phing setup calls

    make install-db

or manually by importing the schema and credentials sql files in `etc/sql`.

### DB Upgrade

All schema update files are located in `etc/sql/updates` and must be applied incrementially.

## Removing the module

    rm -r %your_icinga_path%/app/modules/LConf

Run the removal script in `etc/sql/remove/` in case you also want to delete your connection settings.

## Configuration

### Add User Permissions

Add the credentials for the users who should be allowed to use the lconf module (Admin->Users->select user->Rights->Credentials).


## Packaging

The manual schema is located in etc/sql/lconf_icinga_web.sql and needs to be imported into the existing icinga_web database after install, like

    mysql -u root -p icinga_web < etc/sql/lconf_icinga_web.sql

The LConf schema credentials need to be imported into icinga web as well. Furthermore, you need to manually assign the privileges to your users
in the Icinga Web GUI.

    mysql -u root -p icinga_web < etc/sql/credentials.sql


# Release Checklist

Update `doc/AUTHORS` and `.mailmap` file

    git log --use-mailmap | grep ^Author: | cut -f2- -d' ' | sort | uniq > doc/AUTHORS

Update version

    vim etc/make/version.m4
    autoconf

Create tarball

    VERSION=1.4.1
    git archive --format=tar --prefix=lconf-icinga-mod-$VERSION/ tags/v$VERSION | gzip >lconf-icinga-mod-$VERSION.tar.gz

