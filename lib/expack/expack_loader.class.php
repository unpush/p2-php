<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// {{{ class ExpackLoader

/**
 * 拡張パック初期化クラス
 */
class ExpackLoader
{
    // {{{ loadActiveMona()

    function loadActiveMona()
    {
        global $_conf, $_exconf;

        if (defined('P2_ACTIVEMONA_AVAILABLE')) {
            return;
        }

        if ((!$_conf['ktai'] && ($_exconf['aMona']['*'] || $_exconf['spm']['with_aMona'])) ||
            ($_conf['ktai'] && $_exconf['aMona']['*'] && $_exconf['aMona']['aaryaku_k'])
        ) {
            require_once (P2EX_LIBRARY_DIR . '/activemona.class.php');
            define('P2_ACTIVEMONA_AVAILABLE', 1);
        } else {
            define('P2_ACTIVEMONA_AVAILABLE', 0);
        }
    }

    // }}}
    // {{{ initActiveMona()

    function initActiveMona(&$aShowThread)
    {
        global $_conf, $_exconf;

        $aShowThread->activemona = &ActiveMona::singleton($_exconf['aMona']);

        if (!$_conf['ktai']) {
            if ($_exconf['aMona']['*'] >= 2 && $_exconf['aMona']['aaryaku']) {
                $aShowThread->am_aaryaku = $_exconf['aMona']['aaryaku'];
                $aShowThread->am_aaryaku_msg = htmlspecialchars('<<AA略>>');
            } else {
                $aShowThread->am_enabled = TRUE;
            }
        } else {
            $aShowThread->am_aaryaku = $_exconf['aMona']['aaryaku_k'];
            $aShowThread->am_aaryaku_msg = htmlspecialchars('<<AA略>>');
        }
    }

    // }}}
    // {{{ loadImageCache()

    function loadImageCache()
    {
        global $_conf, $_exconf;

        if (defined('P2_IMAGECACHE_AVAILABLE')) {
            return;
        }

        if ((!$_conf['ktai'] && $_exconf['imgCache']['*'] % 2 == 1) ||
            ($_conf['ktai'] && $_exconf['imgCache']['*'] >= 2)
        ) {
            require_once (P2EX_LIBRARY_DIR . '/ic2/loadconfig.inc.php');
            require_once (P2EX_LIBRARY_DIR . '/ic2/db_images.class.php');
            require_once (P2EX_LIBRARY_DIR . '/ic2/db_blacklist.class.php');
            require_once (P2EX_LIBRARY_DIR . '/ic2/db_errors.class.php');
            require_once (P2EX_LIBRARY_DIR . '/ic2/thumbnail.class.php');
            define('P2_IMAGECACHE_AVAILABLE', 2);
        } else {
            define('P2_IMAGECACHE_AVAILABLE', 0);
        }
    }

    // }}}
    // {{{ initImageCache()

    function initImageCache(&$aShowThread)
    {
        global $_conf, $_exconf;

        if (!$_conf['ktai']) {
            $aShowThread->thumbnailer = &new ThumbNailer(1);
        } else {
            $aShowThread->inline_prvw = &new ThumbNailer(1);
            $aShowThread->thumbnailer = &new ThumbNailer(2);
        }

        if ($aShowThread->thumbnailer->ini['General']['automemo']) {
            $aShowThread->img_memo = IC2DB_Images::uniform($aShowThread->thread->ttitle, 'SJIS-win');
            $hint = mb_convert_encoding('◎◇', 'UTF-8', 'SJIS-win');
            $aShowThread->img_memo_query = '&amp;hint=' . rawurlencode($hint);
            $aShowThread->img_memo_query .= '&amp;memo=' . rawurlencode($aShowThread->img_memo);
        } else {
            $aShowThread->img_memo = NULL;
            $aShowThread->img_memo_query = '';
        }
    }

    // }}}
    // {{{ loadLiveView()

    function loadLiveView()
    {
        global $_conf, $_exconf;

        if (!$_conf['ktai'] && $_exconf['liveView']['*']) {
            require_once (P2EX_LIBRARY_DIR . '/arraycleaner.class.php');
        }
    }

    // }}}
    // {{{ initLiveView(()

    function initLiveView(&$aShowThread)
    {
        global $_conf, $_exconf;

        if (!$_conf['ktai']) {
            $aShowThread->lv_enabled = TRUE;
            $aShowThread->arraycleaner = &ArrayCleaner::singleton(2, 'SJIS');
            if ($_exconf['aMona']['*']) {
                if ($_exconf['aMona']['aaryaku_l']) {
                    $aShowThread->am_aaryaku = $_exconf['aMona']['aaryaku_l'];
                    $aShowThread->am_aaryaku_msg = htmlspecialchars('<<AA略>>');
                    $aShowThread->am_enabled = FALSE;
                } elseif ($_exconf['aMona']['aaryaku']) {
                    $aShowThread->am_aaryaku = FALSE;
                    $aShowThread->am_enabled = TRUE;
                }
            }
        }
    }

    // }}}
}

// }}}

?>
