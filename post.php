<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

/*
    p2 - レス書き込み
*/

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/dataphp.class.php';
require_once P2_LIB_DIR . '/filectl.class.php';

$_login->authorize(); // ユーザ認証

if (!empty($_conf['disable_res'])) {
    P2Util::printSimpleHtml('p2 error: 書き込み機能は無効です。');
    die;
}

// 引数エラー
if (empty($_POST['host'])) {
    P2Util::printSimpleHtml('p2 error: 引数の指定が変です');
    die;
}
if (!isset($_POST['csrfid']) or $_POST['csrfid'] != P2Util::getCsrfId()) {
    P2Util::printSimpleHtml('p2 error: 不正なポストです');
    die;
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
    $bbs_cgi = "/bbs/write.cgi";
    
    // JBBS@したらば なら
    if (P2Util::isHostJbbsShitaraba($host)) {
        $bbs_cgi = "/../bbs/write.cgi";
        preg_match("/(\w+)$/", $host, $ar);
        $dir = $ar[1];
        $dir_k = "DIR";
    }
    
    $submit_k = "submit";
    $bbs_k = "BBS";
    $key_k = "KEY";
    $time_k = "TIME";
    $FROM_k = "NAME";
    $mail_k = "MAIL";
    $MESSAGE_k = "MESSAGE";
    $subject_k = "SUBJECT";
    
// 2ch系なら
} else { 
    if ($sub) {
        $bbs_cgi = "/test/{$sub}bbs.cgi";
    } else {
        $bbs_cgi = "/test/bbs.cgi";
    }
    $submit_k = "submit";
    $bbs_k = "bbs";
    $key_k = "key";
    $time_k = "time";
    $FROM_k = "FROM";
    $mail_k = "mail";
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
            $bbs_k => $bbs, $subject_k => $subject,
            $time_k => $time,
            $FROM_k => $FROM, $mail_k => $mail, $MESSAGE_k => $MESSAGE
        );
    if (P2Util::isHostJbbsShitaraba($host)) {
        $post[$dir_k] = $dir;
    }
    $location_ht = "{$_conf['subject_php']}?host={$host}&amp;bbs={$bbs}{$_conf['k_at_a']}";
    
} else {
    $post = array(
            $submit_k => $submit,
            $bbs_k => $bbs, $key_k => $key,
            $time_k => $time,
            $FROM_k => $FROM, $mail_k => $mail, $MESSAGE_k => $MESSAGE
        );
    if (P2Util::isHostJbbsShitaraba($host)) {
        $post[$dir_k] = $dir;
    }
    $location_ht = "{$_conf['read_php']}?host={$host}&amp;bbs={$bbs}&amp;key={$key}&amp;ls={$rescount}-&amp;refresh=1&amp;nt={$newtime}{$_conf['k_at_a']}#r{$rescount}";
}

// {{{ 2chで●ログイン中ならsid追加

if (!empty($_POST['maru_kakiko']) and P2Util::isHost2chs($host) && file_exists($_conf['sid2ch_php'])) {
    
    // ログイン後、24時間以上経過していたら自動再ログイン
    if (file_exists($_conf['idpw2ch_php']) and filemtime($_conf['sid2ch_php']) < time() - 60*60*24) {
        require_once P2_LIB_DIR . '/login2ch.inc.php';
        login2ch();
    }
    
    include $_conf['sid2ch_php']; // $uaMona, $SID2ch
    $post['sid'] = $SID2ch;
}

// }}}

// 2006/05/27 新仕様？
$post['hana'] = 'mogera';

if (!empty($_POST['newthread'])) {
    $ptitle = "p2 - 新規スレッド作成";
} else {
    $ptitle = "p2 - レス書き込み";
}

//================================================================
// メイン処理
//================================================================

// ポスト実行
$posted = postIt($host, $bbs, $key, $post);

// スレ立て成功なら、subjectからkeyを取得
if (!empty($_POST['newthread']) && $posted) {
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
    $sar = array($akeyline[0], $akeyline[1], $akeyline[2], $akeyline[3], $akeyline[4],
                 $akeyline[5], $akeyline[6], $tagCsvF['FROM'], $tagCsvF['mail'], $akeyline[9],
                 $akeyline[10], $akeyline[11], $akeyline[12]);
    P2Util::recKeyIdx($keyidx, $sar);
}

// }}}

if (!$posted) {
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
        
        if (FileCtl::filePutRename($rh_idx, $cont) === false) {
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
    
    // 旧互換措置。
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
 * @return  boolean
 */
function recResLogSecu($from, $mail, $message, $ttitle, $host, $bbs, $key, $rescount)
{
    global $_conf;
    
    if (!$_conf['rec_res_log_secu_num']) {
        return null;
    }
    
    FileCtl::make_datafile($_conf['p2_res_hist_dat_secu'], $_conf['res_write_perm']);
    
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

    $lines = file($_conf['p2_res_hist_dat_secu']);
    if ($lines === false) {
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

    $cachefile = $_conf['cookie_dir'] . "/{$host}/" . $_conf['cookie_file_name'];

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
    if (file_put_contents($cookie_file, $cookie_cont, LOCK_EX) === false) {
        return false;
    }
    
    return true;
}

/**
 * レスを書き込む or 新規スレッドを立てる
 * スレ立ての場合は、$key は空 '' でよい
 *
 * @return  boolean  書き込み成功なら true、失敗なら false
 */
function postIt($host, $bbs, $key, $post)
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
        $send_port = $purl['port'];
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
    
    $request .= "User-Agent: Monazilla/1.00 (" . $_conf['p2name'] . "/" . $_conf['p2version'] . "{$add_user_info})" . "\r\n";
    
    $request .= 'Referer: http://' . $purl['host'] . '/' . "\r\n";
    
    // クライアントのIPを送信するp2独自のヘッダ
    $request .= "X-P2-Client-IP: " . $_SERVER['REMOTE_ADDR'] . "/\r\n";
    $request .= "X-P2-Client-Host: " . $remote_host . "/\r\n";
    
    // クッキー
    $cookies_to_send = "";

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
    
    $request .= "Connection: Close\r\n";
    
    // {{{ POSTの時はヘッダを追加して末尾にURLエンコードしたデータを添付
    
    if (strtoupper($method) == "POST") {
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

        $postdata = implode("&", $post_enc);
        
        $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $request .= "Content-Length: " . strlen($postdata) . "\r\n";
        $request .= "\r\n";
        $request .= $postdata;
        
    } else {
        $request .= "\r\n";
    }
    
    // }}}

    $maru_kakiko = isset($_POST['maru_kakiko']) ? $_POST['maru_kakiko'] : '';
    setConfUser('maru_kakiko', $_POST['maru_kakiko']);

    // 書き込みを一時的に保存
    $failed_post_file = P2Util::getFailedPostFilePath($host, $bbs, $key);
    $cont = serialize($post_cache);
    DataPhp::writeDataPhp($failed_post_file, $cont, $_conf['res_write_perm']);
    
    // WEBサーバへ接続
    $fp = fsockopen($send_host, $send_port, $errno, $errstr, $_conf['fsockopen_time_limit']);
    if (!$fp) {
        showPostMsg(false, "サーバ接続エラー: $errstr ($errno)<br>p2 Error: 板サーバへの接続に失敗しました", false);
        return false;
    }

    // HTTPリクエスト送信
    fputs($fp, $request);
    
    $post_seikou = false;
    
    // header
    while (!feof($fp)) {
    
        $l = fgets($fp, 8192);
        
        // クッキーキタ
        if (preg_match("/Set-Cookie: (.+?)\r\n/", $l, $matches)) {
            //echo "<p>".$matches[0]."</p>"; //
            $cgroups = explode(";", $matches[1]);
            if ($cgroups) {
                foreach ($cgroups as $v) {
                    if (preg_match("/(.+)=(.*)/", $v, $m)) {
                        $k = ltrim($m[1]);
                        if ($k != "path") {
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
        showPostMsg(true, '書きこみが終わりました。', $reload);
        
        // 投稿失敗記録があれば削除する
        if (file_exists($failed_post_file)) {
            unlink($failed_post_file);
        }
        
        return true;
        
        //$response_ht = htmlspecialchars($response, ENT_QUOTES);
        //echo "<pre>{$response_ht}</pre>";
    
    // ■cookie確認（post再チャレンジ）
    } elseif (preg_match($cookie_kakunin_match, $response, $matches)) {

        $htm['more_hidden_post'] = '';
        $more_hidden_keys = array('newthread', 'submit_beres', 'from_read_new', 'maru_kakiko', 'csrfid', 'k', 'b');
        foreach ($more_hidden_keys as $hk) {
            if (isset($_POST[$hk])) {
                $value_hd = htmlspecialchars($_POST[$hk], ENT_QUOTES);
                $htm['more_hidden_post'] .= "<input type=\"hidden\" name=\"{$hk}\" value=\"{$value_hd}\">\n";
            }
        }

        $form_pattern = '/<form method=\"?POST\"? action=\"?\\.\\.\\/test\\/(sub)?bbs\\.cgi\"?>/i';
        
        $myname = basename($_SERVER['SCRIPT_NAME']);
        $form_replace = <<<EOFORM
<form method="POST" action="{$myname}" accept-charset="{$_conf['accept_charset']}">
    <input type="hidden" name="detect_hint" value="◎◇">
    <input type="hidden" name="host" value="{$host}">
    <input type="hidden" name="popup" value="{$popup}">
    <input type="hidden" name="rescount" value="{$rescount}">
    <input type="hidden" name="ttitle_en" value="{$ttitle_en}">
    <input type="hidden" name="sub" value="\$1">
    {$htm['more_hidden_post']}
EOFORM;
        $response = preg_replace($form_pattern, $form_replace, $response);
        
        $h_b = explode("</head>", $response);
        
        // HTMLプリント
        echo $h_b[0];
        if (!$_conf['ktai']) {
            include_once './style/style_css.inc';
            include_once './style/post_css.inc';
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
        
        return false;
        
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
 * @param   boolean  $is_done     書き込み完了したならtrue
 * @param   string   $result_msg  結果メッセージ
 * @param   boolean  $reload      opener画面を自動で更新するならtrue
 * @return  void
 */
function showPostMsg($is_done, $result_msg, $reload)
{
    global $_conf, $location_ht, $popup, $STYLE, $ttitle, $ptitle;
    
    // プリント用変数
    if (!$_conf['ktai']) {
        $class_ttitle = ' class="thre_title"';
    }
    $ttitle_ht = "<b{$class_ttitle}>{$ttitle}</b>";
    // 2005/03/01 aki: jigブラウザに対応するため、&amp; ではなく & で
    // 2005/04/25 rsk: <script>タグ内もCDATAとして扱われるため、&amp;にしてはいけない
    $location_noenc = preg_replace("/&amp;/", "&", $location_ht);
    $popup_ht = '';
    if ($popup) {
        $popup_ht = <<<EOJS
<script language="JavaScript">
<!--
    opener.location.href="{$location_noenc}";
    var delay= 3*1000;
    setTimeout("window.close()", delay);
// -->
</script>
EOJS;

    } else {
        $meta_refresh_ht = <<<EOP
        <meta http-equiv="refresh" content="1;URL={$location_noenc}">
EOP;
    }

    // HTMLプリント
    echo $_conf['doctype'];
    echo <<<EOHEADER
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
{$meta_refresh_ht}
EOHEADER;

    if ($is_done) {
        echo "<title>p2 - 書きこみました。</title>";
    } else {
        echo "<title>{$ptitle}</title>";
    }

    $kakunin_ht = '';
    
    // PC
    if (!$_conf['ktai']) {
        include_once './style/style_css.inc';
        include_once './style/post_css.inc';
        if ($popup) {
            echo <<<EOSCRIPT
            <script language="JavaScript">
            <!--
                resizeTo({$STYLE['post_pop_size']});
            // -->
            </script>
EOSCRIPT;
        }
        if ($reload) {
            echo $popup_ht;
        }
        
    // 携帯
    } else {
        $kakunin_ht = <<<EOP
<p><a href="{$location_ht}">確認</a></p>
EOP;
    }
    
    echo "</head><body>\n";

    P2Util::printInfoHtml();

    echo <<<EOP
<p>{$ttitle_ht}</p>
<p>{$result_msg}</p>
{$kakunin_ht}
</body>
</html>
EOP;
}

/**
 * subjectからkeyを取得する
 *
 * @return  string|false
 */
function getKeyInSubject()
{
    global $host, $bbs, $ttitle;

    require_once P2_LIB_DIR . '/SubjectTxt.class.php';
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

