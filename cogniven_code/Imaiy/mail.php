<?php
/*
 * Functions to deal with - in game mail
 * Author: Chris Bryant
 */

/*
 * printmaillist - prints list of mail
 *  from or too ai
 */
function printmaillist($ai, $last) {
    global $mysqlidb;
    $query = "select entry,mto,mfrom,sent,subject,toread from game_mail where entry>$last "
                . " and ((mto='$ai' and todeleted!=1) or (mfrom='$ai' and fromdeleted!=1))"
                . " and (mfrom not in (select target from relations where source='$ai' and type=".RELATIONS_TYPE_BLOCK."))"
                . " order by sent desc limit ".MAXMAIL;
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        while ($row = $result->fetch_row()) {
            if ($row[1] == $ai) {
                $type = 3;
                $mto = $row[1];
                $mfrom = "({$row[2]})";
            } else {
                $type = 1;
                $mto = "({$row[1]})";
                $mfrom = $row[2];
            }
            print "CM|{$row[0]}|$type|{$row[5]}|$mfrom>$mto: {$row[3]}: {$row[4]}\n";
        }
    }
}

/*
 * printmail - prints complete info of mail item
 */
function printmail($ai, $entry) {
    global $mysqlidb;
    $query = "select * from game_mail where (mto='$ai' or mfrom='$ai') and entry=$entry";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_assoc();
        if ($row["mto"] == $ai) {
            $type = 3;
        } else {
            $type = 1;
        }
        print "MM|{$row['entry']}|$type|{$row['mfrom']}|{$row['mto']}|{$row['sent']}|{$row['subject']}|{$row['body']}\n";
        if ($row["toread"] == "0") {
            $query = "update game_mail set toread=1 where entry=$entry";
            $result = $mysqlidb->query($query);
        }
    }
}



/*
 * deletemail - deletes specified mail
 */
function deletemail($ai, $entry) {
    global $mysqlidb;
    $rmsg = "";
    $query = "select mto,todeleted,mfrom,fromdeleted from game_mail where entry='$entry';";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_row();
        if (($row[0] == $ai) && ($row[1] == 0)) {
            $query = "update game_mail set todeleted=1 where entry='$entry';";
            $mysqlidb->query($query);
        }
        if (($row[2] == $ai) && ($row[3] == 0)) {
            $query = "update game_mail set fromdeleted=1 where entry='$entry';";
            $mysqlidb->query($query);
        }
    } else {
        $rmsg = "Error deleting mail. Error: Unable to locate mail record";
    }
    if ($rmsg != "") {
        postreport ($ai, 0, $rmsg);
    }
}

/*
 * sendmail - sends mail to specified target
 *  mail format is "to|copy|subject|body"
 */
function sendmail($ai, $mail) {
    global $mysqlidb;
    $mpieces = explode ("|", $mail);
    $mto = $mpieces[0];
    $msubject = str_replace("\n", "", str_replace(";", "", str_replace("'", "", $mpieces[2])));
    $mbody = str_replace("\n", ";", str_replace(";", "", str_replace("'", "", $mpieces[3])));

    $eai = $mysqlidb->real_escape_string($ai);
    $emto = $mysqlidb->real_escape_string($mto);
    $emsubject = $mysqlidb->real_escape_string($msubject);
    $embody = $mysqlidb->real_escape_string($mbody);

    $query = "select name from player where name='$mto';";
    $result = $mysqlidb->query($query);
    if (!$result || ($result->num_rows == 0)) {
        $rmsg = "Error posting mail to:$mto. No such Master AI";
    } else {
        $query = "insert into game_mail(mfrom,mto,subject,sent,body) values('$eai','$emto','$emsubject',now(),'$embody')";
        $result = $mysqlidb->query($query);
        if ($result) {
            $rmsg = "Posted mail to:$mto subject:$msubject";
        } else {
            $rmsg = "Error posting mail to:$mto subject:$msubject. Error:" . $mysqlidb->error;
        }
    }
    postreport ($ai, 0, $rmsg);
    $al = $_SESSION["alliance"];
    if (($mpieces[1] == "true") && ($al != "") && (strtolower($al) != "beginner")) {
        // copy to everyone in alliance, skip to and from players to avoid duplicates
        $query = "select name from player where alliance='$al';";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            while ($row = $result->fetch_row()) {
                if (($row[0] != $ai) && ($row[0] != $mto)) {
                    $queryc = sprintf("insert into game_mail(mfrom,mto,subject,sent,body) values('$eai','%s','$emsubject',now(),'$embody')",
                        $mysqlidb->real_escape_string($row[0]));
                    $resultc = $mysqlidb->query($queryc);
                    if ($resultc) {
                        $rmsg = "Copied mail to:{$row[0]} subject:$msubject";
                    } else {
                        $rmsg = "Error copying mail to:{$row[0]} subject:$msubject";
                    }
                    postreport ($ai, 0, $rmsg);
                }
            }
        }
    }
}

?>
