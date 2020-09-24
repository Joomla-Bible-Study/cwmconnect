<?php
/**
 * Default view for Birthday and Annversary
 *
 * @package     ChurchDirectory
 * @subpackage  Model.BirthdayAnniversary
 * @copyright   2007 - 2016 (C) Joomla Bible Study Team All rights reserved
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link        http://www.christianwebministries.org
 * */

defined('_JEXEC') or die;
$params = $this->params;

/* Get the RenderHelper Class for the Module to us */
$render = new ChurchDirectoryRenderHelper;

/* Return members that have Birthdays of this month. */
$birthdays = $render->getBirthdays($params);

/* Return members that have Anniversary of this month. */
$anniversary = $render->getAnniversary($params);
?>
<div class="churchdirectory_model_wrapper cd_margin_top_20">
	<?php if ($birthdays): ?>
		<?php if ($params->get('show_page_heading', 1)) : ?>
			<h3>
				<?php echo JText::_('COM_BIRTHDAYANNIVERSARY_BIRTHDAY'); ?>
			</h3>
		<?php endif; ?>
		<table style="width: 100%">
			<tr>
				<th class="churchdirectory_model_theder"><?php echo JText::_('COM_BIRTHDAYANNIVERSARY_DAY') ?></th>
				<th class="churchdirectory_model_theder"><?php echo JText::_('COM_BIRTHDAYANNIVERSARY_MEMBER_NAME') ?></th>
			</tr>
			<?php foreach ($birthdays AS $bday): ?>
				<tr>
					<td class="churchdirectory_model_row"><?php echo $bday['day']; ?></td>
					<td class="churchdirectory_model_row"><?php echo $bday['name']; ?></td>
				</tr>
			<?php endforeach; ?>
		</table>
	<?php endif; ?>
	<br/>
	<?php if ($anniversary): ?>
		<?php if ($params->get('show_page_heading', 1)) : ?>
			<h3>
				<?php echo JText::_('COM_BIRTHDAYANNIVERSARY_ANNIVERSARY'); ?>
			</h3>
		<?php endif; ?>
		<table style="width: 100%">
			<tr>
				<th class="churchdirectory_model_theder"><?php echo JText::_('COM_BIRTHDAYANNIVERSARY_DAY') ?></th>
				<th class="churchdirectory_model_theder"><?php echo JText::_('COM_BIRTHDAYANNIVERSARY_MEMBER_NAME') ?></th>
			</tr>
			<?php foreach ($anniversary AS $annday): ?>
				<tr>
					<td class="churchdirectory_model_row"><?php echo $annday['day']; ?></td>
					<td class="churchdirectory_model_row"><?php echo $annday['name']; ?></td>
				</tr>
			<?php endforeach; ?>
		</table>
	<?php endif; ?>
</div>
