<?php
		 require("phpsql_dbinfo.php");
		 require('phpsql_connect.php');
		 
while ($row = mysql_fetch_assoc($result)) {

			$icon_href = "http://maps.google.com/mapfiles/kml/paddle/ylw-blank.png";
			$bgcolor = "ffffffff";
			
		 $query = sprintf("UPDATE markers " .
             " SET icon_href = '%s', bgcolor = '%s' " . 
             " WHERE id = '%s' LIMIT 1;",
             mysql_real_escape_string($icon_href),
						 mysql_real_escape_string($bgcolor),
						 mysql_real_escape_string($id));
      $update_result = mysql_query($query);
			if (!$update_result) {
        die("Invalid query: " . mysql_error());
      }
			
}
mysql_free_result($result);
    print mysql_error();

error_reporting(E_ALL);
ini_set('display_errors', '1');
echo "<h1>The update of Icon_herf and bgcolor have fineshed with no errors</h1>";
?>