<?php
/*
 * Functions to deal with components used to make things
 * Author: Chris Bryant
 */

/*
 * getfullcomponentlist
 *  returns string list of all component types with quantities
 *  from provided in strings
 *
 * $sompstr1 = component string in bases record format
 * $compstr2 = component string in formation format
 *
 * returns string in format "C:type:quantity from str2:quantity from str1;..."
 */
function getfullcomponentlist($compstr1, $compstr2) {
    $carr1 = null;
    $comps = explode(";", $compstr1);
    foreach($comps as $comp) {
        $cparts = explode(":", $comp);
        if (count($cparts) > 1) {
            $carr1[$cparts[0]] = $cparts[1];
        }
    }

    $carr2 = null;
    $comps = explode(";", $compstr2);
    foreach($comps as $comp) {
        $cparts = explode(":", $comp);
        if ((count($cparts) > 2) && ($cparts[0] == "C")) {
            $carr2[$cparts[1]] = $cparts[2];
        }
    }

    $comporderarr = explode(",", getrecipelist(RECIPE_TYPE_COMP));

    $cstr = "";
    foreach ($comporderarr as $ctype) {
        $cstr .= "C:$ctype:";
        if ($carr2 && isset($carr2[$ctype])) {
            $cstr .= "{$carr2[$ctype]}:";
        } else {
            $cstr .= "0:";
        }
        if ($carr1 && isset($carr1[$ctype])) {
            $cstr .= "{$carr1[$ctype]};";
        } else {
            $cstr .= "0;";
        }
    }

    return ($cstr);
}

/*
 * scalecomponents -
 *  adjusts quantities in a component string to reflect level
 *  other than quantities, the string is unchanged
 */
function scalecomponents($compstr, $level) {
    if ($level < 2) {
        // if level is 1 or less then do nothing
        return $compstr;
    }
    $resultstr = "";
    $comparr = explode(";", $compstr);
    foreach ($comparr as $comp) {
        $tparr = explode(":", $comp);
        $newstr = "";
        switch ($tparr[0]) {
            case "D": // Drones - not really component
                if (count($tparr) > 3) {
                    $tparr[3] = floor($tparr[3]) * pow(2,$level-1);
                    $newstr = implode(":", $tparr);
                }
                break;
            case "COFR":
            case "DANO":
            case "HEPR":
            case "CRPRCH":
            case "POPABU":
            case "SUCORO":
                // Master AI Enhancements - not really component
                //  quantity is unchanged
                $newstr = $comp;
                break;
            case "DC":
                // Drudge AI Core - not really component
                //  quantity is unchanged
                $newstr = $comp;
                break;
            default:
                if (count($tparr) > 1) {
                    $tparr[1] = floor($tparr[1]) * pow(2,$level-1);
                    $newstr = implode(":", $tparr);
                }
                break;
        }
        if ($newstr != "") {
            if ($resultstr != "") {
                $resultstr .= ";";
            }
            $resultstr .= $newstr;
        }
    }
    return $resultstr;
}

/*
 * formatcomponent - translates component string into plain text description
 *  compstr format is type:param1:...:paramn - number of params are 1 or more
 *  level is used as (2^(level-1)) multiplier for quantities
 *  amount is used as a straight multiplier for quantities
 */
function formatcomponent($compstr, $level, $amount) {
    global $drone_name;
    global $comp_name;
    $plainstr = "";

    if ($compstr != "") {
        if ($level < 1) {
            $level = 1;
        }
        $tparr = explode(":", $compstr);
        switch ($tparr[0]) {
            case "D": // Drones - not really component
                $tparr[3] *= pow(2,$level-1) * $amount;
                $plainstr = number_format($tparr[3]) . " " . $drone_name[$tparr[1]];
                break;
            case "COFR":
            case "DANO":
            case "HEPR":
            case "CRPRCH":
            case "POPABU":
            case "SUCORO":
                // Master AI Enhancements - not really component
                global $item_name;
                $plainstr = number_format($tparr[2]) . " " . $item_name[$tparr[0]];
                if ($tparr[2] != 1) {
                    $plainstr .= "s";
                }
                break;
            case "DC": // Drudge AI Core - not really component
                $plainstr = number_format($tparr[2]) . " Drudge AI core of target level";
                break;
            default:
                $off = 0;
                if ($tparr[0] == "C") {
                    $off = 1;
                }
                $idx = $tparr[$off];
                if (($idx < 0) || ($idx >= COMPONENT_ARRAY_SIZE)) {
                    $idx = 0;
                }
                $tparr[$off+1] *= pow(2,$level-1) * $amount;
                $plainstr = number_format($tparr[$off+1]) . " {$comp_name[$idx]}";
                break;
        }
    }
    return $plainstr;
}


/*
 * getcomponentquantity - returns count of specified component type
 */
function getcomponentquantity($comps, $comptype) {
    global $comp_name;
    if (strlen($comptype) > 3) {
        // is name, translate to type number
        foreach ($comp_name as $cidx=>$cname) {
            if ($cname == $comptype) {
                $comptype = $cidx;
                break;
            }
        }
    }
    $carr = explode(";", $comps);
    foreach ($carr as $ca) {
        $cs = explode(":", $ca);
        $length = count($cs);
        if ($length > 1) {
            if (($cs[0] == "C") && ($cs[1] == $comptype) && ($length > 2)) {
                return $cs[2];
            } else if ($cs[0] == $comptype) {
                return $cs[1];
            }
        }
    }
    return 0;
}


/*
 * hascomponents - determines if component is present in base
 *  compstr format is type:param1:...:paramn - number of params are 1 or more
 *  level is used as (2^(level-1)) multiplier for quantities
 *  amount is used as a straight multiplier for quantities
 *  returns 1 if component is present in base
 *          0 if is missing from base
 */
function hascomponents($ai, $location, $comps, $compstr, $level, $amount) {
    global $mysqlidb;
    $present = 0;
    $tparr = explode(":", $compstr);

    switch ($tparr[0]) {
        case "D": // Drones
            $tparr[3] *= pow(2,$level-1) * $amount;
            $query = "select drones from bases where controller='$ai' and location='$location';";
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0)) {
                $row = $result->fetch_row();
                if (getdronequantity($row[0], $tparr[1], $tparr[2]) >= $tparr[3]) {
                    $present = 1;
                }
            }
            break;
        case "COFR":
        case "DANO":
        case "HEPR":
        case "CRPRCH":
        case "POPABU":
        case "SUCORO":
            $query = "select items from player where name='$ai';";
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0)) {
                $row = $result->fetch_row();
                if (getitemsquantity($row[0], $tparr[0], $tparr[1]) >= $tparr[2]) {
                    $present = 1;
                }
            }
            break;
        case "DC":
            $query = "select items from player where name='$ai';";
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0)) {
                $row = $result->fetch_row();
                if (getitemsquantity($row[0], $tparr[0], $level) > 0) {
                    $present = 1; // DC of target level found
                }
            }
            break;
        default: // everything else
            if (count($tparr) > 1) {
                $tparr[1] *= pow(2,$level-1) * $amount;
                if (getcomponentquantity($comps, $tparr[0]) >= $tparr[1]) {
                    $present = 1;
                }
            }
            break;
    }
    return $present;
}


/*
 * removecomponentquantity - removes a quantity of components
 *      cstr is the input string of components from base record
 *      rstr is list of components to remove
 *          format of both is "type:quantity;type:quatity;..."
 *  level is used as (2^(level-1)) multiplier for quantities
 *  amount is used as a straight multiplier for quantities
 *  returns array of four strings. first is input string less removed components
 *      second is list of components not removed.
 *      third is only set if component specified is actually a drone or fragment
 *      fourth is list of drones removed
 *      on success, second list should be blank.
 */
function removecomponentquantity($cstr, $rstr, $tlevel, $amount) {
    $resultstr = array($cstr, "", "", "");

    if ($cstr == "") { // can't removed anything from nothing
        $resultstr[1] = $rstr;
    } else if ($rstr != "") { // something to remove
        $mult = pow(2,$tlevel-1) * $amount;

        $clist = explode(";", $cstr);
        $rlist = explode(";", $rstr);
        $resultstr[0] = ""; // clear results
        for ($jj = count($rlist)-1; $jj >= 0; $jj--) {
            if ($rlist[$jj] != "") {
                $rc = explode(":", $rlist[$jj]);
                if ($rc[0] == "D") {
                    // really a drone and not component
                    //  format "D:type:level:quantity"
                    if ($rc[3] > 0) {
                        $rc[3] *= $mult;
                        $resultstr[2] .= $rc[1].":".$rc[2].":".$rc[3].";";
                    }
                    $rlist[$jj] = "";
                } else if ($rc[0] == "F") {
                    // really code fragment and not component
                    // format "F:type:quantity"
                    if ($rc[2] > 0) {
                        $resultstr[2] .= $rlist[$jj].";";
                    }
                    $rlist[$jj] = "";
                } else {
                    //  format "type:quantity" or "C:type:quantity"
                    $off = 0;
                    if ($rc[0] == "C") {
                        $off = 1;
                    }
                    if ($rc[$off+1] == 0) { // none to remove
                        $rlist[$jj] = "";
                    } else {
                        $rc[$off+1] *= $mult;
                        for ($ii = 0; $ii < count($clist); $ii++) {
                            $cc = explode(":", $clist[$ii]);
                            if ($cc[0] == $rc[$off]) { // found type match
                                if ($resultstr[3] != "") {
                                    $resultstr[3] .= ";";
                                }
                                if ($cc[1] >= $rc[$off+1]) { // at least enough, remove them
                                    if ($cc[1] == $rc[$off+1]) {
                                        $clist[$ii] = ""; // no more left in base
                                    } else {
                                        $clist[$ii] = $cc[0] . ":" . (int) ($cc[1] - $rc[$off+1]);
                                    }
                                    $rlist[$jj] = "";
                                    $resultstr[3] .= $cc[0] . ":" . $rc[$off+1];
                                } else {
                                    // remove partial amount
                                    $clist[$ii] = "";
                                    $rlist[$ii] = $rc[0] . ":" . (int) ($rc[$off+1] - $cc[1]);
                                    $resultstr[3] .= $cc[0] . ":" . $cc[1];
                                }
                            }
                        } // for ($ii = 0; $ii < count($clist); $ii++)
                    }
                }
            } // if ($rlist[$jj] != "")
        } // for ($jj = 0; $jj < count($rlist); $jj++)
        for ($ii = count($clist)-1; $ii >= 0; $ii--) {
            // recombine first list skipping blank entries
            if ($clist[$ii] != "") {
                if ($resultstr[0] != "") {
                    $resultstr[0] .= ";";
                }
                $resultstr[0] .= $clist[$ii];
            }
        }
        // now recombine components not removed skipping blank entries
        for ($jj = count($rlist)-1; $jj >= 0; $jj--) {
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
 * formatpreq - tranlates preq string into plain text description
 *  NOTE: this must match the formating done in formatprereq function in utils.js
 *
 */
function formatpreq($preqstr) {
    global $mod_name; global $tech_name;
    $plainstr = "";

    $tparr = explode(":", $preqstr);

    switch ($tparr[0]) {
        case "M":
            $mname = $mod_name[$tparr[1]];
            $plainstr = "Level can not exceed level of $mname module";
            break;
        case "m":
            $mname = $mod_name[$tparr[1]];
            $plainstr = "Level can not exceed level of $mname module and $mname module must be at least level $tparr[2]";
            break;
        case "mm":
            $mname = $mod_name[$tparr[1]];
            $plainstr = "$mname module must be at least level $tparr[2]";
            break;
        case "T":
            $plainstr = "Level can not exceed level of " . $tech_name[$tparr[1]] . " skill";
            break;
        case "t":
            $plainstr = $tech_name[$tparr[1]] . " skill must be at least level $tparr[2]";
            break;
        case "BB":
            $plainstr = "Level determines level of Base";
            break;
        case "B":
            $plainstr = "Level can not exceed level of Base";
            break;
        case "bb":
            $plainstr = "Level can not exceed level of Base and Base level must be at least $tparr[1]";
            break;
        case "b":
            $plainstr = "Base level must be at least $tparr[1]";
            break;
        case "P":
            $plainstr = "Level can not exceed $tparr[1] * (Master AI level + 1)";
            break;
        case "DC":
            $plainstr = "Drudge AI core of the target level";
            break;
        default:
            break;
    }
    return $plainstr;
}


/*
 * meetspreq - evals preq condition
 *  type is used to specify module type if checking prereqs for construction
 *      of new module where number of modules of that type are limited
 *  slot is used to restrict module level checks to module in specific slot
 *      a value of -1 indicates should check all slots.
 *  returns 1 if condition met
 *          0 if condition not met
 *          2 if condition not met and should be hidden
 *          3 if cannot be deleveled
 */
function meetspreq( $ai, $preqstr, $targlevel, $baselevel, $modules) {
    $meets = 1;
    $tparr = explode(":", $preqstr);
    switch ($tparr[0]) {
        case "M": // module level
            $modlevel = getmodulelevel($tparr[1], $modules);
            if ($targlevel > $modlevel) {
                $meets = 0;
            }
            break;
        case "m": // module level & mod >= level
            $modlevel = getmodulelevel($tparr[1], $modules);
            if (($modlevel < $tparr[2]) || ($targlevel > $modlevel)) {
                $meets = 0;
            }
            break;
        case "mm": // module level
            $modlevel = getmodulelevel($tparr[1], $modules);
            if ($modlevel < $tparr[2]) {
                $meets = 0;
            }
            break;
        case "T": // tech level >= targlevel
            $tlevel = gettechlevel( $ai, $_SESSION["techs"], $tparr[1]);
            if ($targlevel > $tlevel) {
                $meets = 0;
            }
            break;
        case "t": // tech level min
            $tlevel = gettechlevel( $ai, $_SESSION["techs"], $tparr[1]);
            if ($tlevel < $tparr[2]) {
                $meets = 0;
            }
            break;
        case "B": // base level and targlevel <= base level
            if (($baselevel < $tparr[1]) || ($targlevel > $baselevel)) {
                $meets = 0;
            }
            break;
        case "b": // base level
            if ($baselevel < $tparr[1]) {
                $meets = 0;
            }
            break;
        case "P":
            // note: player level 1-2->1, 3-4->2, ... ,31-32->16
            if ($targlevel > round(($_SESSION["level"] * $tparr[1]) + 0.5)) {
                $meets = 0;
            }
            break;
        default:
            break;
    }
    return $meets;
}


/*
 * getcomponentlines - returns array of lines for synthesis module
 *      format CS|type|quantity|flag|1|fuel|metal|mineral|xtal|time|comps|preqs
 *      first entry is queue info
 */
function getcomponentlines($ai, &$baserow) {
    $ret = array("");
    $retidx = 0;

    $location = $baserow["location"];
    $modules = $baserow["infra"];
    $resources = $baserow["res_store"];
    $components = $baserow["components"];
    $drones = $baserow["drones"];

    // get how many sets in progress
    $numinprogress = checktimedentry(TIMER_CON_COMPONENT, $ai, $location, "", "");
    // if # sets in progress >= module level then set flag = 0 for all
    $synthlevel = getmodulelevel(MODULE_SYNTHESIS, $modules);
    $qsize = floor(($synthlevel + 1) / 2);
    if ($numinprogress >= $qsize) {
        $busy = 1;
    } else {
        $busy = 0;
    }
    // print summary of queue data
    $ret[$retidx] = "CSQ|$qsize|$numinprogress";
    $retidx++;

    $comporderarr = explode(",", getrecipelist(RECIPE_TYPE_COMP));

    foreach ($comporderarr as $ctype) {
        // get quantity already have
        $quantity = getcomponentquantity($components, $ctype);

        $ret[$retidx] = getrecipeline($ai, RECIPE_TYPE_COMP, $ctype, 0, "CS", $quantity, $busy, $baserow, TECH_SYNTHESIS, DRUDGEAI_ROLE_CONST);

        // calc max that can be constructed based on materials in base
        $cmax = calcmaxpossible($resources, $components, $drones, $ret[$retidx]);
        $ret[$retidx] .= "|$cmax";

        $retidx++;
    } // foreach ($componentlist as $ctype)
    return $ret;
}


/*
 * getcomponentqueueinfo
 *  returns array of two items
 *      number of synthesis jobs in progress
 *      size of synthesis queue
 */
function getcomponentqueueinfo($ai, $location, $modules) {
    $numinprogress = checktimedentry( TIMER_CON_COMPONENT, $ai, $location, "", "");
    $synthlevel = getmodulelevel(MODULE_SYNTHESIS, $modules);
    $qsize = floor(($synthlevel + 1) / 2);

    return array($numinprogress, $qsize);
}


/*
 * synthesiscomponent - check preqs and resources and if valid then create
 *      timer event to create components
 *  type is in format "comptype:quantity"
 */
function synthesiscomponent($ai, $location, $type) {
    global $mysqlidb;
    global $comp_name; global $res_name; global $timer_name;

    $location = convertdloctoloc($location);

    $tparts = explode(":", $type);
    $ctype = $tparts[0];
    $quantity = $tparts[1];
    $targlevel = 0;
    if ($ctype == COMPONENT_DAI) {
        $targlevel = $quantity;
        $quantity = 1;
    }
    if (($ctype > 0) && ($ctype <= COMPONENT_DAI) && ($quantity > 0)) {
        $rmsg = "";

        $baselevel = 0;
        $daicount = 0;
        $res_store = "0/0/0/0/0";
        $newresstr = "";
        $modules = "";
        $query = "select level,infra,res_store,drudgeais,dlocation,location from bases where controller='$ai' and location='$location'";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) { // got base record
            $row = $result->fetch_assoc();
            $res_store = $row["res_store"];
            $baselevel = $row["level"];
            $modules = $row["infra"];
            $dlocation = $row["dlocation"];
            $daicount = count(explode(",", trim($row["drudgeais"], ", ")));

            $fail = "Failed to begin synthesis of " . number_format($quantity) . " {$comp_name[$ctype]} in base at $dlocation, ";
            $faild = "Failed to synthesis level $targlevel {$comp_name[$ctype]} in base at $dlocation, ";

            $recipe = getrecipe($ai, RECIPE_TYPE_COMP, $ctype, 0, $row, TECH_SYNTHESIS, DRUDGEAI_ROLE_CONST);
            if ($recipe == null) {
                $rmsg = (($ctype == COMPONENT_DAI) ? $faild : $fail);
                $rmsg .= " unable to determine how to perform synthesis";
            }

            if ($rmsg == "") {
                // get how many component sets in progress and queue size
                $queue = getcomponentqueueinfo($ai, $location, $modules);
                if ($queue[0] >= $queue[1]) {
                    $rmsg = (($ctype == COMPONENT_DAI) ? $faild : $fail);
                    $rmsg .= " queue is at max capacity";
                } else if ($recipe["notmet"] != "") {
                    $rmsg = (($ctype == COMPONENT_DAI) ? $faild : $fail);
                    $rmsg .= " requirement not met: ";

                    $preqarr = explode(";", $recipe["notmet"]);
                    $pstr = "";
                    foreach ($preqarr as $preq) {
                        if ($pstr != "") {
                            $pstr .= ", ";
                        }
                        $pstr .= formatpreq($preq);
                    }
                    $rmsg .= $pstr;
                } else { // queue has room and preqs all met
                    // calc resources involved
                    $needres = array(MAX_TOTAL_RES);
                    $resstr = "";
                    for ($idx = 0; $idx < MAX_TOTAL_RES; $idx++) {
                        $needres[$idx] = round($recipe["resarr"][$idx] * $quantity);
                        if ($idx < MAX_NORMAL_RES) {
                            $resstr .= $needres[$idx] . "/";
                        } else {
                            $duration = $needres[$idx];
                        }
                    }
                    if ($ctype == COMPONENT_DAI) {
                        // max drudge ai capacity is base level + 4;
                        $curdaicount += checktimedentry( TIMER_CON_COMPONENT, $ai, $location, $ctype, "");
                        if ($curdaicount >= ($baselevel + 4)) {
                            $rmsg = "$faild already contains max ".($baselevel + 4);
                        }
                    }
                    if ($rmsg == "") {
                        // check if sufficient resources to synthesis these components
                        $newres = array(MAX_NORMAL_RES+1);
                        $haveres = explode("/", $res_store);
                        for ($idx = 0; $idx < MAX_NORMAL_RES; $idx++) {
                            $newres[$idx] = (int) ($haveres[$idx] - $needres[$idx]);
                            if ($newres[$idx] < 0) {
                                $tmpstr = number_format($needres[$idx]) . " " . $res_name[$idx];
                                if ($rmsg == "") {
                                    $rmsg = (($ctype == COMPONENT_DAI) ? $faild : $fail);
                                    $rmsg .= " insufficient resources: $tmpstr";
                                } else {
                                    $rmsg .= ", $tmpstr";
                                }
                            }
                        }
                        $newres[MAX_NORMAL_RES] = $haveres[MAX_NORMAL_RES]; // just copy scrap value
                        $newresstr = implode("/", $newres);
                    }
                    // remove core from player items
                    if (($rmsg == "") && ($ctype == COMPONENT_DAI)) {
                        $query = "select items from player where name='$ai'";
                        $presult = $mysqlidb->query($query);
                        if ($presult && ($presult->num_rows > 0)) {
                            $prow = $presult->fetch_row();
                            $ires = removeitemsquantity($prow[0], "DC:$targlevel:1");
                        }
                        if (!$presult || ($presult->num_rows == 0) || ($ires[1] != "")) {
                            $rmsg = "$faild no level $targlevel core found in items";
                        } else {
                            $query = "update player set items='$ires[0]' where name='$ai'";
                            $presult = $mysqlidb->query($query);
                            if (!$presult) {
                                $rmsg = "$faild unable to update record";
                            }
                        }
                    }

                    if ($rmsg == "") {
                        if ($newresstr != "") {
                            // update base record resources
                            $query = "update bases set res_store='$newresstr' where controller='$ai' and location='$location'";
                        }
                        $result = $mysqlidb->query($query);
                        if (!$result) {
                            $rmsg = (($ctype == COMPONENT_DAI) ? $faild : $fail);
                            $rmsg .= " unable to update base record";
                        }
                        if ($rmsg == "") {
                            if ($ctype == COMPONENT_DAI) {
                                $quantity = "level $targlevel";
                            }
                            $qstr = number_format($quantity);
                            $results = "$qstr {$comp_name[$ctype]};$ctype:$quantity";
                            createtimedentry( $duration, TIMER_CON_COMPONENT, $ai, "$location:$dlocation", $ctype, "",
                                                    $recipe['dai'], "", $resstr, "", "", $results);
                            $rmsg = "{$timer_name[TIMER_CON_COMPONENT]}: $qstr {$comp_name[$ctype]} in base at $dlocation etc " . formatduration($duration);
                        }
                    } // if (($rmsg == "") && ($tqpayload != ""))
                } // queue has room
            }
        } // got base record
        if ($rmsg != "") {
            postreport ($ai, 0, $rmsg);
        }
    }
}



?>
