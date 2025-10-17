<?php
/*
 * script to run manual update of source code without rebooting
 *  does not restart java tasks
 */
if (isset($_SERVER{'HTTP_HOST'})) {
    die ("Must run from cron or command line only!\n");
}
// get environment variables
if ($argc < 4) {
    print "\nUsage: php -f MANUALUPDATE.php <database> <mode> <dbserverip>\n\twhere mode is 'production' or 'development'\n"
            ."\t dbserverip is the ip address of the dbserver associated with this game server\n";
    return;
}
$gamedbase = $argv[1];
$gamemode = $argv[2];
$gamedbserver = $argv[3];

$pinfo = pathinfo($argv[0]);
$gamepath = $pinfo["dirname"];

openlog($argv[0], LOG_PERROR, LOG_LOCAL0);

include "globals.php";

// update web files from svn repository
syslog(LOG_NOTICE, "performing manual update of files: $gamemode");
exec("svn revert $gamepath/css/*.css");
exec("svn revert $gamepath/js/*.js");
syslog(LOG_NOTICE, "revert of css and js files complete");
exec("svn update $gamepath --accept theirs-full");
syslog(LOG_NOTICE, "update complete");

// make java server task start scripts executable
exec("chmod a+x $gamepath/java/*.sh");

// insure log directory is writable
exec("chmod a+w $gamepath/logs");

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

syslog(LOG_NOTICE, "Manual update of files: $gamemode complete");


?>
