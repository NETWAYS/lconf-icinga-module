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
Version:        1.3.2
Release:        1
Url:            https://www.netways.org/projects/lconf-for-icinga
License:        GPL v2 or later
Group:          Applications/System
%if "%{_vendor}" == "suse"
%if 0%{?suse_version} > 1020
BuildRequires:  fdupes
%endif
BuildRequires:  php5
BuildRequires:  php5-ldap
Requires:  	php5
Requires:  	php5-ldap
%endif
%if "%{_vendor}" == "redhat"
%if 0%{?el5}
BuildRequires:  php53
BuildRequires:  php53-ldap
Requires:  	php53
Requires:  	php53-ldap
%else
BuildRequires:  php
BuildRequires:  php-ldap
Requires:  	php
Requires:  	php-ldap
%endif
%endif
Requires:       LConf >= 1.3.0
Requires:       icinga-web >= 1.7.0
Source0:        lconf-icinga-mod-%{version}.tar.gz
BuildArch:      noarch
BuildRoot:      %{_tmppath}/%{name}-%{version}-build

%if "%{_vendor}" == "suse"
%define         apacheuser wwwrun
%define         apachegroup www
%endif
%if "%{_vendor}" == "redhat"
%define         apacheuser apache
%define         apachegroup apache
%endif
%define         icingawebdir /usr/share/icinga-web
%define         clearcache %{_bindir}/icinga-web-clearcache
%define         docdir %{_defaultdocdir}
%define         ldapprefix lconf

%description
LConf is a LDAP based configuration tool for Icinga® and Nagios®. All
configuration elements are stored on a LDAP server and exported to text-based
configuration files. Icinga® / Nagios® uses only these config files and will
work independent from the LDAP during runtime.

This is the Icinga Web Module Integration package only, and requires Icinga Web as well as LConf already installed.

%prep
#%setup -q -n lconf-icinga-module
%setup -q -n lconf-icinga-module-%{version}

%build
%configure \
        --with-icinga-web-path="%{icingawebdir}" \
        --with-ldap-prefix="%{ldapprefix}"

%install
%{__rm} -rf %{buildroot}
# install will clear the cache, which we will do in post
%{__make} install-basic \
    DESTDIR="%{buildroot}" \
    INSTALL_OPTS="" \
    COMMAND_OPTS="" \
    INIT_OPTS=""

%post
if [ -x %{clearcache} ]; then %{clearcache}; fi

%postun
if [ -x %{clearcache} ]; then %{clearcache}; fi


%clean
%{__rm} -rf %{buildroot}

%files
%defattr(-,root,root,-)
%doc etc/sql doc/AUTHORS doc/LICENSE doc/INSTALL
%defattr(-,root,root,-)
%if "%{_vendor}" == "redhat"
%doc doc/README.RHEL
%endif
%if "%{_vendor}" == "suse"
%doc doc/README.SUSE
%endif
%config(noreplace) %{_datadir}/icinga-web/app/modules/LConf/config
%{_datadir}/icinga-web/app/modules/LConf/actions
%{_datadir}/icinga-web/app/modules/LConf/lib
%{_datadir}/icinga-web/app/modules/LConf/manifest.xml
%{_datadir}/icinga-web/app/modules/LConf/models
%{_datadir}/icinga-web/app/modules/LConf/pub
%{_datadir}/icinga-web/app/modules/LConf/templates
%{_datadir}/icinga-web/app/modules/LConf/validate
%{_datadir}/icinga-web/app/modules/LConf/views


%changelog
* Thu May 16 2013 michael.friedrich@netways.de
- rewrite for configure/make
- use make install-basic without clearcache
- ldapprefix is now 'lconf', no more sed
- fix rpmplint errors from macro usage in Changelog

* Wed Feb 27 2013 Markus Frosch <markus.frosch@netways.de>
- Fixes for doc handling on SuSE (sql scripts where missing)
- cleaner install, moved stuff to build
- avoid file double listing

* Fri Jan 15 2013 christian.dengler@netways.de
- fix typo; remove sql-templates from distribution differentiation

* Wed Dec 19 2012 michael.friedrich@netways.de
- initial creation, for suse and rhel

