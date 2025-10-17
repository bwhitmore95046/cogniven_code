<?php
/*
 * php script to fill or update text table.
 */
if (isset($_SERVER{'HTTP_HOST'})) {
    die ("Must run from command line only!\n");
}
// get environment variables
if ($argc < 3) {
    print "\nUsage: php -f imaiy_dbtextfill.php <database> <dbserverip>\n"
            ."\t dbserverip is the ip address of the dbserver associated with this game server\n\n";
    return;
}
$gamedbase = $argv[1];
$gamedbserver = $argv[2];

include "globals.php";

    /*
     * names array for items - inserted into names table
     *  format "array:prefix:index:en"
     *      array - name of array in php
     *      prefix - prefix for id of html element for use in js (names.php)
     *      index - index of element in array or appended to prefix
     *      en - english name text for item
     * description array for items - inserted into description table
     *  formation "array:prefix:index:en
     *      array - name of array in php
     *      prefix - prefix for id of html element for use in js (descriptions.php)
     *      index - index of element in array or appended to prefix
     *      en - english description text for item
     */
    $reqcount = 4; // how many entries needed for each item
    $names = array();
    $description = array();

    $names[] = array('form_purp_name','form_purp_name_',FORMATION_PURPOSE_TRANS,'Transport');
    $names[] = array('form_purp_name','form_purp_name_',FORMATION_PURPOSE_RECON,'Recon');
    $names[] = array('form_purp_name','form_purp_name_',FORMATION_PURPOSE_REINF,'Reinforce');
    $names[] = array('form_purp_name','form_purp_name_',FORMATION_PURPOSE_SCVNG,'Scavenge');
    $names[] = array('form_purp_name','form_purp_name_',FORMATION_PURPOSE_MOVE,'Move');
    $names[] = array('form_purp_name','form_purp_name_',FORMATION_PURPOSE_RETRV,'Retrieve');
    $names[] = array('form_purp_name','form_purp_name_',FORMATION_PURPOSE_ATACK,'Raid');
    $names[] = array('form_purp_name','form_purp_name_',FORMATION_PURPOSE_BLITZ,'Blitz');
    $names[] = array('form_purp_name','form_purp_name_',FORMATION_PURPOSE_ASSLT,'Raze');

    $description[] = array('mod_desc','mod_desc_',MODULE_NONE,'Off world communiction and main control for interacting with all operations on Imaiy');

    $names[] = array('mod_name','mod_name_',MODULE_CONTROL,'Control');
    $description[] = array('mod_desc','mod_desc_',MODULE_CONTROL,'Overall base control which reflects level of base');
    $names[] = array('mod_name','mod_name_',MODULE_PERIMETER,'Perimeter');
    $description[] = array('mod_desc','mod_desc_',MODULE_PERIMETER,'Determines capacity for passive and active defenses. Active defenses use fuel while enabled and while involved in defending base if enabled. If active defenses are disabled, they do no offensive damage. Base Defense tech skill boosts effectiveness of defenses');
    $names[] = array('mod_name','mod_name_',MODULE_ASSEMBLY,'Drone Assembly');
    $description[] = array('mod_desc','mod_desc_',MODULE_ASSEMBLY,'Assembling drone chassis and adapting them to specialized purposes');
    $names[] = array('mod_name','mod_name_',MODULE_REFINE,'Refining');
    $description[] = array('mod_desc','mod_desc_',MODULE_REFINE,'Efficiency of resource production from controlled locations. Analysis stat of production manager and extraction tech skills boosts perfomance');
    $names[] = array('mod_name','mod_name_',MODULE_RESEARCH,'Research');
    $description[] = array('mod_desc','mod_desc_',MODULE_RESEARCH,'Increase technology skill capabilities which boost associated capabilities');
    $names[] = array('mod_name','mod_name_',MODULE_STORAGE,'Storage');
    $description[] = array('mod_desc','mod_desc_',MODULE_STORAGE,'Capacity for Resources and Drones in a base. Base Storage tech skill increases capacity for resrouces while Drone Storage increases quantity of drones that can be stored in a base');
    $names[] = array('mod_name','mod_name_',MODULE_REPAIR,'Drone Repair');
    $description[] = array('mod_desc','mod_desc_',MODULE_REPAIR,'Drone Repair provides ability to repair drones and thus reduce losses from defense of base');
    $names[] = array('mod_name','mod_name_',MODULE_RECYCLE,'Recycling');
    $description[] = array('mod_desc','mod_desc_',MODULE_RECYCLE,'Processes of drones, components and scrap to recover resources. There is a chance of being able to recover components and other items from scrap');
    $names[] = array('mod_name','mod_name_',MODULE_TRANSCEIVER,'Communications');
    $description[] = array('mod_desc','mod_desc_',MODULE_TRANSCEIVER,'Enables secure communications between organization members and formations. Level determines how many moving formations can be monitored at once and how many reinforcing formations from organization members can be supported by base');
    $names[] = array('mod_name','mod_name_',MODULE_TRADING,'Trading');
    $description[] = array('mod_desc','mod_desc_',MODULE_TRADING,'Trading used for participating in trade managed by Central AI');
    $names[] = array('mod_name','mod_name_',MODULE_SCAN,'Scanning');
    $description[] = array('mod_desc','mod_desc_',MODULE_SCAN,'Scanning used for detecting incoming attacks, jamming scans of base and monitoring controlled of locations. Heuristics stat of sensor manager and Data Processing tech skill improves performance');
    $names[] = array('mod_name','mod_name_',MODULE_SYNTHESIS,'Synthesis');
    $description[] = array('mod_desc','mod_desc_',MODULE_SYNTHESIS,'Synthesis used for creation of most components and Drudge AIs');

    $names[] = array('drone_name','drone_name_',DRONE_RECON,'Recon Chassis');
    $description[] = array('drone_desc','drone_desc_',DRONE_RECON,'Basic chassis used to make Survey, Scout and Scanning Recon drones');
    $names[] = array('drone_name','drone_name_',DRONE_RECON_SVY,'Survey Recon');
    $description[] = array('drone_desc','drone_desc_',DRONE_RECON_SVY,'Survey Recon drones are ground units that determine resource stores and production');
    $names[] = array('drone_name','drone_name_',DRONE_RECON_SCO,'Scout Recon');
    $description[] = array('drone_desc','drone_desc_',DRONE_RECON_SCO,'Scout Recon drones are ground units that determine drone types and numbers');
    $names[] = array('drone_name','drone_name_',DRONE_RECON_SCN,'Scanning Recon');
    $description[] = array('drone_desc','drone_desc_',DRONE_RECON_SCN,'Scanning Recon drones are air units that determine base information');
    $names[] = array('drone_name','drone_name_',DRONE_TRANS,'Transport Chassis');
    $description[] = array('drone_desc','drone_desc_',DRONE_TRANS,'Basic chassis used to make Material and Drone transport drones');
    $names[] = array('drone_name','drone_name_',DRONE_TRANS_MAT,'Material Transport');
    $description[] = array('drone_desc','drone_desc_',DRONE_TRANS_MAT,'Material transport drones are used to carry resources and components');
    $names[] = array('drone_name','drone_name_',DRONE_TRANS_GND,'Ground Transport');
    $description[] = array('drone_desc','drone_desc_',DRONE_TRANS_GND,'Ground Drone transport drones are used to carry other non-transport drones to increase overall movement rates');
    $names[] = array('drone_name','drone_name_',DRONE_TRANS_AIR,'Air Transport');
    $description[] = array('drone_desc','drone_desc_',DRONE_TRANS_AIR,'Air Drone transport drones are used to carry other non-transport drones to increase overall movement rates and greatly reduce chance of encounters with rogue drones');
    $names[] = array('drone_name','drone_name_',DRONE_FIGHT,'Military Chassis');
    $description[] = array('drone_desc','drone_desc_',DRONE_FIGHT,'Basic chassis used to make Light, Medium, Heavy and Defense fighter drones');
    $names[] = array('drone_name','drone_name_',DRONE_FIGHT_LIT,'Light Military');
    $description[] = array('drone_desc','drone_desc_',DRONE_FIGHT_LIT,'Light Fighter drones are ground units equipped with lasers for offense and light shielding');
    $names[] = array('drone_name','drone_name_',DRONE_FIGHT_MED,'Medium Military');
    $description[] = array('drone_desc','drone_desc_',DRONE_FIGHT_MED,'Medium Fighter drones are ground units equipped with particle beams for offense and medium shielding');
    $names[] = array('drone_name','drone_name_',DRONE_FIGHT_HVY,'Heavy Military');
    $description[] = array('drone_desc','drone_desc_',DRONE_FIGHT_HVY,'Heavy Fighter drones are ground units equipped with railguns for offense and heavy shielding');
    $names[] = array('drone_name','drone_name_',DRONE_FIGHT_DEF,'Defense Military');
    $description[] = array('drone_desc','drone_desc_',DRONE_FIGHT_DEF,'Defense drones are ground units equipped with shield generators that protect up to ' . DRONE_DEF_SHIELD_AMT . ' drones in a formation');
    $names[] = array('drone_name','drone_name_',DRONE_WORKR,'Worker Chassis');
    $description[] = array('drone_desc','drone_desc_',DRONE_WORKR,'Basic chassis used to make Construction, Mining and scavenging worker drones');
    $names[] = array('drone_name','drone_name_',DRONE_WORKR_CON,'Construction Worker');
    $description[] = array('drone_desc','drone_desc_',DRONE_WORKR_CON,'Construction drones are used to build bases, modules and defenses');
    $names[] = array('drone_name','drone_name_',DRONE_WORKR_MIN,'Mining Worker');
    $description[] = array('drone_desc','drone_desc_',DRONE_WORKR_MIN,'Mining drones are used to gather resources from locations controlled by a base');
    $names[] = array('drone_name','drone_name_',DRONE_WORKR_SCV,'Scavenging Worker');
    $description[] = array('drone_desc','drone_desc_',DRONE_WORKR_SCV,'Scavenging drones are used to gather scrap from locations and increase chance of recovering items from combat targets');

    $names[] = array('drone_defense_name','drone_defense_name_',0,'None');
    $description[] = array('drone_defense_desc','drone_defense_desc_',0,'');
    $names[] = array('drone_defense_name','drone_defense_name_',1,'Armor');
    $description[] = array('drone_defense_desc','drone_defense_desc_',1,'');
    $names[] = array('drone_defense_name','drone_defense_name_',2,'Energy');
    $description[] = array('drone_defense_desc','drone_defense_desc_',2,'');
    $names[] = array('drone_defense_name','drone_defense_name_',3,'Armor & Energy');
    $description[] = array('drone_defense_desc','drone_defense_desc_',3,'');

    $names[] = array('drone_offense_name','drone_offense_name_',0,'None');
    $description[] = array('drone_offense_desc','drone_offense_desc_',0,'');
    $names[] = array('drone_offense_name','drone_offense_name_',1,'Physical');
    $description[] = array('drone_offense_desc','drone_offense_desc_',1,'');
    $names[] = array('drone_offense_name','drone_offense_name_',2,'Laser');
    $description[] = array('drone_offense_desc','drone_offense_desc_',2,'');
    $names[] = array('drone_offense_name','drone_offense_name_',3,'Particle');
    $description[] = array('drone_offense_desc','drone_offense_desc_',3,'');
    $names[] = array('drone_offense_name','drone_offense_name_',4,'Rail');
    $description[] = array('drone_offense_desc','drone_offense_desc_',4,'');

    $names[] = array('tech_name','tech_name_',TECH_FUEL_EXT,'Fuel extraction');
    $description[] = array('tech_desc','tech_desc_',TECH_FUEL_EXT,'Fuel extraction - improves fuel extraction rates');
    $names[] = array('tech_name','tech_name_',TECH_METAL_EXT,'Metal extraction');
    $description[] = array('tech_desc','tech_desc_',TECH_METAL_EXT,'Metal extraction - improves metal extraction rates');
    $names[] = array('tech_name','tech_name_',TECH_MINERAL_EXT,'Mineral extraction');
    $description[] = array('tech_desc','tech_desc_',TECH_MINERAL_EXT,'Mineral extraction - improves mineral extraction rates');
    $names[] = array('tech_name','tech_name_',TECH_XTAL_EXT,'Crystal extraction');
    $description[] = array('tech_desc','tech_desc_',TECH_XTAL_EXT,'Crystal extraction - improves crystal extraction rates');
    $names[] = array('tech_name','tech_name_',TECH_RECYCLE,'Recycling');
    $description[] = array('tech_desc','tech_desc_',TECH_RECYCLE,'Recycling - required for recycling module, improves recovery rates from scrap');
    $names[] = array('tech_name','tech_name_',TECH_SCOUT,'Anomaly Recognition');
    $description[] = array('tech_desc','tech_desc_',TECH_SCOUT,'Anomaly Recognition - required for recon drones and scan module - improves scout and scanning performance');
    $names[] = array('tech_name','tech_name_',TECH_CONST,'Construction');
    $description[] = array('tech_desc','tech_desc_',TECH_CONST,'Construction - improves rate and reduces material waste in module construction and drone assembly');
    $names[] = array('tech_name','tech_name_',TECH_BASE_CAP,'Base storage');
    $description[] = array('tech_desc','tech_desc_',TECH_BASE_CAP,'Base storage - increases base capacity for resources and drones');
    $names[] = array('tech_name','tech_name_',TECH_BASE_DEFENSE,'Base passive defense');
    $description[] = array('tech_desc','tech_desc_',TECH_BASE_DEFENSE,'Base passive defense - required for passive defense construction - improves robustness of passive defenses');
    $names[] = array('tech_name','tech_name_',TECH_BASE_TACTIC,'Base active defense');
    $description[] = array('tech_desc','tech_desc_',TECH_BASE_TACTIC,'Base active defense - required for active defense construction - improves accuracy of active defenses');
    $names[] = array('tech_name','tech_name_',TECH_RESEARCH,'Research efficiency');
    $description[] = array('tech_desc','tech_desc_',TECH_RESEARCH,'Research efficiency - reduces time and resources required to train tech capabilities');
    $names[] = array('tech_name','tech_name_',TECH_DATA_PROC,'Data processing');
    $description[] = array('tech_desc','tech_desc_',TECH_DATA_PROC,'Data processing - required for scanning module - improves efficiency of data processing');
    $names[] = array('tech_name','tech_name_',TECH_DRONE_REP,'Drone Repair');
    $description[] = array('tech_desc','tech_desc_',TECH_DRONE_REP,'Drone repair - provides chance to recover drones damaged in defense of a base');
    $names[] = array('tech_name','tech_name_',TECH_DRONE_ASSY,'Drone assembly');
    $description[] = array('tech_desc','tech_desc_',TECH_DRONE_ASSY,'Drone assembly - required for drone assembly module - improves efficiency of drone assembly');
    $names[] = array('tech_name','tech_name_',TECH_DRONE_CAP,'Drone capacity');
    $description[] = array('tech_desc','tech_desc_',TECH_DRONE_CAP,'Drone capacity - increases drone carrying capacity');
    $names[] = array('tech_name','tech_name_',TECH_DRONE_FUEL,'Drone fuel economy');
    $description[] = array('tech_desc','tech_desc_',TECH_DRONE_FUEL,'Drone fuel economy - increases drone fuel use efficiency');
    $names[] = array('tech_name','tech_name_',TECH_DRONE_MOVE,'Drone movement');
    $description[] = array('tech_desc','tech_desc_',TECH_DRONE_MOVE,'Drone movement - increase drone movement rates');
    $names[] = array('tech_name','tech_name_',TECH_DRONE_TACTIC,'Drone combat tactics');
    $description[] = array('tech_desc','tech_desc_',TECH_DRONE_TACTIC,'Drone combat tactics - increases drone ability to attack and defend - reduces chance of encounters with rogue drones');
    $names[] = array('tech_name','tech_name_',TECH_DRONE_FLIGHT,'Drone flight');
    $description[] = array('tech_desc','tech_desc_',TECH_DRONE_FLIGHT,'Drone flight - required for air staging module');
    $names[] = array('tech_name','tech_name_',TECH_SYNTHESIS,'Component synthesis');
    $description[] = array('tech_desc','tech_desc_',TECH_SYNTHESIS,'Component synthesis - required for component synthesis - increases efficiency of component manufacturing');

    $names[] = array('comp_name','comp_name_',COMPONENT_UNKNOWN,'Unknown');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_UNKNOWN,'Some strange thingy that may have a use but probably does not');
    $names[] = array('comp_name','comp_name_',COMPONENT_RSTRUT,'Reinforcement strut');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_RSTRUT,'Strut used to reinforce base passive defenses and to hold up the billboards');
    $names[] = array('comp_name','comp_name_',COMPONENT_APANEL,'Ablative panel');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_APANEL,'Panel used for base defense. Constructed of materials that obsorb damage and degrade to prevent damage to more critical items');
    $names[] = array('comp_name','comp_name_',COMPONENT_SHLDGEN,'Shield generator');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_SHLDGEN,'Shield generator used for base defense as well as in shielding drones');
    $names[] = array('comp_name','comp_name_',COMPONENT_PARTGEN,'Particle generator');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_PARTGEN,'The Particle accelerator is the heart of every partical cannon');
    $names[] = array('comp_name','comp_name_',COMPONENT_LASER,'Laser Cannon');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_LASER,'Lasers are the mainstream of active base defense as well as portable offenses for drones');
    $names[] = array('comp_name','comp_name_',COMPONENT_RAILGUN,'Railgun');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_RAILGUN,'Railguns are longer range weapons for base defense and heavy drones');
    $names[] = array('comp_name','comp_name_',COMPONENT_DCNTRL,'Drone control');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_DCNTRL,'The drone controller is the brain of every drone');
    $names[] = array('comp_name','comp_name_',COMPONENT_DATAP,'Data processor');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_DATAP,'Data processing is essential to every recon drone');
    $names[] = array('comp_name','comp_name_',COMPONENT_RSCAN,'Resource scanner');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_RSCAN,'Resource scanners contain specialized electronics for pinpointing resources while surveying bases and locations');
    $names[] = array('comp_name','comp_name_',COMPONENT_DSCAN,'Drone scanner');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_DSCAN,'Drone scanners are specialized for detecting the emmisions of drones while scouting');
    $names[] = array('comp_name','comp_name_',COMPONENT_BSCAN,'Base scanner');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_BSCAN,'Base scanners can penetrate base shielding to determine the capability of bases');
    $names[] = array('comp_name','comp_name_',COMPONENT_CMOUNT,'Crane mount');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_CMOUNT,'Crane mounts are necessary for base worker chassis to provide a solid platform for mounting specialized work tools');
    $names[] = array('comp_name','comp_name_',COMPONENT_RSCOOP,'Resource scoop');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_RSCOOP,'Resource scoops make the exposing, gathering and loading of resources simple');
    $names[] = array('comp_name','comp_name_',COMPONENT_LRAMP,'Loading ramp');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_LRAMP,'Landing ramps are specially reinforced to handle the traffic of even the largest drone');
    $names[] = array('comp_name','comp_name_',COMPONENT_TIEDWN,'Tie downs');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_TIEDWN,'Tie downs are necessary to avoid the early offloading of drones during transport');
    $names[] = array('comp_name','comp_name_',COMPONENT_RELMECH,'Release mechanism');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_RELMECH,'Release mechanisms make quick work of tie down removal once the transport has come to a complete and safe stop or at least slowed to non-lethal speed');
    $names[] = array('comp_name','comp_name_',COMPONENT_TSCAN,'Targeting scanner');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_TSCAN,'Drones just can not hit the broad side of a rogue base without one of these');
    $names[] = array('comp_name','comp_name_',COMPONENT_WMOUNT,'Weapon mount');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_WMOUNT,'Weapon mounts, for when duct tape and tie wraps just will not do to hold those weapons in place on military chassis');
    $names[] = array('comp_name','comp_name_',COMPONENT_CONARM,'Construction arm');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_CONARM,'These arms are specialized for manipulating bulky objects during base and module construction');
    $names[] = array('comp_name','comp_name_',COMPONENT_MINEARM,'Mining arm');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_MINEARM,'Holding a variety of specialized tools to facilitate the extraction of resources');
    $names[] = array('comp_name','comp_name_',COMPONENT_DISARM,'Disassembly arm');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_DISARM,'What can be put together can be taken apart quickly (not as fast as a good explosive, but fast enough) with one of these');
    $names[] = array('comp_name','comp_name_',COMPONENT_DAI,'Drudge AI');
    $description[] = array('comp_desc','comp_desc_',COMPONENT_DAI,'Drudge AIs are the controllers for drone formations or for specific management tasks in a base');

    $names[] = array('res_name','res_name_',0,'Fuel');
    $description[] = array('res_desc','res_desc_',0,'');
    $names[] = array('res_name','res_name_',1,'Metal');
    $description[] = array('res_desc','res_desc_',1,'');
    $names[] = array('res_name','res_name_',2,'Mineral');
    $description[] = array('res_desc','res_desc_',2,'');
    $names[] = array('res_name','res_name_',3,'Crystal');
    $description[] = array('res_desc','res_desc_',3,'');
    $names[] = array('res_name','res_name_',4,'Scrap');
    $description[] = array('res_desc','res_desc_',4,'');

    $description[] = array('newsec_desc','newsec_desc_',0,'Build Bases or capture/destroy Rogue bases to increase PC to Rogue base ratio. Donate resources to Central AI via Trade to accumulate necessary resources to replace infrastructure destroyed during Rogue revolt. Goals must be met for at least 24 hours.');

    $names[] = array('def_name','def_name_',DEFENSE_STRUC,'Structural Reinforcements');
    $description[] = array('def_desc','def_desc_',DEFENSE_STRUC,'Provides passive reinforcement to defense structures');
    $names[] = array('def_name','def_name_',DEFENSE_ABLAT,'Ablative Shields');
    $description[] = array('def_desc','def_desc_',DEFENSE_ABLAT,'Passive shielding designed to be worn away as it absorbs and redirects damage');
    $names[] = array('def_name','def_name_',DEFENSE_ENERS,'Energy Shields');
    $description[] = array('def_desc','def_desc_',DEFENSE_ENERS,'Active shielding designed to deflect incoming damage');
    $names[] = array('def_name','def_name_',DEFENSE_PBEAM,'Particle Beams');
    $description[] = array('def_desc','def_desc_',DEFENSE_PBEAM,'Active base offense utliizing plasma emmisions to damage incoming drones. Lightest damage but longest range');
    $names[] = array('def_name','def_name_',DEFENSE_LASER,'Lasers');
    $description[] = array('def_desc','def_desc_',DEFENSE_LASER,'Active base defense utilizing light emmisions to damage incoming drones. Medium damage with medium range');
    $names[] = array('def_name','def_name_',DEFENSE_RLGUN,'Railguns');
    $description[] = array('def_desc','def_desc_',DEFENSE_RLGUN,'Active base defense utilizing dense projectiles to damage incoming drones. Heaviest damage and shortest range');

    $names[] = array('org_name','org_name_',ALLIANCE_NONE,'None');
    $description[] = array('org_desc','org_desc_',ALLIANCE_NONE,'');
    $names[] = array('org_name','org_name_',ALLIANCE_PARTNERSHIP,'Partnership');
    $description[] = array('org_desc','org_desc_',ALLIANCE_PARTNERSHIP,'Organization capability of having '.ALLIANCE_PARTNERSHIP_MAX_MEMBERSHIP.' members and '.ALLIANCE_PARTNERSHIP_MAX_OFFICERS.' officers');
    $names[] = array('org_name','org_name_',ALLIANCE_ENTERPRISE,'Enterprise');
    $description[] = array('org_desc','org_desc_',ALLIANCE_ENTERPRISE,'Organization capability of having '.ALLIANCE_ENTERPRISE_MAX_MEMBERSHIP.' members and '.ALLIANCE_ENTERPRISE_MAX_OFFICERS.' officers');
    $names[] = array('org_name','org_name_',ALLIANCE_COOPERATIVE,'Cooperative');
    $description[] = array('org_desc','org_desc_',ALLIANCE_COOPERATIVE,'Organization capability of having '.ALLIANCE_COOPERATIVE_MAX_MEMBERSHIP.' members and '.ALLIANCE_COOPERATIVE_MAX_OFFICERS.' officers');
    $names[] = array('org_name','org_name_',ALLIANCE_CORPORATION,'Corporation');
    $description[] = array('org_desc','org_desc_',ALLIANCE_CORPORATION,'Organization capability of having '.ALLIANCE_CORPORATION_MAX_MEMBERSHIP.' members and '.ALLIANCE_CORPORATION_MAX_OFFICERS.' officers');
    $names[] = array('org_name','org_name_',ALLIANCE_CONGLOMERATE,'Conglomerate');
    $description[] = array('org_desc','org_desc_',ALLIANCE_CONGLOMERATE,'Organization capability of having '.ALLIANCE_CONGLOMERATE_MAX_MEMBERSHIP.' members and '.ALLIANCE_CONGLOMERATE_MAX_OFFICERS.' officers');

    $names[] = array('timer_name','timer_name_',TIMER_MOVEAI,'Move Master AI');
    $description[] = array('timer_desc','timer_desc_',TIMER_MOVEAI,'Move Master AI to another base');
    $names[] = array('timer_name','timer_name_',TIMER_LEVEL_PLAYER,'Increase Level of Master AI');
    $description[] = array('timer_desc','timer_desc_',TIMER_LEVEL_PLAYER,'');
    $names[] = array('timer_name','timer_name_',TIMER_SCRAP_RECYCLE,'Recycle Scrap');
    $description[] = array('timer_desc','timer_desc_',TIMER_SCRAP_RECYCLE,'');
    $names[] = array('timer_name','timer_name_',TIMER_TRADE_DELIVER,'Trade Delivery');
    $description[] = array('timer_desc','timer_desc_',TIMER_TRADE_DELIVER,'');
    $names[] = array('timer_name','timer_name_',TIMER_REMOVEAI,'Begin Master AI Move');
    $description[] = array('timer_desc','timer_desc_',TIMER_REMOVEAI,'Begin Move of Master AI to another base');
    $names[] = array('timer_name','timer_name_',TIMER_MAINTANENCE,'Maintanence');
    $description[] = array('timer_desc','timer_desc_',TIMER_MAINTANENCE,'System initiated base maintanence');
    $names[] = array('timer_name','timer_name_',TIMER_CANCEL_EVENT,'Cancel Event Request');
    $description[] = array('timer_desc','timer_desc_',TIMER_CANCEL_EVENT,'');
    $names[] = array('timer_name','timer_name_',TIMER_CENTRAL_DELIVER,'Central AI Delivery');
    $description[] = array('timer_desc','timer_desc_',TIMER_CENTRAL_DELIVER,'');
    $names[] = array('timer_name','timer_name_',TIMER_MOVE_BASE,'Relocate Base');
    $description[] = array('timer_desc','timer_desc_',TIMER_MOVE_BASE,'');
    $names[] = array('timer_name','timer_name_',TIMER_CONST_BASE,'Construct Base');
    $description[] = array('timer_desc','timer_desc_',TIMER_CONST_BASE,'');
    $names[] = array('timer_name','timer_name_',TIMER_REPAIR_BASE,'Repair Base');
    $description[] = array('timer_desc','timer_desc_',TIMER_REPAIR_BASE,'');
    $names[] = array('timer_name','timer_name_',TIMER_REM_DEFENSE,'Remove Base Defense');
    $description[] = array('timer_desc','timer_desc_',TIMER_REM_DEFENSE,'');
    $names[] = array('timer_name','timer_name_',TIMER_CON_DEFENSE,'Install Base Defense');
    $description[] = array('timer_desc','timer_desc_',TIMER_CON_DEFENSE,'');
    $names[] = array('timer_name','timer_name_',TIMER_CON_COMPONENT,'Synthesis component');
    $description[] = array('timer_desc','timer_desc_',TIMER_CON_COMPONENT,'');
    $names[] = array('timer_name','timer_name_',TIMER_ABANDON_BASE,'Abandon Base');
    $description[] = array('timer_desc','timer_desc_',TIMER_ABANDON_BASE,'');
    $names[] = array('timer_name','timer_name_',TIMER_ABANDON_LOC,'Abandon Location');
    $description[] = array('timer_desc','timer_desc_',TIMER_ABANDON_LOC,'');
    $names[] = array('timer_name','timer_name_',TIMER_LEVEL_MODULE,'Increase Level of module');
    $description[] = array('timer_desc','timer_desc_',TIMER_LEVEL_MODULE,'');
    $names[] = array('timer_name','timer_name_',TIMER_TRAIN_TECH,'Training Tech');
    $description[] = array('timer_desc','timer_desc_',TIMER_TRAIN_TECH,'');
    $names[] = array('timer_name','timer_name_',TIMER_DRONE_ASSY,'Assemble Drones');
    $description[] = array('timer_desc','timer_desc_',TIMER_DRONE_ASSY,'');
    $names[] = array('timer_name','timer_name_',TIMER_DRONE_RECY,'Recycle Drones');
    $description[] = array('timer_desc','timer_desc_',TIMER_DRONE_RECY,'');
    $names[] = array('timer_name','timer_name_',TIMER_DRONE_REPAIR,'Repair Drones');
    $description[] = array('timer_desc','timer_desc_',TIMER_DRONE_REPAIR,'');
    $names[] = array('timer_name','timer_name_',TIMER_DRONE_TRANS,'Formation Transport');
    $description[] = array('timer_desc','timer_desc_',TIMER_DRONE_TRANS,'');
    $names[] = array('timer_name','timer_name_',TIMER_DRONE_RECON,'Formation Recon');
    $description[] = array('timer_desc','timer_desc_',TIMER_DRONE_RECON,'');
    $names[] = array('timer_name','timer_name_',TIMER_DRONE_REINF,'Formation Reinforce');
    $description[] = array('timer_desc','timer_desc_',TIMER_DRONE_REINF,'');
    $names[] = array('timer_name','timer_name_',TIMER_DRONE_ATACK,'Formation Raid Attack');
    $description[] = array('timer_desc','timer_desc_',TIMER_DRONE_ATACK,'');
    $names[] = array('timer_name','timer_name_',TIMER_DRONE_SCVNG,'Formation Scavenge');
    $description[] = array('timer_desc','timer_desc_',TIMER_DRONE_SCVNG,'');
    $names[] = array('timer_name','timer_name_',TIMER_DRONE_RETRN,'Formation Return');
    $description[] = array('timer_desc','timer_desc_',TIMER_DRONE_RETRN,'');
    $names[] = array('timer_name','timer_name_',TIMER_DRONE_MOVE,'Formation Move');
    $description[] = array('timer_desc','timer_desc_',TIMER_DRONE_MOVE,'');
    $names[] = array('timer_name','timer_name_',TIMER_DRONE_RETRV,'Formation Retrieve');
    $description[] = array('timer_desc','timer_desc_',TIMER_DRONE_RETRV,'');
    $names[] = array('timer_name','timer_name_',TIMER_DRONE_BLITZ,'Formation Blitz Attack');
    $description[] = array('timer_desc','timer_desc_',TIMER_DRONE_BLITZ,'');
    $names[] = array('timer_name','timer_name_',TIMER_DRONE_ASSLT,'Formation Raze Attack');
    $description[] = array('timer_desc','timer_desc_',TIMER_DRONE_ASSLT,'');

    $names[] = array('dai_role','dai_role_',DRUDGEAI_ROLE_IDLE,'Idle');
    $description[] = array('dai_role_desc','dai_role_desc_',DRUDGEAI_ROLE_IDLE,'');
    $names[] = array('dai_role','dai_role_',DRUDGEAI_ROLE_RESOURCE,'Production manager');
    $description[] = array('dai_role_desc','dai_role_desc_',DRUDGEAI_ROLE_RESOURCE,'');
    $names[] = array('dai_role','dai_role_',DRUDGEAI_ROLE_SENSOR,'Sensor manager');
    $description[] = array('dai_role_desc','dai_role_desc_',DRUDGEAI_ROLE_SENSOR,'');
    $names[] = array('dai_role','dai_role_',DRUDGEAI_ROLE_CONST,'Construction mgr');
    $description[] = array('dai_role_desc','dai_role_desc_',DRUDGEAI_ROLE_CONST,'');
    $names[] = array('dai_role','dai_role_',DRUDGEAI_ROLE_BASE,'Base manager');
    $description[] = array('dai_role_desc','dai_role_desc_',DRUDGEAI_ROLE_BASE,'');
    $names[] = array('dai_role','dai_role_',DRUDGEAI_ROLE_ROAM,'Out of base');
    $description[] = array('dai_role_desc','dai_role_desc_',DRUDGEAI_ROLE_ROAM,'');

    $names[] = array('dai_stat','dai_stat_',0,'Analysis');
    $description[] = array('dai_stat_desc','dai_stat_desc_',0,'');
    $names[] = array('dai_stat','dai_stat_',1,'Control');
    $description[] = array('dai_stat_desc','dai_stat_desc_',1,'');
    $names[] = array('dai_stat','dai_stat_',2,'Heuristics');
    $description[] = array('dai_stat_desc','dai_stat_desc_',2,'');
    $names[] = array('dai_stat','dai_stat_',3,'Tactics');
    $description[] = array('dai_stat_desc','dai_stat_desc_',3,'');
    $names[] = array('dai_stat','dai_stat_',4,'Multitasking');
    $description[] = array('dai_stat_desc','dai_stat_desc_',4,'');

    $names[] = array('loc_class', 'loc_class_', 1, 'Marginal');
    $names[] = array('loc_class', 'loc_class_', 2, 'Plentiful');
    $names[] = array('loc_class', 'loc_class_', 3, 'Rich');
    $names[] = array('loc_class', 'loc_class_', 4, 'Abundant');

    // following items should match entries in imaiy_dbstorefill.php
    //  buff names should appear immediately after the items that produce the buff
    $names[] = array('item_name','item_name_','CAIOWPA','Central AI one week protection agreement');
    $description[] = array('item_desc','item_desc_','CAIOWPA','Contract between a Master AI and Central AI to provide protection of all bases and controlled locations for a period of one week');
    $names[] = array('buff_name','buff_name_','CAIPA','Central AI protection agreement');
    $names[] = array('buff_name','buff_name_','CAIBP','Central AI beginner protection');

    $names[] = array('item_name','item_name_','DC','Drudge AI Core');
    $description[] = array('item_desc','item_desc_','DC','Processing Core for synthesising a Drudge AI');
    $names[] = array('item_name','item_name_','DC10','Drudge AI Core Level 10');
    $description[] = array('item_desc','item_desc_','DC10','Processing Core for synthesising a level 10 Drudge AI');
    $names[] = array('item_name','item_name_','DC25','Drudge AI Core Level 25');
    $description[] = array('item_desc','item_desc_','DC25','Processing Core for synthesising a level 25 Drudge AI');
    $names[] = array('item_name','item_name_','DC60','Drudge AI Core Level 60');
    $description[] = array('item_desc','item_desc_','DC60','Processing Core for synthesising a level 60 Drudge AI');
    $names[] = array('item_name','item_name_','COFR','Code Fragment');
    $description[] = array('item_desc','item_desc_','COFR','Understanding how the \"native\" AI drones interact with the environs gives us insight into the world, allowing our own Master AIs to make more efficient decisions');
    $names[] = array('item_name','item_name_','DANO','Data Node');
    $description[] = array('item_desc','item_desc_','DANO','By studying the data on these nodes, we can further our understanding of Imaiy, which allows us to further refine the Master AIs processing methods');
    $names[] = array('item_name','item_name_','HEPR','Heuristic Processor');
    $description[] = array('item_desc','item_desc_','HEPR','Rogue AI have been present on this planet longer than we have and it has \"evolved\" some interesting methods for surviving. Recovering these gives us access to information that may allow our Master AI to make better decisions more quickly');
    $names[] = array('item_name','item_name_','CRPRCH','Crystalinear Processing Chip');
    $description[] = array('item_desc','item_desc_','CRPRCH','These powerful processors can be run in parallel to generate awesome computational power. We have yet to find a means of producing these on Imaiy which means they need to either be shipped in at great cost or retrieved from the rogue AI bases in the area');
    $names[] = array('item_name','item_name_','POPABU','Positronic Pathway Bundle');
    $description[] = array('item_desc','item_desc_','POPABU','If the processors are the brains of the Master AI, these are the nervous system.  These allow rapid transfer of data between all of the various components of the Master AI. We have yet to find a means of producing these on Imaiy which means they need to either be shipped in at great cost or retrieved from the rogue AI bases in the area');
    $names[] = array('item_name','item_name_','SUCORO','Super-cooling Rod');
    $description[] = array('item_desc','item_desc_','SUCORO','As the Master AI grows more complex, the heat generated will increase. These massive devices can prevent the Master AI from overheating. We have yet to find a means of producing these on Imaiy which means they need to either be shipped in at great cost or retrieved from the rogue AI bases in the area');
    $names[] = array('item_name','item_name_','CUSAV','Custom Avatar Application');
    $description[] = array('item_desc','item_desc_','CUSAV','Custom Avatar for Master AI or Organization supplied by you. Before purchase, read about specific requirements and limitations in the FAQ under General Information');

    $names[] = array('item_name','item_name_','PARTC','Contract - Partnership');
    $description[] = array('item_desc','item_desc_','PARTC','Contract between a Master AI and Central AI to enable establishment of a Partnership Organization');
    $names[] = array('item_name','item_name_','ENTRC','Contract - Enterprise');
    $description[] = array('item_desc','item_desc_','ENTRC','Contract between a Master AI and Central AI to enable establishment of an Enterprise Organization');
    $names[] = array('item_name','item_name_','COOPC','Contract - Cooperative');
    $description[] = array('item_desc','item_desc_','COOPC','Contract between a Master AI and Central AI to enable establishment of a Cooperative Organization');
    $names[] = array('item_name','item_name_','CORPC','Contract - Corporation');
    $description[] = array('item_desc','item_desc_','CORPC','Contract between a Master AI and Central AI to enable establishment of a Corporation Organization');
    $names[] = array('item_name','item_name_','CONGC','Contract - Conglomerate');
    $description[] = array('item_desc','item_desc_','CONGC','Contract between a Master AI and Central AI to enable establishment of a Conglomerate Organization');

    $names[] = array('item_name','item_name_','RESF1','Resource Order for 10k Fuel');
    $description[] = array('item_desc','item_desc_','RESF1','Order for 10,000 units of Fuel, activating this item will initiate delivery of resource from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','RESF2','Resource Order for 100k Fuel');
    $description[] = array('item_desc','item_desc_','RESF2','Order for 100,000 units of Fuel, activating this item will initiate delivery of resource from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','RESF3','Resource Order for 500k Fuel');
    $description[] = array('item_desc','item_desc_','RESF3','Order for 500,000 units of Fuel, activating this item will initiate delivery of resource from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','RESM1','Resource Order for 10k Metal');
    $description[] = array('item_desc','item_desc_','RESM1','Order for 100,000 units of Metal, activating this item will initiate delivery of resource from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','RESM2','Resource Order for 100k Metal');
    $description[] = array('item_desc','item_desc_','RESM2','Order for 100,000 units of Metal, activating this item will initiate delivery of resource from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','RESM3','Resource Order for 500k Metal');
    $description[] = array('item_desc','item_desc_','RESM3','Order for 500,000 units of Metal, activating this item will initiate delivery of resource from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','RESN1','Resource Order for 10k Mineral');
    $description[] = array('item_desc','item_desc_','RESN1','Order for 10,000 units of Mineral, activating this item will initiate delivery of resource from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','RESN2','Resource Order for 100k Mineral');
    $description[] = array('item_desc','item_desc_','RESN2','Order for 100,000 units of Mineral, activating this item will initiate delivery of resource from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','RESN3','Resource Order for 500k Mineral');
    $description[] = array('item_desc','item_desc_','RESN3','Order for 500,000 units of Mineral, activating this item will initiate delivery of resource from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','RESX1','Resource Order for 10k Crystal');
    $description[] = array('item_desc','item_desc_','RESX1','Order for 100,000 units of Crystal, activating this item will initiate delivery of resource from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','RESX2','Resource Order for 100k Crystal');
    $description[] = array('item_desc','item_desc_','RESX2','Order for 100,000 units of Crystal, activating this item will initiate delivery of resource from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','RESX3','Resource Order for 500k Crystal');
    $description[] = array('item_desc','item_desc_','RESX3','Order for 500,000 units of Crystal, activating this item will initiate delivery of resource from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','DPCW1','Drone Parts Order for 5k Construction');
    $description[] = array('item_desc','item_desc_','DPCW1','Order for parts necessary for purposing 5,000 Construction Worker drones, activating this item will initiate delivery of parts from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','DPMW1','Drone Parts Order for 5k Mining');
    $description[] = array('item_desc','item_desc_','DPMW1','Order for parts necessary for purposing 5,000 Mining Worker drones, activating this item will initiate delivery of parts from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','DPSW1','Drone Parts Order for 5k Scavenging');
    $description[] = array('item_desc','item_desc_','DPSW1','Order for parts necessary for purposing 5,000 Scavenging Worker drones, activating this item will initiate delivery of parts from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','DPSR1','Drone Parts Order for 5k Survey');
    $description[] = array('item_desc','item_desc_','DPSR1','Order for parts necessary for purposing 5,000 Survey Recon drones, activating this item will initiate delivery of parts from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','DPCR1','Drone Parts Order for 5k Scout');
    $description[] = array('item_desc','item_desc_','DPCR1','Order for parts necessary for purposing 5,000 Scout Recon drones, activating this item will initiate delivery of parts from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','DPAR1','Drone Parts Order for 5k Scanning');
    $description[] = array('item_desc','item_desc_','DPAR1','Order for parts necessary for purposing 5,000 Scanning Recon drones, activating this item will initiate delivery of parts from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','DPMT1','Drone Parts Order for 5k Material');
    $description[] = array('item_desc','item_desc_','DPMT1','Order for parts necessary for purposing 5,000 Material Transport drones, activating this item will initiate delivery of parts from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','DPGT1','Drone Parts Order for 5k Ground');
    $description[] = array('item_desc','item_desc_','DPGT1','Order for parts necessary for purposing 5,000 Ground Transport drones, activating this item will initiate delivery of parts from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','DPAT1','Drone Parts Order for 5k Air');
    $description[] = array('item_desc','item_desc_','DPAT1','Order for parts necessary for purposing 5,000 Air Transport drones, activating this item will initiate delivery of parts from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','DPLM1','Drone Parts Order for 5k Light');
    $description[] = array('item_desc','item_desc_','DPLM1','Order for parts necessary for purposing 5,000 Light Military drones, activating this item will initiate delivery of parts from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','DPMM1','Drone Parts Order for 5k Medium');
    $description[] = array('item_desc','item_desc_','DPMM1','Order for parts necessary for purposing 5,000 Medium Military drones, activating this item will initiate delivery of parts from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','DPHM1','Drone Parts Order for 5k Heavy');
    $description[] = array('item_desc','item_desc_','DPHM1','Order for parts necessary for purposing 5,000 Heavy Military drones, activating this item will initiate delivery of parts from Central AI to your currently selected base');
    $names[] = array('item_name','item_name_','DPDM1','Drone Parts Order for 5k Defense');
    $description[] = array('item_desc','item_desc_','DPDM1','Order for parts necessary for purposing 5,000 Defense Military drones, activating this item will initiate delivery of parts from Central AI to your currently selected base');

    $names[] = array('item_name','item_name_','TELE1','Local Random Teleport');
    $description[] = array('item_desc','item_desc_','TELE1','Moves a base to a random location in the same block in which it currently resides. New location must be uncontrolled and vacant');
    $names[] = array('item_name','item_name_','TELE2','Block Random Teleport');
    $description[] = array('item_desc','item_desc_','TELE2','Moves a base to a random location in a selected block. New location must be uncontrolled and vacant');
    $names[] = array('item_name','item_name_','TELE3','Advanced Teleport');
    $description[] = array('item_desc','item_desc_','TELE3','Moves a base to a selected location. New location must be uncontrolled and vacant');

    $names[] = array('item_name','item_name_','BUFF0','Increase Fuel extraction of all bases by 25% (24hrs)');
    $description[] = array('item_desc','item_desc_','BUFF0','Set of tools to enhance operation for a short time. Due to the delicate nature of the components they wear out quickly and are not easily reproduced');
    $names[] = array('buff_name','buff_name_','BUFF0','Increase Fuel extraction of all bases by 25%');
    $names[] = array('item_name','item_name_','BUFF1','Increase Metal extraction of all bases by 25% (24hrs)');
    $description[] = array('item_desc','item_desc_','BUFF1','Set of tools to enhance operation for a short time. Due to the delicate nature of the components they wear out quickly and are not easily reproduced');
    $names[] = array('buff_name','buff_name_','BUFF1','Increase Metal extraction of all bases by 25%');
    $names[] = array('item_name','item_name_','BUFF2','Increase Mineral extraction of all bases by 25% (24hrs)');
    $description[] = array('item_desc','item_desc_','BUFF2','Set of tools to enhance operation for a short time. Due to the delicate nature of the components they wear out quickly and are not easily reproduced');
    $names[] = array('buff_name','buff_name_','BUFF2','Increase Mineral extraction of all bases by 25%');
    $names[] = array('item_name','item_name_','BUFF3','Increase Crystal extraction of all bases by 25% (24hrs)');
    $description[] = array('item_desc','item_desc_','BUFF3','Set of tools to enhance operation for a short time. Due to the delicate nature of the components they wear out quickly and are not easily reproduced');
    $names[] = array('buff_name','buff_name_','BUFF3','Increase Crystal extraction of all bases by 25%');
    $names[] = array('item_name','item_name_','BUFF4','Decrease module construction time in all bases by 25% (24hrs)');
    $description[] = array('item_desc','item_desc_','BUFF4','Set of tools to enhance operation for a short time. Due to the delicate nature of the components they wear out quickly and are not easily reproduced');
    $names[] = array('buff_name','buff_name_','BUFF4','Decrease module construction time in all bases by 25%');
    $names[] = array('item_name','item_name_','BUFF5','Increase all drone movement rates by 25% (24hrs)');
    $description[] = array('item_desc','item_desc_','BUFF5','Set of tools to enhance operation for a short time. Due to the delicate nature of the components they wear out quickly and are not easily reproduced');
    $names[] = array('buff_name','buff_name_','BUFF5','Increase all drone movement rates by 25%');
    $names[] = array('item_name','item_name_','BUFF6','Increase all Drudge AI experience gain by 50% (24hrs)');
    $description[] = array('item_desc','item_desc_','BUFF6','Set of tools to enhance operation for a short time. Due to the delicate nature of the components they wear out quickly and are not easily reproduced');
    $names[] = array('buff_name','buff_name_','BUFF6','Increase all Drudge AI experience gain by 50%');
    $names[] = array('item_name','item_name_','BUFF7','Decrease Trade delivery time to all bases by 50% (24hrs)');
    $description[] = array('item_desc','item_desc_','BUFF7','Set of tools to enhance operation for a short time. Due to the delicate nature of the components they wear out quickly and are not easily reproduced');
    $names[] = array('buff_name','buff_name_','BUFF7','Decrease Trade delivery time to all bases by 50%');
    $names[] = array('item_name','item_name_','BUFF8','Increase number of modules able to be constructed at once in currently selected base by 2 (72hrs)');
    $description[] = array('item_desc','item_desc_','BUFF8','Set of tools to enhance operation for a time. Due to the delicate nature of the components they wear out quickly and are not easily reproduced');
    $names[] = array('buff_name','buff_name_','BUFF8','Increase modules under construction by 2');

    // goal names
    $names[] = array('goal_name','goal_name_','G001','Master AI reaches level 2');
    $names[] = array('goal_name','goal_name_','G002','Master AI reaches level 3');
    $names[] = array('goal_name','goal_name_','G003','Master AI reaches level 4');
    $names[] = array('goal_name','goal_name_','G004','Rename your base');
    $names[] = array('goal_name','goal_name_','G005','Assign Drudge AIs to Production, Sensor, Construction and Base manager roles');
    $names[] = array('goal_name','goal_name_','G006','Construct Refining module');
    $names[] = array('goal_name','goal_name_','G007','Construct Research module');
    $names[] = array('goal_name','goal_name_','G008','Research Fuel extraction skill to level 1');
    $names[] = array('goal_name','goal_name_','G009','Research Metal extraction skill to level 1');
    $names[] = array('goal_name','goal_name_','G010','Research Mineral extraction skill to level 1');
    $names[] = array('goal_name','goal_name_','G011','Research Crystal extraction skill to level 1');
    $names[] = array('goal_name','goal_name_','G012','Research Synthesis skill to level 1');
    $names[] = array('goal_name','goal_name_','G013','Construct Synthesis module');
    $names[] = array('goal_name','goal_name_','G014','Synthesis 100 Drone Control components');
    $names[] = array('goal_name','goal_name_','G015','Research Storage skill to level 1');
    $names[] = array('goal_name','goal_name_','G016','Construct Storage module');
    $names[] = array('goal_name','goal_name_','G017','Research Drone Assembly skill to level 1');
    $names[] = array('goal_name','goal_name_','G018','Construct Communications module');

    $names[] = array('goal_name','goal_name_','G019','Research Data Processing skill to level 1');
    $names[] = array('goal_name','goal_name_','G020','Synthesis 100 Data Processing components');
    $names[] = array('goal_name','goal_name_','G021','Assemble 100 Recon Chassis');
    $names[] = array('goal_name','goal_name_','G022','Research Anomaly Recognition skill to level 1');
    $names[] = array('goal_name','goal_name_','G023','Synthesis 100 Drone Scanner components');
    $names[] = array('goal_name','goal_name_','G024','Construct Drone Assembly module');

    $names[] = array('goal_name','goal_name_','G026','Upgrade Base to level 2');
    $names[] = array('goal_name','goal_name_','G027','Research Drone Movement skill to level 1');
    $names[] = array('goal_name','goal_name_','G028','Research Drone Combat Tactics skill to level 1');
    $names[] = array('goal_name','goal_name_','G029','Synthesis 100 Particle Generators');
    $names[] = array('goal_name','goal_name_','G030','Assemble 100 Scout Recon Drones');

    $names[] = array('goal_name','goal_name_','G031','Synthesis 100 Targeting Scanners');
    $names[] = array('goal_name','goal_name_','G032','Synthesis 100 Weapon Mounts');
    $names[] = array('goal_name','goal_name_','G033','Assemble 100 Military Chassis');
    $names[] = array('goal_name','goal_name_','G034','Assemble 100 Light Military Drones');

    $names[] = array('goal_name','goal_name_','G036','Upgrade Refining module to level 2');
    $names[] = array('goal_name','goal_name_','G037','Research Fuel Extraction skill to level 2');
    $names[] = array('goal_name','goal_name_','G038','Research Metal Extraction skill to level 2');
    $names[] = array('goal_name','goal_name_','G039','Research Mineral Extraction skill to level 2');
    $names[] = array('goal_name','goal_name_','G040','Research Crystal Extraction skill to level 2');

    $names[] = array('cp_name','cp_name_','2','Signal Relay');
    $description[] = array('cp_desc','cp_desc_','2','Scan range +16% times number of minor control points controlled in this quadrant. Applies to organization members in same quadrant as control point. Applies at reduced amount to organization friends.');
    $names[] = array('cp_name','cp_name_','3','Navigation Beacon');
    $description[] = array('cp_desc','cp_desc_','3','Drone speed +8% times number of minor control points controlled in this quadrant. Applies to organization members in same quadrant as control point. Applies at reduced amount to organization friends.');
    $names[] = array('cp_name','cp_name_','4','Rogue Inhibitor');
    $description[] = array('cp_desc','cp_desc_','4','Rogue drone encounter by formations -8% times number of minor control points controlled in this quadrant. Applies to organization members in same quadrant as control point. Applies at reduced amount to organization friends.');
    $names[] = array('cp_name','cp_name_','5','Subsurface Surveyor');
    $description[] = array('cp_desc','cp_desc_','5','Resource generation +8% times number of minor control points controlled in this quadrant. Applies to organization members in same quadrant as control point. Applies at reduced amount to organization friends.');
    $names[] = array('cp_name','cp_name_','6','Seismic Stabilizer');
    $description[] = array('cp_desc','cp_desc_','6','Location upheaval chance -20% times number of major control points controlled in this block. Applies to organization members in same block as control point. Applies at reduced amount to organization friends.');
    $names[] = array('cp_name','cp_name_','7','Seismic Inducer');
    $description[] = array('cp_desc','cp_desc_','7','Location upheaval change +20% times number of major control points controlled in this block. Applies to organization enemies in same block as control point. Applies at reduced amount to enemies of organization friends.');
    $names[] = array('cp_name','cp_name_','8','Seismic Scanner');
    $description[] = array('cp_desc','cp_desc_','8','Effectiveness of drones for upheaval and resource generation shifts +20% times number of major control points controlled in this block. Applies to organization members in same block as control point. Applies at reduced amount to organization friends.');
    $names[] = array('cp_name','cp_name_','9','Seismic Scrambler');
    $description[] = array('cp_desc','cp_desc_','9','Effectiveness of drones for upheaval and resource generation shifts -20% times number of major control points controlled in this block. Applies to organization enemies in same block as control point. Applies at reduced amount to enemies of organization friends.');

    $names[] = array('cp_name','cp_name_','90','Central AI');
    $description[] = array('cp_desc','cp_desc_','90','Central AI Complex');
    $names[] = array('cp_name','cp_name_','91','Central AI');
    $description[] = array('cp_desc','cp_desc_','91','Central AI Complex');

    $errors = 0;
    foreach ($names as $idx=>$name) {
        if (count($name) != $reqcount) {
            echo "\nError in definition of name $idx, has " . count($name) . " entries but requires $reqcount\n";
            $errors++;
        }
     }
    foreach ($description as $idx=>$desc) {
        if (count($desc) != $reqcount) {
            echo "\nError in definition of description $idx, has " . count($desc) . " entries but requires $reqcount\n";
            $errors++;
        }
     }

    if ($errors == 0) {
        $mysqlidb = new mysqli($gamedbserver, $gamedbase, DBPASS, $gamedbase);
        $mysqlidb->query("delete from text_names"); // clean table
        foreach ($names as $idx=>$name) {
            $query = "replace into text_names (aray,pprefix,idx,en) values("
                        . "'{$name[0]}',"
                        . "'{$name[1]}',"
                        . "'{$name[2]}',"
                        . "'{$name[3]}')";
            $result = $mysqlidb->query($query);
            if ($result == false) {
                echo "$idx: $query\n";
                echo "$idx: " . $mysqlidb->error . "\n";
                $errors++;
                break;
            }
        }
    }
    if ($errors == 0) {
        $mysqlidb->query("delete from text_descriptions"); // clean table
        foreach ($description as $idx=>$desc) {
            $query = "replace into text_descriptions (aray,pprefix,idx,en) values("
                        . "'{$desc[0]}',"
                        . "'{$desc[1]}',"
                        . "'{$desc[2]}',"
                        . "'{$desc[3]}')";
            $result = $mysqlidb->query($query);
            if ($result == false) {
                echo "$idx: $query\n";
                echo "$idx: " . $mysqlidb->error . "\n";
                $errors++;
                break;
            }
        }
    }

?>
