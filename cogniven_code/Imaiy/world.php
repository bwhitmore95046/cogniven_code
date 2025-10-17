<?php
/*
 * Functions to deal with world locations
 * Author: Chris Bryant
 */

require_once 'globals.php';

/*
 * generate_location_resources - determines base resource generation value for
 *  a location
 *
 *  Prerequisites: MAX_NORMAL_RES, MAX_TOTAL_RES and MAX_RESGEN_RATE must be defined
 *  Parameters: None
 *  Returns: string (resource1/resource1/...)
 *      MAX_NORMAL_RES resources will be generate.
 *      Any more than that from MAX_TOTAL_RES will be included as 0.
 */
function generate_location_resources() {

    //generate the amount of resources to distribute
    //will be reduced as value is distributed
    $remaining_distribution = mt_rand(MAX_NORMAL_RES, MAX_RESGEN_RATE * MAX_NORMAL_RES);

    //initialize resources and gen_order
    $gen_order = array();
    $resources = array();

    for ($index = 0; $index < MAX_NORMAL_RES; $index++) {
        $resources[] = 0;
        $gen_order[] = $index;
    }

    //randomize the order of resource generation
    for ($index = 0; $index < MAX_NORMAL_RES; $index++) {
        //swap each index with a random other index
        $random_index = mt_rand(0, MAX_NORMAL_RES - 1);

        $temp = $gen_order[$index];
        $gen_order[$index] = $gen_order[$random_index];
        $gen_order[$random_index] = $temp;
    }

    //calculate each resource's generation value
    for ($index = 0; $index < MAX_NORMAL_RES; $index++) {
        //minimum value of other resources is equal to 1 and maximum is MAX_RESGEN_RATE
        //therefore, remaining resources must be at least 1 per remaining resource
        //      and can be no greater than MAX_RESGEN_RATE per remaining resource
        //the minimum value for this resource is 1 and the maximum value is 1000

        if ($remaining_distribution > ((MAX_NORMAL_RES - 1) - $index) * MAX_RESGEN_RATE) {
            $minimum = $remaining_distribution - (((MAX_NORMAL_RES - 1) - $index) * MAX_RESGEN_RATE);
        } else {
            $minimum = 1;
        }

        $maximum = $remaining_distribution - ((MAX_NORMAL_RES - 1) - $index);
        if ($maximum > MAX_RESGEN_RATE) {
            $maximum = MAX_RESGEN_RATE;
        }

        //generate random value, add it to the resource generation and subtract it from remaining distribution amount
        $random_generation_value = mt_rand($minimum, $maximum);
        $resources[$gen_order[$index]] += $random_generation_value;
        $remaining_distribution -= $random_generation_value;
    }

    if (MAX_TOTAL_RES > MAX_NORMAL_RES) {
        for ($index = MAX_NORMAL_RES; $index < MAX_TOTAL_RES; $index++) {
            $resources[] = 0;
        }
    }

    return implode('/', $resources);
}

/*
 * calcbearing - calculates bearing 0-359
 *  from source to target.
 *  formation of params is b,x,y
 *  North = 0, east = 90, south = 180, west = 270
 *
 */
function calcbearing($target, $source) {
    // if blank location given or both the same return 0
    $bearing = 0;

    if (($source != "") && ($target != "") && ($source != $target)) {
        $sloc = explode(".", $source);
        $tloc = explode(".", $target);

        // transform block coords into world coords
        $b1x = ($sloc[0] % MAXROWBLOCKS);
        $b1y = floor($sloc[0] / MAXROWBLOCKS);
        $b2x = ($tloc[0] % MAXROWBLOCKS);
        $b2y = floor($tloc[0] / MAXROWBLOCKS);
        $sloc[1] += (BLOCKSIZE * $b1x);
        $sloc[2] += (BLOCKSIZE * $b1y);
        $tloc[1] += (BLOCKSIZE * $b2x);
        $tloc[2] += (BLOCKSIZE * $b2y);

        // even columns are shifted up half a step
        if (($sloc[1] & 1) == 0) {
            $sloc[2] -= 0.5;
        }
        if (($tloc[1] & 1) == 0) {
            $tloc[2] -= 0.5;
        }
        $sdelta = array(3);
        for ($idx = 1; $idx < 3; $idx++) {
            $sloc[$idx] += WORLDSIZE;
            $tloc[$idx] += WORLDSIZE;
            if ($idx > 1) {
                $sloc[$idx] *= 2;
                $tloc[$idx] *= 2;
            }
            $sdelta[$idx] = $sloc[$idx] - $tloc[$idx];
        }
        if ($sdelta[2] != 0) {
            $sbearing = rad2deg(atan($sdelta[1] / $sdelta[2]));
        }
        if ($sloc[1] >= $tloc[1]) {
            // 0-180
            if ($sloc[2] == $tloc[2]) {
                $bearing = 90;
            } else if ($sloc[2] > $tloc[2]) {
                // 91-180
                $bearing = 180 - $sbearing;
            } else {
                // 0-89
                $bearing = -$sbearing;
            }
        } else {
            // 181-359
            if ($sloc[2] == $tloc[2]) {
                $bearing = 270;
            } else if ($sloc[2] > $tloc[2]) {
                // 181-269
                $bearing = 180 - $sbearing;
            } else {
                // 271-359
                $bearing = 360 - $sbearing;
            }
        }
    }
    return round($bearing);
}




/*
 * getavailableloc
 * retrieves the coords of a location that doesn't have an owner
 *  block can be numeric block of display block
 *
 * return:  coords string in formation "block.x.y"
 *          null if no available block is found.
 *
 */
function getavailableloc($block) {
    global $mysqlidb;
    $found = null;

    mt_srand();
    $startx = mt_rand(0, MAXBLOCK);
    $starty = mt_rand(0, MAXBLOCK);

    $locx = $startx;
    $locy = $starty;

    while (!$found) {
        // location must be uncontrolled, empty and with no conflict in progress
        $query = "select location from world where (location='$block.$locx.$locy' or dlocation='$block.$locx.$locy') and o_type=0 and state='' and controller='none'";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            // create b,x,y string
            $found = "$block.$locx.$locy";
        }
        else {
            // just walk along from initial random spot to find a location
            $locx++;
            if ($locx > MAXBLOCK) {
                $locx = 0;
                $locy++;
                if ($locy > MAXBLOCK) {
                    $locy = 0;
                }
            }
            if (($locx == $startx) && ($locy == $starty)) {
                // nothing available in this entire block
                postlog ("World block $block is full, can not find available location");
                break;
            }
        }
    }
    return $found;
}


/*
 * leveltoclass
 *  transform a base or location level to a class
 *      returns
 *          0 if level is null
 *          1 if level is 1-4
 *          2 if level is 5-8
 *          3 if level is 9-12
 *          4 if level is 13-16
 */
function leveltoclass($level) {
    if (!$level) {
        return 0;
    }
    if ($level < 1) {
        $level = 1;
    } else if ($level > 16) {
        $level = 16;
    }
    return floor(($level + 3) / 4);
}

/*
 * calclocationclass
 *  determine class of location based on resource generation amounts
 *  $gen can either be an array of generation rates or single sum of gen rates
 *  if $gen is null then returns 0
 *
 *  $totalgen is expected to range from 0 to 4000, leveltoclass will limit
 *      range of result
 */
function calclocationclass($gen) {
    if (!$gen) {
        return 0;
    }
    $totalgen = 0;
    if (is_array($gen)) {
        foreach ($gen as $rg) {
            $totalgen += floor($rg);
        }
    } else {
        $totalgen = floor($gen);
    }

    return (leveltoclass(($totalgen + 125) / 250));
}

/*
 * getaffiliation
 *   determines affliation of ai to controller
 */
function getaffiliation($ai, $myalliance, $controller, $theiralliance, &$prelations, &$arelations) {
    global $mysqlidb;

    $affiliation = "";

    if ($controller == "rogue") {
        $theiralliance = "";
        $affiliation = "R";
    } else if ($controller == "none") {
        $theiralliance = "";
        $affiliation = "N";
    } else if ($controller == "system") {
        $theiralliance = "";
        $affiliation = "S";
    } else if ($controller == strtolower($ai)) {
        $theiralliance = $_SESSION["alliance"];
        $affiliation = "M";
    } else {
        if ($theiralliance == "NULL") {
            $theiralliance = "";
            $affiliation = "O";
        } else if (key_exists($theiralliance, $arelations)) {
            $affiliation = $arelations[$theiralliance];
        } else {
            $query = "select status from relations where source='$myalliance' and target='$theiralliance' and type=2;";
            $arresult = $mysqlidb->query($query);
            if ($arresult && ($arresult->num_rows > 0)) {
                $arrow = $arresult->fetch_row();
                if ($arrow[0] == 1) {
                    $affiliation = "F";
                } else if ($arrow[0] == 2) {
                    $affiliation = "E";
                }
            }
            $arelations[$theiralliance] = $affiliation;
        }
        // Player relations over ride alliance relations
        if (key_exists($controller, $prelations)) {
            $affiliation = $prelations[$controller];
        } else {
            $query = "select status from relations where source='$ai' and target='$controller' and type=1;";
            $prresult = $mysqlidb->query($query);
            if ($prresult && ($prresult->num_rows > 0)) {
                $prrow = $prresult->fetch_row();
                if ($prrow[0] == 1) {
                    $affiliation = "F";
                } else if ($prrow[0] == 2) {
                    $affiliation = "E";
                }
            }
            $prelations[$controller] = $affiliation;
        }
    }
    return $affiliation;
}

/*
 * getlocinfo: retrieve info about a specific map location
 *              if controller is same as name then get detailed info
 *              otherwise just summary info
 *      if showinfo is true then includes distance to base information.
 *  if showinfo is false then locs is a comma separate list of locations
 *      of formation "'b.x.y','b.x.y',..." otherwise is just single
 *      location with no single quotes
 *
 * return:  "" on failure
 *  otherwise
 *      format is Key|b.x.y|controller|(L=loc, B=base)|city name|alliance|affiliation|status|level|
 *                      fuel gen|metal gen|mineral gen|crystal gen|drones needed|
 *                      loc fuel store|loc metal store|loc mineral store|loc crystal store|loc scrap store|
 *                      base fuel store|base metal store|base mineral store|base crystal store|base scrap store|
 *                      components|
 *                      <distances to 10 bases>|
 *                      chron 1|chron 2|chron n...|
 *                      form 1| form 2|form n...|
 *  Key is 'D' if controller == $ai else 'S'
 *  if o_type is 2-5 then is minor control point in which case controller is organization, loc class is blanked
 *  if o_type is 6-9 then is major control point in which case controller is organization, loc class is blanked
 *
 */
function getlocinfo ($ai, $dloc, $bases) {
    global $mysqlidb;

    $resstr = "";
    $prelations = array($ai=>"M");
    $myalliance = "";
    if (key_exists("alliance", $_SESSION)) {
        $myalliance = $_SESSION["alliance"];
    }
    $arelations = array($myalliance=>"A");

    $qparams = "coalesce(bases.controller,world.controller) as controller,"
            . "player.alliance,world.location as location,"
            . "world.state as state,bases.level,o_type,"
            . "coalesce(bases.res_store,world.res_store) as res_store,"
            . "coalesce(bases.components,world.components) as components,"
            . "world.res_store as loc_store,res_limit,"
            . "bases.name,world.res_gen,bases.drones,player.rank,bases.res_gen";
    $query = "select $qparams from bases right join world on (bases.location=world.location)"
                . " left join player on (world.controller=player.name)"
                . " where world.dlocation='$dloc'";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_row();
        $controller = $row[0];
        $alliance = $row[1];
        $location = $row[2];
        $state = $row[3];
        $blevel = $row[4];
        $o_type = $row[5];
        $res_store = explode("/", $row[6]);
        $components = $row[7];
        $loc_store = explode("/", $row[8]);
        $res_limit = explode("/", $row[9]);
        $bname = $row[10];
        $lres_gen = explode("/", $row[11]);
        $drones = $row[12];
        $rank = $row[13];
        $basegen = explode("/", $row[14]);

        $iscontrolpoint = false;
        if (($o_type >= 2) && ($o_type <= 9)) {
            $iscontrolpoint = true;
            $checkalliance = $controller;
        } else {
            $checkalliance = $alliance;
        }

        // get list of chronicles applicable for $ai and location
        $chronicles = getchroniclelist($ai, 0, "", $location);
        // get list of formations applicable for $ai at location
        $formations = getformationsatloc($ai, $location);

        // need array initialized for 32 + count($chronicles) + count(formations)
        // put one extra on end because last value gets lost sometimes
        $size = 35 + count($chronicles) + count($formations);
        $arr = array($size);
        for ($ii = 0; $ii < $size; $ii++) {
            $arr[$ii] = "";
        }
        $lwrcontroller = strtolower($controller);

        $affiliation = getaffiliation($ai, $myalliance, $lwrcontroller, $checkalliance, $prelations, $arelations);

        if ($controller == $ai) {
            $arr[0] = "D";
        } else {
            $arr[0] = "S";
        }
        $arr[1] = "$location:$dloc";
        if (((floor($rank) & 1) != 0) && ($iscontrolpoint == false)) {
            $arr[2] = "(GM)$controller";
        } else {
            $arr[2] = $controller;
        }

        $arr[5] = $alliance;
        $arr[6] = $affiliation;
        if ($state == "") {
            $arr[7] = $state; // conflict state
        } else {
            $arr[7] = formatduration($state);
        }
        $arr[8] = calclocationclass($lres_gen);

        switch ($o_type) {
            case 0:
                $arr[3] = "L";
                if ($lwrcontroller == strtolower($ai)) {
                    // detailed info
                    for ($idx = 0; $idx < MAX_NORMAL_RES; $idx++) {
                        $arr[9+$idx] = "/".$lres_gen[$idx];
                    }
                    for ($idx = 0; $idx < MAX_TOTAL_RES; $idx++) {
                        $arr[19+$idx] = $res_store[$idx];
                    }
                }
                break;
            case 1:
                $arr[3] = "B";
                $arr[4] = $bname;
                $arr[8] .= ":" . leveltoclass($blevel);
                if ($lwrcontroller != "rogue") {
                    if ($affiliation == "M") {  // fill in resource storage if this is my base
                        $arr[0] = "D"; // details only for my base
                        $arr[8] .= ":" . $blevel;

                        $pre = "/";
                        $droneshave = getdronecount($drones, DRONE_WORKR_MIN);
                        if ($basegen[MAX_NORMAL_RES] >= $droneshave) {
                            $pre = "Y/"; // insufficient drones for full productions
                        }
                        if (count($res_store) < MAX_TOTAL_RES) {
                            $res_store[MAX_TOTAL_RES-1] = 0;
                        }
                        if (count($res_limit) < MAX_TOTAL_RES) {
                            $res_limit[MAX_TOTAL_RES-1] = 0;
                        }
                        for ($idx = 0; $idx < MAX_TOTAL_RES; $idx++) {
                            if ($res_store[$idx] >= $res_limit[$idx]) {
                                // store is at or over max
                                $arr[9+$idx] = "R/";
                                $arr[14+$idx] = "R/";
                            } else if ($idx < MAX_NORMAL_RES) {
                                // only color non-scrap as yellow
                                $arr[9+$idx] = $pre;
                                $arr[14+$idx] = $pre;
                            } else {
                                $arr[14+$idx] = "/";
                            }
                            $arr[9+$idx] .= $basegen[$idx];
                            $arr[14+$idx] .= $res_store[$idx] . "/" . $res_limit[$idx];
                        }
                        // overwrite 13 because just drone counts
                        $arr[13] = $basegen[MAX_NORMAL_RES] . "/" . $droneshave;

                        for ($idx = 0; $idx < MAX_TOTAL_RES; $idx++) {
                            $arr[19+$idx] = $loc_store[$idx];
                        }
                    } // if ($affiliation == "M")
                }
                break;
            case 2:
            case 3:
            case 4:
            case 5:
                if ($lwrcontroller == "system") {
                    $arr[2] = "None";
                } else {
                    $arr[2] .= " organization";
                }
                $arr[3] = "cp";
                $arr[8] = "";
                if (($controller == $myalliance) || ((floor($_SESSION['rank']) & 1) != 0)) {
                    // display type of control point in $arr[9]
                    $arr[9] = $o_type;
                }
                break;
            case 6:
            case 7:
            case 8:
            case 9:
                if ($lwrcontroller == "system") {
                    $arr[2] = "None";
                } else {
                    $arr[2] .= " organization";
                }
                $arr[3] = "CP";
                $arr[8] = "";
                if (($controller == $myalliance) || ((floor($_SESSION['rank']) & 1) != 0)) {
                    // display type of control point in $arr[9]
                    $arr[9] = $o_type;
                }
                break;
            case 90:
                $arr[3] = "CC";
            case 91:
                if ($arr[3] == "") {
                    $arr[3] = "cc";
                }
                $arr[2] = 'Central AI';
                $arr[8] = number_format($_SESSION["subdualratio"], 2) . "/"
                        . number_format($_SESSION["subdualratiogoal"], 2) . "/"
                        . $_SESSION["accfuel"] . "/"
                        . $_SESSION["accmetal"] . "/"
                        . $_SESSION["accmineral"] . "/"
                        . $_SESSION["accxtal"] . "/"
                        . $_SESSION["accgoal"] . "/"
                        . $_SESSION["highestopensection"];
                $arr[9] = $o_type;
                break;
            default:
                $arr[3] = "U";
                break;
        }
        $index = 24;
        // list of components
        $arr[$index] = $components;
        $index++;

        // calculate distances from each of the bases
        $basecount = 0;
        if ($bases != "") {
            foreach ($bases as $base) {
                if ($base[0] != "") {
                    $distance = calcdistance($location, $base[0]);
                    $bearing = calcbearing($location, $base[0]);
                    if ($distance > 0) {
                        $arr[$index] = "({$base[2]}) {$base[1]}:$bearing:$distance";
                    } else {
                        $arr[$index] = "";
                    }
                    $index++;
                    $basecount++;
                }
            }
        }
        while ($basecount < MAXBASES) {
            $arr[$index] = "";
            $index++;
            $basecount++;
        }
        if ($chronicles != "") {
            // list of chronicles
            foreach ($chronicles as $chron) {
                $arr[$index] = $chron;
                $index++;
            }
        }
        if ($formations != "") {
            // list of formations
            foreach ($formations as $form) {
                $arr[$index] = $form;
                $index++;
            }
        }
        $resstr = implode("|", $arr);
    }
    return $resstr;
}


/*
 * getmapinfo: retrieve info about a list of locations for map display
 *  locs is a comma separate list of locations
 *      of formation "'b.x.y','b.x.y',..."
 *
 * return:  "": failure
 *      format is b.x.y|(L=loc, B=base)|affiliation|status|level category|display loc
 */
function getmapinfo ($ai, $locs) {
    global $mysqlidb;
    $resarr = null;
    $prelations = array($ai=>"M");
    $myalliance = "";
    if (key_exists("alliance", $_SESSION)) {
        $myalliance = $_SESSION["alliance"];
    }
    $arelations = array($myalliance=>"A");

    $qparams = "coalesce(bases.controller,world.controller) as controller,"
            . "player.alliance,world.location as loc,"
            . "world.state as state,bases.level,o_type,world.dlocation as dloc";
    $query = "select $qparams from bases right join world on (bases.location=world.location)"
                . " left join player on (world.controller=player.name)"
                . " where world.location in ($locs)";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        while ($row = $result->fetch_row()) {
            $controller = $row[0];
            $alliance = $row[1];
            $location = $row[2];
            $state = $row[3];
            $blevel = $row[4];
            $o_type = $row[5];
            $dloc = $row[6];

            $arr[0] = $location;
            switch ($o_type) {
                case 0:
                    $arr[1] = "L"; // empty location
                    break;
                case 1:
                    $arr[1] = "B"; // base
                    break;
                case 2:
                case 3:
                case 4:
                case 5:
                    $arr[1] = "c"; // minor control point
                    $alliance = $controller;
                    break;
                case 6:
                case 7:
                case 8:
                case 9:
                    $arr[1] = "C"; // major control point
                    $alliance = $controller;
                    break;
                case 90:
                    $arr[1] = "S";
                    break;
                case 91:
                    $arr[1] = "s";
                    break;
                default:
                    $arr[1] = "U";
                    break;
            }
            $lwrcontroller = strtolower($controller);
            $affiliation = getaffiliation($ai, $myalliance, $lwrcontroller, $alliance, $prelations, $arelations);

            $arr[2] = $affiliation;
            $arr[3] = $state; // conflict state
            $arr[4] = leveltoclass($blevel);
            $arr[5] = $dloc;

            $resarr[$location] = implode("|", $arr);
        } // while ($row = $result->fetch_row())
    }
    return $resarr;
}


/*
 * abandonlocation - abandons control of a location controlled by a base
 */
function abandonlocation($ai, $loc, $alliance=null) {
    global $mysqlidb;
    $atime = 90;
    $otype = "";
    $controller = "";

    // make sure loc is empty and controlled by $ai
    $query = "select o_type,controller,dlocation from world where location='$loc'";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_row();
        $otype = $row[0];
        $controller = $row[1];
        $dlocation = $row[2];
    }
    if ($otype == "") {
        // invalid loc, fail silently
    } else if ($otype == 1) {
        postreport ($ai, 0, "Unable to abandon $loc as location contains base or has base under construction");
    } else if ($otype > 1) {
        if ($alliance == null) {
            $alliance = new Alliance();
            $alliance->open($_SESSION["alliance"]);
        }
        if (!$alliance->is_officer($ai, DIRECTOR) || ($_SESSION["alliance"] != $controller)) {
            postreport ($ai, 0, "Unable to abandon $loc as control points may only be abandoned by directors of controlling organization");
        } else {
            // insert timed event to abandon control point
            createtimedentry($atime, TIMER_ABANDON_LOC, $ai, "", "$loc:$dlocation", 0,
                                    "", "", "", "", "", "");
            postreport ($ai, 0, "Abandoning Control Point at $loc, etc " . formatduration($atime));
        }
    } else if ($controller == $ai) {
        // find base which controlls this loc
        $controlledbyloc = "";
        $controlledbydloc = "";

        $query = "select location,locs,dlocation from bases where controller='$ai';";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            while ((($row = $result->fetch_row()) != null) && ($controlledbyloc == "")) {
                $bloc = $row[0];
                $locs = $row[1];
                $bdloc = $row[2];
                if ($locs != "") {
                    $locarr = explode(",", $locs);
                    for ($idx = 0; $idx < count($locarr); $idx++) {
                        $larr = explode(":", $locarr[$idx]);
                        if ($loc == $larr[0]) {
                            $controlledbyloc = $bloc;
                            $controlledbydloc = $bdloc;
                            break;
                        }
                    }
                }
            }
        }
        // insert timed event to abandon loc
        if ($controlledbyloc != "") {
            createtimedentry($atime, TIMER_ABANDON_LOC, $ai, "$controlledbyloc:$controlledbydloc", "$loc:$dlocation", 0,
                                    "", "", "", "", "", "");
            postreport ($ai, 0, "Abandoning $dlocation controlled via base at $controlledbydloc, etc " . formatduration($atime));
        }
    }
}


/*
 * calc delta between hex cells x1,y1 and x2,y2
 *
 * No longer world wraps so these first 2 comparisons are not done
 * if (∆x > W2) then ∆x = W - ∆x;
 * if (∆y > W2) then ∆y = W - ∆y;
 *      where:  W = width of the world
 *              W2 = half width of the world
 *
 *  if ∆x == 0 then dist = abs(∆y)
 *  else if ∆y == 0 then dist = abs(∆x)
 *  else if (∆x/2) < ∆y then dist = abs(∆x) + (abs(∆y) - floor(abs(∆x)/2))
 *          but if (row is odd to even or even to odd
 *                  and first loc is above second) then subtract one from dist
 *  else if (∆x/2) == ∆y then dist = abs(∆x)
 *  else dist = abs(∆x) + abs(∆y)
 */
function calchexdelta($x1, $y1, $x2, $y2) {
    $deltax = abs($x1 - $x2);
    $deltay = abs($y1 - $y2);

    if ($deltax == 0) {
        $dist = $deltay;
    } else if ($deltay == 0) {
        $dist = $deltax;
    } else {
        $deltax2 = floor(($deltax+1) / 2);
        if ((($x1 % 2) == 0) && (($x2 % 2) == 1) && ($y1 < $y2)) {
            $deltay++;
        }
        if ((($x1 % 2) == 1) && (($x2 % 2) == 0) && ($y1 > $y2)) {
            $deltay++;
        }
        if ($deltay <= $deltax2) {
            $dist = $deltax;
        } else {
            $dist = $deltax + ($deltay - $deltax2);
        }
    }
    return floor($dist);
}


/*
 * calcdistance - calculates distance between two coordinates
 *  locations are b.x.y formatted
 */
function calcdistance($loc1, $loc2) {
    // if blank location given just return 0
    if (($loc1 == "") || ($loc2 == "")) {
        return 0;
    }

    $ploc1 = explode(".", $loc1);
    $ploc2 = explode(".", $loc2);

    if ($ploc1[0] != $ploc2[0]) {
        $b1x = ($ploc1[0] % MAXROWBLOCKS);
        $b1y = floor($ploc1[0] / MAXROWBLOCKS);
        $b2x = ($ploc2[0] % MAXROWBLOCKS);
        $b2y = floor($ploc2[0] / MAXROWBLOCKS);

        // transform block coords into world coords
        $ploc1[1] += (BLOCKSIZE * $b1x);
        $ploc1[2] += (BLOCKSIZE * $b1y);
        $ploc2[1] += (BLOCKSIZE * $b2x);
        $ploc2[2] += (BLOCKSIZE * $b2y);
    }
    return (calchexdelta($ploc1[1], $ploc1[2], $ploc2[1], $ploc2[2]) * LOCSIZEKM);
}



/*
 * shiftcoords - adjusts world coordinates given by supplied delta.
 *      delta will be truncated to int if it is a float value.
 *
 *      returns structure containing the resultant block,x,y array
 */
function shiftcoords($locb, $locx, $locy, $deltax, $deltay) {
    $newb = $locb;
    $newx = $locx + floor($deltax);
    $offmap = false;
    if ($newx < 0) {
        // stepped into block to left
        if (($newb % MAXROWBLOCKS) == 0) {
            // already at left most block, set to left edge
            $newx = 0;
            $offmap = true;
        } else {
            $newx += BLOCKSIZE;
            $newb--;
        }
    } else if ($newx >= BLOCKSIZE) {
        // stepped into block to right
        $newx -= BLOCKSIZE;
        $newb++;
        if (($newb % MAXROWBLOCKS) == 0) {
            // at right most block, set to right edge
            $newb--;
            $newx = BLOCKSIZE - 1;
            $offmap = true;
        }
    }

    $newy = $locy + floor($deltay);
    if ($newy < 0) {
        // stepped into block above this one
        if ($newb < MAXROWBLOCKS) {
            // at top row, set to top of block
            $newy = 0;
            $offmap = true;
        } else {
            $newb -= MAXROWBLOCKS;
            $newy += BLOCKSIZE;
        }
    }
    else if ($newy >= BLOCKSIZE) {
        // stepped into block below this one
        if ($newb > (MAXBLOCK - MAXROWBLOCKS)) {
            // in bottom row, set to bottom of block
            $newy = BLOCKSIZE - 1;
            $offmap = true;
        } else {
            $newy -= BLOCKSIZE;
            $newb += MAXROWBLOCKS;
        }
    }

    // put resultant coords in array for return
    return array($newb, $newx, $newy, $offmap);
}

/*
 * convertdloctoloc
 *  converts display location block number to internal coordinate loc value
 *  if very first character is not alpha then returns loc unchanged
 *  caches conversions
 */
function convertdloctoloc($dloc) {
    global $mysqlidb;

    if (!array_key_exists("convertlocs", $_SESSION)) {
        $_SESSION["convertlocs"] = array();
    }

    $loc = $dloc;
    $larr = explode(".", $dloc);
    if (count($larr) < 3) {
        // too short - invalid
        $loc = "1378.0.0"; // coord loc of A3.0.0
    } else {
        $c1 = substr($larr[0], 0, 1);
        if (!is_numeric($c1)) {
            if (array_key_exists($dloc, $_SESSION["convertlocs"])) {
                $loc = $_SESSION["convertlocs"][$dloc]; // get from cache
            } else {
                // is display loc so convert to internal loc
                // validate it first
                $cidx = ord($c1) - ord('A');
                if ($cidx < 0) {
                    $cidx = 0;
                }
                $nidx = substr($larr[0], 1);
                $nmax = (8*($cidx + 1) - 5);
                if (($nidx == "") || ($nidx < 0) || ($nidx > $nmax)) {
                    $nidx = 0;
                }
                $larr[0] = chr(ord('A') + $cidx) . $nidx;
                $dloc = implode(".", $larr);
                $query = "select location from world where dlocation='$dloc'";
                $result = $mysqlidb->query($query);
                if ($result && ($result->num_rows > 0)) {
                    $row = $result->fetch_row();
                    $loc = $row[0];
                } else {
                    $loc = "1378.0.0"; // coord loc of A3.0.0
                }
                $_SESSION["convertlocs"][$dloc] = $loc; // save in cache
            }
        }
    }
    return ($loc);
}


/*
 * mapshow: display map info centered around given coords
 *  uses zoom to determine dimensions of map.
 *  if realmap is true then creates game play image map
 *          if false then just creates sequence of items with lable of "b,x,y"
 *          and value of location info in the format defined in getlocinfo
 *  Actual placement will be done on client side so don't care about physical
 *      positions. Also, if realmap, do max possible map dimension
 *
 * return:  none
 */
function mapshow ($ai, $request, $cloc, $force) {

    $ops = explode("-", $request);
    switch ($ops[0]) {
        case "locinfo":
            // get location and names of all bases controlled by ai for distance
            //  calculations
            for ($idx = 0; $idx < MAXBASES; $idx++) {
                $bases[$idx] = array("", "", "");
            }
            $basecount = 0;

            if (is_array($_SESSION["bases"])) {
                foreach ($_SESSION["bases"] as $base) {
                    $basecount++;
                    $bases[$basecount][0] = $base["location"];
                    $bases[$basecount][1] = $base["name"];
                    $bases[$basecount][2] = $base["dlocation"];
                }
            }
            $locstr = getlocinfo($ai, $ops[1], $bases, true);
            echo $locstr . " \n";
            break;
        case "datapoll":
            $cloc = convertdloctoloc($cloc);
            $clocs = explode(".", $cloc);
            $locstr = "";
            $thisloc = "";
            $sendidx = 0;
            $sendorder = null;
            for ($row = MAPSTART; $row <= MAPEND; $row++) {
                for ($col = MAPSTART; $col <= MAPEND; $col++) {
                    // get coords of this point
                    $newloc = shiftcoords($clocs[0], $clocs[1], $clocs[2], $col, $row);
                    if ($newloc[3] == false) { // is valid loc not off edge of map
                        $thisloc = "$newloc[0].$newloc[1].$newloc[2]";
                        if ($locstr != "") {
                            $locstr .= ",";
                        }
                        $locstr .= "'$thisloc'";
                    } else {
                        $thisloc = "";
                    }
                    $sendorder[$sendidx][0] = $thisloc;
                    $sendorder[$sendidx][1] = ($col + MAPEND) . "," . ($row + MAPEND);
                    $sendidx++;
                }
            }
            $locarr = getmapinfo($ai, $locstr);
            $locdata = Array();
            $mapidx = 0;
            $locdata[$mapidx] = "MC|$cloc"; // center location of map is first
            $mapidx++;
            foreach ($sendorder as $loc) {
                $locdata[$mapidx] = "MP|" . $loc[1] . "|";
                if ($loc[0] == "") {
                    $locdata[$mapidx] .= "|||||";
                } else {
                    $locdata[$mapidx] .=  $locarr[$loc[0]];
                }
                $mapidx++;
            }
            printdeltas("map", 0, (($force != "0") ? false : true), $locdata);
            break;
    }
}

/*
 * getblockinfo
 *  retrieve info about all locations in a block
 *  returns array of strings, one for each row in specified block
 *      each location is represented by a single charater in the string
 *  R = Rogue base
 *  _ = uncontrolled empty location
 *  M = $ai's bases
 *  m = $ai's controlled locations
 *  A = alliance bases
 *  a = alliance controlled locations
 *  F = friend bases
 *  f = friend controlled locations
 *  E = enemy bases
 *  e = enemy controlled locations
 *  O = other bases
 *  o = other uncontrolled locations
 */

function getblockinfo($ai, $block) {
    global $mysqlidb;
    $lines = null;

    if (enhancedfeatureaccess()) {
        $world = null;
        $prelations = array($ai=>"M");
        $myalliance = "";
        if (key_exists("alliance", $_SESSION)) {
            $myalliance = $_SESSION["alliance"];
        }
        $arelations = array($myalliance=>"A");

        $qparams = "coalesce(bases.controller,world.controller) as controller,"
                . "player.alliance,world.location as location,o_type";
        $query = "select $qparams from bases right join world on (bases.location=world.location)"
                    . " left join player on (world.controller=player.name) where world.controller!='none' and block=$block";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            while ($row = $result->fetch_row()) {
                $controller = $row[0];
                $lwrcontroller = strtolower($controller);
                $alliance = $row[1];
                $location = $row[2];

                switch ($row[3]) {
                    case 0:
                        $type = "L";
                        break;
                    case 1:
                        $type = "B";
                        break;
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                    case 9:
                        $type = "C";
                        $alliance = $controller;
                        break;
                    default:
                        $type = "U";
                        break;
                }

                $affiliation = getaffiliation($ai, $myalliance, $lwrcontroller, $alliance, $prelations, $arelations);
                if ($type == "L") {
                    $affiliation = strtolower($affiliation); // lowercase to flag as not base
                }

                $locparts = explode(".", $location);
                $world[floor($locparts[1])][floor($locparts[2])] = $affiliation;
            } // while ($row = $result->fetch_row())
            $lineidx = 0;
            for ($locy = 0; $locy < BLOCKSIZE; $locy++) {
                // top to bottom all rows in a block
                $lines[$lineidx] = "";
                for ($locx = 0; $locx < BLOCKSIZE; $locx++) {
                    // one row in a block
                    if (isset($world[$locx]) && isset($world[$locx][$locy])) {
                        $lines[$lineidx] .= $world[$locx][$locy];
                    } else {
                        $lines[$lineidx] .= "_";
                    }
                }
                $lineidx++;
            }
        }
    }
    return $lines;
}


?>

