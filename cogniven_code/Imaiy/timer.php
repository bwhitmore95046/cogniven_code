<?php
/*
 * Functions to deal with timer queue entries
 * Author: Chris Bryant
 */


/*
 * createtimedentry - puts entry into timer_queue table to execute an action
 *  some time in the future.
 *  duration - delay until action complete in seconds, used to determine % complete based on time until dueTime
*  type - type of action - see defines in globals.php
 *  ai - name of player initiating action
 *  sloc - source location of action in b,x,y format
 *  tloc - target location of action in b,x,y format
 *  slot - parameter depending on type, usually id of skill, component, etc...
 *  dai - id of drudgeai whose stat was applied when task begun, gets exp when completed, nothing if canceled
 *  drones - drones involved in construction to be returned when complete or canceled
 *               or formation key if type is drone movement
 *  res - resources involved in level/construction prorated amount returned if canceled
 *                  or scrap being recycled - prorated and prorated results if canceled
 *  comp - components involved in construction - prorated amount lost if canceled
 *  items - items involved in construction - lost if canceled
 *  results - components for synthesis, x/x increment for repair of base, drones for assemble or purpose,
 *              defenses for add or remove defense, formation key for formation movements, tech key for research
 *              module type for level/delevel module
 *              format must be "<text description of result>;<internal data representation>"
 *  edue - optional random rogue drones encounter time - no effect if not drone movement type event
 */
function createtimedentry($duration, $type, $ai, $sloc, $tloc, $slot, $dai, $drones, $res, $comp, $items, $results, $edue=0) {
    global $mysqlidb;
    // put entry into timer queue
    if (($edue < 150) || (($duration - $edue) < 150)) {
        // never have encounter within 2 min of source or target
        $edue = 10 * $duration; // make sure this doesn't occur till well after event is done.
    }
    // make sure int fields have a value
    if (($dai == null) || ($dai == "")) {
        $dai = "0";
    }
    if (($slot == null) || ($slot == "")) {
        $slot = 0;
    }
    $srcarr = explode(":", $sloc);
    if (count($srcarr) < 2) {
        $srcarr[1] = $srcarr[0];
    }
    $trgarr = explode(":", $tloc);
    if (count($trgarr) < 2) {
        $trgarr[1] = $trgarr[0];
    }
    $query = "insert into timer_queue (dueTime,encounterTime,originator,type,sourceloc,"
                . "targetloc,sourcedloc,targetdloc,slot,duration,drudgeai,drones,resources,components,items,results) "
                . "values((now() + INTERVAL $duration SECOND),(now() + INTERVAL $edue SECOND)"
                . ",'$ai',$type,'{$srcarr[0]}','{$trgarr[0]}','{$srcarr[1]}','{$trgarr[1]}',$slot,$duration,$dai"
                . ",'$drones','$res','$comp','$items','$results')";
    postlog ($query);
    $result = $mysqlidb->query($query);
    if (!$result) {
        postlog ("Creation of Timer queue entry for:$query failed:" . $mysqlidb->error);
    }
}

/*
 * checktimedentry - checks timer queue and returns number of matching entry found
 *      if type is "" then will check for any event type
 *      if sloc is "" then will check for any sloc
 *      if tloc is "" then will check for any tloc
 *  returns number of timer queue events found
 */
function checktimedentry($type, $ai, $sloc, $tloc, $slot) {
    global $mysqlidb;
    $query = "select count(*) from timer_queue where originator='$ai'";
    if (strlen($type) > 0) {
        $query .= " and type='$type'";
    }
    if (strlen($sloc) > 0) {
        $query .= " and sourceloc='$sloc'";
    }
    if (strlen($tloc) > 0) {
        $query .= " and targetloc='$tloc'";
    }
    if (strlen($slot) > 0) {
        $query .= " and slot=$slot";
    }
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_row();
        return $row[0];
    }
    return 0;
}


/*
 * gettimedentryslots - checks timer queue and returns string containing
 *      slot values separted by colon
 *      if type is "" then will check for any event type
 *      if sloc is "" then will check for any sloc
 *      if tloc is "" then will check for any tloc
 *  returns number of string of slot entries in all records found
 */
function gettimedentryslots($type, $ai, $sloc, $tloc) {
    global $mysqlidb;
    $query = "select slot from timer_queue where originator='$ai'";
    if (strlen($type) > 0) {
        $query .= " and type='$type'";
    }
    if (strlen($sloc) > 0) {
        $query .= " and sourceloc='$sloc'";
    }
    if (strlen($tloc) > 0) {
        $query .= " and targetloc='$tloc'";
    }
    $line = "";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        while (($row = $result->fetch_row()) != null) {
            if ($line != "") {
                $line .= ":";
            }
            $line .= "$row[0]";
        }
    }
    return $line;
}


/*
 * gettimedentrytargets - get targets of matching timed entries
 *      if type is "" then will check for any event type
 *      if sloc is "" then will check for any sloc
 *  returns an array of tloc for all records that match
 */
function gettimedentrytargets($type, $ai, $sloc) {
    global $mysqlidb;
    $retlist = array("");
    $retidx = 0;

    $query = "select targetloc from timer_queue where originator='$ai'";
    if (strlen($type) > 0) {
        $query .= " and type='$type'";
    }
    if (strlen($sloc) > 0) {
        $query .= " and sourceloc='$sloc'";
    }
    $query .= ";";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        while ($row = $result->fetch_row()) {
            $retlist[$retidx] = $row[0];
            $retidx++;
        }
    }
    return $retlist;
}

/*
 * iscancelable
 *  returns true if type is a cancelable event
 *  NOTE: must match isCancelable function in Queue.java
 */
function iscancelable($type) {
    $result = false;

    switch ($type) {
        case TIMER_SCRAP_RECYCLE:
        case TIMER_REM_DEFENSE:
        case TIMER_CON_DEFENSE:
        case TIMER_CON_COMPONENT:
        case TIMER_LEVEL_MODULE:
        case TIMER_TRAIN_TECH:
        case TIMER_DRONE_ASSY:
        case TIMER_DRONE_RECY:
        case TIMER_DRONE_REPAIR:
            $result = true;
            break;
        default:
            $result = false;
            break;
    }

    return $result;
}

/*
 * printtimerqueue - prints timer queue entries for given ai
 *  if $loc is empty then will print for all location otherwise retricts to
 *      just those with sourceloc equal to $loc
 *  exclude formation movements
 */
function printtimerqueue($ai, $loc) {
    global $mysqlidb;
    $ret = array("t|||0|No pending actions found||||");
    $retidx = 0;

    if ($loc == "") {
        $query = "select * from timer_queue where originator='$ai' order by dueTime;";
    } else {
        $query = "select * from timer_queue where originator='$ai' and (sourceloc='$loc' or targetloc='$loc') order by dueTime;";
    }
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            // skip formation movement events
            if (($row["type"] >= TIMER_DRONE_TRANS)
                    && ($row["type"] <= TIMER_DRONE_BLITZ)) {
                continue;
            }
            $text = explode(";", $row["results"]);
            $drones = "";
            $darr = decodedronelist($row["drones"], true);
            foreach ($darr as $dname => $levelarray) {
                foreach ($levelarray as $quantity) {
                    if ($quantity > 0) {
                        if ($drones != "") {
                            $drones .= ", ";
                        }
                        $drones .= $quantity . " " . $dname;
                    }
                }
            }
            if ($drones != "") {
                $text[0] .= ", using " . $drones;
            }
            $flag = (iscancelable($row['type']) ? "1" : "0");
            $ret[$retidx] = "T|{$row['entry']}|{$row['type']}|$flag|{$row['dueTime']}|{$row['sourcedloc']}|{$row['targetdloc']}|{$row['slot']}|{$text[0]}";
            $retidx++;
        }
    }
    return $ret;
}


?>
