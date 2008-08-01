<?php
// p2 -  タイトルページ

include_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/filectl.class.php';

$_login->authorize(); // ユーザ認証

//=========================================================
// 変数
//=========================================================

if (!empty($GLOBALS['pref_dir_realpath_failed_msg'])) {
    $_info_msg_ht .= '<p>'.$GLOBALS['pref_dir_realpath_failed_msg'].'</p>';
}

$p2web_url_r = P2Util::throughIme($_conf['p2web_url']);
$expack_url_r = P2Util::throughIme($_conf['expack.web_url']);
$expack_dl_url_r = P2Util::throughIme($_conf['expack.download_url']);
$expack_hist_url_r = P2Util::throughIme($_conf['expack.history_url']);

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
        include_once P2_LIB_DIR . '/login2ch.inc.php';
        login2ch();
    }
}

//=========================================================
// プリント設定
//=========================================================
// 最新版チェック
if (!empty($_conf['updatan_haahaa'])) {
    $newversion_found = checkUpdatan();
}

// ログインユーザ情報
$htm['auth_user'] = "<p>ログインユーザ: {$_login->user_u} - " . date("Y/m/d (D) G:i") . "</p>\n";

// （携帯）ログイン用URL
//$user_u_q = empty($_conf['ktai']) ? '' : '?user=' . $_login->user_u;
//$url = rtrim(dirname(P2Util::getMyUrl()), '/') . '/' . $user_u_q . '&amp;b=k';
$url = rtrim(dirname(P2Util::getMyUrl()), '/') . '/?b=k';

$htm['ktai_url'] = '<p>携帯ログイン用URL <a href="'.$url.'" target="_blank">'.$url.'</a></p>'."\n";

// 前回のログイン情報
if ($_conf['login_log_rec'] && $_conf['last_login_log_show']) {
    if (($log = P2Util::getLastAccessLog($_conf['login_log_file'])) !== false) {
        $htm['log'] = array_map('htmlspecialchars', $log);
        $htm['last_login'] = <<<EOP
前回のログイン情報 - {$htm['log']['date']}<br>
ユーザ:     {$htm['log']['user']}<br>
IP:         {$htm['log']['ip']}<br>
HOST:       {$htm['log']['host']}<br>
UA:         {$htm['log']['ua']}<br>
REFERER:    {$htm['log']['referer']}
EOP;
    }
/*
    $htm['last_login'] =<<<EOP
<table cellspacing="0" cellpadding="2";>
    <tr>
        <td colspan="2">前回のログイン情報</td>
    </tr>
    <tr>
        <td align="right">時刻: </td><td>{$alog['date']}</td>
    </tr>
    <tr>
        <td align="right">ユーザ: </td><td>{$alog['user']}</td>
    </tr>
    <tr>
        <td align="right">IP: </td><td>{$alog['ip']}</td>
    </tr>
    <tr>
        <td align="right">HOST: </td><td>{$alog['host']}</td>
    </tr>
    <tr>
        <td align="right">UA: </td><td>{$alog['ua']}</td>
    </tr>
    <tr>
        <td align="right">REFERER: </td><td>{$alog['referer']}</td>
</table>
EOP;
*/
}

//=========================================================
// HTMLプリント
//=========================================================
$ptitle = "rep2 - title";

echo $_conf['doctype'];
echo <<<EOP
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    {$_conf['extra_headers_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <title>{$ptitle}</title>
    <base target="read">
    <link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
</head>
<body>\n
EOP;

// 情報メッセージ表示
if (!empty($_info_msg_ht)) {
    echo $_info_msg_ht;
    $_info_msg_ht = '';
}

echo <<<EOP
<br>
<div class="container">
    {$newversion_found}
    <p>rep2-expack rev.{$_conf['p2expack']}; extends rep2-{$_conf['p2version']}<br>
    <a href="{$expack_url_r}"{$_conf['ext_win_target_at']}>{$_conf['expack.web_url']}</a><br>
    <a href="{$p2web_url_r}"{$_conf['ext_win_target_at']}>{$_conf['p2web_url']}</a></p>
    <ul>
        <li><a href="viewtxt.php?file=doc/README.txt">README.txt</a></li>
        <li><a href="viewtxt.php?file=doc/README-EX.txt">README-EX.txt</a></li>
        <li><a href="img/how_to_use.png">ごく簡単な操作法</a></li>
        <li><a href="{$expack_hist_url_r}"{$_conf['ext_win_target_at']}>拡張パック 更新記録</a></li>
        <li><a href="viewtxt.php?file=doc/ChangeLog.txt">ChangeLog（rep2 更新記録）</a></li>
    </ul>
    {$htm['auth_user']}
    {$htm['ktai_url']}
    {$htm['last_login']}
</div>
</body>
</html>
EOP;

//==================================================
// 関数
//==================================================
/**
 * オンライン上のrep2-expack最新版をチェックする
 *
 * @return string HTML
 */
function checkUpdatan()
{
    global $_conf, $p2web_url_r, $expack_url_r, $expack_dl_url_r, $expack_hist_url_r;

    $no_p2status_dl_flag  = false;

    $ver_txt_url = $_conf['expack.web_url'] . 'expack-status2.txt';
    $cachefile = P2Util::cacheFileForDL($ver_txt_url);
    FileCtl::mkdir_for($cachefile);

    if (file_exists($cachefile)) {
        // キャッシュの更新が指定時間以内なら
        if (@filemtime($cachefile) > time() - $_conf['p2status_dl_interval'] * 60) {
            $no_p2status_dl_flag = true;
        }
    }

    if (empty($no_p2status_dl_flag)) {
        P2Util::fileDownload($ver_txt_url, $cachefile);
    }

    $ver_txt = file($cachefile);
    $update_ver = rtrim($ver_txt[0]);
    $kita = 'ｷﾀ━━━━（ﾟ∀ﾟ）━━━━!!!!!!';
    //$kita = 'ｷﾀ*･ﾟﾟ･*:.｡..｡.:*･ﾟ(ﾟ∀ﾟ)ﾟ･*:.｡. .｡.:*･ﾟﾟ･*!!!!!';

    $newversion_found_html = '';
    if ($update_ver && version_compare($update_ver, $_conf['p2expack'], '>')) {
        $newversion_found_html = <<<EOP
<div class="kakomi">
    {$kita}<br>
    オンライン上に 拡張パック の最新バージョンを見つけますた。<br>
    rep2-expack rev.{$update_ver} → <a href="{$expack_dl_url_r}"{$_conf['ext_win_target_at']}>ダウンロード</a> / <a href="{$expack_hist_url_r}"{$_conf['ext_win_target_at']}>更新記録</a>
</div>
<hr class="invisible">
EOP;
    }
    return $newversion_found_html;
}
