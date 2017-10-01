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
            $this->data[$data[0]] = $data[2];
        }
        ksort($this->data);
    }

    public function get()
    {
        return $this->data;
    }

    public function getByID($id, $asArray = false)
    {
        if ($asArray) {
            return empty($this->data[$id]) ? [] : [
                'id' => $id,
                'name' => $this->data[$id],
            ];
        }
        return $this->data[$id];
    }
}