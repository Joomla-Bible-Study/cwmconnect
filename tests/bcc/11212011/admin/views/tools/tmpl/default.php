<?php
/**
 * ChurchDirectory Contact manager component for Joomla! 1.5 and 1.6
 *
 * @version 1.6.0
 * @package churchdirectory
 * @author NFSDA
 * @copyright Copyright (C) 2011 NFSDA. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
 /*
This file is part of ChurchDirectory.
ChurchDirectory is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');
?>

	<div class="col width-60">
	<form action="index.php" method="post" name="adminForm">
	<fieldset class="adminform">
		<legend><?php echo JText::_( 'Backup Global Configuration' ); ?></legend>
	<table class="admintable">
	<tr>
		<td>
			<?php echo JText::_( 'BACKUP_CONFIG' ); ?>
		</td>
	</tr>
	<tr>
		<td align="center">
		<input type="submit" name="submit" value="<?php echo JText::_( 'Execute' ); ?>" />
		</td>
	</tr>
	</table>
	</fieldset>
	<input type="hidden" name="option" value="<?php echo $option; ?>" />
	<input type="hidden" name="task" value="backup" />
	<input type="hidden" name="controller" value="tools" />
	<?php echo JHTML::_( 'form.token' ); ?>
	</form>

	<form action="index.php" method="post" name="adminForm">
	<fieldset class="adminform">
		<legend><?php echo JText::_( 'Restore Global Configuration' ); ?></legend>
	<table class="admintable">
	<tr>
		<td>
			<?php echo JText::_( 'RESTORE_CONFIG' ); ?>
		</td>
	</tr>
	<tr>
		<td align="center">
		<input type="submit" name="submit" value="<?php echo JText::_( 'Execute' ); ?>" />
		</td>
	</tr>
	</table>
	</fieldset>
	<input type="hidden" name="option" value="<?php echo $option; ?>" />
	<input type="hidden" name="task" value="restore" />
	<input type="hidden" name="controller" value="tools" />
	<?php echo JHTML::_( 'form.token' ); ?>
	</form>
	<form action="index.php" method="post" name="adminForm">
	<fieldset class="adminform">
		<legend><?php echo JText::_( 'Import Contacts' ); ?></legend>
	<table class="admintable">
	<tr>
		<td>
			<?php echo JText::_( 'TOOLS_IMPORT_INFO' ); ?>
		</td>
	</tr>
	<tr>
		<td align="center">
		<input type="submit" name="submit" value="<?php echo JText::_( 'Execute' ); ?>" />
		</td>
	</tr>
	</table>
	</fieldset>

	<input type="hidden" name="option" value="<?php echo $option; ?>" />
	<input type="hidden" name="task" value="import" />
	<input type="hidden" name="controller" value="tools" />
	<?php echo JHTML::_( 'form.token' ); ?>
	</form>
</div>
<div class="clr"></div>