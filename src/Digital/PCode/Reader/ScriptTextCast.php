<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;
use Digital\PCode\PCodeReader;

class ScriptTextCast {

    const names = [
        0x21 => 'Count',
        0x41 => 'Height',
        0x45 => 'StringHeight',
        0x44 => 'StringWidth',
        0x42 => 'TextWidth',
        0x43 => 'TextHeight',
        0x40 => 'Width',

        0x33 => 'BackColor.R',
        0x34 => 'BackColor.G',
        0x35 => 'BackColor.B',
        0x48 => 'BackColor.Value',
        0x30 => 'Font.Color.R',
        0x31 => 'Font.Color.G',
        0x32 => 'Font.Color.B',
        0x47 => 'Font.Color.Value',

        0x12 => 'Name',
        0x37 => 'Priority',
        0x22 => 'Strings',
        0x10 => 'Tag',
        0x20 => 'Text',
        0x36 => 'Transparent',
    ];

    const valTypes = [
        0x21 => PCodeReader::VAL_INT,
        0x41 => PCodeReader::VAL_INT,
        0x45 => PCodeReader::VAL_INT,
        0x44 => PCodeReader::VAL_INT,
        0x42 => PCodeReader::VAL_INT,
        0x43 => PCodeReader::VAL_INT,
        0x40 => PCodeReader::VAL_INT,

        0x33 => PCodeReader::VAL_INT,
        0x34 => PCodeReader::VAL_INT,
        0x35 => PCodeReader::VAL_INT,
        0x48 => PCodeReader::VAL_INT,
        0x30 => PCodeReader::VAL_INT,
        0x31 => PCodeReader::VAL_INT,
        0x32 => PCodeReader::VAL_INT,
        0x47 => PCodeReader::VAL_INT,

        0x12 => PCodeReader::VAL_STRING,
        0x37 => PCodeReader::VAL_INT,
        0x22 => PCodeReader::VAL_STRING,
        0x10 => PCodeReader::VAL_INT,
        0x20 => PCodeReader::VAL_STRING,
        0x36 => PCodeReader::VAL_BOOL,
    ];

    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $obj = AstFactory::TextCastNode();

        $obj_index = EvalSystem::digest($root, $bytes, $offset);
        $obj_idxr = AstFactory::indexerNode($obj, $obj_index);

        $prop = d_u1($bytes, $offset);
        // x23 => Add(s)
        // x24 => Delete(1)
        // x26 => Exchange(1,2);
        // x25 => Insert(0,s);
        // x80 => LoadHTTPFile(s)
        // x11 => Reset
        // x27 => Sort
        // x28 => Clear

        if ($prop == 0x11) {
            $node = AstFactory::callNode($obj_idxr, 'Reset', [], 0x11);
            goto LABEL_133;
        }
        if ($prop == 0x27) {
            $node = AstFactory::callNode($obj_idxr, 'Sort', [], 0x27);
            goto LABEL_133;
        }
        if ($prop == 0x28) {
            $node = AstFactory::callNode($obj_idxr, 'Clear', [], 0x28);
            goto LABEL_133;
        }

        if ($prop == 0x90 || $prop == 0x91) {
            // newer version
            $params = [];
            $params[] = EvalSystem::digest($root, $bytes, $offset);
            $params[] = EvalSystem::digest($root, $bytes, $offset);
            $params[] = EvalSystem::digest($root, $bytes, $offset);
            $params[] = EvalSystem::digest($root, $bytes, $offset);

            $node = AstFactory::callNode($obj_idxr, 'X'.dechex($prop), $params, $prop);
            goto LABEL_133;
        }
        // if (($prop + 0x70)&0xFF >= 2) { // ~0x91|0x92
        //     if ($prop != 0x11 & $prop != 0x27 && $prop != 0x28)
        //         EvalSystem::digest($root, $bytes, $offset);
        // } else {
        //     // unknown. x90, x91 .. copyfromintarray?
        //     EvalSystem::digest($root, $bytes, $offset);
        //     EvalSystem::digest($root, $bytes, $offset);
        //     EvalSystem::digest($root, $bytes, $offset);
        //     EvalSystem::digest($root, $bytes, $offset);
        // }

        if ($prop == 0x22) {
            // TextCast[imaxx].Strings[0] = s;
            $obj_string_index = EvalSystem::digest($root, $bytes, $offset);

            $obj_idxr_string = AstFactory::subobjNode($obj_idxr, 'Strings', 0x22);
            $obj_idxr_string_idxr = AstFactory::indexerNode($obj_idxr_string, $obj_string_index);

            $node2 = EvalSystem::digest($root, $bytes, $offset);
            $node = AstFactory::assignNode($obj_idxr_string_idxr, $node2, true);
            goto LABEL_133;
        }
        if ($prop == 0x25) {
            // Insert
            $params = [];
            $params[] = EvalSystem::digest($root, $bytes, $offset);//id
            $params[] = EvalSystem::digest($root, $bytes, $offset);//string

            $node = AstFactory::callNode($obj_idxr, 'Insert', $params, 0x25);
            goto LABEL_133;
        }
        if ($prop == 0x26) {
            // ExChange
            $params = [];
            $params[] = EvalSystem::digest($root, $bytes, $offset);//id
            $params[] = EvalSystem::digest($root, $bytes, $offset);//string

            $node = AstFactory::callNode($obj_idxr, 'ExChange', $params, 0x26);
            goto LABEL_133;
        }

        // if ($prop == 0x22 || $prop == 0x25 || $prop == 0x26) {
        //     EvalSystem::digest($root, $bytes, $offset);
        // }
        if ($prop == 0x23) {
            $params = [];
            $params[] = EvalSystem::digest($root, $bytes, $offset);//string

            $node = AstFactory::callNode($obj_idxr, 'Add', $params, 0x23);
            goto LABEL_133;
        }
        if ($prop == 0x24) {
            $params = [];
            $params[] = EvalSystem::digest($root, $bytes, $offset);//id

            $node = AstFactory::callNode($obj_idxr, 'Delete', $params, 0x24);
            goto LABEL_133;
        }
        if ($prop == 0x80){
            $params = [];
            $params[] = EvalSystem::digest($root, $bytes, $offset);//string

            $node = AstFactory::callNode($obj_idxr, 'LoadHTTPFile', $params, 0x80);
            goto LABEL_133;
        }

        // reset are all prop assign.
        $node2 = EvalSystem::digest($root, $bytes, $offset);
        // $node = AstFactory::assignNode($obj_group_index, $node2);

        $propname = self::names[$prop]??''; 
        $propValType = self::valTypes[$prop]??PCodeReader::VAL_UNKNOWN;

        $obj_prop = AstFactory::propNode($obj_idxr, $prop, $propname, $propValType);
        $node = AstFactory::assignNode($obj_prop, $node2);

//         if ($prop <= 0x32) {
//             // if ($prop == 0x32) {

//             // }
//             // else {
//                 switch ($prop) {
//                     case 0x10:
//                         break;
//                     case 0x11:
//                         break;
//                     case 0x20:
//                         break;
//                     case 0x22:
//                         break;
//                     case 0x23:
//                         break;
//                     case 0x24:
//                         break;
//                     case 0x25:
//                         break;
//                     case 0x26:
//                         break;
//                     case 0x27:
//                         break;
//                     case 0x28:
//                         break;
//                     case 0x30:
//                         break;
//                     case 0x31:
//                         break;
//                     case 0x32:
//                         break;
//                     default:
//                         goto LABEL_131;
//                 }
//             // }
//             goto LABEL_132;
//         }

//         if ($prop <= 0x46) {
//             if ($prop != 0x46) {
//                 switch ($prop) {
//                     case 0x33:
//                         break;
//                     case 0x34:
//                         break;
//                     case 0x35:
//                         break;
//                     case 0x36:
//                         break;
//                     case 0x37:
//                         break;
//                     default:
//                         goto LABEL_131;
//                 }
//             }
//             goto LABEL_132;
//         }

//         if ($prop < 0x80) {
//             if ($prop == 0x90) {

//             }
//             else {
//                 if ($prop != 0x91)
//                     goto LABEL_131;
//                 // 0x91..
//             }
//         }
//         else {
//             if ($prop != 0x80) {
//                 if ($prop == 0x47) {

//                 }
//                 else {
//                     if ($prop != 0x48) {
// LABEL_131:
//                         goto LABEL_132;
//                     }
//                     // 0x48

//                 }
// LABEL_132:
//                 goto LABEL_133;
//             }
//             // 0x80

//         }
LABEL_133:

        return $node;
    }

}
