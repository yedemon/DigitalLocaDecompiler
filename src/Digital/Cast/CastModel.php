<?php

declare(strict_types=1);

namespace Digital;

use Exception;

Class CastModel {
    const GROUP = 'group';
    public static function readModel($fp) {
        $title = DigiLocaReader::readCastName($fp);
        $flag = freadu1($fp);
        if ($flag == 0) {
            $body = static::readCastBody0($fp);
        }
        else {
            $body = static::readCastBody1($fp);
        }

        return ['title'=>$title, 'body'=>$body];
    }

    private static function readCastBody0($fp) {
        throw new Exception('readCastBody0');
    }

    private static function readCastBody1($fp) {
        $body = [];
        $loop = true;
        $a1F34_0 = false;

        $a1F34_1 = 0;
        $a1F34_3 = 0;
        $a1F34_4 = 0;

        $group = [];
        $groupIndex = -1;
        while ($loop) {
            $flag = freadu1($fp);

            switch ($flag) {
                case 0x10:
                    $a1F34_1 = freadu4($fp); break;
                case 0x11:
                    $a1F34_3 = freadu4($fp); break;
                case 0x12:
                    $a1F34_4 = freadu4($fp); break;
                case 0x13:
                    $groupIndex++;
                    $group[$groupIndex] = [];
                    break;

                case 0x14:
                    $a1f74 = freadu4($fp);
                    for ($i=0;$i!=8;$i++) {
                        $v14 = freadu4($fp);
                        if ($v14) {
                            freadsafe($fp, 8 * $a1f74);
                        }
                    }
                    break;

                case 0x15:
                case 0x2E:
                case 0xBF:
                    $v22 = freadu4($fp);
                    break;

                case 0x16:
                    freadsafe($fp, $a1F34_1); break;
                case 0x17:
                    fread($fp, 4); break;
                case 0x18:
                    $v4_63 = freadu4($fp);
                    freadsafe($fp, $v4_63);
                    break;

                case 0x19:
                    static::fread_49FC38($fp);
                    static::fread_49FC38($fp);
                    static::fread_49FBF8($fp);
                    break;
                case 0x1A:
                    static::fread_49FC38($fp);
                    static::fread_49FC38($fp);
                    static::fread_49FBF8($fp);
                    break;
                case 0x1B:
                    $v22 = freadu4($fp);
                    freadsafe($fp, $v22);
                    break;

                case 0x20:
                    freadsafe($fp, $a1F34_1 * 12); break;
                case 0x21:
                    if ($a1F34_0) {
                    } else {
                        freadsafe($fp, $a1F34_4 * 2);
                    }
                    break;
                case 0x22:
                    if ($a1F34_0) {
                    } else {
                        freadsafe($fp, $a1F34_3 * 2);
                    }
                    break;
                case 0x23:
                    $flagX23 = freadu1($fp);
                    if ($a1F34_0) {
                    } else {
                        if ($flagX23 == 0x10) {
                            freadsafe($fp, $a1F34_4);
                        }
                        else {
                            throw new Exception('Model body 0x23 flagX23 not 0x10.');
                        }
                    }
                    break;
                case 0x24:
                    $flagX24 = freadu1($fp);
                    if ($a1F34_0) {
                    } else {
                        if ($flagX24 == 0x10) {
                            freadsafe($fp, intval(floor(($a1F34_3+1) /2)));
                        } else if ($flagX24 == 0x20) {
                            freadsafe($fp, $a1F34_3);
                        } else {
                            throw new Exception('Model body 0x24 flagX24 unexpected.');
                        }
                    }
                    break;
                case 0x70:
                    fread($fp, 4); break;
                case 0x71:
                    static::fread_49FC38($fp); break;
                case 0x72:
                    fread($fp, 4); break;
                case 0x73:
                    fread($fp, 24); break;
                case 0x74:
                    static::fread_49FC38($fp); break;
                case 0x80:
                    fread($fp, 4); break;
                case 0x81:
                    fread($fp, 4); break;
                case 0x82:
                    fread($fp, 4); break;
                case 0x83:
                    fread($fp, 4); break;       
                case 0x90:
                    fread($fp, 16); break;
                case 0x91:
                    fread($fp, 16); break;
                case 0x92:
                    fread($fp, 16); break;
                case 0x93:
                    fread($fp, 16); break;
                case 0x94:
                    fread($fp, 4); break;
                case 0x95:
                    fread($fp, 2); break;
                case 0x96:
                    fread($fp, 2); break;
                case 0x97:
                    fread($fp, 2); break;
                case 0x98:
                    fread($fp, 4); break;
                case 0x99:
                    fread($fp, 4); break;
                case 0x9A:
                    fread($fp, 4); break;
                case 0xA0:
                    fread($fp, 4); break;
                case 0xA1:
                    fread($fp, 4); break;
                case 0xA2:
                    fread($fp, 4); break;
                case 0xA3:
                    fread($fp, 4); break;
                case 0xA4:
                    fread($fp, 4); break;
                case 0xA5:
                    fread($fp, 4); break;
                case 0xA6:
                    fread($fp, 4); break;
                case 0xB0:
                    fread($fp, 4); break;
                case 0xB1:
                    $groupName = freadstr($fp); 
                    if ($groupIndex >= 0) {
                        $group[$groupIndex]['name'] = $groupName;
                    }
                    break;
                case 0xB2:
                    fread($fp, 4); break;
                case 0xC0:
                    fread($fp, 4); break;
                case 0xC1:
                    fread($fp, 4); break;
                case 0xC2:
                    fread($fp, 4); break;
                case 0xC3:
                    fread($fp, 4); break;
                case 0xC4:
                    fread($fp, 4); break;
                case 0xC5:
                    fread($fp, 4); break;
                case 0xC6:
                    fread($fp, 4); break;
                case 0xC7:
                    fread($fp, 4); break;
                case 0xC8:
                    fread($fp, 4); break;
                case 0xC9:
                    fread($fp, 4); break;
                case 0xCA:
                    fread($fp, 4); break;
                case 0xFE:
                    $a1F34_0 = -1;//??
                    break;
                case 0xFF:
                    $loop = false;
                    break;

                default:
                    throw new Exception('Model body unknown flag '.dechex($flag));
            }
        }

        $body[self::GROUP] = $group;
        return $body;
    }

    private static function fread_49FC38($fp) : string {
        return fread($fp, 12);
    }

    private static function fread_49FBF8($fp) : string {
        return fread($fp, 4);
    }
}