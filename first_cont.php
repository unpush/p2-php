<?php
// rep2 -  スレッド表示部分の初期表示
// フレーム3分割画面、右下部分

include_once './conf/conf.inc.php';

// {{{ スレ指定フォーム

$explanation = '見たいスレッドのURLを入力して下さい。例：http://pc.2ch.net/test/read.cgi/mac/1034199997/';

// $defurl = getLastReadTreadUrl();

$onClick_ht = <<<EOP
var url_v=document.forms["urlform"].elements["url_text"].value;
if(url_v=="" || url_v=="{$ini_url_text}"){
    alert("{$explanation}");
    return false;
}
EOP;
$htm['urlform'] = <<<EOP
    <form id="urlform" method="GET" action="{$_conf['read_php']}" target="read">
        スレURLを直接指定
        <input id="url_text" type="text" value="{$defurl}" name="url" size="62">
        <input type="submit" name="btnG" value="表示" onClick='{$onClick_ht}'>
    </form>\n
EOP;

// }}}

//=============================================================
// HTMLプリント
//=============================================================
echo $_conf['doctype'];
echo <<<EOP
<html lang="ja">
<head>
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>rep2</title>
    <link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
</head>
<body>
<br>
<div class="container">
    {$htm['urlform']}
    <hr>
    <h1><img src="img/rep2.gif" alt="rep2" width="131" height="63"></h1>
</div>
</body>
</html>
EOP;
