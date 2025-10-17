<?php

/*
 * genfilelist - generates a semicolon separated files as a string
 *      if unable to open that directory will display error message
 *      if no files in directory then just returns an empty string
 *      only file names are included in list without path info
 */
function genfilelist($basedir, $extlist, $subdir) {
    $flist = "";
    $hdir = null;
    if (is_dir($basedir)) {
        $hdir = opendir($basedir);
    }
    if ($hdir) {
        while (false !== ($filename = readdir($hdir))) {
            $finfo = pathinfo($filename);
            // if no ext list then show everything but "." and ".."
            if (($finfo["basename"] == ".") || ($finfo["basename"] == "..")) {
                continue;
            } else if (($extlist == "") || (array_key_exists("extension", $finfo) && (stristr($extlist, $finfo["extension"])))) {
                if (!array_key_exists("extension", $finfo)) {
                    $filenoext = $finfo["basename"];
                } else {
                    $filenoext = basename($finfo["basename"], "." . $finfo["extension"]);
                }
                // put in array with title, filename and filename w/o extension
                if ($flist != "") {
                    $flist .= ";";
                }
                $flist .= $filename;
            }
        }
        closedir($hdir);
        if ($subdir != "") {
            $sublist = genfilelist($basedir . "/" . $subdir, $extlist, "");
            if ($sublist != "") {
                $flist .= str_replace(";", ";$subdir/", ";$sublist");
            }
        }
    }
    return $flist;
}

/*
 * genoptionlist - generates html to display a list of files as options for menu
 *      if unable to open that directory will display nothing
 *      if no files in directory then just outputs nothing
 *
 */
function genoptionlist($basedir) {
    $hdir = opendir($basedir);
    if ($hdir) {
        while (false !== ($filename = readdir($hdir))) {
            $finfo = pathinfo($filename);
            // show everything but "." and ".."
            if (($finfo["basename"] != ".") && ($finfo["basename"] != "..")) {
                if (array_key_exists("extension", $finfo)) {
                    $filewithext = basename($finfo["basename"], "." . $finfo["extension"]);
                } else {
                    $filewithext = $finfo["basename"];
                }
                $name = str_replace("_", " ", $filewithext);
                print "<option value='" . $filename . "' label='" . $name . "'></option>\n";
            }
        }
        closedir($hdir);
    }
}


/*
 * handleuploadedfiles - process $_FILES and $_POST to deal with uploaded
 *      files and move them into correct locations
 *
 */
//function handleuploadedfiles() {
//    $msg = "";
//    if (!empty($_FILES)) {
//        if (array_key_exists("newsletter", $_FILES)) {
//            if (($_FILES["newsletter"]["error"] == 0) && (move_uploaded_file($_FILES["newsletter"]["tmp_name"], "newsletters/" . $_FILES["newsletter"]["name"]))) {
//                $msg = "Upload of file " . $_FILES["newsletter"]["name"] . " of size " . number_format($_FILES["newsletter"]["size"]) . " complete";
//            } else {
//                $msg = "Upload of file " . $_FILES["newsletter"]["name"] . " failed";
//            }
//        } else if (array_key_exists("map1", $_FILES) && array_key_exists("map2", $_FILES)) {
//            if (($_FILES["map1"]["error"] == 0) && (move_uploaded_file($_FILES["map1"]["tmp_name"], "map_gallery/" . $_FILES["map1"]["name"]))) {
//                $msg = "Upload of file " . $_FILES["map1"]["name"] . " of size " . number_format($_FILES["map1"]["size"]) . " complete\n";
//            } else {
//                $msg = "Upload of file " . $_FILES["map1"]["name"] . " failed\n";
//            }
//            if (($_FILES["map1"]["error"] == 0) && (move_uploaded_file($_FILES["map2"]["tmp_name"], "map_gallery/" . $_FILES["map2"]["name"]))) {
//                $msg .= "Upload of file " . $_FILES["map2"]["name"] . " of size " . number_format($_FILES["map2"]["size"]) . " complete";
//            } else {
//                $msg .= "Upload of file " . $_FILES["map2"]["name"] . " failed";
//            }
//        } else if (array_key_exists("photo", $_FILES) && array_key_exists("gallery", $_FILES)) {
//            $trymove = true;
//
//            if ($_POST["gallery"] == "new") {
//                if (!$_POST["newgallery"]) {
//                    $msg = "Unable to create new gallery, no name specified";
//                    $trymove = false;
//                } else {
//                    $_POST["gallery"] = $_POST["newgallery"];
//                    if (mkdir("photo_gallery/" . $_POST["newgallery"], 0777)) {
//                        $msg = "Successfully created new gallery " . $_POST["newgallery"] . "\n";
//                    } else {
//                        $msg = "Failed to create new gallery " . $_POST["newgallery"];
//                        $trymove = false;
//                    }
//                }
//            }
//
//            if ($trymove == true) {
//                if (($_FILES["photo"]["error"] == 0) && (move_uploaded_file($_FILES["photo"]["tmp_name"], "photo_gallery/" . $_POST["gallery"] . "/" . $_FILES["photo"]["name"]))) {
//                    $msg .= "Upload of file " . $_FILES["photo"]["name"] . " of size " . number_format($_FILES["photo"]["size"]) . " complete\n";
//                } else {
//                    $msg .= "Upload of file " . $_FILES["photo"]["name"] . " failed\n";
//                }
//            }
//        } else if (array_key_exists("video", $_FILES)) {
//            if (($_FILES["video"]["error"] == 0) && (move_uploaded_file($_FILES["video"]["tmp_name"], "video_gallery/" . $_FILES["video"]["name"]))) {
//                $msg .= "Upload of file " . $_FILES["video"]["name"] . " of size " . number_format($_FILES["video"]["size"]) . " complete\n";
//            } else {
//                $msg .= "Upload of file " . $_FILES["video"]["name"] . " failed\n";
//            }
//        } else if (array_key_exists("audio", $_FILES)) {
//            if (($_FILES["audio"]["error"] == 0) && (move_uploaded_file($_FILES["audio"]["tmp_name"], "audio_files/" . $_FILES["audio"]["name"]))) {
//                $msg .= "Upload of file " . $_FILES["audio"]["name"] . " of size " . number_format($_FILES["audio"]["size"]) . " complete\n";
//            } else {
//                $msg .= "Upload of file " . $_FILES["audio"]["name"] . " failed\n";
//            }
//        } else if (array_key_exists("adfile", $_FILES) && array_key_exists("tranlink", $_FILES)) {
//            $finfo = pathinfo($_FILES["adfile"]["name"]);
//            if (($_FILES["adfile"]["error"] == 0) && (move_uploaded_file($_FILES["adfile"]["tmp_name"], "images/adverts/" . $_POST["tranlink"] . "." . $finfo["extension"]))) {
//                $msg .= "Upload of file " . $_FILES["adfile"]["name"] . " of size " . number_format($_FILES["adfile"]["size"]) . " as " . $_POST["tranlink"] . "." . $finfo["extension"] . " complete\n";
//            } else {
//                $msg .= "Upload of file " . $_FILES["adfile"]["name"] . " as " . $_POST["tranlink"] . "." . $finfo["extension"] . " failed\n";
//            }
//        } else {
//            $msg .= "Upload of file failed. Unknown destination\n";
//        }
//        print "<input type='hidden' id='fileuploadresult' value='" . $msg . "'></input>\n";
//    }
//}

?>
