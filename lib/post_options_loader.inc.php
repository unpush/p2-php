<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0: */
/* mi: charset=Shift_JIS */

// p2 - レス書き込みフォームの機能読み込み
// read_footer.inc.php と post_form.php から呼ばれている

// 事前変数
// $host, $bbs, $key ($rescount, $popup, $ttitle_en)

$fake_time = -10; // time を10分前に偽装
$time = time() - 9*60*60;
$time = $time + $fake_time * 60;

$csrfid = P2Util::getCsrfId();

$res_disabled_at = '';

// $resv 'FROM', 'mail', 'MESSAGE, 'subject'
$resv = P2Util::getDefaultResValues($host, $bbs, $key);

//$hs = array_map(create_function('$n', 'return htmlspecialchars($n, ENT_QUOTES);'), $resv);
$hs = array(
    'FROM'    => hs($resv['FROM']),
    'mail'    => hs($resv['mail']),
    'subject' => hs($resv['subject'])
);
$MESSAGE_hs = hs($resv['MESSAGE']);

// これにレス
$htm['orig_msg'] = _getOrigMsgHtmlAndSetMessageHs(
    $host, $bbs, $key, geti($_GET['resnum']), geti($_GET['inyou']), $MESSAGE_hs
);


// 表示指定
// 参考 クラシック COLS='60' ROWS='8'
$mobile = &Net_UserAgent_Mobile::singleton();

$name_size_at = '';
$mail_size_at = '';

// PC
if (UA::isPC()) {
    $name_size_at = ' size="19"';
    $mail_size_at = ' size="19"';
    $msg_cols_at = sprintf(' cols="%d"', $STYLE['post_msg_cols']);
    $wrap = 'off';

// willcom
// 通常はPC用設定に準じるが、携帯用設定がセットされていれば、そちらに準じる。
} elseif($mobile->isWillcom()) {
    if ($_conf['k_post_msg_cols']) {
        $msg_cols_at = sprintf(' cols="%d"', $_conf['k_post_msg_cols']);
    } else {
        $msg_cols_at = sprintf(' cols="%d"', $STYLE['post_msg_cols']);
    }
    // $STYLE['post_msg_rows'] => 10
    if ($_conf['k_post_msg_rows']) {
        $STYLE['post_msg_rows'] = (int)$_conf['k_post_msg_rows'];
    }
    
    $wrap = 'soft';

// 携帯
} else {
    if ($_conf['k_post_msg_cols']) {
        $msg_cols_at = sprintf(' cols="%d"', $_conf['k_post_msg_cols']);
    } else {
        $msg_cols_at = '';
    }
    if ($_conf['k_post_msg_rows']) {
        $STYLE['post_msg_rows'] = (int)$_conf['k_post_msg_rows'];
    } else {
        $STYLE['post_msg_rows'] = 5; // 携帯用デフォルト値
    }
    
    $wrap = 'soft';
}

// Be書き込み
$htm['be2ch'] = '';
if (P2Util::isHost2chs($host) and $_conf['be_2ch_code'] && $_conf['be_2ch_mail']) {
    $htm['be2ch'] = '<input id="submit_beres" type="submit" name="submit_beres" value="BEで書き込む" onClick="setHiddenValue(this);">';
}

// be板では書き込みを無効にする
$htm['title_need_be'] = '';
if (P2Util::isBbsBe2chNet($host, $bbs)) {
    // やっぱり無効にしない。書き込み失敗時に、2ch側でBeログインへの誘導があるので。
    //$res_disabled_at = ' disabled';
    if ($_conf['be_2ch_code'] && $_conf['be_2ch_mail']) {
        $htm['title_need_be'] = ' title="Be板につき、自動Be書き込みします"';
    } else {
        $htm['title_need_be'] = ' title="書き込むにはBeログインが必要です"';
    }
}


// sage checkbox
$on_check_sage = '';
$sage_cb_ht = '';
if (UA::isPC() || UA::isIPhoneGroup()) {
    $on_check_sage = ' onChange="checkSage();"';
    $sage_cb_ht = '<input id="sage" type="checkbox" onClick="mailSage();"><label for="sage">sage</label><br>';
}

// 2ch●書き込み
$htm['maru_kakiko'] = _getMaruKakikoHtml($host);

// ソースコード補正用チェックボックス
$htm['src_fix'] = _getSrcFixHtml($host);

/*
// {{{ 本文が空のときやsageてないときに送信しようとすると注意する

$onsubmit_ht = '';

if (UA::isPC() || UA::isIPhoneGroup()) {
    if ($_exconf['editor']['check_message'] || $_exconf['editor']['check_sage']) {
        $_check_message = (int) $_exconf['editor']['check_message'];
        $_check_sage = (int) $_exconf['editor']['check_sage'];
        $onsubmit_ht = " onsubmit=\"return validateAll({$_check_message},{$_check_sage})\"";
    }
}

// }}}
*/

//==================================================================================
// 関数（このファイル内のみで利用）
//==================================================================================
/**
 * これにレス
 *
 * @return  string  HTML
 */
function _getOrigMsgHtmlAndSetMessageHs($host, $bbs, $key, $resnum, $inyou, &$MESSAGE_hs)
{
    global $_conf;
    
    $orig_msg_ht = '';
    
    // inyou:1 引用
    // inyou:2 プレビュー
    // inyou:3 引用＋プレビュー
    if ((basename($_SERVER['SCRIPT_NAME']) == 'post_form.php' || $inyou) && $resnum) {
        if (!($inyou == 2 && strlen($MESSAGE_hs))) {
            $MESSAGE_hs = '&gt;&gt;' . $resnum . "\r\n";
        }
        if (!empty($inyou)) {
            $q_resar = _getExplodedDatLine($host, $bbs, $key, $resnum);
            $prvwMsgHtml = _getPrvwMsgHtml($q_resar[3]);
            if ($inyou == 1 || $inyou == 3) {
                // 引用レス番号ができてしまわないように、二つの半角スペースを入れている
                $MESSAGE_hs .= '&gt;  ';
                $MESSAGE_hs .= preg_replace('/ *<br> ?/', "\r\n&gt;  ", $prvwMsgHtml);
                $MESSAGE_hs .= "\r\n";
            }
            if ($inyou == 2 || $inyou == 3) {
                // <table border="0" cellpadding="0" cellspacing="0"><tr><td>
                $orig_msg_ht = <<<EOM
<blockquote id="original_msg">
	<div>
		<span class="prvw_resnum">{$resnum}</span>
		：<b class="prvw_name">{$q_resar[0]}</b>
		：<span class="prvw_mail">{$q_resar[1]}</span>
		：<span class="prvw_dateid">{$q_resar[2]}</span>
	<br>
	<div class="prvw_msg">{$prvwMsgHtml}</div>
</blockquote>
EOM;
                // </td></tr></table>
            }
        }
    }
    return $orig_msg_ht;
}

/**
 * @return  array
 */
function _getExplodedDatLine($host, $bbs, $key, $resnum)
{
    require_once P2_LIB_DIR . '/Thread.php';
    require_once P2_LIB_DIR . '/ThreadRead.php';
    $ThreadRead = new ThreadRead;
    $ThreadRead->setThreadPathInfo($host, $bbs, $key);
    $ThreadRead->readDat($ThreadRead->keydat);
    $explodedDatLine = $ThreadRead->explodeDatLine($ThreadRead->datlines[$resnum - 1]);
    return array_map('trim', $explodedDatLine);
}

/**
 * 引用メッセージのためのHTML
 *
 * @return  string  HTML
 */
function _getPrvwMsgHtml($datMsg)
{
    // transMsg(), removeResAnchorTagInDat()
    $datMsg = str_replace('<br>', "\n", $datMsg);
    $datMsg = strip_tags($datMsg);
    $datMsg = str_replace("\n", '<br>', $datMsg);
    return $datMsg;
}

/**
 * 2ch●書き込み
 *
 * @return  string  HTML
 */
function _getMaruKakikoHtml($host)
{
    global $_conf;
    
    $maru_kakiko_ht = '';
    
    if (P2Util::isHost2chs($host) and file_exists($_conf['sid2ch_php'])) {
        $maru_kakiko_ht = sprintf(
            '<span title="2ch●IDの使用"><input id="maru_kakiko" name="maru_kakiko" type="checkbox" value="1"%s><label for="maru_kakiko">●</label></span>',
            $_conf['maru_kakiko'] ? ' checked' : ''
        );
    }
    return $maru_kakiko_ht;
}

/**
 * ソースコード補正用チェックボックス
 *
 * @return  string  HTML
 */
function _getSrcFixHtml($host)
{
    global $_conf;
    
    $src_fix_ht = '';
    
    if (UA::isPC() || UA::isIPhoneGroup()) {
        if (
            $_conf['editor_srcfix'] == 1
            or $_conf['editor_srcfix'] == 2 && preg_match('/pc\d+\.2ch\.net/', $host)
        ) {
            $src_fix_ht = '<input type="checkbox" id="fix_source" name="fix_source" value="1"><label for="fix_source">ソースコード補正</label>';
        }
    }
    return $src_fix_ht;
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
