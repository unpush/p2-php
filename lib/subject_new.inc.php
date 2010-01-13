<?php
/**
 * rep2 - メニューで新着数を知るために使用
 * $shinchaku_num, $_newthre_num をセット
 *
 * subject.php と兄弟なので一緒に面倒をみる
 */

$_newthre_num = 0;
$shinchaku_num = 0;
$ta_num = 0;
$ta_keys = array();
$nowtime = time();

if (!isset($spmode)) {
    $spmode = false;
}

// {{{ sb_keys 設定

if (!$spmode) {
    $sb_keys_txt = P2Util::idxDirOfHostBbs($host, $bbs) . 'p2_sb_keys.txt';

    if ($pre_sb_cont = FileCtl::file_read_contents($sb_keys_txt)) {
        $pre_subject_keys = @unserialize($pre_sb_cont);
        if (!is_array($pre_subject_keys)) {
            $pre_subject_keys = array();
        }
        unset($pre_sb_cont);
    } else {
        $pre_subject_keys = array();
    }
} else {
    $pre_subject_keys = array();
}

// }}}

//============================================================
// メイン
//============================================================

$aThreadList = new ThreadList();

// 板とモードのセット ===================================
if ($spmode) {
    if ($spmode == "taborn" or $spmode == "soko") {
        $aThreadList->setIta($host, $bbs, P2Util::getItaName($host, $bbs));
    }
    $aThreadList->setSpMode($spmode);
} else {
    // if(!$p2_setting['itaj']){$p2_setting['itaj'] = P2Util::getItaName($host, $bbs);}
    $aThreadList->setIta($host, $bbs, $p2_setting['itaj']);

    // スレッドあぼーんリスト読込
    $taborn_file = $aThreadList->getIdxDir() . 'p2_threads_aborn.idx';
    if ($tabornlines = FileCtl::file_read_lines($taborn_file, FILE_IGNORE_NEW_LINES)) {
        $ta_num = sizeof($tabornlines);
        foreach ($tabornlines as $l) {
            $data = explode('<>', $l);
            $ta_keys[ $data[1] ] = true;
        }
    }
}

// ソースリスト読込
$lines = $aThreadList->readList();

//============================================================
// それぞれの行解析
//============================================================

$linesize = sizeof($lines);
$subject_txts = array();

for ($x = 0; $x < $linesize ; $x++) {
    $aThread = new Thread();

    $l = rtrim($lines[$x]);

    // データ読み込み
    if ($aThreadList->spmode) {
        switch ($aThreadList->spmode) {
        case "recent": // 履歴
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "res_hist": // 書き込み履歴
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "fav": // お気に
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "taborn":    // スレッドあぼーん
            $la = explode("<>", $l);
            $aThread->key = $la[1];
            $aThread->host = $aThreadList->host;
            $aThread->bbs = $aThreadList->bbs;
            break;
        case "soko":    // dat倉庫
            $la = explode("<>", $l);
            $aThread->key = $la[1];
            $aThread->host = $aThreadList->host;
            $aThread->bbs = $aThreadList->bbs;
            break;
        case "palace":    // スレの殿堂
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        }
    // subject (not spmode)
    } else {
        $aThread->getThreadInfoFromSubjectTxtLine($l);
        $aThread->host = $aThreadList->host;
        $aThread->bbs = $aThreadList->bbs;
    }

    // メモリ節約のため
    $lines[$x] = null;

    // hostかbbsかkeyが不明ならスキップ
    if (!($aThread->host && $aThread->bbs && $aThread->key)) {
        unset($aThread);
        continue;
    }

    $subject_id = $aThread->host . '/' . $aThread->bbs;

    // {{{ 新しいかどうか(for subject)

    if (!$aThreadList->spmode) {
        if (!isset($pre_subject_keys[$aThread->key])) {
            $aThread->new = true;
        }
    }

    // }}}
    // {{{ スレッドあぼーんチェック

    if ($aThreadList->spmode != 'taborn' && isset($ta_keys[$aThread->key])) {
        unset($ta_keys[$aThread->key]);
        continue; //あぼーんスレはスキップ
    }

    // }}}

    $aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);
    $aThread->getThreadInfoFromIdx(); // 既得スレッドデータをidxから取得

    // {{{ spmode(殿堂入りを除く)なら

    if ($aThreadList->spmode && $aThreadList->spmode != 'palace') {

        //  subject.txtが未DLなら落としてデータを配列に格納
        if (!isset($subject_txts[$subject_id])) {
            $subject_txts[$subject_id] = array();
            $aSubjectTxt = new SubjectTxt($aThread->host, $aThread->bbs);

            //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('subthre_read'); //
            if ($aThreadList->spmode == "soko" or $aThreadList->spmode == "taborn") {

                if (is_array($aSubjectTxt->subject_lines)) {
                    $it = 1;
                    foreach ($aSubjectTxt->subject_lines as $asbl) {
                        if (preg_match("/^([0-9]+)\.(?:dat|cgi)(?:,|<>)(.+) ?(?:\(|（)([0-9]+)(?:\)|）)/", $asbl, $matches)) {
                            $akey = $matches[1];
                            $subject_txts[$subject_id][$akey] = array(
                                //'ttitle' => rtrim($matches[2]),
                                'rescount' => (int)$matches[3],
                                //'torder' => $it,
                            );
                        }
                        $it++;
                    }
                }

            } else {
                $subject_txts[$subject_id] = $aSubjectTxt->subject_lines;

            }
            //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('subthre_read');//
        }

        //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('subthre_check');//
        // スレ情報取得 =============================
        if ($aThreadList->spmode == "soko" or $aThreadList->spmode == "taborn") {

            if (isset($subject_txts[$subject_id][$aThread->key])) {

                // 倉庫はオンラインを含まない
                if ($aThreadList->spmode == "soko") {
                    //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('subthre_check'); //
                    unset($aThread);
                    continue;
                } elseif ($aThreadList->spmode == "taborn") {
                    // subject.txt からスレ情報取得
                    // $aThread->getThreadInfoFromSubjectTxtLine($l);
                    //$aThread->isonline = true;
                    //$ttitle = $subject_txts[$subject_id][$aThread->key]['ttitle'];
                    //$aThread->setTtitle($ttitle);
                    $aThread->rescount = $subject_txts[$subject_id][$aThread->key]['rescount'];
                    if ($aThread->readnum) {
                        $aThread->unum = $aThread->rescount - $aThread->readnum;
                        // machi bbs はsageでsubjectの更新が行われないそうなので調整しておく
                        if ($aThread->unum < 0) { $aThread->unum = 0; }
                        $aThread->nunum = $aThread->unum;
                    }
                    //$aThread->torder = $subject_txts[$subject_id][$aThread->key]['torder'];
                }

            }

        } else {

            if (isset($subject_txts[$subject_id])) {
                $it = 1;
                $thread_key = (string)$aThread->key;
                $thread_key_len = strlen($thread_key);
                foreach ($subject_txts[$subject_id] as $l) {
                    if (strncmp($l, $thread_key, $thread_key_len) == 0) {
                        // subject.txt からスレ情報取得
                        $aThread->getThreadInfoFromSubjectTxtLine($l);
                        break;
                    }
                    $it++;
                }
            }

        }
        //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('subthre_check'); //
    }

    // }}} spmode

    // 新着あり
    if ($aThread->unum > 0) {
        $shinchaku_attayo = true;
        $shinchaku_num = $shinchaku_num + $aThread->unum; // 新着数set

    // 新規スレ
    } elseif ($aThread->new) {
        $_newthre_num++; // ※ShowBrdMenuPc.php
    }

}

unset($aThread, $aThreadList, $lines, $pre_subject_keys, $subject_txts, $ta_keys);

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
