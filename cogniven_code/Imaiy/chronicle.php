<?php
/*
 * Functions to deal with central information retrieval - game stats
 * Author: Chris Bryant
 */
/*
 * getchroniclelist -
 *  prints list of chronicles
 *  for ai limited to target if specified
 */
function getchroniclelist($ai, $last, $source, $target) {
    global $mysqlidb;
    $list = Array();
    if ($last == "0") {
        $list[0] = "CH||1|1|No chronicles found";
    }
    if (key_exists("created", $_SESSION)) {
        $account_created = $_SESSION["created"];
        $count = 0;
        $tselect = "";
        if (($source != "") && ($target != "")) {
            $tselect = "and (sourceloc='$source' or targetloc='$target')";
        } else if ($target != "") {
            $tselect = "and targetloc='$target'";
        } else if ($source != "") {
            $tselect = "and sourceloc='$target'";
        }

        $query = "select entry,`read`,title,tagged from chronicle where originator='$ai' $tselect and state=1 and entry>$last and created>='$account_created' order by entry desc";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            while ($row = $result->fetch_row()) {
                $tag = "1";
                if ($row[3] != "0") {
                    $tag = "2";
                }
                $list[$count] = "CH|{$row[0]}|$tag|{$row[1]}|{$row[2]}";
                $count++;
            }
        }
    }
    return $list;
}

/*
 * getorgchroniclelist -
 *  gets list of chronicles
 *  published to an organization. Always return none found
 *  if org name is empty
 */
function getorgchroniclelist($org) {
    global $mysqlidb;
    $list = array("");
    $count = 0;
    if ($org != "") {
        $query = "select entry,title from chronicle where organization='$org' and state=1 order by entry desc limit ".MAXCHRONICLE;
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            while ($row = $result->fetch_row()) {
                $list[$count] = "{$row[0]};{$row[1]}";
                $count++;
            }
        }
    }
    return $list;
}

/*
 * printchroniclelist - prints list of chronicles
 *  for ai limited to target if specified
 */
function printchroniclelist($ai, $last) {
    $list = getchroniclelist($ai, $last, "", "");
    if ($list) {
        foreach ($list as $line) {
            print $line . "\n";
        }
    }
}

/*
 * printchronicle - prints complete info of chronicle
 */
function printchronicle($ai, $entry) {
    global $mysqlidb;

    $org = $_SESSION["alliance"];
    $query = "select `read`,title,attackers,defenders,results,type,publishedby from chronicle where (originator='$ai' or organization='$org') and state=1 and entry=$entry";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_row();
        $read = $row[0];
        $title = $row[1];
        $results = $row[4];
        $type = $row[5];
        $published = $row[6];
        $plyrlist = "";
        $atkrlist = "";
        $atkarr = explode("/", $row[2]);
        foreach ($atkarr as $atk) {
            $atker = explode("|", $atk);
            if (count($atker) > 6) {
                // build list of player names
                if ($plyrlist != "") {
                    $plyrlist .= ",";
                }
                $plyrlist .= "'" . $atker[0] . "'";

                // just extract name:start drones:lost drones
                if ($atkrlist != "") {
                    $atkrlist .= "<";
                }

                //if formation does not belong to this player, obscure the amount of troops present
                if ($atker[0] != $ai) {
                    //break up formation list
                    $drone_list = explode(";", $atker[5]);
                    foreach ($drone_list as &$drone_type) {
                        $drone_details = explode(":", $drone_type);
                        if (isset($drone_details[2])) {
                            $drone_details[2] = '?';
                        }
                        $drone_type = implode(":", $drone_details);
                    }
                    $atker[5] = implode(";", $drone_list);
                }

                $atkrlist .= $atker[0] . "/" . $atker[3] . "/" . $atker[5] . "/" . $atker[6];
            }
        }
        $dfndrlist = "";
        if ($type != 0) {
            $dfndarr = explode("/", $row[3]);
            foreach ($dfndarr as $def) {
                $dfndr = explode("|", $def);
                if (count($dfndr) > 8) {
                    // build list of player names
                    if ($plyrlist != "") {
                        $plyrlist .= ",";
                    }
                    if ($dfndr[0] == 'system') {
                        $dfndr[0] = 'rogue';
                    }
                    $plyrlist .= "'" . $dfndr[0] . "'";

                    // just extract name:drudgeai:start drones:lost drones:start defenses:lost defenses
                    if ($dfndrlist != "") {
                        $dfndrlist .= "<";
                    }

                    //if formation does not belong to this player, obscure the amount of troops present
                    if ($dfndr[0] != $ai) {
                        //break up start drone list
                        $drone_list = explode(";", $dfndr[5]);
                        foreach ($drone_list as &$drone_type) {
                            $drone_details = explode(":", $drone_type);
                            if (isset($drone_details[2])) {
                                $drone_details[2] = '?';
                            }
                            $drone_type = implode(":", $drone_details);
                        }
                        $dfndr[5] = implode(";", $drone_list);
                        //break up start defenses list
                        $def_list = explode(";", $dfndr[7]);
                        foreach ($def_list as &$def_type) {
                            $def_details = explode(":", $def_type);
                            if (isset($def_details[1])) {
                                $def_details[1] = '?';
                            }
                            $def_type = implode(":", $def_details);
                        }
                        $dfndr[7] = implode(";", $def_list);
                    }

                    $dfndrlist .= $dfndr[0] . "/" . $dfndr[3] . "/" . $dfndr[5] . "/" . $dfndr[6] . "/" . $dfndr[7] . "/" . $dfndr[8];
                }
            }
        }
        if ($read == "0") {
            $query = "update chronicle set `read`=1 where entry=$entry";
            $result = $mysqlidb->query($query);
        }

        $avlist = "";
        $query = "select name,settings from player where name in ($plyrlist)";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            while (($row = $result->fetch_row()) != null) {
                $setarr = explode(";", $row[1]);
                foreach ($setarr as $sets) {
                    $si = explode(":", $sets);
                    if ($si[0] == "SAP") {
                        if ($avlist != "") {
                            $avlist .= ";";
                        }
                        $avlist .= "{$row[0]}:{$si[1]}:P";
                    }
                }
            }
        }
        $query = "select name,avatar from alliance where name in ($plyrlist)";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            while (($row = $result->fetch_row()) != null) {
                if ($avlist != "") {
                    $avlist .= ";";
                }
                $avlist .= "{$row[0]}:{$row[1]}:O";
            }
        }

        if ($published != "") {
            $entry = ""; // flag as already published
        }

        print "HF|$entry|$title|$atkrlist|$dfndrlist|$avlist|$results";
    }
}



/*
 * deletechronicle - deletes specified chronicle
 */
function deletechronicle($ai, $entry) {
    global $mysqlidb;
    $query = "delete from chronicle where originator='$ai' and state=1 and entry='$entry';";
    $result = $mysqlidb->query($query);
    if (!$result) {
        postreport ($ai, 0, "Error deleting chronicle. Error:" . $mysqlidb->error);
    }
}

/*
 * publishchronicle - updates specified chronicle and sets
 *  the organization and publishedby columns
 *
 */
function publishchronicle($ai, $entry) {
    global $mysqlidb;
    $org = $_SESSION["alliance"];
    $query = "update chronicle set organization='$org',publishedby='$ai' where originator='$ai' and state=1 and entry='$entry';";
    $result = $mysqlidb->query($query);
    if ($result) {
        print("x|Chronicle published to Organization\n");
    }
}


/*
 * tagchronicle -
 *  updates specified chronicle and sets
 *  the tagged column
 *
 */
function tagchronicle($ai, $entry) {
    global $mysqlidb;

    $query = "update chronicle set tagged=1 where originator='$ai' and entry='$entry';";
    $result = $mysqlidb->query($query);
    if ($result) {
        print("x|Chronicle tagged\n");
    }
}


?>