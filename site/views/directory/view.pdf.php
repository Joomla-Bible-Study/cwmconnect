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

/**
 * HTML Member View class for the ChurchDirectory component
 *
 * @property mixed document
 * @package  ChurchDirectory.Site
 * @since    1.7.0
 */
class ChurchDirectoryViewDirectory extends JViewLegacy
{
	/**
	 * Protected @var object
	 *
	 * @since       1.7.2
	 */
	protected $state = null;

	/**
	 * Protected @var array
	 *
	 * @since       1.7.2
	 */
	protected $items = null;

	/**
	 * Protected @var array
	 *
	 * @since       1.7.2
	 */
	protected $category = null;

	/**
	 * Protected @var array
	 *
	 * @since       1.7.2
	 */
	protected $categories = null;

	/**
	 * Protected  @var array
	 *
	 * @since       1.7.2
	 */
	protected $pagination = null;

	protected $span;

	protected $maxLevel;

	protected $params;

	protected $children;

	protected $parent;

	protected $header;

	/** @var  ChurchDirectoryRenderHelper */
	protected $renderHelper;

	protected $count;

	public $items_per_row;

	public $rows_per_page;

	/**
	 * @var  \Mpdf\Mpdf
	 * @since 1.7.4
	 */
	protected $pdf;

	public $baseurl;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 *
	 * @since   1.7.2
	 * @throws  Exception
	 */
	public function display($tpl = null)
	{
		// @todo Move to Render Helper as much as possible. This will allow for backend creation. May look at extending a model and view from the back side.
		$app    = JFactory::getApplication();
		$params = JComponentHelper::getParams('com_churchdirectory');

		// Get some data from the models
		$state    = $this->get('State');
		$items    = $this->get('Items');
		$category = $this->get('Category');

		$this->renderHelper = new ChurchDirectoryRenderHelper;

		JFactory::getApplication()->clearHeaders();

		$this->baseurl = JUri::base();

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
		$renderHelper = new ChurchDirectoryRenderHelper;
		$this->span   = $renderHelper->rowWidth($params->get('rows_per_page'));

		// Prepare the data.
		// Compute the contact slug.
		for ($i = 0, $n = $this->count; $i < $n; $i++)
		{
			$item       = & $items[$i];
			$item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
			$temp       = new Joomla\Registry\Registry;
			$temp->loadString($item->params);
			$item->params = clone $params;
			$item->params->merge($temp);

			if ($item->params->get('show_email', 0) == 1)
			{
				$item->email_to = trim($item->email_to);

				if (!empty($item->email_to) && JMailHelper::isEmailAddress($item->email_to))
				{
					$item->email_to = '<a href="mailto::' . $item->email_to . '">' . $item->email_to . '</a>';
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
					if ($params->get('icon_address'))
					{
						$image1 = JHtml::_('image', $params->get('icon_address', 'con_address.png'), JText::_('COM_CONTACT_ADDRESS') . ': ', null, false);
					}
					else
					{
						$image1 = JHtml::_('image', JUri::base() . 'media/contacts/images/' .
							$params->get('icon_address', 'con_address.png'), JText::_('COM_CONTACT_ADDRESS') . ': ', null, true
						);
					}

					if ($params->get('icon_email'))
					{
						$image2 = JHtml::_('image', $params->get('icon_email', 'emailButton.png'), JText::_('JGLOBAL_EMAIL') . ': ', null, false);
					}
					else
					{
						$image2 = JHtml::_('image', JUri::base() . 'media/contacts/images/' .
							$params->get('icon_email', 'emailButton.png'), JText::_('JGLOBAL_EMAIL') . ': ', null, true
						);
					}

					if ($params->get('icon_telephone'))
					{
						$image3 = JHtml::_('image', $params->get('icon_telephone', 'con_tel.png'), JText::_('COM_CONTACT_TELEPHONE') . ': ', null, false);
					}
					else
					{
						$image3 = JHtml::_('image', JUri::base() . 'media/contacts/images/' .
							$params->get('icon_telephone', 'con_tel.png'), JText::_('COM_CONTACT_TELEPHONE') . ': ', null, true
						);
					}

					if ($params->get('icon_fax'))
					{
						$image4 = JHtml::_('image', $params->get('icon_fax', 'con_fax.png'), JText::_('COM_CONTACT_FAX') . ': ', null, false);
					}
					else
					{
						$image4 = JHtml::_('image', JUri::base() . 'media/contacts/images/' .
							$params->get('icon_fax', 'con_fax.png'), JText::_('COM_CONTACT_FAX') . ': ', null, true
						);
					}

					if ($params->get('icon_misc'))
					{
						$image5 = JHtml::_('image', $params->get('icon_misc', 'con_info.png'), JText::_('COM_CONTACT_OTHER_INFORMATION') . ': ', null, false);
					}
					else
					{
						$image5 = JHtml::_('image', JUri::base() . 'media/contacts/images/' .
							$params->get('icon_misc', 'con_info.png'), JText::_('COM_CONTACT_OTHER_INFORMATION') . ': ', null, true
						);
					}

					if ($params->get('icon_mobile'))
					{
						$image6 = JHtml::_('image', $params->get('icon_mobile', 'con_mobile.png'), JText::_('COM_CONTACT_MOBILE') . ': ', null, false);
					}
					else
					{
						$image6 = JHtml::_('image', JUri::base() . 'media/contacts/images/' .
							$params->get('icon_mobile', 'con_mobile.png'), JText::_('COM_CONTACT_MOBILE') . ': ', null, true
						);
					}

					$params->set('marker_address',   $image1);
					$params->set('marker_email',     $image2);
					$params->set('marker_telephone', $image3);
					$params->set('marker_fax',       $image4);
					$params->set('marker_misc',      $image5);
					$params->set('marker_mobile',    $image6);
					$params->set('marker_class',     'jicons-icons');
					break;
			}
		}

		$params->set('prepare_content', '0');

		// Setup the category parameters.
		$cparams          = $category->getParams();
		$category->params = clone $params;
		$category->params->merge($cparams);
		$children = [$category->id => $children];
		$maxLevel = $params->get('maxLevel', -1);
		$items    = $renderHelper->groupit(['items' => & $items, 'field' => 'lname']);

		if (0)
		{
			foreach ($items as $s1)
			{
				$items[$s1] = $renderHelper->groupit(['items' => $items[$s1], 'field' => 'suburb']);
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

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();
		$title = $menu->title;

		// Clean the output buffer
		@ob_end_clean();

		require_once JPATH_ROOT . '/libraries/mpdf/vendor/autoload.php';

		// Create new PDF document
		$this->pdf = new \Mpdf\Mpdf(['tempDir' => JPATH_SITE . '/tmp']);

		// Double-side document - mirror margins
		$this->pdf->mirrorMargins = true;
		$this->pdf->setFooter('{DATE j-m-Y}| |{PAGENO}');

		// Set document information
		$this->pdf->SetCreator($app->getName());
		$this->pdf->SetAuthor('Church Directory Creater');
		$this->pdf->SetTitle($this->params->get('page_title', 'Printable Directory'));
		$this->pdf->SetSubject('Church Directory');
		$this->pdf->SetKeywords('Directory, PDF, Members');

		JLoader::register('DirectoryHeaderHelper', JPATH_SITE . '/components/com_churchdirectory/helpers/directoryheader.php');
		$this->header = new DirectoryHeaderHelper;
		$this->header->setPages($params);

		// Set some text to print
		$this->loadTemplate($tpl);

		$jweb  = new JApplicationWeb;
		$jweb->clearHeaders();

		// @todo need to look at moving the pramitoers to the componet config. This will allow for options.
		$this->pdf->SetProtection(array('copy','print'), '', $this->renderHelper->random_password(24));

		// Close and output PDF document
		$this->pdf->Output($title . date('Ymd') . '.pdf', 'I');

		return null;
	}
}
