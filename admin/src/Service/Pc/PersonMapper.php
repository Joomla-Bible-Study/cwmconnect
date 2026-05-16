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
 * Scope per Phase C: name / contact info / address / privacy gates.
 * Custom fields (`field_data`) defer to Phase D. Avatar / image cache defer
 * to Phase E. Household + campus relationship resolution defer to D (the
 * columns exist after Phase A's migration and this PR's companion migration,
 * but populating them with the right local FK is the next phase's job).
 *
 * The mapper is intentionally pure (no DB, no logger, no clock) — easy to
 * unit-test by feeding canned PC payloads.
 *
 * @since  __DEPLOY_VERSION__
 */
final class PersonMapper
{
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

        $directoryStatus = (string) ($attrs['directory_status'] ?? 'everyone');
        $isChild         = (bool) ($attrs['child'] ?? false);

        $firstName = $this->stringAttr($attrs, 'first_name');
        $lastName  = $this->stringAttr($attrs, 'last_name');
        $fullName  = trim($firstName . ' ' . $lastName);

        return [
            'pc_person_id'         => $pcPersonId,
            'pc_last_synced_at'    => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'name'                 => $fullName !== '' ? $fullName : $this->stringAttr($attrs, 'name'),
            'lname'                => $lastName,
            'surname'              => $lastName,
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
            'directory_scope'      => $this->mapDirectoryScope($directoryStatus),
            'pc_shared_info'       => $this->encodeSharedInfo($attrs['directory_shared_info'] ?? null),
            'display_in_directory' => ($isChild || $directoryStatus === 'no') ? 0 : 1,
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
     * Translate PC's `directory_status` string to our `directory_scope` enum.
     *
     * @param   string  $pcStatus
     *
     * @return  string  One of 'public', 'household', 'hidden'.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function mapDirectoryScope(string $pcStatus): string
    {
        return match ($pcStatus) {
            'no'             => 'hidden',
            'limited_access',
            'household_only' => 'household',
            default          => 'public',
        };
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
