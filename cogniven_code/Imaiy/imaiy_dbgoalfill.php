<?php
/*
 * php script to fill or update goal table.
 */
if (isset($_SERVER{'HTTP_HOST'})) {
    die ("Must run from command line only!\n");
}
// get environment variables
if ($argc < 3) {
    print "\nUsage: php -f imaiy_dbgoalfill.php <database> <dbserverip>\n"
            ."\t dbserverip is the ip address of the dbserver associated with this game server\n\n";
    return;
}
$gamedbase = $argv[1];
$gamedbserver = $argv[2];

include "globals.php";

    /*
     * names array for goals - inserted into goals table
     *  format "id:order:prereq:goal:reward"
     *      id - key string for goal up to 8 characters is saved in player record
     *              completedgoals column so must never be changed
     *      order - order for display of goals in all lists
     *      goal - encoded goal trigger - see goalcheck function in goals.php
     *      reward - code for cash shop item in format "key:quantity;key:quantity"
     *  plain text name and description is in imaiy_dbtextfill.php and is
     *      linked via id string
     */
    $reqcount = 4; // how many entries needed for each item

    $goal = array();
    
    // must have descrition in imaiy_dbtextfill.php
    $goal[] = array("G004", "1000", "bn:1:Base", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G005", "1001", "dair::", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G006", "1002", "mm:".MODULE_REFINE.":1", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G007", "1003", "mm:".MODULE_RESEARCH.":1", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G008", "1004", "t:".TECH_FUEL_EXT.":1", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G009", "1005", "t:".TECH_METAL_EXT.":1", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G010", "1006", "t:".TECH_MINERAL_EXT.":1", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G011", "1007", "t:".TECH_XTAL_EXT.":1", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G001", "1010", "p::2", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G012", "1011", "t:".TECH_SYNTHESIS.":1", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G013", "1012", "mm:".MODULE_SYNTHESIS.":1", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G014", "1013", "comp:".COMPONENT_DCNTRL.":100", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G015", "1014", "t:".TECH_BASE_CAP.":1", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G016", "1015", "mm:".MODULE_STORAGE.":1", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G017", "1016", "t:".TECH_DRONE_ASSY.":1", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G024", "1017", "mm:".MODULE_ASSEMBLY.":1", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G018", "1018", "mm:".MODULE_TRANSCEIVER.":1", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");

    $goal[] = array("G019", "1020", "t:".TECH_DATA_PROC.":1", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G020", "1021", "comp:".COMPONENT_DATAP.":100", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G021", "1022", "drone:".DRONE_RECON.":100", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G022", "1023", "t:".TECH_SCOUT.":1", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G023", "1024", "comp:".COMPONENT_DSCAN.":100", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");

    $goal[] = array("G026", "1040", "bb:1:2", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G002", "1041", "p::3", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G027", "1042", "t:".TECH_DRONE_MOVE.":1", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G028", "1043", "t:".TECH_DRONE_TACTIC.":1", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G030", "1047", "drone:".DRONE_RECON_SCO.":100", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");

    $goal[] = array("G031", "1050", "comp:".COMPONENT_TSCAN.":100", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G032", "1051", "comp:".COMPONENT_WMOUNT.":100", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G033", "1052", "drone:".DRONE_FIGHT.":100", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");
    $goal[] = array("G034", "1053", "drone:".DRONE_FIGHT_LIT.":100", "RESF1:1;RESM1:1;RESN1:1;RESX1:1");

    $goal[] = array("G003", "1060", "p::4", "RESF1:2;RESM1:2;RESN1:2;RESX1:2");
    $goal[] = array("G036", "1061", "mm:".MODULE_REFINE.":2", "RESF1:2;RESM1:2;RESN1:2;RESX1:2");
    $goal[] = array("G037", "1062", "t:".TECH_FUEL_EXT.":2", "RESF1:2;RESM1:2;RESN1:2;RESX1:2");
    $goal[] = array("G038", "1063", "t:".TECH_METAL_EXT.":2", "RESF1:2;RESM1:2;RESN1:2;RESX1:2");
    $goal[] = array("G039", "1064", "t:".TECH_MINERAL_EXT.":2", "RESF1:2;RESM1:2;RESN1:2;RESX1:2");
    $goal[] = array("G040", "1065", "t:".TECH_XTAL_EXT.":2", "RESF1:2;RESM1:2;RESN1:2;RESX1:2");


    $errors = 0;
    foreach ($goal as $idx=>$gitem) {
        if (count($gitem) != $reqcount) {
            echo "\nError in definition of goal $idx, has " . count($gitem) . " entries but requires $reqcount\n";
            $errors++;
        }
     }

    if ($errors == 0) {
        $mysqlidb = new mysqli($gamedbserver, $gamedbase, DBPASS, $gamedbase);
        $mysqlidb->query("delete from goals");
        foreach ($goal as $idx=>$gitem) {
            $query = "insert into goals (id,displayorder,goal,reward) values("
                        . "'{$gitem[0]}',"
                        . "'{$gitem[1]}',"
                        . "'{$gitem[2]}',"
                        . "'{$gitem[3]}')";
            $result = $mysqlidb->query($query);
            if ($result == false) {
                echo "$idx: $query\n";
                echo "$idx: " . $mysqlidb->error . "\n";
                $errors++;
                break;
            }
        }
    }

?>
