<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * */
defined('_JEXEC') or die;

/** @var mPDF $pdf */
$pdf = $this->pdf;

foreach ($this->header->footer as $footer)
{
	$pdf->AddPage();

	// Set a bookmark for the current position
	$pdf->Bookmark($footer->name, 0);
	$pdf->WriteHTML($footer->html);
}
