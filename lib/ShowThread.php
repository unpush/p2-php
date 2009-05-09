<?php
require_once P2_LIB_DIR . '/NgAbornCtl.php';

/**
 * スレッドを表示する クラス
 */
class ShowThread
{
    var $thread; // スレッドオブジェクトの参照
    
    // リンクすべき文字列の正規表現
    var $str_to_link_regex;
    
    // 一つのレスにおけるリンク変換の制限回数（荒らし対策）
    var $str_to_link_limit = 30;
    
    // 上記の残り数カウンター。厳密な適用はしていない。とりあえず>>1,2,3,..対策のために。
    var $str_to_link_rest;
    
    // URLを処理する関数・メソッド名などを格納する配列（デフォルト）
    var $url_handlers       = array();

    // URLを処理する関数・メソッド名などを格納する配列（ユーザ定義、デフォルトのものより優先）
    var $user_url_handlers  = array();

    /**
     * @constructor
     */
    function ShowThread(&$Thread)
    {
        global $_conf;
        
        $this->str_to_link_regex = $this->buildStrToLinkRegex();
        
        $this->thread = &$Thread;
        
        if ($_conf['flex_idpopup']) {
            $this->setIdCountToThread();
            // $this->setBackwordResesToThread();
        }
        
        if (empty($GLOBALS['_P2_NGABORN_LOADED'])) {
            NgAbornCtl::loadNgAborns();
        }
    }
    
    /**
     * static
     * @access  public
     * @param   string  $pattern  ex)'/%full%/'
     * @return  string
     */
    function getAnchorRegex($pattern)
    {
        static $caches_ = array();

        if (!array_key_exists($pattern, $caches_)) {
            $caches_[$pattern] = strtr($pattern, ShowThread::getAnchorRegexParts());
            // 大差はないが compileMobile2chUriCallBack() のように preg_relplace_callback()してもいいかも。
        }
        return $caches_[$pattern];
    }

    /**
     * static
     * @access  private
     * @return  string
     */
    function getAnchorRegexParts()
    {
        static $cache_;
        
        if (isset($cache_)) {
            return $cache_;
        }
        
        $anchor = array();
        
        // アンカーの構成要素（正規表現パーツの配列）

        // 空白文字
        $anchor_space = '(?:[ ]|　)';
        //$anchor[' '] = '';

        // アンカー引用子 >>
        $anchor['prefix'] = "(?:&gt;|＞|&lt;|＜|〉|》|≫){1,2}{$anchor_space}*\.?";
        
        // 数字
        $anchor['a_digit'] = '(?:\\d|０|１|２|３|４|５|６|７|８|９)';
        /*
        $anchor[0] = '(?:0|０)';
        $anchor[1] = '(?:1|１)';
        $anchor[2] = '(?:2|２)';
        $anchor[3] = '(?:3|３)';
        $anchor[4] = '(?:4|４)';
        $anchor[5] = '(?:5|５)';
        $anchor[6] = '(?:6|６)';
        $anchor[7] = '(?:7|７)';
        $anchor[8] = '(?:8|８)';
        $anchor[9] = '(?:9|９)';
        */
        
        // 範囲指定子
        $anchor['range_delimiter'] = "(?:-|‐|\x81\\x5b)"; // ー
        
        // 列挙指定子
        $anchor['delimiter'] = "{$anchor_space}?(?:[,=+]|、|・|＝|，){$anchor_space}?";

        // あぼーん用アンカー引用子
        //$anchor['prefix_abon'] = "&gt;{1,2}{$anchor_space}?";

        // レス番号
        $anchor['a_num'] = sprintf('%s{1,4}', $anchor['a_digit']);
        
        // レス範囲
        $anchor['a_range'] = sprintf("%s(?:%s%s)?",
            $anchor['a_num'], $anchor['range_delimiter'], $anchor['a_num']
        );
        
        // レス範囲の列挙
        $anchor['ranges'] = sprintf('%s(?:%s%s)*(?!%s)',
            $anchor['a_range'], $anchor['delimiter'], $anchor['a_range'], $anchor['a_digit']
        );
        
        // レス番号の列挙
        $anchor['nums'] = sprintf("%s(?:%s%s)*(?!%s)",
            $anchor['a_num'], $anchor['delimiter'], $anchor['a_num'], $anchor['a_digit']
        );
        
        // アンカー全体
        $anchor['full'] = sprintf('(%s)(%s)', $anchor['prefix'], $anchor['ranges']);
        
        // getAnchorRegex() の strtr() 置換用にkeyを '%key%' に変換する
        foreach ($anchor as $k => $v) {
            $anchor['%' . $k . '%'] = $v;
            unset($anchor[$k]);
        }
        
        $cache_ = $anchor;
        
        return $cache_;
    }
    
    /**
     * @access  private
     * @return  string
     */
    function buildStrToLinkRegex()
    {
        return $str_to_link_regex = '{'
            . '(?P<link>(<[Aa] .+?>)(.*?)(</[Aa]>))' // リンク（PCREの特性上、必ずこのパターンを最初に試行する）
            . '|'
            . '(?:'
            .   '(?P<quote>' // 引用
            .       $this->getAnchorRegex('%full%')
            .   ')'
            . '|'
            .   '(?P<url>'
            .       '(ftp|h?ttps?|tps?)://([0-9A-Za-z][\\w!#%&+*,\\-./:;=?@\\[\\]^~]+)' // URL
            .   ')'
            . '|'
            .   '(?P<id>ID: ?([0-9A-Za-z/.+]{8,11})(?=[^0-9A-Za-z/.+]|$))' // ID（8,10桁 +PC/携帯識別フラグ）
            . ')'
            . '}';
    }
    
    /**
     * DatをHTML変換して表示する
     * （継承先クラスで実装）
     *
     * @access  public
     * @return  boolean
     */
    function datToHtml()
    {
    }
    
    /**
     * DatをHTML変換したものを取得する
     *
     * @access  public
     * @return  string
     */
    function getDatToHtml()
    {
        ob_start();
        $this->datToHtml();
        $html = ob_get_clean();
        
        return $html;
    }

    /**
     * BEプロファイルリンク変換
     *
     * @access  protected
     * @param   string     $data_id  2006/10/20(金) 11:46:08 ID:YS696rnVP BE:32616498-DIA(30003)
     * @param   integer    $i        レス番号
     * @return  string
     */
    function replaceBeId($date_id, $i)
    {
        global $_conf;
        
        // urlencodeしているとBE鯖が受け付けないみたい
        $u = "d:http://{$this->thread->host}/test/read.cgi/{$this->thread->bbs}/{$this->thread->key}/{$i}";
        
        // <BE:23457986:1>
        $be_match = '|<BE:(\d+):(\d+)>|i';
        if (preg_match($be_match, $date_id)) {
            $beid_replace = "<a href=\"http://be.2ch.net/test/p.php?i=\$1&u={$u}\"{$_conf['ext_win_target_at']}>Lv.\$2</a>";
            $date_id = preg_replace($be_match, $beid_replace, $date_id);

        // 2006/10/20(金) 11:46:08 ID:YS696rnVP BE:32616498-DIA(30003)
        } else {
            $beid_replace = "<a href=\"http://be.2ch.net/test/p.php?i=\$1&u={$u}\"{$_conf['ext_win_target_at']}>?\$2</a>";
            $date_id = preg_replace('|BE: ?(\d+)-(#*)|i', $beid_replace, $date_id);
        }

        return $date_id;
    }
    
    /**
     * レスのあぼーんをまとめてチェックする（名前、メール、日付、メッセージ）
     *
     * @access  protected
     * @return  string|false  マッチしたらマッチ文字列。マッチしなければfalse
     */
    function checkAborns($name, $mail, $data_id, $msg)
    {
        return NgAbornCtl::checkAborns($name, $mail, $data_id, $msg, $this->thread->bbs, $this->thread->ttitle_hc);
    }
    
    /**
     * NGあぼーんをチェックする
     *
     * @access  protected
     * @return  string|false  マッチしたらマッチ文字列。マッチしなければfalse
     */
    function ngAbornCheck($ngcode, $subject)
    {
        return NgAbornCtl::ngAbornCheck($ngcode, $subject, $this->thread->bbs, $this->thread->ttitle_hc);
    }
    
    /**
     * 特定レスの透明あぼーんチェック
     *
     * @access  protected
     * @return  boolean
     */
    function abornResCheck($resnum)
    {
        $t = $this->thread;

        return NgAbornCtl::abornResCheck($t->host, $t->bbs, $t->key, $resnum);
    }


    /**
     * ユーザ定義URLハンドラ（メッセージ中のURLを書き換える関数）を追加する
     *
     * ハンドラは最初に追加されたものから順番に試行される
     * URLはハンドラの返り値（文字列）で置換される
     * FALSEを帰した場合は次のハンドラに処理が委ねられる
     *
     * ユーザ定義URLハンドラの引数は
     *  1. string $url  URL
     *  2. array  $purl URLをparse_url()したもの
     *  3. string $str  パターンにマッチした文字列、URLと同じことが多い
     *  4. object &$aShowThread 呼び出し元のオブジェクト
     * である
     * 常にFALSEを返し、内部で処理するだけの関数を登録してもよい
     *
     * @param   string|array $function  関数名か、array(string $classname, string $methodname)
     *                                  もしくは array(object $instance, string $methodname)
     * @return  void
     * @access  public
     * @todo    ユーザ定義URLハンドラのオートロード機能を実装
     */
    function addURLHandler($function)
    {
        $this->user_url_handlers[] = $function;
    }
    
    /**
     * レスフィルタリングのターゲット文字列を得る
     *
     * @access  protected
     * @return  string
     */
    function getFilterTarget($i, $name, $mail, $date_id, $msg)
    {
        switch ($GLOBALS['res_filter']['field']) {
            case 'name':
                $target = $name;
                break;
            case 'mail':
                $target = $mail;
                break;
            case 'date':
                $target = preg_replace('| ?ID:[0-9A-Za-z/.+?]+.*$|', '', $date_id);
                break;
            case 'id':
                if ($target = preg_replace('|^.*ID:([0-9A-Za-z/.+?]+).*$|', '$1', $date_id)) {
                    break;
                } else {
                    return '';
                }
            case 'msg':
                $target = $msg;
                break;
            default: // 'whole'
                // 省略前の文字列が入るので $ares の直接利用はダメになった
                // $target = strval($i) . '<>' . $ares;
                $target = implode('<>', array(strval($i), $name, $mail, $date_id, $msg));
        }

        // '<>' だけ許可
        $target = strip_tags($target, '<>');
        
        return $target;
    }

    /**
     * レスフィルタリングのマッチ判定
     *
     * @access  private
     * @return  string|false    マッチしたらマッチ文字列（「含まない」条件の時はtrue）を、マッチしなかったらfalseを返す
     *                          （単純にbooleanを返すようにしてもいいかもしれない）
     */
    function filterMatch($target, $resnum)
    {
        global $_conf;
        global $_filter_hits, $filter_range;

        $failed = ($GLOBALS['res_filter']['match'] == 'off') ? true : false;

        if ($GLOBALS['res_filter']['method'] == 'and') {
            $words_fm_hit = 0;
            foreach ($GLOBALS['words_fm'] as $word_fm_ao) {
                $match = StrCtl::filterMatch($word_fm_ao, $target);
                if ((bool)strlen($match) == $failed) {
                    if ($GLOBALS['res_filter']['match'] != 'off') {
                        return false;
                    } else {
                        $words_fm_hit++;
                    }
                }
            }
            if ($words_fm_hit == count($GLOBALS['words_fm'])) {
                return false;
            }
        } else {
            $match = StrCtl::filterMatch($GLOBALS['word_fm'], $target);
            if ((bool)strlen($match) == $failed) {
                return false;
            }
        }

        $GLOBALS['_filter_hits']++;

        // 表示範囲外なら偽判定とする
        if (isset($GLOBALS['word']) && !empty($filter_range) &&
            ($_filter_hits < $filter_range['start'] || $_filter_hits > $filter_range['to'])
        ) {
            return false;
        }

        $GLOBALS['last_hit_resnum'] = $resnum;

        // 逐次更新用
        if (!$_conf['ktai']) {
            echo <<<EOP
<script type="text/javascript">
<!--
filterCount({$GLOBALS['_filter_hits']});
-->
</script>\n
EOP;
        }

        return $failed ? !(bool)$match : $match;
    }
    
    /**
     * 一つのスレ内でのID出現数をThreadにセットする
     *
     * @access  private
     * @return  void
     */
    function setIdCountToThread()
    {
        $lines = $this->thread->datlines;
        
        if (!is_array($lines)) {
            //trigger_error('no $this->thread->datlines', E_USER_WARNING);
            return;
        }
        foreach ($lines as $k => $line) {
            $lar = explode('<>', $line);
            if (preg_match('|ID: ?([0-9a-zA-Z/.+]{8,10})|', $lar[2], $matches)) {
                $id = $matches[1];
                if (isset($this->thread->idcount[$id])) {
                    $this->thread->idcount[$id]++;
                } else {
                    $this->thread->idcount[$id] = 1;
                }
            }
        }
    }
    
    /**
     * 逆参照をThreadにセットする
     *
     * @access  private
     * @return  void
     */
    function setBackwordResesToThread()
    {
        $lines = $this->thread->datlines;
        
        if (!is_array($lines)) {
            //trigger_error('no $this->thread->datlines', E_USER_WARNING);
            return;
        }

        $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('set_backword_reses');
        
        foreach ($lines as $k => $line) {
        
            // 逆参照のための引用レス番号取得（処理速度が2,3割増になる…）
            if ($nums = $this->getQuoteResNumsName($lar[0])) {
                if (isset($this->thread->backword_reses[$k])) {
                    array_merge($this->thread->backword_reses[$k], $nums);
                } else {
                    $this->thread->backword_reses[$k] = $nums;
                }
            }
            
            if ($nums = $this->getQuoteResNumsMsg($lar[3])) {
                if (isset($this->thread->backword_reses[$k])) {
                    array_merge($this->thread->backword_reses[$k], $nums);
                } else {
                    $this->thread->backword_reses[$k] = $nums;
                }
            }
        }
        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('set_backword_reses');
    }
    
    /**
     * 名前にある引用レス番号を取得する
     *
     * @access  private
     * @param   string  $name（未フォーマット）
     * @return  array|false
     */
    function getQuoteResNumsName($name)
    {
        // トリップを除去
        $name = preg_replace('/(◆.*)/', '', $name, 1);

        /*
        //if (preg_match('/[0-9]+/', $name, $m)) {
             return (int)$m[0];
        }
        */

        if (preg_match_all($this->getAnchorRegex('/(?:^|%prefix%|%delimiter%)(%a_num%)/'), $name, $matches)) {
            foreach ($matches[1] as $a_quote_res_num) {
                $quote_res_nums[] = (int)mb_convert_kana($a_quote_res_num, 'n');
            }
            return array_unique($quote_res_nums);
        }
        
        return false;
    }
    
    /**
     * メッセージにある引用レス番号を取得する
     *
     * @access  private
     * @param   string  $msg（未フォーマット）
     * @return  array|false
     */
    function getQuoteResNumsMsg($msg)
    {
        $quote_res_nums = array();
        
        // DAT中にある>>1のリンクHTMLを取り除く
        $msg = $this->removeResAnchorTagInDat($msg);
        
        if (preg_match_all($this->getAnchorRegex('/%full%/'), $msg, $out, PREG_PATTERN_ORDER)) {

            // $out[2] は第 2 のキャプチャ用サブパターンにマッチした文字列の配列
            foreach ($out[2] as $numberq) {
                if ($matches = preg_split($this->getAnchorRegex('/%delimiter%/'), $numberq)) { 
                    foreach ($matches as $a_quote_res_num) { 
                        if (preg_match($this->getAnchorRegex('/%range_delimiter%/'), $a_quote_res_num)) {
                            continue;
                        }
                        $quote_res_nums[] = (int)mb_convert_kana($a_quote_res_num, 'n');
                    }
                }
            }
        }
        return array_unique($quote_res_nums);
    }
    
    /**
     * @access  protected
     * @return  string  HTML
     */
    function quote_name_callback($s)
    {
        return preg_replace_callback(
            $this->getAnchorRegex('/(%prefix%)?(%a_num%)/'),
            array($this, 'quote_res_callback'), $s[0]
        );
    }
    
    /**
     * DAT中にある>>1のリンクHTMLを取り除く
     *
     * @return  string
     */
    function removeResAnchorTagInDat($msg)
    {
        // <a href="../test/read.cgi/accuse/1001506967/1" target="_blank">&gt;&gt;1</a>
        return preg_replace('{<[Aa] .+?>(&gt;&gt;\\d[\\d\\-]*)</[Aa]>}', '$1', $msg);
    }

    /**
     * AA判定
     *
     * @access  protected
     * @return  integer  0:反応なし, 1:弱反応, 2:強反応（AA略）
     */
    function detectAA($s)
    {
        global $_conf;
        
        // AA によく使われるパディング
        $regexA = '　{3}|(?: 　){2}';

        // 罫線
        // [\u2500-\u257F]
        //var $regexB = '[\\x{849F}-\\x{84BE}]{5}';
        $regexB = '[─-╂■]{4}';

        // Latin-1,全角スペースと句読点,ひらがな,カタカナ,半角・全角形 以外の同じ文字が3つ連続するパターン
        // Unicode の [^\x00-\x7F\x{2010}-\x{203B}\x{3000}-\x{3002}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{FF00}-\x{FFEF}]
        // をベースに SJIS に作り直してあるが、若干の違いがある。
        //$regexC = '([^\\x00-\\x7F\\xA1-\\xDF　、。，．：；０-ヶー～・…※！？＃＄％＆＊＋／＝])\\1\\1';
        $regexC = '([^\\x00-\\x7F\\xA1-\\xDF　、。，．：；０-ヶー～・…※！？＃＄％＆＊＋／＝]|[_,:;\'])\\1\\1';
        
        //$re = '(?:' . $this->regexA . '|' . $this->regexB . '|' . $this->regexC . ')';
        
        $level = 0;
        
        // AA略の対象とする最低行数（3行を超えるもののみ省略する）
        $aa_ryaku = false;
        if (preg_match("/^(.+<br>){3}./", $s)) {
            $aa_ryaku = true;
        }
        
        if (mb_ereg($regexA, $s)) {
            $level = 1;
        }
        
        // AA略しないならここまで
        if (!$_conf['k_aa_ryaku_size'] or !$aa_ryaku) {
            return $level;
        }
        
        if ($level && mb_ereg($regexC, $s)) {
            return 2;
        }

        if (mb_ereg($regexB, $s)) {
            return 2;
        }

        return $level;
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
