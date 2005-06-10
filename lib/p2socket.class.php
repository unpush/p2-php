<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

/**
 * fsockopenのラッパークラス
 */
class P2Socket
{

    var $rsrc;      // ファイルポインタ
    var $errno;     // エラーコード
    var $errstr;    // エラーメッセージ
    var $warning;   // PHPが出力する警告

    /**
     * コンストラクタ (PHP4 style)
     */
    function P2Socket($target, $port, $timeout = 0)
    {
        $this->__construct($target, $port, $timeout);
    }

    /**
     * コンストラクタ (PHP5 style)
     *
     * @param   string  $tareget    ホスト名など、ソケット接続を開く対象のリソース
     * @param   integer $port       ポート番号
     * @param   float   $timeout    ソケットに接続できるまでのタイムアウト（秒）
     */
    function __construct($target, $port, $timeout = 0)
    {
        ob_start();
        if ($timeout) {
            $this->rsrc = fsockopen($target, $port, $this->errno, $this->errstr, $timeout);
        } else {
            $this->rsrc = fsockopen($target, $port, $this->errno, $this->errstr);
        }
        $warning = ob_get_contents();
        ob_end_clean();
        if ($warning) {
            $this->warning = $warning;
        }
    }

    /**
     * ファクトリ
     *
     * 接続に失敗したときはパラメータをキーとするスタティック変数の配列にインスタンスを格納し
     * 以後同じパラメータで呼ばれたときは接続を試みないようにする。
     *
     * 引数はコンストラクタに準ずる
     *
     * @return  object  P2Socketのインスタンス
     */
    function &open($target, $port, $timeout = 0)
    {
        static $errors = array();

        $id = $target . ':' . $port . '(' . $timeout . ')';
        if (isset($errors[$id])) {
            return $errors[$id];
        }

        $sock = &new P2Socket($target, $port, $timeout);

        if ($sock->isError()) {
            $errors[$id] = $sock;
        }

        return $sock;
    }

    /**
     * ソケット接続をオープンできていればTRUE、できていなければFALSEを返す
     */
    function isError()
    {
        return !is_resource($this->rsrc);
    }

    /**
     * ソケットのファイルポインタを返す
     */
    function &getResource()
    {
        return $this->rsrc;
    }

    /**
     * エラーコードとエラーメッセージを配列で返す
     */
    function getError()
    {
        return array($this->errno, $this->errstr);
    }

    /**
     * PHPが出力した警告を返す
     */
    function getWarning()
    {
        return $this->warning;
    }

}

?>
