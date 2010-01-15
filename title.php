<?php
// p2 -  タイトルページ(PC用)

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/FileCtl.php';

$_login->authorize(); // ユーザ認証

//=========================================================
// 変数
//=========================================================

$p2web_url_r = P2Util::throughIme($_conf['p2web_url']);

// {{{ データ保存ディレクトリのパーミッションの注意を喚起する

P2Util::checkDirWritable($_conf['dat_dir']);
$checked_dirs[] = $_conf['dat_dir']; // チェック済みのディレクトリを格納する配列に

// まだチェックしていなければ
if (!in_array($_conf['idx_dir'], $checked_dirs)) {
    P2Util::checkDirWritable($_conf['idx_dir']);
    $checked_dirs[] = $_conf['idx_dir'];
}
if (!in_array($_conf['pref_dir'], $checked_dirs)) {
    P2Util::checkDirWritable($_conf['pref_dir']);
    $checked_dirs[] = $_conf['pref_dir'];
}

// }}}

//=========================================================
// 前処理
//=========================================================
// ●ID 2ch オートログイン
if ($array = P2Util::readIdPw2ch()) {
    list($login2chID, $login2chPW, $autoLogin2ch) = $array;
    if ($autoLogin2ch) {
        require_once P2_LIB_DIR . '/login2ch.func.php';
        login2ch();
    }
}

//=========================================================
// プリント設定
//=========================================================
// 最新版チェック
if ($_conf['updatan_haahaa']) {
    $newversion_found_html = _checkUpdatan();
} else {
    $newversion_found_html = '';
}

// ログインユーザ情報
$htm['auth_user'] = "<p>ログインユーザ: {$_login->user_u} - " . date("Y/m/d (D) G:i") . '</p>' . "\n";

// （携帯）ログイン用URL
$url = rtrim(dirname(P2Util::getMyUrl()), '/') . '/' . '?user=' . $_login->user_u . '&b=k';
$htm['ktai_url'] = '<p>携帯ログイン用URL <a href="' . hs($url) . '" target="_blank">' . hs($url) . '</a></p>' . "\n";

// 前回のログイン情報
if ($_conf['login_log_rec'] && $_conf['last_login_log_show']) {
    if (false !== $log = P2Util::getLastAccessLog($_conf['login_log_file'])) {
        $htm['log'] = array_map('htmlspecialchars', $log);
        $htm['last_login'] = <<<EOP
<div id="last_login">
前回のログイン情報 - {$htm['log']['date']}<br>
ユーザ:     {$htm['log']['user']}<br>
IP:         {$htm['log']['ip']}<br>
HOST:       {$htm['log']['host']}<br>
UA:         {$htm['log']['ua']}<br>
REFERER:    {$htm['log']['referer']}
<div>
EOP;
    }
}

//=========================================================
// HTML表示出力
//=========================================================
$ptitle = "rep2 - title";

P2View::printDoctypeTag();
?>
<html lang="ja">
<head>
<?php
P2View::printExtraHeadersHtml();
?>
	<title><?php eh($ptitle); ?></title>
	<base target="read">
    <?php
    P2View::printIncludeCssHtml('style');
    P2View::printIncludeCssHtml('title');
    ?>
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
<?php P2Util::printInfoHtml(); ?>

<div class="container">
	<?php echo $newversion_found_html; ?>

	<table border="0" cellspacing="0" cellpadding="0"><tr><td>
		<img src="img/rep2.gif" alt="rep2" width="119" height="63">
	</td><td style="padding-left:30px;">

	<p>rep2 version <?php eh($_conf['p2version']); ?> 　<a href="<?php eh($p2web_url_r); ?>" target="_blank"><?php eh($_conf['p2web_url']); ?></a></p>

	<ul>
		<li><a href="viewtxt.php?file=doc/README.txt">README.txt</a></li>
		<li><a href="img/how_to_use.png">ごく簡単な操作法</a></li>
		<li><a href="viewtxt.php?file=doc/ChangeLog.txt">ChangeLog（更新記録）</a></li>
	</ul>
	<!-- <p><a href="<?php eh($p2web_url_r); ?>" target="_blank">rep2 web &lt;<?php eh($_conf['p2web_url']); ?>&gt;</a></p> -->

	</td></tr></table>

	<?php echo $htm['auth_user']; ?>
	<?php echo $htm['ktai_url']; ?>
	<?php echo $htm['last_login']; ?>
</div>
</body></html>
<?php

exit;

//=======================================================================
// 関数 （このファイル内でのみ利用）
//=======================================================================
/**
 * オンライン上のrep2最新版をチェックする
 *
 * @return  string  HTML
 */
function _checkUpdatan()
{
    global $_conf, $p2web_url_r;

    $no_p2status_dl_flag  = false;
    
    $ver_txt_url = $_conf['p2web_url'] . 'p2status.txt';
    $cachefile = P2Util::cacheFileForDL($ver_txt_url);
    FileCtl::mkdirFor($cachefile);
    
    if (file_exists($cachefile)) {
        // キャッシュの更新が指定時間以内なら
        if (filemtime($cachefile) > time() - $_conf['p2status_dl_interval'] * 60) {
            $no_p2status_dl_flag = true;
        }
    }
    
    if (empty($no_p2status_dl_flag)) {
        P2Util::fileDownload($ver_txt_url, $cachefile);
    }
    
    $ver_txt = file($cachefile);
    $update_ver = $ver_txt[0];
    $kita = 'ｷﾀ━━━━（ﾟ∀ﾟ）━━━━!!!!!!';
    //$kita = 'ｷﾀ*･ﾟﾟ･*:.｡..｡.:*･ﾟ(ﾟ∀ﾟ)ﾟ･*:.｡. .｡.:*･ﾟﾟ･*!!!!!';
    
    $newversion_found_html = '';
    if ($update_ver && version_compare($update_ver, $_conf['p2version'], '>')) {
        $update_ver_hs = hs($update_ver);
        $p2web_url_r_hs = hs($p2web_url_r);
        $newversion_found_html = <<<EOP
<div class="kakomi">
    {$kita}<br>
    オンライン上に rep2 の最新バージョンを見つけますた。<br>
    rep2<!-- version {$update_ver_hs}--> → <a href="{$p2web_url_r_hs}cgi/dl/dl.php?dl=p2">ダウンロード</a> / <a href="{$p2web_url_r_hs}p2/doc/ChangeLog.txt"{$_conf['ext_win_target_at']}>更新記録</a>
</div>
<hr class="invisible">
EOP;
    }
    
    return $newversion_found_html;
}

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
