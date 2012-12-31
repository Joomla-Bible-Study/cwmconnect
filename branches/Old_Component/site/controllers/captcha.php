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

class QContactsControllerCaptcha extends QContactsController {
	function display() {
		global $mainframe;
		
		require_once JPATH_COMPONENT . DS . 'includes' . DS . 'securimage' . DS . 'securimage.php';
		$model =& $this->getModel('contact');
		$contact = $model->getContact();
		$params =& $mainframe->getParams('com_qcontacts');
		if(is_object($contact)) {
			$cparams= new JParameter($contact->params);
			$params->merge($cparams);
		}
		$img = new securimage();
		$img->use_wordlist = false;
		$img->ttf_file = JPATH_COMPONENT_ADMINISTRATOR . DS . 'fonts' . DS . $params->get('captcha_font','elephant.ttf');
		$img->code_length = $params->get('captcha_length',4);
		$img->image_width = $params->get('captcha_width',150);
		$img->image_height = $params->get('captcha_height',45);
		$img->font_size = $params->get('captcha_fsize',24);
		$img->image_bg_color = $params->get('captcha_bgcolor','#e3daed');
		$img->draw_lines = $params->get('captcha_lines',1);
		$img->line_color = $params->get('captcha_lines_color','#80bfff');
		$img->line_distance = $params->get('captcha_lines_distance','5');
		$img->arc_linethrough = $params->get('captcha_arclines',1);
		$img->arc_line_colors = $params->get('captcha_arclines_color','#8080ff');
		$tc = $params->get('captcha_txtcolor','#0a68dd,#f65c47,#8d32fd');
		$t = explode(',',$tc);
		$img->text_color = $t[0];
		$img->use_multi_text = false;
		if(count($t) > 1) {
			$img->multi_text_color = implode(',',$t);
			$img->use_multi_text = true;
		}
		$img->text_minimum_distance = $img->font_size - 2;
		$img->text_maximum_distance = $img->text_minimum_distance + 5;
		
		$document =& JFactory::getDocument();
		$document->setMimeEncoding('image/png');
		
		$img->show();
		
	}
}
?>