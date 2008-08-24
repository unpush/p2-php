<?php
// {{{ Google_Converter

class Google_Converter
{
    // {{{ properties

    /**
     * 一覧表示用データのフォーマット
     *
     * @var array
     */
    private $_outputvalue_skel = array(
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
     */
    public function toOutputValue($obj)
    {
        $re_ita  = '{^http://([a-z]+[0-9]*\.2ch\.net)/([0-9a-z]+)/((index|subback)\.html)?$}';
        $re_thre = '{^http://([a-z]+[0-9]*\.2ch\.net)/test/read\.cgi/([0-9a-z]+)/([0-9]+)/([^/]+)?}';

        if (preg_match($re_thre, $obj->URL, $m)) {
            $ov = $this->_toOutputValue2chThread($obj->URL, $m);
        } elseif (preg_match($re_ita, $obj->URL, $m)) {
            $ov = $this->_toOutputValue2chBBS($obj->URL, $m);
        } else {
            $ov = $this->_toOutputValueOthers($obj->URL, $m);
        }

        if ($ov['moto']) {
            $ov['type'] = "<a class=\"thre_title\" href=\"{$ov['moto']}\" targer=\"_blank\">{$ov['type']}</a>";
        }
        $ov['title'] = str_replace('<b>', '<b class="filtering">', $obj->title);
        $ov['title'] = mb_convert_encoding($ov['title'], 'CP932', 'UTF-8');

        return $ov;
    }

    // }}}
    // {{{ _toOutputValue2chThread()

    /**
     * URLが2chのスレッドへのリンクのとき
     *
     * @return array
     */
    private function _toOutputValue2chThread($url, $m)
    {
        $ov = $this->_outputvalue_skel;

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
    // {{{ _toOutputValue2chBBS()

    /**
     * URLが2chの板へのリンクのとき
     *
     * @return array
     */
    private function _toOutputValue2chBBS($url, $m)
    {
        $d = explode('.', $m[1]);
        $subdomain = $d[0];
        if (in_array($subdomain, array('www', 'info', 'find', 'p2'))) {
            return $this->_toOutputValueOthers($url, $m);
        }

        $ov = $this->_outputvalue_skel;

        $ov['type']   = '板';
        $ov['ita']    = $m[2];
        $ov['url']    = $GLOBALS['_conf']['subject_php'] . '?host=' . $m[1] . '&amp;bbs=' . $m[2];
        $ov['moto']   = P2Util::throughIme($url);
        $ov['target'] = 'subject';

        return $ov;
    }

    // }}}
    // {{{ _toOutputValueOthers()

    /**
     * URLがその他のリンクのとき
     *
     * @return array
     */
    private function _toOutputValueOthers($url, $m)
    {
        $ext_win_target = $GLOBALS['_conf']['ext_win_target'];
        $ov = $this->_outputvalue_skel;

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
     */
    public function toPopUpValue($obj)
    {
        $snippet = str_replace('<b>', '<b class="filtering">', $obj->snippet);
        $snippet = mb_convert_encoding($snippet, 'CP932', 'UTF-8');
        $popup = $obj->URL . '<br>' . $snippet;
        return $popup;
    }

    // }}}
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
