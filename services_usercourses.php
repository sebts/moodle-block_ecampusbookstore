<?php
function get_user_courses($userid, &$semestername, &$coursedept, &$coursenum, &$coursesect)
{
    global $DB;

    // The semestername is hard coded for now as well as the course idnumber field in the SQL WHERE clause.
    // eCampus is not ready yet to implement multiple terms. They should be ready before November 2016 (when registration for Winter/Spring 2017 happens).
    // When eCampus implements multiple terms then the part of the SQL clause which is currently commemted out will replace the hard coded 2016040.
    // Also, the semestername will be part of the loop below where coursedept, coursenum, and coursesect are being parsed from the course shortname.
    $semestername = 'Fall 2016';

    //$sql = 'select c.shortname'
    //     . '  from {user} u'
    //     . '  inner join {user_enrolments} ue on u.id=ue.userid'
    //     . '  inner join {enrol} e on ue.enrolid=e.id'
    //     . '  inner join {course} c on e.courseid=c.id'
    //     . ' where c.idnumber = 2016040' //between (select IDNumber from getcurrentcourseidnumbers) and (select IDNumber from getmaxcourseidnumber)'
    //     . '   and u.id=:userid'
    //     . ' order by c.shortname';

    // The SQL below is preferred over the one one above since it targets classes in which the current user is explicitly assigned the role of student or auditor.
    $sql = 'SELECT csr.shortname'
         . '  FROM {role_assignments} rol'
         . '  INNER JOIN {user}       usr ON rol.userid = usr.id     AND rol.roleid IN (5,12)'
         . '  INNER JOIN {context}    ctx ON rol.contextid = ctx.id  AND ctx.contextlevel = 50'
         . '  INNER JOIN {course}     csr ON ctx.instanceid = csr.id'
         . ' WHERE csr.idnumber = 2016040' //between (select IDNumber from getcurrentcourseidnumbers) and (select IDNumber from getmaxcourseidnumber)'
         . '   AND usr.id = :userid'
         . ' ORDER BY csr.shortname';
    $params = array('userid'=>$userid);

    // Get recordset as an associative array
    $rs = $DB->get_records_sql($sql, $params);

    // Set pointer to first element of the array
    reset($rs);

    // Loop through each array element
    while (list($course_shortname) = each($rs))
    {
        // Parse the course shortname into its constituent parts and load up the eCampus Bookstore course variables:
        //  Example: ENG2110.ONL-FA2016
        $coursedept .= substr($course_shortname,0,3).'|';   //returns 'ENG|'
        $coursenum  .= substr($course_shortname,3,4).'|';   //returns '2110|'
        // Find the course section portion of the shortname (the part between the first dot '.' and the immediately following dash '-').
        $dot = strpos($course_shortname,'.');
        $dash = strpos($course_shortname,'-');
        $coursesect .= substr($course_shortname,($dot + 1),($dash - $dot - 1)).'|';   //returns 'ONL|'
    }

    // Remove the trailing pipe '|' from the last element
    if (strlen($coursedept) > 0)
    {
        $coursedept = substr($coursedept, 0, (strlen($coursedept) - 1));
        $coursenum  = substr($coursenum , 0, (strlen($coursenum ) - 1));
        $coursesect = substr($coursesect, 0, (strlen($coursesect) - 1));
    }
}