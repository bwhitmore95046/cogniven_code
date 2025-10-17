<?php
/*
 * Functions to deal with recipe retrieval
 * Author: Chris Bryant
 */

/*
 * getrawrecipe -
 *  retrieves recipe from cache or data base with no adjustements
 *
 *  parameters:
 *      type: type of recipe (RECIPE_TYPE_... from globals.php)
 *      ident: identifier of specific item (from globals.php)
 *      level: level of recipe to retrieve
 */
function getrawrecipe($type, $ident, $level) {
    global $mysqlidb;

    $recipe = null;
    $cachekey = "recipe-$type-$ident-$level";
    if (!array_key_exists("recipes", $_SESSION)) {
        $_SESSION["recipes"] = array();
    }

    if (array_key_exists($cachekey, $_SESSION["recipes"]) && ($_SESSION["recipes"][$cachekey] != "")) {
        $recipe = $_SESSION["recipes"][$cachekey];
    } else {
        // get recipe from data base
        $query = "select * from recipes where type='$type' and ident=$ident and level=$level";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            $row = $result->fetch_assoc();
            foreach ($row as $key=>$value) {
                $_SESSION["recipes"][$cachekey][$key] = $value;
            }
            $recipe = $_SESSION["recipes"][$cachekey];
        }
    }

    return $recipe;
}

/*
 * getrecipestats -
 *  retrieves recipe stat string from cache or data base with no adjustements
 *
 *  parameters:
 *      type: type of recipe (RECIPE_TYPE_... from globals.php)
 *      ident: identifier of specific item (from globals.php)
 *      level: level of recipe to retrieve
 */
function getrecipestats($type, $ident, $level) {
    if (!array_key_exists("recipes", $_SESSION)) {
        $_SESSION["recipes"] = array();
    }

    $recipe = getrawrecipe($type, $ident, $level);
    if ($recipe) {
        return $recipe["stats"];
    }
    return "";
}

/*
 * getrecipecarrierspace -
 *  retrieves recipe stat string from cache or data base with no adjustements
 *
 *  parameters:
 *      type: type of recipe (RECIPE_TYPE_... from globals.php)
 *      ident: identifier of specific item (from globals.php)
 *      level: level of recipe to retrieve
 */
function getrecipecarrierspace($type, $ident, $level) {
    $space = 0;

    if (!array_key_exists("recipes", $_SESSION)) {
        $_SESSION["recipes"] = array();
    }

    $recipe = getrawrecipe($type, $ident, $level);
    if (($recipe) && ($recipe["carrierspace"] != "")) {
        $space = floor($recipe["carrierspace"]);
    }
    return $space;
}

/*
 * getrecipelist -
 *  retrieved list of idents for a recipe set in ascending display order
 *
 *  parameters:
 *      type: type of recipe (RECIPE_TYPE_... from globals.php)
 */
function getrecipelist($type) {
    global $mysqlidb;

    $list = "";
    $cachekey = "recipe-list-$type";
    if (!array_key_exists("recipes", $_SESSION)) {
        $_SESSION["recipes"] = array();
    }

    if (array_key_exists($cachekey, $_SESSION["recipes"]) && ($_SESSION["recipes"][$cachekey] != "")) {
        $list = $_SESSION["recipes"][$cachekey];
    } else {
        // get list from data base
        $query = "select distinct ident from recipes where type='$type' order by dorder asc";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            while (($row = $result->fetch_row()) != null) {
                if ($list != "") {
                    $list .= ",";
                }
                $list .= $row[0];
            }
        }
        $_SESSION["recipes"][$cachekey] = $list;
    }

    return $list;
}

/*
 * getrecipedaistat
 *  returns array of two values
 *      first is stat value from drudgeai with specified role. zero if not found
 *      second is entry id of drudgeai, -1 if not found
 */
function getrecipedaistat($location, $drudgeais, $role) {
    global $mysqlidb;
    global $daistatbyrole;

    $daistats = array(0, -1);

    // adjust by drudgeai stat
    if (($role < 1) || ($role >= count($daistatbyrole))) {
        $role = 0; // invalid role defaults to idle
    }
    $dairolestat = $daistatbyrole[$role];
    $daicachekey = "recipe-dai-$location-$role-$dairolestat";

    if ($role > 0) {
        if (!array_key_exists("recipes", $_SESSION)) {
            $_SESSION["recipes"] = array();
        }

        // cached value will be cleared by updatedrudgeai
        if (array_key_exists($daicachekey, $_SESSION["recipes"]) && ($_SESSION["recipes"][$daicachekey] != "")) {
            $daiarr = explode(":", $_SESSION["recipes"][$daicachekey]);
            $daistats[0] = $daiarr[0];
            $daistats[1] = $daiarr[1];
        } else {
            $dlist = trim($drudgeais, ", ");
            if ($dlist != "") {
                $query = "select entry,$dairolestat from drudgeai where entry in ($dlist) and role=$role";
                $result = $mysqlidb->query($query);
                if ($result && ($result->num_rows > 0)) {
                    $row = $result->fetch_row();
                    $daistats[1] = $row[0];
                    $daistats[0] = $row[1];
                }
            }
            // set cache even if above query failed
            $_SESSION["recipes"][$daicachekey] = "{$daistats[0]}:{$daistats[1]}";
        }
    }
    return $daistats;
}


/*
 * getrecipeline -
 *  returns formated string with recipe adjusted for skills and drudgeai stats
 *      format "tag|type|busy|max level|module level|fuel|metal|mineral|xtal|time|comps|preqs|base loc|other info"
 *
 *  parameters:
 *      ai: ai name
 *      type: type of recipe (RECIPE_TYPE_... from globals.php)
 *      ident: identifier of specific item (from globals.php)
 *      level: level of recipe to retrieve
 *      tag: string to tag line with
 *      busy: busy flag
 *      maxlevel: max level for recipe
 *      base: base info array
 *      tech: tech to use to adjust resources and time
 *      role: drudgeai role to use to locate drudgeai skill to apply
 *
 */
function getrecipeline($ai, $type, $ident, $level, $tag, $busy, $maxlevel, &$base, $tech, $role) {
    $resstr = "";

    if (!array_key_exists("recipes", $_SESSION)) {
        $_SESSION["recipes"] = array();
    }
    $nextlevel = $level + 1;

    $recipe = getrawrecipe($type, $ident, $nextlevel);
    if ($recipe) {
        // check each preq to see if met
        $preqstr = "";
        if (($recipe["preqs"] != "") && array_key_exists("level", $base) && array_key_exists("infra", $base)) {
            $modpreqs = explode(";", $recipe["preqs"]);
            foreach ($modpreqs as $mpstr) {
                $thisflag = meetspreq($ai, $mpstr, $nextlevel, $base["level"], $base["infra"]);
                if ($preqstr != "") {
                    $preqstr .= ";";
                }
                $preqstr .= "$mpstr:$thisflag";
            }
        }

        // adjust resources based on skill
        $mult = 1.0 / gettechmult($ai, "", $tech);

        if (array_key_exists("location", $base) && array_key_exists("drudgeais", $base)) {
            // adjust by drudgeai stat
            $daistats = getrecipedaistat($base['location'], $base["drudgeais"], $role);
            // decrease multipler by 1/8% per stat point
            $mult -= ($mult * floor($daistats[0])) / 800;
            if ($mult < 0.1) {
                $mult = 0.1; // bottom out at 10%
            }
        }

        $res = explode("/", $recipe["resources"]);
        for ($idx = 0; $idx < MAX_TOTAL_RES; $idx++) {
            $thisres = floor($res[$idx]);
            if ($thisres > 0) {
                $res[$idx] = round($thisres * $mult);
                if ($res[$idx] < 1) {
                    $res[$idx] = 1;
                }
            }
        }
        $compstr = $recipe["components"];
        if ($recipe["drones"] != "") {
            if ($compstr != "") {
                $compstr .= ";{$recipe['drones']}";
            } else {
                // compstr is blank
                $compstr = $recipe["drones"];
            }
        }
        if ($recipe["items"] != "") {
            if ($compstr != "") {
                $compstr .= ";{$recipe['items']}";
            } else {
                // compstr is blank
                $compstr = $recipe["items"];
            }
        }

        $resstr = "$tag|$ident|$busy|$maxlevel|$level|" . implode("|", $res) . "|$compstr|$preqstr";
    } else {
        // no recipe found, may be at max level
        $resstr = "$tag|$ident|$busy|$maxlevel|$level|||||||";
    }

    return $resstr;
}

/*
 * cleardairecipecache -
 *  clears cached entries for dai stats used in adjusting recipes
 *
 * parameters:
 *  baseloc: location of base in which drudgeai was updated
 */
function cleardairecipecache($baseloc) {
    if (array_key_exists("recipes", $_SESSION)) {
        foreach ($_SESSION["recipes"] as $key=>$value) {
            if (strpos($key, "recipe-dai-$baseloc") === 0) {
                $_SESSION["recipes"][$key] = "";
            }
        }
    }
}


/*
 * getrecipe -
 *  returns recipe adjusted for skills and drudgeai stats
 *
 *  parameters:
 *      ai: ai name
 *      type: type of recipe (RECIPE_TYPE_... from globals.php)
 *      ident: identifier of specific item (from globals.php)
 *      level: level of recipe to retrieve
 *      base: base associative array, requires level, infra, location and drudgeais
 *      tech: tech to use to adjust resources and time
 *      role: drudgeai role to use to locate drudgeai skill to apply
 *
 *  returns associative array with following entries
 *      resarr: array of MAX_TOTAL_RES entries
 *      comps: string of components required in format "C:type:quantity;..."
 *      items: string of items required in format "ident:0:quantity;..."
 *      drones: string of drones required in format "D:type:ident:quantity;..."
 *      notmet: string of preqs not met in format "preq type:ident:value;..."
 *      dai: entry id # for drudge ai whose stat was applied
 *      scrap: amount of scrap generated by scrapping a damaged drone
 */
function getrecipe($ai, $type, $ident, $level, &$base, $tech, $role) {
    $thisrecipe = null;
    if (!array_key_exists("recipes", $_SESSION)) {
        $_SESSION["recipes"] = array();
    }
    $nextlevel = $level + 1;

    $recipe = getrawrecipe($type, $ident, $nextlevel);
    if ($recipe) {
        // check each preq to see if met
        $preqstr = "";
        if (($recipe["preqs"] != "") && array_key_exists("level", $base) && array_key_exists("infra", $base)) {
            $modpreqs = explode(";", $recipe["preqs"]);
            foreach ($modpreqs as $mpstr) {
                $thisflag = meetspreq($ai, $mpstr, $nextlevel, $base["level"], $base["infra"]);
                if ($thisflag == 0) {
                    if ($preqstr != "") {
                        $preqstr .= ";";
                    }
                    $preqstr .= "$mpstr";
                }
            }
        }
        // adjust resources based on skill
        $mult = 1.0 / gettechmult($ai, "", $tech);

        $daistats = null;
        if (array_key_exists("location", $base) && array_key_exists("drudgeais", $base)) {
            // adjust by drudgeai stat
            $daistats = getrecipedaistat($base['location'], $base["drudgeais"], $role);
            // decrease multipler by 1/8% per stat point
            $mult -= ($mult * floor($daistats[0])) / 800;
            if ($mult < 0.1) {
                $mult = 0.1; // bottom out at 10%
            }
        }

        $res = explode("/", $recipe["resources"]);
        for ($idx = 0; $idx < MAX_TOTAL_RES; $idx++) {
            $thisres = floor($res[$idx]);
            if ($thisres > 0) {
                $res[$idx] = round($thisres * $mult);
                if ($res[$idx] < 1) {
                    $res[$idx] = 1;
                }
            }
        }
        $thisrecipe["resarr"] = $res;
        $thisrecipe["comps"] = $recipe["components"];
        $thisrecipe["items"] = $recipe["items"];
        $thisrecipe["drones"] = $recipe["drones"];
        $thisrecipe["notmet"] = $preqstr;
        if ($daistats != null) {
            $thisrecipe["dai"] = $daistats[1];
        } else {
            $thisrecipe["dai"] = -1;
        }
        $thisrecipe["scrap"] = $recipe["scrap"];
    }

    return $thisrecipe;
}

?>
