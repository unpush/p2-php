<?php
/**
 * rep2expack - 新着まとめ読みキャッシュ管理クラス
 */

// {{{ MatomeCacheDataStore

class MatomeCacheDataStore extends AbstractDataStore
{
    // {{{ getKVS()

    /**
     * まとめ読みデータを保存するP2KeyValueStoreオブジェクトを取得する
     *
     * @param void
     * @return P2KeyValueStore
     */
    static public function getKVS()
    {
        return self::_getKVS($GLOBALS['_conf']['matome_db_path'],
                             P2KeyValueStore::CODEC_COMPRESSING);
    }

    // }}}
    // {{{ AbstractDataStore.php からのコピペ / PHP 5.3 の遅延静的束縛を使って削除したい
    // {{{ get()

    /**
     * データを取得する
     *
     * @param string $key
     * @return mixed
     * @see P2KeyValueStore::get()
     */
    static public function get($key)
    {
        return self::getKVS()->get($key);
            // static::getKVS()
    }

    // }}}
    // {{{ set()

    /**
     * データを保存する
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     * @see P2KeyValueStore::exists(),
     *      P2KeyValueStore::set(),
     *      P2KeyValueStore::update()
     */
    static public function set($key, $value)
    {
        $kvs = self::getKVS();
            // static::getKVS()
        if ($kvs->exists($key)) {
            return $kvs->update($key, $value);
        } else {
            return $kvs->set($key, $value);
        }
    }

    // }}}
    // {{{ delete()

    /**
     * データを削除する
     *
     * @param string $key
     * @return bool
     * @see P2KeyValueStore::delete()
     */
    static public function delete($key)
    {
        return self::getKVS()->delete($key);
            // static::getKVS()
    }

    // }}}
    // {{{ clear()

    /**
     * すべてのデータまたはキーが指定された接頭辞で始まるデータを削除する
     *
     * @param string $prefix
     * @return int
     * @see P2KeyValueStore::clear()
     */
    static public function clear($prefix = null)
    {
        return self::getKVS()->clear($prefix);
            // static::getKVS();
    }

    // }}}
    // }}} コピペここまで
    // {{{ setRaw()

    /**
     * Codecによる変換なしでデータを保存する
     *
     * @param string $key
     * @param string $value
     * @return bool
     */
    static public function setRaw($key, $value)
    {
        $kvs = self::getKVS()->getRawKVS();
        if ($kvs->exists($key)) {
            return $kvs->update($key, $value);
        } else {
            return $kvs->set($key, $value);
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
