<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=4 fdm=marker: */
/* mi: charset=Shift_JIS */

class Google_Converter
{
    // {{{ properties

    /**
     * 一覧表示用データのフォーマット
     *
     * @var array
     * @access private
     */
    var $outputvalue_skel = array(
        'type'   => '',
        'ita'    => '',
        'url'    => '',
        'ls'     => '',
        'moto'   => '',
        'target' => ''
    );

    // }}}
    // {{{ toOutputValue()

    /**
     * ResultElementオブジェクトを一覧表示用に調整する
     *
     * @return array
     * @access public
     */
    function toOutputValue(&$obj)
    {
        $re_ita  = '{^http://([a-z]+[0-9]*\.2ch\.net)/([0-9a-z]+)/((index|subback)\.html)?$}';
        $re_thre = '{^http://([a-z]+[0-9]*\.2ch\.net)/test/read\.cgi/([0-9a-z]+)/([0-9]+)/([^/]+)?}';

        if (preg_match($re_thre, $obj->URL, $m)) {
            $ov = $this->toOutputValue2chThread($obj->URL, $m);
        } elseif (preg_match($re_ita, $obj->URL, $m)) {
            $ov = $this->toOutputValue2chBBS($obj->URL, $m);
        } else {
            $ov = $this->toOutputValueOthers($obj->URL, $m);
        }

        if ($ov['moto']) {
            $ov['type'] = "<a class=\"thre_title\" href=\"{$ov['moto']}\" targer=\"_blank\">{$ov['type']}</a>";
        }
        $ov['title'] = str_replace('<b>', '<b class="filtering">', $obj->title);
        $ov['title'] = mb_convert_encoding($ov['title'], 'SJIS-win', 'UTF-8');

        return $ov;
    }

    // }}}
    // {{{ toOutputValue2chThread()

    /**
     * URLが2chのスレッドへのリンクのとき
     *
     * @return array
     * @access private
     */
    function toOutputValue2chThread($url, $m)
    {
        $ov = $this->outputvalue_skel;

        $ov['type'] = 'スレ';
        $ov['ita']  = $m[2];
        $ov['url']  = $GLOBALS['_conf']['read_php'] . '?host=' . $m[1] . '&amp;bbs=' . $m[2] . '&amp;key=' . $m[3];
        if ($m[4]) {
            $ov['url'] .= '&amp;ls=' . $m[4];
            $ov['ls'] = $m[4];
        }
        $ov['moto']   = P2Util::throughIme($url);
        $ov['target'] = 'read';

        return $ov;
    }

    // }}}
    // {{{ toOutputValue2chBBS()

    /**
     * URLが2chの板へのリンクのとき
     *
     * @return array
     * @access private
     */
    function toOutputValue2chBBS($url, $m)
    {
        $d = explode('.', $m[1]);
        $subdomain = $d[0];
        if (in_array($subdomain, array('www', 'info', 'find', 'p2'))) {
            return $this->toOutputValueOthers($url, $m);
        }

        $ov = $this->outputvalue_skel;

        $ov['type']   = '板';
        $ov['ita']    = $m[2];
        $ov['url']    = $GLOBALS['_conf']['subject_php'] . '?host=' . $m[1] . '&amp;bbs=' . $m[2];
        $ov['moto']   = P2Util::throughIme($url);
        $ov['target'] = 'subject';

        return $ov;
    }

    // }}}
    // {{{ toOutputValueOthers()

    /**
     * URLがその他のリンクのとき
     *
     * @return array
     * @access private
     */
    function toOutputValueOthers($url, $m)
    {
        $ext_win_target = $GLOBALS['_conf']['ext_win_target'];
        $ov = $this->outputvalue_skel;

        $ov['type']   = '他';
        $ov['url']    = P2Util::throughIme($url);
        $ov['target'] = $ext_win_target;

        return $ov;
    }

    // }}}
    // {{{ toPopUpValue()

    /**
     * ResultElementオブジェクトをポップアップ用に調整する
     *
     * @return array
     * @access public
     */
    function toPopUpValue(&$obj)
    {
        $snippet = str_replace('<b>', '<b class="filtering">', $obj->snippet);
        $snippet = mb_convert_encoding($snippet, 'SJIS-win', 'UTF-8');
        $popup = $obj->URL . '<br>' . $snippet;
        return $popup;
    }

    // }}}
}

?>
