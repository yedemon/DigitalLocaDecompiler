<?php

declare(strict_types=1);

namespace Digital;

use Exception;

Class CastCamera {
    public static function readCamera($fp) {
        $title = DigiLocaReader::readCastName($fp);

        $flag = freadu1($fp);
        if (!$flag) {
            $body = static::read476290($fp);
            return ['title'=>$title, 'body'=>$body];
        }

        throw new Exception('camera flag not 0');
    }

    private static function read476290($fp) {
        $loop = true;
        while ($loop) {
            $flag = freadu1($fp);

            switch ($flag) {
                case 0x10:
                    fread($fp, 4); break;
                case 0x11:
                    fread($fp, 4); break;
                case 0x12:
                    fread($fp, 4); break;
                case 0x13:
                    fread($fp, 4); break;
                case 0x20:
                    fread($fp, 4); break;
                case 0x21:
                    fread($fp, 4); break;

                case 0x22:
                    fread($fp, 4); break;

                case 0x26:
                    fread($fp, 4); break;
                case 0x23:
                    fread($fp, 4); break;
                case 0x24:
                    fread($fp, 4); break;
                case 0x25:
                    fread($fp, 4); break;

                case 0x27:
                    fread($fp, 4); break;

                case 0xFF:
                    $loop = false;
                    break;

                default:
                    throw new Exception('camera other flag');
            }
        }
        return [];
    }
}