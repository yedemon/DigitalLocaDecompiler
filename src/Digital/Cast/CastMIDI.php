<?php

declare(strict_types=1);

namespace Digital;

use Exception;

Class CastMIDI {
    public static function readMIDI($fp) {
        $title = DigiLocaReader::readCastName($fp);

        $flag = freadu1($fp);
        if (!$flag) {
            $body = static::read477744($fp);
            return ['title'=>$title, 'body'=>$body];
        }

        throw new Exception('midi flag not 0');
    }

    private static function read477744($fp) {
        $res = false;
        $loop = true;
        $f3C = 0;
        while ($loop) {
            $flag = freadu1($fp);

            switch ($flag) {
                case 0x10:
                    $f3C = freadu4($fp);
                    break;

                case 0x20:
                    $res = fread($fp, $f3C);
                    break;

                case 0xFF:
                    $loop = false;
                    break;

                default:
                    throw new Exception('midi other flag');
            }
        }
        return $res;
    }
}