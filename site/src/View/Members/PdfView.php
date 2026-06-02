<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\View\Members;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Service\Pc\DatabaseCampusRepository;
use CWM\Component\Cwmconnect\Site\Model\MembersModel;
use CWM\Component\Cwmconnect\Site\Service\DirectoryPdfPresenter;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/**
 * Phase I: PDF export of the filtered member directory.
 *
 * Loads the same data as the HTML list view (via MembersModel), builds a
 * {@see DirectoryPdfPresenter} (the shared view-model used by the admin print
 * report too), renders the print template, and streams the result through mpdf.
 * Pagination is removed so the PDF contains every matching row.
 *
 * @since  __DEPLOY_VERSION__
 */
class PdfView extends BaseHtmlView
{
    /**
     * Build the directory PDF and stream it to the browser.
     *
     * @param   string|null  $tpl  Unused — rendering goes through the presenter.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   __DEPLOY_VERSION__
     */
    #[\Override]
    public function display($tpl = null): void
    {
        /** @var MembersModel $model */
        $model = $this->getModel();

        $model->setState('list.start', 0);
        $model->setState('list.limit', 0);

        $items = $model->getItems() ?: [];

        if ($items === []) {
            throw new \RuntimeException(Text::_('COM_CWMCONNECT_PDF_ERROR_NO_MEMBERS'), 404);
        }

        $app    = Factory::getApplication();
        $params = ComponentHelper::getParams('com_cwmconnect');

        $presenter                     = new DirectoryPdfPresenter();
        $presenter->items              = $items;
        $presenter->showSectionHeaders = (bool) $params->get('pdf_section_headers', 1);
        $presenter->pdfLayout          = (string) $params->get('pdf_layout', 'photo_detail');
        $presenter->appendRoster       = (bool) $params->get('pdf_append_roster', 0);

        if ($presenter->pdfLayout === 'family') {
            $presenter->families = $this->loadFamilies($model, $items);
        }
        $presenter->appearance         = [
            'fontBasePt' => match ((string) $params->get('pdf_font_size', 'normal')) {
                'large'  => 12.0,
                'xlarge' => 14.0,
                default  => 10.0,
            },
        ];

        if ((bool) $params->get('pdf_cover', 1)) {
            $presenter->cover = $this->resolveCover($params, $app);
        }

        if ((bool) $params->get('pdf_staff', 1)) {
            $presenter->staff = array_values(
                array_filter(
                    $items,
                    static fn(object $item): bool => trim((string) ($item->con_position ?? '')) !== '',
                ),
            );
        }

        // Rendering hundreds of members + photos in mpdf is memory- and
        // time-hungry; raise the ceilings so a large directory can't die
        // mid-build with an uncatchable fatal. Never lower an unlimited cap.
        @set_time_limit(0);

        if ((string) @ini_get('memory_limit') !== '-1') {
            @ini_set('memory_limit', '1024M');
        }

        $html = $presenter->renderHtml();

        $autoload = JPATH_LIBRARIES . '/mpdf/vendor/autoload.php';

        if (!is_file($autoload)) {
            throw new \RuntimeException(Text::_('COM_CWMCONNECT_PDF_ERROR_LIB_MISSING'), 500);
        }

        require_once $autoload;

        // Booklet = US half-letter (5.5 × 8.5 in) in mm; otherwise US Letter.
        $config = [
            'mode'          => 'utf-8',
            'format'        => $params->get('pdf_page_size', 'letter') === 'booklet' ? [139.7, 215.9] : 'Letter',
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 16,
            'margin_bottom' => 16,
            'tempDir'       => $app->get('tmp_path', sys_get_temp_dir()),
        ];

        // Black & white: restrict the output colour space to grayscale (1).
        if ($params->get('pdf_color', 'color') === 'bw') {
            $config['restrictColorSpace'] = 1;
        }

        try {
            $mpdf = new \Mpdf\Mpdf($config);

            $mpdf->SetTitle(Text::_('COM_CWMCONNECT_PDF_TITLE'));
            $mpdf->SetAuthor(Text::_('COM_CWMCONNECT'));
            $mpdf->WriteHTML($html);
        } catch (\Mpdf\MpdfException $e) {
            throw new \RuntimeException(Text::sprintf('COM_CWMCONNECT_PDF_ERROR_RENDER', $e->getMessage()), 500);
        }

        $filename = 'church-directory-' . date('Y-m-d') . '.pdf';

        $app->setHeader('Content-Type', 'application/pdf', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"', true);
        $app->setHeader('Cache-Control', 'private, max-age=0, must-revalidate', true);
        $app->sendHeaders();

        $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);

        $app->close();
    }

    /**
     * Load the family-unit rows (id/name/image) referenced by the members'
     * `funitid`, keyed by id, for the family layout's household grouping.
     *
     * @param   MembersModel   $model
     * @param   list<object>   $items
     *
     * @return  array<int, object>
     *
     * @since   __DEPLOY_VERSION__
     */
    private function loadFamilies(MembersModel $model, array $items): array
    {
        $ids = [];

        foreach ($items as $item) {
            $fid = (int) ($item->funitid ?? 0);

            if ($fid > 0) {
                $ids[$fid] = $fid;
            }
        }

        if ($ids === []) {
            return [];
        }

        $db    = $model->getDatabase();
        $query = $db->createQuery()
            ->select([$db->quoteName('id'), $db->quoteName('name'), $db->quoteName('image')])
            ->from($db->quoteName('#__cwmconnect_familyunit'))
            ->whereIn($db->quoteName('id'), array_values($ids));

        $db->setQuery($query);

        $families = [];

        foreach ($db->loadObjectList() ?: [] as $row) {
            $families[(int) $row->id] = $row;
        }

        return $families;
    }

    /**
     * Resolve the cover-page content from the component options, filling blank
     * fields from the synced Planning Center campus when "use PC data" is on
     * (K.6). A non-empty manual value overrides the PC value.
     *
     * @param   Registry                  $params
     * @param   CMSApplicationInterface   $app
     *
     * @return  array<string, mixed>
     *
     * @since   __DEPLOY_VERSION__
     */
    private function resolveCover(Registry $params, CMSApplicationInterface $app): array
    {
        $usePc  = (bool) $params->get('pc_enabled', 0) && (bool) $params->get('pdf_cover_use_pc', 1);
        $campus = $usePc ? $this->getCoverCampus() : null;

        $pick = static fn(string $manual, ?string $pc): string => $manual !== ''
            ? $manual
            : trim((string) ($pc ?? ''));

        $manualAddress = trim((string) $params->get('pdf_church_address', ''));

        return [
            'enabled' => true,
            'image'   => $this->resolvePdfImage((string) $params->get('pdf_cover_image', '')),
            'name'    => $pick(trim((string) $params->get('pdf_church_name', '')), $campus->name ?? null)
                ?: (string) $app->get('sitename'),
            'address' => $manualAddress !== '' ? $manualAddress : $this->campusAddress($campus),
            'phone'   => $pick(trim((string) $params->get('pdf_church_phone', '')), $campus->pc_phone ?? null),
            'email'   => $pick(trim((string) $params->get('pdf_church_email', '')), $campus->pc_email ?? null),
            'website' => $pick(trim((string) $params->get('pdf_church_website', '')), $campus->pc_website ?? null),
        ];
    }

    /**
     * Resolve a root-relative image path (the cover image) to an absolute
     * filesystem path mpdf can read, or null when blank / remote / missing.
     *
     * @param   string  $image
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function resolvePdfImage(string $image): ?string
    {
        $image = trim($image);

        if ($image === '' || preg_match('~^https?://~i', $image) === 1) {
            return null;
        }

        $candidate = JPATH_ROOT . '/' . ltrim($image, '/');

        return is_file($candidate) ? $candidate : null;
    }

    /**
     * The primary Planning Center campus row (K.6), or null when none is
     * synced or the lookup fails.
     *
     * @return  object|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function getCoverCampus(): ?object
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            return new DatabaseCampusRepository($db)->findPrimary();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Build a multi-line postal address from a synced campus row.
     *
     * @param   object|null  $campus
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function campusAddress(?object $campus): string
    {
        if ($campus === null) {
            return '';
        }

        $city    = trim((string) ($campus->pc_city ?? ''));
        $state   = trim((string) ($campus->pc_state ?? ''));
        $zip     = trim((string) ($campus->pc_zip ?? ''));
        $country = trim((string) ($campus->pc_country ?? ''));

        $cityState = $city !== '' && $state !== '' ? $city . ', ' . $state : $city . $state;
        $cityLine  = trim($cityState . ' ' . $zip);

        return implode("\n", array_filter([trim((string) ($campus->pc_street ?? '')), $cityLine, $country]));
    }
}
