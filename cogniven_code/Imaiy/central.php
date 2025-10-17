<?php
/*
 * Functions to deal with central information retrieval - game stats
 * Author: Chris Bryant
 */


function requestcentralinfo($ai, $request, $change, $stageinfo) {
    global $mysqlidb;
    global $timer_name; // timer.php

    switch ($request) {
        case "grantitem":
            // only those with GM access can grant items
            if ((floor($_SESSION["rank"]) & 4) != 0) {
                grantitem($ai, $change);
            }
            break;
        case "printlog":
            // only those with GM access can view logs
            if ((floor($_SESSION["rank"]) & 4) != 0) {
                printlog($change);
            }
            break;
        case "goalclaim":
            goalclaim($ai, $change);
            break;
        case "claimdaily":
            claimdaily($ai);
            break;
        case "savelocs":
            updatesavelocs ($ai, $change);
            break;
        case "pendcancel":
            // insert cancel event, change=baseloc:key:type
            $cp = explode(":", $change);
            if (count($cp) > 2) {
                createtimedentry( 5, TIMER_CANCEL_EVENT, $ai, $cp[0], $cp[1], $cp[2], "", "", "", "", "", "");
                postreport($ai, 0, "Request to cancel ".$timer_name[$cp[2]]." submitted");
            }
            break;
        case "datapoll":
            printcentralpoll($ai, $change);
            getlists($ai, $change);
            modulestatoract($ai, $change, $stageinfo);
            break;
        case "savesettings":
            $ca = explode("|", $change);
            $query = sprintf("update player set settings='%s',bio='%s' where name ='$ai'",
                $mysqlidb->real_escape_string(strip_tags($ca[0])),
                $mysqlidb->real_escape_string(strip_tags(substr($ca[1], 0, 1000))));
            $result = $mysqlidb->query($query);
            if ($result && ($mysqlidb->affected_rows > 0)) {
                postreport($ai, 0, "Settings saved");
            }
            break;
        case "alliancerole":
            $parts = explode(":", $change);
            if ((count($parts) > 2) && ($parts[1] != "")) {
                $alliance = new Alliance();
                $alliance->open($_SESSION["alliance"]);
                $error_code = 1;
                if ($parts[0] == "add") {
                    $error_code = $alliance->make_officer($ai, $parts[1], $parts[2]);
                    $report = "$parts[1] is now a $parts[2].";
                } else if ($parts[0] == "remove") {
                    $error_code = $alliance->remove_officer($ai, $parts[1], $parts[2]);
                    $report = "$parts[1] is no longer a $parts[2].";
                }
                if ($error_code != 1) {
                    $error_string = alliance_code_text($error_code);
                    $report = "Failed to change role for $parts[1].  $error_string.";
                } else {
                    postchat($ai, CHANNELALLIANCE-1, $_SESSION["alliance"], $report);
                }
                postreport($ai, REPORT_ALLIANCE, $report);
                print get_alliance_info($ai, $_SESSION["alliance"]) . "\n";
            }
            break;
        case "allianceapplication":
            $parts = explode(":", $change);
            if ((count($parts) > 1) && ($parts[1] != "")) {
                $alliance = new Alliance();
                $alliance->open($_SESSION["alliance"]);
                $report = "Application accepted from $parts[1].";
                if ($parts[0] == "accept") {
                    $error_code = $alliance->accept_application($ai, $parts[1]);
                    if (($error_code != 1) && ($error_code != 6)) {
                        $error_string = alliance_code_text($error_code);
                        $report = "Failed to accept application from $parts[1].  $error_string.";
                    } else {
                        postchat($ai, CHANNELALLIANCE-1, $_SESSION["alliance"], "$parts[1] is now a member of {$_SESSION["alliance"]}");
                    }
                    postreport($ai, REPORT_ALLIANCE, $report);
                } else if ($parts[0] == "reject") {
                    $error_code = $alliance->reject_application($ai, $parts[1]);
                    if ($error_code == 1) {
                        postreport($parts[1], REPORT_ALLIANCE, "Application to {$_SESSION["alliance"]} rejected");
                    }
                }
                print get_alliance_info($ai, $_SESSION["alliance"]) . "\n";
            }
            break;
        case "allianceapply":
            //$change = alliance|resume
            $apply = explode("|", $change);
            $report = "Application submitted to $apply[0].";

            $alliance = new Alliance();
            $alliance->open($apply[0]);
            $error_code = $alliance->submit_application($ai, $apply[1]);
            if ($error_code != 1) {
                $error_string = alliance_code_text($error_code);
                $report = "Failed to submit application to $apply[0].  $error_string.";
            }
            postreport($ai, REPORT_ALLIANCE, $report);
            postlog($ai.": ".$report." Code: $error_code");
            break;
        case "alliancewithdraw":
            $alliance = new Alliance();
            $alliance->open($change);
            $alliance->withdraw_application($ai);
            break;
        case "alliancenotes":
            $alliance = new Alliance();
            $alliance->open($_SESSION["alliance"]);
            $parts = explode("|", $change);
            if (count($parts) > 2) {
                $report = "Updated notes.";
                $error_code = $alliance->set_link_and_notes($ai, $parts[0], $parts[1], $parts[2], $parts[3]);
                if ($error_code != 1) {
                    $error_string = alliance_code_text($error_code);
                    $report = "Failed to update notes.  $error_string.";
                } else {
                    print get_alliance_info($ai, $_SESSION["alliance"]) . "\n";
                }
                postreport($ai, REPORT_ALLIANCE, $report);
            }
            break;
        case "allianceinfo":
            //change=alliance name
            print get_alliance_info($ai, $change) . "\n";
            break;
        case "alliancecreate":
            //change equals type:new alliance name
            $cparts = explode(":", $change);
            $target = $cparts[1];
            $report = "Organization '{$cparts[1]}' successfully created.";

            $alliance = new Alliance();
            $alliance->open($cparts[1]);
            $error_code = $alliance->create($ai, $cparts[0]);

            if ($error_code != 1)
            {
                //unsuccessful action
                $target = $ai;
                $error_string = alliance_code_text($error_code);
                $report = "Failed to create organization '{$cparts[1]}'.  $error_string.";
            } else {
                postchat($ai, CHANNELALLIANCE-1, $cparts[1], $report);
            }

            postreport($target, REPORT_ALLIANCE, $report);
            postlog("$ai: $report Code: $error_code");
            break;
        case "allianceupgrade":
            $cparts = explode(":", $change);
            $target = $cparts[1];
            $report = "Organization '{$cparts[1]}' successfully upgraded.";

            $alliance = new Alliance();
            $alliance->open($_SESSION["alliance"]);
            $error_code = $alliance->upgrade($ai, $cparts[0]);
            if ($error_code != 1)
            {
                //unsuccessful action
                $target = $ai;
                $error_string = alliance_code_text($error_code);
                $report = "Failed to upgrade organization '{$cparts[1]}'.  $error_string.";
            } else {
                postchat($ai, CHANNELALLIANCE-1, $cparts[1], $report);
            }

            postreport($target, REPORT_ALLIANCE, $report);
            postlog("$ai: $report Code: $error_code");
            break;
        case "alliancedisband":
            //change is blank.
            $report = "Organization '{$_SESSION['alliance']}' successfully disbanded.";
            $alliance = new Alliance();
            $alliance->open($_SESSION["alliance"]);
            $error_code = $alliance->disband($ai);

            if ($error_code != 1) {
                //unsuccessful action
                $error_string = alliance_code_text($error_code);
                $report = "Failed to disband organization '{$_SESSION['alliance']}'.  $error_string.";
            }

            postreport($ai, REPORT_ALLIANCE, $report);
            postlog($ai.": ".$report." Code: $error_code");
            break;
        case "alliancetransfer":
            $alliance = new Alliance();
            $alliance->open($_SESSION["alliance"]);
            $report = "$change is now owner of {$_SESSION['alliance']}.";
            $error_code = $alliance->change_owner($ai, $change);
            if ($error_code != 1) {
                //unsuccessful action
                $error_string = alliance_code_text($error_code);
                $report = "Failed to transfer ownership to $change.  $error_string.";
            } else {
                postchat($ai, CHANNELALLIANCE-1, $_SESSION["alliance"], $report);
            }
            postreport($ai, 0, $report);
            print get_alliance_info($ai, $_SESSION["alliance"]) . "\n";
            break;
        case "alliancekick":
            $alliance = new Alliance();
            $alliance->open($_SESSION["alliance"]);
            $report = "$change is no longer a member of {$_SESSION['alliance']}.";
            $error_code = $alliance->kick_member($ai, $change);
            if ($error_code != 1) {
                //unsuccessful action
                $error_string = alliance_code_text($error_code);
                $report = "Failed to kick member $change from {$_SESSION['alliance']}.  $error_string.";
            } else {
                postchat($ai, CHANNELALLIANCE-1, $_SESSION["alliance"], $report);
            }
            postreport($ai, 0, $report);
            print get_alliance_info($ai, $_SESSION["alliance"]) . "\n";
            break;
        case "allianceleave":
            if ($_SESSION["alliance"] == "") {
                break; // ignore if not in alliance
            }
            $alliance = new Alliance();
            $alliance->open($_SESSION["alliance"]);
            if ($alliance->is_officer($ai) == 1) {
                echo "x|You must resign as officer or owner before you can leave your organization.";
            } else {
                $report = "Left Organization {$_SESSION['alliance']}.";
                $error_code = $alliance->leave_alliance($ai);
                if ($error_code != 1) {
                    //unsuccessful action
                    $error_string = alliance_code_text($error_code);
                    $report = "Failed to leave organization {$_SESSION['alliance']}.  $error_string.";
                } else {
                    postchat($ai, CHANNELALLIANCE-1, $_SESSION['alliance'], $report);
                }
                postreport($ai, 0, $report);
            }
            break;
        case "deleteaccount":
            $rmsg = "";  //declared for scope
            $log = "";  //declared for scope
            $query = sprintf("select email from player where name='%s'",
                $mysqlidb->real_escape_string($ai));
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0))
            {
                $do_delete = true;
                $row = $result->fetch_row();
                $email = $row[0];

                $query = sprintf("select deletion_time from last_delete where email='%s'",
                            $mysqlidb->real_escape_string($email));
                $result = $mysqlidb->query($query);
                if ($result && ($result->num_rows > 0))
                {
                    //entry exists, find how long ago it was added
                    //elapsed time = current time - deletion time
                    //time_remaining = Minimum time - (current time - deletion time)
                    $row = $result->fetch_row();
                    $time_remaining = (MINIMUM_HOURS_BETWEEN_DELETES * 60 * 60) - (time() - strtotime($row[0]));
                    if ($time_remaining > 0)
                    {
                        $do_delete = false;
                        $time_remaining = seconds_to_hours($time_remaining);
                        $rmsg = "x|Failed to delete '$ai'.  You must wait $time_remaining[2] hour(s), $time_remaining[1] minute(s) and $time_remaining[0] second(s) before deleting your AI again.";
                    }
                }

                if ($do_delete)
                {
                    $codes = delete_ai($ai);
                    if ($codes[0])
                    {
                        //no errors
                        $rmsg = "X|'$ai' successfully deleted.";
                        $log = "$email: successfully deleted '$ai'.";
                    }
                    else
                    {
                        $rmsg = "X|Error while deleting '$ai'.  Please report this to a system administrator.";
                        $log = "$email: Failed to completely delete '$ai'.";
                        $index = 1;
                        while (isset($codes[$index]))
                        {
                            $log .= " | " . $codes[$index];
                            $index++;
                        }
                    }
                }
            }
            postlog($log);
            print $rmsg . "\n";
            break;
        case "relations":
            changerelation($ai, $change);
            break;
        case "formation":
            $cparts = explode("=", $change);
            if (($cparts[0] == "R") || ($cparts[0] == "S")) { // recall formation
                recallformation($ai, $cparts[1]);
            }
            if ($cparts[0] == "I") { // show incoming formations
                printincformations($ai, $cparts[1]);
            }
            break;
        case "printchronicle":
            printchronicle($ai, $change);
            break;
        case "deletechronicle":
            deletechronicle($ai, $change);
            break;
        case "publishchronicle":
            publishchronicle($ai, $change);
            break;
        case "tagchronicle":
            tagchronicle($ai, $change);
            break;
        case "sendmail":
            sendmail($ai, $change);
            break;
        case "deletemail":
            deletemail($ai, $change);
            break;
        case "printmail":
            printmail($ai, $change);
            break;
        case "useitem":
            // $change format is "item:value:quantity;target"
            $cp = explode(";", $change);
            if (count($cp) > 1) {
                useitem($ai, $cp[0], $cp[1]);
            }
            break;
        case "postlog":
            postlog("$ai> $change");
            break;
        default:
            postlog("Unknown information request from $ai> $request:$change");
            break;
    }
}

/*
 * changerelation
 *  apply a request relation change
 */
function changerelation($ai, $change) {
    global $mysqlidb;

    if ($change != "") {
        $al = $_SESSION["alliance"];
        $query = "";
        $parts = explode(":", $change);
        if (count($parts) > 2) {
            $target = str_replace("(GM)", "", $parts[2]);
            if ((($parts[1] == "P") && ($ai == $target))
                    || ($parts[1] == "O") && ($al == $target)) {
                // can't do relations with yourself
                return;
            }
            $failmsg = "Unable to change relation with $target at this time. Must wait 24 hours between changes";

            $time = time();
            if ($parts[0] == "A") {
                // annull relation
                $time -= SECSINDAY;
                if ($parts[1] == "P") {
                    $query = "delete from relations where type=1 and source='$ai' and target='$target' and established<$time";
                    $successmsg = "$ai annulled relationship with $target";
                } else {
                    $alliance = new Alliance();
                    $alliance->open($al);
                    if ($alliance->is_officer($ai, DIPLOMAT) == 1) {
                        $query = "delete from relations where type=2 and source='$al' and target='$target' and established<$time";
                        $successmsg = "$ai annulled organization $al relationship with $target";
                    }
                }
            } else if ($parts[0] == "F") {
                // make friends
                if ($parts[1] == "P") {
                    $query="insert into relations(source,target,type,status,established) values('$ai','$target',".RELATIONS_TYPE_PERSONAL.",".RELATIONS_STATUS_FRIEND.",$time)";
                    $successmsg = "$ai now considers $target a friend";
                } else {
                    $alliance = new Alliance();
                    $alliance->open($al);
                    if ($alliance->is_officer($ai, DIPLOMAT) == 1) {
                        $query="insert into relations(source,target,type,status,established) values('$al','$target',".RELATIONS_TYPE_ALLIANCE.",".RELATIONS_STATUS_FRIEND.",$time)";
                        $successmsg = "$ai declared that organization $al now considers $target a friend";
                    }
                }
            } else if ($parts[0] == "E") {
                // make enemenies
                if ($parts[1] == "P") {
                    $query="insert into relations(source,target,type,status,established) values('$ai','$target',".RELATIONS_TYPE_PERSONAL.",".RELATIONS_STATUS_ENEMY.",$time)";
                    $successmsg = "$ai now considers $target an enemy";
                } else {
                    $alliance = new Alliance();
                    $alliance->open($al);
                    if ($alliance->is_officer($ai, DIPLOMAT) == 1) {
                        $query="insert into relations(source,target,type,status,established) values('$al','$target',".RELATIONS_TYPE_ALLIANCE.",".RELATIONS_STATUS_ENEMY.",$time)";
                        $successmsg = "$ai declared that organization $al now considers $target an enemy";
                    }
                }
            } else if ($parts[0] == "B") {
                // block em
                if ($parts[1] == "P") {
                    $query="insert into relations(source,target,type,status,established) values('$ai','$target',".RELATIONS_TYPE_BLOCK.",".RELATIONS_STATUS_BLOCK.",$time)";
                    $successmsg = "$ai is now blocking $target";
                    $failmsg = "Can not block $target as they are already being blocked";
                }
            } else if ($parts[0] == "U") {
                // unblock em
                if ($parts[1] == "P") {
                    $query="delete from relations where type=".RELATIONS_TYPE_BLOCK." and status=".RELATIONS_STATUS_BLOCK." and source='$ai' and target='$target'";
                    $successmsg = "$ai is no longer blocking $target";
                    $failmsg = "Can not unblock $target as they are not being blocked";
                }
            }
        }

        if ($query != "") {
            $result = $mysqlidb->query($query);
            if ($result && ($mysqlidb->affected_rows > 0)) {
                postreport ($ai, 0, $successmsg);
                // no notification if block or unblock
                if (($parts[0] != "B") && ($parts[0] != "U")) {
                    if (($parts[1] == "P")) {
                        postchat($ai, CHANNELTELL-1, $parts[2], $successmsg);
                    } else {
                        postchat($ai, CHANNELALL-1, "all", $successmsg);
                    }
                }
            } else {
                postreport ($ai, 0, $failmsg);
                if ($mysqlidb->error) {
                    postlog($query . ": " . $mysqlidb->error);
                }
            }
        } else if (($parts[1] == "O") && ($al != "")) {
            postreport ($ai, 0, "Must be a Diplomat with $al to change relations with $target");
        }
    } // if ($change)
}

/*
 * printcentralpoll -
 *  print poll info for specific displays
 *  these are lists that apply to master AI
 *      and not to a specific base
 */
function printcentralpoll($ai, $section) {
    global $mysqlidb;

    $ops = explode(":", $section);
    if (count($ops) < 2) {
        $ops[1] = "";
    }
    if (count($ops) > 3) {
        // ops[0] is comma separated list of commands
        // ops[1] is ignored
        // ops[2] is sortstyle for mai and org lists
        // ops[3] is ignored
        $cmds = explode(",", $ops[0]);
        foreach ($cmds as $cmd) {
            $list = array();
            $listidx = 0;
            $refresh = false;
            $seckey = "0";
            switch ($cmd) {
                case "refreshtasks":
                    $refresh = true;
                case "gettasks":
                    $list = array();
                    $gl = getgoals($ai);
                    foreach ($gl as $goal) {
                        $list[$listidx] = $goal;
                        $listidx++;
                    }
                    $seckey = "tasks";
                    break;
                case "refreshmais":
                    $refresh = true;
                case "getmais":
                    $list[$listidx] = getplayersline($ops[2]);
                    $listidx++;
                    // if ai flagged as having access to GM functions then show
                    //  list of grantable items from store
                    if ((floor($_SESSION["rank"]) & 4) != 0) {
                        $grantstr = "";
                        $query = "select item from store order by class asc";
                        $result = $mysqlidb->query($query);
                        if ($result && ($result->num_rows > 0)) {
                            while (($row = $result->fetch_row()) != null) {
                                $grantstr .= $row[0] . ":";
                            }
                        }
                        $list[$listidx] = "GRANT|" . $grantstr;
                        $listidx++;
                    }
                    $seckey = "mais";
                    break;
                case "refreshorgs":
                    $refresh = true;
                case "getorgs":
                    $apps = array();
                    $apps["a"] = "";
                    $query = "select alliance,submitted from alliance_applications where applicant='$ai'";
                    $result = $mysqlidb->query($query);
                    if ($result && ($result->num_rows > 0)) {
                        while ($row = $result->fetch_row()) {
                            $apps[$row[0]] = $row[1];
                        }
                    }

                    $alist = get_alliance_list($ops[2]);
                    if ($alist == NULL) {
                        $list[$listidx] = "a|No alliances found";
                        $listidx++;
                    } else {
                        $list[$listidx] = "A|";
                        $index = 0;
                        while (isset($alist[$index]))
                        {
                            $list[$listidx] .= $alist[$index]['name']."|".$alist[$index]['owner']."|".$alist[$index]['created']."|".$alist[$index]['type']."|";
                            if (array_key_exists($alist[$index]['name'], $apps)) {
                                $list[$listidx] .= $apps[$alist[$index]['name']];
                            }
                            $list[$listidx] .= "|".$alist[$index]['power']."|".$alist[$index]['renown']."|";
                            $index++;
                        }
                        $listidx++;
                    }
                    $seckey = "orgs";
                    break;
                case "refreshrels":
                    $refresh = true;
                case "getrels":
                    $al = $_SESSION["alliance"];
                    $query = "select * from relations where ((type=".RELATIONS_TYPE_PERSONAL
                            . ") and (source='$ai' or target='$ai')) or ((type="
                            .RELATIONS_TYPE_BLOCK . ") and (source='$ai')) or ((type="
                            .RELATIONS_TYPE_ALLIANCE.") and (source='$al' or target='$al')) order by source asc";
                    $result = $mysqlidb->query($query);
                    if (!$result || ($result->num_rows == 0)) {
                        $list[$listidx] = "r|No relations found";
                        $listidx++;
                    } else {
                        $list[$listidx] = "R|";
                        while ($row = $result->fetch_assoc()) {
                            $list[$listidx] .= "{$row['source']}|{$row['target']}|{$row['type']}|{$row['status']}|";
                            $delta = SECSINDAY - (time() - $row['established']);
                            if ($delta > 0) {
                                if ((($row['type'] == RELATIONS_TYPE_PERSONAL) && ($row['source'] == $ai))
                                    || (($row['type'] == RELATIONS_TYPE_ALLIANCE) && ($row['source'] == $al))) {
                                    $list[$listidx] .= formatduration($delta);
                                }
                            }
                            $list[$listidx] .= "|";
                        }
                        $listidx++;
                    }
                    $seckey = "rels";
                    break;
                case "refreshsets":
                    $refresh = true;
                case "getsets":
                    $list[$listidx] = "SET|" . $_SESSION["settings"];
                    $listidx++;
                    $list[$listidx] = "BIO|" . str_replace("\n", "\r", $_SESSION["bio"]);
                    $listidx++;
                    $seckey = "sets";
                    break;
                case "refreshitems":
                    $refresh = true;
                case "getitems":
                    // Items
                    $list[$listidx] = "I|" . $_SESSION["items"];
                    $listidx++;
                    $seckey = "items";
                    break;
                case "refresheffs":
                    $refresh = true;
                case "geteffs":
                    // Temporary Effects
                    $list[$listidx] = "TE|" . getbufflist();
                    $listidx++;
                    $seckey = "effs";
                    break;
            }
            if ($listidx > 0) {
                printdeltas("socialinfo", $seckey, $refresh, $list);
            }
        }
    }
}


/*
 * printlog
 *  echos a plain text log file with each line separated by "|"
 *  filename will have .log extension appended and must be in
 *      logs subdirectory.
 */
function printlog($filename) {
    $str = "PL|$filename|";

    $farr = explode("\n", file_get_contents("logs/$filename"));
    foreach ($farr as $fline) {
        $str .= "$fline|";
    }
    echo "$str\n";
}

?>
