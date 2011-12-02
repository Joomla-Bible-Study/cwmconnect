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
defined( '_JEXEC' ) or die( 'Restricted access' ); ?>
<h1 class="componentheading<?php echo $this->params->get('pageclass_sfx'); ?>">
	<?php echo JText::_('CONFIRMATION_TITLE'); ?>
</h1>
<div id="churchdirectory-confirm<?php echo $this->params->get('pageclass_sfx'); ?>">
<?php echo JText::_('CONFIRMATION_MESSAGE'); ?>
<span class="linkback">
<a href="<?php echo JRoute::_('index.php?option=com_churchdirectory&view=contact&id='.$this->contact->slug.'&catid='.$this->contact->catslug, false);?>"><?php echo JText::_('Go Back'); ?></a>
</span>
</div>
