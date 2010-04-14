<?php
/**
 * rep2expack - STYLE変数をJSONエンコードするクラス
 */

// {{{ JStyle

class JStyle implements ArrayAccess
{
    // {{{ properties

    static private $_instance = null;
    private $_style = null;
    private $_cache = array();

    // }}}
    // {{{ singleton()

    /**
     * 規定のインスタンスを返す
     *
     * @param void
     * @return JStyle
     */
    static public function singleton()
    {
        if (self::$_instance === null) {
            self::$_instance = new JStyle;
        }
        return self::$_instance;
    }

    // }}}
    // {{{ encode()

    /**
     * 値をShift_JIS→UTF-8返還してからJSONエンコードする
     *
     * @param mixed $value
     * @return string
     */
    static public function encode($value)
    {
        mb_convert_variables('UTF-8', 'SJIS-win', $value);
        return json_encode($value);
    }

    // }}}
    // {{{ __construct()

    /**
     * コンストラクタ
     *
     * @param array $style
     */
    public function __construct(array $style = null)
    {
        if ($style === null) {
            $this->_style = $GLOBALS['STYLE'];
        } else {
            $this->_style = $style;
        }
    }

    // }}}
    // {{{ offsetExists()

    /**
     * キーに対応する値があるかを調べる
     *
     * @param string $key
     * @return boolean
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->_style);
    }

    // }}}
    // {{{ offsetGet()

    /**
     * キーに対応する値があればJSONエンコードして返す
     *
     * @param string $key
     * @return mixed
     * @fixme 複数の書体が指定されている場合のfont-familyについて
     */
    public function offsetGet($key)
    {
        if (!array_key_exists($key, $this->_style)) {
            return 'null';
        }

        if (!array_key_exists($key, $this->_cache)) {
            if ($key == 'info_pop_size' || $key == 'post_pop_size') {
                $width = 0;
                $height = 0;
                sscanf($this->_style[$key], '%u,%u', $width, $height);
                $this->_cache[$key] = sprintf('%u,%u', $width, $height);
            } else {
                $this->_cache[$key] = self::encode($this->_style[$key]);
            }
        }

        return $this->_cache[$key];
    }

    // }}}
    // {{{ offsetSet()

    /**
     * 何もしない
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
    }

    // }}}
    // {{{ offsetUnset()

    /**
     * キーに対応する値のJSONエンコードキャッシュを消去する
     *
     * @param string $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->_cache[$key]);
    }

    // }}}
    // {{{ clearCache()

    /**
     * すべてのJSONエンコードキャッシュを消去する
     *
     * @param void
     * @return void
     */
    public function clearCache()
    {
        $this->_cache = array();
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
