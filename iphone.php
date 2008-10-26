<?php
// rep2 -  インデックスページ

require_once './conf/conf.inc.php';
require_once './iphone/conf.inc.php';
require_once P2_LIB_DIR . '/filectl.class.php';

$_login->authorize(); // ユーザ認証

// 前処理
// アクセス拒否用の.htaccessをデータディレクトリに作成する
_makeDenyHtaccess($_conf['pref_dir']);
_makeDenyHtaccess($_conf['dat_dir']);
_makeDenyHtaccess($_conf['idx_dir']);

// 変数設定
$me_url = P2Util::getMyUrl();
$me_dir_url = dirname($me_url);


// url指定があれば、そのままスレッド読みへ飛ばす
if (!empty($_GET['url']) || !empty($_GET['nama_url'])) {
    header('Location: ' . $me_dir_url . '/' . $_conf['read_php'] . '?' . $_SERVER['QUERY_STRING']);
    exit;
}
require_once P2_IPHONE_LIB_DIR . '/index_print_k.inc.php';

index_print_k();

exit;

//============================================================================
// 関数（このファイル内でのみ利用）
//============================================================================
/**
 * ディレクトリに（アクセス拒否のための） .htaccess がなければ、自動で生成する
 *
 * @return  void
 */
function _makeDenyHtaccess($dir)
{
    $hta = $dir . '/.htaccess';
    if (!file_exists($hta)) {
        $data = 'Order allow,deny' . "\n"
              . 'Deny from all' . "\n";
        file_put_contents($hta, $data, LOCK_EX);
    }
}
?>