<?php

declare(strict_types=1);

namespace Digital;

Class CastEar {
    public static function readEar($fp) {
        $title = DigiLocaReader::readCastName($fp);

        $loop = true;
        $body = [];

        while ($loop) {
            $flag = freadu1($fp);
            switch ($flag) {
                case 0:
                    // do nothing...
                    break;
                case 0x11:
                    fread($fp, 4);
                    break;
                case 0x10:
                    fread($fp, 4);
                    break;

                case 0x12:
                    fread($fp, 4);
                    break;

                case 0xFF:
                    $loop = false;
                    break;
            }
        }

        return ['title'=>$title, 'body'=>$body];
    }

}