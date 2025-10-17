This document explains what it is that is the cogniven account module what is required to set it up as well as what it actually does.

Things may need to be fixed.
  If an admin creates a new user they are required to enter a password for that new user; however, that password entry is ignored.  The user must request a new password to get a login link to set their password.  The admin CAN set a password, but it must be done AFTER the user has been created.
  
Set up:
  Requires LoginToboggan
  Must have a database setup with with a table titled 'account' with Cogniven account information.
  The 'account' table must include the following columns:
    'email', 'created', 'last_log_on', 'pcredits', 'salt', 'digest', 'noaddsthru'
  The database host, username, password and database are defined in 'cogniven_account_password.inc'
  The pcredit amount that an account begins with is also defined in 'cogniven_account_password.inc'
  
cogniven_account.module
  Makes the following alterations:
    The password tips section is altered to indicate the minimum password length.
	Changed the email description to indicated that you cannot change email addresses once the account has been created.
	Removed references referring to changing email address.
	Prevents attempts to change the email address and flags an alert to the admin.
	Captures the attempt to change password and handles it per Cogniven's requirements.
  
cogniven_account_password.inc
  Replaces password.inc
  Contains the constants used for handling passwords, including database access.
  When drupal creates a new user, it checks for a matching record in cogniven's accounts and creates a new one if one does not already exist.
  When drupal checks a password, the account database is used instead of the usual drupals users database
  The drupal function user_hash_password() and user_needs_new_hash() are replaced with dummy functions as these are used to set the password hash in drupal's users database, which is not used
  Makes use of both a static and random salt for the users password hashes.
