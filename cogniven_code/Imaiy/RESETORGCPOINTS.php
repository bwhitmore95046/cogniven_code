<?php
// routine for rebuilding cpoints field in org records
if (isset($_SERVER{'HTTP_HOST'})) {
    die ("Must run from cron or command line only!\n");
}
// get environment variables
if ($argc < 2) {
    print "Usage: RESETORGCPOINTS.php <database> <dbservierip>\n";
    return;
}
$gamedbase = $argv[1];
$gamedbserver = $argv[2];

include "globals.php";

$mysqlidb = new mysqli($gamedbserver, $gamedbase, DBPASS, $gamedbase);
openlog($argv[0], LOG_PERROR, LOG_LOCAL0);

// minor control points have o_type of 2-5 and major 6-9
syslog(LOG_NOTICE, "Begin rebuild of organization cpoints column");

$cparr = array();

// get all controlled conrol points
$query = "select controller,o_type,block,quadrant,location from world where o_type>1 and controller!='system'";
$qresult = $mysqlidb->query($query);
if ($qresult && ($qresult->num_rows > 0)) {
    while (($row = $qresult->fetch_row()) != null) {
        $org = $row[0];
        $type = $row[1];
        $class = "minor";
        if (((int)$type >= 6) && ((int)$type <= 9)) {
            $class = "major";
        }
        $block = $row[2];
        $quad = $row[3];
        $loc = $row[4];

        $cnt = 1;
        if (key_exists($row[0], $cparr)) {
            for ($idx = 0; $idx < count($cparr[$org]); $idx++) {
                if (($cparr[$org][$idx]["class"] == $class)
                        && ($cparr[$org][$idx]["block"] == $block)) {

                    if ($class == "major") {
                        // major must just be in same block to accumalate
                        $cnt++;
                        $cparr[$org][$idx]["cnt"]++;
                    } else if ($cparr[$org][$idx]["quad"] == $quad) {
                        // minor must be in same block and quad to accumulate
                        $cnt++;
                        $cparr[$org][$idx]["cnt"]++;
                    }
                }
            }
        }
        $cparr[$org][] = array("type"=>$type, "cnt"=>$cnt, "block"=>$block, "quad"=>$quad, "loc"=>$loc, "class"=>$class);
    }
}
// clear all previous cpoints
$query = "update alliance set cpoints=''";
$mysqlidb->query($query);

// walk $cparr and set cpoints
foreach ($cparr as $org=>$oarr) {
    $cpstr = "";
    foreach ($oarr as $cp) {
        if ($cpstr != "") {
            $cpstr .= ";";
        }
        $cpstr .= $cp["block"] . ":" . $cp["quad"] . ":" . $cp["loc"] . ":" . $cp["type"] . ":" . $cp["cnt"];
    }
    $query = "update alliance set cpoints='$cpstr' where name='$org'";
    $mysqlidb->query($query);
}

syslog(LOG_NOTICE, "Completed rebuild of organization cpoints column");

?>
