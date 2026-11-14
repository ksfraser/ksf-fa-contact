-- ksf_FA_Contact: contact profiles side table
--
-- I/F-B classification and CASL consent live on the debtor (see
-- FR-CRM-007-003 / FR-CRM-007-004 in ksf_FA_CRM/ProjectDcs). This table holds
-- the per-contact profile extension keyed on native crm_persons.id.
--
-- Table name uses bare 0_ prefix; db_import() substitutes TB_PREF at install.

CREATE TABLE IF NOT EXISTS `0_ksf_contact_profiles` (
  `person_id`     INT(11) NOT NULL COMMENT 'FK to crm_persons.id (native)',
  `person_type`   CHAR(1) NOT NULL DEFAULT 'I' COMMENT 'I=Individual, F=Family, B=Business (debtor axis)',
  `source`        VARCHAR(32) NULL COMMENT 'manual | import | web_form | phone | counter',
  `notes`         TEXT,
  `updated_by`    INT(11) NULL COMMENT 'user_id who set it',
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`person_id`),
  KEY `idx_person_type` (`person_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
