<?php
/*
    p2 - レス書き込み
*/

include_once './conf/conf.inc.php'; // 基本設定ファイル読込
require_once (P2_LIBRARY_DIR . '/dataphp.class.php');
require_once (P2_LIBRARY_DIR . '/filectl.class.php');

$_login->authorize(); // ユーザ認証

if (!empty($_conf['disable_res'])) {
    P2Util::print403('p2 error: 書き込み機能は無効です。');
}

// 引数エラー
if (empty($_POST['host'])) {
    P2Util::print403('p2 error: 引数の指定が変です');
}

if (!isset($_POST['csrfid']) or $_POST['csrfid'] != P2Util::getCsrfId()) {
    P2Util::print403('p2 error: 不正なポストです');
}

//================================================================
// ■変数
//================================================================
$newtime = date('gis');

$post_keys = array(
    'FROM','mail','MESSAGE',
    'bbs','key','time',
    'host','popup','rescount',
    'subject','submit',
    'sub',
    'ttitle_en');

foreach ($post_keys as $pk) {
    ${$pk} = (isset($_POST[$pk])) ? $_POST[$pk] : '';
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

// {{{ ソースコードがきれいに再現されるように変換

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

// }}}
// {{{ クッキーの読み込み

$cookie_file = P2Util::cachePathForCookie($host);
if ($cookie_cont = @file_get_contents($cookie_file)) {
    $p2cookies = unserialize($cookie_cont);
    if ($p2cookies['expires']) {
        if (time() > strtotime($p2cookies['expires'])) { // 期限切れなら破棄
            // echo "<p>期限切れのクッキーを削除しました</p>";
            unlink($cookie_file);
            unset($cookie_cont, $p2cookies);
        }
    }
}

// }}}

// したらばのlivedoor移転に対応。post先をlivedoorとする。
$host = P2Util::adjustHostJbbs($host);

// machibbs、JBBS@したらば なら
if (P2Util::isHostMachiBbs($host) or P2Util::isHostJbbsShitaraba($host)) {
    $bbs_cgi = "/bbs/write.cgi";
    
    // JBBS@したらば なら
    if (P2Util::isHostJbbsShitaraba($host)) {
        $bbs_cgi = "../../bbs/write.cgi";
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
    
// 2ch
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

$post_cache = array('bbs' => $bbs, 'key' => $key, 'time' => $time, 'FROM' => $FROM, 'mail' => $mail, 'MESSAGE' => $MESSAGE, 'subject' =>$subject);

// submit は書き込むで固定してしまう（Beで書き込むの場合もあるため）
$submit = '書き込む';

if (!empty($_POST['newthread'])) {
    $post = array($submit_k => $submit, $bbs_k => $bbs, $subject_k => $subject, $time_k => $time, $FROM_k => $FROM, $mail_k => $mail, $MESSAGE_k => $MESSAGE);
    if (P2Util::isHostJbbsShitaraba($host)) {
        $post[$dir_k] = $dir;
    }
    $location_ht = "{$_conf['subject_php']}?host={$host}&amp;bbs={$bbs}{$_conf['k_at_a']}";
    
} else {
    $post = array($submit_k => $submit, $bbs_k => $bbs, $key_k => $key, $time_k => $time, $FROM_k => $FROM, $mail_k => $mail, $MESSAGE_k => $MESSAGE);
    if (P2Util::isHostJbbsShitaraba($host)) {
        $post[$dir_k] = $dir;
    }
    $location_ht = "{$_conf['read_php']}?host={$host}&amp;bbs={$bbs}&amp;key={$key}&amp;ls={$rescount}-&amp;refresh=1&amp;nt={$newtime}{$_conf['k_at_a']}#r{$rescount}";
}

// {{{ 2chで●ログイン中ならsid追加

if (!empty($_POST['maru']) and P2Util::isHost2chs($host) && file_exists($_conf['sid2ch_php'])) {
    
    // ログイン後、24時間以上経過していたら自動再ログイン
    if (file_exists($_conf['idpw2ch_php']) and @filemtime($_conf['sid2ch_php']) < time() - 60*60*24) {
        include_once (P2_LIBRARY_DIR . '/login2ch.inc.php');
        login2ch();
    }
    
    include $_conf['sid2ch_php'];
    $post['sid'] = $SID2ch;
}

// }}}

if (!empty($_POST['newthread'])) {
    $ptitle = "p2 - 新規スレッド作成";
} else {
    $ptitle = "p2 - レス書き込み";
}

//================================================================
// 書き込み処理
//================================================================

//=============================================
// ポスト実行 
//=============================================
$posted = postIt($URL);

//=============================================
// cookie 保存
//=============================================
FileCtl::make_datafile($cookie_file, $_conf['p2_perm']); // なければ生成
if ($p2cookies) {$cookie_cont = serialize($p2cookies);}
if ($cookie_cont) {
    if (FileCtl::file_write_contents($cookie_file, $cookie_cont) === false) {
        die("Error: cannot write file.");
    }
}

//=============================================
// スレ立て成功なら、subjectからkeyを取得
//=============================================
if ($_POST['newthread'] && $posted) {
    sleep(1);
    $key = getKeyInSubject();
}

//=============================================
// ■ key.idx 保存
//=============================================
// <> を外す。。
$tag_rec['FROM'] = str_replace('<>', '', $FROM);
$tag_rec['mail'] = str_replace('<>', '', $mail);

// 名前とメール、空白時は P2NULL を記録
$tag_rec_n['FROM'] = ($tag_rec['FROM'] == '') ? 'P2NULL' : $tag_rec['FROM'];
$tag_rec_n['mail'] = ($tag_rec['mail'] == '') ? 'P2NULL' : $tag_rec['mail'];

if ($host && $bbs && $key) {
    $idx_host_dir = P2Util::idxDirOfHost($host);
    
    $keyidx = $idx_host_dir.'/'.$bbs.'/'.$key.'.idx';
    
    // 読み込み
    if ($keylines = @file($keyidx)) {
        $akeyline = explode('<>', rtrim($keylines[0]));
    }
    $sar = array($akeyline[0], $akeyline[1], $akeyline[2], $akeyline[3], $akeyline[4],
                 $akeyline[5], $akeyline[6], $tag_rec_n['FROM'], $tag_rec_n['mail'], $akeyline[9],
                 $akeyline[10], $akeyline[11], $akeyline[12]);
    P2Util::recKeyIdx($keyidx, $sar); // key.idxに記録
}

//=============================================
// 書き込み履歴
//=============================================
if (empty($posted)) {
    exit;
}

if ($host && $bbs && $key) {
    
    $rh_idx = $_conf['pref_dir'] . '/p2_res_hist.idx';
    FileCtl::make_datafile($rh_idx, $_conf['res_write_perm']); // なければ生成
    
    $lines = @file($rh_idx);
    $neolines = array();
    
    // {{{ 最初に重複要素を削除しておく
    
    if (is_array($lines)) {
        foreach ($lines as $line) {
            $line = rtrim($line);
            $lar = explode('<>', $line);
            if ($lar[1] == $key) { continue; } // 重複回避
            if (!$lar[1]) { continue; } // keyのないものは不正データ
            $neolines[] = $line;
        }
    }
    
    // }}}
    
    // 新規データ追加
    $newdata = "$ttitle<>$key<><><><><><>".$tag_rec['FROM'].'<>'.$tag_rec['mail']."<><>$host<>$bbs";
    array_unshift($neolines, $newdata);
    while (sizeof($neolines) > $_conf['res_hist_rec_num']) {
        array_pop($neolines);
    }
    
    // {{{ 書き込む
    
    $temp_file = $rh_idx . '.tmp';
    if ($neolines) {
        $cont = '';
        foreach ($neolines as $l) {
            $cont .= $l . "\n";
        }
        if (FileCtl::file_write_contents($temp_file, $cont) === false or !rename($temp_file, $rh_idx)) {
            die('p2 error: cannot write file.');
        }
    }
    
    // }}}
}

//=============================================
// 書き込みログ記録
//=============================================
if ($_conf['res_write_rec']) {

    // データPHP形式（p2_res_hist.dat.php, タブ区切り）の書き込み履歴を、dat形式（p2_res_hist.dat, <>区切り）に変換する
    P2Util::transResHistLogPhpToDat();

    $date_and_id = date("y/m/d H:i");
    $message = htmlspecialchars($MESSAGE, ENT_NOQUOTES);
    $message = preg_replace("/\r?\n/", "<br>", $message);

    FileCtl::make_datafile($_conf['p2_res_hist_dat'], $_conf['res_write_perm']); // なければ生成
    
    // 新規データ
    $newdata = $tag_rec['FROM'].'<>'.$tag_rec['mail']."<>$date_and_id<>$message<>$ttitle<>$host<>$bbs<>$key";

    // まずタブを全て外して（2chの書き込みではタブは削除される 2004/12/13）
    $newdata = str_replace("\t", '', $newdata);
    // <>をタブに変換して
    //$newdata = str_replace('<>', "\t", $newdata);
    
    $cont = $newdata."\n";
    
    // 書き込み処理
    if (FileCtl::file_write_contents($_conf['p2_res_hist_dat'], $cont, FILE_APPEND) === false) {
        trigger_error('p2 error: 書き込みログの保存に失敗しました', E_USER_WARNING);
        // これは実際は表示されないけれども
        //$_info_msg_ht .= "<p>p2 error: 書き込みログの保存に失敗しました</p>";
    }
}

//===========================================================
// 関数
//===========================================================

/**
 * レス書き込み関数
 */
function postIt($URL)
{
    global $_conf, $post_result, $post_error2ch, $p2cookies, $host, $bbs, $key, $popup, $rescount, $ttitle_en, $STYLE;
    global $bbs_cgi, $post, $post_cache;
    
    $method = "POST";
    $url = "http://" . $host.  $bbs_cgi;
    
    $URL = parse_url($url); // URL分解
    if (isset($URL['query'])) { // クエリー
        $URL['query'] = "?".$URL['query'];
    } else {
        $URL['query'] = "";
    }

    // プロキシ
    if ($_conf['proxy_use']) {
        $send_host = $_conf['proxy_host'];
        $send_port = $_conf['proxy_port'];
        $send_path = $url;
    } else {
        $send_host = $URL['host'];
        $send_port = $URL['port'];
        $send_path = $URL['path'].$URL['query'];
    }

    if (!$send_port) { $send_port = 80; }    // デフォルトを80
    
    $request = $method." ".$send_path." HTTP/1.0\r\n";
    $request .= "Host: ".$URL['host']."\r\n";
    
    $add_user_info = "; p2-client-ip: {$_SERVER['REMOTE_ADDR']}";
    
    $request .= "User-Agent: Monazilla/1.00 (".$_conf['p2name']."/".$_conf['p2version']."{$add_user_info})"."\r\n";
    $request .= 'Referer: http://'.$URL['host'].'/'."\r\n";
    
    // クライアントのIPを送信するp2独自のヘッダ
    $request .= "p2-Client-IP: ".$_SERVER['REMOTE_ADDR']."/\r\n";
    
    // クッキー
    $cookies_to_send = "";
    if ($p2cookies) {
        foreach ($p2cookies as $cname => $cvalue) {
            if ($cname != 'expires') {
                $cookies_to_send .= " {$cname}={$cvalue};";
            }
        }
    }
    
    // be.2ch.net 認証クッキー
    if (P2Util::isHostBe2chNet($host) || !empty($_REQUEST['submit_beres'])) {
        $cookies_to_send .= ' MDMD='.$_conf['be_2ch_code'].';';    // be.2ch.netの認証コード(パスワードではない)
        $cookies_to_send .= ' DMDM='.$_conf['be_2ch_mail'].';';    // be.2ch.netの登録メールアドレス
    }
    
    if (!$cookies_to_send) { $cookies_to_send = ' ;'; }
    $request .= 'Cookie:'.$cookies_to_send."\r\n";
    //$request .= 'Cookie: PON='.$SPID.'; NAME='.$FROM.'; MAIL='.$mail."\r\n";
    
    $request .= "Connection: Close\r\n";
    
    // {{{ POSTの時はヘッダを追加して末尾にURLエンコードしたデータを添付
    if (strtoupper($method) == "POST") {
        while (list($name, $value) = each($post)) {
        
            // したらば or be.2ch.netなら、EUCに変換
            if (P2Util::isHostJbbsShitaraba($host) || P2Util::isHostBe2chNet($host)) {
                $value = mb_convert_encoding($value, 'eucJP-win', 'SJIS-win');
            }
            
            $POST[] = $name."=".urlencode($value);
        }
        $postdata = implode("&", $POST);
        $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $request .= "Content-Length: ".strlen($postdata)."\r\n";
        $request .= "\r\n";
        $request .= $postdata;
    } else {
        $request .= "\r\n";
    }
    // }}}
    
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
    
    //echo '<h4>$request</h4><p>' . $request . "</p>"; //for debug
    fputs($fp, $request);
    
    while (!feof($fp)) {
    
        if ($start_here) {
        
            while (!feof($fp)) {
                $wr .= fread($fp, 164000);
            }
            $response = $wr;
            break;
            
        } else {
            $l = fgets($fp, 164000);
            //echo $l ."<br>"; // for debug
            $response_header_ht .= $l."<br>";
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
                    unset($cookies_to_send);
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
                $start_here = true;
            }
        }
        
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
        $reload = empty($_POST['from_read_new']);
        showPostMsg(true, '書きこみが終わりました。', $reload);
        
        // 投稿失敗記録を削除
        if (file_exists($failed_post_file)) {
            unlink($failed_post_file);
        }
        
        return true;
        //$response_ht = htmlspecialchars($response, ENT_QUOTES);
        //echo "<pre>{$response_ht}</pre>";
    
    // ■cookie確認（post再チャレンジ）
    } elseif (preg_match($cookie_kakunin_match, $response, $matches)) {

        $htm['more_hidden_post'] = '';
        $more_hidden_keys = array('newthread', 'submit_beres', 'from_read_new', 'maru', 'csrfid', 'k', 'b');
        foreach ($more_hidden_keys as $hk) {
            if (isset($_POST[$hk])) {
                $value_hd = htmlspecialchars($_POST[$hk], ENT_QUOTES);
                $htm['more_hidden_post'] .= "<input type=\"hidden\" name=\"{$hk}\" value=\"{$value_hd}\">\n";
            }
        }

        $form_pattern = '/<form method=\"?POST\"? action=\"?\\.\\.\\/test\\/(sub)?bbs\\.cgi\"?>/i';
        $form_replace = <<<EOFORM
<form method="POST" action="./post.php" accept-charset="{$_conf['accept_charset']}">
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
        P2Util::header_content_type();
        echo $h_b[0];
        if (!$_conf['ktai']) {
            @include("style/style_css.inc"); // スタイルシート
            @include("style/post_css.inc"); // スタイルシート
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
        
    // その他はレスポンスをそのまま表示
    } else {
        $response = ereg_replace('こちらでリロードしてください。<a href="\.\./[a-z]+/index\.html"> GO! </a><br>', "", $response);
        echo $response;
        return false;
    }
}

/**
 * 書き込み処理結果表示する
 */
function showPostMsg($isDone, $result_msg, $reload)
{
    global $_conf, $location_ht, $popup, $STYLE, $ttitle;
    global $_info_msg_ht;
    
    // プリント用変数 ===============
    if (!$_conf['ktai']) {
        $class_ttitle = ' class="thre_title"';
    }
    $ttitle_ht = "<b{$class_ttitle}>{$ttitle}</b>";
    // 2005/03/01 aki: jigブラウザに対応するため、&amp; ではなく & で
    // 2005/04/25 rsk: <script>タグ内もCDATAとして扱われるため、&amp;にしてはいけない
    $location_noenc = preg_replace("/&amp;/", "&", $location_ht);
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

    // プリント ==============
    P2Util::header_content_type();
    if ($_conf['doctype']) { echo $_conf['doctype']; }
    echo <<<EOHEADER
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
{$meta_refresh_ht}
EOHEADER;

    if ($isDone) {
        echo "    <title>p2 - 書きこみました。</title>";
    } else {
        echo "    <title>{$ptitle}</title>";
    }

    if (!$_conf['ktai']) {
        @include("style/style_css.inc"); // スタイルシート
        @include("style/post_css.inc"); // スタイルシート
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
    } else {
        $kakunin_ht = <<<EOP
<p><a href="{$location_ht}">確認</a></p>
EOP;
    }
    
    echo "</head>\n";
    echo "<body>\n";

echo $_info_msg_ht;
$_info_msg_ht = "";

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
 */
function getKeyInSubject()
{
    global $host, $bbs, $ttitle;

    require_once (P2_LIBRARY_DIR . '/SubjectTxt.class.php');
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

?>
