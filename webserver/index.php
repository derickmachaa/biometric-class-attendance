<!DOCTYPE html>
<html>

<head>
    <title>IOT BCAS</title>
    <link rel="stylesheet" href="/css/w3.css">
    <style>
        body,
        html {
            height: 100%;
            line-height: 1.8;
        }

        .background {
            background-position: center;
            background-size: cover;
            background-image: url("/images/background.jpg");
            min-height: 100%;
        }
    </style>
</head>

<body>
    <!--nav bar-->
    <div class="w3-top">
        <div class="w3-bar w3-blue-grey w3-card" id="myNavbar">
            <a href="#home" class="w3-bar-item w3-button w3-wide">HOME</a>
            <!-- Right-sided navbar links -->
            <div class="w3-right w3-hide-small">
                <button class="w3-bar-item w3-button"
                    onclick="document.getElementById('login').style.display='block'">LOGIN</button>
                <a href="#about" class="w3-bar-item w3-button">ABOUT</a>
                <a href="#contact" class="w3-bar-item w3-button">CONTACT</a>
            </div>
        </div>
    </div>
    <!-- end navr bar-->
    <header class="background w3-container w3-grayscale-min" id="home">
        <div class="w3-display-topright w3-text-white" style="padding-top:2%;padding-right: 15%;">
            <span class="w3-xxlarge w3-hide-small">Are you in class today?</span><br>
            <span class="w3-xlarge">Your finger will tell!</span>
        </div>
    </header>
    <div id="login" class="w3-modal">
        <form class="w3-modal-content w3-card w3-round w3-padding w3-display-middle" action="/index.php"
            method="post" style="width:30%">
            <div class="w3-container">
                <label for="uname"><b>Username</b></label>
                <input class="w3-input w3-border" type="text" placeholder="Enter Username" name="uname" required>

                <label for="psw"><b>Password</b></label>
                <input class="w3-input w3-border" type="password" placeholder="Enter Password" name="psw" required>
            </div>

            <div class="w3-container">
                <button class="w3-button w3-green w3-left" type="submit">Login</button>
                <button class="w3-button w3-red w3-right" type="button"
                    onclick="document.getElementById('login').style.display='none'">Cancel</button>
            </div>

        </form>
    </div>

    <script>
        // Get the modal
        var modal = document.getElementById('login');

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function (event) {
            if (event.target == w3 - modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>

</html>


<?php
include_once "config/config.php";
session_start();
// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if(isset($_POST['uname'])&&isset($_POST['psw'])){
    $username=$_POST['uname'];
    $password=sha1($_POST['psw']);
    $stmt=$conn->prepare("SELECT UserName from tbl_Admin where UserName=? and Password=?");
    $stmt->bind_param("ss",$username,$password);
    $stmt->execute();
    $result=$stmt->get_result();
    if($result->num_rows==1){
        //success login
        $_SESSION['username']="admin";
        //redirect to main
        header("Location: homepage.php");
        $stmt->close();
        $conn->close();
        exit(0);
    };

}
?>