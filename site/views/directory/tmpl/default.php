<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
$this->printed_items = (int) 0;
$this->printed_rows  = (int) 0;
$this->letter        = null;
$this->rows_per_page = (int) $this->params->get('rows_per_page');
$this->items_per_row = (int) $this->params->get('items_per_row');

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers');

/** @var $this ChurchDirectoryViewDirectory */
?>
<?php
// Add a page
$this->loadTemplate('firstpages');

// Add a page
$this->pdf->AddPage();

foreach ($this->items as $s1 => $sort1)
{
	$this->items = $sort1;

	$html = $this->loadTemplate('items');

	// Print a block of text using Write()
	$this->pdf->WriteHTML($html);
}

$this->loadTemplate('lastpage');

