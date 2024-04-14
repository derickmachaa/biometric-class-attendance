<?php
session_start();
$UNIT_COUNT = 0;
$STUDENT_COUNT = 0;
$SESSION_COUNT = 0;
$FINGERPRINT_COUNT = 0;
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
} else {
    if ($_SESSION['username'] != "admin") {
        header("Location: index.php");
        exit();
    }
    //do some connections to mysql
    include_once "config/config.php";
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    getStats();

    //check whether to insert a new session into the system
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if ($action === "newsession") {
            $stmt = $conn->prepare("INSERT INTO tbl_Sessions(UnitCode,Date,Venue) values (?,?,?)");
            $stmt->bind_param("sss", $_POST['unitcode'], $_POST['date'], $_POST['venue']);
            $stmt->execute();
            if (mysqli_affected_rows($conn) == 1) {
                header("Location: homepage.php#sessions");
                exit();
            }
        } else
        if ($action === "newunit") {
            $stmt = $conn->prepare("INSERT INTO tbl_Units(UnitCode,UnitName) values (?,?)");
            $stmt->bind_param("ss", $_POST['unitcode'], $_POST['unitname']);
            $stmt->execute();
            if (mysqli_affected_rows($conn) == 1) {
                header("Location: homepage.php#units");
                exit();
            }
        } else
        if ($action == "newstudent") {
            $stmt = $conn->prepare("INSERT INTO tbl_Students(AdmissionNo,FirstName,LastName,Email,PhoneNo) values (?,?,?,?,?);");
            $stmt->bind_param("sssss", $_POST['admno'], $_POST['fname'], $_POST['lname'], $_POST['email'], $_POST['phoneno']);
            $stmt->execute();
            if (mysqli_affected_rows($conn) == 1) {
                //insert into fingerprints
                $stmt = $conn->prepare("INSERT INTO tbl_Fingerprints(AdmissionNo,RightFinger,LeftFinger) values (?,?,?);");
                $stmt->bind_param("sss", $_POST['admno'], $_POST['rfinger'], $_POST['lfinger']);
                $stmt->execute();
                if (mysqli_affected_rows($conn) == 1) {
                    header("Location: homepage.php#students");
                    exit();
                }
            }
        }
    }
}
function getStats()
{
    //student count
    $stmt = $GLOBALS['conn']->prepare("SELECT Count(*) from tbl_Students;");
    $stmt->execute();
    $result = $stmt->get_result();
    $GLOBALS['STUDENT_COUNT'] = $result->fetch_column();
    //unit count
    $stmt = $GLOBALS['conn']->prepare("SELECT Count(*) from tbl_Units;");
    $stmt->execute();
    $result = $stmt->get_result();
    $GLOBALS['UNIT_COUNT'] = $result->fetch_column();
    //session count
    $stmt = $GLOBALS['conn']->prepare("SELECT Count(*) from tbl_Sessions;");
    $stmt->execute();
    $result = $stmt->get_result();
    $GLOBALS['SESSION_COUNT'] = $result->fetch_column();
    //get fingerprint count;
    $stmt = $GLOBALS['conn']->prepare("select count(RightFinger)+count(LeftFinger) from tbl_Fingerprints where RightFinger is not null or LeftFinger is not null;");
    $stmt->execute();
    $count = 0;
    $stmt->bind_result($count);
    /* fetch values */
    $stmt->fetch();
    $GLOBALS['FINGERPRINT_COUNT'] = $count;
}
function getSessions()
{
    $stmt = $GLOBALS['conn']->prepare('SELECT * from tbl_Sessions');
    $sessionid = "";
    $unitcode = "";
    $date = "";
    $venue = "";
    $stmt->bind_result($sessionid, $unitcode, $date, $venue);
    $stmt->execute();
    while ($stmt->fetch()) {
        echo "<tr>";
        echo "<td>$sessionid</td>";
        echo "<td>$unitcode</td>";
        echo "<td>$date</td>";
        echo "<td>$venue</td>";
        echo "<tr>";
    }
}

function getUnits()
{
    $stmt = $GLOBALS['conn']->prepare('SELECT * from tbl_Units');
    $unitcode = "";
    $unitname = "";
    $stmt->bind_result($unitcode, $unitname);
    $stmt->execute();
    while ($stmt->fetch()) {
        echo "<tr>";
        echo "<td>$unitcode</td>";
        echo "<td>$unitname</td>";
        echo "<tr>";
    }
}
function getStudents()
{
    $stmt = $GLOBALS['conn']->prepare('select tbl_Students.AdmissionNo,FirstName,LastName,Email,PhoneNo,RightFinger,LeftFinger from tbl_Students inner join tbl_Fingerprints where tbl_Students.AdmissionNo = tbl_Fingerprints.AdmissionNo;');
    $admno = "";
    $fname = "";
    $lname = "";
    $email = "";
    $phoneno = "";
    $rfinger = "";
    $lfinger = "";
    $stmt->bind_result($admno, $fname, $lname, $email, $phoneno, $rfinger, $lfinger);
    $stmt->execute();
    while ($stmt->fetch()) {
        echo "<tr>";
        echo "<td>$admno</td>";
        echo "<td>$fname</td>";
        echo "<td>$lname</td>";
        echo "<td>$email</td>";
        echo "<td>$phoneno</td>";
        echo "<td>$rfinger</td>";
        echo "<td>$lfinger</td>";
        echo "<tr>";
    }
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>BCAS</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        html,
        body{
            font-family: "Raleway", sans-serif
        }
    </style>
</head>

<body>

    <!-- Top container -->
    <div class="w3-bar w3-top w3-blue-grey w3-large">
        <span class="w3-bar-item w3-left w3-text-white" >     Hi  <strong><?php echo $_SESSION['username']; ?></strong></span>
        <a href="logout.php" class="w3-bar-item w3-right w3-button">LOGOUT</a>
    </div>

    <!-- Sidebar/menu -->
    <nav class="w3-sidebar w3-light-grey" style="width:300px;" id="mySidebar">
        <div class="w3-container w3-green">
            <h5>Dashboard</h5>
        </div>
        <div class="w3-bar-block">
            <a href="#" class="w3-bar-item w3-button w3-padding w3-large">Overview</a>
            <a href="#units" class="w3-bar-item w3-button w3-padding w3-large"><i>Units</i></a>
            <a href="#students" class="w3-bar-item w3-button w3-padding w3-large">Students</a>
            <a href="#sessions" class="w3-bar-item w3-button w3-padding w3-large"><i> Sessions</i></a>
        </div>
    </nav>

    <!-- !PAGE CONTENT! -->
    <div class="w3-main" style="margin-left:300px;margin-top:43px;height:100vh">

        <!-- Header -->
        <header class="w3-container">
            <h5><b><i class="fa fa-dashboard"></i> My Dashboard</b></h5>
        </header>

        <div class="w3-row-padding w3-margin-bottom">
            <div class="w3-quarter">
                <div class="w3-container w3-orange w3-text-white w3-padding-16">
                    <div class="w3-left"><i class="fa fa-users w3-xxxlarge"></i></div>
                    <div class="w3-right">
                        <h3><?php echo $GLOBALS['STUDENT_COUNT'] ?></h3>
                    </div>
                    <div class="w3-clear"></div>
                    <h4>Students</h4>
                </div>
            </div>
            <div class="w3-quarter">
                <div class="w3-container w3-red w3-padding-16">
                    <div class="w3-left"><i class="fa fa-university w3-xxxlarge"></i></div>
                    <div class="w3-right">
                        <h3><?php echo $GLOBALS['UNIT_COUNT']; ?></h3>
                    </div>
                    <div class="w3-clear"></div>
                    <h4>Units</h4>
                </div>
            </div>
            <div class="w3-quarter">
                <div class="w3-container w3-blue w3-padding-16">
                    <div class="w3-left"><i class="fa fa-book w3-xxxlarge"></i></div>
                    <div class="w3-right">
                        <h3><?php echo $GLOBALS['SESSION_COUNT']; ?></h3>
                    </div>
                    <div class="w3-clear"></div>
                    <h4>Sessions</h4>
                </div>
            </div>
            <div class="w3-quarter">
                <div class="w3-container w3-green w3-padding-16">
                    <div class="w3-left"><i class="fa fa-thumbs-up w3-xxxlarge"></i></div>
                    <div class="w3-right">
                        <h3><?php echo $GLOBALS['FINGERPRINT_COUNT']; ?></h3>
                    </div>
                    <div class="w3-clear"></div>
                    <h4>Fingerprints</h4>
                </div>
            </div>

        </div>
    </div>

    <!-- well sessions section !-->
    <div class="w3-main" id="sessions" style="margin-left:300px;height:100vh;">
        <!-- Header -->
        <header class="w3-bar w3-orange" style="padding-top:50px;">
            <b class="w3-bar-item">Sessions</b>
            <button class="w3-bar-item w3-button w3-right w3-green w3-padding" type="button" onclick="document.getElementById('sessionform').style.display='block'"><b>New Session</b></button>
        </header>
        <!-- div table for sessions !-->
        <div>
            <table class="w3-table-all w3-card-4">
                <tr class="w3-red">
                    <td>SessionID</td>
                    <td>UnitCode</td>
                    <td>Date</td>
                    <td>Venue</td>
                </tr>
                <?php getSessions(); ?>
            </table>
        </div>

        <!-- div for new session form !-->
        <div class="w3-modal" id="sessionform">
            <form class="w3-modal-content w3-display-middle w3-round w3-padding" action="homepage.php" method="post">
                <div class="w3-container">
                    <label for unitcode="unitcode">UnitCode</label></br>
                    <input class="w3-input w3-border" type="text" name="unitcode" placeholder="UnitCode">
                    <label for date="date">Date</label></br>
                    <input class="w3-input w3-border" type="text" name="date" placeholder="Date">
                    <label for venue="venue">Venue</label></br>
                    <input class="w3-input w3-border" type="text" name="venue" placeholder="Venue">
                    <input type="hidden" type="text" name="action" value="newsession">
                </div>
                <div class="w3-container">
                    <button class="w3-button w3-green w3-left" type="submit">Create</button>
                    <button class="w3-button w3-red w3-right" type="button" onclick="document.getElementById('sessionform').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- end of sessions !-->



    <!-- begin of unit section !-->
    <div class="w3-main" id="units" style="margin-left:300px;height:100vh;">
        <!-- Header -->
        <header class="w3-bar w3-orange" style="padding-top:50px;">
            <b class="w3-bar-item">Units</b>
            <button class="w3-bar-item w3-button w3-right w3-green" style="height:100%" type="button" onclick="document.getElementById('unitform').style.display='block'"><b>Create Unit</b></button>
        </header>
        <!-- div table for sessions !-->
        <div>
            <table class="w3-table-all w3-card-4">
                <tr class="w3-red">
                    <td>UnitCode</td>
                    <td>UnitName</td>
                </tr>
                <?php getUnits(); ?>
            </table>
        </div>

        <!-- div for new session form !-->
        <div class="w3-modal w3-card-4" id="unitform">
            <form class="w3-modal-content w3-round w3-padding w3-display-middle" action="homepage.php" method="post">
                <div class="w3-container">
                    <label for unitcode="unitcode">UnitCode</label></br>
                    <input class="w3-input w3-border w3-padding" type="text" name="unitcode" placeholder="UnitCode">
                    <label for unitname="unitname">UnitName</label></br>
                    <input class="w3-input w3-border w3-padding" type="text" name="unitname" placeholder="UnitName">
                    <input type="hidden" type="text" name="action" value="newunit">
                </div>
                <div class="w3-container">
                    <button class="w3-button w3-green w3-left" type="submit">Create</button>
                    <button class="w3-button w3-red w3-right" type="button" onclick="document.getElementById('unitform').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <!-- end of unit section !-->

    <!-- begin of student section !-->
    <div class="w3-main" id="students" style="margin-left: 300px;height:100vh;">
        <!-- add header !-->
        <div class="w3-bar w3-orange" style="padding-top: 50px;">
            <div class="w3-bar">
                <h class="w3-bar-item">Students</h>
                <button class="w3-bar-item w3-button w3-padding w3-right w3-green" onclick="startNewStudentRegistration();">NEW STUDENT</button>
            </div>
        </div>
        <!-- header done !-->

        <!-- tables now !-->

        <div class="w3-main">
            <table class="w3-table-all w3-card-4">
                <tr class="w3-red">
                    <td>AdmissionNo</td>
                    <td>FirstName</td>
                    <td>LastName</td>
                    <td>Email</td>
                    <td>PhoneNo</td>
                    <td>RightFingerPrint</td>
                    <td>LeftFingerPrint</td>
                </tr>
                <?php getStudents(); ?>
            </table>
        </div>

        <!-- tables done !-->
        <!-- student form !-->
        <div class="w3-modal w3-card-4" id="studentform">
            <form class="w3-modal-content w3-round w3-padding" action="homepage.php" method="post">
                <label for studentadm="admission">Admission Number</label></br>
                <input class="w3-input w3-padding w3-border" type="text" placeholder="Admission Number" name="admno">
                <label for studentfname="fname">First Name</label></br>
                <input class="w3-input w3-padding w3-border" type="text" placeholder="First Name" name="fname">
                <label for studentlname="lname">Last Name</label></br>
                <input class="w3-input w3-padding w3-border" type="text" placeholder="Last Name" name="lname">
                <label for studentemail="email">Email</label></br>
                <input class="w3-input w3-padding w3-border" type="text" placeholder="Email" name="email">
                <label for studentphone="phone">Phone Number</label></br>
                <input class="w3-input w3-padding w3-border" type="text" placeholder="Phone Number" name="phoneno">
                <label for studentfinger="fingers">FingerPrints</label></br>
                <div class="w3-bar">
                    <button class="w3-bar-item w3-button w3-padding w3-round w3-green w3-right" type="button" onclick="doGetRightFinger();">Get RightFinger</button>
                    <input class="w3-bar-item  w3-input w3-border w3-right" id="rfinger" name="rfinger" type="number" readonly>
                    <input class="w3-bar-item  w3-input w3-border w3-left" id="lfinger" name="lfinger" type="number" readonly>
                    <button class="w3-bar-item w3-button w3-padding w3-round w3-green w3-left" type="button" onclick="doGetLeftFinger();">Get LeftFinger</button>
                </div>
                </br>
                <div class="w3-bar">
                    <button class="w3-bar-item w3-button w3-padding w3-round w3-green w3-left w3-green" type="submit">Create</button>
                    <button class="w3-bar-item w3-button w3-padding w3-round w3-red w3-right w3-left" type="button" onclick="document.getElementById('studentform').style.display='none';">Cancel</button>
                </div>
                <input type="hidden" name="action" value="newstudent" type="text">

            </form>
        </div>

    </div>
    <!-- end of student section !-->


    <script>
        var available_slot = -1;
        var rfinger = -1;
        var lfinger = -1;

        //get the various values

        function startNewStudentRegistration() {
            // this function will do is get the available fingerprint slot from the db to allow student creation
            const apiUrl = '/api/free_slot.php';

            // Make a GET request using the Fetch API
            fetch(apiUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(data => {
                    // Process the retrieved user data
                    available_slot = parseInt(data);
                    rfinger = available_slot + 1;
                    lfinger = available_slot + 2;
                    document.getElementById('studentform').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function doGetRightFinger() {
            if (rfinger != -1) {
                //connect to arduino and attempt to get a finger registered
                const apiUrl = 'http://192.168.43.170/register?id=' + rfinger.toString();
                fetch(apiUrl, {
                        headers: {
                            "Authorization": "derick"
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.text();
                    })
                    .then(data => {
                        // Process the retrieved user data
                        if (data.toString() == "added successfully") {
                            document.getElementById("rfinger").value = rfinger;
                        } else {
                            alert("not added ");
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            } else {
                alert("Close and open create dialog again");
            }
        }

        function doGetLeftFinger() {
            if (lfinger != -1) {
                //connect to arduino and attempt to get a finger registered
                const apiUrl = 'http://192.168.43.170/register?id=' + lfinger.toString();
                fetch(apiUrl, {
                        headers: {
                            "Authorization": "derick"
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.text();
                    })
                    .then(data => {
                        // Process the retrieved user data
                        if (data.toString() == "added successfully") {
                            document.getElementById("lfinger").value = lfinger;
                        } else {
                            alert("not added ");
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            } else {
                alert("Close and open create dialog again");

            }
        }
    </script>

</body>

</html>