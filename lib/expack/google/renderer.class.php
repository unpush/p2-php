<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=4 fdm=marker: */
/* mi: charset=Shift_JIS */

require_once 'Pager.php';

class Google_Renderer
{
    // {{{ properties

    /**
     * 検索結果ブロックの開始タグ
     *
     * @var string
     * @access private
     */
    var $opener = '<table cellspacing="0" width="100%">';

    /**
     * 検索結果・ヘッダ
     *
     * @var string
     * @access private
     */
    var $header = '<tr class="tableheader">
    <td class="t%s">種類</td>
    <td class="t%s">タイトル</td>
    <td class="t%s">範囲</td>
    <td class="t%s">板</td>
</tr>';

    /**
     * 検索結果・各アイテム
     *
     * @var string
     * @access private
     */
    var $body = '<tr>
    <td class="t%s">%s</td>
    <td class="t%s">%s</td>
    <td class="tn%s">%s</td>
    <td class="t%s">%s</td>
</tr>';

    /**
     * 検索結果・エラー
     *
     * @var string
     * @access private
     */
    var $error = '<tr><td colspan="4" align="center">%s</td></tr>';

    /**
     * 検索結果・フッタ
     *
     * @var string
     * @access private
     */
    var $footer = '<tr class="tableheader">
    <td class="t%s" colspan="4" align="center">%d-%d / %d hits.</td>
</tr>';

    /**
     * 検索結果ブロックの終了タグ
     *
     * @var string
     * @access private
     */
    var $closer = '</table>';

    // }}}
    // {{{ getRowClass()

    /**
     * 奇数行か偶数行かの識別子を返す
     */
    function getRowClass()
    {
        static $i = 0;
        $i++;
        return ($i % 2 == 1) ? '' : '2';
    }

    // }}}
    // {{{ printSearchResult()

    /**
     * 検索結果を出力する
     *
     * @return void
     * @access public
     */
    function printSearchResult(&$result, $word, $perPage, $start, $totalItems)
    {
        echo $this->opener;
        $this->printSearchResultHeader($this->getRowClass());
        if (is_array($result) && count($result) > 0) {
            foreach ($result as $id => $val) {
                $this->printSearchResultBody($id, $val, $this->getRowClass());
            }
        } elseif (is_string($result) && strlen($result) > 0) {
            printf($this->error, $result);
        }
        $this->printSearchResultFooter($perPage, $start, $totalItems, $this->getRowClass());
        echo $this->closer;
    }

    // }}}
    // {{{ printSearchResultHeader()

    /**
     * 検索結果のヘッダを出力する
     *
     * @return void
     * @access public
     */
    function printSearchResultHeader($rc)
    {
        printf($this->header, $rc, $rc, $rc, $rc);
    }

    // }}}
    // {{{ printSearchResultBody()

    /**
     * 検索結果の本体を出力する
     *
     * @return void
     * @access public
     */
    function printSearchResultBody($id, $val, $rc)
    {
        $eh = "onmouseover=\"gShowPopUp('s%s',event)\" onmouseout=\"gHidePopUp('s%s')\"";
        $title = "<a class=\"thre_title\" href=\"%s\" {$eh} target=\"%s\" >%s</a>";

        $type_col  = $val['type'];
        $title_col = sprintf($title, $val['url'], $id, $id, $val['target'], $val['title']);
        $range_col = ($val['ls']  !== '') ? $val['ls']  : '&nbsp;';
        $ita_col   = ($val['ita'] !== '') ? $val['ita'] : '&nbsp;';

        printf($this->body, $rc, $type_col, $rc, $title_col, $rc, $range_col, $rc, $ita_col);
    }

    // }}}
    // {{{ printSearchResultFooter()

    /**
     * 検索結果のフッタを出力する
     *
     * @return void
     * @access public
     */
    function printSearchResultFooter($perPage, $start, $totalItems, $rc)
    {
        $from = ($totalItems > 0) ? ($start + 1) : 0;
        $to   = min($start + $perPage, $totalItems);

        printf($this->footer, $rc, $from, $to, $totalItems);
    }

    // }}}
    // {{{ printPopup()

    /**
     * ポップアップ用隠し要素を出力する
     *
     * @return void
     * @access public
     */
    function printPopup(&$popups)
    {
        if (!is_array($popups) || count($popups) == 0) {
            return;
        }

        $eh = "onmouseover=\"gShowPopUp('s%s',event)\" onmouseout=\"gHidePopUp('s%s')\"";
        $popup = "<div id=\"s%s\" class=\"respopup\" {$eh}>%s</div>\n";

        foreach ($popups as $id => $content) {
            printf($popup, $id, $id, $id, $content);
        }
    }

    // }}}
    // {{{ printPager()

    /**
     * ページ移動用リンクを出力する
     *
     * @return void
     * @access public
     */
    function printPager($perPage, $totalItems)
    {
        if (FALSE !== ($pager = &$this->makePager($perPage, $totalItems))) {
            echo '<table id="sbtoolbar2" class="toolbar" cellspacing="0"><tr><td align="center">';
            echo $pager->links;
            echo '</td></tr></table>';
        }
    }

    // }}}
    // {{{ makePager()

    /**
     * 検索結果内でのページ移動用にPEAR::Pagerのインスタンスを作成する
     *
     * @return object
     * @access public
     */
    function &makePager($perPage, $totalItems)
    {
        if ($totalItems == 0 || $totalItems <= $perPage) {
            $retval = FALSE;
            return $retval;
        }

        $pagerOptions = array(
            'mode'       => 'Sliding',
            'totalItems' => $totalItems,
            'perPage'    => $perPage,
            'delta'      => 5, // ヒットしたページ前後のリンクを作るページ数
            'urlVar'     => 'p', // ページIDを特定するGET/POSTの変数名、デフォルトは"PageID"
            'spacesBeforeSeparator' => 1,
            'spacesAfterSeparator'  => 1,
        );

        $pager = &Pager::factory($pagerOptions);

        return $pager;
    }

    // }}}
    // {{{ _rawurlencode_cb()

    /**
     * array_walk_recursive()のコールバックメソッドとして使用
     *
     * @return void
     * @access public
     */
    function _rawurlencode_cb(&$value, $key)
    {
        $value = rawurlencode($value);
    }

    // }}}
}
