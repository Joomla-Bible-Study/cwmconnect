<?php
/**
 * Default view for GeoUpdate.Status
 * @package             ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * @todo 		need to clean up code to have translation.
 * @package		ChurchDirectory.Admin
 * @since		1.7.5
 */
?>
<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory&view=geostatus'); ?>" method="post"
      name="adminForm" id="adminForm">
    <div id="j-main-container">
		<?php if (!empty($this->info)) {
		foreach ($this->info AS $data):?>
            <table id="geostatus" class="row-fluid adminlist">
                <thead>
                <tr>
                    <th>
                        <span>Name</span>
                    </th>
					<th>
						Address
					</th>
                    <th>
                        State
                    </th>
                    <th width="10%">
                        Zip
                    </th>
                    <th>
                        <span>Status</span>
                    </th>
					<th  width="1%" class="nowrap center hidden-phone">
						<?php echo JText::_('JGRID_HEADING_ID'); ?>
					</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="name small" style="background-color: #fae4e0">
						<?php echo $data->name; ?>
                    </td>
					<td class="small">
						<?php echo $data->address; ?>
					</td>
                    <td class="small">
						<?php echo $data->state; ?>
                    </td>
                    <td class="small">
						<?php echo $data->postcode; ?>
                    </td>
                    <td class="status small">
						<?php echo $data->status; ?>
                    </td>
					<td align="center hidden-phone">
						<?php echo $data->id; ?>
					</td>
                </tr>
                </tbody>
            </table>
			<?php endforeach;
	} ?>
        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="boxchecked" value="0"/>
		<?php echo JHtml::_('form.token'); ?>
    </div>
</form>