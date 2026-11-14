<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Contact\Tests\Unit;

use Ksfraser\FrontAccounting\Contact\DTO\ContactDTO;
use Ksfraser\FrontAccounting\Contact\Repository\ContactRepository;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ContactDTO display name resolution.
 *
 * Contact display defaults: First + Last name, optionally suffixed with
 * the contact-relation index in parentheses.
 *
 * @package Ksfraser\FrontAccounting\Contact\Tests\Unit
 * @since   1.0.0
 */
class ContactDtoTest extends TestCase
{
    public function testDisplayNameFirstAndLast(): void
    {
        $dto = new ContactDTO(7, 'REF-001', 'Alice', 'Smith');
        $this->assertSame('Alice Smith', $dto->getDisplayName());
    }

    public function testDisplayNameFirstOnly(): void
    {
        $dto = new ContactDTO(7, 'REF-001', 'Alice', '');
        $this->assertSame('Alice', $dto->getDisplayName());
    }

    public function testDisplayNameLastOnly(): void
    {
        $dto = new ContactDTO(7, 'REF-001', '', 'Smith');
        $this->assertSame('Smith', $dto->getDisplayName());
    }

    public function testDisplayNameWithIndex(): void
    {
        $dto = new ContactDTO(7, 'REF-001', 'Alice', 'Smith', '', '', '', '', '', '', '', false, 42);
        $this->assertSame('Alice Smith (42)', $dto->getDisplayName());
    }

    public function testDisplayNameFallsBackToEmail(): void
    {
        $dto = new ContactDTO(7, 'REF-001', '', '', '', '', '', '', 'alice@example.com');
        $this->assertSame('alice@example.com', $dto->getDisplayName());
    }

    public function testToArrayShape(): void
    {
        $dto = new ContactDTO(7, 'REF-001', 'Alice', 'Smith', '', '', '', '', 'alice@example.com', '', '', false, 42);
        $this->assertSame(array(
            'id'         => 7,
            'ref'        => 'REF-001',
            'name'       => 'Alice',
            'name2'      => 'Smith',
            'address'    => '',
            'phone'      => '',
            'phone2'     => '',
            'fax'        => '',
            'email'      => 'alice@example.com',
            'lang'       => '',
            'notes'      => '',
            'inactive'   => false,
            'contact_id' => 42,
        ), $dto->toArray());
    }

    public function testFromArrayReconstructs(): void
    {
        $dto = ContactDTO::fromArray(array(
            'id'         => 9,
            'ref'        => 'REF-002',
            'name'       => 'Bob',
            'name2'      => 'Jones',
            'email'      => 'bob@example.com',
            'inactive'   => true,
            'contact_id' => 11,
        ));
        $this->assertSame(9, $dto->id);
        $this->assertSame('REF-002', $dto->ref);
        $this->assertSame('Bob', $dto->name);
        $this->assertSame('Jones', $dto->name2);
        $this->assertSame('bob@example.com', $dto->email);
        $this->assertTrue($dto->inactive);
        $this->assertSame(11, $dto->contactId);
    }
}

/**
 * Tests for ContactRepository (fake db_* functions, no real database).
 *
 * @package Ksfraser\FrontAccounting\Contact\Tests\Unit
 * @since   1.0.0
 */
class ContactRepositoryTest extends TestCase
{
    /** @var ContactRepository */
    private $repo;

    protected function setUp(): void
    {
        $this->repo = new ContactRepository();
    }

    public function testFindByIdZeroReturnsNull(): void
    {
        $this->assertNull($this->repo->findById(0));
        $this->assertNull($this->repo->findById(-1));
    }

    public function testFindByIdNotFoundReturnsNullOutsideFA(): void
    {
        // Outside FA, get_crm_person is not defined → null
        $this->assertNull($this->repo->findById(1));
    }

    public function testFindByEntityEmptyOutsideFA(): void
    {
        $this->assertSame(array(), $this->repo->findByEntity('customer', 'C001'));
    }

    public function testFindActiveEmptyOutsideFA(): void
    {
        $this->assertSame(array(), $this->repo->findActive());
    }

    public function testHydrateProducesContactDTO(): void
    {
        $reflection = new \ReflectionClass(ContactRepository::class);
        $method = $reflection->getMethod('hydrate');
        $method->setAccessible(true);

        $dto = $method->invoke($this->repo, array(
            'id'        => 3,
            'ref'       => 'REF-3',
            'name'      => 'Carol',
            'name2'     => 'Davis',
            'email'     => 'carol@example.com',
            'inactive'  => 0,
        ), 7);

        $this->assertInstanceOf(ContactDTO::class, $dto);
        $this->assertSame('Carol Davis', $dto->getDisplayName());
        $this->assertSame(7, $dto->contactId);
    }
}
