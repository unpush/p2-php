<?php
// p2 - 書き込み履歴のクラス

/**
 * レス記事のクラス
 */
class ResArticle
{
    var $name;
    var $mail;
    var $daytime;
    var $msg;
    var $ttitle;
    var $host;
    var $bbs;
    var $itaj;
    var $key;
    var $resnum;
    var $order; // 記事番号
}

/**
 * 書き込みログのクラス
 */
class ResHist
{
    // クラス ResArticle のオブジェクトを格納する配列
    var $articles = array();
    
    var $resrange; // array('start' => i, 'to' => i, 'nofirst' => bool)
    
    /**
     * @constructor
     */
    function ResHist()
    {
    }
    
    /**
     * 書き込みログの line 一行をパースして、ResArticleオブジェクトを返す
     *
     * @access  public
     * @param   array    $aline
     * @return  object ResArticle
     */
    function lineToRes($aline, $order)
    {
        $ResArticle = new ResArticle;

        $resar = explode('<>', rtrim($aline));
        $ResArticle->name    = $resar[0];
        $ResArticle->mail    = $resar[1];
        $ResArticle->daytime = $resar[2];
        $ResArticle->msg     = $resar[3];
        $ResArticle->ttitle  = $resar[4];
        $ResArticle->host    = $resar[5];
        $ResArticle->bbs     = $resar[6];
        if (!$ResArticle->itaj = P2Util::getItaName($ResArticle->host, $ResArticle->bbs)) {
            $ResArticle->itaj = $ResArticle->bbs;
        }
        $ResArticle->key   = $resar[7];
        $ResArticle->resnum = isset($resar[8]) ? $resar[8] : null;
        
        $ResArticle->order = $order;
        
        return $ResArticle;
    }
    
    /**
     * レス記事HTMLを表示する PC用
     *
     * @access  public
     * @param   array
     * @return  void
     */
    function printArticlesHtml($datlines)
    {
        global $_conf, $STYLE;

        // Pager 準備
        if (!include_once 'Pager/Pager.php') {
            P2Util::printSimpleHtml('p2 error: PEARの Pager/Pager.php がインストールされていません');
            die;
        }
        
        $qv = UA::getQueryValue();
        
        $perPage = 100;
        $params = array(
            'mode'       => 'Jumping',
            'itemData'   => $datlines,
            'perPage'    => $perPage,
            'delta'      => 25,
            'clearIfVoid' => true,
            'prevImg' => "前の{$perPage}件",
            'nextImg' => "次の{$perPage}件",
            //'separator' => '|',
            //'expanded' => true,
            'spacesBeforeSeparator' => 2,
            'spacesAfterSeparator'  => 0,
            
            'httpMethod' => 'GET', // この指定要る
            
            // checked_hists を引き継いでしまわないように。（'submit'はデフォルトで引き継がない仕様？）
            // appendをfalseにすると、extraVarsオプションが効かなくなるらしい。
            'append' => false, 
            'fileName' => UriUtil::buildQueryUri('read_res_hist.php',
                array(
                    'pageID' => '%d',
                    UA::getQueryKey() => isset($qv) ? rawurlencode($qv) : null
                ),
                array('encode' => null) // %d をエンコードしないように
            )
            
        );

        $pager = &Pager::factory($params);
        $links = $pager->getLinks();
        $data  = $pager->getPageData();

        if ($pager->links) {
            echo "<div>{$pager->links}</div>";
        }
        
        ?><dl><?php
        
        if (isset($_REQUEST['pageID'])) {
            $pageID = max(1, intval($_REQUEST['pageID']));
        } else {
            $pageID = 1;
        }
        
        $n = ($pageID - 1) * $perPage;
        foreach ($data as $aline) {
            $n++;
            
            $aline = rtrim($aline);
            
            $ResArticle = $this->lineToRes($aline, $n);
            
            $daytime_hs = hs($ResArticle->daytime);
            $ttitle_hs = hs(html_entity_decode($ResArticle->ttitle, ENT_COMPAT, 'Shift_JIS'));
            
            $info_qs = array(
                'host' => $ResArticle->host,
                'bbs'  => $ResArticle->bbs,
                'key'  => $ResArticle->key,
                UA::getQueryKey() => UA::getQueryValue(),
            );
            $info_uri = UriUtil::buildQueryUri('info.php', $info_qs);
            $info_uri_hs = hs($info_uri);
            
            $sid_qs = array();
            if (defined('SID') && strlen(SID)) {
                $sid_qs[session_name()] = session_id();
            }
            $info_openwin_qs = array_merge($info_qs, array('popup' => '1'), $sid_qs);
            $info_openwin_uri = UriUtil::buildQueryUri('info.php', $info_openwin_qs);
            $info_openwin_uri_as = str_replace("'", "\\'", $info_openwin_uri);
            
            $info_view_ht = P2View::tagA(
                $info_openwin_uri,
                hs('情報'),
                array(
                    'target'  => '_self',
                    'onClick' => "return !openSubWin('{$info_openwin_uri_as}',{$STYLE['info_pop_size']},0,0)"
                )
            );

            $res_ht = "<dt><input name=\"checked_hists[]\" type=\"checkbox\" value=\"{$ResArticle->order},,,,{$daytime_hs}\"> ";
            
            // 番号
            $res_ht .= "{$ResArticle->order} ：";
            
            // 名前
            $array = explode('#', $ResArticle->name, 2);
            if (count($array) == 2) {
                $name_ht = sprintf('%s◆</b>%s<b>', $array[0], P2Util::mkTrip($array[1]));
                $title_at = ' title="' . hs($ResArticle->name) . '"';
            } else {
                $name_ht = hs($ResArticle->name);
                $title_at = '';
            }
            $res_ht .= '<span class="name"' . $title_at . '><b>' . $name_ht . '</b></span> ：';
            
            // メール
            if ($ResArticle->mail) {
                $res_ht .= hs($ResArticle->mail) . ' ：';
            }
            
            // 日付とID
            $res_ht .= "{$daytime_hs}</dt>\n";
            
            // 板名
            $atag = P2View::tagA(
                UriUtil::buildQueryUri($_conf['subject_php'], array(
                    'host' => $ResArticle->host,
                    'bbs'  => $ResArticle->bbs,
                    UA::getQueryKey() => UA::getQueryValue()
                )),
                hs($ResArticle->itaj),
                array('target' => 'subject')
            );
            $res_ht .= "<dd>$atag / ";
            
            if ($ResArticle->key) {
                if (empty($ResArticle->resnum) || $ResArticle->resnum == 1) {
                    $ls_qs = array();
                    $footer_anchor = '#footer';
                } else {
                    $lf = max(1, $ResArticle->resnum - 0);
                    $ls_qs = array('ls' => "{$lf}-");
                    $footer_anchor = "#r{$lf}";
                }
                
                $ttitle_qs = array_merge(
                    array(
                        'host' => $ResArticle->host,
                        'bbs'  => $ResArticle->bbs,
                        'key'  => $ResArticle->key,
                        UA::getQueryKey() => UA::getQueryValue(),
                        'nt'   => time()
                    ), $ls_qs
                );
                $ttitle_uri = UriUtil::buildQueryUri($_conf['read_php'], $ttitle_qs) . $footer_anchor;
                
                $atag = P2View::tagA(
                    $ttitle_uri,
                    sprintf('<b>%s </b>', $ttitle_hs)
                );
                $res_ht .= "$atag - {$info_view_ht}\n";
            } else {
                $res_ht .= "<b>{$ttitle_hs} </b>\n";
            }
            
            $res_ht .= "<br><br>";
            
            // 内容
            $res_ht .= "{$ResArticle->msg}<br><br></dd>\n";

            echo $res_ht;
            ob_flush(); flush();
        }
        
        echo '</dl>';
        
        if ($pager->links) {
            echo "<div>{$pager->links}</div>";
        }
    }
    
    /**
     * 携帯用ナビをHTML表示する
     * 表示範囲($this->resrange)もセットされる
     *
     * @access  public
     * @param   string  $position  表示箇所識別名 'footer', 'header'
     * @param   integer $totalNum  アイテム総数
     * @return  void
     */
    function showNaviK($position, $totalNum)
    {
        global $_conf;

        // 表示数制限
        $list_disp_all_num = $totalNum;
        $list_disp_range = $_conf['k_rnum_range'];
        
        $from = isset($_GET['from']) ? intval($_GET['from']) : null;
        $end  = isset($_GET['end'])  ? intval($_GET['end'])  : null;
        
        if (!empty($from)) {
            $list_disp_from = $from;
            if (!empty($end)) {
                $list_disp_range = max(1, $end - $list_disp_from + 1);
            }
        } else {
            $list_disp_from = 1;
        }
        
        $disp_navi = P2Util::getListNaviRange($list_disp_from, $list_disp_range, $list_disp_all_num);
        
        $this->resrange['start'] = $disp_navi['from'];
        $this->resrange['to'] = $disp_navi['end'];
        $this->resrange['nofirst'] = false;

        $mae_ht = '';
        if ($disp_navi['from'] > 1) {
            if ($position == 'footer') {
                $attrs = array($_conf['accesskey_for_k'] => $_conf['k_accesskey']['prev']);
                $str = "{$_conf['k_accesskey']['prev']}.前";
            } else {
                $attrs = array();
                $str = "前";
            }
            $atag = P2View::tagA(
                UriUtil::buildQueryUri('read_res_hist.php', array(
                    'from' => $disp_navi['mae_from'],
                    UA::getQueryKey() => UA::getQueryValue()
                )),
                hs($str),
                $attrs
            );
            $mae_ht = " $atag ";
            
        }
        
        $tugi_ht = '';
        if ($disp_navi['end'] < $list_disp_all_num) {
            if ($position == 'footer') {
                $attrs = array($_conf['accesskey_for_k'] => $_conf['k_accesskey']['next']);
                $str = "{$_conf['k_accesskey']['next']}.次";
            } else {
                $attrs = array();
                $str = "次";
            }
            $atag = P2View::tagA(
                UriUtil::buildQueryUri('read_res_hist.php', array(
                    'from' => $disp_navi['tugi_from'],
                    UA::getQueryKey() => UA::getQueryValue()
                )),
                hs($str),
                $attrs
            );
            $tugi_ht = " $atag ";
        }
        
        if (!$disp_navi['all_once']) {
            $list_navi_ht = " {$disp_navi['range_st']}{$mae_ht} {$tugi_ht} ";
        }

        echo $list_navi_ht;
    }
    
    /**
     * レス記事をHTML表示する 携帯用
     *
     * @access  public
     * @param   array
     * @return  void
     */
    function printArticlesHtmlK($datlines)
    {
        global $_conf;
        
        $hr = P2View::getHrHtmlK();
        
        $n = 0;
        foreach ($datlines as $aline) {
            $n++;
            
            if ($n < $this->resrange['start'] or $n > $this->resrange['to']) {
                continue;
            }
            
            $aline = rtrim($aline);
            
            $ResArticle = $this->lineToRes($aline, $n);
            
            $daytime_hs = hs($ResArticle->daytime);
            
            $ttitle = html_entity_decode($ResArticle->ttitle, ENT_COMPAT, 'Shift_JIS');
            $ttitle_hs = hs($ttitle);
            
            $msg_ht = $ResArticle->msg;
            
            // 大きさ制限
            if (empty($_GET['k_continue'])) {
                
                if ($_conf['ktai_res_size'] && strlen($msg_ht) > $_conf['ktai_res_size']) {
                    $msg_ht = substr($msg_ht, 0, $_conf['ktai_ryaku_size']);
                    
                    // 末尾に<br>があれば取り除く（不完全なものも含めて）
                    $brtag = '<br>';
                    for ($i = 0; $i < strlen($brtag); $i++) {
                        if (substr($msg_ht, -1) == $brtag[strlen($brtag)-$i-1]) {
                            $msg_ht = substr($msg_ht, 0, strlen($msg_ht) - 1);
                        }
                    }
                    
                    $msg_ht .= ' ' . P2View::tagA(
                        UriUtil::buildQueryUri('read_res_hist.php', array(
                            'from' => $ResArticle->order,
                            'end'  => $ResArticle->order,
                            'k_continue' => '1',
                            UA::getQueryKey() => UA::getQueryValue()
                        )),
                        hs('略')
                    );
                }
            }

            // 番号
            $res_ht = "[$ResArticle->order]";
            
            // 名前
            $array = explode('#', $ResArticle->name, 2);
            if (count($array) == 2) {
                $name_ht = sprintf('%s◆%s', $array[0], P2Util::mkTrip($array[1]));
            } else {
                $name_ht = hs($ResArticle->name);
            }
            $res_ht .= $name_ht . ':';
            
            // メール
            if ($ResArticle->mail) {
                $res_ht .= hs($ResArticle->mail) . ':';
            }
            
            // 日付とID
            $res_ht .= "{$daytime_hs}<br>\n";
            
            // 板名
            $res_ht .= P2View::tagA(
                UriUtil::buildQueryUri($_conf['subject_php'], array(
                    'host' => $ResArticle->host, 'bbs' => $ResArticle->bbs, UA::getQueryKey() => UA::getQueryValue()
                )),
                hs($ResArticle->itaj)
            ) . ' / ';
            
            if ($ResArticle->key) {
                if (empty($ResArticle->resnum) || $ResArticle->resnum == 1) {
                    $ls_qs = array();
                    $footer_anchor = '#footer';
                } else {
                    $lf = max(1, $ResArticle->resnum - 0);
                    $ls_qs = array('ls' => "{$lf}-");
                    $footer_anchor = "#r{$lf}";
                }
                $time = time();
                $ttitle_qs = array_merge(array(
                    'host' => $ResArticle->host, 'bbs' => $ResArticle->bbs, 'key' => $ResArticle->key,
                    UA::getQueryKey() => UA::getQueryValue(),
                    'nt' => time()
                ), $ls_qs);
                $res_ht .= P2View::tagA(
                    UriUtil::buildQueryUri($_conf['read_php'], $ttitle_qs) . $footer_anchor,
                    "$ttitle_hs "
                );
            } else {
                $res_ht .= "{$ttitle_hs}\n";
            }
            
            // 削除
            // $res_ht = "<dt><input name=\"checked_hists[]\" type=\"checkbox\" value=\"{$ResArticle->order},,,,{$daytime_hs}\"> ";
            $from_q = isset($_GET['from']) ? '&amp;from=' . intval($_GET['from']) : '';
            $dele_ht = "[<a href=\"read_res_hist.php?checked_hists[]={$ResArticle->order},,,," . hs(urlencode($ResArticle->daytime)) . "{$from_q}{$_conf['k_at_a']}\">削除</a>]";
            $res_ht .= $dele_ht;
            
            $res_ht .= '<br>';
            
            // 内容
            $res_ht .= "{$msg_ht}$hr\n";

            if ($_conf['k_save_packet']) {
                $res_ht = mb_convert_kana($res_ht, 'rnsk');
            }
            
            echo $res_ht;
        }
    }
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
