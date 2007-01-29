<?php
// rep2 -  インデックスページ

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/filectl.class.php';

$_login->authorize(); // ユーザ認証

//=============================================================
// 前処理
//=============================================================
// アクセス拒否用の.htaccessをデータディレクトリに作成する
makeDenyHtaccess($_conf['pref_dir']);
makeDenyHtaccess($_conf['dat_dir']);
makeDenyHtaccess($_conf['idx_dir']);

//=============================================================
$me_url = P2Util::getMyUrl();
$me_dir_url = dirname($me_url);

if ($_conf['ktai']) {

    //=========================================================
    // 携帯用 インデックス
    //=========================================================
    // url指定があれば、そのままスレッド読みへ飛ばす
    if (!empty($_GET['url']) || !empty($_GET['nama_url'])) {
        header('Location: ' . $me_dir_url . '/' . $_conf['read_php'] . '?' . $_SERVER['QUERY_STRING']);
        exit;
    }
    require_once P2_LIB_DIR . '/index_print_k.inc.php';
    index_print_k();
    
} else {
    //=========================================
    // PC用 変数
    //=========================================
    $title_page = 'title.php';

    if (!empty($_GET['url']) || !empty($_GET['nama_url'])) {
        $read_page = $_conf['read_php'] . "?" . $_SERVER['QUERY_STRING'];
    } else {
        if (!empty($_conf['first_page'])) {
            $read_page = $_conf['first_page'];
        } else {
            $read_page = 'first_cont.php';
        }
    }
    
    $sidebar = isset($_GET['sidebar']) ? $_GET['sidebar'] : null;
    
    $ptitle = "rep2";
    //======================================================
    // PC用 HTMLプリント
    //======================================================
    P2Util::header_nocache();
    echo <<<EOHEADER
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"
        "http://www.w3.org/TR/html4/frameset.dtd">
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>{$ptitle}</title>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>\n
EOHEADER;

    if (!$sidebar) {
		?>
		<frameset cols="<?php echo htmlspecialchars($_conf['frame_menu_width']); ?>,*" frameborder="1" border="1">
    <frame src="<?php echo htmlspecialchars($_conf['menu_php']); ?>" name="menu" scrolling="auto">
		<?php
    }
    
	?>
    <frameset id="fsright" name="fsright" rows="<?php echo htmlspecialchars($_conf['frame_subject_width']); ?>,<?php echo htmlspecialchars($_conf['frame_read_width']); ?>" frameborder="1" border="2">
        <frame id="subject" name="subject" src="<?php echo htmlspecialchars($title_page); ?>" scrolling="auto">
        <frame id="read" name="read" src="<?php echo htmlspecialchars($read_page); ?>" scrolling="auto">
    </frameset>
	<?php

    if (!$sidebar) {
        echo '</frameset>' . "\n";
    }
    
    echo '</html>';

}

//============================================================================
// 関数（このファイル内でのみ利用）
//============================================================================
/**
 * ディレクトリに（アクセス拒否のための） .htaccess がなければ、自動で生成する
 *
 * @return  void
 */
function makeDenyHtaccess($dir)
{
    $hta = $dir . '/.htaccess';
    if (!file_exists($hta)) {
        $data = 'Order allow,deny' . "\n"
              . 'Deny from all' . "\n";
        FileCtl::file_write_contents($hta, $data);
    }
}
