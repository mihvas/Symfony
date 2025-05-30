<?php

namespace App\Tests\Repository;


use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use App\Repository\HouseRepository;
use App\Entity\House;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class HouseRepositoryTest extends TestCase
{
    private string $filePath;
    private HouseRepository $repository;

    protected function setUp(): void
    {
        $projectDir = dirname(__DIR__, 2);
        $this->filePath = $projectDir . '/tests/data/test_house_unit.csv';
        file_put_contents($this->filePath, '');

        $params = $this->createMock(ParameterBagInterface::class);
        $params->method('get')
            ->with('paths.house_csv')
            ->willReturn($this->filePath);

        $this->repository = new HouseRepository($params);
        $this->house = new House( 'test1', 1, 'Test Address', 123455);
        $this->house1 = new House('test2', 1, 'Test Address1', 123456,false);
    }

    public function testSave(): void
    {
        $id1 = $this->repository->save($this->house);
        $id2 = $this->repository->save($this->house1);

        $house1= $this->repository->findById($id1);
        $house1->setBeds(10);
        $this->repository->save($house1);

        $houses = $this->repository->findAll();
        $this->assertEquals('test1', $houses[0]->getType());

        $this->assertEquals('test2', $houses[1]->getType());
        $this->assertEquals('10', $houses[0]->getBeds());
    }

    public function testFindAll(): void
    {
        $id1 = $this->repository->save($this->house);
        $id2 = $this->repository->save($this->house1);
        $houses = $this->repository->findAll();
        $this->assertCount(2, $houses);
    }

    public function testFindAllFree(): void
    {
        $id1 = $this->repository->save($this->house);
        $id2 = $this->repository->save($this->house1);
        $houses = $this->repository->findAllFree();
        $this->assertCount(1, $houses);
    }

    public function testFindById(): void
    {

        $id1 = $this->repository->save($this->house);
        $id2 = $this->repository->save($this->house1);

        $house_id1 = $this->repository->findById($id1);
        $house_id2 = $this->repository->findById($id2);

        $this->assertNotNull($house_id1);
        $this->assertNotNull($house_id2);
        $this->assertEquals($id1, $house_id1->getId());
        $this->assertEquals($id2, $house_id2->getId());

    }

    public function testDeleteById(): void
    {

        $id1 = $this->repository->save($this->house);
        $id2 = $this->repository->save($this->house1);

        $this->repository->deleteById($id2);
        $houses = $this->repository->findAll();
        $house = $this->repository->findById($id2);

        $this->assertNull($house);
        $this->assertCount(1, $houses);
    }
}