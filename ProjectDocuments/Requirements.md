# Business Requirements — ksf_FA_Contact

**Package:** `ksfraser/ksf-fa-contact`
**Version:** `1.0.0`
**Namespace:** `Ksfraser\FrontAccounting\Contact\*`
**Module sequence:** CT (Contact)

---

## BR-CT-001 — Contact resolution ownership

**Status:** IMPLEMENTED

The system SHALL provide `ksf_FA_Contact` as the owning module for contact/person
resolution. CRM defers to Contact for contact display rather than owning contact
info itself.

1. Contact SHALL own the `contact` type in the RelatedTo registry.
2. Contact SHALL answer `RELATED_TO_FIELDS` for the `contact` type with the
   field map: `['displayName' => 'Contact', 'firstName' => 'First Name',
   'lastName' => 'Last Name', 'contactIndex' => 'Index']`.
3. Contact SHALL answer `CONTACT_RESOLVE` by building a
   `ContactResolvedDto` from native FA `crm_persons`/`crm_contacts` data.
4. Consuming modules MAY override by registering their own `CONTACT_RESOLVE`
   listener or by injecting a custom resolver.

---

## BR-CT-002 — Contact display default

**Status:** IMPLEMENTED

The contact display default SHALL be First + Last name, optionally suffixed
with the contact-relation index in parentheses.

1. `ContactDTO::getDisplayName()` SHALL return `name` + ` ' ' + name2`.
2. When `name2` is empty, return `name` alone.
3. When both are empty, fall back to `email`.
4. When `contactId` is set and > 0, append ` (index)`.
5. All values SHALL be HTML-escaped at render time by `ContactResolvedDto`.

---

## BR-CT-003 — Resolved DTO for RelatedTo

**Status:** IMPLEMENTED

Contact SHALL build `ContactResolvedDto` instances (from
`ksfraser/related-to-core`) for RelatedTo display.

1. `ContactResolvedDto` wraps a `RelatedRecordDto` with resolved fields,
   overridable labels, and a URL.
2. `toHtml()` returns the escaped display name.
3. `toHtmlA()` wraps the display name in an anchor tag to the contact URL.
4. `toTd()` wraps the display name in a table cell.
5. `render()` is an alias for `toTd()`.

---

## BR-CT-004 — Native FA contact layer

**Status:** IMPLEMENTED

Contact SHALL read through FA's native CRM functions
(`get_crm_person`, `get_crm_persons`) guarded by `function_exists()`, never raw
SQL against `crm_*` tables. Degrades gracefully outside FA.
