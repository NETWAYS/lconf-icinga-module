# Known Issues

This list contains issues which cannot be fixed but listed as reference here:

## Icinga-web User who has access to a LConf LDAP connection can not be deleted

https://www.netways.org/issues/2694

The module holds a constraint lock on the web user table, and prohibits its deletion.
The debug log entry looks like this

    [Thu Mar 26 13:49:11 2015] [info] SQLSTATE[23000]: Integrity constraint violation: 1451 Cannot delete or update a parent row: a foreign key constraint fails (`icinga_web`.`lconf_principal`, CONSTRAINT `lconf_principal_principal_user_id_nsm_user_user_id` FOREIGN KEY (`principal_user_id`) REFERENCES `nsm_user` (`user_id`))


**Workaround:** Remove the user from the lconf connection access permissions, and then delete
the web user from the admin panel.
