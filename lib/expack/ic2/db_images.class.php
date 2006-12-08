<?php
/**
 * rep2expack - ImageCache2
 */

require_once P2EX_LIBRARY_DIR . '/ic2/loadconfig.inc.php';
require_once P2EX_LIBRARY_DIR . '/ic2/database.class.php';
require_once P2EX_LIBRARY_DIR . '/ic2/db_blacklist.class.php';
require_once P2EX_LIBRARY_DIR . '/ic2/db_errors.class.php';

define('P2_IMAGECACHE_OK',     0);
define('P2_IMAGECACHE_ABORN',  1);
define('P2_IMAGECACHE_BROKEN', 2);
define('P2_IMAGECACHE_LARGE',  3);
define('P2_IMAGECACHE_VIRUS',  4);

$GLOBALS['_P2_GETIMAGE_CACHE'] = array();

class IC2DB_Images extends IC2DB_Skel
{
    // {{{ properties

    // }}}
    // {{{ constcurtor

    function IC2DB_Images()
    {
        $this->__construct();
    }

    function __construct()
    {
        parent::__construct();
        $this->__table = $this->_ini['General']['table'];
    }

    // }}}
    // {{{ table()

    function table()
    {
        return array(
            'id'   => DB_DATAOBJECT_INT,
            'uri'  => DB_DATAOBJECT_STR,
            'host' => DB_DATAOBJECT_STR,
            'name' => DB_DATAOBJECT_STR,
            'size' => DB_DATAOBJECT_INT,
            'md5'  => DB_DATAOBJECT_STR,
            'width'  => DB_DATAOBJECT_INT,
            'height' => DB_DATAOBJECT_INT,
            'mime' => DB_DATAOBJECT_STR,
            'time' => DB_DATAOBJECT_INT,
            'rank' => DB_DATAOBJECT_INT,
            'memo' => DB_DATAOBJECT_STR,
        );
    }

    // }}}
    // {{{ keys()

    function keys()
    {
        return array('uri');
    }

    // }}}
    // {{{ uniform()

    // 検索用に文字列をフォーマットする
    // このメソッドのみ静的にコールできる
    function uniform($str, $enc)
    {
        // 内部エンコーディングを保存
        $incode = mb_internal_encoding();
        // 内部エンコーディングをUTF-8に
        mb_internal_encoding('UTF-8');
        // 文字列を検索用に変換
        if (!$enc) {
            $enc = mb_detect_encoding($str, 'SJIS-win,UTF-8,eucJP-win,JIS');
        }
        if ($enc != 'UTF-8') {
            $str = mb_convert_encoding($str, 'UTF-8', $enc);
        }
        $str = mb_convert_kana($str, 'KVas');
        $str = mb_convert_case($str, MB_CASE_LOWER);
        $str = trim($str);
        $str = preg_replace('/\s+/u', ' ', $str);
        // 内部エンコーディングを戻す
        mb_internal_encoding($incode);
        return $str;
    }

    // }}}
    // {{{ ic2_isError()

    function ic2_isError($url)
    {
        // ブラックリストをチェック
        $blacklist = &new IC2DB_BlackList;
        if ($blacklist->get($url)) {
            switch ($blacklist->type) {
                case 0:
                    return 'x05'; // No More
                case 1:
                    return 'x01'; // Aborn
                case 2:
                    return 'x04'; // Virus
                default:
                    return 'x06'; // Unknown
            }
        }

        // エラーログをチェック
        if ($this->_ini['Getter']['checkerror']) {
            $errlog = &new IC2DB_Errors;
            if ($errlog->get($url)) {
                return $errlog->errcode;
            }
        }

        return false;
    }

    // }}}

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
