ALTER TABLE `lconf_principal`
 DROP FOREIGN KEY `lconf_principal_principal_role_id_nsm_role_role_id`,
 DROP FOREIGN KEY `lconf_principal_principal_user_id_nsm_user_user_id` 
;

ALTER TABLE `lconf_principal`
 ADD CONSTRAINT `lconf_principal_principal_role_id_nsm_role_role_id`
  FOREIGN KEY (`principal_role_id`) REFERENCES `nsm_role` (`role_id`)
  ON DELETE CASCADE,
 ADD CONSTRAINT `lconf_principal_principal_user_id_nsm_user_user_id`
  FOREIGN KEY (`principal_user_id`) REFERENCES `nsm_user` (`user_id`)
  ON DELETE CASCADE
;
