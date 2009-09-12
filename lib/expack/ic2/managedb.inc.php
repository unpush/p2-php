<?php
/**
 * ImageCache2 - 画像情報を操作する関数
 */

require_once P2EX_LIB_DIR . '/ic2/DataObject/Common.php';
require_once P2EX_LIB_DIR . '/ic2/DataObject/Images.php';
require_once P2EX_LIB_DIR . '/ic2/DataObject/BlackList.php';
require_once P2EX_LIB_DIR . '/ic2/Thumbnailer.php';

// {{{ manageDB_update()

/**
 * 画像情報を更新
 */
function manageDB_update($updated)
{
    if (empty($updated)) {
        return;
    }
    if (!is_array($updated)) {
        global $_info_msg_ht;
        $_info_msg_ht .= '<p>WARNING! manageDB_update(): 不正な引数</p>';
        return;
    }

    // トランザクションの開始
    $ta = new IC2_DataObject_Images;
    $db = $ta->getDatabaseConnection();
    if ($db->phptype == 'pgsql') {
        $ta->query('BEGIN');
    } elseif ($db->phptype == 'sqlite') {
        $db->query('BEGIN;');
    }

    // 画像データを更新
    foreach ($updated as $id => $data) {
        $icdb = new IC2_DataObject_Images;
        $icdb->whereAdd("id = $id");
        if ($icdb->find(true)) {
            // メモを更新
            if ($icdb->memo != $data['memo']) {
                $memo = new IC2_DataObject_Images;
                $memo->memo = (strlen($data['memo']) > 0) ? $data['memo'] : '';
                $memo->whereAdd("id = $id");
                $memo->update();
            }
            // ランクを更新
            if ($icdb->rank != $data['rank']) {
                $rank = new IC2_DataObject_Images;
                $rank->rank = $data['rank'];
                $rank->whereAddQuoted('size', '=', $icdb->size);
                $rank->whereAddQuoted('md5',  '=', $icdb->md5);
                $rank->whereAddQuoted('mime', '=', $icdb->mime);
                $rank->update();
            }
        }
    }

    // トランザクションのコミット
    if ($db->phptype == 'pgsql') {
        $ta->query('COMMIT');
    } elseif ($db->phptype == 'sqlite') {
        $db->query('COMMIT;');
    }
}

// }}}
// {{{ manageDB_remove()

/**
 * 画像を削除
 */
function manageDB_remove($target, $to_blacklist = false)
{
    $removed_files = array();
    if (empty($target)) {
        return $removed_files;
    }
    if (!is_array($target)) {
        if (is_integer($target) || ctype_digit($target)) {
            $id = (int) $target;
            if ($id > 0) {
                $target = array($id);
            } else {
                return $removed_files;
            }
        } else {
            global $_info_msg_ht;
            $_info_msg_ht .= '<p>WARNING! manageDB_remove(): 不正な引数</p>';
            return $removed_files;
        }
    }

    // トランザクションの開始
    $ta = new IC2_DataObject_Images;
    $db = $ta->getDatabaseConnection();
    if ($db->phptype == 'pgsql') {
        $ta->query('BEGIN');
    } elseif ($db->phptype == 'sqlite') {
        $db->query('BEGIN;');
    }

    // 画像を削除
    foreach ($target as $id) {
        $icdb = new IC2_DataObject_Images;
        $icdb->whereAdd("id = {$id}");

        if ($icdb->find(true)) {
            // キャッシュしているファイルを削除
            $t1 = new IC2_Thumbnailer(IC2_Thumbnailer::SIZE_PC);
            $t2 = new IC2_Thumbnailer(IC2_Thumbnailer::SIZE_MOBILE);
            $t3 = new IC2_Thumbnailer(IC2_Thumbnailer::SIZE_INTERMD);
            $srcPath = $t1->srcPath($icdb->size, $icdb->md5, $icdb->mime);
            $t1Path = $t1->thumbPath($icdb->size, $icdb->md5, $icdb->mime);
            $t2Path = $t2->thumbPath($icdb->size, $icdb->md5, $icdb->mime);
            $t3Path = $t3->thumbPath($icdb->size, $icdb->md5, $icdb->mime);
            if (file_exists($srcPath)) {
                unlink($srcPath);
                $removed_files[] = $srcPath;
            }
            if (file_exists($t1Path)) {
                unlink($t1Path);
                $removed_files[] = $t1Path;
            }
            if (file_exists($t2Path)) {
                unlink($t2Path);
                $removed_files[] = $t2Path;
            }
            if (file_exists($t3Path)) {
                unlink($t3Path);
                $removed_files[] = $t3Path;
            }

            // ブラックリスト送りの準備
            if ($to_blacklist) {
                $_blacklist = new IC2_DataObject_BlackList;
                $_blacklist->size = $icdb->size;
                $_blacklist->md5  = $icdb->md5;
                if ($icdb->mime == 'clamscan/infected' || $icdb->rank == -4) {
                    $_blacklist->type = 2;
                } elseif ($icdb->rank < 0) {
                    $_blacklist->type = 1;
                } else {
                    $_blacklist->type = 0;
                }
            }

            // 同一画像を検索
            $remover = new IC2_DataObject_Images;
            $remover->whereAddQuoted('size', '=', $icdb->size);
            $remover->whereAddQuoted('md5',  '=', $icdb->md5);
            //$remover->whereAddQuoted('mime', '=', $icdb->mime); // SizeとMD5で十分
            $remover->find();
            while ($remover->fetch()) {
                // ブラックリスト送りにする
                if ($to_blacklist) {
                    $blacklist = clone $_blacklist;
                    $blacklist->uri = $remover->uri;
                    $blacklist->insert();
                }
                // テーブルから抹消
                $remover->delete();
            }
        }
    }

    // トランザクションのコミット
    if ($db->phptype == 'pgsql') {
        $ta->query('COMMIT');
    } elseif ($db->phptype == 'sqlite') {
        $db->query('COMMIT;');
    }

    return $removed_files;
}

// }}}
// {{{ manageDB_setRank()

/**
 * ランクを設定
 */
function manageDB_setRank($target, $rank)
{
    if (empty($target)) {
        return;
    }
    if (!is_array($target)) {
        if (is_integer($updated) || ctype_digit($updated)) {
            $id = (int)$updated;
            if ($id > 0) {
                $updated = array($id);
            } else {
                return;
            }
        } else {
            global $_info_msg_ht;
            $_info_msg_ht .= '<p>WARNING! manageDB_setRank(): 不正な引数</p>';
            return $removed_files;
        }
    }

    $icdb = new IC2_DataObject_Images;
    $icdb->rank = $rank;
    foreach ($target as $id) {
        $icdb->whereAdd("id = $id", 'OR');
    }
    $icdb->update();
}

// }}}
// {{{ manageDB_addMemo()

/**
 * メモを追加
 */
function manageDB_addMemo($target, $memo)
{
    if (empty($target)) {
        return;
    }
    if (!is_array($target)) {
        if (is_integer($updated) || ctype_digit($updated)) {
            $id = (int)$updated;
            if ($id > 0) {
                $updated = array($id);
            } else {
                return;
            }
        } else {
            global $_info_msg_ht;
            $_info_msg_ht .= '<p>WARNING! manageDB_addMemo(): 不正な引数</p>';
            return $removed_files;
        }
    }

    // トランザクションの開始
    $ta = new IC2_DataObject_Images;
    $db = $ta->getDatabaseConnection();
    if ($db->phptype == 'pgsql') {
        $ta->query('BEGIN');
    } elseif ($db->phptype == 'sqlite') {
        $db->query('BEGIN;');
    }

    // メモに指定文字列が含まれていなければ更新
    foreach ($target as $id) {
        $find = new IC2_DataObject_Images;
        $find->whereAdd("id = $id");
        if ($find->find(true) && strpos($find->memo, $memo) === false) {
            $update = new IC2_DataObject_Images;
            $update->whereAdd("id = $id");
            if (strlen($find->memo) > 0) {
                $update->memo = $find->memo . ' ' . $memo;
            } else {
                $update->memo = $memo;
            }
            $update->update();
            unset($update);
        }
        unset($find);
    }

    // トランザクションのコミット
    if ($db->phptype == 'pgsql') {
        $ta->query('COMMIT');
    } elseif ($db->phptype == 'sqlite') {
        $db->query('COMMIT;');
    }
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
