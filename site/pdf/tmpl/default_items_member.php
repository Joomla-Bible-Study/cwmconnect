<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * */
defined('_JEXEC') or die;

if ($this->letter != ucfirst($this->items[0]->lname[0]))
{
	$this->letter = ucfirst($this->items[0]->lname[0]);
	echo '<a style="page-break-before:auto" name="' . $this->letter . '"></a><h4>' . $this->letter . '</h4><hr />';
}
if (!empty($this->item->funitid) && $this->item->funitid != '0' && $this->item->attribs->get('familypostion') === '0') :
	?>
	<div id="directory-items<?php echo $this->item->id + 1; ?>" style="page-break-before:auto; width:100px;"
	     class="paddingitem">
		<?php
		if ($this->item->funit_image && $this->params->get('dr_show_image')) :
			echo '<img src="' . $this->baseurl . DIRECTORY_SEPARATOR . $this->item->funit_image . '" align="center" hspace="6" alt="' .
				$this->item->funit_name . '" class="directory-img pull-right" />';
		elseif ($this->params->get('image') != null && $this->params->get('dr_show_image')):
			echo '<img src="' . $this->baseurl . DIRECTORY_SEPARATOR . $this->params->get('image') . '" align="center" hspace="6" alt="' .
				JText::_('COM_CHURCHDIRECTORY_NO_PHOTO_AVALIBLE') . '" class="directory-img pull-right" />';
		elseif ($this->params->get('dr_show_image')):
			echo '<img src="' . $this->baseurl . '/media/com_churchdirectory/images/200-photo_not_available.jpg" align="center" hspace="6" alt="' .
				JText::_('COM_CHURCHDIRECTORY_NO_PHOTO_AVALIBLE') . '" class="directory-img pull-right" />';
		endif;
		?>
		<?php
		$families = $this->renderHelper->getFamilyMembers($this->item->funitid);
		?>
		<span id="contact-name"><?php echo $this->item->funit_name; ?></span><br/>
		<?php $children = $this->renderHelper->getChildren($families, true, $this->item->children);
		if ($this->params->get('dr_show_children') && $children || $this->item->children) :
			echo $children; ?>
		<?php endif; ?>
		<div class="churchdirectory-contact">
			<?php if ($this->params->get('dr_show_debug'))
			{
				echo '<a rel="popover" data-placement="bottom" data-trigger="hover" data-content="ID: ' . $this->item->id . ' ' . gettype($this->item->id) . ' <br />
						FUnit ID: ' . $this->item->funitid . ' ' . gettype($this->item->funitid) . ' <br />
						Item Count: ' . $this->printed_items . ' ' . gettype($this->printed_items) . ' <br />
						Row Count: ' . $this->printed_rows . ' ' . gettype($this->printed_rows) . ' <br />
						FamilyPosiion: ' . $this->item->attribs->get('familypostion') . ' ' . gettype($this->item->attribs->get('familypostion')) .
					'" data-original-title="Tips" href="/my_link_goes_here">debug</a>';
			} ?>
			<?php echo $this->renderHelper->renderAddress($this->item, $this->params); ?>
		</div>
		<?php
		foreach ($families as $member)
		{
			$name = $this->renderHelper->getName($member->name);
			echo $this->renderHelper->renderPhonesNumbers($member, $this->params, $name);
		} ?>
		<?php if (!empty($this->item->misc) && $this->params->get('dr_show_misc')) :
			?>
			<div class="contact-miscinfo inner">
					<span class="<?php echo $this->params->get('marker_class'); ?>">
						<?php echo $this->params->get('marker_misc'); ?>
					</span>
				<span class="contact-misc">
						<?php echo $this->item->misc; ?>
					</span>
			</div>
			<?php
		endif; ?>
	</div>
<?php endif;
