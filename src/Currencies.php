<?php

namespace BestChange;

class Currencies
{
    private $data = [];

    public function __construct($data)
    {
        $data = explode("\n", $data);
        foreach ($data as $row) {
            $row = iconv('CP1251', 'UTF-8', $row);
            $data = explode(';', $row);
            $this->data[$data[0]] = [
                'id' => $data[0],
                'name' => $data[2],
            ];
        }
        uasort($this->data, function ($a, $b) {
            return strcmp(
                mb_strtolower($a['name'], 'UTF-8'),
                mb_strtolower($b['name'], 'UTF-8')
            );
        });
        $ecc = new ECurrencyCodes();
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