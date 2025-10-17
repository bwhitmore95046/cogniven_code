<?php
/*
 * Functions to deal with trades
 * Author: Chris Bryant
 */


/*
 * getorderline -
 *  retrieve array of orders of requested type sorted in
 *      requested order.
 *      $type is 1 for sell and 2 for buy
 *      $order must be "asc" or "desc"
 *  format of each entry is "item;amount:price;amount:price;..."
 *  combines all orders with same price and sums the amounts
 */
function getorderline ($type, $item, $order) {
    global $mysqlidb;
    $orders = "$item;";
    $query = "select sum(amount),price from trade_queue where type=$type and item='$item' group by price order by price $order limit 5";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        while (($row = $result->fetch_row()) != false) {
            $orders .= "$row[0]:$row[1];";
        }
    }
    return $orders;
}

/*
 * gettradequeueinfo
 *  returns array of two items
 *      number of trades currently posted
 *      max trades that can be posted
 */
function gettradequeueinfo($ai, $location, $modules) {
    global $mysqlidb;

    $numinprogress = 0;
    $query = "select count(*) from trade_queue where baseloc='$location' and poster='$ai'";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_row();
        $numinprogress = $row[0];
    }

    $tradelevel = getmodulelevel(MODULE_TRADING, $modules);
    $qsize = floor(($tradelevel + 1) / 2);

    return array($numinprogress, $qsize);
}

/*
 * getmyorderlines -
 *  retrieve array of orders posted by $ai
 *
 *  returns associative array with index of base location and
 *      value which is string of "num trades posted{order1{order2{..."
 *
 *  format of each order is "entry:type:item:amount:price"
 *  combines all orders with same price and sums the amounts
 */
function getmyorderlines ($ai) {
    global $mysqlidb;

    $orders = array();
    $counts = array();

    $query = "select baseloc,entry,type,item,amount,price from trade_queue where poster='$ai' order by entry asc";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        while (($row = $result->fetch_row()) != false) {
            if ($row[2] == 2) {
                $type = "B";
            } else {
                $type = "S";
            }
            if (!isset($orders[$row[0]])) {
                $orders[$row[0]] = "";
                $counts[$row[0]] = 0;
            }
            $orders[$row[0]] .= "$row[1]:$type:$row[3]:$row[4]:$row[5]{";
            $counts[$row[0]]++;
        }
    }
    // prepend queue values in beginning of $orders array elements
    foreach ($counts as $key=>$val) {
        $orders[$key] = $val . "{" . $orders[$key];
    }
    return $orders;
}



/*
 * dotrade - performs post of new trade order and list of existing
 *      trades.
 *  ignores invalid buy/sell orders
 */
function dotrade ($ai, $request, $item, $amount, $price, $baseloc) {
    global $mysqlidb;
    global $res_name; global $comp_name;
    $request = strtolower($request);
    $tradecount = 0;
    $key = "";
    $requeststr = "";

    $baseloc = convertdloctoloc($baseloc);

    if (($request != "") && ($request != "list")) {
        if ((($request == "tradebuy") || ($request == "tradesell"))
                && ((($amount * $price) < TRANSMIN) || ($item == ""))) {
            postreport($ai, 1, "Unable to post trade as no item specified or value of trade less than minimum value of ".TRANSMIN." credites");
            return;
        }
        $query = "select controller,dlocation,res_store,components,infra from bases where location='$baseloc' and controller='$ai' for update";
        $result = $mysqlidb->query($query);
        if (!$result || ($result->num_rows == 0)) {
            postreport($ai, 1, "Unable to post trade as can not locate base controlled by $ai");
            return;
        }
        $rowbase = $result->fetch_assoc();
        $dlocation = $rowbase["dlocation"];

        $tradecount = 0;
        if ($request != "cancel") {
            $query = "select count(*) from trade_queue where poster='$ai'";
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0)) {
                $row = $result->fetch_row();
                $tradecount = $row[0];
            }
        }
        $modlevel = getmodulelevel(MODULE_TRADING, $rowbase["infra"]);
        if ($tradecount >= $modlevel) {
            postreport($ai, 1, "Unable to post trade as already have max number ($tradecount) of trades posted");
        } else { // not at max trades
            $type = 0;
            $action = "Post";
            switch ($request) {
                case "tradebuy":
                    $requeststr = "Buy";
                    $type = 2;
                    $ogc = 0;
                    $gc = 0;
                    // remove credits from player account plus transaction fee
                    // round amount up to next highest integer
                    $cost = ceil($amount * $price * TRANSMULT);
                    $query = "select gcredits from player where name='$ai' for update";
                    $result = $mysqlidb->query($query);
                    if ($result && ($result->num_rows > 0)) {
                        $row = $result->fetch_row();
                        $gc = $row[0];
                    }
                    if ($gc < $cost) {
                        $ogc = number_format($gc);
                        $cc = number_format($cost);
                        $type = -1;
                    } else {
                        $ngc = $gc - $cost;
                        $query = "update player set gcredits=$ngc where name='$ai';";
                        $result = $mysqlidb->query($query);
                        if (!$result) {
                            $type = -2;
                        }
                    }
                    break;
                case "tradesell":
                    $requeststr = "Sell";
                    $type = 1;
                    // remove item from base - translate item into key
                    $keyidx = array_search($item, $res_name);
                    if ($keyidx === false) {
                        // must be component
                        $keyidx = array_search($item, $comp_name);
                        if ($keyidx === false) {
                            // invalid item name
                            $type = -3;
                        } else {
                            $newres = $rowbase["res_store"];
                            $comparr = explode(";", $rowbase["components"]);
                            $found = false;
                            $newcomp = "";
                            foreach ($comparr as $compstr) {
                                $comp = explode(":", $compstr);
                                if (count($comp) > 1) {
                                    if ($newcomp != "") {
                                        $newcomp .= ";";
                                    }
                                    if ($comp[0] == $keyidx) {
                                        if ($comp[1] < $amount) {
                                            $type = -4;
                                        } else {
                                            $found = true;
                                            $newcomp .= $comp[0] . ":" . ($comp[1]-$amount);
                                        }
                                    } else {
                                        $newcomp .= $compstr;
                                    }
                                }
                            } // foreach ($comparr as $compstr)
                            if ($found == false) {
                                $type = -4;
                            }
                        }
                    } else {
                        $resarr = explode("/", $rowbase["res_store"]);
                        if ($resarr[$keyidx] < $amount) {
                            $type = -4;
                        } else {
                            $newcomp = $rowbase["components"];
                            $resarr[$keyidx] = (int) ($resarr[$keyidx] - $amount);
                            $newres = implode("/", $resarr);
                        }
                    }
                    if ($type > 0) {
                        $query = "update bases set res_store='$newres',components='$newcomp' where location='$baseloc'";
                        $result = $mysqlidb->query($query);
                        if (!$result) {
                            $type = -5;
                        }
                    }
                    break;
                case "tradedonate":
                    $requeststr = "Donate";
                    $type = 3;
                    // remove item from base - translate item into key
                    $keyidx = array_search($item, $res_name);
                    if ($keyidx === false) {
                        // must be component - can not donate components
                        $type = -6;
                    } else {
                        $resarr = explode("/", $rowbase["res_store"]);
                        if ($resarr[$keyidx] < $amount) {
                            $type = -4;
                        } else {
                            $newcomp = $rowbase["components"];
                            $resarr[$keyidx] = (int) ($resarr[$keyidx] - $amount);
                            $newres = implode("/", $resarr);
                        }
                    }
                    if ($type > 0) {
                        $query = "update bases set res_store='$newres',components='$newcomp' where location='$baseloc'";
                        $result = $mysqlidb->query($query);
                        if (!$result) {
                            $type = -5;
                        }
                    }
                    break;
                case "tradecancel":
                    $requeststr = "Cancel";
                    $type = 4;
                    $action = "Cancel";
                    $key = $item;
                    $query = "select item,price,amount,type from trade_queue where poster='$ai' and baseloc='$baseloc' and entry=$key";
                    $result = $mysqlidb->query($query);
                    if (!$result) {
                        $type = 0;
                    } else {
                        $row = $result->fetch_row();
                        $item = $row[0];
                        $price = $row[1];
                        $amount = $row[2];
                        if ($row[3] == 2) {
                            $request = "buy";
                            // put credits back into player record
                            $cost = ceil($amount * $price);
                            $query = "select gcredits from player where name='$ai' for update";
                            $result = $mysqlidb->query($query);
                            if (!$result) {
                                $type = 0;
                            } else {
                                $row = $result->fetch_row();
                                $ngc = $row[0] + $cost;
                                $query = "update player set gcredits=$ngc where name='$ai';";
                                $result = $mysqlidb->query($query);
                                if (!$result) {
                                    $type = -2;
                                }
                            }
                        } else {
                            $request = "sell";
                            // put item back into base record
                            $keyidx = array_search($item, $res_name);
                            if ($keyidx === false) {
                                // must be component
                                $keyidx = array_search($item, $comp_name);
                                if ($keyidx === false) {
                                    // invalid item name
                                    $type = -3;
                                } else { // is component
                                    $newres = $rowbase["res_store"];
                                    $comparr = explode(";", $rowbase["components"]);
                                    $found = false;
                                    $newcomp = "";
                                    foreach ($comparr as $compstr) {
                                        $comp = explode(":", $compstr);
                                        if (count($comp) > 1) {
                                            if ($newcomp != "") {
                                                $newcomp .= ";";
                                            }
                                            if ($comp[0] == $keyidx) {
                                                $found = true;
                                                $newcomp .= $comp[0] . ":" . ($comp[1]+$amount);
                                             } else {
                                                $newcomp .= $compstr;
                                             }
                                        }
                                    } // foreach ($comparr as $compstr)
                                    if ($found == false) {
                                        if ($newcomp != "") {
                                            $newcomp .= ";";
                                        }
                                        $newcomp .= "$keyidx:$amount";
                                    }
                                } // is component
                            } else {
                                $resarr = explode("/", $rowbase["res_store"]);
                                $newcomp = $rowbase["components"];
                                $resarr[$keyidx] = (int) ($resarr[$keyidx] + $amount);
                                $newres = implode("/", $resarr);
                            }
                            if ($type > 0) {
                                $query = "update bases set res_store='$newres',components='$newcomp' where location='$baseloc'";
                                $result = $mysqlidb->query($query);
                                if (!$result) {
                                    $type = -5;
                                }
                            }
                        }
                    }
                    break;
                default:
                    break;
            }
            if ($type > 0) {
                if ($type == 4) {
                    $query = "delete from trade_queue where poster='$ai' and entry=$key";
                } else {
                    $query = "insert into trade_queue (type,poster,baseloc,basedloc,item,price,amount) values($type,'$ai','$baseloc','$dlocation','$item','$price','$amount')";
                }
                $result = $mysqlidb->query($query);
                if (!$result) {
                    $type = 0;
                } else {
                    $astr = number_format($amount);
                    $rep = "{$action}ed $requeststr trade order for $astr of $item";
                    if ($type != 3) {
                        // donation ignores price
                        $rep .= " at $price";
                    }
                    if (($type == 1) || ($type == 2)) {
                        // only buy/sell have associated fees
                        $rep .= " (transaction fee of " . TRANSFEE . ")";
                    }
                    postreport($ai, 1, $rep);
                }
            }
            if ($type <= 0) {
                $mysqlidb->rollback();
                // must post following reports after transaction rolled back.
                $fail = "Unable to $action trade,";
                switch ($type) {
                    case -1:
                        postreport($ai, 1, "$fail available credits ($ogc) less than cost ($cc)");
                        break;
                    case -2:
                        postreport($ai, 1, "$fail error updating Master AI record");
                        break;
                    case -3:
                        postreport($ai, 1, "$fail invalid item $item");
                        break;
                    case -4:
                        postreport($ai, 1, "$fail insufficient amount of $item in base at $dlocation");
                        break;
                    case -5:
                        postreport($ai, 1, "$fail error updating base record");
                        break;
                    case -6:
                        postreport($ai, 1, "$fail only resource donations are accepted");
                        break;
                    default:
                        break;
                }
            }
        } // not at max trades
    } // if ($request != "list")
}


?>
