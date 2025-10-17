<?php
    /*
     * Functions to deal with central information requests
     * Author: Chris Bryant
     */
    ob_start();
    if (!empty($_GET) || !empty($_POST)) {
        include "globals.php";
        include "bases.php";
        include "central.php";
        include "chat.php";
        include "chronicle.php";
        include "component.php";
        include "defenses.php";
        include "drone.php";
        include "formations.php";
        include "goals.php";
        include "lists.php";
        include "mail.php";
        include "module.php";
        include "player.php";
        include "recipes.php";
        include "store.php";
        include "tech.php";
        include "timer.php";
        include "trade.php";
        include "world.php";

        // if we don't have an AI name we can't process anything
        if (array_key_exists("name", $_SESSION)) {
            // main command type
            $request = "";
            if (array_key_exists("request", $_GET)) {
                $request = $_GET["request"];
            } else if (array_key_exists("request", $_POST)) {
                $request = $_POST["request"];
            }

            // a few common parameters
            $change = "";
            if (array_key_exists("change", $_GET)) {
                $change = $_GET["change"];
            } else if (array_key_exists("change", $_POST)) {
                $change = $_POST["change"];
            }
            // stage info is too big and must only be via post
            $stageinfo = "";
            if (array_key_exists("stageinfo", $_POST)) {
                $stageinfo = $_POST["stageinfo"];
            }
            $op = "";
            if (array_key_exists("op", $_GET)) {
                $op = $_GET["op"];
            } else if (array_key_exists("op", $_POST)) {
                $op = $_POST["op"];
            }
            $baseloc = "";
            if (array_key_exists("baseloc", $_GET)) {
                $baseloc = $_GET["baseloc"];
            } else if (array_key_exists("baseloc", $_POST)) {
                $baseloc = $_POST["baseloc"];
            }

            switch ($request) {
                case "datapoll":
                    // get additional parameters
                    $lastseenchat = "0";
                    if (array_key_exists("clast", $_GET)) {
                        $lastseenchat = $_GET["clast"];
                    } else if (array_key_exists("clast", $_POST)) {
                        $lastseenchat = $_POST["clast"];
                    }
                    $lastseenreport = "0";
                    if (array_key_exists("rlast", $_GET)) {
                        $lastseenreport = $_GET["rlast"];
                    } else if (array_key_exists("rlast", $_POST)) {
                        $lastseenreport = $_POST["rlast"];
                    }
                    $lastseenmail = "0";
                    if (array_key_exists("mlast", $_GET)) {
                        $lastseenmail = $_GET["mlast"];
                    } else if (array_key_exists("mlast", $_POST)) {
                        $lastseenmail = $_POST["mlast"];
                    }
                    $lastseenchronicle = "0";
                    if (array_key_exists("hlast", $_GET)) {
                        $lastseenchronicle = $_GET["hlast"];
                    } else if (array_key_exists("hlast", $_POST)) {
                        $lastseenchronicle = $_POST["hlast"];
                    }

                    $force = "";
                    if (array_key_exists("force", $_GET)) {
                        $force = $_GET["force"];
                    } else if (array_key_exists("force", $_POST)) {
                        $force = $_POST["force"];
                    }
                    if ($force == "") {
                        $force = 1; // don't hold back with data
                    }
                    // if no center positon provided default to current center
                    $center = "";
                    if (array_key_exists("center", $_GET)) {
                        $center = $_GET["center"];
                    } else if (array_key_exists("center", $_POST)) {
                        $center = $_POST["center"];
                    }
                    // if no delta given then default to 0,0
                    $delta = "";
                    if (array_key_exists("delta", $_GET)) {
                        $delta = $_GET["delta"];
                    } else if (array_key_exists("delta", $_POST)) {
                        $delta = $_POST["delta"];
                    }
                    if ($delta == "") {
                        $delta = "0,0";
                    }
                    if (($center != "") && ($delta != "0,0")) {
                        $loc = explode(".", $center);
                        $del = explode(",", $delta);
                        // transform center map coords by delta
                        $loc = shiftcoords($loc[0], $loc[1], $loc[2], $del[0], $del[1]);
                        // Set globals to be the new location
                        $center = "$loc[0].$loc[1].$loc[2]";
                    }
                    showreport ($_SESSION["name"], $lastseenreport);
                    showchat ($_SESSION["name"], $lastseenchat);
                    printmaillist($_SESSION["name"], $lastseenmail);
                    printchroniclelist($_SESSION["name"], $lastseenchronicle);
                    updatebaseinfo($_SESSION["name"], "", "basestatus", "", "", $force);
                    if ($center != "") {
                        mapshow ($_SESSION["name"], $request, $center, $force);
                    }
                    requestcentralinfo($_SESSION["name"], $request, $change, $stageinfo);
                    break;
                case "postbase":
                    $newloc = "";
                    if (array_key_exists("newloc", $_GET)) {
                        $newloc = $_GET["newloc"];
                    } else if (array_key_exists("newloc", $_POST)) {
                        $newloc = $_POST["newloc"];
                    }
                    $bname = "";
                    if (array_key_exists("basename", $_GET)) {
                        $bname = $_GET["basename"];
                    } else if (array_key_exists("basename", $_POST)) {
                        $bname = $_POST["basename"];
                    }
                    $type = "";
                    if (array_key_exists("type", $_GET)) {
                        $type = $_GET["type"];
                    } else if (array_key_exists("type", $_POST)) {
                        $type = $_POST["type"];
                    }

                    updatebaseinfo($_SESSION["name"], $baseloc, $op, $bname, $newloc, $type);
                    break;
                case "postcentral":
                    requestcentralinfo($_SESSION["name"], $op, $change, $stageinfo);
                    break;
                case "postchat":
                    $channel = 0;
                    if (array_key_exists("channel", $_GET)) {
                        $channel = $_GET["channel"];
                    } else if (array_key_exists("channel", $_POST)) {
                        $channel = $_POST["channel"];
                    }
                    if (($channel >= CHANNELSYSTEM) && ($channel <= CHATTYPETELL)) {
                        $text = "";
                        if (array_key_exists("text", $_GET)) {
                            $text = $_GET["text"];
                        } else if (array_key_exists("text", $_POST)) {
                            $text = $_POST["text"];
                        }

                        if (strlen($text) > 0) {
                            if (array_key_exists("targ", $_GET)) {
                                $targ = str_replace("[\n\'\"]", " ", strip_tags($_GET["targ"]));
                            } else if (array_key_exists("targ", $_POST)) {
                                $targ = str_replace("[\n\'\"]", " ", strip_tags($_POST["targ"]));
                            } else {
                                $targ = $_SESSION["name"];
                            }
                            // make text safe by stripping any html and php tags
                            // replace carriage returns and quotes with spaces
                            $text = htmlspecialchars($text);
                            postchat ($_SESSION["name"], $channel, $targ, $text);
                        }
                    }
                    break;
                case "postdrone":
                    if (array_key_exists("drones", $_GET)) {
                        $drones = $_GET["drones"];
                    } else if (array_key_exists("drones", $_POST)) {
                        $drones = $_POST["drones"];
                    } else {
                        $drones = "";
                    }
                    droneaction($_SESSION["name"], $op, $baseloc, $drones);
                    break;
                case "postmap":
                    $force = "";
                    if (array_key_exists("force", $_GET)) {
                        $force = $_GET["force"];
                    } else if (array_key_exists("force", $_POST)) {
                        $force = $_POST["force"];
                    }
                    if ($force == "") {
                        $force = 1; // don't hold back with data
                    }
                    if ($op != "") {
                        mapshow ($_SESSION["name"], $op, "", $force);
                    }
                    break;
                case "postmodule":
                    if (array_key_exists("type", $_GET)) {
                        $type = $_GET["type"];
                    } else if (array_key_exists("type", $_POST)) {
                        $type = $_POST["type"];
                    } else {
                        $type = MODULE_NONE;
                    }
                    moduleaction($_SESSION["name"], $op, $baseloc, $type);
                    break;
                case "poststore":
                    if (array_key_exists("items", $_GET)) {
                        $items = $_GET["items"];
                    } else if (array_key_exists("items", $_POST)) {
                        $items = $_POST["items"];
                    } else {
                        $items = "";
                    }
                    dostore ($_SESSION["name"], $op, $items);
                    break;
                case "posttrade":
                    if (array_key_exists("item", $_GET)) {
                        $item = $_GET["item"];
                    } else if (array_key_exists("item", $_POST)) {
                        $item = $_POST["item"];
                    } else {
                        $item = "";
                    }
                    if (array_key_exists("quantity", $_GET)) {
                        $quantity = $_GET["quantity"];
                    } else if (array_key_exists("quantity", $_POST)) {
                        $quantity = $_POST["quantity"];
                    } else {
                        $quantity = "";
                    }
                    if (array_key_exists("price", $_GET)) {
                        $price = $_GET["price"];
                    } else if (array_key_exists("price", $_POST)) {
                        $price = $_POST["price"];
                    } else {
                        $price = "";
                    }
                    dotrade ($_SESSION["name"], $op, $item, $quantity, $price, $baseloc);
                    break;
            }
        }
        session_commit();
    }
?>
