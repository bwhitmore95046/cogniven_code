<?php
  header("Cache-Control: no-cache, must-revalidate");
  header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
  
  define ('DBHOST', 'localhost');
  define ('DBUSER', 'root');
  define ('DBPASS', '17157');
  define ('DBNAME', 'gamelogin');
  
  $output = 'false';
  
  if (isset($_POST['email']) && isset($_POST['server_name']) && isset($_POST['gamekey'])) {
    $db = mysql_connect(DBHOST, DBUSER, DBPASS);
    if ($db && mysql_select_db(DBNAME, $db)) {
      $query = "select cogniven_key from gamelinks where email = '" . $_POST['email'] . "' and server = '" . $_POST['server_name'] . "'";
      $result = mysql_query($query, $db);
      if ($result && mysql_num_rows($result) > 0) {
        $row = mysql_fetch_row($result);
        if ($row[0] == $_POST['gamekey']) {
          $output = 'true';
        }
      }
    }
  } else {
    header('Location: https://games.cogniven.com/');
    exit;
  }
  
  print $output;
?>
