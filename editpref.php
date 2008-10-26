<?php
/*
    p2 -  設定管理
*/

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/filectl.class.php';
require_once P2_LIB_DIR . '/P2View.php';

$_login->authorize(); // ユーザ認証

// 書き込んだレスのログをダウンロード
if (!empty($_GET['dl_res_hist_log'])) {
    header('Content-Type: text/plain; name=' . basename($_conf['p2_res_hist_dat']));
    header("Content-Disposition: attachment; filename=" . basename($_conf['p2_res_hist_dat']));
    readfile($_conf['p2_res_hist_dat']);
    exit;
}


// 書き込んだレスのログ削除
_clearResHistLogByQuery();

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

$hr = P2View::getHrHtmlK();

$edit_conf_user_atag = P2View::tagA(
    P2Util::buildQueryUri(
        'edit_conf_user.php',
        array(UA::getQueryKey() => UA::getQueryValue())
    ),
    hs('ユーザ設定編集')
);

// }}}

//=========================================================
// HTMLを表示する
//=========================================================
P2Util::headerNoCache();
P2View::printDoctypeTag();
?>
<html lang="ja">
<head>
<?php
P2View::printExtraHeadersHtml();
?>
<title><?php eh($ptitle); ?></title>
<?php

if (!$_conf['ktai']) {
    P2View::printIncludeCssHtml('style');
    P2View::printIncludeCssHtml('editpref');
    ?><link rel="shortcut icon" href="favicon.ico" type="image/x-icon"><?php
}
?>
</head>
<body<?php echo P2View::getBodyAttrK(); ?><?php echo $parent_reload; ?>>
<?php

if (!$_conf['ktai']) {
//<p id="pan_menu"><a href="setting.php">設定</a> &gt; {$ptitle}</p>
    ?><p id="pan_menu"><?php eh($ptitle); ?></p><?php
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

?>
<p>　・<?php echo $edit_conf_user_atag; ?></p>
<?php

// 携帯用表示
if ($_conf['ktai']) {
    echo $hr;
}

// PC
if (!$_conf['ktai']) {
    
    ?><table id="editpref"><?php
    
    // {{{ PC - NGワード編集
    
    ?><tr><td>

<fieldset>
<!-- <a href="http://akid.s17.xrea.com/p2puki/pukiwiki.php?%5B%5BNG%A5%EF%A1%BC%A5%C9%A4%CE%C0%DF%C4%EA%CA%FD%CB%A1%5D%5D" target="read">NGワード</a> -->
<legend>NGワード編集</legend>
<?php
    $sepa = ' | ';
    _printEditFileHtml($ng_name_txt, "名前");
    echo $sepa;
    _printEditFileHtml($ng_mail_txt, "メール");
    echo $sepa;
    _printEditFileHtml($ng_msg_txt, "メッセージ");
    echo $sepa;
    _printEditFileHtml($ng_id_txt, "&nbsp;ID&nbsp;");
    ?>
</fieldset>
<?php

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
_printMatomeCacheLinksHtml();


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

if (!$_conf['ktai']) {
    $dl_res_hist_log_atag = P2View::tagA(
        P2Util::buildQueryUri(
            basename($_SERVER['SCRIPT_NAME']),
            array(
                'dl_res_hist_log' => 1,
                UA::getQueryKey() => UA::getQueryValue()
            )
        ),
        hs('書き込んだレスの生ログを一括ダウンロードする')
    );
?>
<p>[<?php echo $dl_res_hist_log_atag; ?>]</p>
<?php
}

if ($_conf['ktai']) {
    echo $hr;
}

$clear_res_hist_log_atag = P2View::tagA(
    P2Util::buildQueryUri(
        basename($_SERVER['SCRIPT_NAME']),
        array(
            'clear_res_hist_log' => 1,
            UA::getQueryKey() => UA::getQueryValue()
        )
    ),
    hs('書き込んだレスのログを全て削除する')
);
?>
<p>[<?php echo $clear_res_hist_log_atag; ?>]</p>
<?php

// 携帯用フッタHTML
if (UA::isK()) {
    echo $hr . "\n";
    echo P2View::getBackToIndexKATag() . "\n";
}

?></body></html><?php


exit;


//==================================================================================
// 関数（このファイル内のみで利用）
//==================================================================================

/**
 * 書き込んだレスの削除
 */
function _clearResHistLogByQuery()
{
    if (!empty($_GET['clear_res_hist_log'])) {
        /*
        $atag = P2View::tagA(
            P2Util::buildQueryUri(
                basename($_SERVER['SCRIPT_NAME']),
                array(
                    'do_clear_res_hist_log' => 1,
                    'csrfid' => P2Util::getCsrfId(),
                    UA::getQueryKey() => UA::getQueryValue()
                )
            ),
            hs('はい、削除します')
        );
        */
        P2Util::pushInfoHtml(
            sprintf(
                '<form method="POST" action="%s">
                 <input type="hidden" name="do_clear_res_hist_log" value="1">
                 <input type="hidden" name="csrfid" value="%s">
                 <input type="hidden" name="%s" value="%s">
                 確認：本当に書き込んだレスのログを全て削除しても、よろしいですか？
                 <input type="submit" name="submit" value="はい、削除します">
                 </form>',
                hs(basename($_SERVER['SCRIPT_NAME'])),
                hs(P2Util::getCsrfId()),
                hs(UA::getQueryKey()), hs(UA::getQueryValue())
            )
        );

    } elseif (!empty($_POST['do_clear_res_hist_log'])) {
        if (!isset($_POST['csrfid']) or $_POST['csrfid'] != P2Util::getCsrfId()) {
            P2Util::pushInfoHtml('p2 error: 不正なクエリーです（CSRF対策）');
        } else {
            require_once P2_LIB_DIR . '/' . 'read_res_hist.inc.php';
            if (deleteResHistDat()) {
                P2Util::pushInfoHtml('<p>p2 info: ○書き込んだレスのログを削除しました</p>');
            } else {
                P2Util::pushInfoHtml('<p>p2 error: ×書き込んだレスのログを削除できませんでした</p>');
            }
        }
    }
}

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
            'path'      => $path_value,
            UA::getQueryKey() => UA::getQueryValue()
        );
        $url = $edit_php . '?' . P2Util::buildQuery($q_ar);
        $html = P2View::tagA($url, $submit_value) . "\n";
    
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
        if (strstr(geti($_SERVER['HTTP_USER_AGENT']), 'MSIE')) {
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

    if (strstr(geti($_SERVER['HTTP_USER_AGENT']), 'MSIE')) {
        $ht = '&nbsp;' . preg_replace('/>\s+</', '><', $ht);
    }
    return $ht;
}

/**
 * 新着まとめ読みのキャッシュリンクHTMLを表示する
 *
 * @return  void
 */
function _printMatomeCacheLinksHtml()
{
    global $_conf;
    
    $max = $_conf['matome_cache_max'];
    $links = array();
    for ($i = 0; $i <= $max; $i++) {
        $dnum = $i ? '.' . $i : '';
        $file = $_conf['matome_cache_path'] . $dnum . $_conf['matome_cache_ext'];
        //echo '<!-- ' . $file . ' -->';
        if (file_exists($file)) {
            $filemtime = filemtime($file);
            $date = date('Y/m/d G:i:s', $filemtime);
            $b = filesize($file) / 1024;
            $kb = round($b, 0);
            $atag = P2View::tagA(
                P2Util::buildQueryUri('read_new.php', array('cview' => '1', 'cnum' => "$i", 'filemtime' => $filemtime)),
                hs($date),
                array('target' => 'read')
            );
            $links[] = sprintf('%s %dKB', $atag, $kb);
        }
    }
    if ($links) {
        echo '<p>新着まとめ読みの前回キャッシュを表示<br>' . implode('<br>', $links) . '</p>' . "\n";
        
        if ($_conf['ktai']) {
            $hr = P2View::getHrHtmlK();
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
