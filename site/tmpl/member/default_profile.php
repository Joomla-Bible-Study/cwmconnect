<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Site\Helper\RenderHelper;
use CWM\Component\Cwmconnect\Site\Helper\RouteHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

?>
<?php if (JPluginHelper::isEnabled('user', 'profile')) :
    $fields = $this->item->profile->getFieldset('profile');
    ?>
	<div class="cwmconnect-profile" id="users-profile-custom">
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
