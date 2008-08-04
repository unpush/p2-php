<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>tGrep<?php if (strlen($htm['query']) > 0) { echo ' - ', $htm['query']; } ?></title>
    <?php echo $_conf['extra_headers_ht']; ?>
    <style type="text/css">
h1, h2, h3, h4 {
    font-size: medium;
    margin-bottom: 0.5em;
}

ul {
    margin: 0.5em;
}

div#quicksearch ul, div#recent ul {
    display: block;
    padding: 0px;
}

div#quicksearch li, div#recent li {
    display: inline;
    padding: 0px;
    margin: 0px 0.5em 0px 0px;
    font-size: large;
}

div#howto ul {
    padding-left: 1.5em;
}
</style>
</head>
<body>

<h1 id="top" name="top">スレッドタイトル検索</h1>

<!-- Search Form -->
<form action="<?php echo $htm['php_self']; ?>" method="get">
<input type="hidden" name="hint" value="◎◇　◇◎">
<input name="Q" <?php echo $htm['search_attr']; ?>>
<input type="submit" value="検索">
</form>
<hr>

<?php if (!$query) { ?>
<?php
if ($_conf['expack.tgrep.quicksearch']) {
    echo "<div id=\"quicksearch\">\n";
    include_once P2EX_LIB_DIR . '/tgrep/menu_quick.inc.php';
    echo "</div>\n<hr>\n";
}
if ($_conf['expack.tgrep.recent_num'] > 0) {
    echo "<div id=\"recent\">\n";
    include_once P2EX_LIB_DIR . '/tgrep/menu_recent.inc.php';
    echo "</div>\n<hr>\n";
}
?>
<!-- HowTo -->
<div id="howto">
<h4>仕様</h4>
<ul>
<li>キーワードはスペース区切りで3つまで指定でき、すべてを含むものが抽出されます。</li>
<li>2つ目以降のキーワードで頭に&quot;-&quot;をつけると、それを含まないものが抽出されます。</li>
<li>&quot; または &#39; で囲まれた部分はスペースが入っていても一つのキーワードとして扱います。</li>
<li>キーワードの全角半角、大文字小文字は無視されます。</li>
<li>データベースの更新頻度は3時間に1回で、レス数・勢い・活発さは更新時点での値です。</li>
</uL>
</div>
<?php } ?>
<?php if ($errors) { ?>
<!-- Errors -->
<h4>エラー</h4>
<ul><?php foreach ($errors as $error) { ?><li><?php echo htmlspecialchars($error, ENT_QUOTES); ?></li><?php } ?></ul>
<?php } ?>

<?php if (!$errors && $profile) { ?>
<!-- Result and Filter -->
<p>
<?php if ($htm['category'] && isset($profile['categories'][$htm['category']])) { ?>
<b><?php echo htmlspecialchars($profile['categories'][$htm['category']]->name, ENT_QUOTES); ?></b>から<b><?php echo $htm['query']; ?></b>を検索:<?php echo $htm['hits']; ?>hit!(all:<?php echo $htm['allhits']; ?>)
<?php } else { ?>
<b><?php echo $htm['query']; ?></b>で検索:<?php echo $htm['hits']; ?>hit!
<?php } ?>
</p>
<form action="<?php echo $htm['php_self']; ?>" method="get">
<input type="hidden" name="Q" value="<?php echo $htm['query']; ?>">
<select name="C">
<option value="">カテゴリを選択</option>
<?php foreach ($profile['categories'] as $c) { ?><option value="<?php echo $c->id; ?>"<?php if ($c->id == $htm['category']) { echo ' selected'; } ?>><?php echo mb_convert_kana(htmlspecialchars($c->name, ENT_QUOTES), 'rnsk'); ?> (<?php echo $c->hits; ?>)</option><?php } ?>
</select>
<input type="submit" value="絞込">
</form>
<hr>
<?php } ?>

<?php if ($threads) { ?>
<!-- ThreadList and Pager -->
<div><a href="#bottom" align="right" title="下へ">▼</a></div>
<?php
include_once P2_LIB_DIR . '/thread.class.php';
foreach ($threads as $o => $t) {
    $new = '';
    $turl = sprintf('%s?host=%s&amp;bbs=%s&amp;key=%d', $_conf['read_php'], $t->host, $t->bbs, $t->tkey);
    $burl = sprintf('%s?host=%s&amp;bbs=%s&amp;itaj_en=%s&amp;word=%s', $_conf['subject_php'], $t->host, $t->bbs, urlencode(base64_encode($t->ita)), $htm['query_en']);
    $aThread = new Thread;
    $aThread->setThreadPathInfo($t->host, $t->bbs, $t->tkey);
    if ($aThread->getThreadInfoFromIdx() && $aThread->isKitoku()) {
        $rnum = max($t->resnum, $aThread->readnum);
        $nnum = max(0, $rnum - $aThread->readnum);
    } else {
        $rnum = $t->resnum;
        $nnum = '';
    }
    if (!empty($_conf['k_save_packet'])) {
        $ttitle = mb_convert_kana($t->title, 'rnsk');
        $itaj = mb_convert_kana($t->ita, 'rnsk');
    } else {
        $ttitle = $t->title;
        $itaj = $t->ita;
    }
?>
<p><?php echo $o; ?>.<a href="<?php echo $turl; ?>"><?php echo $ttitle; ?></a><br>
<small><?php echo date('y/m/d ', $t->tkey); ?><a href="<?php echo $burl; ?>"><?php echo $itaj; ?>(<?php echo $profile['boards'][$t->bid]->hits; ?>)</a></small></p>
<?php } ?>
<div><a href="#top" align="right" title="上へ">▲</a></div>
<?php if ($htm['pager']) { ?>
<hr>
<div><?php echo $htm['pager']; ?></div>
<?php } ?>
<?php } ?>
<hr>
<p id="bottom" name="bottom">
<a href="index.php">TOP</a>
<?php if ($query) { ?>
<a href="tgrepc.php">tGrep</a>
<?php if ($_conf['expack.tgrep.quicksearch']) { ?>
<a href="tgrepctl.php?file=quick&amp;query=<?php echo $htm['query_en']; ?>"><?php echo $htm['query']; ?>を一発検索に追加</a>
<?php } ?>
<?php } ?>
</p>
</body>
</html>
