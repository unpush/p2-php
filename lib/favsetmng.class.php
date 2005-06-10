<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// お気にセット系ユーティリティクラス

// {{{ class FavSetManager

class FavSetManager
{

    // {{{ switchFavSet()

    /**
     * お気にスレ、お気に板、RSSのカレントセットを切り替える
     */
    function switchFavSet()
    {
        global $_conf;
        static $done = NULL;

        if ($done !== NULL) {
            return;
        }

        $sets = array(
            // お気にスレセット
            'm_favlist_set' => array('favlist_file', 'p2_favlist%d.idx'),
            // お気に板セット
            'm_favita_set' => array('favita_path', 'p2_favita%d.brd'),
            // RSSセット
            'm_rss_set' => array('rss_file', 'p2_rss%d.txt'),
        );

        $ar = array();

        foreach ($sets as $key => $value) {
            if (isset($_REQUEST[$key]) && 0 <= $_REQUEST[$key] && $_REQUEST[$key] <= $_conf['favset_num']) {
                $_SESSION[$key] = (int)$_REQUEST[$key];
            }
            $ar[] = $key . '=' . ((isset($_SESSION[$key])) ? $_SESSION[$key] : 0);
            if (!empty($_SESSION[$key])) {
                list($cnf, $fmt) = $value;
                $_conf[$cnf] = $_conf['pref_dir'] . '/' . sprintf($fmt, $_SESSION[$key]);
            }
        }

        $index_q = implode('&amp;', $ar);
        $_conf['k_to_index_ht'] = "<a {$_conf['accesskey']}=\"0\" href=\"index.php?{$index_q}\">0.TOP</a>";

    }

    // }}}
    // {{{ getFavSetTitles()

    /**
     * お気にスレ、お気に板、RSSのセットリスト（タイトル一覧）を読み込む
     */
    function getFavSetTitles($set_name = NULL)
    {
        global $_conf;

        if (!file_exists($_conf['favset_file'])) {
            return FALSE;
        }

        $favset_titles = @unserialize(file_get_contents($_conf['favset_file']));

        if ($set_name === NULL) {
            return $favset_titles;
        }

        if (is_array($favset_titles) && isset($favset_titles[$set_name]) && is_array($favset_titles[$set_name])) {
            return $favset_titles[$set_name];
        }

        return FALSE;
    }

    // }}}
    // {{{ getFavSetPageTitleHt()

    /**
     * セットリストからページタイトルを取得する
     */
    function getFavSetPageTitleHt($set_name, $default_title)
    {
        $i = (isset($_SESSION[$set_name])) ? (int)$_SESSION[$set_name] : 0;
        $favlist_titles = FavSetManager::getFavSetTitles($set_name);

        if (!$favlist_titles || !isset($favlist_titles[$i]) || strlen($favlist_titles[$i]) == 0) {
            if ($i == 0) {
                return htmlspecialchars($default_title);
            }
            return htmlspecialchars($default_title) . $i;
        }
        return $favlist_titles[$i];
    }

    // }}}
    // {{{ makeFavSetSwitchForm()

    /**
     * お気にスレ、お気に板、RSSのセットリストを切り替えるフォームを生成する
     */
    function makeFavSetSwitchForm($set_name, $set_title,
                                  $script = NULL, $target = NULL, $inline = FALSE,
                                  $hidden_values = array()
                                  )
    {
        // 変数初期化
        if (!$script) {
            $script = $_SERVER['PHP_SELF'];
        }
        if (!$target) {
            $target = '_self';
        }
        $style = ($inline) ? ' style="display:inline;"' : '';

        // フォーム作成
        $form_ht = <<<EOFORM
<form method="get" action="{$script}" target="{$target}"{$style}>\n\t
EOFORM;
        if (is_array($hidden_values)) {
            foreach ($hidden_values as $key => $value) {
                $value = htmlspecialchars($value);
                $form_ht .= "<input type=\"hidden\" name=\"{$key}\" value=\"{$value}\">\n\t";
            }
        }
        $form_ht .= FavSetManager::makeFavSetSwitchElem($set_name, $set_title, TRUE);
        $form_ht .= <<<EOFORM
    <input type="submit" value="セットを切替">
</form>\n
EOFORM;

        return $form_ht;
    }

    // }}}
    // {{{ makeFavSetSwitchElem()

    /**
     * お気にスレ、お気に板、RSSのセットリストを切り替えるフォームを生成する
     */
    function makeFavSetSwitchElem($set_name, $set_title, $set_selected = FALSE, $onchange = NULL)
    {
        global $_conf;

        // 変数初期化
        $i = (isset($_SESSION[$set_name])) ? (int)$_SESSION[$set_name] : 0;
        if ($onchange) {
            $onchange_ht = " onchange=\"{$onchange}\"";
        } else {
            $onchange_ht = '';
        }

        // ユーザ設定タイトルを読み込む
        if (!($titles = FavSetManager::getFavSetTitles($set_name))) {
            $titles = array();
        }

        // SELECT要素作成
        $select_ht  = "<select name=\"{$set_name}\"{$onchange_ht}>";
        if (!$set_selected) {
            $select_ht .= "<option value=\"{$i}\" selected>[{$set_title}]</option>";
        }
        for ($j = 0; $j <= $_conf['favset_num']; $j++) {
            if (!isset($titles[$j]) || strlen($titles[$j]) == 0) {
                $titles[$j] = ($j == 0) ? $set_title : $set_title . $j;
            }
            $selected = ($set_selected && $i == $j) ? ' selected' : '';
            $select_ht .= "<option value=\"{$j}\"{$selected}>{$titles[$j]}</option>";
        }
        $select_ht .= "</select>\n";

        return $select_ht;
    }

    // }}}

}

// }}}

?>
