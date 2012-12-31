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
defined( '_JEXEC' ) or die( 'Restricted access' );
?>
<?php if ($this->params->get('show_page_title', 1)) : ?>
<div class="componentheading<?php echo $this->params->get('pageclass_sfx');?>">
	<?php echo $this->escape($this->params->get('page_title')); ?>
</div>
<?php endif; ?>
<div class="qcontacts-section<?php echo $this->params->get('pageclass_sfx');?>">
<ul>
	<?php foreach ($this->categories as $category) : ?>
		<?php if (!$this->params->get('show_empty_categories') && !$category->numitems) continue; ?>
		<li>
			<a href="<?php echo $category->link; ?>" class="category">
				<?php echo $category->title;?></a>
			<?php if ($this->params->get('show_cat_num_contacts')) : ?>
			&nbsp;
			<span class="small">
				( <?php echo $category->numitems; ?> )
			</span>
			<?php endif; ?>
			<?php if ($this->params->get('show_category_description', 1) && $category->description) : ?>
			<br />
			<?php echo $category->description; ?>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
</div>