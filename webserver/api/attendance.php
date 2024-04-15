<?php
include_once "../config/config.php";
//check if admin is included
session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo "unauthorized";
    exit();
} elseif ($_SESSION['username'] != "admin") {
        http_response_code(401);
        echo "unauthorized";
        exit();
}
// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$stmt = $GLOBALS['conn']->prepare('select UnitCode,Date,Venue,AdmissionNo,Attended from tbl_Attendance inner join tbl_Sessions on tbl_Sessions.SessionID = tbl_Attendance.SessionID where tbl_Sessions.SessionID=?;');
$id=$_GET['id'];
$stmt->bind_param("d", $id);
$admno = "";
$unitcode = "";
$date = "";
$venue = "";
$attended = "";
$stmt->bind_result($unitcode, $date, $venue, $admno, $attended);
$stmt->execute();
$result=array();
while ($stmt->fetch()) {
    $seen=array("unitcode"=>$unitcode,"date"=>$date,"venue"=>$venue,"admissionno"=>$admno,"attended"=>$attended);
    array_push($result,$seen);
}
echo json_encode($result);
?>
