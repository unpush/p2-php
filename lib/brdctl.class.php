<?php
require_once P2_LIBRARY_DIR . '/filectl.class.php';
require_once P2_LIBRARY_DIR . '/brdmenu.class.php';

/**
 * p2 - 板リストコントロールクラス for menu.php
 * スタティックメソッドで利用している
 */
class BrdCtl
{
    /**
     * boardを全て読み込む
     *
     * @access  public
     * @return  array
     */
    function read_brds()
    {
        $brd_menus_dir = BrdCtl::readBrdLocal();
        $brd_menus_online = BrdCtl::readBrdOnline();
        $brd_menus = array_merge($brd_menus_dir, $brd_menus_online);

        return $brd_menus;
    }

    /**
     * ローカルのboardディレクトリを走査して読み込む
     *
     * @access  private
     * @return  array
     */
    function readBrdLocal()
    {
        global $_info_msg_ht;

        $brd_menus = array();
        $brd_dir = './board';

        if (is_dir($brd_dir) and $cdir = dir($brd_dir)) {
            // ディレクトリ走査
            while ($entry = $cdir->read()) {
                if (preg_match('/^\./', $entry)) {
                    continue;
                }
                $filepath = $brd_dir . '/' . $entry;
                if ($data = file($filepath)) {
                    $aBrdMenu =& new BrdMenu();    // クラス BrdMenu のオブジェクトを生成
                    $aBrdMenu->setBrdMatch($filepath);    // パターンマッチ形式を登録
                    $aBrdMenu->setBrdList($data);    // カテゴリーと板をセット
                    $brd_menus[] =& $aBrdMenu;

                } else {
                    $_info_msg_ht .= "<p>p2 error: 板リスト {$entry} が読み込めませんでした。</p>\n";
                }
            }
            $cdir->close();
        }

        return $brd_menus;
    }

    /**
     * オンライン板リストを読み込む
     *
     * @access  private
     * @return  array
     */
    function readBrdOnline()
    {
        global $_conf, $_info_msg_ht;

        if (empty($_conf['brdfile_online'])) {
            return array();
        }

        $brd_menus = array();

        $cachefile = P2Util::cacheFileForDL($_conf['brdfile_online']);
        $noDL = false;

        // キャッシュがある場合
        if (file_exists($cachefile . '.p2.brd')) {

            // norefreshならDLしない
            if (!empty($_GET['nr'])) {
                $noDL = true;

            // キャッシュの更新が指定時間以内ならDLしない
            } elseif (filemtime($cachefile . '.p2.brd') > time() - 60 * 60 * $_conf['menu_dl_interval']) {
                $noDL = true;
            }
        }

        // DLしない
        if ($noDL) {
            ;
        // DLする
        } else {
            //echo "DL!<br>";//
            $brdfile_online_res = P2Util::fileDownload($_conf['brdfile_online'], $cachefile, true, true);
            if ($brdfile_online_res->is_success() && $brdfile_online_res->code != '304') {
                $isNewDL = true;
            }
        }

        // html形式なら
        if (preg_match('/html?$/', $_conf['brdfile_online'])) {

            // 更新されていたら新規キャッシュ作成
            if ($isNewDL) {
                //echo "NEW!<br>"; //
                $aBrdMenu =& new BrdMenu(); // クラス BrdMenu のオブジェクトを生成
                $aBrdMenu->makeBrdFile($cachefile); // .p2.brdファイルを生成
                $brd_menus[] = $aBrdMenu;
                $read_html_flag = true;
                unset($aBrdMenu);
            }

            if (file_exists($cachefile . '.p2.brd')) {
                $cache_brd = $cachefile . '.p2.brd';
            } else {
                $cache_brd = $cachefile;
            }

        } else {
            $cache_brd = $cachefile;
        }

        if (!$read_html_flag) {
            if ($data = file($cache_brd)) {
                $aBrdMenu =& new BrdMenu(); // クラス BrdMenu のオブジェクトを生成
                $aBrdMenu->setBrdMatch($cache_brd); // パターンマッチ形式を登録
                $aBrdMenu->setBrdList($data); // カテゴリーと板をセット
                if ($aBrdMenu->num) {
                    $brd_menus[] =& $aBrdMenu;
                } else {
                    $_info_msg_ht .=  "<p>p2 エラー: {$cache_brd} から板メニューを生成することはできませんでした。</p>\n";
                }
                unset($data, $aBrdMenu);
            } else {
                $_info_msg_ht .=  "<p>p2 エラー: {$cachefile} は読み込めませんでした。</p>\n";
            }
        }

        return $brd_menus;
    }

}

/*
 * Local variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
