<?php
// routine for doing mass conversions and updates of database records - development only!
if (isset($_SERVER{'HTTP_HOST'})) {
    die ("Must run from cron or command line only!\n");
}
// get environment variables
if ($argc < 2) {
    print "Usage: CONVERTLOCS.php <database> <dbservierip>\n";
    return;
}
$gamedbase = $argv[1];
$gamedbserver = $argv[2];

include "globals.php";

$mysqlidb = new mysqli($gamedbserver, $gamedbase, DBPASS, $gamedbase);

// following flags control which section will execute
$doquad = false;
$docpoints = false;
$docoords = false;


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


if ($doquad == true) {
    /*
    * following is for setting new quadrant number column in all world locations
    */
    for ($idx = 0; $idx < BLOCKSIZE; $idx++) {
        $count = 0;
        print "Starting update of world location records in block $idx\n";
        $query = "select location from world where block=$idx";
        $result = $mysqlidb->query($query);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_row()) {
                $lparts = explode(".", $row[0]);
                $quad = 0;
                if ($lparts[1] > 49) {
                    $quad += 1;
                }
                if ($lparts[2] > 49) {
                    $quad += 2;
                }

                $query2 = "update world set quadrant=$quad where location='" . $row[0] . "'";
                $mysqlidb->query($query2);
                $count++;
            }
        }
        print "Completed update of $count world records in block $idx\n";
    }
}

if ($docpoints == true) {
    // control points are in sets of 5, a major point surrounded by 4 smaller points
    //  4 sets per block. minors[0] are to NW of major points, [1] are to NE,
    //      [2] are to SW and [3] are to SE
    //  each minor and major may have separate bonus (TBD)
    $minors[0] = array( "16.16", "32.16", "16.32", "32.32");
    $minors[1] = array( "66.16", "82.16", "66.32", "82.32");
    $minors[2] = array( "16.66", "32.66", "16.82", "32.82");
    $minors[3] = array( "66.66", "82.66", "66.82", "82.82");
    $majors    = array( "24.24", "74.24", "24.74", "74.74");
    $otypeset  = array( array(0, 1, 2, 3), array(0, 1, 3, 2), array(0, 2, 1, 3), array(0, 2, 3, 1), array(0, 3, 1, 2), array(0, 3, 2, 1),
                        array(1, 0, 2, 3), array(1, 0, 3, 2), array(1, 2, 0, 3), array(1, 2, 3, 0), array(1, 3, 0, 2), array(1, 3, 2, 0),
                        array(2, 0, 1, 3), array(2, 0, 3, 1), array(2, 1, 0, 3), array(2, 1, 3, 0), array(2, 3, 0, 1), array(2, 3, 1, 0),
                        array(3, 0, 1, 2), array(3, 0, 2, 1), array(3, 1, 0, 2), array(3, 1, 2, 0), array(3, 2, 0, 1), array(3, 2, 1, 0),
                        );
    $lvl12resgen = "750/750/750/750/0";
    $lvl16resgen = "1000/1000/1000/1000/0";

    print "Starting setting of world control points\n";
    for ($block = 0; $block <= MAXBLOCK; $block++) {
        for ($set = 0; $set < 4; $set++) {
            // determine random type order
            $tidx = rand(0, 23);
            for ($idx = 0; $idx < 4; $idx++) {
                $otype = $otypeset[$tidx][$idx] + 2;
                $loc = "$block.{$minors[$set][$idx]}";
                $query = "select controller from world where location='$loc'";
                $qresult = $mysqlidb->query($query);
                if ($qresult && ($qresult->num_rows > 0)) {
                    $row = $qresult->fetch_row();
                    if ($row[0] == "rogue") {
                        $queryb = "delete from bases where location='$loc'";
                        $qresultb = $mysqlidb->query($queryb);
                        if (!$qresultb) {
                            postlog("Unable to delete base at $loc: " . $mysqlidb->error);
                        }
                        $queryw = "update world set controller='system',o_type=$otype,res_gen='$lvl12resgen',res_store='0/0/0/0/0' where location='$loc'";
                        $qresultw = $mysqlidb->query($queryw);
                    } else if (($row[0] != "none") && ($row[0] != 'system')) {
                        postlog("$loc is controlled by ". $row[0]);
                    } else {
                        $queryw = "update world set controller='system',o_type=$otype,res_gen='$lvl12resgen',res_store='0/0/0/0/0' where location='$loc'";
                        $qresultw = $mysqlidb->query($queryw);
                    }
                } else {
                    postlog("Unable to get info for $loc: " . $mysqlidb->error);
                }
            }
        }
        $tidx = rand(0, 23);
        for ($idx = 0; $idx < 4; $idx++) {
            $otype = $otypeset[$tidx][$idx] + 6;
            $loc = "$block.{$majors[$idx]}";
            $query = "select controller from world where location='$loc'";
            $qresult = $mysqlidb->query($query);
            if ($qresult && ($qresult->num_rows > 0)) {
                $row = $qresult->fetch_row();
                if ($row[0] == "rogue") {
                    $queryb = "delete from bases where location='$loc'";
                    $qresultb = $mysqlidb->query($queryb);
                    if (!$qresultb) {
                        postlog("Unable to delete base at $loc: " . $mysqlidb->error);
                    }
                    $queryw = "update world set controller='system',o_type=$otype,res_gen='$lvl16resgen' where location='$loc'";
                    $qresultw = $mysqlidb->query($queryw);
                } else if (($row[0] != "none") && ($row[0] != 'system')) {
                    postlog("$loc is controlled by ". $row[0]);
                } else {
                    $queryw = "update world set controller='system',o_type=$otype,res_gen='$lvl16resgen' where location='$loc'";
                    $qresultw = $mysqlidb->query($queryw);
                }
            } else {
                postlog("Unable to get info for $loc: " . $mysqlidb->error);
            }
        }
    }
    print "Completed setting of world control points\n";
} // if ($docpoints == true)

if ($docoords == true) {
    print "Begin translating world block numbers\n";

    // remove sessions
    $query = "delete from sessions";
    $mysqlidb->query($query);

    // remove reports
    $query = "delete from reports";
    $mysqlidb->query($query);

    // remove chats
    $query = "delete from chats";
    $mysqlidb->query($query);

    // remove mail
    $query = "delete from game_mail";
    $mysqlidb->query($query);

    // remove chronicles
    $query = "delete from chronicle";
    $mysqlidb->query($query);

    // convert 10 x 10 block world into a 52 x 52 block world
    //  old block becomes # + column offset + (row offset)
    //  old block 0 is col 21 and row 21, (row offset = row * 52)
    // calculations may not be perfect, may not be optimal, but they work!
    $coloff = 21;
    $rowoff = 21;
    for ($block = 0; $block < 100; $block++) {
        $col = $block % 10;
        $row = floor($block / 10);
        $newcol = $col + $coloff;
        $newrow = $rowoff + $row;
        $newrowstart = ($newrow * 52);
        $newblock = $newcol + $newrowstart;
        $polblock = 'x';
        // ZZZZZZZZZZZZZZZZZZZZZZZZZZ ZZZZZZZZZZZZZZZZZZZZZZZZZZ
        // ZYYYYYYYYYYYYYYYYYYYYYYYYY YYYYYYYYYYYYYYYYYYYYYYYYYZ
        // ZYXXXXXXXXXXXXXXXXXXXXXXXX XXXXXXXXXXXXXXXXXXXXXXXXYZ
        // ZYXWWWWWWWWWWWWWWWWWWWWWWW WWWWWWWWWWWWWWWWWWWWWWWXYZ
        // ZYXWVVVVVVVVVVVVVVVVVVVVVV VVVVVVVVVVVVVVVVVVVVVVWXYZ
        // ZYXWVUUUUUUUUUUUUUUUUUUUUU UUUUUUUUUUUUUUUUUUUUUVWXYZ
        // ZYXWVUTTTTTTTTTTTTTTTTTTTT TTTTTTTTTTTTTTTTTTTTUVWXYZ

        // 00000000001111111111222222 22223333333333444444444455
        // 01234567890123456789012345 67890123456789012345678901
        //
        //  0000000000111111111122222 22222333333333344444444445
        // Z0123456789012345678901234 56789012345678901234567892
        //
        //   000000000011111111112222 22222233333333334444444455
        // ZY012345678901234567890123 45678901234567890123456703
        //
        //    00000000001111111111222 22222223333333333444444455
        // ZYX01234567890123456789012 34567890123456789012345814
        //
        //     0000000000111111111122 22222222333333333344444455
        // ZYXW0123456789012345678901 23456789012345678901236925
        if ($newrow < 26) {
            $width = 26 - $newrow;
        } else {
            $width = $newrow - 25;
        }
        if ($newcol < 26) {
            if ($newcol < (26-$width)) {
                $polidx = 25 - $newcol;
            } else {
                $polidx = $width - 1;
            }
        } else {
            if ($newcol > (25+$width)) {
                $polidx = $newcol - 26;
            } else {
                $polidx = $width - 1;
            }
        }
        $qinsectionrow = 2 * $width;
        $qinsectioncol = 2 * ($width - 1);
        $qinsection = (2 * $qinsectionrow) + (2 * $qinsectioncol);
        if ($newcol < 26) {
            if ($newcol < (26-$width)) {
                // left column
                if ($newrow < 26) {
                    // top of left column
                    $polblock = -((3 * ($newcol - 25 - $width)) + (2 * $width) - 1) + ((4 * $polidx) + 2);
                } else {
                    // bottom of left column
                    $polblock = -((3 * ($newcol - 25 - $width)) + ((4 * $width) - 2)) + ((4 * $polidx) + 2);
                }
            } else {
                if ($newrow < 26) {
                    // left side of top row
                    $polblock = $newcol - (26-$width);
                } else {
                    // left side bottom row
                    $polblock =  ($qinsection - $qinsectioncol - 1) - ($newcol - (26-$width));
                }
            }
        } else {
            if ($newcol > (25+$width)) {
                // right column
                if ($newrow < 26) {
                    // top of right column
                    $polblock = (3 * ($newcol - 25 - $width)) + (2 * $width) - 1;
                } else {
                    // bottom of right column
                    $polblock =  (3 * ($newcol - 25 - $width)) + ((4 * $width) - 2);
                }
            } else {
                if ($newrow < 26) {
                    // right side of top row
                    $polblock = $newcol - (26-$width);
                } else {
                    // right side of bottom row
                    $polblock =  ($qinsection - $qinsectioncol - 1) - ($newcol - (26-$width));
                }
            }
        }

        $polchar = chr(ord("A") + $polidx);

        echo "block $block -> $polchar$polblock\n";

        $query = "select location from world where block=$block";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            while ($row = $result->fetch_row()) {
                $locarr = explode(".", $row[0]);
                $newloc = "$newblock.{$locarr[1]}.{$locarr[2]}";
                $disploc = "$polchar$polblock.{$locarr[1]}.{$locarr[2]}";

                $query = "update world set location='$newloc',block=$newblock,section='$polchar',dlocation='$disploc' where location='{$row[0]}'";
                $ures = $mysqlidb->query($query);
                if (!$ures) {
                    echo "Failed $query\n";
                }

                // change location of bases
                $query = "update bases set location='$newloc',dlocation='$disploc' where location='{$row[0]}'";
                $mysqlidb->query($query);

                // scan all PC bases for locs containing $row[0] and replace with $newloc
                $query = "select location,locs from bases where controller!='rogue'";
                $bresult = $mysqlidb->query($query);
                if ($bresult && ($bresult->num_rows > 0)) {
                    while ($brow = $bresult->fetch_row()) {
                        $changed = false;
                        $larr = explode(",", $brow[1]);
                        for ($idx = count($larr)-1; $idx >= 0; $idx--) {
                            if ($larr[$idx] == $row[0]) {
                                if ($changed == true) {
                                    $larr[$idx] = ""; // duplicate
                                } else {
                                    $larr[$idx] = "$newloc:$disploc";
                                    $changed = true;
                                }
                            }
                        }
                        if ($changed == true) {
                            $locs = trim(str_replace(",,", ",", implode(",", $larr)), ", ");

                            $query = "update bases set locs='$locs' where location='{$brow[0]}'";
                            $mysqlidb->query($query);
                        }
                    }
                }

                // update formations baseloc, targetloc and sourceloc
                $query = "update formations set baseloc='$newloc',basedloc='$disploc' where baseloc='{$row[0]}'";
                $mysqlidb->query($query);
                $query = "update formations set targetloc='$newloc',targetdloc='$disploc' where targetloc='{$row[0]}'";
                $mysqlidb->query($query);
                $query = "update formations set sourceloc='$newloc',sourcedloc='$disploc' where sourceloc='{$row[0]}'";
                $mysqlidb->query($query);

                // update trade_queue baseloc
                $query = "update trade_queue set baseloc='$newloc',basedloc='$disploc' where baseloc='{$row[0]}'";
                $mysqlidb->query($query);

                // update timer_queue targetloc and sourceloc
                $query = "update timer_queue set targetloc='$newloc',targetdloc='$disploc' where targetloc='{$row[0]}'";
                $mysqlidb->query($query);
                $query = "update timer_queue set sourceloc='$newloc',sourcedloc='$disploc' where sourceloc='{$row[0]}'";
                $mysqlidb->query($query);
            }
        }
        // update locs and fix drudgeais for all rogue bases
        $query = "update bases set locs=concat(location,':',dlocation),drudgeais=replace(drudgeais,';',','),defenses='' where controller='rogue'";
        $mysqlidb->query($query);

        // clear saved locations
        $query = "update player set savelocs=''";
        $mysqlidb->query($query);
    }

    print "Completed translating world block numbers\n";
} // if ($docoords == true)


?>
