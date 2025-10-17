<?php
/*
 * Functions to deal with drone formations
 * Author: Chris Bryant
 */
define ("BASE_TRANSIT_TIME", 60); // secs to enter or exit base


/*
 * anyreinforcementsout -
 *  checks for any formations in reiforcement status that are not reinforcing
 *  a location controlled by $ai. returns true if any found, false otherwise
 */
function anyreinforcementsout($ai) {
    global $mysqlidb;
    $found = false;

    $query = "select count(*) from formations join world on world.location=formations.targetloc"
                    . " where formations.controller='$ai' and world.controller!='$ai'"
                    . " and formations.status=".FORMATION_STATUS_REIN;
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_row();
        if ($row[0] != 0) {
            $found = true;
        }
    }
    return $found;
}

/*
 * getcontrolpointspeedmult
 *  get control point effect on formation speed if any
 */
function getcontrolpointspeedmult($org, $block, $quadrant) {
    global $mysqlidb;

    $mult = 1.0;
    $found = false;

    if (($org != "") && ($org != "Beginner")) {
        $query = "select cpoints from alliance where name='$org'";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            $row = $result->fetch_row();
            $cparr = explode(";", $row[0]);
            foreach ($cparr as $cp) {
                $cparts = explode(":", $cp);
                if ((count($cparts) > 4) && ($cparts[3] == "3") && ($cparts[0] == $block) && ($cparts[1] == $quadrant)) {
                    // speed boost - can only be one in target quadrant
                    $query = "select lcond from world where location='{$cparts[2]}'";
                    $lresult = $mysqlidb->query($query);
                    if ($lresult && ($lresult->num_rows > 0)) {
                        $lrow = $lresult->fetch_row();
                        if (floor($lrow[0]) >= 5000) {
                            $mult += 0.08 * floor($cparts[4]);
                        }
                    }
                    $found = true;
                    break;
                }
            }
        }
        if ($found == false) {
            // didn't find for org so look at friend control points
            $query = "select cpoints from alliance join relations on (alliance.name=relations.source) where relations.type=2 and relations.status=1 and relations.target='$org'";
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0)) {
                while (($found == false) && (($row = $result->fetch_row()) != null)) {
                    $cparr = explode(";", $row[0]);
                    foreach ($cparr as $cp) {
                        $cparts = explode(":", $cp);
                        if ((count($cparts) > 4) && ($cparts[3] == "3") && ($cparts[0] == $block) && ($cparts[1] == $quadrant)) {
                            // speed boost - can only be one in target quadrant
                            //  only half effect to friends
                            $query = "select lcond from world where location='{$cparts[2]}'";
                            $lresult = $mysqlidb->query($query);
                            if ($lresult && ($lresult->num_rows > 0)) {
                                $lrow = $lresult->fetch_row();
                                if (floor($lrow[0]) >= 5000) {
                                    $mult += 0.04 * floor($cparts[4]);
                                }
                            }
                            $found = true;
                            break;
                        }
                    }
                }
            }
        }
    }

    return $mult;
}


/*
 * validpurposeontarget - validate purpose on target
 *  can only transport to alliance or friend base, ai's bases, or locs controlled by this base
 *  can only recon npc or enemy base or loc not controlled by ally or friend
 *  can only reinforce alliance or friend base or loc
 *  can only attack npc or enemy base or loc controlled by enemy or uncontrolled
 *  can only scavenge uncontrolled loc
 *  can only move to a base controlled by ai
 *  check if need a drudge ai selection based on purpose
 */
function validpurposeontarget($ai, $purpose, $target, $drudgeai, $hascargo) {
    global $mysqlidb;
    $validationerror = "";
    if (($drudgeai == "") || ($drudgeai == "-1") || ($drudgeai == "0")) {
        $hasdrudge = false;
    } else {
        $hasdrudge = true;
    }
    $controller = "system"; // if can't get location info then pretend is owned by system
    $isenemy = false;
    $isfriend = false;
    $isnpc = false;
    $handsoff = false;
    $isbase = false;
    $theyprotected = false;
    $meprotected = false;
    $iscontrolpoint = false;
    $dtarget = "";
    $lastcap = 0;
    // if target hasn't changed then just use info cached in _SESSION
    if ($_SESSION["last_target_loc"] == $target) {
        $dtarget = $_SESSION["last_target_dloc"];
        $controller = $_SESSION["last_target_controller"];
        $isenemy = $_SESSION["last_target_isenemy"];
        $isfriend = $_SESSION["last_target_isfriend"];
        $isnpc = $_SESSION["last_target_isnpc"];
        $handsoff = $_SESSION["last_target_handsoff"];
        $isbase = $_SESSION["last_target_isbase"];
        $theyprotected = $_SESSION["last_target_theyprotected"];
        $meprotected = $_SESSION["last_target_meprotected"];
        $lastcap = $_SESSION["lastcap"];
    } else { // get target info
        $cpspeedmult = 1.0;
        // get information about target
        $query = "select o_type,world.controller,bases.controller,block,quadrant,world.dlocation,world.last_captured from world left join bases on world.location=bases.location where world.location='$target'";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            $row = $result->fetch_row();
            if ($row[0] == 1) {
                $isbase = true;
                $controller = $row[2];
            } else {
                $controller = $row[1];
                if (($row[0] > 1) && ($row[0] < 10)) {
                    $iscontrolpoint = true;
                }
            }
            $cpspeedmult = getcontrolpointspeedmult($_SESSION["alliance"], $row[3], $row[4]);
            $dtarget = $row[5];
            $lastcap = $row[6];
        }
        $lwrcontroller = strtolower($controller);
        if (($lwrcontroller == 'rogue') || ($lwrcontroller == "") || ($lwrcontroller == "none")
                || (($lwrcontroller == 'system') && ($iscontrolpoint == true))) {
            $isnpc = true;
            $isenemy = true; // npcs are enemies
        } else if ($lwrcontroller == 'system') {
            $isnpc = true;
            $handsoff = true;
        } else {
            $isnpc = false;
            $theiralliance = "";
            // get both ai and target player records
            $query = "select alliance,name,buffs from player where name='$ai' or name='$controller'";
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0)) {
                while (($row = $result->fetch_row()) != null) {
                    if ($row[1] == $ai) {
                        $myalliance = $row[0];
                        $meprotected = checkprotected($row[2]);
                    } else {
                        $theiralliance = $row[0];
                        $theyprotected = checkprotected($row[2]);
                    }
                }
                if ($iscontrolpoint == true) {
                    // controller is actually alliance
                    $theiralliance = $lwrcontroller;
                }
                if ($lwrcontroller == strtolower($ai)) {
                    $myalliance = $theiralliance;
                }
                if ($myalliance == $theiralliance) {
                    $isfriend = true; // alliance members are friends
                } else {
                    $query = "select status from relations where type=".RELATIONS_TYPE_ALLIANCE." and source='$myalliance' and target='$theiralliance'";
                    $result = $mysqlidb->query($query);
                    if ($result && ($result->num_rows > 0)) {
                        $row = $result->fetch_row();
                        if ($row[0] == RELATIONS_STATUS_FRIEND) {
                            $isfriend = true;
                        } else {
                            $isenemy = true;
                        }
                    }
                }
                // allow personal relations to override alliance as enemy but not friend
                $query = "select status from relations where type=".RELATIONS_TYPE_PERSONAL." and source='$ai' and target='$controller'";
                $result = $mysqlidb->query($query);
                if ($result && ($result->num_rows > 0)) {
                    $row = $result->fetch_row();
                    if ($row[0] == RELATIONS_STATUS_FRIEND) {
                        if (!$isenemy) {
                            $isfriend = true;
                        }
                    } else {
                        $isfriend = false; // override alliance friend
                        $isenemy = true;
                    }
                }
            }
        }
        // update target cache
        $_SESSION["last_target_loc"] = $target;
        $_SESSION["last_target_dloc"] = $dtarget;
        $_SESSION["last_target_controller"] = $controller;
        $_SESSION["last_target_isenemy"] = $isenemy;
        $_SESSION["last_target_isfriend"] = $isfriend;
        $_SESSION["last_target_isnpc"] = $isnpc;
        $_SESSION["last_target_handsoff"] = $handsoff;
        $_SESSION["last_target_isbase"] = $isbase;
        $_SESSION["last_target_theyprotected"] = $theyprotected;
        $_SESSION["last_target_meprotected"] = $meprotected;
        $_SESSION["last_target_cpspeedmult"] = $cpspeedmult;
        $_SESSION["lastcap"] = $lastcap;
    } // get target info
    if ($handsoff == true) {
        $validationerror = "Target under system control, may not be target";
    } else {
        switch ($purpose) {
            case FORMATION_PURPOSE_TRANS:
                if (($isbase == true) && ($isfriend == false)) {
                    // if alliances match or mine then must be friend
                    $validationerror = "May only transport to friends, organization members or $ai";
                } else if (($isbase == false) && ($controller != $ai)) {
                    $validationerror = "May only transport to locs controlled by $ai";
                } else if ($hasdrudge == false) {
                    $validationerror = "Select Drudge AI";
                }
                break;
            case FORMATION_PURPOSE_RECON:
                if (($meprotected == true) && ($isnpc == false)) {
                    $validationerror = "May not recon a Master AI while you are under Central AI protection";
                } else if ($theyprotected == true) {
                    $validationerror = "May not recon a Master AI under Central AI protection";
                } else if (($isbase == true) && ($isenemy == false)) {
                    $validationerror = "May only recon rogue or enemy bases";
                } else if (($isbase == false) && ($isfriend == true)) {
                    $validationerror = "May only recon uncontrolled or nonfriend locs";
                } else if ($hasdrudge == false) {
                    $validationerror = "Select Drudge AI";
                } else if ($hascargo == true) {
                    $validationerror = "May only carry drone fuel during recon";
                }
                break;
            case FORMATION_PURPOSE_REINF:
                if (($meprotected == true) && ($isnpc == false)) {
                    $validationerror = "May not reinforce a Master AI while you are under Central AI protection";
                } else if ($isfriend == false) {
                    $validationerror = "May only reinforce friend or organization bases and locs";
                } else if ($hasdrudge == false) {
                    $validationerror = "Select Drudge AI";
                }
                break;
            case FORMATION_PURPOSE_ATACK:
            case FORMATION_PURPOSE_BLITZ:
            case FORMATION_PURPOSE_ASSLT:
                if (($meprotected == true) && ($isnpc == false)) {
                    $validationerror = "May not attack a Master AI while you are under Central AI protection";
                } else if ($theyprotected == true) {
                    $validationerror = "May not attack a Master AI under Central AI protection";
                } else if ($isenemy == false) {
                    if ($isbase == true) {
                        $validationerror = "May only attack rogue or enemy bases";
                    } else {
                        $validationerror = "May only attack enemy or uncontrolled locs";
                    }
                } else if ($hasdrudge == false) {
                    $validationerror = "Select Drudge AI";
                } else if ($hascargo == true) {
                    $validationerror = "May only carry drone fuel during attack";
                } else if (($purpose == FORMATION_PURPOSE_ASSLT) && (($isnpc == false) || (isbase == false))) {
                    $validationerror = "May only raze rogue bases";
                } else if (($isbase == true) && ($lastcap > (time() - 3600))) {
                    $validationerror = "One hour must elapse before captured/de-leveled base may be attacked";
                } else if (($iscontrolpoint == true) && ($lastcap > (time() - 86400))) {
                    $validationerror = "24 hours must elapse before captured control point may be attacked";
                }
                break;
            case FORMATION_PURPOSE_SCVNG:
                if (($isnpc == false) || ($isbase == true)) {
                    $validationerror = "Scavenging only allowed on uncontrolled locs";
                } else if ($hasdrudge == false) {
                    $validationerror = "Select Drudge AI";
                } else if ($hascargo == true) {
                    $validationerror = "May only carry drone fuel during scavenge";
                } else if ($iscontrolpoint == true) {
                    $validationerror = "Nothing to scavenge from control points";
                }
                break;
            case FORMATION_PURPOSE_MOVE:
                if (($controller != $ai) || ($isbase == false)) {
                    $validationerror = "Move only allowed to bases controlled by $ai";
                }
                break;
            case FORMATION_PURPOSE_RETRV:
                if ($controller != $ai) {
                    $validationerror = "May only retrieve resources from locs controlled by $ai";
                } else if ($hasdrudge == false) {
                    $validationerror = "Select Drudge AI";
                } else if ($hascargo == true) {
                    $validationerror = "May only carry drone fuel during retrieve";
                }
                break;
            default:
                break;
        } // switch ($purpose)
    } // if (!$handsoff)
    return $validationerror;
}


/*
 * checkforcarge - returns true if cargo contains anything but drone fuel
 */
function checkforcargo($payload) {
    $result = false;
    if ($payload != "") {
        $rarr = explode(";", $payload);
        for ($idx = 0; $idx < count($rarr); $idx++) {
            if ($rarr[$idx] != "") {
                $rparts = explode(":", $rarr[$idx]);
                if ((count($rparts) > 2) && ($rparts[0] != "RD") && ($rparts[2] != "0")) {
                    $result = true;
                    break;
                }
            }
        }
    }
    return $result;
}


/*
 * getfullresourcelist
 *  returns string list of all resource types with quantities
 *  from provided in strings
 *
 * $resstr1 = resource string in bases record format
 * $resstr2 = resource string in formation format
 * $fuel = amount of fuel
 *      (if fuel in str2 exceeds that in str1 then str2 amount is reduced)
 *
 *
 *
 * returns string in format "RD or D:type:quantity from str2:quantity from str1;..."
*/
function getfullresourcelist($resstr1, $resstr2, $fuel) {
    $rarr1 = explode("/", $resstr1); // assumed to have 5 values

    $rarr2 = null;
    $res2 = explode(";", $resstr2);
    foreach ($res2 as $res) {
        $rparts = explode(":", $res);
        // drone fuel in str2 is ignored and replaced by fuel
        if ((count($rparts) > 1) && ($rparts[0] == "R")) {
            $rarr2[$rparts[1]] = $rparts[2];
        }
    }

    $rlist = "RD:0:$fuel:0;";
    foreach ($rarr1 as $idx=>$res) {
        $rlist .= "R:$idx:";
        if ($rarr2 && isset($rarr2[$idx])) {
            if (($idx == 0) && (($rarr2[$idx] + $fuel) > $rarr1[$idx])) {
                $rarr2[$idx] = $rarr1[$idx] - $fuel;
                if ($rarr2[$idx] < 0) {
                    $rarr2[$idx] = 0;
                }
            }
            $rlist .= "{$rarr2[$idx]}:";
        } else {
            $rlist .= "0:";
        }
        $rlist .= "{$rarr1[$idx]};";
    }

    return $rlist;
}



/*
 * calcformationstats
 *  scans formation info and determines
 *  drones needed, cargo space needed, carrier space needed,
 *  speed, fuel (also updates payload with fuel needed)
 */
function calcformationstats($ai, $drones, $payload, $source, $target) {
    global $drone_basic_list;
    $stats = array(
        "fuel" => 0, // fuel needed to reach destination and return
        "speed" => 0, // speed of formation
        "distance" => 0, // distance from source to target
        "eta" => 0, // seconds required to travel distance
        "dronecount" => 0, // count of number of drones in formation
        "needdrones" => "", // drones needed to fill formation
        "formdrones" => "", // above formated for formation
        "needchassis" => "", // chassis needed to fill payload
        "spaceneeded" => 0, // space need to hold payload (excluding chassis)
        "spacehave" => 0, // payload capacity of drones
        "carrierneeded" => 0, // carrier space needed for chassis
        "carrierdrones" => 0, // carrier space needed for drones
        "carrierhave" => 0, // carrier capacity have
        "components" => "", // components needed to fill payload
        "resources" => explode("/", "0/0/0/0/0"), // resources needed to fill payload
        "payload" => "", // updated payload string
        "errors" => ""
        );
    $payarr = explode(";", $payload);
    $darr = explode (";", $drones);

    // extract (needdrones) drones to be removed from inventory
    //  could be in either bases or formation format, reformat for formation
    //  for formdrones, needdrones must be in bases format
    $stats["needdrones"] = "";
    foreach ($darr as $drone) {
        $dp = explode(":", $drone);
        if ((count($dp) > 2) && ($dp[2] != 0)) {
            $dtype = $dp[0];
            if (($dp[0] == "A") || ($dp[0] == "B")) {
                $dtype = $dp[1];
            }
            if (in_array($dtype, $drone_basic_list)) {
                if ($stats["errors"] != "") {
                    $stats["errors"] .= ", ";
                }
                $stats["errors"] .= "drone chassis must be transported as cargo";
            } else {
                if ($stats["formdrones"] != "") {
                    $stats["formdrones"] .= ";";
                }
                $stats["formdrones"] .= "A:$dtype:$dp[2]";
                $stats["carrierdrones"] += ($dp[2] * getrecipecarrierspace(RECIPE_TYPE_DRONE, $dtype, 1));
                $stats["dronecount"] += $dp[2];
                if ($stats["needdrones"] != "") {
                    $stats["needdrones"] .= ";";
                }
                $stats["needdrones"] .= "$dtype:1:$dp[2]";
            }
        }
    }

    // scan payload to determine how many chassis need to be carried
    for ($idx = count($payarr)-1; $idx >= 0; $idx--) {
        if ($payarr[$idx] != "") {
            $rparts = explode(":", $payarr[$idx]);
            if ($rparts[0] == "B") {
                if ($rparts[2] > 0) {
                    if ($stats["needchassis"] != "") {
                        $stats["needchassis"] .= ";";
                    }
                    $stats["needchassis"] .= "$rparts[1]:1:$rparts[2]";
                    // count up drone chassis
                    $stats["carrierneeded"] += ($rparts[2] * getrecipecarrierspace(RECIPE_TYPE_DRONE, $rparts[1], 1));
                    $payarr[$idx] = "$rparts[0]:$rparts[1]:$rparts[2]";
                } else {
                    $payarr[$idx] = "";
                }
            }
        }
    }

    // number of basic drones and compare to capactiy of drone carriers
    $carrierhave = getdronecarriercapacity($ai, $stats["needdrones"]);
    $stats["carrierhave"] = $carrierhave[0] + $carrierhave[1];
    if ($stats["carrierneeded"] > $stats["carrierhave"]) {
        $tmp1 = number_format($stats["carrierneeded"]);
        $tmp2 = number_format($stats["carrierhave"]);
        if ($stats["errors"] != "") {
            $stats["errors"] .= ", ";
        }
        $stats["errors"] .= "chassis occupy $tmp1 but carrier capacity is $tmp2";
    }
    $stats["notcarrieddrones"] = getdronesnotcarried($stats["needdrones"], ($stats["carrierhave"]-$stats["carrierneeded"]));
    // scan list of drones and get slowest speed
    //  include carrier capacity left over after including chassis
    if (!array_key_exists("last_target_cpspeedmult", $_SESSION)) {
        $_SESSION["last_target_cpspeedmult"] = 1;
    }
    $stats["speed"] = floor(getdronespeed($ai, $stats["notcarrieddrones"], 0) * $_SESSION["last_target_cpspeedmult"]);
    if ($stats["speed"] == 0) {
        $stats["speed"] = 1; // force at least 1km/hr
    }

    // recalc eta and fuel needed
    // calc delta between $source and $target
    if (($target != "") && ($target != "-1")) {
        $stats["distance"] = calcdistance($source, $target);
    } else {
        $stats["distance"] = 0;
    }

    // calc eta in seconds based on speed
    $stats["eta"] = floor(($stats["distance"] * 3600) / $stats["speed"]) + BASE_TRANSIT_TIME;

    // calc fuel use
    $stats["fuel"] = getdronefuel($ai, $stats["notcarrieddrones"], $stats["distance"]);
    if ($stats["fuel"] < 1) {
        $stats["fuel"] = 1; // at least 1 unit
    }
    // get sum of drone capacities - excludes drone carriers
    $stats["spacehave"] = getdronecapacity($ai, $stats["needdrones"]);

    // calc capacity used based on $payload
    //  extract (needres and needcomps) resources and components to be removed from inventory
    $usedcap = 0;
    $needcomps = "";
    for ($idx = count($payarr)-1; $idx >= 0; $idx--) {
        if ($payarr[$idx] != "") {
            $rparts = explode(":", $payarr[$idx]);
            if ((count($rparts) > 2) && ($rparts[2] > 0)) {
                if ($rparts[0] == "R") {
                    $stats["resources"][$rparts[1]] += $rparts[2];
                    $usedcap += $rparts[2];
                } else if ($rparts[0] == "RD") {
                    // take out old fuel amount and put in new $fuel
                    $rparts[2] = $stats["fuel"];
                    $payarr[$idx] = implode(":", $rparts);
                    $stats["resources"][$rparts[1]] += $rparts[2];
                    $usedcap += $rparts[2];
                } else if ($rparts[0] == "C") {
                    if ($rparts[2] > 0) {
                        $compstr = $rparts[1] . ":" . $rparts[2];
                        $needcomps .= $compstr . ";";
                    }
                    $usedcap += $rparts[2];
                }
                if (count($rparts) > 3) {
                    $payarr[$idx] = "$rparts[0]:$rparts[1]:$rparts[2]";
                }
            } else {
                $payarr[$idx] = "";
            }
        }
    }
    $stats["components"] = $needcomps;

    $stats["spaceneeded"] = $usedcap;
    if (($stats["spaceneeded"] > $stats["spacehave"]) && ($stats["dronecount"] != 0)) {
        $tmp1 = number_format($stats["spaceneeded"]);
        $tmp2 = number_format($stats["spacehave"]);
        if ($stats["errors"] != "") {
            $stats["errors"] .= ", ";
        }
        $stats["errors"] .= "payload occupies $tmp1 but formation capacity is $tmp2";
    }
    $stats["payload"] = "";
    foreach ($payarr as $pay) {
        if ($pay != "") {
            if ($stats["payload"] != "") {
                $stats["payload"] .= ";";
            }
            $stats["payload"] .= $pay;
        }
    }
    return $stats;
}

/*
 * dispatchstage - create formation record and timed event
 *
 */
function dispatchstage($ai, $source, $formstr) {
    global $mysqlidb;
    global $formationpurpnames;
    global $formationpurpevents;
    global $res_name; global $drone_name; global $comp_name;

    $abort = "Dispatch aborted,";

    $source = convertdloctoloc($source);

    $query = "select infra,drones,res_store,components,dlocation from bases where controller='$ai' and location='$source';";
    $result = $mysqlidb->query($query);
    if (!$result || ($result->num_rows == 0)) {
        postreport($ai, 0, "$abort Can not locate base record");
        return;
    }
    $row = $result->fetch_row();
    $stagelevel = getmodulelevel(MODULE_TRANSCEIVER, $row[0]);
    if ($stagelevel == 0) {
        postreport($ai, 0, "$abort Communications must be at least level 1");
        return;
    }
    $havedrones = $row[1];
    $haveres = explode("/", $row[2]);
    $havecomps = $row[3];
    $dsource = $row[4];

    $curforms = countmonitoredformations($ai, $source);
    if ($curforms >= $stagelevel) {
        postreport($ai, 0, "$abort Already at monitoring capacity");
        return;
    }

    $forminfo = explode("|", $formstr);
    if (count($forminfo) > 6) {
        // formstr format "name|id|drudge ai|purpose|target|drone1;...;dronen|res1;...;resn"
        // don't care about name and id
        $drudgeai = $forminfo[2];
        $purpose = $forminfo[3];
        $target = convertdloctoloc($forminfo[4]);
        $drones = $forminfo[5];
        $payload = $forminfo[6];
    } else {
        postreport($ai, 0, "$abort insufficient information to dispatch formation");
        return;
    }
    $hascargo = checkforcargo($payload);

    $_SESSION["last_target_loc"] = ""; // clear cached target info
    $validationerror = validpurposeontarget($ai, $purpose, $target, $drudgeai, $hascargo);
    if ($validationerror != "") {
        postreport($ai, 0, "$abort " . $validationerror);
        return;
    }
    $dtarget = $_SESSION["last_target_dloc"]; // set by validpurposeontarget

    // recalc fuel needed, validate capacity, validate drudge with all but transport
    //      produce nice list of drones
    $formstats = calcformationstats($ai, $drones, $payload, $source, $target);
    $status = "";
    if ($purpose < 0) {
        $status .= "Select purpose";
    }
    if (strlen($target) < 4) {
        if ($status != "") {
            $status .= ", ";
        }
        $status .= "Select target";
    }
    if ($formstats['dronecount'] == 0) {
        if ($status != "") {
            $status .= ", ";
        }
        $status .= "Select drones";
    }
    if ($formstats['errors'] != "") {
        if ($status != "") {
            $status .= ", ";
        }
        $status .= $formstats['errors'];
    }
    if ($status != "") {
        postreport($ai, 0, "$abort " . $status);
        return;
    }

    $maxdrones = MAX_DRONES_PER_STAGE_LEVEL * $stagelevel;
    if ($formstats["dronecount"] > $maxdrones) {
        $dronecount = number_format($formstats["dronecount"]);
        $maxdrones = number_format($maxdrones);
        postreport($ai, 0, "$abort drones in formation ($dronecount) exceed communications capacity of $maxdrones");
        return;
    }

    // check if sufficient drones and resources
    // check resources and create $newresstr
    $error = "";
    $newres = array(MAX_TOTAL_RES+1);
    for ($idx = 0; $idx < MAX_TOTAL_RES; $idx++) {
        $newres[$idx] = (int) ($haveres[$idx] - $formstats["resources"][$idx]);
        if ($newres[$idx] < 0) {
            $tmp1 = number_format($formstats["resources"][$idx]);
            $tmp2 = number_format($haveres[$idx]);
            if ($error != "") {
                $error .= ", ";
            }
            $error .= "{$res_name[$idx]}:($tmp1/$tmp2)";
        }
    }
    if ($error != "") {
        postreport($ai, 0, "$abort insufficient $error to fill payload");
        return;
    }
    $newresstr = implode("/", $newres);

    // check drones and create $newdronestr
    $dronestrs = removedronequantity($havedrones, $formstats["needdrones"]);
    if ($dronestrs[2] != "") {
        $dstr = "";
        $tmparr = explode(";", $dronestrs[2]);
        for ($idx = count($tmparr)-1; $idx >= 0; $idx--) {
            $darr = explode(":", $tmparr[$idx]);
            if ($dstr != "") {
                $dstr .= ", ";
            }
            $dstr .= $drone_name[$darr[0]];
        }
        postreport($ai, 0, "$abort insufficient $dstr drones to fill formation");
        return;
    }
    $newdronestr = $dronestrs[0];
    if ($formstats["needchassis"] != "") {
        $dronestrs = removedronequantity($newdronestr, $formstats["needchassis"]);
        if ($dronestrs[2] != "") {
            $dstr = "";
            $tmparr = explode(";", $dronestrs[2]);
            for ($idx = count($tmparr)-1; $idx >= 0; $idx--) {
                $darr = explode(":", $tmparr[$idx]);
                if ($dstr != "") {
                    $dstr .= ", ";
                }
                $dstr .= $drone_name[$darr[0]];
            }
            postreport($ai, 0, "$abort insufficient $dstr to fill payload");
            return;
        }
        $newdronestr = $dronestrs[0];
    }
    // check components and create $newcompstr
    $newcomps = removecomponentquantity($havecomps, $formstats["components"], 1, 1);
    if ($newcomps[1] != "") {
        $cstr = "";
        $tmparr = explode(";", $newcomps[1]);
        for ($idx = count($tmparr)-1; $idx >= 0; $idx--) {
            $darr = explode(":", $tmparr[$idx]);
            if ($cstr != "") {
                $cstr .= ", ";
            }
            $cstr .= $comp_name[$darr[0]];
        }
        postreport($ai, 0, "$abort insufficient $cstr to fill payload");
        return;
    }
    $newcompstr = $newcomps[0];

    // create formation record
    $params = "(controller,baseloc,basedloc,sourceloc,sourcedloc,targetloc,targetdloc,traveltime,distance,drudgeai,purpose,drones,payload,status)";
    $values = "('$ai','$source','$dsource','$source','$dsource','$target','$dtarget',"
                . "{$formstats['eta']},{$formstats['distance']},$drudgeai,$purpose,"
                . "'{$formstats['formdrones']}','{$formstats['payload']}'," . FORMATION_STATUS_MOVE . ")";
    $query = "insert into formations$params values$values";
    $result = $mysqlidb->query($query);
    if (!$result) {
        postreport ($ai, 0, "$abort unable to create formation");
        return;
    }
    $formid = $mysqlidb->insert_id; // get autogenerated id of formation just added

    // update drudge ai role
    $query = "update drudgeai set role=".DRUDGEAI_ROLE_ROAM." where entry=$drudgeai";
    $result = $mysqlidb->query($query);
    if (!$result) {
        postreport ($ai, 0, "$abort unable to update drudge AI record");
        // deleted formation
        $query = "delete from formations where entry=$formid";
        $mysqlidb->query($query);
        return;
    }

    // update base record
    $query = "update bases set drones='$newdronestr',res_store='$newresstr',components='$newcompstr' where controller='$ai' and location='$source'";
    $result = $mysqlidb->query($query);
    if (!$result) {
        postreport ($ai, 0, "$abort unable to update base record");
        // deleted formation
        $query = "delete from formations where entry=$formid";
        $mysqlidb->query($query);
        // set drudgeai back to idle
        $query = "update drudgeai set role=".DRUDGEAI_ROLE_IDLE." where entry=$drudgeai";
        $mysqlidb->query($query);
        return;
    }
    // formation may have a random encounter
    //  somewhere between 1/4 and 3/4 of the way to destination
    $d4 = ($formstats['eta'] - BASE_TRANSIT_TIME) / 4;
    $when = $d4 + mt_rand(1, 2*$d4) + BASE_TRANSIT_TIME;
    // add timed event
    $purpevent = $formationpurpevents[$purpose];
    $purpname = $formationpurpnames[$purpose];
    createtimedentry($formstats['eta'], $purpevent, $ai, "$source:$dsource", "$target:$dtarget", 0,
                                $drudgeai, $formid, "", "", "", $purpname, $when);
    postreport ($ai, 0, "Dispatched formation from $dsource to $dtarget, purpose: $purpname, eta: ". formatduration($formstats['eta']));
}


/*
 * savestageinfo -
 *      save stage information record
 *      saves name, purpose, drones and payload
 *
 */
function savestageinfo($ai, $formstr) {
    global $mysqlidb;
    $forminfo = explode("|", $formstr);
    if (count($forminfo) > 6) {
        // formstr format "name|id|drudge ai|purpose|target|drone1;...;dronen|res1;...;resn"
        // don't care about id, drudgeai, purpose, source or target
        $name = $forminfo[0];
        $purpose = $forminfo[3];
        // strip out drones with zero quantities
        $drones = "";
        $darr = explode(";",  $forminfo[5]);
        for ($idx = count($darr)-1; $idx >=0; $idx--) {
            $dp = explode (":", $darr[$idx]);
            if ((count($dp) > 2) && ($dp[2] != 0)) {
                if ($drones != "") {
                    $drones .= ";";
                }
                $drones .= "$dp[0]:$dp[1]:$dp[2]";
            }
        }
        $payload = "";
        $parr = explode(";", $forminfo[6]);
        for ($idx = count($parr)-1; $idx >=0; $idx--) {
            $pp = explode (":", $parr[$idx]);
            if ((count($pp) > 2) && ($pp[2] != 0)) {
                if ($payload != "") {
                    $payload .= ";";
                }
                $payload .= "$pp[0]:$pp[1]:$pp[2]";
            }
        }
    } else {
        $name = "";
        $drones = "";
        $payload = "";
    }
    if ($name == "") {
        postreport ($ai, 0, "Can not save formation without a name");
    } else {
        $where = "where controller='$ai' and comment='$name' and status=" . FORMATION_STATUS_SAVED;
        $query = "select entry from formations $where;";
        $result = $mysqlidb->query($query);
        if (!$result || ($result->num_rows == 0)) {
            $query = "insert into";
            $what = "Created";
            $where = "";
        } else {
            $query = "update";
            $what = "Updated";
        }
        $query .= " formations set controller='$ai',comment='$name',purpose=$purpose,drones='$drones',payload='$payload',status=" . FORMATION_STATUS_SAVED . " $where;";
        $result = $mysqlidb->query($query);
        if ($result) {
            postreport ($ai, 0, "$what saved formation: $name");
        }
    }
}

/*
 * countmonitoredformations -
 *  returns count of formations originating from this source location
 *  that are not saved formations and are not reinforcing a location
 *  controlled by ai
 */
function countmonitoredformations($ai, $source) {
    global $mysqlidb;

    $fcount = 0;
    $query = "select count(*) from formations join world on world.location=formations.targetloc"
                . " where formations.controller='$ai'"
                . " and (world.controller!='$ai' or formations.status!=".FORMATION_STATUS_REIN.")"
                . " and formations.baseloc='$source' and formations.status!=".FORMATION_STATUS_SAVED;
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_row();
        $fcount = $row[0];
    }
    $fcount += checktimedentry(TIMER_DRONE_DISMIS, $ai, "", $source, "");
    return $fcount;
}


/*
 * countallformations -
 *  returns count of formations originating from this source location
 *  that are not saved formations
 */
function countallformations($ai, $source) {
    global $mysqlidb;
    $fcount = 0;

    $source = convertdloctoloc($source);

    $query = "select count(*) from formations where formations.controller='$ai'"
                . " and formations.baseloc='$source' and formations.status!=".FORMATION_STATUS_SAVED.";";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_row();
        $fcount = $row[0];
    }
    return $fcount;
}


/*
 * getstageinfo -
 *  returns lines for stage information dialog
 *
 */
function getstageinfo($ai, $location, $formstr) {
    global $mysqlidb;
    global $formationpurpnames;
    $lineidx = 0;
    $validationerror = "";
    $status = "";
    $line = array();

    $location = convertdloctoloc($location);

    if (is_array($_SESSION["bases"])) {
        foreach ($_SESSION["bases"] as $arr) {
            if (!is_array($arr) || ($arr["location"] != $location)) {
                // skip to the one we need
                continue;
            }
            $base_name = $arr["name"];
            $base_modules = $arr["infra"];
            $base_drones = $arr["drones"];
            $base_drudgeais = $arr["drudgeais"];
            $base_res_store = $arr["res_store"];
            $base_components = $arr["components"];
            $dlocation = $arr["dlocation"];
            // parse formstr
            // formstr format "name|id|drudge ai|purpose|target|drone1;...;dronen|res1;...;resn"
            $forminfo = explode("|", $formstr);
            if (count($forminfo) > 6) {
                $form_id = $forminfo[1];
                $form_drudgeai = $forminfo[2];
                $form_purpose = $forminfo[3];
                $form_target = $forminfo[4];
                if (($form_target != "") && ($form_target != "-1")) {
                    $form_target = convertdloctoloc($form_target);
                }
                $form_drones = $forminfo[5];
                $form_payload = $forminfo[6];
            } else {
                $form_id = -1;
                $form_drudgeai = "";
                $form_purpose = -1;
                $form_target = "-1";
                $form_drones = "";
                $form_payload = "";
            }

            $stagelevel = getmodulelevel(MODULE_TRANSCEIVER, $base_modules);
            if ($stagelevel == 0) {
                $status .= "Can not dispatch formations with no communications";
                $line[$lineidx] = "STF|:No formations found";
                $lineidx++;
                $line[$lineidx] = "STFD|";
                $lineidx++;
                $line[$lineidx] = "STFP|";
                $lineidx++;
                $line[$lineidx] = "STI|||||||||||||||||";
                $lineidx++;
                $line[$lineidx] = "STP||";
                $lineidx++;
                $line[$lineidx] = "STL|";
                $lineidx++;
                $line[$lineidx] = "STDAI|";
                $lineidx++;
                $line[$lineidx] = "STBD|";
                $lineidx++;
                $line[$lineidx] = "STBI|";
                $lineidx++;
            } else {
                $line[$lineidx] = "STF|:No formations found";
                $query = "select entry,comment,drones,payload,purpose from formations where controller='$ai' and status=".FORMATION_STATUS_SAVED.";";
                $result = $mysqlidb->query($query);
                if ($result && ($result->num_rows > 0)) {
                    $line[$lineidx] = "STF|";
                    while (($row = $result->fetch_row()) != null) {
                        // print formation for select list
                        $line[$lineidx] .= "{$row[0]}:{$row[1]};";
                        if ($row[0] == $form_id) {
                            // this saved formation was selected so put drones/payload,purpose
                            //   as current formation selections
                            $form_drones = $row[2];
                            $form_payload = $row[3];
                            $form_purpose = $row[4];
                        }
                    }
                    $lineidx++;
                }

                // recalc fuel needed, validate capacity, validate drudge with all but transport
                //      produce nice list of drones
                $formstats = calcformationstats($ai, $form_drones, $form_payload, $location, $form_target);

                if ($form_purpose < 0) {
                    if ($status != "") {
                        $status .= ", ";
                    }
                    $status .= "Select purpose";
                }
                if (strlen($form_target) < 4) {
                    if ($status != "") {
                        $status .= ", ";
                    }
                    $status .= "Select target";
                }
                if ($formstats['dronecount'] == 0) {
                    if ($status != "") {
                        $status .= ", ";
                    }
                    $status .= "Select drones";
                }
                if ($formstats['errors'] != "") {
                    if ($status != "") {
                        $status .= ", ";
                    }
                    $status .= $formstats['errors'];
                }

                $line[$lineidx] = "STFD|" . getfulldronelist($base_drones, $formstats['formdrones'], "A");
                $lineidx++;
                $line[$lineidx] = "STFP|" . getfullresourcelist($base_res_store, $formstats['payload'], $formstats['fuel']) . ";"
                                            . getfulldronelist($base_drones, $formstats['payload'], "B") . ";"
                                            . getfullcomponentlist($base_components, $formstats['payload']);
                $lineidx++;
                // list of idle drudge AIs in this base
                $daitactics = 0;
                $base_drudgeais = preg_replace("/(,$|^,)/", "", $base_drudgeais); // strip leading or trailing commas
                $line[$lineidx] = "STDAI|";
                $query = "select entry,name,ctactics from drudgeai where entry in ($base_drudgeais) and role=".DRUDGEAI_ROLE_IDLE.";";
                $result = $mysqlidb->query($query);
                if ($result && ($result->num_rows > 0)) {
                    while ($row = $result->fetch_row()) {
                        $line[$lineidx] .= "{$row[0]}:{$row[0]}= {$row[1]};";

                        if ($row[0] == $form_drudgeai) {
                            $daitactics = $row[2];
                        }
                    }
                }
                $lineidx++;

                // calc max size of formation based on stage level
                $maxdrones = $stagelevel * MAX_DRONES_PER_STAGE_LEVEL;
                // get number of formations associated with this base that are not saved formations
                $curforms = countmonitoredformations($ai, $location);
                $eta = formatduration($formstats['eta']);
                $form_defense = getdronedefense($ai, $formstats['needdrones'], $daitactics);
                $form_offense = getdroneoffense($ai, $formstats['needdrones'], $daitactics);

                $line[$lineidx] = "STI|$dlocation|$base_name|$curforms|$stagelevel"
                                    . "|{$formstats['speed']}|$eta"
                                    . "|{$formstats['fuel']}|{$formstats['spaceneeded']}"
                                    . "|{$formstats['spacehave']}|{$formstats['dronecount']}"
                                    . "|$maxdrones|{$formstats['carrierneeded']}"
                                    . "|{$formstats['carrierdrones']}|{$formstats['carrierhave']}"
                                    . "|$form_defense|$form_offense";
                $lineidx++;

                $line[$lineidx] = "STP|$form_purpose|";
                // make list of purposes
                foreach ($formationpurpnames as $pidx=>$name) {
                    $line[$lineidx] .= "$pidx:$name;";
                }
                $lineidx++;
                if ($form_purpose >= 0) {
                    // validate the formation contents, target and purpose
                    $hascargo = checkforcargo($formstats['payload']);
                    $validationerror = validpurposeontarget($ai, $form_purpose, $form_target, $form_drudgeai, $hascargo);
                }

                // get list of saved target locs:
                //      savedlocs in player record, locations of bases, locs in bases records
                $line[$lineidx] = "STL|" . $_SESSION["savelocs"] . ";" . $_SESSION["controlocs"];
                $lineidx++;
            }
            $line[$lineidx] = "STX|$status|$validationerror";
            $lineidx++;
        } // foreach ($_SESSION["bases"] as $arr)
    } // if (is_array($_SESSION["bases"]))
    return ($line);
}


/*
 * printincformations - print lines for display of formations that are
 *      within scan range and have $bloc targeted
 */
function printincformations($ai, $bloc) {
    $found = 0;

    $bloc = convertdloctoloc($bloc);
    $dbloc = "";

    if (is_array($_SESSION["bases"])) {
        foreach ($_SESSION["bases"] as $arr) {
            if (!is_array($arr) || ($arr["location"] != $bloc)) {
                // skip to the one we need
                continue;
            }

            // determine what formations are detectable
            // and print a line for each
            $dbloc = $arr["dlocation"];
            $found = showformationsinc($ai, $arr, true);
            break;
        }
    }
    if ($found == 0) {
        echo ("FRM||$dbloc|No incoming formations detected||||\n");
    }
}

/*
 * showformationsinc - returns code for specified base if any formations targeting
 *  specified base that are within scan range specified by dpskill and scan
 *  module level.
 *  $brow must contain entries for location, modules and locs
 *  if print is true or formation_array is an array then returns
 *      count of formations detected
 *  otherwise returns flag:
 *      0 if no formations
 *      1 if reinforce/transport formations
 *      2 if recon formations
 *      3 if attack formations
 */
function showformationsinc($ai, $brow, $print, &$formation_array = null) {
    global $mysqlidb;
    $formationsdetected = 0;
    $formationsinc = 0;
    $thisformation = 0;
    if (is_array($formation_array)) {
        $arridx = count($formation_array);
    }

    $ranges = getscannerrange($ai, $brow);

    $deltastr = "convert((dueTime-now()),signed) as eta";

    $locstocheck = "'" . str_replace(",", "','", $brow["locstr"]) . "'";

    $query = "select $deltastr,formations.*,timer_queue.dueTime from formations left join timer_queue on "
                . "(formations.entry=timer_queue.drones) "
                . "where formations.controller!='$ai' and formations.targetloc in ($locstocheck) "
                . " and (type>=".TIMER_DRONE_TRANS.") and (type<=".TIMER_DRONE_ASSLT.")";

    $tqresult = $mysqlidb->query($query);
    if ($tqresult && ($tqresult->num_rows > 0)) {
        while (($row = $tqresult->fetch_assoc()) != null) {
            $eta = $row["eta"];
            if ($eta < 0) {
                $eta = 0;
            }
            $thisformation = 0;
            // calc current distance between formation and this this base
            $fspeed = $row["distance"] / ($row["traveltime"] - BASE_TRANSIT_TIME);
            $ftdist = $eta * $fspeed;
            if ($ftdist > $row["distance"]) {
                $ftdist = $row["distance"];
            }
            $fstdist = $row["distance"];
            // check if target is base or loc controlled by base
            if ($brow["location"] != $row["targetloc"]) {
                // calc real distance of formation from base based on bearings
                $fstbear = calcbearing($row["sourceloc"], $row["targetloc"]); // formation source to target bearing
                $fsbbear = calcbearing($row["sourceloc"], $brow["location"]); // formation source to base bearing
                $angle1 = abs($fstbear - $fsbbear);
                $tfsbear = (180 + $fstbear) % 360; // target to formation source bearing
                $tbbear = calcbearing($row["targetloc"], $brow["location"]); // target to base bearing
                $angle2 = abs($tfsbear - $tbbear);
                // cdist is closest distance between base and formation path
                // bfdist is distance from base to formation source
                // btdist is distance from base to target
                $tan1 = tan(deg2rad($angle1));
                $tan2 = tan(deg2rad($angle2));
                if (($tan1 == 0) || ($tan2 == 0)) {
                    $cdist = 0; // path crosses over base!
                } else {
                    $cdist = floor($fstdist / ((1 / $tan1) + (1 / $tan2)));
                }
                if ($cdist > $ranges[0]) {
                    continue; // if will never get in detect range, don't bother
                }
                $c2dist = pow($cdist, 2);
                $bfsdist = calcdistance($brow["location"], $row["sourceloc"]);
                $btdist = calcdistance($brow["location"], $row["targetloc"]);
                // ctdist is distance from closest point to target
                $bt2dist = pow($btdist, 2);
                if ($bt2dist > $c2dist) {
                    $ctdist = sqrt($bt2dist - $c2dist);
                } else {
                    $ctdist = 0;
                }
                if ($ftdist > $ctdist) {
                    if ($fstdist <= $ctdist) {
                        $bfdist = $bfsdist; // at source still
                    } else {
                        // ds1 is speed to get from source distance to closest point distance
                        //$ds1 = ($bfsdist - $cdist) / (($fstdist - $ctdist) / $fspeed);
                        // dt1 is time to get from formation location to closest point location
                        //$dt1 = ($ftdist - $ctdist) / $fspeed;
                        //$bf1dist = $cdist + ($ds1 * $dt1);
                        // following is condensed calc of above
                        $bfdist = $cdist + ((($bfsdist - $cdist) * ($ftdist - $ctdist)) / ($fstdist - $ctdist));
                    }
                } else {
                    if ($ctdist <= 0) {
                        $bfdist = $ftdist; // at target
                    } else {
                        // ds2 is speed to get from closest point distance to target distance
                        //$ds2 = ($btdist - $cdist) / ($ctdist / $fspeed);
                        //$bf2dist = $cdist + ($ds2 * (($ctdist-$ftdist) / $fspeed));
                        // following is condensed calc of above
                        $bfdist = $cdist + ((($btdist - $cdist) * ($ctdist-$ftdist)) / $ctdist);
                    }
                }
                //postlog("eta=$eta; bfdist=$bfdist; (btdist=$btdist; cdist=$cdist; bfsdist=$bfsdist) ctdist=$ctdist; ftdist=$ftdist");
                //postlog("ds1=$ds1; dt1=$dt1; ds2=$ds2; fspeed=$fspeed; bf1dist=$bf1dist; bf2dist=$bf2dist");
            } else {
                $bfdist = $ftdist;
            }

            if ($bfdist <= $ranges[0]) {
                $formationsdetected++;
                if (($row["purpose"] == FORMATION_PURPOSE_ATACK)
                        || ($row["purpose"] == FORMATION_PURPOSE_BLITZ)
                        || ($row["purpose"] == FORMATION_PURPOSE_ASSLT)) {
                    $thisformation = 3;
                } else if ($row["purpose"] == FORMATION_PURPOSE_RECON) {
                    $thisformation = 2;
                } else {
                    $thisformation = 1;
                }
                if ($formationsinc < $thisformation) {
                    $formationsinc = $thisformation;
                }
                if ($print == true) {
                    printformation($ai, $row, ($bfdist <= $ranges[1]), $eta, true);
                }
                if (is_array($formation_array))
                {
                    $formation_array[$arridx] = printformation($ai, $row, ($bfdist <= $ranges[1]), $eta, false);
                    $arridx++;
                }
            }
        }
    }
    if (($print == false) && !is_array($formation_array)) {
        return $formationsinc;
    }
    return $formationsdetected;
}


/*
 * printformation - prints line for a formation record
 */
function printformation($ai, &$row, $details, $eta, $print) {
    global $mysqlidb;
    global $tech_name; global $form_purp_name;

    $tag = "FRMR"; // recallable
    $base = "From {$row['basedloc']}:";
    $purpstr = $form_purp_name[$row["purpose"]];

    if ($row["controller"] != $ai) {
        $tag = "FRMI";
        switch ($row["purpose"]) {
            case FORMATION_PURPOSE_RECON:
                $tag .= "Y";
                break;
            case FORMATION_PURPOSE_SCVNG:
                $tag .= "R";
                break;
            case FORMATION_PURPOSE_ATACK:
                $tag .= "R";
                break;
            case FORMATION_PURPOSE_BLITZ:
                $tag .= "R";
                break;
            case FORMATION_PURPOSE_ASSLT:
                $tag .= "R";
                break;
        }
        $base = "[{$row['controller']}] $base";
    }
    $statstr = "";
    switch ($row["status"]) {
        case FORMATION_STATUS_IDLE:
            $statstr = "idle at {$row['targetdloc']}";
            break;
        case FORMATION_STATUS_REIN:
            if ($row["controller"] != $ai) {
                $tag = "FRMS"; // dismissable
            }
            $statstr = "reinforcing {$row['targetdloc']}";
            break;
        case FORMATION_STATUS_MOVE:
            $statstr = "moving to $purpstr {$row['targetdloc']}";
            break;
        case FORMATION_STATUS_RETRN:
            $statstr = "returning to {$row['targetdloc']}";
            break;
        case FORMATION_STATUS_SAVED:
            $tag = "FRMD"; // deletable
            $base = "Saved formation: {$row['comment']}";
            break;
        case FORMATION_STATUS_COMBAT:
            $tag = "FRMC";
            $statstr = "in combat ($purpstr) at {$row['targetdloc']}";
            break;
    }
    $daistr = "";
    $drudgeai = $row['drudgeai'];
    if ($drudgeai == 0) {
        $drudgeai = "";
    } else {
        $queryd = "select name from drudgeai where entry=$drudgeai;";
        $resultd = $mysqlidb->query($queryd);
        if ($resultd && ($resultd->num_rows > 0)) {
            $rowd = $resultd->fetch_row();
            $daistr = " drudge AI: {$rowd[0]}";
        }
    }
    if ($details) {
        $paystr = str_replace(";", ",", ($row['drones'] . "|" . $row['payload'] . "|" . $drudgeai));
    } else {
        $paystr = "|Insufficient " . $tech_name[TECH_DATA_PROC] . " skill to determine formation details at this distance|";
    }
    if ($eta && ($eta != "")) {
        $eta = "eta: " . formatduration($eta);
    } else {
        $eta = ""; // in case is null
    }
    $ret = "$tag|{$row['entry']}|{$row['targetdloc']}|$base $statstr $daistr $eta|$paystr|{$row['controller']}|";
    if ($print == true) {
        print $ret;
    }
    return $ret;
}


/*
 * hasformations -
 * checks if any formation
 *  associated with a set of locations.
 *  $locs should be one or more locations separted by commas
 */
function hasformations($ai, $locs) {
    global $mysqlidb;
    $numfound = 0;

    // masage locs to be used as in clause
    $locstr = "'" . str_replace(",", "','", $locs) . "'";

    // get all formations controlled by $ai at $loc
    $query = "select formations.*,timer_queue.dueTime from formations left join timer_queue on "
                . "(formations.entry=timer_queue.drones) "
                . "where formations.controller='$ai' and formations.baseloc in ($locstr)";
    $result = $mysqlidb->query($query);
    if ($result) {
        $numfound = $result->num_rows;
    }
    if ($numfound == 0) {
        // get anyone elses formations that are reinforcing this $loc
        $query = "select formations.* from formations join world on world.location=formations.targetloc"
                    . " where formations.controller!='$ai' and world.controller='$ai'"
                    . " and formations.status=".FORMATION_STATUS_REIN." and formations.targetloc in ($locstr)";
        $result = $mysqlidb->query($query);
        if ($result) {
            $numfound = $result->num_rows;
        }
    }
    return ($numfound > 0);
}

/*
 * getformationsatloc -
 *  gets lines for display of formations reinforcing or in combat at specified location
 *  only for those controlled by $ai
 */
function getformationsatloc($ai, $loc) {
    global $mysqlidb;
    $list = array();
    $listidx = 0;

    $loc = convertdloctoloc($loc);

    $query = "select * from formations where controller='$ai' and targetloc='$loc' and (status=" . FORMATION_STATUS_REIN . " or status=" . FORMATION_STATUS_COMBAT . ")";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        while (($row = $result->fetch_assoc()) != null) {
            $list[$listidx] = printformation($ai, $row, true, "", false);
            $listidx++;
        }
    }
    if (count($list) == 0) {
        $list[0] = "FRM|||No formations controlled by $ai at this location||||";
    }
    return $list;
}

/*
 * getorgformationlist
 *  Gets lines for display of all formations for all members of
 *  an organization. Sorted by controller name
 *  Does not display a list for Beginner org
 */
function getorgformationlist($org) {
    global $mysqlidb;
    $list = array();
    $listidx = 0;

    if (($org != "") && (strtolower($org) != "beginner")) {
        $query = "select formations.*,timer_queue.dueTime "
                    . "from player join formations on (player.name=formations.controller) "
                    . "left join timer_queue on (formations.entry=timer_queue.drones) "
                    . "where player.alliance='$org' and formations.status!=" . FORMATION_STATUS_SAVED . " order by name asc, formations.entry asc";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            while (($row = $result->fetch_assoc()) != null) {
                $list[$listidx] = str_replace("|", "{", printformation($row['controller'], $row, true, $row['dueTime'], false));
                $listidx++;
            }
        }
    }

    return $list;
}





/*
 * getallformations -
 *  gets lines for display of all formations
 *  associated with a set of locations.
 */
function getallformations($ai, &$base) {
    global $mysqlidb;
    $list = array();
    $listidx = 0;
    // masage locs to be used as in clause
    $locstr = "'" . str_replace(",", "','", $base["locstr"]) . "'";

    //get any incoming formations that are within scanner range
    // determine what formations are detectable
    // and puts a line for each into $list
    $listidx += showformationsinc($ai, $base, true, $list);
    $location = $base["location"];

    // get all formations controlled by $ai at $loc
    $query = "select formations.*,timer_queue.dueTime from formations left join timer_queue on "
                . "(formations.entry=timer_queue.drones) "
                . "where formations.controller='$ai' "
                . " and ((timer_queue.type>=".TIMER_DRONE_TRANS." and timer_queue.type<=".TIMER_DRONE_ASSLT
                . ") or formations.status=".FORMATION_STATUS_REIN." or formations.status=".FORMATION_STATUS_COMBAT
                . ") and formations.baseloc='$location'";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        while (($row = $result->fetch_assoc()) != null) {
            $list[$listidx] = printformation($ai, $row, true, $row["dueTime"], false);
            $listidx++;
        }
    }
    // get anyone elses formations that are reinforcing this $base or its controlled locations
    $query = "select formations.* from formations join world on world.location=formations.targetloc"
                . " where formations.controller!='$ai' and world.controller='$ai'"
                . " and formations.status=".FORMATION_STATUS_REIN." and formations.targetloc in ($locstr)";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        while (($row = $result->fetch_assoc()) != null) {
            $list[$listidx] = printformation($ai, $row, true, "", false);
            $listidx++;
        }
    }
    return $list;
}


/*
 * recallformation -
 *  recalls a formation
 *  must be controlled by $ai
 */
function recallformation($thisai, $formid) {
    global $mysqlidb;

    $query = "select * from formations where entry=$formid";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_assoc();
        // over ride $ai with formation controller
        $formai = $row["controller"];
        // swap source and target
        $source = $row['targetloc'];
        $dsource = $row['targetdloc'];
        $target = $row['baseloc'];
        $dtarget = $row['basedloc'];
        $status = $row['status'];
        $reason = "Recall";
        if ($formai != $thisai) {
            $reason = "Dismiss";
        }

        if ($status == FORMATION_STATUS_COMBAT) {
            postreport ($thisai, 0, "Unable to $reason formation while it is in combat");
        } else {  // formation not in combat
            if ($row["status"] == FORMATION_STATUS_REIN) {
                $query = "select controller from world where location='$source'";
                $wresult = $mysqlidb->query($query);
                if ($wresult && ($wresult->num_rows > 0)) {
                    $wrow = $wresult->fetch_row();
                    if ($wrow[0] == "system") {
                        postreport ($thisai, 0, "Unable to $reason reinforcements from a location under construction");
                        return;
                    } else if (($formai != $thisai) && ($wrow[0] != $thisai)) {
                        postreport ($thisai, 0, "Unable to $reason a reinforcing formation unless you controll the formation or the location it is reinforcing");
                        return;
                    }
                }
            } else if ($formai != $thisai) {
                postreport ($thisai, 0, "Unable to $reason a moving formation unless you are the controller");
                return;
            }
            $curforms = countmonitoredformations($formai, $row['baseloc']);
            $maxforms = $curforms+1;
            if ($formai != $thisai) {
                // use $_SESSION["bases"] array which contains all bases info set via poll in bases.php
                if (is_array($_SESSION["bases"])) {
                    foreach ($_SESSION["bases"] as $arr) {
                        if ($arr["location"] == $row['baseloc']) {
                            $maxforms = getmodulelevel(MODULE_TRANSCEIVER, $arr["infra"]);
                            break;
                        }
                    }
                }
            } else {
                $query = "select infra from bases where location='" . $row['baseloc'] . "'";
                $bresult = $mysqlidb->query($query);
                if ($bresult && ($bresult->num_rows > 0)) {
                    $brow = $bresult->fetch_row();
                    $maxforms = getmodulelevel(MODULE_TRANSCEIVER, $brow[0]);
                }
            }
            if (($curforms >= $maxforms) && ($row["status"] == FORMATION_STATUS_REIN)) {
                postreport ($thisai, 0, "Unable to $reason formation as $formai's base at {$row['baseloc']} is already at monitoring maximum");
            } else if ($row["status"] == FORMATION_STATUS_SAVED) {
                postreport ($thisai, 0, "Unable to $reason a saved formation");
            } else if ($row["status"] != FORMATION_STATUS_RETRN) {
                // set timed event to tell queue server to dismiss this formation
                $drudgeai = $row["drudgeai"];
                createtimedentry(0, TIMER_DRONE_DISMIS, $formai, "$source:$dsource", "$target:$dtarget", 0,
                                        $drudgeai, $formid, "", "", "", $reason . "ed");
                $rstr = "Sent command to $reason formation from $dsource to $dtarget";
                postreport ($formai, 0, $rstr);
                if ($thisai != $formai) {
                    postreport ($thisai, 0, $rstr);
                }
            }
        }   // formation not in combat
    } else {
        postreport ($thisai, 0, "Unable to locate formation to recall/dismiss");
    }
}

/*
 * deleteformation - deletes saved formation
 */
function deleteformation($ai, $formstr) {
    global $mysqlidb;
    $forminfo = explode("|", $formstr);
    if (count($forminfo) > 2) {
        // formstr format "name|id|drudge ai|purpose|target|drone1;...;dronen|res1;...;resn"
        // don't care about anything but name and id
        $formname = $forminfo[0];
        $formid = $forminfo[1];

        $query = "delete from formations where controller='$ai' and status=".FORMATION_STATUS_SAVED." and entry=$formid";
        $result = $mysqlidb->query($query);
        if ($result) {
            postreport ($ai, 0, "Deleted saved formation: $formname");
        } else {
            postreport ($ai, 0, "Unable to locate saved $formname formation to delete");
        }
    }
}


?>
