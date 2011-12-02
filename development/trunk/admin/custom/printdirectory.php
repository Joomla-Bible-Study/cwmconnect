<?php
require('phpsql_dbinfo.php');
require('phpsql_connect.php');
function groupit( $args ) 
{
	extract($args);

	$result = array();
	foreach ($items as $item) {
		if ( !empty($item[$field]) )
			$key = $item[$field];
		else
			$key = 'nomatch';
		if (array_key_exists($key, $result))
			$result[ $key ][] = $item;
		else {
			$result[ $key ] = array();
			$result[ $key ][] = $item;
		}
	}
	return $result;
}
