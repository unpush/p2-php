<?php
/**
 * p2 - ThreadList クラス
 */
class ThreadList
{
    var $threads;   // クラスThreadのオブジェクトを格納する配列
    var $num;       // 格納されたThreadオブジェクトの数
    var $host;      // ex)pc.2ch.net
    var $bbs;       // ex)mac
    var $itaj;      // 板名 ex)新・mac板
    var $itaj_hd;   // HTML表示用に、板名を htmlspecialchars() したもの
    var $spmode;    // 普通板以外のスペシャルモード
    var $ptitle;    // ページタイトル

    /**
     * @constructor
     */
    function ThreadList()
    {
        $this->num = 0;
    }

    /**
     * setSpMode
     *
     * @access  public
     * @return  void
     */
    function setSpMode($name)
    {
        global $_conf;

        if ($name == 'recent') {
            $this->spmode = $name;
            $this->ptitle = $_conf['ktai'] ? '最近読んだｽﾚ' : '最近読んだスレ';
        } elseif ($name == 'res_hist') {
            $this->spmode = $name;
            $this->ptitle = '書き込み履歴';
        } elseif ($name == 'fav') {
            $this->spmode = $name;
            $this->ptitle = $_conf['ktai'] ? 'お気にｽﾚ' : 'お気にスレ';
        } elseif ($name == 'taborn') {
            $this->spmode = $name;
            $this->ptitle = $this->itaj . ($_conf['ktai'] ? ' (ｱﾎﾞﾝ中)' : ' (あぼーん中)');
        } elseif ($name == 'soko') {
            $this->spmode = $name;
            $this->ptitle = $this->itaj . ' (dat倉庫)';
        } elseif ($name == 'palace') {
            $this->spmode = $name;
            $this->ptitle = $_conf['ktai'] ? 'ｽﾚの殿堂' : 'スレの殿堂';
        } elseif ($name == 'news') {
            $this->spmode = $name;
            $this->ptitle = $_conf['ktai'] ? 'ﾆｭｰｽﾁｪｯｸ' : 'ニュースチェック';
        }
    }

    /**
     * 総合的に板情報（host, bbs, 板名）をセットする
     *
     * @access  public
     * @return  void
     */
    function setIta($host, $bbs, $itaj = '')
    {
        $this->host = $host;
        $this->bbs = $bbs;
        $this->setItaj($itaj);
    }

    /**
     * 板名をセットする
     *
     * @access  public
     * @return  void
     */
    function setItaj($itaj)
    {
        $this->itaj = $itaj ? $itaj : $this->bbs;

        $this->itaj_hd = htmlspecialchars($this->itaj, ENT_QUOTES);
        $this->ptitle = $this->itaj;
    }

    /**
     * readList
     *
     * @access  public
     * @return  array
     */
    function readList()
    {
        global $_conf;

        $lines = array();

        $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('readList()');

        switch ($this->spmode) {

            // {{{ オンライン上の subject.txt を読み込む（spmodeでない場合）

            case null:
            case false:
            case 0:
            case '':
                require_once P2_LIBRARY_DIR . '/SubjectTxt.class.php';
                $aSubjectTxt =& new SubjectTxt($this->host, $this->bbs);
                $lines =& $aSubjectTxt->subject_lines;
                break;

            // }}}
            // {{{ ローカルの履歴ファイル 読み込み

            case 'recent':
                if (file_exists($_conf['rct_file'])) {
                    $lines = file($_conf['rct_file']);
                }
                /*if (!$lines) {
                    P2Util::pushInfoMsgHtml('<p>履歴は空っぽです</p>');
                    return false;
                }*/
                break;

            // }}}
            // {{{ ローカルの書き込み履歴ファイル 読み込み

            case 'res_hist':
                $rh_idx = $_conf['pref_dir'] . '/p2_res_hist.idx';
                if (file_exists($rh_idx)) {
                    $lines = file($rh_idx);
                }
                /*if (!$lines) {
                    P2Util::pushInfoMsgHtml('<p>書き込み履歴は空っぽです</p>');
                    return false;
                }*/
                break;

            // }}}
            // {{{ ローカルのお気にファイル 読み込み

            case 'fav':
                if (file_exists($_conf['favlist_file'])) {
                    $lines = file($_conf['favlist_file']);
                }
                /*if (!$lines) {
                    P2Util::pushInfoMsgHtml('<p>お気にスレは空っぽです</p>');
                    return false;
                }*/
                break;

            // }}}
            // {{{ スレの殿堂の場合  // p2_palace.idx 読み込み

            case 'palace':
                $palace_idx = $_conf['pref_dir'] . '/p2_palace.idx';
                if (file_exists($palace_idx)) {
                    $lines = file($palace_idx);
                }
                /*if (!$lines) {
                    P2Util::pushInfoMsgHtml('<p>殿堂はがらんどうです</p>');
                    return false;
                }*/
                break;

            // }}}
            // {{{ スレッドあぼーんリスト  // p2_threads_aborn.idx 読み込み

            case 'taborn':
                $taborn_idx = P2Util::datDirOfHost($this->host). '/' . $this->bbs . '/p2_threads_aborn.idx';
                if (file_exists($taborn_idx)) {
                    $lines = file($taborn_idx);
                }
                /*if (!$lines) {
                    P2Util::pushInfoMsgHtml('<p>スレッドあぼーんリストは空っぽです</p>');
                    return false;
                }*/
                break;

            // }}}
            // {{{ dat倉庫の場合

            case 'soko':
                $dat_host_dir = P2Util::datDirOfHost($this->host);
                $idx_host_dir = P2Util::idxDirOfHost($this->host);

                $dat_bbs_dir = $dat_host_dir. '/' . $this->bbs;
                $idx_bbs_dir = $idx_host_dir. '/' . $this->bbs;

                $dat_pattern = '/([0-9]+)\.dat$/';
                $idx_pattern = '/([0-9]+)\.idx$/';

                $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('dat');
                // datログディレクトリを走査して孤立datにidx付加
                if ($cdir = dir($dat_bbs_dir)) { // or die ('ログディレクトリがないよ！');
                    // ディレクトリ走査
                    while ($entry = $cdir->read()) {
                        if (preg_match($dat_pattern, $entry, $matches)) {
                            $theidx = $idx_bbs_dir. '/' . $matches[1] . '.idx';
                            if (!file_exists($theidx)) {
                                if ($datlines = @file($dat_bbs_dir. '/' . $entry)) {
                                    $firstdatline = rtrim($datlines[0]);
                                    if (strstr($firstdatline, '<>')) {
                                        $datline_sepa = '<>';
                                    } else {
                                        $datline_sepa = ',';
                                    }
                                    $d = explode($datline_sepa, $firstdatline);
                                    $atitle = $d[4];
                                    $gotnum = sizeof($datlines);
                                    $readnum = $gotnum;
                                    $anewline = $readnum + 1;
                                    $data = array($atitle, $matches[1], '', $gotnum, '',
                                                $readnum, '', '', '', $anewline,
                                                '', '', '');
                                    P2Util::recKeyIdx($theidx, $data);
                                }
                            }
                            // array_push($lines, $idl[0]);
                        }
                    }
                    $cdir->close();
                }
                $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('dat');

                $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('idx');
                // {{{ idxログディレクトリを走査してidx情報を抽出してリスト化
                if ($cdir = dir($idx_bbs_dir)) { // or die ('ログディレクトリがないよ！');
                    // ディレクトリ走査
                    while ($entry = $cdir->read()) {
                        if (preg_match($idx_pattern, $entry)) {
                            $idl = file($idx_bbs_dir . '/' . $entry);
                            array_push($lines, $idl[0]);
                        }
                    }
                    $cdir->close();
                }
                // }}}
                $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('idx');
                break;

            // }}}
            // {{{ お気に板のサブジェクト一覧読み込み

            case 'favita':
                if (!file_exists($_conf['favita_path'])) {
                    break;
                }
                $favitas = file($_conf['favita_path']);
                if (empty($favitas)) {
                    break;
                }
                break;

                // お気に板の各板メニュー読み込み
                require_once P2_LIBRARY_DIR . '/SubjectTxt.class.php';
                foreach ($favitas as $favita) {
                    if (!preg_match('/^\t?(.+)\t(.+)\t(.+)$/', rtrim($favita), $fm)) {
                        continue;
                    }
                    $aSubjectTxt = &new SubjectTxt($fm[1], $fm[2]);
                    if (!is_array($aSubjectTxt->subject_lines)) {
                        continue;
                    }
                    foreach ($aSubjectTxt->subject_lines as $l) {
                        if (!preg_match('/^([0-9]+)\.(dat|cgi)(,|<>)(.+) ?(\(|（)([0-9]+)(\)|）)/', $l, $lm)) {
                            continue;
                        }
                        $lines[] = array(
                            'key'       => $lm[1],
                            'ttitle'    => trim($lm[4]),
                            'rescount'  => $lm[6],
                            'host'      => $fm[1],
                            'bbs'       => $fm[2],
                        );
                    }
                }
                break;

            // }}}
            // {{{ 特定カテゴリのサブジェクト一覧読み込み

            case 'cate':
            //case 'cate_local':
            //case 'cate_online':
                if (!isset($_GET['cate_name'])) {
                    break;
                }

                // 板メニュー読み込み
                //if ($this->spmode == 'cate_local') {
                //    $brd_menus = BrdCtl::readBrdLocal();
                //} elseif ($this->spmode == 'cate_online') {
                //    $brd_menus = BrdCtl::readBrdOnline();
                //} else {
                    $brd_menus = BrdCtl::read_brds();
                //}
                if (!$brd_menus) {
                    break;
                }

                // カテゴリ検索
                $menuitas = null;
                foreach ($brd_menus as $a_brd_menu) {
                    foreach ($a_brd_menu->categories as $cate) {
                        if ($cate->name == $_GET['cate_name']) {
                            $menuitas = $cate->menuitas;
                            break 2;
                        }
                    }
                }
                if (!$menuitas) {
                    break;
                }

                // カテゴリ内の各板メニュー読み込み
                require_once P2_LIBRARY_DIR . '/SubjectTxt.class.php';
                foreach ($menuitas as $mita) {
                    $aSubjectTxt = &new SubjectTxt($mita->host, $mita->bbs);
                    if (!is_array($aSubjectTxt->subject_lines)) {
                        continue;
                    }
                    foreach ($aSubjectTxt->subject_lines as $l) {
                        if (!preg_match('/^([0-9]+)\.(dat|cgi)(,|<>)(.+) ?(\(|（)([0-9]+)(\)|）)/', $l, $matches)) {
                            continue;
                        }
                        $lines[] = array(
                            'key'       => $matches[1],
                            'ttitle'    => trim($matches[4]),
                            'rescount'  => $matches[6],
                            'host'      => $mita->host,
                            'bbs'       => $mita->bbs,
                        );
                    }
                }
                break;

            // }}}
        }

        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('readList()');

        return $lines;
    }

    /**
     * addThread
     *
     * @access  public
     * @return  integer
     */
    function addThread(&$aThread)
    {
        $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('addThread()');

        $this->threads[] =& $aThread;
        $this->num++;

        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('addThread()');

        return $this->num;
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
