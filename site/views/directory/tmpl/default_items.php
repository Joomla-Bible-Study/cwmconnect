<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * */
defined('_JEXEC') or die;

$heading = null;

?>
<?php if (empty($this->items)) : ?>
	<p xmlns="http://www.w3.org/1999/html"><?php echo JText::_('COM_CHURCHDIRECTORY_NO_MEMBERS'); ?></p>
<?php endif; ?>
<?php

foreach ($this->items as $item)
{
	if ($this->printed_items == '0' && $this->printed_rows == '0')
	{
		echo '<!-- new start ' . $item->name . '-->';
		echo '<table class="table churchdirectory-table"><tbody><tr><td class="span6">';
	}
	if ($this->letter != ucfirst($this->items[0]->lname[0]))
	{
		$this->letter = ucfirst($this->items[0]->lname[0]);
		echo '<a name="' . $this->letter . '"></a><h2>' . $this->letter . '</h2><hr />';
	}
	if ($item->funitid != '0' && $item->attribs->get('familypostion') === '0') :
		?>
		<div id="directory-items<?php echo $item->id + 1; ?>" class="paddingitem">
			<?php
			if ($item->funit_image && $this->params->get('dr_show_image')) :
				echo '<img src="' . $this->baseurl . DIRECTORY_SEPARATOR . $item->funit_image . '" align="center" hspace="6" alt="' . $item->funit_name . '" class="directory-img pull-right" />';
			elseif ($this->params->get('image') != null && $this->params->get('dr_show_image')):
				echo '<img src="' . $this->baseurl . DIRECTORY_SEPARATOR . $this->params - get('image') . '" align="center" hspace="6" alt="' . JText::_('COM_CHURCHDIRECTORY_NO_PHOTO_AVALIBLE') . '" class="directory-img pull-right" />';
			elseif ($this->params->get('dr_show_image')):
				echo '<img src="' . $this->baseurl . '/media/com_churchdirectory/images/200-photo_not_available.jpg" align="center" hspace="6" alt="' . JText::_('COM_CHURCHDIRECTORY_NO_PHOTO_AVALIBLE') . '" class="directory-img pull-right" />';
			endif;
			?>
			<?php
			$families = $this->renderHelper->getFamilyMembersPage($item->funit_id); ?>
			<span id="contact-name"><?php echo $item->funit_name; ?></span>
			<?php
			foreach ($families as $member)
			{
				if ($member->attribs->get('familypostion') == '2')
				{
					echo $member->name;
				}
			}?>
			<?php if ($item->children && $this->params->get('dr_show_children')) :
				?>
				<p>
					<?php echo '<span class="jicons-text">' . JText::_('COM_CHURCHDIRECTORY_CHILDREN') . ': </span>' . $item->children; ?>
				</p>
			<?php endif; ?>
			<div class="churchdirectory-contact">
				<?php if ($this->params->get('dr_show_debug')) : ?>
					<p>ID: <?php echo $item->funit_id; ?> <br/>
						Item Count: <?php echo $this->printed_items; ?> <br/>
						Row Count: <?php echo $this->printed_rows; ?> </p>
				<?php endif; ?>
				<?php if (($this->params->get('address_check') > 0) && ($item->address || $item->suburb || $item->state || $item->country || $item->postcode)) : ?>
				<div class="churchdirectory-address">
					<?php if ($this->params->get('address_check') > 0) : ?>
					<span class="<?php echo $this->params->get('marker_class'); ?>">
                                <?php echo $this->params->get('marker_address'); ?>
                            </span>
					<address>
						<?php endif; ?>
						<?php if ($item->address && $this->params->get('dr_show_street_address')) : ?>
							<span class="churchdirectory-street">
                                    <?php echo nl2br($item->address); ?>
                                </span>
						<?php endif; ?>
						<?php if ($item->suburb && $this->params->get('dr_show_suburb')) : ?>
							<span class="churchdirectory-suburb">
                                    <?php echo $item->suburb; ?>
                                </span>
						<?php endif; ?>
						<?php if ($item->state && $this->params->get('dr_show_state')) : ?>
							<span class="churchdirectory-state">
                                    <?php echo $item->state; ?>
                                </span>
						<?php endif; ?>
						<?php if ($item->postcode && $this->params->get('dr_show_postcode')) : ?>
							<span class="churchdirectory-postcode">
                                    <?php echo $item->postcode; ?>
                                </span>
						<?php endif; ?>
						<?php if ($item->country && $this->params->get('dr_show_country')) : ?>
							<span class="churchdirectory-country">
                                    <?php echo $item->country; ?>
                                </span>
						<?php endif; ?>
						<?php endif; ?>

						<?php if ($this->params->get('address_check') > 0 && ($item->address || $item->suburb || $item->state || $item->country || $item->postcode)) : ?>
					</address>
				</div>
			<?php endif; ?>
			</div>
			<div class="clearfix"></div>
			<?php if (($this->params->get('other_check') > 0) && ($item->email_to || $item->telephone || $item->fax || $item->mobile || $item->webpage || $item->spouse || $item->children)) : ?>
			<div class="churchdirectory-churchdirectoryinfo inner">
				<?php endif; ?>
				<?php
				foreach ($families as $member)
				{
					$name = $this->renderHelper->getName($member->name);
					?>
					<?php if ($member->email_to && $this->params->get('dr_show_email')) : ?>
					<p>
                        <span class="<?php echo $this->params->get('marker_class'); ?>">
                            <?php echo $name->firstname . ' : ' . $this->params->get('marker_email'); ?>
                        </span>
                        <span class="churchdirectory-emailto">
                            <?php echo $member->email_to; ?>
                        </span>
					</p>
				<?php endif; ?>

					<?php if ($member->telephone && $this->params->get('dr_show_telephone')) : ?>
					<p>
                        <span class="<?php echo $this->params->get('marker_class'); ?>">
                            <?php echo $name->firstname . ' : ' . $this->params->get('marker_telephone'); ?>
                        </span>
                        <span class="churchdirectory-telephone">
                            <?php echo nl2br($member->telephone); ?>
                        </span>
					</p>
				<?php endif; ?>
					<?php if ($member->fax && $this->params->get('dr_show_fax')) : ?>
					<p>
                        <span class="<?php echo $this->params->get('marker_class'); ?>">
                            <?php echo $name->firstname . ' : ' . $this->params->get('marker_fax'); ?>
                        </span>
                        <span class="churchdirectory-fax">
                            <?php echo nl2br($member->fax); ?>
                        </span>
					</p>
				<?php endif; ?>
					<?php if ($member->mobile && $this->params->get('dr_show_mobile')) : ?>

					<span class="<?php echo $this->params->get('marker_class'); ?>">
                        <?php echo $name->firstname . ' : ' . $this->params->get('marker_mobile'); ?>
                    </span>
					<span class="churchdirectory-mobile">
                        <?php echo nl2br($member->mobile); ?>
                    </span>

				<?php endif; ?>
					<?php if ($member->webpage && $this->params->get('dr_show_webpage')) : ?>
					<p>
                        <span class="<?php echo $name->firstname . ' : ' . $this->params->get('marker_class'); ?>">
                        </span>
                        <span class="churchdirectory-webpage">
                            <a href="<?php echo $member->webpage; ?>" target="_blank">
	                            <?php echo JText::_('COM_CHURCHDIRECTORY_WEBPAGE'); ?></a>
                        </span>
					</p>
				<?php endif; ?>
				<?php } ?>
				<?php if ($this->params->get('other_check') > 0 && ($item->email_to || $item->telephone || $item->fax || $item->mobile || $item->webpage || $item->spouse || $item->children)) : ?>
			</div>
		<?php endif; ?>
			<div class="clearfix"></div>
			<?php if (!empty($item->misc) && $this->params->get('dr_show_misc')) :
				?>
				<div class="contact-miscinfo inner">
					<div class="<?php echo $this->params->get('marker_class'); ?>">
						<?php echo $this->params->get('marker_misc'); ?>
					</div>
					<div class="contact-misc">
						<?php echo $item->misc; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	<?php
	elseif ($item->funitid == '0'):
		?>
		<div id="directory-items<?php echo $item->id + 1; ?>"
		     class="paddingitem">
			<?php
			if ($item->image && $this->params->get('dr_show_image')) :
				echo '<img src="' . $this->baseurl . DIRECTORY_SEPARATOR . $item->image . '" align="center" hspace="6" alt="' . $item->name . '" class="directory-img pull-right" />';
			elseif ($this->params->get('image') != null && $this->params->get('dr_show_image')):
				echo '<img src="' . $this->baseurl . DIRECTORY_SEPARATOR . $this->params - get('image') . '" align="center" hspace="6" alt="' . JText::_('COM_CHURCHDIRECTORY_NO_PHOTO_AVALIBLE') . '" class="directory-img pull-right" />';
			elseif ($this->params->get('dr_show_image')):
				echo '<img src="' . $this->baseurl . '/media/com_churchdirectory/images/200-photo_not_available.jpg" align="center" hspace="6" alt="' . JText::_('COM_CHURCHDIRECTORY_NO_PHOTO_AVALIBLE') . '" class="directory-img pull-right" />';
			endif;
			?>
			<div class="churchdirectory-contact">
				<?php if ($this->params->get('dr_show_debug')) : ?>
					<p>ID: <?php echo $item->funit_id; ?> <br/>
						Item Count: <?php echo $this->printed_items; ?> <br/>
						Row Count: <?php echo $this->printed_rows; ?> </p>
				<?php endif; ?>
				<?php if ($this->params->get('dr_show_member_title_link')) : ?>
					<span id="contact-name">
                    <a href="<?php echo JRoute::_(ChurchDirectoryHelperRoute::getMemberRoute($item->slug, $item->catid)); ?>">
	                    <?php echo $item->name; ?>
                    </a></span>
				<?php else : ?>
					<span id="contact-name"><?php echo $item->name; ?></span>
				<?php endif; ?>
				<?php if ($item->children && $this->params->get('dr_show_children')) :
					?>
					<p>
						<?php echo '<span class="jicons-text">' . JText::_('COM_CHURCHDIRECTORY_CHILDREN') . ': </span>' . $item->children; ?>
					</p>
				<?php endif; ?>
				<?php if ($item->con_position && $this->params->get('show_position')) : ?>
					<dl class="contact-position dl-horizontal">
						<dt>
							<?php if ($item->con_position != '-1'): ?>
								<?php echo JText::_('COM_CHURCHDIRECTORY_POSITION'); ?>
							<?php endif; ?>
						</dt>
						<dd>
							<?php echo $this->renderHelper->getPosition($item->con_position); ?>
						</dd>
					</dl>
				<?php endif; ?>
				<div class="clearfix"></div>
				<?php if (($this->params->get('address_check') > 0) && ($item->address || $item->suburb || $item->state || $item->country || $item->postcode)) : ?>
				<div class="churchdirectory-address">
					<?php if ($this->params->get('address_check') > 0) : ?>
					<span class="<?php echo $this->params->get('marker_class'); ?>">
                                <?php echo $this->params->get('marker_address'); ?>
                            </span>
					<address>
						<?php endif; ?>
						<?php if ($item->address && $this->params->get('dr_show_street_address')) : ?>
							<span class="churchdirectory-street">
                                    <?php echo nl2br($item->address); ?>
                                </span>
						<?php endif; ?>
						<?php if ($item->suburb && $this->params->get('dr_show_suburb')) : ?>
							<span class="churchdirectory-suburb">
                                    <?php echo $item->suburb; ?>
                                </span>
						<?php endif; ?>
						<?php if ($item->state && $this->params->get('dr_show_state')) : ?>
							<span class="churchdirectory-state">
                                    <?php echo $item->state; ?>
                                </span>
						<?php endif; ?>
						<?php if ($item->postcode && $this->params->get('dr_show_postcode')) : ?>
							<span class="churchdirectory-postcode">
                                    <?php echo $item->postcode; ?>
                                </span>
						<?php endif; ?>
						<?php if ($item->country && $this->params->get('dr_show_country')) : ?>
							<span class="churchdirectory-country">
                                    <?php echo $item->country; ?>
                                </span>
						<?php endif; ?>
						<?php endif; ?>

						<?php if ($this->params->get('address_check') > 0 && ($item->address || $item->suburb || $item->state || $item->country || $item->postcode)) : ?>
					</address>
				</div>
			<?php endif; ?>
			</div>
			<div class="clearfix"></div>
			<?php if (($this->params->get('other_check') > 0) && ($item->email_to || $item->telephone || $item->fax || $item->mobile || $item->webpage || $item->spouse || $item->children)) : ?>
			<div class="churchdirectory-churchdirectoryinfo inner">
				<?php endif; ?>
				<?php if ($item->email_to && $this->params->get('dr_show_email')) : ?>
					<p>
                        <span class="<?php echo $this->params->get('marker_class'); ?>">
                            <?php echo $this->params->get('marker_email'); ?>
                        </span>
                        <span class="churchdirectory-emailto">
                            <?php echo $item->email_to; ?>
                        </span>
					</p>
				<?php endif; ?>

				<?php if ($item->telephone && $this->params->get('dr_show_telephone')) : ?>
					<p>
                        <span class="<?php echo $this->params->get('marker_class'); ?>">
                            <?php echo $this->params->get('marker_telephone'); ?>
                        </span>
                        <span class="churchdirectory-telephone">
                            <?php echo nl2br($item->telephone); ?>
                        </span>
					</p>
				<?php endif; ?>
				<?php if ($item->fax && $this->params->get('dr_show_fax')) : ?>
					<p>
                        <span class="<?php echo $this->params->get('marker_class'); ?>">
                            <?php echo $this->params->get('marker_fax'); ?>
                        </span>
                        <span class="churchdirectory-fax">
                            <?php echo nl2br($item->fax); ?>
                        </span>
					</p>
				<?php endif; ?>
				<?php if ($item->mobile && $this->params->get('dr_show_mobile')) : ?>

					<span class="<?php echo $this->params->get('marker_class'); ?>">
                        <?php echo $this->params->get('marker_mobile'); ?>
                    </span>
					<span class="churchdirectory-mobile">
                        <?php echo nl2br($item->mobile); ?>
                    </span>

				<?php endif; ?>
				<?php if ($item->webpage && $this->params->get('dr_show_webpage')) : ?>
					<p>
                        <span class="<?php echo $this->params->get('marker_class'); ?>">
                        </span>
                        <span class="churchdirectory-webpage">
                            <a href="<?php echo $item->webpage; ?>" target="_blank">
	                            <?php echo JText::_('COM_CHURCHDIRECTORY_WEBPAGE'); ?></a>
                        </span>
					</p>
				<?php endif; ?>
				<?php if ($this->params->get('other_check') > 0 && ($item->email_to || $item->telephone || $item->fax || $item->mobile || $item->webpage || $item->spouse || $item->children)) : ?>
			</div>
		<?php endif; ?>
			<div class="clearfix"></div>
			<?php if (!empty($item->misc) && $this->params->get('dr_show_misc')) :
				?>
				<div class="contact-miscinfo inner">
					<div class="<?php echo $this->params->get('marker_class'); ?>">
						<?php echo $this->params->get('marker_misc'); ?>
					</div>
					<div class="contact-misc">
						<?php echo $item->misc; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	<?php
	endif;

	if (($item->funitid != '0' && $item->attribs->get('familypostion', '0') == '0') || ($item->funitid == '0' && $item->attribs->get('familypostion', '-1') == '-1' || $item->attribs->get('familypostion', '0') == '0'))
	{
		$this->printed_items++;
		if ($this->printed_items == $this->items_per_row && $this->printed_rows != $this->rows_per_page - 1)
		{
			echo '<!-- new column -->';
			echo '</td><td class="span6">';
			$this->printed_rows++;
			$this->printed_items = 0;
		}
		elseif ($this->printed_items == $this->items_per_row)
		{
			echo '</td></tr></tbody></table><div style="page-break-after:always"></div>';
			echo '<!-- End column -->';
			$this->printed_rows  = 0;
			$this->printed_items = 0;
		}
	}
}
?>
