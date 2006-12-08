<?php
/*
    ImageCache2 - メンテナンス
*/

// {{{ p2基本設定読み込み&認証

require_once 'conf/conf.inc.php';

$_login->authorize();

if (!$_conf['expack.ic2.enabled']) {
    exit('<html><body><p>ImageCache2は無効です。<br>conf/conf_admin_ex.inc.php の設定を変えてください。</p></body></html>');
}

// }}}
// {{{ 初期化

// ライブラリ読み込み
require_once 'PEAR.php';
require_once 'DB.php';
require_once 'HTML/Template/Flexy.php';
require_once P2EX_LIBRARY_DIR . '/ic2/loadconfig.inc.php';

// 設定読み込み
$ini = ic2_loadconfig();

// データベースに接続
$db = &DB::connect($ini['General']['dsn']);
if (DB::isError($db)) {
    die('<html><body><p>'.$result->getMessage().'</p></body></html>');
}

// テンプレートエンジン初期化
$_flexy_options = array(
    'locale' => 'ja',
    'charset' => 'cp932',
    'compileDir' => $ini['General']['cachedir'] . '/' . $ini['General']['compiledir'],
    'templateDir' => P2EX_LIBRARY_DIR . '/ic2/templates',
    'numberFormat' => '', // ",0,'.',','" と等価
);

$flexy = &new HTML_Template_Flexy($_flexy_options);

// }}}
// {{{ データベース操作・ファイル削除

if (isset($_POST['action'])) {
    switch ($_POST['action']) {

        case 'dropZero':
        case 'dropAborn':
            require_once P2EX_LIBRARY_DIR . '/ic2/managedb.inc.php';

            if ($_POST['action'] == 'dropZero') {
                $where = $db->quoteIdentifier('rank') . ' = 0';
                if (isset($_POST['dropZeroLimit'])) {
                    switch ($_POST['dropZeroSelectTime']) {
                        case '24hours': $expires = 86400; break;
                        case 'aday':    $expires = 86400; break;
                        case 'aweek':   $expires = 86400 * 7; break;
                        case 'amonth':  $expires = 86400 * 31; break;
                        case 'ayear':   $expires = 86400 * 365; break;
                        default: $expires = null;
                    }
                    if ($expires !== null) {
                        $operator = ($_POST['dropZeroSelectType'] == 'within') ? '>' : '<';
                        $where .= sprintf(' AND %s %s %d',
                            $db->quoteIdentifier('time'),
                            $operator,
                            time() - $expires);
                    }
                }
                $to_blacklist = !empty($_POST['dropZeroToBlackList']);
            } else {
                $where = $db->quoteIdentifier('rank') . ' < 0';
                $to_blacklist = true;
            }

            $sql = sprintf('SELECT %s FROM %s WHERE %s;',
                $db->quoteIdentifier('id'),
                $db->quoteIdentifier($ini['General']['table']),
                $where);
            $result = $db->getAll($sql, null, DB_FETCHMODE_ORDERED | DB_FETCHMODE_FLIPPED);
            if (DB::isError($result)) {
                P2Util::pushInfoMsgHtml($result->getMessage());
                break;
            }
            $target = $result[0];
            $removed_files = manageDB_remove($target, $to_blacklist);
            $flexy->setData('toBlackList', $to_blacklist);
            break;

        case 'clearThumb':
            $thumb_dir2 = $ini['General']['cachedir'] . '/' . $ini['Thumb2']['name'];
            $thumb_dir3 = $ini['General']['cachedir'] . '/' . $ini['Thumb3']['name'];
            $result_files2 = FileCtl::garbageCollection($thumb_dir2, -1, '', '', true);
            $result_files3 = FileCtl::garbageCollection($thumb_dir3, -1, '', '', true);
            $removed_files = array_merge($result_files2['successed'], $result_files3['successed']);
            $failed_files = array_merge($result_files2['failed'], $result_files3['failed']);
            if (!empty($failed_files)) {
                P2Util::pushInfoMsgHtml('<p>以下のファイルが削除できませんでした。</p>');
                P2Util::pushInfoMsgHtml('<ul><li>');
                P2Util::pushInfoMsgHtml(implode('</li><li>', array_map('htmlspecialchars', $failed_files)));
                P2Util::pushInfoMsgHtml('</li></ul>');
            }
            break;

        case 'clearCache':
            $result = $db->query('DELETE FROM ' . $db->quoteIdentifier($ini['Cache']['table']));
            if (DB::isError($result)) {
                P2Util::pushInfoMsgHtml($result->getMessage());
            } else {
                P2Util::pushInfoMsgHtml("<p>テーブル {$ini['Cache']['table']} を空にしました。</p>");
            }
            $result_files = FileCtl::garbageCollection($flexy->options['compileDir'], -1, '', '', true);
            $removed_files = $result_files['successed'];
            if (!empty($result_files['failed'])) {
                P2Util::pushInfoMsgHtml('<p>以下のファイルが削除できませんでした。</p>');
                P2Util::pushInfoMsgHtml('<ul><li>');
                P2Util::pushInfoMsgHtml(implode('</li><li>', array_map('htmlspecialchars', $result_files['failed'])));
                P2Util::pushInfoMsgHtml('</li></ul>');
            }
            break;

        case 'clearErrorLog':
            $result = $db->query('DELETE FROM ' . $db->quoteIdentifier($ini['General']['error_table']));
            if (DB::isError($result)) {
                P2Util::pushInfoMsgHtml($result->getMessage());
            } else {
                P2Util::pushInfoMsgHtml('<p>エラーログを消去しました。</p>');
            }
            break;

        case 'clearBlackList':
            $result = $db->query('DELETE FROM ' . $db->quoteIdentifier($ini['General']['blacklist_table']));
            if (DB::isError($result)) {
                P2Util::pushInfoMsgHtml($result->getMessage());
            } else {
                P2Util::pushInfoMsgHtml('<p>ブラックリストを消去しました。</p>');
            }
            break;

        case 'vacuumDB':
            if ($db->dsn['phptype'] == 'sqlite') {
                $db_file = $db->dsn['database'];
                $size_b = filesize($db_file);
                $result = $db->query('VACUUM');
                if (DB::isError($result)) {
                    P2Util::pushInfoMsgHtml($result->getMessage());
                } else {
                    clearstatcache();
                    $size_a = filesize($db_file);
                    P2Util::pushInfoMsgHtml(sprintf('<p>VACUUM実行、ファイルサイズ: %s → %s (-%s)',
                        number_format($size_b),
                        number_format($size_a),
                        number_format($size_b - $size_a)));
                }
            }
            break;

        default:
            P2Util::pushInfoMsgHtml('<p>不正なクエリ: ' . htmlspecialchars($_POST['action'], ENT_QUOTES) . '</p>');

    }
    if (isset($removed_files)) {
        $flexy->setData('removedFiles', $removed_files);
    }
}

// }}}
// {{{ 出力

$flexy->setData('skin', $skin_en);
$flexy->setData('php_self', $_SERVER['SCRIPT_NAME']);
$flexy->setData('info_msg', P2Util::getInfoMsgHtml());
if ($db->dsn['phptype'] == 'sqlite') {
    $flexy->setData('isSQLite', true);
}

P2Util::header_nocache();
$flexy->compile('ic2mng.tpl.html');
$flexy->output();

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
