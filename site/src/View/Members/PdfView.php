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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Database\DatabaseInterface;

/**
 * Phase I: PDF export of the filtered member directory.
 *
 * Loads the same data as the HTML list view (via MembersModel), renders a
 * print-oriented HTML template, passes it through mpdf, and streams the
 * result as a downloadable PDF. Pagination is removed so the PDF contains
 * every matching row.
 *
 * @since  __DEPLOY_VERSION__
 */
class PdfView extends BaseHtmlView
{
    /**
     * @var    list<object>
     * @since  __DEPLOY_VERSION__
     */
    public array $items = [];

    /**
     * Members holding a position (`con_position`), for the staff section.
     *
     * @var    list<object>
     * @since  __DEPLOY_VERSION__
     */
    public array $staff = [];

    /**
     * Cover-page content resolved from the component options. Keys:
     * `enabled`, `image` (absolute path or null), `name`, `address`,
     * `phone`, `email`, `website`.
     *
     * @var    array<string, mixed>
     * @since  __DEPLOY_VERSION__
     */
    public array $cover = ['enabled' => false];

    /**
     * Whether to render a "Staff" section ahead of the member listing.
     *
     * @var    bool
     * @since  __DEPLOY_VERSION__
     */
    public bool $showStaff = true;

    /**
     * Whether to render alphabetical (A, B, C…) surname dividers.
     *
     * @var    bool
     * @since  __DEPLOY_VERSION__
     */
    public bool $showSectionHeaders = true;

    /**
     * Render the PDF and stream it to the browser.
     *
     * @param   string|null  $tpl  Template name (unused — always renders default_pdf).
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

        $this->items = $model->getItems() ?: [];

        if ($this->items === []) {
            throw new \RuntimeException(Text::_('COM_CWMCONNECT_PDF_ERROR_NO_MEMBERS'), 404);
        }

        $app    = Factory::getApplication();
        $params = ComponentHelper::getParams('com_cwmconnect');

        $this->showStaff          = (bool) $params->get('pdf_staff', 1);
        $this->showSectionHeaders = (bool) $params->get('pdf_section_headers', 1);

        if ((bool) $params->get('pdf_cover', 1)) {
            $usePc  = (bool) $params->get('pc_enabled', 0) && (bool) $params->get('pdf_cover_use_pc', 1);
            $campus = $usePc ? $this->getCoverCampus() : null;

            // Per field: a non-empty manual value overrides the synced PC value;
            // otherwise the PC campus value is used.
            $pick = static fn(string $manual, ?string $pc): string => $manual !== ''
                ? $manual
                : trim((string) ($pc ?? ''));

            $manualAddress = trim((string) $params->get('pdf_church_address', ''));

            $this->cover = [
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

        if ($this->showStaff) {
            $this->staff = array_values(
                array_filter(
                    $this->items,
                    static fn(object $item): bool => trim((string) ($item->con_position ?? '')) !== '',
                ),
            );
        }

        ob_start();
        $this->setLayout('default_pdf');
        parent::display();
        $html = ob_get_clean();

        $autoload = JPATH_LIBRARIES . '/mpdf/vendor/autoload.php';

        if (!is_file($autoload)) {
            throw new \RuntimeException(Text::_('COM_CWMCONNECT_PDF_ERROR_LIB_MISSING'), 500);
        }

        require_once $autoload;

        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode'          => 'utf-8',
                'format'        => 'Letter',
                'margin_left'   => 15,
                'margin_right'  => 15,
                'margin_top'    => 16,
                'margin_bottom' => 16,
                'tempDir'       => $app->get('tmp_path', sys_get_temp_dir()),
            ]);

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
     * Resolve a member's photo to an absolute filesystem path mpdf can read,
     * or null when there is no usable local image.
     *
     * The `image` column carries two shapes: a root-relative path (legacy /
     * standalone records, e.g. `images/members/foo.jpg`) or a bare filename
     * for a PC-synced avatar cached under `media/com_cwmconnect/photos/`.
     * Remote URLs are skipped — mpdf cannot reliably fetch them.
     *
     * @param   object  $item  A member row from MembersModel.
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public function memberPhotoPath(object $item): ?string
    {
        $image = trim((string) ($item->image ?? ''));

        if ($image === '' || preg_match('~^https?://~i', $image) === 1) {
            return null;
        }

        $candidate = str_contains($image, '/')
            ? JPATH_ROOT . '/' . ltrim($image, '/')
            : JPATH_ROOT . '/media/com_cwmconnect/photos/' . $image;

        return is_file($candidate) ? $candidate : null;
    }

    /**
     * Resolve an arbitrary root-relative image path (e.g. a media-field value
     * such as the cover image) to an absolute filesystem path mpdf can read,
     * or null when blank / remote / missing.
     *
     * @param   string  $image  Root-relative path, or empty.
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public function resolvePdfImage(string $image): ?string
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
     * synced or the lookup fails. Used to fill blank cover fields.
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
     * Build a multi-line postal address from a synced campus row, skipping
     * blank parts. Empty string when there is no campus.
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

        $street  = trim((string) ($campus->pc_street ?? ''));
        $city    = trim((string) ($campus->pc_city ?? ''));
        $state   = trim((string) ($campus->pc_state ?? ''));
        $zip     = trim((string) ($campus->pc_zip ?? ''));
        $country = trim((string) ($campus->pc_country ?? ''));

        $cityState = $city !== '' && $state !== '' ? $city . ', ' . $state : $city . $state;
        $cityLine  = trim($cityState . ' ' . $zip);

        return implode("\n", array_filter([$street, $cityLine, $country]));
    }

    /**
     * Directory display name: "Surname, First [and Spouse]".
     *
     * @param   object  $item  A member row from MembersModel.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function memberName(object $item): string
    {
        $surname = trim((string) ($item->surname ?? '')) ?: trim((string) ($item->lname ?? ''));
        $given   = trim((string) ($item->name ?? ''));
        $spouse  = trim((string) ($item->spouse ?? ''));

        $label = $given;

        if ($spouse !== '') {
            $label = $given !== ''
                ? Text::sprintf('COM_CWMCONNECT_PDF_NAME_COUPLE', $given, $spouse)
                : $spouse;
        }

        if ($surname !== '' && $label !== '') {
            return $surname . ', ' . $label;
        }

        return $surname !== '' ? $surname : $label;
    }

    /**
     * Up-to-two-letter initials for the no-photo placeholder.
     *
     * @param   object  $item  A member row from MembersModel.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function memberInitials(object $item): string
    {
        $given   = trim((string) ($item->name ?? ''));
        $surname = trim((string) ($item->surname ?? '')) ?: trim((string) ($item->lname ?? ''));

        $initials = mb_substr($given, 0, 1) . mb_substr($surname, 0, 1);

        return mb_strtoupper($initials !== '' ? $initials : '?');
    }

    /**
     * Anniversary formatted as "June 12", or null when unset / zero-date.
     *
     * @param   object  $item  A member row from MembersModel.
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public function memberAnniversary(object $item): ?string
    {
        $raw = trim((string) ($item->anniversary ?? ''));

        if ($raw === '' || str_starts_with($raw, '0000')) {
            return null;
        }

        $timestamp = strtotime($raw);

        return $timestamp !== false ? date('F j', $timestamp) : null;
    }

    /**
     * City/State ZIP line assembled from the address parts, skipping blanks.
     *
     * @param   object  $item  A member row from MembersModel.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function memberLocality(object $item): string
    {
        $city  = trim((string) ($item->suburb ?? ''));
        $state = trim((string) ($item->state ?? ''));
        $zip   = trim((string) ($item->postcode ?? ''));

        $cityState = $city !== '' && $state !== '' ? $city . ', ' . $state : $city . $state;

        return trim($cityState . ' ' . $zip);
    }
}
