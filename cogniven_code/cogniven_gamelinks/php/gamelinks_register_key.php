<?php
  header("Cache-Control: no-cache, must-revalidate");
  header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
  
  define('KEY_LENGTH_MIN', 200);
  define('KEY_LENGTH_MAX', 300);
  define ('DBHOST', 'localhost');
  define ('DBUSER', 'root');
  define ('DBPASS', '17157');
  define ('DBNAME', 'gamelogin');
  $key = '';
  
  if (isset($_POST['email']) && isset($_POST['server_link']) && isset($_POST['login_key'])) {
    $db = mysql_connect(DBHOST, DBUSER, DBPASS);
    if ($db && mysql_select_db(DBNAME, $db)) {
      $query = "select cogniven_key from gamelinks where email = '" . $_POST['email'] . "' and server = 'account'";
      $result = mysql_query($query, $db);
      if ($result && mysql_num_rows($result) > 0) {
        $row = mysql_fetch_row($result);
        if ($_POST['login_key'] == $row[0]) {
          $key = generate_key();
          $query = "replace into gamelinks set email = '" . $_POST['email'] . "', server = '" . $_POST['server_link'] . "', cogniven_key = '" . $key . "'";
          $result = mysql_query($query, $db);
          if (!$result) {
            $key = '';
          }
        }
      }
    }
  } else {
    header('Location: http://games.cogniven.com/');
    exit;
  }
  
  print $key;
  
  function generate_key() {
    $key_characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $key = "";
    $key_length = mt_rand(KEY_LENGTH_MIN, KEY_LENGTH_MAX);
    for ($index = 0; $index < $key_length; $index++) {
      $key .= $key_characters[mt_rand(0, strlen($key_characters) - 1)];
    }
    
    return $key;
  }
?>
