<?php
require_once P2_LIB_DIR . '/index.funcs.php';

/**
 * p2 - 携帯用インデックスをHTMLプリントする関数
 *
 * @return  void
 */
function index_print_k()
{
    global $_conf, $_login;

    $menuKLinkHtmls = getIndexMenuKLinkHtmls($_conf['menuKIni']);
    
    $body = '';
    $ptitle = $_conf['p2name'] . 'iPhone';
    $ptitle_hs = hs($ptitle);
    
    // ログインユーザ情報
    $auth_user_ht   = sprintf(
        '<p>ﾛｸﾞｲﾝﾕｰｻﾞ: %s - %s</p>',
        hs($_login->user_u), date('Y/m/d (D) G:i:s') 
    );
    
    // p2ログイン用URL
    $login_url          = rtrim(dirname(P2Util::getMyUrl()), '/') . '/';
    $login_url_pc       = $login_url . '?b=pc';
    $login_url_pc_hs    = hs($login_url_pc);
    $login_url_k        = $login_url . '?b=k&user=' . $_login->user_u;
    $login_url_k_hs     = hs($login_url_k);
    
    // 前回のログイン情報
    if ($_conf['login_log_rec'] && $_conf['last_login_log_show']) {
        if (false !== $log = P2Util::getLastAccessLog($_conf['login_log_file'])) {
            $log_hs = array_map('htmlspecialchars', $log);
            $htm['last_login'] = <<<EOP
<font color="#888888">
前回のﾛｸﾞｲﾝ情報 - {$log_hs['date']}<br>
ﾕｰｻﾞ:   {$log_hs['user']}<br>
IP:     {$log_hs['ip']}<br>
HOST:   {$log_hs['host']}<br>
UA:     {$log_hs['ua']}<br>
REFERER: {$log_hs['referer']}
</font>
EOP;
        }
    }
    
    // 古いセッションIDがキャッシュされていることを考慮して、ユーザ情報を付加しておく
    // （リファラを考慮して、つけないほうがいい場合もあるので注意）
    $user_at_a = '&amp;user=' . $_login->user_u;
    $user_at_q = '?user=' . $_login->user_u;
    
    require_once P2_LIB_DIR . '/BrdCtl.php';
    $search_form_htm = BrdCtl::getMenuKSearchFormHtml('menu_i.php');

    $body_at    = P2View::getBodyAttrK();
    $hr         = P2View::getHrHtmlK();

    //=========================================================
    // 携帯用 HTML出力
    //=========================================================
    P2Util::headerNoCache();
    P2View::printDoctypeTag();
    ?>
<html>
<head>
<?php
    P2View::printExtraHeadersHtml();
echo <<<EOP
<script type="text/javascript"> 
<!-- 
window.onload = function() { 
setTimeout(scrollTo, 100, 0, 1); 
} 
// --> 
</script> 
<style type="text/css" media="screen">@import "./iui/iui.css";</style>
    <title>{$ptitle_hs}</title>
</head>
<body>
    <div class="toolbar">
<h1 id="pageTitle">{$ptitle_hs}</h1>
    <a class="button" href="edit_indexmenui.php{$user_at_q}{$_conf['k_at_a']}">並替</a>
    </div>
    <ul id="home">
    <li class="group">メニュー</li>
EOP;

P2Util::printInfoHtml();

foreach ($menuKLinkHtmls as $v) {
    echo "<li>" . $v . "</li>\n";
}

echo <<<EOP
<li class="group">検索</li>
{$search_form_htm}
</ul>
<br>
</body>
</html>
EOP;

}
/*

{$hr}
{$auth_user_ht}

{$hr}
{$htm['last_login']}
*/

//============================================================================
// 関数（このファイル内でのみ利用）
//============================================================================
/**
 * メニュー項目のリンクHTMLを取得する
 *
 * @param   array   $menuKIni  メニュー項目 標準設定
 * @param   boolean $noLink    リンクをつけないのならtrue
 * @return  string  HTML
 */
function _getMenuKLinkHtml($code, $menuKIni, $noLink = false)
{
    global $_conf, $_login;
    
    static $accesskey_;
    
    // 無効なコード指定なら
    if (!isset($menuKIni[$code][0]) || !isset($menuKIni[$code][1])) {
        return false;
    }
    
    if (!isset($accesskey_)) {
        $accesskey_ = 0;
    } else {
        $accesskey_++;
    }
    $accesskey = $accesskey_;
    
    if ($_conf['index_menu_k_from1']) {
        $accesskey = $accesskey + 1;
        if ($accesskey == 10) {
            $accesskey = 0;
        }
    }
    if ($accesskey > 9) {
        $accesskey = null;
    }
    
    $href = $menuKIni[$code][0] . '&user=' . $_login->user_u . '&' . UA::getQueryKey() . '=' . UA::getQueryValue();
    $name = $menuKIni[$code][1];
    /*if (!is_null($accesskey)) {
        $name = $accesskey . '.' . $name;
    }*/

    if ($noLink) {
        $linkHtml = hs($name);
    } else {
        $accesskeyAt = is_null($accesskey) ? '' : " {$_conf['accesskey']}=\"{$accesskey}\"";
        $linkHtml = "<a href=\"" . hs($href) . '">' . hs($name) . "</a>";
    }
    
    // 特別 - #.ログ
    if ($code == 'res_hist') {
        $name = 'ログ';
        if ($noLink) {
            $logHt = hs($name);
        } else {
            $newtime = date('gis');
            $logHt = P2View::tagA(
                P2Util::buildQueryUri('read_res_hist.php',
                    array(
                        'nt' => $newtime,
                        UA::getQueryKey() => UA::getQueryValue()
                    )
                ),
                hs($name),
                array($_conf['accesskey'] => '#')
            );
        }
        $linkHtml .= ' </li><li>' . $logHt ;
    }
    
    return $linkHtml;
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
