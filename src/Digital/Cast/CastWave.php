<?php

declare(strict_types=1);

namespace Digital;

use Exception;

Class CastWave {
    public static function readWave($fp) {
        $title = DigiLocaReader::readCastName($fp);

        $flag = freadu1($fp);
        if (!$flag) {
            $body = static::read47606C($fp);
            return ['title'=>$title, 'body'=>$body];
        }
        if ($flag != 1) {
            if ($flag != 2) {
                throw new Exception('Wave body != 2');
            }

            $body = static::readWaveBody($fp);
        }

        return ['title'=>$title, 'body'=>$body];
    }

    private static function readWaveBody($fp) {
        $loop = true;
        while ($loop) {
            $flag = freadu1($fp);
            switch ($flag) {
                case 0x83:
                    fread($fp, 4); break;
                case 0x84:
                    fread($fp, 4); break;
                case 0x85:
                    fread($fp, 1); break;

                case 0x82:
                    fread($fp, 4); break;
                case 0x10:
                    fread($fp, 1); break;

                case 0x20:
                    $body = static::read47606C($fp); break;

                case 0x80:
                    fread($fp, 4); break;
                case 0x81:
                    fread($fp, 4); break;
                case 0xFF:
                    $loop = false;
                    break;
            }
        }
        return $body;
    }

    private static function read47606C($fp) {
        $byte32 = fread($fp,32);
        $len = r_u4($byte32, 28);

        $res = fread($fp, $len);
        return $res;
    }
}