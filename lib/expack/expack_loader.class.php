<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=4 fdm=marker: */
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
        global $_conf;

        if (defined('P2_ACTIVEMONA_AVAILABLE')) {
            return;
        }

        if ((!$_conf['ktai'] && $_conf['expack.am.enabled']) ||
            ($_conf['ktai'] && $_conf['expack.am.enabled'] && $_conf['expack.am.autong_k'])
        ) {
            require_once P2EX_LIB_DIR . '/activemona.class.php';
            define('P2_ACTIVEMONA_AVAILABLE', 1);
        } else {
            define('P2_ACTIVEMONA_AVAILABLE', 0);
        }
    }

    // }}}
    // {{{ initActiveMona()

    function initActiveMona(&$aShowThread)
    {
        global $_conf;

        $aShowThread->activeMona = &ActiveMona::singleton();
        $aShowThread->am_enabled = true;

        if (!$_conf['ktai']) {
            if ($_conf['expack.am.autodetect']) {
                $aShowThread->am_autodetect = true;
            }
            if ($_conf['expack.am.display'] == 0) {
                $aShowThread->am_side_of_id = true;
            } elseif ($_conf['expack.am.display'] == 1) {
                $aShowThread->am_on_spm = true;
            } elseif ($_conf['expack.am.display'] == 2) {
                $aShowThread->am_side_of_id = true;
                $aShowThread->am_on_spm = true;
            }
        } elseif ($_conf['expack.am.autong_k']) {
            $aShowThread->am_autong = true;
        }
    }

    // }}}
    // {{{ loadImageCache()

    function loadImageCache()
    {
        global $_conf;

        if (defined('P2_IMAGECACHE_AVAILABLE')) {
            return;
        }

        if ((!$_conf['ktai'] && $_conf['expack.ic2.enabled'] % 2 == 1) ||
            ($_conf['ktai'] && $_conf['expack.ic2.enabled'] >= 2)
        ) {
            require_once P2EX_LIB_DIR . '/ic2/loadconfig.inc.php';
            require_once P2EX_LIB_DIR . '/ic2/db_images.class.php';
            require_once P2EX_LIB_DIR . '/ic2/db_blacklist.class.php';
            require_once P2EX_LIB_DIR . '/ic2/db_errors.class.php';
            require_once P2EX_LIB_DIR . '/ic2/thumbnail.class.php';
            define('P2_IMAGECACHE_AVAILABLE', 2);
        } else {
            define('P2_IMAGECACHE_AVAILABLE', 0);
        }
    }

    // }}}
    // {{{ loadAAS()

    function loadAAS()
    {
        global $_conf;

        if (defined('P2_AAS_AVAILABLE')) {
            return;
        }

        if ($_conf['expack.aas.enabled']) {
            if ($_conf['expack.aas.inline']) {
                define('P2_AAS_AVAILABLE', 2);
            } else {
                define('P2_AAS_AVAILABLE', 1);
            }
        } else {
            define('P2_AAS_AVAILABLE', 0);
        }
    }

    // }}}
    // {{{ initImageCache()

    function initImageCache(&$aShowThread)
    {
        global $_conf;

        if (!$_conf['ktai']) {
            $aShowThread->thumbnailer = &new ThumbNailer(1);
        } else {
            $aShowThread->inline_prvw = &new ThumbNailer(1);
            $aShowThread->thumbnailer = &new ThumbNailer(2);
        }

        if ($aShowThread->thumbnailer->ini['General']['automemo']) {
            $aShowThread->img_memo = IC2DB_Images::uniform($aShowThread->thread->ttitle, 'SJIS-win');
            $hint = mb_convert_encoding('◎◇', 'UTF-8', 'SJIS-win');
            $aShowThread->img_memo_query = '&amp;_hint=' . rawurlencode($hint);
            $aShowThread->img_memo_query .= '&amp;memo=' . rawurlencode($aShowThread->img_memo);
        } else {
            $aShowThread->img_memo = NULL;
            $aShowThread->img_memo_query = '';
        }
    }

    // }}}
    // {{{ initAAS()

    function initAAS(&$aShowThread)
    {
        global $_conf;

        if (!$_conf['ktai']) {
            //
        } else {
            $mobile = &Net_UserAgent_Mobile::singleton();
            /**
             * @link http://www.nttdocomo.co.jp/p_s/imode/tag/emoji/e1.html
             * @link http://www.au.kddi.com/ezfactory/tec/spec/3.html
             * @link http://developers.vodafone.jp/dp/tool_dl/web/picword_top.php
             */
            if ($mobile->isDoCoMo()) {
                $aShowThread->aas_rotate = '&#63962;';      // リサイクル, 拡42, F9DA
            } elseif ($mobile->isEZweb()) {
                $aShowThread->aas_rotate = '&#xF47D;';      // 循環矢印, 807
            } elseif ($mobile->isVodafone()) {
                $aShowThread->aas_rotate = "\x1b\$Pc\x0f";  // 渦巻, 414
            }
        }
    }

    // }}}
}

// }}}
