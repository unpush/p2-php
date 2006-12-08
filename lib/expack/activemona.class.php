<?php
/**
 * rep2expack - アクティブモナー・クラス
 */
class ActiveMona
{
    // モナーフォント表示スイッチ
    var $mona = '<img src="img/aa.png" width="19" height="12" alt="" class="aMonaSW" onclick="activeMona(\'%s\')">';
    //var $mona = '<img src="img/mona.png" width="39" height="12" alt="（´∀｀） class="aMonaSW" onclick="activeMona(\'%s\')"">';

    // 正規表現
    var $re;

    // AA によく使われるパディング
    var $regexA = '　{4}|(?: 　){2}';

    // 罫線
    // [\u2500-\u257F]
    //var $regexB = '[\\x{849F}-\\x{84BE}]{5}';
    var $regexB = '[─-╂]{5}';

    // Latin-1,全角スペースと句読点,ひらがな,カタカナ,半角・全角形 以外の同じ文字が3つ連続するパターン
    // Unicode の [^\x00-\x7F\x{2010}-\x{203B}\x{3000}-\x{3002}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{FF00}-\x{FFEF}]
    // をベースに SJIS に作り直してあるが、若干の違いがある。
    var $regexC = '([^\\x00-\\x7F\\xA1-\\xDF　、。，．：；０-ヶー～・…※！？＃＄％＆＊＋／＝])\\1\\1';

    /**
     * コンストラクタ（PHP4）
     */
    function ActiveMona()
    {
        $this->__construct();
    }

    /**
     * コンストラクタ（PHP5）
     */
    function __construct()
    {
        $this->re = '(?:' . $this->regexA . '|' . $this->regexB . '|' . $this->regexC . ')';
    }

    /**
     * シングルトン
     */
    function &singleton()
    {
        static $aMona = null;
        if (is_null($aMona)) {
            $aMona = new ActiveMona($config);
        }
        return $aMona;
    }

    /**
     * モナーフォント表示スイッチを生成
     */
    function getMona($id)
    {
        return sprintf($this->mona, $id);
    }

    /**
     * AA判定
     */
    function detectAA($msg)
    {
        if (mb_ereg($this->re, $msg)) {
            return true;
        }
        return false;
    }

}

/*
 * Local variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
