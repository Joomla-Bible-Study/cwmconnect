<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Site\Service;

use CWM\Component\Cwmconnect\Site\Service\DirectoryPdfPresenter;
use Joomla\CMS\Language\Text;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

// JPATH_ROOT is needed by the render tests for the photo/thumbnail cache.
if (!\defined('JPATH_ROOT')) {
    \define('JPATH_ROOT', sys_get_temp_dir() . '/cwm_presenter_root');
}

/**
 * Coverage for the shared directory-PDF view-model: the presentation helpers
 * and an end-to-end render of the real template through the bundled mpdf,
 * across every layout. This is the logic the front-end self-service PDF and
 * the admin print report both rely on.
 */
#[CoversClass(DirectoryPdfPresenter::class)]
final class DirectoryPdfPresenterTest extends TestCase
{
    private DirectoryPdfPresenter $presenter;

    protected function setUp(): void
    {
        $this->presenter = new DirectoryPdfPresenter();
    }

    protected function tearDown(): void
    {
        $root = JPATH_ROOT . '/media/com_cwmconnect/photos/thumb';

        foreach (['/ph', ''] as $sub) {
            foreach (glob($root . $sub . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($root . $sub);
        }
    }

    private static function member(array $overrides): object
    {
        return (object) array_merge([
            'name'        => '', 'surname' => '', 'lname' => '', 'spouse' => '',
            'fname'       => '', 'nickname' => '',
            'con_position' => '', 'address' => '', 'suburb' => '', 'state' => '',
            'postcode'    => '', 'anniversary' => '', 'telephone' => '', 'mobile' => '',
            'email_to'    => '', 'image' => '', 'display_in_directory' => 1,
        ], $overrides);
    }

    #[Test]
    public function memberNameHandlesSinglesCouplesAndBlanks(): void
    {
        self::assertSame('Adair, Judy', $this->presenter->memberName(self::member(['surname' => 'Adair', 'name' => 'Judy'])));
        self::assertSame('Allen, James', $this->presenter->memberName(self::member(['lname' => 'Allen', 'name' => 'James'])));

        $coupleLabel = Text::sprintf('COM_CWMCONNECT_PDF_NAME_COUPLE', 'Marie', 'Tony');
        self::assertSame('Abbott, ' . $coupleLabel, $this->presenter->memberName(self::member(['surname' => 'Abbott', 'name' => 'Marie', 'spouse' => 'Tony'])));

        self::assertSame('Brennan', $this->presenter->memberName(self::member(['surname' => 'Brennan'])));
        self::assertSame('', $this->presenter->memberName(self::member([])));
    }

    #[Test]
    public function memberNameUsesStructuredFirstNameAndNickname(): void
    {
        // Primary path: the PC sync stores fname / nickname like-for-like, so the
        // given name needs no parsing of the computed full name.
        self::assertSame('Ababio, Gifty', $this->presenter->memberName(self::member(['surname' => 'Ababio', 'fname' => 'Gifty'])));
        self::assertSame('Adalla, Antony (Tony)', $this->presenter->memberName(self::member(['surname' => 'Adalla', 'fname' => 'Antony', 'nickname' => 'Tony'])));
        // A nickname echoing the first name is dropped.
        self::assertSame('Allen, James', $this->presenter->memberName(self::member(['surname' => 'Allen', 'fname' => 'James', 'nickname' => 'James'])));
    }

    #[Test]
    public function memberNameFallsBackToStrippingSurnameWhenNoFirstName(): void
    {
        // Fallback path (manual rows / not yet re-synced): no fname, so strip the
        // surname out of the full display name rather than repeat it.
        self::assertSame('Ababio, Gifty B.', $this->presenter->memberName(self::member(['surname' => 'Ababio', 'lname' => 'Ababio', 'name' => 'Gifty B. Ababio'])));
        self::assertSame('Adalla, Antony John (Tony)', $this->presenter->memberName(self::member(['surname' => 'Adalla', 'name' => 'Antony John Adalla (Tony)'])));
        self::assertSame('Addison-Amponsah, Kwabena A', $this->presenter->memberName(self::member(['surname' => 'Addison-Amponsah', 'name' => 'Kwabena A Addison-Amponsah'])));
        self::assertSame('Cox, Sherman, III', $this->presenter->memberName(self::member(['surname' => 'Cox', 'name' => 'Sherman Cox, III'])));
    }

    #[Test]
    public function memberInitialsUsesGivenAndSurname(): void
    {
        self::assertSame('MA', $this->presenter->memberInitials(self::member(['name' => 'Marie', 'surname' => 'Abbott'])));
        self::assertSame('J', $this->presenter->memberInitials(self::member(['name' => 'Judy'])));
        self::assertSame('?', $this->presenter->memberInitials(self::member([])));
    }

    #[Test]
    public function memberAnniversaryFormatsOrSuppresses(): void
    {
        self::assertSame('June 12', $this->presenter->memberAnniversary(self::member(['anniversary' => '1998-06-12 00:00:00'])));
        self::assertNull($this->presenter->memberAnniversary(self::member(['anniversary' => '0000-00-00 00:00:00'])));
        self::assertNull($this->presenter->memberAnniversary(self::member(['anniversary' => ''])));
        self::assertNull($this->presenter->memberAnniversary(self::member(['anniversary' => 'not-a-date'])));
    }

    #[Test]
    public function memberLocalityAssemblesAddressParts(): void
    {
        self::assertSame('Springfield, SC 12345', $this->presenter->memberLocality(self::member(['suburb' => 'Springfield', 'state' => 'SC', 'postcode' => '12345'])));
        self::assertSame('Springfield', $this->presenter->memberLocality(self::member(['suburb' => 'Springfield'])));
        self::assertSame('SC 12345', $this->presenter->memberLocality(self::member(['state' => 'SC', 'postcode' => '12345'])));
        self::assertSame('', $this->presenter->memberLocality(self::member([])));
    }

    #[Test]
    public function isHiddenOnlyWhenBadgesEnabledAndRowHidden(): void
    {
        $hidden = self::member(['display_in_directory' => 0]);

        self::assertFalse($this->presenter->isHidden($hidden), 'off by default');

        $this->presenter->showHiddenBadges = true;
        self::assertTrue($this->presenter->isHidden($hidden));
        self::assertFalse($this->presenter->isHidden(self::member(['display_in_directory' => 1])));
    }

    /**
     * @return iterable<string, array{string, bool, bool}>
     */
    public static function layoutProvider(): iterable
    {
        yield 'photo detail'         => ['photo_detail', false, false];
        yield 'photo grid'           => ['photo_grid', false, false];
        yield 'roster'               => ['roster', false, false];
        yield 'detail + roster back' => ['photo_detail', true, false];
        yield 'admin hidden badges'  => ['photo_detail', false, true];
    }

    #[Test]
    #[DataProvider('layoutProvider')]
    public function rendersEachLayoutToValidPdf(string $layout, bool $appendRoster, bool $hiddenBadges): void
    {
        if (!is_file(JPATH_LIBRARIES . '/mpdf/vendor/autoload.php')) {
            self::markTestSkipped('mpdf library not present.');
        }

        require_once JPATH_LIBRARIES . '/mpdf/vendor/autoload.php';
        @mkdir(JPATH_ROOT . '/media/com_cwmconnect/photos/thumb', 0o755, true);

        $this->presenter->items = [
            self::member(['surname' => 'Abbott', 'name' => 'Marie', 'spouse' => 'Tony', 'suburb' => 'Springfield', 'state' => 'SC', 'postcode' => '12345', 'telephone' => '(217) 555-6270', 'email_to' => 'a@example.com']),
            self::member(['surname' => 'Brennan', 'name' => 'Sam', 'address' => '88 River Rd', 'suburb' => 'Lakeside', 'state' => 'SC', 'postcode' => '12350', 'display_in_directory' => 0]),
        ];
        $this->presenter->cover = [
            'enabled' => true, 'image' => null, 'name' => 'Test Church',
            'address' => "1 Main St\nTown, ST 00000", 'phone' => '555-0100', 'email' => '', 'website' => '',
        ];
        $this->presenter->staff            = [self::member(['surname' => 'Allen', 'name' => 'James', 'con_position' => 'Pastor'])];
        $this->presenter->pdfLayout        = $layout;
        $this->presenter->appendRoster     = $appendRoster;
        $this->presenter->showHiddenBadges = $hiddenBadges;
        $this->presenter->appearance       = ['fontBasePt' => 10.0];

        $html = $this->presenter->renderHtml();
        self::assertStringContainsString('Test Church', $html);
        self::assertStringContainsString('Our Staff', $html);

        if ($layout === 'photo_detail') {
            self::assertStringContainsString('entry-grid', $html, 'photo_detail must use the two-column grid');
        }

        if ($hiddenBadges) {
            self::assertStringContainsString('hidden-badge', $html);
        }

        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => sys_get_temp_dir()]);
        $mpdf->WriteHTML($html);
        $pdf = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

        self::assertStringStartsWith('%PDF-', $pdf, "layout {$layout}");
        self::assertGreaterThanOrEqual(1, $mpdf->page);
    }
}
