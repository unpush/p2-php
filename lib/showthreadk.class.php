<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    p2 - スレッドを表示する クラス 携帯用
*/

require_once (P2_LIBRARY_DIR . '/showthread.class.php');
require_once (P2EX_LIBRARY_DIR . '/expack_loader.class.php');

ExpackLoader::loadActiveMona();
ExpackLoader::loadImageCache();

class ShowThreadK extends ShowThread {

    var $activemona; // アクティブモナークラスのインスタンス
    var $am_aaryaku = FALSE;

    var $thumbnailer; // サムネイル作成クラスのインスタンス
    var $img_memo; // DBの画像情報に付加するメモ（UTF-8エンコードしたスレタイ）
    var $img_memo_query;

    /**
     * コンストラクタ (PHP4 style)
     */
    function ShowThreadK(&$aThread)
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
        } elseif ($_conf['k_use_picto']) {
            $this->url_handlers[] = array('this' => 'plugin_viewImage');
        }
        $this->url_handlers[] = array('this' => 'plugin_linkURL');

        // サムネイル表示制限数を設定
        if (!isset($GLOBALS['pre_thumb_unlimited']) || !isset($GLOBALS['pre_thumb_limit_k'])) {
            if (isset($_conf['pre_thumb_limit_k']) && $_conf['pre_thumb_limit_k'] >= 0) {
                $GLOBALS['pre_thumb_limit_k'] = $_conf['pre_thumb_limit_k'];
                $GLOBALS['pre_thumb_unlimited'] = FALSE;
            } else {
                $GLOBALS['pre_thumb_limit_k'] = NULL;   // ヌル値だとisset()はFALSEを返す
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
    }

    /**
     * DatをHTMLに変換表示する
     */
    function datToHtml()
    {

        if (!$this->thread->resrange) {
            echo '<p><b>p2 error: {$this->resrange} is FALSE at datToHtml()</b></p>';
            return false;
        }

        $start = $this->thread->resrange['start'];
        $to = $this->thread->resrange['to'];
        $nofirst = $this->thread->resrange['nofirst'];

        // 1を表示
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

        //$s2e = array($start, $i-1);
        //return $s2e;
        return true;
    }


    /**
     * DatレスをHTMLレスに変換する
     *
     * 引数 - レス番号
     */
    function transRes($i)
    {
        global $_conf, $_exconf;
        global $STYLE, $mae_msg, $res_filter, $filter_range;
        global $ngaborns_hits;

        $tores = '';
        $rpop = '';

        $name = $this->pDatLines[$i]['name'];
        $mail = $this->pDatLines[$i]['mail'];
        $date_id = $this->transDateId($i);
        $msg = $this->pDatLines[$i]['msg'];

        // {{{ transRes - フィルタリング・あぼーん・NG・AAチェック

        // フィルタリング
        if (!empty($filter_range['to']) && !$this->filterMatch($i)) {
            return '';
        }

        // あぼーんチェック
        $aborned_res = "<div id=\"r{$i}\" name=\"r{$i}\">&nbsp;</div>\n"; //名前
        $aborned_res .= ""; //内容
        if ($this->checker->abornCheck($i)) {
            $ngaborns_hits["aborn_{$aborn_hit_field}"]++;
            return $aborned_res;
        }

        // NGチェック
        if (!$_GET['nong']) {
            $ng_fields = $this->checker->ngCheck($i);
            foreach ($ng_fields as $ng_hit_field => $ng_hit_value) {
                $ngaborns_hits["ng_{$ng_hit_field}"]++;
            }
        }

        // AAチェック
        if ($this->am_aaryaku && $this->activemona->detectAA($msg)) {
            if ($this->am_aaryaku == 2) {
                return $aborned_res;
            } elseif (!$_GET['nong']) {
                $ngaborns_hits['ng_aa']++;
                $ng_fields['aa'] = TRUE;
            }
        }

        // }}}

        //=============================================================
        // まとめて出力
        //=============================================================

        $name = $this->transName($name); // 名前HTML変換
        $msg = $this->transMsg($msg, $i); //メッセージHTML変換

        // {{{ transRes - NGワード変換

        if (!$_GET['nong']) {

            $_ng_color = ($_exconf['ubiq']['c_ngword']) ? $_exconf['ubiq']['c_ngword'] : $STYLE['read_ngword'];

            // NGの理由を設定
            $a_ng_msg = '';
            // AA略
            if (isset($ng_fields['aa'])) {
                $a_ng_msg = $this->am_aaryaku_msg;

            // 行数が多すぎ
            } elseif (isset($ng_fields['lines'])) {
                $a_ng_msg = $ng_fields['lines'] . 'lines';

            // メッセージにNGワードを含む
            } elseif (isset($ng_fields['msg'])) {
                $a_ng_msg = 'NGﾜｰﾄﾞ:' . $ng_fields['msg'];

            // あぼーんレスを参照
            } elseif (isset($ng_fields['aborn'])) {
                $a_ng_msg = '連鎖NG(ｱﾎﾞ-ﾝ):&gt;&gt;' . $ng_fields['aborn'];

            // NGレスを参照
            } elseif (isset($ng_fields['aborn'])) {
                $a_ng_msg = '連鎖NG:&gt;&gt;' . $ng_fields['chain'];
            }

            // NGメッセージ変換
            if ($a_ng_msg) {
                $a_ng_msg = "<s><font color=\"{$_ng_color}\">{$a_ng_msg}</font></s> ";
            }
            if ($ng_fields) {
                $msg = "{$a_ng_msg}<a href=\"{$this->read_url_base}&amp;ls={$i}&amp;k_continue=1&amp;nong=1\">確</a>";
            }

            // NGネーム変換
            if (isset($ng_fields['name'])) {
                $name = "<s><font color=\"{$_ng_color}\">{$name}</font></s>";
            }

            // NGメール変換
            if (isset($ng_fields['mail'])) {
                $mail = "<s><font color=\"{$_ng_color}\">{$mail}</font></s>";
            }

            // NGID変換
            if (isset($ng_fields['id'])) {
                $date_id = "<s><font color=\"{$_ng_color}\">{$date_id}</font></s>";
            }

        }

        // }}}

        /*
        // 「ここから新着」画像を挿入========================
        if ($i == $this->thread->readnum + 1) {
            $tores .=<<<EOP
                <div><img src="img/image.png" alt="新着レス" border="0" vspace="4"></div>
EOP;
        }
        */
        if ($this->thread->onthefly) { // ontheflyresorder
            $GLOBALS['newres_to_show_flag'] = true;
            $_ontefly_color = ($_exconf['ubiq']['c_onthefly']) ? $_exconf['ubiq']['c_onthefly'] : '#00aa00';
            $tores .= "<div id=\"r{$i}\" name=\"r{$i}\">[<font color=\"{$_ontefly_color}'\">{$i}</font>]"; //番号（オンザフライ時）
        } elseif ($i > $this->thread->readnum) {
            $GLOBALS['newres_to_show_flag'] = true;
            $_newres_color = ($_exconf['ubiq']['c_newres']) ? $_exconf['ubiq']['c_newres'] : $STYLE['read_newres_color'];
            $tores .= "<div id=\"r{$i}\" name=\"r{$i}\">[<font color=\"{$_newres_color}\">{$i}</font>]"; //番号（新着レス時）
        } else {
            $tores .= "<div id=\"r{$i}\" name=\"r{$i}\">[{$i}]"; //番号
        }
        $tores .= $name.': '; // 名前
        if ($mail) { $tores .= $mail.': '; } // メール
        $tores .= $date_id."<br>\n"; // 日付とID
        $tores .= $rpop; // レスポップアップ用引用
        $tores .= "{$msg}</div><hr>\n"; //内容

        // しおりを挿入========================
        if ($_exconf['bookmark']['*'] && $i > 0 && $i == $this->thread->readhere) {
            $tores .= "<div id=\"readhere\">{$_exconf['bkmk']['marker_k']}</div><hr>\n";
        }

        return $tores;
    }

    /**
     * 名前をHTML用に変換する
     */
    function transName($name)
    {
        global $_conf;
        $nameID = '';

        // ID付なら分解する
        if (preg_match("/(.*)(◆.*)/", $name, $matches)) {
            $name = $matches[1];
            $nameID = $matches[2];
        }

        // 数字を引用レスリンク化
        // </b>～<b> は、ホストやトリップなのでマッチさせない
        $pettern = '/^( ?(?:&gt;|＞)* ?)?([1-9]\d{0,3})(?=\\D|$)/';
        $name && $name = preg_replace_callback($pettern, array($this, 'quote_res_callback'), $name, 1);

        if ($nameID) { $name = $name . $nameID; }

        $name = $name.' '; // 文字化け回避

        $name = strip_tags($name, '<a>');

        return $name;
    }


    //============================================================================
    // transMsg --  datのレスメッセージをHTML表示用メッセージに変換するメソッド
    // string transMsg(string str)
    //============================================================================
    function transMsg($msg, $mynum)
    {
        global $_conf, $_exconf;
        global $res_filter, $word_fm, $k_filter_marker;
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

        // 大きさ制限
        if (!$_GET['k_continue'] && strlen($msg) > $_conf['ktai_res_size']) {
            // <br>以外のタグを除去し、長さを切り詰める
            $msg = strip_tags($msg, '<br>');
            $msg = mb_strcut($msg, 0, $_conf['ktai_ryaku_size']);
            $msg = preg_replace('/ *<[^>]*$/i', '', $msg);

            // >>1, >1, ＞1, ＞＞1を引用レスポップアップリンク化
            // URLは途中で切れる可能性がかなり高いのでリンクしない
            $msg = preg_replace_callback('/((?:&gt;|＞)+ ?)([1-9][0-9\\-,]+)/', array($this, 'quote_res_callback'), $msg);

            $msg .= " <a href=\"{$this->read_url_base}&amp;ls={$mynum}&amp;k_continue=1&amp;offline=1\">略</a>";
            return $msg;
        }

        // 新着レスの画像は表示制限を無視する設定なら
        if ($mynum > $this->thread->readnum && $_exconf['imgCache']['newres_ignore_limit']) {
            $pre_thumb_ignore_limit = TRUE;
        }

        // 引用やURLなどをリンク
        $msg = preg_replace_callback($this->str_to_link_regex, array($this, 'link_callback'), $msg);

        $pre_thumb_ignore_limit = FALSE;

        // フィルタ色分け
        if ($k_filter_marker && $word_fm && $res_filter['match'] == 'on' && $res_filter['field'] &&
            ($res_filter['field'] == 'msg' || $res_filter['field'] == 'hole')
        ) {
            $msg = StrCtl::filterMarking($word_fm, $msg, $k_filter_marker);
        }

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
            if ($s[9] == 'ftp') {
                return $s[0];
            }
            $url = preg_replace('/^t?(tps?)$/', 'ht$1', $s[8]) . '://' . $s[9];
            $str = $s['url'];

        // ID
        } elseif ($s['id'] && $_exconf['flex']['idlink_k']) {
            return $this->idfilter_callback(array($s['id'], $s[11]));

        // その他（予備）
        } else {
            return strip_tags($s[0]);
        }

        // }}}
        // {{{ URLの前処理

        // ime.nuを外す
        $url = preg_replace('|^([a-z]+://)ime\.nu/|', '$1', $url);

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
     * ■携帯用外部URL変換
     */
    function ktai_exturl_callback($s)
    {
        global $_conf, $_exconf;

        $in_url = $s[1];

        // 通勤ブラウザ
        $tsukin_link = '';
        if ($_conf['k_use_tsukin']) {
            $tsukin_url = 'http://www.sjk.co.jp/c/w.exe?y='.urlencode($in_url);
            if ($_conf['through_ime']) {
                $tsukin_url = P2Util::throughIme($tsukin_url);
            }
            $tsukin_link = '<a href="'.$tsukin_url.'">通</a>';
        }
        /*
        // jigブラウザWEB http://bwXXXX.jig.jp/fweb/?_jig_=
        $jig_link = '';

        $jig_url = 'http://bw5032.jig.jp/fweb/?_jig_='.urlencode($in_url);
        if ($_conf['through_ime']) {
            $jig_url = P2Util::throughIme($jig_url);
        }

        $jig_link = '<a href="'.$jig_url.'">j</a>';
        */

        $sepa ='';
        if ($tsukin_link && $jig_link) {
            $sepa = '|';
        }

        $ext_pre = '';
        if ($tsukin_link || $jig_link) {
            $ext_pre = '('.$tsukin_link.$sepa.$jig_link.')';
        }

        if ($_conf['through_ime']) {
            $in_url = P2Util::throughIme($in_url);
        }
        $r = $ext_pre.'<a href="' . $in_url . '">' . $s[2] . '</a>';

        return $r;
    }

    /**
     * ■引用変換
     */
    function quote_res_callback($s)
    {
        global $_conf, $_exconf;

        list($full, $qsign, $appointed_num) = $s;
        if ($appointed_num == '-') {
            return $s[0];
        }
        $qnum = intval($appointed_num);
        if ($qnum < 1 || $qnum > $this->thread->rescount) {
            return $s[0];
        }

        $read_url = $this->read_url_base . '&amp;offline=1&amp;ls=' . $appointed_num;
        return "<a href=\"{$read_url}{$_conf['k_at_a']}\">{$qsign}{$appointed_num}</a>";
    }

    /**
     * ■引用変換（範囲）
     */
    function quote_res_range_callback($s)
    {
        global $_conf, $_exconf;

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

        $read_url = $this->read_url_base . '&amp;offline=1&amp;ls=' . $from .'-' . $to;

        return "<a href=\"{$read_url}\">{$qsign}{$appointed_num}</a>";
    }

    /**
     * ■IDフィルタリングリンク変換
     */
    function idfilter_callback($s)
    {
        global $_conf, $_exconf;

        $idstr = $s[0]; // ID:xxxxxxxxxx
        $id = $s[1];    // xxxxxxxxxx

        if (isset($this->thread->idcount[$id]) && $this->thread->idcount[$id] > 0) {
            $num_ht = '('.$this->thread->idcount[$id].')';
        } else {
            return $idstr;
        }

        $filter_url = $this->read_url_base . '&amp;ls=all&amp;offline=1&amp;idpopup=1&amp;field=id&amp;method=just&amp;match=on&amp;word=' . rawurlencode($id);

        return "<a href=\"{$filter_url}\">{$idstr}{$num_ht}</a>";
    }

    // }}}
    // {{{ ユーティリティメソッド

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
        if ($_exconf['etc']['datetime_rewrite_k']) {
            if (isset($p['timestamp'])) {
                $epoch = $p['timestamp'];
            } else {
                $epoch = $p['timestamp'] = $this->datetimeToEpoch($p['date'], $p['time']);
            }
            if ($epoch != -1) {
                $date_id = date($_exconf['etc']['datetime_format_k'], $epoch);
                if (strstr($_exconf['etc']['datetime_format'], '%w%')) {
                    $date_id = preg_replace('/%([0-6])%/e', '$_exconf["etc"]["datetime_weekday_k"][$1]', $date_id);
                }
            } else {
                $date_id = $p['date'].' '.$p['time'];
            }
        } else {
            $date_id = $p['date'].' '.$p['time'];
        }

        // ID
        if (isset($p['id'])) {
            if ($_exconf['flex']['idlink_k'] == 1 && $this->thread->idcount[$p['id']] > 1) {
                $date_id .= ' '. $this->idfilter_callback(array('ID:'.$p['id'], $p['id']));
            } else {
                $date_id .= ' ID:' . $p['id'];
            }
            if (isset($p['idopt'])) {
                $date_id .= $p['idopt'];
            }
        }

        // BE
        if ($p_dateid['be']) {
            $be_prof_ref = rawurlencode("http://{$this->thread->host}/test/read.cgi/{$this->thread->bbs}/{$this->thread->key}/{$GLOBALS['ls']}");
            $date_id .= " <a href=\"http://be.2ch.net/test/p.php?i={$p_dateid['beid']}&u=d:{$be_prof_ref}\">Lv.{$p_dateid['belv']}</a>";
        }

        return $date_id;
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
            // 携帯用外部URL変換
            if ($_conf['k_use_tsukin']) {
                return $this->ktai_exturl_callback(array('', $url, $str));
            }
            // ime
            if ($_conf['through_ime']) {
                $link_url = P2Util::throughIme($url);
                $type = 'url';
                if (preg_match('/\.([0-9A-Za-z]{1,5})$/', $url, $matches)) {
                    $_type = strtolower($matches[1]);
                    if (!preg_match('/^(?:[sp]?html?|cgi|phps?|pl|py|rb|[aj]sp)$/', $_type)) {
                        $type = $_type;
                    }
                }
                $title = preg_replace('|^.+?://([^/]+)(/.*)?$|', '$1', $str);
                $link_title = "[{$type}:{$title}]";
            } else {
                $link_url = $url;
                $link_title = $str;
            }
            $link = "<a href=\"{$link_url}\">{$link_title}</a>";
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
            return "<a href=\"{$url}\">{$str}</a> [<a href=\"{$subject_url}{$_conf['k_at_a']}\">板をp2で開く</a>]";
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
            return "<a href=\"{$read_url}{$_conf['k_at_a']}\">{$str}</a>";
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
            return "<a href=\"{$read_url}{$_conf['k_at_a']}\">{$str}</a>";
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
            return "<a href=\"{$read_url}{$_conf['k_at_a']}\">{$str}</a>";
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
            return "<a href=\"{$read_url}{$_conf['k_at_a']}\">{$str}</a>";
        }
        return FALSE;
    }

    /**
     * 画像URLのpic.to変換
     */
    function plugin_viewImage($url, $purl, $str)
    {
        global $_conf, $_exconf;

        if (preg_match('{^https?://.+?\\.(jpe?g|gif|png)$}i', $url) && empty($purl['query'])) {
            $picto_url = 'http://pic.to/'.$purl['host'].$purl['path'];
            $picto_tag = '<a href="'.$picto_url.'">(ﾋﾟ)</a> ';
            if ($_conf['through_ime']) {
                $link_url  = P2Util::throughIme($url);
                $picto_url = P2Util::throughIme($picto_url);
            } else {
                $link_url = $url;
            }
            return "{$picto_tag}<a href=\"{$link_url}\">{$str}</a>";
        }
        return FALSE;
    }

    /**
     * 画像URLのImageCache2変換
     */
    function plugin_imageCache2($url, $purl, $str)
    {
        global $_conf, $_exconf;
        global $pre_thumb_unlimited, $pre_thumb_ignore_limit, $pre_thumb_limit_k;

        if (preg_match('{^https?://.+?\\.(jpe?g|gif|png)$}i', $url) && empty($purl['query'])) {
            // インラインプレビューの有効判定
            if ($pre_thumb_unlimited || $pre_thumb_ignore_limit || $pre_thumb_limit_k > 0) {
                $inline_preview_flag = TRUE;
                $inline_preview_done = FALSE;
            } else {
                $inline_preview_flag = FALSE;
                $inline_preview_done = FALSE;
            }

            $url_en = rawurlencode($url);
            $img_str = '[IC2:'.$purl['host'].':'.basename($purl['path']).']';

            $icdb = &new IC2DB_Images;

            // r=0:リンク;r=1:リダイレクト;r=2:PHPで表示
            // t=0:オリジナル;t=1:PC用サムネイル;t=2:携帯用サムネイル;t=3:中間イメージ
            $img_url = 'ic2.php?r=0&amp;t=2&amp;uri=' . $url_en;

            // DBに画像情報が登録されていたとき
            if ($icdb->get($url)) {

                // ウィルスに感染していたファイルのとき
                if ($icdb->mime == 'clamscan/infected') {
                    return '[IC2:ウィルス警告]';
                }
                // あぼーん画像のとき
                if ($icdb->rank < 0) {
                    return '[IC2:あぼーん画像]';
                }

                // インラインプレビューが有効のとき
                if ($this->thumbnailer->ini['General']['inline'] == 1) {
                    // フルスクリーン画像が作られていれば、リンクを更新
                    /*$_img_url = $this->thumbnailer->thumbPath($icdb->size, $icdb->md5, $icdb->mime);
                    if (file_exists($_img_url)) {
                        $img_url = $_img_url;
                    }*/
                    $_prvw_url = $this->inline_prvw->thumbPath($icdb->size, $icdb->md5, $icdb->mime);
                    // サムネイル表示制限数以内のとき
                    if ($inline_preview_flag) {
                        // プレビュー画像が作られているかどうかでimg要素の属性を決定
                        if (file_exists($_prvw_url)) {
                            $prvw_size = explode('x', $this->inline_prvw->calc($icdb->width, $icdb->height));
                            $img_str = "<img src=\"{$_prvw_url}\" width=\"{$prvw_size[0]}\" height=\"{$prvw_size[1]}\">";
                        } else {
                            $img_str = "<img src=\"ic2.php?r=1&amp;t=1&amp;uri={$url_en}\">";
                        }
                        $inline_preview_done = TRUE;
                    } else {
                        $img_str = '[p2:既得画像(ﾗﾝｸ:' . $icdb->rank . ')]';
                    }
                }

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

            // 画像がキャッシュされていないとき
            // 自動スレタイメモ機能がONならクエリにUTF-8エンコードしたスレタイを含める
            } else {
                // 画像がブラックリストorエラーログにあるか確認
                if (FALSE !== ($errcode = $icdb->ic2_isError($url))) {
                    return "<s>[IC2:ｴﾗｰ({$errcode})]</s>";
                }

                // インラインプレビューが有効で、サムネイル表示制限数以内なら
                if ($this->thumbnailer->ini['General']['inline'] == 1 && $inline_preview_flag) {
                    $img_str = '<img src="ic2.php?r=1&amp;t=1&amp;uri=' . $url_en . $this->img_memo_query . '">';
                    $inline_preview_done = TRUE;
                } else {
                    $img_url .= $this->img_memo_query;
                }
            }

            // 表示数制限をデクリメント
            if ($inline_preview_flag && $inline_preview_done) {
                $pre_thumb_limit_k--;
            }

            return "<a href=\"{$img_url}\">{$img_str}</a>";
        }
        return FALSE;
    }

    // }}}

}

?>
