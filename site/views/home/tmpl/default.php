<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

JHtml::_('jquery.framework');
JHtml::_('formbehavior.chosen');
JHtml::_('bootstrap.tooltip');

/** @var $this ChurchDirectoryViewHome */
$login = $this->user->get('guest') ? true : false;
$check = in_array($this->params->get('accesslevel'), $this->user->get('_authLevels'));
$count = count($this->items);

?>
<div class="chdhome" style="padding: 5px;">
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
	<div class="pull-right">
		<?php echo $this->renderHelper->getSearchField($this->params); ?>
	</div>
	<div class="clearfix"></div>
	<p class="center"><?php echo $this->params->get('home_intro', 'No Intro Text'); ?></p>

	<?php if ($login)
	{ ?>
		<div class="chdlogin" style="padding-bottom: 40px">
			<div class="chdintro">
				<?php
				echo JText::_('COM_CHURCHDIRECTORY_HOME_INTRO');
				if ($this->params->get('form'))
				{
					echo ' <a href="' . $this->params->get('form') . '">' . JText::_('COM_CHURCHDIRECTORY_AUTH_FORM') . '</a>';
				}
				?>
			</div>
		</div>
		<?php
	}

	if (!$check)
	{
		?>
		<span class="chdpleasereg">Please register as a church member. This directory is for church members only</span>
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
						<div class="span6 pull-left" style="margin-left: 0">
							<div class="center">
								<a href="<?php echo JRoute::_(ChurchDirectoryHelperRoute::getMemberRoute($item->slug, $item->catid)); ?>">
									<?php if ($item->image && $item->image != '/')
									{ ?>
										<img src="<?php echo $item->image; ?>"
										     alt="<?php echo $item->name; ?>"
										     style="max-width:240px;" class="img-polaroid"><br/>
									<?php } ?>
								</a>
								<div class="cd-home-positions">
									<a href="<?php echo JRoute::_(ChurchDirectoryHelperRoute::getMemberRoute($item->slug, $item->catid)); ?>">
										<span class="buld" style="font-size: x-large;"><?php echo $item->name ?></span>
									</a>
									<br/>
									<span class="small">
											<?php echo $this->renderHelper->getPosition($item->con_position); ?>
										</span>
								</div>
							</div>

						</div>
						<?php
					}
					else
					{
						?>
						<div class="span6 pull-left" style="margin-left: 0">
							<div class="center">
								<a href="<?php echo JRoute::_(ChurchDirectoryHelperRoute::getMemberRoute($item->slug, $item->catid)); ?>">
									<?php if ($item->image && $item->image != '/')
									{ ?>
										<img src="<?php echo $item->image; ?>"
										     alt="<?php echo $item->name; ?>"
										     style="max-width:240px;" class="img-polaroid"><br/>
									<?php } ?>
								</a>
								<div class="cd-home-positions">
									<a href="<?php echo JRoute::_(ChurchDirectoryHelperRoute::getMemberRoute($item->slug, $item->catid)); ?>">
										<span class="buld" style="font-size: x-large;"><?php echo $item->name ?></span>
									</a>
									<br/>
									<span class="small">
											<?php echo $this->renderHelper->getPosition($item->con_position); ?>
										</span>
								</div>
							</div>

						</div>
						<?php
					}
				} ?>
			</div>
		</div>
	<?php } ?>
</div>
