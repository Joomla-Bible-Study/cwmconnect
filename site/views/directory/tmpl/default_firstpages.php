<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * */
defined('_JEXEC') or die;

/** @var mPDF $pdf */
$pdf = $this->pdf;

$stylesheet = '<style>' . file_get_contents(JPATH_ROOT . '/media/com_churchdirectory/css/churchdirectory.css') . '</style>';
$pdf->WriteHTML($stylesheet, 1);

foreach ($this->header->header as $header)
{
	$pdf->AddPage();

	// $pdf->SetFont('times', 'B', 20);

	// Set a bookmark for the current position
	$pdf->Bookmark($header->name, 0);

	$html = $header->html;

	// Print a line using Cell()
	$pdf->WriteHTML($html);
}
