#
# spec file for package lconf-icinga-web
#
# (c) 2012 Netways GmbH
#
# This file and all modifications and additions to the pristine
# package are under the same license as the package itself.
#

Name:           lconf-icinga-web
Summary:        Icinga Web Module for LConf
Version:        1.3.1rc
Release:        1
Url:            https://www.netways.org/projects/lconf-for-icinga
License:        GPL v2 or later
Group:          System/Monitoring
%if "%{_vendor}" == "suse"
%if 0%{?suse_version} > 1020
BuildRequires:  fdupes
%endif
PreReq:         apache2 
%endif
Requires:	LConf >= 1.3rc
Requires:	icinga-web >= 1.7.0
#Source0:        %name-%version.tar.gz
Source0:        lconf-icinga-module-master.tar.gz
BuildArch:      noarch
BuildRoot:      %{_tmppath}/%{name}-%{version}-build

%if "%{_vendor}" == "suse"
%define		apacheuser wwwrun
%define		apachegroup www
%endif
%if "%{_vendor}" == "redhat"
%define		apacheuser apache
%define		apachegroup apache
%endif
%define 	icingawebdir /usr/share/icinga-web
%define 	clearcache %{_bindir}/icinga-web-clearcache
%define		docdir %{_defaultdocdir}

%description
LConf is a LDAP based configuration tool for Icinga® and Nagios®. All
configuration elements are stored on a LDAP server and exported to text-based
configuration files. Icinga® / Nagios® uses only these config files and will
work independent from the LDAP during runtime.

This is the Icinga Web Module Integration package only, and requires Icinga Web as well as LConf already installed.

%prep
%setup -q -n lconf-icinga-module

%build

%install
%{__mkdir_p} %{buildroot}%{docdir}/%{name}/
%{__cp} -r etc/sql %{buildroot}%{docdir}/%{name}/
%{__mv} %{buildroot}%{docdir}/%{name}/sql/credentials.sql.in %{buildroot}%{docdir}/%{name}/sql/credentials.sql

%{__mkdir_p} %{buildroot}%{icingawebdir}/app/modules
%{__cp} -r LConf %{buildroot}%{icingawebdir}/app/modules/

sed -i 's/@@SCHEMA_PREFIX@@/lconf/g' %{buildroot}%{docdir}/%{name}/sql/credentials.sql
sed -i 's/@@SCHEMA_PREFIX@@/lconf/g' %{buildroot}%{icingawebdir}/app/modules/LConf/lib/js/Components/Configuration.js
sed -i 's/@@SCHEMA_PREFIX@@/lconf/g' %{buildroot}%{icingawebdir}/app/modules/LConf/config/module.xml
sed -i 's/@@SCHEMA_PREFIX@@/lconf/g' %{buildroot}%{icingawebdir}/app/modules/LConf/lib/ldapConfig/staticObjects.ini
sed -i 's/@@SCHEMA_PREFIX@@/lconf/g' %{buildroot}%{icingawebdir}/app/modules/LConf/lib/ldapConfig/objectDefaultAttributes.ini

%post
if [ -x %{clearcache} ]; then %{clearcache}; fi

%postun
if [ -x %{clearcache} ]; then %{clearcache}; fi


%clean
rm -rf %{buildroot}

%files
# FIXME - README.SUSE with the schema explainations (changes to dc=local)????

%defattr(-,root,root,-)
%if "%{_vendor}" == "redhat"
%doc doc/AUTHORS doc/LICENSE doc/INSTALL doc/README.RHEL
%endif
%if "%{_vendor}" == "suse"
%doc doc/AUTHORS doc/LICENSE doc/INSTALL doc/README.SUSE
%endif

%defattr(-,root,root)
%dir %{docdir}/%{name}/sql
%{docdir}/%{name}/sql/*
%attr(0755,%{apacheuser},%{apachegroup}) %{_datadir}/icinga-web/app/modules/LConf/views/
%attr(0755,%{apacheuser},%{apachegroup}) %{_datadir}/icinga-web/app/modules/LConf/templates/
%{_datadir}/icinga-web/app/modules/LConf
%config(noreplace) %{_datadir}/icinga-web/app/modules/LConf/config/access.xml
%config(noreplace) %{_datadir}/icinga-web/app/modules/LConf/config/autoload.xml
%config(noreplace) %{_datadir}/icinga-web/app/modules/LConf/config/config_handlers.xml
%config(noreplace) %{_datadir}/icinga-web/app/modules/LConf/config/css.xml
%config(noreplace) %{_datadir}/icinga-web/app/modules/LConf/config/javascript.xml
%config(noreplace) %{_datadir}/icinga-web/app/modules/LConf/config/menu.xml
%config(noreplace) %{_datadir}/icinga-web/app/modules/LConf/config/module.xml
%config(noreplace) %{_datadir}/icinga-web/app/modules/LConf/config/routing.xml
%config(noreplace) %{_datadir}/icinga-web/app/modules/LConf/config/validators.xml


%changelog
* Fri Jan 15 2013 christian.dengler@netways.de
- fix typo; remove sql-templates from distribution differentiation

* Wed Dec 19 2012 michael.friedrich@netways.de
- initial creation, for suse and rhel

