<?php
/*
    p2 - ‚ ‚Ú[‚ñƒ[ƒh•ÒWƒCƒ“ƒ^ƒtƒF[ƒX
*/

include_once './conf/conf.inc.php';
require_once P2_LIBRARY_DIR . '/filectl.class.php';

$_login->authorize(); // ƒ†[ƒU”FØ

$path = isset($_REQUEST['path']) ? $_REQUEST['path'] : '';
$path_ht = htmlspecialchars($path, ENT_QUOTES);

if (!empty($_POST['submit_save']) || !empty($_POST['submit_default'])) {
    if (!isset($_POST['csrfid']) or $_POST['csrfid'] != P2Util::getCsrfId()) {
        die('p2 error: •s³‚Èƒ|ƒXƒg‚Å‚·');
    }
}

$writable_files = array(
    "p2_aborn_name.txt", "p2_aborn_mail.txt", "p2_aborn_msg.txt", "p2_aborn_id.txt",
    "p2_ng_name.txt", "p2_ng_mail.txt", "p2_ng_msg.txt", "p2_ng_id.txt",
    //"p2_aborn_res.txt",
);

if ($writable_files and (!in_array(basename($path), $writable_files))) {
    $i = 0;
    foreach ($writable_files as $afile) {
        if ($i != 0) {
            $files_st .= "‚Æ";
        }
        $files_st .= "u".$afile."v";
        $i++;
    }
    die("Error: ".basename($_SERVER['SCRIPT_NAME'])." æ¶‚Ì‘‚«‚ß‚éƒtƒ@ƒCƒ‹‚ÍA".$files_st."‚¾‚¯I");
}

//=====================================================================
// ‘Oˆ—
//=====================================================================

// {{{ ¡•Û‘¶ƒ{ƒ^ƒ“‚ª‰Ÿ‚³‚ê‚Ä‚¢‚½‚çAİ’è‚ğ•Û‘¶

if (!empty($_POST['submit_save'])) {

    $newdata = '';
    foreach ($_POST['nga'] as $na_info) {
        $a_word = strtr(trim($na_info['word'], "€t€r€n"), "€t€r€n", "   ");
        $a_bbs = strtr(trim($na_info['bbs'], "€t€r€n"), "€t€r€n", "   ");
        $a_tt = strtr(trim($na_info['tt'], "€t€r€n"), "€t€r€n", "   ");
        $a_time = strtr(trim($na_info['ht']), "€t€r€n", "   ");
        if ($a_time === '') {
            $a_time = '--';
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
        $newdata .= $a_mode . $a_word . "€t" . $a_time . "€t" . $a_hits . "€n";
    }
    if (FileCtl::file_write_contents($path, $newdata) !== FALSE) {
        $_info_msg_ht .= "<p>›İ’è‚ğXV•Û‘¶‚µ‚Ü‚µ‚½</p>";
    } else {
        $_info_msg_ht .= "<p>~İ’è‚ğXV•Û‘¶‚Å‚«‚Ü‚¹‚ñ‚Å‚µ‚½</p>";
    }

// }}}
// {{{ ¡ƒfƒtƒHƒ‹ƒg‚É–ß‚·ƒ{ƒ^ƒ“‚ª‰Ÿ‚³‚ê‚Ä‚¢‚½‚ç

} elseif (!empty($_POST['submit_default'])) {
    if (@unlink($path)) {
        $_info_msg_ht .= "<p>›ƒŠƒXƒg‚ğ‹ó‚É‚µ‚Ü‚µ‚½</p>";
    } else {
        $_info_msg_ht .= "<p>~ƒŠƒXƒg‚ğ‹ó‚É‚Å‚«‚Ü‚¹‚ñ‚Å‚µ‚½</p>";
    }
}

// }}}
// {{{ ƒŠƒXƒg“Ç‚İ‚İ

$formdata = array();
if (file_exists($path)) {
    $lines = file($path);
    $i = 0;
    foreach ($lines as $line) {
        $lar = explode("€t", rtrim($line, "€r€n"));
        if (count($lar) < 3 || strlen($lar[0]) == 0) {
            continue;
        }
        $ar = array(
            'cond' => $lar[0], // ŒŸõğŒ
            'word' => $lar[0], // ‘ÎÛ•¶š—ñ
            'ht' => $lar[1], // ÅŒã‚ÉHIT‚µ‚½ŠÔ
            'hn' => $lar[2], // HIT‰ñ”
            're' => '', // ³‹K•\Œ»
            'ic' => '', // ‘å•¶š¬•¶š‚ğ–³‹
            'bbs' => '', // ”Â
            'tt' => '', // ƒ^ƒCƒgƒ‹
        );
        // ”Â”›‚è
        if (preg_match('!<bbs>(.+?)</bbs>!', $ar['word'], $matches)) {
            $ar['bbs'] = $matches[1];
        }
        $ar['word'] = preg_replace('!<bbs>(.*)</bbs>!', '', $ar['word']);
        // ƒ^ƒCƒgƒ‹”›‚è
        if (preg_match('!<title>(.+?)</title>!', $ar['word'], $matches)) {
            $ar['tt'] = $matches[1];
        }
        $ar['word'] = preg_replace('!<title>(.*)</title>!', '', $ar['word']);
        // ³‹K•\Œ»
        if (preg_match('/^<(mb_ereg|preg_match|regex)(:[imsxeADSUXu]+)?>(.*)$/', $ar['word'], $m)) {
            $ar['word'] = $m[3];
            $ar['re'] = ' checked';
            // ‘å•¶š¬•¶š‚ğ–³‹
            if ($m[2] && strstr($m[2], 'i')) {
                $ar['ic'] = ' checked';
            }
        // ‘å•¶š¬•¶š‚ğ–³‹
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
// ƒvƒŠƒ“ƒgİ’è
//=====================================================================
$ptitle_top = sprintf('‚ ‚Ú[‚ñ/NGƒ[ƒh•ÒW &gt; <a href="%s?path=%s">%s</a>',
    $_SERVER['SCRIPT_NAME'], rawurlencode($path), basename($path));
$ptitle = strip_tags($ptitle_top);

$csrfid = P2Util::getCsrfId();

//=====================================================================
// ƒvƒŠƒ“ƒg
//=====================================================================
// ƒwƒbƒ_HTML‚ğƒvƒŠƒ“ƒg
P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOP
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>{$ptitle}</title>€n
EOP;

if (!$_conf['ktai']) {
    echo <<<EOP
    <script type="text/javascript" src="js/basic.js?{$_conf['p2expack']}"></script>
    <link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
    <link rel="stylesheet" href="css.php?css=edit_conf_user&amp;skin={$skin_en}" type="text/css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">€n
EOP;
}

$body_at = ($_conf['ktai']) ? $_conf['k_colors'] : ' onLoad="top.document.title=self.document.title;"';
echo <<<EOP
</head>
<body{$body_at}>€n
EOP;

// PC—p•\¦
if (!$_conf['ktai']) {
    echo <<<EOP
<p id="pan_menu"><a href="editpref.php">İ’èŠÇ—</a> &gt; {$ptitle_top}</p>€n
EOP;
} else {
    echo basename($path) . "<br>";
}

// PC—p•\¦
if (!$_conf['ktai']) {
    $htm['form_submit'] = <<<EOP
        <tr class="group">
            <td colspan="6" align="center">
                <input type="submit" name="submit_save" value="•ÏX‚ğ•Û‘¶‚·‚é">
                <input type="submit" name="submit_default" value="ƒŠƒXƒg‚ğ‹ó‚É‚·‚é" onClick="if (!window.confirm('ƒŠƒXƒg‚ğ‹ó‚É‚µ‚Ä‚à‚æ‚ë‚µ‚¢‚Å‚·‚©Hi‚â‚è’¼‚µ‚Í‚Å‚«‚Ü‚¹‚ñj')) {return false;}"><br>
            </td>
        </tr>€n
EOP;
// Œg‘Ñ—p•\¦
} else {
    $htm['form_submit'] = <<<EOP
<input type="submit" name="submit_save" value="•ÏX‚ğ•Û‘¶‚·‚é"><br>€n
EOP;
}

// î•ñƒƒbƒZ[ƒW•\¦
if (!empty($_info_msg_ht)) {
    echo $_info_msg_ht;
    $_info_msg_ht = "";
}

$usage = <<<EOP
<ul>
<li>ƒ[ƒh: NG/‚ ‚Ú[‚ñƒ[ƒh (‹ó‚É‚·‚é‚Æ“o˜^‰ğœ)</li>
<li>i: ‘å•¶š¬•¶š‚ğ–³‹</li>
<li>re: ³‹K•\Œ»</li>
<li>”Â: newsplus,software “™ (Š®‘Sˆê’v, ƒJƒ“ƒ}‹æØ‚è)</li>
<li>ƒXƒŒƒ^ƒC: ƒXƒŒƒbƒhƒ^ƒCƒgƒ‹ (•”•ªˆê’v, í‚É‘å•¶š¬•¶š‚ğ–³‹)</li>
</ul>
EOP;
if ($_conf['ktai']) {
    $usage = mb_convert_kana($usage, 'k');
}
echo <<<EOP
{$usage}
<form method="POST" action="{$_SERVER['SCRIPT_NAME']}" target="_self" accept-charset="{$_conf['accept_charset']}">
    {$_conf['k_input_ht']}
    <input type="hidden" name="_hint" value="{$_conf['detect_hint']}">
    <input type="hidden" name="path" value="{$path_ht}">
    <input type="hidden" name="csrfid" value="{$csrfid}">€n
EOP;

// PC—p•\¦itablej
if (!$_conf['ktai']) {
    echo <<<EOP
    <table class="edit_conf_user" cellspacing="0">
        <tr>
            <td align="center">ƒ[ƒh</td>
            <td align="center">i</td>
            <td align="center">re</td>
            <td align="center">”Â</td>
            <td align="center">ƒXƒŒƒ^ƒC</td>
            <td align="center">ÅIƒqƒbƒg“ú‚Æ‰ñ”</td>
        </tr>
        <tr class="group">
            <td colspan="6">V‹K“o˜^</td>
        </tr>€n
EOP;
    $row_format = <<<EOP
        <tr>
            <td><input type="text" size="35" name="nga[%1€$d][word]" value="%2€$s"></td>
            <td><input type="checkbox" name="nga[%1€$d][ic]" value="1"%3€$s></td>
            <td><input type="checkbox" name="nga[%1€$d][re]" value="1"%4€$s></td>
            <td><input type="text" size="10" name="nga[%1€$d][bbs]" value="%7€$s"></td>
            <td><input type="text" size="15" name="nga[%1€$d][tt]" value="%8€$s"></td>
            <td align="right">
                <input type="hidden" name="nga[%1€$d][ht]" value="%5€$s">%5€$s
                <input type="hidden" name="nga[%1€$d][hn]" value="%6€$d">(%6€$d)
            </td>
        </tr>€n
EOP;
// Œg‘Ñ—p•\¦
} else {
    echo "V‹K“o˜^<br>€n";
    $row_format = <<<EOP
<input type="text" name="nga[%1€$d][word]" value="%2€$s"><br>
”Â:<input type="text" size="5" name="nga[%1€$d][bbs]" value="%7€$s">
½ÚÀ²:<input type="text" size="5" name="nga[%1€$d][tt]" value="%8€$s"><br>
<input type="checkbox" name="nga[%1€$d][ic]" value="1"%3€$s>i
<input type="checkbox" name="nga[%1€$d][re]" value="1"%4€$s>re
<input type="hidden" name="nga[%1€$d][ht]" value="%5€$s">
<input type="hidden" name="nga[%1€$d][hn]" value="%6€$d">(%6€$d)<br>€n
EOP;
}

printf($row_format, -1, '', '', '', '--', 0, '', '');

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
            htmlspecialchars($v['tt'], ENT_QUOTES)
        );
    }
    echo $htm['form_submit'];
}

// PC‚È‚ç
if (!$_conf['ktai']) {
    echo '</table>'."€n";
}

echo '</form>'."€n";


// Œg‘Ñ‚È‚ç
if ($_conf['ktai']) {
    echo <<<EOP
<hr>
<a {$_conf['accesskey']}="{$_conf['k_accesskey']['up']}" href="editpref.php{$_conf['k_at_q']}">{$_conf['k_accesskey']['up']}.İ’è•ÒW</a>
{$_conf['k_to_index_ht']}
EOP;
}

echo '</body></html>';

// ‚±‚±‚Ü‚Å
exit;
