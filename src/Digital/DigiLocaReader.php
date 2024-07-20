<?php

declare(strict_types=1);

namespace Digital;

use Exception;

Class DigiLocaReader {

    /**
     * @param resource $fp
     */
    public static function readZlib1($fp) {
        $loop = true;
        while ($loop) {
            $flag = freadu1($fp);//r_u1 ( fread($fp,1), 0 );
            switch ($flag) {
                case 0x10:
                    fread ($fp,4); break;
                case 0x11:
                    fread ($fp,4); break;
                case 0x12:
                    fread ($fp,4); break;
                case 0x13:
                case 0x21:
                case 0x2A:
                    fread ($fp,4); break;
                case 0x14:
                    fread ($fp,4); break;
                case 0x15:
                    fread ($fp,4); break;
                case 0x16:
                    fread ($fp,4); break;
                case 0x17:
                    fread ($fp,4); break;
                case 0x18:
                    fread ($fp,4); break;
                case 0x19:
                    fread ($fp,4); break;
                case 0x1F:
                    fread ($fp,4); break;
                case 0x20:
                    fread ($fp,4); break;
                case 0x22:
                    fread ($fp,4); break;
                case 0x23:
                    fread ($fp,4); break;
                case 0x24:
                    fread ($fp,4); break;
                case 0x25:
                    fread ($fp,4); break;
                case 0x26:
                    fread ($fp,4); break;
                case 0x27:
                    fread ($fp,4); break;
                case 0x28:
                    fread ($fp,4); break;
                case 0x29:
                    fread ($fp,4); break;
                case 0x2B:
                    fread ($fp,4); break;
                case 0x2C:
                    fread ($fp,4); break;
                case 0x2D:
                    fread ($fp,4); break;
                case 0x2E:
                    fread ($fp,4); break;
                case 0x2F:
                    fread ($fp,4); break;
                case 0x30:
                    fread ($fp,4); break;
                case 0x31:
                    freadstr ($fp); break;
                case 0x32:
                    freadstr ($fp); break;
                case 0x33:
                    freadstr ($fp); break;
                case 0x34:
                    freadstr ($fp); break;
                case 0x35:
                    freadstr ($fp); break;
                case 0x36:
                    freadstr ($fp); break;
                case 0x37:
                    freadstr ($fp); break;
                case 0x38:
                    freadstr ($fp); break;
                case 0x39:
                    fread ($fp,4); break;
                case 0x3A:
                    fread ($fp,4); break;
                case 0x3B:
                    fread ($fp,4); break;
                case 0x3C:
                    freadstr ($fp); break;
                case 0x3D:
                    freadstr ($fp); break;

                case 0x40:
                    freadstr ($fp); break;

                case 0x41:
                    freadstr ($fp); break;

                case 0x50:
                    freadstr ($fp); break;
                case 0x60:
                    freadstr ($fp); break;

                case 0x68:
                    fread ($fp,4); break;
                case 0x69:
                    fread ($fp,4); break;
                case 0x6A:
                    fread ($fp,4); break;

                case 0x70:
                    fread ($fp,1); break;
                case 0x71:
                    fread ($fp,1); break;
                case 0x72:
                    fread ($fp,1); break;
                case 0x73:
                    fread ($fp,1); break;
                case 0x74:
                    fread ($fp,1); break;
                case 0x75:
                    fread ($fp,1); break;

                case 0x76:
                case 0x7B:
                case 0x7E:
                    fread ($fp,1); break;

                case 0x77:
                    fread ($fp,1); break;
                case 0x78:
                    fread ($fp,1); break;
                case 0x79:
                    fread ($fp,1); break;
                case 0x7A:
                    fread ($fp,1); break;
                case 0x7C:
                    fread ($fp,1); break;
                case 0x7D:
                    fread ($fp,1); break;

                case 0x83:
                    fread ($fp,1); break;

                case 0x90:
                    fread ($fp,1); break;
                case 0x91:
                    fread ($fp,1); break;
                case 0x92:
                    fread ($fp,1); break;
                case 0x93:
                    fread ($fp,1); break;
                case 0x94:
                    fread ($fp,1); break;
                case 0x95:
                    fread ($fp,1); break;
                case 0x96:
                    fread ($fp,1); break;
                case 0x97:
                    fread ($fp,1);break;

                case 0xFF:
                    $loop = false;
                    break;
            }
        }
    }

    /**
     * @param resource $fp
     */
    public static function readX10($fp) {
        $casts = [];

        $cate = 0;
        $id = 0;
        $soffset = 0;

        $loop = true;
        while ($loop) {
            $flag = freadu1($fp);
            switch ($flag) {
                case 0x10:
                    $soffset = ftell($fp) - 1;
                    $cate = freadu4($fp);
                    break;
                case 0x11:
                    $id = freadu4($fp);
                    break;
                case 0x20:
                    $cast = static::readCast($fp, $cate);
                    $eoffset = ftell($fp) - 1;
                    $cast['soffset'] = $soffset;
                    $cast['eoffset'] = $eoffset;
                    $cast['cate'] = $cate;
                    $cast['id'] = $id;

                    $casts[$cate][] = $cast;
                    break;

                case 0xFF:
                    $loop = false;
                    break;
            }
        }
        
        return $casts;
    }

    /**
     * MultiScore
     * @param resource $fp
     */
    public static function readX20($fp) {
        $scores = [];

        $loop = true;
        while ($loop) {
            $flag = freadu1($fp);
            switch ($flag) {
                case 0x20:
                    $soffset = ftell($fp) - 1;
                    $score = ScoreReader::readScoreInner($fp);

                    $eoffset = ftell($fp) - 1;
                    $score['soffset'] = $soffset;
                    $score['eoffset'] = $eoffset;

                    $scores[] = $score;
                    break;

                case 0xFF:
                    $loop = false;
                    break;
            }
        }

        return $scores;
    }

    /**
     * @param resource $fp
     */
    public static function readX30($fp) {
        $codelen = r_u4( fread ($fp,4), 0 );
        // $bytes = fread($fp, $codelen);
        fseek($fp, $codelen, SEEK_CUR);
        // return $bytes;
    }

    public static function readCast($fp, $cate) {
        $loop = true;
        while ($loop) {
            $flag = freadu1($fp);//r_u1 ( fread($fp,1), 0 );
            if ($flag != 0x10) {
                $loop = false; continue;
            }

            switch ($cate) {
                case 0:
                    $ccast = CastModel::readModel($fp);
                    // $ccast['name'] = 'ModelCast';
                    break;
                case 1:
                    $ccast = CastTexture::readTexture($fp);
                    // $ccast['name'] = 'TextureCast';
                    break;
                case 2:
                    $ccast = CastTexture::readTexture($fp);
                    // $ccast['name'] = 'BitmapCast';
                    break;

                case 3:
                    $ccast = CastTextImage::readTextImage($fp);
                    // $ccast['name'] = 'TextCast';
                    break;

                case 4:
                    $ccast = CastWave::readWave($fp);
                    // $ccast['name'] = 'WaveCast';
                    break;

                case 5:
                    $ccast = CastMIDI::readMIDI($fp);
                    // $ccast['name'] = 'MIDICast';
                    break;

                case 6:
                    $ccast = static::readScript($fp);
                    break;

                case 7:
                    $ccast = CastCamera::readCamera($fp);
                    // $ccast['name'] = 'CameraCast';
                    break;

                case 8:
                    $ccast = CastLight::readLight($fp);
                    // $ccast['name'] = 'LightCast';
                    break;

                case 0xB:
                    $ccast = CastWave::readWave($fp);
                    // $ccast['name'] = 'Sound3DCast';
                    break;
                
                case 0xD:
                    $ccast = CastEar::readEar($fp);
                    // $ccast['name'] = 'EarCast';
                    break;

                default:
                    throw new Exception('Unknown cate');
            }
        }
        
        return $ccast;
    }

    public static function readCastName($fp) {
        $title = [];
        $loop = true;
        while ($loop) {
            $flag = freadu1($fp);//r_u1 ( fread($fp,1), 0 );

            switch ($flag) {
                case 0x0:
                    $title[0] = freadu4($fp);//r_u4(fread($fp,4), 0);
                    break;
                case 0x1:
                    $title[1] = freadstr($fp);
                    break;
                case 0x2:
                    $title[2] = fread8($fp);//r_u4(fread($fp,4), 0);
                    break;
                case 0x3:
                    $title[3] = freadstr($fp);//r_u4(fread($fp,4), 0);
                    break;

                case 0xFF:
                    $loop = false;
                    break;
            }
        }
        return $title;
    }

    public static function readStringarr($fp) {
        $arr = [];
        $count = freadu4($fp);
        for($i=0;$i<$count;$i++) {
            $arr[] = freadstr($fp);
        }

        return $arr;
    }

    /**
     * this only helps to skip the scripts parts.
     */
    public static function readScript($fp) {
        $name = static::readCastName($fp);

        freadu1($fp);
        $lineCount = freadu4($fp);

        for ($i = 0;$i<$lineCount;$i++) {
            $line = freadstr($fp);
        }

        // should be 0xFF
        // freadu1($fp);

        return ['title'=>$name, 'body'=>['lines'=>$lineCount]];
    }
}