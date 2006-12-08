<?php
/**
 * rep2expack - お気にセット系ユーティリティクラス
 */

// {{{ class FavSetManager

class FavSetManager
{
    // {{{ getSetDefinition()

    /**
     * セット定義を取得する
     *
     * @return  array
     * @access  public
     * @static
     */
    function getSetDefinition()
    {
        return array(
            // お気にスレセット
            'm_favlist_set' => array('favlist_set_num', 'favlist_file', 'p2_favlist%d.idx'),
            // お気に板セット
            'm_favita_set' => array('favita_set_num', 'favita_path', 'p2_favita%d.brd'),
            // RSSセット
            'm_rss_set' => array('expack.rss.set_num', 'expack.rss.setting_path', 'p2_rss%d.txt'),
        );
    }

    // }}}
    // {{{ loadAllFavSet()

    /**
     * すべてのお気にスレを読み込む
     *
     * @param   bool $force 通常は1回だけしか実行されないようになっているが、
     *                      true のときは強制的に更新される
     * @return  void
     * @access  public
     * @static
     */
    function loadAllFavSet($force = false)
    {
        global $_conf, $__conf;
        static $done = null;

        if (!$force && !is_null($done)) {
            return;
        }

        $_conf['favlists'] = array();
        $favlist_files = array();
        $favlist_files[0] = $__conf['favlist_file'];
        for ($i = 1; $i <= $_conf['favlist_set_num']; $i++) {
            $favlist_files[$i] = $_conf['pref_dir'] . sprintf('/p2_favlist%d.idx', $i);
        }

        foreach ($favlist_files as $i => $favlist_file) {
            $_conf['favlists'][$i] = array();
            if (file_exists($favlist_file)) {
                $favlines = file($favlist_file);
                foreach ($favlines as $line) {
                    $lar = explode('<>', rtrim($line));
                    // bbsのないものは不正データなのでスキップ
                    if (!isset($lar[11])) {
                        continue;
                    }
                    $_conf['favlists'][$i][] = array('key' => $lar[1], 'bbs' => $lar[11]);
                }
            }
        }

        $done = true;
    }

    /**
     * お気にスレ、お気に板、RSSのカレントセットを切り替える
     *
     * @param   bool $force 通常は1回だけしか実行されないようになっているが、
     *                      true のときは強制的に更新される
     * @return  void
     * @access  public
     * @static
     */
    function switchFavSet($force = false)
    {
        global $_conf;
        static $done = null;

        if (!$force && !is_null($done)) {
            return;
        }

        $sets = FavSetManager::getSetDefinition();
        $ar = array();

        foreach ($sets as $key => $value) {
            list($num_conf_key, $path_conf_key, $name_format) = $value;
            if (isset($_REQUEST[$key]) && 0 <= $_REQUEST[$key] && $_REQUEST[$key] <= $_conf[$num_conf_key]) {
                $_SESSION[$key] = (int)$_REQUEST[$key];
            }
            $ar[] = $key . '=' . ((isset($_SESSION[$key])) ? $_SESSION[$key] : 0);
            if (!empty($_SESSION[$key])) {
                $_conf[$path_conf_key] = $_conf['pref_dir'] . '/' . sprintf($name_format, $_SESSION[$key]);
            }
        }

        $k_to_index_q = implode('&', $ar);
        if ($_conf['ktai'] && $_conf['view_forced_by_query']) {
            $k_to_index_q .= '&b=k';
        }
        $k_to_index_q = htmlspecialchars($k_to_index_q, ENT_QUOTES);
        $_conf['k_to_index_ht'] = "<a {$_conf['accesskey']}=\"0\" href=\"index.php?{$k_to_index_q}\">0.TOP</a>";

        $done = true;
    }

    // }}}
    // {{{ getFavSetTitles()

    /**
     * お気にスレ、お気に板、RSSのセットリスト（タイトル一覧）を読み込む
     *
     * @param   string $set_name    セット名 ()
     * @return  array   セット名が指定されているときはそのセットのタイトル一覧、
     *                  指定されていなければ全てのセットのタイトル一覧
     * @access  public
     * @static
     */
    function getFavSetTitles($set_name = null)
    {
        global $_conf;

        if (!file_exists($_conf['expack.misc.favset_file'])) {
            return false;
        }

        $favset_titles = @unserialize(file_get_contents($_conf['expack.misc.favset_file']));

        if ($set_name === null) {
            return $favset_titles;
        }

        if (is_array($favset_titles) && isset($favset_titles[$set_name]) && is_array($favset_titles[$set_name])) {
            return $favset_titles[$set_name];
        }

        return false;
    }

    // }}}
    // {{{ getFavSetPageTitleHt()

    /**
     * セットリストからページタイトルを取得する
     */
    function getFavSetPageTitleHt($set_name, $default_title)
    {
        global $_conf;

        $i = (isset($_SESSION[$set_name])) ? (int)$_SESSION[$set_name] : 0;
        $favlist_titles = FavSetManager::getFavSetTitles($set_name);

        if (!$favlist_titles || !isset($favlist_titles[$i]) || strlen($favlist_titles[$i]) == 0) {
            if ($i == 0) {
                $title = $default_title;
            } else {
                $title = $default_title . $i;
            }
            $title = htmlspecialchars($title, ENT_QUOTES);
        } else {
            $title = $favlist_titles[$i];
        }
        // 全角英数スペースカナを半角に
        if (!empty($_conf['ktai']) && !empty($_conf['k_save_packet'])) {
            $title = mb_convert_kana($title, 'rnsk');
        }
        return $title;
    }

    // }}}
    // {{{ makeFavSetSwitchForm()

    /**
     * お気にスレ、お気に板、RSSのセットリストを切り替えるフォームを生成する
     */
    function makeFavSetSwitchForm($set_name, $set_title,
                                  $script = null, $target = null, $inline = false,
                                  $hidden_values = array()
                                  )
    {
        global $_conf;

        // 変数初期化
        if (!$script) {
            $script = $_SERVER['SCRIPT_NAME'];
        }
        if (!$target) {
            $target = '_self';
        }
        $style = ($inline) ? ' style="display:inline;"' : '';

        // フォーム作成
        $form_ht = "<form method=\"get\" action=\"{$script}\" target=\"{$target}\"{$style}>";
        $form_ht .= $_conf['k_input_ht'];
        if (is_array($hidden_values)) {
            foreach ($hidden_values as $key => $value) {
                $value = htmlspecialchars($value, ENT_QUOTES);
                $form_ht .= "<input type=\"hidden\" name=\"{$key}\" value=\"{$value}\">";
            }
        }
        $form_ht .= FavSetManager::makeFavSetSwitchElem($set_name, $set_title, true);
        $submit_value = ($_conf['ktai']) ? 'ｾｯﾄ切替' : 'セット切替';
        $form_ht .= "<input type=\"submit\" value=\"{$submit_value}\"></form>\n";

        return $form_ht;
    }

    // }}}
    // {{{ makeFavSetSwitchElem()

    /**
     * お気にスレ、お気に板、RSSのセットリストを切り替えるselect要素を生成する
     */
    function makeFavSetSwitchElem($set_name, $set_title, $set_selected = false, $onchange = null)
    {
        global $_conf;

        // 変数初期化
        $sets = FavSetManager::getSetDefinition();
        if (!isset($sets[$set_name])) {
            return '';
        }
        $num_conf_key = $sets[$set_name][0];
        if ($_conf[$num_conf_key] == 0) {
            return '';
        }
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
        for ($j = 0; $j <= $_conf[$num_conf_key]; $j++) {
            if (!isset($titles[$j]) || strlen($titles[$j]) == 0) {
                $titles[$j] = ($j == 0) ? $set_title : $set_title . $j;
            }
            // 全角英数スペースカナを半角に
            if (!empty($_conf['ktai']) && !empty($_conf['k_save_packet'])) {
                $titles[$j] = mb_convert_kana($titles[$j], 'rnsk');
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
