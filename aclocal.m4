
AC_DEFUN([ACLCONF_CHECK_PHP_MODULE],[
  for x in $1;do
     AC_MSG_CHECKING([if php has $x module])
     AS_IF([ php -m | $GREP -iq "^$x$" ],
            [ AC_MSG_RESULT([found]) ],
            [ AC_MSG_ERROR([not found])])
  done
])

AC_DEFUN([ACLCONF_CHECK_BIN], [
   AC_PATH_PROG([$1],[$2],[not found])

   AS_IF([ test "XX${$1}" == "XXnot found" ],
     [ AC_MSG_WARN([binary $2 not found in PATH]) ])

   test "XX${$1}" == "XXnot found" && $1=""
])

AC_DEFUN([ACLCONF_ESCAPE_DBNAME], [
    NEW=`echo $$1 | $SED 's/\[-\]+/_/g'`
    AS_IF([test "$$1" == "$NEW" ],
        [ AC_MSG_NOTICE([Database name correct: $NEW]) ],
        [ AC_MSG_WARN([Database name changed to: $NEW]) ])

    $1=$NEW
]);

