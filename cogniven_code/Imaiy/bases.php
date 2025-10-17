<?php
/*
 * Functions to deal with base record changes
 * Author: Chris Bryant
 */


/*
 * calcmaxpossible -
 *  calculate max possible based on resources and components needed for one
 * parameters:
 *  res_store - string from base record
 *  components - string from base record (may be null if no components used)
 *  drones - string from base record (may be null if not expecting drones to be required)
 *  rstr - string from getrecipeline function
 */
function calcmaxpossible($res_store, $components, $drones, $rstr) {
    $pmax = 0;

    $rarr = explode("|", $rstr);
    $needres = array(MAX_NORMAL_RES);
    for ($idx = 0; $idx < MAX_NORMAL_RES; $idx++) {
        $needres[$idx] = $rarr[$idx+5];
    }

    if (is_array($rarr) && (count($rarr) > 10)) {
        $haveres = explode("/", $res_store);
        if (count($haveres) >= MAX_NORMAL_RES) {
            $pmax = $haveres[0] + $haveres[1] + $haveres[2] + $haveres[3]; // just make it a big starting value
            for ($idx = 0; $idx < MAX_NORMAL_RES; $idx++) {
                if ($rarr[$idx+5] > 0) {
                    $thismax = floor($haveres[$idx] / $rarr[$idx+5]);
                    if ($thismax < $pmax) {
                        $pmax = $thismax;
                    }
                }
            }
        }
        // skip if no components used or already 0 possible
        $comps = explode(";", $rarr[10]);
        if (is_array($comps) && ($pmax > 0)) {
            $havecomps = explode(";", $components);
            $darr = null;
            if ($drones != null) {
                $darr = explode(";", $drones);
            }
            foreach ($comps as $thiscomp) {
                $tcparts = explode(":", $thiscomp);
                if ((count($tcparts) > 3) && ($tcparts[0] == "D") && ($tcparts[3] > 0)) {
                    // is drone not component $thiscomp in format "D:type:level:quantity"
                    $found = false;
                    foreach ($darr as $td) {
                        // $td in format "type:level:quantity
                        $tdparts = explode(":", $td);
                        if ((count($tdparts) > 2) && ($tdparts[0] == $tcparts[1])) {
                            $thismax = floor($tdparts[2] / $tcparts[3]);
                            if ($thismax < $pmax) {
                                $pmax = $thismax;
                            }
                            $found = true;
                            break; // check for next needed
                        }
                    }
                    if ($found == false) {
                        // none of required drones found
                        $pmax = 0;
                        break;
                    }
                } else if ((count($tcparts) > 2) && ($tcparts[0] == "C") && ($tcparts[2] > 0)) {
                    // $thiscomp in format "C:type:quantity"
                    $found = false;
                    foreach ($havecomps as $hc) {
                        // $hc format is "type:quantity"
                        $hcparts = explode(":", $hc);
                        if ((count($hcparts) > 1) && ($hcparts[0] == $tcparts[1])) {
                            $thismax = floor($hcparts[1] / $tcparts[2]);
                            if ($thismax < $pmax) {
                                $pmax = $thismax;
                            }
                            $found = true;
                            break; // check for next needed
                        }
                    }
                    if ($found == false) {
                        // none of required components found
                        $pmax = 0;
                        break;
                    }
                }
            }
        }
    }
    return $pmax;
}


/*
 * baseloctokey -
 *  translates a base location into the entry column value
 *  returns null if location not found in base list
 */
function baseloctokey($loc) {
    $bkey = null;

    if (is_array($_SESSION["bases"])) {
        foreach ($_SESSION["bases"] as $arr) {
            if (!is_array($arr) || ($arr["location"] != $loc)) {
                // skip to the one we need
                continue;
            }
            $bkey = $arr["entry"];
            break;
        }
    }
    return $bkey;
}

/*
 * basekeytoname -
 *  translates a base key to the base name
 *  if name is blank then returns location
 *  if key not found returns "unknown"
 */
function basekeytoname($bkey) {
    $bname = "unknown";

    if (is_array($_SESSION["bases"])) {
        foreach ($_SESSION["bases"] as $arr) {
            if (!is_array($arr) || ($arr["entry"] != $bkey)) {
                // skip to the one we need
                continue;
            }
            if ($arr["name"] != "") {
                $bname = $arr["name"];
            } else if ($arr["location"] != "") {
                $bname = $arr["location"];
            }
            break;
        }
    }

    return $bname;
}


/*
 * calresourcemultiplier - calculates multiplier as 2^(targlevel-1)
 *      retrieves player tech level and adjusts multiplier
 *      retrieves current drudgeai in given base and adjusts based on stat
 *      if $drudgelist is blank then will retrieve drudge list from base record
 *  returns array of multipliers with $number entries
 *      if $number is 1 then will return only base calc
 *      if $number is MAX_NORMAL_RES then will return just 4 resource multipliers + duration
 *      if $number is MAX_TOTAL_RES then will return 4 resource + number of drones + duration
 *
 *  note: $drudgelist is passed by reference and will contain the ID of the drudge that had the
 *      the appropriate role or be blank if none.
 */
function calcresourcemultiplier($ai, $targlevel, $number, $location, &$drudgelist, $tech, $dairole) {
    $mults = array($number+1);
    global $daistatbyrole;
    global $mysqlidb;

    $basemult = $targlevel * $targlevel;
    for ($idx = 0; $idx < $number; $idx ++) {
        $mults[$idx] = $basemult;
    }
    $mults[$number] = $basemult;

    if ($number > 1) {
        $tmult = gettechmult($ai, "", $tech);
        // decrease multipler based on tech level
        for ($idx = 0; $idx <= $number; $idx ++) {
            $mults[$idx] = $mults[$idx]  / $tmult;
        }

        // if no base location specified then don't check drudgeai stat
        if ($location != "") {
            // if drudge ai list not provided then need to get it from base record
            if ($drudgelist == "") {
                $query = "select drudgeais from bases where controller='$ai' and location='$location';";
                $result = $mysqlidb->query($query);
                if ($result && ($result->num_rows > 0)) {
                    $row = $result->fetch_row();
                    $drudgelist = $row[0];
                }
            }
            // get multitasking stat from drudge ai assigned to construction role
            if (strlen($drudgelist) > 0 && $drudgelist[strlen($drudgelist)-1] == ",") { // strip trailing comma
                $drudgelist = substr($drudgelist, 0, strlen($drudgelist)-1);
            }
            $daistat = $daistatbyrole[$dairole];
            $query = "select entry,$daistat from drudgeai where entry in ($drudgelist) and role=$dairole;";
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0)) {
                $row = $result->fetch_row();
                $drudgelist = $row[0]; // return ID of this drudge ai
                // decrease multipler by 1/8% per stat point
                for ($idx = 0; $idx <= $number; $idx ++) {
                    $mults[$idx] -= ($mults[$idx] * $row[1]) / 800;
                    if ($mults[$idx] < 0.1) {
                        $mults[$idx] = 0.1; // bottom out at 10%
                    }
                }
            } else {
                $drudgelist = ""; // no drudge ai with this role
            }
        }
    }
    return $mults;
}

/*
 * getrecycleinfo
 *  returns string "number of recycle jobs|duration per scrap batch|size of scrap batch|drone times"
 *      where drone times is "type:time;type:time;..."
 */
function getrecycleinfo($ai, &$baserow) {
    $numinprogress = checktimedentry(TIMER_SCRAP_RECYCLE, $ai, $baserow['location'], "", "");
    $numinprogress += checktimedentry(TIMER_DRONE_RECY, $ai, $baserow['location'], "", "");

    $dur = 2;
    $bsize = 10;
    $recipe = getrawrecipe(RECIPE_TYPE_RES, 4, 1);
    if ($recipe) {
        $dura = explode("/", $recipe["resources"]);
        if (count($dura) > 4) {
            $dur = $dura[4];
        }
        $bsize = $recipe["scrap"];
    }

    $duration = floor($dur / gettechmult($ai, "", TECH_RECYCLE));

    $dronestr = getdronerecycletimes($ai, $baserow);

    return ("$numinprogress|$duration|$bsize|$dronestr");
}

/*
 * getrepairbaseline -
 *  returns line with info about repairing base
 */
function getrepairbaseline($ai, $base) {

    $busy = checktimedentry(TIMER_REPAIR_BASE, $ai, $base["location"], "", "");

    $chunk = getrecipestats(RECIPE_TYPE_BASE, TIMER_REPAIR_BASE, 1);

    $condition = explode("/", $base['bcond']);
    // calculate amout to repair in one cycle
    $tamount = 0;
    $repamount = array(2);
    for ($idx = 0; $idx < 2; $idx++) {
        // best is MAX_BASE_CONDITION
        if ($condition[$idx] < MAX_BASE_CONDITION){
            $damage = MAX_BASE_CONDITION - $condition[$idx];
            if ($damage < $chunk) {
                $repamount[$idx] = $damage;
            } else {
                $repamount[$idx] = $chunk;
            }
        } else {
            $repamount[$idx] = 0;
        }
        $tamount += $repamount[$idx];
    }
    if ($tamount < 1) {
        $tamount = 1;
    }

    // calculate resources required for cycle
    $ret = getrecipeline($ai, RECIPE_TYPE_BASE, TIMER_REPAIR_BASE, 0, "", $busy, 1, $base, TECH_CONST, DRUDGEAI_ROLE_CONST);

    $ret .= "|{$condition[0]}|{$condition[1]}|{$repamount[0]}|{$repamount[1]}|$tamount";

    return $ret;
}


/*
 * repairbase -
 *  attempts to initiate a repair cycle to repair up to
 *      10 points of each stat. Resources and time calculated based
 *      one amount of repairs required and level of base.
 */
function repairbase($ai, $location) {
    global $mysqlidb;
    global $res_name;
    $fail = "Unable to begin repair cycle in base at $location as";

    $underrepair = checktimedentry(TIMER_REPAIR_BASE, $ai, $location, "", "");
    if ($underrepair > 0) {
        postreport($ai, 0, "$fail repair cycle already in progress");
        return;
    }

    $query = "select bcond,res_store,drudgeais,level,infras,dlocation from bases where controller='$ai' and location='$location';";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_assoc();
        $res_store = $row["res_store"];
        $dlocation = $row["dlocation"];

        $chunk = getrecipestats(RECIPE_TYPE_BASE, TIMER_REPAIR_BASE, 1);

        // figure out how much damage needs to be repaired
        $condition = explode("/", $row[0]);
        $tamount = 0;
        for ($idx = 0; $idx < 2; $idx++) {
            // best is MAX_BASE_CONDITION
            if ($condition[$idx] < MAX_BASE_CONDITION){
                $damage = MAX_BASE_CONDITION - $condition[$idx];
                if ($damage < $chunk) {
                    $repamount[$idx] = $damage;
                } else {
                    $repamount[$idx] = $chunk;
                }
            } else {
                $repamount[$idx] = 0;
            }
            $tamount += $repamount[$idx];
        }
        if ($tamount == 0) {
            postreport ($ai, 0, "No repairs need to base at $location");
        } else {
            // calc resources required
            $recipe = getrecipe($ai, RECIPE_TYPE_BASE, TIMER_REPAIR_BASE, 0, $row, TECH_CONST, DRUDGEAI_ROLE_CONST);
            if ($recipe == null) {
                postreport($ai, 0, "$fail unable to determine how to perform operation");
            }
            $resstr = "";
            $needres = array(MAX_NORMAL_RES+1);
            for ($idx = 0; $idx <= MAX_NORMAL_RES; $idx++) {
                $needres[$idx] = round($recipe["resarr"][$idx] * $tamount);
                if ($idx < MAX_NORMAL_RES) {
                    $resstr .= $needres[$idx] . "/";
                } else {
                    $duration = $needres[$idx];
                }
            }

            // subtract resources
            $haveres = explode("/", $res_store);
            $newres = array(MAX_NORMAL_RES+1);
            $rmsg = "";
            for ($idx = 0; $idx < MAX_NORMAL_RES; $idx++) {
                $newres[$idx] = (int) ($haveres[$idx] - $needres[$idx]);
                if ($newres[$idx] < 0) {
                    $tmpstr =  number_format($needres[$idx]) . " " . $res_name[$idx];
                    if ($rmsg == "") {
                        $rmsg = "$fail insufficient resources: $tmpstr";
                    } else {
                        $rmsg .= ", $tmpstr";
                    }
                }
            }
            $newres[MAX_NORMAL_RES] = $haveres[MAX_NORMAL_RES]; // just copy scrap value
            $newresstr = implode("/", $newres);

            if ($rmsg != "") {
                postreport($ai, 0, $rmsg);
            } else {
                $query = "update bases set res_store='$newresstr' where controller='$ai' and location='$location';";
                $result = $mysqlidb->query($query);
                if (!$result) {
                    postreport ($ai, 0, "$fail unable to update base record");
                } else {
                    // put entry into timer queue to repair base
                    $results = "Repair amount = $repamount[0]/$repamount[1];$repamount[0]/$repamount[1]";
                    createtimedentry($duration, TIMER_REPAIR_BASE, $ai, "$location:$dlocation", "$location:$dlocation", 0,
                                        $recipe["dai"], "", $resstr, "", "", $results);
                    postreport($ai, 0, "Initiating Repair Cycle in base at $dlocation, etc " . formatduration($duration));
                }
            }
        }
    }
}


/*
 * movebase - moves base from one location to another. Target location
 *              must be empty and uncontrolled
 */
function movebase($ai, $oldloc, $newloc) {
    global $mysqlidb;
    $success = false;

    // convert from display loc to coord loc if necessary
    $oldloc = convertdloctoloc($oldloc);
    $newloc = convertdloctoloc($newloc);

    if (checktimedentry(TIMER_MOVE_BASE, $ai, "", "", "")) {
        postreport ($ai, 0, "Unable to relocate base as only able to relocate one base at a time.");
        return false;
    }
    $rmsg = "Failed to relocate base, "; // default first half of failure msg
    // must be idle
    if (checktimedentry("", $ai, $oldloc, "", "")) {
        $rmsg .= "base must be completely idle with nothing pending.";
        postreport ($ai, 0, $rmsg);
        return false;
    }
    // can not have any formations out
    if (hasformations($ai, $oldloc)) {
        $rmsg .= "base must not have any formations out and must not be reinforced";
        postreport ($ai, 0, $rmsg);
        return false;
    }
    $query = "select locs,dlocation from bases where controller='$ai' and location='$oldloc';";
    $result = $mysqlidb->query($query);
    if (!$result || ($result->num_rows == 0)) {
        $rmsg .= "could not get status of base";
        postreport ($ai, 0, $rmsg);
        return false;
    }
    $row = $result->fetch_row();
    $controlledlocs = $row[0];
    $olddloc = $row[1];

    // move to newloc that is uncontrolled and empty
    $query = "select controller,o_type,state,dlocation from world where location='$newloc';";
    $result = $mysqlidb->query($query);
    if (!$result || ($result->num_rows == 0)) {
        $rmsg .= "could not get status of destination location";
        postreport ($ai, 0, $rmsg);
        return false;
    }
    $row = $result->fetch_row();
    $newdloc = $row[3];
    if (($row[0] != "none") || ($row[1] != 0)) {
        $rmsg .= "destination must be empty and uncontrolled";
    } else if ($row[2] != "") {
        $rmsg .= " conflict in progress at target";
    } else {
        // set source and target locations as under system controll
        $query = "update world set controller='system',o_type=0 where location='$newloc';";
        $result = $mysqlidb->query($query);
        if (!$result) {
            $rmsg .= "could not lock destination";
        } else {
            $clocs = str_replace(",", "','", $controlledlocs);
            // abandon all controlled locs
            $query = "update world set controller='none',o_type=0 where location in ('$clocs')";
            $mysqlidb->query($query);
            $updatedlocs = "$newloc:$newdloc";
            // set base as under system controll
            $query = "update bases set controller='system',locs='$updatedlocs',location='$newloc' where controller='$ai' and location='$oldloc';";
            $mysqlidb->query($query);

            $recipe = getrawrecipe(RECIPE_TYPE_BASE, TIMER_CONST_BASE, 4);
            if ($recipe) {
                $resa = explode("/", $recipe["resources"]);
                if (count($resa) > 4) {
                    $dura = explode(":", $resa[4]);
                } else {
                    $dura[0] = 1;
                    $dura[1] = 1;
                }

                // calc time to do move
                $movetime = $dura[0] + ($dura[1] * calcdistance($oldloc, $newloc));

                // put entry into timer queue to move base
                createtimedentry($movetime, TIMER_MOVE_BASE, $ai, "$oldloc:$olddloc", "$newloc:$newdloc", 0,
                                        "", "", "", "", "", "");
                $rmsg = "Relocating base at $olddloc to $newdloc, etc " . formatduration($movetime);
                $success = true;
            } else {
                $rmsg .= "unable to determine how to move";
            }
        }
    }
    postreport ($ai, 0, $rmsg);
    return $success;
}


/*
 * abandonbase - abandons base at given coordinates and marks location owned
 *      by rogue in world map.
 *      location is in formation bb:xx:yy where bb = block, xx,yy = x,y coords in block
 */
function abandonbase($ai, $location) {
    global $mysqlidb;

    $location = convertdloctoloc($location);

    $fail = "Unable to abandon base, ";

    $query = "select count(*) from bases where controller='$ai';";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_row();
        $basecount = $row[0];
    } else {
        $basecount = 0; // assume 0 if we can't get count
    }
    if ($basecount <= 1) {
        postreport ($ai, 0, "$fail last base controlled by this AI.");
    } else { // not last base
        $query = "select hasplayerai,dlocation from bases where controller='$ai' and location='$location';";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            $row = $result->fetch_row();
            $dlocation = $row[1];
            if ($row[0] != 0) {
                postreport ($ai, 0, "$fail Master AI resides here. Move AI first");
            } else { // okay to abandon
                $movetime = 300;
                createtimedentry($movetime, TIMER_ABANDON_BASE, $ai, "$location:$dlocation", "$location:$dlocation", 0,
                                        "", "", "", "", "", "");
                postreport ($ai, 0, "Abandoning base at $dlocation , etc " . formatduration($movetime));
            }  // okay to abandon
        } else {
            postreport ($ai, 0, "$fail cannot locate base controlled by $ai");
        }
    }  // not last base
}


/*
 * createbase creates a base empty base at given coordinates and marks location owned
 *      by ai in world map.
 *      location is in formation bb:xx:yy where bb = block, xx,yy = x,y coords in block
 *      location must be controlled by $ai or by no one if this is first base created
 *
 *  returns 0: failure
 *          1: success
 */
function createbase($ai, $location) {
    global $mysqlidb;
    global $res_name;

    $location = convertdloctoloc($location);

    $fail = "Unable to begin construction of base,";

    postlog("createBase($ai, $location)");
    if (checktimedentry( TIMER_MOVE_BASE, $ai, "", "", "")) {
        postreport ($ai, 0, "$fail may not construct a new base while moving another");
        return;
    }
    if (checktimedentry( TIMER_CONST_BASE, $ai, "", "", "") >= MAX_BASES_UNDER_CONSTRUCTION) {
        postreport ($ai, 0, "$fail only able to construct " . MAX_BASES_UNDER_CONSTRUCTION . " base at a time");
        return;
    }

    // see how many bases this player controls and level of player
    if (array_key_exists("level", $_SESSION)) {
        $playerlevel = $_SESSION["level"];
    } else {
        $playerlevel = 1;
    }

    $query = "select count(*) from bases where controller='$ai'";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_row();
        $basecount = $row[0];
    } else {
        $basecount = 0; // assume 0 if we can't get count
    }
    $maxbases = floor($playerlevel / 4) + 2;
    if (($basecount != 0) && (($basecount >= MAXBASES) || (($basecount+1) > ($maxbases)))) {
        postreport ($ai, 0, "$fail unable to establish more bases than (Master AI level / 4) + 2");
    } else {
        // check location in world map to make sure it is owned by $ai or "none" if first base
        $query = "select controller,res_store,dlocation,section from world where location='$location';";
        $result = $mysqlidb->query($query);
        if (!$result || ($result->num_rows == 0)) {
            postreport ($ai, 0, "$fail unable to retrieve location info");
        } else {
            $row = $result->fetch_row();
            $controller = $row[0];
            $locres = explode("/", $row[1]);
            $dlocation = $row[2];

            $maxsec = ord('A');
            if (array_key_exists("highestopensection", $_SESSION)) {
                $maxsec = ord($_SESSION["highestopensection"]);
            }
            if (ord($row[3]) > $maxsec) {
                postreport ($ai, 0, "$fail section {$row[3]} is not yet open to new bases");
            } else if ((($basecount == 0) && ($controller != "none")) || (($basecount != 0) && ($controller != $ai))) {
                postreport ($ai, 0, "$fail location not controlled by $ai");
            } else {
                if ($basecount == 0) {
                    // set controller of location as $ai
                    // put default set of drones in location, they will be moved into base when base done
                    // put default resources and wipe components
                    $base = array();
                    $recipe = getrecipe($ai, RECIPE_TYPE_BASE, TIMER_CONST_BASE, 0, $base, 0, 0);
                    if ($recipe != null) {
                        $drones = simplifydrones($recipe["drones"]);
                        $res_store = implode("/", $recipe["resarr"]);
                        $query = "update world set drones='$drones',res_store='$res_store',components='',controller='system' where location='$location'";
                        $result = $mysqlidb->query($query);
                        if (!$result) {
                            postreport ($ai, 0, "$fail could not lock location");
                        } else {
                            // don't require resources and just queue for 1sec duration
                            createtimedentry( 1, TIMER_CONST_BASE, $ai, "$location:$dlocation", "$location:$dlocation", 0,
                                                "", "", "", "", "", "");
                        }
                    } else {
                        postlog("Unable to get base create recipe");
                    }
                } else {
                    $shortres = "";
                    // check to insure required resources are in target location
                    $base = array();
                    $recipe = getrecipe($ai, RECIPE_TYPE_BASE, TIMER_CONST_BASE, 1, $base, 0, 0);
                    if ($recipe == null) {
                        postreport ($ai, 0, "$fail unable to determine how to perform operation");
                    } else {
                        $resstr = "";
                        $needres = array(MAX_NORMAL_RES+1);
                        for ($idx = 0; $idx <= MAX_NORMAL_RES; $idx++) {
                            $needres[$idx] = round($recipe["resarr"][$idx]);
                            if ($idx < MAX_NORMAL_RES) {
                                $resstr .= $needres[$idx] . "/";
                            } else {
                                $duration = $needres[$idx];
                            }
                        }
                        $newres = array(MAX_NORMAL_RES+1);
                        for ($idx = 0; $idx < MAX_NORMAL_RES; $idx++) {
                            $newres[$idx] = (int) ($locres[$idx] - $needres[$idx]);
                            if ($newres[$idx] < 0) {
                                $tmpstr =  number_format($needres[$idx]) . " " . $res_name[$idx];
                                if ($shortres == "") {
                                    $shortres = $tmpstr;
                                } else {
                                    $shortres .= ", $tmpstr";
                                }
                            }
                        }
                        $newres[MAX_NORMAL_RES] = $locres[MAX_NORMAL_RES]; // just copy scrap value
                        $newresstr = implode("/", $newres);
                    }
                    // look for reinforcing formations with enough construction drones
                    $recipedrones = simplifydrones($recipe["drones"]);
                    $needrones = getdronequantity($recipedrones, DRONE_WORKR_CON, 0);

                    $query = "select drones from formations where targetloc='$location' and controller='$ai' and status=".FORMATION_STATUS_REIN;
                    $result = $mysqlidb->query($query);
                    if ($result && ($result->num_rows > 0)) {
                        while ((($rowf = $result->fetch_row()) != null)) {
                            $fdrones = explode(";", $rowf[0]);
                            foreach ($fdrones as $fd) {
                                $fdparts = explode(":", $fd);
                                if ($fdparts[1] == DRONE_WORKR_CON) {
                                    $needrones -= $fdparts[2];
                                }
                            }
                        }
                    }
                    if ($needrones > 0) {
                        if ($shortres != "") {
                            $shortres .= ", ";
                        }
                        $shortres .= number_format($needrones) . " construction worker drones";
                    }
                    if ($shortres != "") {
                        postreport ($ai, 0, "$fail insufficient resources, need $shortres. Reinforce location with formation containing these items");
                    } else {
                        // update location info with new resource quantities;
                        $query = "update world set res_store='$newresstr',controller='system' where location='$location'";
                        $result = $mysqlidb->query($query);
                        if (!$result) {
                            postreport ($ai, 0, "$fail could not update and lock location");
                        } else {
                            // insert construction of base into queue
                            createtimedentry($duration, TIMER_CONST_BASE, $ai, "$location:$dlocation", "$location:$dlocation", 0,
                                                                    "", $recipedrones, $resstr, "", "", "");
                            postreport ($ai, 0, "Construction of base at $dlocation begun by $ai etc " . formatduration($duration));
                        }
                    }
                }
            }
        }
    }
}


/*
 * updatedrudgeai - updates drudge ai based on provided liststr
 *  format of line is "id|name|role|analysis|control|heuristics|tactics|multitasking|
 *  id must be in the list of drudge ais for this base
 */
function updatedrudgeai($ai, $daisinthisbase, $baseloc, $liststr) {
    global $mysqlidb;
    $dai = explode("|", $liststr);
    if (count($dai) >= 8) {
        $daiIdents = explode(",", $daisinthisbase);
        // insure that this drudge AI is in this base
        if (!in_array($dai[0], $daiIdents)) {
            postreport ($ai, 0, "Update of drudge ai with id={$dai[0]} failed, drudge ai is not in current base");
        } else { // retrieve drudge ai record
            $dailist = "('".implode("','", $daiIdents)."')";
            $query = "select * from drudgeai where entry=$dai[0] or (entry!=$dai[0] and role=$dai[2] and role!=0 and role!=5 and entry in $dailist)";
            $result = $mysqlidb->query($query);
            if (!$result || ($result->num_rows == 0)) {
                postreport ($ai, 0, "Update of drudge ai with id=$dai[0] failed, unable to locate drudge ai");
            } else {
                $duplicaterole = false;
                $oob = false;
                $sdelta = -1;
                $oldrole = $dai[2];
                while (($row = $result->fetch_assoc()) != null) {
                    if (($row['entry'] == $dai[0]) && ($row["role"] == DRUDGEAI_ROLE_ROAM)) {
                        $oob = true;
                    } else if ($row['entry'] != $dai[0]) {
                        $duplicaterole = true;
                    } else {
                        $oldrole = $row["role"]; // save old role of dai to be updated
                        // now check to make sure that sum of
                        //  stat deltas is less than or equal unused points
                        $sdelta = (int)$dai[3] - $row["canalysis"];
                        $sdelta += (int)$dai[4] - $row["ccontrol"];
                        $sdelta += (int)$dai[5] - $row["cheuristics"];
                        $sdelta += (int)$dai[6] - $row["ctactics"];
                        $sdelta += (int)$dai[7] - $row["cmultitasking"];
                        $sdelta = $row["unusedpoints"] - $sdelta;
                    }
                }
                $dstr = "Reprogram of drudge ai with id=$dai[0]";
                if ($oob == true) {
                    postreport ($ai, 0, "$dstr failed, drudge ai must be in base");
                } else if ($duplicaterole == true) {
                    postreport ($ai, 0, "$dstr failed, duplicate role assignment only permitted for Formation manager");
                } else if ($sdelta < 0) {
                    postreport ($ai, 0, "$dstr failed, attempted to allocate more points than available");
                } else {
                    $values = "name='$dai[1]',role=$dai[2],canalysis=$dai[3],"
                                ."ccontrol=$dai[4],cheuristics=$dai[5],"
                                ."ctactics=$dai[6],cmultitasking=$dai[7],"
                                ."unusedpoints=$sdelta";
                    $query = "update drudgeai set $values where entry=$dai[0];";
                    $result = $mysqlidb->query($query);
                    if (!$result) {
                        postlog ("$dstr failed. Error:" . $mysqlidb->error);
                    } else {
                        postreport ($ai, 0, "$dstr successful");
                    }
                    // if role is now or was resource manager then set kick in bases record
                    //  to trigger recalc of resource generation amounts
                    if (($oldrole == DRUDGEAI_ROLE_RESOURCE) || ($dai[2] == DRUDGEAI_ROLE_RESOURCE)) {
                        $query = "update bases set kick=1 where location='$baseloc'";
                        $result = $mysqlidb->query($query);
                    }
                }
            } // found record
        }  // retrieve drudge ai record

        // clear drudgeai recipe cache
        cleardairecipecache($baseloc);

    } // if (count($dai) >= 8)
}



/*
 * updatebaseinfo - updates base information in bases report for player
 *  handles updating name and/or location for renaming or moving base
 *  also, delete or create base.
 *
 */
function updatebaseinfo($ai, $baseloc, $operation, $bname, $newloc, $type) {
    global $mysqlidb;
    $refresh = false;

    switch ($operation) {
        case "rename":
            $newname = str_split($bname, 1);
            // (\w| allows letters, digits and underscores ( _ )
            // |-| allows dashes
            // | ) allows spaces
            //this expression requires that their be one character and that character matches one of the above
            $re = "/^(\w|-| )$/";
            foreach($newname as &$character) {
                if (!preg_match($re, $character)) {
                    $character = '';
                }
            }
            if (valid_name($bname) != 1) {
                $newname = '';
            } else {
                $newname = implode('', $newname);
                $newname = substr($newname, 0, 16);
                $newname = trim($newname);
            }

            if ($newname != '') {
                $query = "update bases set name='$newname' where controller='$ai' and location='$baseloc';" ;
                $mysqlidb->query($query);
                postreport ($ai, 0, "Changed name of base controlled by $ai at $baseloc to $newname");
            } else {
                postreport ($ai, 0, "Failed to change name of base controlled by $ai at $baseloc, $bname is invalid or reserved");
            }
            break;
        case "movebase":
            // move to a location controlled by this base
            if (($newloc) && ($newloc != "") && ($newloc != $baseloc)) {
                movebase($ai, $baseloc, $newloc, "B");
            }
            break;
        case "movebaserandom":
            // move to a random empty loc in a block
            if (($newloc) && ($newloc != "") && ($newloc != $baseloc)) {
                movebase($ai, $baseloc, $newloc, "R");
            }
            break;
        case "movebasespecific":
            // move to a specific empty loc in a block
            if (($newloc) && ($newloc != "") && ($newloc != $baseloc)) {
                movebase($ai, $baseloc, $newloc, "E");
            }
            break;
        case "repairbase":
            if (($baseloc) && ($baseloc != "")) {
                repairbase($ai, $baseloc);
            }
            break;
        case "delete":
            abandonbase($ai, $baseloc);
            break;
        case "create":
            createbase($ai, $baseloc);
            break;
        case "defenseenable": // enable $type defenses
            togglebasedefense($ai, $baseloc, $type, true);
            break;
        case "defensedisable": // disable $type defenses
            togglebasedefense($ai, $baseloc, $type, false);
            break;
        case "defenseconst":  // construct $newloc amount of defense $type
            changebasedefense($ai, $baseloc, $type, $newloc);
            break;
        case "defenserem": // remove $newloc amount of defense $type
            changebasedefense($ai, $baseloc, $type, -$newloc);
            break;
        case "moveai":
            $fail = "Master AI move request ignored, ";
            // ignore if timed entry already exists for moveai
            if (checktimedentry( TIMER_MOVEAI, $ai, "", "", "") != 0) {
                postreport ($ai, 0, "$fail already moving");
            } else if (checktimedentry( TIMER_LEVEL_PLAYER, $ai, "", "", "") != 0) {
                postreport ($ai, 0, "$fail can not move while being upgraded");
            } else {
                $newdloc = "";
                $basedloc = "";
                $newloc = convertdloctoloc($baseloc); // destination actually passed in as $baseloc
                $query = "select dlocation,hasplayerai from bases where controller='$ai' and location='$newloc'";
                $result = $mysqlidb->query($query);
                if ($result && ($result->num_rows > 0)) {
                    $row = $result->fetch_row();
                    $newdloc = $row[0];
                    if ($row[1] == "1") {
                        postreport ($ai, 0, "$fail already present in target base");
                    } else {
                        $query = "select location,dlocation from bases where controller='$ai' and hasplayerai=1";
                        $result = $mysqlidb->query($query);
                        if ($result && ($result->num_rows > 0)) {
                            $row = $result->fetch_row();
                            $baseloc = $row[0];
                            $basedloc = $row[1];
                        }

                        $recipe = getrawrecipe(RECIPE_TYPE_BASE, TIMER_CONST_BASE, 3);
                        if ($recipe) {
                            $resa = explode("/", $recipe["resources"]);
                            if (count($resa) > 4) {
                                $dura = explode(":", $resa[4]);
                            } else {
                                $dura[0] = 1;
                                $dura[1] = 1;
                            }
                            // calc time to move
                            $movetime = $dura[0] + ($dura[1] * calcdistance($baseloc, $newloc));
                            // place in timer queue to remove and them move AI
                            createtimedentry( 0, TIMER_REMOVEAI, $ai, "$baseloc:$basedloc", "$newloc:$newdloc", 0,
                                                    "", "", "", "", "", "");
                            createtimedentry( $movetime, TIMER_MOVEAI, $ai, "$baseloc:$basedloc", "$newloc:$newdloc", 0,
                                                    "", "", "", "", "", "");
                            postreport ($ai, 0, "Master AI moving from base at $basedloc to base at $newdloc, eta " . formatduration($movetime));
                        } else {
                            postreport ($ai, 0, "$fail unable to determine how to move");
                        }
                    }
                }
            }
            break;
        case "daiupdate":
            // get list of drudge AIs in base
            $query = "select drudgeais from bases where controller='$ai' and location='$baseloc';" ;
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0)) {
                $row = $result->fetch_row();
                //  update drudge AI with info supplied in $bname if it is in this base
                updatedrudgeai($ai, $row[0], $baseloc, $bname);
            }
            break;
        case "daidismantle":
            // get list of drudge AIs in base
            $query = "select drudgeais from bases where controller='$ai' and location='$baseloc';" ;
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0)) {
                $row = $result->fetch_row();
                // remove drudge AI whose identifier is in $bname from list
                $oldaiIdents = explode(",", $row[0]);
                $newaiIdents[0] = "";
                for ($oldidx = 0, $newidx = 0; $oldidx < count($oldaiIdents); $oldidx++) {
                    if (($oldaiIdents[$oldidx] != "") && ($oldaiIdents[$oldidx] != $bname)) {
                        $newaiIdents[$newidx] = $oldaiIdents[$oldidx];
                        $newidx++;
                    }
                }
                $newailist = implode(",", $newaiIdents);

                $query = "select level from drudgeai where entry=$bname and role=".DRUDGEAI_ROLE_IDLE;
                $result = $mysqlidb->query($query);
                if (!$result || ($result->num_rows == 0)) {
                    postreport($ai, 0, "Drudge AI must be Idle before it can be dismantled");
                } else {
                    $row = $result->fetch_row();
                    $level = $row[0];
                    $query = "delete from drudgeai where entry=$bname and role=".DRUDGEAI_ROLE_IDLE;
                    $result = $mysqlidb->query($query);
                    if (!$result) {
                        postlog ("Removal of drudge ai with id=$bname failed. Error:" . $mysqlidb->error);
                        postreport($ai, 0, "Drudge AI must be Idle before it can be dismantled");
                    } else {
                        $query = "update bases set drudgeais='$newailist' where controller='$ai' and location='$baseloc';" ;
                        $result = $mysqlidb->query($query);
                        if (!$result) {
                            postlog ("Removal of drudge ai with id=$bname from base at $baseloc failed. Error:" . $mysqlidb->error);
                        } else {
                            $query = "select items from player where name='$ai'";
                            $result = $mysqlidb->query($query);
                            if ((mt_rand(1, 100) < 25) || !$result || ($result->num_rows == 0)) {
                                postreport($ai, 0, "Dismantle of Drudge AI id $bname failed to result in usable core");
                            } else {
                                $row = $result->fetch_row();
                                $iarr = explode(";", $row[0]);
                                $found = false;
                                for ($idx = 0; $idx < count($iarr); $idx++) {
                                    $item = explode(":", $iarr[$idx]);
                                    if ((count($item) > 2) && ($item[0] == "DC") && ($item[1] == $level)) {
                                        $found = true;
                                        $item[2]++;
                                        $iarr[$idx] = implode(":", $item);
                                        break;
                                    }
                                }
                                if ($found == false) {
                                    $iarr[count($iarr)] = "DC:$level:1";
                                }
                                $query = "update player set items='".implode(";", $iarr)."' where name='$ai'";
                                $result = $mysqlidb->query($query);
                                if (!$result) {
                                    postreport($ai, 0, "Dismantle of Drudge AI id $bname failed to result in usable core.");
                                } else {
                                    postreport($ai, 0, "Drudge AI id $bname dismantled successfully");
                                }
                            }
                        }
                    }
                }
            }
            break;
        case "abandonloc":
            abandonlocation($ai, $newloc);
            break;
        case "deleteformation":
            deleteformation($ai, $type);
            break;
        case "basestatus":
            $refresh = (($type != 0) ? false : true);
            printbaseinfo($ai, $refresh);
            printdronestatlines($ai, $refresh);
            break;
        default:
            break;
    }
}

/*
 * printbaseinfo -
 *  formats and prints player and base status info
 */
function printbaseinfo($ai, $refresh) {
    global $mysqlidb;
    $list = array();
    $listidx = 0;
    // first include line of player data
    if (getplayerinfo( "", $ai) == 1) {
        if (!isset($_SESSION["controlocs"])) {
            $_SESSION["controlocs"] = "";
        }
        // controlocs is send as part of player info but are built when scanning bases
        //  so will always be one refresh cycle behind
        $list[$listidx] = "PP|$ai|" . $_SESSION["created"] . "|" . $_SESSION["level"] . "|" . $_SESSION["alliance"]
                            . "|" . $_SESSION["power"] . "|" . $_SESSION["renown"]
                            . "|" . $_SESSION["player_status"] . "|" . $_SESSION["settings"]
                            . "|" . $_SESSION["controlocs"] . "|" . $_SESSION["savelocs"]
                            . "|" . $_SESSION["highestopensection"];
        $listidx++;
        $maxbases = floor($_SESSION["level"] / 4) + 2;
        // respond with list of bases and info associated with them
        $query = "select * from bases where controller='$ai' order by name asc";
        $result = $mysqlidb->query($query);
        $bidx = 0;
        $controlocs = "";
        $_SESSION["bases"] = array();
        while ($row = $result->fetch_assoc()) {
            // format each base entry as b.x.y|base name|level|hasplayerai
            // showformationsinc needs location, modules and locs in $row
            foreach ($row as $key=>$value) {
                $_SESSION["bases"][$bidx][$key] = $value;
            }
            $_SESSION["bases"][$bidx]["dronecount"] = getdronequantity($row["drones"], -1, 0);
            $_SESSION["bases"][$bidx]["dronemax"] = $row["drone_limit"];

            $locstr = "";
            $theselocs = explode(",", $row['locs']);
            $lcount = count($theselocs);
            if ($lcount > 0) {
                for ($idx = 0; $idx < $lcount; $idx++) {
                    $larr = explode(":", $theselocs[$idx]);
                    if (count($larr) > 1) {
                        if ($larr[0] == $row['location']) {
                            $theselocs[$idx] .= " BASE " . $row['name'];
                        } else if ($theselocs[$idx] != "") {
                            $theselocs[$idx] .= " via " . $row['name'];
                        }
                        if ($locstr != "") {
                            $locstr .= ",";
                        }
                        $locstr .= $larr[0];
                    }
                }

                if ($controlocs != "") {
                    $controlocs .= ";";
                }
                $controlocs .= implode($theselocs, ";");
            }
            $_SESSION["bases"][$bidx]["locstr"] = $locstr;

            $forminc = showformationsinc($ai, $_SESSION["bases"][$bidx], false);
            $list[$listidx] = "BB|$bidx|{$row['location']}|{$row['name']}|{$row['level']}|{$row['hasplayerai']}|{$row['bcond']}|$forminc|{$row['dlocation']}";
            $listidx++;
            $bidx++;
        }
        $_SESSION["controlocs"] = str_replace(";;", ";", $controlocs);
        // fill in remainder of empty base slots
        while ($bidx < MAXBASES) {
            if ($bidx < $maxbases) {
                $list[$listidx] = "BB|$bidx|||0|0||0";
            } else {
                $list[$listidx] = "BB|$bidx||||0||0";
            }
            $listidx++;
            $bidx++;
        }
        printdeltas("basestats", 0, $refresh, $list);
    }
}

/*
 * getbaseline -
 *  returns string of info for a specific base
 *      resource amounts are those currently stored in base
 *      format "BASE|location|level|fuel|metal|mineral|xtal|scrap|drones|components
*/
function getbaseline($baseloc) {
    $ret = "";

    if (array_key_exists("bases", $_SESSION)) {
        foreach ($_SESSION["bases"] as $base) {
            if ($base["location"] != $baseloc) {
                continue;
            }
            $rstr = str_replace("/", "|", $base['res_store']);

            $ret = "BASE|$baseloc|{$base['level']}|$rstr|{$base['drones']}|{$base['components']}|{$base['hasplayerai']}|{$base['dlocation']}";
            break;
        }
    }
    return $ret;
}


?>
