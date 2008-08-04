<?php
// vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
/**
 * rep2 - iPhone専用メニュー (要iui)
 *
 * @link http://code.google.com/p/iui/
 */

require_once P2_LIB_DIR . '/brdctl.class.php';

// TODO: レンダリング済の板リストをキャッシュする
$brd_menus = BrdCtl::read_brds()

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS" />
    <meta name="viewport" content="width=320" content="initial-scale=1.0" />
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW" />
    <title>rep2</title>
    <script type="application/x-javascript" src="iui/iui.js"></script>
    <link rel="stylesheet" type="text/css" href="iui/iui.css" />
</head>
<body>

<div class="toolbar">
    <h1 id="pageTitle"></h1>
    <a id="backButton" class="button" href="#"></a>
    <a class="button" href="#search">検索</a>
</div>

<!-- {{{ トップメニュー -->
<ul id="top" title="Menu" selected="true">
<?php if ($_info_msg_ht) { ?>
    <li><a href="#info_msg" style="color:red">エラー</a></li>
<?php } ?>

    <li class="group">リスト</li>
<?php if ($_conf['expack.misc.multi_favs']) { ?>
    <li><a href="#fav">お気にスレ</a></li>
    <li><a href="#favita">お気に板</a></li>
<?php } else { ?>
    <li><a href="subject.php?spmode=fav&amp;sb_view=shinchaku" target="_self">お気にスレの新着</a></li>
    <li><a href="subject.php?spmode=fav" target="_self">お気にスレ</a></li>
    <li><a href="menu_k.php?view=favita" target="_self">お気に板</a></li>
<?php } ?>
    <li><a href="#cate">板リスト</a></li>
    <li><a href="subject.php?spmode=palace&amp;norefresh=1" target="_self">スレの殿堂</a></li>

    <li class="group">履歴</li>
    <li><a href="subject.php?spmode=recent&amp;sb_view=shinchaku" target="_self">最近読んだスレの新着</a></li>
    <li><a href="subject.php?spmode=recent" target="_self">最近読んだスレ</a></li>
    <li><a href="subject.php?spmode=res_hist" target="_self">書き込み履歴</a></li>
    <li><a href="read_res_hist.php" target="_self">書き込み履歴の内容</a></li>

    <li class="group">expack</li>
<?php if ($_conf['expack.rss.enabled']) { if ($_conf['expack.misc.multi_favs']) { ?>
    <li><a href="#rss">RSS</a></li>
<?php } else { ?>
    <li><a href="menu_k.php?view=rss" target="_self">RSS</a></li>
<?php } } ?>
    <li><a href="tgrepc.php" target="_self">スレッドタイトル検索</a></li>
<?php if ($_conf['expack.ic2.enabled'] == 2 || $_conf['expack.ic2.enabled'] == 3) { ?>
    <li><a href="iv2.php" target="_self">画像キャッシュ一覧</a></li>
<?php } ?>

    <li class="group">管理</li>
    <li><a href="editpref.php" target="_self">設定管理</a></li>
    <li><a href="setting.php" target="_self">ログイン管理</a></li>
    <li><a href="#login_info">ログイン情報</a></li>
</ul>
<!-- }}} -->

<?php
// エラー
if ($_info_msg_ht) { 
    echo '<div id="info_msg" class="panel" title="エラー">', $_info_msg_ht, '</div>';
}

// {{{ お気にセット
if ($_conf['expack.misc.multi_favs']) {
    // {{{ お気にスレ

    $favlist = FavSetManager::getFavSetTitles('m_favlist_set');
    $fav_elems = '';
    $fav_new_elems = '';
    $fav_elem_prefix = '';

    foreach ($favlist as $no => $name) {
        $fav_url = "subject.php?spmode=fav&amp;m_favlist_set={$no}";
        $fav_elems .= "<li><a href=\"{$fav_url}\" target=\"_self\">{$name}</a></li>";
        $fav_new_elems .= "<li><a href=\"{$fav_url}&amp;sb_view=shinchaku\" target=\"_self\">{$name}</a></li>";
    }

    echo '<ul id="fav" title="お気にスレ">';
    echo '<li class="group">新着</li>';
    echo $fav_new_elems;
    echo '<li class="group">全て</li>';
    echo $fav_elems;
    echo '</ul>';

    // }}}
    // {{{ お気に板

    $favita = FavSetManager::getFavSetTitles('m_favita_set');

    echo '<ul id="favita" title="お気に板">';

    foreach ($favita as $no => $name) {
        echo "<li><a href=\"menu_k.php?view=favita&amp;m_favita_set={$no}\" target=\"_self\">{$name}</a></li>";
    }

    echo '</ul>';

    // }}}
    // {{{ RSS

    if ($_conf['expack.rss.enabled']) { 
        $rss = FavSetManager::getFavSetTitles('m_rss_set');

        echo '<ul id="rss" title="RSS">';

        foreach ($favita as $no => $name) {
            echo "<li><a href=\"menu_k.php?view=rss&amp;m_rss_set={$no}\" target=\"_self\">{$name}</a></li>";
        }

        echo '</ul>';
    }

    // }}}
}
// }}}
?>

<!-- {{{ 板リスト (カテゴリ一覧) -->
<ul id="cate" title="板リスト"><?php
if ($brd_menus) {
    $cate_id = 0;
    foreach ($brd_menus as $a_brd_menu) {
        foreach ($a_brd_menu->categories as $category) {
            $cate_id++;
            echo "<li><a href=\"#cate{$cate_id}\">{$category->name}</a></li>";
        }
    }
}
?></ul>
<!-- }}} -->

<!-- {{{ 板リスト (カテゴリ別) -->
<?php
if ($brd_menus) {
    $cate_id = 0;
    foreach ($brd_menus as $a_brd_menu) {
        foreach ($a_brd_menu->categories as $category) {
            $cate_id++;

            echo "<ul id=\"cate{$cate_id}\" title=\"{$category->name}\">";

            foreach ($category->menuitas as $mita) {
                echo "<li><a href=\"{$_conf['subject_php']}?host={$mita->host}&amp;bbs={$mita->bbs}",
                        "&amp;itaj_en={$mita->itaj_en}\" target=\"_self\">{$mita->itaj_ht}</a></li>";
            }

            echo '</ul>';
        }
    }
}
?>
<!-- }}} -->

<!-- {{{ ログイン情報 -->
<div id="login_info" class="panel" title="ログイン情報">
<h2>認証ユーザ</h2>
<p><strong><?php echo $_login->user; ?></strong> - <?php echo date('Y/m/d (D) G:i:s'); ?></p>
<?php if ($_conf['login_log_rec'] && $_conf['last_login_log_show']) { ?>
<h2>前回のログイン</h2>
<pre style="word-wrap:break-word;word-break:break-all"><?php
if (($log = P2Util::getLastAccessLog($_conf['login_log_file'])) !== false) {
    $log_hd = array_map('htmlspecialchars', $log);
    echo <<<EOP
<strong>DATE:</strong> {$log_hd['date']}
<strong>USER:</strong> {$log_hd['user']}
<strong>  IP:</strong> {$log_hd['ip']}
<strong>HOST:</strong> {$log_hd['host']}
<strong>  UA:</strong> {$log_hd['ua']}
<strong>REFERER:</strong> {$log_hd['referer']}
EOP;
}
?></pre>
<?php } ?>
</div>
<!-- }}} -->

<!-- {{{ 検索用JavaScript -->
<script type="application/x-javascript">
function setSearchTarget(is_board)
{
    var f = document.getElementById('search');
    var k = document.getElementById('keyword');
    if (is_board == 'true') {
        f.setAttribute('action', 'menu_k.php');
        k.setAttribute('name', 'word');
    } else {
        f.setAttribute('action', 'tgrepc.php');
        k.setAttribute('name', 'Q');
    }
}
</script>
<!-- }}} -->

<!-- {{{ 検索パネル -->
<form id="search" class="panel" title="検索"
  method="get" action="tgrepc.php" target="_self"
  accept-charset="<?php echo $_conf['accept_charset']; ?>">
<fieldset>
    <div class="row">
        <label>モード</label>
        <div class="toggle" onclick="setSearchTarget(this.getAttribute('toggled'))">
            <span class="thumb"></span>
            <span class="toggleOn">板</span>
            <span class="toggleOff">スレ</span>
        </div>
    </div>
    <div class="row">
        <label>キーワード</label>
        <input type="text" id="keyword" name="Q" value="" />
    </div>
    <div class="row">
        <input type="submit" value="OK" />
    </div>
</fieldset>
</form>
<!-- }}} -->

</body>
</html>
