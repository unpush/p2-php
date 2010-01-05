<?php
/**
 * rep2 - NGあぼーんを操作するクラス
 */

// {{{ GLOBALS

$GLOBALS['ngaborns_hits'] = array(
    'aborn_chain'   => 0,
    'aborn_freq'    => 0,
    'aborn_mail'    => 0,
    'aborn_id'      => 0,
    'aborn_msg'     => 0,
    'aborn_name'    => 0,
    'aborn_res'     => 0,
    'aborn_thread'  => 0,
    'ng_chain'      => 0,
    'ng_freq'       => 0,
    'ng_id'         => 0,
    'ng_mail'       => 0,
    'ng_msg'        => 0,
    'ng_name'       => 0,
);

// }}}
// {{{ NgAbornCtl

class NgAbornCtl
{
    // {{{ saveNgAborns()

    /**
     * あぼーん&NGワード設定を保存する
     *
     * @param void
     * @return void
     */
    static public function saveNgAborns()
    {
        global $ngaborns, $ngaborns_hits;
        global $_conf;

        $lasttime = date('Y/m/d G:i');
        if ($_conf['ngaborn_daylimit']) {
            $daylimit = time() - 60 * 60 * 24 * $_conf['ngaborn_daylimit'];
        } else {
            $daylimit = 0;
        }
        $errors = '';

        foreach ($ngaborns_hits as $code => $hits) {
            // ヒットしなかった場合でも1/100の確率で古いデータを削除するために処理を続ける
            if (!$hits && mt_rand(1, 100) < 100) {
                continue;
            }

            if (isset($ngaborns[$code]) && !empty($ngaborns[$code]['data'])) {

                // 更新時間でソートする
                usort($ngaborns[$code]['data'], array('NgAbornCtl', 'cmpLastTime'));

                $cont = '';
                foreach ($ngaborns[$code]['data'] as $a_ngaborn) {

                    if (empty($a_ngaborn['lasttime']) || $a_ngaborn['lasttime'] == '--') {
                        // 古いデータを削除する都合上、仮に現在の日時を付与
                        $a_ngaborn['lasttime'] = $lasttime;
                     } else {
                        // 必要ならここで古いデータはスキップ（削除）する
                        if ($daylimit > 0 && strtotime($a_ngaborn['lasttime']) < $daylimit) {
                            continue;
                        }
                    }

                    $cont .= sprintf("%s\t%s\t%d\n", $a_ngaborn['cond'], $a_ngaborn['lasttime'], $a_ngaborn['hits']);
                } // foreach

                /*
                echo "<pre>";
                echo $cont;
                echo "</pre>";
                */

                // 書き込む

                $fp = @fopen($ngaborns[$code]['file'], 'wb');
                if (!$fp) {
                    $errors .= "cannot write. ({$ngaborns[$code]['file']})\n";
                } else {
                    flock($fp, LOCK_EX);
                    fputs($fp, $cont);
                    flock($fp, LOCK_UN);
                    fclose($fp);
                }

            } // if

        } // foreach

        if ($errors !== '') {
            p2die('NGあぼーんファイルが更新できませんでした。', $errors);
        }
    }

    // }}}
    // {{{ saveAbornThreads()

    /**
     * あぼーんスレッド設定を保存する
     *
     * @param array $aborn_threads
     * @return void
     */
    static public function saveAbornThreads(array $aborn_threads)
    {
        if (array_key_exists('ngaborns', $GLOBALS)) {
            $orig_ngaborns = $GLOBALS['ngaborns'];
            $restore_ngaborns = true;
        } else {
            $restore_ngaborns = false;
        }

        $GLOBALS['ngaborns'] = array('aborn_thread' => $aborn_threads);
        self::saveNgAborns();

        if ($restore_ngaborns) {
            $GLOBALS['ngaborns'] = $orig_ngaborns;
        } else {
            unset($GLOBALS['ngaborns']);
        }
    }

    // }}}
    // {{{ cmpLastTime()

    /**
     * NGあぼーんHIT記録を更新時間でソートする
     */
    static public function cmpLastTime($a, $b)
    {
        if (empty($a['lasttime']) || empty($b['lasttime'])) {
            return strcmp($a['lasttime'], $b['lasttime']);
        }
        if ($a['lasttime'] == $b['lasttime']) {
            return $b['hits'] - $a['hits'];
        }
        return strtotime($b['lasttime']) - strtotime($a['lasttime']);
    }

    // }}}
    // {{{ loadNgAborns()

    /**
     * あぼーん&NGワード設定を読み込む
     *
     * @param void
     * @return array
     */
    static public function loadNgAborns()
    {
        $ngaborns = array();

        $ngaborns['aborn_res'] = self::_readNgAbornFromFile('p2_aborn_res.txt'); // これだけ少し性格が異なる
        $ngaborns['aborn_name'] = self::_readNgAbornFromFile('p2_aborn_name.txt');
        $ngaborns['aborn_mail'] = self::_readNgAbornFromFile('p2_aborn_mail.txt');
        $ngaborns['aborn_msg'] = self::_readNgAbornFromFile('p2_aborn_msg.txt');
        $ngaborns['aborn_id'] = self::_readNgAbornFromFile('p2_aborn_id.txt');
        $ngaborns['ng_name'] = self::_readNgAbornFromFile('p2_ng_name.txt');
        $ngaborns['ng_mail'] = self::_readNgAbornFromFile('p2_ng_mail.txt');
        $ngaborns['ng_msg'] = self::_readNgAbornFromFile('p2_ng_msg.txt');
        $ngaborns['ng_id'] = self::_readNgAbornFromFile('p2_ng_id.txt');

        return $ngaborns;
    }

    // }}}
    // {{{ loadAbornThreads()

    /**
     * あぼーんスレッド設定を読み込む
     *
     * @param void
     * @return array
     */
    static public function loadAbornThreads()
    {
        return self::_readNgAbornFromFile('p2_aborn_thread.txt');
    }

    // }}}
    // {{{ _readNgAbornFromFile()

    /**
     * readNgAbornFromFile
     */
    static protected function _readNgAbornFromFile($filename)
    {
        global $_conf;

        $file = $_conf['pref_dir'] . '/' . $filename;
        $data = array();

        if ($lines = FileCtl::file_read_lines($file)) {
            foreach ($lines as $l) {
                $lar = explode("\t", trim($l));
                if (strlen($lar[0]) == 0) {
                    continue;
                }
                $ar = array(
                    'cond' => $lar[0],      // 検索条件
                    'word' => $lar[0],      // 対象文字列
                    'lasttime' => null,     // 最後にHITした時間
                    'hits' => 0,            // HIT回数
                    'regex' => false,       // パターンマッチ関数
                    'ignorecase' => false,  // 大文字小文字を無視
                );
                isset($lar[1]) && $ar['lasttime'] = $lar[1];
                isset($lar[2]) && $ar['hits'] = (int) $lar[2];

                if ($filename == 'p2_aborn_res.txt') {
                    continue;
                }

                // 板縛り
                if (preg_match('!<bbs>(.+?)</bbs>!', $ar['word'], $matches)) {
                    $ar['bbs'] = explode(',', $matches[1]);
                }
                $ar['word'] = preg_replace('!<bbs>(.*)</bbs>!', '', $ar['word']);

                // タイトル縛り
                if (preg_match('!<title>(.+?)</title>!', $ar['word'], $matches)) {
                    $ar['title'] = $matches[1];
                }
                $ar['word'] = preg_replace('!<title>(.*)</title>!', '', $ar['word']);

                // 正規表現
                if (preg_match('/^<(mb_ereg|preg_match|regex)(:[imsxeADSUXu]+)?>(.+)$/', $ar['word'], $matches)) {
                    // マッチング関数とパターンを設定
                    if ($matches[1] == 'regex') {
                        if (P2_MBREGEX_AVAILABLE) {
                            $ar['regex'] = 'mb_ereg';
                            $ar['word'] = $matches[3];
                        } else {
                            $ar['regex'] = 'preg_match';
                            $ar['word'] = '/' . str_replace('/', '\\/', $matches[3]) . '/';
                        }
                    } else {
                        $ar['regex'] = $matches[1];
                        $ar['word'] = $matches[3];
                    }
                    // 大文字小文字を無視
                    if ($matches[2] && strpos($matches[2], 'i') !== false) {
                        if ($ar['regex'] == 'mb_ereg') {
                            $ar['regex'] = 'mb_eregi';
                        } else {
                            $ar['word'] .= 'i';
                        }
                    }
                // 大文字小文字を無視
                } elseif (preg_match('/^<i>(.+)$/', $ar['word'], $matches)) {
                    $ar['word'] = $matches[1];
                    $ar['ignorecase'] = true;
                }

                // 正規表現でないなら、エスケープされていない特殊文字をエスケープ
                /*if (!$ar['regex']) {
                    $ar['word'] = htmlspecialchars($ar['word'], ENT_COMPAT, 'Shift_JIS', false);
                }*/
                // 2chの仕様上、↑は期待通りの結果が得られないことが多いので、<>だけ実体参照にする
                if (!$ar['regex']) {
                    $ar['word'] = str_replace(array('<', '>'), array('&lt;', '&gt;'), $ar['word']);
                }

                $data[] = $ar;
            }
        }

        return array('file' => $file, 'data' => $data);
    }

    // }}}
}

// }}}

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
