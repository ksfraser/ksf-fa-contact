# RTM — ksf_FA_Contact

**Module:** `ksf_FA_Contact`
**Version:** `1.0.0`
**Requirement definitions:** `ProjectDocuments/Requirements.md`

| BR | Requirement | Implementation | Tests |
|----|-------------|----------------|-------|
| BR-CT-001 | Contact resolution ownership — owns `contact` type, answers RELATED_TO_FIELDS + CONTACT_RESOLVE | `hooks.php` (`hooks_ksf_FA_Contact::RELATED_TO_FIELDS`, `::CONTACT_RESOLVE`, `::CONTACT_FIELDS`), `ContactService` (`resolveById`, `resolveByEntity`, `fieldMap`) | (FA runtime only — hooks) |
| BR-CT-002 | Contact display default — First + Last name, optional index, email fallback | `ContactDTO` (`getDisplayName`) | `ContactDtoTest` |
| BR-CT-003 | Resolved DTO for RelatedTo — field map + ContactResolvedDto | `ContactService::fieldMap()`, `hooks.php` `RELATED_TO_FIELDS` | (via related-to-core) |
| BR-CT-004 | Native FA contact layer — reads via `get_crm_person`/`get_crm_persons` guarded by `function_exists()` | `ContactRepository` (`findById`, `findByEmail`, `findByEntity`, `findActive`) | `ContactRepositoryTest` |
