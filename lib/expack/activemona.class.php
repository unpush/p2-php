<?php
// {{{ ActiveMona

/**
 *  rep2expack - アクティブモナー
 */
class ActiveMona
{
    // {{{ constants

    /**
     * AA によく使われるパディング
     */
    const REGEX_A = '　{4}|(?: 　){2}';

    /**
     * 罫線
     * [\\u2500-\\u257F] [\\x{849F}-\\x{84BE}]
     */
    const REGEX_B = '[─-╂]{5}';

    /**
     * Latin-1,全角スペースと句読点,ひらがな,カタカナ,
     * 半角・全角形 以外の同じ文字が3つ連続するパターン
     *
     * Unicode の
     * [^\x00-\x7F\x{2010}-\x{203B}\x{3000}-\x{3002}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{FF00}-\x{FFEF}]
     * をベースに SJIS に作り直してあるが、若干の違いがある。
     *
     * "\1" はREGEX_AやREGEX_Bに捕獲式集合が使われていないことが前提なので注意
     */
    const REGEX_C = '([^\\x00-\\x7F\\xA1-\\xDF　、。，．：；０-ヶー～・…※！？＃＄％＆＊＋／＝])\\1\\1';

    // }}}
    // {{{ properties

    /**
     * インスタンス
     *
     * @var ActiveMona
     */
    static private $_am = null;

    /**
     * モナーフォント表示スイッチ
     *
     * @var string
     */
    private $_mona;

    /**
     * 行数判定に使う改行文字
     *
     * @var string
     */
    private $_lb;

    /**
     * 正規表現で判定する行数の下限-1
     *
     * @var int
     */
    private $_ln;

    /**
     * AA判定する正規表現
     *
     * @var string
     */
    private $_re;

    // }}}
    // {{{ singleton()

    /**
     * シングルトン
     */
    static public function singleton()
    {
        if (self::$_am === null) {
            self::$_am = new ActiveMona();
        }
        return self::$_am;
    }

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     */
    public function __construct($linebreaks = '<br>')
    {
        global $_conf;

        $this->_mona = '<img src="img/aa.png" width="19" height="12" alt="" class="aMonaSW" onclick="activeMona(\'%s\')">';
        //$this->_mona = '<img src="img/mona.png" width="39" height="12" alt="（´∀｀） class="aMonaSW" onclick="activeMona(\'%s\')"">';
        $this->_lb = $linebreaks;
        $this->_ln = $_conf['expack.am.lines_limit'] - 1;
        $this->_re = '(?:' . self::REGEX_A . '|' . self::REGEX_B . '|' . self::REGEX_C . ')';
    }

    // }}}
    // {{{ getMona()

    /**
     * モナーフォント表示スイッチを生成
     */
    function getMona($id)
    {
        return sprintf($this->_mona, $id);
    }

    // }}}
    // {{{ detectAA()

    /**
     * AA判定
     */
    function detectAA($msg)
    {
        if (substr_count($msg, $this->_lb) < $this->_ln) {
            return false;
        } elseif (mb_ereg($this->_re, $msg)) {
            return true;
        } else {
            return false;
        }
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
