<?php
function get_user_courses($userid, &$semestername, &$coursedept, &$coursenum, &$coursesect)
{
    global $CFG, $DB;

    
	
    $sql = 'SELECT crs.id, crs.shortname'
         . '  FROM {role_assignments} rol'
         . '  INNER JOIN {user}       usr ON rol.userid = usr.id     AND rol.roleid IN (5,12)'
         . '  INNER JOIN {context}    ctx ON rol.contextid = ctx.id  AND ctx.contextlevel = 50'
         . '  INNER JOIN {course}     crs ON ctx.instanceid = crs.id'
         . ' WHERE dbo.fromUnixtime(crs.enddate) > GETDATE()'
         . '   AND usr.id = :userid'
         . ' ORDER BY crs.shortname';
    $params = array('userid'=>$userid);

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
        $course_shortname = $course_record->shortname;

        // The shortname field in the course table holds the year/term in a coded format: CourseID.SectionID-TTYYYY
        //  where TT is the first 2 characters of the term and YYYY is the 4-digit year.
        // The $semestername field need the year/term converted where the year is in YYYY format and the term is
        // a 3-digit code (010=Winter, 020=Spring, 030=Summer, 040=Fall).
        //  Example: a 2016 Summer course would have the idnumber 2016030.
        $ttyyyy = strrev(substr(strrev($course_shortname),0,6));
        $term = substr($ttyyyy,0,2);
        $year = substr($ttyyyy,2,4);
        $yyyyttt = "";
        switch ($term) {
            case "JA":
                $yyyyttt = $year."010";
                break;
            case "SP":
                $yyyyttt = $year."020";
                break;
            case "SU":
                $yyyyttt = $year."030";
                break;
            case "FA":
                $yyyyttt = $year."040";
                break;
            default:
                break;
        }

        $semestername .= $yyyyttt.'|';              // returns '2016030|'

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