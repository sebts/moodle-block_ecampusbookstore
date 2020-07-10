<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Block main file.
 *
 * @package    block_ecampusbookstore
 * @copyright  N/a <>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once 'getaccesscode.php';
require_once 'services_usercourses.php';

class block_ecampusbookstore extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_ecampusbookstore');
    }

    function get_content() {
        global $CFG, $OUTPUT, $USER, $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass;

        // Get parameters from config DB
        $accesscodeurl = get_config('ecampusbookstore', 'generateaccesscodeurl');
        $formactionurl = get_config('ecampusbookstore', 'formactionurl');
        $schoolid = get_config('ecampusbookstore', 'schoolid');
        $secretkey = get_config('ecampusbookstore', 'secretkey');

        $userid = $USER->id;
        $username = $USER->idnumber;
        $useremail = $USER->email;
        $userfullname = $USER->firstname.' '.$USER->lastname;

        // Variables to hold courses the student is enrolled
        $semestername="";
        $coursedept="";
        $coursenum="";
        $coursesect="";

        // Determine if current user should be considered as a student or as an intructor
        $usestudentportal = true;
        $coursecontext = context_course::instance($COURSE->id);
        if (has_capability('moodle/course:viewhiddensections', $coursecontext, $USER->id, false) ) {
            // If the current user has the capability to view hidden sections on the course page
            // then the user is 'more' than a student or an auditor. In that case, assume that the
            // user is an instructor or an assistant and give the user access to the eCampus FAST
            // system instead of the normal bookstore.
            $usestudentportal = false;
        }

        // Get appropriate access code depending on whether user is considered a student or an intructor
        $ecampusaccesscode = null;
        if ($usestudentportal) {
            get_user_courses($userid, $semestername, $coursedept, $coursenum, $coursesect);
            $ecampusaccesscode = get_student_access_code($accesscodeurl, $schoolid, $secretkey, $username);
        } else {
            $ecampusaccesscode = get_intructor_access_code($accesscodeurl, $schoolid, $secretkey, $useremail);
        }

        // Make sure the call to get_access_code was successful
        if ($ecampusaccesscode != null) {
            $linkButton = "$CFG->wwwroot/blocks/".$this->name()."/images/eCampusButton.png";

            //Holds the actual block's HTML
            $content = '<body><form name="ecampusform" class="eCampusForm" method="post" action="'.$formactionurl.'" target="_blank">
                        <input type="hidden" name="s" value="'.$schoolid.'" />
                        <input type="hidden" name="accesscode" value="'.$ecampusaccesscode.'" />
                       ';

            if($usestudentportal) {
                $content .= '<input type="hidden" name="studentid" value="'.$username.'" />
                             <input type="hidden" name="email" value="'.$useremail.'" />
                             <input type="hidden" name="fullname" value="'.$userfullname.'" />
                             <input type="hidden" name="semestername" value="'.$semestername.'" />
                             <input type="hidden" name="courses" value="'.$coursedept.'" />
                             <input type="hidden" name="courses2" value="'.$coursenum.'" />
                             <input type="hidden" name="courses3" value="'.$coursesect.'" />
                             <input type="image" class="eCampusButton" src="'.$linkButton.'" alt="Access Your Virtual Bookstore" />
                            ';
            } else {
                $content .= '<input type="hidden" name="instructoremail" value="'.$useremail.'" />
                             <input type="hidden" name="fullname" value="'.$userfullname.'" />
                             <input type="image" class="eCampusButton" src="'.$linkButton.'" alt="Access the FAST System" />
                            ';
            }

            $content .= '</form></body>';
        } else {
            $content = '<body>'.get_string('accesscodeerror','block_ecampusbookstore').'</body>';
        }
		$this->content->text = $content;

        return $this->content;
    }

    public function applicable_formats() {
        return array('course-view' => true);
    }

    function has_config() {
        return true;
    }

    public function html_attributes() {
        $attributes = parent::html_attributes();
        $attributes['class'] .= ' block_'. $this->name();
        return $attributes;
    }

    function instance_allow_multiple() {
        return false;
    }

    public function hide_header() {
        return true;
    }
}
