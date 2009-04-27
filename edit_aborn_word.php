<?php
/*
    p2 - あぼーんワード編集インタフェース
*/

include_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/FileCtl.php';

$_login->authorize(); // ユーザ認証

// フルパスではない ファイル名のみ
$path = isset($_REQUEST['path']) ? $_REQUEST['path'] : '';
$path_ht = htmlspecialchars($path, ENT_QUOTES);

if (!empty($_POST['submit_save']) || !empty($_POST['submit_default'])) {
    if (!isset($_POST['csrfid']) or $_POST['csrfid'] != P2Util::getCsrfId()) {
        p2die('不正なポストです');
    }
}

$writable_files = array(
    'p2_aborn_name.txt', 'p2_aborn_mail.txt', 'p2_aborn_msg.txt', 'p2_aborn_id.txt',
    'p2_ng_name.txt', 'p2_ng_mail.txt', 'p2_ng_msg.txt', 'p2_ng_id.txt',
    //'p2_aborn_res.txt',
);

if ($writable_files and (!in_array($path, $writable_files))) {
    $files_st = implode(' と ', $writable_files);
    $err = basename($_SERVER['SCRIPT_NAME']) . " 先生の書き込めるファイルは、{$files_st} だけ！";
    p2die($err);
}

$path = $_conf['pref_dir'] . '/' . $path;

//=====================================================================
// 前処理
//=====================================================================

$limit = $_conf['ngaborn_data_limit'];

// {{{ 保存ボタンが押されていたら、設定を保存

// @todo 正規表現の妥当性をチェックしたい

if (!empty($_POST['submit_save'])) {

    $newdata = array();
    foreach ($_POST['nga'] as $na_info) {
        $a_word = strtr(trim($na_info['word'], "\t\r\n"), "\t\r\n", "   ");
        $a_bbs  = strtr(trim($na_info['bbs'],  "\t\r\n"), "\t\r\n", "   ");
        $a_tt   = strtr(trim($na_info['tt'],   "\t\r\n"), "\t\r\n", "   ");
        $a_time = strtr(trim($na_info['ht']),  "\t\r\n", "   ");
        
        // 2007/03/12 データに '--' が入っているケースがあったので（時が経ち外せるようになれば外したい）
        if ($a_time == '--') {
            $a_time = '';
        }
        
        $a_hits = $na_info['hn'];
        if ($a_word === '') {
            continue;
        }
        
        if (!empty($na_info['re'])) {
            $a_mode = !empty($na_info['ic']) ? '<regex:i>' : '<regex>';
        } elseif (!empty($na_info['ic'])) {
            $a_mode = '<i>';
        } else {
            $a_mode = '';
        }
        if (strlen($a_bbs) > 0) {
            $a_mode .= '<bbs>' . $a_bbs . '</bbs>';
        }
        if (strlen($a_tt) > 0) {
            $a_mode .= '<title>' . $a_tt . '</title>';
        }
        $newdata[] = $a_mode . $a_word . "\t" . $a_time . "\t" . $a_hits . "\n";
    }
    
    if ($limit and count($newdata) > $limit) {
        $newdata = array_slice($newdata, -10);
        P2Util::pushInfoHtml(
            "<p><span style=\"color:red;\">注意: {$limit}個を超えるデータは登録できません。</span></p>"
        );
    }
    $newdata = implode('', $newdata);
    
    if (false !== file_put_contents($path, $newdata, LOCK_EX)) {
        P2Util::pushInfoHtml("<p>○設定を更新保存しました</p>");
    } else {
        P2Util::pushInfoHtml("<p>×設定を更新保存できませんでした</p>");
    }

// }}}
// {{{ デフォルトに戻すボタンが押されていたら

} elseif (!empty($_POST['submit_default'])) {
    if (@unlink($path)) {
        P2Util::pushInfoHtml("<p>○リストを空にしました</p>");
    } else {
        P2Util::pushInfoHtml("<p>×リストを空にできませんでした</p>");
    }
}

// }}}
// {{{ リスト読み込み

$formdata = array();
if (file_exists($path)) {
    $lines = file($path);
    $i = 0;
    foreach ($lines as $line) {
        $lar = explode("\t", rtrim($line, "\r\n"));
        if (strlen($lar[0]) == 0) {
            continue;
        }
        $ar = array(
            'cond' => $lar[0], // 検索条件
            'word' => $lar[0], // 対象文字列
            'ht'   => geti($lar[1]), // 最後にHITした時間
            'hn'   => geti($lar[2], 0), // HIT回数
            're'   => '', // 正規表現
            'ic'   => '', // 大文字小文字を無視
            'bbs'  => '', // 板
            'tt'   => '', // タイトル
        );
        // 板縛り
        if (preg_match('!<bbs>(.+?)</bbs>!', $ar['word'], $matches)) {
            $ar['bbs'] = $matches[1];
        }
        $ar['word'] = preg_replace('!<bbs>(.*)</bbs>!', '', $ar['word']);
        // タイトル縛り
        if (preg_match('!<title>(.+?)</title>!', $ar['word'], $matches)) {
            $ar['tt'] = $matches[1];
        }
        $ar['word'] = preg_replace('!<title>(.*)</title>!', '', $ar['word']);
        // 正規表現
        if (preg_match('/^<(mb_ereg|preg_match|regex)(:[imsxeADSUXu]+)?>(.*)$/', $ar['word'], $m)) {
            $ar['word'] = $m[3];
            $ar['re'] = ' checked';
            // 大文字小文字を無視
            if ($m[2] && strstr($m[2], 'i')) {
                $ar['ic'] = ' checked';
            }
        // 大文字小文字を無視
        } elseif (preg_match('/^<i>(.*)$/', $ar['word'], $m)) {
            $ar['word'] = $m[1];
            $ar['ic'] = ' checked';
        }
        if (strlen($ar['word']) == 0) {
            continue;
        }
        $formdata[$i++] = $ar;
    }
}

// }}}

//=====================================================================
// プリント設定
//=====================================================================
$ptitle_top_ht = sprintf(
    'NG/あぼーんワード編集 &gt; <a href="%s?path=%s">%s</a>',
    hs($_SERVER['SCRIPT_NAME']), rawurlencode(basename($path)), basename($path)
);
$ptitle_hs = strip_tags($ptitle_top_ht);

$csrfid = P2Util::getCsrfId();

//=====================================================================
// HTML出力
//=====================================================================
P2Util::headerNoCache();
P2View::printDoctypeTag();
?>
<html lang="ja">
<head>
<?php
P2View::printExtraHeadersHtml();
?>
	<title><?php echo ptitle_hs; ?></title>
<?php

if (UA::isPC()) {
    P2View::printIncludeCssHtml('style');
    P2View::printIncludeCssHtml('edit_conf_user');
    echo <<<EOP
    <script type="text/javascript" src="js/basic.js?{$_conf['p2version']}"></script>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">\n
EOP;
}

$body_at = P2View::getBodyAttrK();
if (UA::isPC()) {
    $body_at .= ' onload="top.document.title=self.document.title;"';
}

echo <<<EOP
</head>
<body{$body_at}>\n
EOP;

// PC用表示
if (UA::isPC()) {
    echo <<<EOP
<p id="pan_menu"><a href="{$_conf['editpref_php']}">設定管理</a> &gt; {$ptitle_top_ht}</p>\n
EOP;
} else {
    echo hs(basename($path)) . "<br>";
}

// PC用表示
if (!$_conf['ktai']) {
    $htm['form_submit'] = <<<EOP
        <tr class="group">
            <td colspan="6" align="center">
                <input type="submit" name="submit_save" value="変更を保存する">
                <input type="submit" name="submit_default" value="リストを空にする" onClick="if (!window.confirm('リストを空にしてもよろしいですか？（やり直しはできません）')) {return false;}"><br>
            </td>
        </tr>\n
EOP;
// 携帯用表示
} else {
    $htm['form_submit'] = <<<EOP
<input type="submit" name="submit_save" value="変更を保存する"><br>\n
EOP;
}

// 情報メッセージHTMLを表示する
P2Util::printInfoHtml();

$attent_ht = '';
if ($_conf['ngaborn_data_limit']) {
    $attent_ht = "<li>登録できるデータ件数は、{$_conf['ngaborn_data_limit']}個までです。</li>";
}

$usage = <<<EOP
<ul style="font-size:10pt;">
	{$attent_ht}
	<li>「NGワード」は見えなくしたということを打ち消し線でわかるようにしています。クリックすると、その場で表示確認できます。</li>
	<li>「あぼーんワード」は完全に見えなくします。いわゆる透明あぼーん状態。</li>
	<li>ワード: NG/あぼーんワード (空にすると登録解除)</li>
	<li>i: 大文字小文字を無視</li>
	<li>re: 正規表現</li>
	<li>板: newsplus,software 等 (完全一致, カンマ区切り)</li>
	<li>スレタイ: スレッドタイトル (部分一致, 常に大文字小文字を無視)</li>
</ul>
EOP;
if ($_conf['ktai']) {
    $usage = mb_convert_kana($usage, 'k');
}
echo <<<EOP
{$usage}
<form method="POST" action="{$_SERVER['SCRIPT_NAME']}" target="_self" accept-charset="{$_conf['accept_charset']}">
	<input type="hidden" name="detect_hint" value="◎◇">
	<input type="hidden" name="path" value="{$path_ht}">
	<input type="hidden" name="csrfid" value="{$csrfid}">
	{$_conf['k_input_ht']}\n
EOP;

// PC用表示（table）
if (!$_conf['ktai']) {
    echo <<<EOP
    <table class="edit_conf_user" cellspacing="0">
        <tr>
            <td align="center">ワード</td>
            <td align="center">i</td>
            <td align="center">re</td>
            <td align="center">板</td>
            <td align="center">スレタイ</td>
            <td align="center">最終ヒット日時と回数</td>
        </tr>
        <tr class="group">
            <td colspan="6">新規登録</td>
        </tr>\n
EOP;
    $row_format = <<<EOP
        <tr>
            <td><input type="text" size="35" name="nga[%1\$d][word]" value="%2\$s"></td>
            <td><input type="checkbox" name="nga[%1\$d][ic]" value="1"%3\$s></td>
            <td><input type="checkbox" name="nga[%1\$d][re]" value="1"%4\$s></td>
            <td><input type="text" size="12" name="nga[%1\$d][bbs]" value="%7\$s"></td>
            <td><input type="text" size="16" name="nga[%1\$d][tt]" value="%8\$s"></td>
            <td align="right">
                <input type="hidden" name="nga[%1\$d][ht]" value="%5\$s">%9\$s
                <input type="hidden" name="nga[%1\$d][hn]" value="%6\$d">(%6\$d)
            </td>
        </tr>\n
EOP;
// 携帯用表示
} else {
    echo "新規登録<br>\n";
    $row_format = <<<EOP
<input type="text" name="nga[%1\$d][word]" value="%2\$s"><br>
板:<input type="text" size="5" name="nga[%1\$d][bbs]" value="%7\$s">
ｽﾚﾀｲ:<input type="text" size="5" name="nga[%1\$d][tt]" value="%8\$s"><br>
<input type="checkbox" name="nga[%1\$d][ic]" value="1"%3\$s>i
<input type="checkbox" name="nga[%1\$d][re]" value="1"%4\$s>re
<input type="hidden" name="nga[%1\$d][ht]" value="%5\$s">
<input type="hidden" name="nga[%1\$d][hn]" value="%6\$d">(%6\$d)<br>\n
EOP;
}

printf($row_format, -1, '', '', '', '', 0, '', '', '--');

echo $htm['form_submit'];

if (!empty($formdata)) {
    foreach ($formdata as $k => $v) {
        printf($row_format,
            $k,
            htmlspecialchars($v['word'], ENT_QUOTES),
            $v['ic'],
            $v['re'],
            htmlspecialchars($v['ht'], ENT_QUOTES),
            $v['hn'],
            htmlspecialchars($v['bbs'], ENT_QUOTES),
            htmlspecialchars($v['tt'], ENT_QUOTES),
            (strlen($v['ht']) > 0) ? htmlspecialchars($v['ht'], ENT_QUOTES) : '--'  // 更新時間 表示用
        );
    }
    echo $htm['form_submit'];
}

// PCなら
if (!$_conf['ktai']) {
    echo '</table>'."\n";
}

?>
</form>
<?php

// 携帯なら
if ($_conf['ktai']) {
    $hr = P2View::getHrHtmlK();
    echo <<<EOP
$hr
<a {$_conf['accesskey_for_k']}="{$_conf['k_accesskey']['up']}" href="{$_conf['editpref_php']}{$_conf['k_at_q']}">{$_conf['k_accesskey']['up']}.設定編集</a>
EOP;
    echo P2View::getBackToIndexKATag();
}

?>
</body></html>
<?php

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
