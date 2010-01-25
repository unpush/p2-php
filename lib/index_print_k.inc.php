<?php
/**
 * rep2 - 携帯用インデックスプリント関数
 */

// {{{ index_print_k()

/**
* 携帯用インデックスプリント
*/
function index_print_k()
{
    global $_conf, $_login;

    $info_msg_ht = P2Util::getInfoHtml();

    $newtime = date('gis');

    $body = "";
    $ptitle = "rep2ﾓﾊﾞｲﾙ";

    // 認証ユーザ情報
    $htm['auth_user'] = "<p>ﾛｸﾞｲﾝﾕｰｻﾞ: {$_login->user_u} - " . date("Y/m/d (D) G:i:s") . "</p>\n";

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

    $rss_k_ht = '';
    $iv2_k_ht = '';
    if ($_conf['expack.rss.enabled']) {
        $rss_k_ht = "<a href=\"menu_k.php?view=rss{$m_rss_set_a}{$_conf['k_at_a']}\">RSS</a><br>";
    }
    if ($_conf['expack.ic2.enabled'] == 2 || $_conf['expack.ic2.enabled'] == 3) {
        $iv2_k_ht = "<a href=\"iv2.php?reset_filter=1{$_conf['k_at_a']}\">画像ｷｬｯｼｭ一覧</a><br>";
    }

    //=========================================================
    // 携帯用 HTML プリント
    //=========================================================
    P2Util::header_nocache();
    echo $_conf['doctype'];
    echo <<<EOP
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
{$_conf['extra_headers_ht']}
<title>{$ptitle}</title>
</head>
<body{$_conf['k_colors']}>
<h1>{$ptitle}</h1>
{$info_msg_ht}
<div>
<a href="subject.php?spmode=fav&amp;sb_view=shinchaku{$_conf['k_at_a']}"{$_conf['k_accesskey_at'][1]}>{$_conf['k_accesskey_st'][1]}お気にｽﾚの新着</a><br>
<a href="subject.php?spmode=fav{$_conf['k_at_a']}"{$_conf['k_accesskey_at'][2]}>{$_conf['k_accesskey_st'][2]}お気にｽﾚの全て</a><br>
<a href="menu_k.php?view=favita{$_conf['k_at_a']}"{$_conf['k_accesskey_at'][3]}>{$_conf['k_accesskey_st'][3]}お気に板</a><br>
<a href="menu_k.php?view=cate{$_conf['k_at_a']}"{$_conf['k_accesskey_at'][4]}>{$_conf['k_accesskey_st'][4]}板ﾘｽﾄ</a><br>
<a href="subject.php?spmode=recent&amp;sb_view=shinchaku{$_conf['k_at_a']}"{$_conf['k_accesskey_at'][5]}>{$_conf['k_accesskey_st'][5]}最近読んだｽﾚの新着</a><br>
<a href="subject.php?spmode=recent{$_conf['k_at_a']}"{$_conf['k_accesskey_at'][6]}>{$_conf['k_accesskey_st'][6]}最近読んだｽﾚの全て</a><br>
<a href="subject.php?spmode=res_hist{$_conf['k_at_a']}"{$_conf['k_accesskey_at'][7]}>{$_conf['k_accesskey_st'][7]}書込履歴</a> <a href="read_res_hist.php?nt={$newtime}{$_conf['k_at_a']}">ﾛｸﾞ</a><br>
<a href="subject.php?spmode=palace&amp;norefresh=1{$_conf['k_at_a']}"{$_conf['k_accesskey_at'][8]}>{$_conf['k_accesskey_st'][8]}ｽﾚの殿堂</a><br>
<a href="setting.php?nt={$newtime}{$_conf['k_at_a']}"{$_conf['k_accesskey_at'][9]}>{$_conf['k_accesskey_st'][9]}ﾛｸﾞｲﾝ管理</a><br>
<a href="editpref.php?nt={$newtime}{$_conf['k_at_a']}"{$_conf['k_accesskey_at'][0]}>{$_conf['k_accesskey_st'][0]}設定管理</a><br>
{$rss_k_ht}
<a href="tgrepc.php{$_conf['k_at_q']}">ｽﾚﾀｲ検索</a><br>
{$iv2_k_ht}
</div>
<hr>
{$htm['auth_user']}
{$htm['last_login']}
</body>
</html>
EOP;

}

// }}}

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
