<?php

declare(strict_types=1);

namespace Digital;

use Digital\Ast\AstRoot;
use Digital\IRCore\IRCore;
use Digital\PCode\PCodeDebugger;
use Digital\PCode\PCodeReader;
use Digital\Script\ScriptDebugger;
use Digital\Script\ScriptSnippet;
use Digital\Script\ScriptWriter;
use Exception;

Class DigiLoca extends DigiLocaResource {

    /**
     * create project folder.
     * better use a memory disk.
     */
    const PROJ_BASE = 'R:\\';

    const PROJ_JSON = 'lcproj.json';
    const PROJ_NOPCODE = 'nopcode.lcp';
    const PROJ_PCODE = 'pcode';

    const PROJ_SCRIPT = 'script.txt';

    const PROJ_SCRIPTS = 'script';

    const PROJ_SCRIPTBIN = 'script.bin';

    const PROJ_MERGED = 'merged.lcp';

    // protected PCodeReader $PCodeReader;
    protected IRCore $ircore;

    protected int $zlib1_offset = 0;
    protected int $zlib1_length = 0;

    protected int $x10_offset = 0;//casts
    protected int $x10_length = 0;

    protected int $x20_offset = 0;//scores
    protected int $x20_length = 0;

    protected int $x30_offset = 0;//pcode
    protected int $x30_length = 0;
    // protected $pcode;
    protected $hasPcode = false;
    protected $pcode_offset = 0;

    protected int $script_offset = 0;//scripts(inside casts)
    protected int $script_length = 0;

    // digi loca 3 for stylos already 2E
    protected int $version = 0;

    const DL_VERSION3 = 0x2E;

    public function compileProject(string $proj_file) {

    }

    public function decompilePlay(string $play_file) {
        if (!file_exists($play_file)) {
            throw new Exception($play_file . ' not found.');
        }

        $fp = null;

        $proj_name = explode('.', basename($play_file))[0];
        
        $proj_json_file = self::PROJ_BASE . $proj_name . '\\' . self::PROJ_JSON;
        // check if PROJ_BASE\\$proj_name\\lcproj.json exists.
        if (!file_exists($proj_json_file)) {
            try {
                $fp = fopen($play_file, 'r');

                $this->checkFile($fp);

                // restore resources. mark positions.
                $this->scanFile($fp);

                // decomposite into 
                // nopcode.lcp & pcode & lcproj.json
                $this->saveProj($fp, $proj_name);
            }
            finally {
                fclose($fp);
            }
        }

        $this->casts = null;
        $this->scores = null;

        // reload the project
        // only need PROJ_JSON & PROJ_PCODE
        $fp = null;
        try {
            $fp = fopen($proj_json_file, 'r');
            $proj_json = fread($fp, filesize($proj_json_file));

            $proj = json_decode($proj_json, true);
            $this->zlib1_offset = $proj['zlib_offset'];
            $this->zlib1_length = $proj['zlib_length'];
            $this->x10_offset = $proj['x10_offset'];
            $this->x10_length = $proj['x10_length'];
            $this->x20_offset = $proj['x20_offset'];
            $this->x20_length = $proj['x20_length'];
            $this->casts = $proj['casts'];
            $this->scores = $proj['scores'];
        }
        finally {
            fclose($fp);
        }

        $pcode_file = self::PROJ_BASE . $proj_name . '\\' . self::PROJ_PCODE;
        $this->readPcode($pcode_file);

        // build cast/scores into map.. so ScriptWrite can use.
        $proj_json_file = self::PROJ_BASE . $proj_name . '\\' . self::PROJ_JSON;
        $this->reloadProjectJson($proj_json_file);

        // fillup project infos in general.
        $this->valueInterpretation();

        // decompile pcode -> scripts
        $script_folder = self::PROJ_BASE . $proj_name . '\\' . self::PROJ_SCRIPTS;
        $this->writeScriptBin($script_folder, null);

        // merge nopcode.lcp + scripts => merged.lcp
        // or rewrite merged.lcp
        $this->remergeLcpAndScripts(self::PROJ_BASE . $proj_name);
    }

    /**
     * @param resource $fp
     */
    private function checkFile($fp) {
        $fheader = fread($fp, 11);
        $unknown1 = freadu4($fp);//r_u4( fread($fp, 4), 0 );
        $unknown2 = freadu4($fp);//r_u4( fread($fp, 4), 0 );
        $this->version = freadu4($fp);//r_u4( fread($fp, 4), 0 );   
        if ($this->version >= self::DL_VERSION3) {
            $unknown = freadu1($fp);  // should be 0x88?
        }

        if ($fheader == 'DIGILOCAPLY' || $fheader == 'DIGILOCAPRJ') {
        } else {
            throw new Exception('input file header not match.');
        }

        $flagByte = freadu1($fp);//r_u1( fread($fp, 1), 0 ); // mustbe 0.
        if ($flagByte != 0x0) {
            throw new Exception('file format unknown.');
        }

        $this->zlib1_offset = ftell($fp) - 1;
        DigiLocaReader::readZlib1($fp);
        $this->zlib1_length = ftell($fp) - $this->zlib1_offset;
        if ($this->version >= self::DL_VERSION3) {
            $unknown = freadu1($fp);  // should be 0x89?
        }
    }
    
    /**
     * @param resource $fp
     */
    private function scanFile($fp) {
        $rawcasts = null;
        $rawscores = null;

        $loop = true;
        while ($loop) {
            $flag = freadu1($fp);//r_u1 ( fread($fp,1), 0 );
            if ($flag == 0x10) {
                $this->x10_offset = ftell($fp) - 1;
                $rawcasts = DigiLocaReader::readX10($fp);
                $this->x10_length = ftell($fp) - $this->x10_offset;
                // if ($this->version >= self::DL_VERSION3) {
                //     $unknown = freadu1($fp);  // should be 0x8A?
                // }
            }
            else if ($flag == 0x20) {
                $this->x20_offset = ftell($fp) - 1;
                $rawscores = DigiLocaReader::readX20($fp);
                $this->x20_length = ftell($fp) - $this->x20_offset;
                // if ($this->version >= self::DL_VERSION3) {
                //     $unknown = freadu1($fp);  // should be 0x8B?
                // }
            }
            else if ($flag == 0x30) {
                $this->x30_offset = ftell($fp) - 1;
                /*$this->pcode = */DigiLocaReader::readX30($fp);
                $this->hasPcode = true;
                $this->x30_length = ftell($fp) - $this->x30_offset;
            }
            else if ($flag == 0xFF) {
                $loop = false;
                break;
            }
            // they add many flags after Version 0x2B..
            else if ($flag == 0xF1) {
                $str_f1 = freadstr($fp);
            }
            else if ($flag > 0x93 && $flag <= 0xED) {
                // 0x93 read nothing.
                $unk_93_ed = freadu4($fp);
            }
            else if ($flag >= 0x88 && $flag <= 0x92) {
                // seems read nothing..
                // but 0x88,0x89,0x8A,0x8B are some flags.
            }
            else if ($flag == 0x18) {
                throw new Exception('0x18 appares at scanFile..');
            }
            else {
                // don't know what it is..
                // appear at LD_VERSION3..
                // $unknown = fread($fp, $flag + 3); //..
            }
        }

        if ($rawcasts !== null) {
            $this->casts = $this->summaryCasts($rawcasts);
        }
        if ($rawscores !== null) {
            $this->scores = $this->summaryScores($rawscores, $this->x30_length);
        }
    }

    /**
     * @param resource $fp
     */
    private function saveProj($fp, $proj_name) {
        $proj_folder = self::PROJ_BASE . $proj_name;
        if (!file_exists($proj_folder)) {
            mkdir($proj_folder, 0, true);
        }

        try {
            $lcproj_json_file = fopen($proj_folder.'\\'.self::PROJ_JSON, 'w');
            if (fwrite($lcproj_json_file, 
                    json_encode($this->makeLcpInfo(), 
                        JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) === FALSE) {
                throw new Exception("failed write proj json.");
            }
        } finally {
            fclose($lcproj_json_file);
        }

        try {
            $nopcode_file = fopen($proj_folder.'\\'.self::PROJ_NOPCODE, 'w');
            if (fwrite($nopcode_file, 
                "DIGILOCAPRJ\x00\x00\x00\x00\x00\x00\x00\x00\x29\x00\x00\x00") === FALSE) {
                throw new Exception("failed write nopcode.");
            }

            // write header
            fsectcopy($nopcode_file, $fp, $this->zlib1_offset, $this->zlib1_length);

            // write 0x10
            fsectcopy($nopcode_file, $fp, $this->x10_offset, $this->x10_length);

            // write 0x20
            fsectcopy($nopcode_file, $fp, $this->x20_offset, $this->x20_length);

            if (fwrite($nopcode_file, 
                "\xFF") === FALSE) {
                throw new Exception("failed write nopcode final FF.");
            }

        } finally {
            fclose($nopcode_file);
        }

        if ($this->hasPcode) {
            try {
                $pcode_file = fopen($proj_folder.'\\'.self::PROJ_PCODE, 'w');
                // if (fwrite($pcode_file, $this->pcode) === FALSE) {
                //     echo "failed write pcode.";
                // }
                // write 0x30
                fsectcopy($pcode_file, $fp, $this->x30_offset + 5, $this->x30_length - 5);
            } finally {
                fclose($pcode_file);
            }
        }
    }

    private function makeLcpInfo() {
        $lcp_info = [];

        $lcp_info['zlib_offset'] = $this->zlib1_offset;
        $lcp_info['zlib_length'] = $this->zlib1_length;

        $lcp_info['x10_offset'] = $this->x10_offset;
        $lcp_info['x10_length'] = $this->x10_length;

        $lcp_info['x20_offset'] = $this->x20_offset - $this->x30_length;
        $lcp_info['x20_length'] = $this->x20_length;

        $lcp_info['casts'] = $this->casts;//$this->summaryCasts();

        $lcp_info['scores'] = $this->scores; //$this->summaryScores($this->x30_length);

        $lcp_info['version'] = $this->version;

        return $lcp_info;
    }

    private function setLcpInfo($lcp_info) {
        $this->zlib1_offset = $lcp_info['zlib_offset'];
        $this->zlib1_length = $lcp_info['zlib_length'];

        $this->x10_offset = $lcp_info['x10_offset'];
        $this->x10_length = $lcp_info['x10_length'];

        $this->x20_offset = $lcp_info['x20_offset'];
        $this->x20_length = $lcp_info['x20_length'];
        
        $this->casts = $lcp_info['casts'];
        $this->scores = $lcp_info['scores'];

        $this->version = $lcp_info['version'];
    }

    // read pcode，
    // gene IRCore，and it's codes base_offset(0 or 5)
    public function readPcode(string $pcode_file, $debugger = null) {
        if (!file_exists($pcode_file)) {
            throw new Exception($pcode_file . ' not found.');
        }

        try {
            $fsize = filesize($pcode_file);
            $fp = fopen($pcode_file, 'r');

            // read all the bytes into PCodeReader.(mem consuming...)
            // change. read pcode block and gene ircore. discard pcode block.
            $this->pcode_offset = PCodeReader::predecode($fp, $fsize);
            $this->ircore = PCodeReader::makeIRCore(fread($fp, $fsize - $this->pcode_offset), $debugger);
        }
        finally {
            fclose($fp);
        }
    }

    /**
     * display the ast as xml
     * only for debug..
     */
    public function playWithPcode(string $pcode_file) {
        $procedureName = '';
        // $procedureName = '関節制御.ENTERFRAME';
        $print_all = false;
        if (empty($procedureName)) {
            $print_all = true;
        }

        $debugger = new PCodeDebugger();
        $debugger->on_iterating_procedure = function (AstRoot $root, &$should_digest) use ($procedureName) {
            if (empty($procedureName)) {
                $should_digest = true;
            } else {
                $should_digest = strcasecmp($root->getScriptName().'.'.$root->getName(), $procedureName) == 0;
            }
        };
        
        $pcode_offset = $this->pcode_offset;
        $debugger->on_before_digest_procedure = 
            function (AstRoot $root, $item_offset_end) use ($print_all, $pcode_offset) {
            $root->debug_print = !$print_all;
            print($root->getName().' at '.
                cmdhex($root->getBaseOffset()+$pcode_offset).'-'.
                cmdhex($item_offset_end-1+$pcode_offset));
        };

        $debugger->on_after_digest_procedure = function (AstRoot $root) use ($print_all) {
            // if (!$print_all) {
                // $root->printNodesXML();
                // print(PHP_EOL);
            // }
        };

        $debugger->on_iterating_walking = function (AstRoot $root, &$should_walk) use ($procedureName) {
            if (empty($procedureName)) {
                $should_walk = true;
            } else {
                $should_walk = strcasecmp($root->getName(), $procedureName) == 0;
            }
        };

        $debugger->on_final_iterating = function (AstRoot $root) use ($pcode_offset) {
            print($root->getName().' at '.
                cmdhex($root->getBaseOffset()+$pcode_offset).'-'.PHP_EOL);
            $root->printNodesXML();
        };

        $this->readPcode($pcode_file, $debugger);
    }

    public function reloadProjectJson(string $proj_json_file) {
        if (!file_exists($proj_json_file)) {
            throw new Exception($proj_json_file . ' not found.');
        }

        try {
            $fsize = filesize($proj_json_file);
            $fp = fopen($proj_json_file, 'r');

            $proj_json_str = fread($fp, $fsize);
            $proj_json = json_decode($proj_json_str, true);

            $this->setLcpInfo($proj_json);
        }
        finally {
            fclose($fp);
        }
    }

    private function valueInterpretation($debugger = null) {
        ScriptWriter::valueInterpretation($this, $this->ircore, $debugger);
    }

    /**
     * write final bin，using temp files to append data= =
     * those temp files can be also accessed by better editors.
     * and will be packed into lcps.
     */
    public function writeScriptBin($script_folder, $debugger = null) {
        if (!file_exists($script_folder)) {
            mkdir($script_folder, 644, true);
        }
        
        $script_file_list_file = $script_folder . '\\script.json';
        if (!file_exists($script_file_list_file)) {

            // remove all files - -`
            dirclear($script_folder);

            // scriptId -> scriptName
            $script_name_map = ScriptWriter::writeScript($this->ircore, function($scriptId, $scriptName, ScriptSnippet $ss) 
                use ($script_folder) {

                $file_name = $script_folder . '\\script.' . $scriptId . '.pas';

                try {
                    $fp = fopen($file_name, 'a');

                    // if (fwrite($fp, '// (' . $scriptId . ') ' . $scriptName . PHP_EOL) === FALSE) {
                    //     throw new Exception("failed write plant script.txt.");
                    // }

                    if (fwrite($fp, ScriptWriter::writePlainScript(0, $ss) . PHP_EOL) === FALSE) {
                        throw new Exception("failed to write script.txt.");
                    }
                } finally {
                    fclose($fp);
                }
            }, null);

            $script_file_list = [];
            foreach ($script_name_map as $scriptId => $scriptName) {
                $script_file_list[] = [
                    'id' => $scriptId,
                    // this is bad..
                    'name' => str_j2u($scriptName),
                ];
            }

            try {
                $fp_sfmf = fopen($script_file_list_file, 'w');
                if (fwrite($fp_sfmf, 
                        json_encode($script_file_list, 
                            JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) === FALSE) {
                    throw new Exception("failed to write script map json.");
                }
            } finally {
                fclose($fp_sfmf);
            }
        } else {
            try {
                $fsize = filesize($script_file_list_file);
                $fp_sfmf = fopen($script_file_list_file, 'r');
                $script_file_list_str = fread($fp_sfmf, $fsize);
                $script_file_list = json_decode($script_file_list_str, true);
            } finally {
                fclose($fp_sfmf);
            }
        }

        $fpw_name = $script_folder . '.bin';
        $fpw = fopen($fpw_name, 'w');

        try {
            foreach ($script_file_list as $script) {
                $scriptId = $script['id'];
                // this is bad..
                $scriptName = str_u2j($script['name']);

                $file_name = $script_folder . '\\script.' . $scriptId . '.pas';

                try {
                    $fp = fopen($file_name, 'r');

                    $flines = [];
                    while (($line = fgets($fp)) !== false) {  
                        $flines[] = rtrim($line);
                    }

                    $bytes = DigiLocaWriter::writeScript($scriptId, $scriptName, $flines);

                    fwrite($fpw, $bytes);
                } finally {
                    fclose($fp);
                }
            }
        } finally {
            fclose($fpw);
        }
    }

    // write scripts in plain text mode..
    public function writeScript($script_file, $debugger = null) {
        try {
            $fp = fopen($script_file, 'w');
            ScriptWriter::writeScript($this->ircore, function($scriptId, $scriptName, ScriptSnippet $ss) use ($fp) {

                // print '// (' . $scriptId . ') ' . $scriptName . PHP_EOL;
                // print ScriptWriter::writePlainScript(0, $ss) . PHP_EOL;

                if (fwrite($fp, '// (' . $scriptId . ') ' . $scriptName . PHP_EOL) === FALSE) {
                    throw new Exception("failed write plant script.txt.");
                }

                if (fwrite($fp, ScriptWriter::writePlainScript(0, $ss) . PHP_EOL) === FALSE) {
                    throw new Exception("failed write plant script.txt.");
                }

            }, $debugger);
        }
        finally {
            fclose($fp);
        }
    }

    /**
     * only for debug.
     */
    public function playWithIr(string $pcode_file) {
        $this->readPcode($pcode_file, null);

        $proj_dir_name = dirname($pcode_file);
        if ( strncasecmp($proj_dir_name, self::PROJ_BASE, strlen(self::PROJ_BASE)) !== 0) {
            throw new Exception('proj out side base');
        }

        $proj_name = substr($proj_dir_name, strlen(self::PROJ_BASE));
        
        $proj_json_file = self::PROJ_BASE . $proj_name . '\\' . self::PROJ_JSON;
        $this->reloadProjectJson( $proj_json_file );

        $procedureName = '';
        // $procedureName = 'スクリプト1964.EXITFRAME';

        $debugger = new ScriptDebugger();
        $debugger->on_iterating_procedure = function (AstRoot $root, &$should_digest) use ($procedureName) {
            if (empty($procedureName)) {
                $should_digest = true;
            } else {
                $should_digest = strcasecmp($root->getScriptName().'.'.$root->getName(), $procedureName) == 0;
            }
        };

        $this->valueInterpretation($debugger);

        $script_file = self::PROJ_BASE . $proj_name . '\\' . self::PROJ_SCRIPT;
        $this->writeScript($script_file, $debugger);
    }
    
    public function remergeLcpAndScripts($proj_folder) {
        $nopcode_file = $proj_folder.'\\'.self::PROJ_NOPCODE;
        $merged_file = $proj_folder.'\\'.self::PROJ_MERGED;

        $scriptbin_file = $proj_folder.'\\'.self::PROJ_SCRIPTBIN;

        if (!file_exists($scriptbin_file)) {
            throw new Exception('no script.bin under proj folder '.$proj_folder);
        }

        if (!file_exists($merged_file)) {
            // just copy for the first time.
            if (!copy($nopcode_file, $merged_file)) {  
                throw new Exception('copy nopcode to merged failed.');
            }
        }
        
        // abandon the nopcode.. it's a backup now.
        // because nopcode from lcr might be older.
        if (!copy($merged_file, $nopcode_file)) {  
            throw new Exception('copy merged to nopcode failed.');
        }

        $digiLoca = new DigiLoca();
        try {
            $fp = fopen($nopcode_file,'r');

            $digiLoca->checkFile($fp);
            $digiLoca->scanFile($fp);
        }
        catch (Exception $e) {
            fclose($fp);
        }

        if (empty($digiLoca->casts)) {
            throw new Exception('no cast in lcp, it\'s not a fair play.');
        }

        // skip read all ScriptCasts. then insert the scriptbin..
        // if there is no script cast, insert it after the last cast.
        // lets assume all script casts are in order.

        try {
            $fpsize = filesize($nopcode_file);
            $fp = fopen($nopcode_file,'r');

            try {
                $fp_remerge = fopen($merged_file, 'w');

                $ScriptCastKey = static::CastCateNames[CAST_SCRIPT];
                if (empty($digiLoca->casts[$ScriptCastKey])) {
                    $this->mergeScriptbinWithoutScripts($fp_remerge, $fp, $fpsize, $scriptbin_file, $digiLoca);
                }
                else {
                    $this->mergeScriptbinWithScripts($fp_remerge, $fp, $fpsize, $scriptbin_file, $digiLoca);
                }

            } catch (Exception $e) {
                fclose($fp_remerge);
            }

        } catch (Exception $e) {
            fclose($fp);
        }
    }

    private function mergeScriptbinWithoutScripts($fp_remerge, $fp, $fpsize, $scriptbin_file, DigiLoca $digiLoca) {
        $lastCastCate = end($digiLoca->casts);
        $lastCast = end($lastCastCate);

        $mergePoint = $lastCast['offset'] + $lastCast['length'];
        fsectcopy($fp_remerge, $fp, 0, $mergePoint);

        try {
            $fp_scriptbin = fopen($scriptbin_file,'r');
            fsectcopy($fp_remerge, $fp_scriptbin, 0, filesize($scriptbin_file));
        } finally {
            fclose($fp_scriptbin);
        }

        fsectcopy($fp_remerge, $fp, $mergePoint, $fpsize - $mergePoint);
    }

    private function mergeScriptbinWithScripts($fp_remerge, $fp, $fpsize, $scriptbin_file, DigiLoca $digiLoca) {
        $scriptCastKey = static::CastCateNames[CAST_SCRIPT];
        $scriptCasts = $digiLoca->casts[$scriptCastKey];
        $scriptCastArr = array_values($scriptCasts);

        $sect_start = 0;
        $sect_length = $scriptCastArr[0]['offset'];
        fsectcopy($fp_remerge, $fp, $sect_start, $sect_length);

        for ($i = 0; $i < count($scriptCastArr)-1; $i++) {
            $sect_start = $scriptCastArr[$i]['offset'] + $scriptCastArr[$i]['length'];
            $sect_length = $scriptCastArr[$i+1]['offset'] - $sect_start;
            if ($sect_length > 0) {
                fsectcopy($fp_remerge, $fp, $sect_start, $sect_length);
            }
        }

        try {
            $fp_scriptbin = fopen($scriptbin_file,'r');
            fsectcopy($fp_remerge, $fp_scriptbin, 0, filesize($scriptbin_file));
        } finally {
            fclose($fp_scriptbin);
        }

        $sect_start = $scriptCastArr[$i]['offset'] + $scriptCastArr[$i]['length'];
        fsectcopy($fp_remerge, $fp, $sect_start, $fpsize - $sect_start);
    }
}
