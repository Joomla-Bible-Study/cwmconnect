<?php
require('phpsql_dbinfo.php');
require('phpsql_connect.php');

// Initialize delay in geocode speed
$delay = 0;
$base_url = "http://" . "$maps" . "/maps/geo?output=xml" . "&key=" . "$key";

// Iterate through the rows, geocoding each address
while ($row = mysql_fetch_assoc($result)) {
  $geocode_pending = true;

    while ($geocode_pending) {
    // Defining of Rows to look up
		require('phpsql_rowdf.php');
    	$request_url = $base_url . "&q=" . urlencode("$address". "," . " " . "$suburb". "," . "$state" . " " . "$zip" . " " . "$country");
	$xml = simplexml_load_file($request_url) or die("url not loading");

	$status = $xml->Response->Status->code;
    	if (strcmp($status, "200") == 0) {
      		// Successful geocode
      		$geocode_pending = false;
      		$coordinates = $xml->Response->Placemark->Point->coordinates;
      		$coordinatesSplit = split(",", $coordinates);
      		// Format: Longitude, Latitude, Altitude
      		$ulat = $coordinatesSplit[1];
      		$ulong = $coordinatesSplit[0];

      $query = sprintf("UPDATE $table " .
             " SET lat = '%s', lng = '%s'" . 
             " WHERE id = '%s' LIMIT 1;",
             mysql_real_escape_string($ulat),
             mysql_real_escape_string($ulong),
             mysql_real_escape_string($id));
      $update_result = mysql_query($query);
      if (!$update_result) {
        die("Invalid query: " . mysql_error());
	}
    } else if (strcmp($status, "620") == 0) {
      // sent geocodes too fast
      $delay += 100000;
    } else {
      //failure to geocode
      $geocode_pending = false;
      echo "Name: " . $name . "<br />";
      echo $xml . "<br />";
      echo "Address " . $address . " failed to geocoded.<br /> ";
      echo "Received status " . $status . " \n <br /><br />";
    }
    usleep($delay);
  }
}
echo '<p><a href="https://www.nfsda.org/administrator/index.php?option=com_qcontacts"> All Done</a></p>';
?>