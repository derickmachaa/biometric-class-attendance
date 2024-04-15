<?php
include_once "../config/config.php";
// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
//set the necessary headers
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
//check if admin is logged in
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    //check the header of the file
    $sessionheader = $_SERVER['HTTP_AUTHORIZATION'];
    //get session key
    $session = explode(" ", $sessionheader)[1];
    //decode the key
    if ($session === API_AUTH) {
        //get the posted data from 
        $postdata = json_decode(file_get_contents("php://input"));
        //try to get all the data in the form
        $_id = $postdata->id;
        $_sessionid = $postdata->sessionid;

        if (isset($_id) && isset($_sessionid)) {
            // prepare and bind
            $stmt = $conn->prepare("UPDATE tbl_Attendance join tbl_Fingerprints on tbl_Attendance.AdmissionNo = tbl_Fingerprints.AdmissionNo set tbl_Attendance.Attended = TRUE 
                where sessionID=? and (tbl_Fingerprints.RightFinger = ? or tbl_Fingerprints.LeftFinger = ?);");
            $stmt->bind_param("ddd", $_sessionid, $_id, $_id);
            //execute sql
            $result = $stmt->execute();
            if ($result && mysqli_affected_rows($conn) > 0) {
                http_response_code(200);
                echo json_encode(array("message" => "successfully registered"));
            } else {
                http_response_code(403);
                echo json_encode(array("message" => "not allowed"));
            }
            $stmt->close();
            $conn->close();
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "body required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "invalid bearer token"));
    }
} else {
    //return an error
    http_response_code(400);
    echo json_encode(array("message" => "Authorization required"));
}


?>
