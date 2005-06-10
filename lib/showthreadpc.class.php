<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    p2 - スレッドを表示する クラス PC用
*/

require_once (P2_LIBRARY_DIR . '/showthread.class.php');
require_once (P2EX_LIBRARY_DIR . '/expack_loader.class.php');

ExpackLoader::loadActiveMona();
ExpackLoader::loadImageCache();
ExpackLoader::loadLiveView();

class ShowThreadPc extends ShowThread {

    var $quote_res_nums_checked; // ポップアップ表示されるチェック済みレス番号を登録した配列
    var $quote_res_nums_done; // ポップアップ表示される記録済みレス番号を登録した配列
    var $quote_check_depth; // レス番号チェックの再帰の深さ checkQuoteResNums()

    var $activemona; // アクティブモナークラスのインスタンス
    var $am_enabled = FALSE;
    var $am_aaryaku = FALSE;

    var $thumbnailer; // サムネイル作成クラスのインスタンス
    var $img_memo; // DBの画像情報に付加するメモ（UTF-8エンコードしたスレタイ）
    var $img_memo_query;

    var $lv_enabled = FALSE; // 実況表示フラグ
    var $arraycleaner; // 配列処理クラスのインスタンス

    var $asyncObjName;  // 非同期読み込み用JavaScriptオブジェクト名
    var $spmObjName;    // スマートポップアップメニュー用JavaScriptオブジェクト名

    /**
     * コンストラクタ (PHP4 style)
     */
    function ShowThreadPc(&$aThread)
    {
        $this->__construct($aThread);
    }

    /**
     * コンストラクタ (PHP5 style)
     */
    function __construct(&$aThread)
    {
        parent::__construct($aThread);

        global $_conf, $_exconf;

        // URL書き換えハンドラを登録
        $this->url_handlers = array(
            array('this' => 'plugin_link2ch'),
            array('this' => 'plugin_linkMachi'),
            array('this' => 'plugin_linkJBBS'),
            array('this' => 'plugin_link2chKako'),
            array('this' => 'plugin_link2chSubject'),
        );
        if (P2_IMAGECACHE_AVAILABLE == 2) {
            $this->url_handlers[] = array('this' => 'plugin_imageCache2');
        } elseif ($_conf['preview_thumbnail']) {
            $this->url_handlers[] = array('this' => 'plugin_viewImage');
        }
        $this->url_handlers[] = array('this' => 'plugin_linkURL');

        // サムネイル表示制限数を設定
        if (!isset($GLOBALS['pre_thumb_unlimited']) || !isset($GLOBALS['pre_thumb_limit'])) {
            if (isset($_conf['pre_thumb_limit']) && $_conf['pre_thumb_limit'] >= 0) {
                $GLOBALS['pre_thumb_limit'] = $_conf['pre_thumb_limit'];
                $GLOBALS['pre_thumb_unlimited'] = FALSE;
            } else {
                $GLOBALS['pre_thumb_limit'] = NULL; // ヌル値だとisset()はFALSEを返す
                $GLOBALS['pre_thumb_unlimited'] = TRUE;
            }
        }
        $GLOBALS['pre_thumb_ignore_limit'] = FALSE;

        // アクティブモナー初期化
        if (P2_ACTIVEMONA_AVAILABLE) {
            ExpackLoader::initActiveMona($this);
        }

        // ImageCache2初期化
        if (P2_IMAGECACHE_AVAILABLE == 2) {
            ExpackLoader::initImageCache($this);
        }

        // 実況モード初期化
        if ($_exconf['liveView']['*'] == 1 || ($_exconf['liveView']['*'] == 2 &&
            (preg_match('/^live\d+\.2ch\.net/', $this->thread->host) || $this->thread->bbs == 'liveplus'))
        ) {
            ExpackLoader::initLiveView($this);
        }

        // 非同期レスポップアップ・SPM初期化
        $jsObjId = md5($this->thread->keydat);
        $this->asyncObjName = 'asp_' . $jsObjId;
        $this->spmObjName = 'spm_' . $jsObjId;

    }

    /**
     * ■DatをHTMLに変換表示する
     */
    function datToHtml()
    {
        if (!$this->thread->resrange) {
            echo '<b>p2 error: {$this->resrange} is FALSE at datToHtml()</b>';
            return false;
        }

        $start = $this->thread->resrange['start'];
        $to = $this->thread->resrange['to'];
        $nofirst = $this->thread->resrange['nofirst'];

        $status_title = htmlspecialchars($this->thread->itaj).' / '.$this->thread->ttitle_hd;
        $status_title = str_replace("'", "\'", $status_title);
        $status_title = str_replace('"', "\'\'", $status_title);
        echo "<dl onmouseover=\"window.top.status='{$status_title}';\">";

        // まず 1 を表示
        if (!$nofirst) {
            echo $this->transRes(1);
        }

        for ($i = $start; $i <= $to; $i++) {
            if (!$nofirst && $i == 1) {
                continue;
            }
            if (!isset($this->pDatLines[$i])) {
                $this->thread->readnum = $i-1;
                break;
            }
            echo $this->transRes($i);
            flush();
        }
        echo "</dl>\n";

        // $s2e = array($start, $i-1);
        // return $s2e;
        return true;
    }


    /**
     * ■ DatレスをHTMLレスに変換する
     *
     * 引数 - レス番号
     */
    function transRes($i)
    {
        global $_conf, $_exconf;
        global $STYLE, $mae_msg, $res_filter, $word_fm;
        global $ngaborns_hits;

        $tores = '';
        $rpop = '';
        if (basename($_SERVER['SCRIPT_NAME']) != basename($_conf['read_new_php'])) {
            $resAnchor = " id=\"r{$i}\"";
        } else {
            $resAnchor = '';
        }
        $resID = $i . 'of' . $this->thread->key;
        $resBodyID = 'rb' . $resID;

        $name = $this->pDatLines[$i]['name'];
        $mail = $this->pDatLines[$i]['mail'];
        $date_id = $this->transDateId($i);
        $msg = $this->pDatLines[$i]['msg'];

        // {{{ transRes - フィルタリング・あぼーん・NG・AAチェック

        // フィルタリング
        if (!empty($_conf['filtering']) && !$this->filterMatch($i)) {
            return '';
        }

        // あぼーんチェック
        $aborned_res = "<dt{$resAnchor} class=\"aborned\"><span>&nbsp;</span></dt>\n"; // 名前
        $aborned_res .= "<!-- <dd class=\"aborned\">&nbsp;</dd> -->\n"; // 内容
        if (($aborn_hit_field = $this->checker->abornCheck($i)) !== FALSE) {
            $ngaborns_hits["aborn_{$aborn_hit_field}"]++;
            return $aborned_res;
        }

        // NGチェック
        $ng_fields = $this->checker->ngCheck($i);
        foreach ($ng_fields as $ng_hit_field => $ng_hit_value) {
            $ngaborns_hits["ng_{$ng_hit_field}"]++;
        }

        // AAチェック
        if ($this->am_aaryaku && $this->activemona->detectAA($msg)) {
            if ($this->am_aaryaku == 2) {
                return $aborned_res;
            } else {
                $ngaborns_hits['ng_aa']++;
                $ng_fields['aa'] = TRUE;
            }
        }

        // }}}

        //=============================================================
        // レスをポップアップ表示
        //=============================================================
        if ($_conf['quote_res_view'] && !$_exconf['etc']['async_respop']) {
            $this->quote_check_depth = 0;
            $quote_res_nums = $this->checkQuoteResNums($i, $name, $msg);

            foreach ($quote_res_nums as $rnv) {
                if (!isset($this->quote_res_nums_done[$rnv])) {
                    $ds = $this->qRes($rnv);
                    $onPopUp_at = " onmouseover=\"showResPopUp('q{$rnv}of{$this->thread->key}',event)\" onmouseout=\"hideResPopUp('q{$rnv}of{$this->thread->key}')\"";
                    $rpop .=  "<dd id=\"q{$rnv}of{$this->thread->key}\" class=\"respopup\"{$onPopUp_at}><i>" . rtrim($ds) . "</i></dd>\n";
                    $this->quote_res_nums_done[$rnv] = true;
                }
            }
        }

        // transRes - まとめて出力
        //=============================================================
        // まとめて出力
        //=============================================================

        $name = $this->transName($name); // 名前HTML変換
        $msg = "<div id=\"{$resBodyID}\">" . $this->transMsg($msg, $i) . "</div>";

        // {{{ transRes - ActiveMona
        // アクティブモナー
        if ($this->am_enabled && $_exconf['aMona']['*']) {
            $mona = $this->activemona->transAM($msg, $resBodyID, $this->thread->bbs);
        } else {
            $mona = '';
        }
        // }}}

        // {{{ transRes - NGワード変換

        // NGブロック用IDを設定
        // AA略
        if (isset($ng_fields['aa'])) {
            $ng_msg_type = $this->am_aaryaku_msg;
            $ng_msg_id = 'aang' . $ngaborns_hits['ng_aa'];

        // 行数が多すぎかつ実況モードでない（実況モードは別に設定項目がある）
        } elseif (isset($ng_fields['lines']) && !$this->lv_enabled) {
            $ng_msg_type = $ng_fields['lines'] . 'lines';
            $ng_msg_id = 'ngl' . $ngaborns_hits['ng_lines'];

        // メッセージにNGワードを含む
        } elseif (isset($ng_fields['msg'])) {
            $ng_msg_type = 'NGワード：' . $ng_fields['msg'];
            $ng_msg_id = 'ngmsg' . $ngaborns_hits['ng_msg'];

        // あぼーんレスを参照
        } elseif (isset($ng_fields['aborn'])) {
            $ng_msg_type = '連鎖NG(あぼーん)：&gt;&gt;' . $ng_fields['aborn'];
            $ng_msg_id = 'nga' . $ngaborns_hits['ng_aborn'];

        // NGレスを参照
        } elseif (isset($ng_fields['chain'])) {
            $ng_msg_type = '連鎖NG：&gt;&gt;' . $ng_fields['chain'];
            $ng_msg_id = 'ngc' . $ngaborns_hits['ng_chain'];

        // 名前にNGワードを含む
        } elseif (isset($ng_fields['name'])) {
            $ng_msg_id = 'ngn' . $ngaborns_hits['ng_name'];

        // メールにNGワードを含む
        } elseif (isset($ng_fields['mail'])) {
            $ng_msg_id = 'ngm' . $ngaborns_hits['ng_mail'];

        // IDにNGワードを含む
        } elseif (isset($ng_fields['id'])) {
            $ng_msg_id = 'ngid' . $ngaborns_hits['ng_id'];
        }

        $ng_format = "<s class=\"ngword\" onmouseover=\"document.getElementById('%s').style.display = 'block';\">%s</s>";

        // NGメッセージ変換
        if (isset($ng_msg_type)) {
            $show_ngmsg = sprintf($ng_format, $ng_msg_id, $ng_msg_type);
        } else {
            $show_ngmsg = '';
        }
        if (isset($ng_msg_id)) {
            $msg = "{$show_ngmsg}<div id=\"{$ng_msg_id}\" style=\"display:none;\">{$msg}</div>";
        }

        // NGネーム変換
        if (isset($ng_fields['name'])) {
            $name = sprintf($ng_format, $ng_msg_id, $name);
        }

        // NGメール変換
        if (isset($ng_fields['mail'])) {
            $mail = sprintf($ng_format, $ng_msg_id, $mail);
        }

        // NGID変換
        if (isset($ng_fields['id'])) {
            $date_id = sprintf($ng_format, $ng_msg_id, $date_id);
        }

        // }}}

        /*
        // 「ここから新着」画像を挿入 ========================
        if ($i == $this->thread->readnum + 1) {
            $tores .= "\n<div><img src=\"img/image.png\" alt=\"新着レス\" border=\"0\" vspace=\"4\"></div>\n";
        }
        */

        // {{{ transRes - SPM
        // スマートポップアップメニュー
        if ($_exconf['spm']['*'] == 2) {
            $spmEventHandler = " onclick=\"showSPM({$this->spmObjName},{$i},'{$resBodyID}',event);return false\"";
        } elseif ($_exconf['spm']['*']) {
            $spmEventHandler = " onmouseover=\"showSPM({$this->spmObjName},{$i},'{$resBodyID}',event)\" onmouseout=\"hideResPopUp('{$this->spmObjName}_spm')\"";
        } else {
            $spmEventHandler = '';
        }
        // }}}

        // {{{ transRes - Bookmark
        // しおりを挿入
        if ($_exconf['bookmark']['*'] && $i > 0 && $i == $this->thread->readhere) {
            $msg .= "<table id=\"readhere\"><tr><td>{$_exconf['bkmk']['marker']}</td></tr></table>";
        }
        // }}}

        // {{{ transRes - LiveView
        // 実況モード
        if ($this->lv_enabled) {
            // メッセージを分割
            $live_regex = '/^'
                . '(?P<ngword><s[^>]*?>.+?<\\/s>)?'
                . '(?P<fullbody>'
                    . '(?P<ngbegin><div id="(?P<ngid>ng[\\w\\-]+?)"[^>]*?>)?'
                    . '(?P<msgbegin><div id="(?P<msgid>rb[\\w\\-]+?)"[^>]*?>)?'
                    . '(?P<msgbody>.+?)'
                    . '(?(4)(?P<msgend><\\/div>))'
                    . '(?(2)(?P<ngend><\\/div>))'
                . ')'
                . '(?P<bkmk><table id="readhere">.+<\\/table>)?'
                . '$/';
            if (preg_match($live_regex, $msg, $live_match)) {
                // Live2ch風レンダリング
                include (P2EX_LIBRARY_DIR . '/liveview.inc.php');
                // しおりを入れ直す
                if ($live_match['bkmk']) {
                    $jikkyo_tores .= "<dd class=\"jikkyo\">{$live_match['bkmk']}</dd>\n";
                }
                return $jikkyo_tores;
            } else {
                // パターンにマッチしなかったときはエラーを表示
                $tores .= '<dd><b>実況モード error: メッセージの分割に失敗</b></dd>';
            }
        }
        // }}}

        $tores .= "<dt{$resAnchor}>";
        if ($this->thread->onthefly) {
            $GLOBALS['newres_to_show_flag'] = true;
            $tores .= "<span class=\"ontheflyresorder\">{$i}</span> ："; //番号（オンザフライ時）
        } elseif ($i > $this->thread->readnum) {
            $GLOBALS['newres_to_show_flag'] = true;
            $tores .= "<a href=\"javascript:void(0);\" class=\"newres\"{$spmEventHandler}>{$i}</a> ："; //番号（新着レス時）
        } else {
            $tores .= "<a href=\"javascript:void(0);\" class=\"resnum\"{$spmEventHandler}>{$i}</a> ："; //番号
        }
        $tores .= "<span class=\"name\"><b>{$name}</b></span>："; //名前

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

        $tores .= $date_id; // 日付とID
        $tores .= $mona; // AAボタン
        $tores .= "</dt>\n";
        $tores .= $rpop; // レスポップアップ用引用
        $tores .= "<dd style=\"margin-bottom:2em\">{$msg}</dd>\n"; // 内容

        // まとめてフィルタ色分け
        if ($word_fm && $res_filter['match'] == 'on') {
            $tores = StrCtl::filterMarking($word_fm, $tores);
        }

        // }}}

        return $tores;
    }


    /**
     * >>1 を表示する (引用ポップアップ用)
     */
    function quoteOne()
    {
        global $_conf, $_exconf;

        if (!$_conf['quote_res_view']) {
            return false;
        }

        if ($_exconf['etc']['async_respop']) {
            $rpop = '';
        } else {
            $dummy_msg = '';
            $this->quote_check_depth = 0;
            $quote_res_nums = $this->checkQuoteResNums(0, "1", $dummy_msg);
            $rpop = '';
            foreach ($quote_res_nums as $rnv) {
                if (!isset($this->quote_res_nums_done[$rnv])) {
                    $ds = '';
                    if ($this->thread->ttitle_hd) {
                        $ds = "<b>{$this->thread->ttitle_hd}</b><br><br>";
                    }
                    $ds .= $this->qRes($rnv);
                    $onPopUp_at = " onmouseover=\"showResPopUp('q{$rnv}of{$this->thread->key}',event)\" onmouseout=\"hideResPopUp('q{$rnv}of{$this->thread->key}')\"";
                    $rpop .= "<div id=\"q{$rnv}of{$this->thread->key}\" class=\"respopup\"{$onPopUp_at}><i>" . $ds . "</i></div>\n";
                    $this->quote_res_nums_done[$rnv]=true;
                }
            }
        }
        $res1['q'] = $rpop;

        $m1 = '&gt;&gt;1';
        $res1['body'] = $this->transMsg($m1, 1);
        return $res1;
    }

    /**
     * レス引用HTML
     */
    function qRes($i)
    {
        global $_conf, $_exconf, $word_fm;

        if (!Isset($this->pDatLines[$i])) {
            return false;
        }

        $name = $this->transName($this->pDatLines[$i]['name']);
        $mail = $this->pDatLines[$i]['mail'];
        $date_id = $this->transDateId($i);
        $msg = $this->pDatLines[$i]['msg'];

        $qresid = "qr{$i}of{$this->thread->key}";
        $msg = "<div id=\"{$qresid}\">" . $this->transMsg($msg, $i) . "</div>";

        // {{{ qRes - ActiveMona
        // アクティブモナー
        if ($this->am_enabled) {
            if ($_exconf['aMona']['*']) {
                $mona = $this->activemona->transAM($msg, $qresid, $this->thread->bbs);
            }
        } else {
            $mona = '';
        }
        // }}}

        // {{{ qRes - SPM
        // スマートポップアップメニュー
        if ($_exconf['spm']['*']) {
            if ($_exconf['spm']['*'] == 2) {
                $spmEventHandler = " onclick=\"showSPM({$this->spmObjName},{$i},'{$qresid}',event);return false;\"";
            } else {
                $spmEventHandler = " onmouseover=\"showSPM({$this->spmObjName},{$i},'{$qresid}',event)\" onmouseout=\"hideResPopUp('{$this->spmObjName}_spm')\"";
            }
            $i = "<a href=\"javascript:void(0);\" class=\"resnum\"{$spmEventHandler}>{$i}</a>";
        }
        // }}}

        // $toresにまとめて出力
        $tores = "{$i} ："; //番号
        $tores .= "<b>{$name}</b> ："; //名前
        if ($mail) { $tores .= $mail." ："; } //メール
        $tores .= $date_id; //日付とID
        $tores .= $mona; //AAボタン
        $tores .= "<br>";
        $tores .= $msg."<br>\n"; //内容

        // まとめてフィルタ色分け（乱暴かな？）
        if ($word_fm && $res_filter['match'] == 'on') {
            $tores = StrCtl::filterMarking($word_fm, $tores);
        }

        return $tores;
    }

    /**
     * 名前をHTML用に変換する
     */
    function transName($name)
    {
        global $_conf, $_exconf;
        $nameID = '';

        // ID付なら分解する
        if (preg_match("/(.*)(◆.*)/", $name, $matches)) {
            $name = $matches[1];
            $nameID = $matches[2];
        }

        // 数字を引用レスリンク化
        // </b>～<b> は、ホストやトリップなのでマッチさせない
        // $pettern = '/(?!<\/b>[^>]*)([1-9][0-9]{0,3})+(?![^<]*<b>)/';
        $pettern = '/^( ?(?:&gt;|＞)* ?)?([1-9]\d{0,3})(?=\\D|$)/';
        $name && $name = preg_replace_callback($pettern, array($this, 'quote_res_callback'), $name, 1);

        if ($nameID) { $name = $name . $nameID; }

        $name = $name.' '; // 文字化け回避

        return $name;
    }


    /**
     * datのレスメッセージをHTML表示用メッセージに変換する
     * string transMsg(string str)
     */
    function transMsg($msg, $mynum)
    {
        global $_conf, $_exconf;
        global $res_filter, $word_fm;
        global $pre_thumb_ignore_limit;

        // 2ch旧形式のdat
        if ($this->thread->dat_type == "2ch_old") {
            $msg = str_replace("＠｀", ",", $msg);
            $msg = preg_replace("/&amp([^;])/", "&\$1", $msg);
        }

        // Safariから投稿されたリンク中チルダの文字化け補正（厳密には文字化けとはちょっと違う）
        $msg = preg_replace('{(h?t?tp://[\\w.\\-]+/)～([\\w.\\-%]+/?)}', '$1~$2', $msg);

        // >>1のリンクをいったん外す
        // <a href="../test/read.cgi/accuse/1001506967/1" target="_blank">&gt;&gt;1</a>
        $msg = preg_replace('{<[Aa] .+?>(&gt;&gt;[1-9][\\d\\-]*)</[Aa]>}', '$1', $msg);

        // 新着レスの画像は表示制限を無視する設定なら
        if ($mynum > $this->thread->readnum && $_exconf['imgCache']['newres_ignore_limit']) {
            $pre_thumb_ignore_limit = TRUE;
        }

        // 引用やURLなどをリンク
        $msg = preg_replace_callback($this->str_to_link_regex, array($this, 'link_callback'), $msg);

        $pre_thumb_ignore_limit = FALSE;

        return $msg;
    }

    // {{{ コールバックメソッド

    /**
     * ■リンク対象文字列の種類を判定して対応した関数/メソッドに渡す
     */
    function link_callback($s)
    {
        global $_conf, $_exconf;

        // {{{ preg_replace_callback()では名前付きでキャプチャできないのでマッピング

        if (!isset($s['link'])) {
            $s['link']  = $s[1];
            $s['quote'] = $s[4];
            $s['url']   = $s[7];
            $s['id']    = $s[10];
        }

        // }}
        // {{{ マッチしたサブパターンに応じて分岐

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
            if (strstr($s[6], '-')) {
                return $this->quote_res_range_callback(array($s['quote'], $s[5], $s[6]));
            }
            return preg_replace_callback('/((?:&gt;|＞)+ ?)?([1-9]\\d{0,3})(?=\\D|$)/', array($this, 'quote_res_callback'), $s['quote']);

        // http or ftp のURL
        } elseif ($s['url']) {
            $url = preg_replace('/^t?(tps?)$/', 'ht$1', $s[8]) . '://' . $s[9];
            $str = $s['url'];

        // ID
        } elseif ($s['id'] && $_exconf['flex']['idpopup']) {
            return $this->idfilter_callback(array($s['id'], $s[11]));

        // その他（予備）
        } else {
            return strip_tags($s[0]);
        }

        // }}}
        // {{{ URLの前処理

        // ime.nuを外す
        $url = preg_replace('|^([a-z]+://)ime\\.nu/|', '$1', $url);

        // URLをパース
        $purl = @parse_url($url);
        if (!$purl || !isset($purl['host']) || !strstr($purl['host'], '.') || $purl['host'] == '127.0.0.1') {
            return $str;
        }

        // }}} 
        // {{{ URLを変換

        foreach ($this->url_handlers as $handler) {
            //if (is_array($handler)) {
                if (isset($handler['this'])) {
                    if (FALSE !== ($link = call_user_func(array($this, $handler['this']), $url, $purl, $str))) {
                        return $link;
                    }
                } elseif (isset($handler['class']) && isset($handler['method'])) {
                    if (FALSE !== ($link = call_user_func(array($handler['class'], $handler['method']), $url, $purl, $str))) {
                        return $link;
                    }
                } elseif (isset($handler['function'])) {
                    if (FALSE !== ($link = call_user_func($handler['function'], $url, $purl, $str))) {
                        return $link;
                    }
                }
            /*} elseif (is_string($handler)) {
                $function = explode('::', $handler);
                if (isset($function[1])) {
                    if ($function[0] == 'this') {
                        if (FALSE !== ($link = call_user_func(array($this, $function[1], $url, $purl, $str))) {
                            return $link;
                        }
                    } else 
                        if (FALSE !== ($link = call_user_func(array($function[0], $function[1]), $url, $purl, $str))) {
                            return $link;
                        }
                    }
                } else {
                    if (FALSE !== ($link = call_user_func($handler, $url, $purl, $str))) {
                        return $link;
                    }
                }
            }*/
        }

        // }}}

        return $str;
    }

    /**
     * ■引用変換（単独）
     */
    function quote_res_callback($s)
    {
        global $_conf, $_exconf;

        list($full, $qsign, $appointed_num) = $s;
        $qnum = intval($appointed_num);
        if ($qnum < 1 || $qnum > $this->pDatCount) {
            return $full;
        }

        $read_url = $this->read_url_base . '&amp;offline=1&amp;ls=' . $appointed_num;
        $attributes = $_conf['bbs_win_target_at'];
        $loadpopup_js = ($_exconf['etc']['async_respop']) ? "loadResPopUp({$this->asyncObjName},{$qnum});" : '';
        if ($_conf['quote_res_view']) {
            $attributes .= " onmouseover=\"{$loadpopup_js}showResPopUp('q{$qnum}of{$this->thread->key}',event)\"";
            $attributes .= " onmouseout=\"hideResPopUp('q{$qnum}of{$this->thread->key}')\"";
        }
        return "<a href=\"{$read_url}\"{$attributes}>{$qsign}{$appointed_num}</a>";
    }

    /**
     * ■引用変換（範囲）
     */
    function quote_res_range_callback($s)
    {
        global $_conf, $_exconf;

        list($full, $qsign, $appointed_num) = $s;
        if ($appointed_num == '-') {
            return $full;
        }

        $read_url = $this->read_url_base . '&amp;offline=1&amp;ls=' . $appointed_num . 'n';

        // from-toを展開して引用レスポップアップ化
        if ($_conf['quote_res_view'] && $_exconf['etc']['async_respop'] &&
            preg_match('/^([1-9]\d*)-([1-9]\d*)$/', $appointed_num, $m) &&
            $m[1] < $m[2] && $m[2] < $this->pDatLines &&
            $m[2] - $m[1] < $_exconf['etc']['async_rangepop']
        ) {
            $popId = "rp{$m[1]}to{$m[2]}of{$this->thread->key}";
            $attributes = $_conf['bbs_win_target_at'];
            $attributes .= ' onmouseover="';
            $attributes .= "makeRangeResPopUp({$this->asyncObjName},{$m[1]},{$m[2]});";
            $attributes .= "showResPopUp('{$popId}',event);";
            $attributes .= '"';
            $attributes .= " onmouseout=\"hideResPopUp('{$popId}');\"";
            return "<a href=\"{$read_url}\"{$attributes}>{$qsign}{$appointed_num}</a>";
        }

        // HTMLポップアップ
        if ($_conf['iframe_popup']) {
            $pop_url = $read_url . '&amp;renzokupop=true';
            return $this->iframe_popup(array($read_url, $pop_url), $full, $_conf['bbs_win_target_at']);
        }

        // 普通にリンク
        return "<a href=\"{$read_url}\"{$_conf['bbs_win_target_at']}>{$qsign}{$appointed_num}</a>";
    }

    /**
     * ■HTMLポップアップ変換（コールバック用インターフェース）
     */
    function iframe_popup_callback($s)
    {
        return $this->iframe_popup($s[1], $s[3], $s[2]);
    }

    /**
     * ■HTMLポップアップ変換
     */
    function iframe_popup($url, $str, $attr = '', $mode = NULL)
    {
        global $_conf, $_exconf;

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
                $attr .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
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
                $pop_img = htmlspecialchars($custom_pop_img);
                $x = $STYLE['iframe_popup_mark_width'];
                $y = $STYLE['iframe_popup_mark_height'];
            } else {
                $pop_img = 'img/pop.png';
                $y = $x = 12;
            }
            $pop_str = "<img src=\"{$pop_img}\" width=\"{$x}\" height=\"{$y}\" hspace=\"2\" vspace=\"0\" border=\"0\" align=\"top\">";
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
     * ■IDフィルタリングポップアップ変換
     */
    function idfilter_callback($s)
    {
        global $_conf, $_exconf;

        $idstr = $s[0]; // ID:xxxxxxxxxx
        $id = $s[1];    // xxxxxxxxxx

        if (isset($this->thread->idcount[$id]) && $this->thread->idcount[$id] > 0) {
            $num_ht = '('.$this->thread->idcount[$id].')';
        } else {
            return $id;
        }

        $filter_url = $this->read_url_base . '&amp;ls=all&amp;offline=1&amp;idpopup=1&amp;field=id&amp;method=just&amp;match=on&amp;word=' . rawurlencode($id);

        if ($_conf['iframe_popup']) {
            return $this->iframe_popup($filter_url, $idstr . $num_ht, $_conf['bbs_win_target_at']);
        }
        return "<a href=\"{$filter_url}\"{$_conf['bbs_win_target_at']}>{$idstr}{$num_ht}</a>";
    }

    // }}}
    // {{{ ユーティリティメソッド

    /**
     * HTMLメッセージ中の引用レスの番号を再帰チェックする
     */
    function checkQuoteResNums($res_num, $name, $msg)
    {
        // 再帰リミッタ
        if ($this->quote_check_depth > 30) {
            return array();
        } else {
            $this->quote_check_depth++;
        }

        $quote_res_nums = array();

        $name = preg_replace('/(◆.*)/', '', $name, 1);

        // 名前
        if (preg_match('/[1-9]\d*/', $name, $matches)) {
            $a_quote_res_num = (int)$matches[0];

            if ($a_quote_res_num && isset($this->pDatLines[$a_quote_res_num])) {
                $quote_res_nums[] = $a_quote_res_num;

                // 自分自身の番号と同一でなければ、
                if ($a_quote_res_num != $res_num) {
                    // チェックしていない番号を再帰チェック
                    if (!isset($this->quote_res_nums_checked[$a_quote_res_num])) {
                        $this->quote_res_nums_checked[$a_quote_res_num] = true;

                        $quote_name = $this->pDatLines[$a_quote_res_num]['name'];
                        $quote_msg = $this->pDatLines[$a_quote_res_num]['msg'];
                        $quote_res_nums = array_merge($quote_res_nums, $this->checkQuoteResNums($a_quote_res_num, $quote_name, $quote_msg) );
                    }
                }
            }
            // $name = preg_replace("/([0-9]+)/", "", $name, 1);
        }

        // >>1のリンクをいったん外す
        // <a href="../test/read.cgi/accuse/1001506967/1" target="_blank">&gt;&gt;1</a>
        $msg = preg_replace('{<[Aa] .+?>(&gt;&gt;[1-9][\\d\\-]*)</[Aa]>}', '$1', $msg);

        // echo $msg;
        if (preg_match_all('/(?:&gt;|＞)+ ?([1-9](?:[0-9\\- ,=.]|、)*)/', $msg, $out, PREG_PATTERN_ORDER)) {

            foreach ($out[1] as $numberq) {
                // echo $numberq;
                if (preg_match_all('/[1-9]\\d*/', $numberq, $matches, PREG_PATTERN_ORDER)) {

                    foreach ($matches[0] as $a_quote_res_num) {

                        // echo $a_quote_res_num;
                        $a_quote_res_num = (int)$a_quote_res_num;

                        if (!$a_quote_res_num) { break; }
                        $quote_res_nums[] = $a_quote_res_num;

                        // 自分自身の番号と同一でなければ、
                        if ($a_quote_res_num != $res_num && isset($this->pDatLines[$a_quote_res_num])) {
                        // チェックしていない番号を再帰チェック
                            if (!isset($this->quote_res_nums_checked[$a_quote_res_num])) {
                                $this->quote_res_nums_checked[$a_quote_res_num] = true;

                                $quote_name = $this->pDatLines[$a_quote_res_num]['name'];
                                $quote_msg = $this->pDatLines[$a_quote_res_num]['msg'];
                                $quote_res_nums = array_merge($quote_res_nums, $this->checkQuoteResNums($a_quote_res_num, $quote_name, $quote_msg) );
                            }
                        }

                    }

                }

            }

        }

        return array_unique($quote_res_nums);
    }

    /**
     * 日付・IDを再構築し、BEプロファイルがあればリンクする
     */
    function transDateId($resnum)
    {
        global $_conf, $_exconf;

        if (!isset($this->pDatLines[$resnum])) {
            return '';
        }
        $p = &$this->pDatLines[$resnum]['p_dateid'];

        // 日付
        if ($_exconf['etc']['datetime_rewrite']) {
            if (isset($p['timestamp'])) {
                $epoch = $p['timestamp'];
            } else {
                $epoch = $p['timestamp'] = $this->datetimeToEpoch($p['date'], $p['time']);
            }
            if ($epoch != -1) {
                $date_id = date($_exconf['etc']['datetime_format'], $epoch);
                if (strstr($_exconf['etc']['datetime_format'], '%w%')) {
                    $date_id = preg_replace('/%([0-6])%/e', '$_exconf["etc"]["datetime_weekday"][$1]', $date_id);
                }
            } else {
                $date_id = $p['date'].' '.$p['time'];
            }
        } else {
            $date_id = $p['date'].' '.$p['time'];
        }

        // ID
        if (isset($p['id'])) {
            if ($_exconf['flex']['idpopup'] == 1 && $this->thread->idcount[$p['id']] > 1) {
                $date_id .= ' '. $this->idfilter_callback(array('ID:'.$p['id'], $p['id']));
            } else {
                $date_id .= ' ID:' . $p['id'];
            }
            if (isset($p['idopt'])) {
                $date_id .= $p['idopt'];
            }
        }

        // BE
        if (isset($p['be'])) {
            $be_prof_ref = rawurlencode('http://' . $this->thread->host . '/test/read.cgi/' . $this->thread->bbs . '/' . $this->thread->key . '/' . $GLOBALS['ls']);
            $be_prof_url = 'http://be.2ch.net/test/p.php?i=' . $p['beid'] . '&u=d:' . $be_prof_ref;
            $be_prof_lv  = 'Lv.' . $p['belv'];
            if ($_conf['iframe_popup']) {
                $be_prof_link = $this->iframe_popup($be_prof_url, $be_prof_lv, $_conf['ext_win_target_at']);
            } else {
                $be_prof_link = "<a href=\"{$be_prof_url}\"{$_conf['ext_win_target_at']}>{$be_prof_lv}</a>";
            }
            $date_id .= ' ' . $be_prof_link;
        }

        return $date_id;
    }

    /**
     * 画像をHTMLポップアップ&ポップアップウインドウサイズに合わせる
     */
    function imageHtmpPopup($img_url, $img_tag, $link_str)
    {
        global $_conf, $_exconf;

        if ($_exconf['fitImage']['*']) {
            $fimg_url = str_replace('&amp;', '&', $img_url);
            $popup_url = "fitimage.php?url=" . rawurlencode($fimg_url);
        } else {
            $popup_url = $img_url;
        }

        $pops = ($_conf['iframe_popup'] == 1) ? $img_tag . $link_str : array($link_str, $img_tag);
        return $this->iframe_popup(array($img_url, $popup_url), $pops, $_conf['ext_win_target_at']);
    }

    /**
     * レスポップアップを非同期モードに加工する
     */
    function respop_to_async($str)
    {
        $respop_regex = '/(onmouseover)=\"(showResPopUp\(\'(q(\d+)of\d+)\',event\).*?)\"/';
        $respop_replace = '$1="loadResPopUp(' . $this->asyncObjName . ', $4);$2"';
        return preg_replace($respop_regex, $respop_replace, $str);
    }

    /**
     * 非同期読み込みで利用するJavaScriptオブジェクトを出力する
     */
    function printASyncObjJs()
    {
        global $_conf, $_exconf;
        static $done = array();

        if (isset($done[$this->asyncObjName])) {
            return;
        }
        $done[$this->asyncObjName] = TRUE;

        echo <<<EOJS
<script type="text/javascript">
var {$this->asyncObjName} = {
    host:"{$this->thread->host}", bbs:"{$this->thread->bbs}", key:"{$this->thread->key}",
    readPhp:"{$_conf['read_php']}", readTarget:"{$_conf['bbs_win_target']}"
};
</script>\n
EOJS;
    }

    /**
     * スマートポップアップメニューを生成するJavaScriptコードを出力する
     */
    function printSPMObjJs()
    {
        global $_conf, $_exconf;
        global $STYLE;
        static $done = array();

        if (isset($done[$this->spmObjName])) {
            return;
        }
        $done[$this->spmObjName] = true;

        $ttitle_en = base64_encode($this->thread->ttitle);
        $ttitle_urlen = rawurlencode($ttitle_en);
        $isClickOnOff = ($_exconf['spm']['*'] == 2) ? 'true' : 'false';

        if ($_exconf['spm']['flex_target'] == '' || $_exconf['spm']['flex_target'] == 'read') {
            $_exconf['spm']['flex_target'] = '_self';
        }

        $motothre_url = str_replace('"', '\\"', $this->thread->getMotoThread());
        $ttitle = str_replace('"', '\\"', $this->thread->ttitle);

        echo <<<EOJS
<script type="text/javascript">
// 主なスレッド情報と各種設定をプロパティに持つオブジェクト
var {$this->spmObjName} = {
    objName:"{$this->spmObjName}", rc:"{$this->thread->rescount}",
    title:"{$ttitle}",
    ttitle_en:"{$ttitle_urlen}",
    url:"{$motothre_url}",
    host:"{$this->thread->host}", bbs:"{$this->thread->bbs}", key:"{$this->thread->key}",
    spmHeader:"{$_exconf['spm']['header']}",
    spmOption:[{$_exconf['spm']['confirm']},{$_exconf['spm']['kokores']},{$_exconf['bookmark']['*']},{$_exconf['spm']['aborn']},{$_exconf['spm']['ng']},{$_exconf['spm']['with_aMona']},{$_exconf['spm']['with_flex']},{$_exconf['spm']['fortune']}]
};
//スマートポップアップメニュー生成
var spmFlexTarget = "{$_exconf['spm']['flex_target']}";
makeSPM({$this->spmObjName},{$isClickOnOff});
</script>\n
EOJS;
    }

    // }}}
    // {{{ link_callback()から呼び出されるURL書き換えメソッド

    // これらのメソッドは引数が処理対象パターンに合致しないとFALSEを返し、
    // link_callback()はFALSEが返ってくると$url_handlersに登録されている次の関数/メソッドに処理させようとする。

    /**
     * URLリンク
     */
    function plugin_linkURL($url, $purl, $str)
    {
        global $_conf, $_exconf;

        if (isset($purl['scheme'])) {
            // ime
            if ($_conf['through_ime']) {
                $link_url = P2Util::throughIme($url);
            } else {
                $link_url = $url;
            }

            // HTMLポップアップ
            if ($_conf['iframe_popup'] && preg_match('/https?/', $purl['scheme'])) {
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
     * 2ch bbspink  板リンク
     */
    function plugin_link2chSubject($url, $purl, $str)
    {
        global $_conf, $_exconf;

        if (preg_match('{^http://(\\w+\\.(?:2ch\\.net|bbspink\\.com))/([^/]+)/$}', $url, $m)) {
            $subject_url = "{$_conf['subject_php']}?host={$m[1]}&amp;bbs={$m[2]}";
            return "<a href=\"{$url}\" target=\"subject\">{$str}</a> [<a href=\"{$subject_url}\" target=\"subject\">板をp2で開く</a>]";
        }
        return FALSE;
    }

    /**
     * 2ch bbspink  スレッドリンク
     */
    function plugin_link2ch($url, $purl, $str)
    {
        global $_conf, $_exconf;

        if (preg_match('{^http://(\\w+\\.(?:2ch\\.net|bbspink\\.com))/test/read\\.cgi/([^/]+)/([0-9]+)(?:/([^/]+)?)?$}', $url, $m)) {
            $read_url = "{$_conf['read_php']}?host={$m[1]}&amp;bbs={$m[2]}&amp;key={$m[3]}&amp;ls={$m[4]}";
            if ($_conf['iframe_popup']) {
                if (preg_match('/^[0-9n\\-]+$/', $m[4])) {
                    $pop_url = $url;
                } else {
                    $pop_url = $read_url . '&amp;one=true';
                }
                return $this->iframe_popup(array($read_url, $pop_url), $str, $_conf['bbs_win_target_at']);
            }
            return "<a href=\"{$read_url}\"{$_conf['bbs_win_target_at']}>{$str}</a>";
        }
        return FALSE;
    }

    /**
     * 2ch過去ログhtml
     */
    function plugin_link2chKako($url, $purl, $str)
    {
        global $_conf, $_exconf;

        if (preg_match('{^http://(\\w+(?:\\.2ch\\.net|\\.bbspink\\.com))(?:/[^/]+/)?/([^/]+)/kako/\\d+(?:/\\d+)?/(\\d+)\\.html$}', $url, $m)) {
            $read_url = "{$_conf['read_php']}?host={$m[1]}&amp;bbs={$m[2]}&amp;key={$m[3]}&amp;kakolog=" . rawurlencode($url);
            if ($_conf['iframe_popup']) {
                $pop_url = $read_url . '&amp;one=true';
                return $this->iframe_popup(array($read_url, $pop_url), $str, $_conf['bbs_win_target_at']);
            }
            return "<a href=\"{$read_url}\"{$_conf['bbs_win_target_at']}>{$str}</a>";
        }
        return FALSE;
    }

    /**
     * まちBBS / JBBS＠したらば  内リンク
     */
    function plugin_linkMachi($url, $purl, $str)
    {
        global $_conf, $_exconf;

        if (preg_match('{^http://((\\w+\\.machibbs\\.com|\\w+\\.machi\\.to|jbbs\\.livedoor\\.(?:jp|com)|jbbs\\.shitaraba\\.com)(/\\w+)?)/bbs/read\\.(?:pl|cgi)\\?BBS=(\\w+)(?:&amp;|&)KEY=([0-9]+)(?:(?:&amp;|&)START=([0-9]+))?(?:(?:&amp;|&)END=([0-9]+))?(?=&|$)}', $url, $m)) {
            $read_url = "{$_conf['read_php']}?host={$m[1]}&amp;bbs={$m[4]}&amp;key={$m[5]}";
            if ($m[6] || $m[7]) {
                $read_url .= "&amp;ls={$m[6]}-{$m[7]}";
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
     */
    function plugin_linkJBBS($url, $purl, $str)
    {
        global $_conf, $_exconf;

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
     * 画像ポップアップ変換
     */
    function plugin_viewImage($url, $purl, $str)
    {
        global $_conf, $_exconf;
        global $pre_thumb_unlimited, $pre_thumb_limit;

        // 表示制限
        if (!$pre_thumb_unlimited && empty($pre_thumb_limit)) {
            return FALSE;
        }

        if (preg_match('{^https?://.+?\\.(jpe?g|gif|png)$}i', $url) && empty($purl['query'])) {
            $pre_thumb_limit--; // 表示制限カウンタを下げる
            $img_tag = "<img class=\"thumbnail\" src=\"{$url}\" height=\"{$_conf['pre_thumb_height']}\" weight=\"{$_conf['pre_thumb_width']}\" hspace=\"4\" vspace=\"4\" align=\"middle\">";

            if ($_conf['iframe_popup']) {
                $view_img = $this->imageHtmpPopup($url, $img_tag, $str);
            } else {
                $view_img = "<a href=\"{$url}\"{$_conf['ext_win_target_at']}>{$img_tag}{$str}</a>";
            }

            // ブラクラチェッカ （プレビューとは相容れないのでコメントアウト）
            /*if ($_conf['brocra_checker_use']) {
                $link_url_en = rawurlencode($url);
                $view_img .= " [<a href=\"{$_conf['brocra_checker_url']}?{$_conf['brocra_checker_query']}={$link_url_en}\"{$_conf['ext_win_target_at']}>チェック</a>]";
            }*/

            return $view_img;
        }
        return FALSE;
    }

    /**
     * ImageCache2サムネイル変換
     */
    function plugin_imageCache2($url, $purl, $str)
    {
        global $_conf, $_exconf;
        global $pre_thumb_unlimited, $pre_thumb_ignore_limit, $pre_thumb_limit;
        static $serial = 0;

        if (preg_match('{^https?://.+?\\.(jpe?g|gif|png)$}i', $url) && empty($purl['query'])) {
            // 準備
            $serial++;
            $thumb_id = 'thumbs' . $serial . '_' . P2_REQUEST_ID;
            $tmp_thumb = './img/ic_load.png';
            $url_en = rawurlencode($url);

            $icdb = &new IC2DB_Images;

            // r=0:リンク;r=1:リダイレクト;r=2:PHPで表示
            // t=0:オリジナル;t=1:PC用サムネイル;t=2:携帯用サムネイル;t=3:中間イメージ
            $img_url = 'ic2.php?r=1&amp;uri=' . $url_en;
            $thumb_url = 'ic2.php?r=1&amp;t=1&amp;uri=' . $url_en;

            // DBに画像情報が登録されていたとき
            if ($icdb->get($url)) {

                // ウィルスに感染していたファイルのとき
                if ($icdb->mime == 'clamscan/infected') {
                    return "<img class=\"thumbnail\" src=\"./img/x04.png\" width=\"32\" height=\"32\" hspace=\"4\" vspace=\"4\" align=\"middle\"> <s>{$str}</s>";
                }
                // あぼーん画像のとき
                if ($icdb->rank < 0) {
                    return "<img class=\"thumbnail\" src=\"./img/x01.png\" width=\"32\" height=\"32\" hspace=\"4\" vspace=\"4\" align=\"middle\"> <s>{$str}</s>";
                }

                // オリジナルがキャッシュされているときは画像を直接読み込む
                $_img_url = $this->thumbnailer->srcPath($icdb->size, $icdb->md5, $icdb->mime);
                if (file_exists($_img_url)) {
                    $img_url = $_img_url;
                    $cached = TRUE;
                } else {
                    $cached = FALSE;
                }

                // サムネイルが作成されていているときは画像を直接読み込む
                $_thumb_url = $this->thumbnailer->thumbPath($icdb->size, $icdb->md5, $icdb->mime);
                if (file_exists($_thumb_url)) {
                    $thumb_url = $_thumb_url;
                    // 自動スレタイメモ機能がONでスレタイが記録されていないときはDBを更新
                    if (!is_null($this->img_memo) && !strstr($icdb->memo, $this->img_memo)){
                        $update = &new IC2DB_Images;
                        if (!is_null($icdb->memo) && strlen($icdb->memo) > 0) {
                            $update->memo = $this->img_memo . ' ' . $icdb->memo;
                        } else {
                            $update->memo = $this->img_memo;
                        }
                        $update->whereAddQuoted('uri', '=', $url);
                        $update->update();
                    }
                }

                // サムネイルの画像サイズ
                $thumb_size = $this->thumbnailer->calc($icdb->width, $icdb->height);
                $thumb_size = preg_replace('/(\d+)x(\d+)/', 'width="$1" height="$2"', $thumb_size);
                $tmp_thumb = './img/ic_load1.png';

            // 画像がキャッシュされていないとき
            // 自動スレタイメモ機能がONならクエリにUTF-8エンコードしたスレタイを含める
            } else {
                // 画像がブラックリストorエラーログにあるか確認
                if (FALSE !== ($errcode = $icdb->ic2_isError($url))) {
                    return "<img class=\"thumbnail\" src=\"./img/{$errcode}.png\" width=\"32\" height=\"32\" hspace=\"4\" vspace=\"4\" align=\"middle\"> <s>{$str}</s>";
                }

                $cached = FALSE;

                $img_url .= $this->img_memo_query;
                $thumb_url .= $this->img_memo_query;
                $thumb_size = '';
                $tmp_thumb = './img/ic_load2.png';
            }

            // キャッシュされておらず、表示数制限が有効のとき
            if (!$cached && !$pre_thumb_unlimited && !$pre_thumb_ignore_limit) {
                // 表示制限を超えていたら、表示しない
                // 表示制限を超えていなければ、表示制限カウンタを下げる
                if ($pre_thumb_limit <= 0) {
                    $show_thumb = FALSE;
                } else {
                    $show_thumb = TRUE;
                    $pre_thumb_limit--;
                }
            } else {
                $show_thumb = TRUE;
            }

            // 表示モード
            if ($show_thumb) {
                $img_tag = "<img class=\"thumbnail\" src=\"{$thumb_url}\" {$thumb_size} hspace=\"4\" vspace=\"4\" align=\"middle\">";
                if ($_conf['iframe_popup']) {
                    $view_img = $this->imageHtmpPopup($img_url, $img_tag, $str);
                } else {
                    $view_img = "<a href=\"{$img_url}\"{$_conf['ext_win_target_at']}>{$img_tag}{$str}</a>";
                }
            } else {
                $img_tag = "<img id=\"{$thumb_id}\" class=\"thumbnail\" src=\"{$tmp_thumb}\" hspace=\"4\" vspace=\"4\" align=\"middle\">";
                $view_img = "<a href=\"{$img_url}\" onclick=\"return loadThumb('{$thumb_url}','{$thumb_id}')\"{$_conf['ext_win_target_at']}>{$img_tag}</a><a href=\"{$img_url}\"{$_conf['ext_win_target_at']}>{$str}</a>";
            }

            // ソースへのリンクをime付きで表示
            if ($_exconf['imgCache']['*'] && $_exconf['imgCache']['through_ime']) {
                $ime_url = P2Util::throughIme($url);
                $view_img .= " <a class=\"img_through_ime\" href=\"{$ime_url}\"{$_conf['ext_win_target_at']}>[ime]</a>";
            }

            return $view_img;
        }
        return FALSE;
    }

    // }}}

}
?>
