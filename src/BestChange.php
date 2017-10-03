<?php

namespace BestChange;

class BestChange
{
    private $version = '';
    /**
     * @var \DateTime
     */
    private $lastUpdate;

    const PREFIX_TMPFILE = 'nbc';
    const BESTCHANGE_FILE = 'http://www.bestchange.ru/bm/info.zip';

    const FILE_CURRENCIES = 'bm_cy.dat';
    const FILE_EXCHANGERS = 'bm_exch.dat';
    const FILE_RATES = 'bm_rates.dat';

    private $tmpName;
    /**
     * @var \ZipArchive
     */
    private $zip;
    /**
     * @var Currencies
     */
    private $currencies;
    /**
     * @var Exchangers
     */
    private $exchangers;
    /**
     * @var Rates
     */
    private $rates;

    private $useCache;
    private $cacheTime;

    public function __construct($cachePath = '', $cacheTime = 3600)
    {
        $this->zip = new \ZipArchive();
        if ($cachePath) {
            $this->cacheTime = $cacheTime;
            $this->useCache = true;
            $this->tmpName = $cachePath;
        } else {
            $this->useCache = false;
            $this->tmpName = tempnam(sys_get_temp_dir(), self::PREFIX_TMPFILE);
        }
        register_shutdown_function([$this, 'close']); // уборка мусора
        $this->load();
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getCurrenciesInstance()
    {
        return $this->currencies;
    }

    public function getExchangersInstance()
    {
        return $this->exchangers;
    }

    public function getRatesInstance()
    {
        return $this->rates;
    }

    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    public function getCurrencies()
    {
        return $this->currencies->get();
    }

    public function getExchangers()
    {
        return $this->exchangers->get();
    }

    public function getRates()
    {
        return $this->rates->get();
    }

    public function getRatesFilter($currencyGiveID = 0, $currencyReceiveID = 0)
    {
        return $this->rates->filter($currencyGiveID, $currencyReceiveID);
    }

    /**
     * Завершаем работу. Убираем мусор
     */
    public function close()
    {
        if (!$this->useCache) {
            unlink($this->tmpName);
        }
    }

    private function load()
    {
        $this->getFile()->unzip()->init();
        $this->currencies = new Currencies($this->zip->getFromName(self::FILE_CURRENCIES));
        $this->exchangers = new Exchangers($this->zip->getFromName(self::FILE_EXCHANGERS));
        $this->rates = new Rates($this->zip->getFromName(self::FILE_RATES));
        return $this;
    }

    private function getFile()
    {
        if ($this->useCacheFile()) {
            return $this;
        }
        $file = file_get_contents(self::BESTCHANGE_FILE);
        if ($file) {
            $fp = fopen($this->tmpName, 'wb+');
            fputs($fp, $file);
            fclose($fp);
            return $this;
        }
        throw new \Exception('Файл на bestchange.ru не найден');
    }

    private function useCacheFile()
    {
        clearstatcache(true, $this->tmpName);
        return (
            $this->useCache
            && file_exists($this->tmpName)
            && filemtime($this->tmpName) > (time() - $this->cacheTime)
        );
    }

    private function unzip()
    {
        if (!$this->zip->open($this->tmpName)) {
            throw new \Exception('Получен битый файл с bestchange.ru');
        }
        return $this;
    }

    private function init()
    {
        $file = explode("\n", $this->zip->getFromName('bm_info.dat'));
        foreach ($file as $row) {
            $row = iconv('CP1251', 'UTF-8', $row);
            $data = array_map('trim', explode('=', $row));
            if (count($data) < 2) {
                continue;
            }
            switch ($data[0]) {
                case'last_update':
                    $this->lastUpdate = $this->canonicalDate($data[1]);
                    break;
                case'current_version':
                    $this->version = $data[1];
                    break;
            }
        }
        return $this;
    }

    private function canonicalDate($date)
    {
        $arMonth = [
            'января' => 'January',
            'февраля' => 'February',
            'марта' => 'March',
            'апреля' => 'April',
            'мая' => 'May',
            'июня' => 'June',
            'июля' => 'July',
            'августа' => 'August',
            'сентября' => 'September',
            'октября' => 'October',
            'ноября' => 'November',
            'декабря' => 'December',
        ];
        foreach ($arMonth as $ru => $en) {
            $date = preg_replace('/' . $ru . '/sui', $en, $date);
        }
        return new \DateTime($date);
    }
}