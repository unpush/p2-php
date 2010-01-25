<?php
/**
 * WWW Access on PHP
 * http://member.nifty.ne.jp/hippo2000/perltips/LWP.html を参考にしつつ似たような簡易のものを
 *
 * @author aki
 */

// 2005/04/20 aki このクラスは役割終了にして、PEAR利用に移行したい（HTTP_Clientなど）

// {{{ WapUserAgent

/**
 * UserAgent クラス
 */
class WapUserAgent
{
    // {{{ constants

    const CRLF = "\r\n";

    // }}}
    // {{{ properties

    /**
     * User-Agent
     *
     * @var string
     */
    private $_agent = null;

    /**
     * fsockopen() 時のタイムアウト秒
     *
     * @var int
     */
    private $_timeout = -1;

    /**
     * fread() 時のタイムアウト秒
     *
     * @var int
     */
    private $_readTimeout = -1;

    /**
     * fsockopen() に@演算子を付けて、エラーを抑制するならtrue
     *
     * @var bool
     */
    private $_atFsockopen = false;

    /**
     * @var int
     */
    private $_maxRedirect = 3;

    /**
     * @var int
     */
    private $_redirectCount = 0;

    /**
     * @var array
     */
    private $_redirectCache = array();

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     *
     * @param string $agent_name
     */
    public function __construct($agent_name = null)
    {
        if ($agent_name !== null) {
            $this->setAgent($agent_name);
        }
    }

    // }}}
    // {{{ setAgent()

    /**
     * setAgent
     *
     * @param string $agent_name
     * @return void
     */
    public function setAgent($agent_name)
    {
        $this->_agent = $agent_name;
    }

    // }}}
    // {{{ setTimeout()

    /**
     * set timeout
     *
     * @param int $timeout
     * @param int $readTimeout
     * @return void
     */
    public function setTimeout($timeout, $readTimeout)
    {
        $this->_timeout = $timeout;
        $this->_readTimeout = $readTimeout;
    }

    // }}}
    // {{{ setAtFsockopen()

    /**
     * set atFsockopen
     *
     * @param bool $atFsockopen
     * @return void
     */
    public function setAtFsockopen($atFsockopen)
    {
        $this->_atFsockopen = $atFsockopen;
    }

    // }}}
    // {{{ header()

    /**
     * HTTPリクエストをサーバに送信して、ヘッダレスポンス（WapResponseオブジェクト）を取得する
     *
     * @param WapRequest $req
     * @return WapResponse
     * @see WapUserAgent::request()
     */
    public function header(WapRequest $req)
    {
        return $this->request($req, array('onlyHeader' => true));
    }

    // }}}
    // {{{ request()

    /**
     * HTTPリクエストをサーバに送信して、レスポンス（WapResponseオブジェクト）を取得する
     *
     * @thanks http://www.spencernetwork.org/memo/tips-3.php
     *
     * @param WapRequest $req
     * @param array $options
     * @return WapResponse
     */
    public function request(WapRequest $req, array $options = array())
    {
        if (!empty($options['onlyHeader'])) {
            $req->setOnlyHeader($options['onlyHeader']);
        }

        if (!$purl = parse_url($req->url)) {
            $res = new WapResponse;
            $res->message = 'parse_url() failed';
            return $res;
        }

        if (isset($purl['query'])) {
            $purl['query'] = '?' . $purl['query'];
        } else {
            $purl['query'] = '';
        }
        $default_port = ($purl['scheme'] == 'https') ? 443 : 80;

        // プロキシ
        if ($req->proxy) {
            $send_host = $req->proxy['host'];
            $send_port = isset($req->proxy['port']) ? $req->proxy['port'] : $default_port;
            $send_path = $req->url;
        } else {
            $send_host = $purl['host'];
            $send_port = isset($purl['port']) ? $purl['port'] : $default_port;
            $send_path = $purl['path'] . $purl['query'];
        }

        // SSL
        if ($purl['scheme'] == 'https') {
            $send_host = 'ssl://' . $send_host;
        }

        $request = $req->method . ' ' . $send_path . ' HTTP/1.0' . self::CRLF;
        $request .= 'Host: ' . $purl['host'] . self::CRLF;
        if ($this->_agent) {
            $request .= 'User-Agent: '. $this->_agent . self::CRLF;
        }
        $request .= 'Connection: Close' . self::CRLF;
        //$request .= 'Accept-Encoding: gzip' . self::CRLF;

        if ($req->modified) {
            $request .= 'If-Modified-Since: ' . $req->modified . self::CRLF;
        }

        // Basic認証用のヘッダ
        if (isset($purl['user']) && isset($purl['pass'])) {
            $request .= 'Authorization: Basic ' . base64_encode($purl['user'] . ':' . $purl['pass']) . self::CRLF;
        }

        // 追加ヘッダ
        if ($req->headers) {
            $request .= $req->headers;
        }

        // POSTの時はヘッダを追加して末尾にURLエンコードしたデータを添付
        if (strtoupper($req->method) == 'POST') {
            // 通常はURLエンコードする
            if (empty($req->noUrlencodePost)) {
                foreach ($req->post as $name => $value) {
                    $POST[] = $name . '=' . rawurlencode($value);
                }
                $postdata_content_type = 'application/x-www-form-urlencoded';

            // ●ログインのときなどはURLエンコードしない
            } else {
                foreach ($req->post as $name => $value) {
                    $POST[] = $name . '=' . $value;
                }
                $postdata_content_type = 'text/plain';
            }
            $postdata = implode('&', $POST);
            $request .= 'Content-Type: ' . $postdata_content_type . self::CRLF;
            $request .= 'Content-Length: ' . strlen($postdata) . self::CRLF;
            $request .= self::CRLF;
            $request .= $postdata;
        } else {
            $request .= self::CRLF;
        }

        $res = new WapResponse;

        // WEBサーバへ接続
        if ($this->_timeout > 0) {
            if ($this->_atFsockopen) {
                $fp = @fsockopen($send_host, $send_port, $errno, $errstr, $this->_timeout);
            } else {
                $fp = fsockopen($send_host, $send_port, $errno, $errstr, $this->_timeout);
            }
        } else {
            if ($this->_atFsockopen) {
                $fp = @fsockopen($send_host, $send_port, $errno, $errstr);
            } else {
                $fp = fsockopen($send_host, $send_port, $errno, $errstr);
            }
        }

        if (!$fp) {
            $res->code = $errno; // ex) 602
            $res->message = $errstr; // ex) "Connection Failed"
            return $res;
        }

        if ($this->_readTimeout > 0) {
            stream_set_timeout($fp, $this->_readTimeout, 0);
        }

        fputs($fp, $request);
        $body = '';

        // header response
        while (!p2_stream_eof($fp, $timed_out)) {
            $l = fgets($fp,128000);
            //echo $l."<br>"; //
            // ex) HTTP/1.1 304 Not Modified
            if (preg_match('/^(.+?): (.+)\\r\\n/', $l, $matches)) {
                $res->headers[$matches[1]] = $matches[2];
            } elseif (preg_match('/HTTP\\/1\\.\\d (\\d+) (.+)\\r\\n/', $l, $matches)) {
                $res->code = (int)$matches[1];
                $res->message = $matches[2];
                $res->headers['HTTP'] = rtrim($l);
            } elseif ($l == self::CRLF) {
                break;
            }
        }

        // body response
        if (!$req->onlyHeader) {
            while (!p2_stream_eof($fp, $timed_out)) {
                $body .= fread($fp, 4096);
            }
            $res->setContent($body);
        }

        fclose($fp);

        // リダイレクト(301 Moved, 302 Found)を追跡
        // RFC2616 - Section 10.3
        /*if ($GLOBALS['trace_http_redirect']) {
            if ($res->code == 301 || ($res->code == 302 && $req->isSafeMethod())) {
                if (!$this->_redirectCache) {
                    $this->_maxRedirect   = 5;
                    $this->_redirectCount = 0;
                    $this->_redirectCache = array();
                }
                while ($res->isRedirect() && isset($res->headers['Location']) && $this->_redirectCount < $this->_maxRedirect) {
                    $this->_redirectCache[] = $res;
                    $req->setUrl($res->headers['Location']);
                    $res = $this->request($req);
                    $this->_redirectCount++;
                }
            }
        } elseif ($res->isRedirect() && isset($res->headers['Location'])) {
            $res->message .= " (Location: <a href=\"{$res->headers['Location']}\">{$res->headers['Location']}</a>)";
        }*/

        return $res;
    }

    // }}}
}

// }}}
// {{{ WapRequest

/**
 * Request クラス
 */
class WapRequest
{
    // {{{ constants

    const CRLF = "\r\n";

    // }}}
    // {{{ properties

    /**
     * GET, POST, HEADのいずれか(デフォルトはGET、PUT,DELETE等はなし)
     *
     * @var string
     */
    public $method = 'GET';

    /**
     * http://から始まるURL( http://user:pass@host:port/path?query )
     *
     * @var string
     */
    public $url = null;

    /**
     * 任意の追加ヘッダ
     *
     * @var string
     */
    public $headers = null;

    /**
     * POSTの時に送信するデータを格納した配列("変数名"=>"値")
     *
     * @var array
     */
    public $post = array();

    /**
     * ('host'=>"", 'port'=>"")
     *
     * @var array
     */
    public $proxy = array();

    /**
     * If-Modified-Since
     *
     * @var string
     */
    public $modified = null;

    /**
     * ヘッダだけを取得するならtrue
     *
     * @var bool
     */
    public $onlyHeader = false;

    /**
     * POSTデータをurlencodeしないならtrue。通常はurlencodeするのでfalse
     *
     * @var bool
     */
    public $noUrlencodePost = false;

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     *
     * @param string $url
     * @param string $method
     * @param array $options
     */
    public function __construct($url = null, $method = null, array $options = null)
    {
        if ($url) {
            $this->setUrl($url);
        }
        if ($method) {
            $this->setMethod($method);
        }
        if (!$options) {
            return;
        }
        if (array_key_exists('headers', $options)) {
            $this->setHeaders($options['headers']);
        }
        if (array_key_exists('proxy', $options)) {
            $this->setProxy($options['proxy']);
        }
        if (array_key_exists('modified', $options)) {
            $this->setModified($options['modified']);
        }
        if (array_key_exists('onlyHeader', $options)) {
            $this->setOnlyHeader($options['onlyHeader']);
        }
        if (array_key_exists('noUrlencodePost', $options)) {
            $this->setNoUrlencodePost($options['noUrlencodePost']);
        }
    }

    // }}}
    // {{{ setProxy()

    /**
     * set proxy
     *
     * @param string $host
     * @param string $port
     * @return void
     */
    public function setProxy($host, $port)
    {
        $this->proxy['host'] = $host;
        $this->proxy['port'] = $port;
    }

    // }}}
    // {{{ setMethod()

    /**
     * set method
     *
     * @param string $method
     * @return void
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    // }}}
    // {{{ setUrl()

    /**
     * set url
     *
     * @param string $url
     * @return void
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    // }}}
    // {{{ setModified()

    /**
     * set modified
     *
     * @param string|int $modified
     * @return void
     */
    public function setModified($modified)
    {
        if (is_numeric($modified)) {
            $this->modified = http_date((int)$modified);
        } else {
            $this->modified = $modified;
        }
    }

    // }}}
    // {{{ setOnlyHeader()

    /**
     * set onlyHeader
     *
     * @param bool $onlyHeader
     * @return void
     */
    public function setOnlyHeader($onlyHeader)
    {
        $this->onlyHeader = $onlyHeader;
    }

    // }}}
    // {{{ setHeaders()

    /**
     * set noUrlencodePost
     *
     * @param bool $noUrlencodePost
     * @return void
     */
    public function setNoUrlencodePost($noUrlencodePost)
    {
        $this->noUrlencodePost = $noUrlencodePost;
    }

    // }}}
    // {{{ setHeaders()

    /**
     * set headers
     *
     * @param string $headers
     * @return void
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    // }}}
    // {{{ isSafeMethod()

    /**
     * is safe method?
     *
     * @return bool
     */
    public function isSafeMethod()
    {
        $method = strtoupper($this->method);
        // RFC2616 - Section 9
        if ($method == 'GET' || $method == 'HEAD'){
            return true;
        } else {
            return false;
        }
    }

    // }}}
}

// }}}
// {{{ WapResponse

/**
 * Response クラス
 */
class WapResponse
{
    // {{{ properties

    /**
     * リクエストの結果を示す数値
     *
     * @var int
     */
    public $code = false;

    /**
     * codeに対応する人間が読める短い文字列
     *
     * @var string
     */
    public $message = '';

    /**
     * 配列
     *
     * @var array
     */
    public $headers = array();

    /**
     * 内容。任意のデータの固まり
     *
     * @var string
     */
    public $content = null;

    // }}}
    // {{{ setContent()

    /**
     * set content
     *
     * @param string $content
     * @return void
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    // }}}
    // {{{ isSuccess()

    /**
     * is success?
     *
     * @return bool
     */
    public function isSuccess()
    {
        return in_array($this->code, array(200, 206, 304));
    }

    // }}}
    // {{{ isError()

    /**
     * is error ?
     *
     * @return bool
     */
    public function isError()
    {
        if (!$this->code) {
            return true;
        }
        return !$this->isSuccess();
    }

    // }}}
    // {{{ isRedirect()

    /**
     * is redirect?
     *
     * @return bool
     */
    public function isRedirect()
    {
        return in_array($this->code, array(301, 302));
    }

    // }}}
    // {{{ HTTP Status Codes (note)
/*
    000, 'Unknown Error',
    200, 'OK',
    201, 'CREATED',
    202, 'Accepted',
    203, 'Partial Information',
    204, 'No Response',
    206, 'Partial Content',
    301, 'Moved',
    302, 'Found',
    303, 'Method',
    304, 'Not Modified',
    400, 'Bad Request',
    401, 'Unauthorized',
    402, 'Payment Required',
    403, 'Forbidden',
    404, 'Not Found',
    500, 'Internal Error',
    501, 'Not Implemented',
    502, 'Bad Response',
    503, 'Too Busy',
    600, 'Bad Request in Client',
    601, 'Not Implemented in Client',
    602, 'Connection Failed',
    603, 'Timed Out',
*/
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
