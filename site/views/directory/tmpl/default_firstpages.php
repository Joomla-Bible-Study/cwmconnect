<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * */
defined('_JEXEC') or die;

/** @var TCPDF $pdf */
$pdf = $this->pdf;
?>
<?php $html = $this->pageclass_sfx;

if ($this->params->get('dr_show_page_title', 1))
{
	$html .= "<h1>" . $this->escape($this->params->get('page_heading')) . "</h1>";
}

if ($this->params->get('dr_show_description'))
{
	// If there is a description in the menu parameters use that;
	if ($this->params->get('categories_description'))
	{
		$html .= "<div class='category-desc base-desc'>" .
			JHtml::_('content.prepare', $this->params->get('categories_description')) .
		"</div>";
	}
}
$pdf->writeHTML($html, true, false, true, true);

foreach ($this->header->header as $header)
{
	$pdf->AddPage();
	$pdf->SetFont('times', 'B', 20);

	// Set a bookmark for the current position
	$pdf->Bookmark($header->name, 0, 0, '', 'B', [0, 64, 128]);

	// Print a line using Cell()
	$pdf->Cell(0, 25, $header->name, 5, 1, 'L');
	$pdf->SetFont('times', 'BI', 10, '', 'false');
	$pdf->writeHTML($header->html, true, false, true, true);
}
