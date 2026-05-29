<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Site\View;

use CWM\Component\Cwmconnect\Site\View\Members\PdfView;
use Joomla\CMS\Language\Text;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

// JPATH_ROOT is needed by the render smoke test for the photo/thumbnail cache.
if (!\defined('JPATH_ROOT')) {
    \define('JPATH_ROOT', sys_get_temp_dir() . '/cwm_pdfview_root');
}

/**
 * Coverage for the directory PDF view: the pure presentation helpers
 * (K.1/K.2) and an end-to-end render of the real template through the
 * bundled mpdf (K.1–K.7).
 */
#[CoversClass(PdfView::class)]
final class MembersPdfViewTest extends TestCase
{
    private PdfView $view;

    protected function setUp(): void
    {
        $this->view = new PdfView([]);
    }

    protected function tearDown(): void
    {
        // Clean any placeholder/thumbnail files the render test wrote under
        // the temp JPATH_ROOT.
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
            'con_position' => '', 'address' => '', 'suburb' => '', 'state' => '',
            'postcode'    => '', 'anniversary' => '', 'telephone' => '', 'mobile' => '',
            'email_to'    => '', 'image' => '',
        ], $overrides);
    }

    #[Test]
    public function memberNameHandlesSinglesCouplesAndBlanks(): void
    {
        self::assertSame(
            'Adair, Judy',
            $this->view->memberName(self::member(['surname' => 'Adair', 'name' => 'Judy'])),
        );

        // Surname falls back to lname when surname is blank.
        self::assertSame(
            'Allen, James',
            $this->view->memberName(self::member(['lname' => 'Allen', 'name' => 'James'])),
        );

        // Couple: surname first, then the Text-formatted couple label.
        $coupleLabel = Text::sprintf('COM_CWMCONNECT_PDF_NAME_COUPLE', 'Marie', 'Tony');
        self::assertSame(
            'Abbott, ' . $coupleLabel,
            $this->view->memberName(self::member(['surname' => 'Abbott', 'name' => 'Marie', 'spouse' => 'Tony'])),
        );

        // Surname only.
        self::assertSame('Brennan', $this->view->memberName(self::member(['surname' => 'Brennan'])));
        // Nothing at all.
        self::assertSame('', $this->view->memberName(self::member([])));
    }

    #[Test]
    public function memberInitialsUsesGivenAndSurname(): void
    {
        self::assertSame('MA', $this->view->memberInitials(self::member(['name' => 'Marie', 'surname' => 'Abbott'])));
        self::assertSame('J', $this->view->memberInitials(self::member(['name' => 'Judy'])));
        self::assertSame('?', $this->view->memberInitials(self::member([])));
    }

    #[Test]
    public function memberAnniversaryFormatsOrSuppresses(): void
    {
        self::assertSame('June 12', $this->view->memberAnniversary(self::member(['anniversary' => '1998-06-12 00:00:00'])));
        self::assertNull($this->view->memberAnniversary(self::member(['anniversary' => '0000-00-00 00:00:00'])));
        self::assertNull($this->view->memberAnniversary(self::member(['anniversary' => ''])));
        self::assertNull($this->view->memberAnniversary(self::member(['anniversary' => 'not-a-date'])));
    }

    #[Test]
    public function memberLocalityAssemblesAddressParts(): void
    {
        self::assertSame(
            'Springfield, SC 12345',
            $this->view->memberLocality(self::member(['suburb' => 'Springfield', 'state' => 'SC', 'postcode' => '12345'])),
        );
        self::assertSame('Springfield', $this->view->memberLocality(self::member(['suburb' => 'Springfield'])));
        self::assertSame('SC 12345', $this->view->memberLocality(self::member(['state' => 'SC', 'postcode' => '12345'])));
        self::assertSame('', $this->view->memberLocality(self::member([])));
    }

    #[Test]
    public function rendersAValidMultiPagePdfThroughMpdf(): void
    {
        if (!is_file(JPATH_LIBRARIES . '/mpdf/vendor/autoload.php')) {
            self::markTestSkipped('mpdf library not present.');
        }

        require_once JPATH_LIBRARIES . '/mpdf/vendor/autoload.php';

        @mkdir(JPATH_ROOT . '/media/com_cwmconnect/photos/thumb', 0o755, true);

        $this->view->items = [
            self::member(['surname' => 'Abbott', 'name' => 'Marie', 'spouse' => 'Tony', 'anniversary' => '1998-06-12 00:00:00', 'address' => '164 Tanglewood Ave', 'suburb' => 'Springfield', 'state' => 'SC', 'postcode' => '12345', 'telephone' => '(217) 555-6270', 'email_to' => 'a@example.com']),
            self::member(['surname' => 'Allen', 'name' => 'James', 'con_position' => 'Senior Pastor', 'address' => '9 Main St', 'suburb' => 'Springfield', 'state' => 'SC', 'postcode' => '12345', 'telephone' => '(217) 555-9091']),
            self::member(['surname' => 'Brennan', 'name' => 'Sam', 'spouse' => 'Lydia', 'address' => '88 River Road', 'suburb' => 'Lakeside', 'state' => 'SC', 'postcode' => '12350']),
        ];
        $this->view->staff = [$this->view->items[1]];
        $this->view->cover = [
            'enabled' => true, 'image' => null, 'name' => 'Test Church',
            'address' => "1 Main St\nTown, ST 00000", 'phone' => '555-0100', 'email' => '', 'website' => '',
        ];
        $this->view->showStaff          = true;
        $this->view->showSectionHeaders = true;
        $this->view->appearance         = ['fontBasePt' => 10.0];

        $template = \dirname(__DIR__, 4) . '/site/tmpl/members/default_pdf.php';

        $render = \Closure::bind(function (string $tpl): string {
            ob_start();
            include $tpl;

            return (string) ob_get_clean();
        }, $this->view, PdfView::class);

        $html = $render($template);

        self::assertStringContainsString('Test Church', $html);
        self::assertStringContainsString('Our Staff', $html);

        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => sys_get_temp_dir()]);
        $mpdf->WriteHTML($html);
        $pdf = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

        self::assertStringStartsWith('%PDF-', $pdf);
        self::assertGreaterThan(2000, \strlen($pdf), 'PDF should have real content');
        self::assertGreaterThanOrEqual(3, $mpdf->page, 'cover + staff + listing = 3+ pages');
    }
}
