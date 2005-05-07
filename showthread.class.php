<?php
/**
 * スレッドを表示する クラス
 */
class ShowThread{

    var $thread; // スレッドオブジェクト
    
    var $str_to_link_regex; // リンクすべき文字列の正規表現
    
    var $url_handlers; // URLを処理する関数・メソッド名などを格納する配列
    
    /**
     * コンストラクタ
     */
    function ShowThread(&$aThread)
    {
        // スレッドオブジェクトを登録
        $this->thread = &$aThread;
        
        $this->str_to_link_regex = '{'
            . '(?P<link>(<[Aa] .+?>)(.*?)(</[Aa]>))' // リンク（PCREの特性上、必ずこのパターンを最初に試行する）
            . '|'
            . '(?:'
            .   '(?P<quote>' // 引用
            .       '((?:&gt;|＞){1,2} ?)' // 引用符
            .       '('
            .           '(?:[1-9]\\d{0,3})' // 1つ目の番号
            .           '(?:'
            .               '(?: ?(?:[,=]|、) ?[1-9]\\d{0,3})+' // 連続
            .               '|'
            .               '-(?:[1-9]\\d{0,3})?' // 範囲
            .           ')?'
            .       ')'
            .       '(?=\\D|$)'
            .   ')' // 引用ここまで
            . '|'
            .   '(?P<url>'
            .       '(ftp|h?t?tps?)://([0-9A-Za-z][\\w/\\#~:;.,?+=&%@!\\-]+?)' // URL
            .       '(?=[^\\w/\\#~:;.,?+=&%@!\\-]|$)' // 無効な文字か行末の先読み
            .   ')'
            . '|'
            .   '(?P<id>ID: ?([0-9A-Za-z/.+]{8,11})(?=[^0-9A-Za-z/.+]|$))' // ID（8,10桁 +PC/携帯識別フラグ）
            . ')'
            . '}';
        
        $this->url_handlers = array();
    }
    
    /**
     * DatをHTML変換して表示する
     */
    function datToHtml()
    {
        return '';
    }
    
    /**
     * DatをHTML変換したものを取得する
     */
    function getDatToHtml()
    {
        ob_start();
        $this->datToHtml();
        $html = ob_get_contents();
        ob_end_clean();
        
        return $html;
    }

    /**
     * BEプロファイルリンク変換
     */
    function replaceBeId($date_id)
    {
        global $_conf;
        
        $beid_replace = "<a href=\"http://be.2ch.net/test/p.php?i=\$1&u=d:http://{$this->thread->host}/{$this->thread->bbs}/{$this->thread->key}/\"{$_conf['ext_win_target_at']}>Lv.\$2</a>";
        
        //<BE:23457986:1>
        $be_match = '|<BE:(\d+):(\d+)>|i';
        if (preg_match($be_match, $date_id)) {
            $date_id = preg_replace($be_match, $beid_replace, $date_id);
        
        } else {
        
            $beid_replace = "<a href=\"http://be.2ch.net/test/p.php?i=\$1&u=d:http://{$this->thread->host}/{$this->thread->bbs}/{$this->thread->key}/\"{$_conf['ext_win_target_at']}>?\$2</a>";
            $date_id = preg_replace('|BE: ?(\d+)-(#*)|i', $beid_replace, $date_id);
        }
        
        return $date_id;
    }


    /**
     * NGあぼーんチェック
     */
    function ngAbornCheck($code, $resfield, $ic = FALSE)
    {
        global $ngaborns;
        
        $GLOBALS['debug'] && $GLOBALS['prof']->enterSection('ngAbornCheck()');
        
        $method = $ic ? 'stristr' : 'strstr';
        
        if (isset($ngaborns[$code]['data']) && is_array($ngaborns[$code]['data'])) {
            foreach ($ngaborns[$code]['data'] as $k => $v) {
                if (strlen($v['word']) == 0) {
                    continue;
                }
                
                /*
                if ($method($resfield, $v['word'])) {
                    $this->ngAbornUpdate($code, $k);
                    $GLOBALS['debug'] && $GLOBALS['prof']->leaveSection('ngAbornCheck()');
                    return $v['word'];
                } else {
                    continue;
                }
                */
                
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
                        $GLOBALS['debug'] && $GLOBALS['prof']->leaveSection('ngAbornCheck()');
                        return $v['word'];
                    //if ($re_method($re_pattern, $resfield, $matches)) {
                        //return htmlspecialchars($matches[0]);
                    }

                // 単純に文字列が含まれるかどうかをチェック
                } elseif ($method($resfield, $v['word'])) {
                    $this->ngAbornUpdate($code, $k);
                    $GLOBALS['debug'] && $GLOBALS['prof']->leaveSection('ngAbornCheck()');
                    return $v['word'];
                }
            }
        }
        $GLOBALS['debug'] && $GLOBALS['prof']->leaveSection('ngAbornCheck()');
        return false;
    }

    /**
     * 特定レスの透明あぼーんチェック
     */
    function abornResCheck($host, $bbs, $key, $resnum)
    {
        global $ngaborns;
        
        $target = $host . '/' . $bbs . '/' . $key . '/' . $resnum;
        
        if (isset($ngaborns[$code]['data']) && is_array($ngaborns['aborn_res']['data'])) {
            foreach ($ngaborns['aborn_res']['data'] as $k => $v) {
                if ($ngaborns['aborn_res']['data'][$k]['word'] == $target) {
                    $this->ngAbornUpdate('aborn_res', $k);
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * NG/あぼ～ん日時と回数を更新
     */
    function ngAbornUpdate($code, $k)
    {
        global $ngaborns;

        if (isset($ngaborns[$code]['data'][$k])) {
            $v =& $ngaborns[$code]['data'][$k];
            $v['lasttime'] = date('Y/m/d G:i'); // HIT時間を更新
            if (empty($v['hits'])) {
                $v['hits'] = 1; // 初HIT
            } else {
                $v['hits']++; // HIT回数を更新
            }
        }
    }

    /**
     * url_handlersに関数・メソッドを追加する
     *
     * url_handlersは最後にaddURLHandler()されたものから実行される
     */
    function addURLHandler($name, $handler)
    {
        ;
    }

    /**
     * url_handlersから関数・メソッドを削除する
     */
    function removeURLHandler($name)
    {
        ;
    }

}
?>
