<?php
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

    /*
     * Check for the presence of GET variables indicating a user just logged in from the Games Portal.
     *   If the GET variables exist, set the current session's email and gamekey to these values.
     *   If the GET variables don't exist, check if there are existing cookies for email and gamekey.
     *    If there are existing cookies, set the session's email and gamekey to the cookies.
     *    If there are no existing cookies, redirect the user to the Games Portal.
     */

    //declared for scope
    $ai_exists = false;
    $ai = '';

    require_once 'globals.php';
    require_once 'gamelinks/gamelinks_authenticate.php';
    $serverurl = $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];
    if (!isset($email) || !isset($gamekey)) {
        // bogus attempt to access directly
        header('Location: ' . GAME_PORTAL_EXTERNAL);
        exit;
    }
    if (authenticate_user(GAME_PORTAL_INTERNAL, $serverurl, $email, $gamekey)) {
        $query = "select name from player where email = '" . $email . "'";
        $result = $mysqlidb->query($query);
        if ($mysqlidb->error != null) {
            //failed to access database, revert to games portal
            header('Location: ' . GAME_PORTAL_EXTERNAL);
            exit;
        }
        if ($result->num_rows > 0) {
            $ai_exists = true;
            $row = $result->fetch_row();
            $ai = $row[0];
        }

        if (isset($_GET['expiration'])) {
            $_SESSION['expiration'] = $_GET['expiration'];
        }

        if (!isset($_GET['AI'])) {
            header('Location: ' . 'https://' . $serverurl . '?AI=' . urlencode($ai));
            setcookie(str_replace(' ', '*', $ai) . 'email', $email, 0, null, null, true, true);
            setcookie(str_replace(' ', '*', $ai) . 'gamekey', $gamekey, 0, null, null, true, true);
        } else {
            $email = $_COOKIE[$ai . 'email'];
            $gamekey = $_COOKIE[$ai . 'gamekey'];
            if (key_exists('expiration', $_SESSION)) {
                $cookie_expiration = $_SESSION['expiration'];
            } else {
                $cookie_expiration = 0;
            }
            setcookie(str_replace(' ', '*', $ai) . 'email', $email, $cookie_expiration, null, null, true, true);
            setcookie(str_replace(' ', '*', $ai) . 'gamekey', $gamekey, $cookie_expiration, null, null, true, true);
        }
    } else {
        header('Location: ' . GAME_PORTAL_EXTERNAL);
        exit;
    }

    /*
     * If execution reaches here, then the user has successfully logged into the game server.
     *   Start the session and save the login information in the session variables.
     *
     *   Check if the user has an AI on this server.
     *     If the user has an AI, start the game.
     *     If the user does not have an AI, allow the user to create one.
     */
    $_SESSION['email'] = $email;
    $_SESSION['gamekey'] = $gamekey;
    $_SESSION['logged_in'] = true;

    if ($ai_exists) {
        $UserEmail = $_SESSION['email'];
        require_once 'bases.php';
        require_once 'chat.php';
        require_once 'player.php';
        require_once 'world.php';
        require_once 'alliances.php';

        require_once 'Play.php';
    } else {
        require_once 'newai_form.php';
    }
?>
