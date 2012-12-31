<?php
/**
 * QContacts Contact manager component for Joomla! 1.5
 *
 * @version 1.0.6
 * @package qcontacts
 * @author Massimo Giagnoni
 * @copyright Copyright (C) 2008 Massimo Giagnoni. All rights reserved.
 * @copyright Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
 /*
This file is part of QContacts.
QContacts is free software: you can redistribute it and/or modify
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
defined('_JEXEC') or die('Restricted access');
JToolBarHelper::title( JText::_( 'QContacts' ) .': <small><small>[ '. JText::_( 'Tools' )  .' ]</small></small>', 'generic.png' );
JSubMenuHelper::addEntry(JText::_('Contacts'), 'index.php?option=com_qcontacts');
JSubMenuHelper::addEntry(JText::_('Categories'), 'index.php?option=com_categories&section=com_qcontacts_details');
JSubMenuHelper::addEntry(JText::_('Tools'), 'index.php?option=com_qcontacts&controller=tools',true);
JSubMenuHelper::addEntry(JText::_('Info'), 'index.php?option=com_qcontacts&view=info');
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