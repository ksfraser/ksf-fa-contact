<?php

declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Contact\Service;

use Ksfraser\FrontAccounting\Contact\DTO\ContactDTO;
use Ksfraser\FrontAccounting\Contact\Repository\ContactRepository;
use ksfraser\RelatedTo\DTO\ContactResolvedDto;
use ksfraser\RelatedTo\DTO\RelatedRecordDto;

/**
 * ContactService — use-case layer for contact resolution and display.
 *
 * Composes ContactRepository and builds ContactResolvedDto instances for the
 * RelatedTo display layer. Contact is the owning module for contact resolution;
 * consuming modules override by registering their own CONTACT_RESOLVE listener
 * or by injecting a custom resolver.
 *
 * @package Ksfraser\FrontAccounting\Contact\Service
 * @since   1.0.0
 * @BABOK Related: BR-CT-001
 */
final class ContactService
{
    /** @var ContactRepository */
    private $repo;

    /**
 * @param ContactRepository|null $repo Contact repository (defaults to new)
 * @since 1.0.0
 * @BABOK Related: BR-CT-001
 */
    public function __construct(?ContactRepository $repo = null)
    {
        $this->repo = $repo ?? new ContactRepository();
    }

    /**
     * Resolve one contact by person id into a ContactResolvedDto.
     *
     * @param int $personId Person id (crm_persons.id)
     * @param RelatedRecordDto|null $dto Optional underlying RelatedRecordDto
 * @return ContactResolvedDto The resolved contact (empty fields when not found)
 * @since 1.0.0
 * @BABOK Related: BR-CT-001
 */
    public function resolveById(int $personId, ?RelatedRecordDto $dto = null): ContactResolvedDto
    {
        $contact = $this->repo->findById($personId);
        if ($contact === null) {
            return new ContactResolvedDto(
                $dto ?? new RelatedRecordDto('contact', '0', 'contact', '0', null),
                null,
                null,
                null
            );
        }
        $dto = $dto ?? new RelatedRecordDto(
            'contact',
            (string) $contact->id,
            'contact',
            (string) $contact->id,
            null
        );
        $resolved = new ContactResolvedDto(
            $dto,
            $contact->name,
            $contact->name2,
            $contact->contactId
        );
        $resolved->setUrl('/crm/crm_contacts.php?contact_id=' . (int) $contact->id);
        return $resolved;
    }

    /**
     * Resolve the contacts of an entity into ContactResolvedDto[].
     *
     * @param string $type     Contact type ('customer', 'cust_branch', …)
     * @param string $entityId The related entity's id
     * @param string|null $action Optional contact action
     * @param RelatedRecordDto|null $parentDto Optional parent RelatedRecordDto
 * @return ContactResolvedDto[]
 * @since 1.0.0
 * @BABOK Related: BR-CT-001
 */
    public function resolveByEntity(
        string $type,
        string $entityId,
        ?string $action = null,
        ?RelatedRecordDto $parentDto = null
    ): array {
        $contacts = $this->repo->findByEntity($type, $entityId, $action);
        $dtos   = array();
        foreach ($contacts as $contact) {
            $dto = new RelatedRecordDto(
                'contact',
                (string) $contact->id,
                'contact',
                (string) $contact->id,
                null
            );
            $resolved = new ContactResolvedDto(
                $dto,
                $contact->name,
                $contact->name2,
                $contact->contactId
            );
            $resolved->setUrl('/crm/crm_contacts.php?contact_id=' . (int) $contact->id);
            $dtos[] = $resolved;
        }
        return $dtos;
    }

    /**
     * Return the field map for the contact type (for RELATED_TO_FIELDS
     * consumers building their own Resolved DTOs).
     *
 * @return array<string, string> Field name => display label
 * @since 1.0.0
 * @BABOK Related: BR-CT-001
 */
    public function fieldMap(): array
    {
        return array(
            'displayName' => _('Contact'),
            'firstName'   => _('First Name'),
            'lastName'    => _('Last Name'),
            'contactIndex'=> _('Index'),
        );
    }

    /**
     * Search contacts by name, email, or phone.
     *
     * @param string $query Search term
     * @param int $limit Maximum rows
     * @param bool $includeInactive Include inactive persons
     * @return ContactDTO[]
     * @since 1.0.0
     */
    public function search(string $query, int $limit = 20, bool $includeInactive = false): array
    {
        $query = trim($query);
        if ($query === '' || $limit <= 0) {
            return array();
        }
        $needle = strtolower($query);
        $found  = array();
        foreach ($this->repo->findActive() as $contact) {
            $haystack = strtolower(
                $contact->name . ' ' . $contact->name2 . ' ' .
                $contact->email . ' ' . $contact->phone . ' ' . $contact->phone2
            );
            if (strpos($haystack, $needle) === false) {
                continue;
            }
            if (!$includeInactive && $contact->inactive) {
                continue;
            }
            $found[] = $contact;
            if (count($found) >= $limit) {
                break;
            }
        }
        return $found;
    }
}
