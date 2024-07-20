<?php

declare(strict_types=1);

namespace Digital;

Class DigiLocaResource {

    const CastCateNames = [
        0 => 'ModelCast',
        1 => 'TextureCast',
        2 => 'BitmapCast',
        3 => 'TextCast',
        4 => 'WaveCast',
        5 => 'MIDICast',
        6 => 'ScriptCast',
        7 => 'CameraCast',
        8 => 'LightCast',
        0xb => 'Sound3DCast',//not exists in script
        0xd => 'EarCast',//not exists in script
    ];

    protected $casts;
    
    protected $scores;

    /**
     * create summary of Casts, remove useless info.
     * @return array
     */
    protected function summaryCasts($rawCasts) : array {
        $casts = [];
        foreach ($rawCasts as $cast_cate => $_casts) {
            foreach ($_casts as $_cast) {
                $_ = [];
                $_id = $_['id'] = $_cast['id'];
                $_['name'] = $_cast['title'][1];
                if (!empty($_cast['title'][3])) {
                    $_['file'] = $_cast['title'][3];
                }
                $_['offset'] = $_cast['soffset'];
                $_['length'] = $_cast['eoffset'] - $_cast['soffset'] + 1;
                if ($cast_cate == 0)
                    $_['Group'] = $_cast['body'][CastModel::GROUP];

                $cate_name = static::CastCateNames[$cast_cate];

                // resource Id must be used.
                $casts[$cate_name][$_id] = $_;
            }
        }
        return $casts;
    }

    /**
     * create summary of scores, remove useless info.
     * scores comes after x30, so sub x30's length.
     * @return array
     */
    protected function summaryScores($rawScores, $x30_length) : array {
        $scores = [];
        foreach ($rawScores as $id => $_scores) {
            $_ = [];

            $_['name'] = $_scores['name'];
            $_['label'] = $_scores['label'];

            $tracks = [];
            foreach ($_scores['tracks'] as $trid => $_track) {
                $track = [];
                $castType = $_track['castType'];
                $track['castType'] = $castType;
                if ($castType == 6) {
                    $track['castIds'] = $_track['castIds'];
                }
                if (!empty($_track[64])) {
                    $track['alias'] = $_track[64];
                }
                $tracks[$trid] = $track;
            }
            $_['tracks'] = $tracks;

            $_['offset'] = $_scores['soffset'] - $x30_length;
            $_['length'] = $_scores['eoffset'] - $_scores['soffset'] + 1;

            $scores[$id] = $_;
        }

        return $scores;
    }
    
    // using [xxx.x, xxx.x] to search casts
    public function searchCast($paths) {
        $resource = $this->casts;
        foreach($paths as $path) {
            [$castcate, $resId] = explode('.', $path);
            if ($resId == '?') return null;

            if (!isset($resource[$castcate][$resId])) return '';
            $resource = $resource[$castcate][$resId];
        }

        return $resource['name'];
    }

    public function searchTexture($textureId) {
        $scores = $this->casts;

        $t = self::CastCateNames[CAST_TEXTURE];
        if (!isset($scores[$t][$textureId])) return '';

        $resource = $scores[$t][$textureId];

        return $resource['name'];
    }

    // using [xxx.x:x] to search scores
    public function searchScoreTrack($paths) {
        $name = null;
        $name2 = null;
        $scores = $this->scores;

        [$castcate, $resIds] = explode('.', $paths[0]);
        [$resId, $resId2] = explode(':', $resIds);
        if ($resId == '?' && $resId2 == '?') {
            // do nothing.
        } else if ($resId2 == '?') {
            $name = $resId !== '0' ? $scores[$resId]['name'] : '0';
        } else {
            $name = $resId !== '0' ? $scores[$resId]['name'] : '0';

            $score = $scores[$resId];
            if (isset($score['tracks'][$resId2]['alias']))
                $name2 = $score['tracks'][$resId2]['alias'];
        }
        return [$name, $name2];
    }

    public function searchScoreLabel($paths) {
        $name = null;
        $name2 = null;
        $scores = $this->scores;

        [$nouse, $resIds] = explode('.', $paths[0]);
        [$resId, $resId2] = explode(':', $resIds);
        if ($resId == '?' && $resId2 == '?') {
            // do nothing.
        } else if ($resId2 == '?') {
            $name = $resId !== '0' ? $scores[$resId]['name'] : '0';
        } else {
            $name = $resId !== '0' ? $scores[$resId]['name'] : '0';

            $score = $scores[$resId];
            // cycle..
            if (!empty($score['label'])) {
                foreach($score['label'] as $label) {
                    if ($label['id'] == $resId2) {
                        $name2 = $label['name'];
                    }
                }
            }
        }
        return [$name, $name2];
    }

}