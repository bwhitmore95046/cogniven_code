<?php
/*
 * Functions to deal with - goals
 * Author: Chris Bryant
 */

/*
 * getgoals -
 *  retrieves goals from dbase and returns array of two strings
 *  goal is placed into second string instead of first if its key
 *      appears in player completedgoals string
 *  format of first string:
 *   "GLA|key:completed:reward;key:completed:reward;..."
 *      completed is 0 if goal is not met and 1 if goal met
 *  format of second string:
 *   "GLC|key;key;..."
 */
function getgoals($ai) {
    global $mysqlidb;

    $list = array ("GLA|", "GLC|");

    $query = "select id,goal,reward from goals order by displayorder asc";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        while ($row = $result->fetch_row()) {
            if (strpos($_SESSION["goalscompleted"], "(".$row[0].")") === false) {
                $completed = ((goalcheck($ai, $row[1]) == true) ? "1" : "0");
                // change "key:num;key:num" format to "key.num,key.num"
                $reward = str_replace(":", ".", str_replace(";", ",", $row[2]));
                $list[0] .= $row[0] . ":" . $completed . ":" . $reward . ";";
            } else {
                $list[1] .= $row[0] . ";";
            }
        }
    }

    return $list;
}

/*
 * checkgoal
 *  checks if all goals in goalstr are satisfied
 *  return true if satisfied, false otherwise
 *      uses same format is similar to construction meetspreq check in
 *      component.php
 *  goalstr may contain multiple subgoals separated by ';'
 *
 *  t:key:level = tech skill 'key' must be at least 'level'
 *  mm:key:level = module 'key' (in any base) must be at least 'level'
 *  bb:number:level = 'number' bases must be at least 'level', can also be used to require # of bases by specifying level 1
 *  bn:number:Base = number bases must not have default base name
 *  p::level = master AI must be at least 'level'
 *  dai:number:count = 'count' drudgeais in 'number' bases, if 'number' is 1 then 'count' drudgeais must be in any 1 base
 *                         otherwise may be spread among multiple bases (count is ignored in this second case)
 *  dair:: = must have drudgeais in any one base assigned to production, sensor, construction and base manager roles
 *  comp:key:quantity = any base must contain quantity of 'key' components
 *  drone:key:quantity = any base must contain quantity of 'key' drones
 *
 */
function goalcheck($ai, $goalstr) {
    global $mysqlidb;
    $ret = true;

    $garr = explode(";", $goalstr);
    foreach ($garr as $goal) {
        $gparts = explode(":", $goal);
        if (count($gparts) > 2) {
            switch ($gparts[0]) {
                case "dair":
                    $found = false;
                    if ($_SESSION["bases"] != "") {
                        foreach ($_SESSION["bases"] as $base) {
                            $query = "select count(*) from drudgeai where entry in ({$base['drudgeais']}) and role in ("
                                            . DRUDGEAI_ROLE_RESOURCE . ","
                                            . DRUDGEAI_ROLE_SENSOR . ","
                                            . DRUDGEAI_ROLE_CONST . ","
                                            . DRUDGEAI_ROLE_BASE . ")";
                            $result = $mysqlidb->query($query);
                            if ($result && ($result->num_rows > 0)) {
                                $row = $result->fetch_row();
                                if ($row[0] >= 4) {
                                    $found = true;
                                    break;
                                }
                            }
                        }
                    }
                    if ($found == false) {
                        $ret = false;
                    }
                    break;
                case "t":
                    if (gettechlevel($ai, $_SESSION["techs"], $gparts[1]) < $gparts[2]) {
                        $ret = false;
                    }
                    break;
                case "mm":
                    $found = false;
                    if ($_SESSION["bases"] != "") {
                        foreach ($_SESSION["bases"] as $base) {
                            if (getmodulelevel($gparts[1], $base["infra"]) >= $gparts[2]) {
                                $found = true;
                                break;
                            }
                        }
                    }
                    if ($found == false) {
                        $ret = false;
                    }
                    break;
                case "comp":
                    $found = false;
                    if ($_SESSION["bases"] != "") {
                        foreach ($_SESSION["bases"] as $base) {
                            if (getcomponentquantity($base["components"], $gparts[1]) >= $gparts[2]) {
                                $found = true;
                                break;
                            }
                        }
                    }
                    if ($found == false) {
                        $ret = false;
                    }
                    break;
                case "drone":
                    $found = false;
                    if ($_SESSION["bases"] != "") {
                        foreach ($_SESSION["bases"] as $base) {
                            if (getdronecount($base["drones"], $gparts[1]) >= $gparts[2]) {
                                $found = true;
                                break;
                            }
                        }
                    }
                    if ($found == false) {
                        $ret = false;
                    }
                    break;
                case "bb":
                    $count = 0;
                    if ($_SESSION["bases"] != "") {
                        foreach ($_SESSION["bases"] as $base) {
                            if ($base["level"] >= $gparts[2]) {
                                $count++;
                            }
                        }
                    }
                    if ($count < $gparts[1]) {
                        $ret = false;
                    }
                    break;
                case "bn":
                    $count = 0;
                    if ($_SESSION["bases"] != "") {
                        foreach ($_SESSION["bases"] as $base) {
                            if ($base["name"] != $gparts[2]) {
                                $count++;
                            }
                        }
                    }
                    if ($count < $gparts[1]) {
                        $ret = false;
                    }
                    break;
                case "p":
                    if ($_SESSION["level"] < $gparts[2]) {
                        $ret = false;
                    }
                    break;
                case "dai":
                    $total = 0;
                    if ($_SESSION["bases"] != "") {
                        foreach ($_SESSION["bases"] as $base) {
                            $count = 0;
                            $dparts = explode(",", $base["drudgeais"]);
                            foreach ($dparts as $dp) {
                                if (strlen($dp) > 0) {
                                    $count++;
                                }
                            }
                            if (($gparts[1] == "1") && ($count > $gparts[2])) {
                                $total = $gparts[2];
                                break; // done and it passed
                            } else {
                                $total += $count;
                            }
                        }
                    }
                    if ($total < $gparts[2]) {
                        $ret = false;
                    }
                    break;
            } // switch ($gparts[0])
        }
        if ($ret == false) {
            break; // stop at first failure
        }
    }

    return $ret;
}

/*
 * goalclaim
 *  claims reward for completing a goal.
 *  performs check to insure goal was completed and reward not already claimed
 *
 *  key for claimed rewards is stored in goalscompleted column of player record
 */
function goalclaim($ai, $key) {
    global $mysqlidb;
    global $goal_name;
    global $item_name;

    if (isset($_SESSION["goalscompleted"])) {
        if (strpos($_SESSION["goalscompleted"], $key) === false) {
            $query = "select goal,reward from goals where id='$key'";
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0)) {
                $row = $result->fetch_row();
                // check if goal really met
                if (goalcheck($ai, $row[0]) == false) {
                    postreport($ai, 0, "Condition not yet met for claiming reward for " . $goal_name[$key]);
                } else {
                    // deliver items to player and update completed goals
                    $rparts = explode(";", $row[1]);
                    $rewardquantity = null;
                    $rstr = "";
                    foreach ($rparts as $rp) {
                        $rpp = explode(":", $rp);
                        if (count($rpp) > 1) {
                            if ($rstr != "") {
                                $rstr .= ",";
                            }
                            $rstr .= "'{$rpp[0]}'";
                            $rewardquantity[$rpp[0]] = $rpp[1];
                        }
                    }
                    $newitems = $_SESSION["items"];
                    $rewardstr = "";
                    $query = "select item,code from store where item in ($rstr)";
                    $sresult = $mysqlidb->query($query);
                    if ($sresult && ($sresult->num_rows > 0)) {
                        while (($srow = $sresult->fetch_row()) != null) {
                            $newitems = additemsquantity($newitems, $srow[1] . ":" . $rewardquantity[$srow[0]]);
                            if ($rewardstr != "") {
                                $rewardstr .= ", ";
                            }
                            $rewardstr .= $rewardquantity[$srow[0]] . " of " . $item_name[$srow[0]];
                        }
                    }
                    $_SESSION["items"] = $newitems;
                    $_SESSION["goalscompleted"] .= "($key)";

                    $query = "update player set goalscompleted='" . $_SESSION["goalscompleted"] . "',items='" . $newitems . "' where name='$ai'";
                    $result = $mysqlidb->query($query);
                    if ($result) {
                        postreport($ai, 0, "Delivered $rewardstr for completing " . $goal_name[$key]);
                    }
                }
            } else {
                postlog("Error: $ai attempted to claim reward for $key which was not found in dbase");
            }
        } else {
            postreport($ai, 0, "Reward was already claimed for " . $goal_name[$key]);
        }
    }
}

?>
