<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

?>

<div class="directory container">
	Start Screen
	<div class="directory-links">
		<div class="directory-link pull-left" style="padding-right: 10px">
			<?php
			if ($this->params->get('dr_allow_kml'))
			{
				?>
				<div><a href="<?php echo JRoute::_("index.php?option=com_churchdirectory&amp;view=directory&amp;format=kml");
					?>" class="btn">
						KML
					</a>
				</div>
				<?php
			}
			?>
		</div>
		<div class="directory-link pull-left">
			<a href="<?php echo JRoute::_('index.php?option=com_churchdirectory&view=directory&amp;format=pdf'); ?>"
			   class="btn">PDF</a>
		</div>
	</div>
</div>
