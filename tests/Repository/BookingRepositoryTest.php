<?php

namespace App\Tests\Repository;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use App\Repository\BookingRepository;
use App\Entity\Booking;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BookingRepositoryTest extends TestCase
{
    private string $filePath;
    private BookingRepository $repository;

    protected function setUp(): void
    {
        $projectDir = dirname(__DIR__, 2);
        $this->filePath = $projectDir . '/tests/data/test_booking_unit.csv';
        file_put_contents($this->filePath, '');

        $params = $this->createMock(ParameterBagInterface::class);
        $params->method('get')
            ->with('paths.booking_csv')
            ->willReturn($this->filePath);

        $this->repository = new BookingRepository($params);
        $this->booking = new Booking('78903493259',1,'Test1' );
        $this->booking1 = new Booking('78903493259',2,'Test2');
    }

//    protected function tearDown(): void
//    {
//        unlink($this->testFile);
//    }


    public function testSave(): void
    {
        $id1 = $this->repository->save($this->booking);
        $id2 = $this->repository->save($this->booking1);

        $booking1= $this->repository->findById($id1);
        $booking1->setComment('New Test 2');
        $this->repository->save($booking1);

        $bookings = $this->repository->findAll();
        $this->assertEquals(1, $bookings[0]->getHouseId());

        $this->assertEquals(2, $bookings[1]->getHouseId());
        $this->assertEquals('New Test 2', $bookings[0]->getComment());
    }

    public function testFindAll(): void
    {
        $id1 = $this->repository->save($this->booking);
        $id2 = $this->repository->save($this->booking1);
        $bookings = $this->repository->findAll();
        $this->assertCount(2, $bookings);
    }

    public function testFindById(): void
    {

        $id1 = $this->repository->save($this->booking);
        $id2 = $this->repository->save($this->booking1);

        $booking_id1 = $this->repository->findById($id1);
        $booking_id2 = $this->repository->findById($id2);

        $this->assertNotNull($booking_id1);
        $this->assertNotNull($booking_id2);
        $this->assertEquals($id1, $booking_id1->getId());
        $this->assertEquals($id2, $booking_id2->getId());

    }

    public function testDeleteById(): void
    {

        $id1 = $this->repository->save($this->booking);
        $id2 = $this->repository->save($this->booking1);

        $this->repository->deleteById($id2);
        $bookings = $this->repository->findAll();
        $booking = $this->repository->findById($id2);

        $this->assertNull($booking);
        $this->assertCount(1, $bookings);
    }
}