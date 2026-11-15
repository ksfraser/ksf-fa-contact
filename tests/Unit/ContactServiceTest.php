<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Contact\Tests\Unit;

use Ksfraser\FrontAccounting\Contact\Service\ContactService;
use ksfraser\RelatedTo\DTO\ContactResolvedDto;
use ksfraser\RelatedTo\DTO\RelatedRecordDto;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ContactService. The ContactRepository degrades gracefully outside
 * FA (get_crm_person is undefined), so resolveById returns an empty resolved
 * DTO and searches return empty lists.
 *
 * @package Ksfraser\FrontAccounting\Contact\Tests\Unit
 * @since   1.0.0
 */
class ContactServiceTest extends TestCase
{
    /** @var ContactService */
    private $service;

    protected function setUp(): void
    {
        $this->service = new ContactService();
    }

    public function testResolveByIdNotFoundReturnsEmptyResolvedDto(): void
    {
        // Outside FA get_crm_person is undefined -> repository finds nothing
        $resolved = $this->service->resolveById(999);

        $this->assertInstanceOf(ContactResolvedDto::class, $resolved);
        $this->assertInstanceOf(RelatedRecordDto::class, $resolved->dto);
        $this->assertSame('contact', $resolved->dto->lhsType);
        $this->assertSame('0', $resolved->dto->lhsId);
        $this->assertSame('contact', $resolved->dto->rhsType);
        $this->assertSame('0', $resolved->dto->rhsId);
        $this->assertSame('', $resolved->getField('firstName'));
        $this->assertSame('', $resolved->getField('lastName'));
        $this->assertSame('0', $resolved->getField('displayName'));
        $this->assertNull($resolved->getField('contactIndex'));
        $this->assertSame('#', $resolved->getUrl());
    }

    public function testResolveByIdUsesSuppliedDto(): void
    {
        $dto = new RelatedRecordDto('contact', '7', 'contact', '7', null);
        $resolved = $this->service->resolveById(999, $dto);

        $this->assertSame($dto, $resolved->dto);
        $this->assertSame('7', $resolved->dto->lhsId);
    }

    public function testFieldMapReturnsExpectedLabels(): void
    {
        $this->assertSame(array(
            'displayName' => 'Contact',
            'firstName'   => 'First Name',
            'lastName'    => 'Last Name',
            'contactIndex'=> 'Index',
        ), $this->service->fieldMap());
    }

    public function testSearchReturnsEmptyOutsideFA(): void
    {
        $this->assertSame(array(), $this->service->search('anything'));
        $this->assertSame(array(), $this->service->search('', 20));
        $this->assertSame(array(), $this->service->search('a', 0));
    }

    public function testResolveByEntityReturnsEmptyOutsideFA(): void
    {
        $this->assertSame(array(), $this->service->resolveByEntity('customer', 'C001'));
        $this->assertSame(array(), $this->service->resolveByEntity('', 'C001'));
    }
}
