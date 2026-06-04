<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\Exception\ApiException;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Translates one Planning Center `Person` resource into the column → value
 * shape `MemberRepository::upsertByPcPersonId()` expects.
 *
 * Phase C scope: name / contact info / address / privacy gates.
 * Phase D extends this with {@see extractFieldData()} for PC custom fields.
 * Avatar / image cache defer to Phase E. Household + campus relationship
 * resolution defer to a later phase (the columns exist after Phase A +
 * Phase C's companion migration; populating them with the right local FK
 * is still future work).
 *
 * The mapper is intentionally pure (no DB, no logger, no clock) — easy to
 * unit-test by feeding canned PC payloads.
 *
 * @since  __DEPLOY_VERSION__
 */
final class PersonMapper
{
    /**
     * Default PC field-definition slugs for the directory-role fields. The org
     * that drove this build uses these; another org overrides them (or blanks a
     * slug to disable that role) via the component options. A blank slug means
     * the role is not synced.
     *
     * @var    array<string, string>
     * @since  __DEPLOY_VERSION__
     */
    public const DEFAULT_ROLE_FIELDS = [
        'board'          => 'church_board_member',
        'positions'      => 'positions',
        'ministry_teams' => 'ministry_teams',
        'leader'         => 'leader',
    ];

    /**
     * PC field-definition slugs the directory-role columns are sourced from,
     * keyed `board` / `positions` / `ministry_teams` / `leader`.
     *
     * @param   array<string, string>  $roleFieldSlugs
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(
        private readonly array $roleFieldSlugs = self::DEFAULT_ROLE_FIELDS,
    ) {}

    /**
     * Map a single Person from a PC JSON:API payload to local-row attributes.
     *
     * @param   array<string, mixed>             $personData  The `data` object
     *                                                        from PC (a single
     *                                                        Person resource).
     * @param   array<int, array<string, mixed>> $included    The `included`
     *                                                        array from the
     *                                                        same PC response,
     *                                                        used to resolve
     *                                                        email / phone /
     *                                                        address relations.
     *
     * @return  array<string, mixed>  Column → value pairs for the details row.
     *
     * @throws  ApiException  When the PC payload is missing a usable person id.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function map(array $personData, array $included = []): array
    {
        $pcPersonId = $this->extractPersonId($personData);
        $attrs      = $this->personAttributes($personData);

        $byTypeId = $this->indexIncluded($included);
        $relIds   = $this->relationshipIds($personData);

        $primaryEmail   = $this->pickPrimaryEmail($byTypeId, $relIds['emails'] ?? []);
        $primaryPhone   = $this->pickPrimaryPhone($byTypeId, $relIds['phone_numbers'] ?? [], false);
        $mobilePhone    = $this->pickPrimaryPhone($byTypeId, $relIds['phone_numbers'] ?? [], true);
        $primaryAddress = $this->pickPrimaryAddress($byTypeId, $relIds['addresses'] ?? []);

        // Custom field-data, matched by PC field-definition slug. Used for the
        // printed-directory Officers / Church-Board sections (Phase 3). A
        // checkboxes field (ministry_teams) yields one value per checked option.
        $fields = $this->fieldDataBySlug($byTypeId, $relIds['field_data'] ?? []);

        $pcStatus = (string) ($attrs['status'] ?? 'active');

        $firstName  = $this->stringAttr($attrs, 'first_name');
        $middleName = $this->stringAttr($attrs, 'middle_name');
        $lastName   = $this->stringAttr($attrs, 'last_name');
        $nickname   = $this->stringAttr($attrs, 'nickname');
        $suffix     = $this->suffixFromComputedName($this->stringAttr($attrs, 'name'));

        // Directory display name: First [Middle] Last[, Suffix], collapsing the
        // gaps left by absent middle names. PC People has no dedicated suffix
        // field — a generational suffix (Jr/Sr/II–X) only surfaces in the
        // computed `name` (e.g. "Sherman Cox, III") — so we mine it from there
        // and graft it onto the structured first/middle/last parts.
        $fullName = trim((string) preg_replace('/\s+/', ' ', $firstName . ' ' . $middleName . ' ' . $lastName));

        if ($suffix !== '' && $fullName !== '') {
            $fullName .= ', ' . $suffix;
        }

        // A nickname PC stores apart from the first name (e.g. "Robert" with
        // nickname "Bob") is appended in parentheses; one that merely echoes
        // the first name is dropped.
        if ($nickname !== '' && strcasecmp($nickname, $firstName) !== 0) {
            $fullName = $fullName !== '' ? $fullName . ' (' . $nickname . ')' : $nickname;
        }

        return [
            'pc_person_id'         => $pcPersonId,
            'pc_last_synced_at'    => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
            'name'                 => $fullName !== '' ? $fullName : $this->stringAttr($attrs, 'name'),
            'lname'                => $lastName,
            'surname'              => $lastName,
            // Structured PC name parts stored like-for-like so display names can
            // be composed without re-parsing the computed `name`.
            'fname'                => $firstName,
            'mname'                => $middleName,
            'nickname'             => $nickname,
            'suffix'               => $suffix,
            'alias'                => $this->buildAlias($firstName, $lastName, $pcPersonId),
            'email_to'             => $primaryEmail,
            'telephone'            => $primaryPhone,
            'mobile'               => $mobilePhone,
            'address'              => $primaryAddress['street']   ?? '',
            'suburb'               => $primaryAddress['city']     ?? '',
            'state'                => $primaryAddress['state']    ?? '',
            'country'              => $primaryAddress['country']  ?? '',
            'postcode'             => $primaryAddress['zip']      ?? '',
            'birthdate'            => $this->dateAttr($attrs, 'birthdate'),
            'anniversary'          => $this->dateAttr($attrs, 'anniversary'),
            // Full church directory: every active member is listed by default,
            // regardless of their PC `directory_status` (which is mostly an
            // unset default, not a deliberate opt-out) or `child` flag.
            // `published` still gates active vs inactive membership; an admin
            // can hide an individual by clearing `display_in_directory`.
            'directory_scope'      => 'public',
            'pc_shared_info'       => $this->encodeSharedInfo($attrs['directory_shared_info'] ?? null),
            'display_in_directory' => 1,
            'published'            => $pcStatus === 'active' ? 1 : 0,
            'hidden_reason'        => $pcStatus === 'active' ? '' : 'inactive',
            // PC membership designation (Member / Regular Attender / Visitor /
            // …). Recorded so the directory can tell official members from the
            // household-mates pulled in by the family-expansion policy — drives
            // the admin membership filter and the members-only roster.
            'pc_membership'        => $this->stringAttr($attrs, 'membership'),
            // PC `gender` (Male / Female / '' when unset). Stored verbatim to
            // stay in line with PC rather than the legacy 0/1 "sex" encoding.
            'gender'               => $this->stringAttr($attrs, 'gender'),
            // PC custom fields (admin-mapped slugs) driving the printed-directory
            // Officers / Church-Board sections. A blank slug disables that role.
            'is_board'             => $this->roleBool('board', $fields),
            'is_leader'            => $this->roleBool('leader', $fields),
            'pc_positions'         => trim((string) ($this->roleValues('positions', $fields)[0] ?? '')),
            'pc_ministry_teams'    => implode(', ', $this->roleValues('ministry_teams', $fields)),
            // PC SocialProfile resources (site + url) as a JSON array, e.g.
            // [{"site":"Twitter","url":"https://twitter.com/.."}]. Empty string
            // when the member has none.
            'pc_social'            => $this->socialProfiles($byTypeId, $relIds['social_profiles'] ?? []),
            // Minors aren't listed on their own in the directory — they appear
            // under their family unit instead. Determined by age when a
            // birthdate is on file (most reliable for "under 18"), falling back
            // to PC's `child` flag when it isn't.
            'is_child'             => $this->isMinor($attrs) ? 1 : 0,
        ];
    }

    /**
     * Phase E: pull the PC `avatar` URL off a person. Returns the value of
     * the `avatar` attribute (the URL of an actually-uploaded photo), or
     * null when the person has no avatar. We deliberately do NOT fall
     * back to `demographic_avatar_url` here — that's PC's auto-generated
     * initials placeholder, which the cache decides to skip downstream.
     * Centralising the source-of-truth pick at the mapper level keeps the
     * cache's placeholder detection a defence-in-depth check rather than
     * the only filter.
     *
     * @param   array<string, mixed>  $personData
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public function extractAvatarUrl(array $personData): ?string
    {
        $attrs  = $this->personAttributes($personData);
        $avatar = $attrs['avatar'] ?? null;

        if (!\is_string($avatar) || $avatar === '') {
            return null;
        }

        return $avatar;
    }

    /**
     * Phase D: extract every `FieldDatum` related to a person via the
     * `field_data` relationship, paired with the PC FieldDefinition id it
     * targets. The sync engine resolves each `pc_field_id` against the
     * admin-managed mapping table and writes the value through
     * `FieldsHelper::setFieldValue('com_cwmconnect.member', ...)`.
     *
     * Sensitive PC resources (notes, medical, background checks) are never
     * requested in the engine's `?include=` query, so this method only ever
     * sees `FieldDatum` rows. Any non-FieldDatum entry in `$included` is
     * skipped silently.
     *
     * @param   array<string, mixed>             $personData  PC Person row.
     * @param   array<int, array<string, mixed>> $included    JSON:API
     *                                                        `included` array.
     *
     * @return  list<array{pc_field_id: int, value: string}>
     *
     * @since   __DEPLOY_VERSION__
     */
    public function extractFieldData(array $personData, array $included): array
    {
        $byTypeId = $this->indexIncluded($included);
        $refs     = $this->relationshipIds($personData)['field_data'] ?? [];
        $out      = [];

        foreach ($refs as $ref) {
            if ($ref['type'] !== 'FieldDatum') {
                continue;
            }

            $resource = $byTypeId['FieldDatum:' . $ref['id']] ?? null;

            if ($resource === null) {
                continue;
            }

            $attrs    = (array) ($resource['attributes'] ?? []);
            $value    = $attrs['value'] ?? null;

            if (!\is_scalar($value) || (string) $value === '') {
                continue;
            }

            $fdRel = $resource['relationships']['field_definition']['data'] ?? null;

            if (!\is_array($fdRel) || ($fdRel['type'] ?? null) !== 'FieldDefinition') {
                continue;
            }

            $pcFieldId = (int) ($fdRel['id'] ?? 0);

            if ($pcFieldId <= 0) {
                continue;
            }

            $out[] = [
                'pc_field_id' => $pcFieldId,
                'value'       => (string) $value,
            ];
        }

        return $out;
    }

    /**
     * Collect a person's custom field-data keyed by the PC field-definition
     * slug, e.g. `['church_board_member' => ['true'], 'ministry_teams' =>
     * ['Elders', 'Greeters']]`. A checkboxes field yields one entry per checked
     * option, so values are lists. Empty values are skipped.
     *
     * @param   array<string, array<string, mixed>>  $byTypeId  Indexed `included`.
     * @param   list<array{type: string, id: string}> $refs     `field_data` refs.
     *
     * @return  array<string, list<string>>
     *
     * @since   __DEPLOY_VERSION__
     */
    private function fieldDataBySlug(array $byTypeId, array $refs): array
    {
        $out = [];

        foreach ($refs as $ref) {
            if (($ref['type'] ?? null) !== 'FieldDatum') {
                continue;
            }

            $resource = $byTypeId['FieldDatum:' . ($ref['id'] ?? '')] ?? null;
            $value    = $resource['attributes']['value'] ?? null;

            if ($resource === null || !\is_scalar($value) || (string) $value === '') {
                continue;
            }

            $fdRel = $resource['relationships']['field_definition']['data'] ?? null;

            if (!\is_array($fdRel) || ($fdRel['type'] ?? null) !== 'FieldDefinition') {
                continue;
            }

            $definition = $byTypeId['FieldDefinition:' . ($fdRel['id'] ?? '')] ?? null;
            $slug       = $definition !== null ? trim((string) ($definition['attributes']['slug'] ?? '')) : '';

            if ($slug !== '') {
                $out[$slug][] = (string) $value;
            }
        }

        return $out;
    }

    /**
     * Whether a PC boolean field-data value is set (PC stores booleans as the
     * strings "true"/"false").
     *
     * @param   string  $value
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    private function isTrue(string $value): bool
    {
        return \in_array(strtolower(trim($value)), ['true', '1', 'yes'], true);
    }

    /**
     * Field-data values for a directory role, resolved through the configured
     * slug map. Empty when the role's slug is blank (mapping disabled) or absent
     * on the person.
     *
     * @param   string                       $roleKey  board|positions|ministry_teams|leader.
     * @param   array<string, list<string>>  $fields   Field-data keyed by slug.
     *
     * @return  list<string>
     *
     * @since   __DEPLOY_VERSION__
     */
    private function roleValues(string $roleKey, array $fields): array
    {
        $slug = trim((string) ($this->roleFieldSlugs[$roleKey] ?? ''));

        return $slug !== '' ? ($fields[$slug] ?? []) : [];
    }

    /**
     * 1/0 for a boolean directory-role field resolved through the slug map.
     *
     * @param   string                       $roleKey
     * @param   array<string, list<string>>  $fields
     *
     * @return  int
     *
     * @since   __DEPLOY_VERSION__
     */
    private function roleBool(string $roleKey, array $fields): int
    {
        return $this->isTrue((string) ($this->roleValues($roleKey, $fields)[0] ?? '')) ? 1 : 0;
    }

    /**
     * Resolve the person's PC household to local family-unit columns, or null
     * when they belong to no household (or the Household resource wasn't
     * included on this page). PC allows a person to sit in more than one
     * household; we take the first, which is the primary in practice.
     *
     * @param   array<string, mixed>             $personData  PC Person row.
     * @param   array<int, array<string, mixed>> $included    JSON:API
     *                                                        `included` array,
     *                                                        carrying the
     *                                                        Household resource.
     *
     * @return  array{pc_household_id: int, name: string, alias: string}|null
     *
     * @since   __DEPLOY_VERSION__
     */
    /**
     * The PC household ids a person belongs to (relationship refs only — no
     * `included` lookup needed). Used by the sync's discovery pass to find
     * households that contain a qualifying member, so their household-mates
     * (children, spouses) can be pulled into the directory too.
     *
     * @param   array<string, mixed>  $personData  PC Person row.
     *
     * @return  list<string>  PC household ids, or [].
     *
     * @since   __DEPLOY_VERSION__
     */
    public function householdRefIds(array $personData): array
    {
        $out = [];

        foreach ($this->relationshipIds($personData)['households'] ?? [] as $ref) {
            if ($ref['type'] === 'Household') {
                $out[] = $ref['id'];
            }
        }

        return $out;
    }

    public function extractHousehold(array $personData, array $included): ?array
    {
        $refs = $this->relationshipIds($personData)['households'] ?? [];

        if ($refs === []) {
            return null;
        }

        $byTypeId = $this->indexIncluded($included);

        foreach ($refs as $ref) {
            if ($ref['type'] !== 'Household') {
                continue;
            }

            $resource = $byTypeId['Household:' . $ref['id']] ?? null;

            if ($resource !== null) {
                return $this->mapHousehold($resource);
            }
        }

        return null;
    }

    /**
     * Map a PC Household resource to local family-unit columns. Returns null
     * for a non-positive id.
     *
     * @param   array<string, mixed>  $householdData  A PC Household resource.
     *
     * @return  array{pc_household_id: int, name: string, alias: string}|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function mapHousehold(array $householdData): ?array
    {
        $id = (int) ($householdData['id'] ?? 0);

        if ($id <= 0) {
            return null;
        }

        $attrs = \is_array($householdData['attributes'] ?? null) ? $householdData['attributes'] : [];
        $name  = trim((string) ($attrs['name'] ?? ''));

        if ($name === '') {
            $name = 'Household ' . $id;
        }

        $slug   = trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', strtolower($name)), '-');
        $avatar = $attrs['avatar'] ?? null;

        return [
            'pc_household_id' => $id,
            'name'            => $name,
            'alias'           => ($slug === '' ? 'household' : $slug) . '-pchh-' . $id,
            // Real uploaded family photo URL, or '' (generated -square.png
            // placeholders are skipped by the photo cache, not here).
            'avatar'          => \is_string($avatar) ? $avatar : '',
        ];
    }

    /**
     * Extract and validate the PC person id from a person resource.
     *
     * @param   array<string, mixed>  $personData
     *
     * @return  int
     *
     * @throws  ApiException  When the id is absent or not a positive integer.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function extractPersonId(array $personData): int
    {
        $id = $personData['id'] ?? null;

        if (!\is_string($id) && !\is_int($id)) {
            throw new ApiException('PC person payload missing string id.');
        }

        $intId = (int) $id;

        if ($intId <= 0) {
            throw new ApiException(\sprintf('PC person id is not a positive integer: %s', (string) $id));
        }

        return $intId;
    }

    /**
     * Pull the `attributes` sub-object, normalising missing/non-array values
     * to an empty array.
     *
     * @param   array<string, mixed>  $personData
     *
     * @return  array<string, mixed>
     *
     * @since   __DEPLOY_VERSION__
     */
    private function personAttributes(array $personData): array
    {
        $attrs = $personData['attributes'] ?? [];

        return \is_array($attrs) ? $attrs : [];
    }

    /**
     * Build a `Type:id` → resource lookup table from the JSON:API `included`
     * array so per-person relationship lookups are O(1).
     *
     * @param   array<int, array<string, mixed>>  $included
     *
     * @return  array<string, array<string, mixed>>
     *
     * @since   __DEPLOY_VERSION__
     */
    private function indexIncluded(array $included): array
    {
        $byKey = [];

        foreach ($included as $resource) {
            $type = $resource['type'] ?? null;
            $id   = $resource['id']   ?? null;

            if (\is_string($type) && (\is_string($id) || \is_int($id))) {
                $byKey[$type . ':' . $id] = $resource;
            }
        }

        return $byKey;
    }

    /**
     * Pull the lists of related ids the relationships block declares.
     * Normalises to-many (`data: [...]`) and to-one (`data: {...}`) shapes
     * into a uniform list-of-refs.
     *
     * @param   array<string, mixed>  $personData
     *
     * @return  array<string, list<array{type: string, id: string}>>
     *
     * @since   __DEPLOY_VERSION__
     */
    private function relationshipIds(array $personData): array
    {
        $relationships = $personData['relationships'] ?? [];

        if (!\is_array($relationships)) {
            return [];
        }

        $out = [];

        foreach ($relationships as $name => $rel) {
            if (!\is_array($rel) || !isset($rel['data'])) {
                continue;
            }

            $data = $rel['data'];

            if (isset($data['type'])) {
                $data = [$data];
            }

            $list = [];

            foreach ($data as $item) {
                if (\is_array($item) && isset($item['type'], $item['id'])) {
                    $list[] = ['type' => (string) $item['type'], 'id' => (string) $item['id']];
                }
            }

            $out[$name] = $list;
        }

        return $out;
    }

    /**
     * Pick the primary email address from the related Email resources,
     * falling back to the first available if none is flagged primary.
     *
     * @param   array<string, array<string, mixed>>     $byTypeId
     * @param   list<array{type: string, id: string}>   $refs
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function pickPrimaryEmail(array $byTypeId, array $refs): string
    {
        $candidates = [];

        foreach ($refs as $ref) {
            $resource = $byTypeId[$ref['type'] . ':' . $ref['id']] ?? null;

            if ($resource === null) {
                continue;
            }

            $attrs   = (array) ($resource['attributes'] ?? []);
            $address = (string) ($attrs['address'] ?? '');

            if ($address === '') {
                continue;
            }

            $candidates[] = ['address' => $address, 'primary' => (bool) ($attrs['primary'] ?? false)];
        }

        foreach ($candidates as $c) {
            if ($c['primary']) {
                return $c['address'];
            }
        }

        return $candidates[0]['address'] ?? '';
    }

    /**
     * Collect the member's PC SocialProfile resources into a compact JSON array
     * of `{site, url}` objects (e.g. Twitter / Facebook / LinkedIn / Instagram).
     * Entries without a usable URL are dropped; an empty set yields `''` so the
     * column stays blank rather than holding `[]`.
     *
     * @param   array<string, array<string, mixed>>  $byTypeId  Indexed `included`.
     * @param   array<int, array{type: string, id: string}>  $refs  social_profiles refs.
     *
     * @return  string  JSON array, or '' when the member has no social profiles.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function socialProfiles(array $byTypeId, array $refs): string
    {
        $out = [];

        foreach ($refs as $ref) {
            $resource = $byTypeId[$ref['type'] . ':' . $ref['id']] ?? null;

            if ($resource === null) {
                continue;
            }

            $attrs = (array) ($resource['attributes'] ?? []);
            $url   = trim((string) ($attrs['url'] ?? ''));
            $site  = trim((string) ($attrs['site'] ?? ''));

            if ($url === '') {
                continue;
            }

            $out[] = ['site' => $site, 'url' => $url];
        }

        return $out === [] ? '' : (string) json_encode($out, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
    }

    /**
     * Pick a phone number from the related PhoneNumber resources.
     *
     * When `$wantMobile` is true, only mobile-flagged numbers are considered.
     * When false, mobile numbers are skipped (so the same payload yields
     * distinct `telephone` and `mobile` columns without duplication).
     *
     * @param   array<string, array<string, mixed>>     $byTypeId
     * @param   list<array{type: string, id: string}>   $refs
     * @param   bool                                    $wantMobile
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function pickPrimaryPhone(array $byTypeId, array $refs, bool $wantMobile): string
    {
        $candidates = [];

        foreach ($refs as $ref) {
            $resource = $byTypeId[$ref['type'] . ':' . $ref['id']] ?? null;

            if ($resource === null) {
                continue;
            }

            $attrs    = (array) ($resource['attributes'] ?? []);
            $number   = (string) ($attrs['number'] ?? '');
            $location = strtolower((string) ($attrs['location'] ?? ''));
            $isMobile = $location === 'mobile' || str_contains($location, 'mobile');

            if ($number === '') {
                continue;
            }

            if ($wantMobile && !$isMobile) {
                continue;
            }

            if (!$wantMobile && $isMobile) {
                continue;
            }

            $candidates[] = ['number' => $number, 'primary' => (bool) ($attrs['primary'] ?? false)];
        }

        foreach ($candidates as $c) {
            if ($c['primary']) {
                return $c['number'];
            }
        }

        return $candidates[0]['number'] ?? '';
    }

    /**
     * Pick the primary address from the related Address resources.
     *
     * @param   array<string, array<string, mixed>>     $byTypeId
     * @param   list<array{type: string, id: string}>   $refs
     *
     * @return  array{street: string, city: string, state: string, country: string, zip: string}|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function pickPrimaryAddress(array $byTypeId, array $refs): ?array
    {
        $candidates = [];

        foreach ($refs as $ref) {
            $resource = $byTypeId[$ref['type'] . ':' . $ref['id']] ?? null;

            if ($resource === null) {
                continue;
            }

            $attrs  = (array) ($resource['attributes'] ?? []);
            $street = (string) ($attrs['street'] ?? $attrs['street_line_1'] ?? '');

            if ($street === '') {
                continue;
            }

            $candidates[] = [
                'attrs'   => $attrs,
                'primary' => (bool) ($attrs['primary'] ?? false),
            ];
        }

        $pick = null;

        foreach ($candidates as $c) {
            if ($c['primary']) {
                $pick = $c;
                break;
            }
        }

        $pick ??= $candidates[0] ?? null;

        if ($pick === null) {
            return null;
        }

        $attrs = $pick['attrs'];

        return [
            'street'  => (string) ($attrs['street'] ?? $attrs['street_line_1'] ?? ''),
            'city'    => (string) ($attrs['city'] ?? ''),
            'state'   => (string) ($attrs['state'] ?? ''),
            'country' => (string) ($attrs['country_code'] ?? $attrs['country_name'] ?? ''),
            'zip'     => (string) ($attrs['zip'] ?? ''),
        ];
    }

    /**
     * Serialise the `directory_shared_info` object to JSON for the
     * `pc_shared_info` column. Returns null when there's nothing to store.
     *
     * @param   mixed  $sharedInfo
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function encodeSharedInfo(mixed $sharedInfo): ?string
    {
        if (!\is_array($sharedInfo)) {
            return null;
        }

        try {
            return json_encode($sharedInfo, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
    }

    /**
     * Mine a generational suffix from PC's computed `name` attribute. PC
     * People exposes no dedicated suffix field; a suffix (Jr/Sr/II–X or a
     * numeric ordinal) only appears in `name`, formatted as a trailing
     * ", <suffix>" segment (e.g. "Sherman Cox, III"). Anything that isn't a
     * recognised generational suffix — so a "Last, First" computed format
     * can't masquerade as one — yields ''.
     *
     * @param   string  $name  The computed `name` attribute.
     *
     * @return  string  The suffix without its comma/trailing dot, or ''.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function suffixFromComputedName(string $name): string
    {
        if (preg_match('/,\s*([^,]+?)\s*$/', $name, $m) !== 1) {
            return '';
        }

        $candidate = trim($m[1]);

        return preg_match('/^(?:Jr|Sr|II|III|IV|V|VI|VII|VIII|IX|X|[0-9]+(?:st|nd|rd|th))\.?$/i', $candidate) === 1
            ? rtrim($candidate, '.')
            : '';
    }

    /**
     * Is this person a minor (excluded from standalone directory listings,
     * shown only under their family unit)? Age from the birthdate decides when
     * one is on file — the most faithful reading of "under 18" — otherwise we
     * defer to PC's `child` flag (the church-maintained signal), since most
     * people have no birthdate recorded.
     *
     * @param   array<string, mixed>  $attrs  PC person attributes.
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    private function isMinor(array $attrs): bool
    {
        $age = $this->ageFromBirthdate($this->stringAttr($attrs, 'birthdate'));

        if ($age !== null) {
            return $age < 18;
        }

        return (bool) ($attrs['child'] ?? false);
    }

    /**
     * Whole years between a `YYYY-MM-DD` birthdate and today, or null when the
     * value is missing / unparseable / in the future.
     *
     * @param   string  $birthdate  Raw PC birthdate.
     *
     * @return  int|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function ageFromBirthdate(string $birthdate): ?int
    {
        if ($birthdate === '') {
            return null;
        }

        try {
            $born = new \DateTimeImmutable(substr($birthdate, 0, 10));
        } catch (\Exception) {
            return null;
        }

        $today = new \DateTimeImmutable();

        if ($born > $today) {
            return null;
        }

        return $today->diff($born)->y;
    }

    /**
     * Read a string attribute, defaulting to empty string on missing /
     * non-string values.
     *
     * @param   array<string, mixed>  $attrs
     * @param   string                $key
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function stringAttr(array $attrs, string $key): string
    {
        $value = $attrs[$key] ?? '';

        return \is_string($value) ? $value : '';
    }

    /**
     * Read a date attribute, defaulting to the legacy `0000-00-00 00:00:00`
     * sentinel the existing schema uses for "unset" DATETIME columns.
     *
     * @param   array<string, mixed>  $attrs
     * @param   string                $key
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function dateAttr(array $attrs, string $key): string
    {
        $value = $attrs[$key] ?? null;

        if (!\is_string($value) || $value === '') {
            return '0000-00-00 00:00:00';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value . ' 00:00:00';
        }

        return $value;
    }

    /**
     * Build a deterministic, URL-safe alias from the person's name. Suffixed
     * with `-pc-<id>` so two people named "Jane Doe" don't collide on the
     * `alias` column (it has a UNIQUE-bin collation in the legacy schema).
     *
     * @param   string  $first
     * @param   string  $last
     * @param   int     $pcPersonId
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function buildAlias(string $first, string $last, int $pcPersonId): string
    {
        $base = trim($first . '-' . $last);
        $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $base) ?? '');
        $slug = trim($slug, '-');

        if ($slug === '') {
            return 'pc-' . $pcPersonId;
        }

        return $slug . '-pc-' . $pcPersonId;
    }
}
