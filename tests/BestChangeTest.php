<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use BestChange\BestChange;

class BestChangeTest extends TestCase
{
    private $cachePath = __DIR__ . '/Fixtures/info.zip';

    public function testInfo()
    {
        // не очищаем fixture
        $bc = new BestChange($this->cachePath, 1e8);
        $this->assertEquals($bc->getVersion(), '2.01');
        $this->assertEquals($bc->getLastUpdate(), new \DateTime('2017-10-02 23:35:30'));
    }

    public function testCreateCache()
    {
        $cachePath = __DIR__ . '/Fixtures/testZip';
        $bc = new BestChange($cachePath, 5);
        $this->assertFileExists($cachePath);
        $this->assertFileIsReadable($cachePath);
        $lastUpdate = $bc->getLastUpdate()->getTimestamp();
        sleep(10);
        $bc = new BestChange($cachePath, 5);
        $this->assertNotEquals($bc->getLastUpdate()->getTimestamp(), $lastUpdate);
        unlink($cachePath);
    }
}