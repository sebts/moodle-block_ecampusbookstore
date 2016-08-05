<?php
function get_user_courses($userid, &$semestername, &$coursedept, &$coursenum, &$coursesect)
{
    global $CFG, $DB;

    $currentterm = $CFG->currentSemester;

    $sql = 'SELECT crs.id, crs.idnumber, crs.shortname'
         . '  FROM {role_assignments} rol'
         . '  INNER JOIN {user}       usr ON rol.userid = usr.id     AND rol.roleid IN (5,12)'
         . '  INNER JOIN {context}    ctx ON rol.contextid = ctx.id  AND ctx.contextlevel = 50'
         . '  INNER JOIN {course}     crs ON ctx.instanceid = crs.id'
         . ' WHERE crs.idnumber between :currentterm and (select IDNumber from getmaxcourseidnumber)'
         . '   AND usr.id = :userid'
         . ' ORDER BY crs.shortname';
    $params = array('currentterm'=>$currentterm, 'userid'=>$userid);

    // Get recordset as an associative array
    $rs = $DB->get_records_sql($sql, $params);

    // Set pointer to first element of the array
    reset($rs);

    // Loop through each array element
    // Note: The course ID is not used but per Moodle documentation,
    //      "it appears to be best practice to ensure that your query include an 'id column' as the first field."
    //      This is because the list expects 2 elements for each record of the recordset: an ID and an associative
    //      array of columns from the select statement.
    while (list($course_id, $course_record) = each($rs))
    {
        $course_idnumber = $course_record->idnumber;
        $course_shortname = $course_record->shortname;

        // The idnumber field in the course table holds the year/term in a coded format:
        // The year in YYYY format and a 3-digit code which represent the term (010=Winter, 020=Spring, 030=Summer, 040=Fall).
        //  Example: a 2016 Summer course would have the idnumber 2016030.
        $semestername .= $course_idnumber.'|';              // returns '2016030|'

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
        $semestername = substr($semestername, 0, (strlen($semestername) - 1));
        $coursedept = substr($coursedept, 0, (strlen($coursedept) - 1));
        $coursenum  = substr($coursenum , 0, (strlen($coursenum ) - 1));
        $coursesect = substr($coursesect, 0, (strlen($coursesect) - 1));
    }
}