<?php
/*
    p2 -  設定管理
*/

include_once './conf/conf.inc.php';  // 基本設定
require_once (P2_LIBRARY_DIR . '/filectl.class.php');

authorize(); // ユーザ認証

// {{{ ホストの同期用設定

if (!isset($rh_idx))     { $rh_idx     = $_conf['pref_dir'] . '/p2_res_hist.idx'; }
if (!isset($palace_idx)) { $palace_idx = $_conf['pref_dir'] . '/p2_palace.idx'; }

$synctitle = array(
    basename($_conf['favita_path'])  => 'お気に板',
    basename($_conf['favlist_file']) => 'お気にスレ',
    basename($_conf['rct_file'])     => '最近読んだスレ',
    basename($rh_idx)                => '書き込み履歴',
    basename($palace_idx)            => 'スレの殿堂'
);

// }}}
// {{{ 設定変更処理

// ホストの同期
if (isset($_POST['sync'])) {
    $syncfile = $_conf['pref_dir'].'/'.$_POST['sync'];
    $sync_name = $_POST['sync'];
    if ($syncfile == $_conf['favita_path']) {
        include_once (P2_LIBRARY_DIR . '/syncfavita.inc.php');
    } elseif (in_array($syncfile, array($_conf['favlist_file'], $_conf['rct_file'], $rh_idx, $palace_idx))) {
        include_once (P2_LIBRARY_DIR . '/syncindex.inc.php');
    }
    if ($sync_ok) {
        $_info_msg_ht .= "<p>{$synctitle[$sync_name]}を同期しました。</p>";
    } else {
        $_info_msg_ht .= "<p>{$synctitle[$sync_name]}は変更されませんでした。</p>";
    }
    unset($syncfile);
}

// }}}
// {{{ 書き出し用変数

$ptitle = '設定管理';

if ($_conf['ktai']) {
    $status_st = 'ｽﾃｰﾀｽ';
    $autho_user_st = '認証ﾕｰｻﾞ';
    $client_host_st = '端末ﾎｽﾄ';
    $client_ip_st = '端末IPｱﾄﾞﾚｽ';
    $browser_ua_st = 'ﾌﾞﾗｳｻﾞUA';
    $p2error_st = 'p2 ｴﾗｰ';
} else {
    $status_st = 'ステータス';
    $autho_user_st = '認証ユーザ';
    $client_host_st = '端末ホスト';
    $client_ip_st = '端末IPアドレス';
    $browser_ua_st = 'ブラウザUA';
    $p2error_st = 'p2 エラー';
}

$autho_user_ht = '';

// }}}

//=========================================================
// HTMLプリント
//=========================================================
P2Util::header_nocache();
P2Util::header_content_type();
if ($_conf['doctype']) {
    echo $_conf['doctype'];
}
echo <<<EOP
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>{$ptitle}</title>\n
EOP;
if(!$_conf['ktai']){
    @include("./style/style_css.inc");
    @include("./style/editpref_css.inc");
}
echo <<<EOP
</head>
<body>\n
EOP;

if (empty($_conf['ktai'])) {
//<p id="pan_menu"><a href="setting.php">設定</a> &gt; {$ptitle}</p>
    echo "<p id=\"pan_menu\">{$ptitle}</p>\n";
}


echo $_info_msg_ht;
$_info_msg_ht = '';

// 設定プリント =====================
$aborn_res_txt  = $_conf['pref_dir'] . '/p2_aborn_res.txt';
$aborn_name_txt = $_conf['pref_dir'] . '/p2_aborn_name.txt';
$aborn_mail_txt = $_conf['pref_dir'] . '/p2_aborn_mail.txt';
$aborn_msg_txt  = $_conf['pref_dir'] . '/p2_aborn_msg.txt';
$aborn_id_txt   = $_conf['pref_dir'] . '/p2_aborn_id.txt';
$ng_name_txt    = $_conf['pref_dir'] . '/p2_ng_name.txt';
$ng_mail_txt    = $_conf['pref_dir'] . '/p2_ng_mail.txt';
$ng_msg_txt     = $_conf['pref_dir'] . '/p2_ng_msg.txt';
$ng_id_txt      = $_conf['pref_dir'] . '/p2_ng_id.txt';

if (empty($_conf['ktai'])) {
    
    echo "<table id=\"editpref\">\n";
    
    // {{{ PC - NGワード編集
    echo "<tr><td>\n\n";
    
    echo <<<EOP
<fieldset>
<legend><a href="http://akid.s17.xrea.com:8080/p2puki/pukiwiki.php?%5B%5BNG%A5%EF%A1%BC%A5%C9%A4%CE%C0%DF%C4%EA%CA%FD%CB%A1%5D%5D" target="read">NGワード</a>編集</legend>
EOP;
    printEditFileForm($ng_name_txt, "名前");
    printEditFileForm($ng_mail_txt, "メール");
    printEditFileForm($ng_msg_txt, "メッセージ");
    printEditFileForm($ng_id_txt, " I D ");
    echo <<<EOP
</fieldset>\n\n
EOP;

    echo "</td>";
    // }}}
    // {{{ PC - あぼーんワード編集
    echo "<td>\n\n";

    echo <<<EOP
<fieldset>
<legend>あぼーんワード編集</legend>
EOP;
    printEditFileForm($aborn_name_txt, "名前");
    printEditFileForm($aborn_mail_txt, "メール");
    printEditFileForm($aborn_msg_txt, "メッセージ");
    printEditFileForm($aborn_id_txt, " I D ");
    echo <<<EOP
</fieldset>\n
EOP;

    echo "</td></tr>";
    // }}}
    // {{{ PC - その他 の設定
    echo "<td>\n\n";

    echo <<<EOP
<fieldset>
<legend>その他</legend>
EOP;
    printEditFileForm("conf/conf_user.inc.php", 'ユーザ設定');
    printEditFileForm("conf/conf_user_style.inc.php", 'デザイン設定');
    printEditFileForm("conf/conf.inc.php", '基本設定');
    echo <<<EOP
</fieldset>\n
EOP;
    // }}}

    echo "</td></tr>\n\n";
    $htm['sync'] = "<tr><td colspan=\"2\">\n\n";

    // {{{ PC - ホストの同期 HTMLのセット
    $htm['sync'] .= <<<EOP
<fieldset>
<legend>ホストの同期 （2chの板移転に対応します）</legend>
EOP;
    $exist_sync_flag = false;
    foreach ($synctitle as $syncpath => $syncname) {
        if (is_writable($_conf['pref_dir'].'/'.$syncpath)) {
            $exist_sync_flag = true;
            $htm['sync'] .= getSyncFavoritesFormHt($syncpath, $syncname);
        }
    }
    $htm['sync'] .= <<<EOP
</fieldset>\n
EOP;

    $htm['sync'] .= "</td></tr>\n\n";

    if ($exist_sync_flag) {
        echo $htm['sync'];
    } else {
        echo "&nbsp;";
        // echo "<p>ホストの同期は必要ありません</p>";
    }
    // }}}
    
    echo "</table>\n";
}

// 携帯用表示
if ($_conf['ktai']) {
    $htm['sync'] .= "<p>ﾎｽﾄの同期（2chの板移転に対応します）</p>\n";
    $exist_sync_flag = false;
    foreach ($synctitle as $syncpath => $syncname) {
        if (is_writable($_conf['pref_dir'].'/'.$syncpath)) {
            $exist_sync_flag = true;
            $htm['sync'] .= getSyncFavoritesFormHt($syncpath, $syncname);
        }
    }
    
    if ($exist_sync_flag) {
        echo $htm['sync'];
    } else {
        // echo "<p>ﾎｽﾄの同期は必要ありません</p>";
    }
}

// {{{ 新着まとめ読みのキャッシュ表示
$max = $_conf['matome_cache_max'];
for ($i = 0; $i <= $max; $i++) {
    $dnum = ($i) ? '.'.$i : '';
    $ai = '&amp;cnum='.$i;
    $file = $_conf['matome_cache_path'].$dnum.$_conf['matome_cache_ext'];
    //echo '<!-- '.$file.' -->';
    if (file_exists($file)) {
        $date = date('Y/m/d G:i:s', filemtime($file));
        $b = filesize($file)/1024;
        $kb = round($b, 0);
        $url = 'read_new.php?cview=1'.$ai;
        $links[] = '<a href="'.$url.'" target="read">'.$date.'</a> '.$kb.'KB';
    }
}
if (!empty($links)) {
    if ($_conf['ktai']) {
        echo '<hr>'."\n";
    }
    echo $htm['matome'] = '<p>新着まとめ読みの前回キャッシュを表示<br>' . implode('<br>', $links) . '</p>';
}
// }}}

// 携帯用フッタ
if ($_conf['ktai']) {
    echo "<hr>\n";
    echo $_conf['k_to_index_ht']."\n";
}

echo '</body></html>';

//=====================================================
// 関数
//=====================================================
/**
 * 設定ファイル編集ウインドウを開くフォームをプリントする
 */
function printEditFileForm($path_value, $submit_value)
{
    global $_conf;
    
    if ((file_exists($path_value) && is_writable($path_value)) ||
        (!file_exists($path_value) && is_writable(dirname($path_value)))
    ) {
        $onsubmit = '';
        $disabled = '';
    } else {
        $onsubmit = ' onsubmit="return false;"';
        $disabled = ' disabled';
    }
    
    $rows = 36; // 18
    $cols = 92; // 90
    
    $ht = <<<EOFORM
<form action="editfile.php" method="POST" target="editfile" class="inline-form"{$onsubmit}>
    {$_conf['k_input_ht']}
    <input type="hidden" name="path" value="{$path_value}">
    <input type="hidden" name="encode" value="Shift_JIS">
    <input type="hidden" name="rows" value="{$rows}">
    <input type="hidden" name="cols" value="{$cols}">
    <input type="submit" value="{$submit_value}"{$disabled}>
</form>\n
EOFORM;

    if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
        $ht = '&nbsp;' . preg_replace('/>\s+</', '><', $ht);
    }
    echo $ht;
}

/**
 * ホストの同期用フォームのHTMLを取得する
 */
function getSyncFavoritesFormHt($path_value, $submit_value)
{
    global $_conf;
    
    $ht = <<<EOFORM
<form action="editpref.php" method="POST" target="_self" class="inline-form">
    {$_conf['k_input_ht']}
    <input type="hidden" name="sync" value="{$path_value}">
    <input type="submit" value="{$submit_value}">
</form>\n
EOFORM;

    if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
        $ht = '&nbsp;' . preg_replace('/>\s+</', '><', $ht);
    }
    return $ht;
}

?>
