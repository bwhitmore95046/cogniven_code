<?php
/*
 * Functions to deal with player information
 * Author: Chris Bryant
 */

include_once "alliances.php";

/*
 * getitemsquantity - scan itemstr for matching type and
 *      return sum of all matches
 *  fragstr is in format "type:level:quantity;..."
 */
function getitemsquantity($itemstr, $itype, $ilevel) {
    $count = 0;
    $itemarr = explode(";", $itemstr);
    foreach ($itemarr as $istr) {
        $item = explode(":", $istr);
        if (count($item) > 2) {
            if (($item[0] == $itype) && (($item[1] == $ilevel) || ($ilevel == -1))) {
                $count += $item[2];
            }
        }
    }
    return $count;
}


/*
 * secondlogon - retrieves player record and checks laston field
 *      against $_SESSION["laston"].
 *      Returns true if they are different, otherwise false.
 *
 *  Used to detect if logged onto this same character in second
 *      browser window. Called when chat refreshed
 */
function secondlogon() {
    global $mysqlidb;
    $laston = $threshon = 0;
    if (key_exists("laston", $_SESSION) && key_exists("email", $_SESSION)) {
        $laston = $_SESSION["laston"];
        $threshon = $laston + 5;
        $query = "select laston from player where email='{$_SESSION["email"]}'";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            $row = $result->fetch_row();
            $laston = $row[0];
            if ($laston == 0) {
                // no recorded logon so say it's true
                $laston = $_SESSION["laston"];
            } else if ($laston > $threshon) {
                postlog("secondlogon: {$_SESSION["email"]} $laston > $threshon");
            }
        }
    }
    return ($laston > $threshon);
}

/*
 * getplayerinfo: retrieve player info and puts into _SESSION
 *
 * return:  0: failure
 *          1: success
 */
function getplayerinfo ($email, $ai) {
    global $mysqlidb;
    $success = 0;
    $result = null;
    $where_string = "";
    $now = time();
    if ($email != "") { // get info from login
        // Update laston field
        $query = "update player set laston=$now,lastsaw=$now where email='$email'";
        $_SESSION["laston"] = $now; // only updated here
        $_SESSION["lastchattime"] = 0; // only reset here
        $mysqlidb->query($query);
        $mysqlidb->commit(); // make sure it's updated now

        $where_string = "where player.email='$email';";
        $error_string = "Unable to get Master AI info as record with email ($email) not found in database";
    }
    else if ($ai != "")
    { // get info for refresh
        $where_string = "where player.name='$ai'";
        $error_string = "Unable to get Master AI info as record with name ($ai) not found in database";
    } else {
        postlog ("getplayerinfo: both email and ai are blank!");
        return $success;
    }

    if ($where_string != "") {
        $query = "select * from player $where_string";
        $retrycnt = 1;
        while ($retrycnt > 0) {
            // load player info on load of main script or every 5th refresh via ajax
            if ((stripos($_SERVER['SCRIPT_FILENAME'], "index.php") !== false)
                    || (stripos($_SERVER['SCRIPT_FILENAME'], "postnewai.php") !== false)) {
                $_SESSION["playerloadcount"] = -2; // load it thrice in a row on first load or reload
                // clear recipe cache
                $_SESSION["recipes"] = array();
                // clear bases cache
                $_SESSION["bases"] = array();
                // clear last target cache
                $_SESSION["last_target_loc"] = "";
                $_SESSION["last_target_controller"] = "";
                $_SESSION["last_target_isenemy"] = false;
                $_SESSION["last_target_isfriend"] = false;
                $_SESSION["last_target_isnpc"] = false;
                $_SESSION["last_target_handsoff"] = false;
                $_SESSION["last_target_isbase"] = false;
                $_SESSION["last_target_isprotected"] = false;
            } else if (floor($_SESSION["playerloadcount"]) > 3) {
                $_SESSION["playerloadcount"] = 0;
            } else {
                $_SESSION["playerloadcount"] = floor($_SESSION["playerloadcount"]) + 1;
            }
            if ($_SESSION["playerloadcount"] <= 0) {
                $result = $mysqlidb->query($query);
                if ($result && ($result->num_rows > 0)) {
                    $row = $result->fetch_assoc();
                    $_SESSION["email"] = $row["email"];
                    $_SESSION["name"] = $row["name"];
                    $_SESSION["rank"] = $row["rank"];
                    $_SESSION["created"] = $row["created"];
                    $_SESSION["gcredits"] = $row["gcredits"];
                    $_SESSION["alliance"] = $row["alliance"];
                    $_SESSION["level"] = $row["level"];
                    $_SESSION["techs"] = $row["techs"];
                    $_SESSION["savelocs"] = $row["savelocs"];
                    $_SESSION["buffs"] = $row["buffs"];
                    if (checkprotected() == true) {
                        $_SESSION["player_status"] = "Central AI protected";
                    } else {
                        $_SESSION["player_status"] = "Normal";
                    }
                    if ((floor($_SESSION["rank"]) & 1) != 0) {
                        $_SESSION["player_status"] .= " (GM)";
                    }

                    $_SESSION["settings"] = $row["settings"];
                    $_SESSION["bio"] = $row["bio"];
                    $_SESSION["power"] = $row["power"];
                    $_SESSION["renown"] = $row["renown"];
                    $_SESSION["items"] = $row["items"];
                    $_SESSION["goalscompleted"] = $row["goalscompleted"];
                    $_SESSION["lang"] = $row["language"];

                    // update lastsaw field once every 20 seconds
                    if (($now % 20) == 0) {
                        $newbuffs = trimexpiredbuffs($_SESSION["buffs"]);
                        $query = "update player set lastsaw=$now,buffs='$newbuffs' $where_string";
                        $mysqlidb->query($query);
                        $_SESSION["buffs"] = $newbuffs;
                    }
                    // load ini values
                    loadinis();

                    $success = 1;
                    $retrycnt = 0;
                } else if ($retrycnt > 0) {
                    $mysqlidb = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);
                    if ($mysqlidb->connect_error) {
                        postlog("gpi failed to reconnect to database server: " . $mysqlidb->connect_error);
                    }
                } else {
                    postlog ($error_string . ": " . $query . " : " . $mysqlidb->error);
                }
            } else { // don't reload info at this time
                $retrycnt = 0;
            }
            $retrycnt--;
        } // while ($retrycnt > 0)
    }
    return $success;
}


/*
 * removeitemsquantity - removes a quantity of items
 *      itemstr is the input string of items from player record
 *          format is "type:level:quantity;type:level:quantity;..."
 *      remistr is list of items to remove
 *          format is same as above
 *  returns array of two strings. first is input string less removed items
 *      second is list of items not removed.
 *      on success, second list should be blank.
 */
function removeitemsquantity($itemstr, $remistr) {
    $resultstr = array(2);
    $resultstr[0] = $itemstr;
    $resultstr[1] = "";
    if ($itemstr == "") { // can't removed anything from nothing
        $resultstr[1] = $remistr;
    } else if ($remistr != "") { // need something to remove
        $flist = explode(";", $itemstr);
        $rlist = explode(";", $remistr);
        $resultstr[0] = ""; // clear results
        for ($jj = 0; $jj < count($rlist); $jj++) {
            if ($rlist[$jj] != "") {
                $rf = explode(":", $rlist[$jj]);
                for ($ii = 0; $ii < count($flist); $ii++) {
                    $ff = explode(":", $flist[$ii]);
                    if (($ff[0] == $rf[0]) && ($ff[1] == $rf[1])) { // found type match
                        if ($ff[2] >= $rf[2]) { // at least enough, remove them
                            if ($ff[2] == $rf[2]) {
                                $flist[$ii] = ""; // no more left
                            } else {
                                $flist[$ii] = "$ff[0]:$ff[1]:" . ($ff[2] - $rf[2]);
                            }
                            $rlist[$jj] = "";
                        } else {
                            // remove partial amount
                            $flist[$ii] = "";
                            $rf[2] = $rf[2] - $ff[2];
                            $rlist[$jj] = implode(":", $rf);
                        }
                        break;
                    } // if (($ff[0] == $rf[0]) && ($ff[1] == $rf[1]))
                } // for ($ii = 0; $ii < count($flist); $ii++)
            } // if ($rlist[$jj] != "")
        } // for ($jj = 0; $jj < count($rlist); $jj++)
        for ($ii = 0; $ii < count($flist); $ii++) {
            // recombine first list skipping blank entries
            if ($flist[$ii] != "") {
                if ($resultstr[0] != "") {
                    $resultstr[0] .= ";";
                }
                $resultstr[0] .= $flist[$ii];
            }
        }
        // now recombine components not removed skipping blank entries
        for ($jj = 0; $jj < count($rlist); $jj++) {
            if ($rlist[$jj] != "") {
                if ($resultstr[1] != "") {
                    $resultstr[1] .= ";";
                }
                $resultstr[1] .= $rlist[$jj];
            }
        }
    }
    return $resultstr;
}


/*
 * additemsquantity - adds a quantity of items
 *      itemstr is the input string of items from player record
 *          format is "type:level:quantity;type:level:quantity;..."
 *      addistr is list of items to remove
 *          format is same as above
 *  returns string of combined items in same format as itemstr
 */
function additemsquantity($itemstr, $addistr) {
    $resultstr = $itemstr;
    if ($itemstr == "") {
        $resultstr = $addistr;
    } else if ($addistr != "") { // need something to add
        $flist = explode(";", $itemstr);
        $alist = explode(";", $addistr);
        for ($jj = 0; $jj < count($alist); $jj++) {
            if ($alist[$jj] != "") {
                $af = explode(":", $alist[$jj]);
                for ($ii = 0; $ii < count($flist); $ii++) {
                    $ff = explode(":", $flist[$ii]);
                    if (($ff[0] == $af[0]) && ($ff[1] == $af[1])) { // found type match
                        $flist[$ii] = "$ff[0]:$ff[1]:" . ($ff[2] + $af[2]);
                        $alist[$jj] = "";
                        break;
                    } // if (($ff[0] == $af[0]) && ($ff[1] == $af[1]))
                } // for ($ii = 0; $ii < count($flist); $ii++)
            } // if ($alist[$jj] != "")
        } // for ($jj = 0; $jj < count($alist); $jj++)
        $resultstr = implode(";", $flist);
        // now add components not added above, skipping blank entries
        for ($jj = 0; $jj < count($alist); $jj++) {
            if ($alist[$jj] != "") {
                if ($resultstr != "") {
                    $resultstr .= ";";
                }
                $resultstr .= $alist[$jj];
            }
        }
    }
    return $resultstr;
}

/*
 * grantitem
 *  adds item to target player and posts chat announcement about it
 *
 *  itemstr format is "P:name:item:reason"
 */
function grantitem($ai, $itemstr) {
    global $mysqlidb;
    global $item_name;

    postlog("grantitem($ai, $itemstr)");

    $iparts = explode(":", $itemstr);
    if (count($iparts) > 2) {
        $query = "select items from player where name='{$iparts[0]}'";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            $row = $result->fetch_row();
            $items = $row[0];
            $query = "select code from store where item='{$iparts[1]}'";
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0)) {
                $row = $result->fetch_row();
                $code = $row[0];
                $items = additemsquantity($items, $code . ":1");

                $query = "update player set items='$items' where name='{$iparts[0]}'";
                $result = $mysqlidb->query($query);
                if ($result) {
                    postannouncment($ai, "Congratulations, {$iparts[0]} was awarded " . $item_name[$iparts[1]] . " {$iparts[2]}", false);
                }
            }
        }
    }
}


/*
 * levelplayer - extracts necessary resources from base and inserts
 *      timer entry to increase level of player AI
 */
function levelplayer($ai, $location) {
    global $mysqlidb;
    global $timer_name;

    $dlocation = "";
    $location = convertdloctoloc($location);
    if (is_array($_SESSION["bases"])) {
        foreach ($_SESSION["bases"] as $base) {
            if ($base["location"] != $location) {
                continue;
            }
            $dlocation = $base["dlocation"];
        }
    }
    $fail = "Failed to begin leveling of Master AI,";

    $rmsg = ""; // if still empty at end then we succeeded
    $nextlevel = $_SESSION["level"] + 1;
    if ($nextlevel > PLAYER_MAX_LEVEL) {
        $rmsg = "$fail already at max level";
    } else if (checktimedentry(TIMER_LEVEL_PLAYER, $ai, "", "", "")) {
        $rmsg = "$fail currently being leveled";
    } else if ($dlocation == "") {
        $rmsg = "$fail unable to location base info";
    } else {
        $items = "";
        $query = "select items from player where name='$ai';";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            $row = $result->fetch_row();
            $items = $row[0];
        }

        $base = array();
        $recipe = getrecipe($ai, RECIPE_TYPE_MAI, $_SESSION["level"], $_SESSION["level"], $base, 0, 0);
        if ($recipe == null) {
            $rmsg = "$fail unable to determine how to perform operation";
        } else {
            $fraglist = removeitemsquantity($items, $recipe["items"]);
            if ($fraglist[1] != "") {
                $fragarr = explode(";", $fraglist[1]);
                foreach ($fragarr as $fragstr) {
                    if ($rmsg == "") {
                        $rmsg = "$fail missing items: ";
                    } else {
                        $rmsg .= ", ";
                    }
                    $rmsg .= formatcomponent($fragstr, 1, 1);
                }
            }
        }
        if ($rmsg == "") { // okay to update player record
            $query = "update player set items='{$fraglist[0]}' where name='$ai';";
            $result = $mysqlidb->query($query);
            if (!$result) {
                $rmsg = "$fail the player record failed to update";
            } else { // player updated
                // create timer event
                $duration = $recipe["resarr"][MAX_NORMAL_RES];
                createtimedentry($duration, TIMER_LEVEL_PLAYER, $ai, "$location:$dlocation", "$location:$dlocation", 0,
                                        "", "", "", "", $recipe["items"], "");
                $rmsg = "{$timer_name[TIMER_LEVEL_PLAYER]}: via base at $dlocation. etc " . formatduration($duration);
            }
        }
    }
    if ($rmsg != "") {
        postreport ($ai, 0, $rmsg);
    }
}

/*
 * getlevelplayerline - returns line for control module that includes resources
 *      time, components and prequisites for increasing player AI level
 *      format "MAI|0|busy|max level|level|fuel|metal|mineral|xtal|time|comps|loc|basenam|baseloc"
 *  resources and comps are those for next level
 *  loc just included for consistancy with module line format
 */
function getlevelplayerline($ai, $loc) {
    $basename = "";
    $basedloc = "";

    $loc = convertdloctoloc($loc);

    if ($_SESSION["bases"] != "") {
        foreach ($_SESSION["bases"] as $base) {
            if ($base['hasplayerai'] != "0") {
                $basename = $base['name'];
                $basedloc = $base['dlocation'];
                break;
            }
        }
    }

    if (checktimedentry(TIMER_LEVEL_PLAYER, $ai, "", "", "")) {
        $busy = 1;
    } else {
        $busy = 0;
    }
    $mailevel = $_SESSION["level"];

    $base = array();
    $ret = getrecipeline($ai, RECIPE_TYPE_MAI, $mailevel, $mailevel, "MAI", $busy, PLAYER_MAX_LEVEL, $base, 0, 0)
                . "|$loc|$basename|$basedloc";

    return $ret;
}


/*
 * validate_name($name)
 *      will check if $name is a legal name
 *
 *      Legal names must conform to the following:
 *      Not "none", "rogue", "system", "unknown", or "beginner"
 *      Characters must be alphanumeric, spaces (   ),  underscores ( _ ), or dashes ( - )
 *
 *      return values:
 *          1 = name is valid
 *          2 = name is invalid
 *          3 = name is reserved
 */
function valid_name($name)
{
    // (\w| allows letters, digits and underscores ( _ )
    // |-| allows dashes
    // | ) allows spaces
    // {3,36} previous repeats 3 to 36 times
    $re = "/^(\w|-| ){3,36}$/";

    //reserved names
    $reserved_names = Array("all",
                            "none",
                            "rogue",
                            "system",
                            "unknown",
                            "beginner",
                            "cogniven",
                            "imaiy",
                            "global",
                            "sarshish",
                            "triton",

                            //reserved names for Bill Whitmore
                            "bill",
                            "whitmore",
                            "bill whitmore",

                            "control point",
                            "minor control point",
                            "major control point",
                            "central control",
                            "central ai",
                            "organization");

    if (in_array(strtolower($name), $reserved_names)) {
        return 3;
    } else if (!preg_match($re, $name)) {
        return 2;
    } else {
        return 1;
    }

}

/*
 * delete_player($ai)
 *
 * When an AI is deleted from a world, the following tasks need to be done.
 *
 * /chronicles, /sessions and /logs do not need to be modified.
 *
 * /graphics may need to be updated if they were using a custom avatar or owned an alliance with a custom logo
 *
 * Database needs to be updated as follows:
 *      account: No changes needed.
 *      alliance: If the AI is the owner of an alliance, that alliance needs to be disbanded.
 *      alliance_applications: Any applications this AI has needs to be removed.
 *      alliance_officers: If the AI is an officer, he needs to be removed.
 *      bases: Any bases need to be removed AFTER the drudge AIs
 *      chats: No changes needed.
 *      chronicles: Remove entries where the originator is the AI
 *      drudgeai: Remove drugeais in any of the AI bases BEFORE deleting bases.
 *      formations: Remove all formations where controller is the AI
 *      game_mail: if mfrom = AI, fromdeleted = true : if mto = AI, todeleted = true
 *      last_delete: Updated with the AIs email and current time
 *      player: Remove the AIs player record
 *      relations: Remove any relations where the AI is the target or the source.
 *      reports: Remove any reports where source = AI
 *      tells: no changes
 *      timer_queue: Remove any entries where originator is AI
 *      trade_queue: Remove any entries where poster is AI
 *      world:  where controller = AI, controller = none, o_type = 0, last viewed = current time
 *
 * Note:  Every database should be listed above.  If there is a database not on this list, it was added after this list was made and should be examined with regards to needed actions on an AI deletion.
 *
 *      return values:
 *          array[0] = true if successfully deleted, false otherwise
 *
 *          if array[0] = false, array[1], array[2]...array[x] contain the x
 *              error codes.
 *
*/
function delete_ai($ai) {
    global $mysqlidb;
    $errors = array(1);
    $errors[0] = true;

    $query = sprintf("select email, alliance from player where name='%s'",
        $mysqlidb->real_escape_string($ai));
    $result = $mysqlidb->query($query);
    if (!$result || ($result->num_rows == 0)) {
        //Failed to retrieve email, delete aborted.
        $errors[1] = "1:Failed to retrieve email.";
        $errors[0] = false;
        return $errors;
    }

    $row = $result->fetch_row();
    $email = $row[0];
    $alliance = $row[1];

    //account: No changes needed.

    //alliance: If the AI is the owner of an alliance, that alliance needs to be disbanded.
    $temp = new Alliance();
    $temp->open($alliance);
    if ($temp->is_owner($ai))
    {
        $code = $temp->disband($ai);
        if ($code != 1)
        {
            $errors[] = "2:".$code.":Failed to disband alliance where ai is owner.";
        }
    }

    //alliance_officers: If the AI is an officer, he needs to be removed.
    if ($temp->is_officer($ai))
    {
        $code = $temp->resign_officer($ai);
        if ($code != 1)
        {
            $errors[] = "3:".$code.":Failed to resign officer where ai is an officer.";
        }
    }

    //alliance_applications: Any applications this AI has needs to be removed.
    $query = sprintf("delete from alliance_applications where applicant='%s'",
        $mysqlidb->real_escape_string($ai));
    $result = $mysqlidb->query($query);
    if (!$result)
    {
        $errors[] = "4:Failed to delete from alliance_applications.";
    }

    //drudgeai: Remove drugeais in any of the AI bases BEFORE deleting bases.
    $query = sprintf("select drudgeais from bases where controller='%s'",
        $mysqlidb->real_escape_string($ai));
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0))
    {
        while ($row = $result->fetch_row())
        {
            $query = sprintf("delete from drudgeai where entry in (%s)",
                $mysqlidb->real_escape_string($row[0]));
            $mysqlidb->query($query);
        }
    } else {
        $errors[] = "5:Failed to delete from drudgeais.";
    }

    //bases: Any bases need to be removed AFTER the drudge AIs
    $query = sprintf("delete from bases where controller='%s'",
            $mysqlidb->real_escape_string($ai));
    $result = $mysqlidb->query($query);
    if (!$result)
    {
        $errors[] = "6:Failed to delete bases.";
    }

    //chats: No changes needed.  Will only pull information from after
    //the new account is created.

    //chronicle: No changes needed.  Will only pull information from after
    //the new account is created.

    //formations: Remove all formations where controller is the AI
    $query = sprintf("delete from formations where controller='%s'",
        $mysqlidb->real_escape_string($ai));
    $result = $mysqlidb->query($query);
    if (!$result)
    {
        $errors[] = "7:Failed to delete formations.";
    }

    //game_mail: if mfrom = AI, fromdeleted = true : if mto = AI, todeleted = true
    $query = sprintf("update game_mail set fromdeleted=true where mfrom='%s'",
        $mysqlidb->real_escape_string($ai));
    $result = $mysqlidb->query($query);
    if (!$result)
    {
        $errors[] = "8:Failed to set fromdelete in game_mail.";
    }

    $query = sprintf("update game_mail set todeleted=true where mto='%s'",
        $mysqlidb->real_escape_string($ai));
    $result = $mysqlidb->query($query);
    if (!$result)
    {
        $errors[] = "9:Failed to set todelete in game_mail.";
    }

    //player: Remove the AIs player record
    $query = sprintf("delete from player where name='%s'",
        $mysqlidb->real_escape_string($ai));
    $result = $mysqlidb->query($query);
    if (!$result)
    {
        $errors[] = "10:Failed to delete player.";
    }

    //relations: Remove any relations where the AI is the target or the source.
    $query = sprintf("delete from relations where source='%s' or target='%s'",
        $mysqlidb->real_escape_string($ai),
        $mysqlidb->real_escape_string($ai));
    $result = $mysqlidb->query($query);
    if (!$result)
    {
        $errors[] = "11:Failed to delete relations.";
    }

    //reports: No changes needed.  Will only pull information from after
    //the new account is created.

    //tells: no changes

    //timer_queue: Remove any entries where originator is AI
    $query = sprintf("delete from timer_queue where originator='%s'",
        $mysqlidb->real_escape_string($ai));
    $result = $mysqlidb->query($query);
    if (!$result)
    {
        $errors[] = "12:Failed to delete timer_queue.";
    }

    //trade_queue: Remove any entries where poster is AI
    $query = sprintf("delete from trade_queue where poster='%s'",
        $mysqlidb->real_escape_string($ai));
    $result = $mysqlidb->query($query);
    if (!$result)
    {
        $errors[] = "13:Failed to delete trade_queue.";
    }

    //world:  where controller = AI, controller = none, o_type = 0, last viewed = current time
    $query = sprintf("update world set controller='none',o_type=0,last_viewed=".(time()*1000)." where controller='%s'",
        $mysqlidb->real_escape_string($ai));
    $result = $mysqlidb->query($query);
    if (!$result)
    {
        $errors[] = "14:Failed to update world for controller.";
    }

    //last_delete: Updated with the AIs email and current time
    $query = sprintf("delete from last_delete where email='%s'",
        $mysqlidb->real_escape_string($email));
    $mysqlidb->query($query);

    $query = sprintf("insert into last_delete (email, deletion_time) values ('%s', now())",
        $mysqlidb->real_escape_string($email));
    $result = $mysqlidb->query($query);
    if (!$result)
    {
        $errors[] = "15:Failed to update last_delete.";
    }

    if (isset($errors[1]))
    {
        $errors[0] = false;
    }

    return $errors;
}

/*
 * getplayerlines -
 *  returns list of players
 *  format: P|name|created|level|org|avatar|power|renown|...
 *  sort order is -1 for by name ascending, 0 for by level descending,
 *      1 for power descending, and 2 for renown decending.
 *
 *  rank is a bitwise setting in player record with the following meanings
 *      1 = flagged as GM
 *      2 = GM is visible in people list (ignored if 1 not set)
 *      4 = has access to GM commands
 *      8 = can see online/offline status for everyone
 */
function getplayersline($sort) {
    global $mysqlidb;
    $now = time();
    $ret = "p|No players found";
    if ($sort == "") { // don't confuse blank with zero
        $sort = -1;
    }
    $order = "name asc";
    switch ($sort) {
        case 0:
            $order = "level desc";
            break;
        case 1:
            $order = "power desc";
            break;
        case 2:
            $order = "renown desc";
            break;
    }
    $query = "select name,created,level,alliance,settings,power,renown,bio,lastsaw,rank from player left join (select useremail,max(touched) as last from sessions where useremail!=gamekey) as s1 on (player.email=s1.useremail) order by $order";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $ret = "P|";
        while ($row = $result->fetch_row()) {
            $online = "";
            if (((floor($_SESSION["rank"]) & 8) != 0) ||
                    (($_SESSION["alliance"] != "Beginner")
                    && ($_SESSION["alliance"] != "")
                    && ($_SESSION["alliance"] == $row[3]))) {
                if ($row[8] && (floor($row[8])+25) >= $now) {
                    // if last saw update no older than 25 seconds then they are online.
                    $online = "Online";
                } else {
                    $online = "Offline";
                }
            }
            $av = "blank.png";
            $sa = explode(";", $row[4]);
            foreach ($sa as $set) {
                $seta = explode(":", $set);
                if (count($seta) > 1) {
                    if ($seta[0] == "SAP") {
                        $av = $seta[1];
                        break;
                    }
                }
            }
            $bio = str_replace("\n", "<br/>", $row[7]);
            if ((floor($row[9]) & 1) != 0) {
                if ((floor($row[9]) & 2) == 0) {
                    $name = "";
                } else {
                    $name = "(GM){$row[0]}";
                }
            } else {
                $name = $row[0];
            }
            if ($name != "") {
                $ret .= "$name|{$row[1]}|{$row[2]}|{$row[3]}|$av|{$row[5]}|{$row[6]}|$bio|$online|";
            }
        }
    }
    return $ret;
}

/*
 * updatedsavelocs - updates saved locations string in player record
 *      does nothing if update fails.
 */
function updatesavelocs ($ai, $locs) {
    global $mysqlidb;
    while (strlen($locs) > 4000) {
        // remove entries until string is short enough
        $locarr = explode(";", $locs);
        array_pop($locarr);
        $locs = implode(";", $locs);
    }
    $query = sprintf("update player set savelocs='%s' where name='$ai'",
        $mysqlidb->real_escape_string($locs));
    $mysqlidb->query($query);
    $_SESSION["savelocs"] = $locs; // update session info now
}

/*
 * useitem - activate item specified
 *      item must exist in players item list
 *  item string must match code column entry in store table
 *      format "key:value:quantity"
 *  target is target base location
 */
function useitem($ai, $item, $target) {
    global $mysqlidb;
    global $item_name;
    $hasitems = "";
    $hasbuffs = "";
    $found = false;
    $success = false;

    $dtarget = "";
    $target = convertdloctoloc($target);
    if (is_array($_SESSION["bases"])) {
        foreach ($_SESSION["bases"] as $base) {
            if ($base["location"] == $target) {
                $dtarget = $base["dlocation"];
                break;
            }
        }
    }

    $ip = explode(":", $item);
    if (count($ip) < 3) {
        postreport($ai, 0, "Unable to use item, invalid format");
        postlog("Failed to use item for $ai, format invalid: $item, $target");
        return;
    }
    $usecount = floor($ip[2]);

    // verify player has item and remove
    $query = "select items,buffs from player where name='$ai'";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_row();
        $hasitems = $row[0];
        $hasbuffs = $row[1];
    }

    // items in player table format "key:value:quantity"
    // find and extract $item from items column. Has side effect of removing
    //  anything else with 0 quantity or bad format
    $newitems = "";
    $action = null;
    $hiarr = explode(";", $hasitems);
    foreach ($hiarr as $hi) {
        $arr = explode(":", $hi);
        if (count($arr) > 2) {
            $hascount = floor($arr[2]);
            if ($arr[0] == $ip[0]) {
                if ($hascount >= $usecount) {
                   $hascount -= $usecount;
                } else {
                    $usecount = $hascount;
                    $hascount = 0;
                }
               $found = true;
            }
            if ($hascount > 0) {
               $arr[2] = $hascount;
               if ($newitems != "") {
                   $newitems .= ";";
               }
               $newitems .= implode(":", $arr);
            }
        }
    }

    if ($found == true) {
        $newbuffs = $hasbuffs;
        $itemname = $item_name[$ip[0]];
        $code = $ip[0] . ":" . $ip[1];
        $query = "select action from store where code='$code'";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            $row = $result->fetch_row();
            $action = explode("-", $row[0]);
        }
    }
    if (($action != null) && (count($action) > 1)) {
        // perform action
        switch ($action[0]) {
            case "PA": // protection agreement
                if (anyreinforcementsout($ai) == true) {
                    postreport($ai, 0, "Unable to activate $itemname while reinforcing a location controlled by another Master AI");
                } else {
                    while ($usecount > 0) {
                        $newbuffs = addorextendbuff($ai, $newbuffs, $action[1], $ip[1], $target, $itemname);
                        if ($newbuffs == null) {
                            postreport($ai, 0, "Failed to activate $itemname");
                            break;
                        }
                        $usecount--;
                    }
                    $success = true;
                }
                break;
            case "BUFF": // temporary enhancment
                while ($usecount > 0) {
                    $newbuffs = addorextendbuff($ai, $newbuffs, $action[1], $ip[1], $target, $itemname);
                    if ($newbuffs == null) {
                        postreport($ai, 0, "Failed to activate $itemname");
                        break;
                    }
                    $usecount--;
                }
                // buff changed so kick queue to recalc generation amounts
                $query = "update bases set kick=1 where location='$target'";
                $result = $mysqlidb->query($query);
                $success = true;
                break;
            case "DELRES": // deliver resource
            case "DELDPO": // deliver drone package
                $dur = floor(1800 * getdeliverymult());
                while ($usecount > 0) {
                    createtimedentry($dur, TIMER_CENTRAL_DELIVER, $ai, "", "$target:$dtarget", 0, "", "", "", "", "", $itemname.";".$action[1]);
                    // report what and when
                    postreport($ai, 0, "Executing $itemname, eta ". formatduration($dur));
                    $usecount--;
                }
                $success = true;
                break;
            case "TELE":
                $tparts = explode(":", $target);
                if (count($tparts) < 2) {
                    // just print string to trigger popup of dialog
                    $tstr = "TELE|$item|{$action[1]}|";
                    if (is_array($_SESSION["bases"])) {
                        foreach ($_SESSION["bases"] as $base) {
                            $tstr .= "{$base['location']}:{$base['dlocation']}={$base['name']};";
                        }
                    }
                    echo "$tstr\n";
                } else if (countallformations($ai, $tparts[0]) > 0) {
                    postreport($ai, 0, "Unable to activate $itemname while any formation is out of base");
                } else {
                    // do the actual base teleport and remove item
                    if ($action[1] != "ADV") {
                        // block or local random
                        $targ = explode(".", $tparts[1]);
                        $loc = getavailableloc($targ[0]);
                    } else {
                        // advanced
                        $loc = $tparts[1];
                    }

                    if (($loc != null) && (movebase($ai, $tparts[0], $loc) == true)) {
                        $success = true;
                    }
                }
                break;
            default:
                postreport($ai, 0, "Unable to use $usecount of $itemname, no associated action");
                postlog("Failed to use $usecount item(s) for $ai, no action: $item, $target");
                break;
        }
        if ($success == true) {
            // update player record with $newitems
            $query = "update player set items='$newitems',buffs='$newbuffs' where name='$ai'";
            $mysqlidb->query($query);
            $_SESSION["buffs"] = $newbuffs;
        }
    }
}

/*
 * addorextendbuff -
 *  adds time to existing buff or adds new buff into buff string
 *  format:
 *      has "key:start:end:basekey;key:start:end:basekey;..." where start/end are time() values
 *      buf "key:duration" where duration is in minutes
 *  type is either A or B. B requires base, A specified 'all' for all bases.
 */
function addorextendbuff($ai, $has, $buf, $type, $base, $itemname) {
    $ret = $has;
    if ($ret == null) {
        $ret = "";
    }
    $found = false;
    $newbarr = explode(":", $buf);
    if ($type == "A") {
        $base = "all";
        $inbase = "";
    } else {
        $base = baseloctokey($base);
        if ($base == null) {
            return null; // failed to translate to key
        }
        $inbase = "in base " . basekeytoname($base);
    }

    if (($has != null) && ($has != "")) {
        $harr = explode(";", $has);
        for ($idx = 0; $idx < count($harr); $idx++) {
            $hbarr = explode(":", $harr[$idx]);
            if (count($hbarr) < 4) {
                $hbarr[] = $base; // tack on global for old buffs that don't specify it
            }
            if (count($hbarr) > 3) {
                if (($hbarr[0] == $newbarr[0]) && ($hbarr[3] == $base)) {
                    // extend end time
                    $found = true;
                    $hbarr[2] = floor($hbarr[2]) + ($newbarr[1] * 60);
                    $harr[$idx] = implode(":", $hbarr);
                }
            }
        }
        $ret = implode(";", $harr);
    }
    if ($found == false) {
        // tack onto end
        if ($ret != "") {
            $ret .= ";";
        }
        $start = time();
        $end = $start + ($newbarr[1] * 60);
        $ret .= "{$newbarr[0]}:$start:$end:$base" ;

        postreport($ai, 0, "Activating $itemname $inbase");
    }
    return $ret;
}


/*
 * checkprotected -
 *  check buffs for beginner or regular protection
 *  return true if found, false otherwise
 */
function checkprotected($buffs = null) {
    $protected = false;

    if ($buffs == null) {
        $buffs = $_SESSION["buffs"];
    }
    $now = time();
    $barr = explode(";", $buffs);
    foreach ($barr as $bf) {
        $bfa = explode(":", $bf);
        if (count($bfa) > 2) {
            if (($bfa[0] == "CAIBP") || ($bfa[0] == "CAIPA")) {
                if (($now >= $bfa[1]) && ($now <= $bfa[2])) {
                    $protected = true;
                    break;
                }
            }
        }
    }
    return $protected;
}

/*
 * buffactive
 *  checks for one or more buffs and returns true if at least
 *      specified number of buffs is active for the specified base
 *
 *  buffs can be a single buf name or multiple separated by ';'
 *
 */
function buffactive($bufs, $base, $number = 1) {
    $active = false;
    if ($bufs != null) {
        $check = explode(";", $bufs);
        if ($base != "") {
            $base = baseloctokey($base);
            if ($base == null) {
                $base = ""; // failed to get key but may succeed if all type buff
            }
        }

        $now = time();
        $barr = explode(";", $_SESSION["buffs"]);
        $count = 0;
        foreach ($barr as $bf) {
            $bfa = explode(":", $bf);
            if (count($bfa) > 3) {
                foreach ($check as $cbuf) {
                    if (($bfa[0] == $cbuf) && (($bfa[3] == "all") || ($bfa[3] == $base))) {
                        if (($now >= $bfa[1]) && ($now <= $bfa[2])) {
                            $count++;
                        }
                    }
                }
            }
        }
        if ($count >= $number) {
            $active = true;
        }
    }

    return $active;
}

/*
 * getdronespeedmult -
 *  check buffs for drone speed boost
 *      returns multiplier to represent speed change (25% boost = 1.25 multiplier)
 */
function getdronespeedmult() {
    $mult = 1.0;
    if (buffactive("BUFF5", "")) {
         // 25% increase in speed
        $mult = 1.25;
    }
    return $mult;
}

/*
 * getdeliverymult -
 *  checks buffs for trade deliver boost and returns multiplier
 */
function getdeliverymult() {
    $mult = 1.0;
    if (buffactive("BUFF7", "")) {
         // 50% decrease in delivery times
        $mult = 0.5;
    }
    return $mult;
}

/*
 * getmodulebuildmult -
 *  check buffs for module construction boost
 *      returns multiplier to represent speed change (25% boost = 0.75 multiplier)
 */
function getmodulebuildmult($base) {
    $mult = 1.0;
    if (buffactive("BUFF4", $base)) {
        // 25% increase in construction speed
        $mult = 0.75;
    }
    return $mult;
}

/*
 * getmodulebuildadd -
 *  check buffs for module construction count boost
 *      returns number of extra modules that can be constructed at once
 */
function getmodulebuildadd($base) {
    $add = 0;
    if (buffactive("BUFF8", $base)) {
        // increase of 2
        $add = 2;
    }
    return $add;
}

/*
 * getresourcegenmult -
 *  check buffs for resource boost
 *  $res is resource number 0-3 for Fuel, Metal, Mineral, Crystal respectively
 *      returns multiplier to represent boost (25% boost = 1.25 multiplier)
 */
function getresourcegenmult($res, $base) {
    $mult = 1.0;
    $buff25 = array("BUFF0", "BUFF1", "BUFF2", "BUFF3");

    if (($res >= 0) && ($res < count($buff25))) {
        if (buffactive($buff25[$res], $base)) {
            $mult = 1.25;
        }
    }
    return $mult;
}

/*
 * getbufflist -
 *  returns string of currently active buffs
 *      format "buff:start:end:base name;buff:start:end:base name;..."
 * this is same format as stored in player record, however base keys are
 *  replaced with base names
 */
function getbufflist() {
    $barr = explode(";", $_SESSION["buffs"]);
    foreach ($barr as $idx=>$buff) {
        $bparts = explode(":", $buff);
        if (count($bparts) > 2) {
            if (count($bparts) < 4 ) {
                $bparts[] = "all"; // tack on global flag for old buffs
                $barr[$idx] = implode(":", $bparts);
            } else if (count($bparts) > 3) {
                if ($bparts[3] != "all") {
                    $bparts[3] = basekeytoname($bparts[3]);
                    $barr[$idx] = implode(":", $bparts);
                }
            }
        }
    }
    return implode(";", $barr);
}

/*
 * trimexpiredbuffs -
 *  returns string of currently active buffs with any expired buffs removed
 *      format "buff:start:end:base name;buff:start:end:base name;..."
 */
function trimexpiredbuffs($buffs) {
    $now = time();
    $barr = explode(";", $buffs);
    $newbuffs = "";
    foreach ($barr as $buff) {
        $bparts = explode(":", $buff);
        if (count($bparts) < 4 ) {
            $bparts[] = "all"; // tack on global flag for old buffs
        }
        if (count($bparts) > 3) {
            if ($now < $bparts[2]) {
                // not expired;
                if ($newbuffs != "") {
                    $newbuffs .= ";";
                }
                $newbuffs .= $buff;
            }
        }
    }
    return $newbuffs;
}

/*
 * claimdaily -
 *  attempt to claim daily awards
 */
function claimdaily($ai) {
    global $mysqlidb;
    global $item_name;

    $claims = 0;
    $hasitems = "";
    $claimstr = "";
    $query = "select dailyclaims,items from player where name='$ai'";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_row();
        $claims = $row[0];
        $hasitems = $row[1];
    }

    if ($claims > 0) {
        // get store entries with daily_weight > 0
        $query = "select code,daily_weight,item from store where daily_weight>0";
        $result = $mysqlidb->query($query);
        $randrange = 0;
        if ($result && ($result->num_rows > 0)) {
            // sum all weights to determine max ran
            while ($row = $result->fetch_row()) {
                $randrange += $row[1];
            }
        }
        if ($randrange > 0) {
            $harr = explode(";", $hasitems);
            while ($claims > 0) {
                // select random number
                $irand = mt_rand(0, $randrange-1);

                // walk through store entries to determine which one to claim
                $itemcode = "";
                $item = "";
                $result->data_seek(0);
                while ($row = $result->fetch_row()) {
                    $irand -= $row[1];
                    if ($irand <= 0) {
                        $itemcode = $row[0];
                        $item = $row[2];
                        break;
                    }
                }
                if (($item != "") && ($itemcode != "")) {
                    $ia = explode (":", $itemcode);

                    // add to $hasitems
                    $found = false;
                    for ($idx = 0; $idx < count($harr); $idx++) {
                        $hi = explode(":", $harr[$idx]);
                        if (count($hi) > 2) {
                            if (($hi[0] == $ia[0]) && ($hi[1] == $ia[1])) {
                                $hi[2] = floor($hi[2]) + 1; // increment existing
                                $harr[$idx] = implode(":", $hi);
                                $found = true;
                                break;
                            }
                        }
                    }
                    if ($found == false) {
                        $harr[] = "$itemcode:1";
                    }
                    if ($claimstr != "") {
                        $claimstr .= ", ";
                    }
                    $claimstr .= $item_name[$item];
                }
                $claims--;
            }
            $hasitems = implode(";", $harr);

            // update player record with new items and set claims to 0
            $query = "update player set dailyclaims=0,items='$hasitems' where name='$ai'";
            $mysqlidb->query($query);
        }
    }
    if ($claimstr == "") {
        postreport($ai, 0, "No dailies to claim at this time, check again after daily communication disruption");
    } else {
        postreport($ai, 0, "Central AI has delivered: $claimstr");
    }
}

/*
 * enhancedfeatureaccess
 *  returns true if player has access to enhanced features
 *      false otherwise
 */
function enhancedfeatureaccess() {
    if (key_exists('noaddsthru', $_SESSION) && ($_SESSION['noaddsthru'] >= time())) {
        return true;
    }
    if (key_exists('rank', $_SESSION) && ((floor($_SESSION["rank"]) & 1) != 0)) {
        return true;
    }
    return false;
}


?>
