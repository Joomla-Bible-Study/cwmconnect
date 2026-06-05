<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Geocode;

use CWM\Component\Cwmconnect\Administrator\Service\Geocode\AddressNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the pure address tidying used before a geocode lookup.
 */
#[CoversClass(AddressNormalizer::class)]
final class AddressNormalizerTest extends TestCase
{
    /**
     * @return  array<string, array{0: string, 1: string}>
     */
    public static function streetProvider(): array
    {
        return [
            'unit suffix'      => ['5170 Hickory Hollow Pkwy Unit 202', '5170 Hickory Hollow Pkwy'],
            'apt suffix'       => ['19902 Crystal Rock Dr Apt 302', '19902 Crystal Rock Dr'],
            'hash suffix'      => ['3486 Highway 155 N # 369', '3486 Highway 155 N'],
            'suite abbrev'     => ['100 Main St Ste 4', '100 Main St'],
            'building'         => ['12 Oak Ave Bldg C', '12 Oak Ave'],
            'po box dropped'   => ['PO Box 981', ''],
            'po box dotted'    => ['P.O. Box 12', ''],
            'post office box'  => ['Post Office Box 7', ''],
            'clean unchanged'  => ['1464 Ohara Dr', '1464 Ohara Dr'],
            'street word safe' => ['100 Floor Street', '100 Floor Street'],
            'empty'            => ['  ', ''],
        ];
    }

    #[Test]
    #[DataProvider('streetProvider')]
    public function cleanStreetTidiesUnitsAndPoBoxes(string $input, string $expected): void
    {
        self::assertSame($expected, AddressNormalizer::cleanStreet($input));
    }

    #[Test]
    public function composeJoinsCleanedPartsAndDropsEmpties(): void
    {
        self::assertSame(
            '5170 Hickory Hollow Pkwy, Antioch, TN, US',
            AddressNormalizer::compose('5170 Hickory Hollow Pkwy Unit 202', 'Antioch', 'TN', 'US'),
        );
    }

    #[Test]
    public function poBoxFallsBackToCityState(): void
    {
        // Street drops out, leaving a city/state pin rather than nothing.
        self::assertSame(
            'Hendersonville, TN, US',
            AddressNormalizer::compose('PO Box 981', 'Hendersonville', 'TN', 'US'),
        );
    }
}
