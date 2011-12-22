<?php
 $username = 'root'; // User Name
 $password = '123456'; // Password
 $database = 'joomladev'; // databes to connect to
 $table = 'y5bgo_churchdirectory_details';
 $server = 'localhost'; //server to connect to
 $key = 'ABQIAAAA2KlUERY5udeiRITzQu-ChhR4PgILFleQ_YNts3dXMlUtOL449RQt0FiAZ1peZvSM6Jff-SNXbj-ulQ'; // Google Map API Key
 $maps = 'maps.google.com';
 $nfsda_url = 'http://www.nfsda.org';
 $nfsda_img = 'http://www.nfsda.org/custom/logo.png';

 // Connecting to mysql
$conn = mysql_connect("$server", "$username", "$password");
// Connection error checking
if (!$conn) {
    echo "Unable to connect to DB: " . mysql_error();
    exit;
}
// Solecting Database with error System
if (!mysql_select_db("joomladev")) {
    echo "Unable to select mydbname: " . mysql_error();
    exit;
}
//Solecting Table info
$sql = "SELECT *
        FROM   y5bgo_churchdirectory_details
        WHERE  1";
$result = mysql_query($sql);

if (!$result) {
    echo "Could not successfully run query ($sql) from DB: " . mysql_error();
    exit;
}

if (mysql_num_rows($result) == 0) {
    echo "No rows found, nothing to print so am exiting";
    exit;
}