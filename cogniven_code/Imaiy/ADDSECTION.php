<?php
// routine for adding blocks for new section to world table
if (isset($_SERVER{'HTTP_HOST'})) {
    die ("Must run from cron or command line only!\n");
}
// get environment variables
if ($argc < 3) {
    print "Usage: CONVERTLOCS.php <section> <database> <dbservierip>\n";
    return;
}
$section = $argv[1];
$gamedbase = $argv[2];
$gamedbserver = $argv[3];

include "globals.php";

$mysqlidb = new mysqli($gamedbserver, $gamedbase, DBPASS, $gamedbase);

if (ord($section) < ord('A') || (ord($section) > ord('Z'))) {
    print "Section must be letter from A to Z\n\n";
    return;
}

$query = "select count(*) from world where section='$section'";
$result = $mysqlidb->query($query);
if ($result && ($result->num_rows > 0)) {
    $row = $result->fetch_row();
    if ($row[0] != 0) {
        print "Section $section already exists in world table\n\n";
        return;
    }
}
    // create default modules strings
    for ($level = 1; $level <= MAXBASELEVEL; $level++) {
        $modules[$level] = "";
        for ($mod = 0; $mod < MODULE_ARRAY_SIZE-1; $mod++) {
            $modules[$level] .= "$level:";
        }
        $modules[$level] .= "$level";
    }

    // update maxsection ini value if necessary
    $query = "select 'maxsection' from ini)";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_row();
        if (ord($row[0]) < ord($section)) {
            $query = "update ini set value='$section' where variable='maxsection'";
            $mysqlidb->query($query);
        }
    } else {
        $query = "insert into ini (variable,value,phpneed) values ('maxsection', '$section', '1')";
        $mysqlidb->query($query);
    }

    // determine number of blocks in this section
    $numblocksinrow = 2 * ((1 + ord($section) - ord('A')));
    $numblocksincol = $numblocksinrow - 2; // excluding top/bottom rows

    $numblocks = (2 * $numblocksinrow) + (2 * $numblocksincol);
    print "Creating $numblocks blocks in section $section\n";

    $row = ord('Z') - ord($section);
    $col = $row;

    $blockstr = ""; // for use in setting control points
    $rogue_count = 0; // count of number of rogue bases

    for ($bidx = 0; $bidx < $numblocks; $bidx++) {
        // increment row/coll
        if ($bidx > 0) {
            if ($bidx < $numblocksinrow) {
                // top row
                $col++;
            } else if ($bidx < ($numblocksinrow + $numblocksincol + 1)) {
                // right side column
                $row++;
            } else if ($bidx < ($numblocksinrow + $numblocksincol + $numblocksinrow)) {
                // bottow row
                $col--;
            } else {
                // left side column
                $row--;
            }
        }
        // determine coord block number
        $newblock = ($row * 52) + $col;
        $polchar = $section;
        $polblock = $bidx;

        // following is copy of imaiy_dbtablefill.php code
        $blockstr .= ",$newblock"; // for use in setting control points
        print "Filling block $newblock ($polchar$polblock)\n";

        for ($loc_x = 0; $loc_x < BLOCKSIZE; $loc_x++)
        {
            for ($loc_y = 0; $loc_y < BLOCKSIZE; $loc_y++)
            {
                // create random resource generation levels
                $r1 = mt_rand(1,MAX_RESGEN_RATE);
                $r2 = mt_rand(1,MAX_RESGEN_RATE);
                $r3 = mt_rand(1,MAX_RESGEN_RATE);
                $r4 = mt_rand(1,MAX_RESGEN_RATE);
                $resgenstr = "$r1/$r2/$r3/$r4/0";

                $newloc = "$newblock.$loc_x.$loc_y";
                $disploc = "$polchar$polblock.$loc_x.$loc_y";

                // make random 5% be rogue bases
                if (mt_rand(1,100) <= 5) {
                    $cont = "rogue";
                    $bname = genrandomname();
                    $type = 1;
                    $rogue_count++;
                    $level = mt_rand(1,MAXBASELEVEL);

                    // create 5 random drudgeAIs with $ailevel = base level * rand(1,5)
                    //  set random base stats of rand(1,50)
                    //  boost base primary stats for each by $ailevel;
                    //  set current stats to base stats with primary current stat adding $ailevel
                    for ($idx = 0, $drudgeais = ""; $idx < 5; $idx++) {
                        $name = "dai_" . $disploc . "_" . $idx;
                        $ailevel = $level * mt_rand(1,5);
                        $ca = mt_rand(1,50); if ($idx == 0) $ca += $ailevel;
                        $cc = mt_rand(1,50); if ($idx == 1) $cc += $ailevel;
                        $ch = mt_rand(1,50); if ($idx == 2) $ch += $ailevel;
                        $ct = mt_rand(1,50); if ($idx == 3) $ct += $ailevel;
                        $cm = mt_rand(1,50); if ($idx == 4) $cm += $ailevel;
                        $query="insert into drudgeai(name,level,canalysis,ccontrol,cheuristics,ctactics,cmultitasking) values('$name',$ailevel,$ca,$cc,$ch,$ct,$cm);";
                        $result = $mysqlidb->query($query);
                        if (!$result)
                        {
                            $err = $mysqlidb->error;
                            echo "$query = $err\n";
                        } else  {
                            // then save entry #s in drudgeai list of rogue base
                            if ($drudgeais != "") {
                                $drudgeais .= ",";
                            }
                            $drudgeais .= $mysqlidb->insert_id;
                        }
                    }

                    $query="insert into bases(location,dlocation,controller,level,name,locs,drudgeais,infra) "
                                . "values('$newloc','$disploc','$cont',$level,'$bname','$newloc:$disploc','$drudgeais','{$modules[$level]}');";
                    $qresult = $mysqlidb->query($query);
                    if (!$qresult)
                    {
                        $err = $mysqlidb->error;
                        echo "$query = $err\n";
                    }
                }
                else {
                    $cont = "none";
                    $type = 0;
                }

                $query="insert into world(location,dlocation,block,section,controller,o_type,res_gen) "
                            . "values('$newloc','$disploc',$newblock,'$polchar','$cont',$type,'$resgenstr');";
                $qresult = $mysqlidb->query($query);
                if (!$qresult)
                {
                    $err = $mysqlidb->error;
                    echo "$query = $err\n";
                }
            } // for ($loc_y = 0; $loc_y < BLOCKSIZE; $loc_y++)
        } // for ($loc_x = 0; $loc_x < BLOCKSIZE; $loc_x++)

        print "  Completed filling block $newblock ($polchar$polblock) of size " . BLOCKSIZE . " x " . BLOCKSIZE . "\n";
    } // for ($bidx = 0; $bidx < $numblocks; $bidx++)
    print "Completed filling world table for section $section included " . number_format($rogue_count) . " rogue bases in $bidx blocks\n";

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
    $blockarr = explode(",", $blockstr);

    foreach ($blockarr as $block) {
        if ($block != "") {
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
                            $queryw = "update world set controller='system',o_type=$otype,res_gen='$lvl12resgen' where location='$loc'";
                            $qresultw = $mysqlidb->query($queryw);
                        } else if (($row[0] != "none") && ($row[0] != 'system')) {
                            postlog("$loc is controlled by ". $row[0]);
                        } else {
                            $queryw = "update world set controller='system',o_type=$otype,res_gen='$lvl12resgen' where location='$loc'";
                            $qresultw = $mysqlidb->query($queryw);
                        }
                    } else {
                        postlog("Unable to get info for $loc: " . $mysqlidb->error);
                    }
                }
            } // for ($set = 0; $set < 4; $set++)
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
            } // for ($idx = 0; $idx < 4; $idx++)
        } // if ($block != "")
    } // foreach ($blockarr as $block)
    print "Completed setting of world control points\n";

/*
 * create a name comprised of random letters and numbers
 */
function genrandomname() {
    $minlen = 6;
    $maxlen = 12;
    $chars = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9"
                , "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K"
                , "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V"
                , "W", "X", "Y", "Z", "a", "b", "c", "d", "e", "f", "g"
                , "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r"
                , "s", "t", "u", "v", "w", "x", "y", "z");
    $name = "";
    $maxidx = count($chars) - 1;
    $end = mt_rand($minlen, $maxlen);
    for ($idx = 0; $idx < $end; $idx++) {
        $name .= $chars[mt_rand(0, $maxidx)];
    }
    return $name;
}

?>
