<?php

declare(strict_types=1);

namespace Digital;

use Exception;

Class CastTexture {
    public static function readTexture($fp) {
        $title = DigiLocaReader::readCastName($fp);

        $flag = freadu1($fp);
        if ($flag) {
            if ($flag == 0x10) {
                $body = static::readTextureBody($fp);
            }
        } else {
            throw new Exception('texture flag 0');
        }

        return ['title'=>$title, 'body'=>$body];
    }

    private static function readTextureBody($fp) {
        $body = [];
        $loop = true;
        $v19 = 0;
        $v20 = 0;
        $v21 = 0;
        $v22 = 0;
        $f88 = 0;
        $f1D6 = 0;
        $gap3C = 0;
        while ($loop) {
            $flag = freadu1($fp);
            switch ($flag) {
                case 0x30:
                    $body[0x30] = fread($fp, $gap3C);
                    break;
                case 0x40:
                    $f88 = -1;
                    $lenX40 = freadu4($fp);
                    $body[0x40] = fread($fp, $lenX40);
                    break;
                case 0x10:
                    $v19 = freadu4($fp); break;
                case 0x11:
                    $v20 = freadu4($fp); break;
                case 0x12:
                    $v21 = freadu2($fp); break;
                case 0x13:
                    $v22 = freadu4($fp); break;
                case 0x14:
                    fread($fp, 4); break;
                case 0x15:
                    fread($fp, 4); break;
                case 0x16:
                    fread($fp, 1); break;
                case 0x17:
                case 0x18:
                case 0x1E:
                    fread($fp, 1); break;
                case 0x1A:
                    fread($fp, 4); break;
                case 0x1B:
                    fread($fp, 1); break;
                case 0x1D:
                    fread($fp, 8); break;
                case 0x1F:
                    fread($fp, 1); break;
                case 0x20:
                    fread($fp, 4*$v22); break;
                
                case 0x65:
                    fread($fp, 4); break;
                case 0x62:
                    fread($fp, 4); break;
                case 0x63:
                    fread($fp, 16); break;
                case 0x64:
                    fread($fp, 4); break;
                case 0x61:
                    fread($fp, 4); break;
                
                case 0x50:
                    $lenX50 = freadu4($fp);
                    if ($lenX50) {
                        $body[0x50] = fread($fp, $lenX50);
                    }
                    break;
                case 0x60:
                    fread($fp, 4); break;

                case 0x80:
                    fread($fp, 1); break;
    
                case 0x66:
                case 0x67:
                    fread($fp, 4); break;

                case 0x70:
                    $f1D6 = freadu1($fp); break;

                case 0x81:
                    fread($fp, 1); break;

                case 0xFE:
                    if (!$f1D6 && !$f88) {
                        $v3 = (($v21 * $v19 - 1) | 0x1F) + 1;
                        if ( $v3 < 0 )
                            $v3 = (($v21 * $v19 - 1) | 0x1F) + 8;
                     
                        $gap3C = $v20 * ($v3 >> 3);
                    }
                    break;

                case 0xFF:
                    $loop = false;
                    break;
            }
        }
        return $body;
    }
}