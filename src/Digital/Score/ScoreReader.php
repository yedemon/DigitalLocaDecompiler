<?php

declare(strict_types=1);

namespace Digital;

use Exception;

Class ScoreReader {

    /**
     * @param resource $fp
     */
    public static function readScoreInner($fp) {
        $score = [];
        $tracks = [];

        $loop = true;
        while ($loop) {
            $flag = freadu1($fp);
            switch ($flag) {
                case 0x21:
                    $score[0x21] = static::readScore45BB2C($fp);
                    break;

                case 0x20:
                    // print(dechex(ftell($fp)).PHP_EOL);
                    $tracks[] = static::readTrack($fp); break;

                case 0x10:
                    $score['trackNum'] = freadu4($fp); break;

                case 0x11:
                    $score['name'] = freadstr($fp); break;

                case 0x22:
                    $score['label'] = static::readLabel($fp); break;

                case 0xFF:
                    $loop = false;
                    break;
            }
        }

        $score['tracks'] = $tracks;

        return $score;
    }

    private static function readScore45BB2C($fp) {
        return freadbytearr($fp);
    }

    private static function readTrack($fp) {
        $track = [];

        $loop = true;
        while ($loop) {
            $flag = freadu1($fp);
            switch ($flag) {
                case 0x10:
                    $track[0x10] = fread4($fp); break;
                case 0x11:
                    $track['castType'] = fread4($fp); break;
                case 0x12:
                    $track[0x12] = fread4($fp); break;

                case 0x20:
                    $track['_3dLocation'] = static::read3DLocation($fp); break;
                case 0x21:
                    $track[0x21] = freadPointarr($fp); break;
                case 0x22:
                    $track[0x22] = freadintarr($fp); break;
                case 0x23:
                    $track['castIds'] = freadintarr($fp); break;
                case 0x25:
                    $track[0x25] = freadPointarr($fp); break;
                case 0x26:
                    $track[0x26] = freadPointarr($fp); break;

                case 0x29:
                    $track['material'] = static::readMaterial($fp); break;
    
                case 0x2A:
                    $track[0x2A] = freadShortarr($fp); break;

                case 0x33:
                    $track[0x33] = fread4($fp); break;

                case 0x30:
                    $track[0x30] = freadu1($fp); break;
                case 0x31:
                    $track[0x30] = freadu1($fp); break;
                case 0x32:
                    $track[0x32] = fread4($fp); break;

                case 0x40:
                    $track[0x40] = freadstr($fp); break;

                case 0x34:
                    $track[0x34] = fread4($fp); break;
                case 0x35:
                    $track[0x35] = fread4($fp); break;
                case 0x42:
                    $track[0x42] = fread($fp, 64); break;

                case 0xFF:
                    $loop = false;
                    break;

                default:
                    throw new Exception('track flag unknown '.dechex($flag));
                    break;
            }
        }

        return $track;
    }

    private static function read3DLocation($fp) {
        $_3dLocation = [];

        $flag = freadu1($fp);
        switch ($flag) {
            case 0:
                $_3dLocation[0] = static::read3DLocation47AADC($fp);
                break;
            case 1:
                $_3dLocation[1] = static::read3DLocation47AB54($fp);
                break;
            case 2:
                $_3dLocation[2] = static::read3DLocation47AD10($fp);
                break;
        }

        return $_3dLocation;
    }

    private static function read3DLocation47AADC($fp) {
        $_3dl = [];
        $_3dl['gap4_0'] = array_fill(0, 3072*2, 0);
        
        $a2a = freadu4($fp);
        if ($a2a) {
            static::read3DLocation47A73C($fp, $a2a);
            static::read3DLocation47A89C($fp, $_3dl, $a2a);
            static::read3DLocation47A95C($fp, $_3dl, $a2a);
            static::read3DLocation47AA1C($fp, $_3dl, $a2a);
        }

        unset($_3dl['gap4_0']);
        return $_3dl;
    }

    private static function read3DLocation47AB54($fp) {
        $a3 = 0;
        $loop = true;
        $_3dl = [];
        while ($loop) {
            $flag = freadu1($fp);
            switch ($flag) {
                case 0x30:
                    static::read3DLocation47A89C($fp, $_3dl, $a3); break;  // +4
                case 0x10:
                    $a3 = freadu4($fp); 
                    $_3dl['gap4_0'] = array_fill(0, 48 * ((($a3-1)/64+1)<<6), 0);
                    break;
                case 0x20:
                    static::read3DLocation47A73C($fp, $a3); break;
                case 0x21:
                    static::read3DLocation47A79C($fp, $_3dl, $a3, 0); break;   //+4
                case 0x22:
                    static::read3DLocation47A79C($fp, $_3dl, $a3, 1); break;   //+5
                case 0x23:
                    static::read3DLocation47A79C($fp, $_3dl, $a3, 2); break;   //+6

                case 0x31:
                    static::read3DLocation47A95C($fp, $_3dl, $a3); break;  // +5
                case 0x32:
                    static::read3DLocation47AA1C($fp, $_3dl, $a3); break;  // +6
                
                case 0x90:
                    fread($fp,4);
                    break;

                case 0xFF:
                    $loop = false;
                    break;
            }
        }

        unset($_3dl['gap4_0']);
        return $_3dl;
    }

    private static function read3DLocation47AD10($fp) {
        $gap4_7 = 0;
        $gap4_0 = 0;
        $gap4_1 = 0;
        $gap4_6 = 0;

        $loop = true;
        while ($loop) {
            $flag = freadu1($fp);
            switch ($flag) {
                case 0x83:
                    $gap4_7 = freadu4($fp);
                    fread($fp, $gap4_7);
                    break;
                case 0x80:
                    $gap4_0 = freadu4($fp); break;
                case 0x81:
                    $gap4_1 = freadu4($fp); break;
                case 0x82:
                    $gap4_6 = freadu4($fp); 
                    fread($fp, $gap4_6 * 4);
                    break;

                case 0x84:
                    fread($fp, $gap4_1 - $gap4_0);
                    break;

                case 0x90:
                    fread($fp, 4); break;

                case 0xFF:
                    $loop = false;
                    break;
            }
        }
    }

    private static function read3DLocation47A73C($fp, $a2) {
        $a2a = [0, 0];
        $v10 = 0;
        for ($i=0;$i<$a2;$i++) {
            static::read3DLocation478EE4($fp, $a2a, $v10);
        }
    }

    private static function read3DLocation478EE4($fp, &$a2, &$a3) {
        $a1 = 0;
        if (!$a2[1])
            $a2[0] = fread1($fp);

        $a1 = $a2[0];
        $a3 = $a2[0] < 0;
        $a1 = $a1 & 0x7F;
        $a2[0] = 2*$a1;

        if ( ++$a2[1] == 8 )
        {
            $a2[0] = 0;
            $a2[1] = 0;
        }
    }

    private static function read3DLocation47A89C($fp, &$_3dl, $a3) {
        $a2a = 0;
        $v4 = 0;
        $v5 = 0;
        $v11 = 0;

        while ($v4<$a3) {
            static::read3DLocation47A6FC($fp, $a2a);
            $v5 = $a2a + $v4;
            static::read3DLocation47A6FC($fp, $a2a);
            if ($a2a>0) {
                $v7 = 0;
                for($v11=0;$v11<$a2a;$v11++) {
                    $_3dl['gap4_0'][48 * ($v7 + $v5) + 4] = 1;
                    fread($fp,4);
                    fread($fp,4);
                    fread($fp,4);
                    $v7++;
                }
            }

            $v4 = $a2a + $v5;
        }
    }

    private static function read3DLocation47A95C($fp, &$_3dl, $a3) {
        $a2a = 0;
        $v4 = 0;
        $v5 = 0;
        $v11 = 0;
        while ($v4<$a3) {
            static::read3DLocation47A6FC($fp, $a2a);
            $v5 = $a2a + $v4;
            static::read3DLocation47A6FC($fp, $a2a);
            if ($a2a>0) {
                $v7 = 0;
                for($v11=0;$v11<$a2a;$v11++) {
                    $_3dl['gap4_0'][48 * ($v7 + $v5) + 5] = 1;
                    fread($fp,4);
                    fread($fp,4);
                    fread($fp,4);
                    $v7++;
                }
            }

            $v4 = $a2a + $v5;
        }
    }

    private static function read3DLocation47AA1C($fp, &$_3dl, $a3) {
        $a2a = 0;
        $v4 = 0;
        $v5 = 0;
        $v11 = 0;
        while ($v4<$a3) {
            static::read3DLocation47A6FC($fp, $a2a);
            $v5 = $a2a + $v4;
            static::read3DLocation47A6FC($fp, $a2a);
            if ($a2a>0) {
                $v7 = 0;
                for($v11=0;$v11<$a2a;$v11++) {
                    $_3dl['gap4_0'][48 * ($v7 + $v5) + 6] = 1;
                    fread($fp,4);
                    fread($fp,4);
                    fread($fp,4);
                    $v7++;
                }
            }

            $v4 = $a2a + $v5;
        }
    }

    private static function read3DLocation47A6FC($fp, &$a2) {
        $v6 = freadu1($fp);
        if ( ($v6 & 0x80) != 0 ){
            $a2 = fread4($fp);
            return;
        }
        $a2 = $v6 & 0x7F;
    }

    private static function read3DLocation47A79C($fp, $_3dl, $a3, $a4) {
        $v12 = [0, 0];
        $a3a = 0;
        if ( $a3 > 0 ) {
            $v7 = $a3;
            $v8 = 0;
            while ($v7) {
                $a3a = 0;
                if ($a4 == 0) {
                    $a3a = $_3dl['gap4_0'][48 * $v8 + 4] == 1;
                }
                else if ($a4 == 1) {
                    $a3a = $_3dl['gap4_0'][48 * $v8 + 5] == 1;
                }
                else if ($a4 == 2) {
                    $a3a = $_3dl['gap4_0'][48 * $v8 + 6] == 1;
                }

                if ( $a3a ) {
                    static::read3DLocation478EE4($fp, $v12, $a3a);
                }

                $v7--;
                $v8++;
            }

        }
    }

    private static function readMaterial($fp) {
        $flag = freadu1($fp);
        if ($flag == 1) {
            static::readMaterial47C4B4($fp);
        } else {
            throw new Exception('Material Format err');
        }
    }

    private static function readMaterial47C4B4($fp) {
        $a3 = 0;
        $a4 = 0;
        $material = null;

        $loop = true;
        while ($loop) {
            $flag = freadu1($fp);
            switch ($flag) {
                case 0x10:
                    $a3 = freadu4($fp); break;
                case 0x11:
                    $a4 = freadu4($fp); break;

                case 0x30:
                    $material = static::readMaterial47C434($fp, $a3, $a4); break;

                case 0xFF:
                    $loop = false;
                    break;
            }
        }

        return $material;
    }

    private static function readMaterial47C434($fp, $a3, $a4) {
        $a2a = 0;
        $v4 = 0;
        $v5 = 0;
        $v11 = 0;
        while ($v4<$a3) {
            static::readMaterial47C3F4($fp, $a2a);
            $v5 = $a2a + $v4;
            static::readMaterial47C3F4($fp, $a2a);
            if ($a2a>0) {
                for($v11=0;$v11<$a2a;$v11++) {
                    fread($fp,4 * $a4);
                }
            }

            $v4 = $a2a + $v5;
        }
    }

    private static function readMaterial47C3F4($fp, &$a2) {
        $v6 = freadu1($fp);
        if ( ($v6 & 0x80) != 0 ){
            $a2 = fread4($fp);
            return;
        }
        $a2 = $v6 & 0x7F;
    }

    private static function readLabel($fp) {
        $lables = [];

        $loop = true;
        while ($loop) {
            $flag = fread4($fp);
            if ($flag == -1) {
                $loop = false;
                continue;
            }

            $lable = [];
            $lable['name'] = freadstr($fp);
            $lable['id'] = freadu4($fp);

            $lables[] = $lable;
        }

        return $lables;
    }
}