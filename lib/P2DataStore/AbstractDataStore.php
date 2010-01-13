<?php
/**
 * rep2expack - P2KeyValueStoreをラップする
 * ユーティリティクラスのための基底抽象クラス
 *
 * P2KeyValueStoreはrep2に依存せず単体で使えるが、
 * P2DataStoreはrep2で使うために設計されている。
 */

// {{{ AbstractDataStore

abstract class AbstractDataStore
{
    // {{{ properties

    /**
     * P2KeyValueStoreオブジェクトを保持する連想配列
     *
     * @var array
     */
    static private $_kvs = array();

    // }}}
    // {{{ _getKVS()

    /**
     * データを保存するP2KeyValueStoreオブジェクトを取得する
     *
     * @param string $databasePath
     * @param string $type
     * @return P2KeyValueStore
     */
    static protected function _getKVS($databasePath,
                                      $type = P2KeyValueStore::CODEC_SERIALIZING)
    {
        global $_conf;

        $id = $type . ':' . $databasePath;

        if (array_key_exists($id, self::$_kvs)) {
            return self::$_kvs[$id];
        }

        if (!file_exists($databasePath) && !is_dir(dirname($databasePath))) {
            FileCtl::mkdir_for($databasePath);
        }

        try {
            $kvs = P2KeyValueStore::getStore($databasePath, $type);
            self::$_kvs[$id] = $kvs;
        } catch (Exception $e) {
            p2die(get_class($e) . ': ' . $e->getMessage());
        }

        return $kvs;
    }

    // }}}
    // {{{ getKVS()

    /**
     * _getKVS() を呼び出してP2KeyValueStoreオブジェクトを取得する
     *
     * 現在は self::getKVS() の都合でこれより下のメソッドを
     * サブクラスにコピペという保守性が極めて悪い実装となっている。
     * 将来は PHP 5.3 縛りにして static::getKVS() に変更したい。
     *
     * @param void
     * @return P2KeyValueStore
     */
    abstract static public function getKVS();

    // }}}
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
        $kvs = self::getKVS();

        if ($prefix === null) {
            return $kvs->clear();
        }

        $pattern = str_replace(array(  '%',   '_',   '\\'),
                               array('\\%', '\\_', '\\\\'),
                               $kvs->encodeKey($prefix));
        $query = 'DELETE FROM $__table WHERE $__key LIKE :pattern ESCAPE :escape';
        $stmt = $kvs->prepare($query);
        $stmt->bindValue(':pattern', $pattern);
        $stmt->bindValue(':escape', '\\');

        if ($stmt->execute()) {
            return $stmt->rowCount();
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
