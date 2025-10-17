<?php
/*
 * getlists -
 *  prints one or more strings for each list type
 *  basic list format "key str|baseinfo|baseinfo|..."
 *      where baseinfo format is "location:level:name;data"
 *      data is specific to each list but will not include
 *      semicolons or colons as separators
 */
function getlists($ai, $operation) {
    global $mysqlidb;

    // First get all $baseinfo as this is common to most lists
    // $_SESSION["bases"] should contain 2 dimension array
    //  and is set in bases.php by background base list poll
    //  first index is base #, second is record column, value is column value
    $index = 0;
    $baseinfo = array();
    $locsinfo = array();
    $gen_list = array();
    if (!array_key_exists("bases", $_SESSION)) {
        $_SESSION["bases"] = array();
    }
    foreach ($_SESSION["bases"] as $arr) {
        $baseinfo[$index] = "{$arr['location']}:{$arr['level']}:{$arr['name']}:{$arr['dlocation']}";
        // controlled locations
        $locsinfo[$index] = $arr["locstr"];
        // filled in later for resources request
        $gen_list[$index] = "";
        $index++;
    }
    $ops = explode(":", $operation);
    if (count($ops) > 1) {
        // ops[0] is comma separated list of commands
        // ops[1] is ignored
        // ops[2] is ignored
        // ops[3] is trade item
        $cmds = explode(",", $ops[0]);
        foreach ($cmds as $cmd) {
            $listsinfo = Array();
            $listsindex = 0;
            $refresh = false;
            $seckey = "0";
            $cmdarr = explode(".", $cmd);
            switch ($cmdarr[0]) {
                case "refreshblock":
                    $refresh = true;
                case "getblock":
                    // $cmdarr[1] holds block number
                    //  convert from display block to coord block
                    $larr = explode(".", convertdloctoloc("{$cmdarr[1]}.0.0"));
                    $blockarr = getblockinfo($ai, $larr[0]);
                    if ($blockarr != null) {
                        $listsinfo[$listsindex] = "BLOCK|{$cmdarr[1]}|" . implode("|", $blockarr);
                        $listsindex++;
                        $seckey = "block";
                    }
                    break;
                case "refreshtechs":
                    $refresh = true;
                case "gettechs":
                    $listsinfo[$listsindex] = "TECHS";
                    foreach ($_SESSION["bases"] as $idx=>$arr) {
                        $techs = gettechlines($ai, $arr);
                        $listsinfo[$listsindex] .= "|{$baseinfo[$idx]};" . str_replace("|", "{", str_replace(";", ",", implode("}", $techs)));
                    }
                    $listsindex++;
                    $seckey = "techs";
                    break;
                case "refreshdefs":
                    $refresh = true;
                case "getdefs":
                    $listsinfo[$listsindex] = "DEFS";
                    foreach ($_SESSION["bases"] as $idx=>$arr) {
                        // timer queue
                        $defs = getdefenselines($ai, $arr);
                        $listsinfo[$listsindex] .= "|{$baseinfo[$idx]};" . str_replace("|", "{", str_replace(";", ",", implode("}", $defs)));
                    }
                    $listsindex++;
                    $seckey = "defense";
                    break;
                case "refreshreps":
                    $refresh = true;
                case "getreps":
                    $listsinfo[$listsindex] = "REPS";
                    foreach ($_SESSION["bases"] as $idx=>$arr) {
                        // timer queue
                        $reps = getrepairbaseline($ai, $arr);
                        $listsinfo[$listsindex] .= "|{$baseinfo[$idx]};" . str_replace("|", "{", $reps);
                    }
                    $listsindex++;
                    $seckey = "repair";
                    break;
                case "refreshchrons":
                    $refresh = true;
                case "getchrons":
                    // Chronicles
                    $listsinfo[$listsindex] = "CHRON";
                    foreach ($baseinfo as $idx=>$base) {
                        $locs = explode(",", $locsinfo[$idx]);

                        $temp_chron_list = Array();
                        foreach ($locs as $location) {
                            // get list of chronicles for this base
                            foreach (getchroniclelist($ai, 0, $location, $location) as $chron_listing) {
                                //parts[1] = entry, parts[3] = read flag, parts[4] = chron title
                                $parts = explode("|", $chron_listing);

                                //duplicate entries will just overwrite the list
                                $temp_chron_list[$parts[1]] = $parts[1] . '{' . $parts[3] . '{' . $parts[4];
                            }
                        }
                        $listsinfo[$listsindex] .= "|$base;" . implode("}", $temp_chron_list);
                    }
                    $listsindex++;

                    // print list of chronicles published to organization
                    $listsinfo[$listsindex] = "OCHRON|" . implode(";", getorgchroniclelist($_SESSION["alliance"]));
                    $listsindex++;

                    // if ai flagged as having access to GM functions then show log file list
                    if ((floor($_SESSION["rank"]) & 4) != 0) {
                        $listsinfo[$listsindex] = "LOG|" . getlogfilelist();
                        $listsindex++;
                    }
                    $seckey = "chrons";
                    break;
                case "refreshpends":
                    $refresh = true;
                case "getpends":
                    // Pending
                    $listsinfo[$listsindex] = "TQ";
                    foreach ($_SESSION["bases"] as $idx=>$arr) {
                        // timer queue
                        $timerqueue = printtimerqueue($ai, $arr['location']);
                        $listsinfo[$listsindex] .= "|{$baseinfo[$idx]};" . str_replace("|", "{", implode("{", $timerqueue));
                    }
                    $listsindex++;
                    $seckey = "pends";
                    break;
                case "refreshdais":
                    $refresh = true;
                case "getdais":
                    // Drudge AIs
                    $index = 0;
                    $drudgeaiinfo = array();
                    $drudgeai_list = array();
                    $drudgeai_query_list = "-1,"; // insure it is never empty string
                    foreach ($_SESSION["bases"] as $arr) {
                        $drudgeaiinfo[$index] = trim($arr["drudgeais"], ",");
                        $drudgeai_list[$index] = "";
                        // build list of all drudgeais in all bases
                        if ($drudgeaiinfo[$index] != "") {
                            $drudgeai_query_list .= $drudgeaiinfo[$index] . ",";
                        }
                        $index++;
                    }
                    //Get list of current drudgeais by base.
                    $drudgeai_query_list = trim($drudgeai_query_list, ",");

                    //get list of all drudgeais controlled by this ai
                    $query = "select entry,name,role,level,canalysis,ccontrol,cheuristics,ctactics,cmultitasking,unusedpoints,exp from drudgeai where entry in ($drudgeai_query_list)";
                    $result = $mysqlidb->query($query);
                    if ($result && ($result->num_rows > 0)) {
                        while ($row = $result->fetch_row()) {
                            for ($index = 0; $index < count($drudgeaiinfo); $index++) {
                                //if the drudgeai is at this base, add the drugeai
                                //information to the drudgeai list array.
                                if (preg_match("/" . $row[0] . "/", $drudgeaiinfo[$index]) > 0) {
                                    $drudgeai_list[$index] .= "{" . implode(":", $row);
                                    break;
                                }
                            }
                        }
                    }

                    $listsinfo[$listsindex] = "DAI";
                    foreach ($baseinfo as $idx=>$base) {
                        $listsinfo[$listsindex] .= "|$base;" . $drudgeai_list[$idx];
                    }
                    $listsindex++;
                    $seckey = "dais";
                    break;
                case "refreshdrones":
                    $refresh = true;
                case "getdrones":
                    // Drones
                    $listsinfo[$listsindex] = "DR";
                    foreach ($_SESSION["bases"] as $idx=>$arr) {
                        $base = $baseinfo[$idx];
                        $drones = getdronelines($ai, $arr);
                        $listsinfo[$listsindex] .= "|$base;"
                                                    . str_replace("|", "{", str_replace(";", ",", implode("}", $drones)));
                    }
                    $listsindex++;
                    $listsinfo[$listsindex] = "DDR|";
                    foreach ($_SESSION["bases"] as $arr) {
                        $ddr = str_replace(";", "}", $arr['damaged_drones']);
                        $listsinfo[$listsindex] .= "{$arr['location']}}$ddr{";
                    }
                    $listsindex++;
                    $seckey = "drones";
                    break;
                case "refreshcomps":
                    $refresh = true;
                case "getcomps":
                    // Components
                    $listsinfo[$listsindex] = "CMPS";
                    foreach ($_SESSION["bases"] as $idx=>$arr) {
                        $base = $baseinfo[$idx];
                        $comps = getcomponentlines($ai, $arr);
                        $listsinfo[$listsindex] .= "|$base;"
                                                    . str_replace("|", "{", str_replace(";", ",", implode("}", $comps)));
                    }
                    $listsindex++;
                    $seckey = "comps";
                    break;
                case "refreshreslocs":
                    $refresh = true;
                case "getreslocs":
                    // Resources
                    $query = "select location,res_gen,dlocation from world where controller = '$ai'";
                    $result = $mysqlidb->query($query);
                    if ($result && ($result->num_rows > 0)) {
                        while ($row = $result->fetch_row()) {
                            for ($index = count($locsinfo)-1; $index >= 0; $index--) {
                                //if the controlled location is at this base, add
                                //the resource information to the locsinfo array.
                                if (preg_match("/" . $row[0] . "/", $locsinfo[$index]) > 0) {
                                    $gen_list[$index] .= ":" . $row[2] . ":" . $row[1];
                                    break;
                                }
                            }
                        }
                    }
                    //Get list of current resources by base.
                    $listsinfo[$listsindex] = "RE";
                    foreach ($_SESSION["bases"] as $idx=>$arr) {
                        $base = $baseinfo[$idx];
                        // resources
                        $listsinfo[$listsindex] .= "|$base;"
                                                        . $arr["res_store"] . ":"
                                                        . $arr["res_limit"] . ":"
                                                        . $arr["res_gen"] . "/" . getdronecount($arr["drones"], DRONE_WORKR_MIN)
                                                        . $gen_list[$idx];
                    }
                    $listsindex++;
                    $seckey = "locs";
                    break;
                case "refreshforms":
                    $refresh = true;
                case "getforms":
                    // Formations
                    $listsinfo[$listsindex] = "FRMS";
                    foreach ($_SESSION["bases"] as $idx=>$arr) {
                        $formations_temp = getallformations($ai, $arr);
                        $base = $baseinfo[$idx];
                        $listsinfo[$listsindex] .= "|$base;" . implode("{", str_replace(array("|", ";"), "}", $formations_temp));
                    }
                    $listsindex++;
                    $seckey = "forms";
                    break;
                case "refreshorgforms":
                    $refresh = true;
                case "getorgforms":
                    if (enhancedfeatureaccess()) {
                        $listsinfo[$listsindex] = "FRMOS|"
                                    . implode("|", getorgformationlist($_SESSION["alliance"]));
                        $listsindex++;
                        $seckey = "oforms";
                    }
                    break;
                case "refreshtrades":
                    $refresh = true;
                case "gettrades":
                    $listsinfo[$listsindex] = "TRDOS";
                    $orders = getmyorderlines($ai);
                    foreach ($_SESSION["bases"] as $idx=>$arr) {
                        $base = $baseinfo[$idx];
                        // prepend queuesize for each base
                        $qsize = floor((getmodulelevel(MODULE_TRADING, $arr['infra']) + 1) / 2);
                        $listsinfo[$listsindex] .= "|$base;$qsize:";
                        if (isset($orders[$arr["location"]])) {
                            $listsinfo[$listsindex] .= $orders[$arr["location"]];
                        } else {
                            $listsinfo[$listsindex] .= "0"; // zero trades
                        }
                    }
                    $listsindex++;
                    $seckey = "trades";
                    break;
                case "refreshtrdposts":
                    $refresh = true;
                case "gettrdposts":
                    // unrelated to any base
                    $listsinfo[$listsindex] = "TRDPOSTS|"
                        . getorderline (2, $ops[3], "desc") . "|"
                        . getorderline (1, $ops[3], "asc") . "|"
                        . $_SESSION["gcredits"] . "|"
                        . TRANSFEE . "|" . TRANSMULT;
                    $listsindex++;
                    $seckey = "trdposts";
                    break;
            } // switch ($operation)

            if ($listsindex > 0) {
                printdeltas("listsinfo", $seckey, $refresh, $listsinfo);
            }
        }
    }
}

/*
    * getlogfilelist
    *  returns a colon separated list of *.log files in the logs directory
    *      in descending name order
    */
function getlogfilelist() {
    require_once "genfilelist.php";
    $fstr = genfilelist("logs", "log", "");
    if ($fstr == "") {
        $fstr = "No log files found";
    } else {
        $farr = explode(";", $fstr);
        arsort($farr);
        $fstr = implode(";", $farr);
    }

    return $fstr;
}

?>
