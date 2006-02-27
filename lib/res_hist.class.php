<?php
// p2 - 書き込み履歴のクラス

/**
 * レス記事のクラス
 */
class ResArticle{
    var $name;
    var $mail;
    var $daytime;
    var $msg;
    var $ttitle;
    var $host;
    var $bbs;
    var $itaj;
    var $key;
    var $order; //記事番号
}

/**
 * 書き込みログのクラス
 */
class ResHist{
    var $articles; // クラス ResArticle のオブジェクトを格納する配列
    var $num; // 格納された BrdMenuCate オブジェクトの数

    var $resrange; // array( 'start' => i, 'to' => i, 'nofirst' => bool )

    /**
     * コンストラクタ
     */
    function ResHist()
    {
        $this->articles = array();
        $this->num = 0;
    }

    /**
     * レスを追加する
     *
     * @return void
     */
    function addRes(&$aResArticle)
    {
        $this->articles[] =& $aResArticle;
        $this->num++;
    }

    /**
     * レス記事を表示する PC用
     *
     * @return void
     */
    function showArticles()
    {
        global $_conf, $STYLE;

        $sid_q = (defined('SID')) ? '&amp;' . strip_tags(SID) : '';

        // Pager 準備
        require_once 'Pager/Pager.php';
        $perPage = 100;
        $params = array(
            'mode'       => 'Jumping',
            'itemData'   => $this->articles,
            'perPage'    => $perPage,
            'delta'      => 10,
            'clearIfVoid' => true,
            'prevImg' => "前の{$perPage}件",
            'nextImg' => "次の{$perPage}件",
            //'separator' => '|',
            //'expanded' => true,
            'spacesBeforeSeparator' => 2,
            'spacesAfterSeparator' => 0,
        );

        $pager = & Pager::factory($params);
        $links = $pager->getLinks();
        $data  = $pager->getPageData();

        if ($pager->links) {
            echo "<div>{$pager->links}</div>";
        }

        echo '<dl>';

        foreach ($data as $a_res) {
            $hd['daytime'] = htmlspecialchars($a_res->daytime, ENT_QUOTES);
            $hd['ttitle'] = htmlspecialchars(html_entity_decode($a_res->ttitle, ENT_COMPAT, 'Shift_JIS'), ENT_QUOTES);
            $hd['itaj'] = htmlspecialchars($a_res->itaj, ENT_QUOTES);

            $href_ht = "";
            if ($a_res->key) {
                $href_ht = $_conf['read_php']."?host=".$a_res->host."&amp;bbs=".$a_res->bbs."&amp;key=".$a_res->key."{$_conf['k_at_a']}#footer";
            }
            $info_view_ht = <<<EOP
        <a href="info.php?host={$a_res->host}&amp;bbs={$a_res->bbs}&amp;key={$a_res->key}{$_conf['k_at_a']}" target="_self" onClick="return OpenSubWin('info.php?host={$a_res->host}&amp;bbs={$a_res->bbs}&amp;key={$a_res->key}&amp;popup=1{$sid_q}',{$STYLE['info_pop_size']},0,0)">情報</a>
EOP;

            $res_ht = "<dt><input name=\"checked_hists[]\" type=\"checkbox\" value=\"{$a_res->order},,,,{$hd['daytime']}\"> ";
            $res_ht .= "{$a_res->order} ："; // 番号
            $res_ht .= '<span class="name"><b>' . htmlspecialchars($a_res->name, ENT_QUOTES) . '</b></span> ：'; // 名前
            // メール
            if ($a_res->mail) {
                $res_ht .= htmlspecialchars($a_res->mail, ENT_QUOTES) . ' ：';
            }
            $res_ht .= "{$hd['daytime']}</dt>\n"; // 日付とID
            // 板名
            $res_ht .= "<dd><a href=\"{$_conf['subject_php']}?host={$a_res->host}&amp;bbs={$a_res->bbs}{$_conf['k_at_a']}\" target=\"subject\">{$hd['itaj']}</a> / ";
            if ($href_ht) {
                $res_ht .= "<a href=\"{$href_ht}\"><b>{$hd['ttitle']}</b></a> - {$info_view_ht}\n";
            } elseif ($hd['ttitle']) {
                $res_ht .= "<b>{$hd['ttitle']}</b>\n";
            }
            $res_ht .= "<br><br>";
            $res_ht .= "{$a_res->msg}<br><br></dd>\n"; // 内容

            echo $res_ht;
            flush();
        }

        echo '</dl>';

        if ($pager->links) {
            echo "<div>{$pager->links}</div>";
        }
    }

    /**
     * 携帯用ナビを表示する
     * 表示範囲もセットされる
     */
    function showNaviK($position)
    {
        global $_conf;

        // 表示数制限
        $list_disp_all_num = $this->num;
        $list_disp_range = $_conf['k_rnum_range'];

        if ($_GET['from']) {
            $list_disp_from = $_GET['from'];
            if ($_GET['end']) {
                $list_disp_range = $_GET['end'] - $list_disp_from + 1;
                if ($list_disp_range < 1) {
                    $list_disp_range = 1;
                }
            }
        } else {
            $list_disp_from = 1;
            /*
            $list_disp_from = $this->num - $list_disp_range + 1;
            if ($list_disp_from < 1) {
                $list_disp_from = 1;
            }
            */
        }
        $disp_navi = P2Util::getListNaviRange($list_disp_from, $list_disp_range, $list_disp_all_num);

        $this->resrange['start'] = $disp_navi['from'];
        $this->resrange['to'] = $disp_navi['end'];
        $this->resrange['nofirst'] = false;

        if ($disp_navi['from'] > 1) {
            if ($position == "footer") {
                $mae_ht = <<<EOP
        <a {$_conf['accesskey']}="{$_conf['k_accesskey']['prev']}" href="read_res_hist.php?from={$disp_navi['mae_from']}{$_conf['k_at_a']}">{$_conf['k_accesskey']['prev']}.前</a>
EOP;
            } else {
                $mae_ht = <<<EOP
        <a href="read_res_hist.php?from={$disp_navi['mae_from']}{$_conf['k_at_a']}">前</a>
EOP;
            }
        }
        if ($disp_navi['end'] < $list_disp_all_num) {
            if ($position == "footer") {
                $tugi_ht = <<<EOP
        <a {$_conf['accesskey']}="{$_conf['k_accesskey']['next']}" href="read_res_hist.php?from={$disp_navi['tugi_from']}{$_conf['k_at_a']}">{$_conf['k_accesskey']['next']}.次</a>
EOP;
            } else {
                $tugi_ht = <<<EOP
        <a href="read_res_hist.php?from={$disp_navi['tugi_from']}{$_conf['k_at_a']}">次</a>
EOP;
            }
        }

        if (!$disp_navi['all_once']) {
            $list_navi_ht = <<<EOP
        {$disp_navi['range_st']}{$mae_ht} {$tugi_ht}
EOP;
        }

        echo $list_navi_ht;

    }

    /**
     * レス記事を表示するメソッド 携帯用
     */
    function showArticlesK()
    {
        global $_conf;

        foreach ($this->articles as $a_res) {
            $hd['daytime'] = htmlspecialchars($a_res->daytime, ENT_QUOTES);
            $hd['ttitle'] = htmlspecialchars(html_entity_decode($a_res->ttitle, ENT_COMPAT, 'Shift_JIS'), ENT_QUOTES);
            $hd['itaj'] = htmlspecialchars($a_res->itaj, ENT_QUOTES);

            if ($a_res->order < $this->resrange['start'] or $a_res->order > $this->resrange['to']) {
                continue;
            }

            $href_ht = "";
            if ($a_res->key) {
                $href_ht = $_conf['read_php']."?host=".$a_res->host."&amp;bbs=".$a_res->bbs."&amp;key=".$a_res->key."{$_conf['k_at_a']}#footer";
            }

        // 大きさ制限
        if (!$_GET['k_continue']) {
            $msg = $a_res->msg;
            if (strlen($msg) > $_conf['ktai_res_size']) {
                $msg = substr($msg, 0, $_conf['ktai_ryaku_size']);

                // 末尾に<br>があれば取り除く
                if (substr($msg, -1)==">") {
                    $msg = substr($msg, 0, strlen($msg)-1);
                }
                if (substr($msg, -1)=="r") {
                    $msg = substr($msg, 0, strlen($msg)-1);
                }
                if (substr($msg, -1)=="b") {
                    $msg = substr($msg, 0, strlen($msg)-1);
                }
                if (substr($msg, -1)=="<") {
                    $msg = substr($msg, 0, strlen($msg)-1);
                }

                $msg = $msg."  ";
                $a_res->msg = $msg."<a href=\"read_res_hist?from={$a_res->order}&amp;end={$a_res->order}&amp;k_continue=1{$_conf['k_at_a']}\">略</a>";
            }
        }

            $res_ht = "[$a_res->order]"; // 番号
            $res_ht .= htmlspecialchars($a_res->name, ENT_QUOTES) . ':'; // 名前
            // メール
            if ($a_res->mail) {
                $res_ht .= htmlspecialchars($a_res->mail, ENT_QUOTES) . ':';
            }
            $res_ht .= "{$hd['daytime']}<br>\n"; // 日付とID
            $res_ht .= "<a href=\"{$_conf['subject_php']}?host={$a_res->host}&amp;bbs={$a_res->bbs}{$_conf['k_at_a']}\">{$hd['itaj']}</a> / ";
            if ($href_ht) {
                $res_ht .= "<a href=\"{$href_ht}\">{$hd['ttitle']}</a>\n";
            } elseif ($hd['ttitle']) {
                $res_ht .= "{$hd['ttitle']}\n";
            }
            $res_ht .= '<br>';
            $res_ht .= "{$a_res->msg}<hr>\n"; // 内容

            echo $res_ht;

        }

        return true;
    }
}

?>
