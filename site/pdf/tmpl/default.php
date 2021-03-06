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
$this->pdf->AddPage();
$html = $this->loadTemplate('firstpages');
$this->pdf->writeHTML($html, true, 0, true, true);

?>
	//    foreach ($this->items as $s1 => $sort1)
	//    {
	//        if(isset($this->pdf))
	//        {
	//            // Add a page
	//            $this->pdf->AddPage();
	//        }
	//
	//        $this->items = $sort1;
	//        $html = $this->loadTemplate('items');
	//
	//        if(isset($this->pdf))
	//        {
	//            // Print a block of text using Write()
	//            $this->pdf->writeHTML($html, true, 0, true, true);
	//
	//            // ---------------------------------------------------------
	//            $this->pdf->lastPage();
	//        }
	//        else
	//        {
	//            //echo $html;
	//        }
	//    }
<?php
$this->pdf->AddPage();
$html = $this->loadTemplate('lastpage');
$this->pdf->writeHTML($html, true, 0, true, true);

