<?php

declare(strict_types=1);

namespace Joomla\CMS\MVC\View;

/**
 * Test-only stub of Joomla\CMS\MVC\View\HtmlView. Provides a lenient
 * constructor plus escape()/setLayout()/getModel()/display() so component
 * views can be instantiated and their pure presentation helpers exercised
 * in unit tests without a Joomla install.
 */
class HtmlView
{
    public function __construct(array $config = []) {}

    public function escape($var)
    {
        return htmlspecialchars((string) $var, \ENT_QUOTES, 'UTF-8');
    }

    public function getModel($name = null)
    {
        return null;
    }

    public function setLayout($layout)
    {
        return $this;
    }

    public function display($tpl = null) {}
}
