<?php
require_once P2_LIB_DIR . '/ShowThread.php';

/**
 * p2 - スレッドを表示する クラス PC用
 */
class ShowThreadPc extends ShowThread
{
    var $quote_res_nums_checked;    // ポップアップ表示されるチェック済みレス番号を登録した配列
    var $quote_res_nums_done;       // ポップアップ表示される記録済みレス番号を登録した配列

    /**
     * @constructor
     */
    function ShowThreadPc(&$aThread)
    {
        parent::ShowThread($aThread);

        global $_conf;

        $this->url_handlers = array(
            'plugin_link2ch',
            'plugin_linkMachi',
            'plugin_linkJBBS',
            'plugin_link2chKako',
            'plugin_link2chSubject',
            'plugin_linkReadCgi'
        );
        if ($_conf['preview_thumbnail']) {
            $this->url_handlers[] = 'plugin_viewImage';
        }
        $_conf['link_youtube']  and $this->url_handlers[] = 'plugin_linkYouTube';
        $_conf['link_niconico'] and $this->url_handlers[] = 'plugin_linkNicoNico';
        $_conf['link_yourfilehost'] and $this->url_handlers[] = 'plugin_linkYourFileHost';
        $this->url_handlers[] = 'plugin_linkURL';
    }

    /**
     * DatをHTMLに変換して表示する
     *
     * @access  public
     * @return  boolean
     */
    function datToHtml()
    {
        // 表示レス範囲が指定されていなければ
        if (!$this->thread->resrange) {
            echo '<b>p2 error: {$this->resrange} is FALSE at datToHtml()</b>';
            return false;
        }

        $start = $this->thread->resrange['start'];
        $to = $this->thread->resrange['to'];
        $nofirst = $this->thread->resrange['nofirst'];

        $status_title_hs = hs($this->thread->itaj) . ' / ' . hs($this->thread->ttitle_hc);
        $status_title_hs = str_replace("&#039;", "\&#039;", $status_title_hs);
        //$status_title_hs = str_replace(array("\\", "'"), array("\\\\", "\\'"), $status_title_hs);
        echo "<dl onMouseover=\"window.top.status='{$status_title_hs} ';\">";

        // 1を表示（範囲外のケースもあるのでここで）
        if (!$nofirst) {
            echo $this->transRes($this->thread->datlines[0], 1);
        }

        for ($i = $start; $i <= $to; $i++) {
            
            // 表示範囲外ならスキップ
            if ($this->thread->resrange_multi and !$this->thread->inResrangeMulti($i)) {
                continue;
            }
            
            // 1が前段処理で既表示ならスキップ
            if (!$nofirst and $i == 1) {
                continue;
            }
            if (!$this->thread->datlines[$i - 1]) {
                // $this->thread->readnum = $i - 1; 2006/09/23 ここでセットするのは違う気がした
                break;
            }
            echo $this->transRes($this->thread->datlines[$i - 1], $i);
            
            !isset($GLOBALS['_read_new_html']) && ob_flush() && flush();
        }

        echo "</dl>\n";
        
        //$s2e = array($start, $i-1);
        //return $s2e;
        return true;
    }

    /**
     * DatレスをHTMLレスに変換する
     *
     * @access  public
     * @param   string   $ares  datの1ライン
     * @param   integer  $i     レス番号
     * @return  string  HTML
     */
    function transRes($ares, $i)
    {
        global $_conf, $STYLE, $mae_msg;
        
        global $_ngaborns_head_hits;
        
        $tores      = '';
        $rpop       = '';

        $resar      = $this->thread->explodeDatLine($ares);
        $name       = $resar[0];
        $mail       = $resar[1];
        $date_id    = $resar[2];
        $msg        = $resar[3];

        // {{{ フィルタリングカット
        
        if (isset($GLOBALS['word']) && strlen($GLOBALS['word'])) {
            if (strlen($GLOBALS['word_fm']) <= 0) {
                return '';
            // ターゲット設定
            } elseif (!$target = $this->getFilterTarget($i, $name, $mail, $date_id, $msg)) {
                return '';
            // マッチング
            } else {
                $match = $this->filterMatch($target, $i);
                // strlen(true) は1、strlen(false)は0を返す。
                if (false === (bool)strlen($match)) {
                    return '';
                }
            }
        }
        
        // }}}
        // {{{ あぼーんチェック（名前、メール、ID、メッセージ）

        if (false !== $this->checkAborns($name, $mail, $date_id, $msg)) {
            
            // 名前
            $aborned_res_html = '<dt id="r' . $i . '" class="aborned"><span>&nbsp;</span></dt>' . "\n";
            // 内容
            $aborned_res_html .= '<!-- <dd class="aborned">&nbsp;</dd> -->' . "\n";

            return $aborned_res_html;
        }
        
        // }}}
        // {{{ レスをポップアップ表示
        // （$_ngaborns_head_hits がずれないように、NGチェックよりも前に）
        
        if ($_conf['quote_res_view']) {
            $quote_res_nums = $this->checkQuoteResNums($i, $name, $msg);

            foreach ($quote_res_nums as $rnv) {
                if (empty($this->quote_res_nums_done[$rnv]) and $rnv < count($this->thread->datlines)) {
                    $ds = $this->qRes($this->thread->datlines[$rnv - 1], $rnv);
                    $onPopUp_at = " onMouseover=\"showResPopUp('q{$rnv}of{$this->thread->key}',event,true)\"";
                    $rpop .= "<dd id=\"q{$rnv}of{$this->thread->key}\" class=\"respopup\"{$onPopUp_at}><i>" . $ds . "</i></dd>\n";
                    $this->quote_res_nums_done[$rnv] = true;
                }
            }
        }
        
        // }}}
        // {{{ NGチェック（名前、メール、ID、メッセージ）
        
        $isNgName = false;
        $isNgMail = false;
        $isNgId   = false;
        $isNgMsg  = false;
        
        if (false !== $this->ngAbornCheck('ng_name', strip_tags($name))) {
            $isNgName = true;
        }
        if (false !== $this->ngAbornCheck('ng_mail', $mail)) {
            $isNgMail = true;
        }
        if (false !== $this->ngAbornCheck('ng_id', $date_id)) {
            $isNgId = true;
        }
        if (false !== ($a_ng_msg = $this->ngAbornCheck('ng_msg', $msg))) {
            $isNgMsg = true;
        }
        
        // }}}
        
        //=============================================================
        // まとめて出力
        //=============================================================

        $name_ht = $this->transName($name, $i); // 名前HTML変換
        $msg_ht  = $this->transMsg($msg, $i);   // メッセージHTML変換
        //$date_id = $this->transDateId($date_id);

        // BEプロファイルリンク変換
        $date_id = $this->replaceBeId($date_id, $i);

        // HTMLポップアップ
        if ($_conf['iframe_popup']) {
            $date_id = preg_replace_callback(
                "{<a href=\"(http://[-_.!~*()a-zA-Z0-9;/?:@&=+\$,%#]+)\"({$_conf['ext_win_target_at']})>((\?#*)|(Lv\.\d+))</a>}",
                array($this, 'iframePopupCallback'),
                $date_id
            );
        }
        
        $atTitle = ' title="クリックで表示/非表示"';
        
        $a_ng_msg_hs = htmlspecialchars($a_ng_msg, ENT_QUOTES);
        
        // NGメッセージ変換
        if ($isNgMsg) {
            $msg_ht = <<<EOMSG
<s class="ngword" onClick="showHide(this.nextSibling, 'ngword_cont');"{$atTitle}>NG：{$a_ng_msg_hs}</s><div class="ngword_cont">$msg_ht</div>
EOMSG;
        }

        // NGネーム変換
        if ($isNgName) {
            $name_ht = <<<EONAME
<s class="ngword" onClick="showHide('ngn{$_ngaborns_head_hits}', 'ngword_cont');"{$atTitle}>$name_ht</s>
EONAME;
            $msg_ht = <<<EOMSG
<div id="ngn{$_ngaborns_head_hits}" class="ngword_cont">$msg_ht</div>
EOMSG;

        // NGメール変換
        } elseif ($isNgMail) {
            $mail = <<<EOMAIL
<s class="ngword" onClick="showHide('ngn{$_ngaborns_head_hits}', 'ngword_cont');"{$atTitle}>$mail</s>
EOMAIL;
            $msg_ht = <<<EOMSG
<div id="ngn{$_ngaborns_head_hits}" class="ngword_cont">$msg_ht</div>
EOMSG;

        // NGID変換
        } elseif ($isNgId) {
            $date_id = preg_replace('|ID: ?([0-9A-Za-z/.+]{8,11})|', "<s class=\"ngword\" onClick=\"showHide('ngn{$_ngaborns_head_hits}', 'ngword_cont');\"{$atTitle}>NG：\\0</s>", $date_id);
            
            /*
            $date_id = <<<EOID
<s class="ngword" onClick="showHide('ngn{$_ngaborns_head_hits}', 'ngword_cont');">$date_id</s>
EOID;
            */
            
            $msg_ht = <<<EOMSG
<div id="ngn{$_ngaborns_head_hits}" class="ngword_cont">$msg_ht</div>
EOMSG;
        }

        /*
        //「ここから新着」画像を挿入
        if ($i == $this->thread->readnum +1) {
            $tores .= <<<EOP
                <div><img src="img/image.png" alt="新着レス" border="0" vspace="4"></div>
EOP;
        }
        */
        
        // スマートポップアップメニュー
        if ($_conf['enable_spm']) {
            $onPopUp_at = " onmouseover=\"showSPM({$this->thread->spmObjName},{$i},'{$id}',event,this)\" onmouseout=\"hideResPopUp('{$this->thread->spmObjName}_spm')\"";
        } else {
            $onPopUp_at = "";
        }

        if ($this->thread->onthefly) {
            $GLOBALS['newres_to_show_flag'] = true;
            // 番号（オンザフライ時）
            $tores .= "<dt id=\"r{$i}\"><span class=\"ontheflyresorder\">{$i}</span> ：";
            
        } elseif ($i > $this->thread->readnum) {
            $GLOBALS['newres_to_show_flag'] = true;
            // 番号（新着レス時）
            if ($onPopUp_at) {
                //  style=\"cursor:pointer;\"
                $tores .= "<dt id=\"r{$i}\"><a class=\"resnum\"{$onPopUp_at}><font color=\"{$STYLE['read_newres_color']}\" class=\"newres\">{$i}</font></a> ：";
            } else {
                $tores .= "<dt id=\"r{$i}\"><font color=\"{$STYLE['read_newres_color']}\" class=\"newres\">{$i}</font> ：";
            }
            
        } else {
            // 番号
            if ($onPopUp_at) {
                //  style=\"cursor:pointer;\"
                $tores .= "<dt id=\"r{$i}\"><a href=\"#\" class=\"resnum\"{$onPopUp_at}>{$i}</a> ：";
            } else {
                $tores .= "<dt id=\"r{$i}\">{$i} ：";
            }
        }
        
        // 名前
        $tores .= "<span class=\"name\"><b>{$name_ht}</b></span>：";
        
        // メール
        if ($mail) {
            if (strstr($mail, 'sage') && $STYLE['read_mail_sage_color']) {
                $tores .= "<span class=\"sage\">{$mail}</span> ：";
            } elseif ($STYLE['read_mail_color']) {
                $tores .= "<span class=\"mail\">{$mail}</span> ：";
            } else {
                $tores .= $mail." ：";
            }
        }

        // IDフィルタ
        if ($_conf['flex_idpopup'] == 1) {
            if (preg_match('|ID: ?([0-9A-Za-z/.+]{8,11})|', $date_id, $matches)) {
                $id = $matches[1];
                if ($this->thread->idcount[$id] > 1) {
                    $date_id = preg_replace_callback(
                        '|ID: ?([0-9A-Za-z/.+]{8,11})|',
                        array($this, 'idfilter_callback'), $date_id
                    );
                }
            }
        }
        
        $tores .= $date_id; // 日付とID
        $tores .= "</dt>\n";
        $tores .= $rpop; // レスポップアップ用引用
        $tores .= "<dd>{$msg_ht}<br><br></dd>\n"; // 内容
        
        // まとめてフィルタ色分け
        if (isset($GLOBALS['word_fm']) && strlen($GLOBALS['word_fm']) && $GLOBALS['res_filter']['match'] != 'off') {
            $tores = StrCtl::filterMarking($GLOBALS['word_fm'], $tores);
        }

        return $tores;
    }


    /**
     * >>1 ポップアップ表示用の (引用ポップアップ用) HTMLデータ（配列）を返す
     *
     * @access  public
     * @return  array
     */
    function quoteOne()
    {
        global $_conf;

        if (!$_conf['quote_res_view']) {
            return false;
        }

        $ds = '';
        $rpop = '';
        $dummy_msg = "";
        $quote_res_nums = $this->checkQuoteResNums(0, "1", $dummy_msg);
        foreach ($quote_res_nums as $rnv) {
            if (empty($this->quote_res_nums_done[$rnv])) {
                if ($this->thread->ttitle_hs) {
                    $ds = "<b>{$this->thread->ttitle_hs} </b><br><br>";
                }
                $resline = isset($this->thread->datlines[$rnv - 1]) ? $this->thread->datlines[$rnv - 1] : '';
                $ds .= $this->qRes($resline, $rnv);
                $onPopUp_at = " onMouseover=\"showResPopUp('q{$rnv}of{$this->thread->key}',event,true)\"";
                $rpop .= "<div id=\"q{$rnv}of{$this->thread->key}\" class=\"respopup\"{$onPopUp_at}><i>" . $ds . "</i></div>\n";
                $this->quote_res_nums_done[$rnv] = true;
            }
        }
        $res1['q'] = $rpop;

        $m1 = "&gt;&gt;1";
        $res1['body'] = $this->transMsg($m1, 1);
        
        return $res1;
    }

    /**
     * レス引用HTMLを生成取得する
     *
     * @access  private
     * @param   string   $resline
     * @return  string
     */
    function qRes($resline, $i)
    {
        global $_conf;
        global $_ngaborns_head_hits;
        
        $resar      = $this->thread->explodeDatLine($resline);
        $name       = isset($resar[0]) ? $resar[0] : '';
        $mail       = isset($resar[1]) ? $resar[1] : '';
        $date_id    = isset($resar[2]) ? $resar[2] : '';
        $msg        = isset($resar[3]) ? $resar[3] : '';

        // あぼーんチェック
        if (false !== $this->checkAborns($name, $mail, $date_id, $msg)) {
            $name = $date_id = $msg = 'あぼーん';
            $mail = '';
            // "$i ：あぼーん ：あぼーん<br>あぼーん<br>\n"
            
        } else {
        
            $isNgName = false;
            $isNgMail = false;
            $isNgId   = false;
            $isNgMsg  = false;
        
            if (false !== $this->ngAbornCheck('ng_name', strip_tags($name))) {
                $isNgName = true;
            }
            if (false !== $this->ngAbornCheck('ng_mail', $mail)) {
                $isNgMail = true;
            }
            if (false !== $this->ngAbornCheck('ng_id', $date_id)) {
                $isNgId = true;
            }
            if (false !== ($a_ng_msg = $this->ngAbornCheck('ng_msg', $msg))) {
                $isNgMsg = true;
            }
        
            $name = $this->transName($name, $i);
            $msg  = $this->transMsg($msg, $i); // メッセージ変換
            //$date_id = $this->transDateId($date_id);
            
            // BEプロファイルリンク変換
            $date_id = $this->replaceBeId($date_id, $i);

            // HTMLポップアップ
            if ($_conf['iframe_popup']) {
                $date_id = preg_replace_callback(
                    "{<a href=\"(http://[-_.!~*()a-zA-Z0-9;/?:@&=+\$,%#]+)\"({$_conf['ext_win_target_at']})>((\?#*)|(Lv\.\d+))</a>}",
                    array($this, 'iframePopupCallback'), $date_id
                );
            }
            
            $atTitle = ' title="クリックで表示/非表示"';

            $a_ng_msg_hs = htmlspecialchars($a_ng_msg, ENT_QUOTES);
            
            // NGメッセージ変換
            if ($isNgMsg) {
                $msg = <<<EOMSG
<s class="ngword" onClick="showHide(this.nextSibling, 'ngword_cont');"{$atTitle}>NG：{$a_ng_msg_hs}</s><div  class="ngword_cont">$msg</div>
EOMSG;
            }

            // NGネーム変換
            if ($isNgName) {
                $name = <<<EONAME
<s class="ngword" onClick="showHide('ngn{$_ngaborns_head_hits}', 'ngword_cont');"{$atTitle}>$name</s>
EONAME;
                $msg = <<<EOMSG
<div id="ngn{$_ngaborns_head_hits}" class="ngword_cont">$msg</div>
EOMSG;

            // NGメール変換
            } elseif ($isNgMail) {
                $mail = <<<EOMAIL
<s class="ngword" onClick="showHide('ngn{$_ngaborns_head_hits}', 'ngword_cont');"{$atTitle}>$mail</s>
EOMAIL;
                $msg = <<<EOMSG
<div id="ngn{$_ngaborns_head_hits}" class="ngword_cont">$msg</div>
EOMSG;

            // NGID変換
            } elseif ($isNgId) {
                $date_id = preg_replace(
                    '|ID: ?([0-9A-Za-z/.+]{8,11})|',
                    "<s class=\"ngword\" onClick=\"showHide('ngn{$_ngaborns_head_hits}', 'ngword_cont');\"{$atTitle}>NG：\\0</s>",
                    $date_id
                );
                
                /*
                $date_id = <<<EOID
<s class="ngword" onClick="showHide('ngn{$_ngaborns_head_hits}', 'ngword_cont');">$date_id</s>
EOID;
                */
                
                $msg = <<<EOMSG
<div id="ngn{$_ngaborns_head_hits}" class="ngword_cont">$msg</div>
EOMSG;
            }
            
            // スマートポップアップメニュー
            if ($_conf['enable_spm']) {
                $onPopUp_at = " onmouseover=\"showSPM({$this->thread->spmObjName},{$i},'{$id}',event,this)\" onmouseout=\"hideResPopUp('{$this->thread->spmObjName}_spm')\"";
                $i = "<a href=\"javascript:void(0);\" class=\"resnum\"{$onPopUp_at}>{$i}</a>";
            }
        
            // IDフィルタ
            if ($_conf['flex_idpopup'] == 1) {
                if (preg_match('|ID: ?([0-9a-zA-Z/.+]{8,11})|', $date_id, $matches)) {
                    $id = $matches[1];
                    if ($this->thread->idcount[$id] > 1) {
                        $date_id = preg_replace_callback(
                            '|ID: ?([0-9A-Za-z/.+]{8,11})|',
                            array($this, 'idfilter_callback'), $date_id
                        );
                    }
                }
            }
        
        }
        
        // $toresにまとめて出力
        $tores = "$i ："; // 番号
        $tores .= "<b>$name</b> ："; // 名前
        if ($mail) { $tores .= $mail . " ："; } // メール
        $tores .= $date_id; // 日付とID
        $tores .= "<br>";
        $tores .= $msg . "<br>\n"; // 内容

        return $tores;
    }

    /**
     * 名前をHTML用に変換して返す
     *
     * @access  private
     * @return  string  HTML
     */
    function transName($name, $resnum)
    {
        global $_conf;
        
        $nameID = '';
        
        // ID付なら名前は "aki </b>◆...p2/2... <b>" といった感じでくる。（通常は普通に名前のみ）
        
        // ID付なら分解する
        if (preg_match('~(.*)( </b>◆.*)~', $name, $matches)) {
            $name   = $matches[1];
            $nameID = $matches[2];
        }

        // 数字をリンク化
        if ($_conf['quote_res_view']) {
            /*
            $uri = P2Util::buildQueryUri($_conf['read_php'], array(
                'host' => $this->thread->host,
                'bbs'  => $this->thread->bbs,
                'key'  => $this->thread->key,
                'ls'   => '\\1'
            ));
            $atag = P2View::tagA($uri,
                '\\1',
                array(
                    'target' => $_conf['bbs_win_target'],
                    'onMouseover' => "showResPopUp('q\\1of{$this->thread->key}',event)",
                    'onMouseout'  => "hideResPopUp('q\\1of{$this->thread->key}')"
                )
            );
            $name && $name = preg_replace("/([1-9][0-9]*)/", "$atag", $name, 1);
            */
            
            // 数字を引用レスポップアップリンク化
            // </b>～<b> は、ホストやトリップなのでマッチしないようにしたい
            if ($name) {
                $name = preg_replace_callback(
                    $this->getAnchorRegex('/(?:^|%prefix%)%nums%/'),
                    array($this, 'quote_name_callback'), $name
                );
            }
        }
        
        if ($nameID) { $name = $name . $nameID; }

        $name = $name . ' '; // 簡易的に文字化け回避

        return $name;
    }
    
    /**
     * datのレスメッセージをHTML表示用メッセージに変換して返す
     *
     * @access  private
     * @param   string   $msg
     * @param   integer  $resnum  レス番号
     * @return  string   HTML
     */
    function transMsg($msg, $resnum)
    {
        global $_conf;
        
        $this->str_to_link_rest = $this->str_to_link_limit;
        
        // 2ch旧形式のdat
        if ($this->thread->dat_type == '2ch_old') {
            $msg = str_replace('＠｀', ',', $msg);
            $msg = preg_replace('/&amp([^;])/', '&$1', $msg);
        }

        // Safariから投稿されたリンク中チルダの文字化け補正
        //$msg = preg_replace('{(h?t?tp://[\w\.\-]+/)～([\w\.\-%]+/?)}', '$1~$2', $msg);
        
        // DAT中にある>>1のリンクHTMLを取り除く
        $msg = $this->removeResAnchorTagInDat($msg);
        
        // 2chではなされていないエスケープ（ノートンの誤反応対策を含む）
        // 本来は2chのDAT化時点でなされていないとエスケープの整合性が取れない気がする。
        //（URLリンクのマッチで副作用が出てしまう）
        //$msg = str_replace(array('"', "'"), array('&quot;', '&#039;'), $msg);
        
        // 2006/05/06 ノートンの誤反応対策 body onload=window()
        $msg = str_replace('onload=window()', '<i>onload=window</i>()', $msg);
        
        // 引用やURLなどをリンク
        $msg = preg_replace_callback(
            $this->str_to_link_regex, array($this, 'link_callback'), $msg, $this->str_to_link_limit
        );
        
        // 2ch BEアイコン
        if (in_array($_conf['show_be_icon'], array(1, 2))) {
            $msg = preg_replace(
                '{sssp://(img\\.2ch\\.net/ico/[\\w\\d()\\-]+\\.[a-z]+)}',
                '<img src="http://$1" border="0">', $msg
            );
        }
        
        return $msg;
    }

    // {{{ コールバックメソッド

    /**
     * リンク対象文字列の種類を判定して対応した関数/メソッドに渡す
     *
     * @access  private
     * @return  string  HTML
     */
    function link_callback($s)
    {
        global $_conf;

        // preg_replace_callback()では名前付きでキャプチャできない？
        if (!isset($s['link'])) {
            // $s[1] => "<a...>...</a>", $s[2] => "<a..>", $s[3] => "...", $s[4] => "</a>"
            $s['link']  = isset($s[1]) ? $s[1] : null;
            $s['quote'] = isset($s[5]) ? $s[5] : null;
            $s['url']   = isset($s[8]) ? $s[8] : null;
            $s['id']    = isset($s[11]) ? $s[11] : null;
        }

        // マッチしたサブパターンに応じて分岐
        // リンク
        if ($s['link']) {
            if (preg_match('{ href=(["\'])?(.+?)(?(1)\\1)(?=[ >])}i', $s[2], $m)) {
                $url  = $m[2];
                $html = $s[3];
            } else {
                return $s[3];
            }

        // 引用
        } elseif ($s['quote']) {
            return preg_replace_callback(
                $this->getAnchorRegex('/(%prefix%)?(%a_range%)/'),
                array($this, 'quote_res_callback'), $s['quote'], $this->str_to_link_rest
            );

        // http or ftp のURL
        } elseif ($s['url']) {
            $url  = preg_replace('/^t?(tps?)$/', 'ht$1', $s[9]) . '://' . $s[10];
            $html = $s['url'];

        // ID
        } elseif ($s['id'] && $_conf['flex_idpopup']) {
            return $this->idfilter_callback(array($s['id'], $s[12]));

        // その他（予備）
        } else {
            return strip_tags($s[0]);
        }
        
        // 以下、urlケースの処理
        
        $url = P2Util::htmlEntityDecodeLite($url);
        
        // ime.nuを外す
        $url = preg_replace('|^([a-z]+://)ime\\.nu/|', '$1', $url);

        // URLをパース
        $purl = @parse_url($url);
        if (!$purl || !isset($purl['host']) || !strstr($purl['host'], '.') || $purl['host'] == '127.0.0.1') {
            return $html;
        }

        // URLを処理
        foreach ($this->user_url_handlers as $handler) {
            if (false !== $linkHtml = call_user_func($handler, $url, $purl, $html, $this)) {
                return $linkHtml;
            }
        }
        foreach ($this->url_handlers as $handler) {
            if (false !== $linkHtml = call_user_func(array($this, $handler), $url, $purl, $html)) {
                return $linkHtml;
            }
        }

        return $html;
    }

    /**
     * 引用変換（単独）（2009/05/06 範囲もこちらから）
     *
     * @access  private
     * @return  string  HTML
     */
    function quote_res_callback($s)
    {
        global $_conf;
        
        if (--$this->str_to_link_rest < 0) {
            return $s[0];
        }
        
        list($full, $qsign, $appointed_num) = $s;
        
        $appointed_num = mb_convert_kana($appointed_num, 'n'); // 全角数字を半角数字に変換
        if (preg_match('/\\D/', $appointed_num)) {
            $appointed_num = preg_replace('/\\D+/', '-', $appointed_num);
            return $this->quote_res_range_callback(array($full, $qsign, $appointed_num));
        }
        if (preg_match('/^0/', $appointed_num)) {
            return $s[0];
        }
        
        $qnum = intval($appointed_num);
        if ($qnum < 1 || $qnum >= sizeof($this->thread->datlines)) {
            return $s[0];
        }
        
        // 自分自身の番号も変換せずに戻したいところだが
        
        
        $read_url = P2Util::buildQueryUri($_conf['read_php'],
            array(
                'host' => $this->thread->host,
                'bbs'  => $this->thread->bbs,
                'key'  => $this->thread->key,
                'offline' => '1',
                'ls'   => $appointed_num // "{$appointed_num}n"
            )
        );
        
        $attributes = array();
        strlen($_conf['bbs_win_target']) and $attributes['target'] = $_conf['bbs_win_target'];
        if ($_conf['quote_res_view']) {
            $attributes = array_merge($attributes, array(
                'onmouseover' => "showResPopUp('q{$qnum}of{$this->thread->key}',event)",
                'onmouseout'  => "hideResPopUp('q{$qnum}of{$this->thread->key}')"
            ));
        }
        return P2View::tagA($read_url, "{$full}", $attributes);
    }

    /**
     * 引用変換（範囲）
     *
     * @access  private
     * @return  string
     */
    function quote_res_range_callback($s)
    {
        global $_conf;
        
        list($full, $qsign, $appointed_num) = $s;
        
        if ($appointed_num == '-') {
            return $s[0];
        }

        $read_url = P2Util::buildQueryUri($_conf['read_php'],
            array(
                'host' => $this->thread->host,
                'bbs' => $this->thread->bbs,
                'key' => $this->thread->key,
                'offline' => '1',
                'ls' => "{$appointed_num}n"
            )
        );
        
        if ($_conf['iframe_popup']) {
            $pop_url = $read_url . "&renzokupop=true";
            return $this->iframePopup(
                array($read_url, $pop_url), $full,
                array('target' => $_conf['bbs_win_target']), 1
            );
        }

        // 普通にリンク
        return  P2View::tagA($read_url, "{$full}", array('target' => $_conf['bbs_win_target']));

        // 1つ目を引用レスポップアップ
        /*
        $qnums = explode('-', $appointed_num);
        $qlink = $this->quote_res_callback(array($qsign.$qnum[0], $qsign, $qnum[0])) . '-';
        if (isset($qnums[1])) {
            $qlink .= $qnums[1];
        }
        return $qlink;
        */
    }

    /**
     * HTMLポップアップ変換（コールバック用メソッド）
     *
     * @access  private
     * @retrun  string
     */
    function iframePopupCallback($s)
    {
        return $this->iframePopup($s[1], $s[3], $s[2]);
    }

    /**
     * HTMLポップアップ変換
     *
     * @access  private
     * @param   array|string  $url
     * @param   array|string  $attr
     * @return  string  HTML
     */
    function iframePopup($url, $str, $attr = '', $mode = NULL)
    {
        global $_conf;

        // リンク用URLとポップアップ用URL
        if (is_array($url)) {
            $link_url = $url[0];
            $pop_url  = $url[1];
        } else {
            $link_url = $url;
            $pop_url  = $url;
        }

        // リンク文字列とポップアップの印
        if (is_array($str)) {
            $link_str = $str[0];
            $pop_str  = $str[1];
        } else {
            $link_str = $str;
            $pop_str  = NULL;
        }

        // リンクの属性
        if (is_array($attr)) {
            $attrFor = $attr;
            $attr = '';
            foreach ($attrFor as $key => $value) {
                $attr .= sprintf(' %s="%s"', hs($key), hs($value));
            }
        } elseif ($attr !== '' && substr($attr, 0, 1) != ' ') {
            $attr = ' ' . $attr;
        }

        // リンクの属性にHTMLポップアップ用のイベントハンドラを加える
        $pop_attr = $attr;
        $pop_attr .= " onmouseover=\"showHtmlPopUp('" . hs($pop_url) . "', event, " . hs($_conf['iframe_popup_delay']) . ")\"";
        $pop_attr .= " onmouseout=\"offHtmlPopUp()\"";

        // 最終調整
        if (is_null($mode)) {
            $mode = $_conf['iframe_popup'];
        }
        if ($mode == 2 && !is_null($pop_str)) {
            $mode = 3;
        } elseif ($mode == 3 && is_null($pop_str)) {
            global $skin, $STYLE;
            $custom_pop_img = "skin/{$skin}/pop.png";
            if (file_exists($custom_pop_img)) {
                $pop_img = htmlspecialchars($custom_pop_img, ENT_QUOTES);
                $x = $STYLE['iframe_popup_mark_width'];
                $y = $STYLE['iframe_popup_mark_height'];
            } else {
                $pop_img = 'img/pop.png';
                $y = $x = 12;
            }
            $pop_str = "<img src=\"{$pop_img}\" width=\"{$x}\" height=\"{$y}\" hspace=\"2\" vspace=\"0\" border=\"0\" align=\"top\">";
        }

        /*
        if (preg_match('{^http}', $link_url)) {
            $class_snap = ' class="snap_preview"';
        } else {
            $class_snap = '';
        }
        */
        
        // (p)IDポップアップで同じURLの連続呼び出しなら(p)にしない
        if (!empty($_GET['idpopup']) and isset($_SERVER['QUERY_STRING'])) {
            if ((basename(P2Util::getMyUrl()) . '?' . $_SERVER['QUERY_STRING']) == $link_url) {
                $mode = 0;
            }
        }
        
        $link_url_hs = hs($link_url);
        
        // リンク作成
        switch ($mode) {
            // マーク無し
            case 1:
                return "<a href=\"{$link_url_hs}\"{$pop_attr}>{$link_str}</a>";
            // (p)マーク
            case 2:
                return "(<a href=\"{$link_url_hs}\"{$pop_attr}>p</a>)<a href=\"{$link_url_hs}\"{$attr}>{$link_str}</a>";
            // [p]画像、サムネイルなど
            case 3:
                return "<a href=\"{$link_url_hs}\"{$pop_attr}>{$pop_str}</a><a href=\"{$link_url_hs}\"{$attr}>{$link_str}</a>";
            // ポップアップしない
            default:
                return "<a href=\"{$link_url_hs}\"{$attr}>{$link_str}</a>";
        }
    }

    /**
     * IDフィルタリングポップアップ変換
     *
     * @access  private
     * @return  string  HTML
     */
    function idfilter_callback($s)
    {
        global $_conf;

        list($idstr, $id) = $s;
        // IDは8桁または10桁(+携帯/PC識別子)と仮定して
        /*
        if (strlen($id) % 2 == 1) {
            $id = substr($id, 0, -1);
        }
        */
        $num_ht = '';
        if (isset($this->thread->idcount[$id]) && $this->thread->idcount[$id] > 0) {
            $num_ht = '(' . $this->thread->idcount[$id] . ')';
        } else {
            return $idstr;
        }

        $filter_url = P2Util::buildQueryUri(
            $_conf['read_php'],
            array(
                'bbs'     => $this->thread->bbs,
                'key'     => $this->thread->key,
                'host'    => $this->thread->host,
                'ls'      => 'all',
                'field'   => 'id',
                'word'    => $id,
                'method'  => 'just',
                'match'   => 'on',
                'idpopup' => '1',
                'offline' => '1'
            )
        );
        
        //$idstr = $this->coloredIdStr($idstr, $id);
        
        if ($_conf['iframe_popup']) {
            return $this->iframePopup($filter_url, $idstr, array('target' => $_conf['bbs_win_target'])) . $num_ht;
        }
        
        $attrs = array();
        if ($_conf['bbs_win_target']) {
            $attrs['target'] = $_conf['bbs_win_target'];
        }
        $atag = P2View::tagA(
            $filter_url, $idstr, $attrs
        );
        return "$atag{$num_ht}";
    }

    // }}}

    /**
     * Merged from http://jiyuwiki.com/index.php?cmd=read&page=rep2%A4%C7%A3%C9%A3%C4%A4%CE%C7%D8%B7%CA%BF%A7%CA%D1%B9%B9&alias%5B%5D=pukiwiki%B4%D8%CF%A2
     *
     * @access  private
     * @return  string
     */
    function coloredIdStr($idstr, $id)
    {
        global $STYLE;
        
        // [$id] >= 2　ココの数字でスレに何個以上同じＩＤが出た時に背景色を変えるか決まる
        if (isset($this->thread->idcount[$id]) && $this->thread->idcount[$id] < 2) {
            return $idstr;
        }
        
        $raw = base64_decode(substr($id, 0, 8));

        $arr = unpack('V', substr($raw, 0, 4));
        
        // 色相：値域0～360角度で表す。色相を環状に配置して30分割で使用。
        // 似通った色が判別しやすいように隣合う色の彩度を変えてある。
        $h = ($arr[1] & 0x3f)*360/30;
        $s = ($arr[1] & 0x03) *1; //　彩度：値域0（淡い）～1（濃い)
        $v = 0.5; // 明度：値域0（暗い）～1（明るい）
        // 色相　彩度　明度に関しては以下参考の事　http://konicaminolta.jp/instruments/colorknowledge/part1/05.html

        // 別の、色決定パラメータ
        //$arr = unpack('V*',substr($id, 0, 8));
        //$h = floor(($arr[1] % 36)*360/36); // 色相：36分割
        //$s = ($arr[1] % 3)>=1 ? 0.1 : 0.3; // 彩度：3の剰余が1,2のときは淡く,0のときは少し濃くする
        //$v =($arr[1] % 3)<=1 ? 1 : 0.8;    // 明度：3の剰余が0,1のときは明るさ最大,2の時はちょっと暗くする

        $hi = floor($h/60) % 6;
        $f = $h/60-$hi;
        $p = $v*(1-$s);
        $q = $v*(1-$f*$s);
        $t = $v*(1-(1-$f)*$s);

        switch ($hi) {
            case 0: $R=$v; $G=$t; $B=$p; break;
            case 1: $R=$q; $G=$v; $B=$p; break;
            case 2: $R=$p; $G=$v; $B=$t; break;
            case 3: $R=$p; $G=$q; $B=$v; break;
            case 4: $R=$t; $G=$p; $B=$v; break;
            case 5: $R=$v; $G=$p; $B=$q; break;
        }
        $R = floor($R*255);
        $G = floor($G*255);
        $B = floor($B*255);

        $uline = $STYLE['a_underline_none'] == 1 ? '' : "text-decoration:underline";
        return $idstr = "<span style=\"background-color:rgb({$R},{$G},{$B});{$uline}\">{$idstr}</span>";
    }

    // {{{ ユーティリティメソッド

    /**
     * HTMLメッセージ中の引用レス番号を再帰チェックし、見つかった番号の配列を返す
     *
     * @access  private
     * @param   integer     $res_num       チェック対象レスの番号
     * @param   string|null $name          チェック対象レスの名前（未フォーマットのもの）
     * @param   string|null $msg           チェック対象レスのメッセージ（未フォーマットのもの）
     * @param   integer     $callLimit     再帰での呼び出し数制限
     * @param   integer     $nowDepth      現在の再帰の深さ（マニュアル指定はしない）
     * @return  array    見つかった引用レス番号の配列
     */
    function checkQuoteResNums($res_num, $name, $msg, $callLimit = 20, $nowDepth = 0)
    {
        static $callTimes_ = 0;
        
        if (!$nowDepth) {
            $callTimes_ = 0;
        } else {
            $callTimes_++;
        }
        
        // 再帰での呼び出し数制限
        if ($callTimes_ >= $callLimit) {
            return array();
        }
        
        if ($res_num > count($this->thread->datlines)) {
            return array();
        }
        
        $quote_res_nums = array();
        
        // name, msg が null指定なら datlines, res_num から取得する
        if (is_null($name) || is_null($msg)) {
            $datalinear = $this->thread->explodeDatLine($this->thread->datlines[$res_num - 1]);
            if (is_null($name)) {
                $name = $datalinear[0];
            }
            if (is_null($msg)) {
                $msg = $datalinear[3];
            }
        }
        
        // {{{ 名前をチェックする
        
        if ($matches = $this->getQuoteResNumsName($name)) {
            
            foreach ($matches as $a_quote_res_num) {
            
                $quote_res_nums[] = $a_quote_res_num;

                // 自分自身の番号と同一でなければ
                if ($a_quote_res_num != $res_num) {
                    // チェックしていない番号を再帰チェック
                    if (empty($this->quote_res_nums_checked[$a_quote_res_num])) {
                        $this->quote_res_nums_checked[$a_quote_res_num] = true;
                        $quote_res_nums = array_merge($quote_res_nums,
                            $this->checkQuoteResNums($a_quote_res_num, null, null, $callLimit, $nowDepth + 1)
                        );
                    }
                }
            }
        }
        
        // }}}
        // {{{ メッセージをチェックする
        
        $quote_res_nums_msg = $this->getQuoteResNumsMsg($msg);

        foreach ($quote_res_nums_msg as $a_quote_res_num) {

            $quote_res_nums[] = $a_quote_res_num;

            // 自分自身の番号と同一でなければ、
            if ($a_quote_res_num != $res_num) {
                // チェックしていない番号を再帰チェック
                if (empty($this->quote_res_nums_checked[$a_quote_res_num])) {
                    $this->quote_res_nums_checked[$a_quote_res_num] = true;
                    $quote_res_nums = array_merge($quote_res_nums,
                        $this->checkQuoteResNums($a_quote_res_num, null, null, $callLimit, $nowDepth + 1)
                    );
                 }
             }

        }

        // }}}
        
        return array_unique($quote_res_nums);
    }
    
    // }}}
    // {{{ link_callback()から呼び出されるURL書き換えメソッド

    // これらのメソッドは引数が処理対象パターンに合致しないとFALSEを返し、
    // link_callback()はFALSEが返ってくると$url_handlersに登録されている次の関数/メソッドに処理させようとする。

    /**
     * 通常URLリンク
     *
     * @access  private
     * @param   array   $purl  urlをparse_url()したもの
     * @return  string|false  HTML
     */
    function plugin_linkURL($url, $purl, $html)
    {
        global $_conf;

        if (isset($purl['scheme'])) {
            // ime
            $link_url = $_conf['through_ime'] ? P2Util::throughIme($url) : $url;

            // HTMLポップアップ
            // wikipedia.org は、フレームを解除してしまうので、対象外とする
            if ($_conf['iframe_popup'] && preg_match('/https?/', $purl['scheme']) && !preg_match('~wikipedia\.org~', $url)) {
                // p2pm 指定の場合のみ、特別にm指定を追加する
                if ($_conf['through_ime'] == 'p2pm') {
                    $pop_url = preg_replace('/\\?(enc=1&)url=/', '?$1m=1&url=', $link_url);
                } else {
                    $pop_url = $link_url;
                }
                $link = $this->iframePopup(array($link_url, $pop_url), $html, array('target' => $_conf['ext_win_target']));
            } else {
                $link = P2View::tagA($link_url, $html, array('target' => $_conf['ext_win_target']));
            }
            
            // {{{ ブラクラチェッカ
            
            if ($_conf['brocra_checker_use'] && preg_match('/https?/', $purl['scheme'])) {
                $brocra_checker_url = $_conf['brocra_checker_url'] . '?' . $_conf['brocra_checker_query'] . '=' . rawurlencode($url);
                // ブラクラチェッカ・ime
                if ($_conf['through_ime']) {
                    $brocra_checker_url = P2Util::throughIme($brocra_checker_url);
                }
                // ブラクラチェッカ・HTMLポップアップ
                if ($_conf['iframe_popup']) {
                    // p2pm 指定の場合のみ、特別にm指定を追加する
                    if ($_conf['through_ime'] == 'p2pm') {
                        $brocra_pop_url = preg_replace('/\\?(enc=1&)url=/', '?$1m=1&url=', $brocra_checker_url);
                    } else {
                        $brocra_pop_url = $brocra_checker_url;
                    }
                    $brocra_checker_link_tag = $this->iframePopup(
                        array($brocra_checker_url, $brocra_pop_url), hs('ﾁｪｯｸ'), $_conf['ext_win_target_at']
                    );
                } else {
                    $brocra_checker_link_tag = P2View::tagA(
                        $brocra_checker_url,
                        hs('ﾁｪｯｸ'),
                        array('target' => $_conf['ext_win_target'])
                    );
                }
                $link .= ' [' . $brocra_checker_link_tag . ']';
            }
            
            // }}}
            
            return $link;
        }
        return FALSE;
    }

    /**
     * 2ch, bbspink    板リンク
     *
     * @access  private
     * @return  string|false  HTML
     */
    function plugin_link2chSubject($url, $purl, $html)
    {
        global $_conf;

        if (preg_match('{^http://(\\w+\\.(?:2ch\\.net|bbspink\\.com))/([^/]+)/$}', $url, $m)) {

            return sprintf('%s [%s]',
                P2View::tagA(
                    $url, $html, array('target' => 'subject')
                ),
                P2View::tagA(
                    P2Util::buildQueryUri($_conf['subject_php'], array('host' => $m[1], 'bbs' => $m[2])),
                    hs('板をp2で開く'),
                    array('target' => 'subject')
                )
            );
            
        }
        return false;
    }

    /**
     * 2ch, bbspink    スレッドリンク
     *
     * @access  private
     * @return  string|false  HTML
     */
    function plugin_link2ch($url, $purl, $html)
    {
        global $_conf;

        // http://anchorage.2ch.net/test/read.cgi/occult/1238339367/
        // http://orz.2ch.io/p/-/tsushima.2ch.net/newsplus/1240991583/
        // http://c.2ch.net/test/-/occult/1229761545/i (未対応)

        if (preg_match('{^http://(orz\.2ch\.io/p/-/)?(\\w+\\.(?:2ch\\.net|bbspink\\.com))/(test/read\\.cgi/)?([^/]+)/([1-9]\\d+)(?:/([^/]+)?)?$}', $url, $m)) {
        
            if ($m[1] != '' xor $m[3] != '') {

                $ls = (!isset($m[6]) || $m[6] == 'i') ? '' : $m[6];
                $host = $m[2];
                $bbs  = $m[4];
                $key  = $m[5];
                $read_url = "{$_conf['read_php']}?host={$host}&bbs={$bbs}&key={$key}&ls={$ls}";
                
                if ($_conf['iframe_popup']) {
                    if (preg_match('/^[0-9\\-n]+$/', $ls)) {
                        $pop_url = $url;
                    } else {
                        $pop_url = $read_url . '&onlyone=true';
                    }
                    return $this->iframePopup(
                        array($read_url, $pop_url), $html, array('target' => $_conf['bbs_win_target'])
                    );
                }
                return P2View::tagA($read_url, $html, array('target' => $_conf['bbs_win_target']));
            }
        }
        return false;
    }

    /**
     * 2ch過去ログhtml
     *
     * @access  private
     * @return  string|false  HTML
     */
    function plugin_link2chKako($url, $purl, $html)
    {
        global $_conf;

        if (preg_match('{^http://(\\w+(?:\\.2ch\\.net|\\.bbspink\\.com))(?:/[^/]+/)?/([^/]+)/kako/\\d+(?:/\\d+)?/(\\d+)\\.html$}', $url, $m)) {
            $read_url = "{$_conf['read_php']}?host={$m[1]}&bbs={$m[2]}&key={$m[3]}&kakolog=" . rawurlencode($url);
            
            if ($_conf['iframe_popup']) {
                $pop_url = $read_url . '&onlyone=true';
                return $this->iframePopup(
                    array($read_url, $pop_url), $html, array('target' => $_conf['bbs_win_target'])
                );
            }
            return P2View::tagA($read_url, $html, array('target' => $_conf['bbs_win_target']));
        }
        return FALSE;
    }

    /**
     * まちBBS / JBBS＠したらば  内リンク
     *
     * @access  private
     * @return  string|false  HTML
     */
    function plugin_linkMachi($url, $purl, $html)
    {
        global $_conf;

        if (preg_match(
            '{^http://((\\w+\\.machibbs\\.com|\\w+\\.machi\\.to|jbbs\\.livedoor\\.(?:jp|com)|jbbs\\.shitaraba\\.com)(/\\w+)?)/bbs/read\\.(?:pl|cgi)\\?BBS=(\\w+)(?:&amp;|&)KEY=([0-9]+)(?:(?:&amp;|&)START=([0-9]+))?(?:(?:&amp;|&)END=([0-9]+))?(?=&|$)}',
            $url, $m
        )) {
            $start = isset($m[6]) ? $m[6] : null;
            $end   = isset($m[7]) ? $m[7] : null;
            $read_url = "{$_conf['read_php']}?host={$m[1]}&bbs={$m[4]}&key={$m[5]}";
            if ($start || $end) {
                $read_url .= "&ls={$start}-{$end}";
            }
            if ($_conf['iframe_popup']) {
                $pop_url = $url;
                return $this->iframePopup(
                    array($read_url, $pop_url), $html, array('target' => $_conf['bbs_win_target'])
                );
            }
            return P2View::tagA($read_url, $html, array('target' => $_conf['bbs_win_target']));
        }
        return FALSE;
    }

    /**
     * JBBS＠したらば  内リンク
     *
     * @access  private
     * @return  string|false  HTML
     */
    function plugin_linkJBBS($url, $purl, $html)
    {
        global $_conf;

        if (preg_match(
            '{^http://(jbbs\\.livedoor\\.(?:jp|com)|jbbs\\.shitaraba\\.com)/bbs/read\\.cgi/(\\w+)/(\\d+)/(\\d+)(?:/((\\d+)?-(\\d+)?|[^/]+)|/?)$}',
            $url, $m
        )) {
            $ls = isset($m[5]) ? $m[5] : null;
            $read_url = "{$_conf['read_php']}?host={$m[1]}/{$m[2]}&bbs={$m[3]}&key={$m[4]}&ls={$ls}";
            if ($_conf['iframe_popup']) {
                $pop_url = $url;
                return $this->iframePopup(
                    array($read_url, $pop_url), $html, array('target' => $_conf['bbs_win_target'])
                );
            }
            return P2View::tagA($read_url, $html, array('target' => $_conf['bbs_win_target']));
        }
        return FALSE;
    }
    
    /**
     * 外部板 read.cgi 形式 リンク
     *
     * @access  private
     * @return  string|false  HTML
     */
    function plugin_linkReadCgi($url, $purl, $html)
    {
        global $_conf;

        // 外部板 read.cgi 形式 http://ex14.vip2ch.com/test/read.cgi/operate/1161701941/ 
        if (preg_match('{http://([^/]+)/test/read\\.cgi/(\\w+)/(\\d+)/?([^/]+)?}', $url, $matches)) {
            $host = $matches[1];
            $bbs  = $matches[2];
            $key  = $matches[3];
            $ls   = geti($matches[4]);

            $read_url = "{$_conf['read_php']}?host={$host}&bbs={$bbs}&key={$key}&ls={$ls}";
            if ($_conf['iframe_popup']) {
                if (preg_match('/^[0-9\\-n]+$/', $ls)) {
                    $pop_url = $url;
                } else {
                    $pop_url = $read_url . '&onlyone=true';
                }
                return $this->iframePopup(
                    array($read_url, $pop_url), $html, array('target' => $_conf['bbs_win_target'])
                );
            }
            return P2View::tagA($read_url, $html, array('target' => $_conf['bbs_win_target']));
        }
        return FALSE;
    }
    
    /**
     * YouTubeリンク変換プラグイン
     *
     * [wish] YouTube APIを利用して、画像サムネイルのみにしたい
     *
     * 2007/06/25 YouTube は API を経由させてなくても、真ん中のサムネイルは 
     * http://img.youtube.com/vi/VIDEO_ID/2.jpg でアクセスできる。 
     * 1.jpg と 3.jpg と合わせて 3 枚並べてもいいかもしれない。 
     *
     * @access  private
     * @return  string|false  HTML
     */
    function plugin_linkYouTube($url, $purl, $html)
    {
        global $_conf;

        // http://www.youtube.com/watch?v=Mn8tiFnAUAI
        // http://m.youtube.com/watch?v=OhcX0xJsDK8&client=mv-google&gl=JP&hl=ja&guid=ON&warned=True
        if (preg_match('{^http://(www|jp|m)\\.youtube\\.com/watch\\?(?:.+&amp;)?v=([0-9a-zA-Z_\\-]+)}', $url, $m)) {
            if ($m[1] == 'm') {
                $url = "http://www.youtube.com/watch?v={$m[2]}";
            }
            $url    = P2Util::throughIme($url);
            $url_hs = hs($url);
            $subd   = $m[1];
            $id     = $m[2];
            $atag   = P2View::tagA($url, $html, array('target' => $_conf['ext_win_target']));
            return <<<EOP
$atag<br>
<object width="425" height="350"><param name="movie" value="http://www.youtube.com/v/{$id}"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/{$id}" type="application/x-shockwave-flash" wmode="transparent" width="425" height="350"></embed></object>\n
EOP;
        }
        return FALSE;
    }
    
    /**
     * ニコニコ動画変換プラグイン
     *
     * @access  private
     * @return  string|false  HTML
     */
    function plugin_linkNicoNico($url, $purl, $html)
    {
        global $_conf;

        // http://www.nicovideo.jp/watch?v=utbrYUJt9CSl0
        // http://www.nicovideo.jp/watch/utvWwAM30N0No
/*
<div style="width:318px; border:solid 1px #CCCCCC;"><iframe src="http://www.nicovideo.jp/thumb/utvWwAM30N0No" width="100%" height="198" scrolling="no" border="0" frameborder="0"></iframe></div>
*/
        if (preg_match('{^http://www\\.nicovideo\\.jp/watch(?:/|(?:\\?v=))([0-9a-zA-Z_-]+)}', $url, $m)) {
            //$url = P2Util::throughIme($url);
            //$url_hs = hs($url);
            $id = $m[1];
            return <<<EOP
<div style="width:318px; border:solid 1px #CCCCCC;"><iframe src="http://www.nicovideo.jp/thumb/{$id}" width="100%" height="198" scrolling="no" border="0" frameborder="0"></iframe></div>
EOP;
        }
        return FALSE;
    }
    
    // {{{ plugin_linkYourFileHost()

    /**
     * YourFileHost変換プラグイン
     *
     * @param   string  $url
     * @param   array   $purl
     * @param   string  $html
     * @return  string|false  HTML
     */
    function plugin_linkYourFileHost($url, $purl, $html)
    {
        global $_conf;

        // http://www.yourfilehost.com/media.php?cat=video&file=hogehoge.wmv
        if (preg_match('{^http://www\\.yourfilehost\\.com/media\\.php\\?cat=video&file=([0-9A-Za-z_\\-\\.]+)}', $url, $m)) {
            $link_url = $_conf['through_ime'] ? P2Util::throughIme($url) : $url;

            if ($_conf['iframe_popup']) {
                $linkHtml = $this->iframePopup($link_url, $html, array('target' => $_conf['bbs_win_target']));
                
            } else {
                $linkHtml = P2View::tagA($link_url, $html, array('target' => $_conf['ext_win_target']));
            }

            $dl_url1 = "http://getyourfile.dyndns.tv/video?url=" . rawurlencode($url);
            $dl_url2 = "http://yourfilehostwmv.com/video?url=" . rawurlencode($url);
            if ($_conf['through_ime']) {
                $dl_url1 = P2Util::throughIme($dl_url1);
                $dl_url2 = P2Util::throughIme($dl_url2);
            }
            $dl_url1_atag = P2View::tagA($dl_url1,
                hs('GetYourFile'),
                array('target' => $_conf['ext_win_target'])
            );
            $dl_url2_atag = P2View::tagA($dl_url2,
                hs('GetWMV'),
                array('target' => $_conf['ext_win_target'])
            );
            
            return "{$linkHtml} [$dl_url1_atag][$dl_url2_atag]";
        }
        return FALSE;
    }
    
    // }}}

    /**
     * 画像ポップアップ変換
     *
     * @access  private
     * @return  string|false  HTML
     */
    function plugin_viewImage($url, $purl, $html)
    {
        global $_conf;

        // 表示制限
        if (!isset($GLOBALS['pre_thumb_limit']) && $_conf['pre_thumb_limit']) {
            $GLOBALS['pre_thumb_limit'] = $_conf['pre_thumb_limit'];
        }
        if (!$_conf['preview_thumbnail'] || empty($GLOBALS['pre_thumb_limit'])) {
            return false;
        }

        if (preg_match('{^https?://.+?\\.(jpe?g|gif|png)$}i', $url) && empty($purl['query'])) {
            $GLOBALS['pre_thumb_limit']--;
            
            $img_tag = sprintf(
                '<img class="thumbnail" src="%s" height="%s" weight="%s" hspace="4" vspace="4" align="middle">',
                hs($url),
                hs($_conf['pre_thumb_height']),
                hs($_conf['pre_thumb_width'])
            );
            
            switch ($_conf['iframe_popup']) {
                case 1:
                    $view_img_ht = $this->iframePopup(
                        $url, $img_tag . $html, array('target' => $_conf['ext_win_target'])
                    );
                    break;
                case 2: // (p)の設定だが、画像サムネイルを利用する
                    $view_img_ht = $this->iframePopup(
                        $url, array($html, $img_tag), array('target' => $_conf['ext_win_target'])
                    );
                    break;
                case 3: // p画像の設定だが、画像サムネイルを利用する
                    $view_img_ht = $this->iframePopup(
                        $url, array($html, $img_tag), array('target' => $_conf['ext_win_target'])
                    );
                    break;
                default:
                    $view_img_ht = P2View::tagA($url, "{$img_tag}{$html}", array('target' => $_conf['ext_win_target']));
            }

            // ブラクラチェッカ （プレビューとは相容れないのでコメントアウト）
            /*
            if ($_conf['brocra_checker_use']) {
                $link_url_en = rawurlencode($url);
                $atag = P2View::tagA(
                    "{$_conf['brocra_checker_url']}?{$_conf['brocra_checker_query']}={$link_url_en}",
                    hs('チェック')
                    array('target' => $_conf['ext_win_target'])
                );
                $view_img_ht .= " [$atag]";
            }
            */

            return $view_img_ht;
        }
        return false;
    }

    // }}}
}

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
