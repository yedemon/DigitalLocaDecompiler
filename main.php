<?php

require __DIR__ . "./vendor/autoload.php";

use Digital\DigiLoca;

$options = getopt('t:f:de');

$t = $options['t'];
$f = $options['f'];
$decode = key_exists('d', $options);
$encode = key_exists('e', $options);

$digiLoca = new DigiLoca();

if ($decode) {
    switch ($t) {
        case 'ir2script':
            // usage : php main.php -t ir2script -d -f path_to_PROJ_BASE/pcode
            $digiLoca->playWithIr($f);
            break;

        case 'pcode2ir':
            // usage : php main.php -t pcode2ir -d -f path_to_PROJ_BASE/pcode
            $digiLoca->playWithPcode($f);
            break;

        case 'lcr':
            // usage : php main.php -t lcr -d -f path_to_your_game/unpacked.lcr
            $digiLoca->decompilePlay($f);
            break;
    }
}
else if ($encode) {

}
