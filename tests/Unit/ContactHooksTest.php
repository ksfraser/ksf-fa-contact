<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Contact\Tests\Unit;

use ksfraser\FrontAccounting\Common\ContactResolver\Contact;
use ksfraser\RelatedTo\DTO\ContactResolvedDto;
use ksfraser\RelatedTo\DTO\RelatedRecordDto;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../hooks.php';

/**
 * Tests for \hooks_ksf_FA_Contact responders (RELATED_TO_FIELDS,
 * CONTACT_RESOLVE, CONTACT_FIELDS). FA's hook_invoke_all() is stubbed by the
 * bootstrap; tests install a handler via $GLOBALS['KSF_HOOK_HANDLER'] to
 * simulate a listener answering the contact-resolve hook.
 *
 * @package Ksfraser\FrontAccounting\Contact\Tests\Unit
 * @since   1.0.0
 */
class ContactHooksTest extends TestCase
{
    /** @var \hooks_ksf_FA_Contact */
    private $hooks;

    protected function setUp(): void
    {
        $this->hooks = new \hooks_ksf_FA_Contact();
        $GLOBALS['KSF_HOOK_HANDLER'] = null;
    }

    private function data(array $overrides = array()): array
    {
        return array_merge(array(
            'module' => 'ksf_FA_Opportunity',
            'type'   => 'contact',
            'fields' => array(),
        ), $overrides);
    }

    public function testRespondersDeclared(): void
    {
        $this->assertContains('RELATED_TO_FIELDS', \hooks_ksf_FA_Contact::RESPONDERS);
        $this->assertContains('CONTACT_RESOLVE', \hooks_ksf_FA_Contact::RESPONDERS);
        $this->assertContains('CONTACT_FIELDS', \hooks_ksf_FA_Contact::RESPONDERS);
    }

    public function testRelatedToFieldsSetsContactFields(): void
    {
        $data = $this->data(array('type' => 'contact'));
        $this->hooks->RELATED_TO_FIELDS($data);

        $this->assertSame(array(
            'displayName' => 'Contact',
            'firstName'   => 'First Name',
            'lastName'    => 'Last Name',
            'contactIndex'=> 'Index',
        ), $data['fields']);
    }

    public function testRelatedToFieldsIgnoresNonContactType(): void
    {
        $data = $this->data(array('type' => 'lead'));
        $this->hooks->RELATED_TO_FIELDS($data);

        $this->assertSame(array(), $data['fields']);
    }

    public function testRelatedToFieldsRequiresModuleAndType(): void
    {
        $data = array('fields' => array());
        $this->hooks->RELATED_TO_FIELDS($data);
        $this->assertArrayHasKey('error', $data);

        $data = array('module' => 'x', 'fields' => array());
        $this->hooks->RELATED_TO_FIELDS($data);
        $this->assertArrayHasKey('error', $data);
    }

    public function testContactFieldsReturnsFieldMap(): void
    {
        $data = array();
        $this->hooks->CONTACT_FIELDS($data);

        $this->assertSame(array(
            'displayName' => 'Contact',
            'firstName'   => 'First Name',
            'lastName'    => 'Last Name',
            'contactIndex'=> 'Index',
        ), $data['fields']);
    }

    public function testContactResolveNotFoundSetsEmptyResult(): void
    {
        // No hook listener answered -> ContactResolver falls back to native FA
        // (get_crm_person undefined) -> empty resolved DTO.
        $dto = new RelatedRecordDto('contact', '3', 'contact', '3', null);
        $data = array('person_id' => 3, 'dto' => $dto);

        $this->hooks->CONTACT_RESOLVE($data);

        $this->assertArrayHasKey('result', $data);
        $result = $data['result'];
        $this->assertInstanceOf(ContactResolvedDto::class, $result);
        $this->assertInstanceOf(RelatedRecordDto::class, $result->dto);
        $this->assertSame('contact', $result->dto->lhsType);
        $this->assertSame('3', $result->dto->lhsId);
        $this->assertSame('', $result->getField('firstName'));
        $this->assertSame('', $result->getField('lastName'));
        $this->assertSame('3', $result->getField('displayName'));
        $this->assertNull($result->getField('contactIndex'));
        $this->assertSame('#', $result->getUrl());
    }

    public function testContactResolveFoundSetsPopulatedResult(): void
    {
        $GLOBALS['KSF_HOOK_HANDLER'] = function ($hook, &$payload) {
            $payload['handled'] = true;
            $payload['result']  = array(new Contact(
                1, 'Alice', 'Smith', 'alice@example.com', '', '', '', 'customer', 'general', 'C100', 5, false
            ));
        };

        $dto = new RelatedRecordDto('contact', '1', 'contact', '1', null);
        $data = array('person_id' => 1, 'dto' => $dto);

        $this->hooks->CONTACT_RESOLVE($data);

        $this->assertArrayHasKey('result', $data);
        $result = $data['result'];
        $this->assertInstanceOf(ContactResolvedDto::class, $result);
        $this->assertSame('1', $result->dto->lhsId);
        $this->assertSame('Alice', $result->getField('firstName'));
        $this->assertSame('Smith', $result->getField('lastName'));
        $this->assertSame(5, $result->getField('contactIndex'));
        $this->assertSame('Alice Smith (5)', $result->getField('displayName'));
        $this->assertSame('/crm/crm_contacts.php?contact_id=1', $result->getUrl());
    }

    public function testContactResolveRequiresPersonIdAndDto(): void
    {
        $data = array('dto' => new RelatedRecordDto('contact', '1', 'contact', '1', null));
        $this->hooks->CONTACT_RESOLVE($data);
        $this->assertArrayHasKey('error', $data);

        $data = array('person_id' => 1);
        $this->hooks->CONTACT_RESOLVE($data);
        $this->assertArrayHasKey('error', $data);
    }
}
