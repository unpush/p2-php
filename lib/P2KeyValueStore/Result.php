<?php

// {{{ P2KeyValueStore_Result

/**
 * P2KeyValueStoreでPDOStatement::fetch()の結果を格納する
 */
class P2KeyValueStore_Result implements ArrayAccess
{
    // {{{ properties

    /**
     * PDO::FETCH_INTO で代入されるプロパティ
     * P2KeyValueStore で使うテーブルの各カラム名と同じ
     */
    public $id;
    public $arkey;
    public $value;
    public $mtime;
    public $sort_order;

    /**
     * ArrayAccessのメソッドでアクセスできるキーと
     * 実際のプロパティ名の対応表
     */
    static protected $_keyMap = array(
        0 => 'id',
        1 => 'arkey',
        2 => 'value',
        3 => 'mtime',
        4 => 'sort_order',
        'id' => 'id',
        'key' => 'arkey',
        'arkey' => 'arkey',
        'value' => 'value',
        'mtime' => 'mtime',
        'order' => 'sort_order',
        'sort_order' => 'sort_order',
    );

    // }}}
    // {{{ isExpired()

    /**
     * 有効期限切れのチェックを行う
     *
     * @param int $lifeTime
     * @return bool
     */
    public function isExpired($lifeTime)
    {
        if ($lifeTime > -1 && $this->mtime < time() - $lifeTime) {
            return true;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ toArray()

    /**
     * 結果セットを連想配列に変換する
     *
     * @param P2KeyValueStore_Codec_Interface $codec
     * @return array
     */
    public function toArray(P2KeyValueStore_Codec_Interface $codec = null)
    {
        if ($codc === null) {
            $key = $this->arkey;
            $value = $this->value;
        } else {
            $key = $codec->decodeKey($this->arkey);
            $value = $codec->decodeValue($this->value);
        }

        return array(
            'id' => (int)$this->id,
            'key' => $key,
            'value' => $value,
            'mtime' => (int)$this->mtime,
            'order' => (int)$this->sort_order,
        );
    }

    // }}}
    // {{{ toObject()

    /**
     * 結果セットを別の任意のオブジェクトに変換する
     *
     * @param P2KeyValueStore_Codec_Interface $codec
     * @param mixed $object
     * @return array
     */
    public function toObject($object = null, P2KeyValueStore_Codec_Interface $codec = null)
    {
        $properties = $this->toArray($codec);

        if ($object === null) {
            return (object)$properties; // stdClass
        }

        if (!is_object($object)) {
            $object = new $object;
        }
        foreach ($properties as $key => $value) {
            $object->$key = $value;
        }

        return $object;
    }

    // }}}
    // {{{ clear()

    /**
     * プロパティをリセットする
     *
     * @param void
     * @return void
     */
    public function clear()
    {
        $this->id = null;
        $this->arkey = null;
        $this->value = null;
        $this->mtime = null;
        $this->sort_order = null;
    }

    // }}}
    // {{{ offsetExists()

    /**
     * ArrayAccess::offsetExists()
     *
     * @param string $offset
     * @return string
     */
    public function offsetExists($offset)
    {
        return self::_resolveOffset($offset) !== false;
    }

    // }}}
    // {{{ offsetGet()

    /**
     * ArrayAccess::offsetGet()
     *
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if ($name = self::_resolveOffset($offset)) {
            return $this->$name;
        } else {
            return null;
        }
    }

    // }}}
    // {{{ offsetSet()

    /**
     * ArrayAccess::offsetSet()
     *
     * @param string $offset
     * @param string $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if ($name = self::_resolveOffset($offset)) {
            $this->$name = $value;
        }
    }

    // }}}
    // {{{ offsetUnset()

    /**
     * ArrayAccess::offsetUnset()
     *
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        if ($name = self::_resolveOffset($offset)) {
            $this->$name = null;
        }
    }

    // }}}
    // {{{ _resolveOffset()

    /**
     * ArrayAccessのメソッドに与えられたオフセットと
     * 実際のプロパティ名の解決をする
     *
     * @param string $offset
     * @return string
     */
    static protected function _resolveOffset($offset)
    {
        if (array_key_exists($offset, self::$_keyMap)) {
            return self::$_keyMap[$offset];
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
