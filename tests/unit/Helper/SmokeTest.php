<?php

declare(strict_types=1);

namespace CWM\Component\Churchdirectory\Tests\Helper;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Smoke test that confirms the test harness wires up correctly:
 *   - Composer autoload reaches the package's PSR-4 roots
 *   - tests/bootstrap.php's stub autoloader resolves Joomla classes
 *
 * Failing this test means the suite as a whole is broken; failing
 * any other test should be diagnosed independently.
 *
 * @since  2.0.0
 */
#[CoversNothing]
final class SmokeTest extends TestCase
{
    public function testHarnessIsWiredUp(): void
    {
        self::assertTrue(\defined('_JEXEC'), '_JEXEC must be defined by tests/bootstrap.php');
        self::assertTrue(\defined('JPATH_ADMINISTRATOR'), 'JPATH_ADMINISTRATOR must be defined by tests/bootstrap.php');
        self::assertTrue(\defined('JPATH_SITE'), 'JPATH_SITE must be defined by tests/bootstrap.php');
    }

    public function testJoomlaTextStubResolves(): void
    {
        self::assertSame(
            'COM_CHURCHDIRECTORY_DEMO_KEY',
            \Joomla\CMS\Language\Text::_('COM_CHURCHDIRECTORY_DEMO_KEY'),
            'The stubbed Joomla\\CMS\\Language\\Text::_ should echo the key unchanged.',
        );

        self::assertSame(
            'hello world',
            \Joomla\CMS\Language\Text::sprintf('hello %s', 'world'),
            'The stubbed Text::sprintf should behave like vsprintf.',
        );
    }
}
