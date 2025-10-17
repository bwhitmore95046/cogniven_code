<?php
    if (isset($_POST['email']) && isset($_POST['gamekey']) && isset($_POST['name']) && isset($_POST['starting_block']) && isset($_POST['avatar']) && isset($_POST['bio'])) {
        require_once 'globals.php';
        require_once 'gamelinks/gamelinks_authenticate.php';
        require_once 'player.php';
        require_once 'chat.php';
        require_once 'world.php';
        require_once 'bases.php';
        require_once 'timer.php';
        require_once 'tech.php';
        require_once 'drone.php';
        require_once 'recipes.php';

        $email = $_POST['email'];
        $gamekey = $_POST['gamekey'];
        $name = trim($_POST['name']);
        $starting_block = trim($_POST['starting_block']);
        //urls are not automatically decoded so avatar needs to be manually decoded.
        $avatar = urldecode(end(explode('/', $_POST['avatar'])));
        $bio = $mysqlidb->real_escape_string(strip_tags(substr($_POST['bio'], 0, 1000)));
        $mysqlidb->autocommit(true);

        $serverurl = $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];

        if (authenticate_user(GAME_PORTAL_INTERNAL, $serverurl, $email, $gamekey)) {
            $errors = '';
            $query = "select * from player where email = '" . $email . "'";
            $result = $mysqlidb->query($query);
            if ($result->num_rows > 0) {
                $errors .= "ai:exists;";
            }

            switch (valid_name($name)) {
                case '2':
                    $errors .= 'name:illegal;';
                    break;
                case '3':
                    $errors .= 'name:reserved;';
                    break;
                default;
                    break;
            }

            $query = "select name from player where name = '" . $name . "'";
            $result = $mysqlidb->query($query);
            if ($result->num_rows > 0) {
                $errors .= "name:exists;";
            } else {
                $query = "select name from alliance where name = '" . $name . "'";
                $result = $mysqlidb->query($query);
                if ($result->num_rows > 0) {
                    $errors .= "name:exists;";
                }
            }
            loadinis();
            $cmax = ord($_SESSION["highestopensection"]) - ord('A');
            if (($starting_block == "") || ($starting_block == "0")) {
                // random assignment
                if ($_SESSION["highestopensection"] == "A") {
                    $cidx = 0;
                } else {
                    $cidx = rand(0, $cmax);
                }
                $nidx = rand(0, (8*($cidx + 1) - 5));

                $starting_block = chr(ord("A") + $cidx) . $nidx;
            }
            $cidx = ord(substr($starting_block, 0, 1)) - ord('A');
            $nidx = substr($starting_block, 1);
            $nmax = (8*($cidx + 1) - 5);

            if (($cidx < 0) || ($cidx > $cmax) || !is_numeric($nidx) || ($nidx < 0) || ($nidx > $nmax)) {
                $errors .= "starting_block:invalid;";
            }

            if (!file_exists('graphics/avatars/' . $avatar)) {
                $errors .= 'avatar:invalid;';
            }

            if ($errors == '') {
                $level = STARTINGLEVEL;
                $gcredits = STARTINGGCREDITS;
                $pcredits = STARTINGPCREDITS;
                $alliance = "Beginner";
                $settings = "SAP:" . $avatar . ";";

                $query="replace into player (email, name, level, created, gcredits, pcredits, alliance, settings, bio) values ('$email', '$name', $level, now(), $gcredits, $pcredits, '$alliance', '$settings', '$bio')";
                $result = $mysqlidb->query($query);
                if (!$result) {
                    print 'db:fail';
                } else {
                    getplayerinfo("", $name);

                    $loc = getavailableloc($starting_block);
                    createbase($name, $loc);

                    $base_exists = false;
                    $start_time = microtime(true);
                    $query = "select * from bases where controller = '$name'";
                    while (((microtime(true) - $start_time) < 7) && !$base_exists) {
                        $result = $mysqlidb->query($query);
                        if ($result->num_rows > 0) {
                            $base_exists = true;
                        }
                        usleep(800000);
                    }

                    if (!$base_exists) {
                        // if we failed to create first base then need to remove AI info too
                        $query="delete from player where email='$email'";
                        $result = $mysqlidb->query($query);
                        if (!$result) {
                            print ("base:fail");
                        } else {
                            print ("ai:fail");
                        }
                    } else {
                        print('ai:success');
                        postlog("$email: Created new AI $name.");
                    }
                }
            } else {
                print $errors;
            }
        } else {
            print 'db:fail';
        }
        session_commit();
    } else {
        print 'data:insufficient';
    }
?>
