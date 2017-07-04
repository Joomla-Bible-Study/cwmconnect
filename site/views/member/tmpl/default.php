<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_contact
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$cparams = JComponentHelper::getParams('com_media');
/** @var \Joomla\Registry\Registry $tparams */
$tparams = $this->params;
$app =JFactory::getApplication();
$menu = $app->getMenu()->getActive()->id;
$this->loadHelper('render');
$renderHelper = new ChurchDirectoryRenderHelper;
$presentation_style = $tparams->get('presentation_style');
?>
<div class="contact<?php echo $this->pageclass_sfx ?>">
	<?php if ($this->params->get('show_page_heading'))
	{
		?>
		<h1>
			<?php echo $this->escape($this->params->get('page_heading')); ?>
		</h1>
		<?php
	}

	if ($this->member->name && $this->params->get('show_name'))
	{
		?>
		<div class="page-header">
			<h2>
				<?php if ($this->item->published == 0)
				{
					?>
					<span class="label label-warning"><?php echo JText::_('JUNPUBLISHED'); ?></span>
					<?php
				} ?>
				<span class="contact-name"><?php echo $this->member->name; ?></span>
			</h2>
			<p><a href="<?php echo JRoute::_('index.php?option=com_churchdirectory&view=home'); ?>">Members Home -></a>
				<a href="<?php echo JRoute::_(ChurchDirectoryHelperRoute::getCategoryRoute($this->item->catid));
				?>"><?php echo JText::_($this->item->category_title); ?>
					-></a>
				<span class="contact-name"><?php echo $this->member->name; ?></span></p>
		</div>
		<?php
	}

	$spouse = $renderHelper->getSpouse((int) $this->member->fu_id, (int) $this->member->attribs->get('familypostion'));

	if ($spouse && $this->member->attribs->get('familypostion') != '2')
	{
		?>
		<p>
			<?php echo '<span class="jicons-text">' . JText::_('COM_CHURCHDIRECTORY_SPOUSE') . ': </span>' . $spouse; ?>
		</p>
		<?php
	} ?>
	<?php if ($this->params->get('dr_show_children') && $this->member->attribs->get('familypostion') != '2')
	{
		$children = $renderHelper->getChildren((int) $this->member->fu_id, false, $this->member->children);
		?>
		<p>
			<?php
			if ($children)
			{
				echo $children . '<br />';
			} ?>
		</p>
	<?php } ?>
	<?php if ($this->params->get('show_contact_category') == 'show_no_link')
	{
		?>
		<h3>
			<span class="contact-category"><?php echo $this->member->category_title; ?></span>
		</h3>
	<?php } ?>
	<?php if ($this->params->get('show_contact_category') == 'show_with_link')
	{
		?>
		<?php $contactLink = ChurchDirectoryHelperRoute::getCategoryRoute($this->member->catid); ?>
		<h3>
			<span class="contact-category"><a href="<?php echo $contactLink; ?>">
					<?php echo $this->escape($this->member->category_title); ?></a>
			</span>
		</h3>
	<?php } ?>
	<?php if ($this->params->get('show_contact_list') && count($this->contacts) > 1)
	{
		?>
		<form action="#" method="get" name="selectForm" id="selectForm">
			<?php echo JText::_('COM_CHURCHDIRECTORY_SELECT_CHURCHDIRECTORY'); ?>
			<?php echo JHtml::_('select.genericlist', $this->contacts, 'id', 'class="inputbox" onchange="document.location.href = this.value"', 'link', 'name', $this->member->link); ?>
		</form>
	<?php } ?>

	<?php if ($presentation_style == 'tabs')
	{
		?>
		<ul class="nav nav-tabs" id="myTab">
			<li><a data-toggle="tab" href="#basic-details"><?php echo JText::_('COM_CHURCHDIRECTORY_DETAILS'); ?></a>
			</li>

			<?php if ($this->params->get('show_email_form') && ($this->member->email_to || $this->member->user_id))
			{
				?>
				<li><a data-toggle="tab"
				       href="#display-form"><?php echo JText::_('COM_CHURCHDIRECTORY_EMAIL_FORM'); ?></a>
				</li>
				<?php
			}
			if ($this->params->get('show_links'))
			{
				?>
				<li><a data-toggle="tab" href="#display-links"><?php echo JText::_('COM_CHURCHDIRECTORY_LINKS'); ?></a>
				</li>
				<?php
			}
			if ($this->params->get('show_articles') && $this->member->user_id && $this->member->articles)
			{
				?>
				<li><a data-toggle="tab" href="#display-articles"><?php echo JText::_('JGLOBAL_ARTICLES'); ?></a>
				</li>
				<?php
			}
			if ($this->params->get('show_profile') && $this->member->user_id && JPluginHelper::isEnabled('user', 'profile'))
			{
				?>
				<li><a data-toggle="tab"
				       href="#display-profile"><?php echo JText::_('COM_CHURCHDIRECTORY_PROFILE'); ?></a>
				</li>
				<?php
			}
			if ($this->member->misc && $this->params->get('show_misc'))
			{
				?>
				<li><a data-toggle="tab"
				       href="#display-misc"><?php echo JText::_('COM_CHURCHDIRECTORY_OTHER_INFORMATION'); ?></a>
				</li>
				<?php
			}
			?>
		</ul>
		<?php
	}
	?>
	<?php if ($presentation_style == 'sliders')
	{
		?>
		<?php echo JHtml::_('bootstrap.startAccordion', 'slide-contact', ['active' => 'basic-details']); ?>
	<?php } ?>
	<?php if ($presentation_style == 'tabs')
	{
		?>
		<?php echo JHtml::_('bootstrap.startPane', 'myTab', ['active' => 'basic-details']); ?>
	<?php } ?>

	<?php if ($presentation_style == 'sliders')
	{
		?>
		<?php echo JHtml::_('bootstrap.addSlide', 'slide-contact', JText::_('COM_CHURCHDIRECTORY_DETAILS'), 'basic-details'); ?>
	<?php } ?>
	<?php if ($presentation_style == 'tabs')
	{
		?>
		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'basic-details', JText::_('COM_CHURCHDIRECTORY_DETAILS', true)); ?>
	<?php } ?>
	<?php if ($presentation_style == 'plain')
	{
		?>
		<?php echo '<h3>' . JText::_('COM_CHURCHDIRECTORY_DETAILS') . '</h3>'; ?>
	<?php } ?>

	<div class="row" style="padding-left: 20px;">
		<?php
		if ($this->member->con_position && $this->params->get('show_position'))
		{
			?>
			<div class="span6">
				<span class="span4 jicons-text">
						<?php if ($this->member->con_position != '-1')
						{
							echo JText::_('COM_CHURCHDIRECTORY_POSITIONS') . ':';
						}
						?>
				</span>
				<span class="span8">
					<?php echo $renderHelper->getPosition($this->member->con_position); ?>
				</span>
			</div>
			<?php
		}

		if (!empty($this->member->image) && $this->params->get('show_image'))
		{
			?>
			<div class="thumbnail pull-right">
				<?php echo JHtml::_('image', $this->member->image, JText::_('COM_CHURCHDIRECTORY_IMAGE_DETAILS'), ['align' => 'right', 'class' => 'thumbnail', 'style' => 'max-width: 250px;']); ?>
			</div>
			<?php
		}
		echo "</div>";
		echo '<div class="clearfix"></div>';
		echo $this->loadTemplate('address');

		if ($this->params->get('allow_vcard'))
		{
			echo JText::_('COM_CHURCHDIRECTORY_DOWNLOAD_INFORMATION_AS'); ?>
			<a href="<?php echo JRoute::_('index.php?option=com_churchdirectory&amp;view=member&amp;id=' . $this->member->id . '&amp;format=vcf'); ?>">
				<?php echo JText::_('COM_CHURCHDIRECTORY_VCARD'); ?></a>
			<?php
		}
		if ($presentation_style == 'sliders')
		{
			echo JHtml::_('bootstrap.endSlide');
		}
		if ($presentation_style == 'tabs')
		{
			echo JHtml::_('bootstrap.endTab');
		}
		if ($this->params->get('show_email_form') && !empty($this->member->email_to))
		{
			if ($presentation_style == 'sliders')
			{
				echo JHtml::_('bootstrap.addSlide', 'slide-contact', JText::_('COM_CHURCHDIRECTORY_EMAIL_FORM'), 'display-form');
			}
			if ($presentation_style == 'tabs')
			{
				echo JHtml::_('bootstrap.addTab', 'myTab', 'display-form', JText::_('COM_CHURCHDIRECTORY_EMAIL_FORM', true));
			}
			if ($presentation_style == 'plain')
			{
				?>
				<?php echo '<h3>' . JText::_('COM_CHURCHDIRECTORY_EMAIL_FORM') . '</h3>'; ?>
			<?php } ?>

			<?php echo $this->loadTemplate('form'); ?>

			<?php if ($presentation_style == 'sliders')
		{
			?>
			<?php echo JHtml::_('bootstrap.endSlide'); ?>
		<?php } ?>
			<?php if ($presentation_style == 'tabs')
		{
			?>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php } ?>

		<?php } ?>

		<?php if ($this->params->get('show_links') && $this->member->params->get('link' . 'a') != null)
		{
			?>

			<?php if ($presentation_style == 'sliders')
		{
			?>
			<?php echo JHtml::_('bootstrap.addSlide', 'slide-links', JText::_('COM_CHURCHDIRECTORY_LINKS'), 'display-form'); ?>
		<?php } ?>
			<?php if ($presentation_style == 'tabs')
		{
			?>
			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'display-links', JText::_('COM_CONTACT_LINKS', true)); ?>
		<?php } ?>
			<?php if ($presentation_style == 'plain')
		{
			?>
			<?php echo '<h3>' . JText::_('COM_CHURCHDIRECTORY_LINKS') . '</h3>'; ?>
		<?php } ?>

			<?php echo $this->loadTemplate('links'); ?>

			<?php if ($presentation_style == 'sliders')
		{
			?>
			<?php echo JHtml::_('bootstrap.endSlide'); ?>
		<?php } ?>
			<?php if ($presentation_style == 'tabs')
		{
			?>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php } ?>
		<?php } ?>

		<?php if ($this->params->get('show_articles') && $this->member->user_id && $this->member->articles)
		{
			?>

			<?php if ($presentation_style == 'sliders')
		{
			?>
			<?php echo JHtml::_('bootstrap.addSlide', 'slide-contact', JText::_('JGLOBAL_ARTICLES'), 'display-articles'); ?>
		<?php } ?>
			<?php if ($presentation_style == 'tabs')
		{
			?>
			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'display-articles', JText::_('JGLOBAL_ARTICLES', true)); ?>
		<?php } ?>
			<?php if ($presentation_style == 'plain')
		{
			?>
			<?php echo '<h3>' . JText::_('JGLOBAL_ARTICLES') . '</h3>'; ?>
		<?php } ?>

			<?php echo $this->loadTemplate('articles'); ?>

			<?php if ($presentation_style == 'sliders')
		{
			?>
			<?php echo JHtml::_('bootstrap.endSlide'); ?>
		<?php } ?>
			<?php if ($presentation_style == 'tabs')
		{
			?>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php } ?>

		<?php } ?>
		<?php if ($this->params->get('show_profile') && $this->member->user_id && JPluginHelper::isEnabled('user', 'profile'))
		{
			?>

			<?php if ($presentation_style == 'sliders')
		{
			?>
			<?php echo JHtml::_('bootstrap.addSlide', 'slide-contact', JText::_('COM_CHURCHDIRECTORY_PROFILE'), 'display-profile'); ?>
		<?php } ?>
			<?php if ($presentation_style == 'tabs')
		{
			?>
			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'display-profile', JText::_('COM_CHURCHDIRECTORY_PROFILE', true)); ?>
		<?php } ?>
			<?php if ($presentation_style == 'plain')
		{
			?>
			<?php echo '<h3>' . JText::_('COM_CHURCHDIRECTORY_PROFILE') . '</h3>'; ?>
		<?php } ?>

			<?php echo $this->loadTemplate('profile'); ?>

			<?php if ($presentation_style == 'sliders')
		{
			?>
			<?php echo JHtml::_('bootstrap.endSlide'); ?>
		<?php } ?>
			<?php if ($presentation_style == 'tabs')
		{
			?>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php } ?>

		<?php } ?>
		<?php if ($this->member->misc && $this->params->get('show_misc'))
		{
			?>

			<?php if ($presentation_style == 'sliders')
		{
			?>
			<?php echo JHtml::_('bootstrap.addSlide', 'slide-contact', JText::_('COM_CHURCHDIRECTORY_OTHER_INFORMATION'), 'display-misc'); ?>
		<?php } ?>
			<?php if ($presentation_style == 'tabs')
		{
			?>
			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'display-misc', JText::_('COM_CHURCHDIRECTORY_OTHER_INFORMATION')); ?>
		<?php } ?>
			<?php if ($presentation_style == 'plain')
		{
			?>
			<?php echo '<h3>' . JText::_('COM_CHURCHDIRECTORY_OTHER_INFORMATION') . '</h3>'; ?>
		<?php } ?>

			<div class="contact-miscinfo">
				<dl class="dl-horizontal">
					<dt>
							<span class="<?php echo $this->params->get('marker_class'); ?>">
								<?php echo $this->params->get('marker_misc'); ?>
							</span>
					</dt>
					<dd>
							<span class="contact-misc">
								<?php echo $this->member->misc; ?>
							</span>
					</dd>
				</dl>
			</div>

			<?php if ($presentation_style == 'sliders')
		{
			?>
			<?php echo JHtml::_('bootstrap.endSlide'); ?>
		<?php } ?>
			<?php if ($presentation_style == 'tabs')
		{
			?>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php } ?>

		<?php } ?>

		<?php if ($presentation_style == 'sliders')
		{
			?>
			<?php echo JHtml::_('bootstrap.endAccordion'); ?>
		<?php } ?>
		<?php if ($presentation_style == 'tabs')
		{
			?>
			<?php echo JHtml::_('bootstrap.endTabSet'); ?>
		<?php } ?>
		<?php echo $this->item->event->afterDisplayContent; ?>
	</div>
