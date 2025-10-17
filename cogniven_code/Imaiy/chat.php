<?php
/*
 * Functions to deal with chats, tells and reports
 * Author: Chris Bryant
 */

/*
 * postannouncement
 *  adds a global chat with type 96 or 97 which will also show in banner
 */
function postannouncment($ai, $text, $alert) {
    global $mysqlidb;

    if ((floor($_SESSION["rank"]) & 1) != 0) {
        $from = "(GM)$ai";
        if ($alert == true) {
            $type = 96; // cause alert to popup
        } else {
            $type = 97;
        }

        $query = sprintf("insert into chats (postTime,source,target,channel,type,text) values(now(),'%s','all',".CHANNELALL.",$type,'%s')",
            $mysqlidb->real_escape_string($from),
            $mysqlidb->real_escape_string($text));
        $result = $mysqlidb->query($query);
        if (!$result) {
            postlog ("Error- Unable to post announcement from $ai: $text");
        }
    }
}


/*
 * postchat - adds a chat record to chats table
 *
 */
function postchat ($ai, $channel, $targ, $text) {
    global $mysqlidb;
    $channel++; // internal channels offset by one as 0 is system only
    $dopost = true;
    $type = CHATTYPEGEN;

    if ((floor($_SESSION["rank"]) & 1) != 0) {
        $from = "(GM)$ai";
    } else {
        $from = $ai;
    }

    // default to targeting everyone
    $target = "all";
    if ($channel == CHANNELALLIANCE) {
        // channel is alliance then set target to ai's alliance name
        $target = $_SESSION["alliance"];
        $type = CHATTYPEALLIANCE;
    }
    $now = time();
    // ignore titles when checking status
    $statparts = explode(" ", $_SESSION["player_status"]);
    if (($statparts[0] != "Normal") && ($channel != CHANNELREPORT)) {
        // if in protected mode then limit chat text rate
        if (($now - $_SESSION["lastchattime"]) < MINCHATPAUSE) {
            postreport($ai, 0, "Those under Central AI protection must wait ". MINCHATPAUSE . " seconds between posts");
            $dopost = false;
        }
    }
    if ($dopost == true) {
        $_SESSION["lastchattime"] = $now;
        if ($channel == CHANNELTELL) {
            // add tell text into chats table as type tell
            $query = sprintf("insert into chats (postTime,type,source,target,text) values(now(),".CHATTYPETELL.",'%s','%s','%s')",
                $mysqlidb->real_escape_string($from),
                $mysqlidb->real_escape_string($targ),
                $mysqlidb->real_escape_string($text));
            $result = $mysqlidb->query($query);
            if (!$result) {
                postlog ("Error- Unable to post tell from $ai to $targ: $text");
            }
        } else if ($channel == CHANNELREPORT) {
            // add report text into reports table
            //  target is always $ai
            $type = CHATTYPEREPORT;
            $query = sprintf("insert into reports (postTime,source,target,type,text) values(now(),'%s','%s',%s,'%s')",
                $mysqlidb->real_escape_string($from),
                $mysqlidb->real_escape_string($from),
                $mysqlidb->real_escape_string($type),
                $mysqlidb->real_escape_string($text));
            $result = $mysqlidb->query($query);
            if (!$result) {
                postlog ("Error- Unable to post report from $ai: $text");
            }
        } else {
            // add chat text into chats table
            // channel CHANNELALL and CHANNELALLIANCE
            $query = sprintf("insert into chats (postTime,source,target,channel,type,text) values(now(),'%s','%s',%s,%s,'%s')",
                $mysqlidb->real_escape_string($from),
                $mysqlidb->real_escape_string($target),
                $mysqlidb->real_escape_string($channel),
                $mysqlidb->real_escape_string($type),
                $mysqlidb->real_escape_string($text));
            $result = $mysqlidb->query($query);
            if (!$result) {
                postlog ("Error- Unable to post chat from $ai: $text");
            }
        }
    }
}

/*
 * showreport - retrieve entries from report table with
 *  entry > last and prints them one to a line
 */
function showreport ($ai, $last) {
    global $mysqlidb;
    $account_created = $_SESSION["created"];
    // get report text from reports table
    //      only get those entries with target  of "all" or $ai
    //      or source is $ai.
    $query = "select entry,type,postTime,text from reports where entry>$last"
                . " and postTime>='$account_created' and (target='all'"
                . " or target='$ai' or source='$ai' or target='(GM)$ai'"
                . " or source='(GM)$ai') order by entry desc limit ".MAXREPORT;
    $result = $mysqlidb->query($query);
    // if it failed, it just means no new text since last request
    if ($result && ($result->num_rows > 0)) {
        while ($row = $result->fetch_row()) {
            $time = explode(" ", $row[2]);
            $date = explode("-", $time[0]);
            // format entry|R|type|time>text
            print "CR|{$row[0]}|{$row[1]}|{$date[1]}{$date[2]}-{$time[1]}>{$row[3]}\n";
        }
    }
}


/*
 * postreport - adds a report record to reports table
 *  used directly by php functions to add reports to dbase.
 */
function postreport ($targ, $type, $text) {
    global $mysqlidb;
    $src = $_SESSION["name"];
    // add report text into reports table
    $query = sprintf("insert into reports (postTime,source,target,type,text) values(now(),'%s','%s',%s,'%s')",
        $mysqlidb->real_escape_string($src),
        $mysqlidb->real_escape_string($targ),
        $mysqlidb->real_escape_string($type),
        $mysqlidb->real_escape_string($text));
    $result = $mysqlidb->query($query);
    if (!$result) {
        postlog ("Error- Unable to post report from $src: $text");
    }
}


/*
 * showchat - retrieve entries from chats table with
 *  entry > last and prints them one to a line
 */
function showchat ($ai, $last) {
    global $mysqlidb;
    if (key_exists("created", $_SESSION)) {
        $account_created = $_SESSION["created"];
        if (secondlogon()) {
            print "X|Only one window may be logged into an account at a time\n";
            return;
        }
        $al = "";
        if (key_exists("alliance", $_SESSION)) {
            $al = $_SESSION["alliance"];
        }
        // get chat text from chats table
        //      only get those entries with target  of "all" or $ai
        //      or source = $ai or target of this ai's alliance
        $query = "select * from chats where entry>$last and postTime>='$account_created'"
                    . " and (target='all' or target='$ai' or source='$ai' or target='(GM)$ai' or source='(GM)$ai' or target='$al')"
                    . " and (source not in (select target from relations where source='$ai' and type=".RELATIONS_TYPE_BLOCK."))"
                    . " order by entry desc limit ".MAXCHAT;
        $result = $mysqlidb->query($query);
        // if it failed, it just means no new text since last request
        if ($result && ($result->num_rows > 0)) {
            while ($row = $result->fetch_assoc()) {
                $time = explode(" ", $row["postTime"]);
                $date = explode("-", $time[0]);
                if ($row['type'] == CHATTYPETELL) {
                    print "CT|{$row['entry']}|||{$date[1]}{$date[2]}-{$time[1]}>({$row['source']})/{$row['target']}: {$row['text']}\n";
                } else {
                    // format entry|C|channel|type|time>{source}text
                    print "CC|{$row['entry']}|{$row['channel']}|{$row['type']}|{$date[1]}{$date[2]}-{$time[1]}>({$row['source']}){$row['text']}\n";
                }
            }
        }
    }
}


?>

