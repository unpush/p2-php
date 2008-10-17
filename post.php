<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

/*
    p2 - レス書き込み
*/

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/dataphp.class.php';
require_once P2_LIB_DIR . '/filectl.class.php';
require_once P2_LIB_DIR . '/P2Validate.php';

$_login->authorize(); // ユーザ認証

if (!empty($_conf['disable_res'])) {
    p2die('書き込み機能は無効です。');
}

if (!isset($_POST['csrfid']) or $_POST['csrfid'] != P2Util::getCsrfId()) {
    p2die('ページ遷移の妥当性を確認できませんでした。（CSRF対策）', '投稿フォームを読み込み直してから、改めて投稿してください。');
}

//================================================================
// 変数
//================================================================
$newtime = date('gis');

$post_keys = array(
    'FROM', 'mail', 'MESSAGE',
    'bbs', 'key', 'time',
    'host', 'popup', 'rescount',
    'subject', 'submit',
    'sub',
    'ttitle_en'
);

foreach ($post_keys as $pk) {
    ${$pk} = isset($_POST[$pk]) ? $_POST[$pk] : null;
}

// 引数エラー
if (empty($host)) {
    p2die('引数の指定が変です');
}
if (P2Validate::host($host) || ($bbs) && P2Validate::bbs($bbs) || ($key) && P2Validate::host($key)) {
    p2die('不正な引数です');
}

if ($bbs and _isThreTateSugi()) {
    p2die('スレ立て杉です（しばし待たれよ）');
}

$_conf['last_post_time_file'] = $_conf['pref_dir'] . '/last_post_time.txt';
if (P2Util::isHost2chs($host)) {
    $server_id = preg_replace('{\.2ch\.net$}', '', $host);
    $_conf['last_post_time_file'] = P2Util::idxDirOfHost($host) . '/' . rawurlencode($server_id) . '_' . 'last_post_time.txt';
}

if (!isset($ttitle)) {
    if ($ttitle_en) {
        $ttitle = base64_decode($ttitle_en);
    } elseif ($subject) {
        $ttitle = $subject;
    } else {
        $ttitle = '';
    }
}

// （設定に応じて）ソースコードがHTML上でもきれいに再現されるように、POSTメッセージを変換する
$MESSAGE = formatCodeToPost($MESSAGE);

// したらばのlivedoor移転に対応。post先をlivedoorとする。
$host = P2Util::adjustHostJbbs($host);

// machibbs、JBBS@したらば なら
if (P2Util::isHostMachiBbs($host) or P2Util::isHostJbbsShitaraba($host)) {
    $bbs_cgi = '/bbs/write.cgi';
    
    // JBBS@したらば なら
    if (P2Util::isHostJbbsShitaraba($host)) {
        $bbs_cgi = "/../bbs/write.cgi";
        preg_match("/(\w+)$/", $host, $ar);
        $dir = $ar[1];
        $dir_k = "DIR";
    }
    
    $submit_k  = "submit";
    $bbs_k     = "BBS";
    $key_k     = "KEY";
    $time_k    = "TIME";
    $FROM_k    = "NAME";
    $mail_k    = "MAIL";
    $MESSAGE_k = "MESSAGE";
    $subject_k = "SUBJECT";
    
// 2ch系なら
} else { 
    if ($sub) {
        $bbs_cgi = "/test/{$sub}bbs.cgi";
    } else {
        $bbs_cgi = "/test/bbs.cgi";
    }
    $submit_k  = "submit";
    $bbs_k     = "bbs";
    $key_k     = "key";
    $time_k    = "time";
    $FROM_k    = "FROM";
    $mail_k    = "mail";
    $MESSAGE_k = "MESSAGE";
    $subject_k = "subject";

}

$post_cache = array(
    'bbs' => $bbs, 'key' => $key,
    'FROM' => $FROM, 'mail' => $mail, 'MESSAGE' => $MESSAGE,
    'subject' => $subject,
    'time' => $time
);

// submit は書き込むで固定してしまう（Beで書き込むの場合もあるため）
$submit = '書き込む';

if (!empty($_POST['newthread'])) {
    $post = array(
        $submit_k => $submit,
        $bbs_k  => $bbs,
        $subject_k => $subject,
        $time_k => $time,
        $FROM_k => $FROM, $mail_k => $mail, $MESSAGE_k => $MESSAGE
    );
    if (P2Util::isHostJbbsShitaraba($host)) {
        $post[$dir_k] = $dir;
    }
    $qs_sid = $qs = array(
            'host' => $host,
            'bbs'  => $bbs,
            UA::getQueryKey() => UA::getQueryValue()
    );
    if ($session_id = session_id()) {
        $qs_sid[session_name()] = $session_id;
    }
    
    $location_url     = P2Util::buildQueryUri($_conf['subject_php'], $qs);
    $location_sid_url = P2Util::buildQueryUri($_conf['subject_php'], $qs_sid);
    
} else {
    $post = array(
        $submit_k => $submit,
        $bbs_k  => $bbs,
        $key_k  => $key,
        $time_k => $time,
        $FROM_k => $FROM, $mail_k => $mail, $MESSAGE_k => $MESSAGE
    );
    if (P2Util::isHostJbbsShitaraba($host)) {
        $post[$dir_k] = $dir;
    }
    $qs_sid = $qs = array(
            'host' => $host,
            'bbs'  => $bbs,
            'key'  => $key,
            'ls'   => "$rescount-",
            'refresh' => 1,
            'nt'   => $newtime,
            UA::getQueryKey() => UA::getQueryValue()
    );
    if ($session_id = session_id()) {
        $qs_sid[session_name()] = $session_id;
    }
    
    $location_url     = P2Util::buildQueryUri($_conf['read_php'], $qs) . "#r{$rescount}";
    $location_sid_url = P2Util::buildQueryUri($_conf['read_php'], $qs_sid) . "#r{$rescount}";
}

// {{{ 2chで●ログイン中ならsid追加

if (!empty($_POST['maru_kakiko']) and P2Util::isHost2chs($host) && file_exists($_conf['sid2ch_php'])) {
    
    // ログイン後、24時間以上経過していたら自動再ログイン
    if (file_exists($_conf['idpw2ch_php']) and filemtime($_conf['sid2ch_php']) < time() - 60*60*24) {
        require_once P2_LIB_DIR . '/login2ch.inc.php';
        login2ch();
    }
    
    if ($r = _getSID2ch()) {
        $post['sid'] = $r;
    }
}

// }}}

/*
// 2006/05/27 新仕様？
$post['hana'] = 'mogera';

// 2008/09/15 新仕様？
$post['kiri'] = 'tanpo';
*/
// for hana mogera。クッキー確認画面ではpost、その後はcookieという仕様らしい。
foreach ($_POST as $k => $v) {
    if (!isset($post[$k]) and !in_array($k, $post_keys)) {
        $post[$k] = $_POST[$k];
    }
}


if (!empty($_POST['newthread'])) {
    $ptitle = "p2 - 新規スレッド作成";
} else {
    $ptitle = "p2 - レス書き込み";
}

//================================================================
// メイン処理
//================================================================

// ポスト実行
$posted = _postIt($host, $bbs, $key, $post);

// 最終投稿時間を記録する 確認処理
if ($posted === true) {
    recLastPostTime("SUCCESS");

// クッキーなら試行時間を戻す
} elseif ($posted === 'Cookie') {
    recLastPostTime("FAULT");

// その他のエラーは連打で抜けられるケースがあるので戻さない
} else {
    recLastPostTime();
}

// スレ立て成功なら、subjectからkeyを取得
if (!empty($_POST['newthread']) && $posted === true) {
    sleep(1);
    $key = getKeyInSubject();
}


// {{{ key.idx 保存

$tagCsv = array();

// <> を外す。。
$tagCsv['FROM'] = str_replace('<>', '', $FROM);
$tagCsv['mail'] = str_replace('<>', '', $mail);

// 名前とメール、空白時は P2NULL を記録
$tagCsvF['FROM'] = ($tagCsv['FROM'] == '') ? 'P2NULL' : $tagCsv['FROM'];
$tagCsvF['mail'] = ($tagCsv['mail'] == '') ? 'P2NULL' : $tagCsv['mail'];

if ($host && $bbs && $key) {
    $idx_host_dir = P2Util::idxDirOfHost($host);
    $keyidx = $idx_host_dir . '/' . $bbs . '/' . $key . '.idx';
    
    $akeyline = array();
    if ($keylines = @file($keyidx)) {
        $akeyline = explode('<>', rtrim($keylines[0]));
    }
    $sar = array(
        $akeyline[0], $akeyline[1], $akeyline[2], $akeyline[3], $akeyline[4],
        $akeyline[5], $akeyline[6], $tagCsvF['FROM'], $tagCsvF['mail'], $akeyline[9],
        $akeyline[10], $akeyline[11], $akeyline[12]
    );
    P2Util::recKeyIdx($keyidx, $sar);
}

// }}}

if ($posted !== true) {
    exit;
}

// {{{ 書き込み履歴

if ($host && $bbs && $key) {
    
    $rh_idx = $_conf['pref_dir'] . '/p2_res_hist.idx';
    FileCtl::make_datafile($rh_idx, $_conf['res_write_perm']);
    
    $lines = file($rh_idx);
    $neolines = array();
    
    // 最初に重複要素を削除しておく
    if (is_array($lines)) {
        foreach ($lines as $line) {
            $line = rtrim($line);
            $lar = explode('<>', $line);
            if ($lar[1] == $key) { continue; } // 重複回避
            if (!$lar[1]) { continue; } // keyのないものは不正データ
            $neolines[] = $line;
        }
    }
    
    // 新規データ追加
    $newdata = "$ttitle<>$key<><><><><><>" . $tagCsv['FROM'] . '<>' . $tagCsv['mail'] . "<><>$host<>$bbs";
    array_unshift($neolines, $newdata);
    while (sizeof($neolines) > $_conf['res_hist_rec_num']) {
        array_pop($neolines);
    }
    
    // 書き込む
    if ($neolines) {
        $cont = '';
        foreach ($neolines as $l) {
            $cont .= $l . "\n";
        }
        
        if (false === FileCtl::filePutRename($rh_idx, $cont)) {
            $errmsg = sprintf('p2 error: %s(), FileCtl::filePutRename() failed.', __FUNCTION__);
            trigger_error($errmsg, E_USER_WARNING);
            //return false;
        }
    }
}

// }}}

// 書き込みログ記録

$tagCsv['message'] = formatMessageTagCvs($MESSAGE);

if ($_conf['res_write_rec']) {
    recResLog($tagCsv['FROM'], $tagCsv['mail'], $tagCsv['message'], $ttitle, $host, $bbs, $key, $rescount);
}

recResLogSecu($tagCsv['FROM'], $tagCsv['mail'], $tagCsv['message'], $ttitle, $host, $bbs, $key, $rescount);

exit;


//=======================================================================
// 関数 （このファイル内でのみ利用）
//=======================================================================

/**
 * タグCSV記録のためにメッセージをフォーマット変換する
 *
 * @return  string
 */
function formatMessageTagCvs($message)
{
    $message = htmlspecialchars($message, ENT_NOQUOTES);
    return preg_replace("/\r?\n/", "<br>", $message);
}

/**
 * 書き込みログを記録する
 *
 * @param   string   $from     タグCVS記録のためにフォーマット済みであること
 * @param   string   $mail     同上
 * @param   string   $message  同上
 * @param   string   $ttitle   同上（元々フォーマットの必要なし）
 * @return  boolean
 */
function recResLog($from, $mail, $message, $ttitle, $host, $bbs, $key, $rescount)
{
    global $_conf;
    
    // 後方互換措置。
    // データPHP形式（p2_res_hist.dat.php, タブ区切り）の書き込み履歴を、dat形式（p2_res_hist.dat, <>区切り）に変換する
    P2Util::transResHistLogPhpToDat();

    $date_and_id = date("y/m/d H:i");

    FileCtl::make_datafile($_conf['p2_res_hist_dat'], $_conf['res_write_perm']);
    
    $resnum = '';
    if (!empty($_POST['newthread'])) {
        $resnum = 1;
    } else {
        if ($rescount) {
            $resnum = $rescount + 1;
        }
    }
    
    $newdata = $from . '<>' . $mail . "<>$date_and_id<>$message<>$ttitle<>$host<>$bbs<>$key<>$resnum";

    // まずタブを全て外して（2chの書き込みではタブは削除される 2004/12/13）
    $newdata = str_replace("\t", '', $newdata);
    // <>をタブに変換して
    //$newdata = str_replace('<>', "\t", $newdata);
    
    $cont = $newdata . "\n";
    
    if (false === file_put_contents($_conf['p2_res_hist_dat'], $cont, FILE_APPEND | LOCK_EX)) {
        trigger_error('p2 error: 書き込みログの保存に失敗しました', E_USER_WARNING);
        return false;
    }
    return true;
}

/**
 * 荒らし通報用にログを取る
 *
 * @param   string   $from     タグCVS記録のためにフォーマット済みであること
 * @param   string   $mail     同上
 * @param   string   $message  同上
 * @param   string   $ttitle   同上（元々フォーマットの必要なし）
 * @return  boolean|null
 */
function recResLogSecu($from, $mail, $message, $ttitle, $host, $bbs, $key, $rescount)
{
    global $_conf;
    
    if (!$_conf['rec_res_log_secu_num']) {
        return null;
    }
    
    if (false === FileCtl::make_datafile($_conf['p2_res_hist_dat_secu'], $_conf['res_write_perm'])) {
        return false;
    }
    
    $resnum = '';
    if (!empty($_POST['newthread'])) {
        $resnum = 1;
    } else {
        if ($rescount) {
            $resnum = $rescount + 1;
        }
    }
    
    $newdata_ar = array(
        $from, $mail, date("y/m/d H:i"), $message, $ttitle, $host, $bbs, $key, $resnum, $_SERVER['REMOTE_ADDR']
    );
    $newdata = implode('<>', $newdata_ar) . "\n";

    // まずタブを全て外して（2chの書き込みではタブは削除される 2004/12/13）
    $newdata = str_replace("\t", '', $newdata);
    // <>をタブに変換して
    //$newdata = str_replace('<>', "\t", $newdata);

    if (false === $lines = file($_conf['p2_res_hist_dat_secu'])) {
        return false;
    }
    
    while (count($lines) > $_conf['rec_res_log_secu_num']) {
        array_shift($lines);
    }
    array_push($lines, $newdata);
    $cont = implode('', $lines);
    
    if (false === file_put_contents($_conf['p2_res_hist_dat_secu'], $cont, LOCK_EX)) {
        trigger_error('p2 error: ' . __FUNCTION__ . '()', E_USER_WARNING);
        return false;
    }
    return true;
}

/**
 * スレ立てしすぎならtrueを返す
 */
function _isThreTateSugi()
{
    global $_conf;
    
    if (!file_exists($_conf['p2_res_hist_dat_secu']) or !$lines = file($_conf['p2_res_hist_dat_secu'])) {
        return false;
    }
    $lines = array_reverse($lines);
    
    $count = 0;
    $check_time = 60*60*1; // 1h
    $limit = 6;
    
    foreach ($lines as $v) {
        // $from, $mail, date("y/m/d H:i"), $message, $ttitle, $host, $bbs, $key, $resnum, $_SERVER['REMOTE_ADDR']
        $e = explode('<>', $v);
        $key = geti($e[7]);
        $time_str = '20' . $e[2]; // $e[2] -> 07/12/21 09:27
        //echo '<br>';
        
        // チェックする時間
        if (strtotime($time_str) < time() - $check_time) {
            break;
        }
        // スレ立てなら
        if (!$key) {
            ++$count;
            if ($count > $limit) {
                return true;
            }
        }
    }
    return false;
}

/**
 * （設定に応じて）ソースコードがHTML上でもきれいに再現されるように、POSTメッセージを変換する
 *
 * @param   string  $MESSAGE
 * @return  string
 */
function formatCodeToPost($MESSAGE)
{
    if (!empty($_POST['fix_source'])) {
        // タブをスペースに
        $MESSAGE = tab2space($MESSAGE);
        // 特殊文字を実体参照に
        $MESSAGE = htmlspecialchars($MESSAGE, ENT_QUOTES);
        // 自動URLリンク回避
        $MESSAGE = str_replace('tp://', 't&#112;://', $MESSAGE);
        // 行頭のスペースを実体参照に
        $MESSAGE = preg_replace('/^ /m', '&nbsp;', $MESSAGE);
        // 二つ続くスペースの一つ目を実体参照に
        $MESSAGE = preg_replace('/(?<!&nbsp;)  /', '&nbsp; ', $MESSAGE);
        // 奇数回スペースがくり返すときの仕上げ
        $MESSAGE = preg_replace('/(?<=&nbsp;)  /', ' &nbsp;', $MESSAGE);
    }
    
    return $MESSAGE;
}

/**
 * ホスト名からクッキーファイルパスを返す
 *
 * @access  public
 * @return  string
 */
function cachePathForCookie($host)
{
    global $_conf;

    $cachefile = $_conf['cookie_dir'] . "/" . P2Util::escapeDirPath($host) . "/" . $_conf['cookie_file_name'];

    FileCtl::mkdirFor($cachefile);
    
    return $cachefile;
}

/**
 * クッキーを設定ファイルから読み込む
 *
 * @param   string  $cookie_file
 * @return  array
 */
function readCookieFile($cookie_file)
{
    if (!file_exists($cookie_file)) {
        return array();
    }
    
    if (!$cookie_cont = file_get_contents($cookie_file)) {
        //return false;
        return array();
    }
    
    if (!$p2cookies = unserialize($cookie_cont)) {
        //return false;
        return array();
    }
    
    // 賞味期限切れなら破棄する（本来ならキーごとに期限を保持しなければならないところだが、手を抜いている）
    if (!empty($p2cookies['expires']) and time() > strtotime($p2cookies['expires'])) {

        //P2Util::pushInfoHtml("<p>期限切れのクッキーを削除しました</p>");
        unlink($cookie_file);
        return array();
    }
    
    return $p2cookies;
}

/**
 * クッキーを設定ファイルに保存する
 *
 * @param   array   $p2cookies
 * @param   string  $cookie_file
 * @return  boolean
 */
function saveCookieFile($p2cookies, $cookie_file)
{
    global $_conf;
    
    // 記録するデータがない場合は、成功扱いで何もしない
    if (!$p2cookies) {
        return true;
    }

    $cookie_cont = serialize($p2cookies);

    FileCtl::make_datafile($cookie_file, $_conf['p2_perm']);
    if (false === file_put_contents($cookie_file, $cookie_cont, LOCK_EX)) {
        return false;
    }
    
    return true;
}

/**
 * レスを書き込む or 新規スレッドを立てる
 * スレ立ての場合は、$key は空 '' でよい
 *
 * @return  boolean|string  書き込み成功なら true、失敗なら false または失敗理由文字列
 */
function _postIt($host, $bbs, $key, $post)
{
    global $_conf, $post_result, $post_error2ch, $popup, $rescount, $ttitle_en, $STYLE;
    global $bbs_cgi, $post_cache;
    
    $method = "POST";
    $bbs_cgi_url = "http://" . $host . $bbs_cgi;
    
    $purl = parse_url($bbs_cgi_url);
    if (isset($purl['query'])) {
        $purl['query'] = "?" . $purl['query'];
    } else {
        $purl['query'] = "";
    }

    // プロキシ
    if ($_conf['proxy_use']) {
        $send_host = $_conf['proxy_host'];
        $send_port = $_conf['proxy_port'];
        $send_path = $bbs_cgi_url;
    } else {
        $send_host = $purl['host'];
        $send_port = isset($purl['port']) ? $purl['port'] : null;
        $send_path = $purl['path'] . $purl['query'];
    }

    !$send_port and $send_port = 80;
    
    $request = $method . " " . $send_path . " HTTP/1.0" . "\r\n";
    $request .= "Host: " . $purl['host'] . "\r\n";
    
    $remote_host = P2Util::getRemoteHost($_SERVER['REMOTE_ADDR']);
    
    $add_user_info = '';
    //$add_user_info = "; p2-client-ip: {$_SERVER['REMOTE_ADDR']}";
    //$add_user_info .= "; p2-client-host: {$remote_host}";
    
    $request .= sprintf(
        'User-Agent: Monazilla/1.00 (%s/%s%s)',
        $_conf['p2name'], $_conf['p2version'], $add_user_info
    ) . "\r\n";
    
    $request .= 'Referer: http://' . $purl['host'] . '/' . "\r\n";
    
    // クライアントのIPを送信するp2独自のヘッダ
    $request .= "X-P2-Client-IP: " . $_SERVER['REMOTE_ADDR'] . "\r\n";
    $request .= "X-P2-Client-Host: " . $remote_host . "\r\n";
    
    // クッキー
    $cookies_to_send = '';

    // クッキーの読み込み
    $cookie_file = cachePathForCookie($host);
    $p2cookies = readCookieFile($cookie_file);
    
    if ($p2cookies) {
        foreach ($p2cookies as $cname => $cvalue) {
            if ($cname != 'expires') {
                $cookies_to_send .= " {$cname}={$cvalue};";
            }
        }
    }
    
    // be.2ch 認証クッキー
    // be板では自動Be書き込みを試みる
    if (P2Util::isBbsBe2chNet($host, $bbs) || !empty($_REQUEST['submit_beres'])) {
        $cookies_to_send .= ' MDMD=' . $_conf['be_2ch_code'] . ';';    // be.2ch.netの認証コード(パスワードではない)
        $cookies_to_send .= ' DMDM=' . $_conf['be_2ch_mail'] . ';';    // be.2ch.netの登録メールアドレス
    }
    
    !$cookies_to_send and $cookies_to_send = ' ;';
    
    $request .= 'Cookie:' . $cookies_to_send . "\r\n";
    //$request .= 'Cookie: PON='.$SPID.'; NAME='.$FROM.'; MAIL='.$mail."\r\n";
    
    $request .= 'Connection: Close' . "\r\n";
    
    // {{{ POSTの時はヘッダを追加して末尾にURLエンコードしたデータを添付
    
    if (strtoupper($method) == 'POST') {
        $post_enc = array();
        while (list($name, $value) = each($post)) {
            
            if (!isset($value)) {
                continue;
            }
            
            // したらば or be.2ch.netなら、EUCに変換
            if (P2Util::isHostJbbsShitaraba($host) || P2Util::isHostBe2chNet($host)) {
                $value = mb_convert_encoding($value, 'eucJP-win', 'SJIS-win');
            }
            
            $post_enc[] = $name . "=" . urlencode($value);
        }

        $postdata = implode('&', $post_enc);
        
        $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $request .= "Content-Length: " . strlen($postdata) . "\r\n";
        $request .= "\r\n";
        $request .= $postdata;
        
    } else {
        $request .= "\r\n";
    }
    
    // }}}

    $maru_kakiko = empty($_POST['maru_kakiko']) ? 0 : 1;
    P2Util::setConfUser('maru_kakiko', $maru_kakiko);

    // 書き込みを一時的に保存
    $failed_post_file = P2Util::getFailedPostFilePath($host, $bbs, $key);
    $cont = serialize($post_cache);
    if (!DataPhp::writeDataPhp($failed_post_file, $cont, $_conf['res_write_perm'])) {
        p2die('ファイルの書き込みエラー');
    }
    
    // p2 samba
    $kisei_second = 10;
    $samba24 = null;
    if (P2Util::isHost2chs($host)) {
        if (!empty($_POST['maru_kakiko']) and file_exists($_conf['sid2ch_php'])) {
            // samba24スルー
        } else {
            if ($r = P2Util::getSamba24TimeCache($host, $bbs)) {
                $kisei_second = $r;
                $samba24 = true;
            }
        }
    }
    if (_isSambaDeny($kisei_second)) {
        $samba24_msg = $samba24 ? '2chのsamba24設定 ' : '';
        $msg_ht = sprintf('p2 samba規制: 連続投稿はできません。（%s%d秒）', hs($samba24_msg), $kisei_second);
        _showPostMsg(false, $msg_ht, false);
        return false;
    }
    
    // WEBサーバへ接続
    $fp = fsockopen($send_host, $send_port, $errno, $errstr, $_conf['fsockopen_time_limit']);
    if (!$fp) {
        _showPostMsg(false, "サーバ接続エラー: $errstr ($errno)<br>p2 Error: 板サーバへの接続に失敗しました", false);
        return false;
    }

    // HTTPリクエスト送信
    fwrite($fp, $request, strlen($request));
    
    $post_seikou = false;
    
    // header
    while (!feof($fp)) {
    
        $l = fgets($fp, 8192);
        
        // クッキーキタ
        if (preg_match("/Set-Cookie: (.+?)\r\n/", $l, $matches)) {
            $cgroups = explode(";", $matches[1]);
            if ($cgroups) {
                foreach ($cgroups as $v) {
                    if (preg_match("/(.+)=(.*)/", $v, $m)) {
                        $k = ltrim($m[1]);
                        if ($k != 'path') {
                            $p2cookies[$k] = $m[2];
                        }
                    }
                }
            }
            if ($p2cookies) {
                $cookies_to_send = '';
                foreach ($p2cookies as $cname => $cvalue) {
                    if ($cname != "expires") {
                        $cookies_to_send .= " {$cname}={$cvalue};";
                    }
                }
                $newcokkies = "Cookie:{$cookies_to_send}\r\n";
                // 2008/09/15 ここで書き換えている理由が今となってはよくわからない
                $request = preg_replace("/Cookie: .*?\r\n/", $newcokkies, $request);
            }

        // 転送は書き込み成功と判断
        } elseif (preg_match("/^Location: /", $l, $matches)) {
            $post_seikou = true;
        }
        if ($l == "\r\n") {
            break;
        }
    }
    
    // クッキーをファイルに保存する
    saveCookieFile($p2cookies, $cookie_file);
    
    // body
    $response = '';
    while (!feof($fp)) {
        $response .= fread($fp, 164000);
    }

    fclose($fp);
    
    // be.2ch.net or JBBSしたらば 文字コード変換 EUC→SJIS
    if (P2Util::isHostBe2chNet($host) || P2Util::isHostJbbsShitaraba($host)) {
        $response = mb_convert_encoding($response, 'SJIS-win', 'eucJP-win');
        
        //<META http-equiv="Content-Type" content="text/html; charset=EUC-JP">
        $response = preg_replace("{(<head>.*<META http-equiv=\"Content-Type\" content=\"text/html; charset=)EUC-JP(\">.*</head>)}is", "$1Shift_JIS$2", $response);
    }
    
    $kakikonda_match = "/<title>.*(書きこみました|■ 書き込みました ■|書き込み終了 - SubAll BBS).*<\/title>/is";
    $cookie_kakunin_match = "/<!-- 2ch_X:cookie -->|<title>■ 書き込み確認 ■<\/title>|>書き込み確認。</";
    
    if (eregi("(<.+>)", $response, $matches)) {
        $response = $matches[1];
    }
    
    // カキコミ成功
    if (preg_match($kakikonda_match, $response, $matches) or $post_seikou) {
        
        // クッキーの書き込み自動保存を消去する
        isset($_COOKIE['post_msg']) and setcookie('post_msg', '', time() - 3600);
        
        $reload = empty($_POST['from_read_new']);
        _showPostMsg(true, '書きこみが終わりました。', $reload);
        
        // 投稿失敗記録があれば削除する
        if (file_exists($failed_post_file)) {
            unlink($failed_post_file);
        }
        
        return true;
        
        //$response_ht = htmlspecialchars($response, ENT_QUOTES);
        //echo "<pre>{$response_ht}</pre>";
    
    // ■cookie確認表示（post再チャレンジしてね）
    } elseif (preg_match($cookie_kakunin_match, $response, $matches)) {

        $htm['more_hidden_post'] = '';
        // p2用の追加キー
        $more_hidden_keys = array(
            'newthread', 'submit_beres', 'from_read_new', 'maru_kakiko', 'csrfid', 'k',
            UA::getQueryKey() // 'b'
        );
        foreach ($more_hidden_keys as $hk) {
            if (isset($_POST[$hk])) {
                $htm['more_hidden_post'] .= sprintf(
                    '<input type="hidden" name="%s" value="%s">',
                    hs($hk), hs($_POST[$hk])
                ) . "\n";
            }
        }

        $form_pattern = '/<form method="?POST"? action="?\\.\\.\\/test\\/(sub)?bbs\\.cgi(?:\\?guid=ON)?"?>/i';
        $myname = basename($_SERVER['SCRIPT_NAME']);
        $host_hs = hs($host);
        $popup_hs = hs($popup);
        $rescount_hs = hs($rescount);
        $ttitle_en_hs = hs($ttitle_en);
        
        $form_replace = <<<EOFORM
<form method="POST" action="{$myname}?guid=ON" accept-charset="{$_conf['accept_charset']}">
    <input type="hidden" name="detect_hint" value="◎◇">
    <input type="hidden" name="host" value="{$host_hs}">
    <input type="hidden" name="popup" value="{$popup_hs}">
    <input type="hidden" name="rescount" value="{$rescount_hs}">
    <input type="hidden" name="ttitle_en" value="{$ttitle_en_hs}">
    <input type="hidden" name="sub" value="\$1">
    {$htm['more_hidden_post']}
EOFORM;
        $response = preg_replace($form_pattern, $form_replace, $response);
        
        $h_b = explode("</head>", $response);
        
        // HTMLプリント
        echo $h_b[0];
        if (!$_conf['ktai']) {
            P2View::printIncludeCssHtml('style');
            P2View::printIncludeCssHtml('post');
        }
        if ($popup) {
            $mado_okisa = explode(',', $STYLE['post_pop_size']);
            $mado_okisa_x = $mado_okisa[0];
            $mado_okisa_y = $mado_okisa[1] + 200;
            echo <<<EOSCRIPT
            <script language="JavaScript">
            <!--
                resizeTo({$mado_okisa_x},{$mado_okisa_y});
            // -->
            </script>
EOSCRIPT;
        }
        
        echo "</head>";
        echo $h_b[1];
        
        //return false;
        return 'Cookie';
        
    // その他はレスポンスをそのまま表示（結果はエラーとしてfalseを返す）
    } else {
        $response = ereg_replace('こちらでリロードしてください。<a href="\.\./[a-z]+/index\.html"> GO! </a><br>', "", $response);
        echo $response;
        return false;
    }
}

/**
 * 書き込み処理結果をHTML表示する
 *
 * @param   boolean  $is_done       書き込み完了したならtrue
 * @param   string   $msg_ht        結果メッセージHTML
 * @param   boolean  $reload_opener opener画面を自動で更新するならtrue
 * @return  void
 */
function _showPostMsg($is_done, $msg_ht, $reload_opener)
{
    global $_conf, $location_url, $location_sid_url, $popup, $STYLE, $ttitle, $ptitle;
    
    $body_at = P2View::getBodyAttrK();
    
    $class_ttitle = '';
    if (!$_conf['ktai']) {
        $class_ttitle = ' class="thre_title"';
    }
    $ttitle_ht = "<b{$class_ttitle}>{$ttitle}</b>";
    
    // 2005/04/25 rsk: <script>タグ内もCDATAとして扱われるため、&amp;にしてはいけない
    $popup_ht = '';
    $meta_refresh_ht = '';
    if ($popup) {
        $reload_js = $reload_opener ? 'opener.location.href="' . $location_sid_url . '"' : '';
        $popup_ht = <<<EOJS
<script language="JavaScript">
<!--
    resizeTo({$STYLE['post_pop_size']});
    {$reload_js}
    var delay = 3*1000;
    var closeid = setTimeout("window.close()", delay);
// -->
</script>
EOJS;
        $body_at .= ' onUnload="clearTimeout(closeid)"';
    
    } else {
        // 2005/03/01 aki: jigブラウザに対応するため、&amp; ではなく & で
        // 2007/10/17 ↑今もそうなのかな。hs()するように変更してみた。
        $meta_refresh_ht = '<meta http-equiv="refresh" content="1;URL=' . hs($location_sid_url) . '">';
    }

    // HTMLプリント
    P2View::printDoctypeTag();
?>
<html lang="ja">
<head>
<?php
P2View::printHeadMetasHtml();
echo $meta_refresh_ht;

    if ($is_done) {
        echo "<title>p2 - 書きこみました。</title>";
    } else {
        echo "<title>{$ptitle}</title>";
    }

    $kakunin_ht = '';
    
    // PC向け
    if (!$_conf['ktai']) {
        P2View::printIncludeCssHtml('style');
        P2View::printIncludeCssHtml('post');
        ?>
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<?php
        echo $popup_ht;
        
    // 携帯向け
    } else {
        $kakunin_ht = '<p><a href="' . hs($location_url) . '">確認</a></p>';
    }
    
    echo "</head><body{$body_at}>\n";

    P2Util::printInfoHtml();

    echo <<<EOP
<p>{$ttitle_ht}</p>
<p>{$msg_ht}</p>
{$kakunin_ht}
</body>
</html>
EOP;
}


/**
 * @return  boolean  規制中なら true を返す
 */
function _isSambaDeny($sambatime)
{
    if (!$times = getLastPostTime()) {
        return false;
    }
    $last_try_time = $times[0];
    if (time() - $lasttrytime < $sambatime) {
        return true;
    }
    return false;
}

/**
 * 最終投稿時間規制をチェックして更新する
 *
 * @return  boolean  弾くならtrue
 */
function isDenyWithUpdateLastPostTime($kisei_second)
{
    global $_conf;
    
    $file = $_conf['last_post_time_file'];
    
    FileCtl::make_datafile($file, $_conf['res_write_perm']);
    
    if (!$fp = fopen($file, 'rb+')) {
        return false;
    }
    flock($fp, LOCK_EX);
    $bytes = 12000;
    $lines = array();
    while (!feof($fp)) {
        if ($line = rtrim(fgets($fp, $bytes))) {
            $lines[] = $line;
        }
    }
    
    // 前回書き込み時間を読み込んでチェック
    $last_post_times = $lines;
    if ($last_try_time = $last_post_times[0]) {
        if ($last_try_time > time() - $kisei_second) {
            flock($fp, LOCK_UN);
            fclose($fp);
            return true;
        }
    }
    
    $last_confirm_time = empty($last_post_times[1]) ? '' : $last_post_times[1];
    // 試行時間 : 実行確認時間
    $cont = time() . "\n" . $last_confirm_time . "\n";

    rewind($fp);    // これいる http://jp.php.net/manual/ja/function.ftruncate.php#44702
    ftruncate($fp, 0);
    if (false === fwrite($fp, $cont)) {
        die("p2 error: 最終投稿時間を更新できませんでした");
        return false;
    }
    
    flock($fp, LOCK_UN);
    fclose($fp);
    
    return false;
}

/**
 * 最終投稿時間を取得する
 *
 * @return array|false [0]に試行時間、[1]に成功確認時間を格納した配列
 */
function getLastPostTime()
{
    global $_conf;
    
    $file = $_conf['last_post_time_file'];
    
    if (!file_exists($file)) {
        return false;
    }
    if (!$fp = fopen($file, 'rb')) {
        return false;
    }
    flock($fp, LOCK_EX);
    $bytes = 12000;
    $lines = array();
    while (!feof($fp)) {
        if ($line = rtrim(fgets($fp, $bytes))) {
            $lines[] = $line;
        }
    }
    flock($fp, LOCK_UN);
    fclose($fp);
    
    return $lines ? $lines : false;
}

/**
 * 最終投稿時間を記録する
 *
 *   -試行処理
 *   --試行時間で連投チェック。
 *   ---OKなら確認時間はそのままに、試行時間を更新して続行（デフォルト動作）
 *   ---NGならSamba発動（試行時間の更新は行わないでおこうか）
 *
 *   -確認処理
 *   --成功なら試行/確認時間更新（$confirm = "SUCCESS"）
 *   --失敗なら試行時間を前回の確認時間に戻す（$confirm = "FAULT"）
 *
 * @param   $confirm  確認処理の場合、"SUCCESS" or "FAULT" を指定する
 * @return  boolean
 */
function recLastPostTime($confirm = "")
{
    global $_conf;
    
    // 確認処理 成功なら
    if ($confirm == 'SUCCESS') {
        // 試行時間 : （投稿成功の）確認時間
        $cont = time() . "\n" . time() . "\n";
    
    // 確認処理 失敗なら
    } elseif ($confirm == 'FAULT') {
        $last_post_times = getLastPostTime();
        $last_confirm_time = empty($last_post_times[1]) ? '' : $last_post_times[1];
        $cont = $last_confirm_time . "\n" . $last_confirm_time . "\n";
        
    // 試行処理 試行時間更新
    } else {
        $last_post_times = getLastPostTime();
        $last_confirm_time = empty($last_post_times[1]) ? '' : $last_post_times[1];
        $cont = time() . "\n" . $last_confirm_time . "\n";
    }
    
    FileCtl::make_datafile($_conf['last_post_time_file'], $_conf['res_write_perm']);
    
    if (false === file_put_contents($_conf['last_post_time_file'], $cont, LOCK_EX)) {
        die("p2 error: 最終投稿時間を更新できませんでした");
        return false;
    }
    
    return true;
}

/**
 * subjectからkeyを取得する
 *
 * @return  string|false
 */
function getKeyInSubject()
{
    global $host, $bbs, $ttitle;

    require_once P2_LIB_DIR . '/SubjectTxt.php';
    $aSubjectTxt =& new SubjectTxt($host, $bbs);

    foreach ($aSubjectTxt->subject_lines as $l) {
        if (strstr($l, $ttitle)) {
            if (preg_match("/^([0-9]+)\.(dat|cgi)(,|<>)(.+) ?(\(|（)([0-9]+)(\)|）)/", $l, $matches)) {
                return $key = $matches[1];
            }
        }
    }
    return false;
}

/**
 * 整形を維持しながら、タブをスペースに置き換える
 *
 * @return  string
 */
function tab2space($in_str, $tabwidth = 4, $crlf = "\n")
{
    $out_str = '';
    $lines = preg_split('/\r\n|\r|\n/', $in_str);
    $ln = count($lines);

    for ($i = 0; $i < $ln; $i++) {
        $parts = explode("\t", rtrim($lines[$i]));
        $pn = count($parts);

        for ($j = 0; $j < $pn; $j++) {
            if ($j == 0) {
                $l = $parts[$j];
            } else {
                //$t = $tabwidth - (strlen($l) % $tabwidth);
                $sn = $tabwidth - (mb_strwidth($l) % $tabwidth); // UTF-8でも全角文字幅を2とカウントする
                for ($k = 0; $k < $sn; $k++) {
                    $l .= ' ';
                }
                $l .= $parts[$j];
            }
        }

        $out_str .= $l;
        if ($i + 1 < $ln) {
            $out_str .= $crlf;
        }
    }

    return $out_str;
}

/**
 * @return  string|null
 */
function _getSID2ch()
{
    global $_conf;
    
    $SID2ch = null;
    if (file_exists($_conf['sid2ch_php'])) {
        include $_conf['sid2ch_php']; // $uaMona, $SID2ch がセットされる
    }
    return $SID2ch;
}
