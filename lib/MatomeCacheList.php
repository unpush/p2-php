<?php

// {{{ MatomeCacheList

/**
 * まとめ読みキャッシュリストクラス
 */
class MatomeCacheList
{
    // {{{ add()

    /**
     * 新しいエントリを追加する
     *
     * @param string $content
     * @param array $metaData
     * @param bool $isRawFile   trueなら$contentはファイル名で、その内容は
     *                          deflate圧縮+Base64エンコードされたデータ
     * @return string
     */
    static public function add($content, array $metaData, $isRawFile = false)
    {
        $key = sprintf('%s%0.6f', self::getKeyPrefix(), microtime(true));

        if ($isRawFile) {
            MatomeCacheDataStore::setRaw($key, file_get_contents($content));
        } else {
            MatomeCacheDataStore::set($key, $content);
        }

        MatomeCacheMetaDataStore::set($key, $metaData);

        return $key;
    }

    // }}}
    // {{{ getKeyPrefix()

    /**
     * キー接頭辞を取得する
     *
     * @param string $type
     * @return array
     */
    static public function getKeyPrefix($type = null)
    {
        global $_conf, $_login;

        if ($type === null) {
            if ($_conf['iphone']) {
                $type = 'iphone';
            } elseif ($_conf['ktai']) {
                $type = 'ktai';
            } else {
                $type = 'pc';
            }
        }

        return $_login->user_u . '/' . $type . '/';
    }

    // }}}
    // {{{ getList()

    /**
     * まとめ読みキャッシュのリストを取得する
     *
     * @param string $type
     * @return array
     */
    static public function getList($type = null)
    {
        $prefix = self::getKeyPrefix($type);
        $orderBy = array('mtime' => 'DESC', 'key' => 'DESC');

        return MatomeCacheMetaDataStore::getKVS()->getAll($prefix, $orderBy);
    }

    // }}}
    // {{{ getAllList()

    /**
     * 全まとめ読みキャッシュのリストを取得する
     *
     * @return array
     */
    static public function getAllList()
    {
        $types = array('pc', 'ktai', 'iphone');
        $lists = array();
        foreach ($types as $type) {
            $lists[$type] = self::getList($type);
        }
        return $list;
    }

    // }}}
    // {{{ trim()

    /**
     * 残す数を指定してキャッシュを削除する
     *
     * @param int $length
     * @param string $type
     * @return int
     */
    static public function trim($length, $type = null)
    {
        // $lengthが負数の場合は削除しない
        if ($length < 0) {
            return false;
        }

        $prefix = self::getKeyPrefix($type);

        // $lengthがゼロの場合は全件削除
        if ($length == 0) {
            $numRemoved = MatomeCacheDataStore::clear($prefix);
            if ($numRemoved === false) {
                return false;
            }
            MatomeCacheMetaDataStore::clear($prefix);
            return $numRemoved;
        }

        // 更新時刻順にソートして$length+1番目のレコードを取得
        $kvs = MatomeCacheDataStore::getKVS();
        $orderBy = array('mtime' => 'DESC', 'key' => 'DESC');
        $result = $kvs->getAll($prefix, $orderBy, 1, $length, true);
        if (empty($result)) {
            return 0;
        }

        $key = key($result);
        $mtime = current($result)->mtime;
        $query = 'DELETE FROM $__table WHERE '
               . P2KeyValueStore::C_KEY_BEGINS
               . ' AND $__mtime <= :mtime';

        // 見つかったレコードと、それより更新時刻が古いデータを削除
        $stmt = $kvs->prepare($query);
        $kvs->bindValueForPrefixSearch($stmt, $prefix);
        $stmt->bindValue(':mtime', $mtime, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $numRemoved = $stmt->rowCount();
        } else {
            return false;
        }

        // メタデータも削除
        $kvs = MatomeCacheMetaDataStore::getKVS();
        $stmt = $kvs->prepare($query);
        $kvs->bindValueForPrefixSearch($stmt, $prefix);
        $stmt->bindValue(':mtime', $mtime, PDO::PARAM_INT);
        if ($stmt->execute()) {
            if ($stmt->rowCount() != $numRemoved) {
                // メタデータの方が一瞬遅れて挿入されるため、ごく稀にデータの
                // mtimeとメタデータのmtimeが異なることがあり、ここに到達する
                $kvs->delete($key);
            }
        }

        // 削除したデータ数を返す
        return $numRemoved;
    }

    // }}}
    // {{{ optimize()

    /**
     * まとめ読みキャッシュを最適化する
     *
     * @param void
     * @return void
     */
    static public function optimize()
    {
        MatomeCacheDataStore::getKVS()->optimize();
        MatomeCacheMetaDataStore::getKVS()->optimize();
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
