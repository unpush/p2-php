<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0: */
/* mi: charset=Shift_JIS */

// p2 - レス書き込みフォームの機能読み込み

$fake_time = -10; // time を10分前に偽装
$time = time() - 9*60*60;
$time = $time + $fake_time * 60;

$csrfid = P2Util::getCsrfId();

// key.idxから名前とメールを読込み
if (file_exists($key_idx) and $lines = file($key_idx)) {
    $line = explode('<>', rtrim($lines[0]));
    $hd['FROM'] = htmlspecialchars($line[7], ENT_QUOTES);
    $hd['mail'] = htmlspecialchars($line[8], ENT_QUOTES);
} else {
    $hd['FROM'] = null;
    $hd['mail'] = null;
}

// 空白はユーザ設定値に変換
$hd['FROM'] = ($hd['FROM'] == '') ? htmlspecialchars($_conf['my_FROM'], ENT_QUOTES) : $hd['FROM'];
$hd['mail'] = ($hd['mail'] == '') ? htmlspecialchars($_conf['my_mail'], ENT_QUOTES) : $hd['mail'];

// P2NULLは空白に変換
$hd['FROM'] = ($hd['FROM'] == 'P2NULL') ? '' : $hd['FROM'];
$hd['mail'] = ($hd['mail'] == 'P2NULL') ? '' : $hd['mail'];

// 前回のPOST失敗があれば呼び出し
$failed_post_file = P2Util::getFailedPostFilePath($host, $bbs, $key);
if ($cont_srd = DataPhp::getDataPhpCont($failed_post_file)) {
    $last_posted = unserialize($cont_srd);
    
    // まとめてサニタイズ
    $last_posted = array_map(create_function('$n', 'return htmlspecialchars($n, ENT_QUOTES);'), $last_posted);
    //$addslashesS = create_function('$str', 'return str_replace("\'", "\\\'", $str);');
    //$last_posted = array_map($addslashesS, $last_posted);

    $hd['FROM'] = $last_posted['FROM'];
    $hd['mail'] = $last_posted['mail'];
    $MESSAGE_hs = $last_posted['MESSAGE'];
    $hd['subject'] = $last_posted['subject'];

} else {
    $MESSAGE_hs = '';
    $hd['subject'] = '';
}


// 表示指定
// 参考 クラシック COLS='60' ROWS='8'
$mobile = &Net_UserAgent_Mobile::singleton();
// PC
if (empty($_conf['ktai'])) {
    $name_size_at = ' size="19"';
    $mail_size_at = ' size="19"';
    $msg_cols_at = ' cols="' . $STYLE['post_msg_cols'] . '"';
    $wrap = 'off';
// willcom
} elseif($mobile->isAirHPhone()) {
    $msg_cols_at = ' cols="' . $STYLE['post_msg_cols'] . '"';
    $wrap = 'soft';
// 携帯
} else {
    $STYLE['post_msg_rows'] = 5;
    $msg_cols_at = '';
    $wrap = 'soft';
}


!isset($htm['res_disabled']) and $htm['res_disabled'] = '';

// Be書き込み
if (P2Util::isHost2chs($host) and $_conf['be_2ch_code'] && $_conf['be_2ch_mail']) {
    $htm['be2ch'] = '<input id="submit_beres" type="submit" name="submit_beres" value="BEで書き込む" onClick="setHiddenValue(this);">';
} else {
    $htm['be2ch'] = '';
}

// be板では書き込みを無効にする
$htm['title_need_be'] = '';
if (P2Util::isBbsBe2chNet($host, $bbs)) {
    // やっぱり無効にしない。書き込み失敗時に、2ch側でBeログインへの誘導があるので。
    //$htm['res_disabled'] = ' disabled';
    if ($_conf['be_2ch_code'] && $_conf['be_2ch_mail']) {
        $htm['title_need_be'] = ' title="Be板につき、自動Be書き込みします"';
    } else {
        $htm['title_need_be'] = ' title="書き込むにはBeログインが必要です"';
    }
}

// PC用 sage checkbox
if (!$_conf['ktai']) {
    $on_check_sage = 'onChange="checkSage();"';
    $sage_cb_ht = <<<EOP
<input id="sage" type="checkbox" onClick="mailSage();"><label for="sage">sage</label><br>
EOP;
}

// {{{ 2ch●書き込み

$htm['maru_kakiko'] = '';
if (P2Util::isHost2chs($host) and file_exists($_conf['sid2ch_php'])) {
    $maru_kakiko_checked = empty($_conf['maru_kakiko']) ? '' : ' checked';
    $htm['maru_kakiko'] = <<<EOP
<span title="2ch●IDの使用"><input id="maru_kakiko" name="maru_kakiko" type="checkbox" value="1"{$maru_kakiko_checked}><label for="maru_kakiko">●</label></span>
EOP;
}

// }}}
// {{{ソースコード補正用チェックボックス

$htm['src_fix'] = '';
if (!$_conf['ktai']) {
    if ($_conf['editor_srcfix'] == 1 ||
        ($_conf['editor_srcfix'] == 2 && preg_match('/pc\d\.2ch\.net/', $host))
    ) {
        $htm['src_fix'] = '<input type="checkbox" id="fix_source" name="fix_source" value="1"><label for="fix_source">ソースコード補正</label>';
    }
}

// }}}

/*
// {{{ 本文が空のときやsageてないときに送信しようとすると注意する

$onsubmit_ht = '';

if (!$_conf['ktai']) {
    if ($_exconf['editor']['check_message'] || $_exconf['editor']['check_sage']) {
        $_check_message = (int) $_exconf['editor']['check_message'];
        $_check_sage = (int) $_exconf['editor']['check_sage'];
        $onsubmit_ht = " onsubmit=\"return validateAll({$_check_message},{$_check_sage})\"";
    }
}

// }}}
*/
// {{{ これにレス

// inyou:1 引用
// inyou:2 プレビュー
// inyou:3 引用＋プレビュー

$htm['orig_msg'] = '';
if ((basename($_SERVER['SCRIPT_NAME']) == 'post_form.php' || !empty($_GET['inyou'])) && !empty($_GET['resnum'])) {
    $q_resnum = $_GET['resnum'];
    if (!($_GET['inyou'] == 2 && strlen($MESSAGE_hs))) {
        $MESSAGE_hs = "&gt;&gt;" . $q_resnum . "\r\n";
    }
    if (!empty($_GET['inyou'])) {
        require_once P2_LIB_DIR . '/thread.class.php';
        require_once P2_LIB_DIR . '/threadread.class.php';
        $aThread = &new ThreadRead;
        $aThread->setThreadPathInfo($host, $bbs, $key);
        $aThread->readDat($aThread->keydat);
        $q_resar = $aThread->explodeDatLine($aThread->datlines[$q_resnum - 1]);
        $q_resar = array_map('trim', $q_resar);
        $q_resar[3] = strip_tags($q_resar[3], '<br>');
        if ($_GET['inyou'] == 1 || $_GET['inyou'] == 3) {
            // 引用レス番号ができてしまわないように、二つの半角スペースを入れている
            $MESSAGE_hs .= "&gt;  ";
            $MESSAGE_hs .= preg_replace("/ *<br> ?/","\r\n&gt;  ", $q_resar[3]);
            $MESSAGE_hs .= "\r\n";
        }
        if ($_GET['inyou'] == 2 || $_GET['inyou'] == 3) {
            $htm['orig_msg'] = <<<EOM
<blockquote id="original_msg">
    <div>
        <span class="prvw_resnum">{$q_resnum}</span>
        ：<b class="prvw_name">{$q_resar[0]}</b>
        ：<span class="prvw_mail">{$q_resar[1]}</span>
        ：<span class="prvw_dateid">{$q_resar[2]}</span>
    <br>
    <div class="prvw_msg">{$q_resar[3]}</div>
</blockquote>
EOM;
        }
    }
}

// }}}
