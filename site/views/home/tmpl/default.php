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
?>
<div>
	<h1 class="center"><?php if ($this->params->get('show_page_heading', 0))
		{
			echo $this->params->get('page_heading');
		}?>
	</h1>

	<div class="span6 pull-left">
		<a href="index.php?option=com_users&return=<?php echo $this->return ?>">
			<button class="btn btn-primary">
				<?php echo $login ? JText::_('JLOGIN') : JText::_('JLOGOUT') ?>
			</button>
		</a>
	</div>
	<div class="pull-right"><?php echo $this->search; ?></div>
	<div class="clearfix"></div>
	<p class="center"><?php echo $this->params->get('home_intro', 'No Intro Text'); ?></p>

	<div class="login">
		<?php if ($login)
		{
			echo JText::_('COM_CHURCHDIRECTORY_HOME_INTRO');
			echo ' <a href="' . $this->params->get('form') . '">' . JText::_('COM_CHURCHDIRECTORY_AUTH_FORM') . '</a>'; ?>
			<br /><br />
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
				<div class="span12">
					<div class="span5 pull-left">Left</div>
					<div class="span5 pull-left">Right</div>
				</div>
			</div>
		<?php } ?>
	</div>
</div>
