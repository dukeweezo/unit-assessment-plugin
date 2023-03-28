<?php

class Utils
{
    public static function write_log($value)
    {
        $file = ABSPATH . "debug.json";
        file_put_contents($file, json_encode($value) . PHP_EOL, FILE_APPEND);
    }
}