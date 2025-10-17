<?php
/*
 * Daily maintanence script to post that reboot is complete
 */
if (isset($_SERVER{'HTTP_HOST'})) {
    die ("Must run from cron or command line only!\n");
}
// get environment variables
if ($argc < 4) {
    print "\nUsage: php -f DMCOMPLETE.php <database> <mode> <dbserverip>\n"
            ."\t where mode is 'production' or 'development'\n"
            ."\t dbserverip is the ip address of the dbserver associated with this game server\n\n";
    return;
}
$gamedbase = $argv[1];
$gamemode = $argv[2];
$gamedbserver = $argv[3];

include "globals.php";

// connect to db server - retry for 5min before giving up
$count = 30;
do {
    $mysqlidb = new mysqli($gamedbserver, $gamedbase, DBPASS, $gamedbase);
    if ($mysqlidb->connect_error != null) {
        sleep(10);
        $count--;
    }
} while (($mysqlidb->connect_error != null) && ($count > 0));

if ($mysqlidb->connect_error != null) {
    die ("Could not connect to mysql server: " . $mysqlidb->connect_error . "!\n");
}

$textstr = "Communication restored";
$type = 98;
echo "$textstr\n";
$query="insert into chats(postTime,source,target,channel,type,text) values(now(),'System','all',0,$type,'$textstr');";
$mysqlidb->query($query);


?>
