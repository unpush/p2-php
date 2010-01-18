<?php
/**
 * rep2 - お気に板の処理
 */

// {{{ setFavIta()

/**
 * お気に板をセットする (後方互換用)
 *
 * @param   void
 * @return  bool
 */
function setFavIta()
{
    return setFavItaByRequest();
}

// }}}
// {{{ setFavItaByRequest()

/**
 * リクエストパラメータからお気に板をセットする
 *
 * @param   void
 * @return  bool
 */
function setFavItaByRequest()
{
    global $_conf, $_info_msg_ht;

    $setfavita = null;
    $host = null;
    $bbs = null;
    $itaj = null;
    $list = null;

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (isset($_GET['setfavita'])) {
            $setfavita = $_GET['setfavita'];
        }
        if (isset($_GET['host'])) {
            $host = $_GET['host'];
        }
        if (isset($_GET['bbs'])) {
            $bbs = $_GET['bbs'];
        }
        if (isset($_GET['itaj_en'])) {
            $itaj = UrlSafeBase64::decode($_GET['itaj_en']);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['setfavita'])) {
            $setfavita = $_POST['setfavita'];
        }
        if (isset($_POST['itaj'])) {
            $itaj = $_POST['itaj'];
        }
        if (isset($_POST['url'])) {
            if (preg_match("/http:\/\/(.+)\/([^\/]+)\/([^\/]+\.html?)?/", $_POST['url'], $matches)) {
                $host = $matches[1];
                $host = preg_replace('{/test/read\.cgi$}', '', $host);
                $bbs = $matches[2];
            } else {
                $url_ht = htmlspecialchars($_POST['url'], ENT_QUOTES);
                $_info_msg_ht .= "<p>p2 info: 「{$url_ht}」は板のURLとして無効です。</p>";
            }
        } elseif (!empty($_POST['submit_setfavita']) && $_POST['list']) {
            $list = $_POST['list'];
        }
    }

    if ($host && $bbs) {
        return setFavItaByHostBbs($host, $bbs, $setfavita, $itaj);
    } elseif ($list) {
        return setFavItaByList($list);
    } else {
        $_info_msg_ht .= "<p>p2 info: 板の指定が変です</p>";
        return false;
    }
}

// }}}
// {{{ setFavItaByHostBbs()

/**
 * host,bbsからお気に板をセットする
 *
 * @param   string      $host
 * @param   string      $bbs
 * @param   int|string  $setfavita  0(解除), 1(追加), top, up, down, bottom
 * @param   string      $itaj
 * @param   int|null    $setnum
 * @return  bool
 */
function setFavItaByHostBbs($host, $bbs, $setfavita, $itaj = null, $setnum = null)
{
    global $_conf;

    // p2_favita.brd 読み込み
    $favita_brd = setFavItaGetBrdPath($setnum);
    $lines = FileCtl::file_read_lines($favita_brd, FILE_IGNORE_NEW_LINES);

    //================================================
    // 処理
    //================================================
    $neolines = array();
    $before_line_num = 0;

    // 最初に重複要素を消去
    if (!empty($lines)) {
        $i = -1;
        foreach ($lines as $l) {
            $i++;

            // {{{ 旧データ（ver0.6.0以下）移行措置
            if ($l[0] != "\t") {
                $l = "\t".$l;
            }
            // }}}

            $lar = explode("\t", $l);

            if ($lar[1] == $host and $lar[2] == $bbs) { // 重複回避
                $before_line_num = $i;
                continue;
            } elseif (!$lar[1] || !$lar[2]) { // 不正データ（host, bbsなし）もアウト
                continue;
            } else {
                $neolines[] = $l;
            }
        }
    }

    // 記録データ設定
    if ($setfavita && $host && $bbs) {
        if (!is_string($itaj) || strlen($itaj) == 0) {
            $itaj = $bbs;
        }
        $newdata = "\t{$host}\t{$bbs}\t{$itaj}";
        require_once P2_LIB_DIR . '/getsetposlines.inc.php';
        $rec_lines = getSetPosLines($neolines, $newdata, $before_line_num, $setfavita);

    // 解除
    } else {
        $rec_lines = $neolines;
    }

    $cont = '';
    if (!empty($rec_lines)) {
        foreach ($rec_lines as $l) {
            $cont .= $l . "\n";
        }
    }

    // 書き込む
    if (FileCtl::file_write_contents($favita_brd, $cont) === false) {
        p2die('cannot write file.');
    }

    return true;
}

// }}}
// {{{ setFavItaByList()

/**
 * カンマ区切り+@区切りのリストからお気に板をセットする
 *
 * @param   string      $list
 * @param   int|null    $setnum
 * @return  bool
 */
function setFavItaByList($list, $setnum = null)
{
    global $_conf, $_info_msg_ht;

    // 記録データ設定
    $rec_lines = array();
    foreach (explode(',', $list) as $aList) {
        list($host, $bbs, $itaj_en) = explode('@', $aList);
        $rec_lines[] = "\t{$host}\t{$bbs}\t" . UrlSafeBase64::decode($itaj_en);
    }

    $_info_msg_ht .= <<<EOJS
<script type="text/javascript">
//<![CDATA[
if (parent.menu) {
    parent.menu.location.href = '{$_conf['menu_php']}?nr=1';
}
//]]>
</script>\n
EOJS;

    $cont = '';
    if (!empty($rec_lines)) {
        foreach ($rec_lines as $l) {
            $cont .= $l . "\n";
        }
    }

    // 書き込む
    if (FileCtl::file_write_contents(setFavItaGetBrdPath($setnum), $cont) === false) {
        p2die('cannot write file.');
    }

    return true;
}

// }}}
// {{{ setFavItaGetBrdPath

/**
 * p2_favita.brdのパスを取得する
 *
 * @param   int|null    $setnum
 * @return  string
 */
function setFavItaGetBrdPath($setnum = null)
{
    global $_conf;

    if (!is_null($setnum) && $_conf['expack.misc.multi_favs']) {
        if (0 < $setnum && $setnum <= $_conf['expack.misc.favset_num']) {
            $favita_brd = $_conf['pref_dir'] . sprintf('/p2_favita%d.brd', $setnum);
        } else {
            $favita_brd = $_conf['orig_favita_brd'];
        }
    } else {
        $favita_brd = $_conf['favita_brd'];
    }

    // p2_favita.brd ファイルがなければ生成
    FileCtl::make_datafile($favita_brd, $_conf['favita_perm']);

    return $favita_brd;
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
