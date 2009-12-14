<?php
$GLOBALS['_ngaborns_head_hits'] = 0;

/*
    p2 - NGあぼーんを操作するクラス
    スタティックメソッドで利用する
    
    $_ngaborns[$ngcode] => array(
        'file' =>
        'data' => array(
            array(
                'word' =>
                'hits' =>
                ...
            ),
            array(
                'word' =>
                'hits' =>
                ...
            ),
            ...
        ),
    )
*/
class NgAbornCtl
{
    /**
     * あぼーん&NGワード設定を読み込む
     * // 編集UIがついたので、設定ファイルは一枚のシリアライズデータでもよいところ
     *
     * @static
     * @access  public
     * @return  array
     */
    function loadNgAborns()
    {
        global $_ngaborns;
        
        $_ngaborns = array();

        // aborn_res だけマッチチェックの仕方が違う abornResCheck()
        $_ngaborns['aborn_res']  = NgAbornCtl::readNgAbornFromFile('p2_aborn_res.txt');
        $_ngaborns['aborn_name'] = NgAbornCtl::readNgAbornFromFile('p2_aborn_name.txt');
        $_ngaborns['aborn_mail'] = NgAbornCtl::readNgAbornFromFile('p2_aborn_mail.txt');
        $_ngaborns['aborn_msg']  = NgAbornCtl::readNgAbornFromFile('p2_aborn_msg.txt');
        $_ngaborns['aborn_id']   = NgAbornCtl::readNgAbornFromFile('p2_aborn_id.txt');
        $_ngaborns['ng_name']    = NgAbornCtl::readNgAbornFromFile('p2_ng_name.txt');
        $_ngaborns['ng_mail']    = NgAbornCtl::readNgAbornFromFile('p2_ng_mail.txt');
        $_ngaborns['ng_msg']     = NgAbornCtl::readNgAbornFromFile('p2_ng_msg.txt');
        $_ngaborns['ng_id']      = NgAbornCtl::readNgAbornFromFile('p2_ng_id.txt');

        $GLOBALS['_P2_NGABORN_LOADED'] = true;
        
        return $_ngaborns;
    }

    /**
     * データファイルからNGあぼーんデータを読み込む
     *
     * @static
     * @access  private
     * @return  array
     */
    function readNgAbornFromFile($filename)
    {
        global $_conf;

        $lines = array();
        $array['file'] = $_conf['pref_dir'] . '/' . $filename;
        if (file_exists($array['file']) and $lines = file($array['file'])) {
            foreach ($lines as $l) {
                $lar = explode("\t", trim($l));
                if (strlen($lar[0]) == 0) {
                    continue;
                }
                
                $ar = array(
                    'cond' => $lar[0], // 検索条件
                    'word' => $lar[0], // 対象文字列
                    'lasttime' => null, // 最後にHITした時間
                    'hits' => 0, // HIT回数
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
                    if ($matches[2] && strstr($matches[2], 'i')) {
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
                
                $array['data'][] = $ar;
            }
        }
        return $array;
    }
    
    /**
     * レスのあぼーんをまとめてチェックする（名前、メール、日付、メッセージ）
     *
     * @access  public
     * @return  string|false  マッチしたらマッチ文字列。マッチしなければfalse
     */
    function checkAborns($name, $mail, $date_id, $msg, $bbs, $ttitle_hc)
    {
        if (false !== ($match_word = NgAbornCtl::ngAbornCheck('aborn_name', strip_tags($name), $bbs, $ttitle_hc))) {
            return $match_word;
        }
        if (false !== ($match_word = NgAbornCtl::ngAbornCheck('aborn_mail', $mail, $bbs, $ttitle_hc))) {
            return $match_word;
        }
        if (false !== ($match_word = NgAbornCtl::ngAbornCheck('aborn_id', $date_id, $bbs, $ttitle_hc))) {
            return $match_word;
        }
        if (false !== ($match_word = NgAbornCtl::ngAbornCheck('aborn_msg', $msg, $bbs, $ttitle_hc))) {
            return $match_word;
        }
        return false;
    }
    
    /**
     * NGあぼーんをチェックする
     *
     * @access  public
     * @return  string|false  マッチしたらマッチ文字列。マッチしなければfalse
     */
    function ngAbornCheck($code, $subject, $bbs, $ttitle_hc)
    {
        global $_ngaborns;

        $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('ngAbornCheck()');
        
        $match_word = null;

        if (isset($_ngaborns[$code]['data']) && is_array($_ngaborns[$code]['data'])) {
            foreach ($_ngaborns[$code]['data'] as $k => $v) {
            
                if (strlen($v['word']) == 0) {
                    continue;
                }
                
                // 板チェック
                if ((strlen($bbs) > 0) and isset($v['bbs']) && in_array($bbs, $v['bbs']) == false) {
                    continue;
                }

                // タイトルチェック
                if ((strlen($ttitle_hc) > 0) and isset($v['title']) && stristr($ttitle_hc, $v['title']) === false) {
                    continue;
                }
                
                // ワードチェック
                // 正規表現
                if (!empty($v['regex'])) {
                    $re_method = $v['regex'];
                    /*if (@$re_method($v['word'], $subject, $matches)) {
                        NgAbornCtl::ngAbornUpdate($code, $k);
                        $match_word = htmlspecialchars($matches[0], ENT_QUOTES);
                        break;
                    }*/
                     if (@$re_method($v['word'], $subject)) {
                        NgAbornCtl::ngAbornUpdate($code, $k);
                        $match_word = $v['word'];
                        break;
                    }
               // 大文字小文字を無視(1)
                } elseif (!empty($v['ignorecase'])) {
                    if (stristr($subject, $v['word'])) {
                        NgAbornCtl::ngAbornUpdate($code, $k);
                        $match_word = $v['word'];
                        break;
                    }
                // 単純に文字列が含まれるかどうかをチェック
                } else {
                    if (strstr($subject, $v['word'])) {
                        NgAbornCtl::ngAbornUpdate($code, $k);
                        $match_word = $v['word'];
                        break;
                    }
                }
            }
        }
        
        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('ngAbornCheck()');
        
        return is_null($match_word) ? false : $match_word;
    }
    
    /**
     * 特定レスの透明あぼーんをチェックする
     *
     * @access  public
     * @return  boolean  マッチしたらtrue
     */
    function abornResCheck($host, $bbs, $key, $resnum)
    {
        global $_ngaborns;

        $target = $host . '/' . $bbs . '/' . $key . '/' . $resnum;
        
        if (isset($_ngaborns['aborn_res']['data']) && is_array($_ngaborns['aborn_res']['data'])) {
            foreach ($_ngaborns['aborn_res']['data'] as $k => $v) {
                if ($_ngaborns['aborn_res']['data'][$k]['word'] == $target) {
                    NgAbornCtl::ngAbornUpdate('aborn_res', $k);
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * NG/あぼ～ん日時と回数を更新
     *
     * @access  private
     * @return  void
     */
    function ngAbornUpdate($ngcode, $k)
    {
        global $_ngaborns;

        if (isset($_ngaborns[$ngcode]['data'][$k])) {
            $v =& $_ngaborns[$ngcode]['data'][$k];
            $v['lasttime'] = date('Y/m/d G:i'); // HIT時間を更新
            if (empty($v['hits'])) {
                $v['hits'] = 1; // 初HIT
            } else {
                $v['hits']++; // HIT回数を更新
            }
        }
        
        NgAbornCtl::countNgAbornsHits($ngcode);
    }
    
    /**
     * $GLOBALS['ngaborn_hits'] （HITの更新チェックに用意した） と、
     * $GLOBALS['_ngaborns_head_hits']（read_new のHTML id用に用意した）をカウント更新する
     *
     * @static
     * @public
     * @return  integer
     */
    function countNgAbornsHits($ngcode)
    {
        if (!isset($GLOBALS['ngaborns_hits'])) {
            NgAbornCtl::initNgAbornsHits();
        }
        
        if ($ngcode != 'ng_msg') {
            $GLOBALS['_ngaborns_head_hits']++;
        }
        
        return ++$GLOBALS['ngaborns_hits'][$ngcode];
    }
    
    /**
     * @access  private
     * @return  void
     */
    function initNgAbornsHits()
    {
        $GLOBALS['ngaborns_hits'] = array(
            'aborn_res'  => 0,
            'aborn_name' => 0,
            'aborn_mail' => 0,
            'aborn_msg'  => 0,
            'aborn_id'   => 0,
            'ng_name'    => 0,
            'ng_mail'    => 0,
            'ng_msg'     => 0,
            'ng_id'      => 0
        );
    }
    
    /**
     * あぼーん&NGワード設定を保存する
     *
     * @static
     * @access  public
     * @return  boolean
     */
    function saveNgAborns()
    {
        global $_ngaborns;
        global $_conf;
        
        // HITがなければ更新もなし
        if (empty($GLOBALS['ngaborns_hits'])) {
            return true;
        }
        
        // HITしたものだけ更新
        foreach ($GLOBALS['ngaborns_hits'] as $ngcode => $v) {
        
            // 設定データがないなら抜ける
            if (empty($_ngaborns[$ngcode]['data'])) {
                continue;
            }
            
            // 更新時間でソートする
            usort($_ngaborns[$ngcode]['data'], array('NgAbornCtl', 'cmpLastTime'));
        
            $cont = "";
            foreach ($_ngaborns[$ngcode]['data'] as $a_ngaborn) {
            
                // 必要ならここで古いデータはスキップ（削除）する
                if (!empty($a_ngaborn['lasttime']) && $_conf['ngaborn_daylimit']) {
                    
                    // 2007/03/12 データに '--' が入っているケースがあったので（時が経ち外せるようになれば外したい）
                    if ($a_ngaborn['lasttime'] != '--') {
                        if (strtotime($a_ngaborn['lasttime']) < time() - 60 * 60 * 24 * $_conf['ngaborn_daylimit']) {
                            continue;
                        }
                    }
                }
                
                if (empty($a_ngaborn['lasttime'])) {
                    $a_ngaborn['lasttime'] = date('Y/m/d G:i');
                }
                
                $cont .= $a_ngaborn['cond'] . "\t" . $a_ngaborn['lasttime'] . "\t" . $a_ngaborn['hits'] . "\n";
            }
            
            // 書き込む
            if (false === file_put_contents($_ngaborns[$ngcode]['file'], $cont, LOCK_EX)) {
                return false;
            }
        
        } // foreach
        
        return true;
    }

    /**
     * NGあぼーんHIT記録を更新時間でソートする
     *
     * @static
     * @access  private
     * @return  integer
     */
    function cmpLastTime($a, $b)
    {
        if (empty($a['lasttime']) || empty($b['lasttime'])) {
            return strcmp($a['lasttime'], $b['lasttime']);
        }
        return (strtotime($a['lasttime']) < strtotime($b['lasttime'])) ? 1 : -1;
    }

}

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
