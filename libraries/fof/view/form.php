<?php
/**
 * @package    FrameworkOnFramework
 * @copyright  Copyright (C) 2010 - 2012 Akeeba Ltd. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

/**
 * FrameworkOnFramework HTML Form Edit class
 */

class FOFViewForm extends FOFViewHtml
{
	/** @var FOFForm The form to render */
	protected $form;
	
	/**
	 * Displays the view
	 *
	 * @param   string  $tpl  The template to use
	 *
	 * @return  boolean|null False if we can't render anything
	 */
	public function  display($tpl = null)
	{
		$model = $this->getModel();

		// Get the form
		$this->form = $this->getModel()->getForm();
		$this->form->setModel($model);
		$this->form->setView($this);
		
		// Get some useful information
		list($isCli, $isAdmin) = FOFDispatcher::isCliAdmin();
		
		// Get the task set in the model
		$task = $model->getState('task','browse');

		// Call the relevant method
		$method_name = 'on'.ucfirst($task);
		if(method_exists($this, $method_name)) {
			$result = $this->$method_name($tpl);
		} else {
			$result = $this->onDisplay();
		}

		// Bail out if we're told not to render anything
		if($result === false) {
			return;
		}

		// Render the toolbar
		$toolbar = FOFToolbar::getAnInstance($this->input->getCmd('option','com_foobar'), $this->config);
		$toolbar->perms = $this->perms;
		$toolbar->renderToolbar($this->input->getCmd('view','cpanel'), $task, $this->input);

		// Show the view
		// -- Output HTML before the view template
		$this->preRender();
		
		// -- Try to load a view template; if not exists render the form directly
		$basePath = $isAdmin ? 'admin:' : 'site:';
		$basePath .= $this->config['option'].'/';
		$basePath .= $this->config['view'].'/';
		$path = $basePath.$this->getLayout();
		if($tpl){
			$path .= '_'.$tpl;
		}
		$viewTemplate = $this->loadAnyTemplate($path);
		
		if($viewTemplate instanceof Exception) {
			// No view template available, render form directly
			$renderer = $this->getRenderer();
			if($renderer instanceof FOFRenderAbstract) {
				// Load CSS and Javascript files defined in the form
				$this->form->loadCSSFiles();
				$this->form->loadJSFiles();
				// Get the form's HTML
				$viewTemplate = $renderer->renderForm($this->form, $model, $this->input);
			}
		}
		// -- Output the view template
		echo $viewTemplate;
		// -- Output HTML after the view template
		$this->postRender();
	}
	
	protected function onAdd($tpl = null)
	{
		// Hide the main menu
		JRequest::setVar('hidemainmenu', true);
		
		// Get the model
		$model	= $this->getModel();
		
		// Assign the item and form to the view
		$this->assign( 'item',		$model->getItem() );
		$this->assign( 'form',		$this->form );
		return true;
	}
}