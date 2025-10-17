<?php
/*
 * Global constants and variables to control value ranges and display formats
 * Author: Chris Bryant
 */

/*
 *  THIS MUST BE DONE BEFORE ANY COOKIES ARE USED!
 *  Any time we are setting a cookie we are self-encoding spaces in names as asterixes.
 *  This is done because PHP converts url encoded spaces into underscore when the cookie is fetched.
 */
$cookie_keys = array_keys($_COOKIE);
foreach ($cookie_keys as $key) {
    if (strpos($key, '*') !== false) {
        //this cookie key has an asterix within it.  These need to be converted into spaces.
        $_COOKIE[str_replace('*', ' ', $key)] = $_COOKIE[$key];
        unset($_COOKIE[$key]);
    }
}


define ("DEBUG", false);
//Cogniven sites url information
$envportal = getenv("GAME_PORTAL_IP");
if ($envportal && ($envportal != "")) {
    define ("GAME_PORTAL_INTERNAL", $envportal);
} else {
    define ("GAME_PORTAL_INTERNAL", "192.168.4.30");
}

if (key_exists('HTTP_REFERER', $_SERVER)) {
    define ("GAME_PORTAL_EXTERNAL", $_SERVER['HTTP_REFERER']);
} else {
    define ("GAME_PORTAL_EXTERNAL", "192.168.4.30");
}

// data base access info
$envdbserver = getenv("GAME_DBSERVER_IP");
if ($envdbserver && ($envdbserver != "")) {
    define ("DBHOST", $envdbserver);
} else {
    define ("DBHOST", "localhost");
}
$envdbname = getenv("GAMEDBASE");
if ($envdbname && ($envdbname != "")) {
    define ("DBNAME", $envdbname);
} else {
    define ("DBNAME", "imaiy");
}
define ("DBUSER", DBNAME);
define ("DBPASS", "75171game");

// account dbase resides on game portal
define ("DBAHOST", GAME_PORTAL_INTERNAL);
define ("DBAUSER", "gamestore");
define ("DBAPASS", "75171sg");
define ("DBADBASE", "gamelogin");



/*
 * Limits and formats
 */
// map values
define ("MAPDIMEN", 29); // displayed map is 29 x 29
define ("MAPSTART", -14); // START to END = MAPDIMEN steps
define ("MAPEND", 14);

// base limits
define ("MAXBASES", 10);
define ("MAXBASELEVEL", 16);
define ("MAX_BASES_UNDER_CONSTRUCTION", 1);
define ("MAX_MODULES_UNDER_CONSTRUCTION", 1);
define ("MAX_BASE_CONDITION", 100); // Queue.has this value in CompleteBaseRepair
define ("MAX_DRONES_PER_SM_LEVEL", 50000); // drones per level of storage module
//
// chat limits and defines
define ("MINCHATPAUSE", 10); // 10 seconds between chats
define ("MAXCHAT", 400);
define ("MAXREPORT", 50);
define ("MAXMAIL", 200);
define ("MAXCHRONICLE", 200);
define ("CHANNELSYSTEM", 0);
define ("CHANNELALL", 1);
define ("CHANNELREPORT", 2);
define ("CHANNELTELL", 3);
define ("CHANNELALLIANCE", 4);
define ("CHATTYPESYS", 0);
define ("CHATTYPEGEN", 1);
define ("CHATTYPEALLIANCE", 2);
define ("CHATTYPEREPORT", 3);
define ("CHATTYPETELL", 4);
// types 99 and 96 are also displayed in the marquee
// following type used by rogue server for section status
define ("CHATTYPESYSSTATUS2", 96);
define ("CHATTYPESYSSTATUS1", 97);
// following 2 used by DAILYMAINT.php and DMCOMPLETE.php
define ("CHATTYPESYSDM2", 98);
define ("CHATTYPESYSDM1", 99);

// values for determining limits of coordinates in world
define ("MAXROWBLOCKS", 52);
define ("MAXBLOCK", 99); // block range 0-99 = 100 blocks
define ("BLOCKSIZE", 100);
define ("WORLDSIZE", 52000); // MAXROWBLOCKS * BLOCKSIZE
define ("LOCSIZEKM", 10); // number of km in each block

// starting character statsMAX_NORMAL_RES
define ("PLAYER_MAX_LEVEL", 32);
define ("STARTINGGCREDITS", 100000);
define ("STARTINGPCREDITS", 1000);
define ("STARTINGLEVEL", 1);

define ("MAX_NORMAL_RES", 4);
define ("MAX_TOTAL_RES", 5);
define ("MAX_RESGEN_RATE", 1000);

define ("MAX_DEFENSE_TYPES", 6);
define ("DEFENSE_STRUC", 1);
define ("DEFENSE_ABLAT", 2);
define ("DEFENSE_ENERS", 3);
define ("DEFENSE_PBEAM", 4);
define ("DEFENSE_LASER", 5);
define ("DEFENSE_RLGUN", 6);

define ("SECSINDAY", 86400); // 24*60*60
define ("SECSINHOUR", 3600); // 60*60
define ("SECSINMIN", 60);

// transaction fee. two formats for same value
define ("TRANSMULT", 1.01);
define ("TRANSFEE", "1%");
define ("TRANSMIN", 500);

define ("DRUDGEAI_ROLE_IDLE", 0); // idle
define ("DRUDGEAI_ROLE_RESOURCE", 1); // resource manager in base
define ("DRUDGEAI_ROLE_SENSOR", 2); // sensor manager in base
define ("DRUDGEAI_ROLE_CONST", 3); // construction manager in base
define ("DRUDGEAI_ROLE_BASE", 4); // base manager in base
define ("DRUDGEAI_ROLE_ROAM", 5); // out of base
define ("DRUDGEAI_ARRAY_SIZE", 10);
$daistatbyrole = array("", "canalysis", "cheuristics", "cmultitasking", "ccontrol", "ctactics");
define ("DRUDGEAI_STAT_MULT", 0.0025); // what each point of stat is worth

define ("RELATIONS_TYPE_PERSONAL", 1);
define ("RELATIONS_TYPE_ALLIANCE", 2);
define ("RELATIONS_TYPE_BLOCK", 3);
define ("RELATIONS_STATUS_FRIEND", 1);
define ("RELATIONS_STATUS_ENEMY", 2);
define ("RELATIONS_STATUS_BLOCK", 3);

define ("RECIPE_TYPE_COMP", "C");
define ("RECIPE_TYPE_DRONE", "D");
define ("RECIPE_TYPE_DEFENSE", "F");
define ("RECIPE_TYPE_TECH", "T");
define ("RECIPE_TYPE_MAI", "P");
define ("RECIPE_TYPE_MODULE", "M");
define ("RECIPE_TYPE_BASE", "B");
define ("RECIPE_TYPE_ROGUE", "R");
define ("RECIPE_TYPE_RES", "S");

// COMPONENT DEFINES
define ("COMPONENT_UNKNOWN", 0);
define ("COMPONENT_RSTRUT", 1);
define ("COMPONENT_APANEL", 2);
define ("COMPONENT_SHLDGEN", 3);
define ("COMPONENT_PARTGEN", 4);
define ("COMPONENT_LASER", 5);
define ("COMPONENT_RAILGUN", 6);
define ("COMPONENT_DCNTRL", 7);
define ("COMPONENT_DATAP", 8);
define ("COMPONENT_RSCAN", 9);
define ("COMPONENT_DSCAN", 10);
define ("COMPONENT_BSCAN", 11);
define ("COMPONENT_CMOUNT", 12);
define ("COMPONENT_RSCOOP", 13);
define ("COMPONENT_LRAMP", 14);
define ("COMPONENT_TIEDWN", 15);
define ("COMPONENT_RELMECH", 16);
define ("COMPONENT_TSCAN", 17);
define ("COMPONENT_WMOUNT", 18);
define ("COMPONENT_CONARM", 19);
define ("COMPONENT_MINEARM", 20);
define ("COMPONENT_DISARM", 21);
define ("COMPONENT_DAI", 22);

define ("COMPONENT_ARRAY_SIZE", 30);


// TECH DEFINES
define ("TECH_MIN_LEVEL", 1);
define ("TECH_MAX_LEVEL", 16);

define ("TECH_FUEL_EXT",      1); // it is expected that resource techs are in order
define ("TECH_METAL_EXT",     2);
define ("TECH_MINERAL_EXT",   3);
define ("TECH_XTAL_EXT",      4);
define ("TECH_RECYCLE",       5);
define ("TECH_SCOUT",         6);
define ("TECH_CONST",         7);
define ("TECH_BASE_CAP",      8);
define ("TECH_BASE_DEFENSE",  9);
define ("TECH_BASE_TACTIC",   10);
define ("TECH_RESEARCH",      11);
define ("TECH_DATA_PROC",     12);
define ("TECH_DRONE_REP",     13);
define ("TECH_DRONE_ASSY",    14);
define ("TECH_DRONE_PURPOSE", 15); // obsolete
define ("TECH_DRONE_CAP",     16);
define ("TECH_DRONE_FUEL",    17);
define ("TECH_DRONE_STORE",   18); // obsolete
define ("TECH_DRONE_MOVE",    19);
define ("TECH_DRONE_TACTIC",  20);
define ("TECH_DRONE_FLIGHT",  21); // obsolete
define ("TECH_SYNTHESIS",     22);

define ("MAX_TECH_INDEX", 22); // matches last tech value
define ("TECH_ARRAY_SIZE", 30); // must be larger than highest value above


// DRONE DEFINES
// drone min,max levels - restricted to 1/2 level of player
define ("DRONE_MIN_LEVEL", 1);
define ("DRONE_MAX_LEVEL", 16);

// type definitions
define ("DRONE_RECON", 10);
define ("DRONE_RECON_SVY", 11);
define ("DRONE_RECON_SCO", 12);
define ("DRONE_RECON_SCN", 13);

define ("DRONE_TRANS", 20);
define ("DRONE_TRANS_MAT", 21);
define ("DRONE_TRANS_GND", 22);
define ("DRONE_TRANS_AIR", 23);

define ("DRONE_FIGHT", 30);
define ("DRONE_FIGHT_LIT", 31);
define ("DRONE_FIGHT_MED", 32);
define ("DRONE_FIGHT_HVY", 33);
define ("DRONE_FIGHT_DEF", 34);

define ("DRONE_WORKR", 40);
define ("DRONE_WORKR_CON", 41);
define ("DRONE_WORKR_MIN", 42);
define ("DRONE_WORKR_SCV", 43);

define ("DRONE_ARRAY_SIZE", 50); // must be larger than highest value above
define ("DRONE_DEF_SHIELD_AMT", 100); // number of drones effected by a DRONE_FIGHT_DEF


// MODULE DEFINES
// module min,max levels - restricted to 1/2 level of player
//  PLAYER_MAX_LEVEL defined in player.php
define ("MODULE_MIN_LEVEL", 1);
define ("MODULE_MAX_LEVEL", 16);

// type definitions
//  Queue.java depends on these module numbers
define ("MODULE_NONE",        0);
define ("MODULE_CONTROL",     1); // special - level 1 provided to all new bases
define ("MODULE_PERIMETER",   2);
define ("MODULE_ASSEMBLY",    3);
define ("MODULE_REFINE",      7);
define ("MODULE_RESEARCH",    8);
define ("MODULE_STORAGE",     9);
define ("MODULE_REPAIR",      12);
define ("MODULE_RECYCLE",     13);
define ("MODULE_TRANSCEIVER", 14);
define ("MODULE_TRADING",     15);
define ("MODULE_SCAN",        16);
define ("MODULE_SYNTHESIS",   17);

define ("MODULE_ARRAY_SIZE", 20); // must be larger than highest value above

// TIMER QUEUE DEFINES
// base timer entry types
// must match constants in Queue.java
//  note: uses of sloc and tloc are important and is used to count events
//  ->adding more defines here means updating parsecentralinfo in central.js to display event params correctly
define ("TIMER_MOVEAI", 1);
define ("TIMER_LEVEL_PLAYER", 2);
define ("TIMER_SCRAP_RECYCLE", 3);
define ("TIMER_TRADE_DELIVER", 4);
define ("TIMER_CANCEL_EVENT", 5);
define ("TIMER_CENTRAL_DELIVER", 6);
define ("TIMER_REMOVEAI", 7);
define ("TIMER_MAINTANENCE", 8);
define ("TIMER_MOVE_BASE", 10);
define ("TIMER_CONST_BASE", 11);
define ("TIMER_REPAIR_BASE", 12);
define ("TIMER_REM_DEFENSE", 13);
define ("TIMER_CON_DEFENSE", 14);
define ("TIMER_CON_COMPONENT", 15);
define ("TIMER_ABANDON_BASE", 16);
define ("TIMER_ABANDON_LOC", 17);
define ("TIMER_LEVEL_MODULE", 21);
define ("TIMER_TRAIN_TECH", 23);
define ("TIMER_DRONE_ASSY", 30);
define ("TIMER_DRONE_RECY", 32);
define ("TIMER_DRONE_REPAIR", 33);
define ("TIMER_DRONE_DISMIS", 34);
// retrieval of incoming formations requires all drone movement events to be clumped together
define ("TIMER_DRONE_TRANS", 40);
define ("TIMER_DRONE_RECON", 41);
define ("TIMER_DRONE_REINF", 42);
define ("TIMER_DRONE_ATACK", 43);
define ("TIMER_DRONE_SCVNG", 44);
define ("TIMER_DRONE_RETRN", 45);
define ("TIMER_DRONE_MOVE", 46);
define ("TIMER_DRONE_RETRV", 47);
define ("TIMER_DRONE_BLITZ", 48);
define ("TIMER_DRONE_ASSLT", 49);

define ("TIMER_ARRAY_SIZE", 50); // must be larger than highest value above

// formation defines
define ("FORMATION_STATUS_SAVED", 0);
define ("FORMATION_STATUS_IDLE", 1);
define ("FORMATION_STATUS_MOVE", 2);
define ("FORMATION_STATUS_REIN", 3);
define ("FORMATION_STATUS_RETRN", 4);
define ("FORMATION_STATUS_COMBAT", 5);

define ("FORMATION_PURPOSE_TRANS", 0);
define ("FORMATION_PURPOSE_RECON", 1);
define ("FORMATION_PURPOSE_REINF", 2);
define ("FORMATION_PURPOSE_SCVNG", 3);
define ("FORMATION_PURPOSE_MOVE",  4);
define ("FORMATION_PURPOSE_RETRV", 5);
define ("FORMATION_PURPOSE_ATACK", 6);
define ("FORMATION_PURPOSE_BLITZ", 7);
define ("FORMATION_PURPOSE_ASSLT", 8);
// following two arrays must be same length
$formationpurpnames = array ("Transport", "Recon", "Reinforce", "Scavenge", "Move", "Retrieve", "Raid", "Blitz", "Raze");
$formationpurpevents = array (TIMER_DRONE_TRANS, TIMER_DRONE_RECON,
                                 TIMER_DRONE_REINF, TIMER_DRONE_SCVNG,
                                 TIMER_DRONE_MOVE,  TIMER_DRONE_RETRV,
                                 TIMER_DRONE_ATACK, TIMER_DRONE_BLITZ,
                                 TIMER_DRONE_ASSLT);

// hashed password constants
define ("DIGEST_ITERATION_COUNT", 1030);
define ("STATIC_SALT", "Cogniven.");  //must be exactly 9 characters

// alliance constants
define ("ALLIANCE_NONE", 0);
define ("ALLIANCE_PARTNERSHIP", 1);
define ("ALLIANCE_ENTERPRISE", 2);
define ("ALLIANCE_COOPERATIVE", 3);
define ("ALLIANCE_CORPORATION", 4);
define ("ALLIANCE_CONGLOMERATE", 5);

define ("ORG_ARRAY_SIZE", 10);

define ("ALLIANCE_PARTNERSHIP_MAX_MEMBERSHIP", 8);
define ("ALLIANCE_ENTERPRISE_MAX_MEMBERSHIP", 16);
define ("ALLIANCE_COOPERATIVE_MAX_MEMBERSHIP", 32);
define ("ALLIANCE_CORPORATION_MAX_MEMBERSHIP", 64);
define ("ALLIANCE_CONGLOMERATE_MAX_MEMBERSHIP", 128);

define ("ALLIANCE_PARTNERSHIP_MAX_OFFICERS", 2);
define ("ALLIANCE_ENTERPRISE_MAX_OFFICERS", 4);
define ("ALLIANCE_COOPERATIVE_MAX_OFFICERS", 8);
define ("ALLIANCE_CORPORATION_MAX_OFFICERS", 16);
define ("ALLIANCE_CONGLOMERATE_MAX_OFFICERS", 32);

define ("OWNER", 1);
define ("DIRECTOR", 2);
define ("DIPLOMAT", 3);
define ("RECRUITER", 4);
define ("WARDEN", 5);

define ("MINIMUM_LEVEL_TO_CREATE_ALLIANCE", 5);
define ("MAXIMUM_ALLIANCE_APPLICATIONS", 1);


define ("MAX_DRONES_PER_STAGE_LEVEL", "8192");

define ("MINIMUM_HOURS_BETWEEN_DELETES", 24);

//report types
define ("REPORT_GENERAL", 0);   // general type
define ("REPORT_TRADE", 1);     // trade
define ("REPORT_ALLIANCE", 2);  // alliance

/*
 * load correct string resource definitions based on player language
 */
$lang = 'en';
if (isset ($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
}
$fname = "text/names_php.inc.{$lang}";
if (file_exists($fname)) {
    include $fname;
}


/*
 * loadinis
 *  load variables from ini table
 */
function loadinis() {
    global $mysqlidb;

    $result = $mysqlidb->query("select variable,value from ini where phpneed='1'");
    if ($result && ($result->num_rows > 0)) {
        while (($row = $result->fetch_assoc()) != null) {
            $_SESSION[$row["variable"]] = $row["value"];
        }
    }
}

/*
 * printdeltas
 *  will send only the changed lines in a provided list
 *  $prikey = primary index into $_SESSIONS array for data
 *  $seckey = seconday index into array
 *  $refresh = boolean flag, if falses forces print of all lines
 *  $list = array of strings
 *
 */
function printdeltas($prikey, $seckey, $refresh, $list) {
    $count = count($list);
    // if need full update or listsinfo array doesn't exist
    //  create it and set to send all
    if (!array_key_exists($prikey, $_SESSION)) {
        $_SESSION[$prikey] = array();
        $refresh = false; // force refresh as primary key array not present
//postlog("Primary key ($prikey) missing from session ");
    } else if (!array_key_exists($seckey, $_SESSION[$prikey])) {
        $_SESSION[$prikey][$seckey] = array();
        $refresh = false; // force refresh as secondary key array not present
//postlog("Secondary key ($seckey) missing from session[$prikey] ");
    }

    if ($refresh == false) {
        for ($index = 0; $index < $count; $index++) {
            $_SESSION[$prikey][$seckey][$index] = "";
        }
    }

    $licount = count($_SESSION[$prikey][$seckey]);

    // now output only the changed lines
    for ($index = 0; $index < $count; $index++) {
        if ($licount <= $index) {
            // add new line and force it to be sent
            $_SESSION[$prikey][$seckey][$index] = "";
        }
        if ($_SESSION[$prikey][$seckey][$index] != $list[$index]) {
            $_SESSION[$prikey][$seckey][$index] = $list[$index];
//postlog("sending line $index for $prikey and $seckey");
            echo $list[$index] . "\n";
        }
    }
}


/*
 * formatduration - takes a value in seconds and formats it into a
 *      string of days, hours, minutes, seconds.
 */
function formatduration($duration) {
    $days = floor($duration / SECSINDAY);
    $hours = floor(($duration % SECSINDAY) / SECSINHOUR);
    $mins = floor(($duration % SECSINHOUR) / SECSINMIN);
    $secs = $duration % SECSINMIN;
    $result = "";
    if ($days > 0) {
        $result .= number_format($days,0);
        if ($days > 1) {
            $result .= "days ";
        } else {
            $result .= "day ";
        }
    }
    if ($hours > 0) {
        $result .= $hours;
        if ($hours > 1) {
            $result .= "hours ";
        } else {
            $result .= "hour ";
        }
    }
    if ($mins > 0) {
        $result .= $mins;
        if ($mins > 1) {
            $result .= "mins ";
        } else {
            $result .= "min ";
        }
    }
    if ($secs > 0) {
        $result .= $secs . "sec";
    }

    if ($result == "") {
        $result = "immediate";
    }
    return $result;
}


/*
 * insertTextFile - reads specified file and inserts into text stream
 *      appending <br/> to each line
 */
function insertTextFile($file) {
    $text = file($file);
    if ($text) {
        foreach ($text as $line) {
            $line = trim($line);
            print "<p>$line</p>\n";
        }
    }
}

/*
 * Bill Whitmore
 * Generates and returns a string of random alphanumeric characters of the
 *      length requested.
 */
function generateRandomString($length) {
    $characters = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $randString = "";

    for ($i = 0; $i < $length; $i++) {
        $randString .= $characters[mt_rand(0, strlen($characters) - 1)];
    }

    return $randString;
}

/*
 * Bill Whitmore
 *      seconds_to_hours($time) takes a time in seconds and breaks it down into
 * hours, minutes and seconds.
 *
 *      requirements:
 *          $time must be an integer
 *
 *      returns:
 *          array[0] = seconds
 *          array[1] = minutes
 *          array[2] = hours
 *
 *          if $time is not an integer, it returns 0 for all indices
 */
function seconds_to_hours($seconds)
{
    if (!is_integer($seconds))
    {
        return array(0, 0, 0);
    }
    $time = array(3);

    //find hours
    $time[2] = floor($seconds / (3600));  //3600 seconds in 1 hour
    $seconds %= 3600;

    $time[1] = floor($seconds / (60));  //60 seconds in 1 minute
    $seconds %= 60;

    $time[0] = $seconds;  //1 second in 1 second

    return $time;
}

/*
 * postlog - writes a line of text to the log file in logs directory
 *              the name of the file is todays date
 */
function postlog ($text) {
    $fname = "logs/" . date("Y_m_d") . ".log";
    if (!is_dir("logs")) {
        $fname = "../$fname";
    }
    file_put_contents($fname, date("H:i:s:u ") . $text . "\n", FILE_APPEND);
}

/*
 * session handler functions
 */
$email = "";
$gamekey = "";
$mysqlidb = null;
function sess_open($sess_path, $sess_name) {
    return true;
}
function sess_close() {
    return true;
}
function sess_read($sess_id) {
    global $email;
    global $gamekey;
    global $mysqlidb;
    $then = time();

    $mysqlidb = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);
    if (!$mysqlidb->connect_error) {
        $email = "";
        if (key_exists("email", $_SESSION)) {
            $email = $_SESSION["email"];
        } else if (key_exists("email", $_POST)) {
            $email = $_POST["email"];
        } else if (key_exists("email", $_GET)) {
            $email = $_GET["email"];
        }
        $gamekey = $email;
        if (key_exists("gamekey", $_SESSION) && ($_SESSION["gamekey"] != $email)) {
            $gamekey = $_SESSION["gamekey"];
        } else if (key_exists("gamekey", $_POST)) {
            $gamekey = $_POST["gamekey"];
        } else if (key_exists("gamekey", $_GET)) {
            $gamekey = $_GET["gamekey"];
        }
        $ai = "";
        if (key_exists('AI', $_GET)) {
            $ai = $_GET['AI'];
        } else if (key_exists('AI', $_POST)) {
            $ai = $_POST['AI'];
        } else if (key_exists('HTTP_REFERER', $_SERVER)) {
            $ai = substr(strstr($_SERVER['HTTP_REFERER'], "AI="), 3);
        }
        if (($email == "") && key_exists($ai.'email', $_COOKIE)) {
            $email = $_COOKIE[$ai.'email'];
        }
        if (($gamekey == "") && key_exists($ai.'gamekey', $_COOKIE)) {
            $gamekey = $_COOKIE[$ai.'gamekey'];
        }
        if ($email == "") {
            if (key_exists("side", $_POST)) {
                $email = $_POST["side"];
            }
            if (key_exists("sidg", $_POST)) {
                $gamekey = $_POST["sidg"];
            }
        }
        if (($gamekey != "") && ($email != "")) {
            $mysqlidb->commit();
            $mysqlidb->autocommit(false);
            $result = $mysqlidb->query("select * from sessions where useremail='$email' and gamekey='$gamekey' for update");
        } else {
            postlog("Can't retrieve session info without email and gamekey! '$ai':'$email':'$gamekey'");
//            postlog("sr GET: " . str_replace("\n", " ", var_export($_GET, true)));
//            postlog("sr POST: " . str_replace("\n", " ", var_export($_POST, true)));
//            postlog("sr COOKIE: " . str_replace("\n", " ", var_export($_COOKIE, true)));
//            postlog("sr SERVER: " . str_replace("\n", " ", var_export($_SERVER, true)));
            //unable to retrieve email and gamekey information from GET, POST, SESSION or COOKIE
            //redirect to allow user to log in via Game Portal
            header('Location: ' . GAME_PORTAL_EXTERNAL);
            exit();
        }
        $now = time();
        if ($mysqlidb->error != null) {
            $delta = $now - $then;
            postlog("sr ($delta): " . $mysqlidb->error . " error num: " . $mysqlidb->errno);
            postlog("sr GET: " . str_replace("\n", " ", var_export($_GET, true)));
            postlog("sr POST: " . str_replace("\n", " ", var_export($_POST, true)));
            postlog("sr COOKIE: " . str_replace("\n", " ", var_export($_COOKIE, true)));
            postlog("sr SERVER: " . str_replace("\n", " ", var_export($_SERVER, true)));
            die();
        }
        if (!$result || ($result->num_rows == 0)) {
            // remove any old sessions for this useremail
            $mysqlidb->query("delete from sessions where useremail='$email' and gamekey!='$email'");
            // create new session for email/gamekey pair
            $mysqlidb->query("insert into sessions(useremail,gamekey,touched,sdata) values('$email','$gamekey',$now,'')");
            postlog("sr new session for $ai:$email");
            return "";
        } else {
            $row = $result->fetch_assoc();
//postlog("retrieved sessions data of size " . strlen($row["sdata"]));
            return $row["sdata"];
        }
    } else {
        postlog("sr failed to connect to database server: ". DBHOST . " error=" . $mysqlidb->connect_error);
        die();
    }
}
function sess_write($sess_id, $sess_data) {
    global $email;
    global $gamekey;
    global $mysqlidb;

    $now = time();

    if (($gamekey != "") && ($email != "")) {
        $retrycnt = 1;
        while ($retrycnt > 0) {
            if (!$sess_data) {
                $sess_data = "";
            }
            $query = sprintf("update sessions set touched=$now,sdata='%s' where useremail='$email' and gamekey='$gamekey'",
                    $mysqlidb->real_escape_string($sess_data));
//postlog("save session data query size " . strlen($query));
            $mysqlidb->query($query);
            if ($mysqlidb->error == null) {
                $retrycnt = 0;
                $mysqlidb->commit();
            } else if ($retrycnt > 0) {
                postlog("sw $retrycnt: " . $mysqlidb->error . " error num: " . $mysqlidb->errno);
                $retrycnt--;
                $mysqlidb = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);
                if ($mysqlidb->connect_error) {
                    postlog("sw failed to reconnect to database server: " . $mysqlidb->connect_error);
                }
            } else {
                postlog("sw: " . $mysqlidb->error);
            }
        }
    } else {
        postlog("Can't save session info without email and gamekey! '$email':'$gamekey'");
        $mysqlidb->commit();
        die();
    }
    return true;
}
function sess_destroy($sess_id) {
    return true;
}
function sess_gc($sess_maxlifetime) {
    // delete records older than max lifetime
    $cut = time() - $sess_maxlifetime;
    // must connect here as cannot access globals nor session info
    $mysqlidbgc = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);
    if ($mysqlidbgc) {
        $mysqlidbgc->query("delete from sessions where touched<$cut");
        $mysqlidbgc->commit();
        $mysqlidbgc->kill($mysqlidbgc->thread_id);
        $mysqlidbgc->close();
    }
    return true;
}
 // don't start session if run from command line (or cron)
 if (isset($_SERVER{'HTTP_HOST'})) {
    session_set_save_handler("sess_open", "sess_close", "sess_read",
                            "sess_write", "sess_destroy", "sess_gc");
    session_start();
 }

?>

