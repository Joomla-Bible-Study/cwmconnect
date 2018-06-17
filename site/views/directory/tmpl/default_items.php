<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * */
defined('_JEXEC') or die;

/** @var $this ChurchDirectoryViewDirectory */
$heading = null;

foreach ($this->items as $item)
{
	$this->item = $item;

	$this->subcount--;

	if ($this->letter != ucfirst($this->items[0]->lname[0]))
	{
		$this->letter = ucfirst($this->items[0]->lname[0]);

		// Set a bookmark for the current position
		$this->pdf->Bookmark($this->letter);
	}

	if (!empty($item->funitid) && $item->funitid != '0' && $item->attribs->get('familypostion') === '0') :
		?>
        <div id="directory-items<?php echo $item->id + 1; ?>" class="cd_items paddingitem"
             style="page-break-inside: avoid">
            <table width="100%">
                <tr>
                    <td class="cd-image-td">
                        <div class="cd-image">
							<?php
							if ($item->funit_image && $this->params->get('dr_show_image')) :
								echo '<img src="' . $this->baseurl . DIRECTORY_SEPARATOR . $item->funit_image . '" align="center" alt="' .
									$item->funit_name . '" class="directory-img" />';
                            elseif ($this->params->get('image') != null && $this->params->get('dr_show_image')):
								echo '<img src="' . $this->baseurl . DIRECTORY_SEPARATOR . $this->params->get('image') . '"  align="center" alt="' .
									JText::_('COM_CHURCHDIRECTORY_NO_PHOTO_AVALIBLE') . '" class="directory-img" />';
                            elseif ($this->params->get('dr_show_image')):
								echo '<img src="' . $this->baseurl . '/media/com_churchdirectory/images/200-photo_not_available.jpg"  align="center" alt="' .
									JText::_('COM_CHURCHDIRECTORY_NO_PHOTO_AVALIBLE') . '" class="directory-img" />';
							endif;
							?>
                        </div>
                    </td>
                    <td class="cd-content">
						<?php
						$families = $this->renderHelper->getFamilyMembers($item->funitid);
						?>
                        <span class="cd-name"><?php echo $item->funit_name; ?></span><br/>
						<?php $children = $this->renderHelper->getChildren($families, false, $item->children);
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
                            <div class="cd-miscinfo">
								<span class="<?php echo $this->params->get('marker_class'); ?>">
									<?php echo $this->params->get('marker_misc'); ?>
								</span>
                                <span class="cd-misc">
									<?php echo $item->misc; ?>
								</span>
                            </div>
						<?php
						endif; ?>
                    </td>
                </tr>
            </table>
        </div>
	<?php
    elseif ($item->funitid === '0'):
		?>
        <div id="directory-items<?php echo $item->id + 1; ?>" class="cd_items paddingitem"
             style="page-break-inside: avoid">
            <table width="100%">
                <tr>
                    <td class="cd-image">
                        <div class="cd-image">
							<?php
							if ($item->image && $this->params->get('dr_show_image')) :
								echo '<img src="' . $this->baseurl . DIRECTORY_SEPARATOR . $item->image . '"  align="center" alt="' .
									$item->name . '" class="directory-img" />';
                            elseif ($this->params->get('image') != null && $this->params->get('dr_show_image')):
								echo '<img src="' . $this->baseurl . DIRECTORY_SEPARATOR . $this->params->get('image') . '" align="center" alt="' .
									JText::_('COM_CHURCHDIRECTORY_NO_PHOTO_AVALIBLE') . '" class="directory-img" />';
                            elseif ($this->params->get('dr_show_image')):
								echo '<img src="' . $this->baseurl . '/media/com_churchdirectory/images/200-photo_not_available.jpg" align="center" alt="' .
									JText::_('COM_CHURCHDIRECTORY_NO_PHOTO_AVALIBLE') . '" class="directory-img" />';
							endif;
							?>
                        </div>
                    </td>
                    <td class="cd-content">
                        <div class="churchdirectory-contact">
							<?php if ($this->params->get('dr_show_member_title_link')) : ?>
                                <span id="cd-name">
				                    <a href="<?php echo JRoute::_(ChurchDirectoryHelperRoute::getMemberRoute($item->slug, $item->catid)); ?>">
					                    <?php echo $item->name; ?>
				                    </a>
								</span>
							<?php else : ?>
                                <span class="cd-name"><?php echo $item->name; ?></span>
							<?php endif; ?>
							<?php if ($item->children && $this->params->get('dr_show_children')) :
								$children = $this->renderHelper->getChildren($item, false, $item->children);
								?><br/>
                                <span><?php echo $children; ?></span>
							<?php endif; ?>
							<?php if ($item->con_position && $this->params->get('show_position')) : ?>
                                <br/>
                                <span class="cd-position">
							<?php if ($item->con_position != '-1'): ?>
								<?php echo JText::_('COM_CHURCHDIRECTORY_POSITIONS') . ': '; ?>
							<?php endif; ?>
							<?php echo $this->renderHelper->getPosition($item->con_position); ?>
							</span>
							<?php endif; ?>
                        </div>
						<?php echo $this->renderHelper->renderAddress($item, $this->params); ?>
						<?php echo $this->renderHelper->renderPhonesNumbers($item, $this->params); ?>
						<?php if (!empty($item->misc) && $this->params->get('dr_show_misc')) :
							?>
                            <div class="cd-miscinfo">
								<span class="<?php echo $this->params->get('marker_class'); ?>">
									<?php echo $this->params->get('marker_misc'); ?>
								</span>
                                <span class="cd-misc">
									<?php echo $item->misc; ?>
								</span>
                            </div>
						<?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
	<?php
	endif;
}
