<?php
/**
 * @package    FrameworkOnFramework
 * @copyright  Copyright (C) 2010 - 2012 Akeeba Ltd. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

jimport('legacy.view.legacy');

/**
 * FrameworkOnFramework View class
 *
 * FrameworkOnFramework is a set of classes which extend Joomla! 1.5 and later's
 * MVC framework with features making maintaining complex software much easier,
 * without tedious repetitive copying of the same code over and over again.
 */
abstract class FOFView extends JViewLegacy
{
	static $renderers = array();

	protected $config = array();

	protected $input = array();

	protected $rendererObject = null;

	public function  __construct($config = array()) {
		parent::__construct($config);

		// Get the input
		if(array_key_exists('input', $config)) {
			if($config['input'] instanceof FOFInput) {
				$this->input = $config['input'];
			} else {
				$this->input = new FOFInput($config['input']);
			}
		} else {
			$this->input = new FOFInput();
		}

		// Get the component name
		if(array_key_exists('input', $config)) {
			if($config['input'] instanceof FOFInput) {
				$tmpInput = $config['input'];
			} else {
				$tmpInput = new FOFInput($config['input']);
			}
			$component = $tmpInput->getCmd('option','');
		} else {
			$tmpInput = $this->input;
		}
		if(array_key_exists('option', $config)) if($config['option']) $component = $config['option'];
		$config['option'] = $component;

		// Get the view name
		if(array_key_exists('input', $config)) {
			$view = $tmpInput->getCmd('view','');
		}
		if(array_key_exists('view', $config)) if($config['view']) $view = $config['view'];
		$config['view'] = $view;

		// Set the component and the view to the input array
		if(array_key_exists('input', $config)) {
			$tmpInput->set('option', $config['option']);
			$tmpInput->set('view', $config['view']);
		}

		// Set the view name
		if (array_key_exists('name', $config))  {
			$this->_name = $config['name'];
		} else {
			$this->_name = $config['view'];
		}
		$tmpInput->set('view', $this->_name);
		$config['input'] = $tmpInput;
		$config['name'] = $this->_name;
		$config['view'] = $this->_name;

		// Set a base path for use by the view
		if (array_key_exists('base_path', $config)) {
			$this->_basePath	= $config['base_path'];
		} else {
			list($isCli, $isAdmin) = FOFDispatcher::isCliAdmin();
			$this->_basePath	= ($isAdmin ? JPATH_ADMINISTRATOR : JPATH_SITE).'/'.$config['option'];
		}

		// Set the default template search path
		if (array_key_exists('template_path', $config)) {
			// User-defined dirs
			$this->_setPath('template', $config['template_path']);
		} else {
			$altView = FOFInflector::isSingular($this->getName()) ? FOFInflector::pluralize($this->getName()) : FOFInflector::singularize($this->getName());
			$this->_setPath('template', $this->_basePath . '/views/' . $altView . '/tmpl');
			$this->_addPath('template', $this->_basePath . '/views/' . $this->getName() . '/tmpl');
		}

		// Set the default helper search path
		if (array_key_exists('helper_path', $config)) {
			// User-defined dirs
			$this->_setPath('helper', $config['helper_path']);
		} else {
			$this->_setPath('helper', $this->_basePath . '/helpers');
		}

		$this->config = $config;

		$app = JFactory::getApplication();
		if (isset($app))
		{
			$component = preg_replace('/[^A-Z0-9_\.-]/i', '', $component);
			$fallback = JPATH_THEMES . '/' . $app->getTemplate() . '/html/' . $component . '/' . $this->getName();
			$this->_addPath('template', $fallback);
		}
	}

	/**
	 * Loads a template given any path. The path is in the format:
	 * [admin|site]:com_foobar/viewname/templatename
	 * e.g. admin:com_foobar/myview/default
	 *
	 * This function searches for Joomla! version override templates. For example,
	 * if you have run this under Joomla! 3.0 and you try to load
	 * admin:com_foobar/myview/default it will automatically search for the
	 * template files default.j30.php, default.j3.php and default.php, in this
	 * order.
	 *
	 * @param string $path
	 * @param array $forceParams A hash array of variables to be extracted in the local scope of the template file
	 */
	public function loadAnyTemplate($path = '', $forceParams = array())
	{
		// Automatically check for a Joomla! version specific override
		$throwErrorIfNotFound = true;

		$jversion = new JVersion();
		$versionParts = explode('.', $jversion->getLongVersion());
		$majorVersion = array_shift($versionParts);
		$suffixes = array(
			'.j'.str_replace('.', '', $jversion->getHelpVersion()),
			'.j'.$majorVersion,
		);
		unset($jversion, $versionParts, $majorVersion);

		foreach($suffixes as $suffix) {
			if(substr($path, -strlen($suffix)) == $suffix) {
				$throwErrorIfNotFound = false;
				break;
			}
		}

		if($throwErrorIfNotFound) {
			foreach($suffixes as $suffix) {
				$result = $this->loadAnyTemplate($path.$suffix, $forceParams);
				if($result !== false) {
					return $result;
				}
			}
		}

		$template = JFactory::getApplication()->getTemplate();
		$layoutTemplate = $this->getLayoutTemplate();

		// Parse the path
		$templateParts = $this->_parseTemplatePath($path);

		// Get the default paths
		$paths = array();
		$paths[] = ($templateParts['admin'] ? JPATH_ADMINISTRATOR : JPATH_SITE).'/templates/'.
			$template.'/html/'.$templateParts['component'].'/'.$templateParts['view'];
		$paths[] = ($templateParts['admin'] ? JPATH_ADMINISTRATOR : JPATH_SITE).'/components/'.
			$templateParts['component'].'/views/'.$templateParts['view'].'/tmpl';
		if(isset($this->_path) || property_exists($this, '_path')) {
			$paths = array_merge($paths, $this->_path['template']);
		} elseif(isset($this->path) || property_exists($this, 'path')) {
			$paths = array_merge($paths, $this->path['template']);
		}

		// Look for a template override
		if (isset($layoutTemplate) && $layoutTemplate != '_' && $layoutTemplate != $template)
		{
			$apath = array_shift($paths);
			array_unshift($paths, str_replace($template, $layoutTemplate, $apath));
		}

		$filetofind = $templateParts['template'].'.php';
		jimport('joomla.filesystem.path');
		$this->_tempFilePath = JPath::find($paths, $filetofind);
		if($this->_tempFilePath) {
			// Unset from local scope
			unset($template); unset($layoutTemplate); unset($paths); unset($path);
			unset($filetofind);

			// Never allow a 'this' property
			if (isset($this->this)) {
				unset($this->this);
			}

			// Force parameters into scope
			if(!empty($forceParams)) {
				extract($forceParams);
			}

			// Start capturing output into a buffer
			ob_start();
			// Include the requested template filename in the local scope
			// (this will execute the view logic).
			include $this->_tempFilePath;

			// Done with the requested template; get the buffer and
			// clear it.
			$this->_output = ob_get_contents();
			ob_end_clean();

			return $this->_output;
		} else {
			if($throwErrorIfNotFound) {
				return new Exception(JText::sprintf('JLIB_APPLICATION_ERROR_LAYOUTFILE_NOT_FOUND', $path), 500);
			}
			return false;
		}
	}

	/**
	 * Overrides the default method to execute and display a template script.
	 * Instead of loadTemplate is uses loadAnyTemplate which allows for automatic
	 * Joomla! version overrides. A little slice of awesome pie!
	 *
	 * @param   string  $tpl  The name of the template file to parse
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 */
	public function display($tpl = null)
	{
		if (version_compare(JVERSION, '3.0', 'lt'))
		{
			JError::setErrorHandling(E_ALL,'ignore');
		}
		
		$result = $this->loadTemplate($tpl);

		if (version_compare(JVERSION, '3.0', 'lt'))
		{
			JError::setErrorHandling(E_WARNING,'callback');
		}
		
		if ($result instanceof Exception) {
			JError::raiseError($result->getCode(), $result->getMessage());
			return $result;
		}

		echo $result;
	}
	
	/**
	 * Overrides the built-in loadTemplate function with an FOF-specific one.
	 * Our overriden function uses loadAnyTemplate to provide smarter view
	 * template loading.
	 * 
	 * @param   string  $tpl  The name of the template file to parse
	 * 
	 * @return  mixed  A string if successful, otherwise a JError object
	 */
	public function loadTemplate($tpl = null) {
		list($isCli, $isAdmin) = FOFDispatcher::isCliAdmin();

		$basePath = $isAdmin ? 'admin:' : 'site:';
		$basePath .= $this->config['option'].'/';
		$altBasePath = $basePath;
		$basePath .= $this->config['view'].'/';
		$altBasePath .= FOFInflector::isSingular($this->config['view']) ? FOFInflector::pluralize($this->config['view']) : FOFInflector::singularize($this->config['view']).'/';
		
		$paths = array(
			$basePath.$this->getLayout().($tpl ? "_$tpl" : ''),
			$basePath.$this->getLayout(),
			$basePath.'default',
			$altBasePath.$this->getLayout().($tpl ? "_$tpl" : ''),
			$altBasePath.$this->getLayout(),
			$altBasePath.'default',
		);
		
		foreach($paths as $path) {
			$result = $this->loadAnyTemplate($path);
			if (!($result instanceof Exception)) {
				break;
			}
		}
		
		if ($result instanceof Exception) {
			JError::raiseError($result->getCode(), $result->getMessage());
		}
		
		return $result;
	}

	private function _parseTemplatePath($path = '')
	{
		$parts = array(
			'admin'		=> 0,
			'component'	=> $this->config['option'],
			'view'		=> $this->config['view'],
			'template'	=> 'default'
		);

		if(substr($path,0,6) == 'admin:') {
			$parts['admin'] = 1;
			$path = substr($path,6);
		} elseif(substr($path,0,5) == 'site:') {
			$path = substr($path,5);
		}

		if(empty($path)) return;

		$pathparts = explode('/', $path, 3);
		switch(count($pathparts)) {
			case 3:
				$parts['component'] = array_shift($pathparts);

			case 2:
				$parts['view'] = array_shift($pathparts);

			case 1:
				$parts['template'] = array_shift($pathparts);
				break;
		}

		return $parts;
	}

	/**
	 * Get the renderer object for this view
	 * @return FOFRenderAbstract
	 */
	public function &getRenderer()
	{
		if(!($this->rendererObject instanceof FOFRenderAbstract)) {
			$this->rendererObject = $this->findRenderer();
		}
		return $this->rendererObject;
	}

	/**
	 * Sets the renderer object for this view
	 * @param FOFRenderAbstract $renderer
	 */
	public function setRenderer(FOFRenderAbstract &$renderer)
	{
		$this->rendererObject = $renderer;
	}

	/**
	 * Finds a suitable renderer
	 *
	 * @return FOFRenderAbstract
	 */
	protected function findRenderer()
	{
		jimport('joomla.filesystem.folder');
		
		// Try loading the stock renderers shipped with FOF
		if(empty(self::$renderers) || !class_exists('FOFRenderJoomla', false)) {
			$path = dirname(__FILE__).'/../render/';
			$renderFiles = JFolder::files($path, '.php');
			if(!empty($renderFiles)) {
				foreach($renderFiles as $filename) {
					if($filename == 'abstract.php') continue;
					@include_once $path.'/'.$filename;
					$camel = FOFInflector::camelize($filename);
					$className = 'FOFRender'.  ucfirst(FOFInflector::getPart($camel, 0));
					$o = new $className;
					self::registerRenderer($o);
				}
			}
		}

		// Try to detect the most suitable renderer
		$o = null;
		$priority = 0;
		if(!empty(self::$renderers)) {
			foreach(self::$renderers as $r) {
				$info = $r->getInformation();
				if(!$info->enabled) continue;
				if($info->priority > $priority) {
					$priority = $info->priority;
					$o = $r;
				}
			}
		}

		// Return the current renderer
		return $o;
	}

	public static function registerRenderer(FOFRenderAbstract &$renderer)
	{
		self::$renderers[] = $renderer;
	}
	
	/**
	 * Load a helper file
	 *
	 * @param   string  $hlp  The name of the helper source file automatically searches the helper paths and compiles as needed.
	 *
	 * @return  void
	 *
	 * @since   12.2
	 */
	public function loadHelper($hlp = null)
	{
		// Clean the file name
		$file = preg_replace('/[^A-Z0-9_\.-]/i', '', $hlp);

		// Load the template script using the default Joomla! features
		jimport('joomla.filesystem.path');
		$helper = JPath::find($this->_path['helper'], $this->_createFileName('helper', array('name' => $file)));

		if($helper == false) {
			list($isCli, $isAdmin) = FOFDispatcher::isCliAdmin();
			$path = ($isAdmin ? JPATH_ADMINISTRATOR : JPATH_SITE) . '/components/' .
					$this->config['option'] . '/helpers';
			$helper = JPath::find($path, $this->_createFileName('helper', array('name' => $file)));
			
			if($helper == false) {
				$path = ($isAdmin ? JPATH_SITE : JPATH_ADMINISTRATOR) . '/components/' .
					$this->config['option'] . '/helpers';
				$helper = JPath::find($path, $this->_createFileName('helper', array('name' => $file)));
			}
		}
		
		if ($helper != false)
		{
			// Include the requested template filename in the local scope
			include_once $helper;
		}
	}
	
	/**
	 * Returns the view's option (component name) and view name in an
	 * associative array.
	 * 
	 * @return  array
	 * 
	 * @since   2.0
	 */
	public function getViewOptionAndName()
	{
		return array(
			'option'	=> $this->config['option'],
			'view'		=> $this->config['view'],
		);
	}
}
