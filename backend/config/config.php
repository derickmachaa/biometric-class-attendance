<?php

//This file contains the global webstie configurations

//the root dir
define('ROOT', __DIR__ .'/../');

//database configs
define('DB_HOST','localhost'); //mysql host
define('DB_PORT',3306);//mysql port
define('DB_NAME','online_attendance');//database name
define('DB_USER','derick');
define('DB_PASS','derick');
define('API_AUTH','86b5ce8802f2a8686aa5f54dd877ddad');
//end configs

//set the default time zone for a better clock
date_default_timezone_set('Africa/Nairobi');
?>
