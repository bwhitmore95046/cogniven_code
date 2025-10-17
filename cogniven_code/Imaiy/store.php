<?php
/*
 * Functions to deal with store
 * Author: Chris Bryant
 */

// connection to account dbase
$mysqliadb = null;

/*
 * itemlist - retrieves a list of items by getting a distinct list of item
 *      column values from the trade_queue.
 *  if none found then returns default string
 */
function storeitemlist() {
    global $mysqlidb;
    $liststr = "";

    $query = "select item,price,class from store order by class asc";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        while (($row = $result->fetch_row()) != null) {
            $liststr .= "{$row[0]}:{$row[1]}:{$row[2]};";
        }
    } else {
        $liststr = ":No Items Found::;";
    }

    return $liststr;
}


/*
 * dostore - performs list of items and purchase actions
 */
function dostore ($ai, $request, $items) {
    $request = strtolower($request);

    switch ($request) {
        case "list":
            $list = storeitemlist();
            echo "SI|$list\n";
            $credits = getstorecredits($ai, $_SESSION["email"]);
            echo "SC|$credits\n";
            break;
        case "purchase":
            $credits = getstorecredits($ai, $_SESSION["email"]);
            $credits = storepurchase($ai, $_SESSION["email"], $items, $credits);
            echo "SC|$credits\n";
            break;
        default:
            break;
    } // switch ($request)
}


/*
 * storepurchase -
 *  execute purchase of specified items - deducts credits from local
 *  server creds first
 *      $items is in format "key:quantity;key:quantity;..."
 *      $credits in form "game portal creds|local server creds"
 */
function storepurchase($ai, $email, $items, $credits) {
    global $mysqlidb;
    global $mysqliadb;
    global $item_name;
    
    $carr = explode("|", $credits);
    $havecredits = floor($carr[0]) + floor($carr[1]);
    // create associative array for items
    $iarr = explode(";", $items);
    $items = null;
    foreach ($iarr as $istr) {
        $item = explode(":", $istr);
        if (count($item) > 1) {
            $items[$item[0]] = $item[1];
        }
    }
    if ($items != null) {
        // determine cost for items.
        $totalcost = 0;
        $itemlist = "";
        $codestr = "";
        $query = "select * from store order by class asc";
        $result = $mysqlidb->query($query);
        if ($result && ($result->num_rows > 0)) {
            while (($row = $result->fetch_assoc()) != null) {
                $ikey = $row['item'];
                if (key_exists($ikey, $items)) {
                    $quantity = $items[$ikey];
                    $totalcost += ($quantity * $row['price']);
                    if ($itemlist != "") {
                        $itemlist .= ", ";
                        $codestr .= ";";
                    }
                    $itemlist .= $items[$ikey] . " " . $item_name[$ikey];
                    $codestr .= $row['code'] . ":" . $quantity;
                }
            }
        }
        if ($itemlist != "") {
            if ($totalcost > $havecredits) {
                postreport($ai, 0, "Purchase of $itemlist failed, insufficient credits need ". number_format($totalcost)
                                . " but " . number_format($havecredits) . " found in account.");
            } else {
                if ($totalcost <= floor($carr[1])) {
                    $carr[1] -= $totalcost;
                } else {
                    $carr[0] -= ($totalcost - $carr[1]);
                    $carr[1] = 0;
                }
                $query = "update account set pcredits={$carr[0]} where email='$email'";
                $result = $mysqliadb->query($query);
                if (!$result) {
                    postreport($ai, 0, "Purchase of $itemlist failed, unable to update account.");
                    postlog("Update of pcredits failed for $ai: $email: " . $mysqliadb->error);
                } else {
                    $trancomplete = true;
                    $query = "select items from player where name='$ai'";
                    $result = $mysqlidb->query($query);
                    if (!$result || ($result->num_rows == 0)) {
                        postreport($ai, 0, "Purchase of $itemlist failed, unable to update items.");
                        postlog("Select of items failed for $ai: $email: " . $mysqlidb->error);
                        $trancomplete = false;
                    } else {
                        $row = $result->fetch_row();
                        $newitems = additemsquantity($row[0], $codestr);
                        $query = "update player set items='$newitems',pcredits={$carr[1]} where name='$ai'";
                        $result = $mysqlidb->query($query);
                        if (!$result) {
                            postreport($ai, 0, "Purchase of $itemlist failed, unable to update items.");
                            postlog("Select of items failed for $ai: $email: " . $mysqlidb->error);
                            $trancomplete = false;
                        }
                    }
                    if ($trancomplete == false) {
                        // undo update of account credits
                        $carr = explode("|", $credits);
                        $query = "update account set pcredits={$carr[0]} where email='$email'";
                        $mysqliadb->query($query);
                    } else {
                        postreport($ai, 0, "Purchase of $itemlist complete, " . number_format($totalcost) . " premium credits removed from account.");
                    }
                }
            }
        }
    }
    return implode("|", $carr);
}


/*
 * getstorecredits - performs dbase query to retrieve number of credits
 *  in primary account on game portal as well as pcredits on local server
 */
function getstorecredits($ai, $email) {
    global $mysqlidb;
    global $mysqliadb;
    $acreds = 0;
    $lcreds = 0;

    // get local game server pcredits
    $query = "select pcredits from player where name='$ai'";
    $result = $mysqlidb->query($query);
    if ($result && ($result->num_rows > 0)) {
        $row = $result->fetch_row();
        $lcreds = $row[0];
    }

    $mysqliadb = new mysqli(DBAHOST, DBAUSER, DBAPASS, DBADBASE);
    if ($mysqliadb->connect_error) {
        postreport($ai, 0, "Unable to retrieve credit balance for $ai");
        postlog("Connect to dbase failed for $ai: $email: " . $mysqliadb->connect_error);
    } else {
        $query = "select pcredits from account where email='$email'";
        $result = $mysqliadb->query($query);
        if (!$result || ($result->num_rows == 0)) {
            postlog("Retrieval of pcredits failed for $ai: $email: " . $mysqliadb->error);
        } else {
            $row = $result->fetch_row();
            $acreds = $row[0];
        }
    }

    return "$acreds|$lcreds";
}


?>
