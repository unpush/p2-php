<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    p2 - スレッドを表示する クラス PC用
*/

require_once (P2_LIBRARY_DIR . '/showthreadpc.class.php');

// {{{ class ShowThreadTree

class ShowThreadTree extends ShowThreadPc {

    // {{{ properties

    /**
     * ツリー構造を保存する配列
     *
     * @access  public
     * @var array
     */
    var $tree;

    /**
     * ツリー作成を補助する配列
     * ツリーから特定レス配下の部分を取り出すのにも使える
     *
     * @access  public
     * @var array
     */
    var $node;

    /**
     * ノードのマーカー
     * dt要素の最初に表示する
     *
     * @access  private
     * @var array
     */
    var $marker;

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ (PHP4 style)
     *
     * @param   object  $aThread    スレッド オブジェクト
     */
    function ShowThreadTree(&$aThread)
    {
        $this->__construct($aThread);
    }

    /**
     * コンストラクタ (PHP5 style)
     *
     * @param   object  $aThread    スレッド オブジェクト
     */
    function __construct(&$aThread)
    {
        parent::__construct($aThread);
        $this->mkTree();
        $this->marker = array();
        //$this->marker['root'] = '&clubs;';
        //$this->marker['root'] = '√';
        $this->marker['root'] = '';
        $this->marker['branch'] = '┣';
        $this->marker['suekko'] = '┗';
    }

    // }}}
    // {{{ mkTree()

    /**
     * datのツリー構造を解析する
     *
     * @access  private
     * @return  void
     */
    function mkTree()
    {
        $this->tree = array();
        $this->node = array();

        $this->node[0] = &$this->tree;

        for ($i = 1; $i <= $this->pDatCount; $i++) {
            if (($parent = $this->pDatLines[$i]['parent']) == 0) {
                $this->tree[$i] = array();
                $this->node[$i] = &$this->tree[$i];
            } else {
                $this->node[$parent][$i] = array();
                $this->node[$i] = &$this->node[$parent][$i];
            }
        }
    }

    // }}}
    // {{{ datToTree()

    /**
     * datをツリー表示する
     *
     * @access  public
     * @return  void
     */
    function datToTree()
    {
        if (!$this->thread->resrange) {
            echo '<b>p2 error: {$this->resrange} is false at datToHtml() in lib/threadread.class.php</b>';
        }

        $status_title = htmlspecialchars($this->thread->itaj).' / '.$this->thread->ttitle_hd;
        $status_title = str_replace("'", "\'", $status_title);
        $status_title = str_replace('"', "\'\'", $status_title);
        echo "<dl onmouseover=\"window.top.status='{$status_title}';\">\n";

        $this->transNode();

        echo '</dl>'."\n";
    }

    // }}}
    // {{{ transNode()

    /**
     * datの全部または一部を再帰的に表示する
     *
     * @access  public
     * @param   mixed   $nodeID レス番号（整数）もしくは任意のレス配下のノード（多次元の配列）
     * @param   array   $ancestors  親レス、親レスの親レス、...のレス番号が入っている配列
     * @param   integer $loops  バグによる無限ループを監視するための再帰回数
     * @return void
     */
    function transNode($nodeID = NULL, $ancestors = array(), $loops = 0)
    {
        global $_conf, $_exconf, $res_filter, $word_fm, $STYLE;
        static $hits = 0; // フィルタ条件にマッチした順番に付けられる。

        if (is_array($nodeID)) {
            $parent_node = $nodeID;
        } elseif (is_null($nodeID)) {
            $parent_node = $this->tree;
        } elseif (is_int($nodeID) && isset($this->node[$nodeID])) {
            $parent_node = $this->node[$nodeID];
        } else {
            trigger_error('ShowThreadTree::transNode() - Invalid node given.', E_USER_WARNING);
            return;
        }

        if ($loops > 1000) {
            trigger_error('ShowThreadTree::transNode() - 無限ループの可能性があります。', E_USER_WARNING);
            return;
        }

        foreach ($parent_node as $resnum => $node) {
            // {{{ transNode - 判定

            /*if (!isset($this->pDatLines[$resnum])) {
                return;
            }*/

            $resID = $resnum . 'of' . $this->thread->key;
            $pedigree = $ancestors;
            $pedigree[] = $resID;

            //$children = count($node);
            //$children = count($node) + array_sum(array_map('count', $node));
            $children = $this->countRecursive($node);

            if (!empty($_conf['filtering'])) {
                if ($this->filterMatch($resnum)) {
                    $filtermarking = TRUE;
                    $showcontent = TRUE;
                    $hits++;
                } else {
                    $filtermarking = FALSE;
                    $showcontent = FALSE;
                }
            } elseif ($resnum == 1 && !$this->thread->resrange['nofirst']) {
                $filtermarking = FALSE;
                $showcontent = TRUE;
            } else {
                $filtermarking = FALSE;
                $showcontent = FALSE;
            }

            // }}}
            // {{{ transNode - ヘッダ表示

            // 変数展開
            $name = $this->transName($this->pDatLines[$resnum]['name']);
            $mail = $this->pDatLines[$resnum]['mail'];
            $date_id = $this->transDateId($resnum);
            $resBodyID = 'rb'.$resID;

            // 開閉ハンドラ
            $nodeEventHandler = " onclick=\"return showHideNode({$this->asyncObjName},'content{$resID}',1,event);\"";

            // SPMハンドラ
            if ($_exconf['spm']['*'] == 2) {
                $spmEventHandler = " onclick=\"showSPM({$this->spmObjName},{$resnum},'{$resBodyID}',event);return false;\"";
            } elseif ($_exconf['spm']['*']) {
                $spmEventHandler = " onmouseover=\"showSPM({$this->spmObjName},{$resnum},'{$resBodyID}',event)\" onmouseout=\"hideResPopUp('{$this->spmObjName}_spm')\"";
            } else {
                $spmEventHandler = '';
            }

            // ツリー記号
            $parent_num = $this->pDatLines[$resnum]['parent'];
            if ($parent_num) {
                if ($resnum == end(array_keys($parent_node))) {
                    $marker = $this->marker['suekko'];
                } else {
                    $marker = $this->marker['branch'];
                }
            } else {
                $marker = $this->marker['root'];
            }

            // 名前
            $head = '<span class="name"><b>'.$name.'</b></span> : ';
            // メール
            if ($mail) {
                if (strstr($mail, 'sage') && $STYLE['read_mail_sage_color']) {
                    $head .= '<span class="sage">'.$mail.'</span>';
                } elseif ($STYLE['read_mail_color']) {
                    $head .= '<span class="mail">'.$mail.'</span>';
                } else {
                    $head .= $mail;
                }
                $head .= ' : ';
            }
            // 日付・ID
            $head .= $date_id;

            // マーキング
            if ($filtermarking && $res_filter['field'] != 'msg') {
                $head = StrCtl::filterMarking($word_fm, $head);
            }

            // 表示開始
            echo '<dt'
                . (($filtermarking) ? " id=\"hitNo{$hits}\"" : '')
                . (($parent_num) ? '' : ' style="margin-top:0.5em;"')
                . '>';

            if ($marker !== '') {
                echo '<span class="node_marker"'.$nodeEventHandler.'>'.$marker.'</span>'."\n";
            }

            // レス番号と開閉ボタン
            echo '<a href="javascript:void(0);" class="resnum"'.$spmEventHandler.'>'.$resnum.'</a> ';
            echo '<span id="opener'.$resID.'" class="node_opener"'.$nodeEventHandler.'>';
            echo ($showcontent) ? '-' : '+';
            if ($children) {
                echo '['.$children.']';
            }
            echo '</span> : ';

            echo $head;

            echo '</dt>'."\n";

            // }}}
            // {{{ transNode - メッセージ・子レス表示

            echo '<dd id="content'.$resID.'" style="' . (($showcontent) ? '' : 'display:none;') . 'margin-bottom:1em;">'."\n";

            // 内容を表示
            if ($showcontent) {
                echo '<div id="'.$resBodyID.'">';
                $body =  $this->transMsg($this->pDatLines[$resnum]['msg'], $resnum);
                if ($filtermarking && ($res_filter['field'] == 'msg' || $res_filter['field'] == 'hole')) {
                    $body = StrCtl::filterMarking($word_fm, $body);
                }
                echo $body;
                echo '</div>'."\n";

                // フィルタリングあり
                if ($filtermarking) {
                    // 前後にヒットしたレスに移動する
                    echo '<div style="margin-top:1em;">';
                    if ($hits > 1) {
                        echo '[<a href="#hitNo'.($hits - 1).'">▲Prev</a>] / ';
                    }
                    echo '[<a href="#hitNo'.($hits + 1).'">▼Next</a>]';
                    echo '</div>'."\n";
                    // 親レスのヘッダを再帰的に表示させるJavaScript
                    if ($ancestors) {
                        $this->printShowAncestorsJs($ancestors);
                    }
                }

            // 内容を表示しない
            } else {
                // 本文を読み込み、表示させるボタン
                echo '<div id="rbr'.$resID.'">';
                echo '<input type="button" onclick="loadResBody('.$this->asyncObjName.','.$resnum.');" value="表示">';
                echo '</div>'."\n";
            }

            // 子レスがあれば再帰
            if ($children) {
                echo '<dl id="children'.$resID.'" style="margin-top:1em;">'."\n";
                $this->transNode($node, $pedigree, $loops + 1);
                echo '</dl>'."\n";
            }

            echo '</dd>'."\n";

            // }}}
            if ($loops == 0) {
                flush();
            }
        }

    }

    // }}}
    // {{{ transMsg()

    /**
     * ShowThreadPc::transMsg()を実行し、その結果をレスポップアップが
     * 強制的に非同期モードになるように加工する
     *
     * @access  public
     * @see lib/showthreadpc.class.php
     * @param   string  $msg    メッセージ内容
     * @param   integer $mynum  レス番号
     * @return  string
     */
    function transMsg($msg, $mynum)
    {
        $msg = parent::transMsg($msg, $mynum);
        if (!$GLOBALS['_exconf']['etc']['async_respop']) {
            $msg = $this->respop_to_async($msg);
        }
        return $msg;
    }

    // }}}
    // {{{ countRecursive()

    /**
     * 子レスの数を再帰的にカウントする
     *
     * transNode()で利用
     *
     * @access  private
     * @param   mixed   $node   任意のレス配下のノード
     * @return  integer
     */
    function countRecursive($node, $c = 0)
    {
        if (is_array($node)) {
            $c += count($node);
            foreach ($node as $n) {
                $c = $this->countRecursive($n, $c);
            }
        }
        return $c;
    }

    // }}}
    // {{{ printShowAncestorsJs()

    /**
     * 親レスのヘッダを再帰的に表示させるJavaScriptを出力する
     *
     * transNode()で利用
     *
     * @access  private
     * @param   array   $ancestors  親レス、親レスの親レス、...のレス番号が入っている配列
     * @return  void
     */
    function printShowAncestorsJs($ancestors)
    {
        $ancestors_js = "['" . implode("','", $ancestors) . "']";
        echo <<<EOJS
<script type="text/javascript">
showAncestors({$this->asyncObjName}, {$ancestors_js});
</script>\n
EOJS;
    }

    // }}}

}

// }}}

?>
