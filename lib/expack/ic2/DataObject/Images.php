<?php
require_once P2EX_LIB_DIR . '/ic2/loadconfig.inc.php';
require_once P2EX_LIB_DIR . '/ic2/DataObject/Common.php';
require_once P2EX_LIB_DIR . '/ic2/DataObject/BlackList.php';
require_once P2EX_LIB_DIR . '/ic2/DataObject/Errors.php';

// {{{ constants

define('P2_IMAGECACHE_OK',     0);
define('P2_IMAGECACHE_ABORN',  1);
define('P2_IMAGECACHE_BROKEN', 2);
define('P2_IMAGECACHE_LARGE',  3);
define('P2_IMAGECACHE_VIRUS',  4);

// }}}
// {{{ GLOBALS

$GLOBALS['_P2_GETIMAGE_CACHE'] = array();

// }}}
// {{{ IC2_DataObject_Images

class IC2_DataObject_Images extends IC2_DataObject_Common
{
    // {{{ constants

    const OK     = 0;
    const ABORN  = 1;
    const BROKEN = 2;
    const LARGE  = 3;
    const VIRUS  = 4;

    // }}}
    // {{{ constcurtor

    public function __construct()
    {
        parent::__construct();
        $this->__table = $this->_ini['General']['table'];
    }

    // }}}
    // {{{ table()

    public function table()
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

    public function keys()
    {
        return array('uri');
    }

    // }}}
    // {{{ uniform()

    // 検索用に文字列をフォーマットする
    public function uniform($str, $enc)
    {
        return self::staticUniform($str, $enc);
    }

    // }}}
    // {{{ ic2_isError()

    public function ic2_isError($url)
    {
        // ブラックリストをチェック
        $blacklist = new IC2_DataObject_BlackList;
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
            $errlog = new IC2_DataObject_Errors;
            if ($errlog->get($url)) {
                return $errlog->errcode;
            }
        }

        return FALSE;
    }

    // }}}
    // {{{ staticUniform()

    /**
     * 検索用に文字列をフォーマットする
     */
    static public function staticUniform($str, $enc)
    {
        // 内部エンコーディングを保存
        $incode = mb_internal_encoding();

        // 内部エンコーディングをUTF-8に
        mb_internal_encoding('UTF-8');

        // 文字列を検索用に変換
        if (!$enc) {
            $enc = mb_detect_encoding($str, 'CP932,UTF-8,CP51932,JIS');
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
