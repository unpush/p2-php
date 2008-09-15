<?php
/**
 * rep2expack feat. pecl_http
 */

require_once P2_LIB_DIR . '/filectl.class.php';
require_once P2_LIB_DIR . '/p2util.class.php';

// {{{ CONSTANTS

define('P2HTTPEXT_DEBUG', 0);

// }}}
// {{{ P2HttpCallback

/**
 * コールバック用インターフェース
 */
interface P2HttpCallback
{
    /**
     * コールバックメソッド
     *
     * @param P2HttpGet $req
     * @return void
     */
    public function execute(P2HttpGet $req);
}

// }}}
// {{{ P2HttpCallback_SaveEucjpAsSjis

/**
 * EUC-JPのレスポンスボディをShift_JISに変換してファイルに保存する
 */
class P2HttpCallback_SaveEucjpAsSjis implements P2HttpCallback
{
    // {{{ execute()

    /**
     * EUC-JPのレスポンスボディをShift_JISに変換してファイルに保存する
     *
     * CP51932のサポートはPHP 5.2.1から
     *
     * @param P2HttpGet $req
     * @return void
     */
    public function execute(P2HttpGet $req)
    {
        $save_path = $req->getSavePath();
        file_put_contents($save_path,
                          mb_convert_encoding($req->getResponseBody(), 'CP932', 'CP51932'),
                          LOCK_EX
                          );
        chmod($save_path, $req->getSavePermission());
    }

    // }}}
}

// }}}
// {{{ P2HttpCallback_SaveUtf8AsSjis

/**
 * UTF-8のレスポンスボディをShift_JISに変換してファイルに保存する
 */
class P2HttpCallback_SaveUtf8AsSjis implements P2HttpCallback
{
    // {{{ execute()

    /**
     * UTF-8のレスポンスボディをShift_JISに変換してファイルに保存する
     *
     * @param P2HttpGet $req
     * @return void
     */
    public function execute(P2HttpGet $req)
    {
        $save_path = $req->getSavePath();
        file_put_contents($save_path,
                          mb_convert_encoding($req->getResponseBody(), 'CP932', 'UTF-8'),
                          LOCK_EX
                          );
        chmod($save_path, $req->getSavePermission());
    }

    // }}}
}

// }}}
// {{{ P2HttpGet

/**
 * HTTP GET
 */
class P2HttpGet extends HttpRequest
{
    // {{{ constants

    /**
     * エラーコード：デバッグ
     */
    const E_DEBUG = -1;

    /**
     * エラーコード：エラーなし
     */
    const E_NONE = 0;

    /**
     * エラーコード：HTTPエラー
     */
    const E_HTTP = 1;

    /**
     * エラーコード：接続失敗
     */
    const E_CONNECTION = 2;

    /**
     * エラーコード：例外発生
     */
    const E_EXCEPTION = 3;

    // }}}
    // {{{ properties

    /**
     * ダウンロードしたデータを保存するパス
     *
     * @var string
     */
    private $_savePath;

    /**
     * ダウンロードしたデータを保存する際のパーミッション
     *
     * @var int
     */
    private $_savePerm;

    /**
     * エラーコード
     *
     * @var int
     */
    private $_errorCode;

    /**
     * エラー情報
     *
     * @var string
     */
    private $_errorInfo;

    /**
     * レスポンスコードが200の時のコールバックオブジェクト
     *
     * @var P2HttpCallback
     */
    private $_onSuccess;

    /**
     * レスポンスコードが200以外の時のコールバックオブジェクト
     *
     * @var P2HttpCallback
     */
    private $_onFailure;

    /**
     * 次に実行するリクエスト
     *
     * @var P2HttpGet
     */
    private $_next;

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     *
     * @param string $url
     * @param string $save_path
     * @param array $options
     * @param P2HttpCallback $on_success
     * @param P2HttpCallback $on_failure
     */
    public function __construct($url,
                                $save_path,
                                array $options = null,
                                P2HttpCallback $on_success = null,
                                P2HttpCallback $on_failure = null
                                )
    {
        global $_conf;

        if ($options === null) {
            $options = array();
        }

        if (!isset($options['connecttimeout'])) {
            $options['connecttimeout'] = $_conf['fsockopen_time_limit'];
        }

        if (!isset($options['timeout'])) {
            $options['timeout'] = $_conf['fsockopen_time_limit'] * 2;
        }

        if (!isset($options['compress'])) {
            $options['compress'] = true;
        }

        if (!isset($options['useragent'])) {
            $options['useragent'] = sprintf('Monazilla/1.00 (%s/%s; expack-%s)',
                                            $_conf['p2name'],
                                            $_conf['p2version'],
                                            $_conf['p2expack']
                                            );
        }

        if ($_conf['proxy_use'] && !isset($options['proxyhost']) && !empty($_conf['proxy_host'])) {
            $options['proxyhost'] = $_conf['proxy_host'];
            if (!empty($_conf['proxy_port']) && is_numeric($_conf['proxy_port'])) {
                $options['proxyport'] = (int)$_conf['proxy_port'];
            } elseif (strpos($_conf['proxy_host'], ':') === false) {
                $options['proxyport'] = 80;
            }
            /*
            $options['proxytype'] = HTTP_PROXY_HTTP;
            if (isset($_conf['proxy_type'])) {
                switch ($_conf['proxy_type']) {
                case 'http':   $options['proxytype'] = HTTP_PROXY_HTTP;   break;
                case 'socks4': $options['proxytype'] = HTTP_PROXY_SOCKS4; break;
                case 'socks5': $options['proxytype'] = HTTP_PROXY_SOCKS5; break;
                default:
                    if (is_numeric($options['proxytype'])) {
                        $options['proxytype'] = (int)$_conf['proxy_type'];
                    }
                }
            }

            if (!empty($_conf['proxy_auth'])) {
                $options['proxy_auth'] = $_conf['proxy_auth'];
                $options['proxyauthtype'] = HTTP_AUTH_BASIC;
                if (isset($_conf['proxy_auth_type'])) {
                    switch ($_conf['proxy_auth_type']) {
                    case 'basic':  $options['proxyauthtype'] = HTTP_AUTH_BASIC;  break;
                    case 'digest': $options['proxyauthtype'] = HTTP_AUTH_DIGEST; break;
                    case 'ntlm':   $options['proxyauthtype'] = HTTP_AUTH_NTLM;   break;
                    case 'gssneg': $options['proxyauthtype'] = HTTP_AUTH_GSSNEG; break;
                    case 'any':    $options['proxyauthtype'] = HTTP_AUTH_ANY;    break;
                    default:
                        if (is_numeric($options['proxytype'])) {
                            $options['proxyauthtype'] = (int)$_conf['proxy_auth_type'];
                        }
                    }
                }
            }
            */
        }

        if (!isset($options['lastmodified']) && file_exists($save_path)) {
            $options['lastmodified'] = filemtime($save_path);
        } else {
            FileCtl::mkdir_for($save_path);
        }

        $this->_savePath = $save_path;
        $this->_savePerm = !empty($_conf['dl_perm']) ? $_conf['dl_perm'] : 0606;
        $this->_errorCode = self::E_NONE;
        $this->_errorInfo = '';
        $this->_onSuccess = $on_success;
        $this->_onFailure = $on_failure;
        $this->_next = null;

        parent::__construct($url, HttpRequest::METH_GET, $options);
    }

    // }}}
    // {{{ __toString()

    /**
     * オブジェクトの文字列表記を取得する
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s: %s => %s', get_class($this), $this->getUrl(), $this->_savePath);
    }

    // }}}
    // {{{ send()

    /**
     * リクエストを送信する
     *
     * @return HttpMessage
     */
    public function send()
    {
        try {
            return parent::send();
        } catch (HttpException $e) {
            if ($this->getResponseCode() == 0) {
                $this->onFinish(false);
            } else {
                $this->setError(sprintf('%s (%d) %s',
                                        get_class($e),
                                        $e->getCode(),
                                        htmlspecialchars($e->getMessage(), ENT_QUOTES)
                                        ),
                                self::E_EXCEPTION
                                );
            }
            return false;
        }
    }

    // }}}
    // {{{ onFinish()

    /**
     * リクエスト終了時に自動で呼び出されるコールバックメソッド
     *
     * @param bool $success
     * @return void
     */
    public function onFinish($success)
    {
        if ($success) {
            if (($code = $this->getResponseCode()) == 200) {
                if ($this->_onSuccess) {
                    $this->_onSuccess->execute($this);
                } else {
                    file_put_contents($this->_savePath, $this->getResponseBody(), LOCK_EX);
                    chmod($this->_savePath, $this->_savePerm);
                }
            } else {
                if ($this->_onFailure) {
                    $this->_onFailure->execute($this);
                } elseif ($code == 304) {
                    //touch($this->_savePath);
                } else {
                    $this->setError(sprintf('HTTP %d %s', $code, $this->getResponseStatus()),
                                    self::E_HTTP
                                    );
                }
            }
            if (P2HTTPEXT_DEBUG && !$this->hasError()) {
                $this->setError(sprintf('HTTP %d %s', $code, $this->getResponseStatus()),
                                self::E_DEBUG
                                );
            }
        } else {
            $this->setError('HTTP Connection Error!', self::E_CONNECTION);
            $this->setNext(null);
        }
    }

    // }}}
    // {{{ getSavePath()

    /**
     * ダウンロードしたデータを保存する際のパスを取得する
     *
     * @return string
     */
    public function getSavePath()
    {
        return $this->_savePath;
    }

    // }}}
    // {{{ getSavePermission()

    /**
     * ダウンロードしたデータを保存する際のパーミッションを取得する
     *
     * @return int
     */
    public function getSavePermission()
    {
        return $this->_savePerm;
    }

    // }}}
    // {{{ setSavePath()

    /**
     * ダウンロードしたデータを保存する際のパスを設定する
     *
     * @param string $path
     * @return void
     */
    public function setSavePath($path)
    {
        $this->_savePath = $path;
    }

    // }}}
    // {{{ setSavePermission()

    /**
     * ダウンロードしたデータを保存する際のパーミッションを設定する
     *
     * @param int $perm
     * @return void
     */
    public function setSavePermission($perm)
    {
        $this->_savePerm = $perm;
    }

    // }}}
    // {{{ getErrorCode()

    /**
     * エラーコードを取得する
     *
     * @return int
     */
    public function getErrorCode()
    {
        return $this->_errorCode;
    }

    // }}}
    // {{{ getErrorInfo()

    /**
     * エラー情報を取得する
     *
     * @return string
     */
    public function getErrorInfo()
    {
        return $this->_errorInfo;
    }

    // }}}
    // {{{ setError()

    /**
     * エラー情報を設定する
     *
     * @param string $info
     * @param int $code
     * @return void
     */
    public function setError($info, $code)
    {
        $this->_errorCode = $code;
        $this->_errorInfo = $info;
    }

    // }}}
    // {{{ hasError()

    /**
     * エラーの有無をチェックする
     *
     * @return bool
     */
    public function hasError()
    {
        return ($this->_errorCode != self::E_NONE);
    }

    // }}}
    // {{{ getNext()

    /**
     * 次のリクエストを取得する
     *
     * @return P2HttpGet
     */
    public function getNext()
    {
        return $this->_next;
    }

    // }}}
    // {{{ setNext()

    /**
     * 次のリクエストを設定する
     *
     * @param P2HttpGet $next
     * @return void
     */
    public function setNext(P2HttpGet $next = null)
    {
        $this->_next = $next;
    }

    // }}}
    // {{{ hasNext()

    /**
     * 次のリクエストの有無をチェックする
     *
     * @return bool
     */
    public function hasNext()
    {
        return !is_null($this->_next);
    }

    // }}}
    // {{{ fetch()

    /**
     * 静的呼び出し用メソッド
     *
     * @param string $url
     * @param string $save_path
     * @param array $options
     * @param P2HttpCallback $on_success
     * @param P2HttpCallback $on_failure
     * @return array(P2HttpGet, HttpMessage)
     */
    static public function fetch($url,
                                 $save_path,
                                 array $options = null,
                                 P2HttpCallback $on_success = null,
                                 P2HttpCallback $on_failure = null
                                 )
    {
        $req = new P2HttpGet($url, $save_path, $options, $on_success, $on_failure);
        $res = $req->send();
        return array($req, $res);
    }

    // }}}
}

// }}}
// {{{ P2HttpRequestQueue

/**
 * HttpRequest用のキュー
 */
class P2HttpRequestQueue implements Iterator, Countable
{
    // {{{ properties

    /**
     * HttpRequestの配列
     *
     * @var array
     */
    protected $_queue;

    /**
     * 現在の要素
     *
     * @var HttpRequest
     */
    private $_current;

    /**
     * 現在のキー
     *
     * @var int
     */
    private $_key;

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     *
     * @param HttpRequest ...
     */
    public function __construct()
    {
        $this->_queue = array();

        $argc = func_num_args();
        if ($argc > 0) {
            $argv = func_get_args();
            foreach ($argv as $req) {
                $this->push($req);
            }
        }
    }

    // }}}
    // {{{ push()

    /**
     * キューにHttpRequestを追加する
     *
     * @param HttpRequest $req
     * @return void
     */
    public function push(HttpRequest $req)
    {
        $this->_queue[] = $req;
    }

    // }}}
    // {{{ pop()

    /**
     * キューからHttpRequestを取り出す
     *
     * @return HttpRequest|null
     */
    public function pop()
    {
        return array_shift($this->_queue);
    }

    // }}}
    // {{{ count()

    /**
     * キューに登録されているHttpRequestの数を取得する
     * (Countable)
     *
     * @return int
     */
    public function count()
    {
        return count($this->_queue);
    }

    // }}}
    // {{{ current()

    /**
     * 現在の要素を取得する
     * (Iterator)
     *
     * @return HttpRequest
     */
    public function current()
    {
        return $this->_current;
    }

    // }}}
    // {{{ key()

    /**
     * 現在のキーを取得する
     * (Iterator)
     *
     * @return int
     */
    public function key()
    {
        return $this->_key;
    }

    // }}}
    // {{{ next()

    /**
     * イテレータを前方に移動する
     * (Iterator)
     *
     * @return void
     */
    public function next()
    {
        $this->_current = next($this->_queue);
        $this->_key = key($this->_queue);
    }

    // }}}
    // {{{ rewind()

    /**
     * イテレータを巻き戻す
     * (Iterator)
     *
     * @return void
     */
    public function rewind()
    {
        $this->_current = reset($this->_queue);
        $this->_key = key($this->_queue);
    }

    // }}}
    // {{{ valid()

    /**
     * 現在の要素が有効かどうかをチェックする
     * (Iterator)
     *
     * @return bool
     */
    public function valid()
    {
        return ($this->_current !== false);
    }

    // }}}
}

// }}}
// {{{ P2HttpRequestStack

/**
 * HttpRequest用のスタック
 */
class P2HttpRequestStack extends P2HttpRequestQueue
{
    // {{{ push()

    /**
     * スタックにHttpRequestを追加する
     *
     * @param HttpRequest $req
     * @return void
     */
    public function push(HttpRequest $req)
    {
        array_unshift($this->_queue, $req);
    }

    // }}}
}

// }}}
// {{{ P2HttpRequestPool

/**
 * HttpRequestPoolを使った並列ダウンロードクラス
 *
 * @static
 */
class P2HttpRequestPool
{
    // {{{ constants

    /**
     * 並列に実行するリクエスト数の上限
     */
    const MAX_REQUESTS = 10;

    /**
     * 同一ホストに対して並列に実行するリクエスト数の上限
     */
    const MAX_REQUESTS_PER_HOST = 2;

    // }}}
    // {{{ send()

    /**
     * プールにアタッチされているリクエストを送信する
     *
     * @param HttpRequestPool $pool
     * @param P2HttpRequestQueue $queue
     * @return void
     */
    static public function send(HttpRequestPool $pool, P2HttpRequestQueue $queue = null)
    {
        $err = '';

        try {
            // キューからプールに追加
            if ($queue && ($c = count($pool)) < self::MAX_REQUESTS) {
                while ($c < self::MAX_REQUESTS && ($req = $queue->pop())) {
                    $pool->attach($req);
                    $c++;
                }
            }

            // リクエストを送信
            while ($c = count($pool)) {
                $pool->send();

                // 終了したリクエストの処理
                foreach ($pool->getFinishedRequests() as $req) {
                    $pool->detach($req);
                    $c--;

                    if ($req instanceof P2HttpGet) {
                        if ($req->hasError()) {
                            $err .= sprintf('<li><em>%s</em>: %s</li>',
                                            htmlspecialchars($req->getUrl(), ENT_QUOTES),
                                            htmlspecialchars($req->getErrorInfo(), ENT_QUOTES)
                                            );
                        }

                        if ($req->hasNext()) {
                            $pool->attach($req->getNext());
                            $c++;
                        }
                    }
                }

                // キューからプールに追加
                if ($queue) {
                    while ($c < self::MAX_REQUESTS && ($req = $queue->pop())) {
                        $pool->attach($req);
                        $c++;
                    }
                }
            }
        } catch (HttpException $e) {
            $err .= sprintf('<li>%s (%d) %s</li>',
                            get_class($e),
                            $e->getCode(),
                            htmlspecialchars($e->getMessage(), ENT_QUOTES)
                            );
        }

        if ($err !== '') {
            $GLOBALS['_info_msg_ht'] .= "<ul class=\"errors\">{$err}</ul>\n";
        }
    }

    // }}}
    // {{{ fetchSubjectTxt()

    /**
     * subject.txtを一括ダウンロード&保存する
     *
     * @param array|string $subjects
     * @param bool $force
     * @return void
     */
    static public function fetchSubjectTxt($subjects, $force = false)
    {
        global $_conf;

        // {{{ ダウンロード対象を設定

        // お気に板等の.idx形式のファイルをパース
        if (is_string($subjects)) {
            $lines = FileCtl::file_read_lines($subjects, FILE_IGNORE_NEW_LINES);
            if (!$lines) {
                return;
            }

            $subjects = array();

            foreach ($lines as $l) {
                $la = explode('<>', $l);
                if (count($la) < 12) {
                    continue;
                }

                $host = $la[10];
                $bbs = $la[11];
                if ($host === '' || $bbs === '') {
                    continue;
                }

                $id = $host . '<>' . $bbs;
                if (isset($subjects[$id])) {
                    continue;
                }

                $subjects[$id] = array($host, $bbs);
            }

        // [host, bbs] の連想配列を検証
        } elseif (is_array($subjects)) {
            $originals = $subjects;
            $subjects = array();

            foreach ($originals as $s) {
                if (!is_array($s) || !isset($s['host']) || !isset($s['bbs'])) {
                    continue;
                }

                $id = $s['host'] . '<>' . $s['bbs'];
                if (isset($subjects[$id])) {
                    continue;
                }

                $subjects[$id] = array($s['host'], $s['bbs']);
            }

        // 上記以外
        } else {
            return;
        }

        if (!count($subjects)) {
            return;
        }

        // }}}
        // {{{ キューをセットアップ

        // キューおよびその他の変数を初期化
        $queue = new P2HttpRequestQueue;
        $hosts = array();
        $time = time() - $_conf['sb_dl_interval'];
        $eucjp2sjis = null;

        // 各subject.txtへのリクエストをキューに追加
        foreach ($subjects as $subject) {
            list($host, $bbs) = $subject;

            $file = P2Util::datDirOfHostBbs($host, $bbs) . 'subject.txt';
            if (!$force && file_exists($file) && filemtime($file) > $time) {
                continue;
            }

            $url = 'http://' . $host . '/' . $bbs . '/subject.txt';

            if (P2Util::isHostJbbsShitaraba($host) || P2Util::isHostBe2chNet($host)) {
                if ($eucjp2sjis === null) {
                    $eucjp2sjis = new P2HttpCallback_SaveEucjpAsSjis;
                }
                $req = new P2HttpGet($url, $file, null, $eucjp2sjis);
            } else {
                $req = new P2HttpGet($url, $file);
            }

            // 同一ホストに対しての同時接続は MAX_REQUESTS_PER_HOST まで
            if (!isset($hosts[$host])) {
                $hosts[$host] = new P2HttpRequestQueue;
                $queue->push($req);
            } elseif (count($hosts[$host]) < self::MAX_REQUESTS_PER_HOST) {
                $queue->push($req);
            } else {
                $hosts[$host]->pop()->setNext($req);
            }
            $hosts[$host]->push($req);
        }

        // }}}

        // リクエストを送信
        if (count($queue)) {
            self::send(new HttpRequestPool, $queue);
            clearstatcache();
        }
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
