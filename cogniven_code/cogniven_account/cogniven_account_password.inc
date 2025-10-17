<?php

/**
 * @file
 * Custom Cogniven Studios secure password hashing functions for user authentication.
 */

/**
 * These database constants must allow access to the cogniven database containing the 'account' table which holds the information needed to authenticate cogniven users.
 */
define ("DBHOST", "localhost");
define ("DBUSER", "root");
define ("DBPASS", "17157");
define ("DBNAME", "gamelogin");

/**
 * This constant defines the amount of pcredits a new account is created with.
 */
define ("STARTINGPCREDITS", 1000);

/**
 * These constants are used to hash passwords.  If these are changed, every single user will need to generate a single login and update their password.
 */
define ("DIGEST_ITERATION_COUNT", 6612);
define ("STATIC_SALT", "Cogniven.");  //must be exactly 9 characters

/**
 * Implements hook_init().
 */
function cogniven_account_init() {
  variable_set('password_inc', drupal_get_path('module', 'cogniven_account') . '/cogniven_account_password.inc');
}

/**
 * Implements hook_user_insert().
 */
function cogniven_account_user_insert(&$edit, $account, $category)
{
  $email = $account->mail;
  $error = true;
  
  $db = mysql_connect(DBHOST, DBUSER, DBPASS);
  if ($db && mysql_select_db(DBNAME, $db)) {
    $query = "select count(*) from account where email='$email'";
	$result = mysql_query($query, $db);
	if ($result && $row = mysql_fetch_row($result)) {
	  if ($row[0] == 1) {
	    $error = false;
	  }
	  else {
	    $query = "insert into account (email, created, last_log_on, pcredits, salt, digest, noaddsthru) values ('$email', now(), now(), " . STARTINGPCREDITS . " , '', '', 0)";
		$result = mysql_query($query, $db);
		
		if ($result)
		{
		  $error = false;
		}
	  }
	}
  }
  
  if ($error) {
    form_set_error("Cogniven Account", 'There was an error during account creation.  Please notify an admin of the error.');
    watchdog('Cogniven Account', 'Error trying to create account for %email.  Failed to create Cogniven account database entry.', array('%email' => $email), WATCHDOG_ALERT, $link = NULL);
  }
}

/*
 * Updates an existing account's pcredits by delta.
 *  posts error log entries to track updating of pcredits and success or failure.
 *
 * @param $name
 *   Plain text user name that must match a user record
 * @param $delta
 *   Positive or negative integer delta to apply to pcredits.
 *
 * @return
 *   TRUE if account updated and resultant pcredits >=0
 *   FALSE if account with $name not found or pcredits would be negative
 */
function cogniven_account_user_update_pcredits($name, $delta) {
  $success = false;

  error_log("Attempting to adjust $name pcredits by $delta");

  // get email of target user with username = $name
  $user = user_load_by_name(check_plain($name));
  if ($user !== false) {
    $email = $user->mail;

    $db = mysql_connect(DBHOST, DBUSER, DBPASS);
    if ($db && mysql_select_db(DBNAME, $db)) {
      $query = "select pcredits from account where email='$email'";
      $result = mysql_query($query, $db);
      if ($result && $row = mysql_fetch_row($result)) {
          $oldpc = $row[0];
          $newpc = $oldpc + floor($delta);
          if ($newpc >= 0) {
              $query = "update account set pcredits=$newpc where email='$email'";
              $result = mysql_query($query, $db);
              if ($result == true) {
                  error_log("Updated $name pcredits was $oldpc is now $newpc");
                  $success = true;
              } else {
                  error_log("Failed to update $name pcredits");
              }
          }
      }
    }
  }
  return $success;
}

/**
 * Check whether a plain text password matches a stored hashed password.
 *
 * Custom password checking for Cogniven Studios
 *
 * @param $password
 *   A plain-text password
 * @param $account
 *   A user object with at least the fields from the {users} table.
 *
 * @return
 *   TRUE or FALSE.
 */
function user_check_password($password, $account) {
  $email = $account->mail;
  
  $db = mysql_connect(DBHOST, DBUSER, DBPASS);
  if ($db && mysql_select_db(DBNAME, $db)) {
    $query = "select digest, salt from account where email = '$email'";
    $result = mysql_query($query, $db);
    if ($result && mysql_num_rows($result) > 0) {
      $row = mysql_fetch_assoc($result);
      if ($row["digest"] == digest_password($row["salt"], $password)) {
        return true;
      }
    }
  }
  return false;
}
 
/**
 * Hash a password using a secure hash.
 *
 * @param $password
 *   A plain-text password.
 * @param $count_log2
 *   This is ignored for this implementation.
 *
 * @return
 *   Hashes are stored in a different database so this returns an empty string.
 */
function user_hash_password($password, $count_log2 = 0) {
  return md5(rand(1000000, 999999999) . 'this is creating a dummy hash that is not used');
}

/**
 * Check whether a user's hashed password needs to be replaced with a new hash.
 *
 * If the is returns true, drupal stores the new hash in the drupal database.
 * Password hashes are not stored in the drupal database.
 * Therefore, this always returns FALSE.
 */
function user_needs_new_hash($account) {
  return false;
}

/**
 * Concatenates the salt and password together then hashes it DIGEST_ITERATION_COUNT number of times using the md5 method.
 *
 * Returns the hashed value.
 */
function digest_password ($salt, $password)
{
    $hash = $salt.$password;
    for ($i = 0; $i < DIGEST_ITERATION_COUNT; $i++)
    {
        $hash = md5($hash);
    }
    return $hash;
}

/**
 * Sends the hash of the password of given account to the password submittted.
 *
 * If there is no existing account fails.
 *
 * Returns TRUE on success, FALSE on failure
 */
function cogniven_account_change_password($email, $new_pass)
{
  $salt = '';
  $db = mysql_connect(DBHOST, DBUSER, DBPASS);
  if ($db && mysql_select_db(DBNAME, $db)) {
    
    $query = "select salt from account where email = '$email'";
	$result = mysql_query($query, $db);
	
	if ($result && mysql_num_rows($result) > 0) {
	  $row = mysql_fetch_row($result);
	  if ($row && $row[0] == '') {
        $new_salt = uniqid(STATIC_SALT, true);
	    $query_add_salt = "update account set salt='$new_salt' where email='$email'";
		$result_add_salt = mysql_query($query_add_salt, $db);
		
		if ($result_add_salt) {
		  $salt = $new_salt;
		}
	  }
	  else {
	    $salt = $row[0];
	  }
	}
  }
  
  if ($salt != '') {
    return cogniven_account_save_password ($email, $new_pass);
  }
  else {
    return false;
  }
}

/**
 * Sets the password of the supplied email account to the supplied password in the Cogniven account database.
 * 
 * Arguments: (string) email, (string) new password, (string) old password
 * returns: true if the password is successfully set
 *          false if the password is not set successfully
 */
function cogniven_account_save_password ($email, $new_pass) {
  $db = mysql_connect(DBHOST, DBUSER, DBPASS);
  if ($db && mysql_select_db(DBNAME, $db)) {
    $query = "select salt from account where email = '$email';";
    $result = mysql_query($query, $db);
    if ($result) {
      $row = mysql_fetch_assoc($result);
    
      $new_digest = digest_password($row["salt"], $new_pass);
      $query = "update account set digest='$new_digest' where email = '$email';";
      $result = mysql_query($query, $db);
      if ($result) {
        return true;
      }
    }
  }
  return false;
}
