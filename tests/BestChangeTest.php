<?php

namespace Tests;

use DateTime;
use Exception;
use PHPUnit\Framework\TestCase;
use BestChange\BestChange;

class BestChangeTest extends TestCase
{
    private $cachePath = __DIR__ . '/Fixtures/info.zip';
    private $cacheECurrencyCodes = __DIR__ . '/../src/e-currency-codes';

    /**
     * @throws Exception
     */
    public function testInfo()
    {
        // не очищаем fixture
        $bc = new BestChange($this->cachePath, 1e8);
        $this->assertEquals($bc->getVersion(), '2.01');
        // в bm_info.dat год не указывается. Предполагается текущий. Для тестов лежит файл 2017 года
        $currentYear = date('Y');
        $this->assertEquals($bc->getLastUpdate(), new DateTime($currentYear . '-10-02 23:35:30'));
    }

    /**
     * @throws Exception
     */
    public function testCreateCache()
    {
        if (file_exists($this->cacheECurrencyCodes)) {
            unlink($this->cacheECurrencyCodes);
        }
        $cachePath = __DIR__ . '/Fixtures/testZip';
        // кэш на 5 сек
        $bc = new BestChange($cachePath, 5);
        $this->assertFileExists($cachePath);
        $this->assertFileIsReadable($cachePath);
        $lastUpdate = $bc->getLastUpdate()->getTimestamp();
        // ждем устаревания кэша
        sleep(10);
        $bc = new BestChange($cachePath, 5);
        $this->assertNotEquals($bc->getLastUpdate()->getTimestamp(), $lastUpdate);
        unlink($cachePath);
    }
}