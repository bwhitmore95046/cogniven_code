<?php
/*
 * Functions to deal with drones
 * Author: Chris Bryant
 */

$drone_basic_list = array(DRONE_WORKR, DRONE_TRANS, DRONE_RECON, DRONE_FIGHT);
$drone_advanced_list = array(DRONE_WORKR_CON, DRONE_WORKR_MIN, DRONE_WORKR_SCV,
                            DRONE_TRANS_MAT, DRONE_TRANS_GND, DRONE_TRANS_AIR,
                            DRONE_RECON_SVY, DRONE_RECON_SCO, DRONE_RECON_SCN,
                            DRONE_FIGHT_LIT, DRONE_FIGHT_MED, DRONE_FIGHT_HVY,
                            DRONE_FIGHT_DEF);
$drone_total_list = array(DRONE_WORKR, DRONE_TRANS, DRONE_RECON, DRONE_FIGHT,
                            DRONE_WORKR_MIN, DRONE_WORKR_CON, DRONE_WORKR_SCV,
                            DRONE_TRANS_MAT,
                            DRONE_RECON_SVY, DRONE_RECON_SCO, DRONE_RECON_SCN,
                            DRONE_FIGHT_LIT, DRONE_FIGHT_MED, DRONE_FIGHT_HVY,
                            DRONE_FIGHT_DEF, DRONE_TRANS_GND, DRONE_TRANS_AIR);

// in order from slowest to fastest
$drone_speedorder = array(DRONE_RECON, DRONE_TRANS, DRONE_FIGHT, DRONE_WORKR,
                        DRONE_WORKR_CON, DRONE_FIGHT_HVY, DRONE_WORKR_SCV,
                        DRONE_WORKR_MIN, DRONE_FIGHT_DEF, DRONE_FIGHT_MED,
                        DRONE_TRANS_MAT, DRONE_FIGHT_LIT, DRONE_RECON_SVY,
                        DRONE_RECON_SCO, DRONE_RECON_SCN);

/*
 * encodedronelist - takes a multi dimensional array
 *      array is 2 dimensions comprised of type, level, quantity
 *          eg. arr[type][level] = quantity
 *      array is sorted by ascending type
 *      creates a string in format "type:level:quantity;type:level:quantity;..." repeating for each index entry
 */
function encodedronelist($dronearray) {
    $dronestr = "";
    asort($dronearray);
    foreach ($dronearray as $type => $levelarray) {
        foreach ($levelarray as $level => $quantity) {
            if ($quantity > 0) {
                $dronestr .= "$type:$level:$quantity;";
            }
        }
    }
    return $dronestr;
}


/* decodedronelist -
 *  takes a string in format as produced by encodedronelist
 *      and returns multi dimensional array
 *      if $names is true then uses name strings as first index otherwise uses type numbers
 */
function decodedronelist($dronestr, $names) {
    global $drone_name;
    $dronearray = array();

    if ($names) {
        $dronearray[$drone_name[DRONE_RECON]][1] = 0;
        $dronearray[$drone_name[DRONE_TRANS]][1] = 0;
        $dronearray[$drone_name[DRONE_FIGHT]][1] = 0;
        $dronearray[$drone_name[DRONE_WORKR]][1] = 0;
    } else {
        $dronearray[DRONE_RECON][1] = 0;
        $dronearray[DRONE_TRANS][1] = 0;
        $dronearray[DRONE_FIGHT][1] = 0;
        $dronearray[DRONE_WORKR][1] = 0;
    }

    $typearray = explode(";", $dronestr); // each entry has format "type:level:quantity"
    foreach ($typearray as $typestr) {
        // levelarry should have at least 3 entries in array
        $levelarray = explode(":", $typestr);
        $off = 0;
        if ($levelarray[0] == "D") {
            $off = 1;
        }
        if ((count($levelarray) > 2) && ($levelarray[$off+2] > 0)) {
            for ($idx = 0; $idx < 3; $idx++) {
                $levarr[$idx] = floor($levelarray[$off+$idx]);
            }
            if ($names) {
                $dronearray[$drone_name[$levarr[0]]][$levarr[1]] = $levarr[2];
            } else {
                $dronearray[$levarr[0]][$levarr[1]] = $levarr[2];
            }
        }
    }
    return $dronearray;
}

/*
 * getdronerecycletimes
 *  returns a string in format "type:time;type:time;..."
 *  where time is number of seconds required to recycle the drone type
 *  types include all drone types
 */
function getdronerecycletimes($ai, &$baserow) {
    $retstr = "";

    $droneorderarr = explode(",", getrecipelist(RECIPE_TYPE_DRONE));

    foreach ($droneorderarr as $dtype) {
        $rec = getrecipeline($ai, RECIPE_TYPE_DRONE, $dtype, 0, "DD", 1, 0, $baserow, TECH_RECYCLE, DRUDGEAI_ROLE_BASE);

        $rarr = explode("|", $rec);
        $retstr .= "$dtype:" . round(floor($rarr[9])/4) . ";";

    } // foreach ($dronelist as $dtype)
    return $retstr;
}

/*
 * getrepairinfo
 *  returns a array composed of:
 *      current repair jobs in progress
 *      dronetype;;;;;fuel;metal;mineral;xtal;time;;
 *          ... repeat drone type line for all drone types
 *
 * padding of drone type lines is required to make them align with module line format
 */
function getdronerepairinfo($ai, &$baserow) {
    $retstr = array();
    $stridx = 0;

    $retstr[$stridx] = checktimedentry(TIMER_DRONE_REPAIR, $ai, $baserow['location'], "", "");
    $stridx++;

    $droneorderarr = explode(",", getrecipelist(RECIPE_TYPE_DRONE));

    foreach ($droneorderarr as $dtype) {
        $rec = getrecipeline($ai, RECIPE_TYPE_DRONE, $dtype, 0, "DD", 0, 0, $baserow, TECH_DRONE_REP, DRUDGEAI_ROLE_BASE);

        $rarr = explode("|", $rec);
        $needres = array(MAX_NORMAL_RES+1);
        // 1/8 the resources
        for ($idx = 0; $idx < MAX_NORMAL_RES; $idx++) {
            $needres[$idx] = round(floor($rarr[$idx+5]) / 8);
            if ($needres[$idx] < 1) {
                $needres[$idx] = 1;
            }
        }
        // repair uses only 1/4 time
        $needres[MAX_NORMAL_RES] = round(floor($rarr[9]) / 4);
        if ($needres[MAX_NORMAL_RES] < 1) {
            $needres[MAX_NORMAL_RES] = 1;
        }

        $retstr[$stridx] = "$dtype;;;;;" . implode(";", $needres) . ";;";
        $stridx++;
    }
    return ($retstr);
}


/*
 * getfulldronelist
 *  returns string list of all drone types with quantities
 *  from provided array
 *
 * $dronestr1 = drone string in bases record format
 * $dronestr2 = drone string in formation format
 * $type = "A" for advanced drones
 *          "B" for basic drones
 *          "AB" (or anything else) for both
 *
 * returns string in format "type:drone type:quantity from str2:quantity from str1;..."
 */
function getfulldronelist($dronestr1, $dronestr2, $type) {
    global $drone_advanced_list; global $drone_basic_list; global $drone_total_list;

    $dlist = $drone_total_list;
    switch ($type) {
        case "A":
            $dlist = $drone_advanced_list;
            break;
        case "B":
            $dlist = $drone_basic_list;
            break;
    }

    $darr1 = null;
    $drones = explode(";", $dronestr1);
    foreach($drones as $drone) {
        $dparts = explode(":", $drone);
        if (count($dparts) > 2) {
            $darr1[$dparts[0]] = $dparts[2];
        }
    }

    $darr2 = null;
    $drones = explode(";", $dronestr2);
    foreach($drones as $drone) {
        $dparts = explode(":", $drone);
        if (count($dparts) > 2) {
            if (($dparts[0] == $type) || ($type == "AB")) {
                $darr2[$dparts[1]] = $dparts[2];
            }
        }
    }

    $dstr = "";
    foreach ($dlist as $dtype) {
        $dstr .= "$type:$dtype:";
        if ($darr2 && isset($darr2[$dtype])) {
            $dstr .= "{$darr2[$dtype]}:";
        } else {
            $dstr .= "0:";
        }
        if ($darr1 && isset($darr1[$dtype])) {
            $dstr .= "{$darr1[$dtype]};";
        } else {
            $dstr .= "0;";
        }
    }

    return ($dstr);
}


/*
 * getdronesnotcarried
 *  removes drones that don't fit into carriers from slowest to fastest
 *  carriercap must already have been adjusted for skills
 *  dronestr and returned are in base format
 */
function getdronesnotcarried($dronestr, $carriercap) {
    global $drone_speedorder;
    $resultstr = "";

    if ($carriercap <= 0) {
        $resultstr = $dronestr;
    } else {
        $darr = decodedronelist($dronestr, false);
        foreach ($drone_speedorder as $dtype) {
            if ((isset($darr[$dtype])) && ($dtype != DRONE_TRANS_GND) && ($dtype != DRONE_TRANS_AIR)) {
                $neededcap = $darr[$dtype][1] * getrecipecarrierspace(RECIPE_TYPE_DRONE, $dtype, 1);

                if ($neededcap > $carriercap) {
                    $darr[$dtype][1] -= floor($neededcap / $carriercap);
                    break;
                } else {
                    $carriercap -= $neededcap;
                    $darr[$dtype][1] = 0;
                }
            }
        }
        $resultstr = encodedronelist($darr);
    }
    return $resultstr;
}

/*
 * getdronespeed - gets speed of slowest drone in the list
 *  if carriers are included then removed slowest drones untill
 *  they are full then pick slowest
 *
 * this calculation must match the calculation don in Drone.java
 *  for the queue server
 */
function getdronespeed($ai, $dronestr, $carriercap) {
    global $drone_speedorder;
    $minspeed = 0;

    // set min to speed of slower carrier
    if (getdronecount($dronestr, DRONE_TRANS_GND) > 0) {
        $stats = explode(";", getrecipestats(RECIPE_TYPE_DRONE, DRONE_TRANS_GND, 1));
        if (count($stats) > 4) {
            $minspeed = $stats[4];
        }
    } else if (getdronecount($dronestr, DRONE_TRANS_AIR) > 0) {
        $stats = explode(";", getrecipestats(RECIPE_TYPE_DRONE, DRONE_TRANS_AIR, 1));
        if (count($stats) > 4) {
            $minspeed = $stats[4];
        }
    }
    $darr = decodedronelist($dronestr, false);
    foreach ($drone_speedorder as $dtype) {
        if (isset($darr[$dtype])) {
            $carriercap -= $darr[$dtype][1] * getrecipecarrierspace(RECIPE_TYPE_DRONE, $dtype, 1);
            if ($carriercap < 0) {
                // carriers are full so pick this as speed since it may be slowest
                // if it's faster than carriers just pick carrier speed as we've
                //  already passed anything slower than carriers
                $stats = explode(";", getrecipestats(RECIPE_TYPE_DRONE, $dtype, 1));
                if ((count($stats) > 4) && (($stats[4] < $minspeed) || ($minspeed == 0))) {
                    $minspeed = $stats[4];
                }
                break;
            }
        }
    }
    // increase drone speed by tech level
    $minspeed = $minspeed * gettechmult($ai, "", TECH_DRONE_MOVE) * getdronespeedmult();

    return floor($minspeed);
}

/*
 * getdronecarriercapacity - gets sum of all capacities of drone carriers in list
 *  returns 2 values, first is ground carrier capacity, second is air carrier
 */
function getdronecarriercapacity($ai, $dronestr) {
    $capacity = array(0, 0);

    $darr = decodedronelist($dronestr, false);
    foreach ($darr as $dtype => $levelarray) {
        if (($dtype != DRONE_TRANS_GND) && ($dtype != DRONE_TRANS_AIR)) {
            continue; // ignore all but carriers
        }
        foreach ($levelarray as $quantity) {
            if ($quantity > 0) {
                $stats = explode(";", getrecipestats(RECIPE_TYPE_DRONE, $dtype, 1));
                if ($dtype == DRONE_TRANS_GND) {
                    $capacity[0] += ($stats[0] * $quantity);
                } else {
                    $capacity[1] += ($stats[0] * $quantity);
                }
            }
        }
    }
    // increase drone capacity based on tech level
    $mult = gettechmult($ai, "", TECH_DRONE_CAP);
    $capacity[0] = round($capacity[0] * $mult);
    $capacity[1] = round($capacity[1] * $mult);
    return $capacity;
}

/*
 * getdronecount - gets count of drones of a specific type
 *  or all drones if "" is supplied
 */
function getdronecount($dronestr, $type) {
    $dcount = 0;

    $darr = decodedronelist($dronestr, false);
    foreach ($darr as $dtype => $levelarray) {
        if (($type == "") || ($dtype == $type)) {
            foreach ($levelarray as $quantity) {
                $dcount += $quantity;
            }
        }
    }
    return $dcount;
}

/*
 * getdronecapacity - gets sum of all capacities in list
 */
function getdronecapacity($ai, $dronestr) {
    $capacity = 0;
    $darr = decodedronelist($dronestr, false);
    foreach ($darr as $dtype => $levelarray) {
        if (($dtype == DRONE_TRANS_GND) || ($dtype == DRONE_TRANS_AIR)) {
            continue; // special which don't add to payload capacity
        }
        foreach ($levelarray as $quantity) {
            if ($quantity > 0) {
                $stats = explode(";", getrecipestats(RECIPE_TYPE_DRONE, $dtype, 1));
                $capacity += ($stats[0] * $quantity);
            }
        }
    }
    // increase drone capacity based on tech level
    $capacity = $capacity * gettechmult($ai, "", TECH_DRONE_CAP);
    return floor($capacity);
}


/*
 * getdronefuel -
 *  gets units of fuel need to go distance for set of drones
 *  fuel use is km/unit with a min of 1 unit
 */
function getdronefuel($ai, $dronestr, $distance) {
    $fuel = 0;

    $darr = decodedronelist($dronestr, false);
    foreach ($darr as $dtype => $levelarray) {
        foreach ($levelarray as $level=>$quantity) {
            if ($quantity > 0) {
                $stats = explode(";", getrecipestats(RECIPE_TYPE_DRONE, $dtype, $level));
                if (count($stats) > 3) {
                    // mult by 2 for round trip
                    $fuel +=  (2 * $quantity * $distance) / $stats[3];
                }
            }
        }
    }
    // reduce fuel use by tech level
    $fuel = floor($fuel / gettechmult($ai, "", TECH_DRONE_FUEL));
    if ($fuel < 1) {
        $fuel = 1;
    }

    return ($fuel);
}

/*
 * getdronedefense -
 *  gets defense amount for set of drones
 */
function getdronedefense($ai, $dronestr, $daitactics) {
    $defamt = 0;

    $darr = decodedronelist($dronestr, false);
    foreach ($darr as $dtype => $levelarray) {
        foreach ($levelarray as $level=>$quantity) {
            if ($quantity > 0) {
                $stats = explode(";", getrecipestats(RECIPE_TYPE_DRONE, $dtype, $level));
                if (count($stats) > 1) {
                    $defarr = explode(",", $stats[1]);
                    foreach ($defarr as $def) {
                        $defs = explode(":", $def);
                        $defamt += $defs[1] * $quantity;
                    }
                }
            }
        }
    }
    $mult = gettechmult($ai, "", TECH_DRONE_TACTIC) + ($daitactics * DRUDGEAI_STAT_MULT);
    $defamt = floor($defamt * $mult);

    return ($defamt);
}

/*
 * getdroneoffense -
 *  gets offense stats for set of drones
 *  return format "range:amount"
 */
function getdroneoffense($ai, $dronestr, $daitactics) {
    $offstr = "";
    $offrng = 0;
    $offamt = 0;

    $darr = decodedronelist($dronestr, false);
    foreach ($darr as $dtype => $levelarray) {
        foreach ($levelarray as $level=>$quantity) {
            if ($quantity > 0) {
                $stats = explode(";", getrecipestats(RECIPE_TYPE_DRONE, $dtype, $level));
                if (count($stats) > 2) {
                    $offarr = explode(",", $stats[2]);
                    foreach ($offarr as $off) {
                        $offs = explode(":", $off);
                        if (($offs[1] < $offrng) || ($offrng == 0)) {
                            $offrng = $offs[1];
                        }
                        $offamt += $offs[2] * $quantity; // amt
                    }
                }
            }
        }
    }
    // increase defense range and amount as per TECH_DRONE_TACTIC
    $mult = gettechmult($ai, "", TECH_DRONE_TACTIC) + ($daitactics * DRUDGEAI_STAT_MULT);
    $offrng = floor($offrng * $mult);
    $offamt = floor($offamt * $mult);
    $offstr .= "$offrng:$offamt";

    return ($offstr);
}

/*
 * getdronequantity - gets a drone quantity for a given type and level
 *      acceptable is given level or higher. using 0 for level returns all of given type
 *      dstr is the input string of drones from base record
 *      if type is -1 then counts all drones that meet level criteria
 *  returns count of drones matching criteria
 */
function getdronequantity($dstr, $type, $level) {
    $dronecount = 0;

    $darr = decodedronelist($dstr, false);
    foreach ($darr as $dt => $levelarray) {
        foreach ($levelarray as $dl => $dq) {
            if ((($type == -1) || ($dt == $type)) && ($level <= $dl)) {
                $dronecount += $dq;
            }
        }
    }
    return $dronecount;
}


/*
 * removedronequantity - removes a quantity of drones for a given type and level
 *      acceptable is given level or higher;
 *      if level is 0 then don't care about level and will remove lowest level first
 *      dstr is the input string of drones from base record
 *      rstr is list of drones to remove in bases record format
 *  returns array of two strings.
 *      first is input string less removed drones
 *      second is list of drones removed
 *      third is list of drones not removed
 */
function removedronequantity($dstr, $rstr) {
    $resultstr = array($dstr, "", "");
    $dremarr = null;

    $darr = decodedronelist($dstr, false);
    $darr2 = $darr; // make copy, scan one, modify other
    $rarr = decodedronelist($rstr, false);
    $rarr2 = $rarr; // make copy, scan one, modify other
    foreach ($rarr as $drt => $rlevelarray) {
        foreach ($rlevelarray as $drl => $drq) {
            $level = $drl;
            $quantity = $drq;
            while (($level <= DRONE_MAX_LEVEL) && ($quantity > 0)) {
                foreach ($darr as $dt => $levelarray) {
                    foreach ($levelarray as $dl => $dq) {
                        if (($dt == $drt) && ($level == $dl)) {
                            // found matching type and level
                            if ($quantity >= $dq) {
                                $darr2[$dt][$dl] = 0;
                                $quantity -= $dq;
                                $dremarr[$dt][$dl] = $dq;
                                $rarr2[$drt][$drl] -= $dq;
                            } else {
                                $darr2[$dt][$dl] = (int) ($dq - $quantity);
                                $dremarr[$dt][$dl] = $quantity;
                                $quantity = 0;
                                $rarr2[$drt][$drl] = 0;
                            }
                        }
                    } // foreach ($levelarray as $dl => $dq)
                } // foreach ($darr as $dt => $levelarray)
                $level++; // scan next higher level
            } //  while (($level <= DRONE_MAX_LEVEL) && ($quantity > 0))
        } // foreach ($rlevelarray as $drl => $drq)
    } // foreach ($rarr as $drt => $rlevelarray)

    $resultstr[0] = encodedronelist($darr2);
    if ($dremarr) {
        $resultstr[1] = encodedronelist($dremarr);
    }
    $resultstr[2] = encodedronelist($rarr2);
    return $resultstr;
}


/*
 * droneaction - processes commands from client for action on drones
 *      $loc is world or base source location in format "b,x,y"
 *      $slot is slot in base in which this action is being performed
 *      $drones is singled set of drones to operate on in format "type:level:quantity"
 */
function droneaction($ai, $operation, $loc, $drones) {
    $parts = explode(":", $drones);
    if (count($parts) > 2) {
        switch ($operation) {
            case "dronerecycle":
            case "dronerepair":
            case "dronescrap":
                if ($parts[2] > 0) {
                    $parts[2] = -$parts[2]; // must be negative for these
                }
            case "droneassy":
                dodrones($ai, $operation, $loc, $parts[0], $parts[2]);
                break;
            default:
                break;
        }
    }
}



/*
 * getdronequeueinfo
 *  returns array of two values
 *      number of assembly jobs in progress
 *      assembly queuesize
 *
 * note, assembly is different from other queues as it is same as assembly
 *  level and not half level
 */
function getdronequeueinfo($ai, $baserow) {
    // get how many sets in progress
    $numinprogress = checktimedentry(TIMER_DRONE_ASSY, $ai, $baserow["location"], "", "");
    // queuesize is same as assembly level
    $assylevel = getmodulelevel(MODULE_ASSEMBLY, $baserow["infra"]);

    return array($numinprogress, $assylevel);
}


/*
 * getdronelines -
 *  return an array of lines for drone assembly
 *      format is DD|type|quantity|targ level|flag|fuel|metal|mineral|xtal|time|comp1;...;compn|preq1;...;preqn|max|
 *      first entry is queue info second contains all strings for drones
*/
function getdronelines($ai, &$baserow) {
    $ret = array("");
    $retidx = 0;
    $resources = $baserow["res_store"];
    $components = $baserow["components"];
    $drones = $baserow["drones"];
    $dronelimit = $baserow["drone_limit"];
    $dronecount = $baserow["dronecount"];

    // get how many sets in progress and queue size
    $queue = getdronequeueinfo($ai, $baserow);
    if ($queue[0] >= $queue[1]) {
        $busy = true;
        $flag = 0;
    } else {
        $busy = false;
        $flag = 1;
    }

    // print summary of queue data
    $ret[$retidx] = "DQ|$queue[1]|$queue[0]|$dronelimit|$dronecount";
    $retidx++;

    $droneorderarr = explode(",", getrecipelist(RECIPE_TYPE_DRONE));

    foreach ($droneorderarr as $dtype) {
        if ($busy) {
            $flag = -1; // don't allow construct or remove
        } else {
            $flag = 1;
        }
        // get quantity already have
        $quantity = getdronequantity($drones, $dtype, 0);

        $ret[$retidx] = getrecipeline($ai, RECIPE_TYPE_DRONE, $dtype, 0, "DD", $quantity, $flag, $baserow, TECH_DRONE_ASSY, DRUDGEAI_ROLE_CONST);

        // calc max that can be constructed based on materials in base
        $cmax = calcmaxpossible($resources, $components, $drones, $ret[$retidx]);
        $ret[$retidx] .= "|$cmax";

        $retidx++;
    } // foreach ($dronelist as $dtype)
    return $ret;
}


/*
 * printdronestatlines -
 *   prints list of DPS lines for drone stats
 *      format "DPS|type|capacity|def1,...,defn|off1,...,offn|fuel use|speed"
 */
function printdronestatlines($ai, $refresh) {
    global $drone_defense_name; global $drone_offense_name;
    $list = "";
    $listidx = 0;
    $techstr = $_SESSION["techs"];
    $dtacticmult = gettechmult($ai, $techstr, TECH_DRONE_TACTIC);
    $dfuelmult = gettechmult($ai, $techstr, TECH_DRONE_FUEL);
    $dmovemult = gettechmult($ai, $techstr, TECH_DRONE_MOVE);
    $dcapmult = gettechmult($ai, $techstr, TECH_DRONE_CAP);

    $droneorderarr = explode(",", getrecipelist(RECIPE_TYPE_DRONE));

    foreach ($droneorderarr as $dtype) {
        $statarr = explode(";", getrecipestats(RECIPE_TYPE_DRONE, $dtype, 1));
        if (count($statarr) > 4) {
            $defarr = explode(",", $statarr[1]);
            $defstr = "";
            foreach ($defarr as $def) {
                $defs = explode(":", $def);
                if ($def[0] == "0") {
                    $defstr .= $drone_defense_name[$defs[0]] . ":,";
                } else {
                    // increase defense amount as per TECH_DRONE_TACTIC
                    $defamt = floor($defs[1] * $dtacticmult);
                    $defstr .= $drone_defense_name[$defs[0]] . ":" . $defamt . ",";
                }
            }
            $offarr = explode(",", $statarr[2]);
            $offstr = "";
            foreach ($offarr as $off) {
                $offs = explode(":", $off);
                if ($offs[0] == "0") {
                    $offstr .= $drone_offense_name[$offs[0]] . "::,";
                } else {
                    // increase offense range as per TECH_DRONE_TACTIC
                    $offrange = floor ($offs[1] * $dtacticmult);
                    // increase offense amount as per TECH_DRONE_TACTIC
                    $offamt = floor($offs[2] * $dtacticmult);
                    $offstr .= $drone_offense_name[$offs[0]] . ":" . $offrange . ":". $offamt . ",";
                }
            }
            // increase capacity as per TECH_DRONE_CAP
            $dcap = floor($statarr[0] * $dcapmult);
            // decrease fuel use as per TECH_DRONE_FUEL
            $dfuel = floor($statarr[3] / $dfuelmult);
            // increase movement rates as per TECH_DRONE_MOVE
            $dmove = floor($statarr[4] * $dmovemult);
            $list[$listidx] = "DPS|$dtype|$dcap|$defstr|$offstr|$dfuel|$dmove";
            $listidx++;
        }
    } // foreach ($dronelist as $dtype)
    printdeltas("dronestats", 0, $refresh, $list);
}

/*
 * dodrones - verifies pregs, resources, etc for a set of drones and queues
 *      them up. Removes resources from base
*/
function dodrones($ai, $op, $location, $dtype, $quantity) {
    global $mysqlidb;
    global $drone_name; global $res_name; global $timer_name;

    $location = convertdloctoloc($location);

    $rmsg = "";
    $compused = "";
    $needdronestr = "";
    $resstr = "";
    $query = "select * from bases where controller='$ai' and location='$location';";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) { // got base record
        $row = $result->fetch_assoc();
        $modules = $row["infra"];
        $dronelimit = $row["drone_limit"];
        $havedrones = $row["drones"];
        $damageddrones = $row["damaged_drones"];
        $haveres = $row["res_store"];
        $components = $row["components"];
        $dlocation = $row["dlocation"];

        $numinprogress = 0;
        if ($op == "dronerecycle") {
            $tmsg = "Recycle";
            $tqevent = TIMER_DRONE_RECY;
            $tmodule = MODULE_RECYCLE;
            $tech = TECH_RECYCLE;
            // get scrap recycle sets too
            $numinprogress = checktimedentry(TIMER_SCRAP_RECYCLE, $ai, $location, "", "");
        } else if ($op == "droneassy") {
            $tmsg = "Assemble";
            $tqevent = TIMER_DRONE_ASSY;
            $tmodule = MODULE_ASSEMBLY;
            $tech = TECH_DRONE_ASSY;
        } else if ($op == "dronerepair") {
            $tmsg = "Repair";
            $tqevent = TIMER_DRONE_REPAIR;
            $tmodule = MODULE_REPAIR;
            $tech = TECH_DRONE_REP;
            $havedrones = $damageddrones;
        } else if ($op == "dronescrap") {
            $tmsg = "Scrap";
            $tqevent = -1;
            $tmodule = MODULE_REPAIR;
            $tech = TECH_DRONE_REP;
            $havedrones = $damageddrones;
        } else {
            return; // unrecognized - ignore
        }
        $absamt = abs($quantity);
        $fail = "Failed to $tmsg ". number_format($absamt) . " {$drone_name[$dtype]} in base at $dlocation,";

        $recipe = getrecipe($ai, RECIPE_TYPE_DRONE, $dtype, 0, $row, $tech, DRUDGEAI_ROLE_BASE);
        if ($recipe == null) {
            $rmsg = "$fail unable to determine how to perform $tmsg operation";
        } else {
            $numinprogress += checktimedentry($tqevent, $ai, $location, "", "");
            $assylevel = getmodulelevel($tmodule, $modules);
            if ($tmodule == MODULE_ASSEMBLY) {
                $qsize = $assylevel;
            } else {
                $qsize = floor(($assylevel + 1) / 2);
            }
            // scraping damaged drones is instant so doesn't use queue
            if (($numinprogress >= $qsize) && ($op != "dronescrap")) {
                $rmsg = "$fail queue is at max capacity";
            } else { // queue has room
                if ($op == "dronerecycle") {
                    // recycle uses only 1/4 time
                    $recipe["resarr"][MAX_NORMAL_RES] /= 4;
                } else if ($op == "dronerepair") {
                    // repair uses only 1/8 reourses
                    for ($idx = 0; $idx < MAX_NORMAL_RES; $idx++) {
                        $recipe["resarr"][$idx] /= 8;
                    }
                    // repair uses only 1/4 time
                    $recipe["resarr"][MAX_NORMAL_RES] /= 4;
                }

                $needres = array(MAX_NORMAL_RES+1);
                for ($idx = 0; $idx <= MAX_NORMAL_RES; $idx++) {
                    $needres[$idx] = round($recipe["resarr"][$idx] * $absamt);
                    if ($idx < MAX_NORMAL_RES) {
                        $resstr .= $needres[$idx] . "/";
                    } else {
                        $duration = $needres[$idx];
                    }
                }
                $newresstr = $haveres; // default to unchanged
                if ($quantity < 0) {
                    // recycle, repair or scrap
                    // check if sufficient drones of this type exist to remove
                    $newdronestrs = removedronequantity($havedrones, "$dtype:1:$absamt");
                    if ($newdronestrs[2] == "") {
                        // enough to remove
                        if ($op == "dronescrap") {
                            // convert drones to scrap
                            $newres = explode("/", $haveres);
                            $newres[MAX_NORMAL_RES] = floor($newres[MAX_NORMAL_RES]) + ($recipe["scrap"] * $absamt);
                            $newresstr = implode("/", $newres);
                        }
                    } else {
                        $rmsg = "$fail insufficient drones of this type";
                    }
                } else if ($quantity > 0) {
                    // check if sufficient room for new drones
                    $addstore = getdronequantity("$dtype:1:$quantity", -1, 0);
                    if ($addstore > 0) {
                        $hasstore = getdronequantity($havedrones, -1, 0);
                        if ($dronelimit < ($hasstore + $addstore)) {
                            $rmsg = "$fail insufficient drone storage capacity";
                        }
                    }
                    if ($rmsg == "") {
                        // check if prereqs met
                        if ($recipe["notmet"] != "") {
                            $preqarr = explode(";", $recipe["notmet"]);
                            foreach ($preqarr as $preq) {
                                if ($rmsg != "") {
                                    $rmsg .= ", ";
                                }
                                $rmsg .= formatpreq($preq);
                            }
                            $rmsg = "$fail requirement not met: $rmsg";
                        }
                    }
                    if ($rmsg == "") {
                        // check if sufficient resources to construct these drones
                        $wasres = explode("/", $haveres);
                        for ($idx = 0; $idx < MAX_NORMAL_RES; $idx++) {
                            $newres[$idx] = (int) ($wasres[$idx] - $needres[$idx]);
                            if ($newres[$idx] < 0) {
                                $tmpstr = number_format($needres[$idx]) . " " . $res_name[$idx];
                                if ($rmsg == "") {
                                    $rmsg = "$fail insufficient resources: $tmpstr";
                                } else {
                                    $rmsg .= ", $tmpstr";
                                }
                            }
                        }
                        $newres[MAX_NORMAL_RES] = $wasres[MAX_NORMAL_RES]; // just copy scrap value
                        $newresstr = implode("/", $newres);
                    }
                    if ($rmsg == "") {
                        // components
                        $comparr = removecomponentquantity($components, $recipe["comps"], 1, $quantity);
                        if ($comparr[1] != "") {
                            $complist = explode(";", $comparr[1]);
                            foreach ($complist as $comp) {
                                if ($rmsg != "") {
                                    $rmsg .= ", ";
                                }
                                $rmsg .= formatcomponent($comp, 1, $quantity);
                            }
                            $rmsg = "$fail insufficient components: $rmsg";
                        }
                        $newcompstr = $comparr[0];
                        $needdronestr = $comparr[2];
                        $compused .= $comparr[3] . ";";
                        if (($rmsg == "") && ($needdronestr != "")) {
                            // check drones
                           // subtract drones from base stores - if not enough then third return string is not empty
                            $newdronestrs = removedronequantity($havedrones, $needdronestr);
                            if ($newdronestrs[2] != "") {
                                $rmsg = "$fail insufficient drone chassis of appropriate type";
                            }
                        }
                    }
                } // if ($quantity > 0)

                if ($rmsg == "") {
                    if (($quantity > 0) && ($newresstr != "")) {
                        if (isset($newdronestrs) && (count($newdronestrs)) > 1) {
                            // update base record resources, components and drones
                            $query = "update bases set res_store='$newresstr',drones='$newdronestrs[0]',components='$newcompstr' where controller='$ai' and location='$location';";
                        } else {
                            // update base record resources and components
                            $query = "update bases set res_store='$newresstr',components='$newcompstr' where controller='$ai' and location='$location';";
                        }
                    } else if (($quantity < 0) && (count($newdronestrs) > 1)) {
                        if (($op == "dronerepair") || ($op == "dronescrap")) {
                            // update base record resources and damaged_drones
                            $query = "update bases set res_store='$newresstr',damaged_drones='$newdronestrs[0]' where controller='$ai' and location='$location';";
                            // invert quantity now so results have a positive value
                            $quantity = -$quantity;
                        } else {
                            // update base record drones
                            $query = "update bases set drones='$newdronestrs[0]' where controller='$ai' and location='$location';";
                        }
                    }
                    $result = $mysqlidb->query($query);
                    if (!$result) {
                        $rmsg = "$fail unable to update base record";
                    }
                    $qstr = number_format($absamt);
                    if ($op == "dronescrap") {
                        // no timed entry for scrapping drones
                        $rmsg = "Scrapped $qstr {$drone_name[$dtype]} in base at $dlocation";
                    } else if ($rmsg == "") {
                        $results = "$qstr {$drone_name[$dtype]};$dtype:1:$quantity";
                        createtimedentry($duration, $tqevent, $ai, "$location:$dlocation", "$location:$dlocation", "",
                                            $recipe["dai"], $needdronestr, $resstr, $compused, "", $results);
                        $rmsg = "{$timer_name[$tqevent]}: $qstr {$drone_name[$dtype]} in base at $dlocation etc " . formatduration($duration);
                    }
                } // if (($rmsg == "") && ($tqpayload != ""))
            } // queue has room
        }
    } // got base record
    if ($rmsg != "") {
        postreport ($ai, 0, $rmsg);
    }
}

/*
 * scaledrones -
 *  adjusts quantities in a drone string to reflect quantity
 *  other than quantities, the string is unchanged
 */
function scaledrones($dronestr, $quantity) {
    if ($quantity < 2) {
        // if level is 1 or less then do nothing
        return $dronestr;
    }
    $darr = explode(";", $dronestr);
    for ($idx = count($darr)-1; $idx >= 0; $idx--) {
        $drone = explode(":", $darr[$idx]);
        if (count($drone) > 2) {
            $off = 0;
            if ($drone[0] == "D") {
                $off = 1;
            }
            $drone[$off+2] = round($drone[$off+2] * $quantity);
        }
        $darr[$idx] = implode(":", $drone);
    }
    return implode(";", $darr);
}

/*
 * simplifydrones -
 *  adjusts drone string format to "type:level:quantity;..."
 */
function simplifydrones($dronestr) {
    $darr = explode(";", $dronestr);
    for ($idx = count($darr)-1; $idx >= 0; $idx--) {
        $drone = explode(":", $darr[$idx]);
        if (count($drone) > 3) {
            $darr[$idx] = "{$drone[1]}:{$drone[2]}:{$drone[3]}";
        }
    }
    return implode(";", $darr);
}


?>
