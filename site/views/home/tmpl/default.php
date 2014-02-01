<?php
/**
 * @package        ChurchDirectory.Site
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

JHtml::_('bootstrap.framework');

?>
<div>
	<h1 class="center"><?php echo $this->params->get('hometitle', JText::_('COM_CHURCHDIRECTORY_HOME_TITLE')); ?></h1>

	<p class="center"><?php echo $this->params->get('homeintro', JText::sprintf('COM_CHURCHDIRECTORY_HOME_INTRO', $this->params->get('form'))); ?></p>
	<?php if (in_array($this->params->get('accesslevel', 0), $this->user->get('_authLevels'))): ?>
	<div class="row-fluid">
		<div class="span6 center">Left</div>
		<div class="span6 center">Right</div>
	</div>
	<?php endif; ?>
</div>
