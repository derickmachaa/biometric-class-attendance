<?php
include_once "../config/config.php";
// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

//get the latest fingerprint number
$stmt = $conn->prepare("select MAX(GREATEST(RightFinger,LeftFinger)) as lastfinger from tbl_Fingerprints;");
$num=-1;
$stmt->bind_result($num);
//execute sql
$stmt->execute();
$stmt->fetch();
if($num=="")
    $num=0;
echo $num;

?>