<?php

declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Contact\Repository;

use Ksfraser\FrontAccounting\Contact\DTO\ContactDTO;

/**
 * ContactRepository — SRP access to native FA crm_persons / crm_contacts.
 *
 * Reads through FA's native functions (get_crm_person, get_crm_persons) guarded
 * by function_exists(), degrading gracefully outside FA. Issues no raw SQL
 * against crm_* tables itself.
 *
 * @package Ksfraser\FrontAccounting\Contact\Repository
 * @since   1.0.0
 * @BABOK Related: BR-CT-004
 */
final class ContactRepository
{
    /** @var bool Whether the native CRM include has already been attempted */
    private static $includeAttempted = false;

    /**
     * Resolve one person by person id.
     *
     * @param int $personId Person id (crm_persons.id)
 * @return ContactDTO|null The person, or null when not found
 * @since 1.0.0
 * @BABOK Related: BR-CT-004
 */
    public function findById(int $personId): ?ContactDTO
    {
        if ($personId <= 0) {
            return null;
        }
        $this->ensureNativeFunctions();
        if (!function_exists('get_crm_person')) {
            return null;
        }
        try {
            $row = get_crm_person($personId);
        } catch (\Throwable $e) {
            return null;
        }
        if (!is_array($row) || !isset($row['id'])) {
            return null;
        }
        return $this->hydrate($row, null);
    }

    /**
     * Resolve every person whose email matches.
     *
     * @param string $email Email address to match
     * @param bool   $includeInactive Include inactive persons
 * @return ContactDTO[] Matching people
 * @since 1.0.0
 * @BABOK Related: BR-CT-004
 */
    public function findByEmail(string $email, bool $includeInactive = false): array
    {
        $email = trim($email);
        if ($email === '') {
            return array();
        }
        $needle = strtolower($email);
        $found  = array();
        foreach ($this->allRows() as $row) {
            if (strtolower((string) ($row['email'] ?? '')) !== $needle) {
                continue;
            }
            if (!$includeInactive && !empty($row['inactive'])) {
                continue;
            }
            $found[] = $this->hydrate($row, null);
        }
        return $found;
    }

    /**
     * Resolve the contacts of an entity (e.g. a customer branch).
     *
     * @param string $type     Contact type ('customer', 'cust_branch', …)
     * @param string $entityId The related entity's id
     * @param string|null $action Optional contact action to narrow to
 * @return ContactDTO[] The entity's contacts
 * @since 1.0.0
 * @BABOK Related: BR-CT-004
 */
    public function findByEntity(string $type, string $entityId, ?string $action = null): array
    {
        $type     = trim($type);
        $entityId = trim($entityId);
        if ($type === '' || $entityId === '') {
            return array();
        }
        $this->ensureNativeFunctions();
        if (!function_exists('get_crm_persons')) {
            return array();
        }
        try {
            $result = get_crm_persons($type, $action, $entityId, null, false);
        } catch (\Throwable $e) {
            return array();
        }
        $contacts = array();
        foreach ($this->rowsFrom($result) as $row) {
            if (!isset($row['entity_id'])) {
                $row['entity_id'] = $entityId;
            }
            $contacts[] = $this->hydrate($row, null);
        }
        return $contacts;
    }

    /**
     * Every active person the native relation query can see.
     *
 * @return ContactDTO[]
 * @since 1.0.0
 * @BABOK Related: BR-CT-004
 */
    public function findActive(): array
    {
        $this->ensureNativeFunctions();
        if (!function_exists('get_crm_persons')) {
            return array();
        }
        try {
            $result = get_crm_persons(null, null, null, null, true);
        } catch (\Throwable $e) {
            return array();
        }
        $contacts = array();
        foreach ($this->rowsFrom($result) as $row) {
            if (!empty($row['inactive'])) {
                continue;
            }
            $contacts[] = $this->hydrate($row, null);
        }
        return $contacts;
    }

    /**
     * Hydrate a native row into a ContactDTO.
     *
     * @param array $row Native row (may carry extra crm_contacts columns)
     * @param int|null $contactId Optional xref row id
     * @return ContactDTO
     * @since 1.0.0
     */
    private function hydrate(array $row, ?int $contactId = null): ContactDTO
    {
        return new ContactDTO(
            (int) ($row['id'] ?? 0),
            (string) ($row['ref'] ?? ''),
            (string) ($row['name'] ?? ''),
            (string) ($row['name2'] ?? ''),
            (string) ($row['address'] ?? ''),
            (string) ($row['phone'] ?? ''),
            (string) ($row['phone2'] ?? ''),
            (string) ($row['fax'] ?? ''),
            (string) ($row['email'] ?? ''),
            (string) ($row['lang'] ?? ''),
            (string) ($row['notes'] ?? ''),
            !empty($row['inactive']),
            $contactId
        );
    }

    /**
     * Normalise a native result into a list of rows.
     *
     * @param mixed $result Native return value
     * @return array<int, array> Rows
     * @since 1.0.0
     */
    private function rowsFrom($result): array
    {
        if (is_array($result)) {
            $rows = array();
            foreach ($result as $row) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }
            return $rows;
        }
        if (is_object($result) && function_exists('db_fetch')) {
            $rows = array();
            while (($row = db_fetch($result)) !== false && $row !== null) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }
            return $rows;
        }
        return array();
    }

    /**
     * Every person row the native relation query can see.
     *
     * @return array<int, array> Person rows
     * @since 1.0.0
     */
    private function allRows(): array
    {
        $this->ensureNativeFunctions();
        if (!function_exists('get_crm_persons')) {
            return array();
        }
        try {
            $result = get_crm_persons(null, null, null, null, true);
        } catch (\Throwable $e) {
            return array();
        }
        return $this->rowsFrom($result);
    }

    /**
     * Make sure FA's native CRM functions are loaded.
     *
     * @return void
     * @since 1.0.0
     */
    private function ensureNativeFunctions(): void
    {
        if (function_exists('get_crm_person')) {
            return;
        }
        if (self::$includeAttempted) {
            return;
        }
        self::$includeAttempted = true;
        $pathToRoot = isset($GLOBALS['path_to_root']) ? (string) $GLOBALS['path_to_root'] : '';
        if ($pathToRoot === '') {
            return;
        }
        $file = $pathToRoot . '/includes/db/crm_contacts_db.inc';
        if (is_file($file)) {
            include_once $file;
        }
    }
}
