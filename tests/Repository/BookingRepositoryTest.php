<?php

namespace App\Tests\Repository;

use App\Entity\Booking;
use App\Entity\House;
use App\Repository\BookingRepository;
use App\Repository\HouseRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class BookingRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private BookingRepository $bookingRepository;
    private HouseRepository $houseRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->bookingRepository = $container->get(BookingRepository::class);
        $this->houseRepository = $container->get(HouseRepository::class);

        $conn = $this->em->getConnection();
        $platform = $conn->getDatabasePlatform();
        $conn->executeStatement($platform->getTruncateTableSQL('Booking', true));
        $conn->executeStatement($platform->getTruncateTableSQL('House', true));

        $this->house1 = $this->createHouse('Test Booking 1');
        $this->house2 = $this->createHouse('Test Booking 2');
        $this->userId = 111;
        $this->booking1 = new Booking($this->userId, 1112,'Comment 1', '71234567890');
        $this->booking2 = new Booking(222, 2222,'Comment 2', '77777777777');
    }

    private function createHouse(string $type): House
    {
        $house = new House($type, 2, 'Test Address',100);
        $this->houseRepository->save($house);
        return $house;
    }

    public function testSaveAndFind(): void
    {


        $this->booking1->setHouse($this->house1);

        $this->bookingRepository->save($this->booking1);

        $fetched = $this->bookingRepository->findById($this->booking1->getId());

        $this->assertNotNull($fetched);
        $this->assertEquals('71234567890', $fetched->getPhone());
        $this->assertEquals('Comment 1', $fetched->getComment());
        $this->assertEquals('1112', $fetched->getTelegramChatId());
        $this->assertEquals('111', $fetched->getTelegramUserId());
        $this->assertEquals($this->house1->getId(), $fetched->getHouse()->getId());
    }

    public function testFindAll(): void
    {

        $this->booking1->setHouse($this->house1);
        $this->booking2->setHouse($this->house2);

        $this->bookingRepository->save($this->booking1);
        $this->bookingRepository->save($this->booking2);

        $all = $this->bookingRepository->findAll();
        $this->assertCount(2, $all);
    }

    public function testFindAllUser(): void
    {

        $this->booking1->setHouse($this->house1);
        $this->booking2->setHouse($this->house2);

        $this->bookingRepository->save($this->booking1);
        $this->bookingRepository->save($this->booking2);

        $all = $this->bookingRepository->findAllUser($this->userId);
        $this->assertCount(1, $all);
        $this->assertSame('Comment 1', $all[0]->getComment());
    }

    public function testUpdate(): void
    {
        $this->booking1->setHouse($this->house1);

        $this->bookingRepository->save($this->booking1);

        $this->booking1->setPhone('78888888888');
        $this->booking1->setComment('New comment');
        $this->bookingRepository->save($this->booking1);

        $reloaded = $this->bookingRepository->findById($this->booking1->getId());
        $this->assertSame('78888888888', $reloaded->getPhone());
        $this->assertSame('New comment', $reloaded->getComment());
    }

    public function testDeleteById(): void
    {
        $this->booking2->setHouse($this->house2);

        $this->bookingRepository->save($this->booking2);
        $id = $this->booking2->getId();

        $this->assertTrue($this->bookingRepository->deleteById($id));
        $this->assertNull($this->bookingRepository->findById($id));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}
