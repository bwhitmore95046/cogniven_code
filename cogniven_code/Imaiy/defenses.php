<?php
/*
 * Functions to deal with defenses
 * Author: Chris Bryant
 */


/*
 * printdefenses - returns an array of lines for each defense type specified
 *  format of defstr is "type:quantity;enable;type:quantity:enable;..."
 *  first entry is queue info
 */
function getdefenselines($ai, &$base) {
    $ret = array(1);
    $retidx = 0;

    $location = $base["location"];
    $modules = $base["infra"];
    $resources = $base["res_store"];
    $components = $base["components"];
    $drones = $base["drones"];
    $defenses = $base["defenses"];
    if ($defenses == "") {
        $defenses = getrecipestats(RECIPE_TYPE_ROGUE, 1, 0);
    }
    $deflist = explode(";", $defenses);
    $maxdefs = getdefensecapacity($modules);
    // count total number of defenses
    $totaldefs = 0;
    foreach ($deflist as $def) {
        $defarr = explode(":", $def);
        if (count($defarr) > 1) {
            $totaldefs += $defarr[1];
        }
    }
    $availdefs = max($maxdefs - $totaldefs, 0);

    // get quantities under construction and subtract from availdefs
    $underconst = 0;
    $slotlist = gettimedentryslots(TIMER_CON_DEFENSE, $ai, $location, "");
    $parts = explode(":", $slotlist);
    foreach ($parts as $part) {
        $underconst += floor($part);
    }
    $availdefs -= $underconst;
    // get quantities being removed and add to availdefs
    $underrem = 0;
    $slotlist = gettimedentryslots(TIMER_REM_DEFENSE, $ai, $location, "");
    $parts = explode(":", $slotlist);
    foreach ($parts as $part) {
        $underrem += floor($part);
    }
    $availdefs += $underrem;
    // get how many defense sets in progress
    $numinprogress = checktimedentry(TIMER_REM_DEFENSE, $ai, $location, "", "");
    $numinprogress += checktimedentry(TIMER_CON_DEFENSE, $ai, $location, "", "");

    // if # sets in progress >= perimeter module level then set flag = 0 for all
    $perimlevel = getmodulelevel(MODULE_PERIMETER, $modules);
    $qsize = floor(($perimlevel + 1) / 2);
    // print summary of queue data
    $ret[$retidx] = "BQ|$qsize|$numinprogress|$maxdefs|$availdefs|$underconst|$underrem";
    $retidx++;

    foreach ($deflist as $def) {
        $defarr = explode(":", $def);
        if (count($defarr) > 2) {
            $ret[$retidx] = getrecipeline($ai, RECIPE_TYPE_DEFENSE, $defarr[0], 0, "BD", $defarr[1], $defarr[2], $base, TECH_CONST, DRUDGEAI_ROLE_CONST);

            // calc max that can be constructed based on materials in base
            $cmax = calcmaxpossible($resources, $components, $drones, $ret[$retidx]);
            if ($cmax > $availdefs) {
                $cmax = $availdefs; // limit to max mounts available
            }
            if ($cmax < floor($defarr[1])) {
                $cmax = $defarr[1]; // must be able to remove installed quantity
            }

            $ret[$retidx] .= "|$cmax";
            $retidx++;
        }
    }
    return $ret;
}


/*
 * togglebasedefense - sets specified base defense as active or inactive
 *      as specified by the enable flag.
 */
function togglebasedefense($ai, $bloc, $type, $enable) {
    global $mysqlidb;
    global $def_name;
    if (array_key_exists($type, $def_name)) {
        $query = "select defenses from bases where controller='$ai' and location='$bloc';";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            $row = $result->fetch_row();
            $defenses = $row[0];
            if ($defenses == "") {
                $defenses = getrecipestats(RECIPE_TYPE_ROGUE, 1, 0);
            }
            $deflist = explode(";", $defenses);

            $defidx = 0;
            $doupdate = false;
            foreach ($deflist as $def) {
                $defarr = explode(":", $def);
                if ($defarr[0] == $type) {
                    if ($enable) {
                        if ($defarr[2] == "0") {
                            $defarr[2] = "1";
                            $doupdate = true;
                        }
                    } else {
                        if ($defarr[2] == "1") {
                            $defarr[2] = "0";
                            $doupdate = true;
                        }
                    }
                    $deflist[$defidx] = implode(":", $defarr);
                    break;
                }
                $defidx++;
            }

            if ($doupdate) {
                $newdefs = implode(";", $deflist);
                $query = "update bases set defenses='$newdefs' where controller='$ai' and location='$bloc';";
                $result = $mysqlidb->query($query);
                if ($result) {
                    postreport ($ai, 0, "Base defense {$def_name[$type]} at $bloc " . ($enable ? "enabled" : "disabled"));
                }
            }
        }
    }
}


/*
 * changebasedefense - handles construction or removal of defense types
 *      if quantity > 0 begins construction of specified type of defense
 *      if quantity < 0 begins removal of specified type of defense
 */
function changebasedefense($ai, $bloc, $type, $quantity) {
    global $mysqlidb;
    global $res_name; global $timer_name; global $def_name;
    $rmsg = "";
    $newresstr = "";

    $bloc = convertdloctoloc($bloc);

    if (array_key_exists($type, $def_name)) {
        if ($quantity < 0) {
            $operation = "remove";
        } else if ($quantity > 0) {
            $operation = "install";
        }
        $absamt = abs($quantity);

        $query = "select dlocation,level,infra,drudgeais,defenses,res_store,components,drones from bases where controller='$ai' and location='$bloc'";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) { // got base record
            $row = $result->fetch_assoc();
            $modules = $row["infra"];
            $defenses = $row["defenses"];
            if ($defenses == "") {
                $defenses = getrecipestats(RECIPE_TYPE_ROGUE, 1, 0);
            }
            $res_store = $row["res_store"];
            $components = $row["components"];
            $drones = $row["drones"];
            $dlocation = $row["dlocation"];
            $fail = "Failed to $operation " . number_format($absamt) . " {$def_name[$type]} in base at $dlocation, ";

            // get how many defense sets in progress
            $numinprogress = checktimedentry(TIMER_REM_DEFENSE, $ai, $bloc, "", "");
            $numinprogress += checktimedentry(TIMER_CON_DEFENSE, $ai, $bloc, "", "");

            // if # sets in progress >= perimeter module level then set flag = 0 for all
            $perimlevel = getmodulelevel(MODULE_PERIMETER, $modules);
            $max = 1024 * $perimlevel * $perimlevel;
            $qsize = floor(($perimlevel + 1) / 2);
            if ($numinprogress >= $qsize) {
                $rmsg = "$fail queue is at max capacity";
            } else { // queue has room
                $deflist = explode(";", $defenses);
                $totalcur = 0;
                foreach ($deflist as $def) {
                    $defarr = explode(":", $def);
                    if (count($defarr) > 1) {
                        $totalcur += $defarr[1];
                    }
                }
                if (($totalcur + $quantity) > $max) {
                    $rmsg = "$fail insufficient mounts available";
                } else {
                    $recipe = getrecipe($ai, RECIPE_TYPE_DEFENSE, $type, 0, $row, TECH_CONST, DRUDGEAI_ROLE_CONST);
                    if ($recipe == null) {
                        $rmsg = "$fail unable to determine how to perform operation";
                    }
                }
                if ($rmsg == "") {
                    // enough mounts
                    foreach ($deflist as $def) {
                        $defarr = explode(":", $def);
                        if ($defarr[0] == $type) {
                            $resstr = "";
                            $needres = array(MAX_NORMAL_RES+1);
                            for ($idx = 0; $idx <= MAX_NORMAL_RES; $idx++) {
                                $needres[$idx] = round($recipe["resarr"][$idx] * $absamt);
                                if ($idx < MAX_NORMAL_RES) {
                                    $resstr .= $needres[$idx] . "/";
                                } else {
                                    $duration = $needres[$idx];
                                }
                            }

                            if ($quantity < 0) {
                                // check if sufficient defenses of this type exist to remove
                                if ($defarr[1] >= $quantity) {
                                    // enough to remove
                                    $tqevent = TIMER_REM_DEFENSE;
                                    $duration /= 4; // only takes 1/4 time to remove
                                } else {
                                    $rmsg = "$fail insufficient defenses of this type";
                                }
                                $newresstr = $res_store;
                            } else if ($quantity > 0) {
                                // prereqs met?
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
                                }

                                if ($rmsg == "") {
                                    // subtract resources
                                    $haveres = explode("/", $res_store);
                                    $newres = array(MAX_NORMAL_RES+1);
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
                                }
                                if ($rmsg == "") {
                                    $tqevent = TIMER_CON_DEFENSE;
                                }
                            } // if ($quantity > 0)
                            if ($rmsg == "") {
                                // subtract drones from base stores - if not enough then third return string is not empty
                                $dronestrs = removedronequantity($drones, $recipe["drones"]);
                                if ($dronestrs[2] != "") {
                                    $rmsg = "$fail insufficient drones";
                                }
                            }

                            if (($rmsg == "") && ($quantity != 0)) {
                                if ($quantity > 0) {
                                    // update base record removing resources
                                    $query = "update bases set res_store='$newresstr',drones='$dronestrs[0]',components='$newcompstr' where controller='$ai' and location='$bloc';";
                                    $result = $mysqlidb->query($query);
                                    if (!$result) {
                                        $rmsg = "$fail unable to update base record";
                                    }
                                } else {
                                    $query = "update bases set drones='$dronestrs[0]' where controller='$ai' and location='$bloc';";
                                    $result = $mysqlidb->query($query);
                                    if (!$result) {
                                        $rmsg = "$fail unable to update base record";
                                    }
                                }
                                if ($rmsg == "") {
                                    $qstr = number_format($absamt);
                                    $results = "$qstr {$def_name[$type]};$type:$quantity";
                                    createtimedentry($duration, $tqevent, $ai, "$bloc:$dlocation", "$bloc:$dlocation", $absamt,
                                                        $recipe["dai"], $dronestrs[1], $resstr, $recipe['comps'], "", $results);
                                    $rmsg = "{$timer_name[$tqevent]}: $qstr {$def_name[$type]} in base at $dlocation etc " . formatduration($duration);
                                }
                            } // if ($rmsg == "")
                            break;
                        } // if ($defarr[0] == $type)
                    } // foreach ($deflist as $def)
                } // enough mounts
            } // queue has room
        } // got base record
    } // if (($rmsg == "") && (array_key_exists($type, $def_name)))
    if ($rmsg != "") {
        postreport ($ai, 0, $rmsg);
    }
}




?>
