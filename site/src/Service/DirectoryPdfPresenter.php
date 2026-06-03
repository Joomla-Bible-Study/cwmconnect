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
     * Family-unit rows keyed by id, each carrying `name` + `image`. Supplied by
     * the view so the `family` layout can attach a household photo / name.
     *
     * @var    array<int, object>
     * @since  __DEPLOY_VERSION__
     */
    public array $families = [];

    /**
     * Memoised household grouping built from {@see self::households()}.
     *
     * @var    list<array<string, mixed>>|null
     * @since  __DEPLOY_VERSION__
     */
    private ?array $householdsCache = null;

    /**
     * Members holding a position (`con_position`), for the staff section.
     *
     * @var    list<object>
     * @since  __DEPLOY_VERSION__
     */
    public array $staff = [];

    /**
     * Officers (a PC position or an officer-type ministry team), for the
     * Officers section.
     *
     * @var    list<object>
     * @since  __DEPLOY_VERSION__
     */
    public array $officers = [];

    /**
     * Church-board members (PC `church_board_member`), for the Board section.
     *
     * @var    list<object>
     * @since  __DEPLOY_VERSION__
     */
    public array $board = [];

    /**
     * Cover-page content. Keys: `enabled`, `image` (absolute path or null),
     * `name`, `address`, `phone`, `email`, `website`.
     *
     * @var    array<string, mixed>
     * @since  __DEPLOY_VERSION__
     */
    public array $cover = ['enabled' => false];

    /**
     * Welcome-letter body (trusted admin rich text), shown on its own page after
     * the cover. Null/empty suppresses the page.
     *
     * @var    string|null
     * @since  __DEPLOY_VERSION__
     */
    public ?string $welcome = null;

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
        return $this->resolveImageThumb((string) ($item->image ?? ''));
    }

    /**
     * Resolve any stored image reference (member `image` or a family-unit
     * `image`) to an absolute 3:4-thumbnail path mpdf can read, building the
     * thumbnail on demand. Null when there is no usable local image.
     *
     * @param   string  $image
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function resolveImageThumb(string $image): ?string
    {
        $image  = trim($image);
        $source = $image !== '' ? PhotoAccess::resolvePath($image) : null;

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
        return $this->memberPhotoPath($item) ?? $this->placeholderSrc($this->memberInitials($item));
    }

    /**
     * Generate (or reuse) a 3:4 initials placeholder card and return its path.
     *
     * @param   string  $initials
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function placeholderSrc(string $initials): ?string
    {
        $safe = preg_replace('/[^A-Z0-9]/', '', strtoupper($initials)) ?: substr(sha1($initials), 0, 8);
        $file = JPATH_ROOT . '/media/com_cwmconnect/photos/thumb/ph/' . $safe . '.jpg';

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
    public function memberGiven(object $item): string
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

    /**
     * Officer-title keywords matched (case-insensitively, as substrings) inside
     * the comma-separated PC `positions` / `ministry_teams` text. `positions` is
     * free-text holding ALL of a member's roles ("Video Team Member", "Elder,
     * Praise Team"…), so a church officer is recognised by these specific titles
     * rather than by simply having any position. "deacon" also catches
     * Deacons / Deaconess (and the "Deacones" misspelling); "clerk" catches
     * Church Clerk.
     *
     * @var    list<string>
     * @since  __DEPLOY_VERSION__
     */
    public const OFFICER_KEYWORDS = ['elder', 'deacon', 'treasurer', 'clerk'];

    /**
     * Active officer-title keywords (lower-case), overridable from the component
     * options so each church can define what counts as an officer. Defaults to
     * {@see self::OFFICER_KEYWORDS}. A "Head Deacon" still matches "deacon".
     *
     * @var    list<string>
     * @since  __DEPLOY_VERSION__
     */
    public array $officerKeywords = self::OFFICER_KEYWORDS;

    /**
     * Whether a member qualifies for the Officers section: any of their role
     * text matches an officer title.
     *
     * @param   object  $item
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    public function isOfficer(object $item): bool
    {
        return $this->officerRoles($item) !== [];
    }

    /**
     * The role label shown under a member's name in a front-matter section. For
     * officers it is just the matching officer title(s) (e.g. "Deacon", "Head
     * Elder") — not the member's whole list of ministry roles. Otherwise the raw
     * positions text, else the legacy `con_position`.
     *
     * @param   object  $item
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function memberRole(object $item): string
    {
        $officer = $this->officerRoles($item);

        if ($officer !== []) {
            return implode(', ', $officer);
        }

        return trim((string) ($item->pc_positions ?? '')) ?: trim((string) ($item->con_position ?? ''));
    }

    /**
     * The member's officer titles: comma-separated parts of `pc_positions` (then,
     * if none, `pc_ministry_teams`) that contain an officer keyword.
     *
     * @param   object  $item
     *
     * @return  list<string>
     *
     * @since   __DEPLOY_VERSION__
     */
    private function officerRoles(object $item): array
    {
        $fromPositions = $this->matchOfficerParts((string) ($item->pc_positions ?? ''));

        return $fromPositions !== [] ? $fromPositions : $this->matchOfficerParts((string) ($item->pc_ministry_teams ?? ''));
    }

    /**
     * The comma-separated parts of a role string that name an officer.
     *
     * @param   string  $roles
     *
     * @return  list<string>
     *
     * @since   __DEPLOY_VERSION__
     */
    private function matchOfficerParts(string $roles): array
    {
        $out = [];

        foreach (explode(',', $roles) as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            $lower = mb_strtolower($part);

            foreach ($this->officerKeywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    $out[$part] = $part;

                    break;
                }
            }
        }

        return array_values($out);
    }

    /**
     * Group the members into households for the `family` layout: members sharing
     * a `funitid` form one household; each unlinked member (`funitid <= 0`) is its
     * own one-person household. Adults sort before children within a household;
     * households sort alphabetically by surname then head given name. Memoised.
     *
     * Each entry: { familyId:int, surname:string, image:string, members:list<object> }.
     *
     * @return  list<array<string, mixed>>
     *
     * @since   __DEPLOY_VERSION__
     */
    public function households(): array
    {
        if ($this->householdsCache !== null) {
            return $this->householdsCache;
        }

        $groups    = [];
        $singleton = 0;

        foreach ($this->items as $item) {
            $fid = (int) ($item->funitid ?? 0);
            $key = $fid > 0 ? 'f' . $fid : 's' . $singleton++;

            if (!isset($groups[$key])) {
                $family = $fid > 0 ? ($this->families[$fid] ?? null) : null;

                $groups[$key] = [
                    'familyId' => $fid,
                    'surname'  => trim((string) ($item->surname ?? '')) ?: trim((string) ($item->lname ?? '')),
                    'image'    => $family !== null ? trim((string) ($family->image ?? '')) : '',
                    'members'  => [],
                ];
            }

            $groups[$key]['members'][] = $item;
        }

        $households = array_values($groups);

        foreach ($households as &$household) {
            usort(
                $household['members'],
                static fn(object $a, object $b): int => ((int) ($a->is_child ?? 0)) <=> ((int) ($b->is_child ?? 0)),
            );
        }

        unset($household);

        usort($households, fn(array $a, array $b): int => [strtolower($a['surname']), strtolower($this->memberGiven($a['members'][0]))]
            <=> [strtolower($b['surname']), strtolower($this->memberGiven($b['members'][0]))]);

        $this->householdsCache = $households;

        return $households;
    }

    /**
     * The household's head member (first adult, else first member).
     *
     * @param   array<string, mixed>  $household
     *
     * @return  object
     *
     * @since   __DEPLOY_VERSION__
     */
    public function householdHead(array $household): object
    {
        return $household['members'][0];
    }

    /**
     * Family headline name: "SURNAME, Given, Given and Given" listing every
     * household member (adults first, then children — they share the family
     * unit). Singleton → "SURNAME, Given".
     *
     * @param   array<string, mixed>  $household
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function householdDisplayName(array $household): string
    {
        $givens = array_values(array_filter(array_map(
            fn(object $m): string => $this->memberGiven($m),
            $household['members'],
        )));

        $names = match (true) {
            $givens === []       => '',
            \count($givens) <= 2 => implode(' and ', $givens),
            default              => implode(', ', \array_slice($givens, 0, -1)) . ' and ' . end($givens),
        };

        $surname = mb_strtoupper(trim((string) $household['surname']));

        if ($surname === '') {
            return $names;
        }

        return $names !== '' ? $surname . ', ' . $names : $surname;
    }

    /**
     * The household photo for mpdf: the family-unit photo, else the head member's
     * photo, else a surname initials card.
     *
     * @param   array<string, mixed>  $household
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public function householdPhotoSrc(array $household): ?string
    {
        $family = $this->resolveHouseholdThumb((string) ($household['image'] ?? ''));

        if ($family !== null) {
            return $family;
        }

        // No household photo on file → a neutral surname-initials card. We do NOT
        // fall back to a member's photo: a single member's face misrepresents the
        // whole family. A PC re-sync fills in real household photos.
        $initials = mb_strtoupper(mb_substr(trim((string) $household['surname']), 0, 2)) ?: '?';

        return $this->placeholderSrc($initials);
    }

    /**
     * Resolve a family-unit image to an absolute 3:4-thumbnail path mpdf can
     * read. Family photos live under `photos/households/` (NOT the member dir),
     * so this resolves there via {@see PhotoAccess::resolveHouseholdImage()} and
     * thumbnails on demand (the thumb is `hh-`-prefixed to avoid colliding with a
     * member thumbnail of the same numeric name). Null when there is no photo.
     *
     * @param   string  $image
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function resolveHouseholdThumb(string $image): ?string
    {
        $image  = trim($image);
        $source = $image !== '' ? PhotoAccess::resolveHouseholdImage($image, '', false) : null;

        if ($source === null) {
            return null;
        }

        // 'hhn-' (household natural) cache: a household group photo is scaled to
        // fit at its own aspect (contain), never centre-cropped, so nobody is
        // sliced off and a wide photo fills the card width.
        $thumb = JPATH_ROOT . '/media/com_cwmconnect/photos/thumb/hhn-' . PhotoThumbnailer::thumbFilename($image);

        if (is_file($thumb)) {
            return $thumb;
        }

        if (new PhotoThumbnailer()->generate($source, $thumb, 'jpg', 'contain')) {
            return $thumb;
        }

        return $source;
    }

    /**
     * City/State ZIP for the household, taken from the head member (a PC
     * household shares one address).
     *
     * @param   array<string, mixed>  $household
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function householdLocality(array $household): string
    {
        return $this->memberLocality($this->householdHead($household));
    }
}
