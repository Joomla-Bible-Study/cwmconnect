<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\AccessCode;

use CWM\Component\Cwmconnect\Administrator\Service\AccessCode\AccessCodeService;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the shared access code.
 *
 * This is a credential typed in by members, so the tests lean on the two
 * things that decide whether it is safe: it must not be accepted while the
 * feature is half-configured or lapsed, and it must tolerate the ways people
 * transcribe a code without becoming loose about what actually matches.
 *
 * `grant()` is not covered — it calls `UserHelper`, a CMS static the unit
 * bootstrap cannot autoload — so it is verified live instead.
 */
#[CoversClass(AccessCodeService::class)]
final class AccessCodeServiceTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    private function service(array $overrides = []): AccessCodeService
    {
        return new AccessCodeService(new Registry(array_merge([
            'access_code_enabled' => 1,
            'access_code'         => 'K7F2-9XQM',
            'access_code_group'   => 12,
            'access_code_expires' => '',
        ], $overrides)));
    }

    #[Test]
    public function generatedCodesAreGroupedAndUseTheSafeAlphabet(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $code = AccessCodeService::generate();

            self::assertMatchesRegularExpression('/^[234679ACDEFGHJKMNPQRTWXYZ]{4}-[234679ACDEFGHJKMNPQRTWXYZ]{4}$/', $code);
            // The characters people confuse when reading a code aloud or off
            // paper must never appear.
            self::assertDoesNotMatchRegularExpression('/[01OILSBUV58]/', $code);
        }
    }

    #[Test]
    public function generatedCodesDiffer(): void
    {
        $codes = [];

        for ($i = 0; $i < 20; $i++) {
            $codes[] = AccessCodeService::generate();
        }

        self::assertGreaterThan(15, \count(array_unique($codes)));
    }

    /**
     * @return  array<string, array{0: string}>
     */
    public static function transcriptionProvider(): array
    {
        return [
            'exact'            => ['K7F2-9XQM'],
            'lower case'       => ['k7f2-9xqm'],
            'no dash'          => ['K7F29XQM'],
            'spaces'           => ['K7F2 9XQM'],
            'mixed case+space' => ['k7F2 9xQm'],
            'stray punctuation' => ['K7F2--9XQM.'],
        ];
    }

    #[Test]
    #[DataProvider('transcriptionProvider')]
    public function acceptsTheCodeHoweverItWasTypedOut(string $typed): void
    {
        self::assertTrue($this->service()->matches($typed));
    }

    /**
     * @return  array<string, array{0: string}>
     */
    public static function wrongCodeProvider(): array
    {
        return [
            'different code' => ['ZZZZ-ZZZZ'],
            'empty'          => [''],
            'whitespace'     => ['   '],
            'punctuation'    => ['---'],
            'prefix only'    => ['K7F2'],
            'extra char'     => ['K7F2-9XQMA'],
        ];
    }

    #[Test]
    #[DataProvider('wrongCodeProvider')]
    public function rejectsAnythingElse(string $typed): void
    {
        self::assertFalse($this->service()->matches($typed));
    }

    #[Test]
    public function rejectsWhileSwitchedOff(): void
    {
        self::assertFalse($this->service(['access_code_enabled' => 0])->matches('K7F2-9XQM'));
    }

    #[Test]
    public function rejectsWhenNoGroupIsConfigured(): void
    {
        // Half-configured must fail closed: granting "no group" would look
        // like success to the user while doing nothing.
        self::assertFalse($this->service(['access_code_group' => 0])->matches('K7F2-9XQM'));
        self::assertFalse($this->service(['access_code_group' => 0])->isEnabled());
    }

    #[Test]
    public function rejectsWhenNoCodeIsSet(): void
    {
        self::assertFalse($this->service(['access_code' => ''])->isEnabled());
        self::assertFalse($this->service(['access_code' => '   '])->isEnabled());
    }

    #[Test]
    public function rejectsOnceExpired(): void
    {
        $service = $this->service(['access_code_expires' => '2000-01-01 00:00:00']);

        self::assertTrue($service->hasExpired());
        self::assertFalse($service->matches('K7F2-9XQM'));
    }

    #[Test]
    public function acceptsBeforeExpiry(): void
    {
        $service = $this->service(['access_code_expires' => '2999-01-01 00:00:00']);

        self::assertFalse($service->hasExpired());
        self::assertTrue($service->matches('K7F2-9XQM'));
    }

    #[Test]
    public function treatsTheZeroDateAsNoExpiry(): void
    {
        // Joomla writes an empty calendar field as the null date rather than
        // an empty string; reading that as "expired in year 0" would silently
        // disable every code.
        self::assertFalse($this->service(['access_code_expires' => '0000-00-00 00:00:00'])->hasExpired());
        self::assertTrue($this->service(['access_code_expires' => '0000-00-00 00:00:00'])->matches('K7F2-9XQM'));
    }

    #[Test]
    public function anUnparseableExpiryFailsClosed(): void
    {
        $service = $this->service(['access_code_expires' => 'not a date']);

        self::assertTrue($service->hasExpired());
        self::assertFalse($service->matches('K7F2-9XQM'));
    }

    #[Test]
    public function theRotateButtonUsesTheSameAlphabetAsThePhpGenerator(): void
    {
        // The field mints codes in the browser, so its alphabet is a copy.
        // Copies drift; this fails the moment they do.
        $field = file_get_contents(__DIR__ . '/../../../../admin/src/Field/AccesscodeField.php');

        self::assertIsString($field);
        self::assertStringContainsString("const alphabet = '234679ACDEFGHJKMNPQRTWXYZ'", $field);
    }
}
