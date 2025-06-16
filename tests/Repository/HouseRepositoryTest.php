<?php

namespace App\Tests\Repository;

use App\Entity\House;
use App\Repository\HouseRepository;
use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class HouseRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private HouseRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->repository = $container->get(HouseRepository::class);

        $conn = $this->em->getConnection();
        $platform = $conn->getDatabasePlatform();
        $conn->executeStatement($platform->getTruncateTableSQL('house', true));

        $this->house1 = new House(type: 'test1', beds: 2, address: 'Addr1', price: 100, free: true);
        $this->house2 = new House(type: 'test2', beds: 3, address: 'Addr2', price: 200, free: false);
    }

    public function testSaveAndFindById(): void
    {
        $this->repository->save($this->house1 );
        $this->assertNotNull($this->house1->getId());

        $found = $this->repository->findById($this->house1->getId());
        $this->assertInstanceOf(House::class, $found);
        $this->assertSame('test1', $found->getType());
    }

    public function testFindAllAndFindAllFree(): void
    {
        $this->repository->save($this->house1);
        $this->repository->save($this->house2);

        $all = $this->repository->findAll();
        $this->assertCount(2, $all);

        $free = $this->repository->findAllFree();
        $this->assertCount(1, $free);
        $this->assertTrue($free[0]->getFree());
    }

    public function testUpdate(): void
    {
        $this->repository->save($this->house1);

        $this->house1->setBeds(10);
        $this->house1->setAddress('New');
        $this->repository->save($this->house1);

        $reloaded = $this->repository->findById($this->house1->getId());
        $this->assertSame(10, $reloaded->getBeds());
        $this->assertSame('New', $reloaded->getAddress());
    }

    public function testDeleteById(): void
    {
        $this->repository->save($this->house2);

        $id = $this->house2->getId();
        $deleted = $this->repository->deleteById($id);
        $this->assertTrue($deleted);

        $this->assertNull($this->repository->findById($id));
        $this->assertCount(0, $this->repository->findAll());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}
