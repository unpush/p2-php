<?php
/**
 * rep2 - iPhone/iPod Touch用メニューのためのライブラリ
 */

// {{{ menu_iphone_unicode_urldecode()

/**
 * フォームから %uHHHH 形式で送られてきた文字列をデコードする
 * %UHHHHHHHH としてBMP外の文字も来る可能性がある?
 *
 * %HH は既にデコードされているものとして扱うため、予期しない結果になることも。
 * 厳密には $_SERVER['QUERY_STRING'] (GET) なり php://input (POST) なりを読んで
 * 生のデータを解析する必要がある。
 *
 * @param string $str
 */
function menu_iphone_unicode_urldecode($str)
{
    return preg_replace_callback('/%u([0-9A-F]{4})/', '_menu_iphone_unicode_urldecode', $str);
}

/**
 * menu_iphone_unicode_urldecode() から呼ばれるコールバック関数
 *
 * @param array $m
 * @return string
 */
function _menu_iphone_unicode_urldecode($m)
{
    $code = hexdec($m[1]);

    if (/* Out of Unicode */
        //$code > 0x10FFFF ||
        /* Out of BMP */
        $code > 0xFFFF ||
        /* Surrogate */
        ($code > 0xD7FF && $code < 0xE000) ||
        /* Noncharacter */
        ($code > 0xFDCF && $code < 0xFDF0) || ($code & 0xFFFE) == 0xFFFE ||
        /* Overflow */
        $code < 0 ||
        /* Null byte */
        $code == 0)
    {
        return '';
    }

    return mb_convert_encoding(pack('n', $code), 'CP932', 'UCS-2');
}

// }}}
// {{{ menu_iphone_ajax()

/**
 * XMLHttpRequest用のラッパー
 *
 * @param callback $func
 * @param ...
 * @return mixed
 */
function menu_iphone_ajax($func)
{
    ob_start();

    $args = func_get_args();
    if (count($args) > 1) {
        array_shift($args);
        $return = call_user_func_array($func, $args);
    } else {
        $return = call_user_func($func);
    }

    $content = mb_convert_encoding(ob_get_clean(), 'UTF-8', 'CP932');

    if (!headers_sent()) {
        //header('Content-Type: application/xhtml+xml; charset=UTF-8');
        header('Content-Type: application/xml; charset=UTF-8');
        //header('Content-Type: text/plain; charset=UTF-8');
        //header('Content-Length: '. strlen($content));
    }

    echo $content;

    return $return;
}

// }}}
// {{{ menu_iphone_show_board_menu()

/**
 * 板リストをカテゴリごとに表示する
 *
 * @param int $cateid
 * @return void
 */
function menu_iphone_show_board_menu($cateid = 0)
{
    global $_conf;

    require_once P2_LIB_DIR . '/BrdCtl.php';

    $brd_menus = BrdCtl::read_brds();

    if (!$brd_menus) {
        echo "<div id=\"cate{$cateid}\" class=\"panel\">板リストは空です。</div>\n";
        return;
    }

    // {{{ カテゴリ一覧

    if (!$cateid) {
        echo '<ul id="cate0" title="板リスト">';
        $i = 0;
        $j = 0;
        foreach ($brd_menus as $a_brd_menu) {
            foreach ($a_brd_menu->categories as $category) {
                $i++;
                echo "<li><a href=\"menu_i.php?cateid={$i}\">{$category->name}</a></li>";
            }
            if ($j++ > 0) {
                echo '<li class="group">&nbsp;</li>';
            }
        }
        echo "</ul>\n";
        return;
    }

    // }}}
    // {{{ カテゴリ

    $i = 0;
    foreach ($brd_menus as $a_brd_menu) {
        foreach ($a_brd_menu->categories as $category) {
            if (++$i == $cateid) {
                echo "<ul id=\"cate{$cateid}\" title=\"{$category->name}\">";
                foreach ($category->menuitas as $mita) {
                    echo "<li><a href=\"{$_conf['subject_php']}?host={$mita->host}&amp;bbs={$mita->bbs}",
                            "&amp;itaj_en={$mita->itaj_en}\" target=\"_self\">{$mita->itaj_ht}</a></li>";
                }
                echo "</ul>\n";
                return;
            }
        }
    }

    // }}}

    echo "<div id=\"cate{$cateid}\" class=\"panel\">カテゴリが見つかりませんでした。</div>\n";
}

// }}}
// {{{ menu_iphone_show_matched_boards()

/**
 * キーワードにマッチした板リストを表示する
 *
 * @param string $word
 * @return void
 */
function menu_iphone_show_matched_boards($word)
{
    global $_conf;

    require_once P2_LIB_DIR . '/BrdCtl.php';

    $brd_menus = BrdCtl::read_brds();

    $word_ht = htmlspecialchars($word, ENT_QUOTES);
    $title = $word_ht . ' (板)';

    if (!$brd_menus) {
        echo "<div class=\"panel\" title=\"{$title}\">板リストは空です。</div>\n";
        return;
    }

    if ($GLOBALS['ita_mikke']['num'] == 0) {
        echo "<div class=\"panel\" title=\"{$title}\">",
                "&quot;{$word_ht}&quot; にマッチする板はありませんでした。</div>\n";
        return;
    }

    printf('<ul id="foundbrd%u" title="%s">', crc32($word . microtime()), $title);

    foreach ($brd_menus as $a_brd_menu) {
        foreach ($a_brd_menu->categories as $category) {
            $t = false;
            foreach ($category->menuitas as $mita) {
                if (!$t) {
                    echo "<li class=\"group\">{$category->name}</li>";
                    $t = true;
                }
                echo "<li><a href=\"{$_conf['subject_php']}?host={$mita->host}&amp;bbs={$mita->bbs}",
                        "&amp;itaj_en={$mita->itaj_en}\" target=\"_self\">{$mita->itaj_ht}</a></li>";
                $i++;
            }
        }
    }

    echo "</ul>\n";
}

// }}}
// {{{ menu_iphone_show_favorite_boards()

/**
 * お気に板リストを表示する
 *
 * @param string $title
 * @param int    $no
 * @return void
 */
function menu_iphone_show_favorite_boards($title, $no = null)
{
    global $_conf;

    if ($_conf['expack.misc.multi_favs']) {
        $favset_q = "?m_favita_set={$no}";
        $favset_q_a = "&amp;m_favita_set={$no}";
    } else {
        $favset_q = $favset_q_a = '';
    }

    echo "<ul id=\"favita{$no}\" title=\"{$title}\">";

    if ($_conf['merge_favita']) {
        echo "<li><a href=\"{$_conf['subject_php']}?spmode=merge_favita{$favset_q_a}\" target=\"_self\">{$title} (まとめ)</a></li>";
    }

    if ($lines = FileCtl::file_read_lines($_conf['favita_brd'], FILE_IGNORE_NEW_LINES)) {
        foreach ($lines as $l) {
            if (preg_match("/^\t?(.+)\t(.+)\t(.+)\$/", $l, $matches)) {
                $itaj = rtrim($matches[3]);
                $itaj_view = htmlspecialchars($itaj, ENT_QUOTES);
                $itaj_en = rawurlencode(base64_encode($itaj));
                echo "<li><a href=\"{$_conf['subject_php']}?host={$matches[1]}&amp;bbs={$matches[2]}",
                        "&amp;itaj_en={$itaj_en}\" target=\"_self\">{$itaj_view}</a></li>";
            }
        }
    }

    //echo '<li class="group">&nbsp;</li>';
    echo "<li><a href=\"editfavita.php{$favset_q}\" class=\"align-r\" target=\"_self\">編集</a></li>";

    echo "</ul>\n";
}

// }}}
// {{{ menu_iphone_show_feed_list()

/**
 * フィードリストを表示する
 *
 * @param string $title
 * @param int    $no
 * @return void
 */
function menu_iphone_show_feed_list($title, $no = null)
{
    global $_conf;

    require_once P2EX_LIB_DIR . '/rss/common.inc.php';

    echo "<ul id=\"rss{$no}\" title=\"{$title}\">";

    $errors = array();

    if ($rss_list = FileCtl::file_read_lines($_conf['expack.rss.setting_path'], FILE_IGNORE_NEW_LINES)) {
        foreach ($rss_list as $rss_info) {
            $p = explode("\t", $rss_info);
            if (count($p) > 1) {
                $site = $p[0];
                $xml  = $p[1];
                if (!empty($p[2])) {
                    $atom = 1;
                    $atom_q = '&atom=1';
                } else {
                    $atom = 0;
                    $atom_q = '';
                }
                $localpath = rss_get_save_path($xml);
                if (PEAR::isError($localpath)) {
                    $errors[] = array($site, $localpath->getMessage());
                } else {
                    $mtime   = file_exists($localpath) ? filemtime($localpath) : 0;
                    $site_en = rawurlencode(base64_encode($site));
                    $xml_en = rawurlencode($xml);
                    $rss_q = sprintf('?xml=%s&site_en=%s%s&mt=%d', $xml_en, $site_en, $atom_q, $mtime);
                    $rss_q_ht = htmlspecialchars($rss_q, ENT_QUOTES);
                    echo "<li><a href=\"subject_rss.php{$rss_q_ht}\" target=\"_self\">{$site}</a></li>";
                }
            }
        }
    }

    if (count($errors)) {
        echo '<li class="group">エラー</li>';
        foreach ($errors as $error) {
            echo "<li>{$error[0]} - {$error[1]}</li>";
        }
    }

    //echo '<li class="group">&nbsp;</li>';
    echo '<li><a href="editrss.php';
    if ($_conf['expack.misc.multi_favs']) {
        echo '?m_rss_set=' . $no;
    }
    echo '" class="align-r" target="_self">編集</a></li>';

    echo "</ul>\n";
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
