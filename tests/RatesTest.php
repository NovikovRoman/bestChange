<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use BestChange\Rates;

class RatesTest extends TestCase
{
    private $filepath = __DIR__ . '/Fixtures/info/bm_rates.dat';
    private $rates;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        $this->rates = new Rates(file_get_contents($this->filepath));
        parent::__construct($name, $data, $dataName);
    }

    /**
     * Загружаем данные. Всего должно быть 135 видов валют, которые отдают
     */
    public function testLoad()
    {
        $dataRates = $this->rates->get();
        $this->assertEquals(count($dataRates), 135);
    }

    /**
     * Проверим фильтр.
     * Если меняем рубли (id: 91) на доллары США (id: 1), то получим список от самых выгодных курсов
     * к невыгодным. В этом случае отработала обратная сортировка по полю rate
     * Если меняем доллары США (id: 1) на рубли (id: 91), то получим список от самых выгодных курсов
     * к невыгодным. В этом случае отработала прямая сортировка по полю rate
     */
    public function testFilter()
    {
        $rates = $this->rates->filter(91, 1);
        $this->assertEquals(count($rates), 5);
        $lastRateGive = 0;
        foreach ($rates as $rate) {
            $this->assertGreaterThan($lastRateGive, $rate['rate_give']);
            $lastRateGive = $rate['rate_give'];
            $this->assertEquals($rate['rate_receiver'], 1);
        }

        $rates = $this->rates->filter(1, 91);
        $this->assertEquals(count($rates), 4);
        $lastRateReceiver = 0;
        foreach ($rates as $rate) {
            $this->assertGreaterThan($lastRateReceiver, $rate['rate_receiver']);
            $lastRateReceiver = $rate['rate_receiver'];
            $this->assertEquals($rate['rate_give'], 1);
        }
    }
}