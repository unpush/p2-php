var $str_to_link_limit = 30; // 一つのレスにおけるリンク変換の制限回数（荒らし対策）<?php
/**
 * スレッドを表示する クラス
 */
class ShowThread
{
    var $thread; // スレッドオブジェクトの参照

    var $str_to_link_regex; // リンクすべき文字列の正規表現
    var $str_to_link_limit = 30; // 一つのレスにおけるリンク変換の制限回数（荒らし対策）

    var $url_handlers;      // URLを処理する関数・メソッド名などを格納する配列（デフォルト）
    var $user_url_handlers; // URLを処理する関数・メソッド名などを格納する配列（ユーザ定義、デフォルトのものより優先）

    var $ngaborn_frequent; // 頻出IDをあぼーんする

    var $aborn_nums;    // あぼーんレス番号を格納する配列
    var $ng_nums;       // NGレス番号を格納する配列

    var $activeMona;            // アクティブモナー・オブジェクト
    var $am_enabled = false;    // アクティブモナーが有効か否か

    /**
     * コンストラクタ
     */
    function ShowThread(&$aThread)
    {
        global $_conf;

        // スレッドオブジェクトの参照を登録
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
            .   '(?P<url>(ftp|h?t?tps?)://([0-9A-Za-z][\\w!#%&+*,\\-./:;=?@\\[\\]^~]+))' // URL
            .   '([^\s<>]*)' // URLの直後、タグorホワイトスペースが現れるまでの文字列
            . '|'
            .   '(?P<id>ID: ?([0-9A-Za-z/.+]{8,11})(?=[^0-9A-Za-z/.+]|$))' // ID（8,10桁 +PC/携帯識別フラグ）
            . ')'
            . '}';

        $this->url_handlers = array();
        $this->user_url_handlers = array();

        $this->ngaborn_frequent = 0;
        if ($_conf['ngaborn_frequent']) {
            if ($_conf['ngaborn_frequent_dayres'] == 0) {
                $this->ngaborn_frequent = $_conf['ngaborn_frequent'];
            } elseif ($this->thread->setDayRes() && $this->thread->dayres < $_conf['ngaborn_frequent_dayres']) {
                $this->ngaborn_frequent = $_conf['ngaborn_frequent'];
            }
        }

        $this->aborn_nums = array();
        $this->ng_nums = array();
    }

    /**
     * DatをHTML変換して表示する
     * （継承先クラスで実装）
     */
    function datToHtml()
    {
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
     *
     * @access  protected
     * @param   string     $data_id  2006/10/20(金) 11:46:08 ID:YS696rnVP BE:32616498-DIA(30003)
     * @param   integer    $i        レス番号
     * @return  string
     */
    function replaceBeId($date_id, $i)
    {
        global $_conf;

        $beid_replace = "<a href=\"http://be.2ch.net/test/p.php?i=\$1&u=d:http://{$this->thread->host}/test/read.cgi/{$this->thread->bbs}/{$this->thread->key}/{$i}\"{$_conf['ext_win_target_at']}>Lv.\$2</a>";

        //<BE:23457986:1>
        $be_match = '|<BE:(\d+):(\d+)>|i';
        if (preg_match($be_match, $date_id)) {
            $date_id = preg_replace($be_match, $beid_replace, $date_id);

        // 2006/10/20(金) 11:46:08 ID:YS696rnVP BE:32616498-DIA(30003)
        } else {
            $beid_replace = "<a href=\"http://be.2ch.net/test/p.php?i=\$1&u=d:http://{$this->thread->host}/test/read.cgi/{$this->thread->bbs}/{$this->thread->key}/{$i}\"{$_conf['ext_win_target_at']}>?\$2</a>";
            $date_id = preg_replace('|BE: ?(\d+)-(#*)|i', $beid_replace, $date_id);
        }

        return $date_id;
    }


    /**
     * NGあぼーんチェック
     *
     * @return  string|false
     */
    function ngAbornCheck($code, $resfield, $ic = false)
    {
        global $ngaborns;

        $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('ngAbornCheck()');

        if (isset($ngaborns[$code]['data']) && is_array($ngaborns[$code]['data'])) {
            foreach ($ngaborns[$code]['data'] as $k => $v) {
                // 板チェック
                if (isset($v['bbs']) && in_array($this->thread->bbs, $v['bbs']) == false) {
                    continue;
                }

                // タイトルチェック
                if (isset($v['title']) && stristr($this->thread->ttitle_hc, $v['title']) === false) {
                    continue;
                }

                // ワードチェック
                // 正規表現
                if (!empty($v['regex'])) {
                    $re_method = $v['regex'];
                    /*if ($re_method($v['word'], $resfield, $matches)) {
                        $this->ngAbornUpdate($code, $k);
                        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('ngAbornCheck()');
                        return htmlspecialchars($matches[0], ENT_QUOTES);
                    }*/
                     if ($re_method($v['word'], $resfield)) {
                        $this->ngAbornUpdate($code, $k);
                        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('ngAbornCheck()');
                        return $v['cond'];
                    }
               // 大文字小文字を無視(1)
                } elseif (!empty($v['ignorecase'])) {
                    if (stristr($resfield, $v['word'])) {
                        $this->ngAbornUpdate($code, $k);
                        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('ngAbornCheck()');
                        return $v['cond'];
                    }
                // 大文字小文字を無視(2)
                } elseif ($ic) {
                    if (stristr($resfield, $v['word'])) {
                        $this->ngAbornUpdate($code, $k);
                        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('ngAbornCheck()');
                        return $v['cond'];
                    }
                // 単純に文字列が含まれるかどうかをチェック
                } else {
                    if (strstr($resfield, $v['word'])) {
                        $this->ngAbornUpdate($code, $k);
                        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('ngAbornCheck()');
                        return $v['cond'];
                    }
                }
            }
        }

        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('ngAbornCheck()');
        return false;
    }

    /**
     * 特定レスの透明あぼーんチェック
     *
     * @return  boolean
     */
    function abornResCheck($resnum)
    {
        global $ngaborns;

        $t = &$this->thread;
        $target = $t->host . '/' . $t->bbs . '/' . $t->key . '/' . $resnum;

        if (isset($ngaborns['aborn_res']['data']) && is_array($ngaborns['aborn_res']['data'])) {
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
     *
     * @return  void
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
     * レスフィルタリングのターゲットを得る
     *
     * @return  string
     */
    function getFilterTarget(&$ares, &$i, &$name, &$mail, &$date_id, &$msg)
    {
        switch ($GLOBALS['res_filter']['field']) {
            case 'name':
                $target = $name; break;
            case 'mail':
                $target = $mail; break;
            case 'date':
                $target = preg_replace('| ?ID:[0-9A-Za-z/.+?]+.*$|', '', $date_id); break;
            case 'id':
                if ($target = preg_replace('|^.*ID:([0-9A-Za-z/.+?]+).*$|', '$1', $date_id)) {
                    break;
                } else {
                    return '';
                }
            case 'msg':
                $target = $msg; break;
            default: // 'hole'
                $target = strval($i) . '<>' . $ares;
        }

        $target = @strip_tags($target, '<>');

        return $target;
    }

    /**
     * レスフィルタリングのマッチ判定
     *
     * @return  boolean
     */
    function filterMatch(&$target, &$resnum)
    {
        global $_conf;
        global $filter_hits, $filter_range;

        $failed = ($GLOBALS['res_filter']['match'] == 'off') ? true : false;

        if ($GLOBALS['res_filter']['method'] == 'and') {
            $words_fm_hit = 0;
            foreach ($GLOBALS['words_fm'] as $word_fm_ao) {
                if (StrCtl::filterMatch($word_fm_ao, $target) == $failed) {
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
            if (StrCtl::filterMatch($GLOBALS['word_fm'], $target) == $failed) {
                return false;
            }
        }

        $GLOBALS['filter_hits']++;

        // 表示範囲外なら偽判定とする
        if (isset($GLOBALS['word']) && !empty($filter_range) &&
            ($filter_hits < $filter_range['start'] || $filter_hits > $filter_range['to'])
        ) {
            return false;
        }

        $GLOBALS['last_hit_resnum'] = $resnum;

        if (!$_conf['ktai']) {
            echo <<<EOP
<script type="text/javascript">
<!--
filterCount({$GLOBALS['filter_hits']});
-->
</script>\n
EOP;
        }

        return true;
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
