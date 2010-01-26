<?php

// {{{ P2Ime

/**
 * rep2 - URLゲートウェイ変換クラス
 */
class P2Ime
{
    // {{{ properties

    /**
     * througth() から呼び出される、実際のURL変換メソッド名
     *
     * @var string
     */
    protected $_method;

    /**
     * 自動転送しない例外拡張子のリスト
     *
     * @var array
     */
    protected $_exceptions;

    /**
     * 自動転送しない例外拡張子の最大の長さ
     *
     * @var int
     */
    protected $_maxExceptionLength;

    /**
     * httpプロトコルのリンクはゲートを通さない
     *
     * @var bool
     */
    protected $_ignoreHttp;

    /**
     * ゲートのURL
     *
     * @var string
     */
    protected $_gateUrl;

    /**
     * 自動転送の待ち時間 (秒)
     * 負数の場合は手動転送
     *
     * @var int
     */
    protected $_delay;

    // }}}
    // {{{ __construct()

    /**
     * コンストラクタ
     *
     * @param string $type
     * @param array $exceptions
     * @param bool $ignoreHttp
     */
    public function __construct($type = null, array $exceptions = null, $ignoreHttp = null)
    {
        global $_conf;

        // {{{ パラメータの初期化

        // ゲートウェイタイプ
        if ($type === null) {
            $type = $_conf['through_ime'];
            // Cookieが無効 (URIにセッションIDを含む) のときは強制
            if (!$type && !$_conf['use_cookies']) {
                $type = 'ex';
            }
        }

        // pのみ手動転送
        if ($type == 'p2pm') {
            $type = 'p2';
        } elseif ($type == 'expm') {
            $type = 'ex';
        }

        // 自動転送しない拡張子
        if ($exceptions === null) {
            if ($_conf['ime_manual_ext']) {
                $this->_exceptions = explode(',', strtolower(trim($_conf['ime_manual_ext'])));
            } else {
                $this->_exceptions = array();
            }
        } else {
            $this->_exceptions = array_map('strtolower', $exceptions);
        }
        if ($this->_exceptions) {
            $this->_maxExceptionLength = max(array_map('strlen', $this->_exceptions));
        } else {
            $this->_maxExceptionLength = 0;
        }

        // httpのリンクは通さない
        if ($ignoreHttp === null) {
            // $_conf['through_ime_http_only'] が 1 で、
            // セキュアな接続で、Cookieが有効 (URIにセッションIDを含まない) のとき、
            // httpプロトコルのリンクはゲートを通さない。
            if ($_conf['through_ime_http_only'] && P2_HTTPS_CONNECTION && $_conf['use_cookies']) {
                $this->_ignoreHttp = true;
            } else {
                $this->_ignoreHttp = false;
            }
        } else {
            $this->_ignoreHttp = (bool)$ignoreHttp;
        }

        // 自動転送の待ち時間の既定値
        $this->_delay = -1;

        // }}}
        // {{{ ゲートウェイ判定

        switch ($type) {
        // {{{ p2ime
        case 'p2':   // 自動転送
        case 'p2m':  // 手動転送
            $this->_method = '_throughP2Ime';
            if ($type == 'p2m') {
                $this->_delay = -1;
            } else {
                $this->_delay = 0;
            }
            $this->_gateUrl = $_conf['p2ime_url'];
            break;
        // }}}
        // {{{ gate.php
        case 'ex':   // 自動転送1秒
        case 'exq':  // 自動転送0秒
        case 'exm':  // 手動転送
            $this->_method = '_throughGatePhp';
            if ($type == 'exm') {
                $this->_delay = -1;
            } elseif ($type == 'exq') {
                $this->_delay = 0;
            } else {
                $this->_delay = 1;
            }
            $this->_gateUrl = $_conf['expack.gate_php'];
            break;
        // }}}
        // {{{ Google
        case 'google':
            $this->_method = '_throughGoogleGateway';
            if ($_conf['ktai'] && !$_conf['iphone']) {
                $this->_gateUrl = 'http://www.google.co.jp/gwt/x?u=';
            } else {
                $this->_gateUrl = 'http://www.google.co.jp/url?q=';
            }
            break;
        // }}}
        default:
            $this->_method = '_passThrough';
            $this->_gateUrl = null;
        }

        // }}}
    }

    // }}}
    // {{{ through()

    /**
     * URLを変換する
     *
     * @param string $url
     * @param int $delay
     * @param bool $escape
     * @return string
     */
    public function through($url, $delay = null, $escape = true)
    {
        if ($delay === null) {
            if ($this->_isExceptionUrl($url)) {
                $delay = -1;
            } else {
                $delay = $this->_delay;
            }
        }

        if (!($this->_ignoreHttp && preg_match('!^http://!', $url))) {
            $url = $this->{$this->_method}($url, $delay);
        }
        if ($escape) {
            return htmlspecialchars($url, ENT_QUOTES, 'Shift_JIS', false);
        } else {
            return $url;
        }
    }

    // }}}
    // {{{ _throughP2Ime()

    /**
     * p2imeを通すようにURLを変換する
     *
     * p2imeは、enc, m, url の引数順序が固定されているので注意
     *
     * @param string $url
     * @param int $delay
     * @return string
     */
    protected function _throughP2Ime($url, $delay)
    {
        if ($delay < 0) {
            return $this->_gateUrl . '?enc=1&m=1&url=' . rawurlencode($url);
        } else {
            return $this->_gateUrl . '?enc=1&url=' . rawurlencode($url);
        }
    }

    // }}}
    // {{{ _throughGatePhp()

    /**
     * gate.phpを通すようにURLを変換する
     *
     * @param string $url
     * @param int $delay
     * @return string
     */
    protected function _throughGatePhp($url, $delay)
    {
        return sprintf('%s?u=%s&d=%d', $this->_gateUrl, rawurlencode($url), $delay);
    }

    // }}}
    // {{{ _throughGoogleGateway()

    /**
     * GoogleのURLゲートウェイを通すようにURLを変換する
     *
     * @param string $url
     * @param int $delay (unused)
     * @return string
     */
    protected function _throughGoogleGateway($url, $delay)
    {
        return $this->_gateUrl . rawurlencode($url);
    }

    // }}}
    // {{{ _passThrough()

    /**
     * URLをそのまま返す
     *
     * @param string $url
     * @param int $delay (unused)
     * @return string
     */
    protected function _passThrough($url, $delay)
    {
        return $url;
    }

    // }}}
    // {{{ _isExceptionUrl()

    /**
     * 自動転送の例外URL判定
     *
     * @param string $url
     * @return bool
     */
    protected function _isExceptionUrl($url)
    {
        if ($this->_exceptions) {
            if (false !== ($pos = strrpos($url, '.'))) {
                $pos++;
                if (strlen($url) - $pos <= $this->_maxExceptionLength) {
                    $extension = strtolower(substr($url, $pos));
                    if (in_array($extension, $this->_exceptions)) {
                        return false;
                    }
                }
            }
        }
        return false;
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
