<?php
require_once 'HTTP/Client.php';
require_once dirname(__FILE__) . '/P2DOM.php';
require_once dirname(__FILE__) . '/P2KeyValueStore/Serializing.php';

// {{{ P2Client

/**
 * p2.2ch.net クライアント
 *
 * プロバイダ規制時に書き込むために設計した。
 * モリタポを消費してdat取得権限の無いdat落ちスレッドの
 * 生datを取得できるようになったなら、即対応する。
 */
class P2Client
{
    // {{{ constants

    /**
     * Cookieを保存するSQLite3データベースのファイル名
     */
    const COOKIE_STORE_NAME = 'p2_2ch_net_cookie.sq3';

    /**
     * 公式P2のURIと各エントリポイント
     */
    const P2_ROOT_URI = 'http://p2.2ch.net/p2/';
    const SCRIPT_NAME_READ = 'read.php';
    const SCRIPT_NAME_POST = 'post.php';

    /**
     * User-Agent
     */
    const HTTP_USER_AGENT = 'Monazilla/1.0 (rep2-expack-p2client)';

    /**
     * フォームのパラメータ名
     */
    const FIELD_NAME_LOGIN_ID   = 'form_login_id';
    const FIELD_NAME_LOGIN_PASS = 'form_login_pass';
    const FIELD_NAME_POPUP      = 'popup';
    const FIELD_NAME_FROM       = 'FROM';
    const FIELD_NAME_MAIL       = 'mail';
    const FIELD_NAME_MESSAGE    = 'MESSAGE';
    const FIELD_NAME_BERES      = 'submit_beres';

    /**
     * 書き込み正否判定のための正規表現
     */
    const REGEX_SUCCESS = '{<title>.*(?:書き(?:込|こ)みました|書き込み終了 - SubAll BBS).*</title>}is';
    const REGEX_COOKIE  = '{<!-- 2ch_X:cookie -->|<title>■ 書き込み確認 ■</title>|>書き込み確認。<}';

    // }}}
    // {{{ properties

    /**
     * p2.2ch.net/モリタポ ログインID (メールアドレス)
     *
     * @var string
     */
    private $_loginId;

    /**
     * p2.2ch.net/モリタポ ログインパスワード
     *
     * @var string
     */
    private $_loginPass;

    /**
     * Cookieを保存するKey-Value Storeオブジェクト
     *
     * @var P2KeyValueStore_Serializing
     */
    private $_cookieStore;

    /**
     * Cookieを管理するオブジェクト
     *
     * @var HTTP_Client_CookieManager
     */
    private $_cookieManager;

    /**
     * HTTPクライアントオブジェクト
     *
     * @var HTTP_Client
     */
    private $_httpClient;

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     *
     * @param string $loginId
     * @param string $loginPass
     * @param string $cookieSaveDir
     * @throws P2Exception
     */
    public function __construct($loginId, $loginPass, $cookieSaveDir)
    {
        try {
            $cookieSavePath = $cookieSaveDir . DIRECTORY_SEPARATOR . self::COOKIE_STORE_NAME;
            $cookieStore = P2KeyValueStore::getStore($cookieSavePath, 'Serializing');
        } catch (Exception $e) {
            throw new P2Exception(get_class($e) . ': ' . $e->getMessage());
        }

        if ($cookieManager = $cookieStore->get($loginId)) {
            if (!$cookieManager instanceof HTTP_Client_CookieManager) {
                throw new Exception('Cannot restore the cookie manager.');
            }
        } else {
            $cookieManager = new HTTP_Client_CookieManager;
        }

        $this->_loginId = $loginId;
        $this->_loginPass = $loginPass;
        $this->_cookieStore = $cookieStore;
        $this->_cookieManager = $cookieManager;

        $defaultHeaders = array(
            'User-Agent' => self::HTTP_USER_AGENT,
        );
        $this->_httpClient = new HTTP_Client(null, $defaultHeaders, $cookieManager);
    }

    // }}}
    // {{{ destructor

    /**
     * データベースにCookieを保存する
     *
     * @param void
     */
    public function __destruct()
    {
        $this->_cookieStore->set($this->_loginId, $this->_cookieManager);
    }

    // }}}
    // {{{ login()

    /**
     * 公式p2にログインする
     *
     * @param string $uri
     * @param array $data
     * @param P2DOM $dom
     * @param DOMElement $form
     * @return array HTTPレスポンス
     * @throws P2Exception
     */
    public function login($uri = null, array $data = array(),
                          P2DOM $dom = null, DOMElement $form = null)
    {
        if ($uri === null) {
            $uri = self::P2_ROOT_URI;
        }

        if ($dom === null) {
            $response = $this->httpGet($uri);
            $dom = new P2DOM($response['body']);
        }

        if ($form === null) {
            $form = $this->getLoginForm($dom);
            if ($form === null) {
                throw new P2Exception('Login form not found.');
            }
        }

        $postData = array();
        foreach ($data as $name => $value) {
            $postData[$name] = rawurlencode($value);
        }
        $postData = $this->getFormValues($dom, $form, $postData);
        $postData[self::FIELD_NAME_LOGIN_ID] = rawurlencode($this->_loginId);
        $postData[self::FIELD_NAME_LOGIN_PASS] = rawurlencode($this->_loginPass);

        return $this->httpPost($uri, $postData, true);
    }

    // }}}
    // {{{ readThread()

    /**
     * スレッドを読む
     *
     * @param string $host
     * @param string $bbs
     * @param string $key
     * @param string|integer $ls
     * @param mixed &$response
     * @return string HTTPレスポンスボディ
     * @throws P2Exception
     */
    public function readThread($host, $bbs, $key, $ls = 1, &$response = null)
    {
        $getData = array(
            'host'  => (string)$host,
            'bbs'   => (string)$bbs,
            'key'   => (string)$key,
            'ls'    => (string)$ls,
        );

        $uri = self::P2_ROOT_URI . self::SCRIPT_NAME_READ;
        $response = $this->httpGet($uri, $getData);
        $dom = new P2DOM($response['body']);

        if ($form = $this->getLoginForm($dom)) {
            $response = $this->login($uri, $getData, $dom, $form);
            $dom = new P2DOM($response['body']);
            if ($this->getLoginForm($dom)) {
                throw new P2Exception('Login failed.');
            }
        }

        return $response['body'];
    }

    // }}}
    // {{{ post()

    /**
     * スレッドに書き込む
     *
     * csrfIdを取得し、かつ公式p2の既読を最新の状態にするため、
     * まず read.php を叩く。
     * 通信量を節約できるように ls=l1n としている。
     * popup=1 は書き込み後のページにリダイレクトさせないため。
     *
     * @param string $host
     * @param string $bbs
     * @param string $key
     * @param string $from
     * @param string $mail
     * @param string $message
     * @param bool $beRes
     * @param mixed &$response
     * @return bool
     * @throws P2Exception
     */
    public function post($host, $bbs, $key, $from, $mail, $message,
                         $beRes = false, &$response = null)
    {
        $dom = new P2DOM($this->readThread($host, $bbs, $key, 'l1n', $response));
        if ($form = $this->getPostForm($dom)) {
            $uri = self::P2_ROOT_URI . self::SCRIPT_NAME_POST;

            $postData = $this->getFormValues($dom, $form);
            $postData[self::FIELD_NAME_POPUP]   = '1';
            $postData[self::FIELD_NAME_FROM]    = rawurlencode($from);
            $postData[self::FIELD_NAME_MAIL]    = rawurlencode($mail);
            $postData[self::FIELD_NAME_MESSAGE] = rawurlencode($message);
            if ($beRes) {
                $postData[self::FIELD_NAME_BERES] = '1';
            } elseif (array_key_exists(self::FIELD_NAME_BERES, $postData)) {
                unset($postData[self::FIELD_NAME_BERES]);
            }

            $response = $this->httpPost($uri, $postData, true);

            if (preg_match(self::REGEX_COOKIE, $response['body'])) {
                $dom = new P2DOM($response['body']);
                $expression = './/form[contains(@action, "' . self::SCRIPT_NAME_POST . '")]';
                $result = $dom->query($expression);
                if ($result instanceof DOMNodeList && $result->length > 0) {
                    $postData = $this->getFormValues($dom, $result->item(0));
                    $response = $this->httpPost($uri, $postData, true);
                }
            }

            return (bool)preg_match(self::REGEX_SUCCESS, $response['body']);
        } else {
            throw new P2Exception('Post form not found.');
        }
    }

    // }}}
    // {{{ httpGet()

    /**
     * HTTP_Client::get() のラッパーメソッド
     *
     * @param string $uri
     * @param mixed $data
     * @param bool $preEncoded
     * @param array $headers
     * @return array HTTPレスポンス
     * @throws P2Exception
     */
    protected function httpGet($uri, $data = null, $preEncoded = false,
                               $headers = array())
    {
        $code = $this->_httpClient->get($uri, $data, $preEncoded, $headers);
        P2Exception::pearErrorToP2Exception($code);
        if ($code < 200 || $code >= 300) {
            throw new P2Exception('HTTP Error: '. $code);
        }
        return $this->_httpClient->currentResponse();
    }

    // }}}
    // {{{ httpPost()

    /**
     * HTTP_Client::post() のラッパーメソッド
     *
     * @param string $uri
     * @param mixed $data
     * @param bool $preEncoded
     * @param array $files
     * @param array $headers
     * @return array HTTPレスポンス
     * @throws P2Exception
     */
    protected function httpPost($uri, $data, $preEncoded = false,
                                $files = array(), $headers = array())
    {
        $code = $this->_httpClient->post($uri, $data, $preEncoded, $files, $headers);
        P2Exception::pearErrorToP2Exception($code);
        if ($code < 200 || $code >= 300) {
            throw new P2Exception('HTTP Error: '. $code);
        }
        return $this->_httpClient->currentResponse();
    }

    // }}}
    // {{{ getLoginForm()

    /**
     * ログインフォームを抽出する
     *
     * @paramP2DOM $dom
     * @return DOMElement|null
     */
    protected function getLoginForm(P2DOM $dom)
    {
        $result = $dom->query('.//form[@action and @id="login"]');
        if ($result instanceof DOMNodeList && $result->length > 0) {
            return $result->item(0);
        }
        return null;
    }

    // }}}
    // {{{ getPostForm()

    /**
     * read.php/post_form.php の出力から書き込みフォームを抽出する
     *
     * @paramP2DOM $dom
     * @return DOMElement|null
     */
    protected function getPostForm(P2DOM $dom)
    {
        $result = $dom->query('.//form[@action and @id="resform"]');
        if ($result instanceof DOMNodeList && $result->length > 0) {
            return $result->item(0);
        }
        return null;
    }

    // }}}
    // {{{ getFormValues()

    /**
     * フォームからinput要素を抽出し、連想配列を生成する
     *
     * select要素とtextarea要素は無視する。
     * また、<input type="checkbox" name="foo[]" value="bar"> のように
     * name属性で配列を指示しているものは正しく扱えない。
     * (このクラス自体がそういった要素を扱う必要のある場合を考慮していない)
     *
     * @param P2DOM $dom
     * @param DOMElement $form
     * @param array $data
     * @return array
     */
    protected function getFormValues(P2DOM $dom, DOMElement $form,
                                     array $data = array())
    {
        $fields = $dom->query('.//input[@name and @value]', $form);
        foreach ($fields as $field) {
            $name = $field->getAttribute('name');
            $value = $field->getAttribute('value');
            $value = rawurlencode(mb_convert_encoding($value, 'SJIS-win', 'UTF-8'));
            $data[$name] = $value;
        }

        return $data;
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
