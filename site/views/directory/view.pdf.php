<?php
/**
 * Directory view for ChurchDirectory
 *
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

require_once JPATH_COMPONENT . '/models/category.php';
jimport('tcpdf.tcpdf');

/**
 * HTML Member View class for the ChurchDirectory component
 *
 * @property mixed document
 * @package  ChurchDirectory.Site
 * @since    1.7.0
 */
class ChurchDirectoryViewDirectory extends JViewLegacy
{

	/** Protected @var object */
	protected $state = null;

	/** Protected @var array */
	protected $items = null;

	/** Protected @var array */
	protected $category = null;

	/** Protected @var array */
	protected $categories = null;

	/**  Protected  @var array */
	protected $pagination = null;

	protected $span;

	protected $maxLevel;

	protected $params;

	protected $children;

	protected $parent;

	protected $header;

	protected $renderHelper;

	protected $count;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{
		$app    = JFactory::getApplication();
		$params = JComponentHelper::getParams('com_churchdirectory');

		// Get some data from the models
		$state    = $this->get('State');
		$items    = $this->get('Items');
		$category = $this->get('Category');

		// Check whether category access level allows access.
		$user   = JFactory::getUser();
		$groups = $user->getAuthorisedViewLevels();

		if (!in_array($category->access, $groups))
		{
			$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');

			return false;
		}

		if ($items == false)
		{
			$app->enqueueMessage(JText::_('COM_CHURCHDIRECTOY_ERROR_DIRECTORY_NOT_FOUND'), 'error');

			return false;
		}

		$this->count    = count($items);
		$this->subcount = count($items);
		$children       = $this->get('Children');
		$pagination     = $this->get('Pagination');
		$this->loadHelper('render');
		$renderHelper = new RenderHelper;
		$this->span   = $renderHelper->rowWidth($params->get('rows_per_page'));
		JLoader::register('DirectoryHeaderHelper', JPATH_SITE . '/components/com_churchdirectory/helpers/directoryheader.php');
		$this->header = new DirectoryHeaderHelper;
		$this->header->setPages($params);

		// Prepare the data.
		// Compute the contact slug.
		for ($i = 0, $n = $this->count; $i < $n; $i++)
		{
			$item       = & $items[$i];
			$item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
			$temp       = new Joomla\Registry\Registry;
			$temp->loadString($item->params);
			$item->params = clone($params);
			$item->params->merge($temp);

			if ($item->params->get('show_email', 0) == 1)
			{
				$item->email_to = trim($item->email_to);

				if (!empty($item->email_to) && JMailHelper::isEmailAddress($item->email_to))
				{
					$item->email_to = JHtml::_('email.cloak', $item->email_to);
				}
				else
				{
					$item->email_to = '';
				}
			}
			if ($item->params->get('dr_show_street_address') || $item->params->get('dr_show_suburb')
				|| $item->params->get('dr_show_state') || $item->params->get('dr_show_postcode') || $item->params->get('dr_show_country'))
			{
				$params->set('address_check', 1);
			}
			else
			{
				$params->set('address_check', 0);
			}
			if ($item->params->get('dr_show_email') || $item->params->get('dr_show_telephone')
				|| $item->params->get('dr_show_fax') || $item->params->get('dr_show_mobile')
				|| $item->params->get('dr_show_webpage') || $item->params->get('dr_show_spouse')
				|| $item->params->get('dr_show_children'))
			{
				$params->set('other_check', 1);
			}
			else
			{
				$params->set('other_check', 0);
			}

			switch ($params->get('dr_churchdirectory_icons'))
			{
				case 1 :
					// Text
					$params->set('marker_address', JText::_('COM_CHURCHDIRECTORY_ADDRESS') . ": ");
					$params->set('marker_email', JText::_('JGLOBAL_EMAIL') . ": ");
					$params->set('marker_telephone', JText::_('COM_CHURCHDIRECTORY_TELEPHONE') . ": ");
					$params->set('marker_fax', JText::_('COM_CHURCHDIRECTORY_FAX') . ": ");
					$params->set('marker_mobile', JText::_('COM_CHURCHDIRECTORY_MOBILE') . ": ");
					$params->set('marker_misc', JText::_('COM_CHURCHDIRECTORY_OTHER_INFORMATION') . ": ");
					$params->set('marker_class', 'jicons-text');
					break;

				case 2 :
					// None
					$params->set('marker_address', '');
					$params->set('marker_email', '');
					$params->set('marker_telephone', '');
					$params->set('marker_mobile', '');
					$params->set('marker_fax', '');
					$params->set('marker_misc', '');
					$params->set('marker_class', 'jicons-none');
					break;

				default :
					// Icons
					$image1 = JHtml::_('image', 'contacts/' . $params->get('icon_address', 'con_address.png'), JText::_('COM_CHURCHDIRECTORY_ADDRESS') .
							": ", null, true
					);
					$image2 = JHtml::_('image', 'contacts/' . $params->get('icon_email', 'emailButton.png'), JText::_('JGLOBAL_EMAIL') . ": ", null, true);
					$image3 = JHtml::_('image', 'contacts/' . $params->get('icon_telephone', 'con_tel.png'), JText::_('COM_CHURCHDIRECTORY_TELEPHONE') .
							": ", null, true
					);
					$image4 = JHtml::_('image', 'contacts/' . $params->get('icon_fax', 'con_fax.png'), JText::_('COM_CHURCHDIRECTORY_FAX') . ": ", null, true);
					$image5 = JHtml::_('image', 'contacts/' . $params->get('icon_misc', 'con_info.png'), JText::_('COM_CHURCHDIRECTORY_OTHER_INFORMATION') .
							": ", null, true
					);
					$image6 = JHtml::_('image', 'contacts/' . $params->get('icon_mobile', 'con_mobile.png'), JText::_('COM_CHURCHDIRECTORY_MOBILE') .
							": ", null, true
					);

					$params->set('marker_address', $image1);
					$params->set('marker_email', $image2);
					$params->set('marker_telephone', $image3);
					$params->set('marker_fax', $image4);
					$params->set('marker_misc', $image5);
					$params->set('marker_mobile', $image6);
					$params->set('marker_class', 'jicons-icons');
					break;
			}
		}

		$params->set('prepare_content', '0');

		// Setup the category parameters.
		$cparams          = $category->getParams();
		$category->params = clone($params);
		$category->params->merge($cparams);
		$children = array($category->id => $children);
		$maxLevel = $params->get('maxLevel', -1);
		$items    = RenderHelper::groupit(array('items' => & $items, 'field' => 'lname'));

		if (0)
		{
			foreach ($items as $s1)
			{
				$items[$s1] = RenderHelper::groupit(array('items' => $items[$s1], 'field' => 'suburb'));

			}
		}

		$this->renderHelper = $renderHelper;
		$this->maxLevel     = & $maxLevel;
		$this->state        = & $state;
		$this->items        = $items;
		$this->category     = & $category;
		$this->children     = & $children;
		$this->params       = & $params;
		$this->pagination   = & $pagination;

		// Escape strings for HTML output
		$this->pageclass_sfx = htmlspecialchars($params->get('pageclass_sfx'));

		$menus = $app->getMenu();
		$title = 'directory_prent_out';

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();

		// Clean the output buffer
		@ob_end_clean();

		// Create new PDF document
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		// Set document information
		$pdf->SetCreator('Nashville First SDA Church');
		$pdf->SetAuthor('NFSDA Church');
		$pdf->SetTitle($this->params->get('page_title', ''));
		$pdf->SetSubject('Church Directory');
		$pdf->SetKeywords('Directory, PDF, Members');

		// Remove default header/footer
		$pdf->setPrintHeader(true);
		$pdf->setPrintFooter(true);

		// Set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// Set margins
		$pdf->SetMargins(10, 10, 10);

		// Set auto page breaks
		$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

		// Set image scale factor
		$pdf->setImageScale(2.5);

		// ---------------------------------------------------------

		// Set font
		$pdf->SetFont('times', '', 9);

		// Add a page
		$pdf->AddPage();

		// Set some text to print
		$html = $this->loadTemplate($tpl);

		// Print a block of text using Write()
		$pdf->writeHTML($html, true, false, false, false, '');

		// ---------------------------------------------------------
		$pdf->lastPage();

		$jweb  = new JApplicationWeb;
		$jweb->clearHeaders();

		// Close and output PDF document
		$pdf->Output($title . '.pdf', 'I');

		return null;
	}

	/**
	 * ABC Links for bottom of page
	 *
	 * @return string
	 */
	public function abclinks()
	{
		$links = '<a href="#top"> Top </a>';
		$links .= '<a href="#A"> A </a>';
		$links .= '<a href="#B"> B </a>';
		$links .= '<a href="#C"> C </a>';
		$links .= '<a href="#D"> D </a>';
		$links .= '<a href="#E"> E </a>';
		$links .= '<a href="#F"> F </a>';
		$links .= '<a href="#G"> G </a>';
		$links .= '<a href="#H"> H </a>';
		$links .= '<a href="#I"> I </a>';
		$links .= '<a href="#J"> J </a>';
		$links .= '<a href="#K"> K </a>';
		$links .= '<a href="#L"> L </a>';
		$links .= '<a href="#M"> M </a>';
		$links .= '<a href="#N"> N </a>';
		$links .= '<a href="#O"> O </a>';
		$links .= '<a href="#P"> P </a>';
		$links .= '<a href="#Q"> Q </a>';
		$links .= '<a href="#R"> R </a>';
		$links .= '<a href="#S"> S </a>';
		$links .= '<a href="#T"> T </a>';
		$links .= '<a href="#U"> U </a>';
		$links .= '<a href="#V"> V </a>';
		$links .= '<a href="#W"> W </a>';
		$links .= '<a href="#X"> X </a>';
		$links .= '<a href="#Y"> Y </a>';
		$links .= '<a href="#Z"> Z </a>';
		$links .= '<a href="#bottom"> Bottom </a>';

		return $links;
	}

}
