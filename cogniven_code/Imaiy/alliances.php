<?php
/*
 * Classes and functions to deal with alliances
 * Author: Bill Whitmore
 *
 * Requirements
 *      This class requires a public function called valid_name($name) that
 *  will return 1 if the name passed in is valid.
 *
 * All organizations have exactly 1 owner.
 *
 * Organizations come in 5 levels:
 *      Partnership:      8 members,  2 officers (including owner)
 *      Enterprise:      16 members,  4 officers (including owner)
 *      Cooperative:     32 members,  8 officers (including owner)
 *      Corporation:     64 members,  16 officers (including owner)
 *      Conglamerate:   128 members, 32 officers (including owner)
 *
 * Officer Roles:
 *      Owner: Has all roles.  Only one who can disband organization.
 *      Director: Has permission to assign and remove roles.
 *      Diplomats: May adjust the relationship statuses of the alliance
 *      Recruiters: May invite new members to join the alliance
 *      Wardens: May remove members from the alliance
 *
 * Return values for all Alliance methods as per values in alliance_code_text
 */

    include_once "globals.php";

    class Alliance
    {
        private $alliance_name;
        private $requireditem = array("", "PARTC", "ENTRC", "COOPC", "CORPC", "CONGC");
        private $owner;
        private $created;
        private $type;
        private $link;
        private $avatar;
        private $pubnote;
        private $prinote;
        private $power;
        private $renown;
        private $cpoints;

        /*
         * This constructor takes one argument, which is the db, if
         *  not specfied then will open and connect to db itself
         *    also inits variables;
         */
        public function __construct()
        {
            $this->clear();
            $this->alliance_name = "";
            $this->owner = "";
            $this->created = "";
            $this->type = ALLIANCE_NONE;
            $this->link = "";
            $this->avatar = "";
            $this->pubnote = "";
            $this->prinote = "";
            $this->power = 0;
            $this->renown = 0;
            $this->cpoints = "";
        }

        /*
         * This method resets the object.
         */
        public function clear()
        {
            $this->alliance_name = "";
        }

        /*
         * open($name)
         *      Sets the class to work on a particular alliance by name
         *
         *      requirements
         *          none
         */
        public function open($name)
        {
            global $mysqlidb;
            $this->alliance_name = trim($name);

            $query = sprintf("select * from alliance where name='%s'",
                $mysqlidb->real_escape_string($this->alliance_name));
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0))
            {
                $row = $result->fetch_assoc();
                $this->owner = $row["owner"];
                $this->created = $row["created"];
                $this->type = $row["type"];
                $this->link = $row["link"];
                $this->avatar = $row["avatar"];
                $this->pubnote = $row["public"];
                $this->prinote = $row["private"];
                $this->power = $row["power"];
                $this->renown = $row["renown"];
                $this->cpoints = $row["cpoints"];
            }

            return $this->exists();
        }

        /*
         * exists()
         *      checks if the alliance exists
         *      don't allow beginner, cogniven nor imaiy alliance names to be used
         */
        public function exists()
        {
            if ((strtolower($this->alliance_name) == "beginner")
                    || (strtolower($this->alliance_name) == "cogniven")
                    || (strtolower($this->alliance_name) == "imaiy")) {
                return true;
            }
            if ($this->type != ALLIANCE_NONE) {
                //alliance exists
                return true;
            } else {
                //alliance does not exist
                return false;
            }
        }

        /*
         * is_owner($ai)
         *      checks if the ai is the owner of this alliance.
         *
         *
         */
        public function is_owner($ai)
        {
            if (strtolower($this->alliance_name) == "beginner") {
                return false;
            }
            if ($this->owner == $ai) {
                return true;
            }

            return false;
        }

        /*
         * is_member($ai)
         *      checks if the ai is a member of this alliance.
         *
         *
         */
        public function is_member($ai)
        {
            global $mysqlidb;
            $query = sprintf("select name from player where name='%s' and alliance='%s'",
                $mysqlidb->real_escape_string($ai),
                $mysqlidb->real_escape_string($this->alliance_name));
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0))
            {
                return true;
            }

            return false;
        }

        /*
         * member_list()
         *      returns the list of members in the alliance
         */
        public function member_list()
        {
            global $mysqlidb;
            $list = "";
            $query = sprintf("select name from player where alliance='%s'",
                $mysqlidb->real_escape_string($this->alliance_name));
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0))
            {
                while ($row = $result->fetch_row()) {
                    $list .= $row[0] . ";";
                }
            }
            return $list;
        }

        /*
         * updatepower()
         *  sums power of all members
         */
        public function updatepower() {
            global $mysqlidb;
            $power = 0;
            $query = sprintf("select power from player where alliance='%s'",
                $mysqlidb->real_escape_string($this->alliance_name));
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0))
            {
                while ($row = $result->fetch_row()) {
                    $power += $row[0];
                }
            }
            $query = sprintf("update alliance set power=$power where name='%s'",
                $mysqlidb->real_escape_string($this->alliance_name));
            $mysqlidb->query($query);
        }

        /*
         * member_count()
         *      returns the number of members in the alliance
         */
        public function member_count()
        {
            global $mysqlidb;
            $query = sprintf("select count(*) from player where alliance='%s'",
                $mysqlidb->real_escape_string($this->alliance_name));
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0))
            {
                $row = $result->fetch_row();
                return $row[0];
            }
            else
            {
                return 0;
            }
        }

        /*
         * member_max()
         *      returns the max number of members allowed in the alliance
         */
        public function member_max() {
            $max_members = 0;

            switch ($this->type)
            {
                case ALLIANCE_PARTNERSHIP:
                    $max_members = ALLIANCE_PARTNERSHIP_MAX_MEMBERSHIP;
                    break;
                case ALLIANCE_ENTERPRISE:
                    $max_members = ALLIANCE_ENTERPRISE_MAX_MEMBERSHIP;
                    break;
                case ALLIANCE_COOPERATIVE:
                    $max_members = ALLIANCE_COOPERATIVE_MAX_MEMBERSHIP;
                    break;
                case ALLIANCE_CORPORATION:
                    $max_members = ALLIANCE_CORPORATION_MAX_MEMBERSHIP;
                    break;
                case ALLIANCE_CONGLOMERATE:
                    $max_members = ALLIANCE_CONGLOMERATE_MAX_MEMBERSHIP;
                    break;
            }
            return $max_members;
        }

        /*
         * officer_count()
         *      returns the number of officers in the alliance
         */
        public function officer_count()
        {
            global $mysqlidb;
            $query = sprintf("select count(*) from alliance_officers where alliance='%s'",
                $mysqlidb->real_escape_string($this->alliance_name));
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0))
            {
                $row = $result->fetch_row();
                return $row[0];
            }
            else
            {
                return 0;
            }
        }

        /*
         * create($ai)
         *      Will create a new alliance at the specified level.
         *
         *      requirements
         *          1) ai is not in an alliance
         *          2) alliance name not already in use
         *          3) ai does not own an alliance
         *          4) ai must be a minimum level
         *          5) must be a legal name
         *          6) must have appropriate item
         *
         *
         */
        public function create($ai, $type)
        {
            global $mysqlidb;
            // is ai in an alliance
            if ($_SESSION["alliance"] != "") {
                return 28;
            }
            // does alliance already exist?
            //  reserved names will return true as well
            if ($this->exists())
            {
                //create failed - alliance already exists
                return 2;
            }

            //does ai already own an alliance
            $query = sprintf("select owner from alliance where owner='%s'",
                $mysqlidb->real_escape_string($ai));
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0))
            {
                //ai owns another alliance
                return 3;
            }

            //does ai have sufficient level
            if ($_SESSION["level"] < MINIMUM_LEVEL_TO_CREATE_ALLIANCE) {
                //ai is insufficient level
                return 4;
            }

            //is the name legal
            $return_code = valid_name($this->alliance_name);
            if ($return_code != 1)
            {
                // illegal alliance name
                return 5;
            }

            // is name used by player?
            $query = "select name from player where name='{$this->alliance_name}'";
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0)) {
                return 34;
            }

            // check for appropriate item and remove from player record
            if (($type < ALLIANCE_PARTNERSHIP) || ($type > ALLIANCE_CONGLOMERATE)) {
                return 29;
            }
            $query = "select items from player where name='$ai'";
            $result = $mysqlidb->query($query);
            if (!$result || ($result->num_rows == 0)) {
                return 30;
            } else {
                $row = $result->fetch_row();
                $items = explode(";", $row[0]);
                $newitems = "";
                $found = false;
                for ($idx = 0; $idx < count($items); $idx++) {
                    $item = explode(":", $items[$idx]);
                    if ($item[0] == $this->requireditem[$type]) {
                        $found = true;
                        if ($item[2] > 1) {
                            $item[2]--;
                            $items[$idx] = implode(":", $item);
                        } else {
                            $items[$idx] = "";
                        }
                    }
                    if ($items[$idx] != "") {
                        if ($newitems != "") {
                            $newitems .= ";";
                        }
                        $newitems .= $items[$idx];
                    }
                }
                if ($found == false) {
                    return 30;
                }
                $query = "update player set items='$newitems' where name='$ai'";
                $mysqlidb->query($query);
            }

            //create the alliance
            $query = sprintf("insert into alliance (name, owner, created, type, link, public, private) values ('%s', '%s', curdate(), $type, '', '', '')",
                $mysqlidb->real_escape_string($this->alliance_name),
                $mysqlidb->real_escape_string($ai));
            $mysqlidb->query($query);

            //set owner as officer with full permissions
            $query = sprintf("insert into alliance_officers (alliance, name, promoted, director, diplomat, recruiter, warden) values ('%s', '%s', curdate(), true, true, true, true)",
                $mysqlidb->real_escape_string($this->alliance_name),
                $mysqlidb->real_escape_string($ai));
            $mysqlidb->query($query);

            //set owner as member of alliance
            $query = sprintf("update player set alliance='%s' where name='%s'",
                $mysqlidb->real_escape_string($this->alliance_name),
                $mysqlidb->real_escape_string($ai));
            $mysqlidb->query($query);

            //delete any pending applications the owner may have
            $query = sprintf("delete from alliance_applications where applicant='%s'",
                $mysqlidb->real_escape_string($ai));
            $mysqlidb->query($query);

            return 1;
        }

        /*
         * upgrade($ai, $target_type)
         *      Will upgrade the alliance one step
         *
         *      requirements
         *          1) alliance must exist
         *          2) ai must be owner
         *          3) alliance must only be 1 step below target type
         */
        public function upgrade($ai, $target_type)
        {
            global $mysqlidb;
            //does alliance exist
            if(!$this->exists())
            {
                return 0;
            }

            if (!$this->is_owner($ai))
            {
                //only owner can upgrade alliance
                return 23;
            }

            if ($target_type <= $this->type) {
                return 25;
            } else if ($this->type == ALLIANCE_CONGLOMERATE) {
                return 26;
            }

            $query = "select items from player where name='$ai'";
            $result = $mysqlidb->query($query);
            if (!$result || ($result->num_rows == 0)) {
                return 30;
            } else {
                $row = $result->fetch_row();
                $items = explode(";", $row[0]);
                $newitems = "";
                $found = false;
                for ($idx = 0; $idx < count($items); $idx++) {
                    $item = explode(":", $items[$idx]);
                    if ($item[0] == $this->requireditem[$target_type]) {
                        $found = true;
                        if ($item[2] > 1) {
                            $item[2]--;
                            $items[$idx] = implode(":", $item);
                        } else {
                            $items[$idx] = "";
                        }
                    }
                    if ($items[$idx] != "") {
                        if ($newitems != "") {
                            $newitems .= ";";
                        }
                        $newitems .= $items[$idx];
                    }
                }
                if ($found == false) {
                    return 30;
                }
                $query = "update player set items='$newitems' where name='$ai'";
                $mysqlidb->query($query);
            }
            //update the alliance type
            $query = sprintf("update alliance set type=%s where name='%s'",
                $mysqlidb->real_escape_string($target_type),
                $mysqlidb->real_escape_string($this->alliance_name));
            $mysqlidb->query($query);

            return 1;
        }

        /*
         * disband($ai)
         *      Will remove all members from the alliance and remove the
         * alliance from the game.
         *
         *      requirements
         *          1) alliance must exist
         *          2) ai must be owner
         */
        public function disband($ai)
        {
            global $mysqlidb;
            //does alliance exist
            if(!$this->exists())
            {
                return 0;
            }

            if (!$this->is_owner($ai))
            {
                //ai is not owner
                return 6;
            }

            //remove all officers from the alliance
            $query = sprintf("delete from alliance_officers where alliance='%s'",
                $mysqlidb->real_escape_string($this->alliance_name));
            $mysqlidb->query($query);

            //remove all members from the alliance
            $query = sprintf("update player set alliance='' where alliance='%s'",
                $mysqlidb->real_escape_string($this->alliance_name));
            $mysqlidb->query($query);

            //delete any pending applications for this alliance
            $query = sprintf("delete from alliance_applications where alliance='%s'",
                $mysqlidb->real_escape_string($this->alliance_name));
            $mysqlidb->query($query);

            // abandon control points
            $cparr = explode(";", $this->cpoints);
            foreach ($cparr as $cp) {
                $cparts = explode(":", $cp);
                if (count($cparts) > 2) {
                    abandonlocation($ai, $cparts[2], $this);
                }
            }

            //remove the alliance
            $query = sprintf("delete from alliance where name='%s'",
                $mysqlidb->real_escape_string($this->alliance_name));
            $mysqlidb->query($query);

            return 1;
        }

        /*
         * submit_application($ai, $resume)
         *      submits a request to join the alliance for the listed ai.
         * The resume is a block of text limited to 1000 characters.
         *
         *      requirements
         *          alliance must exist
         *          ai must not own an alliance
         *          ai must not belong to the alliance
         *          ai must not already have an application with this alliance
         *          ai must not exceed maximum application
         */
        public function submit_application($ai, $resume)
        {
            global $mysqlidb;
            //does alliance exist
            if(!$this->exists())
            {
                return 0;
            }
            if ($_SESSION["alliance"] != "") {
                return 28;
            }

            $query = sprintf("select applicant from alliance_applications where applicant='%s' and alliance='%s'",
                $mysqlidb->real_escape_string($ai),
                $mysqlidb->real_escape_string($this->alliance_name));
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0))
            {
                //already have an application with this alliance
                return 8;
            }

            $query = sprintf("select applicant from alliance_applications where applicant='%s'",
                $mysqlidb->real_escape_string($ai));
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows >= MAXIMUM_ALLIANCE_APPLICATIONS))
            {
                //reached limit of application amount
                return 9;
            }
             // limit to 1000 chars and remove semicolons and pipes
            $resume = str_replace("|", "", str_replace(";", "", substr($resume, 0, 1000)));
            //insert the application into the database.
            $query = sprintf("insert into alliance_applications (applicant, alliance, submitted, resume) values ('%s', '%s', curdate(), '%s')",
                $mysqlidb->real_escape_string($ai),
                $mysqlidb->real_escape_string($this->alliance_name),
                $mysqlidb->real_escape_string($resume));
            $mysqlidb->query($query);

            return 1;
        }


        /*
         * submit_application($ai, $resume)
         *      submits a request to join the alliance for the listed ai.
         * The resume is a block of text limited to 1000 characters.
         *
         *      requirements
         *          alliance must exist
         *          ai must have an application here
         */
        public function withdraw_application($ai)
        {
            global $mysqlidb;
            //does alliance exist
            if(!$this->exists())
            {
                return 0;
            }

            $query = sprintf("delete from alliance_applications where applicant='%s' and alliance='%s'",
                $mysqlidb->real_escape_string($ai),
                $mysqlidb->real_escape_string($this->alliance_name));
            $mysqlidb->query($query);
            postreport($ai, 0, "Application to organization " . $this->alliance_name . " has been withdrawn");

            return 1;
        }


        /*
         * is_officer($ai, $role)
         *      will check if the ai is an officer in the alliance with the
         * requested role.  If role is left blank, it will check if the ai is
         * an officer in the alliance.
         *
         *      requirements
         *          alliance must exist
         *
         *      return values
         *          1 = false
         *          0 = alliance does not exist or false
         */
        public function is_officer($ai, $role = "")
        {
            global $mysqlidb;
            if ($this->alliance_name == "Beginner") {
                return false;
            }
            //does alliance exist
            if(!$this->exists())
            {
                return 0;
            }

            switch ($role)
            {
                case "":
                    $role_string = "";
                    break;
                case DIPLOMAT:
                    $role_string = "and diplomat=true";
                    break;
                case DIRECTOR:
                    $role_string = "and director=true";
                    break;
                case RECRUITER:
                    $role_string = "and recruiter=true";
                    break;
                case WARDEN:
                    $role_string = "and warden=true";
                    break;
                default:
                    break;
            }

            //get officer information for the alliance
            $query = sprintf("select name from alliance_officers where name='%s' and alliance='%s' %s",
                $mysqlidb->real_escape_string($ai),
                $mysqlidb->real_escape_string($this->alliance_name),
                $mysqlidb->real_escape_string($role_string));
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0))
            {
                return 1;
            }
            else
            {
                return 0;
            }
        }


        /*
         * accept_application($acting_ai, $target_ai)
         *      will attempt to add the target ai to the alliance.
         *
         *      requirements
         *          1) the alliance must exist
         *          2) the alliance must have room
         *          3) the ai cannot be an owner of another alliance
         *          4) the acting member must have permission to take this action
         *          5) the target ai must have an application to join the alliance
         */
        public function accept_application($acting_ai, $target_ai)
        {
            global $mysqlidb;
            if (!$this->exists())
            {
                // alliance does not exist
                return 0;
            }

            if (!$this->is_officer($acting_ai, RECRUITER))
            {
                // ai lacks permission to do this action
                return 6;
            }

            $query = sprintf("select alliance from player where name='%s'",
                $mysqlidb->real_escape_string($target_ai));
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0)) {
                $row = $result->fetch_row();
                if ($row[0] != "") {
                    // target ai in another alliance
                    return 31;
                }
            }

            $query = sprintf("select applicant from alliance_applications where applicant='%s' and alliance='%s'",
                $mysqlidb->real_escape_string($target_ai),
                $mysqlidb->real_escape_string($this->alliance_name));
            $result = $mysqlidb->query($query);
            if (!$result || ($result->num_rows == 0))
            {
                //target ai has no application to this alliance
                return 11;
            }

            $max_members = $this->member_max();
            if ($max_members == 0) {
                //error getting max members
                return 12;
            }

            if ($this->member_count() >= $max_members) {
                    //alliance is full
                    return 13;
            }

            //add member to alliance
            $query = sprintf("update player set alliance='%s' where name='%s'",
                $mysqlidb->real_escape_string($this->alliance_name),
                $mysqlidb->real_escape_string($target_ai));
            $mysqlidb->query($query);

            //remove any applications the new member may have pending
            $query = sprintf("delete from alliance_applications where applicant='%s'",
                $mysqlidb->real_escape_string($target_ai));
            $mysqlidb->query($query);

            $this->updatepower();

            return 1;
        }

        /*
         * reject_application($acting_ai, $target_ai)
         *      will attempt to reject the target ai's application
         *
         *      requirements
         *          1) the alliance must exist
         *          2) the ai cannot be an owner of another alliance
         *          3) the acting member must have permission to take this action
         *          4) the target ai must have an application to join the alliance
         */
        public function reject_application($acting_ai, $target_ai)
        {
            global $mysqlidb;
            if (!$this->exists())
            {
                // alliance does not exist
                return 0;
            }

            if (!$this->is_officer($acting_ai, RECRUITER))
            {
                // ai lacks permission to do this action
                return 6;
            }

            $query = sprintf("select applicant from alliance_applications where applicant='%s' and alliance='%s'",
                $mysqlidb->real_escape_string($target_ai),
                $mysqlidb->real_escape_string($this->alliance_name));
            $result = $mysqlidb->query($query);
            if (!$result || ($result->num_rows == 0))
            {
                //target ai has no application to this alliance
                return 11;
            }

            //remove any applications the new member may have pending
            $query = sprintf("delete from alliance_applications where applicant='%s' and alliance='%s'",
                $mysqlidb->real_escape_string($target_ai),
                $mysqlidb->real_escape_string($this->alliance_name));
            $mysqlidb->query($query);

            return 1;
        }


        /*
         * application_list
         *  retrieves list of applications
         */
        function application_list() {
            global $mysqlidb;
            $list = "";
            $query = sprintf("select applicant,submitted,resume from alliance_applications where alliance='%s' order by submitted desc",
                $mysqlidb->real_escape_string($this->alliance_name));
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0)) {
                while ($row = $result->fetch_row()) {
                    $list .= $row[0] . ";" . $row[1] . ";" . str_replace("\n", "<br/>", $row[2]) . ";";
                }
            }
            return $list;
        }


        /*
         * kick_member($acting_ai, $target_ai)
         *    Will remove the target ai from the alliance if the acting ai
         * has the authority to kick a member from the alliance
         *
         *      requirements
         *          1) the alliance must exist
         *          2) the target ai must be a member of this alliance
         *          3) the target ai cannot be an officer of the alliance
         *          4) the acting member must have permission to take this action
         */
        public function kick_member($acting_ai, $target_ai)
        {
            global $mysqlidb;
            if (!$this->exists())
            {
                // alliance does not exist
                return 0;
            }

            if (!$this->is_officer($acting_ai, WARDEN))
            {
                // ai lacks permission to perform this action
                return 6;
            }

            if (!$this->is_member($target_ai))
            {
                //target ai is not a member of this alliance
                return 14;
            }

            if ($this->is_officer($target_ai))
            {
                //can't kick officer from alliance
                return 15;
            }

            //remove player from alliance
            $query = sprintf("update player set alliance='' where name='%s'",
                $mysqlidb->real_escape_string($target_ai));
            $mysqlidb->query($query);
            $this->updatepower();

            return 1;
        }


        /*
         * leave_alliance($ai)
         *    Will remove the ai from the alliance if the ai is in the alliance
         *
         *      requirements
         *          1) the alliance must exist
         *          2) the ai must be a member of this alliance
         *          3) the ai cannot be the owner of the alliance
         */
        public function leave_alliance($ai)
        {
            global $mysqlidb;
            if (!$this->exists())
            {
                // alliance does not exist
                return 0;
            }

            if ($this->is_owner($ai))
            {
                //owner can't leave alliance
                return 16;
            }

            if (!$this->is_member($ai))
            {
                //target ai is not a member of this alliance
                return 14;
            }

            //remove player from officer list
            $query = sprintf("delete from alliance_officers where name='%s'",
                $mysqlidb->real_escape_string($ai));
            $mysqlidb->query($query);

            //remove player from alliance
            $query = sprintf("update player set alliance='' where name='%s'",
                $mysqlidb->real_escape_string($ai));
            $mysqlidb->query($query);

            $this->updatepower();

            return 1;
        }


        /*
         * change_owner($acting_ai, $target_ai)
         *      The acting ai will transfer ownership to target_ai
         *
         *      requirements
         *          1) the alliance must exist
         *          2) the target ai must be a director of this alliance
         *          3) target ai must be >= level MINIMUM_LEVEL_TO_CREATE_ALLIANCE
         *          4) the acting member must be owner
         */
        public function change_owner($acting_ai, $target_ai)
        {
            global $mysqlidb;
            if (!$this->exists())
            {
                // alliance does not exist
                return 0;
            }

            if (!$this->is_owner($acting_ai))
            {
                // acting ai lacks permission for this action
                return 6;
            }

            $level = 0;
            $query = "select level from player where name='$target_ai'";
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0)) {
                $row = $result->fetch_row();
                $level = $row[0];
            }
            if (!$this->is_officer($target_ai, DIRECTOR) || ($level < MINIMUM_LEVEL_TO_CREATE_ALLIANCE))
            {
                //target ai is not a director or under level 5
                return 33;
            }
            $query = sprintf("update alliance set owner='%s' where name='%s'",
                    $mysqlidb->real_escape_string($target_ai),
                    $mysqlidb->real_escape_string($this->alliance_name));
            $mysqlidb->query($query);

            $newrole = "director=true,diplomat=true,recruiter=true,warden=true";
            $query = sprintf("update alliance_officers set $newrole where alliance='%s' and name='%s'",
                    $mysqlidb->real_escape_string($this->alliance_name),
                    $mysqlidb->real_escape_string($target_ai));
            $mysqlidb->query($query);

            return 1;
        }


        /*
         * make_officer($acting_ai, $target_ai)
         *      The acting ai will add the $target_ai to the officer list for
         * the alliance.  No roles will be initially set for this officer.
         *
         *      requirements
         *          1) the alliance must exist
         *          2) the target ai must be a member of this alliance
         *          3) the acting member must have permission to take this action
         *          4) there must be room for additional officers in the alliance if not already an officer
         */
        public function make_officer($acting_ai, $target_ai, $role)
        {
            global $mysqlidb;
            if (!$this->exists())
            {
                // alliance does not exist
                return 0;
            }

            if (!$this->is_officer($acting_ai, DIRECTOR))
            {
                // acting ai lacks permission for this action
                return 6;
            }

            if (!$this->is_member($target_ai))
            {
                //target ai is not a member of this alliance
                return 14;
            }

            $newrole = "";
            $roles = "false, false, false, false";
            switch (strtolower($role)) {
                case "director":
                    $newrole = "director=true";
                    $roles = "true, false, false, false";
                    break;
                case "diplomat":
                    $newrole = "diplomat=true";
                    $roles = "false, true, false, false";
                    break;
                case "recruiter":
                    $newrole = "recruiter=true";
                    $roles = "false, false, true, false";
                    break;
                case "warden":
                    $newrole = "warden=true";
                    $roles = "false, false, false, true";
                    break;
            }
            if ($newrole == "") {
                return 32;
            }

            if (!$this->is_officer($target_ai)) {
                switch ($this->type)
                {
                    case ALLIANCE_PARTNERSHIP:
                        $max_officers = ALLIANCE_PARTNERSHIP_MAX_OFFICERS;
                        break;
                    case ALLIANCE_ENTERPRISE:
                        $max_officers = ALLIANCE_ENTERPRISE_MAX_OFFICERS;
                        break;
                    case ALLIANCE_COOPERATIVE:
                        $max_officers = ALLIANCE_COOPERATIVE_MAX_OFFICERS;
                        break;
                    case ALLIANCE_CORPORATION:
                        $max_officers = ALLIANCE_CORPORATION_MAX_OFFICERS;
                        break;
                    case ALLIANCE_CONGLOMERATE:
                        $max_officers = ALLIANCE_CONGLOMERATE_MAX_OFFICERS;
                        break;
                    default:
                        //error setting max officers
                        return 19;
                }

                if ($this->officer_count() >= $max_officers) {
                    //no more officer slots
                    return 18;
                }

                //add to officer list
                $query = sprintf("insert into alliance_officers (alliance, name, promoted, director, diplomat, recruiter, warden) values ('%s', '%s', curdate(), $roles)",
                    $mysqlidb->real_escape_string($this->alliance_name),
                    $mysqlidb->real_escape_string($target_ai));
                $mysqlidb->query($query);
            } else {
                $query = sprintf("update alliance_officers set $newrole where alliance='%s' and name='%s'",
                    $mysqlidb->real_escape_string($this->alliance_name),
                    $mysqlidb->real_escape_string($target_ai));
                $mysqlidb->query($query);
            }

            return 1;
        }

        /*
         * remove_officer($acting_ai, $target_ai)
         *      The acting ai will remove the target ai.
         *
         *      requirements:
         *          1) the alliance must exist
         *          2) the target ai must be an officer in the alliance
         *          3) the acting member must have permission to take this action
         *          4) target ai cannot be the owner of the alliance
         */
        public function remove_officer($acting_ai, $target_ai, $role)
        {
            global $mysqlidb;
            if (!$this->exists())
            {
                // alliance does not exist
                return 0;
            }
            if ($acting_ai != $target_ai) {
                if (!$this->is_officer($acting_ai, DIRECTOR))
                {
                    // acting ai lacks permission for this action
                    return 6;
                }
            }

            if (!$this->is_officer($target_ai))
            {
                //target ai is not an officer
                return 20;
            }

            if ($this->is_owner($target_ai))
            {
                //can't change owner roles
                return 21;
            }

            $oldrole = "";
            $others = "";
            switch (strtolower($role)) {
                case "director":
                    $oldrole = "director=false";
                    $others = "diplomat,recruiter,warden";
                    break;
                case "diplomat":
                    $oldrole = "diplomat=false";
                    $others = "director,recruiter,warden";
                    break;
                case "recruiter":
                    $oldrole = "recruiter=false";
                    $others = "director,diplomat,warden";
                    break;
                case "warden":
                    $oldrole = "warden=false";
                    $others = "director,diplomat,recruiter";
                    break;
            }
            if ($oldrole == "") {
                return 32;
            }
            $noroles = false;
            $query = sprintf("select $others from alliance_officers where name='%s'",
                $mysqlidb->real_escape_string($target_ai));
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0)) {
                $row = $result->fetch_row();

                if (($row[0] == false) && ($row[1] == false) && ($row[2] == false)) {
                    $noroles = true;
                }
            }
            if ($noroles == true) {
                //delete target ai from officer list
                $query = sprintf("delete from alliance_officers where alliance='%s' and name='%s'",
                    $mysqlidb->real_escape_string($this->alliance_name),
                    $mysqlidb->real_escape_string($target_ai));
            } else {
                $query = sprintf("update alliance_officers set $oldrole where alliance='%s' and name='%s'",
                    $mysqlidb->real_escape_string($this->alliance_name),
                    $mysqlidb->real_escape_string($target_ai));
            }
            $mysqlidb->query($query);

            return 1;
        }



        /*
         * get_owner()
         *      Returns the owner of the alliance
         */
        public function get_owner()
        {
            return $this->owner;
        }

        /*
         * get_power()
         *      Returns the power of the alliance
         */
        public function get_power()
        {
            return $this->power;
        }

        /*
         * get_renown()
         *      Returns the renown of the alliance
         */
        public function get_renown()
        {
            return $this->renown;
        }

        /*
         * get_role_list($role)
         *      Returns a list of all members of the alliance with the
         * requested role
         *
         *      requirements:
         *          1) alliance exists
         */
        public function get_role_list($role)
        {
            global $mysqlidb;
            if (!$this->exists())
            {
                // alliance does not exist
                return 0;
            }

            switch ($role)
            {
                case DIPLOMAT:
                    $role_string = "diplomat";
                    break;
                case DIRECTOR:
                    $role_string = "director";
                    break;
                case RECRUITER:
                    $role_string = "recruiter";
                    break;
                case WARDEN:
                    $role_string = "warden";
                    break;
                default:
                    //invalid role
                    return 27;
                    break;
            }

            $query = sprintf("select name from alliance_officers where alliance='%s' and %s=true",
                $mysqlidb->real_escape_string($this->alliance_name),
                $mysqlidb->real_escape_string($role_string));
            $result = $mysqlidb->query($query);

            $index = 0;
            $list = array("");
            while ($row = $result->fetch_row()) {
                $list[$index] = $row[0];
                $index ++;
            }

            return $list;
        }

        /*
         * set_link_and_notes
         *  updates the link, public and private notes
         *
         *  requirements:
         *      1) alliance exists
         *      1) ai is officer
         */
        public function set_link_and_notes($ai, $link, $av, $public, $private) {
            global $mysqlidb;
            if (!$this->exists()) {
                // alliance does not exist
                return 0;
            }
            if (!$this->is_officer($ai)) {
                //target ai is not an officer
                return 20;
            }
            $query = sprintf("update alliance set link='%s',avatar='%s',public='%s',private='%s' where name ='%s'",
                $mysqlidb->real_escape_string(strip_tags($link)),
                $mysqlidb->real_escape_string(strip_tags($av)),
                $mysqlidb->real_escape_string(strip_tags(substr($public, 0, 2500))),
                $mysqlidb->real_escape_string(strip_tags(substr($private, 0, 2500))),
                $mysqlidb->real_escape_string($this->alliance_name));
            $mysqlidb->query($query);
            return 1;
        }


        /*
         * get_link_info($role)
         *      Returns the link info for the alliance.
         *
         *      requirements:
         *          1) alliance exists
         */
        public function get_link_info()
        {
            return $this->link;
        }

        /*
         * get_type
         */
        public function get_type() {
            return $this->type;
        }


        /*
         * get_avatar
         */
        public function get_avatar() {
            return $this->avatar;
        }


        /*
         * get_public_info()
         *      Returns the public info for the alliance.
         */
        public function get_public_info()
        {
            return str_replace("\n", "\r", $this->pubnote);
        }

        /*
         * get_private_info($role)
         *      Returns the private info for the alliance.
         */
        public function get_private_info()
        {
            return str_replace("\n", "\r", $this->prinote);
        }

        /*
         * get_cpoints_info()
         *      returns string of control point info
         */
        public function get_cpoints_info() {
            global $mysqlidb;
            $ret = "";
            $locs = "";

            $cparr = explode(";", $this->cpoints);
            for ($idx = count($cparr)-1; $idx >= 0; $idx--) {
                $tcp = explode(":", $cparr[$idx]);
                $cparr[$idx] = array(2);
                if (count($tcp) > 4) {
                    if ($locs != "") {
                        $locs .= ",";
                    }
                    $locs .= "'" . $tcp[2] . "'";
                    $cparr[$idx][0] = $tcp[2];
                    $cparr[$idx][1] = $tcp[2] . ":" . $tcp[3] . ":" . $tcp[4] . ":";
                } else {
                    $cparr[$idx][0] = "";
                    $cparr[$idx][1] = "";
                }
            }
            $query = "select location,lcond from world where location in ($locs)";
            $result = $mysqlidb->query($query);
            if ($result && ($result->num_rows > 0)) {
                while (($row = $result->fetch_row()) != null) {
                    for ($idx = count($cparr)-1; $idx >= 0; $idx--) {
                        if ($cparr[$idx][0] == $row[0]) {
                            $cparr[$idx][1] .= $row[1];
                            break;
                        }
                    }
                }
            }

            foreach ($cparr as $cp) {
                if ($cp[0] != "") {
                    if ($ret != "") {
                        $ret .= ";";
                    }
                    $ret .= $cp[1];
                }
            }
            return $ret;
        }
    }


    /*
     * get_alliance_list()
     *      Returns a list of all alliance in the game along with some basic
     * information about those alliances sorted alphabetically by name.
     *
     *      return values:
     *          array[0]["name"]
     *          array[0]["owner"]
     *          array[0]["created"]
     *          array[0]["type"]
     *          array[1]["name"]
     *          array[1]["owner"]
     *          array[1]["created"]
     *          array[1]["type"]
     *          .
     *          .
     *          .
     */
    function get_alliance_list($sort)
    {
        global $mysqlidb;
        $order = "name asc";
        switch ($sort) {
            case 0:
                $order = "type desc";
                break;
            case 1:
                $order = "power desc";
                break;
            case 2:
                $order = "renown desc";
                break;
        }
        $query = "select name,owner,created,type,power,renown from alliance order by $order";
        $result = $mysqlidb->query($query);

        $list = array("");
        $index = 0;
        if ($result && ($result->num_rows > 0)) {
            while ($row = $result->fetch_row())
            {
                $list[$index]["name"] = $row[0];
                $list[$index]["owner"] = $row[1];
                $list[$index]["created"] = $row[2];
                $list[$index]["type"] = $row[3];
                $list[$index]["power"] = $row[4];
                $list[$index]["renown"] = $row[5];

                $index ++;
            }
        }
        if ($index == 0) {
            return null;
        }

        return $list;
    }

    /*
     * retrieves line for the alliance info or manage dialog
     */
    function get_alliance_info($ai, $al) {
        $line = "";
        if ($al != "") {
            $alliance = new Alliance();
            $alliance->open($al);
            $alliance->updatepower();

            $myal = $_SESSION["alliance"];
            if (($al == $myal) && ($alliance->is_officer($ai) == 1)) {
                $line = "ORGM|$al|"
                    . $alliance->get_type() . "|"
                    . $alliance->member_count() . "|"
                    . $alliance->member_max() . "|"
                    . $alliance->get_power() . "|"
                    . $alliance->get_renown() . "|"
                    . $alliance->get_owner() . "|"
                    . implode(':', $alliance->get_role_list(DIRECTOR)) . "|"
                    . implode(':', $alliance->get_role_list(DIPLOMAT)) . "|"
                    . implode(':', $alliance->get_role_list(RECRUITER)) . "|"
                    . implode(':', $alliance->get_role_list(WARDEN)) . "|"
                    . $alliance->get_link_info() . "|"
                    . $alliance->get_avatar() . "|"
                    . $alliance->get_public_info() . "|"
                    . $alliance->get_private_info() . "|"
                    . $alliance->member_list() . "|"
                    . $alliance->application_list() . "|"
                    . $alliance->get_cpoints_info();
            } else {
                $line = "ORGI|$al|"
                    . $alliance->get_type() . "|"
                    . $alliance->get_avatar() . "|"
                    . $alliance->member_count() . "|"
                    . $alliance->member_max() . "|"
                    . $alliance->get_power() . "|"
                    . $alliance->get_renown() . "|"
                    . $alliance->get_owner() . "|"
                    . implode(':', $alliance->get_role_list(DIRECTOR)) . "|"
                    . implode(':', $alliance->get_role_list(DIPLOMAT)) . "|"
                    . implode(':', $alliance->get_role_list(RECRUITER)) . "|"
                    . implode(':', $alliance->get_role_list(WARDEN)) . "|"
                    . $alliance->get_link_info() . "|"
                    . $alliance->get_public_info() . "|"
                    . (($myal == $al) ? $alliance->get_private_info() : "") . "|"
                    . $alliance->member_list();
            }
        }
        return $line;
    }

    /*
     * alliance_code_text($error)
     *      Returns a string with a description of the error code generated
     * by the Alliance class.
     */
    function alliance_code_text($error)
    {
        $errtext = "Unknown error";
        switch ($error)
        {
               case 0:
                   $errtext = "Organization does not exist";
                   break;
               case 1:
                   $errtext = "Success";
                   break;
               case 2:
                   $errtext = "Organization already exists";
                   break;
               case 3:
                   $errtext = "Master AI owns another organization";
                   break;
               case 4:
                   $errtext = "Master AI is insufficient level";
                   break;
               case 5:
                   $errtext = "Illegal organization name";
                   break;
               case 6:
                   $errtext = "Master AI does not have permission to perform this action";
                   break;
               case 7:
                   $errtext = "Master AI is already a member";
                   break;
               case 8:
                   $errtext = "Master AI already has an application with this organization";
                   break;
               case 9:
                   $errtext = "Master AI has reached limit of applications";
                   break;
               case 10:
                   $errtext = "No organization exists";
                   break;
               case 11:
                   $errtext = "Target Master AI has no application to this organization";
                   break;
               case 12:
                   $errtext = "Error finding max members";
                   break;
               case 13:
                   $errtext = "Organization is full";
                   break;
               case 14:
                   $errtext = "Target Master AI is not a member of this organization";
                   break;
               case 15:
                   $errtext = "Can't kick officer from organization";
                   break;
               case 16:
                   $errtext = "Owner can't leave organization";
                   break;
               case 17:
                   $errtext = "Target Master AI is already an officer";
                   break;
               case 18:
                   $errtext = "No more officer slots";
                   break;
               case 19:
                   $errtext = "Error finding max members";
                   break;
               case 20:
                   $errtext = "Target Master AI is not an officer";
                   break;
               case 21:
                   $errtext = "Can't change owner roles";
                   break;
               case 22:
                   $errtext = "Failed to set role";
                   break;
               case 23:
                   $errtext = "Only owner can upgrade organization";
                   break;
               case 24:
                   $errtext = "Error getting organization type";
                   break;
               case 25:
                   $errtext = "Can not downgrade level of organization";
                   break;
               case 26:
                   $errtext = "Organization at max size";
                   break;
               case 27:
                   $errtext = "Invalid role";
                   break;
               case 28:
                   $errtext = "Can not apply to an organization while in another organization";
                   break;
               case 29:
                   $errtext = "Invalid Organization type";
                   break;
               case 30:
                   $errtext = "Missing required item";
                   break;
               case 31:
                   $errtext = "Target Master AI is already in another organization";
                   break;
               case 32:
                   $errtext = "Invalid officer role";
                   break;
               case 33:
                   $errtext = "Target Master AI must be a director and at least level ".MINIMUM_LEVEL_TO_CREATE_ALLIANCE;
                   break;
               case 34:
                   $errtext = "Name already in use by a Master AI";
                   break;
               default:
                   $errtext = "Unknown error";
                   break;
        }
        return $errtext;
    }
?>