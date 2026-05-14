<?php

namespace Tests\Unit\Service;

use App\Service\TunisianPriceService;
use PHPUnit\Framework\TestCase;

class TunisianPriceServiceTest extends TestCase
{
    private TunisianPriceService $priceService;

    protected function setUp(): void
    {
        $this->priceService = new TunisianPriceService();
    }

    public function testGetPriceWithValidItem(): void
    {
        $result = $this->priceService->getPrice('Poulet');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('price', $result);
        $this->assertArrayHasKey('unit', $result);
        $this->assertArrayHasKey('category', $result);
        $this->assertEquals(9.000, $result['price']);
    }

    public function testGetPriceWithInvalidItem(): void
    {
        $result = $this->priceService->getPrice('InvalidItem');
        
        $this->assertNull($result);
    }

    public function testCalculateTotalWithValidItem(): void
    {
        $result = $this->priceService->calculateTotal('Poulet', 2.0);
        
        $this->assertEquals(18.0, $result);
    }

    public function testCalculateTotalWithInvalidItem(): void
    {
        $result = $this->priceService->calculateTotal('InvalidItem', 2.0);
        
        $this->assertEquals(0, $result);
    }
}
