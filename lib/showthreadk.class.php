<?php
require_once P2_LIBRARY_DIR . '/StrSjis.php';
require_once P2EX_LIBRARY_DIR . '/expack_loader.class.php';
ExpackLoader::loadAAS();
ExpackLoader::loadActiveMona();
ExpackLoader::loadImageCache();

/**
 * p2 - 携帯用にスレッドを表示するクラス
 */
class ShowThreadK extends ShowThread
{
    var $_quote_link_max = 15;

    var $BBS_NONAME_NAME = '';

    var $am_autong = false; // 自動AA略をするか否か

    var $aas_rotate = '右に90°回転'; // AAS 回転リンク文字列

    /**
     * コンストラクタ
     */
    function ShowThreadK(&$aThread)
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
        if (P2_IMAGECACHE_AVAILABLE == 2) {
            $this->url_handlers[] = 'plugin_imageCache2';
        } elseif ($_conf['k_use_picto']) {
            $this->url_handlers[] = 'plugin_viewImage';
        }
        $this->url_handlers[] = 'plugin_linkURL';

        if (empty($_conf['k_bbs_noname_name'])) {
            require_once P2_LIBRARY_DIR . '/SettingTxt.php';
            $st = new SettingTxt($this->thread->host, $this->thread->bbs);
            if (!empty($st->setting_array['BBS_NONAME_NAME'])) {
                $this->BBS_NONAME_NAME = $st->setting_array['BBS_NONAME_NAME'];
            }
        }

        // サムネイル表示制限数を設定
        if (!isset($GLOBALS['pre_thumb_unlimited']) || !isset($GLOBALS['expack.ic2.pre_thumb_limit_k'])) {
            if (isset($_conf['expack.ic2.pre_thumb_limit_k']) && $_conf['expack.ic2.pre_thumb_limit_k'] > 0) {
                $GLOBALS['pre_thumb_limit_k'] = $_conf['expack.ic2.pre_thumb_limit_k'];
                $GLOBALS['pre_thumb_unlimited'] = false;
            } else {
                $GLOBALS['pre_thumb_limit_k'] = null;   // ヌル値だとisset()はFALSEを返す
                $GLOBALS['pre_thumb_unlimited'] = true;
            }
        }
        $GLOBALS['pre_thumb_ignore_limit'] = false;

        // アクティブモナー初期化
        if (P2_ACTIVEMONA_AVAILABLE) {
            ExpackLoader::initActiveMona($this);
        }

        // ImageCache2初期化
        if (P2_IMAGECACHE_AVAILABLE == 2) {
            ExpackLoader::initImageCache($this);
        }

        // AAS 初期化
        if (P2_AAS_AVAILABLE) {
            ExpackLoader::initAAS($this);
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
            flush();

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
     * @return  string
     */
    function transRes($ares, $i)
    {
        global $_conf, $STYLE, $mae_msg, $res_filter;
        global $ngaborns_hits;
        static $ngaborns_head_hits = 0;
        static $ngaborns_body_hits = 0;

        $tores      = '';
        $rpop       = '';
        $isNgName   = false;
        $isNgMail   = false;
        $isNgId     = false;
        $isNgMsg    = false;
        $isFreq     = false;
        $isChain    = false;
        $isAA       = false;

        $resar      = $this->thread->explodeDatLine($ares);
        $name       = $resar[0];
        $mail       = $resar[1];
        $date_id    = $resar[2];
        $msg        = $resar[3];

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
        // {{{ IDリンク

        if (($_conf['flex_idpopup'] || $this->ngaborn_frequent || $_conf['ngaborn_chain']) &&
            preg_match('|ID: ?([0-9A-Za-z/.+]{8,11})|', $date_id, $matches))
        {
            $id_full = $matches[0];
            $id = $matches[1];
        } else {
            $id_full = null;
            $id = null;
        }

        // }}}
        // {{{ あぼーんチェック

        $aborned_res .= "<div id=\"r{$i}\" name=\"r{$i}\">&nbsp;</div>\n"; // 名前
        $aborned_res .= ''; // 内容
        $ng_msg_info = array();

        // 頻出IDあぼーん
        if ($this->ngaborn_frequent && $id && $this->thread->idcount[$id] >= $_conf['ngaborn_frequent_num']) {
            if (!$_conf['ngaborn_frequent_one'] && $id == $this->thread->one_id) {
                // >>1 はそのまま表示
            } elseif ($this->ngaborn_frequent == 1) {
                $ngaborns_hits['aborn_freq']++;
                $this->aborn_nums[] = $i;
                return $aborned_res;
            } elseif (!$_GET['nong']) {
                $ngaborns_hits['ng_freq']++;
                $ngaborns_body_hits++;
                $this->ng_nums[] = $i;
                $isFreq = true;
                $ng_msg_info[] = sprintf('頻出ID:%s(%d)', $id, $this->thread->idcount[$id]);
            }
        }

        // 連鎖あぼーん
        if ($_conf['ngaborn_chain'] && preg_match_all('/(?:&gt;|＞)([1-9][0-9\\-,]*)/', $msg, $matches)) {
            $chain_nums = array_unique(array_map('intval', split('[-,]+', trim(implode(',', $matches[1]), '-,'))));
            if (array_intersect($chain_nums, $this->aborn_nums)) {
                if ($_conf['ngaborn_chain'] == 1) {
                    $ngaborns_hits['aborn_chain']++;
                    $this->aborn_nums[] = $i;
                    return $aborned_res;
                } else {
                    $a_chain_num = array_shift($chain_nums);
                    $ngaborns_hits['ng_chain']++;
                    $this->ng_nums[] = $i;
                    $ngaborns_body_hits++;
                    $isChain = true;
                    $ng_msg_info[] = sprintf('連鎖NG:&gt;&gt;%d(ｱﾎﾞﾝ)', $a_chain_num);
                }
            } elseif (array_intersect($chain_nums, $this->ng_nums)) {
                $a_chain_num = array_shift($chain_nums);
                $ngaborns_hits['ng_chain']++;
                $ngaborns_body_hits++;
                $this->ng_nums[] = $i;
                $isChain = true;
                $ng_msg_info[] = sprintf('連鎖NG:&gt;&gt;%d', $a_chain_num);
            }
        }

        // あぼーんレス
        if ($this->abornResCheck($i) !== false) {
            $ngaborns_hits['aborn_res']++;
            $this->aborn_nums[] = $i;
            return $aborned_res;
        }

        // あぼーんネーム
        if ($this->ngAbornCheck('aborn_name', $name) !== false) {
            $ngaborns_hits['aborn_name']++;
            $this->aborn_nums[] = $i;
            return $aborned_res;
        }

        // あぼーんメール
        if ($this->ngAbornCheck('aborn_mail', $mail) !== false) {
            $ngaborns_hits['aborn_mail']++;
            $this->aborn_nums[] = $i;
            return $aborned_res;
        }

        // あぼーんID
        if ($this->ngAbornCheck('aborn_id', $date_id) !== false) {
            $ngaborns_hits['aborn_id']++;
            $this->aborn_nums[] = $i;
            return $aborned_res;
        }

        // あぼーんメッセージ
        if ($this->ngAbornCheck('aborn_msg', $msg) !== false) {
            $ngaborns_hits['aborn_msg']++;
            $this->aborn_nums[] = $i;
            return $aborned_res;
        }

        // }}}
        // {{{ NGチェック

        if (empty($_GET['nong'])) {
            // NGネームチェック
            if ($this->ngAbornCheck('ng_name', $name) !== false) {
                $ngaborns_hits['ng_name']++;
                $ngaborns_head_hits++;
                $this->ng_nums[] = $i;
                $isNgName = true;
            }

            // NGメールチェック
            if ($this->ngAbornCheck('ng_mail', $mail) !== false) {
                $ngaborns_hits['ng_mail']++;
                $ngaborns_head_hits++;
                $this->ng_nums[] = $i;
                $isNgMail = true;
            }

            // NGIDチェック
            if ($this->ngAbornCheck('ng_id', $date_id) !== false) {
                $ngaborns_hits['ng_id']++;
                $ngaborns_head_hits++;
                $this->ng_nums[] = $i;
                $isNgId = true;
            }

            // NGメッセージチェック
            $a_ng_msg = $this->ngAbornCheck('ng_msg', $msg);
            if ($a_ng_msg !== false) {
                $ngaborns_hits['ng_msg']++;
                $ngaborns_body_hits++;
                $this->ng_nums[] = $i;
                $isNgMsg = true;
                $ng_msg_info[] = sprintf('NGﾜｰﾄﾞ:%s', htmlspecialchars($a_ng_msg, ENT_QUOTES));
            }

            // AAチェック
            if ($this->am_autong && $this->activeMona->detectAA($msg)) {
                $this->ng_nums[] = $i;
                $ngaborns_body_hits++;
                $isAA = true;
                $ng_msg_info[] = '&lt;AA略&gt;';
            }
        }

        // }}}

        //=============================================================
        // まとめて出力
        //=============================================================

        $name = $this->transName($name, $i); // 名前HTML変換

        $has_aa = 0; // 1:弱反応, 2:強反応（AA略）
        if (empty($_GET['nong']) && $this->am_autong) {
            $has_aa = -1; // AAチェックをスキップ
        }
        $msg = $this->transMsg($msg, $i, $has_aa); // メッセージHTML変換

        // BEプロファイルリンク変換
        $date_id = $this->replaceBeId($date_id, $i);

        // NGメッセージ変換
        if ($ng_msg_info) {
            $ng_type = implode(', ', $ng_msg_info);
            $msg = <<<EOMSG
<s><font color="{$STYLE['mobile_read_ngword_color']}">$ng_type</font></s> <a href="{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls={$i}&amp;k_continue=1&amp;nong=1{$_conf['k_at_a']}">確</a>
EOMSG;
            // AAS
            if ($isAA && P2_AAS_AVAILABLE) {
                $aas_url = "aas.php?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;resnum={$i}{$_conf['k_at_a']}";
                if (P2_AAS_AVAILABLE == 2) {
                    $aas_txt = "<img src=\"{$aas_url}&amp;inline=1\">";
                } else {
                    $aas_txt = "AAS";
                }
                $msg .= " <a href=\"{$aas_url}\">{$aas_txt}</a>";
                $msg .= " <a href=\"{$aas_url}&amp;rotate=1\">{$this->aas_rotate}</a>";
            }
        }

        // NGネーム変換
        if ($isNgName) {
            $name = <<<EONAME
<s><font color="{$STYLE['mobile_read_ngword_color']}">$name</font></s>
EONAME;
            $msg = <<<EOMSG
<a href="{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls={$i}&amp;k_continue=1&amp;nong=1{$_conf['k_at_a']}">確</a>
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
            $date_id = <<<EOID
<s><font color="{$STYLE['mobile_read_ngword_color']}">$date_id</font></s>
EOID;
            $msg = <<<EOMSG
<a href="{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls={$i}&amp;k_continue=1&amp;nong=1{$_conf['k_at_a']}">確</a>
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

        // 番号（オンザフライ時）
        if ($this->thread->onthefly) {
            $GLOBALS['newres_to_show_flag'] = true;
            $tores .= "<div id=\"r{$i}\" name=\"r{$i}\">[<font color=\"{$STYLE['mobile_read_onthefly_color']}\">{$i}</font>]";
        // 番号（新着レス時）
        } elseif ($i > $this->thread->readnum) {
            $GLOBALS['newres_to_show_flag'] = true;
            $tores .= "<div id=\"r{$i}\" name=\"r{$i}\">[<font color=\"{$STYLE['mobile_read_newres_color']}\">{$i}</font>]";
        // 番号
        } else {
            $tores .= "<div id=\"r{$i}\" name=\"r{$i}\">[{$i}]";
        }

        //$tores .= ' ';

        // 名前
        (strlen($name) > 0) and $tores .= $name;

        // メール
        $is_sage = false;
        if (strlen($mail) > 0) {
            if ($mail == 'sage') {
                $is_sage = true;
            } else {
                //$tores .= $mail . " :";
                $tores .= ':' . StrSjis::fixSjis($mail);
            }
        }

        if (strlen($name) > 0 or strlen($mail) > 0 && !$is_sage) {
            $tores .= ' ';
        }

        $no_trim_id_flag = false;

        // {{{ IDフィルタ

        if ($_conf['flex_idpopup'] == 1 && $id) {
            if ($this->thread->idcount[$id] > 1) {
                $date_id = preg_replace_callback('!ID: ?([0-9A-Za-z/.+]{8,11})(?=[^0-9A-Za-z/.+]|$)!', array($this, 'idfilter_callback'), $date_id);
            } elseif ($_conf['k_clip_unique_id'] && strlen($id) % 2) {
                $date_id = str_replace($id_full, 'ID:' . substr($id, -1), $date_id);
                $no_trim_id_flag = true;
            }
        }

        // }}}

        if ($_conf['mobile.id_underline'] && strlen($id) % 2) {
            $date_id = preg_replace('!(ID: ?)([0-9A-Za-z/.+]+|\\?\\?\\?)?O(?=[^0-9A-Za-z/.+]|$)!', '$1$2<u>O</u>', $date_id);
        }

        if ($_conf['k_clip_unique_id']) {
            $date_id = str_replace('???', '?', $date_id);
        }

        if (!$no_trim_id_flag) {
            $date_id = preg_replace('/ID: ?/', '', $date_id);
        }

        $tores .= $date_id;

        if ($is_sage) {
            $tores .= "<font color=\"{$conf_user_def['mobile.sage_color']}\">↓</font>";
        }

        $tores .= "<br>\n"; // 日付とID
        $tores .= $rpop; // レスポップアップ用引用
        $tores .= "{$msg}</div><hr>\n"; // 内容

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
     * @return  string
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
        // </b>～<b> は、ホスト（やトリップ）なのでマッチしないようにしたい
        $pettern = '/^( ?(?:&gt;|＞)* ?)?([1-9]\d{0,3})(?=\\D|$)/';
        $this->_quote_parent_resnum = $resnum;
        $name && $name = preg_replace_callback($pettern, array($this, 'quote_res_callback'), $name, 1);

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
     *                             拡張パックではAA判定にアクティブモナーを使うので利用されない
     * @return  string
     */
    function transMsg($msg, $resnum, &$has_aa)
    {
        global $_conf;
        global $res_filter, $word_fm;
        global $pre_thumb_ignore_limit;

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
        if ($has_aa != -1) {
            $has_aa = $this->detectAA($msg);
        }

        // {{{ 大きさ制限

        // AAの強制省略。
        //$aa_ryaku_flag = false;
        if ($_conf['k_aa_ryaku_size'] and strlen($msg) > $_conf['k_aa_ryaku_size'] and $has_aa == 2) {
            $aa_ryaku_flag = true;
        }

        if (empty($_GET['k_continue']) && strlen($msg) > $_conf['ktai_res_size'] or $aa_ryaku_flag) {
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
            $msg = preg_replace_callback('/((?:&gt;|＞){1,2})([1-9](?:[0-9\\-,])*)+/', array($this, 'quote_res_callback'), $msg, $this->_quote_link_max);

            $msg .= "<a href=\"{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls={$resnum}&amp;k_continue=1&amp;offline=1{$_conf['k_at_a']}\">{$ryaku_st}</a>";
            return $msg;
        }

        // }}}

        // 新着レスの画像は表示制限を無視する設定なら
        if ($mynum > $this->thread->readnum && $_conf['expack.ic2.newres_ignore_limit_k']) {
            $pre_thumb_ignore_limit = true;
        }

        // 引用やURLなどをリンク
        $this->_quote_parent_resnum = $resnum;
        $msg = preg_replace_callback($this->str_to_link_regex, array($this, 'link_callback'), $msg);

        $this->_quote_link_counts[$this->_quote_parent_resnum] = 0;

        return $msg;
    }

    /**
     * AA判定
     *
     * @return  integer  0:反応なし, 1:弱反応, 2:強反応
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
     * @return  string
     */
    function link_callback($s)
    {
        global $_conf;

        $following = '';

        // preg_replace_callback()では名前付きでキャプチャできないので
        if (!isset($s['link'])) {
            $s['link']  = $s[1];
            $s['quote'] = $s[5];
            $s['url']   = $s[8];
            $s['id']    = $s[12];
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
            if ($s[9] == 'ftp') {
                return $s[0];
            }
            $url = preg_replace('/^t?(tps?)$/', 'ht$1', $s[9]) . '://' . $s[10];
            $str = $s['url'];
            $following = $s[11];
            // ウィキペディア日本語版のURLで、SJISの2バイト文字の上位バイト(0x81-0x9F,0xE0-0xEF)が続くとき
            if (P2Util::isUrlWikipediaJa($url) && strlen($following) > 0) {
                $leading = ord($following);
                if ((($leading ^ 0x90) < 32 && $leading != 0x80) || ($leading ^ 0xE0) < 16) {
                    $url .= rawurlencode(mb_convert_encoding($following, 'UTF-8', 'SJIS-win'));
                    $str .= $following;
                    $following = '';
                }
            }

        // ID
        } elseif ($s['id'] && $_conf['flex_idpopup']) { // && $_conf['flex_idlink_k']
            return $this->idfilter_callback(array($s['id'], $s[13]));

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
            return $str . $following;
        }

        // URLを処理
        foreach ($this->user_url_handlers as $handler) {
            if (false !== ($link = call_user_func($handler, $url, $purl, $str, $this))) {
                return $link . $following;
            }
        }
        foreach ($this->url_handlers as $handler) {
            if (false !== ($link = call_user_func(array($this, $handler), $url, $purl, $str))) {
                return $link . $following;
            }
        }

        return $str . $following;
    }

    /**
     * 携帯用外部URL変換
     *
     * @access  private
     * @return  string
     */
    function ktai_exturl_callback($s)
    {
        global $_conf;

        $in_url = $s[1];

        // 通勤ブラウザ
        $tsukin_link = '';
        if ($_conf['k_use_tsukin']) {
            $tsukin_url = 'http://www.sjk.co.jp/c/w.exe?y=' . urlencode($in_url);
            if ($_conf['through_ime']) {
                $tsukin_url = P2Util::throughIme($tsukin_url);
            }
            $tsukin_link = '<a href="' . $tsukin_url . '">通</a>';
        }

        // jigブラウザWEB http://bwXXXX.jig.jp/fweb/?_jig_=
        $jig_link = '';
        /*
        $jig_url = 'http://bwXXXX.jig.jp/fweb/?_jig_=' . urlencode($in_url);
        if ($_conf['through_ime']) {
            $jig_url = P2Util::throughIme($jig_url);
        }
        $jig_link = '<a href="' . $jig_url . '">j</a>';
        */

        $sepa = '';
        if ($tsukin_link && $jig_link) {
            $sepa = '|';
        }

        $ext_pre = '';
        if ($tsukin_link || $jig_link) {
            $ext_pre = '(' . $tsukin_link . $sepa . $jig_link . ')';
        }

        if ($_conf['through_ime']) {
            $in_url = P2Util::throughIme($in_url);
        }
        $r = $ext_pre . '<a href="' . $in_url . '">' . $s[2] . '</a>';

        return $r;
    }

    /**
     * 引用変換
     *
     * @access  private
     * @return  string
     */
    function quote_res_callback($s)
    {
        global $_conf;

        list($full, $qsign, $appointed_num) = $s;

        // アンカーボム対策
        if ($qsign != '&gt;&gt;') {
            $this->_quote_link_counts[$this->_quote_parent_resnum]++;
            if ($this->_quote_link_counts[$this->_quote_parent_resnum] > $this->_quote_link_max) {
                return $s[0];
            }
        }

        if ($appointed_num == '-') {
            return $s[0];
        }
        $qnum = intval($appointed_num);
        if ($qnum < 1 || $qnum > $this->thread->rescount) {
            return $s[0];
        }

        $read_url = "{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;offline=1&amp;ls={$appointed_num}";
        return "<a href=\"{$read_url}{$_conf['k_at_a']}\">{$qsign}{$appointed_num}</a>";
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

        // アンカーボム対策
        if ($qsign != '&gt;&gt;') {
            $this->_quote_link_counts[$this->_quote_parent_resnum]++;
            if ($this->_quote_link_counts[$this->_quote_parent_resnum] > $this->_quote_link_max) {
                return $s[0];
            }
        }

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

        $read_url = "{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;offline=1&amp;ls={$from}-{$to}";

        return "<a href=\"{$read_url}{$_conf['k_at_a']}\">{$qsign}{$appointed_num}</a>";
    }

    /**
     * IDフィルタリングリンク変換
     *
     * @param   array   $s  正規表現にマッチした要素の配列
     * @return  string
     * @access  public
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

        $filter_url = "{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls=all&amp;offline=1&amp;idpopup=1&amp;field=id&amp;method=just&amp;match=on&amp;word=" . rawurlencode($id).$_conf['k_at_a'];

        if (isset($this->thread->idcount[$id]) && $this->thread->idcount[$id] > 0) {
            $num_ht = '(' . "<a href=\"{$filter_url}\">" . $this->thread->idcount[$id] . '</a>)';
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
     * @return  string|false
     */
    function plugin_linkURL($url, $purl, $str)
    {
        global $_conf;

        if (isset($purl['scheme'])) {
            // 携帯用外部URL変換
            if ($_conf['k_use_tsukin']) {
                return $this->ktai_exturl_callback(array('', $url, $str));
            }
            // ime
            if ($_conf['through_ime']) {
                $link_url = P2Util::throughIme($url);
            } else {
                $link_url = $url;
            }
            return "<a href=\"{$link_url}\">{$str}</a>";
        }
        return false;
    }

    /**
     * 2ch bbspink 板リンク
     *
     * @access  private
     * @return  string|false
     */
    function plugin_link2chSubject($url, $purl, $str)
    {
        global $_conf;

        if (preg_match('{^http://(\\w+\\.(?:2ch\\.net|bbspink\\.com))/([^/]+)/$}', $url, $m)) {
            $subject_url = "{$_conf['subject_php']}?host={$m[1]}&amp;bbs={$m[2]}";
            return "<a href=\"{$url}\">{$str}</a> [<a href=\"{$subject_url}{$_conf['k_at_a']}\">板をp2で開く</a>]";
        }
        return false;
    }

    /**
     * 2ch bbspink スレッドリンク
     *
     * @access  private
     * @return  string|false
     */
    function plugin_link2ch($url, $purl, $str)
    {
        global $_conf;

        if (preg_match('{^http://(\\w+\\.(?:2ch\\.net|bbspink\\.com))/test/read\\.cgi/([^/]+)/([0-9]+)(?:/([^/]+)?)?$}', $url, $m)) {
            $read_url = "{$_conf['read_php']}?host={$m[1]}&amp;bbs={$m[2]}&amp;key={$m[3]}&amp;ls={$m[4]}";
            return "<a href=\"{$read_url}{$_conf['k_at_a']}\">{$str}</a>";
        }
        return false;
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
            return "<a href=\"{$read_url}{$_conf['k_at_a']}\">{$str}</a>";
        }
        return false;
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
            $read_url = "{$_conf['read_php']}?host={$m[1]}&amp;bbs={$m[4]}&amp;key={$m[5]}";
            if ($m[6] || $m[7]) {
                $read_url .= "&amp;ls={$m[6]}-{$m[7]}";
            }
            return "<a href=\"{$read_url}{$_conf['k_at_a']}\">{$str}</a>";
        }
        return false;
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
            return "<a href=\"{$read_url}{$_conf['k_at_a']}\">{$str}</a>";
        }
        return false;
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

        if (P2Util::isUrlWikipediaJa($url)) {
            return false;
        }

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
        return false;
    }

    /**
     * 画像URLのImageCache2変換
     *
     * @access  private
     * @return  string|false
     */
    function plugin_imageCache2($url, $purl, $str)
    {
        global $_conf;
        global $pre_thumb_unlimited, $pre_thumb_ignore_limit, $pre_thumb_limit_k;

        if (P2Util::isUrlWikipediaJa($url)) {
            return false;
        }

        if (preg_match('{^https?://.+?\\.(jpe?g|gif|png)$}i', $url) && empty($purl['query'])) {
            // インラインプレビューの有効判定
            if ($pre_thumb_unlimited || $pre_thumb_ignore_limit || $pre_thumb_limit_k > 0) {
                $inline_preview_flag = true;
                $inline_preview_done = false;
            } else {
                $inline_preview_flag = false;
                $inline_preview_done = false;
            }

            $url_en = rawurlencode($url);
            $img_str = '[IC2:'.$purl['host'].':'.basename($purl['path']).']';

            $icdb = &new IC2DB_Images;

            // r=0:リンク;r=1:リダイレクト;r=2:PHPで表示
            // t=0:オリジナル;t=1:PC用サムネイル;t=2:携帯用サムネイル;t=3:中間イメージ
            $img_url = 'ic2.php?r=0&amp;t=2&amp;uri=' . $url_en;
            $img_url2 = 'ic2.php?r=0&amp;t=2&amp;id=';
            $src_exists = false;

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

                // オリジナルの有無を確認
                $_src_url = $this->thumbnailer->srcPath($icdb->size, $icdb->md5, $icdb->mime);
                if (file_exists($_src_url)) {
                    $src_exists = true;
                    $img_url = $img_url2 . $icdb->id;
                } else {
                    $img_url = $this->thumbnailer->thumbPath($icdb->size, $icdb->md5, $icdb->mime);
                }

                // インラインプレビューが有効のとき
                if ($this->thumbnailer->ini['General']['inline'] == 1) {
                    $_prvw_url = $this->inline_prvw->thumbPath($icdb->size, $icdb->md5, $icdb->mime);
                    $r_type = ($this->thumbnailer->ini['General']['redirect'] == 1) ? 1 : 2;
                    // サムネイル表示制限数以内のとき
                    if ($inline_preview_flag) {
                        // プレビュー画像が作られているかどうかでimg要素の属性を決定
                        if (file_exists($_prvw_url)) {
                            $prvw_size = explode('x', $this->inline_prvw->calc($icdb->width, $icdb->height));
                            $img_str = "<img src=\"ic2.php?r={$r_type}&amp;t=1&amp;id={$icdb->id}\" width=\"{$prvw_size[0]}\" height=\"{$prvw_size[1]}\">";
                        } elseif ($src_exists) {
                            $img_str = "<img src=\"ic2.php?r={$r_type}&amp;t=1&amp;id={$icdb->id}\">";
                        } else {
                            $img_str = "<img src=\"ic2.php?r={$r_type}&amp;t=1&amp;uri={$url_en}\">";
                        }
                        $inline_preview_done = true;
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
                if (false !== ($errcode = $icdb->ic2_isError($url))) {
                    return "<s>[IC2:ｴﾗｰ({$errcode})]</s>";
                }

                // インラインプレビューが有効で、サムネイル表示制限数以内なら
                if ($this->thumbnailer->ini['General']['inline'] == 1 && $inline_preview_flag) {
                    $img_str = '<img src="ic2.php?r=2&amp;t=1&amp;uri=' . $url_en . $this->img_memo_query . '">';
                    $inline_preview_done = true;
                } else {
                    $img_url .= $this->img_memo_query;
                }
            }

            // 表示数制限をデクリメント
            if ($inline_preview_flag && $inline_preview_done) {
                $pre_thumb_limit_k--;
            }

            if (!empty($_SERVER['REQUEST_URI'])) {
                $backto = '&amp;from=' . rawurlencode($_SERVER['REQUEST_URI']);
            } else {
                $backto = '';
            }
            return "<a href=\"{$img_url}{$backto}\">{$img_str}</a>";
        }
        return false;
    }

    // }}}

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * mode: php
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
