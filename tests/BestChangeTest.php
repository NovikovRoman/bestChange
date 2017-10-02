<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use BestChange\BestChange;

class BestChangeTest extends TestCase
{
    private $cachePath = __DIR__ . '/Fixtures/info.zip';

    public function testInfo()
    {
        $bc = new BestChange($this->cachePath);
        $this->assertEquals($bc->getVersion(), '2.01');
        $this->assertEquals($bc->getLastUpdate(), new \DateTime('2017-10-02 22:01:43'));
    }

    public function testCreateCache()
    {
        $cachePath = __DIR__ . '/testZip';
        $bc = new BestChange($cachePath);
        $this->assertFileExists($cachePath);
        $this->assertFileIsReadable($cachePath);
        unlink($cachePath);
    }
}