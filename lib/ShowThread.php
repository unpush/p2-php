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
    
    var $anchor_regex; // @access  protected
    
    /**
     * @constructor
     */
    function ShowThread(&$Thread)
    {
        global $_conf;
        
        $this->initAnchorRegex();       // set $this->anchor_regex
        $this->initStrToLinkRegex();    // set $this->str_to_link_regex
        
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
     * @access  private
     * @return  void    set $this->anchor_regex
     */
    function initAnchorRegex()
    {
        $anchor = array();
        
        // アンカー用空白文字の正規表現
        $anchor_space = '(?:[ ]|　)';
        
        // アンカー引用子の正規表現
        $anchor['prefix'] = "(?:&gt;|＞|&lt;|＜|〉|》|≫){1,2}{$anchor_space}*\.?";
        
        // あぼーん用アンカー引用子の正規表現
        $anchor['prefix_abon'] = "&gt;{1,2}{$anchor_space}?";

        // アンカー先の正規表現
        // $anchor['a_num']='(?:[1-9]|１|２|３|４|５|６|７|８|９)(?:\\d|０|１|２|３|４|５|６|７|８|９){0,3}';
        
        // アンカー先の正規表現
        $anchor['a_digit'] = '(?:\\d|０|１|２|３|４|５|６|７|８|９)';
        
        // アンカー先の正規表現
        $anchor['a_num'] = "{$anchor['a_digit']}{1,4}";

        $anchor['range_delimiter'] = "(?:-|‐|\x81\\x5b)"; // ー
        $anchor['a_range']   = "{$anchor['a_num']}(?:{$anchor['range_delimiter']}{$anchor['a_num']})?";
        $anchor['delimiter'] = "{$anchor_space}?(?:[,=+]|、|・|＝|，){$anchor_space}?";

        $anchor['ranges'] = "{$anchor['a_range']}(?:{$anchor['delimiter']}{$anchor['a_range']})*";
        $anchor['full']   = "{$anchor['prefix']}{$anchor['ranges']}";
        
        $this->anchor_regex = $anchor;
    }
    
    /**
     * @access  private
     * @return  void     set $this->str_to_link_regex
     */
    function initStrToLinkRegex()
    {
        $this->str_to_link_regex = '{'
            . '(?P<link>(<[Aa] .+?>)(.*?)(</[Aa]>))' // リンク（PCREの特性上、必ずこのパターンを最初に試行する）
            . '|'
            . '(?:'
            .   '(?P<quote>' // 引用
            /*
            .       '((?:&gt;|＞){1,2} ?)' // 引用符
            .       '('
            .           '(?:[1-9]\\d{0,3})' // 1つ目の番号
            .           '(?:'
            .               '(?: ?(?:[,=]|、) ?[1-9]\\d{0,3})+' // 連続
            .               '|'
            .               '-(?:[1-9]\\d{0,3})?' // 範囲
            .           ')?'
            .       ')'
            */
            .       '(' . $this->anchor_regex['prefix'] . ')'  // 引用符
            .       '(' . $this->anchor_regex['ranges'] . ')'  // 番号範囲の併記[7]
            .       '(?=\\D|$)'
            .   ')' // 引用ここまで
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
        $pattern = "/(?:^|{$this->anchor_regex['prefix']}|{$this->anchor_regex['delimiter']})({$this->anchor_regex['a_num']}+)/";
        
        // トリップを除去
        $name = preg_replace('/(◆.*)/', '', $name, 1);

        /*
        //if (preg_match('/[0-9]+/', $name, $m)) {
             return (int)$m[0];
        }
        */
        
        if (preg_match_all($pattern, $name, $matches)) {
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
        $pattern_anchor = "/{$this->anchor_regex['prefix']}({$this->anchor_regex['ranges']})/";
        $pattern_num = "/({$this->anchor_regex['a_num']})/";
        
        $quote_res_nums = array();
        
        // >>1のリンクを除去
        // <a href="../test/read.cgi/accuse/1001506967/1" target="_blank">&gt;&gt;1</a>
        /*
        $msg = preg_replace('{<[Aa] .+?>(&gt;&gt;[1-9][\\d\\-]*)</[Aa]>}', '$1', $msg);
        */
        $msg = preg_replace('{<[Aa] .+?>(&gt;&gt;[\\d\\-]+)</[Aa]>}', '$1', $msg);
        
        //if (preg_match_all('/(?:&gt;|＞)+ ?([1-9](?:[0-9\\- ,=.]|、)*)/', $msg, $out, PREG_PATTERN_ORDER)) {
        if (preg_match_all($pattern_anchor, $msg, $out, PREG_PATTERN_ORDER)) {

            // $out[1] は第 1 のキャプチャ用サブパターンにマッチした文字列の配列
            foreach ($out[1] as $numberq) {
                //if (preg_match_all('/[1-9]\\d*/', $numberq, $matches, PREG_PATTERN_ORDER)) {
                /*
                if (preg_match_all($pattern_num, $numberq, $matches, PREG_PATTERN_ORDER)) {
                    // $matches[0] はパターン全体にマッチした文字列の配列
                    foreach ($matches[1] as $a_quote_res_num) {
                */
                if ($matches = preg_split("/{$this->anchor_regex['delimiter']}/", $numberq)) { 
                    foreach ($matches as $a_quote_res_num) { 
                        if (preg_match("/{$this->anchor_regex['range_delimiter']}/", $a_quote_res_num)) {
                            continue;
                        }
                        $quote_res_nums[] = (int)mb_convert_kana($a_quote_res_num, 'n');
                    }
                }
            }
        }
        return array_unique($quote_res_nums);
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
