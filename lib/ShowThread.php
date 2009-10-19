<?php
/**
 * rep2- スレッドを表示する クラス
 */

require_once P2_LIB_DIR . '/HostCheck.php';
require_once P2_LIB_DIR . '/ThreadRead.php';

// {{{ ShowThread

abstract class ShowThread
{
    // {{{ constants

    /**
     * リンクとして扱うパターン
     *
     * @type string
     */
    const LINK_REGEX = '{
(?P<link>(<[Aa][ ].+?>)(.*?)(</[Aa]>)) # リンク（PCREの特性上、必ずこのパターンを最初に試行する）
|
(?:
  (?P<quote> # 引用
    ((?:&gt;|＞){1,2}[ ]?) # 引用符
    (
      (?:[1-9]\\d{0,3}) # 1つ目の番号
      (?:
        (?:[ ]?(?:[,=]|、)[ ]?[1-9]\\d{0,3})+ # 連続
        |
        -(?:[1-9]\\d{0,3})? # 範囲
      )?
    )
    (?=\\D|$)
  ) # 引用ここまで
|                                  # PHP 5.3縛りにするなら、↓の\'のエスケープを外し、NOWDOCにする
  (?P<url>(ftp|h?t?tps?)://([0-9A-Za-z][\\w;/?:@=&$\\-_.+!*\'(),#%\\[\\]^~]+)) # URL
  ([^\\s<>]*) # URLの直後、タグorホワイトスペースが現れるまでの文字列
|
  (?P<id>ID:[ ]?([0-9A-Za-z/.+]{8,11})(?=[^0-9A-Za-z/.+]|$)) # ID（8,10桁 +PC/携帯識別フラグ）
)
}x';

    /**
     * リダイレクタの種類
     *
     * @type int
     */
    const REDIRECTOR_NONE = 0;
    const REDIRECTOR_IMENU = 1;
    const REDIRECTOR_PINKTOWER = 2;
    const REDIRECTOR_MACHIBBS = 3;

    /**
     * NGあぼーんの種類
     *
     * @type int
     */
    const ABORN = -1;
    const NG_NONE = 0;
    const NG_NAME = 1;
    const NG_MAIL = 2;
    const NG_ID = 4;
    const NG_MSG = 8;
    const NG_FREQ = 16;
    const NG_CHAIN = 32;
    const NG_AA = 64;

    // }}}
    // {{{ static properties

    /**
     * まとめ読みモード時のスレッド数
     *
     * @type int
     */
    static private $_matome_count = 0;

    /**
     * 本文以外がNGあぼーんにヒットした総数
     *
     * @type int
     */
    static protected $_ngaborns_head_hits = 0;

    /**
     * 本文がNGあぼーんにヒットした総数
     *
     * @type int
     */
    static protected $_ngaborns_body_hits = 0;

    // }}}
    // {{{ properties

    /**
     * まとめ読みモード時のスレッド番号
     *
     * @type int
     */
    protected $_matome;

    /**
     * URLを処理する関数・メソッド名などを格納する配列
     * (組み込み)
     *
     * @type array
     */
    protected $_url_handlers;

    /**
     * URLを処理する関数・メソッド名などを格納する配列
     * (ユーザ定義、組み込みのものより優先)
     *
     * @type array
     */
    protected $_user_url_handlers;

    /**
     * 頻出IDをあぼーんする
     *
     * @type bool
     */
    protected $_ngaborn_frequent;

    /**
     * あぼーんレス番号およびNGレス番号を格納する配列
     * array_intersect()を効率よく行うため、該当するレス番号は文字列にキャストして格納する
     *
     * @type array
     */
    protected $_aborn_nums;
    protected $_ng_nums;

    /**
     * リダイレクタの種類
     *
     * @type int
     */
    protected $_redirector;

    /**
     * スレッドオブジェクト
     *
     * @type ThreadRead
     */
    public $thread;

    /**
     * アクティブモナー・オブジェクト
     *
     * @type ActiveMona
     */
    public $activeMona;

    /**
     * アクティブモナーが有効か否か
     *
     * @type bool
     */
    public $am_enabled = false;

    protected $_quote_from; // 被アンカーを集計した配列 // [被参照レス番 : [参照レス番, ...], ...)

    public $BBS_NONAME_NAME = '';

    private $_auto_fav_rank = false; // お気に自動ランク

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     */
    protected function __construct(ThreadRead $aThread, $matome = false)
    {
        global $_conf;

        // スレッドオブジェクトを登録
        $this->thread = $aThread;
        $this->str_to_link_regex = $this->buildStrToLinkRegex();

        // まとめ読みモードか否か
        if ($matome) {
            $this->_matome = ++self::$_matome_count;
        } else {
            $this->_matome = false;
        }

        $this->_url_handlers = array();
        $this->_user_url_handlers = array();

        $this->_ngaborn_frequent = 0;
        if ($_conf['ngaborn_frequent']) {
            if ($_conf['ngaborn_frequent_dayres'] == 0) {
                $this->_ngaborn_frequent = $_conf['ngaborn_frequent'];
            } elseif ($this->thread->setDayRes() && $this->thread->dayres < $_conf['ngaborn_frequent_dayres']) {
                $this->_ngaborn_frequent = $_conf['ngaborn_frequent'];
            }
        }

        $this->_aborn_nums = array();
        $this->_ng_nums = array();

        if (P2Util::isHostBbsPink($this->thread->host)) {
            $this->_redirector = self::REDIRECTOR_PINKTOWER;
        } elseif (P2Util::isHost2chs($this->thread->host)) {
            $this->_redirector = self::REDIRECTOR_IMENU;
        } elseif (P2Util::isHostMachiBbs($this->thread->host)) {
            $this->_redirector = self::REDIRECTOR_MACHIBBS;
        } else {
            $this->_redirector = self::REDIRECTOR_NONE;
        }
    }

    // }}}

    /**
     * @access  protected
     * @return  void
     */
    function setBbsNonameName()
    {
        if (P2Util::isHost2chs($this->thread->host)) {
            if (!class_exists('SettingTxt', false)) {
                require P2_LIB_DIR . '/SettingTxt.php';
            }
            $st = new SettingTxt($this->thread->host, $this->thread->bbs);
            if (!empty($st->setting_array['BBS_NONAME_NAME'])) {
                $this->BBS_NONAME_NAME = $st->setting_array['BBS_NONAME_NAME'];
            }
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
        static $cache_ = null;

        if (!is_null($cache_)) {
            return $cache_;
        }

        $anchor = array();

        // アンカーの構成要素（正規表現パーツの配列）

        // 空白文字
        $anchor_space = '(?:[ ]|　)';
        //$anchor[' '] = '';

        // アンカー引用子 >>
        $anchor['prefix'] = "(?:(?:&gt;|＞|&lt;|＜|〉){1,2}|(?:\)){2}|》|≫){$anchor_space}*\.?";

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
        $anchor['delimiter'] = "{$anchor_space}?(?:[\.,=+]|、|・|＝|，){$anchor_space}?";

        // あぼーん用アンカー引用子
        $anchor['prefix_abon'] = "&gt;{1,2}{$anchor_space}?";

        // レス番号
        $anchor['a_num'] = sprintf('%s{1,4}', $anchor['a_digit']);

        // レス範囲
        $anchor['a_range'] = sprintf("%s(?:%s(?:%s)?%s)?",
            $anchor['a_num'], $anchor['range_delimiter'], $anchor['prefix'],$anchor['a_num']
        );

        // レス範囲の列挙
        $anchor['ranges'] = sprintf('%s(?:%s%s)*(?!%s)',
            $anchor['a_range'], $anchor['delimiter'], $anchor['a_range'], $anchor['a_digit']
        );

        // レス番号の列挙
        $anchor['nums'] = sprintf("%s(?:%s%s)*(?!%s)",
            $anchor['a_num'], $anchor['delimiter'], $anchor['a_num'], $anchor['a_digit']
        );

        // アンカー全体（メッセージ欄用）
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
//            . '|'
//            .   '(?P<ip>発信元: ?((?:[1-2]?\\d{2}|\\d)(?:\\.[1-2]?\\d{2}|\\d){3})(?=[^0-9A-Za-z/.+]|$))'
            . ')'
            . '}';
    }

    // {{{ getDatToHtml()

    /**
     * DatをHTML変換したものを取得する
     *
     * @param   bool $is_fragment
     * @return  bool|string
     */
    public function getDatToHtml($is_fragment = false)
    {
        return $this->datToHtml(true, $is_fragment);
    }

    // }}}
    // {{{ datToHtml()

    /**
     * DatをHTMLに変換して表示する
     *
     * @param   bool $capture       trueなら変換結果を出力せずに返す
     * @param   bool $is_fragment   trueなら<div class="thread"></div>で囲まない
     * @return  bool|string
     */
    public function datToHtml($capture = false, $is_fragment = false)
    {
        global $_conf;

        // 表示レス範囲が指定されていなければ
        if (!$this->thread->resrange) {
            $error = '<p><b>p2 error: {$this->resrange} is FALSE at datToHtml()</b></p>';
            if ($capture) {
                return $error;
            } else {
                echo $error;
                return false;
            }
        }

        $start = $this->thread->resrange['start'];
        $to = $this->thread->resrange['to'];
        $nofirst = $this->thread->resrange['nofirst'];

        $buf['body'] = $is_fragment ? '' : "<div class=\"thread\">\n";
        $buf['q'] = '';

        // まず 1 を表示
        if (!$nofirst) {
            $res = $this->transRes($this->thread->datlines[0], 1);
            if (is_array($res)) {
                $buf['body'] .= $res['body'];
                $buf['q'] .= $res['q'] ? $res['q'] : '';
            } else {
                $buf['body'] .= $res;
            }
        }

        // 連鎖のため、範囲外のNGあぼーんチェック
        if ($_conf['ngaborn_chain_all'] && empty($_GET['nong'])) {
            for ($i = ($nofirst) ? 0 : 1; $i < $start; $i++) {
                list($name, $mail, $date_id, $msg) = $this->thread->explodeDatLine($this->thread->datlines[$i]);
                if (($id = $this->thread->ids[$i]) !== null) {
                    $date_id = str_replace($this->thread->idp[$i] . $id, $idstr, $date_id);
                }
                $this->_ngAbornCheck($i + 1, strip_tags($name), $mail, $date_id, $id, $msg);
            }
        }

        // 指定範囲を表示
        for ($i = $start - 1; $i < $to; $i++) {
            if (!$nofirst and $i == 0) {
                continue;
            }
            if (!$this->thread->datlines[$i]) {
                $this->thread->readnum = $i;
                break;
            }
            $res = $this->transRes($this->thread->datlines[$i], $i + 1);
            if (is_array($res)) {
                $buf['body'] .= $res['body'];
                $buf['q'] .= $res['q'] ? $res['q'] : '';
            } else {
                $buf['body'] .= $res;
            }
            if (!$capture && $i % 10 == 0) {
                echo $buf['body'];
                flush();
                $buf['body'] = '';
            }
        }

        if (!$is_fragment) {
            $buf['body'] .= "</div>\n";
        }

        if ($capture) {
            return $buf['body'] . $buf['q'];
        } else {
            echo $buf['body'];
            echo $buf['q'];
            flush();
            return true;
        }
    }

    // }}}
    // {{{ transRes()

    /**
     * DatレスをHTMLレスに変換する
     *
     * @param   string  $ares   datの1ライン
     * @param   int     $i      レス番号
     * @return  string
     */
    abstract public function transRes($ares, $i);

    // }}}
    // {{{ transName()

    /**
     * 名前をHTML用に変換する
     *
     * @param   string  $name   名前
     * @return  string
     */
    abstract public function transName($name);

    // }}}
    // {{{ transMsg()

    /**
     * datのレスメッセージをHTML表示用メッセージに変換する
     *
     * @param   string  $msg    メッセージ
     * @param   int     $mynum  レス番号
     * @return  string
     */
    abstract public function transMsg($msg, $mynum);

    // }}}
    // {{{ replaceBeId()

    /**
     * BEプロファイルリンク変換
     */
    public function replaceBeId($date_id, $i)
    {
        global $_conf;

        $beid_replace = "<a href=\"http://be.2ch.net/test/p.php?i=\$1&u=d:http://{$this->thread->host}/test/read.cgi/{$this->thread->bbs}/{$this->thread->key}/{$i}\"{$_conf['ext_win_target_at']}>Lv.\$2</a>";

        //<BE:23457986:1>
        $be_match = '|<BE:(\d+):(\d+)>|i';
        if (preg_match($be_match, $date_id)) {
            $date_id = preg_replace($be_match, $beid_replace, $date_id);

        } else {

            $beid_replace = "<a href=\"http://be.2ch.net/test/p.php?i=\$1&u=d:http://{$this->thread->host}/test/read.cgi/{$this->thread->bbs}/{$this->thread->key}/{$i}\"{$_conf['ext_win_target_at']}>?\$2</a>";
            $date_id = preg_replace('|BE: ?(\d+)-(#*)|i', $beid_replace, $date_id);
        }

        return $date_id;
    }

    // }}}
    // {{{ _ngAbornCheck()

    /**
     * NGあぼーんチェック
     *
     * @param   int     $i          レス番号
     * @param   string  $name       名前欄
     * @param   string  $mail       メール欄
     * @param   string  $date_id    日付・ID欄
     * @param   string  $id         ID
     * @param   string  $msg        レス本文
     * @param   bool    $nong       NGチェックをするかどうか
     * @param   array  &$info       NGの理由が格納される変数の参照
     * @return  int NGタイプ。ShowThread::NG_XXX のビット和か ShowThread::ABORN
     */
    protected function _ngAbornCheck($i, $name, $mail, $date_id, $id, $msg, $nong = false, &$info = null)
    {
        global $_conf, $ngaborns_hits;

        $info = array();
        $type = self::NG_NONE;

        // {{{ 頻出IDチェック

        if ($this->_ngaborn_frequent && $id && $this->thread->idcount[$id] >= $_conf['ngaborn_frequent_num']) {
            if (!$_conf['ngaborn_frequent_one'] && $id == $this->thread->ids[1]) {
                // >>1 はそのまま表示
            } elseif ($this->_ngaborn_frequent == 1) {
                $ngaborns_hits['aborn_freq']++;
                return $this->_markNgAborn($i, self::ABORN, false);
            } elseif (!$nong) {
                $ngaborns_hits['ng_freq']++;
                $type |= $this->_markNgAborn($i, self::NG_FREQ, false);
                $info[] = sprintf('頻出ID:%s(%d)', $id, $this->thread->idcount[$id]);
            }
        }

        // }}}
        // {{{ 連鎖チェック

        if ($_conf['ngaborn_chain'] && preg_match_all('/(?:&gt;|＞)([1-9][0-9\\-,]*)/', $msg, $matches)) {
            $references = array_unique(preg_split('/[-,]+/',
                                                  trim(implode(',', $matches[1]), '-,'),
                                                  -1,
                                                  PREG_SPLIT_NO_EMPTY));
            $intersections = array_intersect($references, $this->_aborn_nums);
            $info_suffix = '';

            if ($intersections) {
                if ($_conf['ngaborn_chain'] == 1) {
                    $ngaborns_hits['aborn_chain']++;
                    return $this->_markNgAborn($i, self::ABORN, true);
                }
                if ($nong) {
                    $intersections = null;
                } else {
                    $info_suffix = '(' . (($_conf['ktai']) ? 'ｱﾎﾞﾝ' : 'あぼーん') . ')';
                }
            } elseif (!$nong) {
                $intersections = array_intersect($references, $this->_ng_nums);
            }

            if ($intersections) {
                $ngaborns_hits['ng_chain']++;
                $type |= $this->_markNgAborn($i, self::NG_CHAIN, true);
                $info[] = sprintf('連鎖NG:&gt;&gt;%d%s', array_shift($intersections), $info_suffix);
            }
        }

        // }}}
        // {{{ あぼーんチェック

        // あぼーんレス
        if ($this->abornResCheck($i) !== false) {
            $ngaborns_hits['aborn_res']++;
            return $this->_markNgAborn($i, self::ABORN, false);
        }

        // あぼーんネーム
        if ($this->ngAbornCheck('aborn_name', $name) !== false) {
            $ngaborns_hits['aborn_name']++;
            return $this->_markNgAborn($i, self::ABORN, false);
        }

        // あぼーんメール
        if ($this->ngAbornCheck('aborn_mail', $mail) !== false) {
            $ngaborns_hits['aborn_mail']++;
            return $this->_markNgAborn($i, self::ABORN, false);
        }

        // あぼーんID
        if ($this->ngAbornCheck('aborn_id', $date_id) !== false) {
            $ngaborns_hits['aborn_id']++;
            return $this->_markNgAborn($i, self::ABORN, false);
        }

        // あぼーんメッセージ
        if ($this->ngAbornCheck('aborn_msg', $msg) !== false) {
            $ngaborns_hits['aborn_msg']++;
            return $this->_markNgAborn($i, self::ABORN, true);
        }

        // }}}

        if ($nong) {
            return $type;
        }

        // {{{ NGチェック

        // NGネームチェック
        if ($this->ngAbornCheck('ng_name', $name) !== false) {
            $ngaborns_hits['ng_name']++;
            $type |= $this->_markNgAborn($i, self::NG_NAME, false);
        }

        // NGメールチェック
        if ($this->ngAbornCheck('ng_mail', $mail) !== false) {
            $ngaborns_hits['ng_mail']++;
            $type |= $this->_markNgAborn($i, self::NG_MAIL, false);
        }

        // NGIDチェック
        if ($this->ngAbornCheck('ng_id', $date_id) !== false) {
            $ngaborns_hits['ng_id']++;
            $type |= $this->_markNgAborn($i, self::NG_ID, false);
        }

        // NGメッセージチェック
        $a_ng_msg = $this->ngAbornCheck('ng_msg', $msg);
        if ($a_ng_msg !== false) {
            $ngaborns_hits['ng_msg']++;
            $type |= $this->_markNgAborn($i, self::NG_MSG, true);
            $info[] = sprintf('NG%s:%s',
                              ($_conf['ktai']) ? 'ﾜｰﾄﾞ' : 'ワード',
                              htmlspecialchars($a_ng_msg, ENT_QUOTES));
        }

        // }}}

        return $type;
    }

    // }}}
    // {{{ _markNgAborn()

    /**
     * NGあぼーんにヒットしたレス番号を記録する
     *
     * @param   int $num        レス番号
     * @param   int $type       NGあぼーんの種類
     * @param   bool $isBody    本文にヒットしたかどうか
     * @return  int $typeと同じ値
     */
    protected function _markNgAborn($num, $type, $isBody)
    {
        if ($type) {
            if ($isBody) {
                self::$_ngaborns_body_hits++;
            } else {
                self::$_ngaborns_head_hits++;
            }

            // array_intersect()を効率よく行うため、レス番号を文字列型にキャストする
            $str = (string)$num;
            if ($type == self::ABORN) {
                $this->_aborn_nums[$num] = $str;
            } else {
                $this->_ng_nums[$num] = $str;
            }
        }

        return $type;
    }

    // }}}
    // {{{ ngAbornCheck()

    /**
     * NGあぼーんチェック
     */
    public function ngAbornCheck($code, $resfield, $ic = false)
    {
        global $ngaborns;

        //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('ngAbornCheck()');

        if (isset($ngaborns[$code]['data']) && is_array($ngaborns[$code]['data'])) {
            // +Wiki:BEあぼーん
            /* preg_replace がエラーになるのでこのへんコメントアウト
            if ($code == 'aborn_be' || $code == 'ng_be') {
                // プロフィールIDを抜き出す
                if ($prof_id = preg_replace('/BE:(\d+)/', '$1')) {
                    echo $prof_id;
                    $resfield = P2UtilWiki::calcBeId($prof_id);
                    if($resfield == 0) return false;
                } else {
                    return false;
                }
            }
             */
            $bbs = $this->thread->bbs;
            $title = $this->thread->ttitle_hc;

            foreach ($ngaborns[$code]['data'] as $k => $v) {
                // 板チェック
                if (isset($v['bbs']) && in_array($bbs, $v['bbs']) == false) {
                    continue;
                }

                // タイトルチェック
                if (isset($v['title']) && stripos($title, $v['title']) === false) {
                    continue;
                }

                // ワードチェック
                // 正規表現
                if (!empty($v['regex'])) {
                    $re_method = $v['regex'];
                    /*if ($re_method($v['word'], $resfield, $matches)) {
                        $this->ngAbornUpdate($code, $k);
                        //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('ngAbornCheck()');
                        return htmlspecialchars($matches[0], ENT_QUOTES);
                    }*/
                     if ($re_method($v['word'], $resfield)) {
                        $this->ngAbornUpdate($code, $k);
                        //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('ngAbornCheck()');
                        return $v['cond'];
                    }
                // +Wiki:BEあぼーん(完全一致)
                } else if ($code == 'aborn_be' || $code == 'ng_be') {
                    if ($resfield == $v['word']) {
                        $this->ngAbornUpdate($code, $k);
                        //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('ngAbornCheck()');
                        return $v['cond'];
                    }
               // 大文字小文字を無視
                } elseif ($ic || !empty($v['ignorecase'])) {
                    if (stripos($resfield, $v['word']) !== false) {
                        $this->ngAbornUpdate($code, $k);
                        //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('ngAbornCheck()');
                        return $v['cond'];
                    }
                // 単純に文字列が含まれるかどうかをチェック
                } else {
                    if (strpos($resfield, $v['word']) !== false) {
                        $this->ngAbornUpdate($code, $k);
                        //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('ngAbornCheck()');
                        return $v['cond'];
                    }
                }
            }
        }

        //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('ngAbornCheck()');
        return false;
    }

    // }}}
    // {{{ abornResCheck()

    /**
     * 特定レスの透明あぼーんチェック
     */
    public function abornResCheck($resnum)
    {
        global $ngaborns;

        $target = $this->thread->host . '/' . $this->thread->bbs . '/' . $this->thread->key . '/' . $resnum;

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

    // }}}
    // {{{ ngAbornUpdate()

    /**
     * NG/あぼ～ん日時と回数を更新
     */
    public function ngAbornUpdate($code, $k)
    {
        global $ngaborns;

        if (isset($ngaborns[$code]['data'][$k])) {
            $ngaborns[$code]['data'][$k]['lasttime'] = date('Y/m/d G:i'); // HIT時間を更新
            if (empty($ngaborns[$code]['data'][$k]['hits'])) {
                $ngaborns[$code]['data'][$k]['hits'] = 1; // 初HIT
            } else {
                $ngaborns[$code]['data'][$k]['hits']++; // HIT回数を更新
            }
        }
    }

    // }}}
    // {{{ addURLHandler()

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
     *  4. object $aShowThread 呼び出し元のオブジェクト
     * である
     * 常にFALSEを返し、内部で処理するだけの関数を登録してもよい
     *
     * @param   callback $function  コールバックメソッド
     * @return  void
     * @access  public
     * @todo    ユーザ定義URLハンドラのオートロード機能を実装
     */
    public function addURLHandler($function)
    {
        $this->_user_url_handlers[] = $function;
    }

    // }}}
    // {{{ getFilterTarget()

    /**
     * レスフィルタリングのターゲットを得る
     */
    public function getFilterTarget($ares, $i, $name, $mail, $date_id, $msg)
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

    // }}}
    // {{{ filterMatch()

    /**
     * レスフィルタリングのマッチ判定
     */
    public function filterMatch($target, $resnum)
    {
        global $_conf;
        global $filter_hits, $filter_range;

        $failed = ($GLOBALS['res_filter']['match'] == 'off') ? TRUE : FALSE;

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

        $filter_hits++;

        if ($_conf['filtering'] && !empty($filter_range) &&
            ($filter_hits < $filter_range['start'] || $filter_hits > $filter_range['to'])
        ) {
            return false;
        }

        $GLOBALS['last_hit_resnum'] = $resnum;

        if (!$_conf['ktai']) {
            echo <<<EOP
<script type="text/javascript">
//<![CDATA[
filterCount({$filter_hits});
//]]>
</script>\n
EOP;
        }

        return true;
    }

    // }}}
    // {{{ stripLineBreaks()

    /**
     * 文末の改行と連続する改行を取り除く
     *
     * @param string $msg
     * @param string $replacement
     * @return string
     */
    public function stripLineBreaks($msg, $replacement = ' <br><br> ')
    {
        if (P2_MBREGEX_AVAILABLE) {
            $msg = mb_ereg_replace('(?:[\\s　]*<br>)+[\\s　]*$', '', $msg);
            $msg = mb_ereg_replace('(?:[\\s　]*<br>){3,}', $replacement, $msg);
        } else {
            mb_convert_variables('UTF-8', 'CP932', $msg, $replacement);
            $msg = preg_replace('/(?:[\\s\\x{3000}]*<br>)+[\\s\\x{3000}]*$/u', '', $msg);
            $msg = preg_replace('/(?:[\\s\\x{3000}]*<br>){3,}/u', $replacement, $msg);
            $msg = mb_convert_encoding($msg, 'CP932', 'UTF-8');
        }

        return $msg;
    }

    // }}}
    // {{{ transLink()

    /**
     * リンク対象文字列を変換する
     *
     * @param   string $str
     * @return  string
     */
    public function transLink($str)
    {
        return preg_replace_callback($this->str_to_link_regex, array($this, 'transLinkDo'), $str);
    }

    // }}}
    // {{{ transLinkDo()

    /**
     * リンク対象文字列の種類を判定して対応した関数/メソッドに渡す
     *
     * @param   array   $s
     * @return  string
     */
    public function transLinkDo(array $s)
    {
        global $_conf;

        $orig = $s[0];
        $following = '';

        // PHP 5.2.7 未満の preg_replace_callback() では名前付き捕獲式集合が使えないので
        /*
        if (!array_key_exists('link', $s)) {
            $s['link']  = $s[1];
            $s['quote'] = $s[5];
            $s['url']   = $s[8];
            $s['id']    = $s[11];
        }
        */

        // マッチしたサブパターンに応じて分岐
        // リンク
        if ($s['link']) {
            if (preg_match('{ href=(["\'])?(.+?)(?(1)\\1)(?=[ >])}i', $s[2], $m)) {
                $url = $m[2];
                $str = $s[3];
            } else {
                return $s[3];
            }

        // 引用
        } elseif ($s['quote']) {
            return  preg_replace_callback(
                $this->getAnchorRegex('/(%prefix%)?(%a_range%)/'),
                array($this, 'quoteResCallback'), $s['quote']);

        // http or ftp のURL
        } elseif ($s['url']) {
            if ($_conf['ktai'] && $s[9] == 'ftp') {
                return $orig;
            }
            $url = preg_replace('/^t?(tps?)$/', 'ht$1', $s[9]) . '://' . $s[10];
            $str = $s['url'];
            $following = $s[11];
            if (strlen($following) > 0) {
                // ウィキペディア日本語版のURLで、SJISの2バイト文字の上位バイト
                // (0x81-0x9F,0xE0-0xEF)が続くとき
                if (P2Util::isUrlWikipediaJa($url)) {
                    $leading = ord($following);
                    if ((($leading ^ 0x90) < 32 && $leading != 0x80) || ($leading ^ 0xE0) < 16) {
                        $url .= rawurlencode(mb_convert_encoding($following, 'UTF-8', 'CP932'));
                        $str .= $following;
                        $following = '';
                    }
                } elseif (strpos($following, 'tp://') !== false) {
                    // 全角スペース+URL等の場合があるので再チェック
                    $following = $this->transLink($following);
                }
            }

        // ID
        } elseif ($s['id'] && $_conf['flex_idpopup']) { // && $_conf['flex_idlink_k']
            return $this->idFilter($s['id'], $s[12]);

        // その他（予備）
        } else {
            return strip_tags($orig);
        }

        // リダイレクタを外す
        switch ($this->_redirector) {
            case self::REDIRECTOR_IMENU:
                $url = preg_replace('{^([a-z]+://)ime\\.nu/}', '$1', $url);
                break;
            case self::REDIRECTOR_PINKTOWER:
                $url = preg_replace('{^([a-z]+://)pinktower\\.com/}', '$1', $url);
                break;
            case self::REDIRECTOR_MACHIBBS:
                $url = preg_replace('{^[a-z]+://machi(?:bbs\\.com|\\.to)/bbs/link\\.cgi\\?URL=}', '', $url);
                break;
        }

        // エスケープされていない特殊文字をエスケープ
        $url = htmlspecialchars($url, ENT_QUOTES, 'Shift_JIS', false);
        $str = htmlspecialchars($str, ENT_QUOTES, 'Shift_JIS', false);
        // 実態参照・数値参照を完全にデコードしようとすると負荷が大きいし、
        // "&"以外の特殊文字はほとんどの場合URLエンコードされているはずなので
        // 中途半端に凝った処理はせず、"&amp;"→"&"のみ再変換する。
        $raw_url = str_replace('&amp;', '&', $url);

        // URLをパース・ホストを検証
        $purl = @parse_url($raw_url);
        if (!$purl || !array_key_exists('host', $purl) ||
            strpos($purl['host'], '.') === false ||
            $purl['host'] == '127.0.0.1' ||
            //HostCheck::isAddressLocal($purl['host']) ||
            //HostCheck::isAddressPrivate($purl['host']) ||
            P2Util::isHostExample($purl['host']))
        {
            return $orig;
        }
        // URLのマッチングで"&amp;"を考慮しなくて済むように、生のURLを登録しておく
        $purl[0] = $raw_url;

        // URLを処理
        foreach ($this->_user_url_handlers as $handler) {
            if (false !== ($link = call_user_func($handler, $url, $purl, $str, $this))) {
                return $link . $following;
            }
        }
        foreach ($this->_url_handlers as $handler) {
            if (false !== ($link = $this->$handler($url, $purl, $str))) {
                return $link . $following;
            }
        }

        return $orig;
    }

    // }}}
    // {{{ idFilter()

    /**
     * IDフィルタリング変換
     *
     * @param   string  $idstr  ID:xxxxxxxxxx
     * @param   string  $id        xxxxxxxxxx
     * @return  string
     */
    abstract public function idFilter($idstr, $id);

    // }}}
    // {{{ idFilterCallback()

    /**
     * IDフィルタリング変換
     *
     * @param   array   $s  正規表現にマッチした要素の配列
     * @return  string
     */
    final public function idFilterCallback(array $s)
    {
        return $this->idFilter($s[0], $s[1]);
    }

    // }}}

    /**
     * @access  protected
     * @return  string  HTML
     */
    function quote_name_callback($s)
    {
        return preg_replace_callback(
            $this->getAnchorRegex('/(%prefix%)?(%a_num%)/'),
            array($this, 'quoteResCallback'), $s[0]
        );
    }

    // {{{ quoteRes()

    /**
     * 引用変換（単独）
     *
     * @param   string  $full           >>1
     * @param   string  $qsign          >>
     * @param   string  $appointed_num    1
     * @return  string
     */
    abstract public function quoteRes($full, $qsign, $appointed_num);

    // }}}
    // {{{ quoteResCallback()

    /**
     * 引用変換（単独）
     *
     * @param   array   $s  正規表現にマッチした要素の配列
     * @return  string
     */
    final public function quoteResCallback(array $s)
    {
        return $this->quoteRes($s[0], $s[1], $s[2]);
    }

    // }}}
    // {{{ quoteResRange()

    /**
     * 引用変換（範囲）
     *
     * @param   string  $full           >>1-100
     * @param   string  $qsign          >>
     * @param   string  $appointed_num    1-100
     * @return  string
     */
    abstract public function quoteResRange($full, $qsign, $appointed_num);

    // }}}
    // {{{ quoteResRangeCallback()

    /**
     * 引用変換（範囲）
     *
     * @param   array   $s  正規表現にマッチした要素の配列
     * @return  string
     */
    final public function quoteResRangeCallback(array $s)
    {
        return $this->quoteResRange($s[0], $s[1], $s[2]);
    }

    // }}}
    // {{{ getQuoteResNumsName()

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

    // }}}
    // {{{ wikipediaFilter()

    /**
     * [[語句]]があった時にWikipediaへ自動リンク
     *
     * @param   string  $msg            メッセージ
     * @return  string
     *
     * original code:
     *  http://akid.s17.xrea.com/p2puki/index.phtml?%A5%E6%A1%BC%A5%B6%A1%BC%A5%AB%A5%B9%A5%BF%A5%DE%A5%A4%A5%BA%28rep2%20Ver%201.7.0%A1%C1%29#led2c85d
     */
    protected function wikipediaFilter($msg) {
        $msg = mb_convert_encoding($msg, "UTF-8", "SJIS-win"); // SJISはうざいからUTF-8に変換するんだぜ？
        $wikipedia = "http://ja.wikipedia.org/wiki/"; // WikipediaのURLなんだぜ？
        $search = "/\[\[([^\[\]\n<>]+)\]\]+/"; // 目印となる正規表現なんだぜ？
        preg_match_all($search, $msg, $matches); // [[語句]]を探すんだぜ？
        foreach ($matches[1] as $value) { // リンクに変換するんだぜ？
            $replaced = $this->link_wikipedia($value);
            $msg = str_replace("[[$value]]", "[[$replaced]]", $msg); // 変換後の本文を戻すんだぜ？
        }
        $msg = mb_convert_encoding($msg, "SJIS-win", "UTF-8"); // UTF-8からSJISに戻すんだぜ？
        return $msg;
    }

    // }}}
    // {{{ link_wikipedia()

    /**
     * Wikipediaの語句をリンクに変換して返す.
     *
     * @param   string  $word   語句
     * @return  string
     */
    abstract protected function link_wikipedia($word);

    // {{{ _make_quote_from()

    /**
     * 被レスデータを集計して$this->_quote_fromに保存.
     */
    protected function _make_quote_from()
    {
        global $_conf;
        $this->_quote_from = array();
        if (!$this->thread->datlines) return;

        foreach($this->thread->datlines as $num => $line) {
            list($name, $mail, $date_id, $msg) = $this->thread->explodeDatLine($line);

           // NGあぼーんチェック
            if (($id = $this->thread->ids[$num + 1]) !== null) {
                $date_id = str_replace($this->thread->idp[$i] . $id, 'ID:' . $id, $date_id);
            }
            $ng_type = $this->_ngAbornCheck($num + 1, strip_tags($name), $mail, $date_id, $id, $msg);
            if ($ng_type == self::ABORN) {continue;}

            // >>1のリンクをいったん外す
            // <a href="../test/read.cgi/accuse/1001506967/1" target="_blank">&gt;&gt;1</a>
            $msg = preg_replace('{<[Aa] .+?>(&gt;&gt;[1-9][\\d\\-]*)</[Aa]>}', '$1', $msg);
            if (!preg_match_all($this->getAnchorRegex('/%full%/'), $msg, $out, PREG_PATTERN_ORDER)) continue;
            foreach ($out[2] as $numberq) {
                if (!preg_match_all($this->getAnchorRegex('/(?:%prefix%)?(%a_range%)/'), $numberq, $anchors, PREG_PATTERN_ORDER)) continue;
                foreach ($anchors[1] as $anchor) {
                    if (preg_match($this->getAnchorRegex('/(%a_num%)%range_delimiter%(?:%prefix%)?(%a_num%)/'), $anchor, $matches)) {
                        $from = intval(mb_convert_kana($matches[1], 'n'));
                        $to = intval(mb_convert_kana($matches[2], 'n'));
                        if ($from < 1 || $to < 1 || $from > $to
                            || ($to - $from + 1) > sizeof($this->thread->datlines))
                                continue;
                        if ($_conf['backlink_list_range_anchor_limit'] != 0) {
                            if ($to - $from >= $_conf['backlink_list_range_anchor_limit'])
                                continue;
                        }
                        for ($i = $from; $i <= $to; $i++) {
                            if ($i > sizeof($this->thread->datlines)) break;
                            if ($_conf['backlink_list_future_anchor'] == 0) {
                                if ($i >= $num+1) {continue;}   // レス番号以降のアンカーは無視する
                            }
                            if (!array_key_exists($i, $this->_quote_from) || $this->_quote_from[$i] === null) {
                                $this->_quote_from[$i] = array();
                            }
                            if (!in_array($num + 1, $this->_quote_from[$i])) {
                                $this->_quote_from[$i][] = $num + 1;
                            }
                        }
                    } else if (preg_match($this->getAnchorRegex('/(%a_num%)/'), $anchor, $matches)) {
                        $quote_num = intval(mb_convert_kana($matches[1], 'n'));
                        if ($_conf['backlink_list_future_anchor'] == 0) {
                            if ($quote_num >= $num+1) {continue;}   // レス番号以降のアンカーは無視する
                        }
                        if (!array_key_exists($quote_num, $this->_quote_from) || $this->_quote_from[$quote_num] === null) {
                            $this->_quote_from[$quote_num] = array();
                        }
                        if (!in_array($num + 1, $this->_quote_from[$quote_num])) {
                            $this->_quote_from[$quote_num][] = $num + 1;
                        }
                    }
                }
            }
        }
    }

    // }}}
    // {{{ _get_quote_from()

    /**
     * 被レスリストを返す.
     *
     * @return  array
     */
    public function get_quote_from()
    {
        if ($this->_quote_from === null) {
            $this->_make_quote_from();  // 被レスデータ集計
        }
        return $this->_quote_from;
    }

    // }}}
    // {{{ _quoteback_list_html()

    /**
     * 被レスリストをHTMLで整形して返す.
     *
     * @param   int     $resnum レス番号
     * @param   int     $type   1:縦形式 2:横形式 3:展開用ブロック用文字列
     * @param   bool    $popup  横形式でのポップアップ処理(true:ポップアップする、false:挿入する)
     * @return  string
     */
    protected function quoteback_list_html($resnum, $type,$popup=true)
    {
        $quote_from = $this->get_quote_from();
        if (!array_key_exists($resnum, $quote_from)) return $ret;

        $anchors = $quote_from[$resnum];
        sort($anchors);

        if ($type == 1) {
            return $this->_quoteback_vertical_list_html($anchors);
        } else if ($type == 2) {
            return $this->_quoteback_horizontal_list_html($anchors,$popup);
        } else if ($type == 3) {
            return $this->_quoteback_res_data($anchors);
        }
    }
    protected function _quoteback_vertical_list_html($anchors)
    {
        $ret = '<div class="v_reslist"><ul>';
        $anchor_cnt = 1;
        foreach($anchors as $anchor) {
            if ($anchor_cnt > 1) $ret .= '<li>│</li>';
            if ($anchor_cnt < count($anchors)) {
                $ret .= '<li>├';
            } else {
                $ret .= '<li>└';
            }
            $ret .= $this->quoteRes($anchor, '', $anchor, true);
            $anchor_cnt++;
        }
        $ret .= '</ul></div>';
        return $ret;
    }
    protected function _quoteback_horizontal_list_html($anchors,$popup)
    {
        $ret="";
        $ret.= '<div class="reslist">';
        $count=0;

        foreach($anchors as $idx=>$anchor) {
            $anchor_link= $this->quoteRes('>>'.$anchor, '>>', $anchor);
            $qres_id = ($this->_matome ? "t{$this->_matome}" : "" ) ."qr{$anchor}";
            $ret.='<div class="reslist_inner" >';
            $ret.=sprintf('<div>【参照レス：%s】</div>',$anchor_link);
            $ret.='</div>';
            $count++;
        }
        $ret.='</div>';
        return $ret;
    }
    protected function _quoteback_res_data($anchors)
    {
        foreach($anchors as $idx=>$anchor) {
            $anchors2[]=($this->_matome ? "t{$this->_matome}" : "" ) ."qr{$anchor}";
        }

        return join('/',$anchors2);
    }

    // }}}
    // {{{ getDatochiResiduums()

    /**
     * DAT落ちの際に取得できた>>1と最後のレスをHTMLで返す.
     *
     * @return  string|false
     */
    public function getDatochiResiduums()
    {
        $ret = '';
        $elines = $this->thread->datochi_residuums;
        if (!count($elines)) return $ret;

        $this->thread->onthefly = true;
        $ret = "<div><span class=\"onthefly\">on the fly</span></div>\n";
        $ret .= "<div class=\"thread\">\n";
        foreach($elines as $num => $line) {
            $res = $this->transRes($line, $num);
            $ret .= is_array($res) ? $res['body'] . $res['q'] : $res;
        }
        $ret .= "</div>\n";
        return $ret;
    }
    // }}}
    // {{{ getAutoFavRanks()

    /**
     * 自動ランク設定を返す.
     *
     * @return  array
     */
    public function getAutoFavRank()
    {
        if ($this->_auto_fav_rank !== false) return $this->_auto_fav_rank;
        global $_conf;

        $ranks = explode(',', strtr($_conf['expack.ic2.fav_auto_rank_setting'], ' ', ''));
        $ret = null;
        if ($_conf['expack.misc.multi_favs']) {
            $idx = 0;
            if (!is_array($this->thread->favs)) return null;
            foreach ($this->thread->favs as $fav) {
                if ($fav) {
                    $rank = $ranks[$idx];
                    if (is_numeric($rank)) {
                        $rank = intval($rank);
                        $ret = $ret === null ? $rank
                            : ($ret < $rank ? $rank : $ret);
                    }
                }
                $idx++;
            }
        } else {
            if ($this->thread->fav && is_numeric($ranks[0])) {
                $ret = intval($ranks[0]);
            }
        }
        return $this->_auto_fav_rank = $ret;
    }

    // }}}
    // {{{ isAutoFavRankOverride()

    /**
     * 自動ランク設定でランクを上書きすべきか返す.
     *
     * @param   int $now    現在のランク
     * @param   int $new    自動ランク
     * @return  bool
     */
    static public function isAutoFavRankOverride($now, $new)
    {
        global $_conf;

        switch ($_conf['expack.ic2.fav_auto_rank_override']) {
        case 0:
            return false;
            break;
        case 1:
            return $now != $new;
            break;
        case 2:
            return $now == 0 && $now != $new;
            break;
        case 3:
            return $now < $new;
            break;
        default:
            return false;
        }
        return false;
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
