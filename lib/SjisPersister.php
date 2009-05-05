<?php
require_once dirname(__FILE__) . '/KeyValuePersister.php';

// {{{ SjisPersister

/**
 * Shift_JISの文字列をUTF-8に変換して永続化する
 */
class SjisPersister extends KeyValuePersister
{
    // {{{ _encode()

    /**
     * Shift_JIS (CP932) の文字列をUTF-8に変換する
     *
     * @param string $str
     * @return string
     */
    private function _encode($str)
    {
        return mb_convert_encoding($str, 'UTF-8', 'CP932');
    }

    // }}}
    // {{{ _decode()

    /**
     * UTF-8の文字列をShift_JIS (CP932) に変換する
     *
     * @param string $str
     * @return string
     */
    private function _decode($str)
    {
        return mb_convert_encoding($str, 'CP932', 'UTF-8');
    }

    // }}}
    // {{{ _encodeKey()

    /**
     * キーを変換する
     *
     * @param string $key
     * @return string
     */
    protected function _encodeKey($key)
    {
        return $this->_encode($key);
    }

    // }}}
    // {{{ _decodeKey()

    /**
     * キーを変換する
     *
     * @param string $key
     * @return string
     */
    protected function _decodeKey($key)
    {
        return $this->_decode($key);
    }

    // }}}
    // {{{ _encodeValue()

    /**
     * 値を変換する
     *
     * @param string $value
     * @return string
     */
    protected function _encodeValue($value)
    {
        return $this->_encode($value);
    }

    // }}}
    // {{{ _decodeValue()

    /**
     * 値を変換する
     *
     * @param string $value
     * @return string
     */
    protected function _decodeValue($value)
    {
        return $this->_decode($value);
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
