<?php
/**
 * Helper routines.
 *
 * @package    block_ecampusbookstore
 * @copyright  N/a <>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function get_student_access_code($accesscodeurl, $schoolid, $secretkey, $username) {
    // Build an array of parameters to be past in the URL
    $params = http_build_query(array('s' => $schoolid, 'k' => $secretkey, 'studentid' => $username),'','&');

    // Get cURL resource
    $curl = curl_init();

    // Set cURL options do a POST request with supplied parms and capture the response to a variable
    curl_setopt_array($curl, array( CURLOPT_URL => $accesscodeurl
                                  , CURLOPT_POST => 1
                                  , CURLOPT_POSTFIELDS => $params
                                  , CURLOPT_RETURNTRANSFER => 1
                                  )
                     );

    // Send the request & capture the response
    $resp = curl_exec($curl);

    // Test for errors and issue a safe error message instead.
    if (strpos($resp, 'Error') !== false) {
        $resp = null;
    }

    // Clean up
    curl_close($curl);

    return $resp;
}

function get_intructor_access_code($accesscodeurl, $schoolid, $secretkey, $useremail) {
    // Build an array of parameters to be past in the URL
    $params = http_build_query(array('s' => $schoolid, 'k' => $secretkey, 'instructoremail' => $useremail),'','&');

    // Get cURL resource
    $curl = curl_init();

    // Set cURL options do a POST request with supplied parms and capture the response to a variable
    curl_setopt_array($curl, array( CURLOPT_URL => $accesscodeurl
                                  , CURLOPT_POST => 1
                                  , CURLOPT_POSTFIELDS => $params
                                  , CURLOPT_RETURNTRANSFER => 1
                                  )
                     );
    
    // Send the request & capture the response
    $resp = curl_exec($curl);
    
    // Test for errors and issue a safe error message instead.
    if (strpos($resp, 'Error') !== false) {
        $resp = null;
    }
    
    // Clean up
    curl_close($curl);

    return $resp;
}
