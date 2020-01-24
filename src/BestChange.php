<?php

namespace BestChange;

use BestChange\Exception\BestChangeException;
use DateTime;
use ZipArchive;

class BestChange
{
    private $version = '';
    /** @var DateTime */
    private $lastUpdate;

    const PREFIX_TMPFILE = 'nbc';
    const BESTCHANGE_FILE = 'http://api.bestchange.ru/info.zip';

    const FILE_CURRENCIES = 'bm_cy.dat';
    const FILE_EXCHANGERS = 'bm_exch.dat';
    const FILE_RATES = 'bm_rates.dat';

    const TIMEOUT = 5;

    /** @var ZipArchive */
    private $zip;
    /** @var Currencies */
    private $currencies;
    /** @var Exchangers */
    private $exchangers;
    /** @var Rates */
    private $rates;

    private $cachePath;
    private $useCache;
    private $cacheTime;

    /**
     * BestChange constructor.
     * @param string $cachePath
     * @param int $cacheTime
     * @throws BestChangeException
     */
    public function __construct($cachePath = '', $cacheTime = 3600)
    {
        $this->zip = new ZipArchive();
        if ($cachePath) {
            $this->cacheTime = $cacheTime;
            $this->useCache = true;
            $this->cachePath = $cachePath;
        } else {
            $this->useCache = false;
            $this->cachePath = tempnam(sys_get_temp_dir(), self::PREFIX_TMPFILE);
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

    /**
     * @param int $currencyGiveID
     * @param int $currencyReceiveID
     * @return array
     * @throws Exception\NoExchangeException
     */
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
            if (!is_writable($this->cachePath)) {
                chmod($this->cachePath, 0644);
            }
            unlink($this->cachePath);
        }
        return $this;
    }

    /**
     * @return $this
     * @throws BestChangeException
     */
    private function load()
    {
        $this->getFile()->unzip()->init();
        $this->currencies = new Currencies($this->zip->getFromName(self::FILE_CURRENCIES));
        $this->exchangers = new Exchangers($this->zip->getFromName(self::FILE_EXCHANGERS));
        $this->rates = new Rates($this->zip->getFromName(self::FILE_RATES));
        return $this;
    }

    /**
     * @return $this
     * @throws BestChangeException
     */
    private function getFile()
    {
        if ($this->useCacheFile()) {
            return $this;
        }
        $file = $this->loadFile(self::BESTCHANGE_FILE);
        if ($file) {
            $fp = fopen($this->cachePath, 'wb+');
            fputs($fp, $file);
            fclose($fp);
            return $this;
        }
        throw new BestChangeException('Файл на bestchange.ru не найден или недоступен');
    }

    private function useCacheFile()
    {
        clearstatcache(true, $this->cachePath);
        return (
            $this->useCache
            && file_exists($this->cachePath)
            && filemtime($this->cachePath) > (time() - $this->cacheTime)
        );
    }

    /**
     * @return $this
     * @throws BestChangeException
     */
    private function unzip()
    {
        if (!$this->zip->open($this->cachePath)) {
            throw new BestChangeException('Получен битый файл с bestchange.ru');
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
        return new DateTime($date);
    }

    private function loadFile($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}