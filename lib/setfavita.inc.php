<?php
/**
 * rep2 - お気に板の処理
 */

require_once P2_LIB_DIR . '/FileCtl.php';

// {{{ setFavIta()

/**
 * お気に板をセットする
 *
 * $set は、0(解除), 1(追加), top, up, down, bottom
 */
function setFavIta()
{
    global $_conf, $_info_msg_ht;

    if (isset($_GET['setfavita'])) {
        $setfavita = $_GET['setfavita'];
    } elseif (isset($_POST['setfavita'])) {
        $setfavita = $_POST['setfavita'];
    }

    $host = isset($_GET['host']) ? $_GET['host'] : NULL;
    $bbs = isset($_GET['bbs']) ? $_GET['bbs'] : NULL;

    if ($_POST['url']) {
        if (preg_match("/http:\/\/(.+)\/([^\/]+)\/([^\/]+\.html?)?/", $_POST['url'], $matches)) {
            $host = $matches[1];
            $host = preg_replace('{/test/read\.cgi$}', '', $host);
            $bbs = $matches[2];
        } else {
            $_info_msg_ht .= "<p>p2 info: 「{$_POST['url']}」は板のURLとして無効です。</p>";
        }
    }

    $list = $_POST['list'];

    if (!$host && !$bbs and (!(!empty($_POST['submit_setfavita']) && $list))) {
        $_info_msg_ht .= "<p>p2 info: 板の指定が変です</p>";
        return false;
    }

    if (isset($_POST['itaj'])) {
        $itaj = $_POST['itaj'];
    }
    if (!isset($itaj) && isset($_GET['itaj_en'])) {
        $itaj = base64_decode($_GET['itaj_en']);
    }
    if (empty($itaj)) { $itaj = $bbs; }

    //================================================
    // 読み込み
    //================================================
    // p2_favita.brd ファイルがなければ生成
    FileCtl::make_datafile($_conf['favita_brd'], $_conf['favita_perm']);

    // p2_favita.brd 読み込み;
    $lines = FileCtl::file_read_lines($_conf['favita_brd'], FILE_IGNORE_NEW_LINES);

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
    if (!empty($_POST['submit_setfavita']) && $list) {
        $rec_lines = array();
        foreach (explode(',', $list) as $aList) {
            list($host, $bbs, $itaj_en) = explode('@', $aList);
            $rec_lines[] = "\t{$host}\t{$bbs}\t" . base64_decode($itaj_en);
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

    } elseif ($setfavita and $host && $bbs && $itaj) {
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
    if (FileCtl::file_write_contents($_conf['favita_brd'], $cont) === false) {
        p2die('cannot write file.');
    }

    return true;
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
