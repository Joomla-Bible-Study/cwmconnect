<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Geocode;

use CWM\Component\Cwmconnect\Administrator\Service\Geocode\AddressNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The geocode back-fill treats {@see AddressNormalizer::compose()} as its
 * dedupe key: members whose addresses compose to the same query are given the
 * same point from a single lookup, instead of each paying for a call.
 *
 * That is only sound while compose() equality really does imply "the geocoder
 * was asked the identical question". These cases pin the two directions that
 * matter — variations that must collapse (so the saving is real) and
 * distinctions that must survive (so nobody is put at a neighbour's address).
 *
 * @see \CWM\Component\Cwmconnect\Administrator\Model\GeoupdateModel::backfillSameAddress()
 */
#[CoversClass(AddressNormalizer::class)]
final class AddressDedupeKeyTest extends TestCase
{
    private function key(string $street, string $city = 'Nashville', string $state = 'TN', string $country = 'USA'): string
    {
        return strtolower(AddressNormalizer::compose($street, $city, $state, $country));
    }

    /**
     * @return  array<string, array{0: string, 1: string}>
     */
    public static function collapsingProvider(): array
    {
        return [
            'unit designator'      => ['120 Oak St Apt 4', '120 Oak St'],
            'hash designator'      => ['120 Oak St # 4', '120 Oak St'],
            'suite vs bare'        => ['120 Oak St Suite 200', '120 Oak St'],
            'trailing whitespace'  => ['120 Oak St ', '120 Oak St'],
            'case difference'      => ['120 OAK ST', '120 oak st'],
        ];
    }

    #[Test]
    #[DataProvider('collapsingProvider')]
    public function variationsOfOneAddressShareAKey(string $a, string $b): void
    {
        self::assertSame($this->key($a), $this->key($b));
    }

    #[Test]
    public function poBoxesInTheSameTownShareAKey(): void
    {
        // Both compose away to the city/state query, so the geocoder returns
        // one town-level point for each — sharing it is exact, not a guess.
        self::assertSame($this->key('PO Box 123'), $this->key('P.O. Box 4567'));
        self::assertSame($this->key('PO Box 123'), 'nashville, tn, usa');
    }

    /**
     * @return  array<string, array{0: string, 1: string}>
     */
    public static function distinctProvider(): array
    {
        return [
            'different house number' => ['120 Oak St', '122 Oak St'],
            'different street'       => ['120 Oak St', '120 Elm St'],
            'number vs none'         => ['120 Oak St', 'Oak St'],
        ];
    }

    #[Test]
    #[DataProvider('distinctProvider')]
    public function genuinelyDifferentAddressesKeepDistinctKeys(string $a, string $b): void
    {
        self::assertNotSame($this->key($a), $this->key($b));
    }

    #[Test]
    public function sameStreetInDifferentTownsStaysDistinct(): void
    {
        // The town is part of the key, so a PO box — or any address — in one
        // town is never given another town's point.
        self::assertNotSame(
            $this->key('PO Box 123', 'Nashville'),
            $this->key('PO Box 123', 'Columbia'),
        );
        self::assertNotSame(
            $this->key('120 Oak St', 'Nashville'),
            $this->key('120 Oak St', 'Columbia'),
        );
    }
}
