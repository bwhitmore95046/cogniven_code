<?php
/*
 * php script to fill or update store table.
 */
if (isset($_SERVER{'HTTP_HOST'})) {
    die ("Must run from command line only!\n");
}
// get environment variables
if ($argc < 3) {
    print "\nUsage: php -f imaiy_dbstorefill.php <database> <dbserverip>\n"
            ."\t dbserverip is the ip address of the dbserver associated with this game server\n\n";
    return;
}
$gamedbase = $argv[1];
$gamedbserver = $argv[2];

include "globals.php";

    // items to add to store table
    //      NOTE: name and descriptions are entered in imaiy_dbtextfill.php
    //  format "key:code:price:class:weight:field,base:action:name:description"
    //      key - key used to reference item in this table
    //      code - code used to add this item to player item list format is "key:level"
    //              code is appended with ":quantity" before insertion into player item list
    //              if 'level' is A then item has an action associated with it
    //      price - price in premium credits
    //      class - class of item for grouping and sorting
    //      weight - weight used to determine daily gift - 1000 is average, lower
    //                  awards item less often, higher more often, 0 means never awarded
    //      field - chance of item appearing as loot from attacking an empty location
    //      base - chance of item appearing as loot from attacking a base
    //      action - encoded string of action to take when item is used - see useitem in player.php
    //                  if empty then no action associated with this item
    //                  format is "key-data" and must contain '-'
    //                  durations in actions should always be in minutes
    //      name - name of item (not in this file, see above note)
    //      description - long description of item and its use (not in this file, see above note)
    $reqcount = 8; // how many entries needed for each item

    $items[0] = array('CAIOWPA', 'CAIOWPA:A', 2500, 'Status', 0, 0, 0, 'PA-CAIPA:10080');

    $items[] = array('DC10','DC:10', 1000, 'Drudge AI Synthesis', 0, 0, 0, '');
    $items[] = array('DC25','DC:25', 2250, 'Drudge AI Synthesis', 0, 0, 0, '');
    $items[] = array('DC60','DC:60', 5000, 'Drudge AI Synthesis', 0, 0, 0, '');

    $items[] = array('COFR','COFR:0', 2, 'Master AI Enhancement', 0, 60, 60, '');
    $items[] = array('DANO','DANO:0', 4, 'Master AI Enhancement', 0, 30, 30, '');
    $items[] = array('HEPR','HEPR:0', 8, 'Master AI Enhancement', 0, 15, 15, '');
    $items[] = array('CRPRCH','CRPRCH:0', 16, 'Master AI Enhancement', 0, 0, 60, '');
    $items[] = array('POPABU','POPABU:0', 32, 'Master AI Enhancement', 0, 0, 30, '');
    $items[] = array('SUCORO','SUCORO:0', 64, 'Master AI Enhancement', 0, 0, 15, '');
    $items[] = array('CUSAV','CUSAV:0', 10000, 'Master AI Enhancement', 0, 0, 0, '');

    $items[] = array('PARTC','PARTC:0', 2000, 'Organization', 0, 0, 0, '');
    $items[] = array('ENTRC','ENTRC:0', 5000, 'Organization', 0, 0, 0, '');
    $items[] = array('COOPC','COOPC:0', 8000, 'Organization', 0, 0, 0, '');
    $items[] = array('CORPC','CORPC:0', 11000, 'Organization', 0, 0, 0, '');
    $items[] = array('CONGC','CONGC:0', 14000, 'Organization', 0, 0, 0, '');

    $items[] = array('RESF1','RESF1:A', 1000, 'Resource Order', 1000, 0, 0, 'DELRES-R:0:10000');
    $items[] = array('RESF2','RESF2:A', 5000, 'Resource Order', 200, 0, 0, 'DELRES-R:0:100000');
    $items[] = array('RESF3','RESF3:A', 10000, 'Resource Order', 100, 0, 0, 'DELRES-R:0:500000');
    $items[] = array('RESM1','RESM1:A', 1000, 'Resource Order', 1000, 0, 0, 'DELRES-R:1:10000');
    $items[] = array('RESM2','RESM2:A', 5000, 'Resource Order', 200, 0, 0, 'DELRES-R:1:100000');
    $items[] = array('RESM3','RESM3:A', 10000, 'Resource Order', 100, 0, 0, 'DELRES-R:1:500000');
    $items[] = array('RESN1','RESN1:A', 1000, 'Resource Order', 1000, 0, 0, 'DELRES-R:2:10000');
    $items[] = array('RESN2','RESN2:A', 5000, 'Resource Order', 200, 0, 0, 'DELRES-R:2:100000');
    $items[] = array('RESN3','RESN3:A', 10000, 'Resource Order', 100, 0, 0, 'DELRES-R:2:500000');
    $items[] = array('RESX1','RESX1:A', 1000, 'Resource Order', 1000, 0, 0, 'DELRES-R:3:10000');
    $items[] = array('RESX2','RESX2:A', 5000, 'Resource Order', 200, 0, 0, 'DELRES-R:3:100000');
    $items[] = array('RESX3','RESX3:A', 10000, 'Resource Order', 100, 0, 0, 'DELRES-R:3:500000');

    $items[] = array('DPCW1','DPCW1:A', 1000, 'Drone Parts Order', 100, 0, 0, 'DELDPO-B:'.DRONE_WORKR.':5000;C:'.COMPONENT_CONARM.':5000');
    $items[] = array('DPMW1','DPMW1:A', 2000, 'Drone Parts Order', 80, 0, 0, 'DELDPO-B:'.DRONE_WORKR.':5000;C:'.COMPONENT_MINEARM.':5000');
    $items[] = array('DPSW1','DPSW1:A', 4000, 'Drone Parts Order', 50, 0, 0, 'DELDPO-B:'.DRONE_WORKR.':5000;C:'.COMPONENT_DISARM.':5000');

    $items[] = array('DPSR1','DPSR1:A', 1500, 'Drone Parts Order', 100, 0, 0, 'DELDPO-B:'.DRONE_RECON.':5000;C:'.COMPONENT_RSCAN.':5000;C:'.COMPONENT_LASER.':5000');
    $items[] = array('DPCR1','DPCR1:A', 2500, 'Drone Parts Order', 80, 0, 0, 'DELDPO-B:'.DRONE_RECON.':5000;C:'.COMPONENT_DSCAN.':5000;C:'.COMPONENT_LASER.':5000');
    $items[] = array('DPAR1','DPAR1:A', 4500, 'Drone Parts Order', 50, 0, 0, 'DELDPO-B:'.DRONE_RECON.':5000;C:'.COMPONENT_BSCAN.':5000;C:'.COMPONENT_LASER.':5000');

    $items[] = array('DPMT1','DPMT1:A', 2500, 'Drone Parts Order', 100, 0, 0, 'DELDPO-B:'.DRONE_TRANS.':5000;C:'.COMPONENT_RSCOOP.':5000');
    $items[] = array('DPGT1','DPGT1:A', 4500, 'Drone Parts Order', 80, 0, 0, 'DELDPO-B:'.DRONE_TRANS.':5000;C:'.COMPONENT_LRAMP.':5000');
    $items[] = array('DPAT1','DPAT1:A', 95000, 'Drone Parts Order', 5, 0, 0, 'DELDPO-B:'.DRONE_TRANS.':5000;C:'.COMPONENT_LRAMP.':5000;C:'.COMPONENT_TIEDWN.':5000;C:'.COMPONENT_RELMECH.':5000');

    $items[] = array('DPLM1','DPLM1:A', 1000, 'Drone Parts Order', 75, 0, 0, 'DELDPO-B:'.DRONE_FIGHT.':5000;C:'.COMPONENT_PARTGEN.':5000');
    $items[] = array('DPMM1','DPMM1:A', 2500, 'Drone Parts Order', 50, 0, 0, 'DELDPO-B:'.DRONE_FIGHT.':5000;C:'.COMPONENT_LASER.':5000');
    $items[] = array('DPHM1','DPHM1:A', 4000, 'Drone Parts Order', 25, 0, 0, 'DELDPO-B:'.DRONE_FIGHT.':5000;C:'.COMPONENT_RAILGUN.':5000');
    $items[] = array('DPDM1','DPDM1:A', 7500, 'Drone Parts Order', 10, 0, 0, 'DELDPO-B:'.DRONE_FIGHT.':5000;C:'.COMPONENT_SHLDGEN.':5000');

    $items[] = array('TELE1','TELE1:A', 1500, 'Base Teleport', 150, 0, 0, 'TELE-LR');
    $items[] = array('TELE2','TELE2:A', 3500, 'Base Teleport', 40, 0, 0, 'TELE-BR');
    $items[] = array('TELE3','TELE3:A', 6000, 'Base Teleport', 0, 0, 0, 'TELE-ADV');

    // action for buffs format is 'type-value:duration' where duration is in minutes
    $items[] = array('BUFF0','BUFF0:A', 1000, 'Temporary Enhancement', 1000, 0, 0, 'BUFF-BUFF0:1440');
    $items[] = array('BUFF1','BUFF1:A', 1000, 'Temporary Enhancement', 1000, 0, 0, 'BUFF-BUFF1:1440');
    $items[] = array('BUFF2','BUFF2:A', 1000, 'Temporary Enhancement', 1000, 0, 0, 'BUFF-BUFF2:1440');
    $items[] = array('BUFF3','BUFF3:A', 1000, 'Temporary Enhancement', 1000, 0, 0, 'BUFF-BUFF3:1440');
    $items[] = array('BUFF4','BUFF4:A', 1000, 'Temporary Enhancement', 1000, 0, 0, 'BUFF-BUFF4:1440');
    $items[] = array('BUFF5','BUFF5:A', 1000, 'Temporary Enhancement', 1000, 0, 0, 'BUFF-BUFF5:1440');
    $items[] = array('BUFF6','BUFF6:A', 1000, 'Temporary Enhancement', 1000, 0, 0, 'BUFF-BUFF6:1440');
    $items[] = array('BUFF7','BUFF7:A', 1000, 'Temporary Enhancement', 1000, 0, 0, 'BUFF-BUFF7:1440');
    $items[] = array('BUFF8','BUFF8:B', 5000, 'Temporary Enhancement', 5, 0, 0, 'BUFF-BUFF8:4320');

    $errors = 0;
    foreach ($items as $idx=>$item) {
        if (count($item) != $reqcount) {
            echo "\nError in definition of item $idx, has " . count($item) . " entries but requires $reqcount\n";
            $errors++;
        }
     }

    if ($errors == 0) {
        $mysqlidb = new mysqli($gamedbserver, $gamedbase, DBPASS, $gamedbase);
        foreach ($items as $idx=>$item) {
            // key:code:price:class:weight:field:base:action:name:description
            $query = "replace into store (item,code,price,class,daily_weight,field_loot_chance,base_loot_chance,action) values("
                        . "'{$item[0]}',"
                        . "'{$item[1]}',"
                        . "{$item[2]},"
                        . "'{$item[3]}',"
                        . "{$item[4]},"
                        . "{$item[5]},"
                        . "{$item[6]},"
                        . "'{$item[7]}')";
            $result = $mysqlidb->query($query);
            if ($result == false) {
                echo $idx . ": " . $mysqlidb->error . "\n";
                break;
            }
        }
    }
    ?>
