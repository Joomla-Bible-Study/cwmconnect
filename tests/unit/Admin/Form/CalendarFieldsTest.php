<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Admin\Form;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A calendar field with no `filter` attribute converts its value into the
 * user's timezone for display (CalendarField::setup() defaults to user_utc)
 * but stores what comes back unconverted (CalendarField::filter() reads the
 * bare attribute). West of UTC, every save then moves the value back a day.
 * Each calendar field must therefore say how it converts, and the birth and
 * anniversary dates, being calendar dates rather than moments, convert nothing.
 */
#[CoversNothing]
final class CalendarFieldsTest extends TestCase
{
    /**
     * Every XML file in the package that can define form fields.
     */
    private const array FORM_GLOBS = [
        'admin/forms/*.xml',
        'admin/config.xml',
        'site/forms/*.xml',
        'modules/site/*/*.xml',
        'plugins/*/*/*.xml',
    ];

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function calendarFields(): iterable
    {
        foreach (self::FORM_GLOBS as $pattern) {
            foreach (glob(self::root() . '/' . $pattern) ?: [] as $path) {
                $xml = simplexml_load_string((string) file_get_contents($path));

                if ($xml === false) {
                    continue;
                }

                $file = substr($path, \strlen(self::root()) + 1);

                foreach ($xml->xpath('//field[@type="calendar"]') ?: [] as $field) {
                    $name = (string) $field['name'];

                    yield $file . ' ' . $name => [$file, $name, (string) $field['filter']];
                }
            }
        }
    }

    #[Test]
    #[DataProvider('calendarFields')]
    public function everyCalendarFieldNamesHowItConverts(string $file, string $name, string $filter): void
    {
        self::assertNotSame('', $filter, "$file: calendar field `$name` needs a filter (user_utc for a moment, raw for a calendar date).");
    }

    #[Test]
    public function birthAndAnniversaryDatesConvertNoTimezone(): void
    {
        $filters = [];

        foreach (self::calendarFields() as [$file, $name, $filter]) {
            if (\in_array($name, ['birthdate', 'anniversary'], true)) {
                $filters[$file . ' ' . $name] = $filter;
            }
        }

        ksort($filters);

        self::assertSame(
            [
                'admin/forms/member.xml anniversary'  => 'raw',
                'admin/forms/member.xml birthdate'    => 'raw',
                'site/forms/myprofile.xml anniversary' => 'raw',
                'site/forms/myprofile.xml birthdate'   => 'raw',
            ],
            $filters,
        );
    }

    private static function root(): string
    {
        return \dirname(__DIR__, 4);
    }
}
