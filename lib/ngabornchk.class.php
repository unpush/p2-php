<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// {{{ class NgAbornChk

/**
 * p2expack - NG/あぼーん判定をするクラス
 * ShowThreadクラスに継承させる
*/
class NgAbornChk
{
    // {{{ properties

    // スレッドオブジェクトから受け取るプロパティ
    var $host;
    var $bbs;
    var $key;
    var $rescount;

    // パース済みdatの配列
    var $pDatLines;

    // あぼーん対象のレス番号を保存する配列
    var $aborn_hit_cache;

    // NG対象のレス番号を保存する配列
    var $ng_hit_cache;

    // 連鎖あぼーん対象のレス番号を保存する配列
    var $chain_aborn_resnum;

    // 連鎖NG対象のレス番号を保存する配列
    var $chain_ng_resnum;

    // 連鎖判定をするレス番号のキャッシュ
    var $chain_pre_num;
    var $chain_pre_refs;

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ (PHP4 style)
     */
    function NgAbornChk(&$aThread, $pDatLines)
    {
        $this->__construct($aThread, $pDatLines);
    }

    /**
     * コンストラクタ (PHP5 style)
     */
    function __construct(&$aThread, $pDatLines)
    {
        $this->host = $aThread->host;
        $this->bbs  = $aThread->bbs;
        $this->key  = $aThread->key;
        $this->rescount = $aThread->rescount;
        $this->pDatLines    = $pDatLines;
        $this->aborn_hit_cache      = array();
        $this->ng_hit_cache         = array();
        $this->chain_aborn_resnum   = array();
        $this->chain_ng_resnum      = array();
    }

    // }}}
    // {{{ ngAbornPrepare()

    /**
     * NG/あぼーんチェック用にフォーマットする
     */
    function ngAbornPrepare($resnum, $fname)
    {
        switch ($fname) {
            case 'msg':
                $field = strip_tags($this->pDatLines[$resnum]['msg'], '<br>');
                break;
            case 'id':
                $field = $this->pDatLines[$resnum]['p_dateid']['id'];
                break;
            default:
                $field = strip_tags($this->pDatLines[$resnum][$fname]);
        }
        return $field;
    }

    // }}}
    // {{{ ngAbornWordCheck()

    /**
     * NG/あぼーんワードチェック
     */
    function ngAbornWordCheck($code, $resfield, $ic = FALSE)
    {
        global $ngaborns;

        $method = $ic ? 'stristr' : 'strstr';

        if (isset($ngaborns[$code]['data']) && is_array($ngaborns[$code]['data'])) {
            foreach ($ngaborns[$code]['data'] as $k => $v) {
                if (strlen($v['word']) == 0) {
                    continue;
                }
                // <関数:オプション>パターン 形式の行は正規表現として扱う
                // バイナリセーフでない（日本語でエラーが出ることがある）のでereg()系は使わない
                if (preg_match('/^<(mb_ereg|preg_match|regex)(:[imsxeADSUXu]+)?>(.+)$/', $v['word'], $re)) {
                    // "regex"のときは自動設定
                    if ($re[1] == 'regex') {
                        if (P2_MBREGEX_AVAILABLE) {
                            $re_method = 'mb_ereg';
                            $re_pattern = $re[3];
                        } else {
                            $re_method = 'preg_match';
                            $re_pattern = '/' . str_replace('/', '\\/', $re[3]) . '/';
                        }
                    } else {
                        $re_method = $re[1];
                        $re_pattern = $re[3];
                    }
                    // 大文字小文字を無視
                    if ($re[2] && strstr($re[2], 'i')) {
                        if ($re_method == 'preg_match') {
                            $re_pattern .= 'i';
                        } else {
                            $re_method .= 'i';
                        }
                    }
                    // マッチ
                    if ($re_method($re_pattern, $resfield)) {
                        $this->ngAbornUpdate($code, $k);
                        return $v['word'];
                    //if ($re_method($re_pattern, $resfield, $matches)) {
                        //return htmlspecialchars($matches[0]);
                    }

                // 単純に文字列が含まれるかどうかをチェック
                } elseif ($method($resfield, $v['word'])) {
                    $this->ngAbornUpdate($code, $k);
                    return $v['word'];
                }
            }
        }
        return FALSE;
    }

    // }}}
    // {{{ ngAbornUpdate()

    /**
     * NG/あぼーん日時と回数を更新
     */
    function ngAbornUpdate($code, $k)
    {
        global $ngaborns;

        if (isset($ngaborns[$code]['data'][$k])) {
            $v = &$ngaborns[$code]['data'][$k];
            $v['lasttime'] = date('Y/m/d G:i'); // HIT時間を更新
            if (empty($v['hits'])) {
                $v['hits'] = 1; // 初HIT
            } else {
                $v['hits']++;   // HIT回数を更新
            }
        }
    }

    // }}}
    // {{{ abornResCheck()

    /**
     * 特定レスの透明あぼーんチェック
     */
    function abornResCheck($host, $bbs, $key, $resnum)
    {
        global $ngaborns;

        $target = $host.'/'.$bbs.'/'.$key.'/'.$resnum;

        if (isset($ngaborns['aborn_res']['data']) && is_array($ngaborns['aborn_res']['data'])) {
            foreach ($ngaborns['aborn_res']['data'] as $k => $v) {
                if ($v['word'] == $target) {
                    $this->ngAbornUpdate('aborn_res', $k);
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    // }}}
    // {{{ abornCheck()

    /**
     * 全フィールドの透明あぼーんチェック
     */
    function abornCheck($resnum, $is_chain = FALSE)
    {
        global $_exconf;

        if (isset($this->aborn_hit_cache[$resnum])) {
            return $this->aborn_hit_cache[$resnum];
        } elseif (!isset($this->pDatLines[$resnum])) {
            return $this->abornResultCache($resnum, FALSE);
        }

        // {{{ 決め打ちあぼーんチェック

        if ($this->abornResCheck($this->host, $this->bbs, $this->key, $resnum)) {
            $this->chain_aborn_resnum[] = $resnum;
            return $this->abornResultCache($resnum, 'res');
        }

        // }}}
        // {{{ 行数あぼーんチェック

        // これには連鎖させない
        if (!$is_chain && $_exconf['aborn']['break_aborn'] && !$_exconf['aborn']['break_aborn_ng'] &&
            $this->pDatLines[$resnum]['lines'] > $_exconf['aborn']['break_aborn']
        ) {
            //$this->chain_aborn_resnum[] = $resnum;    // 行数オーバーは連鎖対象にしない
            //return $this->abornResultCache($resnum, 'lines'); // 再帰時にあぼーんされないよう、結果をキャッシュしない
            return 'lines';
        }

        // }}}
        // {{{ キーワードあぼーんチェック

        $fields = array('name', 'mail', 'id', 'msg');
        foreach ($fields as $fname) {
            if ($this->ngAbornWordCheck('aborn_'.$fname, $this->ngAbornPrepare($resnum, $fname)) !== FALSE) {
                $this->chain_aborn_resnum[] = $resnum;
                return $this->abornResultCache($resnum, $fname);
            }
        }

        // }}}

        if (!$_exconf['aborn']['chain_aborn']) {
            return $this->abornResultCache($resnum, FALSE);
        }

        // {{{ 連鎖あぼーんチェック

        $chain_aborn = FALSE;
        $chain_found = array();
        $refs = $this->getChainNums($resnum);   // 参照している番号で、自分より小さいもの
        if (!$refs) {
            return $this->abornResultCache($resnum, FALSE);
        }

        // 連鎖対象に登録されていないかチェック
        if ($chain_found = array_intersect($refs, $this->chain_aborn_resnum)) {
            $chain_aborn = TRUE;
        } else {
            // 各参照レス番号を再帰的にチェック
            foreach ($refs as $ref) {
                // $refsはソートされているので自分以上の番号がくると、そこで終了
                // ※getChainNums()でフィルタリングするのでこの判定は不要
                /*if ($ref >= $resnum) {
                    break;
                }*/
                // 無限再帰チェック（再帰時、$is_chainは元のレス番号）
                if ($ref == $is_chain) {
                    continue;
                }
                if ($this->abornCheck($ref, $resnum)) {
                    $chain_aborn = TRUE;
                    $chain_found[] = $ref;
                    break;
                }
            }
        }

        // 登録
        if ($chain_aborn) {
            // 連鎖あぼーんをNG扱いにするとき
            if ($_exconf['aborn']['chain_aborn_ng']) {
                // 遡って連鎖の対象とするとき
                if ($_exconf['aborn']['chain_ng'] == 2) {
                    $this->chain_ng_resnum[] = $resnum;
                }
                // これによって、引き続いてNGチェックしたときに「あぼーんレスにレス」とされる
                $this->ngResultCache($resnum, array('aborn' => implode(',', $chain_found)));
                // あぼーんではないのでFALSEを登録
                return $this->abornResultCache($resnum, FALSE);
            }
            // 遡って連鎖の対象とするとき
            if ($_exconf['aborn']['chain_aborn'] == 2) {
                $this->chain_aborn_resnum[] = $resnum;
            }
            return $this->abornResultCache($resnum, 'chain');
        }

        // }}}

        return $this->abornResultCache($resnum, FALSE);
    }

    // }}}
    // {{{ ngCheck()

    /**
     * 全フィールドのNGチェック
     */
    function ngCheck($resnum, $is_chain = FALSE)
    {
        global $_exconf;

        if (isset($this->ng_hit_cache[$resnum])) {
            return $this->ng_hit_cache[$resnum];
        } elseif (!isset($this->pDatLines[$resnum])) {
            return $this->ngResultCache($resnum, array());
        }

        $ng_fields = array();
        $ng_only_line = FALSE;

        // {{{ 行数NGチェック

        // これには連鎖させない
        if (!$is_chain && $_exconf['aborn']['break_aborn'] && $_exconf['aborn']['break_aborn_ng'] &&
            $this->pDatLines[$resnum]['lines'] > $_exconf['aborn']['break_aborn']
        ) {
            //$this->chain_aborn_resnum[] = $resnum;    // 行数オーバーは連鎖対象にしない
            $ng_fields['lines'] = $this->pDatLines[$resnum]['lines'];
            $ng_only_line = TRUE;
        }

        // }}}
        // {{{ キーワードNGチェック

        $fields = array('name', 'mail', 'id', 'msg');
        foreach ($fields as $fname) {
            if (($found = $this->ngAbornWordCheck('ng_'.$fname, $this->ngAbornPrepare($resnum, $fname))) !== FALSE) {
                $this->chain_ng_resnum[] = $resnum;
                $ng_fields[$fname] = htmlspecialchars($found);
                $ng_only_line = FALSE;
            }
        }

        // }}}

        if ($ng_fields || !$_exconf['aborn']['chain_ng']) {
            if ($ng_only_line) {
                //$this->ngResultCache($resnum, array());   // 再帰時にNGとされないよう、結果をキャッシュしない
                return $ng_fields;
            }
            return $this->ngResultCache($resnum, $ng_fields);
        }

        // {{{ 連鎖NGチェック

        $chain_ng = FALSE;
        $chain_found = array();
        $refs = $this->getChainNums($resnum);   // 参照している番号で、自分より小さいもの
        if (!$refs) {
            return $this->ngResultCache($resnum, array());
        }

        // 連鎖対象に登録されていないかチェック
        if ($chain_found = array_intersect($refs, $this->chain_ng_resnum)) {
            $chain_ng = TRUE;
        } else {
            // 各参照レス番号を再帰的にチェック
            foreach ($refs as $ref) {
                // $refsはソートされているので自分以上の番号がくると、そこで終了
                // ※getChainNums()でフィルタリングするのでこの判定は不要
                /*if ($ref >= $resnum) {
                    break;
                }*/
                // 無限再帰チェック（再帰時、$is_chainは元のレス番号）
                if ($ref == $is_chain) {
                    continue;
                }
                if ($this->ngCheck($ref, $resnum)) {
                    $chain_ng = TRUE;
                    $chain_found[] = $ref;
                    break;
                }
            }
        }


        // 登録
        if ($chain_ng) {
            // 遡って連鎖の対象とするとき
            if ($_exconf['aborn']['chain_ng'] == 2) {
                $this->chain_ng_resnum[] = $resnum;
            }
            $ng_fields['chain'] = implode(',', $chain_found);
        }

        // }}}

        return $this->ngResultCache($resnum, $ng_fields);
    }

    // }}}
    // {{{ ngAbornCheckAll()

    /**
     * 全レスのあぼーん/NGチェック
     */
    function ngAbornCheckAll()
    {
        for ($i = 1; $i <= $this->rescount; $i++) {
            $this->abornCheck($i) || $this->ngCheck($i);
        }
    }

    // }}}
    // {{{ abornResultCache()

    /**
     * 連鎖チェック用にあぼーんチェックの結果をキャッシュ
     */
    function abornResultCache($resnum, $result)
    {
        $this->aborn_hit_cache[$resnum] = $result;
        return $result;
    }

    // }}}
    // {{{ ngResultCache()

    /**
     * 連鎖チェック用にNGチェックの結果をキャッシュ
     */
    function ngResultCache($resnum, $result)
    {
        $this->ng_hit_cache[$resnum] = $result;
        return $result;
    }

    // }}}
    // {{{ getChainNums()

    /**
     * 連鎖チェック用に参照レス番号をグループ分けする
     *
     * 自身とそれより大きいレス番号は除く
     */
    function getChainNums($resnum)
    {
        global $_exconf;

        // 同じレスに対して連続であぼーん→NGチェックをかけたときの二度手間を省く
        if ($resnum == $this->chain_pre_num) {
            return $this->chain_pre_refs;
        }

        // >>n >>x,y,z から$resnumより小さいものを抽出
        $refs = array_filter($this->pDatLines[$resnum]['refs'], create_function('$n', "return (\$n < $resnum);"));

        // >>from-to を展開
        if ($_exconf['aborn']['chain_range'] && $this->pDatLines[$resnum]['refr']) {
            foreach ($this->pDatLines[$resnum]['refr'] as $refr) {
                // まずありえないけど、念のためチェック
                if (!isset($refr['from']) && !isset($refr['to'])) {
                    continue;
                }
                $x = (!empty($refr['from'])) ? max($refr['from'], 1) : 1;
                $y = (!empty($refr['to'])) ? min($refr['to'], $this->rescount) : $this->rescount;
                $z = min($y + 1, $resnum);
                for ($i = $x; $i < $z; $i++) {
                    $refs[] = $i;
                }
                // $refsが肥大するのを防ぐため、毎回実行
                $refs = array_unique($refs);
            }
        }

        sort($refs);

        $this->chain_pre_num = $resnum;
        $this->chain_pre_refs = $refs;
        return $refs;
    }

    // }}}
}

// }}}

?>
