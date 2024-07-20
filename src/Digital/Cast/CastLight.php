<?php

declare(strict_types=1);

namespace Digital;

use Exception;

Class CastLight {
    public static function readLight($fp) {
        $title = DigiLocaReader::readCastName($fp);

        $flag = freadu1($fp);
        if (!$flag) {
            $body = static::read4766A8($fp);
            return ['title'=>$title, 'body'=>$body];
        }

        throw new Exception('light flag not 0');
    }

    private static function read4766A8($fp) {
        $loop = true;
        while ($loop) {
            $flag = freadu1($fp);

            switch ($flag) {
                case 0x10:
                    fread($fp, 1); break;
                case 0x11:
                    fread($fp, 3); break;
                case 0x15:
                    fread($fp, 4); break;
                case 0x16:
                    fread($fp, 4); break;
                case 0x17:
                    fread($fp, 4); break;
                case 0x18:
                    fread($fp, 4); break;

                case 0x19:
                    fread($fp, 4); break;
                case 0x1A:
                    fread($fp, 4); break;
                case 0x1B:
                    fread($fp, 4); break;

                case 0xFF:
                    $loop = false;
                    break;

                default:
                    throw new Exception('light other flag');
            }
        }
        return [];
    }
}