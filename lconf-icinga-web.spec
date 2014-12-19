#
# spec file for package lconf-icinga-web
#
# (c) 2012-2014 Netways GmbH
#
# This file and all modifications and additions to the pristine
# package are under the same license as the package itself.
#

%define revision 1

%define phpname php

# el5 requires newer php53 rather than php (5.1)
%if 0%{?el5} || 0%{?rhel} == 5 || "%{?dist}" == ".el5"
%define phpname php53
%endif

%define phpbuildname %{phpname}

%if "%{_vendor}" == "suse"
%define phpbuildname php5
%endif

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

Name:           lconf-icinga-web
Summary:        Icinga Web Module for LConf
Version:        1.4.1
Release:        1%{?dist}%{?custom}
Url:            https://www.netways.org/projects/lconf-for-icinga
License:        GPL v2 or later
Group:          Applications/System
BuildArch:      noarch

%if "%{_vendor}" == "suse"
%if 0%{?suse_version} > 1020
BuildRequires:  fdupes
%endif
%endif

%if "%{_vendor}" == "suse"
AutoReqProv:    Off
%endif

Source0:        lconf-icinga-mod-%{version}.tar.gz

BuildRoot:      %{_tmppath}/%{name}-%{version}-build

BuildRequires:  %{phpbuildname} >= 5.2.3
BuildRequires:  %{phpbuildname}-ldap
Requires:  	%{phpname} >= 5.2.3
Requires:       %{phpname}-ldap
Requires:       LConf >= 1.3.0
Requires:       icinga-web >= 1.7.0


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
%doc etc/sql doc/AUTHORS doc/LICENSE doc/install.md
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
