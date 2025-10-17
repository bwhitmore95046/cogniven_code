<?php
    // This file is assumed to only be called from Index.php
    if (!isset($_SESSION['logged_in'])) {
        require_once "globals.php";
        header('Location: ' . GAME_PORTAL_EXTERNAL);
    }
    ob_start();
    if (!isset($UserEmail)) {
        die("Must login to access this page.");
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
    <head>
        <title>Battle for IMAIY</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="copyright" content="&copy;2010-2012 Cogniven Studios Inc. All rights reserved" />
        <meta name="author" content="Cogniven Studios, Inc" />
        <meta name="robots" content="noarchive, nofollow" />
        <meta name="googlebot" content="noarchive, nofollow" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <link rel="stylesheet" type="text/css" href="css/gform.css" />
        <link href='https://fonts.googleapis.com/css?family=Imprima' rel='stylesheet' type='text/css' />
        <link href='https://fonts.googleapis.com/css?family=Orbitron' rel='stylesheet' type='text/css' />
        <?php
            include "genfilelist.php";
            if (is_file("js/combined.js")) {
                echo "<script type='text/javascript' src='js/combined.js'></script>\n";
            } else {
                $farr = explode(";", genfilelist("js", "js", ""));
                asort($farr); // make sure loaded in alpha order
                foreach ($farr as $file) {
                    echo "<script type='text/javascript' src='js/$file'></script>\n";
                }
            }
        ?>
    </head>
    <body>
        <?php
            getplayerinfo($UserEmail, "");
            echo "<div id='misc_info' class='noshow'>\n";
            echo "<input type='text' id='selectedbase' value='' /></div>\n";
        ?>
        <div id="outerdiv" class="noscroll">
            <div id="overlaydiv"><img id="overlayimg" class="overlay" src="graphics/Overlay.png" /></div>
            <div id="box_topcenter">
                <table class="innertable"><tbody><tr>
                    <td class="colplayeravatar">
                        <img id="playeravatar" class="plyravatar" src="graphics/avatars/blank.png" alt="Avatar picture" title="Avatar picture" />
                    </td>
                    <td id="colplayer">
                        <ul id="plyrlist">
                        <li class="plyrline"><label class="plyrname" id="playername"></label></li>
                        <li class="plyrline"><label class="plyrtext">Level: </label><label class="plyrtext" id='playerlevel'></label><label class="plyrtext"> | Power: </label><label class="plyrtext" id='playerpower'></label></li>
                        <li class="plyrline"><label class="plyrtext">Renown: </label><label class="plyrtext" id='playerrenown'></label></li>
                        <li class="plyrline"><label class="plyrtext">Org: </label><label class="plyrtext" id='playeralliance'></label></li>
                        <li class="plyrline"><label class="plyrtext">Status: </label><label class="plyrtext" id='playerstatus'></label></li>
                        </ul>
                    </td>
                </tr></tbody></table>
            </div>
            <div id="box_topright">
                <?php
                    if (enhancedfeatureaccess()) {
                        echo "\n<div id='colchattertabbedupper'>";
                        echo "<div id='chattertab2' class='chattertabupper nohide'><ul></ul></div>";
                        echo "<div id='chattertab6' class='chattertabupper noshow'><ul></ul></div>";
                        echo "<div id='chattertab7' class='chattertabupper noshow'><ul></ul></div>";
                        echo "<div id='chattertab8' class='chattertabupper noshow'><ul></ul></div>";
                        echo "<div id='chattertab9' class='chattertabupper noshow'><ul></ul></div>";
                        echo "</div>\n";
                    } else {
                        echo "\n<div id='advert_right'>\n";
                        echo "<script type='text/javascript'><!--\n";
                        echo "google_ad_client = 'ca-pub-1974448694481474';\n";
                        echo "/* MedRect */\n";
                        echo "google_ad_slot = '0590801172';\n";
                        echo "google_ad_width = 300;\n";
                        echo "google_ad_height = 250;\n";
                        echo "//--></script><script type='text/javascript'\n";
                        echo "src='http://pagead2.googlesyndication.com/pagead/show_ads.js'>\n";
                        echo "</script>\n";
                        echo "</div>\n";
                    }
                ?>
            </div>
            <div id="box_bottomright">
                <table class="innertable"><tbody>
                    <tr id="rowchattertabbedlower"><td id="colchattertabbedlower">
                        <div id="chattertab1" class="chattertablower nohide"><ul></ul></div>
                        <div id="chattertab3" class="chattertablower noshow"><ul></ul></div>
                        <div id="chattertab4" class="chattertablower noshow"><ul></ul></div>
                        <div id="chattertab5" class="chattertablower noshow"><ul></ul></div>
                        <?php
                            if (!enhancedfeatureaccess()) {
                                echo "<div id='chattertab2' class='chattertablower noshow'><ul></ul></div>";
                                echo "<div id='chattertab6' class='chattertablower noshow'><ul></ul></div>";
                            }
                        ?>
                    </td></tr>
                    <tr id="rowchatterpost">
                        <td>
                            <input type="text" id="chattertarg" class="aboveoverlay" maxlength='36' />
                            <input type="text" id="chattergt" maxlength="1" value=">" readonly="readonly" />
                            <input type="text" id="chattertext" maxlength='250' class="chattertext aboveoverlay" />
                            <div id="chatterpost-div"></div>
                        </td>
                    </tr>
                    <tr><td id="chat_pinfo_div" class="grid_horizontal"></td></tr>
                    <tr><td>
                    </td></tr>
                </tbody></table>
            </div>
            <div id="box_serverclock">
                <?php
                    $sname = explode(".", $_SERVER['SERVER_NAME']);
                    echo "<div class='servername'>{$sname[0]}</div>";
                    echo "<div id='timedisp' class='clock'>".date('H:i:s')."</div>\n";
                ?>
            </div>
            <div id="box_topleft">
                <?php
                    if (enhancedfeatureaccess()) {
                        echo "\n<div id='tactical'>";
                        echo "</div>\n";
                    } else {
                        echo "\n<div id='advert_left'>\n";
                        echo "<script type='text/javascript'><!--\n";
                        echo "google_ad_client = 'ca-pub-1974448694481474';\n";
                        echo "/* MedRect */\n";
                        echo "google_ad_slot = '0590801172';\n";
                        echo "google_ad_width = 300;\n";
                        echo "google_ad_height = 250;\n";
                        echo "//--></script><script type='text/javascript'\n";
                        echo "src='http://pagead2.googlesyndication.com/pagead/show_ads.js'>\n";
                        echo "</script>\n";
                        echo "</div>\n";
                    }
                ?>
                <div><table class="innertable" cellspacing="0"><tbody>
                    <tr class="rowloc">
                        <td class="loclabel"><label>Saved:</label></td>
                        <td id="locmenusavedcell"></td>
                        <td><div id="savelocedit" class="nohide"></div></td>
                        <td><div id="savelocdel" class="nohide"></div></td>
                    </tr>
                    <tr class="rowloc">
                        <td class="loclabel"><label>Control:</label></td>
                        <td id="locmenucontrolledcell"></td>
                        <td></td><td></td>
                    </tr>
                </tbody></table></div>
            </div>
            <div id="box_bottomleft"></div>
            <div id="mapdisp" class="map">
                <img id="mapgrid" class="map" alt="" src="graphics/map_grid.png"/>
            </div>
            <div id="box_bottomcenter">
                <?php
                for ($idx = MAXBASES-1; $idx >= 0; $idx--) {
                    echo "<table class='baseButton-table'><tbody>\n";
                    echo "<tr><td><p class='blabel' id='BASEL$idx'></p></td></tr>\n";
                    echo "<tr><td><div class='baseButton-div' id='BASEBD$idx'><img src='graphics/map_icons.png' class='baseButton-img' id='BASEB$idx' alt='' /></div></td></tr>\n";
                    echo "<tr><td><div class='bcaption-div' id='BASEBC$idx'></div></td></tr>\n";
                    echo "<tr><td><p class='bcaption-label' id='BASECL$idx'></p></td></tr>\n";
                    echo "</tbody></table>\n";
                }
                ?>
            </div>
            <div id="box_links">
                <a href="https://games.cogniven.com" target="_blank" title="To server portal"><img class="logolinkimg" src="graphics/Cogniven logo.png" /></a>
                <div class="guidelinks""><a href="https://games.cogniven.com/node/3" target="_blank" title="To FAQ">FAQ</a><br/>
                <a href="https://games.cogniven.com/node/83" target="_blank" title="To Guide">GUIDE</a></div>
            </div>
            <div id="box_marquee" class="marq_container">
                <label class="marq_text">Welcome to the Battle for Imaiy! Don't forget to claim your daily supplies. Use the Claim button in the list window.</label>
            </div>
            <div id="box_copyright">
                <p class="copyright">Copyright &copy;2010-2012 by Cogniven Studios Inc. All rights reserved.
                    (<?php include "revision.txt"; ?>)
                </p>
            </div>
            <div id="float_curlocdisplay">
                <div id="gotolocdiv" class="locrow-div"></div>
                <input type="text" id="gototext0" class="locinputb noerror aboveoverlay" maxlength="3" name="Block" />
                <input type="text" id="gototext1" class="locinput noerror aboveoverlay" maxlength="2" name="X" />
                <input type="text" id="gototext2" class="locinput noerror aboveoverlay" maxlength="2" name="Y" />
                <div id="infolocdiv" class="locrow-div"></div>
                <div id="stagingdiv" class="locrow-div"></div>
            </div>
            <div id="box_topleft_buttons"></div>
            <div id="box_topright_buttons"></div>
            <div id="box_bottomleft_buttons"></div>
            <?php
                if (enhancedfeatureaccess()) {
                    echo "<div id='box_bottomright_buttons1'></div>";
                } else {
                    echo "<div id='box_bottomright_buttons2'></div>";
                }
            ?>
            <div id="box_bottomleft2_buttons"></div>
            <p id="debug_stat"></p>
        </div>
        <div>
            <div id="hlight-div" class="novis"><div id="hlight-ddiv"><img id="hlight-img" src="graphics/map_buttons.png" alt="" /></div></div>
            <div id="zoomout-div" class="zoomdiv"><div id="zoomout-ddiv"><img id="zoomout-img" src="graphics/map_buttons.png" class="zoomimg novis" alt="map zoom out"/></div></div>
            <div id="zoomin-div" class="zoomdiv"><div id="zoomin-ddiv"><img id="zoomin-img" src="graphics/map_buttons.png" class="zoomimg novis" alt="map zoom in"/></div></div>
            <div id="mapleft-div" class="zoomdiv"><div id="mapleft-ddiv"><img id="mapleft-img" src="graphics/map_buttons.png" class="zoomimg novis" alt="shift map view left"/></div></div>
            <div id="mapright-div" class="zoomdiv"><div id="mapright-ddiv"><img id="mapright-img" src="graphics/map_buttons.png" class="zoomimg novis" alt="shift map view right"/></div></div>
            <div id="mapup-div" class="zoomdiv"><div id="mapup-ddiv"><img id="mapup-img" src="graphics/map_buttons.png" class="zoomimg novis" alt="shift map view up"/></div></div>
            <div id="mapdown-div" class="zoomdiv"><div id="mapdown-ddiv"><img id="mapdown-img" src="graphics/map_buttons.png" class="zoomimg novis" alt="shift map view down"/></div></div>
        </div>
        <div id="strings" class="noshow">
            <?php
                include "text/names_html.inc.{$_SESSION["lang"]}";
                include "text/descriptions_html.inc.{$_SESSION["lang"]}";
                echo "<p id='avatar_list'>".genfilelist("graphics/avatars", "png,jpg", str_replace("@", ".at.",$_SESSION["email"]))."</p>\n";
                echo "<p id='orgav_list'>".genfilelist("graphics/orgs", "png,jpg", str_replace("@", ".at.",$_SESSION["email"]))."</p>\n";

                session_commit(); // must be last php line
            ?>
        </div>
    </body>
</html>

