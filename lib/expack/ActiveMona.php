<?php
// {{{ ActiveMona

/**
 *  rep2expack - アクティブモナー
 */
class ActiveMona
{
    // {{{ constants

    /**
     * AA用フォント切替スイッチのフォーマット
     */
    const MONA = '<img src="img/aa.png" width="19" height="12" alt="" class="aMonaSW" onclick="activeMona(\'%s\')">';
    //const MONA = '<img src="img/mona.png" width="39" height="12" alt="（´∀｀） class="aMonaSW" onclick="activeMona(\'%s\')"">';

    /**
     * AA判定パターン
     *
     * 罫線
     * [\\u2500-\\u257F] [\\x{849F}-\\x{84BE}]
     *
     * および
     *
     * Latin-1,全角スペースと句読点,ひらがな,カタカナ,
     * 半角・全角形 以外の同じ文字が3つ連続するパターン
     *
     * Unicode の
     * [^\x00-\x7F\x{2010}-\x{203B}\x{3000}-\x{3002}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{FF00}-\x{FFEF}]
     * をベースに SJIS に作り直してあるが、若干の違いがある。
     */
    const REGEX = '(?:[─-╂]{5}|([^\\x00-\\x7F\\xA1-\\xDF　、。，．：；０-ヶー～・…※！？＃＄％＆＊＋／＝])\\1\\1)';

    // }}}
    // {{{ properties

    /**
     * インスタンス
     *
     * @var array(ActiveMona)
     */
    static private $_am = array();

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

    // }}}
    // {{{ singleton()

    /**
     * シングルトン
     *
     * @param string $linebreaks
     * @return ActiveMona
     */
    static public function singleton($linebreaks = '<br>')
    {
        if (!array_key_exists($linebreaks, self::$_am)) {
            self::$_am[$linebreaks] = new ActiveMona($linebreaks);
        }
        return self::$_am[$linebreaks];
    }

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     *
     * @param string $linebreaks
     */
    public function __construct($linebreaks = '<br>')
    {
        global $_conf;

        $this->_lb = $linebreaks;
        $this->_ln = $_conf['expack.am.lines_limit'] - 1;
    }

    // }}}
    // {{{ getMona()

    /**
     * AA用フォント切替スイッチを生成
     *
     * @param string $id
     * @return string
     */
    function getMona($id)
    {
        return sprintf(self::MONA, $id);
    }

    // }}}
    // {{{ detectAA()

    /**
     * AA判定
     *
     * @param string $msg
     * @return bool
     */
    function detectAA($msg)
    {
        if (substr_count($msg, $this->_lb) < $this->_ln) {
            return false;
        } elseif (substr_count($msg, '　　') > 5) {
            return true;
        } elseif (!mb_ereg_search_init($msg, self::REGEX)) {
            return false;
        } else {
            $i = 0;
            while ($i < 3 && mb_ereg_search()) {
                $i++;
            }
            return ($i == 3);
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
