<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Geocode;

use CWM\Component\Cwmconnect\Administrator\Service\Geocode\NominatimGeocoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the pure response mapping {@see NominatimGeocoder::parse()} and
 * the shared address composer.
 */
#[CoversClass(NominatimGeocoder::class)]
final class NominatimGeocoderTest extends TestCase
{
    #[Test]
    public function firstMatchIsFound(): void
    {
        // Nominatim returns a list; lat/lon are strings, hence the float cast.
        $r = NominatimGeocoder::parse([
            ['lat' => '36.131973', 'lon' => '-86.812370', 'display_name' => 'Nashville'],
            ['lat' => '1.0', 'lon' => '2.0'],
        ]);

        self::assertTrue($r->found);
        self::assertSame(36.131973, $r->lat);
        self::assertSame(-86.81237, $r->lng);
    }

    #[Test]
    public function emptyListIsNotFound(): void
    {
        $r = NominatimGeocoder::parse([]);

        self::assertFalse($r->found);
        self::assertFalse($r->rateLimited);
        self::assertSame('ZERO_RESULTS', $r->status);
    }

    #[Test]
    public function matchWithoutCoordinatesIsNotFound(): void
    {
        $r = NominatimGeocoder::parse([['display_name' => 'Somewhere']]);

        self::assertFalse($r->found);
    }

    #[Test]
    public function composeAddressJoinsAndDropsEmpties(): void
    {
        self::assertSame(
            '2800 Blair Blvd, Nashville, TN, USA',
            NominatimGeocoder::composeAddress('2800 Blair Blvd', 'Nashville', 'TN', 'USA'),
        );
        self::assertSame('', NominatimGeocoder::composeAddress('', '', '', ''));
    }
}
