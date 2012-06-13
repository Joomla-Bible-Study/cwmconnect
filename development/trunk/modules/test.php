<?php
/* ------------------------------------------------------------------------
  # @author    Nashville First SDA Church
  # @copyright Copyright © 2011 nfsda.org. All rights reserved.
  # @license  http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
  # Website   http://www.nfsda.org
  ------------------------------------------------------------------------- */

defined('_JEXEC') or die;

JHtml::_('behavior.framework', true);

// variables
$app = JFactory::getApplication();
$doc = JFactory::getDocument();
$params = &$app->getParams();
$pageclass = $params->get('pageclass_sfx');
$tpath = $this->baseurl . '/templates/' . $this->template;

$this->setGenerator(null);

// load sheets and scripts
$doc->addStyleSheet($tpath . '/css/template.css.php?v=1.0.0');
$doc->addScript($tpath . '/js/modernizr.js'); // this script must be in the head
// unset scripts, put them into /js/template.js.php to minify http requests
//unset($doc->_scripts[$this->baseurl . '/media/system/js/mootools-core.js']);
//unset($doc->_scripts[$this->baseurl . '/media/system/js/core.js']);
//unset($doc->_scripts[$this->baseurl . '/media/system/js/caption.js']);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" lang="<?php echo $this->language; ?>">

    <head>
        <jdoc:include type="head" />
        <link rel="stylesheet" href="<?php echo $tpath ?>/css/<?php echo $this->params->get('colorVariation'); ?>.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $tpath ?>/css/<?php echo $this->params->get('backgroundVariation'); ?>_bg.css" type="text/css" />
        <link rel="apple-touch-icon-precomposed" href="<?php echo $tpath; ?>/apple-touch-icon-57x57.png"> <!-- iphone, ipod, android -->
            <link rel="apple-touch-icon-precomposed" sizes="72x72" href="<?php echo $tpath; ?>/apple-touch-icon-72x72.png"> <!-- ipad -->
                <link rel="apple-touch-icon-precomposed" sizes="114x114" href="<?php echo $tpath; ?>/apple-touch-icon-114x114.png"> <!-- iphone retina -->
                    <!--[if lte IE 8]>
                            <style>
                                    {behavior:url(<?php echo $tpath; ?>/js/PIE.htc);}
                            </style>
                    <![endif]-->
                    <?php if ($this->direction == 'rtl') : ?>
                        <link href="<?php echo $this->baseurl ?>/templates/nfsda_01/css/template_rtl.css" rel="stylesheet" type="text/css" />
                    <?php endif; ?>


                    </head>

                    <body id="page_bg" class="color_<?php echo $this->params->get('colorVariation'); ?> bg_<?php echo $this->params->get('backgroundVariation'); ?> width_<?php echo $this->params->get('widthStyle'); ?>">
                        <a name="up" id="up"></a>
                        <div class="centera" align="centera">
                            <div id="wrapper">
                                <div id="wrapper_r">
                                    <div id="header">
                                        <div id="header_l">
                                            <div id="header_r">
                                                <div id="logo">
                                                    <jdoc:include type="modules" name="position-1" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="tabarea">
                                        <div id="tabarea_l">
                                            <div id="tabarea_r">
                                                <div id="tabmenu">
                                                    <table cellpadding="0" cellspacing="0" class="pill">
                                                        <tr>
                                                            <td class="pill_l">&nbsp;</td>
                                                            <td class="pill_m">
                                                                <div id="pillmenu">
                                                                    <jdoc:include type="modules" name="position-15" />
                                                                </div>
                                                            </td>
                                                            <td class="pill_r">&nbsp;</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="search">
                                        <jdoc:include type="modules" name="position-0" />
                                    </div>

                                    <div id="pathway">
                                        <jdoc:include type="modules" name="position-2" />
                                    </div>

                                    <div class="clr"></div>

                                    <div id="whitebox">
                                        <div id="whitebox_t">
                                            <div id="whitebox_tl">
                                                <div id="whitebox_tr"></div>
                                            </div>
                                        </div>

                                        <div id="whitebox_m">
                                            <div id="area">
                                                <jdoc:include type="message" />

                                                <nav id="leftcolumn">
                                                    <?php if ($this->countModules('position-7')) : ?>
                                                        <jdoc:include type="modules" name="position-7" style="rounded" />
                                                    <?php endif; ?>
                                                </nav>

                                                <?php if ($this->countModules('position-7')) : ?>
                                                    <div id="maincolumn">
                                                    <?php else: echo '<div id="maincolumn_full">'; ?> <!-- Main Column Start -->
                                                    <?php endif; ?>
                                                    <?php if ($this->countModules('position-6')) : ?>
                                                        <div id="centercolumn">
                                                        <?php else: echo '<div id="centercolumn_full">'; ?> <!-- Center Column Start -->
                                                        <?php endif; ?>
                                                        <jdoc:include type="modules" name="user5" />
                                                        <jdoc:include type="modules" name="precomponent" style="xhtml" />
                                                        <jdoc:include type="component" />
                                                        <jdoc:include type="modules" name="bottom" style="xhtml"/>
                                                    </div> <!-- End Center Column -->
                                                    <?php if ($this->countModules('position-6') and JRequest::getCmd('layout') != 'form') : ?>
                                                        <aside id="rightcolumn">
                                                            <jdoc:include type="modules" name="position-6" style="nfsdaDivision" headerLevel="3"/>
                                                            <jdoc:include type="modules" name="position-8" style="nfsdaDivision" headerLevel="3"  />
                                                            <jdoc:include type="modules" name="position-3" style="nfsdaDivision" headerLevel="3"  />
                                                        </aside>
                                                    <?php endif; ?>
                                                </div> <!-- End Main Column -->
                                                <div class="clr"></div>
                                                <?php if ($this->countModules('user1 or user2')) : ?>
                                                    <div id="maindivider"></div>
                                                    <?php if ($this->countModules('user1')) : ?>
                                                        <div style="float:left; width:50%">
                                                            <jdoc:include type="modules" name="user1" style="xhtml" />
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($this->countModules('user2')) : ?>
                                                        <div style="float:left; width:50%">
                                                            <jdoc:include type="modules" name="user2" style="xhtml" />
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <div class="clr"></div>
                                                <jdoc:include type="modules" name="footer" style="xhtml" />
                                                <div class="clr"></div>
                                            </div>
                                        </div> <!-- End Area -->

                                        <div id="whitebox_b">
                                            <div id="whitebox_bl">
                                                <div id="whitebox_br"> </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="footerspacer"></div>

                                </div>

                                <div id="footer">
                                    <div id="footer_l">
                                        <div id="footer_r">
                                            <div id="footer_logo"></div>
                                            <div id="footer_center">
                                                <?php if ($this->countModules('syndicate')) : ?>
                                                    <jdoc:include type="modules" name="syndicate" />
                                                <?php endif; ?>
                                            </div>
                                            <div id="footer_counter">
                                                <?php if ($this->countModules('counter')) : ?>
                                                    <jdoc:include type="modules" name="counter" />
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                        </div>
                        <jdoc:include type="modules" name="debug" />

                    </body>
                    </html>