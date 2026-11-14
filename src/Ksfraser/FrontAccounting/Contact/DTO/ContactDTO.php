<?php

declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Contact\DTO;

/**
 * Contact person — immutable value object wrapping native FA crm_persons data.
 *
 * name = first name, name2 = last name (matching the native editor's labels).
 * contactId is the crm_contacts.xref row id, null when the person is not
 * attached to an entity.
 *
 * @package Ksfraser\FrontAccounting\Contact\DTO
 * @since   1.0.0
 */
final class ContactDTO
{
    public $id;
    public $ref;
    public $name;
    public $name2;
    public $address;
    public $phone;
    public $phone2;
    public $fax;
    public $email;
    public $lang;
    public $notes;
    public $inactive;
    public $contactId;

    public function __construct(
        int $id,
        string $ref = '',
        string $name = '',
        string $name2 = '',
        string $address = '',
        string $phone = '',
        string $phone2 = '',
        string $fax = '',
        string $email = '',
        string $lang = '',
        string $notes = '',
        bool $inactive = false,
        ?int $contactId = null
    ) {
        $this->id        = $id;
        $this->ref       = $ref;
        $this->name      = $name;
        $this->name2     = $name2;
        $this->address   = $address;
        $this->phone     = $phone;
        $this->phone2    = $phone2;
        $this->fax       = $fax;
        $this->email     = $email;
        $this->lang      = $lang;
        $this->notes     = $notes;
        $this->inactive  = $inactive;
        $this->contactId = $contactId;
    }

    public function getDisplayName(): string
    {
        $display = trim($this->name);
        $second  = trim($this->name2);
        if ($second !== '') {
            $display = $display === '' ? $second : $display . ' ' . $second;
        }
        if ($display === '') {
            $display = $this->email;
        }
        if ($this->contactId !== null && $this->contactId > 0) {
            $display .= ' (' . (int) $this->contactId . ')';
        }
        return $display;
    }

    public function toArray(): array
    {
        return array(
            'id'         => $this->id,
            'ref'        => $this->ref,
            'name'       => $this->name,
            'name2'      => $this->name2,
            'address'    => $this->address,
            'phone'      => $this->phone,
            'phone2'     => $this->phone2,
            'fax'        => $this->fax,
            'email'      => $this->email,
            'lang'       => $this->lang,
            'notes'      => $this->notes,
            'inactive'   => $this->inactive,
            'contact_id' => $this->contactId,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['id'] ?? 0),
            (string) ($data['ref'] ?? ''),
            (string) ($data['name'] ?? ''),
            (string) ($data['name2'] ?? ''),
            (string) ($data['address'] ?? ''),
            (string) ($data['phone'] ?? ''),
            (string) ($data['phone2'] ?? ''),
            (string) ($data['fax'] ?? ''),
            (string) ($data['email'] ?? ''),
            (string) ($data['lang'] ?? ''),
            (string) ($data['notes'] ?? ''),
            !empty($data['inactive']),
            isset($data['contact_id']) && is_numeric($data['contact_id'])
                ? (int) $data['contact_id']
                : null
        );
    }
}
