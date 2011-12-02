<?php
/**
 * QContacts Contact manager component for Joomla! 1.5
 *
 * @version 1.0.6
 * @package qcontacts
 * @author Massimo Giagnoni
 * @copyright Copyright (C) 2008 Massimo Giagnoni. All rights reserved.
 * @copyright Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
 /*
This file is part of QContacts.
QContacts is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
defined('_JEXEC') or die('Restricted access');

jimport('bitfolge.vcard');

class JvCard extends vCard {
	// needed to fix bug in vcard class
	function setName( $family='', $first='', $additional='', $prefix='', $suffix='' ) {
		$this->properties["N"] 	= "$family;$first;$additional;$prefix;$suffix";
		$this->setFormattedName( trim( "$prefix $first $additional $family $suffix" ) );
	}

	// needed to fix bug in vcard class
	function setAddress( $postoffice='', $extended='', $street='', $city='', $region='', $zip='', $country='', $type='HOME;POSTAL' ) {
		// $type may be DOM | INTL | POSTAL | PARCEL | HOME | WORK or any combination of these: e.g. "WORK;PARCEL;POSTAL"
		$separator = ';';

		$key 		= 'ADR';
		if ( $type != '' ) {
			$key	.= $separator . $type;
		}
		$key.= ';ENCODING=QUOTED-PRINTABLE';

		$return = encode( $postoffice );
		$return .= $separator . encode( $extended );
		$return .= $separator . encode( $street );
		$return .= $separator . encode( $city );
		$return .= $separator . encode( $region);
		$return .= $separator . encode( $zip );
		$return .= $separator . encode( $country );

		$this->properties[$key] = $return;
	}

	// added ability to set filename
	function setFilename( $filename ) {
		$this->filename = $filename .'.vcf';
	}

	// added ability to set position/title
	function setTitle( $title ) {
		$title 	= trim( $title );

		$this->properties['TITLE'] 	= $title;
	}

	// added ability to set organisation/company
	function setOrg( $org ) {
		$org 	= trim( $org );

		$this->properties['ORG'] = $org;
	}

	function getVCard( $sitename ) {
		$text 	= 'BEGIN:VCARD';
		$text	.= "\r\n";
		$text 	.= 'VERSION:2.1';
		$text	.= "\r\n";

		foreach( $this->properties as $key => $value ) {
			$text	.= "$key:$value";
			$text	.= "\r\n";
		}
		$text	.= 'REV:'. date( 'Y-m-d' ) .'T'. date( 'H:i:s' ). 'Z';
		$text	.= "\r\n";
		$text	.= 'MAILER: Joomla! vCard for '. $sitename;
		$text	.= "\r\n";
		$text	.= 'END:VCARD';
		$text	.= "\r\n";

		return $text;
	}
}