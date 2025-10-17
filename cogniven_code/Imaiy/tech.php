<?php
/*
 * Functions to deal with techs
 * Author: Chris Bryant
 */


/*
 * gettechlines - returns an array of lines for each tech in list provided
 *      format is T|tech|maxlevel|level|flag|fuel|metal|mineral|xtal|time|preqs
 */
function gettechlines($ai, &$base) {
    $ret = array("");
    $retidx = 0;

    $techarr = explode(":", $_SESSION["techs"]);

    // if tech already training for this base
    $techstraining = gettimedentryslots(TIMER_TRAIN_TECH, $ai, $base["location"], "");
    $ret[$retidx] = "TQ|1|0|$techstraining|{$base['name']}|{$base['dlocation']}"; // by default it is only one in each base
    $retidx++;

    $trainable = ($techstraining == "") ? 0 : 1;

    $techorderarr = explode(",", getrecipelist(RECIPE_TYPE_TECH));

    foreach ($techorderarr as $tech) {
        $ret[$retidx] = getrecipeline($ai, RECIPE_TYPE_TECH, $tech, $techarr[$tech], "TE", $trainable, TECH_MAX_LEVEL, $base, TECH_RESEARCH, DRUDGEAI_ROLE_BASE);
        $retidx++;
    }
    return $ret;
}


/*
 * leveltech - queue up training of tech
 */
function leveltech($ai, $location, $type) {
    global $mysqlidb;
    global $tech_name; global $timer_name; global $res_name;

    $location = convertdloctoloc($location);

    $rmsg = "";
    if (($type < 1) || ($type > MAX_TECH_INDEX)) {
        $rmsg = "Failed to begin training tech, invalid tech specified ($type)";
    } else {
        $fail = "Failed to begin training tech {$tech_name[$type]}";

        $newresstr = "";
        $query = "select dlocation,drudgeais,res_store,level,infra,location from bases where controller='$ai' and location='$location'";
        $resultb = $mysqlidb->query($query);
        if (!$resultb || ($resultb->num_rows < 1)) {
            $rmsg = "$fail, could not locate base record";
        } else {
            $rowb = $resultb->fetch_assoc();
            $dlocation = $rowb["dlocation"];

            $fail .= " in base at $dlocation,";

            $query = "select techs from player where name='$ai'";
            $resultp = $mysqlidb->query($query);
            if ($resultp && ($resultp->num_rows > 0)) {
                $rowp = $resultp->fetch_row();

                $_SESSION["techs"] = $rowp[0]; // might as well update session info now
                $techlist = explode(":", $rowp[0]);

                if ($techlist[$type] >= TECH_MAX_LEVEL) {
                    $rmsg = "$fail this tech already max level";
                } else if (checktimedentry(TIMER_TRAIN_TECH, $ai, "", "", $type)) {
                    $rmsg = "$fail this tech already being trained in this or another base";
                } else if (checktimedentry(TIMER_TRAIN_TECH, $ai, $location, "", "")) {
                    $rmsg = "$fail already training a tech in this base";
                } else { // tech is in right range and not maxed
                    $recipe = getrecipe($ai, RECIPE_TYPE_TECH, $type, $techlist[$type], $rowb, TECH_RESEARCH, DRUDGEAI_ROLE_BASE);
                    if ($recipe == null) {
                        $rmsg = "$fail unable to determine how to train tech";
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
                        // calc resources involved
                        $haveres = explode("/", $rowb["res_store"]);
                        $newres = array(MAX_NORMAL_RES+1);
                        for ($idx = 0; $idx < MAX_NORMAL_RES; $idx++) {
                            $newres[$idx] = round($haveres[$idx] - $recipe["resarr"][$idx]);
                            if ($newres[$idx] < 0) {
                                $tmpstr = number_format($recipe["resarr"][$idx]) . " " . $res_name[$idx];
                                if ($rmsg == "") {
                                    $rmsg = "$fail insufficient resources: $tmpstr";
                                } else {
                                    $rmsg .= ", $tmpstr";
                                }
                            }
                        }
                        $newres[MAX_NORMAL_RES] = $haveres[MAX_NORMAL_RES]; // just copy scrap value
                        $newresstr = implode("/", $newres);
                        $duration = $recipe["resarr"][MAX_NORMAL_RES];
                    }
                }

                if (($rmsg == "") && ($newresstr != "")) { // resources and prereqs are good
                    // subtract resources from base stores
                    $query = "update bases set res_store='$newresstr' where controller='$ai' and location='$location'";
                    $resultb = $mysqlidb->query($query);
                    if (!$resultb) {
                        $rmsg = "$fail unable to update base record";
                    } else { // base updated
                        // create timer event
                        $resstr = implode("/", $recipe["resarr"]);
                        $results = "{$tech_name[$type]};$type";
                        createtimedentry($duration, TIMER_TRAIN_TECH, $ai, "$location:$dlocation", "$location:$dlocation", $type,
                                                $recipe["dai"], "", $resstr, "", "", $results);
                        $rmsg = "{$timer_name[TIMER_TRAIN_TECH]}: {$tech_name[$type]} in base at $dlocation etc " . formatduration($duration);
                    } // base updated
                } // resources and techs are good
            } // tech is in right range and not maxed
        } // if ($resultp->num_rows > 0)
    }
    if ($rmsg != "") {
        postreport ($ai, 0, $rmsg);
    }
}


/*
 * gettechlevel - gets level of tech skill from tech string
 *      format of tech string is expected to be that as from player record
 *      if techstr is provided then ai is ignored
 *      either ai or techstr must be provided
 *          if techstr is "" then ai will be used to get techstr from player record
 */
function gettechlevel($ai, $techstr, $tech) {
    global $mysqlidb;
    $retlevel = 0;
    if ($techstr == "") {
        $query = "select techs from player where name='$ai';";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            $row = $result->fetch_row();
            $techstr = $row[0];
        }
    }
    $techarr = explode(":", $techstr);
    if (($tech > 0) && ($tech < count($techarr))) {
        $retlevel = $techarr[$tech];
    }
    return $retlevel;
}

/*
 * gettechmult - gets a multiplier based on a tech skill
 *  returns multiplier of 2% times tech level
 */
function gettechmult($ai, $techstr, $tech) {
    $mult = 1.0;

    if (($techstr == null) || ($techstr == "")) {
        $techstr = $_SESSION["techs"];
    }
    $stats = getrecipestats(RECIPE_TYPE_TECH, $tech, gettechlevel($ai, $techstr, $tech));
    if ($stats != "") {
        $mult = 1.0 + (gettechlevel($ai, $techstr, $tech) * $stats);
    }
    return $mult;
}


?>

