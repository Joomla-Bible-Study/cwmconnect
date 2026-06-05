<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Geocode;

use CWM\Component\Cwmconnect\Administrator\Service\Geocode\GoogleGeocoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the pure response mapping {@see GoogleGeocoder::parse()} and the
 * shared address composer. The networked geocode() path is a thin wrapper.
 */
#[CoversClass(GoogleGeocoder::class)]
final class GoogleGeocoderTest extends TestCase
{
    #[Test]
    public function okWithLocationIsFound(): void
    {
        $r = GoogleGeocoder::parse([
            'status'  => 'OK',
            'results' => [['geometry' => ['location' => ['lat' => 36.131973, 'lng' => -86.81237]]]],
        ]);

        self::assertTrue($r->found);
        self::assertSame(36.131973, $r->lat);
        self::assertSame(-86.81237, $r->lng);
    }

    #[Test]
    public function overQueryLimitIsRateLimited(): void
    {
        $r = GoogleGeocoder::parse(['status' => 'OVER_QUERY_LIMIT', 'error_message' => 'slow down']);

        self::assertFalse($r->found);
        self::assertTrue($r->rateLimited);
    }

    #[Test]
    public function zeroResultsIsNotFoundNotError(): void
    {
        $r = GoogleGeocoder::parse(['status' => 'ZERO_RESULTS']);

        self::assertFalse($r->found);
        self::assertFalse($r->rateLimited);
        self::assertSame('ZERO_RESULTS', $r->status);
    }

    #[Test]
    public function requestDeniedIsError(): void
    {
        $r = GoogleGeocoder::parse(['status' => 'REQUEST_DENIED', 'error_message' => 'bad key']);

        self::assertFalse($r->found);
        self::assertFalse($r->rateLimited);
        self::assertSame('REQUEST_DENIED', $r->status);
        self::assertSame('bad key', $r->message);
    }

    #[Test]
    public function okWithoutLocationIsError(): void
    {
        $r = GoogleGeocoder::parse(['status' => 'OK', 'results' => []]);

        self::assertFalse($r->found);
    }

    #[Test]
    public function composeAddressJoinsAndDropsEmpties(): void
    {
        self::assertSame(
            '2800 Blair Blvd, Nashville, TN, USA',
            GoogleGeocoder::composeAddress('2800 Blair Blvd', 'Nashville', 'TN', 'USA'),
        );
        self::assertSame('Nashville, TN', GoogleGeocoder::composeAddress('', 'Nashville', 'TN', ''));
        self::assertSame('', GoogleGeocoder::composeAddress(' ', '', '', ''));
    }
}
