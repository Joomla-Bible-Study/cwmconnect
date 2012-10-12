<?php
		 require("phpsql_dbinfo.php");
		 require('phpsql_connect.php');
$iconhref = 'http://maps.google.com/mapfiles/kml/paddle/blu-blank.png';
		 
while ($row = mysql_fetch_assoc($result)) {	

		require('phpsql_rowdf.php');	 
	 $dbdescription =
	 "<p>" .
         //"<b>Primary contact:<b/> &nbsp;". "$contact1fname" . "&nbsp;" . "$contact1lname" . "&nbsp;" . "ph: $contact1ph" .
	 //"<br /><b>Secondary Contact:</b> &nbsp;". "$contact2fname" . "&nbsp;" . "$contact2lname" . "&nbsp;" . "ph: $contact2ph" .
         //"<br /><b> Facility Tour Dates: <b/>". "&nbsp;" . "$tour_dates" .
	 "<br /> <br />". "$address" . "<br>" . "$address1" .
	 "<br />". "$town".",". "&nbsp;" . "$fid" . "&nbsp;" . "$zip" . "&nbsp;" . "$country" .
	 "<br />". "Ph:". "&nbsp;" . "$officeph" .
	 "<br />". "Fax:". "&nbsp;" . "$officefax" .
	 "<br />".
	 "<br />". "<b> Web Site: <b/>" ."&nbsp;". "$wsite" . "<p/>" .
        //"<br /> Here are the Asset Teams that will be touring this facility:" . "$assteam"; 

    $updatedescription =
		 "<table width='400' border='0' cellpadding='5' cellspacing='0' bgcolor='#CCFFCC'>" .
		 "<tr><td align='right'>".
		 "<img src='http://earth.google.com/outreach/images/spreadsheet_karlovy_most.jpg' alt='picture' width='400'  /><br />" .
		 "<tr><td><h2><font color='#333399'>" . "$name" . "</font></h2></td></tr><tr><td>" .
		 "<blockquote>" .
 		 "<font color='#000000'>" .
 		 "<p>". "$dbdescription" . "</p>". 
 		 "</font>" .
 		 "</blockquote>" .
 		 "</td></tr>" .
 		 "<tr><td>&nbsp;</td></tr>" .
 		 "<tr><td>" .
 		 "<table width='100%' border='0' cellpadding='0' cellspacing='0'><tr>" .
 		 "<td width='99%' align='right' valign='middle'>" .
 		 "<table border='0' cellpadding='0' cellspacing='0'><tr>" .
 		 "<td align='right' valign='top'><h2>" .
 		 "<font color='#333399'>". "$name" ."</font></h2></td>" .
		 "</tr><tr align='right' valign='top'><td>" .
		 "<a href='http://". "$deloitte_url" . "'>". "$deloitte_url". "</a></td>" .
 		 "</tr></table></td><td width='1%' align='right' valign='middle'>" .
 		 "<a href='http://" . "$dsllc_url" . "'>" .
 		 "<img src='http://" . "$dsllc_img" . "' border='0' alt='org logo' />" .
 		 "</a></td></tr></table></td></tr></table>";
		 
		 $query = sprintf("UPDATE markers " .
             " SET description = '%s', icon_href = '%s' " . 
             " WHERE id = '%s' LIMIT 1;",
             mysql_real_escape_string($updatedescription),
						 mysql_real_escape_string($iconhref),
						 mysql_real_escape_string($id));
      $update_result = mysql_query($query);
			if (!$update_result) {
        die("Invalid query: " . mysql_error());
      }	
}
echo "<h1>The update of Descriptoin have fineshed with no errors</h1>";

mysql_free_result($result);
    print mysql_error();

error_reporting(E_ALL);
ini_set('display_errors', '1');
?>
