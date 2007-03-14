<?php
/**
 * p2 - スレッドを表示する クラス PC用
 */
class ShowThreadPc extends ShowThread
{
    var $quote_res_nums_checked; // ポップアップ表示されるチェック済みレス番号を登録した配列
    var $quote_res_nums_done; // ポップアップ表示される記録済みレス番号を登録した配列

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
        );
        if ($_conf['preview_thumbnail']) {
            $this->url_handlers[] = 'plugin_viewImage';
        }
        $_conf['link_youtube']  and $this->url_handlers[] = 'plugin_linkYouTube';
        $_conf['link_niconico'] and $this->url_handlers[] = 'plugin_linkNicoNico';
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

        $status_title = htmlspecialchars($this->thread->itaj, ENT_QUOTES) . " / " . $this->thread->ttitle_hd;
        //$status_title = str_replace("'", "\'", $status_title);
        //$status_title = str_replace('"', "\'\'", $status_title);
        echo "<dl onMouseover=\"window.top.status='{$status_title}';\">";

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
            flush();
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
     * @return  string
     */
    function transRes($ares, $i)
    {
        global $_conf, $STYLE, $mae_msg;

        global $ngaborns_head_hits, $ngaborns_body_hits;
        
        $tores      = "";
        $rpop       = "";
        
        $resar      = $this->thread->explodeDatLine($ares);
        $name       = $resar[0];
        $mail       = $resar[1];
        $date_id    = $resar[2];
        $msg        = $resar[3];

        // {{{ フィルタリングカット
        
        if (isset($GLOBALS['word']) && strlen($GLOBALS['word']) > 0) {
            if (strlen($GLOBALS['word_fm']) <= 0) {
                return '';
            // ターゲット設定
            } elseif (!$target = $this->getFilterTarget($i, $name, $mail, $date_id, $msg)) {
                return '';
            // マッチング
            } elseif (!$this->filterMatch($target, $i)) {
                return '';
            }
        }
        
        // }}}
        // {{{ あぼーんチェック（名前、メール、ID、メッセージ）
        
        $aborned_res = "<dt id=\"r{$i}\" class=\"aborned\"><span>&nbsp;</span></dt>\n"; // 名前
        $aborned_res .= "<!-- <dd class=\"aborned\">&nbsp;</dd> -->\n"; // 内容

        if (false !== $this->checkAborns($name, $mail, $date_id, $msg)) {
            return $aborned_res;
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
        // {{{ レスをポップアップ表示
        
        if ($_conf['quote_res_view']) {
            $quote_res_nums = $this->checkQuoteResNums($i, $name, $msg);

            foreach ($quote_res_nums as $rnv) {
                if (empty($this->quote_res_nums_done[$rnv]) and $rnv < count($this->thread->datlines)) {
                    $ds = $this->qRes($this->thread->datlines[$rnv-1], $rnv);
                    $onPopUp_at = " onMouseover=\"showResPopUp('q{$rnv}of{$this->thread->key}',event,true)\"";
                    $rpop .= "<dd id=\"q{$rnv}of{$this->thread->key}\" class=\"respopup\"{$onPopUp_at}><i>" . $ds . "</i></dd>\n";
                    $this->quote_res_nums_done[$rnv] = true;
                }
            }
        }
        
        // }}}
        
        //=============================================================
        // まとめて出力
        //=============================================================

        $name = $this->transName($name, $i); // 名前HTML変換
        $msg = $this->transMsg($msg, $i); // メッセージHTML変換


        // BEプロファイルリンク変換
        $date_id = $this->replaceBeId($date_id, $i);

        // HTMLポップアップ
        if ($_conf['iframe_popup']) {
            $date_id = preg_replace_callback("{<a href=\"(http://[-_.!~*()a-zA-Z0-9;/?:@&=+\$,%#]+)\"({$_conf['ext_win_target_at']})>((\?#*)|(Lv\.\d+))</a>}", array($this, 'iframe_popup_callback'), $date_id);
        }


        $a_ng_msg_hs = htmlspecialchars($a_ng_msg, ENT_QUOTES);
        
        // NGメッセージ変換
        if ($isNgMsg) {
            $msg = <<<EOMSG
<s class="ngword" onMouseover="document.getElementById('ngm{$ngaborns_body_hits}').style.display = 'block';">NG：{$a_ng_msg_hs}</s>
<div id="ngm{$ngaborns_body_hits}" style="display:none;">$msg</div>
EOMSG;
        }

        // NGネーム変換
        if ($isNgName) {
            $name = <<<EONAME
<s class="ngword" onMouseover="document.getElementById('ngn{$ngaborns_head_hits}').style.display = 'block';">$name</s>
EONAME;
            $msg = <<<EOMSG
<div id="ngn{$ngaborns_head_hits}" style="display:none;">$msg</div>
EOMSG;

        // NGメール変換
        } elseif ($isNgMail) {
            $mail = <<<EOMAIL
<s class="ngword" onMouseover="document.getElementById('ngn{$ngaborns_head_hits}').style.display = 'block';">$mail</s>
EOMAIL;
            $msg = <<<EOMSG
<div id="ngn{$ngaborns_head_hits}" style="display:none;">$msg</div>
EOMSG;

        // NGID変換
        } elseif ($isNgId) {
            $date_id = preg_replace('|ID: ?([0-9A-Za-z/.+]{8,11})|', "<s class=\"ngword\" onMouseover=\"document.getElementById('ngn{$ngaborns_head_hits}').style.display = 'block';\">\\0</s>", $date_id);
            
            /*
            $date_id = <<<EOID
<s class="ngword" onMouseover="document.getElementById('ngn{$ngaborns_head_hits}').style.display = 'block';">$date_id</s>
EOID;
            */
            $msg = <<<EOMSG
<div id="ngn{$ngaborns_head_hits}" style="display:none;">$msg</div>
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
        $tores .= "<span class=\"name\"><b>{$name}</b></span>：";

        // メール
        if ($mail) {
            if (strstr($mail, "sage") && $STYLE['read_mail_sage_color']) {
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
                    $date_id = preg_replace_callback('|ID: ?([0-9A-Za-z/.+]{8,11})|', array($this, 'idfilter_callback'), $date_id);
                }
            }
        }

        $tores .= $date_id; // 日付とID
        $tores .= "</dt>\n";
        $tores .= $rpop; // レスポップアップ用引用
        $tores .= "<dd>{$msg}<br><br></dd>\n"; // 内容

        // まとめてフィルタ色分け
        if (!empty($GLOBALS['word_fm']) && $GLOBALS['res_filter']['match'] != 'off') {
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
                if ($this->thread->ttitle_hd) {
                    $ds = "<b>{$this->thread->ttitle_hd}</b><br><br>";
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

        $resar      = $this->thread->explodeDatLine($resline);
        $name       = isset($resar[0]) ? $resar[0] : '';
        $mail       = isset($resar[1]) ? $resar[1] : '';
        $date_id    = isset($resar[2]) ? $resar[2] : '';
        $msg        = isset($resar[3]) ? $resar[3] : '';
        
        // あぼーんチェック
        if (false !== $this->checkAborns($name, $mail, $date_id, $msg)) {
            $name = $date_id = $msg = 'あぼーん';
            $mail = '';
        
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
            $msg = $this->transMsg($msg, $i); // メッセージ変換
        
            // BEプロファイルリンク変換
            $date_id = $this->replaceBeId($date_id, $i);

            // HTMLポップアップ
            if ($_conf['iframe_popup']) {
                $date_id = preg_replace_callback("{<a href=\"(http://[-_.!~*()a-zA-Z0-9;/?:@&=+\$,%#]+)\"({$_conf['ext_win_target_at']})>((\?#*)|(Lv\.\d+))</a>}", array($this, 'iframe_popup_callback'), $date_id);
            }


            $a_ng_msg_hs = htmlspecialchars($a_ng_msg, ENT_QUOTES);
            
            // NGメッセージ変換
            if ($isNgMsg) {
                $msg = <<<EOMSG
<s class="ngword" onMouseover="document.getElementById('ngm{$ngaborns_body_hits}').style.display = 'block';">NG：{$a_ng_msg_hs}</s>
<div id="ngm{$ngaborns_body_hits}" style="display:none;">$msg</div>
EOMSG;
            }

            // NGネーム変換
            if ($isNgName) {
                $name = <<<EONAME
<s class="ngword" onMouseover="document.getElementById('ngn{$ngaborns_head_hits}').style.display = 'block';">$name</s>
EONAME;
                $msg = <<<EOMSG
<div id="ngn{$ngaborns_head_hits}" style="display:none;">$msg</div>
EOMSG;

            // NGメール変換
            } elseif ($isNgMail) {
                $mail = <<<EOMAIL
<s class="ngword" onMouseover="document.getElementById('ngn{$ngaborns_head_hits}').style.display = 'block';">$mail</s>
EOMAIL;
                $msg = <<<EOMSG
<div id="ngn{$ngaborns_head_hits}" style="display:none;">$msg</div>
EOMSG;

            // NGID変換
            } elseif ($isNgId) {
                $date_id = preg_replace('|ID: ?([0-9A-Za-z/.+]{8,11})|', "<s class=\"ngword\" onMouseover=\"document.getElementById('ngn{$ngaborns_head_hits}').style.display = 'block';\">\\0</s>", $date_id);
            
                /*
                $date_id = <<<EOID
<s class="ngword" onMouseover="document.getElementById('ngn{$ngaborns_head_hits}').style.display = 'block';">$date_id</s>
EOID;
                */
            
                $msg = <<<EOMSG
<div id="ngn{$ngaborns_head_hits}" style="display:none;">$msg</div>
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
                        $date_id = preg_replace_callback('|ID: ?([0-9A-Za-z/.+]{8,11})|', array($this, 'idfilter_callback'), $date_id);
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
     * @return  string
     */
    function transName($name, $resnum)
    {
        global $_conf;

        $nameID = "";
        
        // ID付なら名前は "aki </b>◆...p2/2... <b>" といった感じでくる。（通常は普通に名前のみ）
        
        // ID付なら分解する
        if (preg_match('~(.*)( </b>◆.*)~', $name, $matches)) {
            $name = $matches[1];
            $nameID = $matches[2];
        }

        // 数字をリンク化
        if ($_conf['quote_res_view']) {
            /*
            $onPopUp_at = " onMouseover=\"showResPopUp('q\\1of{$this->thread->key}',event)\" onMouseout=\"hideResPopUp('q\\1of{$this->thread->key}')\"";
            $name && $name = preg_replace("/([1-9][0-9]*)/","<a href=\"{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls=\\1\"{$_conf['bbs_win_target_at']}{$onPopUp_at}>\\1</a>", $name, 1);
            */
            // 数字を引用レスポップアップリンク化
            // </b>～<b> は、ホストやトリップなのでマッチしないようにしたい
            $pettern = '/^( ?(?:&gt;|＞)* ?)?([1-9]\d{0,3})(?=\\D|$)/';
            $name && $name = preg_replace_callback($pettern, array($this, 'quote_res_callback'), $name, 1);
        }

        if ($nameID) { $name = $name . $nameID; }

        $name = $name . " "; // 簡易的に文字化け回避

        return $name;
    }

    /**
     * datのレスメッセージをHTML表示用メッセージに変換して返す
     *
     * @access  private
     * @param   string   $msg
     * @param   integer  $resnum  レス番号
     * @return  string
     */
    function transMsg($msg, $resnum)
    {
        // 2ch旧形式のdat
        if ($this->thread->dat_type == "2ch_old") {
            $msg = str_replace('＠｀', ',', $msg);
            $msg = preg_replace('/&amp([^;])/', '&$1', $msg);
        }

        // Safariから投稿されたリンク中チルダの文字化け補正
        //$msg = preg_replace('{(h?t?tp://[\w\.\-]+/)～([\w\.\-%]+/?)}', '$1~$2', $msg);

        // >>1のリンクをいったん外す
        // <a href="../test/read.cgi/accuse/1001506967/1" target="_blank">&gt;&gt;1</a>
        $msg = preg_replace('{<[Aa] .+?>(&gt;&gt;[1-9][\\d\\-]*)</[Aa]>}', '$1', $msg);
        
        // 2chではなされていないエスケープ（ノートンの誤反応対策を含む）
        // 本来は2chのDAT時点でなされていないとエスケープの整合性が取れない気がする。（URLリンクのマッチで副作用が出てしまう）
        //$msg = str_replace(array('"', "'"), array('&quot;', '&#039;'), $msg);
        
        // 2006/05/06 ノートンの誤反応対策 body onload=window()
        $msg = str_replace('onload=window()', '<i>onload=window</i>()', $msg);
        
        // 引用やURLなどをリンク
        $msg = preg_replace_callback($this->str_to_link_regex, array($this, 'link_callback'), $msg, $this->str_to_link_limit);
        
        return $msg;
    }

    // {{{ コールバックメソッド

    /**
     * リンク対象文字列の種類を判定して対応した関数/メソッドに渡す
     *
     * @access  private
     * @return  string
     */
    function link_callback($s)
    {
        global $_conf;

        // preg_replace_callback()では名前付きでキャプチャできない？
        if (!isset($s['link'])) {
            $s['link']  = isset($s[1]) ? $s[1] : null;
            $s['quote'] = isset($s[5]) ? $s[5] : null;
            $s['url']   = isset($s[8]) ? $s[8] : null;
            $s['id']    = isset($s[11]) ? $s[11] : null;
        }

        // マッチしたサブパターンに応じて分岐
        // リンク
        if ($s['link']) {
            if (preg_match('{ href=(["\'])?(.+?)(?(1)\\1)(?=[ >])}i', $s[2], $m)) {
                $url = $m[2];
                $str = $s[3];
            } else {
                return $s[3];
            }

        // 引用
        } elseif ($s['quote']) {
            if (strstr($s[7], '-')) {
                return $this->quote_res_range_callback(array($s['quote'], $s[6], $s[7]));
            }
            return preg_replace_callback('/((?:&gt;|＞)+ ?)?([1-9]\\d{0,3})(?=\\D|$)/', array($this, 'quote_res_callback'), $s['quote']);

        // http or ftp のURL
        } elseif ($s['url']) {
            $url = preg_replace('/^t?(tps?)$/', 'ht$1', $s[9]) . '://' . $s[10];
            $str = $s['url'];

        // ID
        } elseif ($s['id'] && $_conf['flex_idpopup']) {
            return $this->idfilter_callback(array($s['id'], $s[12]));

        // その他（予備）
        } else {
            return strip_tags($s[0]);
        }

        // 以下、urlケースの処理
        
        // ime.nuを外す
        $url = preg_replace('|^([a-z]+://)ime\\.nu/|', '$1', $url);

        // URLをパース
        $purl = @parse_url($url);
        if (!$purl || !isset($purl['host']) || !strstr($purl['host'], '.') || $purl['host'] == '127.0.0.1') {
            return $str;
        }

        // URLを処理
        foreach ($this->user_url_handlers as $handler) {
            if (false !== ($link = call_user_func($handler, $url, $purl, $str, $this))) {
                return $link;
            }
        }
        foreach ($this->url_handlers as $handler) {
            if (false !== ($link = call_user_func(array($this, $handler), $url, $purl, $str))) {
                return $link;
            }
        }

        return $str;
    }

    /**
     * 引用変換（単独）
     *
     * @access  private
     * @return  string
     */
    function quote_res_callback($s)
    {
        global $_conf;
        
        list($full, $qsign, $appointed_num) = $s;
        
        $qnum = intval($appointed_num);
        if ($qnum < 1 || $qnum > sizeof($this->thread->datlines)) {
            return $s[0];
        }

        $read_url = "{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;offline=1&amp;ls={$appointed_num}";
        $attributes = $_conf['bbs_win_target_at'];
        if ($_conf['quote_res_view']) {
            $attributes .= " onmouseover=\"showResPopUp('q{$qnum}of{$this->thread->key}',event)\"";
            $attributes .= " onmouseout=\"hideResPopUp('q{$qnum}of{$this->thread->key}')\"";
        }
        return "<a href=\"{$read_url}\"{$attributes}>{$qsign}{$appointed_num}</a>";
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

        $read_url = "{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;offline=1&amp;ls={$appointed_num}n";

        if ($_conf['iframe_popup']) {
            $pop_url = $read_url . "&amp;renzokupop=true";
            return $this->iframe_popup(array($read_url, $pop_url), $full, $_conf['bbs_win_target_at'], 1);
        }

        // 普通にリンク
        return "<a href=\"{$read_url}\"{$_conf['bbs_win_target_at']}>{$qsign}{$appointed_num}</a>";

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
     * HTMLポップアップ変換（コールバック用インターフェース）
     *
     * @access  private
     * @retrun  string
     */
    function iframe_popup_callback($s)
    {
        return $this->iframe_popup($s[1], $s[3], $s[2]);
    }

    /**
     * HTMLポップアップ変換
     *
     * @access  private
     * @return  string
     */
    function iframe_popup($url, $str, $attr = '', $mode = NULL)
    {
        global $_conf;

        // リンク用URLとポップアップ用URL
        if (is_array($url)) {
            $link_url = $url[0];
            $pop_url = $url[1];
        } else {
            $link_url = $url;
            $pop_url = $url;
        }

        // リンク文字列とポップアップの印
        if (is_array($str)) {
            $link_str = $str[0];
            $pop_str = $str[1];
        } else {
            $link_str = $str;
            $pop_str = NULL;
        }

        // リンクの属性
        if (is_array($attr)) {
            $_attr = $attr;
            $attr = '';
            foreach ($_attr as $key => $value) {
                $attr .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES) . '"';
            }
        } elseif ($attr !== '' && substr($attr, 0, 1) != ' ') {
            $attr = ' ' . $attr;
        }

        // リンクの属性にHTMLポップアップ用のイベントハンドラを加える
        $pop_attr = $attr;
        $pop_attr .= " onmouseover=\"showHtmlPopUp('{$pop_url}',event,{$_conf['iframe_popup_delay']})\"";
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
        
        // (p)IDポップアップで同じURLの連続呼び出しなら(p)にしない
        if (!empty($_GET['idpopup']) and isset($_SERVER['QUERY_STRING'])) {
            if (htmlspecialchars(basename(P2Util::getMyUrl()) . '?' . $_SERVER['QUERY_STRING']) == $link_url) {
                $mode = 0;
            }
        }
        
        // リンク作成
        switch ($mode) {
            // マーク無し
            case 1:
                return "<a href=\"{$link_url}\"{$pop_attr}>{$link_str}</a>";
            // (p)マーク
            case 2:
                return "(<a href=\"{$link_url}\"{$pop_attr}>p</a>)<a href=\"{$link_url}\"{$attr}>{$link_str}</a>";
            // [p]画像、サムネイルなど
            case 3:
                return "<a href=\"{$link_url}\"{$pop_attr}>{$pop_str}</a><a href=\"{$link_url}\"{$attr}>{$link_str}</a>";
            // ポップアップしない
            default:
                return "<a href=\"{$link_url}\"{$attr}>{$link_str}</a>";
        }
    }

    /**
     * IDフィルタリングポップアップ変換
     *
     * @access  private
     * @return  string
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

        $word = rawurlencode($id);
        $filter_url = "{$_conf['read_php']}?bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;host={$this->thread->host}&amp;ls=all&amp;field=id&amp;word={$word}&amp;method=just&amp;match=on&amp;idpopup=1&amp;offline=1";

        if ($_conf['iframe_popup']) {
            return $this->iframe_popup($filter_url, $idstr, $_conf['bbs_win_target_at']) . $num_ht;
        }
        return "<a href=\"{$filter_url}\"{$_conf['bbs_win_target_at']}>{$idstr}</a>{$num_ht}";
    }

    // }}}
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
        
        if ($a_quote_res_num = $this->thread->getQuoteResNumName($name)) {
            $quote_res_nums[] = $a_quote_res_num;

            // 自分自身の番号と同一でなければ
            if ($a_quote_res_num != $res_num) {
                // チェックしていない番号を再帰チェック
                if (empty($this->quote_res_nums_checked[$a_quote_res_num])) {
                    $this->quote_res_nums_checked[$a_quote_res_num] = true;
                    $quote_res_nums = array_merge($quote_res_nums, $this->checkQuoteResNums($a_quote_res_num, null, null, $callLimit, $nowDepth + 1));
                 }
            }
        }
        
        // }}}
        // {{{ メッセージをチェックする
        
        $quote_res_nums_msg = $this->thread->getQuoteResNumsMsg($msg);

        foreach ($quote_res_nums_msg as $a_quote_res_num) {

            $quote_res_nums[] = $a_quote_res_num;

            // 自分自身の番号と同一でなければ、
            if ($a_quote_res_num != $res_num) {
                // チェックしていない番号を再帰チェック
                if (empty($this->quote_res_nums_checked[$a_quote_res_num])) {
                    $this->quote_res_nums_checked[$a_quote_res_num] = true;
                    $quote_res_nums = array_merge($quote_res_nums, $this->checkQuoteResNums($a_quote_res_num, null, null, $callLimit, $nowDepth + 1));
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
     * URLリンク
     *
     * @access  private
     * @param   array   $purl  urlをparse_url()したもの
     * @return  string|false
     */
    function plugin_linkURL($url, $purl, $str)
    {
        global $_conf;

        if (isset($purl['scheme'])) {
            // ime
            if ($_conf['through_ime']) {
                $link_url = P2Util::throughIme($url);
            } else {
                $link_url = $url;
            }

            // HTMLポップアップ
            // wikipedia.org は、フレームを解除してしまうので、対象外とする
            if ($_conf['iframe_popup'] && preg_match('/https?/', $purl['scheme']) && !preg_match('~wikipedia\.org~', $url)) {
                // p2pm 指定の場合のみ、特別にm指定を追加する
                if ($_conf['through_ime'] == 'p2pm') {
                    $pop_url = preg_replace('/\\?(enc=1&amp;)url=/', '?$1m=1&amp;url=', $link_url);
                } else {
                    $pop_url = $link_url;
                }
                $link = $this->iframe_popup(array($link_url, $pop_url), $str, $_conf['ext_win_target_at']);
            } else {
                $link = "<a href=\"{$link_url}\"{$_conf['ext_win_target_at']}>{$str}</a>";
            }

            // ブラクラチェッカ
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
                        $brocra_pop_url = preg_replace('/\\?(enc=1&amp;)url=/', '?$1m=1&amp;url=', $brocra_checker_url);
                    } else {
                        $brocra_pop_url = $brocra_checker_url;
                    }
                    $brocra_checker_link = $this->iframe_popup(array($brocra_checker_url, $brocra_pop_url), 'チェック', $_conf['ext_win_target_at']);
                } else {
                    $brocra_checker_link = "<a href=\"{$brocra_checker_url}\"{$_conf['ext_win_target_at']}>チェック</a>";
                }
                $link .= ' [' . $brocra_checker_link . ']';
            }

            return $link;
        }
        return FALSE;
    }

    /**
     * 2ch bbspink    板リンク
     *
     * @access  private
     * @return  string|false
     */
    function plugin_link2chSubject($url, $purl, $str)
    {
        global $_conf;

        if (preg_match('{^http://(\\w+\\.(?:2ch\\.net|bbspink\\.com))/([^/]+)/$}', $url, $m)) {
            $subject_url = "{$_conf['subject_php']}?host={$m[1]}&amp;bbs={$m[2]}";
            return "<a href=\"{$url}\" target=\"subject\">{$str}</a> [<a href=\"{$subject_url}\" target=\"subject\">板をp2で開く</a>]";
        }
        return FALSE;
    }

    /**
     * 2ch bbspink    スレッドリンク
     *
     * @access  private
     * @return  string|false
     */
    function plugin_link2ch($url, $purl, $str)
    {
        global $_conf;

        if (preg_match('{^http://(\\w+\\.(?:2ch\\.net|bbspink\\.com))/test/read\\.cgi/([^/]+)/([0-9]+)(?:/([^/]+)?)?$}', $url, $m)) {
            $ls = isset($m[4]) ? $m[4] : '';
            $read_url = "{$_conf['read_php']}?host={$m[1]}&amp;bbs={$m[2]}&amp;key={$m[3]}&amp;ls={$ls}";
            if ($_conf['iframe_popup']) {
                if (preg_match('/^[0-9\\-n]+$/', $ls)) {
                    $pop_url = $url;
                } else {
                    $pop_url = $read_url . '&amp;onlyone=true';
                }
                return $this->iframe_popup(array($read_url, $pop_url), $str, $_conf['bbs_win_target_at']);
            }
            return "<a href=\"{$read_url}\"{$_conf['bbs_win_target_at']}>{$str}</a>";
        }
        return FALSE;
    }

    /**
     * 2ch過去ログhtml
     *
     * @access  private
     * @return  string|false
     */
    function plugin_link2chKako($url, $purl, $str)
    {
        global $_conf;

        if (preg_match('{^http://(\\w+(?:\\.2ch\\.net|\\.bbspink\\.com))(?:/[^/]+/)?/([^/]+)/kako/\\d+(?:/\\d+)?/(\\d+)\\.html$}', $url, $m)) {
            $read_url = "{$_conf['read_php']}?host={$m[1]}&amp;bbs={$m[2]}&amp;key={$m[3]}&amp;kakolog=" . rawurlencode($url);
            if ($_conf['iframe_popup']) {
                $pop_url = $read_url . '&amp;onlyone=true';
                return $this->iframe_popup(array($read_url, $pop_url), $str, $_conf['bbs_win_target_at']);
            }
            return "<a href=\"{$read_url}\"{$_conf['bbs_win_target_at']}>{$str}</a>";
        }
        return FALSE;
    }

    /**
     * まちBBS / JBBS＠したらば  内リンク
     *
     * @access  private
     * @return  string|false
     */
    function plugin_linkMachi($url, $purl, $str)
    {
        global $_conf;

        if (preg_match('{^http://((\\w+\\.machibbs\\.com|\\w+\\.machi\\.to|jbbs\\.livedoor\\.(?:jp|com)|jbbs\\.shitaraba\\.com)(/\\w+)?)/bbs/read\\.(?:pl|cgi)\\?BBS=(\\w+)(?:&amp;|&)KEY=([0-9]+)(?:(?:&amp;|&)START=([0-9]+))?(?:(?:&amp;|&)END=([0-9]+))?(?=&|$)}', $url, $m)) {
            $start = isset($m[6]) ? $m[6] : null;
            $end   = isset($m[7]) ? $m[7] : null;
            $read_url = "{$_conf['read_php']}?host={$m[1]}&amp;bbs={$m[4]}&amp;key={$m[5]}";
            if ($start || $end) {
                $read_url .= "&amp;ls={$start}-{$end}";
            }
            if ($_conf['iframe_popup']) {
                $pop_url = $url;
                return $this->iframe_popup(array($read_url, $pop_url), $str, $_conf['bbs_win_target_at']);
            }
            return "<a href=\"{$read_url}\"{$_conf['bbs_win_target_at']}>{$str}</a>";
        }
        return FALSE;
    }

    /**
     * JBBS＠したらば  内リンク
     *
     * @access  private
     * @return  string|false
     */
    function plugin_linkJBBS($url, $purl, $str)
    {
        global $_conf;

        if (preg_match('{^http://(jbbs\\.livedoor\\.(?:jp|com)|jbbs\\.shitaraba\\.com)/bbs/read\\.cgi/(\\w+)/(\\d+)/(\\d+)(?:/((\\d+)?-(\\d+)?|[^/]+)|/?)$}', $url, $m)) {
            $read_url = "{$_conf['read_php']}?host={$m[1]}/{$m[2]}&amp;bbs={$m[3]}&amp;key={$m[4]}&amp;ls={$m[5]}";
            if ($_conf['iframe_popup']) {
                $pop_url = $url;
                return $this->iframe_popup(array($read_url, $pop_url), $str, $_conf['bbs_win_target_at']);
            }
            return "<a href=\"{$read_url}\"{$_conf['bbs_win_target_at']}>{$str}</a>";
        }
        return FALSE;
    }

    /**
     * YouTubeリンク変換プラグイン
     * [wish] YouTube APIを利用して、画像サムネイルのみにしたい
     *
     * @access  private
     * @return  string|false
     */
    function plugin_linkYouTube($url, $purl, $str)
    {
        global $_conf;

        // http://www.youtube.com/watch?v=Mn8tiFnAUAI
        if (preg_match('{^http://www\\.youtube\\.com/watch\\?v=([0-9a-zA-Z_-]+)}', $url, $m)) {
            $url = P2Util::throughIme($url);
            return <<<EOP
<a href="$url"{$_conf['ext_win_target_at']}>$str</a><br>
<object width="425" height="350"><param name="movie" value="http://www.youtube.com/v/{$m[1]}"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/{$m[1]}" type="application/x-shockwave-flash" wmode="transparent" width="425" height="350"></embed></object>\n
EOP;
        }
        return FALSE;
    }
    
    /**
     * ニコニコ動画変換プラグイン
     *
     * @access  private
     * @return  string|false
     */
    function plugin_linkNicoNico($url, $purl, $str)
    {
        global $_conf;

        // http://www.nicovideo.jp/watch?v=utbrYUJt9CSl0
        // http://www.nicovideo.jp/watch/utvWwAM30N0No
/*
<div style="width:318px; border:solid 1px #CCCCCC;"><iframe src="http://www.nicovideo.jp/thumb?v=utvWwAM30N0No" width="100%" height="198" scrolling="no" border="0" frameborder="0"></iframe></div>
*/
        if (preg_match('{^http://www\\.nicovideo\\.jp/watch(?:/|(?:\\?v=))([0-9a-zA-Z_-]+)}', $url, $m)) {
            $url = P2Util::throughIme($url);
            $id = $m[1];
            return <<<EOP
<div style="width:318px; border:solid 1px #CCCCCC;"><iframe src="http://www.nicovideo.jp/thumb?v={$id}" width="100%" height="198" scrolling="no" border="0" frameborder="0"></iframe></div>
EOP;
        }
        return FALSE;
    }
    
    /**
     * 画像ポップアップ変換
     *
     * @access  private
     * @return  string|false
     */
    function plugin_viewImage($url, $purl, $str)
    {
        global $_conf;

        // 表示制限
        if (!isset($GLOBALS['pre_thumb_limit']) && isset($_conf['pre_thumb_limit'])) {
            $GLOBALS['pre_thumb_limit'] = $_conf['pre_thumb_limit'];
        }
        if (!$_conf['preview_thumbnail'] || empty($GLOBALS['pre_thumb_limit'])) {
            return false;
        }

        if (preg_match('{^https?://.+?\\.(jpe?g|gif|png)$}i', $url) && empty($purl['query'])) {
            $GLOBALS['pre_thumb_limit']--;
            $img_tag = "<img class=\"thumbnail\" src=\"{$url}\" height=\"{$_conf['pre_thumb_height']}\" weight=\"{$_conf['pre_thumb_width']}\" hspace=\"4\" vspace=\"4\" align=\"middle\">";

            switch ($_conf['iframe_popup']) {
                case 1:
                    $view_img = $this->iframe_popup($url, $img_tag.$str, $_conf['ext_win_target_at']);
                    break;
                case 2:
                    $view_img = $this->iframe_popup($url, array($str, $img_tag), $_conf['ext_win_target_at']);
                    break;
                case 3:
                    $view_img = $this->iframe_popup($url, array($str, $img_tag), $_conf['ext_win_target_at']);
                    break;
                default:
                    $view_img = "<a href=\"{$url}\"{$_conf['ext_win_target_at']}>{$img_tag}{$str}</a>";
            }

            // ブラクラチェッカ （プレビューとは相容れないのでコメントアウト）
            /*if ($_conf['brocra_checker_use']) {
                $link_url_en = rawurlencode($url);
                $view_img .= " [<a href=\"{$_conf['brocra_checker_url']}?{$_conf['brocra_checker_query']}={$link_url_en}\"{$_conf['ext_win_target_at']}>チェック</a>]";
            }*/

            return $view_img;
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
