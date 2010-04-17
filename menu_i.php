<?php
/**
 * rep2 - iPhone/iPod Touch専用メニュー (要iui)
 *
 * @link http://code.google.com/p/iui/
 */

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/menu_iphone.inc.php';

$_login->authorize(); //ユーザ認証

if ($_conf['view_forced_by_query']) {
    output_add_rewrite_var('b', $_conf['b']);
}

// {{{ 板リスト (Ajax)

if (isset($_GET['cateid'])) {
    menu_iphone_ajax('menu_iphone_show_board_menu', (int)$_GET['cateid']);
    exit;
}

// }}}
// {{{ 板検索 (Ajax)

if (isset($_POST['word'])) {
    $word = menu_iphone_unicode_urldecode($_POST['word']);
    if (substr_count($word, '.') == strlen($word)) {
        $word = '';
    }

    if (strlen($word) > 0 && p2_set_filtering_word($word, 'and') !== null) {
        menu_iphone_ajax('menu_iphone_show_matched_boards', $word);
    } else {
        header('Content-Type: application/xml; charset=UTF-8');
        echo mb_convert_encoding('<div class="panel">無効なキーワードです。</div>', 'UTF-8', 'CP932');
    }
    exit;
}

// }}}
// {{{ HTML出力
// {{{ ヘッダ
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja">
<head>
    <meta http-equiv="Content-Type" content="application/xhtml+xml; charset=Shift_JIS" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=yes" />
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW" />
    <title>rep2</title>
    <link rel="stylesheet" type="text/css" href="iui/iui.css?<?php echo $_conf['p2_version_id']; ?>" />
    <link rel="stylesheet" type="text/css" href="css/menu_i.css?<?php echo $_conf['p2_version_id']; ?>" />
    <link rel="apple-touch-icon" type="image/png" href="img/touch-icon/p2-serif.png" />
    <script type="text/javascript" src="iui/iui.js?<?php echo $_conf['p2_version_id']; ?>"></script>
    <script type="text/javascript" src="js/json2.js?<?php echo $_conf['p2_version_id']; ?>"></script>
    <script type="text/javascript" src="js/iphone.js?<?php echo $_conf['p2_version_id']; ?>"></script>
    <script type="text/javascript" src="js/menu_i.js?<?php echo $_conf['p2_version_id']; ?>"></script>
<?php
// {{{ 指定サブメニューへ自動で移動
// $hashesの取得が未実装なので封印。
/*
if (isset($hashes) && is_array($hashes) && count($hashes)) {
    $js = '';
    $last = array_pop($hashes);
    while (($hash = array_shift($hashes)) !== null) {
        $hash = trim($hash);
        if ($hash === '') {
            continue;
        }
        $js .= "'" . StrCtl::toJavaScript($hash) . "',";
    }
    $hash = trim($last);
    if ($hash !== '') {
        $js .= "'" . StrCtl::toJavaScript($hash) . "'";
    } else {
        $js .= "'_'";
    }
?>
    <script type="text/javascript">
    // <![CDATA[
    window.addEventListener('load', function(event) {
        window.removeEventListener(event.type, arguments.callee, false);

        window.setTimeout(function(subMenus, contextNode, delayMsec) {
            var id, anchor, child, evt;

            if (!subMenus.length || !contextNode) {
                return;
            }

            id = subMenus.shift();
            anchor = document.evaluate('./li/a[@href="#' + id + '"]',
                                       contextNode,
                                       null,
                                       XPathResult.FIRST_ORDERED_NODE_TYPE,
                                       null).singleNodeValue;
            child = document.getElementById(id);

            if (anchor && child) {
                evt = document.createEvent('MouseEvents');
                evt.initMouseEvent('click', true, true, window,
                                   0, 0, 0, 0, 0,
                                   false, false, false, false, 0, null);
                anchor.dispatchEvent(evt);

                if (subMenus.length) {
                    contextNode = child;
                    window.setTimeout(arguments.callee, delayMsec,
                                      subMenus, child, delayMsec);
                }
            }
        }, 200, [<?php echo $js; ?>], document.getElementById('top'), 200);
    });
    // ]]>
    </script>
<?php
}*/
// }}}
?>
</head>
<body>

<?php
// }}}
// {{{ ツールバー
?>

<div class="toolbar">
    <h1 id="pageTitle"></h1>
    <a id="backButton" class="button" style="z-index:2" href="#"></a>
    <a class="button leftButton" href="#boardSearch">板</a>
    <a class="button" href="#threadSearch">ｽﾚ</a>
</div>

<?php
// }}}
// {{{ トップメニュー
?>

<ul id="top" title="rep2" selected="true">
<?php if (P2Util::hasInfoHtml()) { ?>
    <li><a href="#info_msg" class="color-r">エラー</a></li>
<?php } ?>
    <li class="group">リスト</li>
<?php if ($_conf['expack.misc.multi_favs']) { ?>
    <li><a href="#fav">お気にスレ</a></li>
<?php } else { ?>
    <?php /* <li><a href="subject.php?spmode=fav&amp;sb_view=shinchaku" target="_self">お気にスレの新着</a></li> */ ?>
    <li><a href="subject.php?spmode=fav" target="_self">お気にスレ</a></li>
<?php } ?>
    <li><a href="#favita">お気に板</a></li>
    <li><a href="menu_i.php?cateid=0">板リスト</a></li>
    <li><a href="subject.php?spmode=palace&amp;norefresh=1" target="_self">スレの殿堂</a></li>

    <li class="group">履歴</li>
    <?php /* <li><a href="subject.php?spmode=recent&amp;sb_view=shinchaku" target="_self">最近読んだスレの新着</a></li> */ ?>
    <li><a href="subject.php?spmode=recent" target="_self">最近読んだスレ</a></li>
<?php if ($_conf['res_hist_rec_num']) { ?>
    <li><a href="subject.php?spmode=res_hist" target="_self">書き込み履歴</a></li>
<?php if ($_conf['res_write_rec']) { ?>
    <li><a href="read_res_hist.php" target="_self">書き込み履歴の内容</a></li>
<?php } } ?>

    <li class="group">expack</li>
<?php if ($_conf['expack.rss.enabled']) { ?>
    <li><a href="#rss">RSS</a></li>
<?php } ?>
<?php if ($_conf['expack.ic2.enabled'] == 2 || $_conf['expack.ic2.enabled'] == 3) { ?>
    <li><a href="iv2.php?reset_filter=1" target="_self">画像キャッシュ一覧</a></li>
<?php } ?>
    <li><a href="#tgrep">スレッド検索</a></li>

    <li class="group">管理</li>
    <li><a href="editpref.php" target="_self">設定管理</a></li>
    <li><a href="setting.php" target="_self">ログイン管理</a></li>
    <li><a href="#login_info">ログイン情報</a></li>
</ul>

<?php
// }}}
// {{{ エラー

if (P2Util::hasInfoHtml()) { 
    echo '<div id="info_msg" class="panel" title="エラー">';
    P2Util::printInfoHtml();
    echo '</div>';
}

// }}}
// {{{ サブメニュー

if ($_conf['expack.misc.multi_favs']) {
    // {{{ お気にスレ

    $favlist = FavSetManager::getFavSetTitles('m_favlist_set');
    if (!$favlist) {
        $favlist = array();
    }
    $fav_elems = '';
    $fav_new_elems = '';
    $fav_elem_prefix = '';

    for ($no = 0; $no <= $_conf['expack.misc.favset_num']; $no++) {
        if (isset($favlist[$no]) && strlen($favlist[$no]) > 0) {
            $name = $favlist[$no];
        } else {
            $favlist[$no] = $name = ($no ? "お気にスレ{$no}" : 'お気にスレ');
        }
        $fav_url = "subject.php?spmode=fav&amp;m_favlist_set={$no}";
        $fav_elems .= "<li><a href=\"{$fav_url}\" target=\"_self\">{$name}</a></li>";
        //$fav_new_elems .= "<li><a href=\"{$fav_url}&amp;sb_view=shinchaku\" target=\"_self\">{$name}</a></li>";
    }

    echo '<ul id="fav" title="お気にスレ">';
    //echo '<li class="group">新着</li>';
    //echo $fav_new_elems;
    //echo '<li class="group">全て</li>';
    echo $fav_elems;
    echo "</ul>\n";

    // }}}
    // {{{ お気に板

    $favita = FavSetManager::getFavSetTitles('m_favita_set');
    if (!$favita) {
        $favita = array();
    }

    echo '<ul id="favita" title="お気に板">';

    for ($no = 0; $no <= $_conf['expack.misc.favset_num']; $no++) {
        if (isset($favita[$no]) && strlen($favita[$no]) > 0) {
            $name = $favita[$no];
        } else {
            $favita[$no] = $name = ($no ? "お気に板{$no}" : 'お気に板');
        }
        echo "<li><a href=\"#favita{$no}\">{$name}</a></li>";
    }

    echo "</ul>\n";

    $orig_favita_brd = $_conf['favita_brd'];

    foreach ($favita as $no => $name) {
        $_conf['favita_brd'] = $_conf['pref_dir'] . DIRECTORY_SEPARATOR
            . ($no ? "p2_favita{$no}.brd" : 'p2_favita.brd');
        menu_iphone_show_favorite_boards($name, $no);
    }

    $_conf['favita_brd'] = $orig_favita_brd;

    // }}}
    // {{{ RSS

    if ($_conf['expack.rss.enabled']) { 
        $rss = FavSetManager::getFavSetTitles('m_rss_set');
        if (!$rss) {
            $rss = array();
        }

        echo '<ul id="rss" title="RSS">';

        for ($no = 0; $no <= $_conf['expack.misc.favset_num']; $no++) {
            if (isset($rss[$no]) && strlen($rss[$no]) > 0) {
                $name = $rss[$no];
            } else {
                $rss[$no] = $name = ($no ? "RSS{$no}" : 'RSS');
            }
            echo "<li><a href=\"#rss{$no}\">{$name}</a></li>";
        }

        echo "</ul>\n";

        $orig_rss_setting_path = $_conf['expack.rss.setting_path'];

        foreach ($rss as $no => $name) {
            $_conf['expack.rss.setting_path'] = $_conf['pref_dir'] . DIRECTORY_SEPARATOR
                    . ($no ? "p2_rss{$no}.txt" : 'p2_rss.txt');
            menu_iphone_show_feed_list($name, $no);
        }

        $_conf['expack.rss.setting_pat'] = $orig_rss_setting_path;
    }

    // }}}
} else {
    menu_iphone_show_favorite_boards('お気に板');

    if ($_conf['expack.rss.enabled']) { 
        menu_iphone_show_feed_list('RSS');
    }
}

// }}}
// {{{ ログイン情報
?>

<div id="login_info" class="panel" title="ログイン情報">
<h2>認証ユーザ</h2>
<p><strong><?php echo $_login->user_u; ?></strong> - <?php echo date('Y/m/d (D) G:i:s'); ?></p>
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

<?php
// }}}
// {{{ スレッド検索
?>

<ul id="tgrep" title="スレッド検索">
    <li><a href="#tgrep_info">スレッド検索について</a></li>
    <li class="group">クイックサーチ</li>
    <?php require_once P2EX_LIB_DIR . '/tgrep/menu_quick.inc.php'; ?>
    <li class="group">検索履歴</li>
    <?php require_once P2EX_LIB_DIR . '/tgrep/menu_recent.inc.php'; ?>
</ul>

<?php
// }}}
// {{{ スレッド検索について
?>

<div id="tgrep_info" class="panel" title="tGrepについて">
<ul>
    <li>rep2 機能拡張パックのスレッド検索は tGrep (<a href="http://page2.xrea.jp/tgrep/" target="_blank">http://page2.xrea.jp/tgrep/</a>) を利用しています。</li>
    <li>iPhoneではメニュー右上の「ｽﾚ」ボタンをタップして現れるダイアログから検索します。</li>
    <li>キーワードはスペース区切りで3つまで指定でき、すべてを含むものが抽出されます。</li>
    <li>2つ目以降のキーワードで頭に - (半角マイナス) をつけると、それを含まないものが抽出されます。</li>
    <li>&quot; または &#39; で囲まれた部分は一つのキーワードとして扱われます。</li>
    <li>キーワードの全角半角、大文字小文字は無視されます。</li>
    <li>データベースの更新は3時間に1回で、レス数などは更新時点での値です。</li>
</ul>
</div>

<?php
// }}}
// {{{ 板検索ダイアログ
?>

<form id="boardSearch" class="dialog"
  method="post" action="menu_i.php"
  accept-charset="<?php echo $_conf['accept_charset']; ?>">
<fieldset>
    <h1>板検索</h1>
    <a class="button leftButton" type="cancel">取消</a>
    <a class="button blueButton" type="submit">検索</a>
    <label>word:</label>
    <input type="text" name="word" autocorrect="off" autocapitalize="off" />
</fieldset>
</form>

<?php
// }}}
// {{{ スレッド検索ダイアログ
?>

<form id="threadSearch" class="dialog"
  method="post" action="tgrepc.php"
  accept-charset="<?php echo $_conf['accept_charset']; ?>">
<fieldset>
    <h1>スレッド検索</h1>
    <a class="button leftButton" type="cancel">取消</a>
    <a class="button blueButton" type="submit">検索</a>
    <label>word:</label>
    <input type="text" name="iq" autocorrect="off" autocapitalize="off" />
</fieldset>
</form>

<?php
// }}}
?>

</body>
</html>
<?php
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
