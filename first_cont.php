<?php
/**
 * rep2 - スレッド表示部分の初期表示
 * フレーム3分割画面、右下部分
 */

require_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証

// {{{ スレ指定フォーム

$explanation = '見たいスレッドのURLを入力して下さい。例：http://pc.2ch.net/test/read.cgi/mac/1034199997/';

// $defurl = getLastReadTreadUrl();
$defurl = '';
$ini_url_text = '';

$onclick_ht = <<<EOP
var url_v=document.forms["urlform"].elements["url_text"].value;
if(url_v=="" || url_v=="{$ini_url_text}"){
    alert("{$explanation}");
    return false;
}
EOP;
$onclick_ht = htmlspecialchars($onclick_ht, ENT_QUOTES);
$htm['urlform'] = <<<EOP
    <form id="urlform" method="GET" action="{$_conf['read_php']}" target="read">
        スレURLを直接指定
        <input id="url_text" type="text" value="{$defurl}" name="url" size="60">
        <input type="submit" name="btnG" value="表示" onclick="{$onclick_ht}">
    </form>\n
EOP;

// }}}
// {{{ HTMLプリント

echo $_conf['doctype'];
echo <<<EOP
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    {$_conf['extra_headers_ht']}
    <title>rep2</title>
    <link rel="stylesheet" type="text/css" href="css.php?css=style&amp;skin={$skin_en}">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <style type="text/css">
    h1#rep2logo, #urlform {
        line-height: 100%;
        margin: 10px 0;
        padding: 0;
    }
    h1#rep2logo span {
        display: inline-block;
        height: 63px;
        background-color: #fff;
        padding: 10px 15px;
        border-radius: 12px;
        -moz-border-radius: 12px;
        -opera-border-radius: 12px;
        -webkit-border-radius: 12px;
    }
    </style>
</head>
<body>
<br>
<div class="container">
    {$htm['urlform']}
    <hr>
    <h1 id="rep2logo"><span><img src="img/rep2.gif" alt="rep2" title="rep2" width="131" height="63"></span></h1>
</div>
</body>
</html>
EOP;

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
