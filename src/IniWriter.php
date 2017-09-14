<?php

namespace OmekaCli;

class IniWriter
{
    protected $filename;

    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    public function writeArray(array $array)
    {
        file_put_contents($this->filename, $this->array2Ini($array));
    }

    protected function array2Ini($array)
    {
        $out = '';
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $out .= "[$key]" . PHP_EOL;
                $out .= $this->array2Ini($value);
            } else {
                $out .= "$key = \"$value\"" . PHP_EOL;
            }
        }

        return $out;
    }
}
