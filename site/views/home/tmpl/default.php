<?php
/**
 * @package        ChurchDirectory.Site
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

JHtml::_('bootstrap.framework');
$login = $this->user->get('guest') ? true : false;
$check = in_array($this->params->get('accesslevel'), $this->user->get('_authLevels'));
var_dump($this->params);
?>
<div>
	<h1 class="center"><?php if ($this->params->get('show_page_heading', 0))
		{
			echo $this->params->get('page_heading');
		}?>
	</h1>

	<p class="center"><?php echo $this->params->get('intro', JText::sprintf('COM_CHURCHDIRECTORY_HOME_INTRO', $this->params->get('form'))); ?></p>

	<div class="login">
		<?php if ($login)
		{
			?>
			<a href="index.php?option=com_users&return=<?php echo $this->return ?>"><?php echo $login ? JText::_('JLOGIN') : JText::_('JLOGOUT') ?></a>
		<?php
		}
		elseif (!$check)
		{
			?>
			<span>Please register as a church member. This directory is for church members only</span>
		<?php
		}
		elseif ($check)
		{
			?>
			<div class="row-fluid">
				<div class="span6 center">Left</div>
				<div class="span6 center">Right</div>
			</div>
		<?php } ?>
	</div>
</div>
