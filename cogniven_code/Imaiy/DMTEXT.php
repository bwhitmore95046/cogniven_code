<?php
/*
 * Daily maintanence script to extract strings from db and create language text files
 *  creates files text subdirectory
 *  extracts names into names_php.inc.* and names_html.inc.*
 *  extracts descriptions into descriptions_php.inc.* and descriptions_html.inc.*
 */
if (isset($_SERVER{'HTTP_HOST'})) {
    die ("Must run from cron or command line only!\n");
}
// get environment variables
if ($argc < 3) {
    print "\nUsage: php -f DMTEXT.php <database> <dbserverip>\n"
            ."\t dbserverip is the ip address of the dbserver associated with this game server\n\n";
    return;
}
$gamedbase = $argv[1];
$pinfo = pathinfo($argv[0]);
$gamepath = $pinfo["dirname"];
$gamedbserver = $argv[2];

openlog($argv[0], LOG_PERROR, LOG_LOCAL0);

include "globals.php";

$tables = array("names", "descriptions");
$languages = array("en");

$mysqlidb = new mysqli($gamedbserver, $gamedbase, DBPASS, $gamedbase);

// extract db string tables into include files
foreach ($tables as $tab) {
    $query = "select * from text_$tab order by aray";
    $qresult = $mysqlidb->query($query);
    if (!$qresult) {
        syslog(LOG_INFO, $mysqlidb->error);
        break;
    }
    if ($qresult->num_rows > 0) {
        foreach ($languages as $lang) {
            $text_php = array();
            $text_html = array();
            $text_php[] = "<?php";
            $qresult->data_seek(0);
            while ($row = $qresult->fetch_assoc()) {
                $text_php[] = "\${$row['aray']}['{$row['idx']}'] = '{$row[$lang]}';";
                $text_html[] = "<p id='{$row['pprefix']}{$row['idx']}'>{$row[$lang]}</p>";
            }
            $text_php[] = "?>";
            $text_php[] = "";
            $text_html[] = "";
            $fname = "$gamepath/text/{$tab}_php.inc.$lang";
            $num = file_put_contents($fname, implode("\n", $text_php));
            syslog(LOG_INFO, "$num bytes written to $fname");
            $fname = "$gamepath/text/{$tab}_html.inc.$lang";
            $num = file_put_contents($fname, implode("\n", $text_html));
            syslog(LOG_INFO, "$num bytes written to $fname");
        }
    }
}


?>
