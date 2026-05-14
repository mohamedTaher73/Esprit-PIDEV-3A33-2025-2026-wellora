<?php

namespace Tests\Unit\Service;

use App\Service\MapService;
use PHPUnit\Framework\TestCase;

class MapServiceTest extends TestCase
{
    private MapService $mapService;

    protected function setUp(): void
    {
        $this->mapService = new MapService();
    }

    public function testGeocodeLocationWithEmptyString(): void
    {
        $result = $this->mapService->geocodeLocation('');
        
        $this->assertNull($result);
    }

    public function testGeocodeLocationWithNull(): void
    {
        $result = $this->mapService->geocodeLocation(null);
        
        $this->assertNull($result);
    }

    public function testReverseGeocodeCoordinatesWithNullLatitude(): void
    {
        $result = $this->mapService->reverseGeocodeCoordinates(null, 10.0);
        
        $this->assertNull($result);
    }

    public function testReverseGeocodeCoordinatesWithNullLongitude(): void
    {
        $result = $this->mapService->reverseGeocodeCoordinates(36.8, null);
        
        $this->assertNull($result);
    }
}
