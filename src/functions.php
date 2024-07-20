<?php

define("HEXCMD", true);

const SCOPE_GLOBAL = 'G';
const SCOPE_LOCAL = 'L';

const TYPE_CONST = 'C';//0x81;
const TYPE_VAR = 'V';//0x80;
const TYPE_PROCEDURE = 'P';//0x83;
const TYPE_FUNCTION = 'F';//0x82;
const TYPE_ONEVENT = 'E';//0x84;
const TYPE_UNKNOWN = 'U';

const VAL_BOOL_STR = 'Boolean';//0x0B;
const VAL_FLOAT_STR = 'Float';//0x05;
const VAL_INT_STR = 'Integer';//0x03;
const VAL_STRING_STR = 'String';//0x100;
const VAL_UNKNOWN_STR = 'UNK';//0x0;

const VT_BOOL = 0x0B;
const VT_FLOAT = 0x05;
const VT_INT = 0x03;
const VT_STRING = 0x100;
const VT_UNK = 0x0;

Const EVT_ENTERFRAME = 0;
Const EVT_CASTCLICK = 1;
Const EVT_MOUSEDOWN = 2;
Const EVT_MOUSEMOVE = 3;
Const EVT_MOUSEUP = 4;
Const EVT_KEYDOWN = 5;
Const EVT_KEYPRESS = 6;
Const EVT_KEYUP = 7;
Const EVT_EXITFRAME = 8;

const CAST_MODEL = 0;
const CAST_TEXTURE = 1;
const CAST_BITMAP = 2;
const CAST_TEXT = 3;
const CAST_WAVE = 4;
const CAST_MIDI = 5;
const CAST_SCRIPT = 6;
const CAST_CAMERA = 7;
const CAST_LIGHT = 8;
const CAST_SOUND3D = 0xb;
const CAST_EAR = 0xd;

function cmdhex(int $cmd) {
    if (!HEXCMD) return $cmd;
    return dechex($cmd);
}

function tab(int $tabs) : string {
    $ret = '';
    for ($i = 0; $i < $tabs; $i++) {
        $ret .= '  ';
    }

    return $ret;
}

// use pi() is ok.. but i will keep this.
function _pi() {
    return upk0('e', "\x18\x2D\x44\x54\xFB\x21\x09\x40");
}

function upk0(string $format, string $string, int $offset = 0) {
    $ret = unpack($format, $string, $offset);

    if (is_array($ret)) {
        return current($ret);
    }
    
    return null;
}

function r_u1(string $bytes, int $offset) : int {
    return upk0('C', $bytes, $offset);
}

function d_u1(string $bytes, int &$offset) : int {
    $byte = upk0('C', $bytes, $offset/*substr($bytes, $offset, 1)*/); $offset += 1;
    return $byte;
}

function r_u2(string $bytes, int $offset) : int {
    return upk0('v', $bytes, $offset);
}

function d_u2(string $bytes, int &$offset) : int {
    $short = upk0('v', $bytes, $offset/*substr($bytes, $offset, 2)*/);  $offset += 2;
    return $short;
}

function r_4(string $bytes, int $offset) : int {
    return upk0('l', $bytes, $offset);
}

function d_4(string $bytes, int &$offset) : int {
    $int = upk0('l', $bytes, $offset/*substr($bytes, $offset, 4)*/); $offset += 4;
    return $int;
}

function r_u4(string $bytes, int $offset) : int {
    return upk0('V', $bytes, $offset);
}

function d_u4(string $bytes, int &$offset) : int {
    $int = upk0('V', $bytes, $offset /*substr($bytes, $offset, 4)*/); $offset += 4;
    return $int;
}

function d_str(string $bytes, int &$offset) : string {
    $strlen = upk0('V', $bytes, $offset/*substr($bytes, $offset, 4)*/); $offset += 4;
    if ($strlen > strlen($bytes)) {
        return null;
    }
    $string = substr($bytes, $offset, $strlen); $offset += $strlen;
    // $string = mb_convert_encoding($string,'UTF-8','Shift_JIS');
    return str_j2u($string);
}

function d_var(int $vt, string $bytes, int &$offset) {
    if ($vt == VT_BOOL) {
        $_value = upk0('c', $bytes, $offset/*substr($bytes, $offset++, 1)*/); $offset += 1;
    }
    else if ($vt == VT_INT) {
        $_value = upk0('l', $bytes, $offset/*substr($bytes, $offset, 4)*/); $offset += 4;
    }
    else if ($vt == VT_FLOAT) {
        $_value = upk0('e', $bytes, $offset/*substr($bytes, $offset, 8)*/); $offset += 8;
    }

    else if ($vt == VT_STRING) {
        $_value = d_str($bytes, $offset);
    }
    
    return $_value;
}

function identIndex($id_index) {
    if ($id_index >= 0) return $id_index;
    return ($id_index & 0x7FFFFFFF);
}

function get_class_name($classname)
{
    if ($pos = strrpos($classname, '\\')) return substr($classname, $pos + 1);
    return $pos;
}

function freadu1($fp) {
    return upk0('C', fread ($fp,1), 0);
}

function fread1($fp) {
    return upk0('c', fread ($fp,1), 0);
}

function freadu2($fp) {
    return upk0('v', fread ($fp,2), 0);
}

function freadu4($fp) {
    return upk0('V', fread ($fp,4), 0);
}

function fread4($fp) {
    return upk0('l', fread ($fp,4), 0);
}

function fread8($fp) {
    return upk0('e', fread ($fp,8), 0);
}

function freadstr($fp) {
    $strlen = //r_u4(fread ($fp,4),0);
        upk0('V', fread ($fp,4), 0);
    if ($strlen > 0) {
        $string = fread($fp, $strlen);
        // $string = mb_convert_encoding($string,'UTF-8','Shift_JIS');   
        return str_j2u($string);
    }

    return '';
}

function freadsafe($fp, $len) {
    if ($len == 0) return '';
    return fread($fp, $len);
}

function freadintarr($fp) {
    $flag = upk0('C', fread ($fp,1), 0);
    $count = upk0('V', fread ($fp,4), 0);

    $arr = [];
    if ($count > 0) {
        for ($i=0; $i<$count; $i++) {
            $arr[] = upk0('l', fread ($fp,4), 0);
        }
    }

    return $arr;
}

function freadPointarr($fp) {
    $flag = upk0('C', fread ($fp,1), 0);
    $count = upk0('V', fread ($fp,4), 0);

    $arr = [];
    if ($count > 0) {
        for ($i=0; $i<$count; $i++) {
            $arr[] = [upk0('l', fread ($fp,4), 0),
                    upk0('l', fread ($fp,4), 0)];
        }
    }

    return $arr;
}

function freadShortarr($fp) {
    $flag = upk0('C', fread ($fp,1), 0);
    $count = upk0('V', fread ($fp,4), 0);

    $arr = [];
    if ($count > 0) {
        for ($i=0; $i<$count; $i++) {
            $arr[] = upk0('s', fread ($fp,2), 0);
        }
    }

    return $arr;
}

function freadbytearr($fp) {
    $flag = upk0('C', fread ($fp,1), 0);
    $count = upk0('V', fread ($fp,4), 0);

    $arr = [];
    if ($count > 0) {
        for ($i=0; $i<$count; $i++) {
            $arr[] = upk0('C', fread ($fp,1), 0);
        }
    }

    return $arr;
}

function fsectcopy($wfp, $rfp, $roffset, $rlength) {
    $sectlen = 65536;
    $totalen = $rlength;
    fseek($rfp, $roffset);
    if ($totalen < 65536) {
        $sectlen = $totalen;
    }
    while(($b=fread($rfp, $sectlen))) { 
        $written = fwrite($wfp, $b);

        $totalen = $totalen - $written;
        if ($totalen == 0) {
            break;
        }
        if ($totalen < 65536) {
            $sectlen = $totalen;
        }
    }
}

/**
 * using it only on convert ir back into scripts
 */
function str_u2j(string $str) : string {
    return mb_convert_encoding($str,'Shift_JIS','UTF-8');
}

function str_j2u(string $str) : string {
    return mb_convert_encoding($str,'UTF-8', 'Shift_JIS');
}


function w_u1(string &$bytes, int $byte) {
    $packed = pack('C', $byte);  
    $bytes .= $packed; 
}

function w_u2(string &$bytes, int $short) {
    $packed = pack('v', $short);  
    $bytes .= $packed;  
}

function w_4(string &$bytes, int $int) {
    $packed = pack('l', $int);
    $bytes .= $packed; 
}

function w_u4(string &$bytes, int $int) {
    $packed = pack('V', $int);
    $bytes .= $packed; 
}

function w_8(string &$bytes, $double) {
    $packed = pack('e', $double);
    $bytes .= $packed; 
}

function w_str(string &$bytes, string $str) {
    $strlen = strlen($str);  
    $lengthPacked = pack('V', $strlen);  
    $bytes .= $lengthPacked . $str;
}

function dirclear($dir) {
    $files = scandir($dir);  
    if ($files !== false) {  
        foreach ($files as $file) {  
            if ($file != "." && $file != "..") {  
                $filePath = $dir .'\\'. $file;

                if (!is_dir($filePath)) {
                    unlink($filePath);
                }
            }
        }
    }
}