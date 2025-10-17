<?php
  header("Cache-Control: no-cache, must-revalidate");
  header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
  
  $target_url = 'http://games.cogniven.com/';
  
  if (isset($_GET['email']) && isset($_GET['login_key']) && isset($_GET['server_link']) && isset($_GET['expiration'])) {
    $curl_handle = curl_init();
    if ($curl_handle) {
      curl_setopt($curl_handle, CURLOPT_URL, 'http://127.0.0.1/sites/default/modules/cogniven_gamelinks/php/gamelinks_register_key.php'); 
      curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true); 
      curl_setopt($curl_handle, CURLOPT_POST, true);
      curl_setopt($curl_handle, CURLOPT_POSTFIELDS, 'email=' . $_GET['email'] . '&server_link=' . $_GET['server_link'] . '&login_key=' . $_GET['login_key']);
      curl_setopt($curl_handle, CURLOPT_TIMEOUT_MS, 1000);
      
      $gamekey = curl_exec($curl_handle);
      curl_close($curl_handle);
      
      $server_link = 'https://' . $_GET['server_link'] . '/';
      $target_url = $server_link . '?email=' . $_GET['email'] . '&gamekey=' . $gamekey . '&expiration=' . $_GET['expiration'];
    }
  }
  
  header('Location: ' . $target_url);

?>
