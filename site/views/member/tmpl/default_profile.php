<?php
/**
 * Sub view member for profile
 *
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
?>
<?php if (JPluginHelper::isEnabled('user', 'profile')) :
	$fields = $this->item->profile->getFieldset('profile');
	?>
	<div class="churchdirectory-profile" id="users-profile-custom">
		<dl class="dl-horizontal">
			<?php
			foreach ($fields as $profile) :
				if ($profile->value) :
					echo '<dt>' . $profile->label . '</dt>';
					$profile->text = htmlspecialchars($profile->value, ENT_COMPAT, 'UTF-8');

					switch ($profile->id) :
						case "profile_website":
							$v_http = substr($profile->profile_value, 0, 4);

							if ($v_http == "http") :
								echo '<dd><a href="' . $profile->text . '">' . $profile->text . '</a></dd>';
							else :
								echo '<dd><a href="http://' . $profile->text . '">' . $profile->text . '</a></dd>';
							endif;
							break;

						default:
							echo '<dd>' . $profile->text . '</dd>';
							break;
					endswitch;
				endif;
			endforeach;
			?>
		</dl>
	</div>
<?php endif; ?>
