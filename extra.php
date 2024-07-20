<?php

require __DIR__ . "./vendor/autoload.php";

if ($argc < 2) {
    echo "Usage: php exra.php <your_lcl_file>\n";
    exit(1);
}

$lcl_file = $argv[1];

if (!file_exists($lcl_file)) {
    echo "Error: File '{$lcl_file}' does not exist.\n";
    exit(1);
}

if (!is_readable($lcl_file)) {
    echo "Error: File '{$lcl_file}' is not readable.\n";
    exit(1);
}

try {
    $fp = fopen($lcl_file, 'r');
    $fheader = fread($fp, 11);
    $unknown1 = freadu4($fp);
    $unknown2 = freadu4($fp);
    $version = freadu4($fp);
    if ($version >= 0x2E) {
        $unknown = freadu1($fp);  // should be 0x88?
    }

    if ($fheader == 'DIGILOCAPLY' || $fheader == 'DIGILOCAPRJ') {
    } else {
        echo 'Error: file header not match.';
    }

    $flag1 = freadu1($fp);
    $zlib1_length = freadu4($fp);
    // don't know what a format is this, but it's handled like this.
    // 80 F7 00 00 -> F7 00 00 80, then whip the 8.
    $zlib1_length = rotate($zlib1_length, -8);
    $zlib1_length = $zlib1_length & 0xFFFFFFF;

    $zlib1 = fread($fp, $zlib1_length);
    $zlib1_uncompress = gzuncompress($zlib1);

    $flag2 = freadu1($fp);  // 0x20
    $zlib2_length = freadu4($fp);
    $zlib2_length = rotate($zlib2_length, -8);
    $zlib2_length = $zlib2_length & 0xFFFFFFF;

    $zlib2 = fread($fp, $zlib2_length);
    $zlib2_uncompress = gzuncompress($zlib2);

    // there are still some bytes after, but don't handle is ok..

    // .lcl -> .lcr
    $length = strlen($lcl_file);
    if ($length >= 4 && substr($lcl_file, $length - 4) === '.lcl') {  
        $lcr_file = substr($lcl_file, 0, $length - 4) . '.lcr';  
    } else {
        $lcr_file = $lcl_file . '.lcr';
    }
    
    try {
        $fpw = fopen($lcr_file, 'w');

        // write old format only. hope it will be ok with newer lcls.
        fwrite($fpw, 
                "DIGILOCAPLY\x00\x00\x00\x00\x00\x00\x00\x00\x2B\x00\x00\x00");

        fwrite($fpw, "\x00");
        fwrite($fpw, $zlib1_uncompress);
        fwrite($fpw, $zlib2_uncompress);

    } catch (Exception $e) {
        fclose($fpw);
    }

} finally {
    fclose($fp);
}

function rotate($decimal, $bits)
{
    $binary = decbin($decimal);
    $binary = str_pad($binary, 32, '0', STR_PAD_LEFT);
    return bindec(substr($binary, $bits) . substr($binary, 0, $bits));
}