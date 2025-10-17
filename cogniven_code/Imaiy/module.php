<?php
/*
 * Functions to deal with modules
 * Author: Chris Bryant
 */

/*
 * getscannerranges
 *  parameters
 *      $scanlevel = level of scanner module
 *      scanskill = level of scanning tech skill
 *
 *  returns array of two values
 *      first is detect range
 *      second is scan range
 */
function getscannerrange($ai, &$base) {

    $daistats = getrecipedaistat($base['location'], $base["drudgeais"], DRUDGEAI_ROLE_SENSOR);

    // increase both by 0.0025 * drudgeai heuristics stat
    $mult = 1.0 + ($daistats[0] * DRUDGEAI_STAT_MULT);
    // detect range is at least 40km and increases by 40km for each level of scan module
    $detect = round((40 * (getmodulelevel(MODULE_SCAN, $base["infra"]) + 1)) * $mult);
    // scan range is at least 20km and increases by 20km for each level of scan skill
    $scan = round((20 * (gettechlevel($ai, $_SESSION["techs"], TECH_DATA_PROC) + 1)) * $mult);
    if ($scan > $detect) {
        $scan = $detect; // limit scan to no more than detect range
    }
    return array($detect, $scan);
}


/*
 * getdefensecapacity
 *  returns defense capacity based on current permimeter level
 */
function getdefensecapacity($modules) {
    $perimlevel = getmodulelevel(MODULE_PERIMETER, $modules);
    return (1024 * $perimlevel * $perimlevel);
}


/*
 * getmodulelevel -
 *  level from modstr for modtype
 */
function getmodulelevel($modtype, $modstr) {
    $retlevel = 0; // default if module type not found
    $modarr = explode(":", $modstr);
    if (($modtype > 0) && ($modtype < count($modarr))) {
        $retlevel = $modarr[$modtype];
    }
    return $retlevel;
}


/*
 * additionalmoduleinfo -
 *  returns string for additonal information to be displayed
 *      for a specific module type
 *      baserow contains the entire record retrieved for this base
 */
function additionalmoduleinfo($ai, &$base, $module) {
    $list = array("");

    // not all module types have addtional information
    switch ($module) {
        case MODULE_SCAN:
            // detect and scan ranges
            $list = getscannerrange($ai, $base);
            break;
        case MODULE_STORAGE:
            $list = explode("/", $base['res_limit']);
            $list[] = $base['drone_limit'];
            break;
        case MODULE_REFINE:
            $resgen = explode("/", $base['res_gen']);
            $resraw = explode("/", $base['res_raw']);
            for ($idx = 0; $idx < MAX_NORMAL_RES; $idx++) {
                if ($resraw[$idx] < 1) {
                    $resraw[$idx] = 1;
                }
                $rates[$idx] = floor((100 * $resgen[$idx]) / $resraw[$idx]);
            }
            $list[0] = implode("|", $rates);
            break;
        case MODULE_PERIMETER:
            $list[0] = getdefensecapacity($base['infra']);
            break;
        case MODULE_REPAIR:
            $list = getdronerepairinfo($ai, $base);
            break;
        case MODULE_RECYCLE:
            $list[0] = getrecycleinfo($ai, $base);
            break;
        case MODULE_RESEARCH:
            $list[0] = gettimedentryslots(TIMER_TRAIN_TECH, $ai, $base['location'], "");
            break;
        case MODULE_ASSEMBLY:
            $list = getdronequeueinfo($ai, $base);
            break;
        case MODULE_SYNTHESIS:
            $list = getcomponentqueueinfo($ai, $base['location'], $base['infra']);
            break;
        case MODULE_TRADING:
            $list = gettradequeueinfo($ai, $base['location'], $base['infra']);
            break;
        case MODULE_CONTROL:
            $list = getmodulelevelingstatus($ai, $base);
            break;
        default:
            break;
    }
    return $list;
}


/*
 * modulestatus
 *  process client commands for module info and actions on modules
 */
function modulestatoract($ai, $operation, $stageinfo) {
    $list = array(1);
    $listidx = 0;

    $ops = explode(":", $operation);
    if (count($ops) > 2) {
        // ops[0] is comma separated list of commands
        // ops[1] is base location
        // ops[2] is ignored
        // ops[3] is ignored
        $cmds = explode(",", $ops[0]);
        foreach ($cmds as $cmd) {
            // cparts[0] is command
            // cparts[1] is module number (may not be present)
            $cparts = explode("-", $cmd);
            $seckey = "";
            $refresh = false;
            switch ($cparts[0]) {
                case "savestage":
                    savestageinfo($ai, $stageinfo);
                    break;
                case "dispatchstage":
                    dispatchstage($ai, $ops[1], $stageinfo);
                    break;
                case "refreshstage":
                    $refresh = true;
                case "getstage":
                    $list = getstageinfo($ai, $ops[1], $stageinfo);
                    $listidx = count($list);
                    $seckey = "stageinfo";
                    break;
                case "refreshmod":
                    $refresh = true;
                case "getmod":
                    $list[$listidx] = getmoduleline($ai, $ops[1], $cparts[1]);
                    $listidx++;
                    $seckey = "mod$cparts[1]";
                    break;
                case "refreshmai":
                    $refresh = true;
                case "getmai":
                    $list[$listidx] = getlevelplayerline($ai, $ops[1]);
                    $listidx++;
                    $list[$listidx] = "ITEMS|{$_SESSION['items']}";
                    $listidx++;
                    $seckey = "mai";
                    break;
                case "refreshbase":
                    $refresh = true;
                case "getbase":
                    $list[$listidx] = getbaseline($ops[1]);
                    $listidx++;
                    $seckey = "base";
                    break;
            }
            if ($seckey != "") {
                printdeltas("modinfo$ops[1]", $seckey, $refresh, $list);
            }
        }
    }
}



/*
 * moduleaction -
 *  processes commands from client for action on modules
 *      $location is base location in format "b.x.y"
 *      $type is specific module to operate on
 */
function moduleaction($ai, $operation, $location, $type) {
    if ($location != "") {
        switch ($operation) {
            case "traintech":
                // level tech given in $type
                leveltech($ai, $location, $type);
                break;
            case "level":
                if ($type == MODULE_NONE) {
                    // master AI and not module
                    levelplayer($ai, $location);
                } else {
                    levelmodule($ai, $location, $type);
                }
                break;
            case "synthesis":
                synthesiscomponent($ai, $location, $type);
                break;
            case "scraprecycle":
                recyclescrap($ai, $location, $type);
                break;
            default:
                break;
        }
    }
}

/*
 * recyclescrap
 *  initiates recycle of scrap
 */
function recyclescrap($ai, $baseloc, $amount) {
    global $mysqlidb;
    global $timer_name;

    $baseloc = convertdloctoloc($baseloc);

    $rmsg = "";
    // type is amount to recycle
    $numinprogress = checktimedentry(TIMER_SCRAP_RECYCLE, $ai, $baseloc, "", "");
    $numinprogress += checktimedentry(TIMER_DRONE_RECY, $ai, $baseloc, "", "");
    $assylevel = 0;
    $scrap = 0;
    $query = "select res_store,infra,dlocation from bases where controller='$ai' and location='$baseloc';";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_row();
        $assylevel = getmodulelevel(MODULE_RECYCLE, $row[1]);
        $res = explode("/", $row[0]);
        $scrap = $res[MAX_NORMAL_RES];
        $basedloc = $row[2];

        $fail = $timer_name[TIMER_SCRAP_RECYCLE] . " for base $basedloc failed,";

        $qsize = floor(($assylevel + 1) / 2);
        if ($numinprogress >= $qsize) {
            $rmsg = "$fail queue is full";
        } else {
            if ($amount > $scrap) {
                $rmsg = "$fail more scrap specified than present in base ($amount / $scrap)";
            } else {
                $res[MAX_NORMAL_RES] = (int) ($scrap - $amount);
                $res_store = implode("/", $res);
                $query = "update bases set res_store='$res_store' where controller='$ai' and location='$baseloc';";
                $result = $mysqlidb->query($query);
                if (!$result) {
                    $rmsg = "$fail unable to remove scrap from base record";
                } else {
                    $recipe = getrawrecipe(RECIPE_TYPE_RES, 4, 1);
                    if ($recipe) {
                        $dur = 2;
                        $dura = explode("/", $recipe["resources"]);
                        if (count($dura) > 4) {
                            $dur = $dura[4];
                        }
                        $duration = floor($amount * ($dur / gettechmult($ai, "", TECH_RECYCLE)) / $recipe["scrap"]);
                        $tqpayload = number_format($amount)." units";
                        $res = "////$amount";
                        $results = "$tqpayload;$amount";
                        createtimedentry($duration, TIMER_SCRAP_RECYCLE, $ai, "$baseloc:$basedloc", "$baseloc:$basedloc", $amount,
                                                "", "", $res, "", "", $results);
                        $rmsg = $timer_name[TIMER_SCRAP_RECYCLE] . " $tqpayload in base at $basedloc etc " . formatduration($duration);
                    } else {
                        $rmsg = "$fail unable to determine how to process scrap";
                    }
                }
            }
        }
    }
    if ($rmsg != "") {
        postreport($ai, 0, $rmsg);
    }
}


/*
 * getmoduleline -
 *  returns array of module info for a specific module
 *      format "MOD|type|busy|max level|module level|fuel|metal|mineral|xtal|time|comps|preqs|base loc|other info"
 *  resource, comps and preqs are those required for next level
*/
function getmoduleline($ai, $baseloc, $module) {
    $ret = "";

    $baseloc = convertdloctoloc($baseloc);

    // need all columns from base record
    if (is_array($_SESSION["bases"])) {
        foreach ($_SESSION["bases"] as $base) {
            if ($base["location"] != $baseloc) {
                continue;
            }

            $marr = explode(":", $base["infra"]);
            if (($module >= 0) && ($module < count($marr))) {
                $modlevel = $marr[$module];

                // query timer queue for module in process
                if (checktimedentry(TIMER_LEVEL_MODULE, $ai, $baseloc, "", $module) > 0) {
                    $busy = 1; // if 1 then module is in process of upgrade
                } else {
                    $busy = 0;
                }

                $addinfo = implode("|", additionalmoduleinfo($ai, $base, $module));

                $ret = getrecipeline($ai, RECIPE_TYPE_MODULE, $module, $modlevel, "MOD", $busy, MODULE_MAX_LEVEL, $base, TECH_CONST, DRUDGEAI_ROLE_CONST) . "|$baseloc|$addinfo";
            }
        }
    }
    return $ret;
}


/*
 * levelmodule -
 *  level module of given $modtype
 */
function levelmodule($ai, $location, $modtype) {
    global $mysqlidb;
    global $mod_name; global $timer_name; global $res_name;

    $location = convertdloctoloc($location);

    $rmsg = ""; // if still empty at end then we succeeded
    // verify correct type of module is in this slot
    $query = "select * from bases where controller='$ai' and location='$location';";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_assoc();

        $dlocation = $row["dlocation"];

        $fail = "Module operation for base $dlocation failed,";

        $baselevel = $row["level"];
        $maxconstruction = floor(($baselevel + 3) / 4);
        // check buff to add to max under construction
        $maxconstruction += getmodulebuildadd($location);

        // calc number currently under construction
        $underconstruction = checktimedentry(TIMER_LEVEL_MODULE, $ai, $location, "", "");

        if ($underconstruction >= $maxconstruction) {
            $rmsg = "$fail only able to construct " . $maxconstruction . " module(s) at a time.";
        } else if (checktimedentry(TIMER_LEVEL_MODULE, $ai, $location, "", $modtype)) {
            $rmsg = "$fail module is currently being leveled";
        } else { // module is valid
            $have_res = explode("/", $row["res_store"]);
            $drones = $row["drones"];
            $modules = $row["infra"];
            $modlevel = getmodulelevel($modtype, $modules);
            $modname = $mod_name[$modtype];
            $recipe = getrecipe($ai, RECIPE_TYPE_MODULE, $modtype, $modlevel, $row, TECH_CONST, DRUDGEAI_ROLE_CONST);
            if ($recipe == null) {
                $rmsg = "$fail unable to determine how to perform construction";
            } else if ($recipe["notmet"] != "") {
                $preqarr = explode(";", $recipe["notmet"]);
                foreach ($preqarr as $preq) {
                    if ($rmsg != "") {
                        $rmsg .= ", ";
                    }
                    $rmsg .= formatpreq($preq);
                }
                $rmsg = "$fail requirement not met: $rmsg";
            } else {
                // check resources
                $newres = array(MAX_NORMAL_RES+1);
                for ($idx = 0; $idx < MAX_NORMAL_RES; $idx++) {
                    $newres[$idx] = round($have_res[$idx] - $recipe["resarr"][$idx]);
                    if ($newres[$idx] < 0) {
                        $tmpstr = number_format($recipe["resarr"][$idx]) . " " . $res_name[$idx];
                        if ($rmsg == "") {
                            $rmsg = "$fail insufficient resources: $tmpstr";
                        } else {
                            $rmsg .= ", $tmpstr";
                        }
                    }
                }
                $newres[MAX_NORMAL_RES] = $have_res[MAX_NORMAL_RES]; // just copy scrap value
                $newresstr = implode("/", $newres);
                $duration = $recipe["resarr"][MAX_NORMAL_RES];
            }

            if ($rmsg == "") {
                // check drones
                // subtract drones from base stores - if not enough then third return string is not empty
                $dronestrs = removedronequantity($drones, $recipe["drones"]);
                if ($dronestrs[2] != "") {
                    $rmsg = "$fail insufficient drones";
                }
            }
            if ($rmsg == "") {
                // update base record
                $query = "update bases set res_store='$newresstr',drones='{$dronestrs[0]}' where controller='$ai' and location='$location';";
                $result = $mysqlidb->query($query);
                if (!$result) {
                    $rmsg = "$fail unable to update base record";
                } else { // base updated
                    // create timer event
                    $resstr = implode("/", $recipe["resarr"]);
                    $results = "$modname;$modtype";
                    createtimedentry($duration, TIMER_LEVEL_MODULE, $ai, "$location:$dlocation", "$location:$dlocation", $modtype,
                                            $recipe["dai"], $dronestrs[1], $resstr, $recipe["comps"], "", $results);
                    $rmsg = "{$timer_name[TIMER_LEVEL_MODULE]}: $modname in base at $dlocation. etc " . formatduration($duration);
                } // base updated
            } // resources and techs are good
        } // slot is valid
    } // if ($result->num_rows > 0)
    if ($rmsg != "") {
        postreport ($ai, 0, $rmsg);
    }
}


/*
 * getmodulelevelingstatus
 *  return array of strings representing
 *      number of modules that can be leveled at once
 *      modules currently being leveled
 */
function getmodulelevelingstatus($ai, &$base) {
    $stat = array();

    // get max based on base level + buffage
    $baselevel = $base["level"];
    $stat[0] = floor(($baselevel + 3) / 4) + getmodulebuildadd($base["location"]);

    // get modules currently under construction
    $stat[1] = gettimedentryslots(TIMER_LEVEL_MODULE, $ai, $base["location"], "");

    return $stat;
}

?>


