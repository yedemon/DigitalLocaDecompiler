<?php

declare(strict_types=1);

namespace Digital;

use Exception;

Class CastTextImage {
    public static function readTextImage($fp) {
        $title = DigiLocaReader::readCastName($fp);
        $flag = freadu1($fp);
        if ($flag == 0x00) {
            $body = static::readTextImage477190($fp);
        }
        else if ($flag == 0x10) {
            $body = static::readTextImage4772B0($fp);
        }

        return ['title'=>$title, 'body'=>$body];
    }

    private static function readTextImage477190($fp) {
        throw new Exception('readTextImage477190');
    }
    
    private static function readTextImage4772B0($fp) {
        $body = [];
        $loop = true;

        while ($loop) {
            $flag = freadu1($fp);

            switch ($flag) {
                case 0x16:
                    freadu1($fp); break;

                case 0x10:
                    fread($fp, 4); break;
                case 0x11:
                    fread($fp, 4); break;
                case 0x12:
                    fread($fp, 4); break;
                case 0x13:
                    fread($fp, 4); break;
                case 0x14:
                    fread($fp, 4); break;
                case 0x15:
                    freadstr($fp); break;

                case 0x1A:
                    fread($fp, 4); break;
                case 0x17:
                    fread($fp, 4); break;
                case 0x18:
                    fread($fp, 4); break;
                case 0x19:
                    fread($fp, 4); break;

                case 0x20:
                    DigiLocaReader::readStringarr($fp); break;
                
                case 0xFF:
                    $loop = false;
                    break;
            }
        }
        return $body;
    }

}