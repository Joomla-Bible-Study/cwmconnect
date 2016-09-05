<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * */
defined('_JEXEC') or die;

JHtml::_('bootstrap.framework');

$heading            = null;
$this->renderHelper = new RenderHelper;

?>
<?php if (empty($this->items))
{
	?>
	<p><?php echo JText::_('COM_CHURCHDIRECTORY_NO_MEMBERS'); ?></p>
	<?php
} ?>
<?php
foreach ($this->items as $item)
{
	if ($this->printed_items == 0 && $this->printed_rows == 0)
	{
		echo '<!-- new start ' . $item->name . '-->';
		if (($item->funitid != '0' && $item->attribs->get('familypostion', '0') === '0')
			|| ($item->funitid == '0' && $item->attribs->get('familypostion', '-1') === '-1' || $item->attribs->get('familypostion', '0') === '0')
		)
		{
			if ($this->params->get('dr_show_debug'))
			{
				echo '<a rel="popover" data-placement="bottom" data-trigger="hover" data-content="ID: ' . $item->id . ' ' . gettype($item->id) . ' <br />
						FUnit ID: ' . $item->funitid . ' ' . gettype($item->funitid) . ' <br />
						Item Count: ' . $this->printed_items . ' ' . gettype($this->printed_items) . ' <br />
						Row Count: ' . $this->printed_rows . ' ' . gettype($this->printed_rows) . ' <br />
						FamilyPosiion: ' . $item->attribs->get('familypostion') . ' ' . gettype($item->attribs->get('familypostion')) .
					'" data-original-title="Tips" href="/my_link_goes_here">debug</a>';
			}
			echo '<div class="row-fled"><div class="span' . $this->span . '">';
		}
	}
	$this->subcount--;

	if ($this->letter != ucfirst($this->items[0]->lname[0]))
	{
		$this->letter = ucfirst($this->items[0]->lname[0]);
		echo '<a style="page-break-before:auto" name="' . $this->letter . '"></a><h4>' . $this->letter . '</h4><hr />';
	}
	if (!empty($item->funitid) && $item->funitid != '0' && $item->attribs->get('familypostion') === '0') :
		?>
		<div id="directory-items<?php echo $item->id + 1; ?>" style="page-break-before:auto; width:100px;" class="paddingitem">
			<?php
			if ($item->funit_image && $this->params->get('dr_show_image')) :
				echo '<img src="' . $this->baseurl . DIRECTORY_SEPARATOR . $item->funit_image . '" align="center" hspace="6" alt="' .
					$item->funit_name . '" class="directory-img pull-right" />';
			elseif ($this->params->get('image') != null && $this->params->get('dr_show_image')):
				echo '<img src="' . $this->baseurl . DIRECTORY_SEPARATOR . $this->params->get('image') . '" align="center" hspace="6" alt="' .
					JText::_('COM_CHURCHDIRECTORY_NO_PHOTO_AVALIBLE') . '" class="directory-img pull-right" />';
			elseif ($this->params->get('dr_show_image')):
				echo '<img src="' . $this->baseurl . '/media/com_churchdirectory/images/200-photo_not_available.jpg" align="center" hspace="6" alt="' .
					JText::_('COM_CHURCHDIRECTORY_NO_PHOTO_AVALIBLE') . '" class="directory-img pull-right" />';
			endif;
			?>
			<?php
			$families = $this->renderHelper->getFamilyMembers($item->funitid);
			?>
			<span id="contact-name"><?php echo $item->funit_name; ?></span><br/>
			<?php $children = $this->renderHelper->getChildren($families, true, $item->children);
			if ($this->params->get('dr_show_children') && $children || $item->children) :
				echo $children; ?>
			<?php endif; ?>
			<div class="churchdirectory-contact">
				<?php if ($this->params->get('dr_show_debug'))
				{
					echo '<a rel="popover" data-placement="bottom" data-trigger="hover" data-content="ID: ' . $item->id . ' ' . gettype($item->id) . ' <br />
						FUnit ID: ' . $item->funitid . ' ' . gettype($item->funitid) . ' <br />
						Item Count: ' . $this->printed_items . ' ' . gettype($this->printed_items) . ' <br />
						Row Count: ' . $this->printed_rows . ' ' . gettype($this->printed_rows) . ' <br />
						FamilyPosiion: ' . $item->attribs->get('familypostion') . ' ' . gettype($item->attribs->get('familypostion')) .
						'" data-original-title="Tips" href="/my_link_goes_here">debug</a>';
				} ?>
				<?php echo $this->renderHelper->renderAddress($item, $this->params); ?>
			</div>
				<?php
				foreach ($families as $member)
				{
					$name = $this->renderHelper->getName($member->name);
					echo $this->renderHelper->renderPhonesNumbers($member, $this->params, $name);
				} ?>
			<?php if (!empty($item->misc) && $this->params->get('dr_show_misc')) :
				?>
				<div class="contact-miscinfo inner">
					<span class="<?php echo $this->params->get('marker_class'); ?>">
						<?php echo $this->params->get('marker_misc'); ?>
					</span>
					<span class="contact-misc">
						<?php echo $item->misc; ?>
					</span>
				</div>
				<?php
			endif; ?>
		</div>
		<?php
	elseif ($item->funitid === '0'):
		?>
		<div id="directory-items<?php echo $item->id + 1; ?>"
		     class="paddingitem" style="page-break-before:auto">
			<?php
			if ($item->image && $this->params->get('dr_show_image')) :
				echo '<img src="' . $this->baseurl . DIRECTORY_SEPARATOR . $item->image . '" align="center" hspace="6" alt="' .
					$item->name . '" class="directory-img pull-right" />';
			elseif ($this->params->get('image') != null && $this->params->get('dr_show_image')):
				echo '<img src="' . $this->baseurl . DIRECTORY_SEPARATOR . $this->params->get('image') . '" align="center" hspace="6" alt="' .
					JText::_('COM_CHURCHDIRECTORY_NO_PHOTO_AVALIBLE') . '" class="directory-img pull-right" />';
			elseif ($this->params->get('dr_show_image')):
				echo '<img src="' . $this->baseurl . '/media/com_churchdirectory/images/200-photo_not_available.jpg" align="center" hspace="6" alt="' .
					JText::_('COM_CHURCHDIRECTORY_NO_PHOTO_AVALIBLE') . '" class="directory-img pull-right" />';
			endif;
			?>
			<div class="churchdirectory-contact">
				<?php if ($this->params->get('dr_show_member_title_link')) : ?>
					<span id="contact-name">
                    <a href="<?php echo JRoute::_(ChurchDirectoryHelperRoute::getMemberRoute($item->slug, $item->catid)); ?>">
	                    <?php echo $item->name; ?>
                    </a></span>
				<?php else : ?>
					<span id="contact-name"><?php echo $item->name; ?></span>
				<?php endif; ?>
				<?php if ($item->children && $this->params->get('dr_show_children')) :
					$children = $this->renderHelper->getChildren($item, true, $item->children);
					?><br/>
					<span><?php echo $children; ?></span>
				<?php endif; ?>
				<?php if ($item->con_position && $this->params->get('show_position')) : ?>
					<br/><span class="contact-position">
							<?php if ($item->con_position != '-1'): ?>
								<?php echo JText::_('COM_CHURCHDIRECTORY_POSITION') . ': '; ?>
							<?php endif; ?>
							<?php echo $this->renderHelper->getPosition($item->con_position); ?>
					</span>
				<?php endif; ?>
			</div>
			<?php echo $this->renderHelper->renderAddress($item, $this->params); ?>
			<?php echo $this->renderHelper->renderPhonesNumbers($item, $this->params); ?>
			<?php if (!empty($item->misc) && $this->params->get('dr_show_misc')) :
				?>
				<div class="contact-miscinfo inner">
					<span class="<?php echo $this->params->get('marker_class'); ?>">
						<?php echo $this->params->get('marker_misc'); ?>
					</span>
					<span class="contact-misc">
						<?php echo $item->misc; ?>
					</span>
				</div>
			<?php endif; ?>
		</div>
		<?php
	endif;
	if (($item->funitid != '0' && $item->attribs->get('familypostion', '0') === '0')
		|| ($item->funitid == '0' && $item->attribs->get('familypostion', '-1') === '-1' || $item->attribs->get('familypostion', '0') === '0')
	)
	{
		$this->printed_items++;

		if ($this->printed_items == $this->items_per_row && $this->printed_rows != $this->rows_per_page - 1)
		{
			echo '<!-- new column -->';
			echo '</div><div class="span' . $this->span . '" style="page-break-after:auto">';
			$this->printed_rows++;
			$this->printed_items = 0;
		}
		elseif ($this->printed_items == $this->items_per_row)
		{
			echo '</div></div><div style="clear: both; page-break-after:auto"></div><hr />';
			echo '<!-- End column -->';
			$this->printed_rows  = 0;
			$this->printed_items = 0;
		}
	}
}
