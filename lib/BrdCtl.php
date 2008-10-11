<?php

require_once P2_LIB_DIR . '/FileCtl.php';
require_once P2_LIB_DIR . '/BrdMenu.php';

// {{{ BrdCtl

/**
 * rep2 - BrdCtl -- 板リストコントロールクラス for menu.php
 *
 * @static
 */
class BrdCtl
{
    // {{{ read_brds()

    /**
     * boardを全て読み込む
     */
    static public function read_brds()
    {
        $brd_menus_dir = BrdCtl::read_brd_dir();
        $brd_menus_online = BrdCtl::read_brd_online();
        $brd_menus = array_merge($brd_menus_dir, $brd_menus_online);
        return $brd_menus;
    }

    // }}}
    // {{{ read_brd_dir()

    /**
     * boardディレクトリを走査して読み込む
     */
    static public function read_brd_dir()
    {
        global $_info_msg_ht;

        $brd_menus = array();
        $brd_dir = './board';

        if ($cdir = @dir($brd_dir)) {
            // ディレクトリ走査
            while ($entry = $cdir->read()) {
                if ($entry[0] == '.') {
                    continue;
                }
                $filepath = $brd_dir.'/'.$entry;
                if ($data = FileCtl::file_read_lines($filepath)) {
                    $aBrdMenu = new BrdMenu();    // クラス BrdMenu のオブジェクトを生成
                    $aBrdMenu->setBrdMatch($filepath);    // パターンマッチ形式を登録
                    $aBrdMenu->setBrdList($data);    // カテゴリーと板をセット
                    $brd_menus[] = $aBrdMenu;

                } else {
                    $_info_msg_ht .= "<p>p2 error: 板リスト {$entry} が読み込めませんでした。</p>\n";
                }
            }
            $cdir->close();
        }

        return $brd_menus;
    }

    // }}}
    // {{{ read_brd_online()

    /**
    * オンライン板リストを読込む
    */
    static public function read_brd_online()
    {
        global $_conf, $_info_msg_ht;

        $brd_menus = array();
        $isNewDL = false;

        if ($_conf['brdfile_online']) {
            $cachefile = P2Util::cacheFileForDL($_conf['brdfile_online']);
            $noDL = false;
            $read_html_flag = false;

            // キャッシュがある場合
            if (file_exists($cachefile.'.p2.brd')) {
                // norefreshならDLしない
                if (!empty($_GET['nr'])) {
                    $noDL = true;
                // キャッシュの更新が指定時間以内ならDLしない
                } elseif (@filemtime($cachefile.'.p2.brd') > time() - 60 * 60 * $_conf['menu_dl_interval']) {
                    $noDL = true;
                }
            }

            // DLしない
            if ($noDL) {
                ;
            // DLする
            } else {
                //echo "DL!<br>";//
                $brdfile_online_res = P2Util::fileDownload($_conf['brdfile_online'], $cachefile);
                if ($brdfile_online_res->isSuccess() && $brdfile_online_res->code != 304) {
                    $isNewDL = true;
                }
            }

            // html形式なら
            if (preg_match('/html?$/', $_conf['brdfile_online'])) {

                // 更新されていたら新規キャッシュ作成
                if ($isNewDL) {
                    // 検索結果がキャッシュされるのを回避
                    if (isset($GLOBALS['word']) && strlen($GLOBALS['word']) > 0) {
                        $_tmp = array($GLOBALS['word'], $GLOBALS['word_fm'], $GLOBALS['words_fm']);
                        $GLOBALS['word'] = null;
                        $GLOBALS['word_fm'] = null;
                        $GLOBALS['words_fm'] = null;
                    } else {
                        $_tmp = null;
                    }

                    //echo "NEW!<br>"; //
                    $aBrdMenu = new BrdMenu(); // クラス BrdMenu のオブジェクトを生成
                    $aBrdMenu->makeBrdFile($cachefile); // .p2.brdファイルを生成
                    $brd_menus[] = $aBrdMenu;
                    unset($aBrdMenu);

                    if ($_tmp) {
                        list($GLOBALS['word'], $GLOBALS['word_fm'], $GLOBALS['words_fm']) = $_tmp;
                        $brd_menus = array();
                    } else {
                        $read_html_flag = true;
                    }
                }

                if (file_exists($cachefile.'.p2.brd')) {
                    $cache_brd = $cachefile.'.p2.brd';
                } else {
                    $cache_brd = $cachefile;
                }

            } else {
                $cache_brd = $cachefile;
            }

            if (!$read_html_flag) {
                if ($data = FileCtl::file_read_lines($cache_brd)) {
                    $aBrdMenu = new BrdMenu(); // クラス BrdMenu のオブジェクトを生成
                    $aBrdMenu->setBrdMatch($cache_brd); // パターンマッチ形式を登録
                    $aBrdMenu->setBrdList($data); // カテゴリーと板をセット
                    if ($aBrdMenu->num) {
                        $brd_menus[] = $aBrdMenu;
                    } else {
                        $_info_msg_ht .=  "<p>p2 エラー: {$cache_brd} から板メニューを生成することはできませんでした。</p>\n";
                    }
                    unset($data, $aBrdMenu);
                } else {
                    $_info_msg_ht .=  "<p>p2 エラー: {$cachefile} は読み込めませんでした。</p>\n";
                }
            }
        }

        return $brd_menus;
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
