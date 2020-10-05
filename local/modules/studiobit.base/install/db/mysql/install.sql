CREATE TABLE b_studiobit_form_field_perms (
	ID int(18) NOT NULL AUTO_INCREMENT,
	ROLE_ID int(11) NOT NULL,
	FORM_ID varchar(255) NOT NULL DEFAULT '',
	FIELD varchar(255) NOT NULL DEFAULT '',
	PERM varchar(3) DEFAULT '',
	PRIMARY KEY (ID)
);