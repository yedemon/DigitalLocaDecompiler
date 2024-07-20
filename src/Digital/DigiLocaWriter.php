<?php

declare(strict_types=1);

namespace Digital;


Class DigiLocaWriter {

    public static function writeCastName(&$bytes, $name) {
        w_u1($bytes, 0x00);
        w_u4($bytes, 0x00); // don't know

        w_u1($bytes, 0x1);
        w_str($bytes, $name);

        w_u1($bytes, 0x2);
        w_8($bytes, 0); // <- OLEtime...

        w_u1($bytes, 0x3);
        w_u4($bytes, 0x00); // don't know

        w_u1($bytes, 0xFF);
    }

    /**
     * @param array $flines
     */
    public static function writeScript($scriptId, $scriptName, $flines) : string {
        $bytes = '';

        w_u1($bytes, 0x10);
        w_u4($bytes, CAST_SCRIPT);

        w_u1($bytes, 0x11);
        w_u4($bytes, $scriptId);

        w_u1($bytes, 0x20);
        {
            w_u1($bytes, 0x10); // extern??
            static::writeCastName($bytes, $scriptName);
        }
        {
            w_u1($bytes, 0x00);
            w_u4($bytes, count($flines));

            foreach($flines as $fline) {
                w_str($bytes, $fline);
            }
        }
        w_u1($bytes, 0xFF);

        return $bytes;
    }
}