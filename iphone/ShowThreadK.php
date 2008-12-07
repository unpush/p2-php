<?php
require_once P2_LIB_DIR . '/ShowThread.php';
require_once P2_LIB_DIR . '/StrSjis.php';

/**
 * p2 - 携帯用にスレッドを表示するクラス
 */
class ShowThreadK extends ShowThread
{
    var $BBS_NONAME_NAME = '';
    
    /**
     * @constructor
     */
    function ShowThreadK(&$aThread)
    {
        parent::ShowThread($aThread);

        global $_conf;

        $this->url_handlers = array(
            array('this' => 'plugin_link2ch'),
            array('this' => 'plugin_linkMachi'),
            array('this' => 'plugin_linkJBBS'),
            array('this' => 'plugin_link2chKako'),
            array('this' => 'plugin_link2chSubject'),
            array('this' => 'plugin_linkReadCgi'),
        );
        if ($_conf['k_use_picto']) {
            $this->url_handlers[] = array('this' => 'plugin_viewImage');
        }
        $_conf['link_youtube']  and $this->url_handlers[] = array('this' => 'plugin_linkYouTube');
        $_conf['link_niconico'] and $this->url_handlers[] = array('this' => 'plugin_linkNicoNico');
        $this->url_handlers[] = array('this' => 'plugin_linkURL');
        
        if (!$_conf['k_bbs_noname_name'] and P2Util::isHost2chs($this->thread->host)) {
            require_once P2_LIB_DIR . '/SettingTxt.php';
            $st = new SettingTxt($this->thread->host, $this->thread->bbs);
            if (!empty($st->setting_array['BBS_NONAME_NAME'])) {
                $this->BBS_NONAME_NAME = $st->setting_array['BBS_NONAME_NAME'];
            }
        }
    }

    /**
     * DatをHTMLに変換表示する
     *
     * @access  public
     * @return  boolean
     */
    function datToHtml()
    {
        global $_conf;
        
        if (!$this->thread->resrange) {
            echo '<p><b>p2 error: {$this->resrange} is FALSE at datToHtml()</b></p>';
            return false;
        }

        $start = $this->thread->resrange['start'];
        $to = $this->thread->resrange['to'];
        $nofirst = $this->thread->resrange['nofirst'];
        
        // for マルチレス範囲のページスキップ
        if ($this->thread->resrange_multi and !isset($GLOBALS['_skip_resnum'])) {
            $page = isset($_REQUEST['page']) ? max(1, intval($_REQUEST['page'])) : 1;
            $GLOBALS['_skip_resnum'] = ($page - 1) * $GLOBALS['_conf']['k_rnum_range'];
            $this->thread->resrange_readnum = 0;
        }

        !isset($GLOBALS['_shown_resnum']) and $GLOBALS['_shown_resnum'] = 0;
        
        // 1を表示（範囲外のケースもあるのでここで）
        if (!$nofirst) {
            if ($this->thread->resrange_multi and $GLOBALS['_skip_resnum']) {
                $GLOBALS['_skip_resnum']--;
            } else {
                echo $this->transRes($this->thread->datlines[0], 1);
                $GLOBALS['_shown_resnum']++;
                
                if ($this->thread->resrange_readnum < $i) {
                    $this->thread->resrange_readnum = $i;
                }
                
            }
        }
        
        for ($i = $start; $i <= $to; $i++) {
            
            // マルチレス範囲なら
            if ($this->thread->resrange_multi) {
            
                // 表示数超過なら抜ける
                if ($GLOBALS['_shown_resnum'] >= $GLOBALS['_conf']['k_rnum_range']) {
                    break;
                }
                
                // 表示範囲外ならスキップ
                if (!$this->thread->inResrangeMulti($i)) {
                    continue;
                }
            }
            
            // 1が前段処理で既表示ならスキップ
            if (!$nofirst and $i == 1) {
                continue;
            }
            if (!$this->thread->datlines[$i - 1]) {
                break;
            }
            
            // マルチレス範囲のページスキップ
            if ($this->thread->resrange_multi and $GLOBALS['_skip_resnum']) {
                $GLOBALS['_skip_resnum']--;
                continue;
            }

            $res = $this->transRes($this->thread->datlines[$i - 1], $i);
            echo $res;
            
            !isset($GLOBALS['_read_new_html']) && ob_flush() && flush();
            
            if (strlen($res) > 0) {
                $GLOBALS['_shown_resnum']++;
            }
            
            if ($this->thread->resrange_readnum < $i) {
                $this->thread->resrange_readnum = $i;
            }
            
        }
        
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
        global $STYLE, $mae_msg, $res_filter, $word_fm;
        global $ngaborns_hits;
        global $_conf;
        
        $hr = P2View::getHrHtmlK();
        
        $tores      = "";
        $rpop       = "";
        
        $resar      = $this->thread->explodeDatLine($ares);
        $name       = $resar[0];
        $mail       = $resar[1];
        $date_id    = $resar[2];
        $msg        = $resar[3];

		if (!empty($this->BBS_NONAME_NAME) and $this->BBS_NONAME_NAME == $name) {
            $name = '';
        }

        // 現在の年号は省略カットする。（設定で）月日の先頭0もカット。
        if ($_conf['k_date_zerosuppress']) {
            $date_id = preg_replace('~^(?:' . date('Y') . '|' . date('y') . ')/(?:0(\d)|(\d\d))?(?:(/)0)?~', '$1$2$3', $date_id);
        } else {
            $date_id = preg_replace('~^(?:' . date('Y') . '|' . date('y') . ')/~', '$1', $date_id);
        }
        
        // 曜日と時間の間を詰める
        $date_id = str_replace(') ', ')', $date_id);
        
        // 秒もカット
        if ($_conf['k_clip_time_sec']) {
            $date_id = preg_replace('/(\d\d:\d\d):\d\d(\.\d\d)?/', '$1', $date_id);
        }

        // {{{ フィルタリング
        
        if (isset($GLOBALS['word']) && strlen($GLOBALS['word']) > 0) {
            if (strlen($GLOBALS['word_fm']) <= 0) {
                return '';
            // ターゲット設定
            } elseif (!$target = $this->getFilterTarget($i, $name, $mail, $date_id, $msg)) {
                return '';
            // マッチング
            } elseif (false === $this->filterMatch($target, $i)) {
                return '';
            }
        }
        
        // }}}
        // {{{ あぼーんチェック（名前、メール、ID、メッセージ）
        
        /*
        $aborned_res = "<div id=\"r{$i}\" name=\"r{$i}\">&nbsp;</div>\n"; // 名前
        $aborned_res .= ""; // 内容
        */
        $aborned_res = "<span id=\"r{$i}\" name=\"r{$i}\"></span>\n";
        
        if (false !== $this->checkAborns($name, $mail, $date_id, $msg)) {
            return $aborned_res;
        }
        
        // }}}
        // {{{ NGチェック（名前、メール、ID、メッセージ）
        
        $isNgName = false;
        $isNgMail = false;
        $isNgId   = false;
        $isNgMsg  = false;
        
        if (empty($_GET['nong'])) {
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
        }
        
        // }}}
// iPhone用PopUp

        // {{{ レスをポップアップ表示
        
        if ($_conf['quote_res_view']) {
            $quote_res_nums = $this->checkQuoteResNums($i, $name, $msg);

            foreach ($quote_res_nums as $rnv) {
                if (empty($this->quote_res_nums_done[$rnv]) and $rnv < count($this->thread->datlines)) {
                    $ds = $this->qRes($this->thread->datlines[$rnv-1], $rnv, 'q' . $rnv . 'of' .$this->thread->key);
                    $onPopUp_at = " onMouseover=\"showResPopUp('q{$rnv}of{$this->thread->key}',event,true)\"";
                    $rpop .= "<span id=\"q{$rnv}of{$this->thread->key}\" class=\"respopup\"{$onPopUp_at}>" . $ds . "</span>\n";
                    $this->quote_res_nums_done[$rnv] = true;
                }
            }
        }
        
        //=============================================================
        // まとめて出力
        //=============================================================
        
        $name = $this->transName($name, $i); // 名前HTML変換
        
        $has_aa = 0; // 1:弱反応, 2:強反応（AA略）
        $msg = $this->transMsg($msg, $i, $has_aa); // メッセージHTML変換

        // BEプロファイルリンク変換
        $date_id = $this->replaceBeId($date_id, $i);
        
        $a_ng_msg_hs = htmlspecialchars($a_ng_msg, ENT_QUOTES);
        
        // NG変換
        $kakunin_msg_ht = <<<EOP
<a href="{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls={$i}&amp;k_continue=1&amp;nong=1{$_conf['k_at_a']}">確</a>
EOP;
        
        // NGメッセージ変換
        if ($isNgMsg) {
            $msg = <<<EOMSG
<s><font color="{$STYLE['read_ngword']}">NG:{$a_ng_msg_hs}</font></s> $kakunin_msg_ht
EOMSG;
        }
        
        // NGネーム変換
        if ($isNgName) {
            $name = <<<EONAME
<s><font color="{$STYLE['read_ngword']}">$name</font></s>
EONAME;
            $msg = $kakunin_msg_ht;
        
        // NGメール変換
        } elseif ($isNgMail) {
            $mail = <<<EOMAIL
<s><font color="{$STYLE['read_ngword']}">$mail</font></s>
EOMAIL;
            $msg = $kakunin_msg_ht;

        // NGID変換
        } elseif ($isNgId) {
            $date_id = preg_replace('|ID: ?([0-9A-Za-z/.+]{8,11})|', "<s><font color=\"{$STYLE['read_ngword']}\">\\0</font></s>", $date_id);
            /*
            $date_id = <<<EOID
<s><font color="{$STYLE['read_ngword']}">$date_id</font></s>
EOID;
            */
            
            $msg = $kakunin_msg_ht;
        }
        
        /*
        //「ここから新着」画像を挿入
        if ($i == $this->thread->readnum +1) {
            $tores .= <<<EOP
                <div><img src="img/image.png" alt="新着レス" border="0" vspace="4"></div>
EOP;
        }
        */
        
        $id = "qr{$i}of{$this->thread->key}";
        
        // iphone用
        // スマートポップアップメニュー
        if ($_conf['enable_spm']) {
            $onPopUp_at = " onmouseover=\"showSPM({$this->thread->spmObjName},{$i},'{$id}',event,this)\" onmouseout=\"hideResPopUp('{$this->thread->spmObjName}_spm')\"";
            $is = "<a href=\"javascript:void(0);\" class=\"resnum\"{$onPopUp_at}>{$i}</a>";
        }else{
            $is = $i;
        }
        // レスポップアップ用引用
        $tores .= $rpop; 
        // 番号（オンザフライ時）
        if ($this->thread->onthefly) {
            $GLOBALS['newres_to_show_flag'] = true;
            $tores .= "<div id=\"r{$i}\" name=\"r{$i}\">[<font color=\"#00aa00\">{$i}</font>]";
        // 番号（新着レス時）
        } elseif ($i > $this->thread->readnum) {
            $GLOBALS['newres_to_show_flag'] = true;
            $tores .= "<div id=\"r{$i}\" name=\"r{$i}\">[<font color=\"{$STYLE['read_newres_color']}\">{$is}</font>]";
        // 番号
        } else {
            $tores .= "<div class=\"thread\" id=\"r{$i}\" name=\"r{$i}\">[{$is}]";
        }//iPhone用にクラス追加  thread 以下も同様
        
        //$tores .= " ";
        
        // 名前
        (strlen($name) > 0) and $tores .= '<span class="tname">'.$name.'</span>';
        
        // メール
        $is_sage = false;
        if (strlen($mail) > 0) {
            if ($mail == 'sage') {
                $is_sage = true;
            } else {
                //$tores .= $mail . " :";
                $tores .= ':<span class="tmail">' . StrSjis::fixSjis($mail). '</span>';
            }
        }
        
        if (strlen($name) > 0 or strlen($mail) > 0 && !$is_sage) {
            $tores .= ' ';
        }
        
        $no_trim_id_flag = false;
        
        // {{ IDフィルタ
        
        if ($_conf['flex_idpopup'] == 1) {
            if (preg_match('|ID: ?([0-9a-zA-Z/.+]{8,11})|', $date_id, $matches)) {
                $id = $matches[1];
                if ($this->thread->idcount[$id] > 1) {
                    $date_id = preg_replace_callback(
                        '|ID: ?([0-9A-Za-z/.+]{8,11})|',
                        array($this, 'idfilter_callback'), $date_id
                    );
                } else {
                    if ($_conf['k_clip_unique_id']) {
                        $date_id = str_replace($matches[0], 'ID:' . substr($matches[0], -1, 1), $date_id);
                        $no_trim_id_flag = true;
                    }
                }
            }
        }
        
        // }}}
        
        if ($_conf['mobile.id_underline']) {
            $date_id = preg_replace(
                '!((?:ID: ?)| )([0-9A-Za-z/.+]{10}|[0-9A-Za-z/.+]{8}|\\?\\?\\?)?O(?=[^0-9A-Za-z/.+]|$)!',
                '$1$2<u>O</u>', $date_id
            );
        }

        if ($_conf['k_clip_unique_id']) {
            $date_id = str_replace('???', '?', $date_id);
        }
        
        if (!$no_trim_id_flag) {
            $date_id = preg_replace('/ID: ?/', '', $date_id);
        }
        
        $tores .= '<span class="tdate">'.$date_id. '</span>';
        
        if ($is_sage) {
            $tores .= '<font color="#aaaaaa">↓</font>';
        }
        
        $tores .="<br>\n"; // 日付とID
        
        $tores .= "{$msg}</div>$hr\n"; // 内容  // iPhone用にhr削除
        
        // まとめてフィルタ色分け
        if (strlen($GLOBALS['word_fm']) && $GLOBALS['res_filter']['match'] != 'off') {
            if (is_string($_conf['k_filter_marker'])) {
                $tores = StrCtl::filterMarking($GLOBALS['word_fm'], $tores, $_conf['k_filter_marker']);
            } else {
                $tores = StrCtl::filterMarking($GLOBALS['word_fm'], $tores);
            }
        }
        
        // 全角英数スペースカナを半角に
        if ($_conf['k_save_packet']) {
            $tores = mb_convert_kana($tores, 'rnsk'); // SJIS-win だと ask で ＜ を < に変換してしまうようだ
        }
        //080809 スマートポップアップの背景色削除 iPhone用
        $STYLE['respop_bgcolor'] = ""; 
        /* -- 追加 -- */
        
         //iphone 引用してレス
        //できれば埋め込みしないでアクションがあったときに呼び出したい
		/* -- 追加ここから -- */
		$quoteMsg = $msg;

        $matches = null;
		if(preg_match("/(.*)<a href\=\".+\" target=\"_blank\">(\&gt;)*([0-9]{1,4})<\/a>([\\x00-\\xff]+)/im",$msg,$matches)){
			$quoteMsg = $matches[1]."&gt;&gt;".$matches[3].$matches[4];
		}

		// タグ化＆改行にマークしとく
		$quoteMsg = "\r\n<span class=\"respopup\" id=\"quote_msg".$i."\">".str_replace("<br>","___[br]___&gt;",nl2br($quoteMsg))."</span>\r\n";
		/* -- 追加 いったんここまで 次 return句まで移動 -- */
        return $quoteMsg.$tores;
      
    }
    
    
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
    
    /**
     * レス引用HTMLを生成取得する
     *
     * @access  private
     * @param   string   $resline
     * @return  string
     */
    function qRes($resline, $i, $hideid)
    {
        global $_conf;

        $resar      = $this->thread->explodeDatLine($resline);
        $name       = isset($resar[0]) ? $resar[0] : '';
        $mail       = isset($resar[1]) ? $resar[1] : '';
        $date_id    = isset($resar[2]) ? $resar[2] : '';
        $msg        = isset($resar[3]) ? $resar[3] : '';
        
        
        if (!empty($this->BBS_NONAME_NAME) and $this->BBS_NONAME_NAME == $name) {
            $name = '';
        }
        
        // 現在の年号は省略カットする。月日の先頭0もカット。
        if ($_conf['k_date_zerosuppress']) {
            $date_id = preg_replace('~^(?:' . date('Y') . '|' . date('y') . ')/(?:0(\d)|(\d\d))?(?:(/)0)?~', '$1$2$3', $date_id);
        } else {
            $date_id = preg_replace('~^(?:' . date('Y') . '|' . date('y') . ')/~', '$1', $date_id);
        }
        
        // 曜日と時間の間を詰める
        $date_id = str_replace(') ', ')', $date_id);
        
        // 秒もカット
        if ($_conf['k_clip_time_sec']) {
            $date_id = preg_replace('/(\d\d:\d\d):\d\d(\.\d\d)?/', '$1', $date_id);
        }
        
        
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
            
            $has_aa = 0; // 1:弱反応, 2:強反応（AA略）
            $msg = $this->transMsg($msg, $i, $has_aa); // メッセージ変換
        
            // BEプロファイルリンク変換
            $date_id = $this->replaceBeId($date_id, $i);
            
            $a_ng_msg_hs = htmlspecialchars($a_ng_msg, ENT_QUOTES);
            
        	// NG変換
        	$kakunin_msg_ht = <<<EOP
<a href="{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls={$i}&amp;k_continue=1&amp;nong=1{$_conf['k_at_a']}">確</a>
EOP;
            // NGメッセージ変換
        	if ($isNgMsg) {
            	$msg = <<<EOMSG
<s><font color="{$STYLE['read_ngword']}">NG:{$a_ng_msg_hs}</font></s> $kakunin_msg_ht
EOMSG;
			}
			
            // NGネーム変換
            if ($isNgName) {
                $name = <<<EONAME
<s><font color="{$STYLE['read_ngword']}">$name</font></s>
EONAME;
                $msg = $kakunin_msg_ht;

            // NGメール変換
            } elseif ($isNgMail) {
                $mail = <<<EOMAIL
<s><font color="{$STYLE['read_ngword']}">$mail</font></s>
EOMAIL;
                $msg = $kakunin_msg_ht;

            // NGID変換
            } elseif ($isNgId) {
				$date_id = preg_replace('|ID: ?([0-9A-Za-z/.+]{8,11})|', "<s><font color=\"{$STYLE['read_ngword']}\">\\0</font></s>", $date_id);
                /*
                $date_id = <<<EOID
<s><font color="{$STYLE['read_ngword']}">$date_id</font></s>
EOID;
                */
                $msg = $kakunin_msg_ht;
            }
            

            
            // IDフィルタ
            if ($_conf['flex_idpopup'] == 1) {
                if (preg_match('|ID: ?([0-9a-zA-Z/.+]{8,11})|', $date_id, $matches)) {
                    $id = $matches[1];
                    if ($this->thread->idcount[$id] > 1) {
                        $date_id = preg_replace_callback('|ID: ?([0-9A-Za-z/.+]{8,11})|', array($this, 'idfilter_callback'), $date_id);
                    } else {
	                    if ($_conf['k_clip_unique_id']) {
	                        $date_id = str_replace($matches[0], 'ID:' . substr($matches[0], -1, 1), $date_id);
	                        $no_trim_id_flag = true;
	                    }
                    }
                }
            }
        
        }
        
        // $toresにまとめて出力
        //$tores = "<input type=\"submit\" value=\"閉じる\" onClick=\"hideResPopUp('{$hideid}')\"><br>\n";
        $tores = "<img class=\"close\" src=\"iui/icon_close.png\" onClick=\"hideResPopUp('{$hideid}')\">\n";

        $tores .= "　$i ："; // 番号
        $tores .= "<b>$name</b> ："; // 名前
        if ($mail) { $tores .= $mail . " ："; } // メール
        
       if ($_conf['mobile.id_underline']) {
            $date_id = preg_replace('!(ID: ?)([0-9A-Za-z/.+]{10}|[0-9A-Za-z/.+]{8}|\\?\\?\\?)?O(?=[^0-9A-Za-z/.+]|$)!', '$1$2<u>O</u>', $date_id);
        }
        
        if ($_conf['k_clip_unique_id']) {
            $date_id = str_replace('???', '?', $date_id);
        }
        
        if (!$no_trim_id_flag) {
            $date_id = preg_replace('/ID: ?/', '', $date_id);
        }
        
        $tores .= '<span class="tdate">' . $date_id . '</span>';
        /*
        if ($is_sage) {
            $tores .= '<font color="#aaaaaa">↓</font>';
        }
        */
        
        $tores .="<br>\n";  // 日付とID
        $tores .= "{$msg}\n"; // 内容  iPhone用にhr削除

        // まとめてフィルタ色分け
        if ($GLOBALS['word_fm'] && $GLOBALS['res_filter']['match'] != 'off') {
            if (is_string($_conf['k_filter_marker'])) {
                $tores = StrCtl::filterMarking($GLOBALS['word_fm'], $tores, $_conf['k_filter_marker']);
            } else {
                $tores = StrCtl::filterMarking($GLOBALS['word_fm'], $tores);
            }
        }
        
        // 全角英数スペースカナを半角に
        if ($_conf['k_save_packet']) {
            $tores = mb_convert_kana($tores, 'rnsk'); // SJIS-win だと ask で ＜ を < に変換してしまうようだ
        }

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
        if (preg_match("~(.*)( </b>◆.*)~", $name, $matches)) {
            $name = rtrim($matches[1]);
            $nameID = trim(strip_tags($matches[2]));
        }
        
        // 数字を引用レスポップアップリンク化
        if ($_conf['quote_res_view']) {
        // </b>～<b> は、ホスト（やトリップ）なのでマッチしないようにしたい
        $pettern = '/^( ?(?:&gt;|＞)* ?)?([1-9]\d{0,3})(?=\\D|$)/';
        $name && $name = preg_replace_callback($pettern, array($this, 'quote_res_callback'), $name, 1);
        }
        
        // ふしあなさんとか？
        $name = preg_replace('~</b>(.+?)<b>~', '<font color="#777777">$1</font>', $name);
        
        //(strlen($name) > 0) and $name = $name . " "; // 文字化け回避
        $name = StrSjis::fixSjis($name);
        
        if ($nameID) {
            $name = $name . $nameID;
        }
        
        return $name;
    }

    
    /**
     * datのレスメッセージをHTML表示用メッセージに変換して返す
     *
     * @access  private
     * @param   string    $msg
     * @param   integer   $resnum  レス番号
     * @param   ref bool  $has_aa  AAを含んでいるかどうか。この渡し方はイマイチぽ。レス単位でオブジェクトにした方がいいかな。
     * @return  string  HTML
     */
    function transMsg($msg, $resnum, &$has_aa)
    {
        global $_conf;
        global $res_filter, $word_fm;
        
        $this->str_to_link_rest = $this->str_to_link_limit;
        
        $ryaku = false;
        
        // 2ch旧形式のdat
        if ($this->thread->dat_type == "2ch_old") {
            $msg = str_replace('＠｀', ',', $msg);
            $msg = preg_replace('/&amp([^;])/', '&$1', $msg);
        }

        // >>1のリンクをいったん外す
        // <a href="../test/read.cgi/accuse/1001506967/1" target="_blank">&gt;&gt;1</a>
        $msg = preg_replace('{<[Aa] .+?>(&gt;&gt;[1-9][\\d\\-]*)</[Aa]>}', '$1', $msg);
        
        // AAチェック
        $has_aa = $this->detectAA($msg);
        
        // {{{ 大きさ制限
        
        // AAの強制省略。
        $aa_ryaku_flag = false;
        if ($_conf['k_aa_ryaku_size'] && strlen($msg) > $_conf['k_aa_ryaku_size'] and $has_aa == 2) {
            $aa_ryaku_flag = true;
        }
        
        if (
            !(UA::isIPhoneGroup() && !$aa_ryaku_flag)
            and empty($_GET['k_continue']) 
            and $_conf['ktai_res_size'] && strlen($msg) > $_conf['ktai_res_size'] || $aa_ryaku_flag
        ) {
            // <br>以外のタグを除去し、長さを切り詰める
            $msg = strip_tags($msg, '<br>');
            if ($aa_ryaku_flag) {
                $ryaku_size = min($_conf['k_aa_ryaku_size'], $_conf['ktai_ryaku_size']);
                $ryaku_st = 'AA略';
            } else {
                $ryaku_size = $_conf['ktai_ryaku_size'];
                $ryaku_st = '略';
            }
            $msg = mb_strcut($msg, 0, $ryaku_size);
            $msg = preg_replace('/ *<[^>]*$/i', '', $msg);

            // >>1, >1, ＞1, ＞＞1を引用レスポップアップリンク化
            $msg = preg_replace_callback(
                '/((?:&gt;|＞){1,2})([1-9](?:[0-9\\-,])*)+/',
                array($this, 'quote_res_callback'), $msg, $this->str_to_link_limit
            );
            $msg .= P2View::tagA(
                P2Util::buildQueryUri($_conf['read_php'],
                    array(
                        'host' => $this->thread->host,
                        'bbs'  => $this->thread->bbs,
                        'key'  => $this->thread->key,
                        'ls'   => $resnum,
                        'k_continue' => '1',
                        'offline' => '1',
                        UA::getQueryKey() => UA::getQueryValue()
                    )
                ),
                $ryaku_st
            );
            return $msg;
        }
        
        // }}}
        
        // 引用やURLなどをリンク
        $msg = preg_replace_callback($this->str_to_link_regex, array($this, 'link_callback'), $msg, $this->str_to_link_limit);
        
        // 2ch BEアイコン
        if (in_array($_conf['show_be_icon'], array(1, 3))) {
            $msg = preg_replace(
                '{sssp://(img\\.2ch\\.net/ico/[\\w\\d()\\-]+\\.[a-z]+)}',
                '<img src="http://$1" border="0">', $msg
            );
        }
        
        return $msg;
    }

    /**
     * AA判定
     *
     * @return  integer  0:反応なし, 1:弱反応, 2:強反応（AA略）
     */
    function detectAA($s)
    {
        global $_conf;
        
        // AA によく使われるパディング
        $regexA = '　{3}|(?: 　){2}';

        // 罫線
        // [\u2500-\u257F]
        //var $regexB = '[\\x{849F}-\\x{84BE}]{5}';
        $regexB = '[─-╂■]{4}';

        // Latin-1,全角スペースと句読点,ひらがな,カタカナ,半角・全角形 以外の同じ文字が3つ連続するパターン
        // Unicode の [^\x00-\x7F\x{2010}-\x{203B}\x{3000}-\x{3002}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{FF00}-\x{FFEF}]
        // をベースに SJIS に作り直してあるが、若干の違いがある。
        //$regexC = '([^\\x00-\\x7F\\xA1-\\xDF　、。，．：；０-ヶー～・…※！？＃＄％＆＊＋／＝])\\1\\1';
        $regexC = '([^\\x00-\\x7F\\xA1-\\xDF　、。，．：；０-ヶー～・…※！？＃＄％＆＊＋／＝]|[_,:;\'])\\1\\1';
        
        //$re = '(?:' . $this->regexA . '|' . $this->regexB . '|' . $this->regexC . ')';
        
        $level = 0;
        
        // AA略の対象とする最低行数（3行を超えるもののみ省略する）
        $aa_ryaku = false;
        if (preg_match("/^(.+<br>){3}./", $s)) {
            $aa_ryaku = true;
        }
        
        if (mb_ereg($regexA, $s)) {
            $level = 1;
        }
        
        // AA略しないならここまで
        if (!$_conf['k_aa_ryaku_size'] or !$aa_ryaku) {
            return $level;
        }
        
        if ($level && mb_ereg($regexC, $s)) {
            return 2;
        }

        if (mb_ereg($regexB, $s)) {
            return 2;
        }

        return $level;
    }
    
    // {{{ コールバックメソッド

    /**
     * リンク対象文字列の種類を判定して対応した関数/メソッドに渡して処理する
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
            $s['link']  = $s[1];
            $s['quote'] = $s[5];
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
            if (strstr($s[7], '-')) {
                return $this->quote_res_range_callback(array($s['quote'], $s[6], $s[7]));
            }
            return preg_replace_callback(
                '/((?:&gt;|＞)+ ?)?([1-9]\\d{0,3})(?=\\D|$)/',
                array($this, 'quote_res_callback'), $s['quote'], $this->str_to_link_rest
            );

        // http or ftp のURL
        } elseif ($s['url']) {
            if ($s[9] == 'ftp') {
                return $s[0];
            }
            $url  = preg_replace('/^t?(tps?)$/', 'ht$1', $s[9]) . '://' . $s[10];
            $html = $s['url'];

        // ID
        } elseif ($s['id'] && $_conf['flex_idpopup']) { // && $_conf['flex_idlink_k']
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
        foreach ($this->url_handlers as $handler) {
            if (isset($handler['this'])) {
                if (FALSE !== ($linkHtml = call_user_func(array($this, $handler['this']), $url, $purl, $html))) {
                    return $linkHtml;
                }
            } elseif (isset($handler['class']) && isset($handler['method'])) {
                if (FALSE !== ($linkHtml = call_user_func(array($handler['class'], $handler['method']), $url, $purl, $html))) {
                    return $linkHtml;
                }
            } elseif (isset($handler['function'])) {
                if (FALSE !== ($linkHtml = call_user_func($handler['function'], $url, $purl, $html))) {
                    return $linkHtml;
                }
            }
        }

        return $html;
    }

    /**
     * 携帯用外部URL変換
     *
     * @access  private
     * @return  string  HTML
     */
    function ktai_exturl_callback($s)
    {
        global $_conf;
        
        $url = $s[1];
        /*
        // 通勤ブラウザ
        $tsukin_link_ht = '';
        if ($_conf['k_use_tsukin']) {
            $tsukin_url = 'http://www.sjk.co.jp/c/w.exe?y=' . urlencode($url);
            if ($_conf['through_ime']) {
                $tsukin_url = P2Util::throughIme($tsukin_url);
            }
            $tsukin_link_ht = '<a href="' . hs($tsukin_url) . '">通</a>';
        }
        */
        
        // iPhone用　別窓変換。通勤ブラウザを書き換え
        $tsukin_link_ht = '';
        if ($_conf['k_use_tsukin']) {
            $tsukin_link_ht = P2Util::tagA(
                $_conf['through_ime'] ? P2Util::throughIme($url) : $url,
                hs('窓'),
                array('target' => '_blank')
            );
        }

        // jigブラウザWEB http://bwXXXX.jig.jp/fweb/?_jig_=
        $jig_link_ht = '';
        /*
        $jig_url = 'http://bw5032.jig.jp/fweb/?_jig_=' . urlencode($url);
        if ($_conf['through_ime']) {
            $jig_url = P2Util::throughIme($jig_url);
        }
        $jig_link_ht = '<a href="' . hs($jig_url) . '">j</a>';
        */
        
        $sepa = '';
        if ($tsukin_link_ht && $jig_link_ht) {
            $sepa = '|';
        }
        
        $ext_pre_ht = '';
        if ($tsukin_link_ht || $jig_link_ht) {
            $ext_pre_ht = '(' . $tsukin_link_ht . $sepa . $jig_link_ht . ')';
        }
        
        if ($_conf['through_ime']) {
            $url = P2Util::throughIme($url);
        }
        $r = $ext_pre_ht . '<a href="' . hs($url) . '">' . $s[2] . '</a>';

        return $r;
    }

    /**
     * 引用変換
     *
     * @access  private
     * @return  string  HTML
     */
    function quote_res_callback($s)
    {
        global $_conf;
        
        list($full, $qsign, $appointed_num) = $s;
        
        if ($appointed_num == '-') {
            return $s[0];
        }
        $qnum = intval($appointed_num);
        if ($qnum < 1 || $qnum > $this->thread->rescount) {
            return $s[0];
        }

        $read_url = "{$_conf['read_php']}?host={$this->thread->host}&bbs={$this->thread->bbs}&key={$this->thread->key}&offline=1&ls={$appointed_num}&b={$_conf['b']}";
        $read_url_hs = hs($read_url);
        //iPhone　レスポップアップ用に追加
        $read_on_rpop = " onmouseover=\"showResPopUp('q{$qnum}of{$this->thread->key}',event)\"";
        return "<a href=\"{$read_url_hs}\"{$read_on_rpop}>{$qsign}{$appointed_num}</a>";
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

        list($from, $to) = explode('-', $appointed_num);
        if (!$from) {
            $from = 1;
        } elseif ($from < 1 || $from > $this->thread->rescount) {
            return $s[0];
        }
        // read.phpで表示範囲を判定するので冗長ではある
        if (!$to) {
            $to = min($from + $_conf['k_rnum_range'] - 1, $this->thread->rescount);
        } else {
            $to = min($to, $from + $_conf['k_rnum_range'] - 1, $this->thread->rescount);
        }

        $read_url = "{$_conf['read_php']}?host={$this->thread->host}&bbs={$this->thread->bbs}&key={$this->thread->key}&offline=1&ls={$from}-{$to}&b={$_conf['b']}";
        $read_url_hs = hs($read_url);
        return "<a href=\"{$read_url_hs}\">{$qsign}{$appointed_num}</a>";
    }

    /**
     * IDフィルタリングリンク変換
     *
     * @access  private
     * @return  string  HTML
     */
    function idfilter_callback($s)
    {
        global $_conf;

        $idstr = $s[0]; // ID:xxxxxxxxxx
        $id = $s[1];    // xxxxxxxxxx
        $idflag = '';   // 携帯/PC識別子
        // IDは8桁または10桁(+携帯/PC識別子)と仮定して
        /*
        if (strlen($id) % 2 == 1) {
            $id = substr($id, 0, -1);
            $idflag = substr($id, -1);
        } elseif (isset($s[2])) {
            $idflag = $s[2];
        }
        */
        
        $filter_url = "{$_conf['read_php']}?host={$this->thread->host}&bbs={$this->thread->bbs}&key={$this->thread->key}&ls=all&offline=1&idpopup=1&field=id&method=just&match=on&word=" . rawurlencode($id) . '&b=' . $_conf['b'];
        $filter_url_hs = hs($filter_url);
        
        if (isset($this->thread->idcount[$id]) && $this->thread->idcount[$id] > 0) {
            $num_ht = '(' . "<a href=\"{$filter_url_hs}\">" . $this->thread->idcount[$id] . '</a>)';
        } else {
            return $idstr;
        }

        return "{$idstr}{$num_ht}";
    }

    // }}}
    // {{{ link_callback()から呼び出されるURL書き換えメソッド

    // これらのメソッドは引数が処理対象パターンに合致しないとFALSEを返し、
    // link_callback()はFALSEが返ってくると$url_handlersに登録されている次の関数/メソッドに処理させようとする。

    /**
     * URLリンク
     *
     * @access  private
     * @return  string|false  HTML
     */
    function plugin_linkURL($url, $purl, $html)
    {
        global $_conf;

        if (isset($purl['scheme'])) {
            // 携帯用外部URL変換
            if ($_conf['k_use_tsukin']) {
                return $this->ktai_exturl_callback(array('', $url, $html));
            }
            // ime
            if ($_conf['through_ime']) {
                $link_url = P2Util::throughIme($url);
            } else {
                $link_url = $url;
            }
            return sprintf(
                '<a href="%s">%s</a>',
                hs($link_url), $html
            );
        }
        return FALSE;
    }

    /**
     * 2ch bbspink 板リンク
     *
     * @access  private
     * @return  string|false  HTML
     */
    function plugin_link2chSubject($url, $purl, $html)
    {
        global $_conf;

        if (preg_match('{^http://(\\w+\\.(?:2ch\\.net|bbspink\\.com))/([^/]+)/$}', $url, $m)) {
            $subject_url = "{$_conf['subject_php']}?host={$m[1]}&bbs={$m[2]}&b={$_conf['b']}";
            return sprintf(
                '<a href="%s">%s</a> [<a href="%s">板をp2で開く</a>]',
                hs($url), $html, hs($subject_url)
            );
        }
        return FALSE;
    }

    /**
     * 2ch bbspink スレッドリンク
     *
     * @access  private
     * @return  string|false  HTML
     */
    function plugin_link2ch($url, $purl, $html)
    {
        global $_conf;

        if (preg_match('{^http://(\\w+\\.(?:2ch\\.net|bbspink\\.com))/test/read\\.cgi/([^/]+)/([0-9]+)(?:/([^/]+)?)?$}', $url, $m)) {
            $ls = isset($m[4]) ? $m[4] : null;
            $read_url = "{$_conf['read_php']}?host={$m[1]}&bbs={$m[2]}&key={$m[3]}&ls={$ls}";
            $read_url_hs = hs($read_url);
            return "<a href=\"{$read_url_hs}{$_conf['k_at_a']}\">{$html}</a>";
        }
        return FALSE;
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
            $read_url_hs = hs($read_url);
            return "<a href=\"{$read_url_hs}{$_conf['k_at_a']}\">{$html}</a>";
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

        if (preg_match('{^http://((\\w+\\.machibbs\\.com|\\w+\\.machi\\.to|jbbs\\.livedoor\\.(?:jp|com)|jbbs\\.shitaraba\\.com)(/\\w+)?)/bbs/read\\.(?:pl|cgi)\\?BBS=(\\w+)(?:&amp;|&)KEY=([0-9]+)(?:(?:&amp;|&)START=([0-9]+))?(?:(?:&amp;|&)END=([0-9]+))?(?=&|$)}', $url, $m)) {
            $read_url = "{$_conf['read_php']}?host={$m[1]}&bbs={$m[4]}&key={$m[5]}";
            if ($m[6] || $m[7]) {
                $read_url .= "&ls={$m[6]}-{$m[7]}";
            }
            $read_url_hs = hs($read_url);
            return "<a href=\"{$read_url_hs}{$_conf['k_at_a']}\">{$html}</a>";
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

        if (preg_match('{^http://(jbbs\\.livedoor\\.(?:jp|com)|jbbs\\.shitaraba\\.com)/bbs/read\\.cgi/(\\w+)/(\\d+)/(\\d+)(?:/((\\d+)?-(\\d+)?|[^/]+)|/?)$}', $url, $m)) {
            $read_url = "{$_conf['read_php']}?host={$m[1]}/{$m[2]}&bbs={$m[3]}&key={$m[4]}&ls={$m[5]}";
            $read_url_hs = hs($read_url);
            return "<a href=\"{$read_url_hs}{$_conf['k_at_a']}\">{$html}</a>";
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
            $bbs = $matches[2];
            $key = $matches[3];
            $ls = $matches[4];
            
            $read_url = "{$_conf['read_php']}?host={$host}&bbs={$bbs}&key={$key}&ls={$ls}";
            $read_url_hs = hs($read_url);
            
            return "<a href=\"{$read_url_hs}{$_conf['k_at_a']}\">{$html}</a>";
        }
        return FALSE;
    }
    
    /**
     * 画像ポップアップ変換
     *
     * @access  private
     * @return  string|false  HTML
     */
/*iPhone用にサムネイルにしてみる*/
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
            //$picto_url = 'http://pic.to/'.$purl['host'].$purl['path'];
            $picto_url = 'http://'.$purl['host'].$purl['path'];
            //書き換えどころ　080728
            $picto_tag = '<a href="'.$picto_url.'" target="_blank"><img src="'.$url.'"></a> ';
            if ($_conf['through_ime']) {
                $link_url  = P2Util::throughIme($url);
                $picto_url = P2Util::throughIme($picto_url);
            } else {
                $link_url = $url;
            }
            return "{$picto_tag}<a href=\"{$link_url}\">{$str}</a>";// {$str}　→ URL
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
   //iPhone用にサムネイル表示
    function plugin_linkYouTube($url, $purl, $str)
    {
        global $_conf;

        // http://www.youtube.com/watch?v=Mn8tiFnAUAI
        if (preg_match('{^http://jp\\.youtube\\.com/watch\\?v=([0-9a-zA-Z_-]+)}', $url, $m)||preg_match('{^http://jp\\.youtube\\.com/watch\\?v=([0-9a-zA-Z_-]+)}', $url, $m)) {
            $url = P2Util::throughIme($url);
            return <<<EOP
<a href="youtube:{$m[1]}"><img src="http://i.ytimg.com/vi/{$m[1]}/default.jpg">{$str}</a><br>
EOP;
        }
        return FALSE;
    }
    // }}}

    /**
     * ニコニコ動画変換プラグイン
     *
     * @access  private
     * @return  string|false
    */
//iPhone用に改造
// iflame でも表示できるがフッタと重なった時に不具合あり
//画像サムネイルのみ表示
    function plugin_linkNicoNico($url, $purl, $str)
    {
        global $_conf;

        // http://www.nicovideo.jp/watch?v=utbrYUJt9CSl0
        // http://www.nicovideo.jp/watch/utvWwAM30N0No
/*
<div style="width:318px; border:solid 1px #CCCCCC;"><iframe src="http://www.nicovideo.jp/thumb?v=utvWwAM30N0No" width="100%" height="198" scrolling="no" border="0" frameborder="0"></iframe></div>
*/
        if (preg_match('{^http://www\\.nicovideo\\.jp/watch(?:/|(?:\\?v=))([0-9a-zA-Z_-]+)}', $url, $m)) {
            //$url = P2Util::throughIme($url);
            //$url_hs = hs($url);
            $id = $m[1];
            $ids = str_replace( 'sm', '',$id);
            $ids = str_replace( 'nm', '',$ids);
return <<<EOP
<a href="mailto:?subject=rep2iPhone からニコニコ&body=http:%2F%2Fwww.nicovideo.jp%2Fwatch%2F{$id}"><img class="nico" src="http://tn-skr.smilevideo.jp/smile?i={$ids}"></a>
<a href="$url" target="_blank">{$str}</a>
EOP;
        }
        return FALSE;
    }
}
