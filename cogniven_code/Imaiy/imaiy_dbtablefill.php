<?php
/*
 * php script to fill world table with initial default location and rogue base
 *  records.
 */
if (isset($_SERVER{'HTTP_HOST'})) {
    die ("Must run from command line only!\n");
}
// get environment variables
if ($argc < 3) {
    print "\nUsage: php -f imaiy_dbtablefill.php <database> <dbserverip>\n"
            ."\t dbserverip is the ip address of the dbserver associated with this game server\n\n";
    return;
}
$gamedbase = $argv[1];
$gamedbserver = $argv[2];

include "globals.php";

    // fill data base tables with default info
    $str = "Start filling world table in " . $gamedbase . " on " . $gamedbserver;
    echo "$str\n";

    $mysqlidb = new mysqli($gamedbserver, $gamedbase, DBPASS, $gamedbase);

    $qresult = $mysqlidb->query($query);
    if (!$qresult)
    {
        $err = $mysqlidb->error;
        echo "$query = $err\n";
        return;
    }

    // world table sections A and B, will set ini maxsection variable
    passthru("php -f ADDSECTION.php A $gamedbase $gamedbserver");
    passthru("php -f ADDSECTION.php B $gamedbase $gamedbserver");

    print "Starting setting of Central AI points\n";
    // central control blocks always set use dlocations because easier to visualize
    $centrals = array( "A2.0.0", "A2.1.0", "A2.0.1", "A3.99.0",
                        "A0.99.99", "A1.0.99", "A1.1.99");
    $otype = 90; // for first which is central point of the seven above
    for ($idx = 0; $idx < count($centrals); $idx++) {
        $query = "delete from bases where dlocation='{$centrals[$idx]}'";
        $mysqlidb->query($query);

        $query = "update world set controller='system',o_type=$otype where dlocation='{$centrals[$idx]}'";
        $mysqlidb->query($query);

        $otype = 91; // for the six locs surrounding the central point
    }
    print "Completed setting of Central AI points\n";


    print "Initializing ini values\n";
    $query = "insert into ini (variable,value,phpneed) values ('imaiyresourceserver_lastupdate', '0', '0')";
    $mysqlidb->query($query);
    $query = "insert into ini (variable,value,phpneed) values ('highestopensection', 'A', '1')";
    $mysqlidb->query($query);
    $query = "insert into ini (variable,value,phpneed) values ('maxsupportedsection', 'Z', '0')";
    $mysqlidb->query($query);
    $query = "insert into ini (variable,value,phpneed) values ('pcbasetrigger', '250', '0')";
    $mysqlidb->query($query);
    $query = "insert into ini (variable,value,phpneed) values ('rogbasetrigger', '100', '0')";
    $mysqlidb->query($query);
    $query = "insert into ini (variable,value,phpneed) values ('rogbaseavg', '500', '0')";
    $mysqlidb->query($query);
    $query = "insert into ini (variable,value,phpneed) values ('rogbasechance', '0.001', '0')";
    $mysqlidb->query($query);
    $query = "insert into ini (variable,value,phpneed) values ('accresdecline', '500', '0')";
    $mysqlidb->query($query);
    $query = "insert into ini (variable,value,phpneed) values ('resgoalopen', '20000000', '1')";
    $mysqlidb->query($query);
    $query = "insert into ini (variable,value,phpneed) values ('resgoalnew', '5000000', '1')";
    $mysqlidb->query($query);
    $query = "insert into ini (variable,value,phpneed) values ('rogatkthreshold', '1500', '0')";
    $mysqlidb->query($query);
    $query = "insert into ini (variable,value,phpneed) values ('rogencthreshold', '32767', '0')";
    $mysqlidb->query($query);
    $query = "insert into ini (variable,value,phpneed) values ('rogencthresmult', '128000', '0')";
    $mysqlidb->query($query);
    $query = "insert into ini (variable,value,phpneed) values ('subdualratiogoal', '2.5', '1')";
    $mysqlidb->query($query);
    $query = "insert into ini (variable,value,phpneed) values ('subdualratio', '0.0', '1')";
    $mysqlidb->query($query);
    $query = "insert into ini (variable,value,phpneed) values ('accgoal', '0', '1')";
    $mysqlidb->query($query);
    $query = "insert into ini (variable,value,phpneed) values ('accfuel', '0', '1')";
    $mysqlidb->query($query);
    $query = "insert into ini (variable,value,phpneed) values ('accmetal', '0', '1')";
    $mysqlidb->query($query);
    $query = "insert into ini (variable,value,phpneed) values ('accmineral', '0', '1')";
    $mysqlidb->query($query);
    $query = "insert into ini (variable,value,phpneed) values ('accxtal', '0', '1')";
    $mysqlidb->query($query);
    print "Completed setting ini values\n";

    $str = "Database tables re-initialized";
    $query="insert into reports(postTime,source,target,type,text) values(now(),'System','all',0,'$str')";
    $qresult = $mysqlidb->query($query);
    if (!$qresult)
    {
        $err = $mysqlidb->error;
        echo "$query = $err\n";
        return;
    }
    echo "Data table fill complete\n";

?>

