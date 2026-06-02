<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Service;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\PhotoThumbnailer;
use CWM\Component\Cwmconnect\Site\Helper\PhotoAccess;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * K.5: shared view-model for the directory PDF.
 *
 * Holds the data + presentation logic that the `members/default_pdf` template
 * binds to, so the front-end self-service PDF (`PdfView`) and the admin
 * "Print Directory" report (`ReportbuildHelper`) render identically — same
 * layouts, same normalized 3:4 photos, same cover/staff handling — instead of
 * each maintaining its own HTML builder.
 *
 * No Joomla base class, so it is unit-testable directly. The caller populates
 * the public properties, then calls {@see renderHtml()}.
 *
 * @since  __DEPLOY_VERSION__
 */
final class DirectoryPdfPresenter
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
     * Cover-page content. Keys: `enabled`, `image` (absolute path or null),
     * `name`, `address`, `phone`, `email`, `website`.
     *
     * @var    array<string, mixed>
     * @since  __DEPLOY_VERSION__
     */
    public array $cover = ['enabled' => false];

    /**
     * Whether to render alphabetical (A, B, C…) surname dividers.
     *
     * @var    bool
     * @since  __DEPLOY_VERSION__
     */
    public bool $showSectionHeaders = true;

    /**
     * Appearance options. Key: `fontBasePt` (float — base body size).
     *
     * @var    array<string, mixed>
     * @since  __DEPLOY_VERSION__
     */
    public array $appearance = ['fontBasePt' => 10.0];

    /**
     * Member-entry layout: `photo_detail`, `photo_grid`, or `roster`.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    public string $pdfLayout = 'photo_detail';

    /**
     * Whether to append a text roster after the photo pages.
     *
     * @var    bool
     * @since  __DEPLOY_VERSION__
     */
    public bool $appendRoster = false;

    /**
     * Whether to flag rows with `display_in_directory = 0` (admin "include
     * hidden members" override, spec §17). Off for the member-facing PDF.
     *
     * @var    bool
     * @since  __DEPLOY_VERSION__
     */
    public bool $showHiddenBadges = false;

    /**
     * Whether to render the body title block (`<h1>` + "Generated …" meta).
     * On for the member-facing PDF; off for the admin print, which already
     * carries the title/date in its mpdf running header and footer.
     *
     * @var    bool
     * @since  __DEPLOY_VERSION__
     */
    public bool $showTitleBlock = true;

    /**
     * Escape a value for HTML output.
     *
     * @param   mixed  $value
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function escape($value): string
    {
        return htmlspecialchars((string) $value, \ENT_QUOTES, 'UTF-8');
    }

    /**
     * Render the shared template to HTML, bound to this presenter.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function renderHtml(): string
    {
        $template = \dirname(__DIR__, 2) . '/tmpl/members/default_pdf.php';

        $render = \Closure::bind(function (string $tpl): string {
            ob_start();
            include $tpl;

            return (string) ob_get_clean();
        }, $this, self::class);

        return $render($template);
    }

    /**
     * Whether a row should be flagged as hidden in the rendered output.
     *
     * @param   object  $item
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    public function isHidden(object $item): bool
    {
        return $this->showHiddenBadges && (int) ($item->display_in_directory ?? 1) === 0;
    }

    /**
     * Resolve a member's photo to an absolute filesystem path mpdf can read,
     * preferring the normalized 3:4 thumbnail (built at sync / on demand), or
     * null when there is no usable local image. The `image` column is either a
     * root-relative legacy path or a bare PC-avatar filename under
     * `media/com_cwmconnect/photos/`. Remote URLs are skipped.
     *
     * @param   object  $item
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public function memberPhotoPath(object $item): ?string
    {
        $image  = trim((string) ($item->image ?? ''));
        $source = PhotoAccess::resolvePath($image);

        if ($source === null) {
            return null;
        }

        $thumb = JPATH_ROOT . '/media/com_cwmconnect/photos/thumb/' . PhotoThumbnailer::thumbFilename($image);

        if (is_file($thumb)) {
            return $thumb;
        }

        if (new PhotoThumbnailer()->generate($source, $thumb)) {
            return $thumb;
        }

        return $source;
    }

    /**
     * The image src for a member cell: real thumbnail, else a generated 3:4
     * initials placeholder so every cell is the same size; null only when even
     * the placeholder can't be produced.
     *
     * @param   object  $item
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public function memberPhotoSrc(object $item): ?string
    {
        $photo = $this->memberPhotoPath($item);

        if ($photo !== null) {
            return $photo;
        }

        $initials = $this->memberInitials($item);
        $safe     = preg_replace('/[^A-Z0-9]/', '', strtoupper($initials)) ?: substr(sha1($initials), 0, 8);
        $file     = JPATH_ROOT . '/media/com_cwmconnect/photos/thumb/ph/' . $safe . '.jpg';

        if (is_file($file)) {
            return $file;
        }

        $font = JPATH_LIBRARIES . '/mpdf/vendor/mpdf/mpdf/ttfonts/DejaVuSans.ttf';

        return new PhotoThumbnailer()->placeholder($initials, $file, is_file($font) ? $font : null)
            ? $file
            : null;
    }

    /**
     * Directory display name: "Surname, First [and Spouse]".
     *
     * @param   object  $item
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function memberName(object $item): string
    {
        $surname = trim((string) ($item->surname ?? '')) ?: trim((string) ($item->lname ?? ''));
        $spouse  = trim((string) ($item->spouse ?? ''));
        $given   = $this->memberGiven($item);

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
     * The given-name portion for the inverted "Surname, Given" directory label.
     *
     * Prefers the structured `fname` (+ `nickname`) the PC sync now stores
     * like-for-like. Falls back — for manual rows or any not yet re-synced — to
     * stripping the surname out of the full display `name` so the inverted label
     * doesn't repeat it ("Ababio, Gifty B." not "Ababio, Gifty B. Ababio").
     *
     * @param   object  $item
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function memberGiven(object $item): string
    {
        $first = trim((string) ($item->fname ?? ''));

        if ($first !== '') {
            $nick = trim((string) ($item->nickname ?? ''));

            return ($nick !== '' && strcasecmp($nick, $first) !== 0)
                ? $first . ' (' . $nick . ')'
                : $first;
        }

        $given   = trim((string) ($item->name ?? ''));
        $surname = trim((string) ($item->surname ?? '')) ?: trim((string) ($item->lname ?? ''));

        if ($surname === '' || $given === '') {
            return $given;
        }

        $stripped = preg_replace('/\b' . preg_quote($surname, '/') . '\b/u', '', $given);
        $stripped = trim((string) preg_replace('/\s+,/', ',', (string) $stripped));
        $stripped = trim(preg_replace('/\s{2,}/', ' ', $stripped) ?? '', " ,");

        return $stripped !== '' ? $stripped : $given;
    }

    /**
     * Up-to-two-letter initials for the no-photo placeholder.
     *
     * @param   object  $item
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function memberInitials(object $item): string
    {
        $given   = trim((string) ($item->fname ?? '')) ?: trim((string) ($item->name ?? ''));
        $surname = trim((string) ($item->surname ?? '')) ?: trim((string) ($item->lname ?? ''));

        $initials = mb_substr($given, 0, 1) . mb_substr($surname, 0, 1);

        return mb_strtoupper($initials !== '' ? $initials : '?');
    }

    /**
     * Anniversary formatted as "June 12", or null when unset / zero-date.
     *
     * @param   object  $item
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
     * @param   object  $item
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
