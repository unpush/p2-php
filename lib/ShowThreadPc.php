<?php
/**
 * rep2 - スレッドを表示する クラス PC用
 */

require_once P2_LIB_DIR . '/ShowThread.php';
require_once P2_LIB_DIR . '/StrCtl.php';
require_once P2EX_LIB_DIR . '/ExpackLoader.php';

ExpackLoader::loadAAS();
ExpackLoader::loadActiveMona();
ExpackLoader::loadImageCache();

// {{{ ShowThreadPc

class ShowThreadPc extends ShowThread
{
    // {{{ properties

    static private $_spm_objects = array();

    private $_quote_res_nums_checked; // ポップアップ表示されるチェック済みレス番号を登録した配列
    private $_quote_res_nums_done; // ポップアップ表示される記録済みレス番号を登録した配列
    private $_quote_check_depth; // レス番号チェックの再帰の深さ checkQuoteResNums()

    private $_ids_for_render;   // 出力予定のID(重複のみ)のリスト(8桁)
    private $_idcount_average;  // ID重複数の平均値
    private $_idcount_tops;     // ID重複数のトップ入賞までの重複数値

    public $am_autodetect = false; // AA自動判定をするか否か
    public $am_side_of_id = false; // AAスイッチをIDの横に表示する
    public $am_on_spm = false; // AAスイッチをSPMに表示する

    public $asyncObjName;  // 非同期読み込み用JavaScriptオブジェクト名
    public $spmObjName; // スマートポップアップメニュー用JavaScriptオブジェクト名

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     */
    public function __construct($aThread, $matome = false)
    {
        parent::__construct($aThread, $matome);

        global $_conf;

        $this->_url_handlers = array(
            'plugin_linkThread',
            'plugin_link2chSubject',
        );
        // +Wiki
        if (isset($GLOBALS['linkplugin']))      $this->_url_handlers[] = 'plugin_linkPlugin';
        if (isset($GLOBALS['replaceimageurl'])) $this->_url_handlers[] = 'plugin_replaceImageURL';
        if (P2_IMAGECACHE_AVAILABLE == 2) {
            $this->_url_handlers[] = 'plugin_imageCache2';
        } elseif ($_conf['preview_thumbnail']) {
            $this->_url_handlers[] = 'plugin_viewImage';
        }
        if ($_conf['link_youtube']) {
            $this->_url_handlers[] = 'plugin_linkYouTube';
        }
        if ($_conf['link_niconico']) {
            $this->_url_handlers[] = 'plugin_linkNicoNico';
        }
        $this->_url_handlers[] = 'plugin_linkURL';

        // imepitaのURLを加工してImageCache2させるプラグインを登録
        $this->addURLHandler(array($this, 'plugin_imepita_to_imageCache2'));

        // サムネイル表示制限数を設定
        if (!isset($GLOBALS['pre_thumb_unlimited']) || !isset($GLOBALS['pre_thumb_limit'])) {
            if (isset($_conf['pre_thumb_limit']) && $_conf['pre_thumb_limit'] > 0) {
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

        // 非同期レスポップアップ・SPM初期化
        $js_id = sprintf('%u', crc32($this->thread->keydat));
        if ($this->_matome) {
            $this->asyncObjName = "t{$this->_matome}asp{$js_id}";
            $this->spmObjName = "t{$this->_matome}spm{$js_id}";
        } else {
            $this->asyncObjName = "asp{$js_id}";
            $this->spmObjName = "spm{$js_id}";
        }

        // 名無し初期化
        $this->setBbsNonameName();
    }

    // }}}
    // {{{ transRes()

    /**
     * DatレスをHTMLレスに変換する
     *
     * @param   string  $ares   datの1ライン
     * @param   int     $i      レス番号
     * @return  string
     */
    public function transRes($ares, $i)
    {
        global $_conf, $STYLE, $mae_msg, $res_filter;

        list($name, $mail, $date_id, $msg) = $this->thread->explodeDatLine($ares);
        if (($id = $this->thread->ids[$i]) !== null) {
            $idstr = 'ID:' . $id;
            $date_id = str_replace($this->thread->idp[$i] . $id, $idstr, $date_id);
        } else {
            $idstr = null;
        }

        // {{{ フィルタリング
        if (isset($_REQUEST['word']) && strlen($_REQUEST['word']) > 0) {
            if (strlen($GLOBALS['word_fm']) <= 0) {
                return '';
            // ターゲット設定（空のときはフィルタリング結果に含めない）
            } elseif (!$target = $this->getFilterTarget($ares, $i, $name, $mail, $date_id, $msg)) {
                return '';
            // マッチング
            } elseif (!$this->filterMatch($target, $i)) {
                return '';
            }
        }
        // }}}

        $tores = '';
        $rpop = '';
        if ($this->_matome) {
            $res_id = "t{$this->_matome}r{$i}";
            $msg_id = "t{$this->_matome}m{$i}";
        } else {
            $res_id = "r{$i}";
            $msg_id = "m{$i}";
        }
        $msg_class = 'message';

        // NGあぼーんチェック
        $ng_type = $this->_ngAbornCheck($i, strip_tags($name), $mail, $date_id, $id, $msg, false, $ng_info);
        if ($ng_type == self::ABORN) {
            return $this->_abornedRes($res_id);
        }
        if ($ng_type != self::NG_NONE) {
            $ngaborns_head_hits = self::$_ngaborns_head_hits;
            $ngaborns_body_hits = self::$_ngaborns_body_hits;
        }

        // AA判定
        if ($this->am_autodetect && $this->activeMona->detectAA($msg)) {
            $msg_class .= ' ActiveMona';
        }

        //=============================================================
        // レスをポップアップ表示
        //=============================================================
        if ($_conf['quote_res_view']) {
            $this->_quote_check_depth = 0;
            $quote_res_nums = $this->checkQuoteResNums($i, $name, $msg);

            foreach ($quote_res_nums as $rnv) {
                if (!isset($this->_quote_res_nums_done[$rnv])) {
                    $this->_quote_res_nums_done[$rnv] = true;
                    if (isset($this->thread->datlines[$rnv-1])) {
                        if ($this->_matome) {
                            $qres_id = "t{$this->_matome}qr{$rnv}";
                        } else {
                            $qres_id = "qr{$rnv}";
                        }
                        $ds = $this->qRes($this->thread->datlines[$rnv-1], $rnv);
                        $onPopUp_at = " onmouseover=\"showResPopUp('{$qres_id}',event)\" onmouseout=\"hideResPopUp('{$qres_id}')\"";
                        $rpop .= "<div id=\"{$qres_id}\" class=\"respopup\"{$onPopUp_at}>\n{$ds}</div>\n";
                    }
                }
            }
        }

        //=============================================================
        // まとめて出力
        //=============================================================

        $name = $this->transName($name); // 名前HTML変換
        $msg = $this->transMsg($msg, $i); // メッセージHTML変換


        // BEプロファイルリンク変換
        $date_id = $this->replaceBeId($date_id, $i);

        // HTMLポップアップ
        if ($_conf['iframe_popup']) {
            $date_id = preg_replace_callback("{<a href=\"(http://[-_.!~*()0-9A-Za-z;/?:@&=+\$,%#]+)\"({$_conf['ext_win_target_at']})>((\?#*)|(Lv\.\d+))</a>}", array($this, 'iframePopupCallback'), $date_id);
        }

        // NGメッセージ変換
        if ($ng_type != self::NG_NONE && count($ng_info)) {
            $ng_info = implode(', ', $ng_info);
            $msg = <<<EOMSG
<span class="ngword" onclick="show_ng_message('ngm{$ngaborns_body_hits}', this);">{$ng_info}</span>
<div id="ngm{$ngaborns_body_hits}" class="ngmsg ngmsg-by-msg">{$msg}</div>
EOMSG;
        }

        // NGネーム変換
        if ($ng_type & self::NG_NAME) {
            $name = <<<EONAME
<span class="ngword" onclick="show_ng_message('ngn{$ngaborns_head_hits}', this);">{$name}</span>
EONAME;
            $msg = <<<EOMSG
<div id="ngn{$ngaborns_head_hits}" class="ngmsg ngmsg-by-name">{$msg}</div>
EOMSG;

        // NGメール変換
        } elseif ($ng_type & self::NG_MAIL) {
            $mail = <<<EOMAIL
<span class="ngword" onclick="show_ng_message('ngn{$ngaborns_head_hits}', this);">{$mail}</span>
EOMAIL;
            $msg = <<<EOMSG
<div id="ngn{$ngaborns_head_hits}" class="ngmsg ngmsg-by-mail">{$msg}</div>
EOMSG;

        // NGID変換
        } elseif ($ng_type & self::NG_ID) {
            $date_id = <<<EOID
<span class="ngword" onclick="show_ng_message('ngn{$ngaborns_head_hits}', this);">{$date_id}</span>
EOID;
            $msg = <<<EOMSG
<div id="ngn{$ngaborns_head_hits}" class="ngmsg ngmsg-by-id">{$msg}</div>
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

        // SPM
        if ($_conf['expack.spm.enabled']) {
            $spmeh = " onmouseover=\"{$this->spmObjName}.show({$i},'{$msg_id}',event)\"";
            $spmeh .= " onmouseout=\"{$this->spmObjName}.hide(event)\"";
        } else {
            $spmeh = '';
        }

        $tores .= "<div id=\"{$res_id}\" class=\"res\">\n";
        $tores .= "<div class=\"res-header\">";

        if ($this->thread->onthefly) {
            $GLOBALS['newres_to_show_flag'] = true;
            //番号（オンザフライ時）
            $tores .= "<span class=\"ontheflyresorder spmSW\"{$spmeh}>{$i}</span> : ";
        } elseif ($i > $this->thread->readnum) {
            $GLOBALS['newres_to_show_flag'] = true;
            // 番号（新着レス時）
            $tores .= "<span style=\"color:{$STYLE['read_newres_color']}\" class=\"spmSW\"{$spmeh}>{$i}</span> : ";
        } elseif ($_conf['expack.spm.enabled']) {
            // 番号（SPM）
            $tores .= "<span class=\"spmSW\"{$spmeh}>{$i}</span> : ";
        } else {
            // 番号
            $tores .= "{$i} : ";
        }
        // 名前
        $tores .= preg_replace('{<b>[ ]*</b>}i', '', "<span class=\"name\"><b>{$name}</b></span> : ");

        // メール
        if ($mail) {
            if (strpos($mail, 'sage') !== false && $STYLE['read_mail_sage_color']) {
                $tores .= "<span class=\"sage\">{$mail}</span> : ";
            } elseif ($STYLE['read_mail_color']) {
                $tores .= "<span class=\"mail\">{$mail}</span> : ";
            } else {
                $tores .= $mail . ' : ';
            }
        }

        // IDフィルタ
        if ($_conf['flex_idpopup'] == 1 && $id && $this->thread->idcount[$id] > 1) {
            $date_id = str_replace($idstr, $this->idFilter($idstr, $id), $date_id);
        }

        $tores .= $date_id; // 日付とID
        if ($this->am_side_of_id) {
            $tores .= ' ' . $this->activeMona->getMona($msg_id);
        }
        $tores .= "</div>\n"; // res-headerを閉じる

        // 被レスリスト(縦形式)
        if ($_conf['backlink_list'] == 1) {
            $tores .= $this->quoteback_list_html($i, 1);
        }

        $tores .= "<div id=\"{$msg_id}\" class=\"{$msg_class}\">{$msg}</div>\n"; // 内容
        // 被レスリスト(横形式)
        if ($_conf['backlink_list'] == 2) {
            $tores .= $this->quoteback_list_html($i, 2);
        }
        $tores .= "</div>\n";

//        $tores .= $rpop; // レスポップアップ用引用
        /*if ($_conf['expack.am.enabled'] == 2) {
            $tores .= <<<EOJS
<script type="text/javascript">
//<![CDATA[
detectAA("{$msg_id}");
//]]>
</script>\n
EOJS;
        }*/

        // まとめてフィルタ色分け
        if (!empty($GLOBALS['word_fm']) && $res_filter['match'] != 'off') {
            $tores = StrCtl::filterMarking($GLOBALS['word_fm'], $tores);
        }

        return array('body' => $tores, 'q' => $rpop);
    }

    // }}}
    // {{{ quoteOne()

    /**
     * >>1 を表示する (引用ポップアップ用)
     */
    public function quoteOne()
    {
        global $_conf;

        if (!$_conf['quote_res_view']) {
            return false;
        }

        $rpop = '';
        $this->_quote_check_depth = 0;
        $quote_res_nums = $this->checkQuoteResNums(0, '1', '');

        foreach ($quote_res_nums as $rnv) {
            if (!isset($this->_quote_res_nums_done[$rnv])) {
                $this->_quote_res_nums_done[$rnv] = true;
                if (isset($this->thread->datlines[$rnv-1])) {
                    if ($this->_matome) {
                        $qres_id = "t{$this->_matome}qr{$rnv}";
                    } else {
                        $qres_id = "qr{$rnv}";
                    }
                    $ds = $this->qRes($this->thread->datlines[$rnv-1], $rnv);
                    $onPopUp_at = " onmouseover=\"showResPopUp('{$qres_id}',event)\" onmouseout=\"hideResPopUp('{$qres_id}')\"";
                    $rpop .= "<div id=\"{$qres_id}\" class=\"respopup\"{$onPopUp_at}>\n{$ds}</div>\n";
                }
            }
        }

        $res1['q'] = $rpop;
        $res1['body'] = $this->transMsg('&gt;&gt;1', 1);

        return $res1;
    }

    // }}}
    // {{{ qRes()

    /**
     * レス引用HTML
     */
    public function qRes($ares, $i)
    {
        global $_conf;

        $resar = $this->thread->explodeDatLine($ares);
        $name = $this->transName($resar[0]);
        $mail = $resar[1];
        if (($id = $this->thread->ids[$i]) !== null) {
            $idstr = 'ID:' . $id;
            $date_id = str_replace($this->thread->idp[$i] . $id, $idstr, $resar[2]);
        } else {
            $idstr = null;
            $date_id = $resar[2];
        }
        $msg = $this->transMsg($resar[3], $i);

        $tores = '';

        if ($this->_matome) {
            $qmsg_id = "t{$this->_matome}qm{$i}";
        } else {
            $qmsg_id = "qm{$i}";
        }

        // >>1
        if ($i == 1) {
            $tores = "<h4 class=\"thread_title\">{$this->thread->ttitle_hd}</h4>";
        }

        // BEプロファイルリンク変換
        $date_id = $this->replaceBeId($date_id, $i);

        // HTMLポップアップ
        if ($_conf['iframe_popup']) {
            $date_id = preg_replace_callback("{<a href=\"(http://[-_.!~*()0-9A-Za-z;/?:@&=+\$,%#]+)\"({$_conf['ext_win_target_at']})>((\?#*)|(Lv\.\d+))</a>}", array($this, 'iframePopupCallback'), $date_id);
        }
        //

        // IDフィルタ
        if ($_conf['flex_idpopup'] == 1 && $id && $this->thread->idcount[$id] > 1) {
            $date_id = str_replace($idstr, $this->idFilter($idstr, $id), $date_id);
        }

        $msg_class = 'message';

        // AA 判定
        if ($this->am_autodetect && $this->activeMona->detectAA($msg)) {
            $msg_class .= ' ActiveMona';
        }

        // SPM
        if ($_conf['expack.spm.enabled']) {
            $spmeh = " onmouseover=\"{$this->spmObjName}.show({$i},'{$qmsg_id}',event)\"";
            $spmeh .= " onmouseout=\"{$this->spmObjName}.hide(event)\"";
        } else {
            $spmeh = '';
        }

        // $toresにまとめて出力
        $tores .= '<div class="res-header">';
        $tores .= "<span class=\"spmSW\"{$spmeh}>{$i}</span> : "; // 番号
        $tores .= preg_replace('{<b>[ ]*</b>}i', '', "<b>{$name}</b> : ");
        if ($mail) {
            $tores .= $mail . ' : '; // メール
        }
        $tores .= $date_id; // 日付とID
        if ($this->am_side_of_id) {
            $tores .= ' ' . $this->activeMona->getMona($qmsg_id);
        }
        $tores .= "</div>\n";

        // 被レスリスト(縦形式)
        if ($_conf['backlink_list'] == 1) {
            $tores .= $this->quoteback_list_html($i, 1);
        }

        $tores .= "<div id=\"{$qmsg_id}\" class=\"{$msg_class}\">{$msg}</div>\n"; // 内容
        // 被レスリスト(横形式)
        if ($_conf['backlink_list'] == 2) {
            $tores .= $this->quoteback_list_html($i, 2);
        }

        return $tores;
    }

    // }}}
    // {{{ transName()

    /**
     * 名前をHTML用に変換する
     *
     * @param   string  $name   名前
     * @return  string
     */
    public function transName($name)
    {
        global $_conf;

        // トリップやホスト付きなら分解する
        if (($pos = strpos($name, '◆')) !== false) {
            $trip = substr($name, $pos);
            $name = substr($name, 0, $pos);
        } else {
            $trip = null;
        }

        // 数字を引用レスポップアップリンク化
        if ($_conf['quote_res_view']) {
            if (strlen($name) && $name != $this->BBS_NONAME_NAME) {
                $name = preg_replace_callback(
                    $this->getAnchorRegex('/(?:^|%prefix%)(%nums%)/'),
                    array($this, 'quote_name_callback'), $name
                );
            }
        }

        if ($trip) {
            $name .= $trip;
        } elseif ($name) {
            // 文字化け回避
            $name = $name . ' ';
            //if (in_array(0xF0 & ord(substr($name, -1)), array(0x80, 0x90, 0xE0))) {
            //    $name .= ' ';
            //}
        }

        return $name;
    }

    // }}}
    // {{{ transMsg()

    /**
     * datのレスメッセージをHTML表示用メッセージに変換する
     *
     * @param   string  $msg    メッセージ
     * @param   int     $mynum  レス番号
     * @return  string
     */
    public function transMsg($msg, $mynum)
    {
        global $_conf;
        global $pre_thumb_ignore_limit;

        // 2ch旧形式のdat
        if ($this->thread->dat_type == '2ch_old') {
            $msg = str_replace('＠｀', ',', $msg);
            $msg = preg_replace('/&amp(?=[^;])/', '&', $msg);
        }

        // &補正
        $msg = preg_replace('/&(?!#?\\w+;)/', '&amp;', $msg);

        // Safariから投稿されたリンク中チルダの文字化け補正
        //$msg = preg_replace('{(h?t?tp://[\w\.\-]+/)～([\w\.\-%]+/?)}', '$1~$2', $msg);

        // >>1のリンクをいったん外す
        // <a href="../test/read.cgi/accuse/1001506967/1" target="_blank">&gt;&gt;1</a>
        $msg = preg_replace('{<[Aa] .+?>(&gt;&gt;\\d[\\d\\-]*)</[Aa]>}', '$1', $msg);

        // 本来は2chのDAT時点でなされていないとエスケープの整合性が取れない気がする。（URLリンクのマッチで副作用が出てしまう）
        //$msg = str_replace(array('"', "'"), array('&quot;', '&#039;'), $msg);

        // 2006/05/06 ノートンの誤反応対策 body onload=window()
        $msg = str_replace('onload=window()', '<i>onload=window</i>()', $msg);

        // 新着レスの画像は表示制限を無視する設定なら
        if ($mynum > $this->thread->readnum && $_conf['expack.ic2.newres_ignore_limit']) {
            $pre_thumb_ignore_limit = TRUE;
        }

        // 文末の改行と連続する改行を除去
        if ($_conf['strip_linebreaks']) {
            $msg = $this->stripLineBreaks($msg /*, ' <br><span class="stripped">***</span><br> '*/);
        }

        // 引用やURLなどをリンク
        $msg = $this->transLink($msg);

        // Wikipedia記法への自動リンク
        if ($_conf['link_wikipedia']) {
            $msg = $this->wikipediaFilter($msg);
        }

        return $msg;
    }

    // }}}
    // {{{ _abornedRes()

    /**
     * あぼーんレスのHTMLを取得する
     *
     * @param  string $res_id
     * @return string
     */
    protected function _abornedRes($res_id)
    {
        return <<<EOP
<div id="{$res_id}" class="res aborned">
<div class="res-header">&nbsp;</div>
<div class="message">&nbsp;</div>
</div>\n
EOP;
    }

    // }}}
    // {{{ idFilter()

    /**
     * IDフィルタリングポップアップ変換
     *
     * @param   string  $idstr  ID:xxxxxxxxxx
     * @param   string  $id        xxxxxxxxxx
     * @return  string
     */
    public function idFilter($idstr, $id)
    {
        global $_conf;

        // IDは8桁または10桁(+携帯/PC識別子)と仮定して
        /*
        if (strlen($id) % 2 == 1) {
            $id = substr($id, 0, -1);
        }
        */
        $num_ht = '';
        if (isset($this->thread->idcount[$id]) && $this->thread->idcount[$id] > 0) {
            $num = (string) $this->thread->idcount[$id];
            if ($_conf['iframe_popup'] == 3) {
                $num_ht = ' <img src="img/ida.png" width="2" height="12" alt="">';
                $num_ht .= preg_replace('/\\d/', '<img src="img/id\\0.png" height="12" alt="">', $num);
                $num_ht .= '<img src="img/idz.png" width="2" height="12" alt=""> ';
            } else {
                $num_ht = '('.$num.')';
            }
        } else {
            return $idstr;
        }

        if ($_conf['coloredid.enable'] > 0) {
            if ($this->_ids_for_render === null) $this->_ids_for_render = array();
            $this->_ids_for_render[substr($id, 0, 8)] = $this->thread->idcount[$id];
            if ($_conf['coloredid.click'] > 0) {
                $num_ht = '<a href="javascript:void(0);" class="' . ShowThreadPc::cssClassedId($id) . '" onClick="idCol.click(\'' . substr($id, 0, 8) . '\', event); return false;" onDblClick="this.onclick(event); return false;">' . $num_ht . '</a>';
            }
            $idstr = $this->coloredIdStr(
                $idstr, $id, $_conf['coloredid.click'] > 0 ? true : false);
        }

        $word = rawurlencode($id);
        $filter_url = "{$_conf['read_php']}?bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;host={$this->thread->host}&amp;ls=all&amp;field=id&amp;word={$word}&amp;method=just&amp;match=on&amp;idpopup=1&amp;offline=1";

        if ($_conf['iframe_popup']) {
            return $this->iframePopup($filter_url, $idstr, $_conf['bbs_win_target_at']) . $num_ht;
        }
        return "<a href=\"{$filter_url}\"{$_conf['bbs_win_target_at']}>{$idstr}</a>{$num_ht}";
    }

    // }}}
    // {{{ link_wikipedia()

    /**
     * @see ShowThread
     */
    function link_wikipedia($word) {
        global $_conf;
        $link = 'http://ja.wikipedia.org/wiki/' . rawurlencode($word);
        return  '<a href="' . ($_conf['through_ime'] ?
            P2Util::throughIme($link) : $link) .
            "\"{$_conf['ext_win_target_at']}>{$word}</a>";
    }

    // }}}
    // {{{ quoteRes()

    /**
     * 引用変換（単独）
     *
     * @param   string  $full           >>1-100
     * @param   string  $qsign          >>
     * @param   string  $appointed_num    1-100
     * @return  string
     */
    public function quoteRes($full, $qsign, $appointed_num, $anchor_jump = false)
    {
        global $_conf;

        $appointed_num = mb_convert_kana($appointed_num, 'n');   // 全角数字を半角数字に変換
        if (preg_match("/\D/",$appointed_num)) {
            $appointed_num = preg_replace('/\D+/', '-', $appointed_num);
            return $this->quoteResRange($full, $qsign, $appointed_num);
        }
        if (preg_match("/^0/", $appointed_num)) {
            return $full;
        }

        $qnum = intval($appointed_num);
        if ($qnum < 1 || $qnum > sizeof($this->thread->datlines)) {
            return $full;
        }

        if ($anchor_jump && $qnum >= $this->thread->resrange['start'] && $qnum <= $this->thread->resrange['to']) {
            $read_url = '#' . ($this->_matome ? "t{$this->_matome}" : '') . "r{$qnum}";
        } else {
            $read_url = "{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;offline=1&amp;ls={$appointed_num}";
        }
        $attributes = $_conf['bbs_win_target_at'];
        if ($_conf['quote_res_view']) {
            if ($this->_matome) {
                $qres_id = "t{$this->_matome}qr{$qnum}";
            } else {
                $qres_id = "qr{$qnum}";
            }
            $attributes .= " onmouseover=\"showResPopUp('{$qres_id}',event)\"";
            $attributes .= " onmouseout=\"hideResPopUp('{$qres_id}')\"";
        }
        return "<a href=\"{$read_url}\"{$attributes}>{$full}</a>";
    }

    // }}}
    // {{{ quoteResRange()

    /**
     * 引用変換（範囲）
     *
     * @param   string  $full           >>1-100
     * @param   string  $qsign          >>
     * @param   string  $appointed_num    1-100
     * @return  string
     */
    public function quoteResRange($full, $qsign, $appointed_num)
    {
        global $_conf;

        if ($appointed_num == '-') {
            return $full;
        }

        $read_url = "{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;offline=1&amp;ls={$appointed_num}n";

        if ($_conf['iframe_popup']) {
            $pop_url = $read_url . "&amp;renzokupop=true";
            return $this->iframePopup(array($read_url, $pop_url), $full, $_conf['bbs_win_target_at'], 1);
        }

        // 普通にリンク
        return "<a href=\"{$read_url}\"{$_conf['bbs_win_target_at']}>{$full}</a>";

        // 1つ目を引用レスポップアップ
        /*
        $qnums = explode('-', $appointed_num);
        $qlink = $this->quoteRes($qsign . $qnum[0], $qsign, $qnum[0]) . '-';
        if (isset($qnums[1])) {
            $qlink .= $qnums[1];
        }
        return $qlink;
        */
    }

    // }}}
    // {{{ iframePopup()

    /**
     * HTMLポップアップ変換
     *
     * @param   string|array    $url
     * @param   string|array    $str
     * @param   string          $attr
     * @param   int|null        $mode
     * @return  string
     */
    public function iframePopup($url, $str, $attr = '', $mode = null)
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
            $pop_str = null;
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
        if ($_conf['iframe_popup_event'] == 1) {
            $pop_attr .= " onClick=\"stophide=true; showHtmlPopUp('{$pop_url}',event,0); return false;\"";
        } else {
            $pop_attr .= " onmouseover=\"showHtmlPopUp('{$pop_url}',event,{$_conf['iframe_popup_delay']})\"";
        }
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
            $pop_str = "<img src=\"{$pop_img}\" width=\"{$x}\" height=\"{$y}\" hspace=\"2\" vspace=\"0\" border=\"0\" align=\"top\" alt=\"\">";
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

    // }}}
    // {{{ iframePopupCallback()

    /**
     * HTMLポップアップ変換（コールバック用インターフェース）
     *
     * @param   array   $s  正規表現にマッチした要素の配列
     * @return  string
     */
    public function iframePopupCallback($s)
    {
        return $this->iframePopup(htmlspecialchars($s[1], ENT_QUOTES, 'Shift_JIS', false),
                                  htmlspecialchars($s[3], ENT_QUOTES, 'Shift_JIS', false),
                                  $s[2]);
    }

    // }}}
    // {{{ coloredIdStr()

    /**
     * Merged from http://jiyuwiki.com/index.php?cmd=read&page=rep2%A4%C7%A3%C9%A3%C4%A4%CE%C7%D8%B7%CA%BF%A7%CA%D1%B9%B9&alias%5B%5D=pukiwiki%B4%D8%CF%A2
     *
     * @access  private
     * @return  string
     */
    function coloredIdStr($idstr, $id, $classed = false)
    {
        global $_conf;

        if (!(isset($this->thread->idcount[$id])
                && $this->thread->idcount[$id] > 1)) return $idstr;

        $colored = array();
        if (!$classed) {
            switch ($_conf['coloredid.rate.type']) {
            case 1:
                $rate = $_conf['coloredid.rate.times'];
                break;
            case 2:
                $rate = $this->getIdCountRank(10);
                break;
            case 3:
                $rate = $this->getIdCountAverage();
                break;
            default:
                return $idstr;
            }
            if ($rate > 1 && $this->thread->idcount[$id] >= $rate)
                $colored = $this->coloredIdStyle($id);
        }

        $ret = ''; $i = 0;
        foreach ($arr = explode(':', $idstr) as $str) {   // コロンでID文字列を分割
            if (isset($arr[$i + 1])) $str .= ':';
            if ($classed) {
                $ret .= '<span class="' . ShowThreadPc::cssClassedId($id)
                    . ($i == 0 ? '-l' : '-b') . '">' . $str . '</span>';
            } else {
                if ($colored[$i]) {
                    $ret .= "<span style=\"{$colored[$i]}\">{$str}</span>";
                } else {
                    $ret .= $str;
                }
            }
            $i++;
        }
        return $ret;
    }

    // }}}
    // {{{ cssClassedId()

    static public function cssClassedId($id) {
        return 'idcss-' . bin2hex(
            base64_decode(str_replace('.', '+', substr($id, 0, 8))));
    }

    // }}}
    // {{{ coloredIdStyle()

    /**
     * @param   string  $id        xxxxxxxxxx
     * @return  array(style1, style2 [, debug])
     */
    function coloredIdStyle($id)
    {
        global $_conf, $STYLE;

        // Version.20081215
        //   ID本体とは別に、ID:の部分に別の背景色を適用
        //   色変換処理をcolorchange.phpにまとめた。
        //   その他、こまごまとした修正
        // Version.20081216
        //   HSV,HLSに加え、L*C*h表色系にも対応（ライブラリも修正）
        // Version.20081224 設定で変換前と変換後のカラーコードを表示できるようにした（デバッグ用？）
        // Version.20081228 色パラメータ設定を色空間別に個別化
        require_once P2_LIB_DIR . '/colorchange.php';

        if (!(isset($this->thread->idcount[$id]))) return null;
        if (isset($this->idstyles[$id])) return $this->idstyles[$id];

        // IDから色の元を抽出
        $coldiv=360; // 色相環の分割数
        $arr1 = unpack('N', pack('H', 0) .
            base64_decode(str_replace('.', '+', substr($id, 0, 4))));
        $arr2 = unpack('N', pack('H', 0) .
            base64_decode(str_replace('.', '+', substr($id, 4, 4))));
        $color=$arr1[1] % $coldiv;
        $color2=$arr2[1] % $coldiv;


        // HSV（またはHLS）パラメータ設定
        // レス数が増えるほど、色が濃く、暗くなる

        // 色相H：値域0～360（角度）
        $h= $color*360/$coldiv;
        $h2=$color2*360/$coldiv;

        $colorMode=2;       // 0:HSV,1:HSL,2:L*C*h
        switch ($colorMode) {
        case 0:     // HSV色空間
            // 彩度S(HSV)：値域0（淡い）～1（濃い)
            $S=$this->thread->idcount[$id]*0.05;
            if ($S>1) {$S=1;}

            // 明度V(HSV)：値域0（暗い）～1（明るい）
            $V=1   -$this->thread->idcount[$id]*0.025;
            if ($L<0.1) {$L=0.1;}

            $color_param=array(
                array($h,$S,$V,$colorMode), // 背景色（ID本体）
                array($h2,1,0.6,$colorMode)  // 背景色（ID:部分）
            );
            break;
        case 1:  // HLS色空間
            // 輝度L(HLS)：値域0（黒）～0.5（純色）～1（白）
            $L=0.95   -$this->thread->idcount[$id]*0.025;
            if ($L<0.1) {$L=0.1;}

            // 彩度S(HLS)：値域0（灰色）～1（純色）
            $S=$this->thread->idcount[$id]*0.05;
            if ($S>1) {$S=1;}

            $color_param=array(
                array($h,$L,$S,$colorMode), // 背景色（ID本体）
                array($h2,0.6,0.5,$colorMode)  // 背景色（ID:部分）
            );
            break;
        case 2:  // L*C*h色空間
            // 明度L*(L*C*h)：値域0（黒）～50（純色）～100（白）
            $L=100   -$this->thread->idcount[$id]*2.5;
            if ($L<10) {$L=10;}

            // 彩度C*(L*C*h)：値域0（灰色）～100（純色）
            $C=floor(40*sin(deg2rad($this->thread->idcount[$id]*180/50)) + 8);
            if ($C<0) {$C=0;}
            $C += (30 - $L > 0) ? 30 - $L : 0;

            $color_param=array(
                array($L,$C,$h,$colorMode), // 背景色（ID本体）
                array(50,60,$h2,$colorMode)  // 背景色（ID:部分）
            );
            break;
        }

        // 色空間に関する参考資料
        // HSV,HLS色空間 http://tt.sakura.ne.jp/~hiropon/lecture/color.html
        // L*C*h表色系 http://konicaminolta.jp/instruments/colorknowledge/part1/08.html
        // L*a*b*表色系 http://konicaminolta.jp/instruments/colorknowledge/part1/07.html
        // RGBに変換
        $rgb=array();
        for($key=0;$key<count($color_param);$key++) {
            $colorMode=$color_param[$key][3];
            if ($colorMode==2) {
                array_push($rgb,LCh2RGB($color_param[$key]));
            } else {
                array_push($rgb,$colorMode 
                    ? HLS2RGB($color_param[$key])
                    : HSV2RGB($color_param[$key])
                );
                //  unset($color_param[$key]);
            }
        }

        // CSSで色をつける
        $idstr2=preg_split('/:/',$idstr,2); // コロンでID文字列を分割
        $idstr2[0].=':';
        $uline=$STYLE['a_underline_none']==1 ? '' : "text-decoration:underline;";
        $bcolor=array();
        $LCh=array();
        for ($i=0;$i<count($rgb);$i++) {
            if ($rgb['type']=='L*C*h') {
                $LCh[$i]=$color_param[$i];
            } else {
                $LCh[$i]=RGB2LCh($rgb[$i]);
               /*  if ($LCh[$i][0]<70 && $LCh[$i][0]>40) {
                  $LCh[$i][0]-=30;
                  $rgb[$i]=LCh2RGB($LCh[$i]);
               }*/
            }
            $colorcode=$rgb[$i]['color'];
            $bcolor[$i]="background-color:{$colorcode};";
            //    $border="border-width:thin;border-style:solid;";

            if      ($LCh[$i][0]>60) {$bcolor[$i].="color:#000;";}
            else //if ($LCh[$i][0]<40) 
            {$bcolor[$i].="color:#fff;";}
        }

        if ($_conf['coloredid.rate.hissi.times'] > 0 && $this->thread->idcount[$id]>=$_conf['coloredid.rate.hissi.times']) {     // 必死チェッカー発動
            $uline.="text-decoration:blink;";
        }

        //       $colorprint=1;      // 1にすると、色の変換結果が表示される
        if ($colorprint) {
            $debug = '';
            for ($i=0;$i<1;$i++) {
                switch ($rgb[$i]['type']) {
                case 'L*C*h' :
                    $debug.= "(L*={$rgb[$i][9]},C*={$rgb[$i][10]},h={$rgb[$i][11]})";
                    $X=$rgb[$i][3];
                    $Y=$rgb[$i][4];
                    $Z=$rgb[$i][5];
                    if ($X>0.9504 || $X<0) {$X="<span style=\"color:#F00\">{$X}</span>";}
                    if ($Y>1 || $Y<0) {$Y="<span style=\"color:#F00\">{$Y}</span>";}
                    if ($Z>1.0889 || $Z<0) {$Z="<span style=\"color:#F00\">{$Z}</span>";}
                    $debug.= ",(X={$X},Y={$Y},Z={$Z})";

                    break;
                case 'HSV' :$debug.= "(H={$rgb[$i][3]},S={$rgb[$i][4]},V={$rgb[$i][5]})";
                    break;
                case 'HLS' :$debug.= "(H={$rgb[$i][3]},L={$rgb[$i][4]},S={$rgb[$i][5]})";
                    break;
                }

                $R=$rgb[$i][0];
                $G=$rgb[$i][1];
                $B=$rgb[$i][2];
                if ($R>255 || $R<0) {$R="<span style=\"color:#F00\">{$R}</span>";}
                if ($G>255 || $G<0) {$G="<span style=\"color:#F00\">{$G}</span>";}
                if ($B>255 || $B<0) {$B="<span style=\"color:#F00\">{$B}</span>";}
                $debug.= ",(R={$R},G={$G},B={$B}),{$rgb[$i]['color']}";
            }
            //  $idstr2[1].= join(",",$rgb[0]);
            $this->idstyles[$id] = array(
                (isset($rgb[1]) ? "{$bcolor[1]}{$border}{$uline}" : ''),
                "{$bcolor[0]}{$border}{$uline}",
                $debug);
        } else {
            $this->idstyles[$id] = array(
                (isset($rgb[1]) ? "{$bcolor[1]}{$border}{$uline}" : ''),
                "{$bcolor[0]}{$border}{$uline}");
        }
        return $this->idstyles[$id];
    }

    // }}}
    // {{{ ユーティリティメソッド
    // {{{ checkQuoteResNums()

    /**
     * HTMLメッセージ中の引用レスの番号を再帰チェックする
     */
    public function checkQuoteResNums($res_num, $name, $msg)
    {
        global $_conf;

        // 再帰リミッタ
        if ($this->_quote_check_depth > 30) {
            return array();
        } else {
            $this->_quote_check_depth++;
        }

        $quote_res_nums = array();

        $name = preg_replace('/(◆.*)/', '', $name, 1);

        // 名前
        if ($matches = $this->getQuoteResNumsName($name)) {
            foreach ($matches as $a_quote_res_num) {
                if ($a_quote_res_num) {
                    $quote_res_nums[] = $a_quote_res_num;
                    $a_quote_res_idx = $a_quote_res_num - 1;

                    // 自分自身の番号と同一でなければ、
                    if ($a_quote_res_num != $res_num) {
                        // チェックしていない番号を再帰チェック
                        if (!isset($this->_quote_res_nums_checked[$a_quote_res_num])) {
                            $this->_quote_res_nums_checked[$a_quote_res_num] = true;
                            if (isset($this->thread->datlines[$a_quote_res_idx])) {
                                $datalinear = $this->thread->explodeDatLine($this->thread->datlines[$a_quote_res_idx]);
                                $quote_name = $datalinear[0];
                                $quote_msg = $this->thread->datlines[$a_quote_res_idx];
                                $quote_res_nums = array_merge($quote_res_nums, $this->checkQuoteResNums($a_quote_res_num, $quote_name, $quote_msg));
                            }
                         }
                     }
                }
                // $name=preg_replace("/([0-9]+)/", "", $name, 1);
            }
        }

        // >>1のリンクをいったん外す
        // <a href="../test/read.cgi/accuse/1001506967/1" target="_blank">&gt;&gt;1</a>
        $msg = preg_replace('{<[Aa] .+?>(&gt;&gt;[1-9][\\d\\-]*)</[Aa]>}', '$1', $msg);

        //echo $msg;
        if (preg_match_all($this->getAnchorRegex('/%full%/'), $msg, $out, PREG_PATTERN_ORDER)) {
            foreach ($out[2] as $numberq) {
                if ($matches=preg_split($this->getAnchorRegex('/%delimiter%/'), $numberq)) {
                    foreach ($matches as $a_quote_res_num) {
                        if (preg_match($this->getAnchorRegex('/%range_delimiter%/'),$a_quote_res_num)) { continue;}
                        $a_quote_res_num = (int) (mb_convert_kana($a_quote_res_num, 'n'));
                        $a_quote_res_idx = $a_quote_res_num - 1;

                        //echo $a_quote_res_num;

                        if (!$a_quote_res_num) {break;}
                        $quote_res_nums[] = $a_quote_res_num;

                        // 自分自身の番号と同一でなければ、
                        if ($a_quote_res_num != $res_num) {
                            // チェックしていない番号を再帰チェック
                            if (!isset($this->_quote_res_nums_checked[$a_quote_res_num])) {
                                $this->_quote_res_nums_checked[$a_quote_res_num] = true;
                                if (isset($this->thread->datlines[$a_quote_res_idx])) {
                                    $datalinear = $this->thread->explodeDatLine($this->thread->datlines[$a_quote_res_idx]);
                                    $quote_name = $datalinear[0];
                                    $quote_msg = $this->thread->datlines[$a_quote_res_idx];
                                    $quote_res_nums = array_merge($quote_res_nums, $this->checkQuoteResNums($a_quote_res_num, $quote_name, $quote_msg));
                                }
                             }
                         }

                     }

                }

            }

        }

        if ($_conf['backlink_list'] > 0) {
            // レスが付いている場合はそれも対象にする
            $quote_from = $this->get_quote_from();
            if (array_key_exists($res_num, $quote_from)) {
                foreach ($quote_from[$res_num] as $quote_from_num) {
                    $quote_res_nums[] = $quote_from_num;
                    if ($quote_from_num != $res_num) {
                        if (!isset($this->_quote_res_nums_checked[$quote_from_num])) {
                            $this->_quote_res_nums_checked[$quote_from_num] = true;
                            if (isset($this->thread->datlines[$quote_from_num - 1])) {
                                $datalinear = $this->thread->explodeDatLine($this->thread->datlines[$quote_from_num - 1]);
                                $quote_name = $datalinear[0];
                                $quote_msg = $this->thread->datlines[$quote_from_num - 1];
                                $quote_res_nums = array_merge($quote_res_nums, $this->checkQuoteResNums($quote_from_num, $quote_name, $quote_msg));
                            }
                        }
                    }
                }
            }
        }

        return $quote_res_nums;
    }

    // }}}
    // {{{ imageHtmlPopup()

    /**
     * 画像をHTMLポップアップ&ポップアップウインドウサイズに合わせる
     */
    public function imageHtmlPopup($img_url, $img_tag, $link_str)
    {
        global $_conf;

        if ($_conf['expack.ic2.enabled'] && $_conf['expack.ic2.fitimage']) {
            $popup_url = 'ic2_fitimage.php?url=' . rawurlencode(str_replace('&amp;', '&', $img_url));
        } else {
            $popup_url = $img_url;
        }

        $pops = ($_conf['iframe_popup'] == 1) ? $img_tag . $link_str : array($link_str, $img_tag);
        return $this->iframePopup(array($img_url, $popup_url), $pops, $_conf['ext_win_target_at']);
    }

    // }}}
    // {{{ respopToAsync()

    /**
     * レスポップアップを非同期モードに加工する
     */
    public function respopToAsync($str)
    {
        $respop_regex = '/(onmouseover)=\"(showResPopUp\(\'(q(\d+)of\d+)\',event\).*?)\"/';
        $respop_replace = '$1="loadResPopUp(' . $this->asyncObjName . ', $4);$2"';
        return preg_replace($respop_regex, $respop_replace, $str);
    }

    // }}}
    // {{{ getASyncObjJs()

    /**
     * 非同期読み込みで利用するJavaScriptオブジェクトを生成する
     */
    public function getASyncObjJs()
    {
        global $_conf;
        static $done = array();

        if (isset($done[$this->asyncObjName])) {
            return;
        }
        $done[$this->asyncObjName] = TRUE;

        $code = <<<EOJS
<script type="text/javascript">
//<![CDATA[
var {$this->asyncObjName} = {
    host:"{$this->thread->host}", bbs:"{$this->thread->bbs}", key:"{$this->thread->key}",
    readPhp:"{$_conf['read_php']}", readTarget:"{$_conf['bbs_win_target']}"
};
//]]>
</script>\n
EOJS;
        return $code;
    }

    // }}}
    // {{{ getSpmObjJs()

    /**
     * スマートポップアップメニューを生成するJavaScriptコードを生成する
     */
    public function getSpmObjJs($retry = false)
    {
        global $_conf, $STYLE;

        if (isset(self::$_spm_objects[$this->spmObjName])) {
            return $retry ? self::$_spm_objects[$this->spmObjName] : '';
        }

        $ttitle_en = rawurlencode(base64_encode($this->thread->ttitle));

        if ($_conf['expack.spm.filter_target'] == '' || $_conf['expack.spm.filter_target'] == 'read') {
            $_conf['expack.spm.filter_target'] = '_self';
        }

        $motothre_url = $this->thread->getMotoThread();
        $motothre_url = substr($motothre_url, 0, strlen($this->thread->ls) * -1);

        $_spmOptions = array(
            'null',
            ((!$_conf['disable_res'] && $_conf['expack.spm.kokores']) ? (($_conf['expack.spm.kokores_orig']) ? '2' : '1') : '0'),
            (($_conf['expack.spm.ngaborn']) ? (($_conf['expack.spm.ngaborn_confirm']) ? '2' : '1') : '0'),
            (($_conf['expack.spm.filter']) ? '1' : '0'),
            (($this->am_on_spm) ? '1' : '0'),
            (($_conf['expack.aas.enabled']) ? '1' : '0'),
        );
        $spmOptions = implode(',', $_spmOptions);

        // エスケープ
        $_spm_title = StrCtl::toJavaScript($this->thread->ttitle_hc);
        $_spm_url = addslashes($motothre_url);
        $_spm_host = addslashes($this->thread->host);
        $_spm_bbs = addslashes($this->thread->bbs);
        $_spm_key = addslashes($this->thread->key);
        $_spm_ls = addslashes($this->thread->ls);

        $code = <<<EOJS
<script type="text/javascript">
//<![CDATA[\n
EOJS;

        if (!count(self::$_spm_objects)) {
            $code .= sprintf("spmFlexTarget = '%s';\n", StrCtl::toJavaScript($_conf['expack.spm.filter_target']));
            if ($_conf['expack.aas.enabled']) {
                $code .= sprintf("var aas_popup_width = %d;\n", $_conf['expack.aas.default.width'] + 10);
                $code .= sprintf("var aas_popup_height = %d;\n", $_conf['expack.aas.default.height'] + 10);
            }
        }

        $code .= <<<EOJS
var {$this->spmObjName} = {
    'objName':'{$this->spmObjName}',
    'rc':'{$this->thread->rescount}',
    'title':'{$_spm_title}',
    'ttitle_en':'{$ttitle_en}',
    'url':'{$_spm_url}',
    'host':'{$_spm_host}',
    'bbs':'{$_spm_bbs}',
    'key':'{$_spm_key}',
    'ls':'{$_spm_ls}',
    'spmOption':[{$spmOptions}]
};
SPM.init({$this->spmObjName});
//]]>
</script>\n
EOJS;

        self::$_spm_objects[$this->spmObjName] = $code;

        return $code;
    }

    // }}}
    // }}}
    // {{{ transLinkDo()から呼び出されるURL書き換えメソッド
    /**
     * これらのメソッドは引数が処理対象パターンに合致しないとFALSEを返し、
     * transLinkDo()はFALSEが返ってくると$_url_handlersに登録されている次の関数/メソッドに処理させようとする。
     */
    // {{{ plugin_linkURL()

    /**
     * URLリンク
     *
     * @param   string $url
     * @param   array $purl
     * @param   string $str
     * @return  string|false
     */
    public function plugin_linkURL($url, $purl, $str)
    {
        global $_conf;

        if (isset($purl['scheme'])) {
            // ime
            if ($_conf['through_ime']) {
                $link_url = P2Util::throughIme($purl[0]);
            } else {
                $link_url = $url;
            }

            $is_http = ($purl['scheme'] == 'http' || $purl['scheme'] == 'https');

            // HTMLポップアップ
            if ($_conf['iframe_popup'] && $is_http) {
                // p2pm/expm 指定の場合のみ、特別に手動転送指定を追加する
                if ($_conf['through_ime'] == 'p2pm') {
                    $pop_url = preg_replace('/\\?(enc=1&amp;)url=/', '?$1m=1&amp;url=', $link_url);
                } elseif ($_conf['through_ime'] == 'expm') {
                    $pop_url = preg_replace('/(&amp;d=-?\d+)?$/', '&amp;d=-1', $link_url);
                } else {
                    $pop_url = $link_url;
                }
                $link = $this->iframePopup(array($link_url, $pop_url), $str, $_conf['ext_win_target_at']);
            } else {
                $link = "<a href=\"{$link_url}\"{$_conf['ext_win_target_at']}>{$str}</a>";
            }

            // ブラクラチェッカ
            if ($_conf['brocra_checker_use'] && $_conf['brocra_checker_url'] && $is_http) {
                if (strlen($_conf['brocra_checker_query'])) {
                    $brocra_checker_url = $_conf['brocra_checker_url'] . '?' . $_conf['brocra_checker_query'] . '=' . rawurlencode($purl[0]);
                } else {
                    $brocra_checker_url = rtrim($_conf['brocra_checker_url'], '/') . '/' . $url;
                }
                // ブラクラチェッカ・ime
                if ($_conf['through_ime']) {
                    $brocra_checker_url = P2Util::throughIme($brocra_checker_url);
                }
                $check_mark = 'チェック';
                $check_mark_prefix = '[';
                $check_mark_suffix = ']';
                // ブラクラチェッカ・HTMLポップアップ
                if ($_conf['iframe_popup']) {
                    // p2pm/expm 指定の場合のみ、特別に手動転送指定を追加する
                    if ($_conf['through_ime'] == 'p2pm') {
                        $brocra_pop_url = preg_replace('/\\?(enc=1&amp;)url=/', '?$1m=1&amp;url=', $brocra_checker_url);
                    } elseif ($_conf['through_ime'] == 'expm') {
                        $brocra_pop_url = $brocra_checker_url . '&amp;d=-1';
                    } else {
                        $brocra_pop_url = $brocra_checker_url;
                    }
                    if ($_conf['iframe_popup'] == 3) {
                        $check_mark = '<img src="img/check.png" width="33" height="12" alt="">';
                        $check_mark_prefix = '';
                        $check_mark_suffix = '';
                    }
                    $brocra_checker_link = $this->iframePopup(array($brocra_checker_url, $brocra_pop_url), $check_mark, $_conf['ext_win_target_at']);
                } else {
                    $brocra_checker_link = "<a href=\"{$brocra_checker_url}\"{$_conf['ext_win_target_at']}>{$check_mark}</a>";
                }
                $link .= $check_mark_prefix . $brocra_checker_link . $check_mark_suffix;
            }

            return $link;
        }
        return FALSE;
    }

    // }}}
    // {{{ plugin_link2chSubject()

    /**
     * 2ch bbspink    板リンク
     *
     * @param   string $url
     * @param   array $purl
     * @param   string $str
     * @return  string|false
     */
    public function plugin_link2chSubject($url, $purl, $str)
    {
        global $_conf;

        if (preg_match('{^http://(\\w+\\.(?:2ch\\.net|bbspink\\.com))/(\\w+)/$}', $purl[0], $m)) {
            $subject_url = "{$_conf['subject_php']}?host={$m[1]}&amp;bbs={$m[2]}";
            return "<a href=\"{$url}\" target=\"subject\">{$str}</a> [<a href=\"{$subject_url}\" target=\"subject\">板をp2で開く</a>]";
        }
        return FALSE;
    }

    // }}}
    // {{{ plugin_linkThread()

    /**
     * スレッドリンク
     *
     * @param   string $url
     * @param   array $purl
     * @param   string $str
     * @return  string|false
     */
    public function plugin_linkThread($url, $purl, $str)
    {
        global $_conf;

        list($nama_url, $host, $bbs, $key, $ls) = P2Util::detectThread($purl[0]);
        if ($host && $bbs && $key) {
            $read_url = "{$_conf['read_php']}?host={$host}&amp;bbs={$bbs}&amp;key={$key}&amp;ls={$ls}";
            if ($_conf['iframe_popup']) {
                if ($ls && preg_match('/^[0-9\\-n]+$/', $ls)) {
                    $pop_url = $read_url;
                } else {
                    $pop_url = $read_url . '&amp;one=true';
                }
                return $this->iframePopup(array($read_url, $pop_url), $str, $_conf['bbs_win_target_at']);
            }
            return "<a href=\"{$read_url}{$_conf['bbs_win_target_at']}\">{$str}</a>";
        }

        return false;
    }

    // }}}
    // {{{ plugin_linkYouTube()

    /**
     * YouTubeリンク変換プラグイン
     *
     * Zend_Gdata_Youtubeを使えばサムネイルその他の情報を簡単に取得できるが...
     *
     * @param   string $url
     * @param   array $purl
     * @param   string $str
     * @return  string|false
     */
    public function plugin_linkYouTube($url, $purl, $str)
    {
        global $_conf;

        // http://www.youtube.com/watch?v=Mn8tiFnAUAI
        // http://m.youtube.com/watch?v=OhcX0xJsDK8&client=mv-google&gl=JP&hl=ja&guid=ON&warned=True
        if (preg_match('{^http://(www|jp|m)\\.youtube\\.com/watch\\?(?:.+&amp;)?v=([0-9a-zA-Z_\\-]+)}', $url, $m)) {
            // ime
            if ($_conf['through_ime']) {
                $link_url = P2Util::throughIme($url);
            } else {
                $link_url = $url;
            }

            // HTMLポップアップ
            if ($_conf['iframe_popup']) {
                $link = $this->iframePopup($link_url, $str, $_conf['ext_win_target_at']);
            } else {
                $link = "<a href=\"{$link_url}\"{$_conf['ext_win_target_at']}>{$str}</a>";
            }

            $subd = $m[1];
            $id = $m[2];

            if ($_conf['link_youtube'] == 2) {
                return <<<EOP
{$link} <img class="preview-video-switch" src="img/show.png" width="30" height="12" alt="show" onclick="preview_video_youtube('{$id}', this);">
EOP;
            } else {
                return <<<EOP
{$link}<div class="preview-video preview-video-youtuve"><object width="425" height="350"><param name="movie" value="http://www.youtube.com/v/{$id}" valuetype="ref" type="application/x-shockwave-flash"><param name="wmode" value="transparent"><embed src="http://www.youtube.com/v/{$id}" type="application/x-shockwave-flash" wmode="transparent" width="425" height="350"></object></div>
EOP;
            }
        }
        return FALSE;
    }

    // }}}
    // {{{ plugin_linkNicoNico()

    /**
     * ニコニコ動画変換プラグイン
     *
     * @param   string $url
     * @param   array $purl
     * @param   string $str
     * @return  string|false
     */
    public function plugin_linkNicoNico($url, $purl, $str)
    {
        global $_conf;

        // http://www.nicovideo.jp/watch?v=utbrYUJt9CSl0
        // http://www.nicovideo.jp/watch/utvWwAM30N0No
        // http://m.nicovideo.jp/watch/sm7044684
        if (preg_match('{^http://(?:www|m)\\.nicovideo\\.jp/watch(?:/|(?:\\?v=))([0-9a-zA-Z_-]+)}', $url, $m)) {
            // ime
            if ($_conf['through_ime']) {
                $link_url = P2Util::throughIme($purl[0]);
            } else {
                $link_url = $url;
            }

            // HTMLポップアップ
            if ($_conf['iframe_popup']) {
                $link = $this->iframePopup($link_url, $str, $_conf['ext_win_target_at']);
            } else {
                $link = "<a href=\"{$link_url}\"{$_conf['ext_win_target_at']}>{$str}</a>";
            }

            $id = $m[1];

            if ($_conf['link_niconico'] == 2) {
                return <<<EOP
{$link} <img class="preview-video-switch" src="img/show.png" width="30" height="12" alt="show" onclick="preview_video_niconico('{$id}', this);">
EOP;
            } else {
                return <<<EOP
{$link}<div class="preview-video preview-video-niconico"><iframe src="http://ext.nicovideo.jp/thumb/{$id}" width="425" height="175" scrolling="auto" frameborder="0"></iframe></div>
EOP;
            }
        }
        return FALSE;
    }

    // }}}
    // {{{ plugin_viewImage()

    /**
     * 画像ポップアップ変換
     *
     * @param   string $url
     * @param   array $purl
     * @param   string $str
     * @return  string|false
     */
    public function plugin_viewImage($url, $purl, $str)
    {
        global $_conf;
        global $pre_thumb_unlimited, $pre_thumb_limit;

        if (P2Util::isUrlWikipediaJa($url)) {
            return false;
        }

        // 表示制限
        if (!$pre_thumb_unlimited && empty($pre_thumb_limit)) {
            return false;
        }

        if (preg_match('{^https?://.+?\\.(jpe?g|gif|png)$}i', $purl[0]) && empty($purl['query'])) {
            $pre_thumb_limit--; // 表示制限カウンタを下げる
            $img_tag = "<img class=\"thumbnail\" src=\"{$url}\" height=\"{$_conf['pre_thumb_height']}\" weight=\"{$_conf['pre_thumb_width']}\" hspace=\"4\" vspace=\"4\" align=\"middle\">";

            if ($_conf['iframe_popup']) {
                $view_img = $this->imageHtmlPopup($url, $img_tag, $str);
            } else {
                $view_img = "<a href=\"{$url}\"{$_conf['ext_win_target_at']}>{$img_tag}{$str}</a>";
            }

            // ブラクラチェッカ （プレビューとは相容れないのでコメントアウト）
            /*if ($_conf['brocra_checker_use']) {
                $link_url_en = rawurlencode($url);
                if ($_conf['iframe_popup'] == 3) {
                    $check_mark = '<img src="img/check.png" width="33" height="12" alt="">';
                    $check_mark_prefix = '';
                    $check_mark_suffix = '';
                } else {
                    $check_mark = 'チェック';
                    $check_mark_prefix = '[';
                    $check_mark_suffix = ']';
                }
                $view_img .= $check_mark_prefix . "<a href=\"{$_conf['brocra_checker_url']}?{$_conf['brocra_checker_query']}={$link_url_en}\"{$_conf['ext_win_target_at']}>{$check_mark}</a>" . $check_mark_suffix;
            }*/

            return $view_img;
        }

        return false;
    }

    // }}}
    // {{{ plugin_imageCache2()

    /**
     * ImageCache2サムネイル変換
     *
     * @param   string $url
     * @param   array $purl
     * @param   string $str
     * @return  string|false
     */
    public function plugin_imageCache2($url, $purl, $str,
        $force = false, $referer = null)
    {
        global $_conf;
        global $pre_thumb_unlimited, $pre_thumb_ignore_limit, $pre_thumb_limit;
        static $serial = 0;

        if (P2Util::isUrlWikipediaJa($url)) {
            return false;
        }

        if ((preg_match('{^https?://.+?\\.(jpe?g|gif|png)$}i', $purl[0]) && empty($purl['query'])) || $force) {
            // 準備
            $serial++;
            $thumb_id = 'thumbs' . $serial . $this->thumb_id_suffix;
            $tmp_thumb = './img/ic_load.png';
            $url_ht = $url;
            $url = $purl[0];
            $url_en = rawurlencode($url) .
                ($referer ? '&amp;ref=' . rawurlencode($referer) : '');
            $img_id = null;

            $icdb = new IC2_DataObject_Images;

            // r=0:リンク;r=1:リダイレクト;r=2:PHPで表示
            // t=0:オリジナル;t=1:PC用サムネイル;t=2:携帯用サムネイル;t=3:中間イメージ
            $img_url = 'ic2.php?r=1&amp;uri=' . $url_en;
            $thumb_url = 'ic2.php?r=1&amp;t=1&amp;uri=' . $url_en;

            // DBに画像情報が登録されていたとき
            if ($icdb->get($url)) {
                $img_id = $icdb->id;

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
                    $cached = true;
                } else {
                    $cached = false;
                }

                // サムネイルが作成されていているときは画像を直接読み込む
                $_thumb_url = $this->thumbnailer->thumbPath($icdb->size, $icdb->md5, $icdb->mime);
                if (file_exists($_thumb_url)) {
                    $thumb_url = $_thumb_url;
                    // 自動スレタイメモ機能がONでスレタイが記録されていないときはDBを更新
                    if (!is_null($this->img_memo) && strpos($icdb->memo, $this->img_memo) === false){
                        $update = new IC2_DataObject_Images;
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

                $orig_img_url   = $img_url;
                $orig_thumb_url = $thumb_url;

            // 画像がキャッシュされていないとき
            // 自動スレタイメモ機能がONならクエリにUTF-8エンコードしたスレタイを含める
            } else {
                // 画像がブラックリストorエラーログにあるか確認
                if (false !== ($errcode = $icdb->ic2_isError($url))) {
                    return "<img class=\"thumbnail\" src=\"./img/{$errcode}.png\" width=\"32\" height=\"32\" hspace=\"4\" vspace=\"4\" align=\"middle\"> <s>{$str}</s>";
                }

                $cached = false;


                $orig_img_url   = $img_url;
                $orig_thumb_url = $thumb_url;
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
                    $show_thumb = false;
                } else {
                    $show_thumb = true;
                    $pre_thumb_limit--;
                }
            } else {
                $show_thumb = true;
            }

            // 表示モード
            if ($show_thumb) {
                $img_tag = "<img class=\"thumbnail\" src=\"{$thumb_url}\" {$thumb_size} hspace=\"4\" vspace=\"4\" align=\"middle\">";
                if ($_conf['iframe_popup']) {
                    $view_img = $this->imageHtmlPopup($img_url, $img_tag, $str);
                } else {
                    $view_img = "<a href=\"{$img_url}\"{$_conf['ext_win_target_at']}>{$img_tag}{$str}</a>";
                }
            } else {
                $img_tag = "<img id=\"{$thumb_id}\" class=\"thumbnail\" src=\"{$tmp_thumb}\" width=\"32\" height=\"32\" hspace=\"4\" vspace=\"4\" align=\"middle\">";
                $view_img = "<a href=\"{$img_url}\" onclick=\"return loadThumb('{$thumb_url}','{$thumb_id}')\"{$_conf['ext_win_target_at']}>{$img_tag}</a><a href=\"{$img_url}\"{$_conf['ext_win_target_at']}>{$str}</a>";
            }

            // ソースへのリンクをime付きで表示
            if ($_conf['expack.ic2.enabled'] && $_conf['expack.ic2.through_ime']) {
                $ime_url = P2Util::throughIme($url);
                if ($_conf['iframe_popup'] == 3) {
                    $ime_mark = '<img src="img/ime.png" width="22" height="12" alt="">';
                } else {
                    $ime_mark = '[ime]';
                }
                $view_img .= " <a class=\"img_through_ime\" href=\"{$ime_url}\"{$_conf['ext_win_target_at']}>{$ime_mark}</a>";
            }

            $view_img .= '<img class="ic2-info-opener" src="img/s2a.png" width="16" height="16" onclick="ic2info.show('
                       . (($img_id) ? $img_id : "'{$url_ht}'") . ', event)">';

            return $view_img;
        }

        return false;
    }

    /**
     * 置換画像URL+ImageCache2
     */
    function plugin_replaceImageURL($url, $purl, $str)
    {
        global $_conf;
        global $pre_thumb_unlimited, $pre_thumb_ignore_limit, $pre_thumb_limit;
        static $serial = 0;

        // +Wiki
        global $replaceimageurl;
        $replaced = $replaceimageurl->replaceImageURL($url);
        if (!$replaced[0]) return FALSE;

        foreach($replaced as $v) {
            $url_en = rawurlencode($v['url']);
            $url_ht = htmlspecialchars($v['url'], ENT_QUOTES);
            $ref_en = $v['referer'] ? '&amp;ref=' . rawurlencode($v['referer']) : '';

            // 準備
            $serial++;
            $thumb_id = 'thumbs' . $serial . $this->thumb_id_suffix;
            $tmp_thumb = './img/ic_load.png';

            $icdb = new IC2_DataObject_Images;

            // r=0:リンク;r=1:リダイレクト;r=2:PHPで表示
            // t=0:オリジナル;t=1:PC用サムネイル;t=2:携帯用サムネイル;t=3:中間イメージ
            // +Wiki
            $img_url = 'ic2.php?r=1&amp;uri=' . $url_en . $ref_en;
            $thumb_url = 'ic2.php?r=1&amp;t=1&amp;uri=' . $url_en . $ref_en;

            // DBに画像情報が登録されていたとき
            if ($icdb->get($v['url'])) {

                // ウィルスに感染していたファイルのとき
                if ($icdb->mime == 'clamscan/infected') {
                    $result .= "<img class=\"thumbnail\" src=\"./img/x04.png\" width=\"32\" height=\"32\" hspace=\"4\" vspace=\"4\" align=\"middle\">";
                    continue;
                }
                // あぼーん画像のとき
                if ($icdb->rank < 0) {
                    $result .= "<img class=\"thumbnail\" src=\"./img/x01.png\" width=\"32\" height=\"32\" hspace=\"4\" vspace=\"4\" align=\"middle\">";
                    continue;
                }

                // オリジナルがキャッシュされているときは画像を直接読み込む
                $_img_url = $this->thumbnailer->srcPath($icdb->size, $icdb->md5, $icdb->mime);
                if (file_exists($_img_url)) {
                    $img_url = $_img_url;
                    $cached = true;
                } else {
                    $cached = false;
                }

                // サムネイルが作成されていているときは画像を直接読み込む
                $_thumb_url = $this->thumbnailer->thumbPath($icdb->size, $icdb->md5, $icdb->mime);
                if (file_exists($_thumb_url)) {
                    $thumb_url = $_thumb_url;
                    // 自動スレタイメモ機能がONでスレタイが記録されていないときはDBを更新
                    if (!is_null($this->img_memo) && strpos($icdb->memo, $this->img_memo) === false){
                        $update = new IC2_DataObject_Images;
                        if (!is_null($icdb->memo) && strlen($icdb->memo) > 0) {
                            $update->memo = $this->img_memo . ' ' . $icdb->memo;
                        } else {
                            $update->memo = $this->img_memo;
                        }
                        $update->whereAddQuoted('uri', '=', $v['url']);
                        $update->update();
                    }
                }

                // サムネイルの画像サイズ
                $thumb_size = $this->thumbnailer->calc($icdb->width, $icdb->height);
                $thumb_size = preg_replace('/(\d+)x(\d+)/', 'width="$1" height="$2"', $thumb_size);
                $tmp_thumb = './img/ic_load1.png';

                $orig_img_url   = $img_url;
                $orig_thumb_url = $thumb_url;

            // 画像がキャッシュされていないとき
            // 自動スレタイメモ機能がONならクエリにUTF-8エンコードしたスレタイを含める
            } else {
                // 画像がブラックリストorエラーログにあるか確認
                if (false !== ($errcode = $icdb->ic2_isError($v['url']))) {
                    $result .= "<img class=\"thumbnail\" src=\"./img/{$errcode}.png\" width=\"32\" height=\"32\" hspace=\"4\" vspace=\"4\" align=\"middle\">";
                    continue;
                }

                $cached = false;


                $orig_img_url   = $img_url;
                $orig_thumb_url = $thumb_url;
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
                    $show_thumb = false;
                } else {
                    $show_thumb = true;
                    $pre_thumb_limit--;
                }
            } else {
                $show_thumb = true;
            }

            // 表示モード
            if ($show_thumb) {
                $img_tag = "<img class=\"thumbnail\" src=\"{$thumb_url}\" {$thumb_size} hspace=\"4\" vspace=\"4\" align=\"middle\">";
                if ($_conf['iframe_popup']) {
                    $view_img = $this->imageHtmlPopup($img_url, $img_tag, '');
                } else {
                    $view_img = "<a href=\"{$img_url}\"{$_conf['ext_win_target_at']}>{$img_tag}</a>";
                }
            } else {
                $img_tag = "<img id=\"{$thumb_id}\" class=\"thumbnail\" src=\"{$tmp_thumb}\" width=\"32\" height=\"32\" hspace=\"4\" vspace=\"4\" align=\"middle\">";
                $view_img = "<a href=\"{$img_url}\" onclick=\"return loadThumb('{$thumb_url}','{$thumb_id}')\"{$_conf['ext_win_target_at']}>{$img_tag}</a><a href=\"{$img_url}\"{$_conf['ext_win_target_at']}></a>";
            }

            $view_img .= '<img class="ic2-info-opener" src="img/s2a.png" width="16" height="16" onclick="ic2info.show('
                    //. "'{$url_ht}', '{$orig_img_url}', '{$_conf['ext_win_target']}', '{$orig_thumb_url}', event)\">";
                      . "'{$url_ht}', event)\">";

            $result .= $view_img;
        }
        // ソースへのリンクをime付きで表示
        $ime_url = P2Util::throughIme($url);
        $result .= "<a class=\"img_through_ime\" href=\"{$ime_url}\"{$_conf['ext_win_target_at']}>{$str}</a>";
        return $result;
    }

    /**
     * +Wiki:リンクプラグイン
     */
    function plugin_linkPlugin($url, $purl, $str)
    {
        global $linkplugin;
        return $linkplugin->replaceLinkToHTML($url, $str);
    }

    // }}}
    // {{{ plugin_imepita_to_imageCache2()

    /**
     * imepitaのURLを加工してImageCache2させるプラグイン
     *
     * @param   string $url
     * @param   array $purl
     * @param   string $str
     * @return  string|false
     */
    public function plugin_imepita_to_imageCache2($url, $purl, $str)
    {
        if (preg_match('{^https?://imepita\.jp/(?:image/)?(\d{8}/\d{6})}i',
                $purl[0], $m) && empty($purl['query'])) {
            $_url = 'http://imepita.jp/image/' . $m[1];
            $_purl = @parse_url($_url);
            $_purl[0] = $_url;
            return $this->plugin_imageCache2($_url, $_purl, $str, true, $url);
        }
        return false;
    }

    // }}}
    // }}}

    public function get_quotebacks_json() {
        if ($this->_quote_from === null) {
            $this->_make_quote_from();  // 被レスデータ集計
        }
        $ret = array();
        foreach ($this->_quote_from as $resnum => $quote_from) {
            if (!$quote_from) continue;
            if ($resnum != 1 && ($resnum < $this->thread->resrange['start'] || $resnum > $this->thread->resrange['to'])) continue;
            $tmp = array();
            foreach ($quote_from as $quote) {
                if ($quote != 1 && ($quote < $this->thread->resrange['start'] || $quote > $this->thread->resrange['to'])) continue;
                $tmp[] = $quote;
            }
            if ($tmp) $ret[] = "{$resnum}:[" . join(',', $tmp) . "]";
        }
        return '{' . join(',', $ret) . '}';
    }

    public function getResColorJs() {
        global $_conf, $STYLE;
        $fontstyle_bold = empty($STYLE['fontstyle_bold']) ? 'normal' : $STYLE['fontstyle_bold'];
        $fontweight_bold = empty($STYLE['fontweight_bold']) ? 'normal' : $STYLE['fontweight_bold'];
        $fontfamily_bold = $STYLE['fontfamily_bold'];
        $backlinks = $this->get_quotebacks_json();
        $colors = array();
        $backlink_colors = join(',',
            array_map(create_function('$x', 'return "\'{$x}\'";'),
                explode(',', $_conf['backlink_coloring_track_colors']))
        );
        $prefix = $this->_matome ? "t{$this->_matome}" : '';
        return <<<EOJS
<script type="text/javascript">
if (typeof rescolObjs == 'undefined') rescolObjs = [];
rescolObjs.push((function() {
    var obj = new BacklinkColor('{$prefix}');
    obj.colors = [{$backlink_colors}];
    obj.highlightStyle = {fontStyle :'{$fontstyle_bold}', fontWeight : '{$fontweight_bold}', fontFamily : '{$fontfamily_bold}'};
    obj.backlinks = {$backlinks};
    return obj;
})());
</script>
EOJS;
    }

    public function get_ids_for_render_json() {
        $ret = array();
        if ($this->_ids_for_render) {
            foreach ($this->_ids_for_render as $id => $count) {
                $ret[] = "'{$id}':{$count}";
            }
        }
        return '{' . join(',', $ret) . '}';
    }

    public function getIdColorJs() {
        global $_conf, $STYLE;
        if ($_conf['coloredid.enable'] < 1 || $_conf['coloredid.click'] < 1)
            return '';
        if (count($this->thread->idcount) < 1) return;

        $idslist = $this->get_ids_for_render_json();

        $rate = $_conf['coloredid.rate.times'];
        $tops = $this->getIdCountRank(10);
        $average = $this->getIdCountAverage();
        $color_init = '';
        if ($_conf['coloredid.rate.type'] > 0) {
            switch($_conf['coloredid.rate.type']) {
            case 2:
                $init_rate = $tops;
                break;
            case 3:
                $init_rate = $average;
                break;
            case 1:
                $init_rate = $rate;
            default:
            }
            if ($init_rate > 1)
                $color_init .= 'idCol.initColor(' . $init_rate . ', idslist);';
        }
        $color_init .= "idCol.rate = {$rate};";
        if (!$this->_matome) {
            $color_init .= "idCol.tops = {$tops};";
            $color_init .= "idCol.average = {$average};";
        }
        $hissiCount = $_conf['coloredid.rate.hissi.times'];
        $mark_colors = join(',',
            array_map(create_function('$x', 'return "\'{$x}\'";'),
                explode(',', $_conf['coloredid.marking.colors']))
        );
        $fontstyle_bold = empty($STYLE['fontstyle_bold']) ? 'normal' : $STYLE['fontstyle_bold'];
        $fontweight_bold = empty($STYLE['fontweight_bold']) ? 'normal' : $STYLE['fontweight_bold'];
        $fontfamily_bold = $STYLE['fontfamily_bold'];
        $uline = $STYLE['a_underline_none'] != 1
            ? 'idCol.colorStyle["textDecoration"] = "underline"' : '';
        return <<<EOJS
<script>
(function() {
var idslist = {$idslist};
if (typeof idCol == 'undefined') {
    idCol = new IDColorChanger(idslist, {$hissiCount});
    idCol.colors = [{$mark_colors}];
{$uline};
    idCol.highlightStyle = {fontStyle :'{$fontstyle_bold}', fontWeight : '{$fontweight_bold}', fontFamily : '{$fontfamily_bold}', fontSize : '104%'};
} else idCol.addIdlist(idslist);
{$color_init}
idCol.setupSPM('{$this->spmObjName}');
})();
</script>
EOJS;
    }

    public function getIdCountAverage() {
        if ($this->_idcount_average !== null) return $this->_idcount_average;
        $sum = 0; $param = 0;
        foreach ($this->thread->idcount as $count) {
            if ($count > 1) {
                $sum += $count;
                $param++;
            }
        }
        return $this->_idcount_average = $param < 1 ? 0 : ceil($sum / $param);
    }

    public function getIdCountRank($rank) {
        if ($this->_idcount_tops !== null) return $this->_idcount_tops;
        $ranking = array();
        foreach ($this->thread->idcount as $count) {
            if ($count > 1) $ranking[] = $count;
        }
        if (count($ranking) == 0) return 0;
        rsort($ranking);
        $result = count($ranking) >= $rank ? $ranking[$rank - 1] : $ranking[count($ranking) - 1];
        return $this->_idcount_tops = $result;
    }
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
