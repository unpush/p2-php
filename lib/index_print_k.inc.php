<?php
/*
    rep2 -  携帯用インデックスプリント関数
*/

/**
* 携帯用インデックスプリント
*/
function index_print_k()
{
    global $_conf, $_login, $_info_msg_ht;

    $newtime = date('gis');
    
    $body = "";
    $ptitle = "rep2ﾓﾊﾞｲﾙ";
    
    // 認証ユーザ情報
    $htm['auth_user'] = "<p>ﾛｸﾞｲﾝﾕｰｻﾞ: {$_login->user_u} - ".date("Y/m/d (D) G:i:s")."</p>\n";
    
    // 前回のログイン情報
    if ($_conf['login_log_rec'] && $_conf['last_login_log_show']) {
        if (($log = P2Util::getLastAccessLog($_conf['login_log_file'])) !== false) {
            $log_hd = array_map('htmlspecialchars', $log);
            $htm['last_login'] = <<<EOP
前回のﾛｸﾞｲﾝ情報 - {$log_hd['date']}<br>
ﾕｰｻﾞ:   {$log_hd['user']}<br>
IP:     {$log_hd['ip']}<br>
HOST:   {$log_hd['host']}<br>
UA:     {$log_hd['ua']}<br>
REFERER: {$log_hd['referer']}
EOP;
        }
    }
    
    // 古いセッションIDがキャッシュされていることを考慮して、ユーザ情報を付加しておく
    // （リファラを考慮して、つけないほうがいい場合もあるので注意）
    $user_at_a = '&amp;user='.$_login->user_u;
    $user_at_q = '?user='.$_login->user_u;
    
    //=========================================================
    // 携帯用 HTML プリント
    //=========================================================
    P2Util::header_nocache();
    P2Util::header_content_type();
    if (isset($_conf['doctype'])) {
        echo $_conf['doctype'];
    }
    echo <<<EOP
<html>
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <title>{$ptitle}</title>
</head>
<body>
<h1>{$ptitle}</h1>
{$_info_msg_ht}

<a {$_conf['accesskey']}="1" href="subject.php?spmode=fav&amp;sb_view=shinchaku{$_conf['k_at_a']}{$user_at_a}">1.お気にｽﾚの新着</a><br>
<a {$_conf['accesskey']}="2" href="subject.php?spmode=fav{$_conf['k_at_a']}{$user_at_a}">2.お気にｽﾚの全て</a><br>
<a {$_conf['accesskey']}="3" href="menu_k.php?view=favita{$_conf['k_at_a']}{$user_at_a}">3.お気に板</a><br>
<a {$_conf['accesskey']}="4" href="menu_k.php?view=cate{$_conf['k_at_a']}{$user_at_a}">4.板ﾘｽﾄ</a><br>
<a {$_conf['accesskey']}="5" href="subject.php?spmode=recent&amp;sb_view=shinchaku{$_conf['k_at_a']}{$user_at_a}">5.最近読んだｽﾚの新着</a><br>
<a {$_conf['accesskey']}="6" href="subject.php?spmode=recent{$_conf['k_at_a']}{$user_at_a}">6.最近読んだｽﾚの全て</a><br>
<a {$_conf['accesskey']}="7" href="subject.php?spmode=res_hist{$_conf['k_at_a']}{$user_at_a}">7.書込履歴</a> <a href="read_res_hist.php?nt={$newtime}{$_conf['k_at_a']}">ﾛｸﾞ</a><br>
<a {$_conf['accesskey']}="8" href="subject.php?spmode=palace&amp;norefresh=true{$_conf['k_at_a']}{$user_at_a}">8.ｽﾚの殿堂</a><br>
<a {$_conf['accesskey']}="9" href="setting.php?dummy=1{$user_at_a}{$_conf['k_at_a']}">9.ﾛｸﾞｲﾝ管理</a><br>
<a {$_conf['accesskey']}="0" href="editpref.php?dummy=1{$user_at_a}{$_conf['k_at_a']}">0.設定管理</a><br>

<hr>
{$htm['auth_user']}
{$htm['last_login']}
</body>
</html>
EOP;

}
?>
