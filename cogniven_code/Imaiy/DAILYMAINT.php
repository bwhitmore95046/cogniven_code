<?php
/*
 * Daily maintanence script to clean up dbase records, update code and reboot server
 */
if (isset($_SERVER{'HTTP_HOST'})) {
    die ("Must run from cron or command line only!\n");
}
// get environment variables
if ($argc < 6) {
    print "\nUsage: php -f DAILYMAINT.php <database> <mode> <domain> <dbserverip> <gameportalip)\n"
            ."\twhere mode is 'production' or 'development'\n"
            ."\t domain is the server domain url such as 'region0.imaiy.com'\n"
            ."\t dbserverip is the ip address of the dbserver associated with this game server\n"
            ."\t gameportalip is the ip address of the game portal associated with this game server\n\n";
    return;
}
$gamedbase = $argv[1];
$gamemode = $argv[2];
$gamedomain = $argv[3];
$gamedbserver = $argv[4];
$gameportal = $argv[5];

$pinfo = pathinfo($argv[0]);
$gamepath = $pinfo["dirname"];

openlog($argv[0], LOG_PERROR, LOG_LOCAL0);

include "globals.php";

$hstr = "Communication disruption incoming in";
$timetillreport = 15;
$type = 99;
while ($timetillreport > 0) {
    $textstr = "$hstr $timetillreport";
    if ($timetillreport > 1) {
        $textstr .= " minutes";
    } else {
        $textstr .= " minute";
    }
    syslog(LOG_NOTICE, "$textstr");
    $mysqlidb = new mysqli($gamedbserver, $gamedbase, DBPASS, $gamedbase);
    $query="insert into chats(postTime,source,target,channel,type,text) values(now(),'System','all',0,$type,'$textstr');";
    $qresult = $mysqlidb->query($query);
    $mysqlidb->close();
    $type = 98;
    if ($timetillreport > 5) {
        $timetillreport -= 5;
        $sleeptime = 300;
    } else {
        $timetillreport--;
        $sleeptime = 60;
    }
    sleep($sleeptime);
}

// update web files from svn repository
// must do before taking apache2 down
exec("svn cleanup $gamepath");
syslog(LOG_NOTICE, "cleanup complete");
exec("svn revert $gamepath/css/*.css");
exec("svn revert $gamepath/js/*.js");
syslog(LOG_NOTICE, "revert of css and js files complete");
exec("svn update $gamepath --accept theirs-full");
syslog(LOG_NOTICE, "update complete");

// make java server task start scripts executable
exec("sudo -u root -S chmod a+x $gamepath/java/*.sh < /var/overpw");

// insure log directory is writable
exec("sudo -u root -S chmod a+w $gamepath/logs < /var/overpw");

// get version string
$who = "";
if ($gamemode != "production") {
    $who = exec("whoami") . " ";
}
$revision = exec("svn info $gamepath | grep 'Revision:'");
file_put_contents("$gamepath/revision.txt", "$who$revision");

if ($gamemode == "production") {
    // remove old combined js file
    unlink("$gamepath/js/combined.js");
    // compress css and js files
    exec("java -jar $gamepath/java/yuicompressor-2.4.6.jar -o '.css$:.css' $gamepath/css/*.css");
    exec("java -jar $gamepath/java/yuicompressor-2.4.6.jar -o '.js$:.js' $gamepath/js/*.js");
    exec("cat $gamepath/js/*.js > $gamepath/js/combined.js");
    syslog(LOG_NOTICE, "compression of css and js files complete");
}

// take apache offline
exec("sudo -u root -S /etc/init.d/apache2 stop < /var/overpw");
syslog(LOG_NOTICE, "apache offline");

// stop server java tasks
exec("sudo -u root -S pkill -f Imaiyresourceserver.jar < /var/overpw");
exec("sudo -u root -S pkill -f Imaiyqueueserver.jar < /var/overpw");
exec("sudo -u root -S pkill -f Imaiytradeserver.jar < /var/overpw");
exec("sudo -u root -S pkill -f Imaiyrogueserver.jar < /var/overpw");
syslog(LOG_NOTICE, "server jar tasks stopped");

$dailyamount = 0;
$mysqlidb = new mysqli($gameportal, DBAUSER, DBAPASS, DBADBASE);
$query = "select dailygift from games where serverlink='$gamedomain'";
$qresult = $mysqlidb->query($query);
if ($qresult && ($qresult->num_rows > 0)) {
    $row = $qresult->fetch_row();
    $dailyamount = $row[0];
}
$mysqlidb->close();

$mysqlidb = new mysqli($gamedbserver, $gamedbase, DBPASS, $gamedbase);
// remove all sessions
$query = "delete from sessions";
$qresult = $mysqlidb->query($query);

$cutoff = 90;
// remove chronicles older than cutoff time
$query = "delete from chronicle where created<(now()- INTERVAL $cutoff DAY)";
$qresult = $mysqlidb->query($query);
// remove reports older than cutoff time
$query = "delete from reports where postTime<(now()- INTERVAL $cutoff DAY)";
$qresult = $mysqlidb->query($query);
// remove chats older than cutoff time
// remove old system alerts for comm disruptions
$query = "delete from chats where postTime<(now()- INTERVAL $cutoff DAY) or type=99 or type=98";
$qresult = $mysqlidb->query($query);
// remove mail older than cutoff time
$query = "delete from game_mail where sent<(now()- INTERVAL $cutoff DAY)";
$qresult = $mysqlidb->query($query);
// update player daily gift amounts
$query = "update player set dailyclaims=$dailyamount";
$qresult = $mysqlidb->query($query);

// remove any player accounts with no bases and created > 48hours ago
$query = "select * from (select player.*,count(bases.location) as numbases from player left join bases on (bases.controller=player.name) group by player.name) as tmpp where numbases=0";
$qresult = $mysqlidb->query($query);
if ($qresult && $qresult->num_rows > 0) {
    while ($row = $qresult->fetch_assoc()) {
        postlog("Removing old player account for ". $row["name"]);
        postlog(var_export($row, true));
        $mysqlidb->query("delete from player where name='" . $row["name"] . "'");
    }
}

syslog(LOG_NOTICE, "database udpates complete");

// remove log files older than cutoff time
$ldir = "$gamepath/logs";
$files = scandir($ldir);
$cut = time() - ($cutoff * 24 * 60 * 60); // in seconds
for ($idx = 0; $idx < count($files); $idx++) {
    $finfo = pathinfo($files[$idx]);
    if ($finfo["extension"] == "log") {
        $mtime = filemtime($ldir."/".$files[$idx]);
        if ($mtime < $cut) {
            unlink($ldir."/".$files[$idx]);
        }
    }
}
syslog(LOG_NOTICE, "logs cleanup complete");

// run db store table update
exec("php -f $gamepath/imaiy_dbstorefill.php $gamedbase $gamedbserver");

// run db goal table update
exec("php -f $gamepath/imaiy_dbgoalfill.php $gamedbase $gamedbserver");

// run db recipe table update
exec("php -f $gamepath/imaiy_dbrecipefill.php $gamedbase $gamedbserver");

// run db text string tables update
exec("php -f $gamepath/imaiy_dbtextfill.php $gamedbase $gamedbserver");
// extract db text string tables into include files
exec("php -f $gamepath/DMTEXT.php $gamedbase $gamedbserver");

// create kick event for every organization owner
syslog(LOG_NOTICE, "Kicking all organization owners");
$query = "select owner from alliance";
$qresult = $mysqlidb->query($query);
if ($qresult && $qresult->num_rows > 0) {
    while ($row = $qresult->fetch_row()) {
        $query = "insert into timer_queue (dueTime,encounterTime,originator,type,slot) "
                . "values(now(),(now() + INTERVAL 60 SECOND)"
                . ",'{$row[0]}'," . TIMER_MAINTANENCE . ",4)";
        $mysqlidb->query($query);
    }
}
syslog(LOG_NOTICE, "Kicking of organization owners complete");

$maxsupsec = "Z";
$query = "select value from ini where variable='maxsupportedsection'";
$qresult = $mysqlidb->query($query);
if ($qresult && $qresult->num_rows > 0) {
    $row = $qresult->fetch_row();
    $maxsupsec = $row[0];
}
$curmaxsec = "A";
$query = "select value from ini where variable='highestopensection'";
$qresult = $mysqlidb->query($query);
if ($qresult && $qresult->num_rows > 0) {
    $row = $qresult->fetch_row();
    $curmaxsec = $row[0];
}
if (ord($curmaxsec) < ord($maxsupsec)) {
    $nextsec = chr(ord($curmaxsec) + 1);
    syslog(LOG_NOTICE, "Creating section $nextsec if it doesn't exist");
    // add next section if it doesn't already exist
    passthru("php -f $gamepath/ADDSECTION.php $nextsec $gamedbase $gamedbserver");
}


$mysqlidb->close();
closelog(); // close before reboot

// reboot server in 30 seconds
exec("sudo -u root -S /sbin/shutdown -r 0 < /var/overpw");

?>
