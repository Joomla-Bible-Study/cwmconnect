<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2014 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @see        Akeebe Script
 */

defined('_JEXEC') or die;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 * Class for install Script
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class Com_ChurchdirectoryInstallerScript
{

	/** @var string The component's name */
	protected $churchdirectory_extension = 'com_churchdirectory';

	/**
	 * The list of extra modules and plugins to install
	 *
	 * @author Nicholas K. Dionysopoulos
	 * @var   array $_installation_queue Array of Items to install
	 */
	private $_installation_queue = array(
		// -- modules => { (folder) => { (module) => { (position), (published) } }* }*
		'modules' => array(
			'admin' => array(),
			'site'  => array('birthdayanniversary' => 0,),
		),
		// -- plugins => { (folder) => { (element) => (published) }* }*
		'plugins' => array(
			'finder' => array('churchdirectory' => 1,),
			'search' => array('churchdirectory' => 0,),
		),
	);

	/**
	 * Variables to set default params
	 *
	 * @var   array  $_param_array  Array of default settings
	 */
	private $_param_array = array(
		'protectedaccess'               => "8",
		"churchdirectory_layout"        => "_:default",
		"show_churchdirectory_category" => "hide",
		"show_churchdirectory_list"     => "0",
		"presentation_style"            => "sliders",
		"show_address_full"             => "0",
		"show_name"                     => "1",
		"show_position"                 => "1",
		"show_email"                    => "1",
		"show_street_address"           => "1",
		"show_suburb"                   => "1",
		"show_state"                    => "1",
		"show_postcode"                 => "1",
		"show_country"                  => "1",
		"show_telephone"                => "1",
		"show_mobile"                   => "1",
		"show_fax"                      => "1",
		"show_webpage"                  => "1",
		"show_misc"                     => "1",
		"show_image"                    => "1",
		"image"                         => "",
		"allow_vcard"                   => "0",
		"show_articles"                 => "1",
		"show_profile"                  => "1",
		"show_links"                    => "1",
		"linka_name"                    => "",
		"linkb_name"                    => "",
		"linkc_name"                    => "",
		"linkd_name"                    => "",
		"linke_name"                    => "",
		"churchdirectory_icons"         => "0",
		"icon_address"                  => "",
		"icon_email"                    => "",
		"icon_telephone"                => "",
		"icon_mobile"                   => "",
		"icon_fax"                      => "",
		"icon_misc"                     => "",
		"category_layout"               => "_:default",
		"show_category_title"           => "1",
		"show_description"              => "1",
		"show_description_image"        => "0",
		"maxLevel"                      => "-1",
		"show_empty_categories"         => "0",
		"show_subcat_desc"              => "1",
		"show_cat_items"                => "1",
		"show_base_description"         => "1",
		"maxLevelcat"                   => "-1",
		"show_empty_categories_cat"     => "0",
		"show_subcat_desc_cat"          => "1",
		"show_cat_items_cat"            => "1",
		"show_pagination_limit"         => "1",
		"show_headings"                 => "1",
		"show_position_headings"        => "1",
		"show_email_headings"           => "1",
		"show_telephone_headings"       => "1",
		"show_mobile_headings"          => "0",
		"show_fax_headings"             => "0",
		"show_suburb_headings"          => "0",
		"show_state_headings"           => "0",
		"show_country_headings"         => "0",
		"show_pagination"               => "2",
		"show_pagination_results"       => "1",
		"initial_sort"                  => "ordering",
		"directory_layout"              => "_:default",
		"dr_presentation_style"         => "sliders",
		"dr_churchdirectory_icons"      => "0",
		"dr_show_address_full"          => "0",
		"show_page_title"               => "1",
		"dr_show_position"              => "1",
		"dr_show_email"                 => "1",
		"dr_show_street_address"        => "1",
		"dr_show_suburb"                => "1",
		"dr_show_state"                 => "1",
		"dr_show_postcode"              => "1",
		"dr_show_country"               => "1",
		"dr_show_telephone"             => "1",
		"dr_show_mobile"                => "1",
		"dr_show_fax"                   => "1",
		"dr_show_webpage"               => "1",
		"dr_show_misc"                  => "1",
		"dr_show_spouse"                => "1",
		"dr_show_children"              => "1",
		"dr_show_image"                 => "1",
		"dr_image"                      => "",
		"dr_allow_kml"                  => "1",
		"dr_show_articles"              => "1",
		"dr_show_profile"               => "1",
		"dr_show_links"                 => "1",
		"dr_linka_name"                 => "",
		"dr_linkb_name"                 => "",
		"dr_linkc_name"                 => "",
		"dr_linkd_name"                 => "",
		"dr_linke_name"                 => "",
		"rows_per_page"                 => "3",
		"items_per_row"                 => "2",
		"dinitial_sort"                 => "lname",
		"dr_show_debug"                 => "0",
		"show_email_form"               => "1",
		"show_email_copy"               => "1",
		"banned_email"                  => "",
		"banned_subject"                => "",
		"banned_text"                   => "",
		"validate_session"              => "1",
		"custom_reply"                  => "0",
		"redirect"                      => "",
		"show_feed_link"                => "1",
		"sef_advanced_link"             => "0",
	);

	/**
	 * Joomla! pre-flight event
	 *
	 * @param   string      $type    is the type of change (install, update or discover_install, not uninstall).
	 * @param   JInstaller  $parent  is the class calling this method.
	 *
	 * @return boolean
	 */
	public function preflight($type, $parent)
	{
		// Only allow to install on Joomla! 3.4.4 or later with PHP 5.3.0 or later
		if (defined('PHP_VERSION'))
		{
			$version = PHP_VERSION;
		}
		elseif (function_exists('phpversion'))
		{
			$version = phpversion();
		}
		else
		{
			$version = '5.0.0'; // All bets are off!
		}
		if (!version_compare(JVERSION, '3.4.4', 'ge'))
		{
			$msg = "<p>You need Joomla! 2.5.16 or later to install this component</p>";
			new Exception($msg, 100);

			return false;
		}
		if (!version_compare($version, '5.3.10', 'ge'))
		{
			$msg = "<p>You need PHP 5.3.10 or later to install this component</p>";
			JLog::add($msg, JLog::WARNING, 'jerror');

			return false;
		}

		return true;
	}

	/**
	 * Runs after install, update or discover_update
	 *
	 * @param   string      $type    is the type of change (install, update or discover_install, not uninstall).
	 * @param   JInstaller  $parent  is the class calling this method.
	 *
	 * @return boolean
	 */
	public function postflight($type, $parent)
	{

		// Install subextensions
		$status = $this->_installSubextensions($parent);

		// Install TCPDF Libraries
		$this->_installTCPDF($parent);

		// Remove old stuff
		$this->deleteUnexistingFiles();

		// Show the post-installation page
		$this->_renderPostInstallation($status, $parent);

		// Clear FOF's cache
		if (!defined('FOF_INCLUDED'))
		{
			@include_once JPATH_LIBRARIES . '/fof/include.php';
		}

		if (defined('FOF_INCLUDED'))
		{
			FOFPlatform::getInstance()->clearCache();
		}

		return true;
	}

	/**
	 * Install is run on every new install.
	 *
	 * @param   JInstaller  $parent  is the class calling this method.
	 *
	 * @return void
	 */
	public function install($parent)
	{
		// Set params
		$this->setParams();

		// Install Default DB
		$this->setDefaultDB();
	}

	/**
	 * Uninstall runs before any other action is taken (file removal or database processing)
	 *
	 * @param   JInstaller  $parent  is the class calling this method.
	 *
	 * @return void
	 */
	public function uninstall($parent)
	{
		// Uninstall subextensions
		$status = $this->_uninstallSubextensions($parent);

		// Show the post-uninstallation page
		$this->_renderPostUninstallation($status, $parent);
	}

	/**
	 * Renders the post-installation message
	 *
	 * @param   object      $status  ?
	 * @param   JInstaller  $parent  is the class calling this method.
	 *
	 * @since 1.7.4
	 * @return void
	 *
	 * @todo  need to add verion check system.
	 */
	private function _renderPostInstallation($status, $parent)
	{
		$rows = 1; ?>
		<img src="../media/com_churchdirectory/images/icons/icon-48-churchdirectory.png" width="48" height="48"
		     alt="ChurchDirectory"/>

		<h2>Welcome to Church Directory System</h2>

		<table class="adminlist">
			<thead>
			<tr>
				<th class="title" colspan="2">Extension</th>
				<th style="width: 30%"><?php echo JTEXT::_('COM_CHURCHDIRECTORY_STATUS'); ?></th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<td colspan="3"></td>
			</tr>
			</tfoot>
			<tbody>
			<tr class="row0">
				<td class="key" colspan="2"><?php echo JTEXT::_('COM_CHURCHDIRECTORY_COMPONENT'); ?></td>
				<td><strong style="color: green"><?php echo JTEXT::_('COM_CHURCHDIRECTORY_INSTALLED'); ?></strong></td>
			</tr>
			<?php if (count($status->modules)) : ?>
				<tr>
					<th>Module</th>
					<th>Client</th>
					<th></th>
				</tr>
				<?php foreach ($status->modules as $module) : ?>
					<tr class="row<?php echo($rows++ % 2); ?>">
						<td class="key"><?php echo $module['name']; ?></td>
						<td class="key"><?php echo ucfirst($module['client']); ?></td>
						<td>
							<strong style="color: <?php echo ($module['result']) ? "green" : "red" ?>">
								<?php echo ($module['result']) ? JTEXT::_('COM_CHURCHDIRECTORY_INSTALLED') : JTEXT::_('COM_CHURCHDIRECTORY_NOT_INSTALLED'); ?>
							</strong>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			<?php if (count($status->plugins)) : ?>
				<tr>
					<th>Plugin</th>
					<th>Group</th>
					<th></th>
				</tr>
				<?php foreach ($status->plugins as $plugin) : ?>
					<tr class="row<?php echo($rows++ % 2); ?>">
						<td class="key"><?php echo ucfirst($plugin['name']); ?></td>
						<td class="key"><?php echo ucfirst($plugin['group']); ?></td>
						<td>
							<strong style="color: <?php echo ($plugin['result']) ? "green" : "red" ?>">
								<?php echo ($plugin['result']) ? JTEXT::_('COM_CHURCHDIRECTORY_INSTALLED') : JTEXT::_('COM_CHURCHDIRECTORY_NOT_INSTALLED'); ?>
							</strong>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>

		<fieldset>
			<p></p>
		</fieldset>
	<?php
	}

	/**
	 * Render Post Uninstallation
	 *
	 * @param   object      $status  ?
	 * @param   JInstaller  $parent  is the class calling this method.
	 *
	 * @return void
	 */
	private function _renderPostUninstallation($status, $parent)
	{
		?>
		<?php $rows = 0; ?>
		<h2><?php echo JText::_('COM_CHURCHDIRECTORY_UNINSTALL'); ?></h2>
		<table class="adminlist">
			<thead>
			<tr>
				<th class="title" colspan="2"><?php echo JText::_('COM_CHURCHDIRECTORY_EXTENSION'); ?></th>
				<th width="30%"><?php echo JText::_('COM_CHURCHDIRECTORY_STATUS'); ?></th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<td colspan="3"></td>
			</tr>
			</tfoot>
			<tbody>
			<tr class="row0">
				<td class="key" colspan="2"><?php echo JText::_('COM_CHURCHDIRECTORY'); ?></td>
				<td><strong style="color: green"><?php echo JText::_('COM_CHURCHDIRECTORY_REMOVED'); ?></strong></td>
			</tr>
			<?php if (count($status->modules)) : ?>
				<tr>
					<th><?php echo JText::_('COM_CHURCHDIRECTORY_MODULE'); ?></th>
					<th><?php echo JText::_('COM_CHURCHDIRECTORY_CLIENT'); ?></th>
					<th></th>
				</tr>
				<?php foreach ($status->modules as $module) : ?>
					<tr class="row<?php echo(++$rows % 2); ?>">
						<td class="key"><?php echo $module['name']; ?></td>
						<td class="key"><?php echo ucfirst($module['client']); ?></td>
						<td><strong
								style="color: <?php echo ($module['result']) ? "green" : "red" ?>"><?php echo ($module['result']) ? JText::_('COM_CHURCHDIRECTORY_REMOVED') : JText::_('COM_CHURCHDIRECTORY_NOT_REMOVED'); ?></strong>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			<?php if (count($status->plugins)) : ?>
				<tr>
					<th><?php echo JText::_('Plugin'); ?></th>
					<th><?php echo JText::_('Group'); ?></th>
					<th></th>
				</tr>
				<?php foreach ($status->plugins as $plugin) : ?>
					<tr class="row<?php echo(++$rows % 2); ?>">
						<td class="key"><?php echo ucfirst($plugin['name']); ?></td>
						<td class="key"><?php echo ucfirst($plugin['group']); ?></td>
						<td><strong style="color: <?php echo ($plugin['result']) ? "green" : "red" ?>">
								<?php echo ($plugin['result']) ? JText::_('COM_CHURCHDIRECTORY_REMOVED') : JText::_('COM_CHURCHDIRECTORY_NOT_REMOVED'); ?>
							</strong>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	<?php
	}

	/**
	 * Installs subextensions (modules, plugins) bundled with the main extension
	 *
	 * @param   JInstaller  $parent  is the class calling this method.
	 *
	 * @return JObject The subextension installation status
	 */
	private function _installSubextensions($parent)
	{
		$src = $parent->getParent()->getPath('source');

		$db = JFactory::getDbo();

		$status          = new stdClass;
		$status->modules = array();
		$status->plugins = array();

		// Modules installation
		if (count($this->_installation_queue['modules']))
		{
			foreach ($this->_installation_queue['modules'] as $folder => $modules)
			{
				if (count($modules))
				{
					foreach ($modules as $module => $modulePreferences)
					{
						// Install the module
						if (empty($folder))
						{
							$folder = 'site';
						}
						$path = "$src/modules/$folder/$module";

						if (!is_dir($path))
						{
							$path = "$src/modules/$folder/mod_$module";
						}
						if (!is_dir($path))
						{
							$path = "$src/modules/$module";
						}
						if (!is_dir($path))
						{
							$path = "$src/modules/mod_$module";
						}
						if (!is_dir($path))
						{
							continue;
						}

						// Was the module already installed?
						$sql = $db->getQuery(true)->select('COUNT(*)')
							->from('#__modules')
							->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
						$db->setQuery($sql);
						$count             = $db->loadResult();
						$installer         = new JInstaller;
						$result            = $installer->install($path);
						$status->modules[] = array(
							'name'   => 'mod_' . $module,
							'client' => $folder,
							'result' => $result
						);

						// Modify where it's published and its published state
						if (!$count)
						{
							// A. Position and state
							list($modulePosition, $modulePublished) = $modulePreferences;

							if ($modulePosition == 'cpanel')
							{
								$modulePosition = 'icon';
							}
							$sql = $db->getQuery(true)
								->update($db->qn('#__modules'))
								->set($db->qn('position') . ' = ' . $db->q($modulePosition))
								->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));

							if ($modulePublished)
							{
								$sql->set($db->qn('published') . ' = ' . $db->q('1'));
							}
							$db->setQuery($sql);
							$db->execute();

							// B. Change the ordering of back-end modules to 1 + max ordering
							if ($folder == 'admin')
							{
								$query = $db->getQuery(true);
								$query->select('MAX(' . $db->qn('ordering') . ')')
									->from($db->qn('#__modules'))
									->where($db->qn('position') . '=' . $db->q($modulePosition));
								$db->setQuery($query);
								$position = $db->loadResult();
								$position++;

								$query = $db->getQuery(true);
								$query->update($db->qn('#__modules'))
									->set($db->qn('ordering') . ' = ' . $db->q($position))
									->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
								$db->setQuery($query);
								$db->execute();
							}

							// C. Link to all pages
							$query = $db->getQuery(true);
							$query->select('id')
								->from($db->qn('#__modules'))
								->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
							$db->setQuery($query);
							$moduleid = $db->loadResult();

							$query = $db->getQuery(true);
							$query->select('*')
								->from($db->qn('#__modules_menu'))
								->where($db->qn('moduleid') . ' = ' . $db->q($moduleid));
							$db->setQuery($query);
							$assignments = $db->loadObjectList();
							$isAssigned  = !empty($assignments);

							if (!$isAssigned)
							{
								$o = (object) array(
									'moduleid' => $moduleid,
									'menuid'   => 0
								);
								$db->insertObject('#__modules_menu', $o);
							}
						}
					}
				}
			}
		}

		// Plugins installation
		if (count($this->_installation_queue['plugins']))
		{
			foreach ($this->_installation_queue['plugins'] as $folder => $plugins)
			{
				if (count($plugins))
				{
					foreach ($plugins as $plugin => $published)
					{
						$path = "$src/plugins/$folder/$plugin";

						if (!is_dir($path))
						{
							$path = "$src/plugins/$folder/plg_$plugin";
						}
						if (!is_dir($path))
						{
							$path = "$src/plugins/$plugin";
						}
						if (!is_dir($path))
						{
							$path = "$src/plugins/plg_$plugin";
						}
						if (!is_dir($path))
						{
							continue;
						}

						// Was the plugin already installed?
						$query = $db->getQuery(true)
							->select('COUNT(*)')
							->from($db->qn('#__extensions'))
							->where($db->qn('element') . ' = ' . $db->q($plugin))
							->where($db->qn('folder') . ' = ' . $db->q($folder));
						$db->setQuery($query);
						$count = $db->loadResult();

						$installer = new JInstaller;
						$result    = $installer->install($path);

						$status->plugins[] = array(
							'name'   => 'plg_' . $plugin,
							'group'  => $folder,
							'result' => $result
						);

						if ($published && !$count)
						{
							$query = $db->getQuery(true)
								->update($db->qn('#__extensions'))
								->set($db->qn('enabled') . ' = ' . $db->q('1'))
								->where($db->qn('element') . ' = ' . $db->q($plugin))
								->where($db->qn('folder') . ' = ' . $db->q($folder));
							$db->setQuery($query);
							$db->execute();
						}
					}
				}
			}
		}

		return $status;
	}

	/**
	 * Uninstalls subextensions (modules, plugins) bundled with the main extension
	 *
	 * @param   JInstaller  $parent  is the class calling this method.
	 *
	 * @return JObject The subextension uninstallation status
	 */
	private function _uninstallSubextensions($parent)
	{
		jimport('joomla.installer.installer');

		$db = JFactory::getDBO();

		$status          = new stdClass;
		$status->modules = array();
		$status->plugins = array();

		$src = $parent->getParent()->getPath('source');

		// Modules uninstallation
		if (count($this->_installation_queue['modules']))
		{
			foreach ($this->_installation_queue['modules'] as $folder => $modules)
			{
				if (count($modules))
				{
					foreach ($modules as $module => $modulePreferences)
					{
						// Find the module ID
						$sql = $db->getQuery(true)
							->select($db->qn('extension_id'))
							->from($db->qn('#__extensions'))
							->where($db->qn('element') . ' = ' . $db->q('mod_' . $module))
							->where($db->qn('type') . ' = ' . $db->q('module'));
						$db->setQuery($sql);
						$id = $db->loadResult();

						// Uninstall the module
						if ($id)
						{
							$installer         = new JInstaller;
							$result            = $installer->uninstall('module', $id, 1);
							$status->modules[] = array(
								'name'   => 'mod_' . $module,
								'client' => $folder,
								'result' => $result
							);
						}
					}
				}
			}
		}

		// Plugins uninstallation
		if (count($this->_installation_queue['plugins']))
		{
			foreach ($this->_installation_queue['plugins'] as $folder => $plugins)
			{
				if (count($plugins))
				{
					foreach ($plugins as $plugin => $published)
					{
						$sql = $db->getQuery(true)
							->select($db->qn('extension_id'))
							->from($db->qn('#__extensions'))
							->where($db->qn('type') . ' = ' . $db->q('plugin'))
							->where($db->qn('element') . ' = ' . $db->q($plugin))
							->where($db->qn('folder') . ' = ' . $db->q($folder));
						$db->setQuery($sql);

						$id = $db->loadResult();

						if ($id)
						{
							$installer         = new JInstaller;
							$result            = $installer->uninstall('plugin', $id, 1);
							$status->plugins[] = array(
								'name'   => 'plg_' . $plugin,
								'group'  => $folder,
								'result' => $result
							);
						}
					}
				}
			}
		}

		return $status;
	}

	/**
	 * Install TCPDF
	 *
	 * @param   string  $parent  How this was tarted
	 *
	 * @return array
	 */
	private function _installTCPDF($parent)
	{
		$src = $parent->getParent()->getPath('source');

		// Install the TCPDF library
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');
		jimport('joomla.utilities.date');
		$source = $src . '/libraries/tcpdf';
		if (!defined('JPATH_LIBRARIES'))
		{
			$target = JPATH_ROOT . '/libraries/tcpdf';
		}
		else
		{
			$target = JPATH_LIBRARIES . '/tcpdf';
		}

		$haveToInstallTCPDF = false;
		if (!JFolder::exists($target))
		{
			$haveToInstallTCPDF = true;
		}
		else
		{
			$TCPDFVersion = array();

			if (JFile::exists($target . '/README.TXT'))
			{
				$rawData                   = file_get_contents($target . '/README.TXT');
				$info                      = explode("\n", $rawData);
				$TCPDFVersion['installed'] = array(
					'version' => trim($info[0]),
					'date'    => new JDate(trim($info[1]))
				);
			}
			else
			{
				$TCPDFVersion['installed'] = array(
					'version' => '0.0',
					'date'    => new JDate('2011-01-01')
				);
			}
		}

		$installedTCPDF = false;

		if ($haveToInstallTCPDF)
		{
			$versionSource  = 'package';
			$installer      = new JInstaller;
			$installedTCPDF = $installer->install($source);
		}
		else
		{
			$versionSource = 'installed';
		}

		return array(
			'required'  => $haveToInstallTCPDF,
			'installed' => $installedTCPDF,
			'version'   => $TCPDFVersion[$versionSource],
		);
	}

	/**
	 * Remove Old Files and Folders
	 *
	 * @since 7.1.0
	 * @return void
	 */
	public function deleteUnexistingFiles()
	{
		$files = array('/media/com_churchdirectory/startfile.php',);

		$folders = array('/components/com_churchdirectory/views/churchdirectory',);

		foreach ($files as $file)
		{
			if (JFile::exists(JPATH_ROOT . $file) && !JFile::delete(JPATH_ROOT . $file))
			{
				echo JText::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $file) . '<br />';
			}
		}

		foreach ($folders as $folder)
		{
			if (JFolder::exists(JPATH_ROOT . $folder) && !JFolder::delete(JPATH_ROOT . $folder))
			{
				echo JText::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $folder) . '<br />';
			}
		}
	}

	/**
	 * Set new Parrams to system.
	 *
	 * @return void
	 */
	private function setParams()
	{
		if (count($this->_param_array) > 0)
		{
			// Read the existing component value(s)
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('params')
				->from('#__extensions')
				->where('name = ' . $db->quote($this->churchdirectory_extension));
			$db->setQuery($query);
			$params = json_decode($db->loadResult(), true);

			// Add the new variable(s) to the existing one(s)
			foreach ($this->_param_array as $name => $value)
			{
				$params[(string) $name] = (string) $value;
			}
			// Store the combined new and existing values back as a JSON string
			$paramsString = json_encode($params);
			$query        = $db->getQuery(true);
			$query->update('#__extensions')
				->set('params = ' . $db->quote($paramsString))
				->where('name = ' . $db->quote($this->churchdirectory_extension));
			$db->setQuery($query);
			$db->query();
		}
	}

	/**
	 * Set Default DB
	 *
	 * @return void
	 */
	private function setDefaultDB()
	{

		// Create categories for our component
		$basePath = JPATH_ADMINISTRATOR . '/components/com_categories';
		require_once $basePath . '/models/category.php';
		$config   = array('table_path' => $basePath . '/tables');
		$catmodel = new CategoriesModelCategory($config);
		$catData  = array(
			'id'          => 0,
			'parent_id'   => 0,
			'level'       => 1,
			'path'        => 'default-team',
			'extension'   => 'com_churchdirectory',
			'title'       => 'Default Team',
			'alias'       => 'default-team',
			'description' => '<p>Default Team where members are assigned if non are specified.</p>',
			'published'   => 1,
			'language'    => '*'
		);
		$status   = $catmodel->save($catData);

		if (!$status)
		{
			JFactory::getApplication()->enqueueMessage(JText::_('Unable to create default ChurchDirectory category!'), 'error');
		}

	}

}
