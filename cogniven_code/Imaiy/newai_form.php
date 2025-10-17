<?php
    if (!isset($_SESSION['logged_in'])) {
        require_once "globals.php";
        header('Location: ' . GAME_PORTAL_EXTERNAL);
    }

    print "<?php xml-stylesheet href='css/gform.css' type='text/css'?>\n";
    print "<?php xml-stylesheet href='css/NewAI.css' type='text/css'?>\n";

    require_once 'genfilelist.php';
    print "<p id='avatar_list' class='noshow'>" . genfilelist('graphics/avatars', 'png,jpg', '') . "</p>\n";
    print "<p id='email' class='noshow'>" . $_SESSION['email'] . "</p>\n";
    print "<p id='gamekey' class='noshow'>" . $_SESSION['gamekey'] . "</p>\n";
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
    <head>
        <title>IMAIY</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></meta>
        <meta name="author" content="Cogniven Studios, Inc."></meta>
        <meta name="robots" content="noarchive, nofollow"></meta>
        <meta name="googlebot" content="noarchive, nofollow"></meta>
        <link rel="stylesheet" type="text/css" href="css/gform.css"></link>
        <link rel="stylesheet" type="text/css" href="css/NewAI.css"></link>
        <script type="text/javascript" src="js/ajax.js"></script>
        <script type="text/javascript" src="js/newai.js"></script>
    </head>
    <body>
        <div id="NewAI_div">
            <table id="NewAI_main_table">
                <tbody>
                    <tr id="NewAI_row_1" class="NewAI_row_spacer">
                        <td colspan="3">
                        </td>
                    </tr>
                    <tr id="NewAI_row_2">
                        <td class="NewAI_left_margin">
                        </td>
                        <td>
                            <p id="NewAI__header">MASTER AI ON FINAL APPROACH TO IMAIY</p>
                        </td>
                        <td class="NewAI_right_margin">
                        </td>
                    </tr>
                    <tr id="NewAI_row_3" class="NewAI_row_spacer">
                        <td colspan="3">
                        </td>
                    </tr>
                    <tr id="NewAI_row_4">
                        <td class="NewAI_left_margin">
                        </td>
                        <td>
                            <p class="NewAI_description">Your Master AI has entered orbit and is requesting operational identity and general landing area.</p>
                            <br/>
                            <p class="NewAI_description">
                                Enter the Name for your Master AI, select an avatar image using the arrows, and choose a landing block.
                                You may also enter your biography. You can change the image and biography later on the Settings tab of the Social Dialog.
                            </p>
                        </td>
                        <td class="NewAI_right_margin">
                        </td>
                    </tr>
                    <tr id="NewAI_row_5" class="NewAI_row_spacer">
                        <td colspan="3">
                        </td>
                    </tr>
                    <tr id="NewAI_row_6">
                        <td class="NewAI_left_margin">
                        </td>
                        <td>
                            <table id="NewAI_row_6_table">
                                <tbody>
                                    <tr>
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <label for="NewAI_input_name">Name</label>
                                                        </td>
                                                        <td class="label_input_spacer">
                                                        </td>
                                                        <td colspan="3">
                                                            <input id="NewAI_input_name" maxlength="36" value="" placeholder="Master AI Name" />
                                                        </td>
                                                    </tr>
                                                    <tr class="NewAI_row_spacer">
                                                        <td colspan="3">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="3">
                                                            <label for="NewAI_input_starting_block">Landing Block</label>
                                                        </td>
                                                        <td class="label_input_spacer">
                                                        </td>
                                                        <td>
                                                            <input id="NewAI_input_starting_block" maxlength="2" placeholder="" />
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <?php
                                                            loadinis();
                                                            $maxsection = "A";
                                                            if (array_key_exists("highestopensection", $_SESSION)) {
                                                                $maxsection = $_SESSION["highestopensection"];
                                                            }
                                                            $maxstr = "A0-A3";
                                                            $maxidx = ord($maxsection) - ord("A");
                                                            for ($idx = 1; $idx <= $maxidx; $idx++) {
                                                                $maxstr .= ", " . chr(ord("A") + $idx);
                                                                $maxstr .= "0-" . (8*($idx + 1) - 5);
                                                            }

                                                            echo "<td colspan='5'><label>Currently restricted to $maxstr. Leave blank for random assignment.</label></td>\n";
                                                        ?>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td id="NewAI_row_6_table_spacer">
                                        </td>
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr id="avatar_row">
                                                        <td>
                                                            <div id="left" class="plyravatarbutd">
                                                                <img id="socialsdialog_tab_3-left" class="plyravatarbutl" src="graphics/map_buttons.png" startdrag="0" />
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <img id="socialsdialog_tab_3SAP" class="plyravatar" src="graphics/avatars/blank.png" startdrag="0" />
                                                        </td>
                                                        <td>
                                                            <div id="right" class="plyravatarbutd">
                                                                <img id="socialsdialog_tab_3-right" class="plyravatarbutr" src="graphics/map_buttons.png" startdrag="0" />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                        <td class="NewAI_right_margin">
                        </td>
                    </tr>
                    <tr id="NewAI_row_7" class="NewAI_row_spacer">
                        <td colspan="3">
                        </td>
                    </tr>
                    <tr id="NewAI_row_8">
                        <td class="NewAI_left_margin">
                        </td>
                        <td>
                            <textarea id="NewAI_textarea_bio" placeholder="Biography (up to 1,000 characters)" maxlength="1000"></textarea>
                        </td>
                        <td class="NewAI_right_margin">
                        </td>
                    </tr>
                    <tr id="NewAI_row_9" class="NewAI_row_spacer">
                        <td colspan="3">
                        </td>
                    </tr>
                    <tr id="NewAI_row_10">
                        <td class="NewAI_left_margin">
                        </td>
                        <td>
                            <table>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div id="NewAI_div_feedback_label">
                                                <p>Master AI is standing by...</p>
                                                <p>Press button when ready -->></p>
                                            </div>
                                        </td>
                                        <td>
                                        </td>
                                        <td>
                                            <button id ="NewAI_div_action_submit"></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                        <td class="NewAI_right_margin">
                        </td>
                    </tr>
                    <tr id="NewAI_row_11" class="NewAI_row_spacer">
                        <td colspan="3">
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="copyright">Copyright &copy;2010-2012 by Cogniven Studios Inc. All rights reserved.</p>
    </body>
</html>
