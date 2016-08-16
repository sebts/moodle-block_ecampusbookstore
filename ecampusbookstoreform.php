<?php
/**
 * This file is derived from the block_ecampusbookstore.php file.
 * The block_ecampusbookstore.php file is an extension of the block class and can only be used
 * within Moodle's blocks framework.
 * This ecampusbookstoreform.php, however, is intended to be used outside of Moodle's block framework,
 * specifically in the site menu. It can be called directly but is best called by a redirecting page
 * such as the sitemenupage.php page in this same folder.
 *
 * @package    block_ecampusbookstore
 * @version 2016070100
 * @author tstamp
 */

require_once('../../config.php');
require_once 'getaccesscode.php';
require_once 'services_usercourses.php';

$ecampusform = get_ecampusbookstoreform();
echo $ecampusform;

function get_ecampusbookstoreform() {
    global $CFG, $OUTPUT, $USER, $COURSE;
    $content = "";

    // Get parameters from config DB
    $accesscodeurl = get_config('ecampusbookstore', 'generateaccesscodeurl');
    $formactionurl = get_config('ecampusbookstore', 'formactionurl');
    $schoolid = get_config('ecampusbookstore', 'schoolid');
    $secretkey = get_config('ecampusbookstore', 'secretkey');
    $userid = $USER->id;
    $username = $USER->username;
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
        //Holds the actual block's HTML
        $content = '<body><form name="ecampusform" class="eCampusForm" method="post" action="'.$formactionurl.'">
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
                             <input type="hidden" name="courses3" value="'.$coursesect.'" />';
        } else {
            $content .= '<input type="hidden" name="instructoremail" value="'.$useremail.'" />
                             <input type="hidden" name="fullname" value="'.$userfullname.'" />';
        }
        $content .= '</form><script language="javascript">document.forms[0].submit();</script></body>';
    } else {
        $content = '<body>'.get_string('accesscodeerror','block_ecampusbookstore').'</body>';
    }

    return $content;
}
