<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=4 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 - 携帯版レスコピペ用フォーム

require_once 'conf/conf.inc.php';
require_once P2_LIB_DIR . '/Thread.php';
require_once P2_LIB_DIR . '/ThreadRead.php';

$_login->authorize(); // ユーザ認証

$host   = geti($_GET['host']);
$bbs    = geti($_GET['bbs']);
$key    = geti($_GET['key']);

$resid  = $GLOBALS['_read_copy_resnum'];
$quote  = !empty($_GET['inyou']);

$back_link_atag = '';
if (isset($_SERVER['HTTP_REFERER'])) {
    $back_link_atag = P2View::tagA($_SERVER['HTTP_REFERER'], hs('戻る'), array('title' => '戻る'));
}

// レス読み込み
$aThread = new ThreadRead;
$aThread->setThreadPathInfo($host, $bbs, $key);
$aThread->ls = $resid;

$moto_url   = '';
$moto_url_k = '';

$post_link_atag = '';
$moto_link_atag = '';

$name_txt   = '';
$mail_txt   = '';
$date_txt   = '';
$msg_txt    = '';
$id_txt     = '';
$id_ht      = '';
$form_id    = P2_REQUEST_ID;

if (!file_exists($aThread->keydat)) {
    p2die('ｽﾚｯﾄﾞの指定が変です。');
}

// スレッド情報
$aThread->readDat($aThread->keydat);
$first = $aThread->explodeDatLine($aThread->datlines[0]);
$ttitle     = trim($first[4]);
$ttitle_en  = base64_encode($ttitle);
$moto_url   = $aThread->getMotoThread(true);
$moto_url_k = $aThread->getMotoThread();

if ($moto_url != $moto_url_k) {
    $moto_url_k_ht = sprintf('<input type="text" name="dummy_moto_url_k" value="%s"><br>', hs($moto_url_k));
}

// 投稿フォーム <a>
$post_link_atag = _getPostLinkATag($aThread, $ttitle_en);


// 元スレへ <a>
$moto_link_atag = P2View::tagA(
    P2Util::throughIme($moto_url_k),
    '元ｽﾚ'
);

// 指定番号のレスをパース
$p = $resid - 1;
if (isset($aThread->datlines[$p])) {
    $resar = $aThread->explodeDatLine($aThread->datlines[$p]);
    // $resar[2]: 2006/10/20(金) 11:46:08 ID:YS696rnVP BE:32616498-DIA(30003)" 
    $name_txt = trim(strip_tags($resar[0]));
    $mail_txt = trim(strip_tags($resar[1]));
    if (strstr($resar[2], 'ID:')) {
        //$id_preg = 'ID: ?([0-9A-Za-z\/.+?]+)([.,]|†)?';
        $id_preg  = 'ID: ?([0-9A-Za-z\/.+?]+)(,|†)?';
        $date_txt = preg_replace('/ ?' . $id_preg . '.*$/', '', $resar[2]);
        $id_txt   = preg_replace('/^.*' . $id_preg . '.*$/', 'ID:$1', $resar[2]);
        $id_ht    = sprintf('<input type="text" name="dummy_id" value="%s"><br>', hs($id_txt));
    
    } elseif (strstr($resar[2], 'HOST:')) {
        $id_preg  = 'HOST: ?([0-9A-Za-z.,_\-<>]+)';
        $date_txt = preg_replace('/ ?' . $id_preg . '.*$/', '', $resar[2]);
        $id_txt   = preg_replace('/^.*' . $id_preg . '.*$/', 'HOST:$1', $resar[2]);
        $id_ht    = sprintf('<input type="text" name="dummy_id" value="%s"><br>', hs($id_txt));
    
    } else {
        //$date_txt = $resar[2];
        // IDがなかったり、0,O だけで（2007/12/22(土) 16:49:16 0）、BEや株主優待がある場合があるので。
        // ちなみにID記述が 0,O だけの時は、ここでは表示されない…。
        // 2chの日付はたまに自由表記っぽい設定になる時があるので、パース困難だ。
        $e = explode(' ', $resar[2]);
        $date_txt = $e[0] . ' ' . $e[1];
    }
    // ここで $date_txt に <a href="http://2ch.se/">還</a> が入っていることがあったので取り除く
    $date_txt = strip_tags($date_txt);
    
    $be_txt = '';
    if (preg_match('|BE: ?(\d+)-(#*.+)|i', $resar[2], $m)) {
        $be_txt = "?{$m[2]}";
    }
    $msg_txt = trim(strip_tags($resar[3], '<br>'));
    if ($quote) {
        $msg_txt = "&gt;&gt;{$resid}\r\n&gt; " . preg_replace('/ *<br[^>]*> */i', "\n&gt; ", $msg_txt);
    } else {
        $msg_txt = preg_replace('/ *<br[^>]*> */i', "\n", $msg_txt);
    }
} else {
    P2Util::pushInfoHtml('<p>p2 error: ﾚｽ番号の指定が変です｡</p>');
}

// 写
if ($_GET['ktool_name'] == 'copy') {
    $mail_txt_tmp = strlen($mail_txt) ? "$mail_txt :" : '';
    $id_txt_tmp   = strlen($id_txt)   ? " $id_txt" : '';
    $be_txt_tmp   = strlen($be_txt)   ? " $be_txt" : '';
    
    //↓一行目の表示はこっちの方がいいかも？
    //"{$resid} 名前:{$name_txt} [{$mail_txt_tmp}] 投稿日:{$date_txt} {$id_txt_tmp}{$be_txt_tmp}">
    $msg_txt = "$resid :$name_txt :{$mail_txt_tmp}$date_txt{$id_txt_tmp}{$be_txt_tmp}\n{$msg_txt}";
    
    // auのバグ？対応
    $mobile = &Net_UserAgent_Mobile::singleton();
    if ($mobile->isEZweb()) {
        $msg_txt = preg_replace("/\n&/", "\n\n&", $msg_txt, 1);
    }
}

$msg_len = mb_strlen($msg_txt);
$len = $GLOBALS['_conf']['k_copy_divide_len'] ? $GLOBALS['_conf']['k_copy_divide_len'] : 10000;
$msg_txts = array();
for ($i = 0; $i < $msg_len; $i += $len) {
    $msg_txts[] = mb_substr($msg_txt, $i, $len);
}

$action_uri = UriUtil::buildQueryUri(
    $_SERVER['SCRIPT_NAME'],
    array(
        'host' => $aThread->host,
        'bbs'  => $aThread->bbs,
        'key'  => $aThread->key,
        'copy' => $GLOBALS['_read_copy_resnum']
    )
);

//=====================================================
// コピー用フォーム HTMLを表示
//=====================================================
// willcom はtextareaのサイズが小さいと使いにくいらしい
/*
JavaScriptにしてしまった方がいいかも？
javascript:(function(){for (var j=0;j<document.forms.length;j++){for (var i=0;i<document.forms[j].elements.length;i++) {k=document.forms[j].elements[i];if(k.type=="textarea"){k.rows=10;k.cols=34;}}}})(); 
*/
$kyopon_size_at = '';
$mobile = &Net_UserAgent_Mobile::singleton();
if ($mobile->isWillcom()) {
    $kyopon_size_at = ' rows="10" cols="34"';
}

$hr = P2View::getHrHtmlK();
$body_at = P2View::getBodyAttrK();

P2Util::headerNoCache();
P2View::printDoctypeTag();
?>
<html>
<head>
 <title><?php eh($ttitle); ?>/<?php eh($resid); ?></title>
</head>
<body<?php echo $body_at; ?>>
<?php P2Util::printInfoHtml(); ?>
<form id="<?php eh($form_id); ?>" action="<?php eh($action_uri); ?>" method="post">
ｽﾚ:<br>
<input type="text" name="dummy_ttitle" value="<?php eh($ttitle); ?>"><br>
<input type="text" name="dummy_moto_url" value="<?php eh($moto_url); ?>"><br>
<?php echo $moto_url_k_ht; ?>
<?php eh($resid); ?>:<br>

<?php if ($_GET['ktool_name'] != 'copy') { ?>
<?php // $name_txt, $mail_txt, $date_txt は既に実体参照を含んでいる場合がある。タグは除去されている。 ?>
<input type="text" name="dummy_name" value="<?php echo $name_txt; ?>"><br>
<input type="text" name="dummy_mail" value="<?php echo $mail_txt; ?>"><br>
<input type="text" name="dummy_date" value="<?php echo $date_txt; ?>"><br>
<?php echo $id_ht; ?>
<?php } ?>

<?php foreach ($msg_txts as $msg_txt) { ?>
<textarea<?php echo $kyopon_size_at; ?>><?php echo $msg_txt; ?></textarea><br>
<?php } ?>
ﾌﾘｰ:<br>
<textarea name="free" rows="2"></textarea>
</form>
<?php echo $back_link_atag; ?> <?php echo $post_link_atag; ?> <?php echo $moto_link_atag; ?>

<?php echo $hr . P2View::getBackToIndexKATag(); ?>
</body>
</html>

<?php
//==========================================================================================
// 関数（このファイル内でのみ利用）
//==========================================================================================
/**
 * @return  string  HTML
 */
function _getPostLinkATag($aThread, $ttitle_en)
{
    return $post_link_atag = P2View::tagA(
        UriUtil::buildQueryUri(
            'post_form.php',
            array(
                'host' => $aThread->host,
                'bbs'  => $aThread->bbs,
                'key'  => $aThread->key,
                'rescount' => $aThread->rescount,
                'ttitle_en' => $ttitle_en,
                UA::getQueryKey() => UA::getQueryValue()
            )
        ),
        '書'
    );
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
