<?php

declare(strict_types=1);

/**
 * KSF FrontAccounting Module Hooks — ksf_FA_Contact
 *
 * Contact is the owning module for contact/person resolution. It answers the
 * RELATED_TO_FIELDS hook for the 'contact' type and provides ContactResolvedDto
 * instances built from native FA crm_persons/crm_contacts data.
 *
 * Contact resolution defaults: show First + Last name, optionally suffixed with
 * the contact-relation index in parentheses. Consuming modules may override
 * labels/fields via RELATED_TO_FIELDS or by injecting a custom resolver.
 *
 * @package KsfFA_ksf_FA_Contact
 * @version 1.0.0
 */

// ComposerDependencies: per-module copy of the template with MODULENAME
// replaced in the namespace. Required at the top of hooks.php to break the
// chicken-and-egg loop (vendor/ doesn't exist until composer runs).
require_once __DIR__ . '/ComposerDependencies.php';

\ksfraser\FrontAccounting\Contact\Utils\ComposerDependencies::ensure(__DIR__);

$autoload_path = dirname(__FILE__) . '/vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
}

require_once __DIR__ . '/includes/common.inc';
require_once __DIR__ . '/includes/events.inc';

use ksfraser\FrontAccounting\Contact\Service\ContactService;
use ksfraser\RelatedTo\DTO\RelatedRecordDto;

define('SS_ksf_FA_Contact', 152 << 8);

if (!defined('SA_CONTACT_VIEW')) {
    define('SA_CONTACT_VIEW', SS_ksf_FA_Contact | 1);
    define('SA_CONTACT_MANAGE', SS_ksf_FA_Contact | 2);
}

/**
 * Contact module hooks — owns the 'contact' type in RelatedTo, answers
 * RELATED_TO_FIELDS, CONTACT_RESOLVE and CONTACT_FIELDS.
 *
 * @package KsfFA_ksf_FA_Contact
 * @version 1.0.0
 * @BABOK Related: BR-CT-001
 */
class hooks_ksf_FA_Contact extends hooks {
    /**
     * The hook names this module answers to.
     */
    const RESPONDERS = array('RELATED_TO_FIELDS', 'CONTACT_RESOLVE', 'CONTACT_FIELDS');

    var $module_name = 'ksf_FA_Contact';
    var $version = '1.0.0';

    /**
     * @BABOK Related: BR-CT-001
     */
    function install_tabs($app) {
        // Contact is reached via hooks from other modules; no dedicated app tab.
    }

    /**
     * @BABOK Related: BR-CT-001
     */
    function install_options($app) {
    }

    /**
     * @BABOK Related: BR-CT-001
     */
    function install_access() {
        $security_sections[SS_ksf_FA_Contact] = _("Contact Management");
        $security_areas['SA_CONTACT_VIEW'] = array(SA_CONTACT_VIEW, _("View contacts"));
        $security_areas['SA_CONTACT_MANAGE'] = array(SA_CONTACT_MANAGE, _("Manage contacts"));

        return array($security_areas, $security_sections);
    }

    /**
     * @BABOK Related: BR-CT-001
     */
    function activate_extension($company, $check_only = true) {
        if ($check_only) {
            return true;
        }

        \ksfraser\FrontAccounting\Contact\Utils\ComposerDependencies::ensure(__DIR__);

        $this->update_databases($company, array(
            '0_ksf_contact_profiles.sql' => array('ksf_contact_profiles'),
        ), false);

        return true;
    }

    private function ensure_composer_dependencies() {
        \ksfraser\FrontAccounting\Contact\Utils\ComposerDependencies::ensure(__DIR__);
    }

    // ------------------------------------------------------------------
    // Inter-module discovery contract
    // ------------------------------------------------------------------

    /**
     * @BABOK Related: BR-CT-001
     */
    public function getModuleConstants(&$data, $opts = null) {
        $data['constants'] = array(
            'SS_ksf_FA_Contact' => SS_ksf_FA_Contact,
            'SA_CONTACT_VIEW' => SA_CONTACT_VIEW,
            'SA_CONTACT_MANAGE' => SA_CONTACT_MANAGE,
        );
        return true;
    }

    /**
     * @BABOK Related: BR-CT-001
     */
    public function getModuleCapabilities(&$data, $opts = null) {
        $data['capabilities'] = self::RESPONDERS;
        return true;
    }

    /**
     * @BABOK Related: BR-CT-001
     */
    public function hasCapability(&$data, $opts = null) {
        $capability = is_array($opts) && isset($opts['capability']) ? $opts['capability'] : null;
        if ($capability === null && isset($data['capability'])) {
            $capability = $data['capability'];
        }
        $data['has_capability'] = in_array($capability, self::RESPONDERS, true);
        return $data['has_capability'];
    }

    /**
     * @BABOK Related: BR-CT-001
     */
    public function respondToCapabilityRequest(&$data, $opts = null) {
        $request = is_array($opts) && isset($opts['request'])
            ? $opts['request']
            : (isset($data['request']) ? $data['request'] : 'capabilities');

        $data['request'] = $request;
        $data['module'] = $this->module_name;

        if (strpos($request, 'has:') === 0) {
            return $this->hasCapability($data, array('capability' => substr($request, 4)));
        }

        switch ($request) {
            case 'capabilities':
                return $this->getModuleCapabilities($data, $opts);
            case 'constants':
                return $this->getModuleConstants($data, $opts);
            default:
                $data['error'] = 'Unknown request type: ' . $request;
                return null;
        }
    }

    // ------------------------------------------------------------------
    // Hook responders
    // ------------------------------------------------------------------

    /**
     * RELATED_TO_FIELDS — the owning module answers with the display fields
     * it wants shown for the 'contact' type.
     *
     * $data carries ['module' => <consumer>, 'type' => 'contact',
     * 'fields' => &[]] by reference. Contact answers with:
     *   ['displayName' => 'Contact', 'firstName' => 'First Name',
     *    'lastName' => 'Last Name', 'contactIndex' => 'Index']
     *
     * @param array $data Modified by reference
     * @param array|null $opts
 * @return null
 * @since 1.0.0
 * @BABOK Related: BR-CT-001
 */
    public function RELATED_TO_FIELDS(&$data, $opts = null) {
        if (!isset($data['module']) || !is_string($data['module'])) {
            $data['error'] = 'RELATED_TO_FIELDS requires data[module]';
            return null;
        }
        if (!isset($data['type']) || !is_string($data['type'])) {
            $data['error'] = 'RELATED_TO_FIELDS requires data[type]';
            return null;
        }
        if ($data['type'] !== 'contact') {
            // Contact only owns the 'contact' type; other modules answer for
            // their own types. Leave fields untouched.
            return null;
        }
        if (!isset($data['fields']) || !is_array($data['fields'])) {
            $data['fields'] = array();
        }
        $data['fields'] = array_merge($data['fields'], array(
            'displayName' => _('Contact'),
            'firstName'   => _('First Name'),
            'lastName'    => _('Last Name'),
            'contactIndex'=> _('Index'),
        ));
        return null;
    }

    /**
     * CONTACT_RESOLVE — resolve one contact by person id, returning a
     * ContactResolvedDto. This is the default contact resolution that
     * consuming modules can override by registering their own listener.
     *
     * $data carries ['person_id' => int, 'dto' => RelatedRecordDto,
     * 'result' => &ContactResolvedDto|null] by reference.
     *
     * @param array $data Modified by reference
     * @param array|null $opts
 * @return null
 * @since 1.0.0
 * @BABOK Related: BR-CT-001
 */
    public function CONTACT_RESOLVE(&$data, $opts = null) {
        if (!isset($data['person_id']) || !is_numeric($data['person_id'])) {
            $data['error'] = 'CONTACT_RESOLVE requires data[person_id]';
            return null;
        }
        if (!isset($data['dto']) || !$data['dto'] instanceof RelatedRecordDto) {
            $data['error'] = 'CONTACT_RESOLVE requires data[dto] as RelatedRecordDto';
            return null;
        }

        $personId = (int) $data['person_id'];
        $dto = $data['dto'];

        // Resolve through ContactService (which uses ContactResolver internally,
        // providing a consistent service-layer pattern with other modules).
        $service = new ContactService();
        $result = $service->resolveById($personId, $dto);
        $data['result'] = $result;
        return null;
    }

    /**
     * CONTACT_FIELDS — return the field map for the contact type, used by
     * consumers to build Resolved DTOs.
     *
     * @param array $data Modified by reference
     * @param array|null $opts
 * @return null
 * @since 1.0.0
 * @BABOK Related: BR-CT-001
 */
    public function CONTACT_FIELDS(&$data, $opts = null) {
        $data['fields'] = array(
            'displayName' => _('Contact'),
            'firstName'   => _('First Name'),
            'lastName'    => _('Last Name'),
            'contactIndex'=> _('Index'),
        );
        return null;
    }

    /**
     * Canonical FA URL for viewing one contact record.
     *
     * @param int $personId Person id (crm_persons.id)
     * @return string Web-root-relative URL
     * @since 1.0.0
     */
    private function contactUrl($personId) {
        return '/crm/crm_contacts.php?contact_id=' . (int) $personId;
    }
}
