<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Site\Service;

use CWM\Component\Cwmconnect\Site\Service\DirectoryPdfFonts;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * lib_mpdf ships DejaVu Sans alone: libraries/mpdf/prune-fonts.php deletes mPDF's
 * other fonts when Composer installs it. These tests run that script on a scratch
 * copy of mPDF's font folder and render with what it leaves, whether or not the
 * installed library was pruned already. A font request that needs a deleted file
 * fails here instead of on a site.
 */
#[CoversClass(DirectoryPdfFonts::class)]
final class DirectoryPdfFontsTest extends TestCase
{
    private string $fontDir = '';

    private string $tempDir = '';

    protected function setUp(): void
    {
        if (!is_file(JPATH_LIBRARIES . '/mpdf/vendor/autoload.php')) {
            self::markTestSkipped('mpdf library not present.');
        }

        require_once JPATH_LIBRARIES . '/mpdf/vendor/autoload.php';

        $scratch       = sys_get_temp_dir() . '/cwm_pdf_fonts_' . bin2hex(random_bytes(4));
        $this->fontDir = $scratch . '/fonts';
        $this->tempDir = $scratch . '/mpdf';
        mkdir($this->fontDir, 0o755, true);
        mkdir($this->tempDir, 0o755, true);

        // Links are enough for the script, which only deletes; copy where links aren't allowed.
        foreach (glob(JPATH_LIBRARIES . '/mpdf/vendor/mpdf/mpdf/ttfonts/*') ?: [] as $file) {
            $target = $this->fontDir . '/' . basename($file);

            if (!@symlink($file, $target)) {
                copy($file, $target);
            }
        }

        $process = proc_open(
            [PHP_BINARY, JPATH_LIBRARIES . '/mpdf/prune-fonts.php', $this->fontDir],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), (string) $output);
    }

    protected function tearDown(): void
    {
        if ($this->fontDir === '') {
            return;
        }

        $scratch = \dirname($this->fontDir);
        $files   = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($scratch, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $path = $file->getPathname();
            is_dir($path) && !is_link($path) ? rmdir($path) : unlink($path);
        }

        rmdir($scratch);
    }

    #[Test]
    public function pruningLeavesDejaVuSansAndItsLicence(): void
    {
        $left = array_map('basename', glob($this->fontDir . '/*') ?: []);
        sort($left);

        self::assertSame(
            ['DejaVuSans-Bold.ttf', 'DejaVuSans-BoldOblique.ttf', 'DejaVuSans-Oblique.ttf', 'DejaVuSans.ttf', 'DejaVuinfo.txt'],
            $left
        );
    }

    #[Test]
    public function everyFontRequestRendersInDejaVuSans(): void
    {
        $mpdf = new \Mpdf\Mpdf(DirectoryPdfFonts::config([
            'mode'    => 'utf-8',
            'format'  => 'Letter',
            'fontDir' => [$this->fontDir],
            'tempDir' => $this->tempDir,
        ]));

        // Headers and footers default to bold oblique.
        $mpdf->SetHeader('Church Member Directory|{STAFF COPY}|Page {PAGENO}');
        $mpdf->SetFooter('Generated today||2 members');

        // mPDF's default body font, its generic families, fonts it maps to families of its
        // own, a font it has never heard of, and the elements its stylesheet sets monospace on.
        $mpdf->WriteHTML(<<<'HTML'
            <p>Default font. Zoë Łukasz, Ελένη, Наталья, דוד</p>
            <p style="font-family: serif">serif</p>
            <p style="font-family: sans-serif">sans-serif</p>
            <p style="font-family: monospace">monospace</p>
            <p style="font-family: Georgia, 'Times New Roman', Times">named serif fonts</p>
            <p style="font-family: 'Courier New', Courier">named monospace fonts</p>
            <p style="font-family: 'Comic Sans MS', cursive">cursive</p>
            <p style="font-family: NoSuchFont">unknown font</p>
            <pre>pre</pre><p><code>code</code> <tt>tt</tt> <kbd>kbd</kbd> <samp>samp</samp></p>
            <p><b>bold</b> <i>italic</i> <b><i>bold italic</i></b> <strong>strong</strong> <em>em</em></p>
            <table><tr><td style="font-family: serif"><strong>table cell</strong></td></tr></table>
            HTML);

        $pdf = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

        preg_match_all('#/BaseFont /[A-Z]{6}\+([\w-]+)#', $pdf, $matches);
        $fonts = array_values(array_unique($matches[1]));
        sort($fonts);

        self::assertSame(['DejaVuSans', 'DejaVuSans-Bold', 'DejaVuSans-BoldOblique', 'DejaVuSans-Oblique'], $fonts);
    }
}
