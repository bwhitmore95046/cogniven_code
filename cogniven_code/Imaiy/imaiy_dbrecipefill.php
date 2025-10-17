<?php
/*
 * php script to fill or update text table.
 */
if (isset($_SERVER{'HTTP_HOST'})) {
    die ("Must run from command line only!\n");
}
// get environment variables
if ($argc < 3) {
    print "\nUsage: php -f imaiy_dbrecipefill.php <database> <dbserverip>\n"
            ."\t dbserverip is the ip address of the dbserver associated with this game server\n\n";
    return;
}
$gamedbase = $argv[1];
$gamedbserver = $argv[2];

include "globals.php";

    /*
     * recipes array - inserted into recipes table
     * format (from imaiy_dbtableinit.sql
    `type` varchar(8) NOT NULL default '',                    # set type such as module, drone, component, etc...
    `ident` int NOT NULL default '0',                         # identifier for item in set
    `dorder` int NOT NULL default '0',                        # order for display in set
    `level` int NOT NULL default '1',                         # level of item
    `resources` varchar(100) NOT NULL default '0/0/0/0/0',    # resources needed 'fuel/metal/mineral/crystals/time in secs'
    `drones` varchar(100) NOT NULL default '',                # drones required 'ident:level:quantity;...'
    `components` varchar(100) NOT NULL default '',            # components required 'D:type:ident:quantity;...'
    `items` varchar(100) NOT NULL default '',                 # items required 'ident:0:quantity;...'
    `preqs` varchar(100) NOT NULL default '',                 # prerequisites 'preq type:ident:value;...'

    `scrap` int unsigned NOT NULL default '0',                # scrap amount for drone
    `carrierspace` int unsigned NOT NULL default '0',         # carrier space used for drone (1.5 * (resource for chassis + resources for purpose)) + cargocap
    `stats` varchar(100) NOT NULL default '',                 # stats, format dependant on type
     */
    $reqcount = 12; // how many entries needed for each item

    $recipes = array();
    // rogue locs (ident=0), bases (ident=1) and attacks(ident=2) stats=defenses for bases, carrierspace = max drone counts
    $recipes[] = array(RECIPE_TYPE_ROGUE, 0,  1,  1, '', '31:1:70;41:1:4;42:1:8;43:1:18;11:1:13;12:1:28;13:1:9', '', '', '', 0, 150, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 0,  2,  2, '', '31:1:280;41:1:16;42:1:33;43:1:73;11:1:50;12:1:113;13:1:35', '', '', '', 0, 600, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 0,  3,  3, '', '31:1:674;41:1:37;42:1:79;43:1:176;11:1:121;12:1:272;13:1:85;21:1:26', '', '', '', 0, 1470, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 0,  4,  4, '', '31:1:1165;41:1:65;42:1:137;43:1:304;11:1:209;12:1:470;13:1:147;21:1:145', '', '', '', 0, 2642, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 0,  5,  5, '', '31:1:1161;32:1:581;41:1:97;42:1:204;43:1:454;11:1:312;12:1:702;13:1:219;21:1:391', '', '', '', 0, 4121, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 0,  6,  6, '', '31:1:1760;32:1:880;41:1:147;42:1:310;43:1:688;11:1:473;12:1:1065;13:1:333;21:1:748', '', '', '', 0, 6404, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 0,  7,  7, '', '31:1:2454;32:1:1227;41:1:205;42:1:432;43:1:960;11:1:660;12:1:1485;13:1:464;21:1:1292', '', '', '', 0, 9179, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 0,  8,  8, '', '31:1:3331;32:1:1666;41:1:278;42:1:586;43:1:1302;11:1:895;12:1:2015;13:1:630;21:1:2031', '', '', '', 0, 12734, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 0,  9,  9, '', '31:1:3772;32:1:1886;33:1:943;41:1:367;42:1:774;43:1:1720;11:1:1183;12:1:2661;13:1:832;21:1:2996;22:1:249', '', '', '', 0, 17383, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 0, 10, 10, '', '31:1:4822;32:1:2411;33:1:1206;41:1:469;42:1:990;43:1:2200;11:1:1512;12:1:3403;13:1:1063;21:1:4237;22:1:312', '', '', '', 0, 22625, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 0, 11, 11, '', '31:1:6029;32:1:3015;33:1:1507;41:1:586;42:1:1237;43:1:2750;11:1:1891;12:1:4254;13:1:1329;21:1:5781;22:1:384', '', '', '', 0, 28763, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 0, 12, 12, '', '31:1:7435;32:1:3718;33:1:1859;41:1:723;42:1:1526;43:1:3391;11:1:2332;12:1:5246;13:1:1639;21:1:7647;22:1:465', '', '', '', 0, 35981, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 0, 13, 13, '', '31:1:14724;32:1:7362;33:1:3681;34:1:1841;41:1:1534;42:1:3238;43:1:7196;11:1:4947;12:1:11131;13:1:3478;21:1:7402;22:1:982', '', '', '', 0, 67516, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 0, 14, 14, '', '31:1:17621;32:1:8811;33:1:4405;34:1:2203;41:1:1836;42:1:3875;43:1:8611;11:1:5920;12:1:13321;13:1:4163;21:1:9546;22:1:1151', '', '', '', 0, 81463, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 0, 15, 15, '', '31:1:20853;32:1:10427;33:1:5213;34:1:2607;41:1:2172;42:1:4586;43:1:10191;11:1:7006;12:1:15764;13:1:4926;21:1:12063;22:1:1334', '', '', '', 0, 97142, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 0, 16, 16, '', '31:1:24172;32:1:12086;33:1:6043;34:1:3022;41:1:2518;42:1:5316;43:1:11813;11:1:8121;12:1:18273;13:1:5710;21:1:15084;23:1:1517', '', '', '', 0, 113675, '');

    // first is default empty defenses string in stats
    $recipes[] = array(RECIPE_TYPE_ROGUE, 1,  0,  0, '', '', '', '', '', 0, 0, '1:0:x;2:0:x;3:0:1;4:0:1;5:0:1;6:0:1');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 1,  1,  1, '', '11:1:1;12:1:2;13:1:1;21:1:8;31:1:63;41:1:2;42:1:1;43:1:1', '', '', '', 0, 79, '1:486:x;2:487:x;3:0:1;4:51:1;5:0:1;6:0:1');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 1,  2,  2, '', '11:1:3;12:1:11;13:1:6;21:1:35;31:1:278;41:1:8;42:1:5;43:1:3', '', '', '', 0, 349, '1:1945:x;2:1946:x;3:0:1;4:205:1;5:0:1;6:0:1');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 1,  3,  3, '', '11:1:8;12:1:30;13:1:15;21:1:86;31:1:681;41:1:20;42:1:13;43:1:7', '', '', '', 0, 860, '1:4377:x;2:4378:x;3:0:1;4:461:1;5:0:1;6:0:1');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 1,  4,  4, '', '11:1:17;12:1:66;13:1:33;21:1:173;31:1:1358;41:1:42;42:1:28;43:1:14', '', '', '', 0, 1731, '1:7782:x;2:7783:x;3:0:1;4:819:1;5:0:1;6:0:1');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 1,  5,  5, '', '11:1:31;12:1:125;13:1:63;21:1:430;31:1:2329;32:1:1165;41:1:78;42:1:52;43:1:26', '', '', '', 0, 4299, '1:5120:x;2:5120:x;3:0:1;4:2560:1;5:12800:1;6:0:1');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 1,  6,  6, '', '11:1:57;12:1:228;13:1:114;21:1:718;31:1:3854;32:1:1927;41:1:142;42:1:95;43:1:47', '', '', '', 0, 7182, '1:7373:x;2:7373:x;3:0:1;4:3686:1;5:18432:1;6:0:1');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 1,  7,  7, '', '11:1:94;12:1:377;13:1:189;21:1:1094;31:1:5808;32:1:2904;41:1:237;42:1:158;43:1:79', '', '', '', 0, 10940, '1:10035:x;2:10035:x;3:0:1;4:5018:1;5:25088:1;6:0:1');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 1,  8,  8, '', '11:1:159;12:1:638;13:1:319;21:1:1701;31:1:8923;32:1:4461;41:1:406;42:1:270;43:1:135', '', '', '', 0, 17012, '1:13107:x;2:13107:x;3:0:1;4:6554:1;5:32768:1;6:0:1');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 1,  9,  9, '', '11:1:248;12:1:991;13:1:496;21:1:2787;22:1:279;31:1:12609;32:1:6305;33:1:3152;41:1:640;42:1:427;43:1:213', '', '', '', 0, 28147, '1:29030:x;2:29031:x;3:0:1;4:12442:1;5:8294:1;6:4147:1');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 1, 10, 10, '', '11:1:404;12:1:1617;13:1:809;21:1:4188;22:1:419;31:1:18703;32:1:9351;33:1:4676;41:1:1064;42:1:709;43:1:355', '', '', '', 0, 42295, '1:35840:x;2:35840:x;3:0:1;4:15360:1;5:10240:1;6:5120:1');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 1, 11, 11, '', '11:1:605;12:1:2420;13:1:1210;21:1:5779;22:1:578;31:1:25441;32:1:12721;33:1:6360;41:1:1625;42:1:1083;43:1:542', '', '', '', 0, 58364, '1:43366:x;2:43367:x;3:0:1;4:18586:1;5:12390:1;6:6195:1');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 1, 12, 12, '', '11:1:969;12:1:3874;13:1:1937;21:1:8545;22:1:855;31:1:37033;32:1:18516;33:1:9258;41:1:2661;42:1:1774;43:1:887', '', '', '', 0, 86309, '1:51609:x;2:51610:x;3:0:1;4:22118:1;5:14746:1;6:7373:1');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 1, 13, 13, '', '11:1:1413;12:1:5651;13:1:2826;21:1:12214;22:1:1221;31:1:49110;32:1:24555;33:1:12278;34:1:6139;41:1:3978;42:1:2652;43:1:1326', '', '', '', 0, 123363, '1:43264:x;2:43264:x;3:8653:1;4:34611:1;5:25958:1;6:17306:1');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 1, 14, 14, '', '11:1:2235;12:1:8942;13:1:4471;21:1:17890;22:1:1789;31:1:70638;32:1:35319;33:1:17659;34:1:8830;41:1:6460;42:1:4307;43:1:2153', '', '', '', 0, 180693, '1:50176:x;2:50176:x;3:10035:1;4:40141:1;5:30106:1;6:20070:1');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 1, 15, 15, '', '11:1:3200;12:1:12801;13:1:6400;21:1:23754;22:1:2375;23:1:238;31:1:91933;32:1:45967;33:1:22983;34:1:11492;41:1:9503;42:1:6335;43:1:3168', '', '', '', 0, 240149, '1:57600:x;2:57600:x;3:11520:1;4:46080:1;5:34560:1;6:23040:1');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 1, 16, 16, '', '11:1:5017;12:1:20069;13:1:10034;21:1:34605;22:1:3461;23:1:346;31:1:131027;32:1:65513;33:1:32757;34:1:16378;41:1:15326;42:1:10217;43:1:5109', '', '', '', 0, 349859, '1:65536:x;2:65536:x;3:13107:1;4:52429:1;5:39322:1;6:26214:1');

    $recipes[] = array(RECIPE_TYPE_ROGUE, 2,  1,  1, '', '31:1:16', '', '', '', 0, 16, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 2,  2,  2, '', '31:1:64', '', '', '', 0, 64, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 2,  3,  3, '', '31:1:256', '', '', '', 0, 256, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 2,  4,  4, '', '31:1:1024', '', '', '', 0, 1024, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 2,  5,  5, '', '31:1:2048;32:1:16', '', '', '', 0, 2064, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 2,  6,  6, '', '31:1:2048;32:1:64', '', '', '', 0, 2112, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 2,  7,  7, '', '31:1:4096;32:1:256', '', '', '', 0, 4352, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 2,  8,  8, '', '31:1:4096;32:1:1024', '', '', '', 0, 5120, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 2,  9,  9, '', '31:1:8192;32:1:2048;33:1:16', '', '', '', 0, 10256, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 2, 10, 10, '', '31:1:8192;32:1:2048;33:1:64', '', '', '', 0, 10304, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 2, 11, 11, '', '31:1:16384;32:1:4096;33:1:256', '', '', '', 0, 20736, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 2, 12, 12, '', '31:1:16384;32:1:4096;33:1:1024', '', '', '', 0, 21504, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 2, 13, 13, '', '31:1:32768;32:1:8192;33:1:2048;34:1:16', '', '', '', 0, 43024, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 2, 14, 14, '', '31:1:32768;32:1:8192;33:1:2048;34:1:64', '', '', '', 0, 43072, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 2, 15, 15, '', '31:1:65536;32:1:16384;33:1:4096;34:1:256', '', '', '', 0, 86272, '');
    $recipes[] = array(RECIPE_TYPE_ROGUE, 2, 16, 16, '', '31:1:65536;32:1:16384;33:1:4096;34:1:1024', '', '', '', 0, 87040, '');


    // stats for components are for trade server use:  min buy;max buy;min sell;max sell;quantity. if empty then won't appear in trade
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_DCNTRL,   1, 1, '8/16/16/16/8',             '', '', '', '', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_CMOUNT,   2, 1, '32/32/32/32/8',            '', '', '', '', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_RSCOOP,   3, 1, '32/64/64/32/8',            '', '', '', 't:'.TECH_DRONE_MOVE.':1', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_CONARM,   4, 1, '32/64/64/64/8',            '', '', '', '', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_MINEARM,  5, 1, '32/128/64/32/8',           '', '', '', '', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_DISARM,   6, 1, '32/64/64/128/8',           '', '', '', '', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_DATAP,    7, 1, '32/32/128/128/8',          '', '', '', 't:'.TECH_DATA_PROC.':1', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_RSCAN,    8, 1, '32/16/128/64/8',           '', '', '', 't:'.TECH_DATA_PROC.':2', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_DSCAN,    9, 1, '128/128/512/256/8',        '', '', '', 't:'.TECH_DATA_PROC.':1', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_BSCAN,   10, 1, '256/256/1024/1024/8',      '', '', '', 't:'.TECH_DATA_PROC.':4', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_TSCAN,   11, 1, '16/32/16/64/8',            '', '', '', 't:'.TECH_DRONE_TACTIC.':1', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_WMOUNT,  12, 1, '16/64/16/32/8',            '', '', '', 't:'.TECH_DRONE_TACTIC.':2', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_PARTGEN, 13, 1, '16/64/8/32/8',             '', '', '', 't:'.TECH_DRONE_TACTIC.':1', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_LASER,   14, 1, '32/128/64/128/8',          '', '', '', 't:'.TECH_DRONE_TACTIC.':4', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_RAILGUN, 15, 1, '128/512/128/512/8',        '', '', '', 't:'.TECH_DRONE_TACTIC.':8', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_RSTRUT,  16, 1, '8/16/16/4/8',              '', '', '', 't:'.TECH_BASE_DEFENSE.':1', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_APANEL,  17, 1, '16/16/32/32/8',            '', '', '', 't:'.TECH_BASE_DEFENSE.':4', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_SHLDGEN, 18, 1, '256/1024/256/512/8',       '', '', '', 't:'.TECH_BASE_DEFENSE.':4;t:'.TECH_DRONE_TACTIC.':4', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_LRAMP,   19, 1, '128/256/256/16/8',         '', '', '', 't:'.TECH_DRONE_MOVE.':4', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_TIEDWN,  20, 1, '256/1024/256/16/8',        '', '', '', 't:'.TECH_DRONE_MOVE.':8', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_RELMECH, 21, 1, '256/256/1024/16/8',        '', '', '', 't:'.TECH_DRONE_MOVE.':8', 0, 0, '5.0;20.0;80.0;100.0;200000');
    $recipes[] = array(RECIPE_TYPE_COMP, COMPONENT_DAI,   1999, 1, '80/1024/8192/16385/14400', '', '1 Drudge AI core of target level', '', 'DC:1', 0, 0, '');

    // following resource recipes just for stats for trade server use:  min buy;max buy;min sell;max sell;quantity
    $recipes[] = array(RECIPE_TYPE_RES, 0, 1, 1, '', '', '', '', '', 0, 0, '1.0;3.0;8.0;10.0;1000000');
    $recipes[] = array(RECIPE_TYPE_RES, 1, 2, 1, '', '', '', '', '', 0, 0, '1.0;3.0;8.0;10.0;1000000');
    $recipes[] = array(RECIPE_TYPE_RES, 2, 3, 1, '', '', '', '', '', 0, 0, '1.0;3.0;8.0;10.0;1000000');
    $recipes[] = array(RECIPE_TYPE_RES, 3, 4, 1, '', '', '', '', '', 0, 0, '1.0;3.0;8.0;10.0;1000000');
    // for scrap recycling - stats must be blank to prevent trade server confusion
    $recipes[] = array(RECIPE_TYPE_RES, 4, 5, 1, '////16', '', '', '', '', 32768, 0, '');

    $recipes[] = array(RECIPE_TYPE_MAI, 1,  1,  2, '0/0/0/0/4096',    '', '', 'COFR:0:1', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 2,  2,  3, '0/0/0/0/9216',    '', '', 'COFR:0:2', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 3,  3,  4, '0/0/0/0/16384',   '', '', 'COFR:0:4', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 4,  4,  5, '0/0/0/0/25600',   '', '', 'COFR:0:8', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 5,  5,  6, '0/0/0/0/36864',   '', '', 'COFR:0:16', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 6,  6,  7, '0/0/0/0/50176',   '', '', 'COFR:0:32', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 7,  7,  8, '0/0/0/0/65536',   '', '', 'COFR:0:32;DANO:0:1', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 8,  8,  9, '0/0/0/0/82944',   '', '', 'COFR:0:64;DANO:0:1', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 9,  9, 10, '0/0/0/0/102400',  '', '', 'COFR:0:64;DANO:0:2', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 10, 10, 11, '0/0/0/0/123904',  '', '', 'COFR:0:128;DANO:0:2', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 11, 11, 12, '0/0/0/0/147456',  '', '', 'COFR:0:128;DANO:0:2;HEPR:0:1', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 12, 12, 13, '0/0/0/0/173056',  '', '', 'COFR:0:256;DANO:0:4', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 13, 13, 14, '0/0/0/0/200704',  '', '', 'COFR:0:256;DANO:0:4', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 14, 14, 15, '0/0/0/0/230400',  '', '', 'COFR:0:256;DANO:0:4', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 15, 15, 16, '0/0/0/0/262144',  '', '', 'COFR:0:256;DANO:0:4;HEPR:0:2;CRPRCH:0:1', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 16, 16, 17, '0/0/0/0/295936',  '', '', 'COFR:0:512;DANO:0:8', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 17, 17, 18, '0/0/0/0/331776',  '', '', 'COFR:0:512;DANO:0:8;HEPR:0:4;CRPRCH:0:2;POPABU:0:1', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 18, 18, 19, '0/0/0/0/369664',  '', '', 'COFR:0:1024;DANO:0:16', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 19, 19, 20, '0/0/0/0/409600',  '', '', 'COFR:0:1024;DANO:0:16;HEPR:0:8;CRPRCH:0:4;POPABU:0:2;SUCORO:0:1', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 20, 20, 21, '0/0/0/0/451584',  '', '', 'COFR:0:2048;DANO:0:32', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 21, 21, 22, '0/0/0/0/495616',  '', '', 'COFR:0:2048;DANO:0:64;HEPR:0:16;CRPRCH:0:8;POPABU:0:4;SUCORO:0:2', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 22, 22, 23, '0/0/0/0/541696',  '', '', 'COFR:0:4096;DANO:0:128', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 23, 23, 24, '0/0/0/0/589824',  '', '', 'COFR:0:4096;DANO:0:128;HEPR:0:32;CRPRCH:0:16;POPABU:0:8;SUCORO:0:4', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 24, 24, 25, '0/0/0/0/640000',  '', '', 'COFR:0:8192;DANO:0:256', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 25, 25, 26, '0/0/0/0/692224',  '', '', 'COFR:0:8192;DANO:0:256;HEPR:0:64;CRPRCH:0:32;POPABU:0:16;SUCORO:0:8', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 26, 26, 27, '0/0/0/0/746496',  '', '', 'COFR:0:16384;DANO:0:512;HEPR:0:128;CRPRCH:0:64;POPABU:0:32;SUCORO:0:16', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 27, 27, 28, '0/0/0/0/802816',  '', '', 'COFR:0:16384;DANO:0:1024;HEPR:0:256;CRPRCH:0:128;POPABU:0:64;SUCORO:0:32', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 28, 28, 29, '0/0/0/0/861184',  '', '', 'COFR:0:32768;DANO:0:2048;HEPR:0:512;CRPRCH:0:256;POPABU:0:128;SUCORO:0:64', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 29, 29, 30, '0/0/0/0/921600',  '', '', 'COFR:0:32768;DANO:0:4096;HEPR:0:1024;CRPRCH:0:512;POPABU:0:256;SUCORO:0:128', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 30, 30, 31, '0/0/0/0/984064',  '', '', 'COFR:0:65536;DANO:0:8192;HEPR:0:2048;CRPRCH:0:1024;POPABU:0:512;SUCORO:0:256', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MAI, 31, 31, 32, '0/0/0/0/1048576', '', '', 'COFR:0:65536;DANO:0:16384;HEPR:0:4096;CRPRCH:0:2048;POPABU:0:1024;SUCORO:0:512', '', 0, 0, '');

    $recipes[] = array(RECIPE_TYPE_BASE, TIMER_CONST_BASE, 1, 1, '500000/500000/500000/500000/0', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:400;'.RECIPE_TYPE_DRONE.':'.DRONE_WORKR_MIN.':1:800', '', '', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_BASE, TIMER_CONST_BASE, 1, 2, '50000/50000/50000/50000/3600', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:100', '', '', '', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_BASE, TIMER_REPAIR_BASE, 2, 1, '50/250/250/500/30', '', '', '', '', 0, 0, '10'); // per point
    // for moving Master AI, time=base:per km
    $recipes[] = array(RECIPE_TYPE_BASE, TIMER_CONST_BASE, 1, 3, '////10:5', '', '', '', '', 0, 0, '');
    // for moving Base, time = base:per km
    $recipes[] = array(RECIPE_TYPE_BASE, TIMER_CONST_BASE, 1, 4, '////60:10', '', '', '', '', 0, 0, '');

    // drone stats - capacity;defense;offense;fuel use in km per unit;speed km per hour;shield quantity
    //      defense - type:amt,type:amt...
    //          type = 1:physcal, 2:energy, 3: both
    //      offense - type:range:amt,type:range:amt...
    //          type = 1:physical, 2:laser, 3:particle, 4:rail
    //          range in meters
    $recipes[] = array(RECIPE_TYPE_DRONE, DRONE_WORKR,      1, 1, '64/128/128/128/12',       '',
                            RECIPE_TYPE_COMP.':'.COMPONENT_DCNTRL.':1',
                            '', '',
                            100, 672, '');
    $recipes[] = array(RECIPE_TYPE_DRONE, DRONE_TRANS,      2, 1, '32/128/128/32/24',        '',
                            RECIPE_TYPE_COMP.':'.COMPONENT_DCNTRL.':1;'.RECIPE_TYPE_COMP.':'.COMPONENT_CMOUNT.':1',
                            '', 't:'.TECH_DRONE_MOVE.':1',
                            100, 480, '');
    $recipes[] = array(RECIPE_TYPE_DRONE, DRONE_RECON,      3, 1, '16/16/32/32/36',          '',
                            RECIPE_TYPE_COMP.':'.COMPONENT_DCNTRL.':1;'.RECIPE_TYPE_COMP.':'.COMPONENT_DATAP.':1',
                            '', 't:'.TECH_SCOUT.':1',
                            100, 144, '');
    $recipes[] = array(RECIPE_TYPE_DRONE, DRONE_FIGHT,      4, 1, '32/64/32/64/48',          '',
                            RECIPE_TYPE_COMP.':'.COMPONENT_DCNTRL.':1;'.RECIPE_TYPE_COMP.':'.COMPONENT_TSCAN.":1;".RECIPE_TYPE_COMP.':'.COMPONENT_WMOUNT.':1',
                            '', 't:'.TECH_DRONE_TACTIC.':1',
                            100, 288, '');
    $recipes[] = array(RECIPE_TYPE_DRONE, DRONE_WORKR_MIN,  5, 1, '64/64/64/64/30',          DRONE_WORKR,
                            RECIPE_TYPE_DRONE.':'.DRONE_WORKR.':1:1;'.RECIPE_TYPE_COMP.':'.COMPONENT_MINEARM.':1',
                            '', '',
                            200, 1306, '250;1:15;1:40:4;300;300;0');
    $recipes[] = array(RECIPE_TYPE_DRONE, DRONE_WORKR_CON,  6, 1, '64/64/64/64/60',          DRONE_WORKR,
                            RECIPE_TYPE_DRONE.':'.DRONE_WORKR.':1:1;'.RECIPE_TYPE_COMP.':'.COMPONENT_CONARM.':1',
                            '', '',
                            200, 1131, '75;1:15;1:40:4;300;200;0');
    $recipes[] = array(RECIPE_TYPE_DRONE, DRONE_WORKR_SCV,  7, 1, '64/64/64/64/90',          DRONE_WORKR,
                            RECIPE_TYPE_DRONE.':'.DRONE_WORKR.':1:1;'.RECIPE_TYPE_COMP.':'.COMPONENT_DISARM.':1',
                            '', 'mm:'.MODULE_ASSEMBLY.':4;t:'.TECH_RECYCLE.':5',
                            200, 1306, '250;1:15;1:40:4;300;300;0');
    $recipes[] = array(RECIPE_TYPE_DRONE, DRONE_TRANS_MAT,  8, 1, '32/128/128/32/30',        DRONE_TRANS,
                            RECIPE_TYPE_DRONE.':'.DRONE_TRANS.':1:1;'.RECIPE_TYPE_COMP.':'.COMPONENT_RSCOOP.':1',
                            '', 't:'.TECH_DRONE_MOVE.':1',
                            200, 1960, '1000;1:100;0:0:0;300;300;0');
    $recipes[] = array(RECIPE_TYPE_DRONE, DRONE_RECON_SVY,  9, 1, '32/32/64/64/60',          DRONE_RECON,
                            RECIPE_TYPE_DRONE.':'.DRONE_RECON.':1:1;'.RECIPE_TYPE_COMP.':'.COMPONENT_RSCAN.':1;'.RECIPE_TYPE_COMP.':'.COMPONENT_PARTGEN.':1',
                            '', 'mm:'.MODULE_ASSEMBLY.':2;t:'.TECH_SCOUT.':3',
                            200, 452, '20;1:15;2:910:2;500;500;0');
    $recipes[] = array(RECIPE_TYPE_DRONE, DRONE_RECON_SCO, 10, 1, '64/64/128/128/60',        DRONE_RECON,
                            RECIPE_TYPE_DRONE.':'.DRONE_RECON.':1:1;'.RECIPE_TYPE_COMP.':'.COMPONENT_DSCAN.':1;'.RECIPE_TYPE_COMP.':'.COMPONENT_PARTGEN.':1',
                            '', 't:'.TECH_SCOUT.':1',
                            400, 735, '15;1:15;2:910:2;500;750;0');
    $recipes[] = array(RECIPE_TYPE_DRONE, DRONE_RECON_SCN, 11, 1, '128/128/256/256/120',     DRONE_RECON,
                            RECIPE_TYPE_DRONE.':'.DRONE_RECON.':1:1;'.RECIPE_TYPE_COMP.':'.COMPONENT_BSCAN.':1;'.RECIPE_TYPE_COMP.':'.COMPONENT_PARTGEN.':1',
                            '', 'mm:'.MODULE_ASSEMBLY.':4;t:'.TECH_SCOUT.':5',
                            800, 1306, '10;1:15;2:910:2;500;1000;0');
    $recipes[] = array(RECIPE_TYPE_DRONE, DRONE_FIGHT_LIT, 12, 1, '64/128/64/128/60',        DRONE_FIGHT,
                            RECIPE_TYPE_DRONE.':'.DRONE_FIGHT.':1:1;'.RECIPE_TYPE_COMP.':'.COMPONENT_PARTGEN.':1',
                            '', 't:'.TECH_DRONE_TACTIC.':1',
                            200, 964, '100;1:100;2:300:10;300;400;0');
    $recipes[] = array(RECIPE_TYPE_DRONE, DRONE_FIGHT_MED, 13, 1, '256/512/256/512/90',      DRONE_FIGHT,
                            RECIPE_TYPE_DRONE.':'.DRONE_FIGHT.':1:1;'.RECIPE_TYPE_COMP.':'.COMPONENT_LASER.':1',
                            '', 'mm:'.MODULE_ASSEMBLY.':4;t:'.TECH_DRONE_TACTIC.':4',
                            400, 2692, '100;1:200;3:100:20;200;300;0');
    $recipes[] = array(RECIPE_TYPE_DRONE, DRONE_FIGHT_HVY, 14, 1, '512/1024/512/1024/120',   DRONE_FIGHT,
                            RECIPE_TYPE_DRONE.':'.DRONE_FIGHT.':1:1;'.RECIPE_TYPE_COMP.':'.COMPONENT_RAILGUN.':1',
                            '', 'mm:'.MODULE_ASSEMBLY.':8;t:'.TECH_DRONE_TACTIC.':8',
                            800, 4996, '100;1:400;4:200:40;100;200;0');
    $recipes[] = array(RECIPE_TYPE_DRONE, DRONE_FIGHT_DEF, 15, 1, '1024/2048/1024/2048/150', DRONE_FIGHT,
                            RECIPE_TYPE_DRONE.':'.DRONE_FIGHT.':1:1;'.RECIPE_TYPE_COMP.':'.COMPONENT_SHLDGEN.':1',
                            '', 'mm:'.MODULE_ASSEMBLY.':12;t:'.TECH_DRONE_TACTIC.':12',
                            800, 9604, '100;3:4000;0:0:0;100;300;'.DRONE_DEF_SHIELD_AMT);
    $recipes[] = array(RECIPE_TYPE_DRONE, DRONE_TRANS_GND, 16, 1, '64/256/256/64/120',       DRONE_TRANS,
                            RECIPE_TYPE_DRONE.':'.DRONE_TRANS.':1:1;'.RECIPE_TYPE_COMP.':'.COMPONENT_LRAMP.':1',
                            '', 'mm:'.MODULE_ASSEMBLY.':8;t:'.TECH_DRONE_MOVE.':5',
                            400, 0, '160000;1:100;0:0:0;80;400;0');
    $recipes[] = array(RECIPE_TYPE_DRONE, DRONE_TRANS_AIR, 17, 1, '512/1024/1024/512/180',   DRONE_TRANS,
                            RECIPE_TYPE_DRONE.':'.DRONE_TRANS.':1:1;'.RECIPE_TYPE_COMP.':'.COMPONENT_LRAMP.':1;'.RECIPE_TYPE_COMP.':'.COMPONENT_TIEDWN.':1;'.RECIPE_TYPE_COMP.':'.COMPONENT_RELMECH.':1',
                            '', 'mm:'.MODULE_ASSEMBLY.':12;t:'.TECH_DRONE_MOVE.':9',
                            500, 0, '160000;1:100;0:0:0;80;800;0');

    // defense stats - which;damage type;range;offense power;defense power
    //          which: 0=defense, 1=offsense
    //          type defense: 1=physcal, 2=energy, 3=both
    //          type offense: 1=physical, 2=laser, 3=particle, 4=rail
    //          range is in meters
    $recipes[] = array(RECIPE_TYPE_DEFENSE, DEFENSE_STRUC, 1, 1, '16/32/16/8/100', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4',
                            RECIPE_TYPE_COMP.':'.COMPONENT_RSTRUT.':1', '',
                            '', 0, 0, '0;1;0;0;200');
    $recipes[] = array(RECIPE_TYPE_DEFENSE, DEFENSE_ABLAT, 2, 1, '16/64/32/16/100', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4',
                            RECIPE_TYPE_COMP.':'.COMPONENT_APANEL.':1', '',
                            'mm:'. MODULE_PERIMETER.':3;t:'.TECH_BASE_DEFENSE.':3', 0, 0, '0;3;0;0;400');
    $recipes[] = array(RECIPE_TYPE_DEFENSE, DEFENSE_ENERS, 3, 1, '16/256/512/512/100', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8',
                            RECIPE_TYPE_COMP.':'.COMPONENT_SHLDGEN.':1', '',
                            'mm:'. MODULE_PERIMETER.':6;t:'.TECH_BASE_DEFENSE.':6', 0, 0, '0;2;0;0;800');
    $recipes[] = array(RECIPE_TYPE_DEFENSE, DEFENSE_PBEAM, 4, 1, '16/64/8/32/100', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16',
                            RECIPE_TYPE_COMP.':'.COMPONENT_PARTGEN.':1', '',
                            'mm:'. MODULE_PERIMETER.':9;t:'.TECH_BASE_TACTIC.':9', 0, 0, '1;3;350;10;150');
    $recipes[] = array(RECIPE_TYPE_DEFENSE, DEFENSE_LASER, 5, 1, '16/128/256/128/100', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16',
                            RECIPE_TYPE_COMP.':'.COMPONENT_LASER.':1', '',
                            'mm:'. MODULE_PERIMETER.':12;t:'.TECH_BASE_TACTIC.':12', 0, 0, '1;2;250;20;250');
    $recipes[] = array(RECIPE_TYPE_DEFENSE, DEFENSE_RLGUN, 6, 1, '16/64/256/64/100', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32',
                            RECIPE_TYPE_COMP.':'.COMPONENT_RAILGUN.':1', '',
                            'mm:'. MODULE_PERIMETER.':15;t:'.TECH_BASE_TACTIC.':15', 0, 0, '1;4;150;40;350');

    // tech stats - mult
    //  mult = multiplier per level of skill to apply
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_FUEL_EXT, 1, 1, '1000/1000/1000/1000/60', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_FUEL_EXT, 1, 2, '4000/4000/4000/4000/240', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_FUEL_EXT, 1, 3, '9000/9000/9000/9000/540', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_FUEL_EXT, 1, 4, '16000/16000/16000/16000/960', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_FUEL_EXT, 1, 5, '25000/25000/25000/25000/1500', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_FUEL_EXT, 1, 6, '36000/36000/36000/36000/2160', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_FUEL_EXT, 1, 7, '49000/49000/49000/49000/2940', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_FUEL_EXT, 1, 8, '64000/64000/64000/64000/3840', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_FUEL_EXT, 1, 9, '81000/81000/81000/81000/4860', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_FUEL_EXT, 1, 10, '100000/100000/100000/100000/6000', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_FUEL_EXT, 1, 11, '121000/121000/121000/121000/7260', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_FUEL_EXT, 1, 12, '144000/144000/144000/144000/8640', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_FUEL_EXT, 1, 13, '169000/169000/169000/169000/10140', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_FUEL_EXT, 1, 14, '196000/196000/196000/196000/11760', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_FUEL_EXT, 1, 15, '225000/225000/225000/225000/13500', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_FUEL_EXT, 1, 16, '256000/256000/256000/256000/15360', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');

    $recipes[] = array(RECIPE_TYPE_TECH, TECH_METAL_EXT, 2, 1, '1000/1000/1000/1000/60', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_METAL_EXT, 2, 2, '4000/4000/4000/4000/240', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_METAL_EXT, 2, 3, '9000/9000/9000/9000/540', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_METAL_EXT, 2, 4, '16000/16000/16000/16000/960', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_METAL_EXT, 2, 5, '25000/25000/25000/25000/1500', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_METAL_EXT, 2, 6, '36000/36000/36000/36000/2160', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_METAL_EXT, 2, 7, '49000/49000/49000/49000/2940', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_METAL_EXT, 2, 8, '64000/64000/64000/64000/3840', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_METAL_EXT, 2, 9, '81000/81000/81000/81000/4860', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_METAL_EXT, 2, 10, '100000/100000/100000/100000/6000', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_METAL_EXT, 2, 11, '121000/121000/121000/121000/7260', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_METAL_EXT, 2, 12, '144000/144000/144000/144000/8640', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_METAL_EXT, 2, 13, '169000/169000/169000/169000/10140', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_METAL_EXT, 2, 14, '196000/196000/196000/196000/11760', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_METAL_EXT, 2, 15, '225000/225000/225000/225000/13500', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_METAL_EXT, 2, 16, '256000/256000/256000/256000/15360', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');

    $recipes[] = array(RECIPE_TYPE_TECH, TECH_MINERAL_EXT, 3, 1, '1000/1000/1000/1000/60', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_MINERAL_EXT, 3, 2, '4000/4000/4000/4000/240', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_MINERAL_EXT, 3, 3, '9000/9000/9000/9000/540', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_MINERAL_EXT, 3, 4, '16000/16000/16000/16000/960', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_MINERAL_EXT, 3, 5, '25000/25000/25000/25000/1500', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_MINERAL_EXT, 3, 6, '36000/36000/36000/36000/2160', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_MINERAL_EXT, 3, 7, '49000/49000/49000/49000/2940', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_MINERAL_EXT, 3, 8, '64000/64000/64000/64000/3840', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_MINERAL_EXT, 3, 9, '81000/81000/81000/81000/4860', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_MINERAL_EXT, 3, 10, '100000/100000/100000/100000/6000', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_MINERAL_EXT, 3, 11, '121000/121000/121000/121000/7260', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_MINERAL_EXT, 3, 12, '144000/144000/144000/144000/8640', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_MINERAL_EXT, 3, 13, '169000/169000/169000/169000/10140', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_MINERAL_EXT, 3, 14, '196000/196000/196000/196000/11760', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_MINERAL_EXT, 3, 15, '225000/225000/225000/225000/13500', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_MINERAL_EXT, 3, 16, '256000/256000/256000/256000/15360', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');

    $recipes[] = array(RECIPE_TYPE_TECH, TECH_XTAL_EXT, 4, 1, '1000/1000/1000/1000/60', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_XTAL_EXT, 4, 2, '4000/4000/4000/4000/240', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_XTAL_EXT, 4, 3, '9000/9000/9000/9000/540', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_XTAL_EXT, 4, 4, '16000/16000/16000/16000/960', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_XTAL_EXT, 4, 5, '25000/25000/25000/25000/1500', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_XTAL_EXT, 4, 6, '36000/36000/36000/36000/2160', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_XTAL_EXT, 4, 7, '49000/49000/49000/49000/2940', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_XTAL_EXT, 4, 8, '64000/64000/64000/64000/3840', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_XTAL_EXT, 4, 9, '81000/81000/81000/81000/4860', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_XTAL_EXT, 4, 10, '100000/100000/100000/100000/6000', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_XTAL_EXT, 4, 11, '121000/121000/121000/121000/7260', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_XTAL_EXT, 4, 12, '144000/144000/144000/144000/8640', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_XTAL_EXT, 4, 13, '169000/169000/169000/169000/10140', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_XTAL_EXT, 4, 14, '196000/196000/196000/196000/11760', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_XTAL_EXT, 4, 15, '225000/225000/225000/225000/13500', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_XTAL_EXT, 4, 16, '256000/256000/256000/256000/15360', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_RESEARCH, 0, 0, '0.08');

    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RECYCLE, 5, 1, '1000/1000/1000/1000/60', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_STORAGE.';M:'.MODULE_RESEARCH.';T:'.TECH_FUEL_EXT.';T:'.TECH_METAL_EXT.';T:'.TECH_MINERAL_EXT.';T:'.TECH_XTAL_EXT, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RECYCLE, 5, 2, '4000/4000/4000/4000/240', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_STORAGE.';M:'.MODULE_RESEARCH.';T:'.TECH_FUEL_EXT.';T:'.TECH_METAL_EXT.';T:'.TECH_MINERAL_EXT.';T:'.TECH_XTAL_EXT, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RECYCLE, 5, 3, '9000/9000/9000/9000/540', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_STORAGE.';M:'.MODULE_RESEARCH.';T:'.TECH_FUEL_EXT.';T:'.TECH_METAL_EXT.';T:'.TECH_MINERAL_EXT.';T:'.TECH_XTAL_EXT, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RECYCLE, 5, 4, '16000/16000/16000/16000/960', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_STORAGE.';M:'.MODULE_RESEARCH.';T:'.TECH_FUEL_EXT.';T:'.TECH_METAL_EXT.';T:'.TECH_MINERAL_EXT.';T:'.TECH_XTAL_EXT, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RECYCLE, 5, 5, '25000/25000/25000/25000/1500', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_STORAGE.';M:'.MODULE_RESEARCH.';T:'.TECH_FUEL_EXT.';T:'.TECH_METAL_EXT.';T:'.TECH_MINERAL_EXT.';T:'.TECH_XTAL_EXT, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RECYCLE, 5, 6, '36000/36000/36000/36000/2160', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_STORAGE.';M:'.MODULE_RESEARCH.';T:'.TECH_FUEL_EXT.';T:'.TECH_METAL_EXT.';T:'.TECH_MINERAL_EXT.';T:'.TECH_XTAL_EXT, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RECYCLE, 5, 7, '49000/49000/49000/49000/2940', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_STORAGE.';M:'.MODULE_RESEARCH.';T:'.TECH_FUEL_EXT.';T:'.TECH_METAL_EXT.';T:'.TECH_MINERAL_EXT.';T:'.TECH_XTAL_EXT, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RECYCLE, 5, 8, '64000/64000/64000/64000/3840', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_STORAGE.';M:'.MODULE_RESEARCH.';T:'.TECH_FUEL_EXT.';T:'.TECH_METAL_EXT.';T:'.TECH_MINERAL_EXT.';T:'.TECH_XTAL_EXT, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RECYCLE, 5, 9, '81000/81000/81000/81000/4860', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_STORAGE.';M:'.MODULE_RESEARCH.';T:'.TECH_FUEL_EXT.';T:'.TECH_METAL_EXT.';T:'.TECH_MINERAL_EXT.';T:'.TECH_XTAL_EXT, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RECYCLE, 5, 10, '100000/100000/100000/100000/6000', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_STORAGE.';M:'.MODULE_RESEARCH.';T:'.TECH_FUEL_EXT.';T:'.TECH_METAL_EXT.';T:'.TECH_MINERAL_EXT.';T:'.TECH_XTAL_EXT, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RECYCLE, 5, 11, '121000/121000/121000/121000/7260', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_STORAGE.';M:'.MODULE_RESEARCH.';T:'.TECH_FUEL_EXT.';T:'.TECH_METAL_EXT.';T:'.TECH_MINERAL_EXT.';T:'.TECH_XTAL_EXT, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RECYCLE, 5, 12, '144000/144000/144000/144000/8640', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_STORAGE.';M:'.MODULE_RESEARCH.';T:'.TECH_FUEL_EXT.';T:'.TECH_METAL_EXT.';T:'.TECH_MINERAL_EXT.';T:'.TECH_XTAL_EXT, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RECYCLE, 5, 13, '169000/169000/169000/169000/10140', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_STORAGE.';M:'.MODULE_RESEARCH.';T:'.TECH_FUEL_EXT.';T:'.TECH_METAL_EXT.';T:'.TECH_MINERAL_EXT.';T:'.TECH_XTAL_EXT, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RECYCLE, 5, 14, '196000/196000/196000/196000/11760', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_STORAGE.';M:'.MODULE_RESEARCH.';T:'.TECH_FUEL_EXT.';T:'.TECH_METAL_EXT.';T:'.TECH_MINERAL_EXT.';T:'.TECH_XTAL_EXT, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RECYCLE, 5, 15, '225000/225000/225000/225000/13500', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_STORAGE.';M:'.MODULE_RESEARCH.';T:'.TECH_FUEL_EXT.';T:'.TECH_METAL_EXT.';T:'.TECH_MINERAL_EXT.';T:'.TECH_XTAL_EXT, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RECYCLE, 5, 16, '256000/256000/256000/256000/15360', '', '', '',
                            'M:'.MODULE_REFINE.';M:'.MODULE_STORAGE.';M:'.MODULE_RESEARCH.';T:'.TECH_FUEL_EXT.';T:'.TECH_METAL_EXT.';T:'.TECH_MINERAL_EXT.';T:'.TECH_XTAL_EXT, 0, 0, '0.04');

    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_CAP, 6, 1, '1000/1000/1000/1000/60', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_CAP, 6, 2, '4000/4000/4000/4000/240', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_CAP, 6, 3, '9000/9000/9000/9000/540', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_CAP, 6, 4, '16000/16000/16000/16000/960', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_CAP, 6, 5, '25000/25000/25000/25000/1500', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_CAP, 6, 6, '36000/36000/36000/36000/2160', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_CAP, 6, 7, '49000/49000/49000/49000/2940', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_CAP, 6, 8, '64000/64000/64000/64000/3840', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_CAP, 6, 9, '81000/81000/81000/81000/4860', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_CAP, 6, 10, '100000/100000/100000/100000/6000', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_CAP, 6, 11, '121000/121000/121000/121000/7260', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_CAP, 6, 12, '144000/144000/144000/144000/8640', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_CAP, 6, 13, '169000/169000/169000/169000/10140', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_CAP, 6, 14, '196000/196000/196000/196000/11760', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_CAP, 6, 15, '225000/225000/225000/225000/13500', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_CAP, 6, 16, '256000/256000/256000/256000/15360', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');

    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SYNTHESIS, 7, 1, '1000/1000/1000/1000/60', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SYNTHESIS, 7, 2, '4000/4000/4000/4000/240', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SYNTHESIS, 7, 3, '9000/9000/9000/9000/540', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SYNTHESIS, 7, 4, '16000/16000/16000/16000/960', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SYNTHESIS, 7, 5, '25000/25000/25000/25000/1500', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SYNTHESIS, 7, 6, '36000/36000/36000/36000/2160', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SYNTHESIS, 7, 7, '49000/49000/49000/49000/2940', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SYNTHESIS, 7, 8, '64000/64000/64000/64000/3840', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SYNTHESIS, 7, 9, '81000/81000/81000/81000/4860', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SYNTHESIS, 7, 10, '100000/100000/100000/100000/6000', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SYNTHESIS, 7, 11, '121000/121000/121000/121000/7260', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SYNTHESIS, 7, 12, '144000/144000/144000/144000/8640', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SYNTHESIS, 7, 13, '169000/169000/169000/169000/10140', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SYNTHESIS, 7, 14, '196000/196000/196000/196000/11760', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SYNTHESIS, 7, 15, '225000/225000/225000/225000/13500', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SYNTHESIS, 7, 16, '256000/256000/256000/256000/15360', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');

    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_ASSY, 8, 1, '1000/1000/1000/1000/60', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_ASSY, 8, 2, '4000/4000/4000/4000/240', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_ASSY, 8, 3, '9000/9000/9000/9000/540', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_ASSY, 8, 4, '16000/16000/16000/16000/960', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_ASSY, 8, 5, '25000/25000/25000/25000/1500', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_ASSY, 8, 6, '36000/36000/36000/36000/2160', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_ASSY, 8, 7, '49000/49000/49000/49000/2940', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_ASSY, 8, 8, '64000/64000/64000/64000/3840', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_ASSY, 8, 9, '81000/81000/81000/81000/4860', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_ASSY, 8, 10, '100000/100000/100000/100000/6000', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_ASSY, 8, 11, '121000/121000/121000/121000/7260', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_ASSY, 8, 12, '144000/144000/144000/144000/8640', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_ASSY, 8, 13, '169000/169000/169000/169000/10140', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_ASSY, 8, 14, '196000/196000/196000/196000/11760', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_ASSY, 8, 15, '225000/225000/225000/225000/13500', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.08');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_ASSY, 8, 16, '256000/256000/256000/256000/15360', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.08');

    $recipes[] = array(RECIPE_TYPE_TECH, TECH_CONST, 14, 1, '1000/1000/1000/1000/60', '', '', '',
                            'm:'.MODULE_RESEARCH.':4', 0, 0, '0.16');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_CONST, 14, 2, '4000/4000/4000/4000/240', '', '', '',
                            'm:'.MODULE_RESEARCH.':4', 0, 0, '0.16');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_CONST, 14, 3, '9000/9000/9000/9000/540', '', '', '',
                            'm:'.MODULE_RESEARCH.':4', 0, 0, '0.16');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_CONST, 14, 4, '16000/16000/16000/16000/960', '', '', '',
                            'm:'.MODULE_RESEARCH.':4', 0, 0, '0.16');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_CONST, 14, 5, '25000/25000/25000/25000/1500', '', '', '',
                            'm:'.MODULE_RESEARCH.':4', 0, 0, '0.16');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_CONST, 14, 6, '36000/36000/36000/36000/2160', '', '', '',
                            'm:'.MODULE_RESEARCH.':4', 0, 0, '0.16');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_CONST, 14, 7, '49000/49000/49000/49000/2940', '', '', '',
                            'm:'.MODULE_RESEARCH.':4', 0, 0, '0.16');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_CONST, 14, 8, '64000/64000/64000/64000/3840', '', '', '',
                            'm:'.MODULE_RESEARCH.':4', 0, 0, '0.16');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_CONST, 14, 9, '81000/81000/81000/81000/4860', '', '', '',
                            'm:'.MODULE_RESEARCH.':4', 0, 0, '0.16');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_CONST, 14, 10, '100000/100000/100000/100000/6000', '', '', '',
                            'm:'.MODULE_RESEARCH.':4', 0, 0, '0.16');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_CONST, 14, 11, '121000/121000/121000/121000/7260', '', '', '',
                            'm:'.MODULE_RESEARCH.':4', 0, 0, '0.16');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_CONST, 14, 12, '144000/144000/144000/144000/8640', '', '', '',
                            'm:'.MODULE_RESEARCH.':4', 0, 0, '0.16');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_CONST, 14, 13, '169000/169000/169000/169000/10140', '', '', '',
                            'm:'.MODULE_RESEARCH.':4', 0, 0, '0.16');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_CONST, 14, 14, '196000/196000/196000/196000/11760', '', '', '',
                            'm:'.MODULE_RESEARCH.':4', 0, 0, '0.16');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_CONST, 14, 15, '225000/225000/225000/225000/13500', '', '', '',
                            'm:'.MODULE_RESEARCH.':4', 0, 0, '0.16');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_CONST, 14, 16, '256000/256000/256000/256000/15360', '', '', '',
                            'm:'.MODULE_RESEARCH.':4', 0, 0, '0.16');

    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SCOUT, 9, 1, '1000/1000/1000/1000/60', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SCOUT, 9, 2, '4000/4000/4000/4000/240', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SCOUT, 9, 3, '9000/9000/9000/9000/540', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SCOUT, 9, 4, '16000/16000/16000/16000/960', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SCOUT, 9, 5, '25000/25000/25000/25000/1500', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SCOUT, 9, 6, '36000/36000/36000/36000/2160', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SCOUT, 9, 7, '49000/49000/49000/49000/2940', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SCOUT, 9, 8, '64000/64000/64000/64000/3840', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SCOUT, 9, 9, '81000/81000/81000/81000/4860', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SCOUT, 9, 10, '100000/100000/100000/100000/6000', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SCOUT, 9, 11, '121000/121000/121000/121000/7260', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SCOUT, 9, 12, '144000/144000/144000/144000/8640', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SCOUT, 9, 13, '169000/169000/169000/169000/10140', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SCOUT, 9, 14, '196000/196000/196000/196000/11760', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SCOUT, 9, 15, '225000/225000/225000/225000/13500', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_SCOUT, 9, 16, '256000/256000/256000/256000/15360', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');

    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DATA_PROC, 10, 1, '1000/1000/1000/1000/60', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DATA_PROC, 10, 2, '4000/4000/4000/4000/240', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DATA_PROC, 10, 3, '9000/9000/9000/9000/540', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DATA_PROC, 10, 4, '16000/16000/16000/16000/960', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DATA_PROC, 10, 5, '25000/25000/25000/25000/1500', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DATA_PROC, 10, 6, '36000/36000/36000/36000/2160', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DATA_PROC, 10, 7, '49000/49000/49000/49000/2940', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DATA_PROC, 10, 8, '64000/64000/64000/64000/3840', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DATA_PROC, 10, 9, '81000/81000/81000/81000/4860', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DATA_PROC, 10, 10, '100000/100000/100000/100000/6000', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DATA_PROC, 10, 11, '121000/121000/121000/121000/7260', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DATA_PROC, 10, 12, '144000/144000/144000/144000/8640', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DATA_PROC, 10, 13, '169000/169000/169000/169000/10140', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DATA_PROC, 10, 14, '196000/196000/196000/196000/11760', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DATA_PROC, 10, 15, '225000/225000/225000/225000/13500', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DATA_PROC, 10, 16, '256000/256000/256000/256000/15360', '', '', '',
                            'M:'.MODULE_RESEARCH, 0, 0, '0.04');

    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_MOVE, 11, 1, '1000/1000/1000/1000/60', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_MOVE, 11, 2, '4000/4000/4000/4000/240', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_MOVE, 11, 3, '9000/9000/9000/9000/540', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_MOVE, 11, 4, '16000/16000/16000/16000/960', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_MOVE, 11, 5, '25000/25000/25000/25000/1500', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_MOVE, 11, 6, '36000/36000/36000/36000/2160', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_MOVE, 11, 7, '49000/49000/49000/49000/2940', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_MOVE, 11, 8, '64000/64000/64000/64000/3840', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_MOVE, 11, 9, '81000/81000/81000/81000/4860', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_MOVE, 11, 10, '100000/100000/100000/100000/6000', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_MOVE, 11, 11, '121000/121000/121000/121000/7260', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_MOVE, 11, 12, '144000/144000/144000/144000/8640', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_MOVE, 11, 13, '169000/169000/169000/169000/10140', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_MOVE, 11, 14, '196000/196000/196000/196000/11760', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_MOVE, 11, 15, '225000/225000/225000/225000/13500', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_MOVE, 11, 16, '256000/256000/256000/256000/15360', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');

    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_TACTIC, 12, 1, '1000/1000/1000/1000/60', '', '', '',
                            'm:'.MODULE_RESEARCH.':2;T:'.TECH_DRONE_MOVE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_TACTIC, 12, 2, '4000/4000/4000/4000/240', '', '', '',
                            'm:'.MODULE_RESEARCH.':2;T:'.TECH_DRONE_MOVE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_TACTIC, 12, 3, '9000/9000/9000/9000/540', '', '', '',
                            'm:'.MODULE_RESEARCH.':2;T:'.TECH_DRONE_MOVE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_TACTIC, 12, 4, '16000/16000/16000/16000/960', '', '', '',
                            'm:'.MODULE_RESEARCH.':2;T:'.TECH_DRONE_MOVE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_TACTIC, 12, 5, '25000/25000/25000/25000/1500', '', '', '',
                            'm:'.MODULE_RESEARCH.':2;T:'.TECH_DRONE_MOVE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_TACTIC, 12, 6, '36000/36000/36000/36000/2160', '', '', '',
                            'm:'.MODULE_RESEARCH.':2;T:'.TECH_DRONE_MOVE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_TACTIC, 12, 7, '49000/49000/49000/49000/2940', '', '', '',
                            'm:'.MODULE_RESEARCH.':2;T:'.TECH_DRONE_MOVE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_TACTIC, 12, 8, '64000/64000/64000/64000/3840', '', '', '',
                            'm:'.MODULE_RESEARCH.':2;T:'.TECH_DRONE_MOVE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_TACTIC, 12, 9, '81000/81000/81000/81000/4860', '', '', '',
                            'm:'.MODULE_RESEARCH.':2;T:'.TECH_DRONE_MOVE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_TACTIC, 12, 10, '100000/100000/100000/100000/6000', '', '', '',
                            'm:'.MODULE_RESEARCH.':2;T:'.TECH_DRONE_MOVE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_TACTIC, 12, 11, '121000/121000/121000/121000/7260', '', '', '',
                            'm:'.MODULE_RESEARCH.':2;T:'.TECH_DRONE_MOVE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_TACTIC, 12, 12, '144000/144000/144000/144000/8640', '', '', '',
                            'm:'.MODULE_RESEARCH.':2;T:'.TECH_DRONE_MOVE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_TACTIC, 12, 13, '169000/169000/169000/169000/10140', '', '', '',
                            'm:'.MODULE_RESEARCH.':2;T:'.TECH_DRONE_MOVE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_TACTIC, 12, 14, '196000/196000/196000/196000/11760', '', '', '',
                            'm:'.MODULE_RESEARCH.':2;T:'.TECH_DRONE_MOVE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_TACTIC, 12, 15, '225000/225000/225000/225000/13500', '', '', '',
                            'm:'.MODULE_RESEARCH.':2;T:'.TECH_DRONE_MOVE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_TACTIC, 12, 16, '256000/256000/256000/256000/15360', '', '', '',
                            'm:'.MODULE_RESEARCH.':2;T:'.TECH_DRONE_MOVE, 0, 0, '0.04');

    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_DEFENSE, 13, 1, '1000/1000/1000/1000/60', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_DEFENSE, 13, 2, '4000/4000/4000/4000/240', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_DEFENSE, 13, 3, '9000/9000/9000/9000/540', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_DEFENSE, 13, 4, '16000/16000/16000/16000/960', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_DEFENSE, 13, 5, '25000/25000/25000/25000/1500', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_DEFENSE, 13, 6, '36000/36000/36000/36000/2160', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_DEFENSE, 13, 7, '49000/49000/49000/49000/2940', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_DEFENSE, 13, 8, '64000/64000/64000/64000/3840', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_DEFENSE, 13, 9, '81000/81000/81000/81000/4860', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_DEFENSE, 13, 10, '100000/100000/100000/100000/6000', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_DEFENSE, 13, 11, '121000/121000/121000/121000/7260', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_DEFENSE, 13, 12, '144000/144000/144000/144000/8640', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_DEFENSE, 13, 13, '169000/169000/169000/169000/10140', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_DEFENSE, 13, 14, '196000/196000/196000/196000/11760', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_DEFENSE, 13, 15, '225000/225000/225000/225000/13500', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_DEFENSE, 13, 16, '256000/256000/256000/256000/15360', '', '', '',
                            'm:'.MODULE_RESEARCH.':2', 0, 0, '0.04');

    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_TACTIC, 15, 1, '1000/1000/1000/1000/60', '', '', '',
                            'm:'.MODULE_RESEARCH.':4;T:'.TECH_BASE_DEFENSE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_TACTIC, 15, 2, '4000/4000/4000/4000/240', '', '', '',
                            'm:'.MODULE_RESEARCH.':4;T:'.TECH_BASE_DEFENSE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_TACTIC, 15, 3, '9000/9000/9000/9000/540', '', '', '',
                            'm:'.MODULE_RESEARCH.':4;T:'.TECH_BASE_DEFENSE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_TACTIC, 15, 4, '16000/16000/16000/16000/960', '', '', '',
                            'm:'.MODULE_RESEARCH.':4;T:'.TECH_BASE_DEFENSE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_TACTIC, 15, 5, '25000/25000/25000/25000/1500', '', '', '',
                            'm:'.MODULE_RESEARCH.':4;T:'.TECH_BASE_DEFENSE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_TACTIC, 15, 6, '36000/36000/36000/36000/2160', '', '', '',
                            'm:'.MODULE_RESEARCH.':4;T:'.TECH_BASE_DEFENSE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_TACTIC, 15, 7, '49000/49000/49000/49000/2940', '', '', '',
                            'm:'.MODULE_RESEARCH.':4;T:'.TECH_BASE_DEFENSE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_TACTIC, 15, 8, '64000/64000/64000/64000/3840', '', '', '',
                            'm:'.MODULE_RESEARCH.':4;T:'.TECH_BASE_DEFENSE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_TACTIC, 15, 9, '81000/81000/81000/81000/4860', '', '', '',
                            'm:'.MODULE_RESEARCH.':4;T:'.TECH_BASE_DEFENSE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_TACTIC, 15, 10, '100000/100000/100000/100000/6000', '', '', '',
                            'm:'.MODULE_RESEARCH.':4;T:'.TECH_BASE_DEFENSE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_TACTIC, 15, 11, '121000/121000/121000/121000/7260', '', '', '',
                            'm:'.MODULE_RESEARCH.':4;T:'.TECH_BASE_DEFENSE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_TACTIC, 15, 12, '144000/144000/144000/144000/8640', '', '', '',
                            'm:'.MODULE_RESEARCH.':4;T:'.TECH_BASE_DEFENSE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_TACTIC, 15, 13, '169000/169000/169000/169000/10140', '', '', '',
                            'm:'.MODULE_RESEARCH.':4;T:'.TECH_BASE_DEFENSE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_TACTIC, 15, 14, '196000/196000/196000/196000/11760', '', '', '',
                            'm:'.MODULE_RESEARCH.':4;T:'.TECH_BASE_DEFENSE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_TACTIC, 15, 15, '225000/225000/225000/225000/13500', '', '', '',
                            'm:'.MODULE_RESEARCH.':4;T:'.TECH_BASE_DEFENSE, 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_BASE_TACTIC, 15, 16, '256000/256000/256000/256000/15360', '', '', '',
                            'm:'.MODULE_RESEARCH.':4;T:'.TECH_BASE_DEFENSE, 0, 0, '0.04');

    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_REP, 16, 1, '1000/1000/1000/1000/60', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_REP, 16, 2, '4000/4000/4000/4000/240', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_REP, 16, 3, '9000/9000/9000/9000/540', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_REP, 16, 4, '16000/16000/16000/16000/960', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_REP, 16, 5, '25000/25000/25000/25000/1500', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_REP, 16, 6, '36000/36000/36000/36000/2160', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_REP, 16, 7, '49000/49000/49000/49000/2940', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_REP, 16, 8, '64000/64000/64000/64000/3840', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_REP, 16, 9, '81000/81000/81000/81000/4860', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_REP, 16, 10, '100000/100000/100000/100000/6000', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_REP, 16, 11, '121000/121000/121000/121000/7260', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_REP, 16, 12, '144000/144000/144000/144000/8640', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_REP, 16, 13, '169000/169000/169000/169000/10140', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_REP, 16, 14, '196000/196000/196000/196000/11760', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_REP, 16, 15, '225000/225000/225000/225000/13500', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_REP, 16, 16, '256000/256000/256000/256000/15360', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');

    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_CAP, 17, 1, '1000/1000/1000/1000/60', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_CAP, 17, 2, '4000/4000/4000/4000/240', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_CAP, 17, 3, '9000/9000/9000/9000/540', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_CAP, 17, 4, '16000/16000/16000/16000/960', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_CAP, 17, 5, '25000/25000/25000/25000/1500', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_CAP, 17, 6, '36000/36000/36000/36000/2160', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_CAP, 17, 7, '49000/49000/49000/49000/2940', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_CAP, 17, 8, '64000/64000/64000/64000/3840', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_CAP, 17, 9, '81000/81000/81000/81000/4860', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_CAP, 17, 10, '100000/100000/100000/100000/6000', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_CAP, 17, 11, '121000/121000/121000/121000/7260', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_CAP, 17, 12, '144000/144000/144000/144000/8640', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_CAP, 17, 13, '169000/169000/169000/169000/10140', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_CAP, 17, 14, '196000/196000/196000/196000/11760', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_CAP, 17, 15, '225000/225000/225000/225000/13500', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_CAP, 17, 16, '256000/256000/256000/256000/15360', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');

    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RESEARCH, 18, 1, '1000/1000/1000/1000/60', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RESEARCH, 18, 2, '4000/4000/4000/4000/240', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RESEARCH, 18, 3, '9000/9000/9000/9000/540', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RESEARCH, 18, 4, '16000/16000/16000/16000/960', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RESEARCH, 18, 5, '25000/25000/25000/25000/1500', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RESEARCH, 18, 6, '36000/36000/36000/36000/2160', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RESEARCH, 18, 7, '49000/49000/49000/49000/2940', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RESEARCH, 18, 8, '64000/64000/64000/64000/3840', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RESEARCH, 18, 9, '81000/81000/81000/81000/4860', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RESEARCH, 18, 10, '100000/100000/100000/100000/6000', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RESEARCH, 18, 11, '121000/121000/121000/121000/7260', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RESEARCH, 18, 12, '144000/144000/144000/144000/8640', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RESEARCH, 18, 13, '169000/169000/169000/169000/10140', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RESEARCH, 18, 14, '196000/196000/196000/196000/11760', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RESEARCH, 18, 15, '225000/225000/225000/225000/13500', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_RESEARCH, 18, 16, '256000/256000/256000/256000/15360', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');

    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_FUEL, 19, 1, '1000/1000/1000/1000/60', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_FUEL, 19, 2, '4000/4000/4000/4000/240', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_FUEL, 19, 3, '9000/9000/9000/9000/540', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_FUEL, 19, 4, '16000/16000/16000/16000/960', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_FUEL, 19, 5, '25000/25000/25000/25000/1500', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_FUEL, 19, 6, '36000/36000/36000/36000/2160', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_FUEL, 19, 7, '49000/49000/49000/49000/2940', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_FUEL, 19, 8, '64000/64000/64000/64000/3840', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_FUEL, 19, 9, '81000/81000/81000/81000/4860', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_FUEL, 19, 10, '100000/100000/100000/100000/6000', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_FUEL, 19, 11, '121000/121000/121000/121000/7260', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_FUEL, 19, 12, '144000/144000/144000/144000/8640', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_FUEL, 19, 13, '169000/169000/169000/169000/10140', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_FUEL, 19, 14, '196000/196000/196000/196000/11760', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_FUEL, 19, 15, '225000/225000/225000/225000/13500', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');
    $recipes[] = array(RECIPE_TYPE_TECH, TECH_DRONE_FUEL, 19, 16, '256000/256000/256000/256000/15360', '', '', '',
                            'm:'.MODULE_RESEARCH.':8', 0, 0, '0.04');

    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_CONTROL, 1, 2, '1600/3600/3600/3600/1200', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4', '', '',
                            'BB:1;P:0.5', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_CONTROL, 1, 3, '3600/8100/8100/8100/2700', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8', '', '',
                            'BB:1;P:0.5', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_CONTROL, 1, 4, '6400/14400/14400/14400/4800', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16', '', '',
                            'BB:1;P:0.5', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_CONTROL, 1, 5, '10000/22500/22500/22500/7500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32', '', '',
                            'BB:1;P:0.5', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_CONTROL, 1, 6, '14400/32400/32400/32400/10800', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:64', '', '',
                            'BB:1;P:0.5', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_CONTROL, 1, 7, '19600/44100/44100/44100/14700', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:128', '', '',
                            'BB:1;P:0.5', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_CONTROL, 1, 8, '25600/57600/57600/57600/19200', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:256', '', '',
                            'BB:1;P:0.5', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_CONTROL, 1, 9, '32400/72900/72900/72900/24300', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:512', '', '',
                            'BB:1;P:0.5', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_CONTROL, 1, 10, '40000/90000/90000/90000/30000', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1024', '', '',
                            'BB:1;P:0.5', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_CONTROL, 1, 11, '48400/108900/108900/108900/36300', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2048', '', '',
                            'BB:1;P:0.5', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_CONTROL, 1, 12, '57600/129600/129600/129600/43200', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4096', '', '',
                            'BB:1;P:0.5', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_CONTROL, 1, 13, '67600/152100/152100/152100/50700', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8192', '', '',
                            'BB:1;P:0.5', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_CONTROL, 1, 14, '78400/176400/176400/176400/58800', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16384', '', '',
                            'BB:1;P:0.5', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_CONTROL, 1, 15, '90000/202500/202500/202500/67500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32768', '', '',
                            'BB:1;P:0.5', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_CONTROL, 1, 16, '102400/230400/230400/230400/76800', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:65536', '', '',
                            'BB:1;P:0.5', 0, 0, '');

    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_PERIMETER, 1, 1, '800/1600/800/400/60', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_PERIMETER, 1, 2, '3200/6400/3200/1600/240', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_PERIMETER, 1, 3, '7200/14400/7200/3600/540', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_PERIMETER, 1, 4, '12800/25600/12800/6400/960', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_PERIMETER, 1, 5, '20000/40000/20000/10000/1500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_PERIMETER, 1, 6, '28800/57600/28800/14400/2160', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:64', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_PERIMETER, 1, 7, '39200/78400/39200/19600/2940', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:128', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_PERIMETER, 1, 8, '51200/102400/51200/25600/3840', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:256', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_PERIMETER, 1, 9, '64800/129600/64800/32400/4860', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:512', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_PERIMETER, 1, 10, '80000/160000/80000/40000/6000', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1024', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_PERIMETER, 1, 11, '96800/193600/96800/48400/7260', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2048', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_PERIMETER, 1, 12, '115200/230400/115200/57600/8640', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4096', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_PERIMETER, 1, 13, '135200/270400/135200/67600/10140', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8192', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_PERIMETER, 1, 14, '156800/313600/156800/78400/11760', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16384', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_PERIMETER, 1, 15, '180000/360000/180000/90000/13500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32768', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_PERIMETER, 1, 16, '204800/409600/204800/102400/15360', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:65536', '', '',
                            'B:1', 0, 0, '');

    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_ASSEMBLY, 1, 1, '800/1600/1600/1600/60', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1', '', '',
                            'B:1;M:'.MODULE_STORAGE.';T:'.TECH_DRONE_ASSY, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_ASSEMBLY, 1, 2, '3200/6400/6400/6400/240', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2', '', '',
                            'B:1;M:'.MODULE_STORAGE.';T:'.TECH_DRONE_ASSY, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_ASSEMBLY, 1, 3, '7200/14400/14400/14400/540', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4', '', '',
                            'B:1;M:'.MODULE_STORAGE.';T:'.TECH_DRONE_ASSY, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_ASSEMBLY, 1, 4, '12800/25600/25600/25600/960', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8', '', '',
                            'B:1;M:'.MODULE_STORAGE.';T:'.TECH_DRONE_ASSY, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_ASSEMBLY, 1, 5, '20000/40000/40000/40000/1500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16', '', '',
                            'B:1;M:'.MODULE_STORAGE.';T:'.TECH_DRONE_ASSY, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_ASSEMBLY, 1, 6, '28800/57600/57600/57600/2160', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32', '', '',
                            'B:1;M:'.MODULE_STORAGE.';T:'.TECH_DRONE_ASSY, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_ASSEMBLY, 1, 7, '39200/78400/78400/78400/2940', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:64', '', '',
                            'B:1;M:'.MODULE_STORAGE.';T:'.TECH_DRONE_ASSY, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_ASSEMBLY, 1, 8, '51200/102400/102400/102400/3840', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:128', '', '',
                            'B:1;M:'.MODULE_STORAGE.';T:'.TECH_DRONE_ASSY, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_ASSEMBLY, 1, 9, '64800/129600/129600/129600/4860', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:256', '', '',
                            'B:1;M:'.MODULE_STORAGE.';T:'.TECH_DRONE_ASSY, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_ASSEMBLY, 1, 10, '80000/160000/160000/160000/6000', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:512', '', '',
                            'B:1;M:'.MODULE_STORAGE.';T:'.TECH_DRONE_ASSY, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_ASSEMBLY, 1, 11, '96800/193600/193600/193600/7260', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1024', '', '',
                            'B:1;M:'.MODULE_STORAGE.';T:'.TECH_DRONE_ASSY, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_ASSEMBLY, 1, 12, '115200/230400/230400/230400/8640', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2048', '', '',
                            'B:1;M:'.MODULE_STORAGE.';T:'.TECH_DRONE_ASSY, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_ASSEMBLY, 1, 13, '135200/270400/270400/270400/10140', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4096', '', '',
                            'B:1;M:'.MODULE_STORAGE.';T:'.TECH_DRONE_ASSY, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_ASSEMBLY, 1, 14, '156800/313600/313600/313600/11760', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8192', '', '',
                            'B:1;M:'.MODULE_STORAGE.';T:'.TECH_DRONE_ASSY, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_ASSEMBLY, 1, 15, '180000/360000/360000/360000/13500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16384', '', '',
                            'B:1;M:'.MODULE_STORAGE.';T:'.TECH_DRONE_ASSY, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_ASSEMBLY, 1, 16, '204800/409600/409600/409600/15360', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32768', '', '',
                            'B:1;M:'.MODULE_STORAGE.';T:'.TECH_DRONE_ASSY, 0, 0, '');

    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REFINE, 1, 1, '400/800/800/400/60', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REFINE, 1, 2, '1600/3200/3200/1600/240', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REFINE, 1, 3, '3600/7200/7200/3600/540', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REFINE, 1, 4, '6400/12800/12800/6400/960', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REFINE, 1, 5, '10000/20000/20000/10000/1500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REFINE, 1, 6, '14400/28800/28800/14400/2160', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REFINE, 1, 7, '19600/39200/39200/19600/2940', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:64', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REFINE, 1, 8, '25600/51200/51200/25600/3840', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:128', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REFINE, 1, 9, '32400/64800/64800/32400/4860', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:256', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REFINE, 1, 10, '40000/80000/80000/40000/6000', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:512', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REFINE, 1, 11, '48400/96800/96800/48400/7260', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1024', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REFINE, 1, 12, '57600/115200/115200/57600/8640', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2048', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REFINE, 1, 13, '67600/135200/135200/67600/10140', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4096', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REFINE, 1, 14, '78400/156800/156800/78400/11760', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8192', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REFINE, 1, 15, '90000/180000/180000/90000/13500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16384', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REFINE, 1, 16, '102400/204800/204800/102400/15360', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32768', '', '',
                            'B:1', 0, 0, '');

    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RESEARCH, 1, 1, '400/800/800/1600/60', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RESEARCH, 1, 2, '1600/3200/3200/6400/240', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RESEARCH, 1, 3, '3600/7200/7200/14400/540', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RESEARCH, 1, 4, '6400/12800/12800/25600/960', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RESEARCH, 1, 5, '10000/20000/20000/40000/1500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RESEARCH, 1, 6, '14400/28800/28800/57600/2160', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RESEARCH, 1, 7, '19600/39200/39200/78400/2940', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:64', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RESEARCH, 1, 8, '25600/51200/51200/102400/3840', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:128', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RESEARCH, 1, 9, '32400/64800/64800/129600/4860', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:256', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RESEARCH, 1, 10, '40000/80000/80000/160000/6000', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:512', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RESEARCH, 1, 11, '48400/96800/96800/193600/7260', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1024', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RESEARCH, 1, 12, '57600/115200/115200/230400/8640', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2048', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RESEARCH, 1, 13, '67600/135200/135200/270400/10140', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4096', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RESEARCH, 1, 14, '78400/156800/156800/313600/11760', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8192', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RESEARCH, 1, 15, '90000/180000/180000/360000/13500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16384', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RESEARCH, 1, 16, '102400/204800/204800/409600/15360', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32768', '', '',
                            'B:1', 0, 0, '');

    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_STORAGE, 1, 1, '400/800/800/400/60', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1', '', '',
                            'B:1;T:'.TECH_BASE_CAP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_STORAGE, 1, 2, '1600/3200/3200/1600/240', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2', '', '',
                            'B:1;T:'.TECH_BASE_CAP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_STORAGE, 1, 3, '3600/7200/7200/3600/540', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4', '', '',
                            'B:1;T:'.TECH_BASE_CAP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_STORAGE, 1, 4, '6400/12800/12800/6400/960', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8', '', '',
                            'B:1;T:'.TECH_BASE_CAP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_STORAGE, 1, 5, '10000/20000/20000/10000/1500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16', '', '',
                            'B:1;T:'.TECH_BASE_CAP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_STORAGE, 1, 6, '14400/28800/28800/14400/2160', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32', '', '',
                            'B:1;T:'.TECH_BASE_CAP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_STORAGE, 1, 7, '19600/39200/39200/19600/2940', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:64', '', '',
                            'B:1;T:'.TECH_BASE_CAP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_STORAGE, 1, 8, '25600/51200/51200/25600/3840', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:128', '', '',
                            'B:1;T:'.TECH_BASE_CAP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_STORAGE, 1, 9, '32400/64800/64800/32400/4860', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:256', '', '',
                            'B:1;T:'.TECH_BASE_CAP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_STORAGE, 1, 10, '40000/80000/80000/40000/6000', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:512', '', '',
                            'B:1;T:'.TECH_BASE_CAP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_STORAGE, 1, 11, '48400/96800/96800/48400/7260', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1024', '', '',
                            'B:1;T:'.TECH_BASE_CAP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_STORAGE, 1, 12, '57600/115200/115200/57600/8640', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2048', '', '',
                            'B:1;T:'.TECH_BASE_CAP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_STORAGE, 1, 13, '67600/135200/135200/67600/10140', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4096', '', '',
                            'B:1;T:'.TECH_BASE_CAP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_STORAGE, 1, 14, '78400/156800/156800/78400/11760', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8192', '', '',
                            'B:1;T:'.TECH_BASE_CAP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_STORAGE, 1, 15, '90000/180000/180000/90000/13500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16384', '', '',
                            'B:1;T:'.TECH_BASE_CAP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_STORAGE, 1, 16, '102400/204800/204800/102400/15360', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32768', '', '',
                            'B:1;T:'.TECH_BASE_CAP, 0, 0, '');

    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REPAIR, 1, 1, '400/800/800/800/60', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1', '', '',
                            'bb:6;T:'.TECH_DRONE_REP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REPAIR, 1, 2, '1600/3200/3200/3200/240', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2', '', '',
                            'bb:6;T:'.TECH_DRONE_REP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REPAIR, 1, 3, '3600/7200/7200/7200/540', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4', '', '',
                            'bb:6;T:'.TECH_DRONE_REP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REPAIR, 1, 4, '6400/12800/12800/12800/960', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8', '', '',
                            'bb:6;T:'.TECH_DRONE_REP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REPAIR, 1, 5, '10000/20000/20000/20000/1500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16', '', '',
                            'bb:6;T:'.TECH_DRONE_REP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REPAIR, 1, 6, '14400/28800/28800/28800/2160', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32', '', '',
                            'bb:6;T:'.TECH_DRONE_REP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REPAIR, 1, 7, '19600/39200/39200/39200/2940', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:64', '', '',
                            'bb:6;T:'.TECH_DRONE_REP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REPAIR, 1, 8, '25600/51200/51200/51200/3840', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:128', '', '',
                            'bb:6;T:'.TECH_DRONE_REP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REPAIR, 1, 9, '32400/64800/64800/64800/4860', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:256', '', '',
                            'bb:6;T:'.TECH_DRONE_REP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REPAIR, 1, 10, '40000/80000/80000/80000/6000', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:512', '', '',
                            'bb:6;T:'.TECH_DRONE_REP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REPAIR, 1, 11, '48400/96800/96800/96800/7260', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1024', '', '',
                            'bb:6;T:'.TECH_DRONE_REP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REPAIR, 1, 12, '57600/115200/115200/115200/8640', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2048', '', '',
                            'bb:6;T:'.TECH_DRONE_REP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REPAIR, 1, 13, '67600/135200/135200/135200/10140', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4096', '', '',
                            'bb:6;T:'.TECH_DRONE_REP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REPAIR, 1, 14, '78400/156800/156800/156800/11760', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8192', '', '',
                            'bb:6;T:'.TECH_DRONE_REP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REPAIR, 1, 15, '90000/180000/180000/180000/13500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16384', '', '',
                            'bb:6;T:'.TECH_DRONE_REP, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_REPAIR, 1, 16, '102400/204800/204800/204800/15360', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32768', '', '',
                            'bb:6;T:'.TECH_DRONE_REP, 0, 0, '');

    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RECYCLE, 1, 1, '400/800/800/400/60', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1', '', '',
                            'bb:6;T:'.TECH_RECYCLE, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RECYCLE, 1, 2, '1600/3200/3200/1600/240', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2', '', '',
                            'bb:6;T:'.TECH_RECYCLE, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RECYCLE, 1, 3, '3600/7200/7200/3600/540', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4', '', '',
                            'bb:6;T:'.TECH_RECYCLE, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RECYCLE, 1, 4, '6400/12800/12800/6400/960', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8', '', '',
                            'bb:6;T:'.TECH_RECYCLE, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RECYCLE, 1, 5, '10000/20000/20000/10000/1500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16', '', '',
                            'bb:6;T:'.TECH_RECYCLE, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RECYCLE, 1, 6, '14400/28800/28800/14400/2160', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32', '', '',
                            'bb:6;T:'.TECH_RECYCLE, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RECYCLE, 1, 7, '19600/39200/39200/19600/2940', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:64', '', '',
                            'bb:6;T:'.TECH_RECYCLE, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RECYCLE, 1, 8, '25600/51200/51200/25600/3840', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:128', '', '',
                            'bb:6;T:'.TECH_RECYCLE, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RECYCLE, 1, 9, '32400/64800/64800/32400/4860', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:256', '', '',
                            'bb:6;T:'.TECH_RECYCLE, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RECYCLE, 1, 10, '40000/80000/80000/40000/6000', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:512', '', '',
                            'bb:6;T:'.TECH_RECYCLE, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RECYCLE, 1, 11, '48400/96800/96800/48400/7260', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1024', '', '',
                            'bb:6;T:'.TECH_RECYCLE, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RECYCLE, 1, 12, '57600/115200/115200/57600/8640', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2048', '', '',
                            'bb:6;T:'.TECH_RECYCLE, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RECYCLE, 1, 13, '67600/135200/135200/67600/10140', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4096', '', '',
                            'bb:6;T:'.TECH_RECYCLE, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RECYCLE, 1, 14, '78400/156800/156800/78400/11760', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8192', '', '',
                            'bb:6;T:'.TECH_RECYCLE, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RECYCLE, 1, 15, '90000/180000/180000/90000/13500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16384', '', '',
                            'bb:6;T:'.TECH_RECYCLE, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_RECYCLE, 1, 16, '102400/204800/204800/102400/15360', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32768', '', '',
                            'bb:6;T:'.TECH_RECYCLE, 0, 0, '');

    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRANSCEIVER, 1, 1, '400/800/800/1600/60', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRANSCEIVER, 1, 2, '1600/3200/3200/6400/240', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRANSCEIVER, 1, 3, '3600/7200/7200/14400/540', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRANSCEIVER, 1, 4, '6400/12800/12800/25600/960', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRANSCEIVER, 1, 5, '10000/20000/20000/40000/1500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRANSCEIVER, 1, 6, '14400/28800/28800/57600/2160', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRANSCEIVER, 1, 7, '19600/39200/39200/78400/2940', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:64', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRANSCEIVER, 1, 8, '25600/51200/51200/102400/3840', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:128', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRANSCEIVER, 1, 9, '32400/64800/64800/129600/4860', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:256', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRANSCEIVER, 1, 10, '40000/80000/80000/160000/6000', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:512', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRANSCEIVER, 1, 11, '48400/96800/96800/193600/7260', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1024', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRANSCEIVER, 1, 12, '57600/115200/115200/230400/8640', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2048', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRANSCEIVER, 1, 13, '67600/135200/135200/270400/10140', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2096', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRANSCEIVER, 1, 14, '78400/156800/156800/313600/11760', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8192', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRANSCEIVER, 1, 15, '90000/180000/180000/360000/13500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16384', '', '',
                            'B:1', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRANSCEIVER, 1, 16, '102400/204800/204800/409600/15360', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32768', '', '',
                            'B:1', 0, 0, '');

    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRADING, 1, 1, '400/400/400/400/60', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1', '', '',
                            'bb:3', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRADING, 1, 2, '1600/1600/1600/1600/240', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2', '', '',
                            'bb:3', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRADING, 1, 3, '3600/3600/3600/3600/540', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4', '', '',
                            'bb:3', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRADING, 1, 4, '6400/6400/6400/6400/960', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8', '', '',
                            'bb:3', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRADING, 1, 5, '10000/10000/10000/10000/1500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16', '', '',
                            'bb:3', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRADING, 1, 6, '14400/14400/14400/14400/2160', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32', '', '',
                            'bb:3', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRADING, 1, 7, '19600/19600/19600/19600/2940', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:64', '', '',
                            'bb:3', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRADING, 1, 8, '25600/25600/25600/25600/3840', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:128', '', '',
                            'bb:3', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRADING, 1, 9, '32400/32400/32400/32400/4860', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:256', '', '',
                            'bb:3', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRADING, 1, 10, '40000/40000/40000/40000/6000', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:512', '', '',
                            'bb:3', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRADING, 1, 11, '48400/48400/48400/48400/7260', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1024', '', '',
                            'bb:3', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRADING, 1, 12, '57600/57600/57600/57600/8640', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2048', '', '',
                            'bb:3', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRADING, 1, 13, '67600/67600/67600/67600/10140', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4096', '', '',
                            'bb:3', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRADING, 1, 14, '78400/78400/78400/78400/11760', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8192', '', '',
                            'bb:3', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRADING, 1, 15, '90000/90000/90000/90000/13500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16384', '', '',
                            'bb:3', 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_TRADING, 1, 16, '102400/102400/102400/102400/15360', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32768', '', '',
                            'bb:3', 0, 0, '');

    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SCAN, 1, 1, '400/400/900/900/60', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1', '', '',
                            'bb:3;T:'.TECH_SCOUT.';T:'.TECH_DATA_PROC, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SCAN, 1, 2, '1600/1600/3600/3600/240', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2', '', '',
                            'bb:3;T:'.TECH_SCOUT.';T:'.TECH_DATA_PROC, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SCAN, 1, 3, '3600/3600/8100/8100/540', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4', '', '',
                            'bb:3;T:'.TECH_SCOUT.';T:'.TECH_DATA_PROC, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SCAN, 1, 4, '6400/6400/14400/14400/960', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8', '', '',
                            'bb:3;T:'.TECH_SCOUT.';T:'.TECH_DATA_PROC, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SCAN, 1, 5, '10000/10000/22500/22500/1500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16', '', '',
                            'bb:3;T:'.TECH_SCOUT.';T:'.TECH_DATA_PROC, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SCAN, 1, 6, '14400/14400/32400/32400/2160', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32', '', '',
                            'bb:3;T:'.TECH_SCOUT.';T:'.TECH_DATA_PROC, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SCAN, 1, 7, '19600/19600/44100/44100/2940', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:64', '', '',
                            'bb:3;T:'.TECH_SCOUT.';T:'.TECH_DATA_PROC, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SCAN, 1, 8, '25600/25600/57600/57600/3840', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:128', '', '',
                            'bb:3;T:'.TECH_SCOUT.';T:'.TECH_DATA_PROC, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SCAN, 1, 9, '32400/32400/72900/72900/4860', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:256', '', '',
                            'bb:3;T:'.TECH_SCOUT.';T:'.TECH_DATA_PROC, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SCAN, 1, 10, '40000/40000/90000/90000/6000', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:512', '', '',
                            'bb:3;T:'.TECH_SCOUT.';T:'.TECH_DATA_PROC, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SCAN, 1, 11, '48400/48400/108900/108900/7260', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1024', '', '',
                            'bb:3;T:'.TECH_SCOUT.';T:'.TECH_DATA_PROC, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SCAN, 1, 12, '57600/57600/129600/129600/8640', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2048', '', '',
                            'bb:3;T:'.TECH_SCOUT.';T:'.TECH_DATA_PROC, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SCAN, 1, 13, '67600/67600/152100/152100/10140', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4096', '', '',
                            'bb:3;T:'.TECH_SCOUT.';T:'.TECH_DATA_PROC, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SCAN, 1, 14, '78400/78400/176400/176400/11760', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8192', '', '',
                            'bb:3;T:'.TECH_SCOUT.';T:'.TECH_DATA_PROC, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SCAN, 1, 15, '90000/90000/202500/202500/13500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16384', '', '',
                            'bb:3;T:'.TECH_SCOUT.';T:'.TECH_DATA_PROC, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SCAN, 1, 16, '102400/102400/230400/230400/15360', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32768', '', '',
                            'bb:3;T:'.TECH_SCOUT.';T:'.TECH_DATA_PROC, 0, 0, '');

    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SYNTHESIS, 1, 1, '400/900/900/900/60', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1', '', '',
                            'B:1;T:'.TECH_SYNTHESIS, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SYNTHESIS, 1, 2, '1600/3600/3600/3600/240', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2', '', '',
                            'B:1;T:'.TECH_SYNTHESIS, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SYNTHESIS, 1, 3, '3600/8100/8100/8100/540', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4', '', '',
                            'B:1;T:'.TECH_SYNTHESIS, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SYNTHESIS, 1, 4, '6400/14400/14400/14400/960', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8', '', '',
                            'B:1;T:'.TECH_SYNTHESIS, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SYNTHESIS, 1, 5, '10000/22500/22500/22500/1500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16', '', '',
                            'B:1;T:'.TECH_SYNTHESIS, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SYNTHESIS, 1, 6, '14400/32400/32400/32400/2160', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32', '', '',
                            'B:1;T:'.TECH_SYNTHESIS, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SYNTHESIS, 1, 7, '19600/44100/44100/44100/2940', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:64', '', '',
                            'B:1;T:'.TECH_SYNTHESIS, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SYNTHESIS, 1, 8, '25600/57600/57600/57600/3840', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:128', '', '',
                            'B:1;T:'.TECH_SYNTHESIS, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SYNTHESIS, 1, 9, '32400/72900/72900/72900/4860', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:256', '', '',
                            'B:1;T:'.TECH_SYNTHESIS, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SYNTHESIS, 1, 10, '40000/90000/90000/90000/6000', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:512', '', '',
                            'B:1;T:'.TECH_SYNTHESIS, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SYNTHESIS, 1, 11, '48400/108900/108900/108900/7260', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:1024', '', '',
                            'B:1;T:'.TECH_SYNTHESIS, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SYNTHESIS, 1, 12, '57600/129600/129600/129600/8640', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:2048', '', '',
                            'B:1;T:'.TECH_SYNTHESIS, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SYNTHESIS, 1, 13, '67600/152100/152100/152100/10140', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:4096', '', '',
                            'B:1;T:'.TECH_SYNTHESIS, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SYNTHESIS, 1, 14, '78400/176400/176400/176400/11760', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:8192', '', '',
                            'B:1;T:'.TECH_SYNTHESIS, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SYNTHESIS, 1, 15, '90000/202500/202500/202500/13500', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:16384', '', '',
                            'B:1;T:'.TECH_SYNTHESIS, 0, 0, '');
    $recipes[] = array(RECIPE_TYPE_MODULE, MODULE_SYNTHESIS, 1, 16, '102400/230400/230400/230400/15360', RECIPE_TYPE_DRONE.':'.DRONE_WORKR_CON.':1:32768', '', '',
                            'B:1;T:'.TECH_SYNTHESIS, 0, 0, '');


    $errors = 0;
    foreach ($recipes as $idx=>$recipe) {
        if (count($recipe) != $reqcount) {
            echo "\nError in definition of recipe $idx, has " . count($recipe) . " entries but requires $reqcount\n";
            $errors++;
        }
    }

    if ($errors == 0) {
        $mysqlidb = new mysqli($gamedbserver, $gamedbase, DBPASS, $gamedbase);
        $mysqlidb->query("delete from recipes"); // clean table
        foreach ($recipes as $idx=>$recipe) {
            $query = "replace into recipes (type,ident,dorder,level,resources,drones,components,items,preqs,scrap,carrierspace,stats) values("
                        . "'{$recipe[0]}',"
                        . "'{$recipe[1]}',"
                        . "{$recipe[2]},"
                        . "{$recipe[3]},"
                        . "'{$recipe[4]}',"
                        . "'{$recipe[5]}',"
                        . "'{$recipe[6]}',"
                        . "'{$recipe[7]}',"
                        . "'{$recipe[8]}',"
                        . "{$recipe[9]},"
                        . "{$recipe[10]},"
                        . "'{$recipe[11]}')";
            $result = $mysqlidb->query($query);
            if ($result == false) {
                echo "$idx: $query\n";
                echo "$idx: " . $mysqlidb->error . "\n";
                $errors++;
                break;
            }
        }
    }
    $idx++;
    echo "$idx recipes added\n";
?>
