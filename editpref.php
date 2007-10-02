<?php
/*
    p2 -  設定管理
*/

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/filectl.class.php';

$_login->authorize(); // ユーザ認証

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

// ホストを同期する
if (isset($_POST['sync'])) {
    require_once P2_LIB_DIR . '/BbsMap.class.php';
    $syncfile = $_conf['pref_dir'] . '/' . $_POST['sync'];
    $sync_name = $_POST['sync'];
    if ($syncfile == $_conf['favita_path']) {
        BbsMap::syncBrd($syncfile);
    } elseif (in_array($syncfile, array($_conf['favlist_file'], $_conf['rct_file'], $rh_idx, $palace_idx))) {
        BbsMap::syncIdx($syncfile);
    }
}

$parent_reload = '';
if (isset($_GET['reload_skin'])) {
    $parent_reload = " onload=\"parent.menu.location.href='./{$_conf['menu_php']}'; parent.read.location.href='./first_cont.php';\"";
}

// }}}
// {{{ 書き出し用変数

$ptitle = '設定管理';

if ($_conf['ktai']) {
    $status_st      = 'ｽﾃｰﾀｽ';
    $autho_user_st  = '認証ﾕｰｻﾞ';
    $client_host_st = '端末ﾎｽﾄ';
    $client_ip_st   = '端末IPｱﾄﾞﾚｽ';
    $browser_ua_st  = 'ﾌﾞﾗｳｻﾞUA';
    $p2error_st     = 'p2 ｴﾗｰ';
} else {
    $status_st      = 'ステータス';
    $autho_user_st  = '認証ユーザ';
    $client_host_st = '端末ホスト';
    $client_ip_st   = '端末IPアドレス';
    $browser_ua_st  = 'ブラウザUA';
    $p2error_st     = 'p2 エラー';
}

$autho_user_ht = '';

$body_at = P2Util::getBodyAttrK();
$hr = P2Util::getHrHtmlK();

// }}}

//=========================================================
// HTMLを表示する
//=========================================================
P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOP
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>{$ptitle}</title>\n
EOP;

if (!$_conf['ktai']) {
    include_once './style/style_css.inc';
    include_once './style/editpref_css.inc';
}
echo <<<EOP
</head>
<body{$body_at}{$parent_reload}>\n
EOP;

if (!$_conf['ktai']) {
//<p id="pan_menu"><a href="setting.php">設定</a> &gt; {$ptitle}</p>
    echo "<p id=\"pan_menu\">{$ptitle}</p>\n";
}


P2Util::printInfoHtml();

$aborn_res_txt  = $_conf['pref_dir'] . '/p2_aborn_res.txt';
$aborn_name_txt = $_conf['pref_dir'] . '/p2_aborn_name.txt';
$aborn_mail_txt = $_conf['pref_dir'] . '/p2_aborn_mail.txt';
$aborn_msg_txt  = $_conf['pref_dir'] . '/p2_aborn_msg.txt';
$aborn_id_txt   = $_conf['pref_dir'] . '/p2_aborn_id.txt';
$ng_name_txt    = $_conf['pref_dir'] . '/p2_ng_name.txt';
$ng_mail_txt    = $_conf['pref_dir'] . '/p2_ng_mail.txt';
$ng_msg_txt     = $_conf['pref_dir'] . '/p2_ng_msg.txt';
$ng_id_txt      = $_conf['pref_dir'] . '/p2_ng_id.txt';

echo <<<EOP
<p>　<a href="edit_conf_user.php{$_conf['k_at_q']}">ユーザ設定編集</a></p>
EOP;

// 携帯用表示
if ($_conf['ktai']) {
    echo $hr;
}

// PC
if (!$_conf['ktai']) {
    
    echo "<table id=\"editpref\">\n";
    
    // {{{ PC - NGワード編集
    
    echo "<tr><td>\n\n";
    
    echo <<<EOP
<fieldset>
<!-- <a href="http://akid.s17.xrea.com/p2puki/pukiwiki.php?%5B%5BNG%A5%EF%A1%BC%A5%C9%A4%CE%C0%DF%C4%EA%CA%FD%CB%A1%5D%5D" target="read">NGワード</a> -->
<legend>NGワード編集</legend>
EOP;
    $sepa = ' | ';
    _printEditFileHtml($ng_name_txt, "名前");
    echo $sepa;
    _printEditFileHtml($ng_mail_txt, "メール");
    echo $sepa;
    _printEditFileHtml($ng_msg_txt, "メッセージ");
    echo $sepa;
    _printEditFileHtml($ng_id_txt, "&nbsp;ID&nbsp;");
    echo <<<EOP
</fieldset>\n\n
EOP;

    echo "</td>";
    
    // }}}
    // {{{ PC - あぼーんワード編集
    
    echo "<td>\n\n";

    echo <<<EOP
<fieldset>
<legend>あぼーんワード編集</legend>\n
EOP;
    _printEditFileHtml($aborn_name_txt, "名前");
    echo $sepa;
    _printEditFileHtml($aborn_mail_txt, "メール");
    echo $sepa;
    _printEditFileHtml($aborn_msg_txt, "メッセージ");
    echo $sepa;
    _printEditFileHtml($aborn_id_txt, "&nbsp;ID&nbsp;");
    echo <<<EOP
</fieldset>\n
EOP;

    echo "</td></tr>";
    
    // }}}
    // {{{ PC - その他 の設定
    
    /*
    php は editfile しない
    
    echo <<<EOP
<fieldset>
<legend>その他</legend>
EOP;
    _printEditFileHtml("conf/conf_user_style.inc.php", 'デザイン設定');
    _printEditFileHtml("conf/conf.inc.php", '基本設定');
    echo <<<EOP
</fieldset>\n
EOP;
    */
    
    // }}}
    
    echo "</table>\n";
}

// 携帯用表示 NG/ｱﾎﾞﾝﾜｰﾄﾞ
if ($_conf['ktai']) {
    $ng_name_txt_bn     = basename($ng_name_txt);
    $ng_mail_txt_bn     = basename($ng_mail_txt);
    $ng_msg_txt_bn      = basename($ng_msg_txt);
    $ng_id_txt_bn       = basename($ng_id_txt);
    $aborn_name_txt_bn  = basename($aborn_name_txt);
    $aborn_mail_txt_bn  = basename($aborn_mail_txt);
    $aborn_msg_txt_bn   = basename($aborn_msg_txt);
    $aborn_id_txt_bn    = basename($aborn_id_txt);
    echo <<<EOP
<p>NG/ｱﾎﾞﾝﾜｰﾄﾞ編集</p>
<form method="GET" action="edit_aborn_word.php">
{$_conf['k_input_ht']}
<select name="path">
	<option value="{$ng_name_txt_bn}">NG:名前</option>
	<option value="{$ng_mail_txt_bn}">NG:ﾒｰﾙ</option>
	<option value="{$ng_msg_txt_bn}">NG:ﾒｯｾｰｼﾞ</option>
	<option value="{$ng_id_txt_bn}">NG:ID</option>
	<option value="{$aborn_name_txt_bn}">ｱﾎﾞﾝ:名前</option>
	<option value="{$aborn_mail_txt_bn}">ｱﾎﾞﾝ:ﾒｰﾙ</option>
	<option value="{$aborn_msg_txt_bn}">ｱﾎﾞﾝ:ﾒｯｾｰｼﾞ</option>
	<option value="{$aborn_id_txt_bn}">ｱﾎﾞﾝ:ID</option>
</select>
<input type="submit" value="編集">
</form>
$hr
EOP;

}

// 新着まとめ読みのキャッシュリンクHTMLを表示する
printMatomeCacheLinksHtml();


// PC - ホストの同期 HTMLを表示 

if (!$_conf['ktai']) {

    $sync_htm = <<<EOP
<table><tr><td>
<fieldset>
<legend>ホストの同期</legend>
2chの板移転に対応します。通常は自動で行われるので、この操作は特に必要ありません<br>
EOP;

    $exist_sync_flag = false;
    foreach ($synctitle as $syncpath => $syncname) {
        if (is_writable($_conf['pref_dir'] . '/' . $syncpath)) {
            $exist_sync_flag = true;
            $sync_htm .= _getSyncFavoritesFormHtml($syncpath, $syncname);
        }
    }

    $sync_htm .= <<<EOP
</fieldset>
</td></tr></table>\n
EOP;

    if ($exist_sync_flag) {
        echo $sync_htm;
    } else {
        echo "&nbsp;";
        // echo "<p>ホストの同期は必要ありません</p>";
    }

// 携帯用表示
} else {
    $sync_htm = "<p>ﾎｽﾄの同期<br>（2chの板移転に対応します。通常は自動で行われるので、この操作は特に必要ありません）</p>\n";
    $exist_sync_flag = false;
    foreach ($synctitle as $syncpath => $syncname) {
        if (is_writable($_conf['pref_dir'] . '/' . $syncpath)) {
            $exist_sync_flag = true;
            $sync_htm .= _getSyncFavoritesFormHtml($syncpath, $syncname);
        }
    }
    
    if ($exist_sync_flag) {
        echo $sync_htm;
    } else {
        // echo "<p>ﾎｽﾄの同期は必要ありません</p>";
    }
}


// 携帯用フッタHTML
if ($_conf['ktai']) {
    echo "$hr\n";
    echo $_conf['k_to_index_ht'] . "\n";
}

echo '</body></html>';


exit;


//==============================================================================
// 関数（このファイル内のみで利用）
//==============================================================================
/**
 * 設定ファイル編集ウインドウを開くHTMLを表示する
 *
 * @return  void
 */
function _printEditFileHtml($path_value, $submit_value)
{
    global $_conf;
    
    // アクティブ
    if ((file_exists($path_value) && is_writable($path_value)) || (!file_exists($path_value) && is_writable(dirname($path_value)))) {
        $onsubmit = '';
        $disabled = '';
    
    // 非アクティブ
    } else {
        $onsubmit = ' onsubmit="return false;"';
        $disabled = ' disabled';
    }
    
    $rows = 36; // 18
    $cols = 92; // 90

    // edit_aborn_word.php
    if (preg_match('/^p2_(aborn|ng)_(name|mail|id|msg)\.txt$/', basename($path_value))) {
        $edit_php = 'edit_aborn_word.php';
        $target = '_self';
        $path_value = basename($path_value);
        
        $q_ar = array(
            'path'      => $path_value
        );
        isset($_conf['b']) and $q_ar['b'] = $_conf['b'];
        $url = $edit_php . '?' . http_build_query($q_ar);
        $html = P2Util::tagA($url, $submit_value) . "\n";
    
    // editfile.php
    } else {
        $edit_php = 'editfile.php';
        $target = 'editfile';
        
        $html = <<<EOFORM
<form action="{$edit_php}" method="POST" target="{$target}" class="inline-form"{$onsubmit}>
	{$_conf['k_input_ht']}
	<input type="hidden" name="path" value="{$path_value}">
	<input type="hidden" name="encode" value="Shift_JIS">
	<input type="hidden" name="rows" value="{$rows}">
	<input type="hidden" name="cols" value="{$cols}">
	<input type="submit" value="{$submit_value}"{$disabled}>
</form>\n
EOFORM;
        // IE用にform内のタグ間の空白を除去　する
        if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
            $html = '&nbsp;' . preg_replace('{>\s+<}', '><', $html);
        }
    }
    
    echo $html;
}

/**
 * ホストの同期用フォームのHTMLを取得する
 *
 * @return  string
 */
function _getSyncFavoritesFormHtml($path_value, $submit_value)
{
    global $_conf;
    
    $ht = <<<EOFORM
<form action="editpref.php" method="POST" target="_self" class="inline-form">
    {$_conf['k_input_ht']}
    <input type="hidden" name="sync" value="{$path_value}">
    <input type="submit" value="{$submit_value}">
</form>

EOFORM;

    if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
        $ht = '&nbsp;' . preg_replace('/>\s+</', '><', $ht);
    }
    return $ht;
}

/**
 * 新着まとめ読みのキャッシュリンクHTMLを表示する
 *
 * @return  void
 */
function printMatomeCacheLinksHtml()
{
    global $_conf;
    
    $max = $_conf['matome_cache_max'];
    $links = array();
    for ($i = 0; $i <= $max; $i++) {
        $dnum = $i ? '.' . $i : '';
        $ai = '&amp;cnum=' . $i;
        $file = $_conf['matome_cache_path'] . $dnum . $_conf['matome_cache_ext'];
        //echo '<!-- ' . $file . ' -->';
        if (file_exists($file)) {
            $filemtime = filemtime($file);
            $date = date('Y/m/d G:i:s', $filemtime);
            $b = filesize($file) / 1024;
            $kb = round($b, 0);
            $url = 'read_new.php?cview=1' . $ai . '&amp;filemtime=' . $filemtime;
            $links[] = '<a href="' . $url . '" target="read">' . $date . '</a> ' . $kb . 'KB';
        }
    }
    if ($links) {
        echo '<p>新着まとめ読みの前回キャッシュを表示<br>' . implode('<br>', $links) . '</p>' . "\n";
        
        if ($_conf['ktai']) {
            $hr = P2Util::getHrHtmlK();
            echo $hr . "\n";
        }
    }
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
