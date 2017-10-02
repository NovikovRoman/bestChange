<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use BestChange\Exchangers;

class ExchangersTest extends TestCase
{
    private $filepath = __DIR__ . '/Fixtures/info/bm_exch.dat';
    private $exchangers;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        $this->exchangers = new Exchangers(file_get_contents($this->filepath));
        parent::__construct($name, $data, $dataName);
    }

    /**
     * Загружаем данные. Всего должно быть 299 обменников
     */
    public function testLoad()
    {
        $dataCurrency = $this->exchangers->get();
        $this->assertEquals(count($dataCurrency), 299);
    }

    /**
     * метод getByID
     */
    public function testGetById()
    {
        $exchanger = $this->exchangers->getByID(199);
        $this->assertEquals($exchanger, 'WMChange24');

        $exchanger = $this->exchangers->getByID(268, true);
        $this->assertEquals($exchanger['id'], 268);
        $this->assertEquals($exchanger['name'], 'RapidObmen');
    }
}