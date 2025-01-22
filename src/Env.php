<?php

namespace src;

class Env
{
    public static function load(): bool
    {
        $env = file_get_contents(realpath(__DIR__ . '/../.env'));

        if (!empty($env)) {
            $lines = explode("\n",$env);

            foreach($lines as $line){
                preg_match("/([^#]+)\=(.*)/",$line,$matches);

                if (isset($matches[2])) {
                    putenv(trim($line));
                }
            }
        }

        return true;
    }
}