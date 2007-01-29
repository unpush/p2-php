<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

require_once P2_LIB_DIR . '/filectl.class.php';
require_once P2_LIB_DIR . '/brdmenu.class.php';

/**
 * p2 - 板リストコントロールクラス for menu.php
 * スタティックメソッドで利用している
 */
class BrdCtl
{
    /**
     * boardを全て読み込む
     *
     * @static
     * @access  public
     * @return  array
     */
    function readBrdMenus()
    {
        $brd_menus_dir = BrdCtl::readBrdLocal();
        $brd_menus_online = BrdCtl::readBrdOnline();
        $brd_menus = array_merge($brd_menus_dir, $brd_menus_online);
        
        return $brd_menus;
    }
    
    /**
     * ローカルのboardディレクトリを走査して読み込む
     *
     * @static
     * @access  private
     * @return  array
     */
    function readBrdLocal()
    {
        $brd_menus = array();
        $brd_dir = './board';
        
        if (is_dir($brd_dir) and $cdir = dir($brd_dir)) {
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
                    P2Util::pushInfoHtml("<p>p2 error: 板リスト {$entry} が読み込めませんでした。</p>\n");
                }
            }
            $cdir->close();
        }
        
        return $brd_menus;
    }
    
    /**
     * オンライン板リストを読み込む
     *
     * @static
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
        $isNewDL = null;
        $read_html_flag = null;
        
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
                $aBrdMenu =& new BrdMenu();         // クラス BrdMenu のオブジェクトを生成
                $aBrdMenu->setBrdMatch($cache_brd); // パターンマッチ形式を登録
                $aBrdMenu->setBrdList($data);       // カテゴリーと板をセット
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
    
    /**
     * 板検索（スレタイ検索）のwordクエリーがあればパースする
     * $GLOBALS['word'], $GLOBALS['words_fm'], $GLOBALS['word_fm'] をセットする
     *
     * @static
     * @access  public
     * @return  void
     */
    function parseWord()
    {
        $GLOBALS['word'] = null;
        $GLOBALS['words_fm'] = null;
        $GLOBALS['word_fm'] = null;
        
        if (isset($_GET['word'])) {
            $word = $_GET['word'];
        } elseif (isset($_POST['word'])) {
            $word = $_POST['word'];
        }

        if (!isset($word) || strlen($word) == 0) {
            return;
        }
        
        /*
        // 特別に除外する条件
        // 何でもマッチしてしまう正規表現
        if (preg_match('/^\.+$/', $word)) {
            return;
        }
        */
        
        require_once P2_LIB_DIR . '/strctl.class.php';
        // and検索でよろしく（正規表現ではない）
        $word_fm = StrCtl::wordForMatch($word, 'and');
        if (P2_MBREGEX_AVAILABLE == 1) {
            $GLOBALS['words_fm'] = mb_split('\s+', $word_fm);
            $GLOBALS['word_fm'] = mb_ereg_replace('\s+', '|', $word_fm);
        } else {
            $GLOBALS['words_fm'] = preg_split('/\s+/', $word_fm);
            $GLOBALS['word_fm'] = preg_replace('/\s+/', '|', $word_fm);
        }
    
        $GLOBALS['word'] = $word;
    }
    
    /**
     * 携帯用 板検索（スレタイ検索）のフォームHTMLを取得する
     *
     * @static
     * @access  public
     * @return  void
     */
    function getMenuKSearchFormHtml($action = null)
    {
        global $_conf;
        
        is_null($action) and $action = $_SERVER['SCRIPT_NAME'];
        
        $threti_ht = ''; // スレタイ検索は未対応
    
        $word_hs = isset($GLOBALS['word']) ? htmlspecialchars($GLOBALS['word'], ENT_QUOTES) : null;
    
        return <<<EOFORM
<form method="GET" action="{$action}" accept-charset="{$_conf['accept_charset']}">
    <input type="hidden" name="detect_hint" value="◎◇">
    {$_conf['k_input_ht']}
    <input type="hidden" name="nr" value="1">
    <input type="text" id="word" name="word" value="{$word_hs}" size="12">
    {$threti_ht}
    <input type="submit" name="submit" value="板検索">
</form>\n
EOFORM;
    }
}
