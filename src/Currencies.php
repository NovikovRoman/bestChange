<?php

namespace BestChange;

use BestChange\Exception\BestChangeException;

class Currencies
{
    private $data = [];

    /**
     * Currencies constructor.
     * @param $data
     * @throws BestChangeException
     */
    public function __construct($data)
    {
        $data = explode("\n", $data);
        foreach ($data as $row) {
            $row = iconv('CP1251', 'UTF-8', $row);
            $data = explode(';', $row);
            $this->data[$data[0]] = [
                'id' => (int)$data[0],
                'name' => $data[2],
            ];
        }
        uasort($this->data, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });
        $ecc = new ECurrencyCodes($this);
        foreach ($this->data as $id => &$item) {
            $res = $ecc->getByID($id);
            $item['code'] = $res['code'];
        }
    }

    public function get()
    {
        return $this->data;
    }

    public function getByID($id)
    {
        return empty($this->data[$id]) ? false : $this->data[$id];
    }
}