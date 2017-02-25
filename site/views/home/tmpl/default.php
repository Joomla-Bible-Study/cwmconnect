<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

$login = $this->user->get('guest') ? true : false;
$check = in_array($this->params->get('accesslevel'), $this->user->get('_authLevels'));
$count = count($this->items);
?>
<div class="chhome" style="padding: 5px;">
	<h1 class="center"><?php if ($this->params->get('show_page_heading', 0))
		{
			echo $this->params->get('page_heading');
		} ?>
	</h1>

	<div class="span2 pull-left">
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
			if ($this->params->get('form'))
			{
				echo ' <a href="' . $this->params->get('form') . '">' . JText::_('COM_CHURCHDIRECTORY_AUTH_FORM') . '</a>';
			}
			?>
			<br/><br/>
			<?php
		}
		if (!$check)
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
					<?php
					$split = $count / 2;
					foreach ($this->items as $i => $item)
					{
						if ($i < $split)
						{
							?>
							<div class="span6 pull-left">
								<div class="center">
									<a href="<?php echo JRoute::_('index.php?option=com_churchdirectory&view=member&id=' . $item->id); ?>">
										<?php if ($item->image && $item->image != '/')
										{ ?>
											<img src="<?php echo $item->image; ?>"
											     alt="<?php echo $item->name; ?>"
											     style="max-width:240px; border: none;"><br/>
										<?php } ?>
										<span class="large buld"><?php echo $item->name ?></span><br/>
										<span class="small">
											<?php echo $this->renderHelper->getPosition($item->con_position); ?>
										</span>
									</a>
								</div>

							</div>
							<?php
						}
						else
						{
							?>
							<div class="span6 pull-left">
								<div class="center">
									<a href="<?php echo JRoute::_('index.php?option=com_churchdirectory&view=member&id=' . $item->id); ?>">
										<?php if ($item->image && $item->image != '/')
										{ ?>
											<img src="<?php echo $item->image; ?>"
											     alt="<?php echo $item->name; ?>"
											     style="max-width:240px; border: none;"><br/>
										<?php } ?>
										<span class="large buld"><?php echo $item->name ?></span><br/>
										<span class="small">
											<?php echo $this->renderHelper->getPosition($item->con_position); ?>
										</span>
									</a>
								</div>

							</div>
							<?php
						}
					} ?>
				</div>
			</div>
		<?php } ?>
	</div>
</div>
