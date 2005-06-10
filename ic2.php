<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

/* ImageCache2 - 画像のダウンロード・サムネイル作成 */

// {{{ p2基本設定読み込み&認証

require_once 'conf/conf.php';

authorize();

if ($_exconf['imgCache']['*'] == 0) {
    exit('<html><body><p>ImageCache2は無効です。<br>conf/conf_user_ex.phpの設定を変えてください。</p></body></html>');
}

// }}}
// {{{ 初期化

$debug = FALSE;

// ライブラリ読み込み
require_once 'PEAR.php';
require_once 'DB/DataObject.php';
require_once 'HTTP/Client.php';
if ($debug) { require_once 'Var_Dump.php'; }
require_once (P2EX_LIBRARY_DIR . '/ic2/findexec.inc.php');
require_once (P2EX_LIBRARY_DIR . '/ic2/loadconfig.inc.php');
require_once (P2EX_LIBRARY_DIR . '/ic2/database.class.php');
require_once (P2EX_LIBRARY_DIR . '/ic2/db_images.class.php');
require_once (P2EX_LIBRARY_DIR . '/ic2/thumbnail.class.php');


// }}}
// {{{ config


$mimemap = array('image/jpeg' => '.jpg', 'image/png' => '.png', 'image/gif' => '.gif');

// 設定ファイル読み込み
$ini = ic2_loadconfig();

// DB_DataObjectの設定
$_dbdo_options = &PEAR::getStaticProperty('DB_DataObject','options');
$_dbdo_options = array('database' => $ini['General']['dsn'], 'debug' => FALSE, 'quote_identifiers' => TRUE);


// }}}
// {{{ prepare


// パラメータを設定
$uri = isset($_REQUEST['uri']) ? $_REQUEST['uri'] : (isset($_REQUEST['url']) ? $_REQUEST['url'] : NULL);
$force    = !empty($_REQUEST['f']); // 強制更新
$thumb    = isset($_REQUEST['t']) ? intval($_REQUEST['t']) : 0; // サムネイルタイプ
$redirect = isset($_REQUEST['r']) ? intval($_REQUEST['r']) : 0; // 表示方法
$rank     = isset($_REQUEST['rank']) ? intval($_REQUEST['rank']) : 0; // ランキング
$memo = (isset($_REQUEST['memo']) && strlen($_REQUEST['memo']) > 0) ? $_REQUEST['memo'] : NULL; // メモ

/*if (!isset($uri) && FALSE !== ($url = getenv('PATH_INFO'))) {
    $uri = 'http:/' . $url;
}*/
if (empty($uri)) {
    ic2_error('x06', '不正なURLです。', FALSE);
}
$uri = preg_replace('|^(https?://)ime\\.nu/|', '$1', $uri);
$pURL = @parse_url($uri);
if (!$pURL || !preg_match('/^(https?)$/', $pURL['scheme']) || empty($pURL['host']) || empty($pURL['path'])) {
    ic2_error('x06', '不正なURLです。', FALSE);
}

// 強制あぼーんホストのとき
if ($ini['Getter']['reject']) {
    $pattern = preg_quote($ini['Getter']['reject']);
    $pattern = str_replace(array(',', '/'), array('|', '\\/'), $pattern);
    $pattern = '/' . $pattern . '/i';
    if (preg_match($pattern, $pURL['host'])) {
        ic2_error('x01', "あぼーん対象ホストです。");
    }
}

// 値の調整
if ($thumb < 1 || $thumb > 3 ) { $thumb = 0; }
if ($rank < -1) { $rank = -1; } elseif ($rank > 5) { $rank = 5; }
if ($memo === '') { $memo = NULL; }

$thumbnailer = &new ThumbNailer($thumb);


// }}}
// {{{ sleep


// 同じ画像のURIに対するクエリが（ほぼ）同時に発行されたときの重複GETを防ぐ
// sleepした時間はプロセスの実行時間に含まれないので独自にタイマーを用意する（無限ループ回避）
$tmpchecker = $ini['General']['cachedir'] . '/q_' . md5($uri);
if (file_exists($tmpchecker)) {
    $offtimer = ini_get('max_execution_time');
    if ($offtimer == 0) {
        $offtimer = 30;
    }
    while (file_exists($tmpchecker)) {
        sleep(1); // 1秒停止
        $offtimer--;
        if ($offtimer < 0) {
            ic2_error(504);
        }
    }
}
// テンポラリファイルを作成、終了時に自動削除
touch($tmpchecker);
// exitしたときはregister_shutdown_function()が効かないようなので
// ic2_display(),ic2_error()各関数の先頭でic2_removeTmpFile()をコールすることにした。
// スマートとは言いがたいが期待通りの動作はしてくれるのでよしとする。
//register_shutdown_function('ic2_removeTmpFile');


// }}}
// {{{ search


// 画像がキャッシュされているか確認
$search = &new IC2DB_Images;
$retry = FALSE;
if ($memo !== NULL) {
    $memo = $search->uniform($memo, 'SJIS-win');
}
if ($search->get($uri)) {
    // ウィルススキャンにひっかかったファイルだったら終了。
    if (!$force && $search->mime == 'clamscan/infected') {
        ic2_error('x04', '', FALSE);
    }
    // あぼーんフラグ（rankが負）が立っていたら終了。
    if (!$force && $search->rank < 0 ) {
        ic2_error('x01', '', FALSE);
    }
    $filepath = $thumbnailer->srcPath($search->size, $search->md5, $search->mime);
    $params = array('uri' => $search->uri, 'name' => $search->name, 'size' => $search->size,
                    'md5' => $search->md5, 'width' => $search->width, 'height' => $search->height,
                    'mime' => $search->mime, 'memo' => $search->memo);

    // 自動メモ機能が有効のとき
    if ($ini['General']['automemo'] && !is_null($memo) && !strstr($search->memo, $memo)) {
        if (!empty($search->memo)) {
            $memo .= ' ' . $search->memo;
        }
        $update = &new IC2DB_Images;
        $update->memo = $memo;
        $update->whereAddQuoted('uri', '=', $uri);
        $update->update();
    }

    // ファイルが保存されていればそれでよし、保存されていなければレコードを削除する。
    if (file_exists($filepath)) {
        if ($force) {
            $_size = $search->size;
            $_md5  = $search->md5;
            $_mime = $search->mime;
            $time  = $search->time;
        } else {
            ic2_finish($filepath, $thumb, $params, FALSE);
        }
    } else {
        $retry = TRUE;
        $force = FALSE;
        $_size = $search->size;
        $_md5  = $search->md5;
        $_mime = $search->mime;
    }
} else {
    $filepath = '';
}

// 画像がブラックリストにあるか確認
require_once (P2EX_LIBRARY_DIR . '/ic2/db_blacklist.class.php');
$blacklist = &new IC2DB_BlackList;
if ($blacklist->get($uri)) {
    switch ($blacklist->type) {
        case 0:
            $errcode = 'x05'; // お腹いっぱい
            break;
        case 1:
            $errcode = 'x01'; // あぼーん
            break;
        case 2:
            $errcode = 'x04'; // ウィルス感染
            break;
        default:
            $errcode = 'x06'; // ???
    }
    ic2_error($errcode, '', FALSE);
}


// 画像がエラーログにあるか確認
if (!$force && $ini['Getter']['checkerror']) {
    require_once (P2EX_LIBRARY_DIR . '/ic2/db_errors.class.php');
    $errlog = &new IC2DB_Errors;
    if ($errlog->get($uri)) {
        ic2_error($errlog->errcode, '', FALSE);
    }
}


// }}}
// {{{ init http-client


// キャッシュされていなければ、取得を試みる
$client = &new HTTP_Client;
$client->setRequestParameter('timeout', 60);
$client->setMaxRedirects(3);
$ic2_ua = empty($expack_ua) ? $_SERVER['HTTP_USER_AGENT'] : $expack_ua;
$client->setDefaultHeader('User-Agent', $ic2_ua);
if ($force && $time) {
    $client->setDefaultHeader('If-Modified-Since', gmdate('D, d M Y H:i:s \G\M\T', $time));
}

// プロキシ設定
if ($ini['Proxy']['enabled'] && $ini['Proxy']['host'] && $ini['Proxy']['port']) {
    $client->setRequestParameter('proxy_host', $ini['Proxy']['host']);
    $client->setRequestParameter('proxy_port', $ini['Proxy']['port']);
    if ($ini['Proxy']['user']) {
        $client->setRequestParameter('proxy_user', $ini['Proxy']['user']);
        $client->setRequestParameter('proxy_pass', $ini['Proxy']['pass']);
        $proxy_auth_data = base64_encode($ini['Proxy']['user'] . ':' . $ini['Proxy']['pass']);
        $client->setDefaultHeader('Proxy-Authorization', 'Basic ' . $proxy_auth_data);
    }
}

// リファラ設定
$send_referer = (boolean)$ini['Getter']['sendreferer'];
if ($send_referer) {
    if ($ini['Getter']['norefhosts']) {
        $pattern = preg_quote($ini['Getter']['norefhosts'], '/');
        $pattern = str_replace(',', '|', $pattern);
        $pattern = '/' . $pattern . '/i';
        if (preg_match($pattern, $pURL['host'])) {
            $send_referer = FALSE;
        }
    }
} elseif ($ini['Getter']['refhosts']) {
    $pattern = preg_quote($ini['Getter']['refhosts'], '/');
    $pattern = str_replace(',', '|', $pattern);
    $pattern = '/' . $pattern . '/i';
    if (preg_match($pattern, $pURL['host'])) {
        $send_referer = TRUE;
    }
}

if ($send_referer) {
    $referer = $uri . '.html';
    $client->setDefaultHeader('Referer', $referer);
}


// }}}
// {{{ head


// まずはHEADでチェック
$client_h = clone($client);
$code = $client_h->head($uri);
if (PEAR::isError($code)) {
    ic2_error('x02', $code->getMessage());
}
$head = &$client_h->currentResponse();

// 304 Not Modified のとき
if ($filepath && $force && $time && $code == 304) {
    ic2_finish($filepath, $thumb, $params, FALSE);
}

// 200以外のときは失敗とみなす
if ($code != 200) {
    ic2_error($code);
}

// Content-Type検証
if (isset($head['headers']['Content-Type'])) {
    $conent_type = $head['headers']['Content-Type'];
    if (!preg_match('{^image/}', $conent_type) && $conent_type != 'application/x-shockwave-flash') {
        ic2_error('x02', "サポートされていないファイルタイプです。({$conent_type})");
    }
}

// Content-Length検証
if (isset($head['headers']['Content-Length'])) {
    $conent_length = (int)$head['headers']['Content-Length'];
    $maxsize = $ini['Source']['maxsize'];
    if (preg_match('/(\d+\.?\d*)([KMG])/i', $maxsize, $m)) {
        $maxsize = (float)$m[1];
        switch (strtoupper($m[2])) {
            case 'G': $maxsize = $maxsize * 1024;   // breakしないのがミソ
            case 'M': $maxsize = $maxsize * 1024;
            case 'K': $maxsize = $maxsize * 1024;
        }
    }
    $maxsize = (int)$maxsize;
    if (0 < $maxsize && $maxsize < $conent_length) {
        ic2_error('x03', "ファイルサイズが大きすぎます。(file:{$conent_length}; max:{$maxsize};)");
    }
}

unset($client_h, $code, $head);


// }}}
// {{{ get


// ダウンロード
$code = $client->get($uri);
if (PEAR::isError($code)) {
    ic2_error('x02', $code->getMessage());
} elseif ($code != 200) {
    ic2_error($code);
}

$response = &$client->currentResponse();

// 一時ファイルに保存
$tmpfile = tempnam($ini['General']['cachedir'], 'tmp_');
$fp = @fopen($tmpfile, 'wb');
if (!$fp) {
    ic2_error('x02', "fopen失敗。($tmpfile)");
}
fwrite($fp, $response['body']);
fclose($fp);


// }}}
// {{{ check


// ウィルススキャン
if ($ini['Getter']['virusscan']) {
    $searchpath = $thumbnailer->ini['Getter']['clamav'];
    if ($ini['Getter']['virusscan'] == 2) {
        $clamscan = 'clamdscan';
    } else {
        $clamscan = 'clamscan';
    }
    if (findexec($clamscan, $searchpath)) {
        if ($searchpath) {
            $clamscan = $searchpath . DIRECTORY_SEPARATOR . $clamscan;
        }
        $scan_command = $clamscan . ' --stdout ' . escapeshellarg(realpath($tmpfile));
        $scan_result  = @exec($scan_command, $scan_stdout, $scan_result);
        if ($scan_result == 1) {
            $params = array(
                'uri'    => $uri,
                'host'   => $pURL['host'],
                'name'   => basename($pURL['path']),
                'size'   => filesize($tmpfile),
                'md5'    => md5_file($tmpfile),
                'width'  => 0,
                'height' => 0,
                'mime' => 'clamscan/infected',
                'memo' => $memo
            );
            ic2_aborn($params, TRUE);
            @unlink($tmpfile);
            ic2_error('x04', 'ウィルスを発見しました。');
        }
    }
}

// 画像情報を調べる。MIMEタイプはサーバが送ってきたものを信頼しない。
$info = @getimagesize($tmpfile);
if (!$info) {
    ic2_error('x02', '画像サイズの取得に失敗しました。');
} elseif (!isset($info['mime'])) {
    // < PHP4.3.0
    ic2_error('x02', 'MIMEタイプの取得に失敗しました。');
} else {
    $mime = $info['mime'];
}
if (!in_array($mime, array_keys($mimemap))) {
    ic2_error('x02', "サポートされていないファイルタイプです。({$mime})");
}

// 正規の画像なら、ファイルサイズとMD5チェックサムを計算
$host = $pURL['host'];
$name = basename($pURL['path']);
$size = filesize($tmpfile);
$md5  = md5_file($tmpfile);
$width  = $info[0];
$height = $info[1];

// 強制更新を試みたものの、更新されていなかったとき（レスポンスコードは200）
if ($filepath && $force && $time && $size == $_size && $md5 == $_md5 && $mime == $_mime) {
    ic2_finish($filepath, $thumb, $params, FALSE);
}

$params = array('uri' => $uri, 'host' => $host, 'name' => $name, 'size' => $size, 'md5' => $md5,
                'width' => $width, 'height' => $height, 'mime' => $mime, 'memo' => $memo);

// ファイルサイズが上限を越えていないか確認
ic2_checkSizeOvered($tmpfile, $params);

// 同じ画像があぼーんされているか確認
$rank = ic2_checkAbornedFile($tmpfile, $params);


// }}}
// {{{ finish


// すべてのチェックをパスしたなら、保存用の名前にリネームする
$newfile = $thumbnailer->srcPath($size, $md5, $mime);
$newdir = dirname($newfile);
if (!is_dir($newdir) && !@mkdir($newdir)) {
    ic2_error('x02', "ディレクトリを作成できませんでした。({$newdir})");
}
if (($force || !file_exists($newfile)) && !@rename($tmpfile, $newfile)) {
    ic2_error('x02', "リネーム失敗。({$tmpfile} → {$newfile})");
}
@chmod($newfile, 0644);

// データベースにファイル情報を記録する
$record = &new IC2DB_Images;
if ($retry && $size == $_size && $md5 == $_md5 && $mime == $_mime) {
    $record->time = time();
    if ($ini['General']['automemo'] && !is_null($memo)) {
        $record->memo = $memo;
    }
    $record->whereAddQuoted('uri',  '=', $uri);
    $record->whereAddQuoted('size', '=', $size);
    $record->whereAddQuoted('md5',  '=', $md5);
    $record->whereAddQuoted('mime', '=', $mime);
    $record->update();
} else {
    $record->uri = $uri;
    $record->host = $host;
    $record->name = $name;
    $record->size = $size;
    $record->md5 = $md5;
    $record->width = $width;
    $record->height = $height;
    $record->mime = $mime;
    $record->time = time();
    $record->rank = $rank;
    if ($ini['General']['automemo'] && !is_null($memo)) {
        $record->memo = $memo;
    }
    $record->insert();
}

// 画像を表示
ic2_finish($newfile, $thumb, $params, $force);


// }}}
// {{{ 関数


function ic2_aborn($params, $infected = FALSE)
{
    global $ini;
    extract($params);

    $aborn = &new IC2DB_Images;
    $aborn->uri = $uri;
    $aborn->host = $host;
    $aborn->name = $name;
    $aborn->size = $size;
    $aborn->md5 = $md5;
    $aborn->width = $width;
    $aborn->height = $height;
    $aborn->mime = $mime;
    $aborn->time = time();
    $aborn->rank = $infected ? -4 : -1;
    if ($ini['General']['automemo'] && !is_null($memo)) {
        $aborn->memo = $memo;
    }
    return $aborn->insert();
}

function ic2_checkAbornedFile($tmpfile, $params)
{
    global $ini;
    extract($params);

    // ブラックリスト検索
    $bl_check = &new IC2DB_BlackList;
    $bl_check->whereAddQuoted('size', '=', $size);
    $bl_check->whereAddQuoted('md5',  '=', $md5);
    if ($bl_check->find(TRUE)) {
        $bl_add = clone($bl_check);
        $bl_add->uri = $uri;
        $bl_add->insert();
        switch ($bl_check->type) {
            case 0:
                $errcode = 'x05'; // No More
            case 1:
                $errcode = 'x01'; // Aborn
            case 2:
                $errcode = 'x04'; // Virus
            default:
                $errcode = 'x06'; // Unknown
        }
        // 厳密には、その可能性が限りなく高いだけで100%ではない
        ic2_error($errcode, 'ブラックリストにある画像と同じ内容です。', FALSE);
    }

    // あぼーん画像検索
    $check = &new IC2DB_Images;
    $check->whereAddQuoted('size', '=', $size);
    $check->whereAddQuoted('md5',  '=', $md5);
    //$check->whereAddQuoted('mime', '=', $mime); // SizeとMD5で十分
    // 同じのが異なるURLで複数登録されていて、ランクが違う可能性があるので
    // （普通に使う分には起こらない...と思う。少なくとも起こりにくいはず）
    $check->orderByArray(array('rank' => 'ASC'));
    if ($check->find(TRUE)) {
        if ($check->rank < 0) {
            @unlink($tmpfile);
            ic2_aborn($params);
            // 現状では（たぶんずっと） -1 or -4 だけだが、一応
            if ($check->rank >= -5) {
                $errcode = 'x0' . abs($check->rank);
            } else {
                $errcode = 'x06'; // Unknown
            }
            // 厳密には、以下同文
            if ($check->rank == -4) {
                $errmsg = 'ウィルスに感染していた画像と同じ内容です。';
            } else {
                $errmsg = '既にあぼーんされている画像と同じ内容です。';
            }
            ic2_error($errcode, $errmsg);
        } else {
            return $check->rank;
        }
    }

    return 0;
}

function ic2_checkSizeOvered($tmpfile, $params)
{
    global $ini;
    extract($params);

    $isError = FALSE;

    $maxsize = $ini['Source']['maxsize'];
    if (preg_match('/(\d+\.?\d*)([KMG])/i', $maxsize, $m)) {
        $maxsize = (float)$m[1];
        switch (strtoupper($m[2])) {
            case 'G': $maxsize = $maxsize * 1024;   // breakしないのがミソ
            case 'M': $maxsize = $maxsize * 1024;
            case 'K': $maxsize = $maxsize * 1024;
        }
    }
    $maxsize = (int)$maxsize;
    if (0 < $maxsize && $maxsize < $conent_length) {
        $isError = TRUE;
        $errmsg = "ファイルサイズが大きすぎます。(file:{$size}; max:{$maxsize};)";
    }

    $maxwidth = (int)$ini['Source']['maxwidth'] ;
    $maxheight = (int)$ini['Source']['maxheight'];
    if ((0 < $maxwidth && $maxwidth < $width) ||
        (0 < $maxheight && $maxheight < $height)
    ) {
        $isError = TRUE;
        $errmsg = "画像サイズが大きすぎます。(file:{$width}x{$height}; max:{$maxwidth}x{$maxheight};)";
    }

    if ($isError) {
        @unlink($tmpfile);
        ic2_aborn($params);
        ic2_error('x03', $errmsg);
    }

    return TRUE;
}

function ic2_display($path)
{
    global $redirect;

    ic2_removeTmpFile();

    $ext = strrchr($path, '.');
    $name = basename($path);

    switch ($redirect) {
        case 1:
            header("Location: {$path}");
            exit;
        case 2:
            switch ($ext) {
                case '.jpg': header("Content-Type: image/jpeg; name=\"{$name}\""); break;
                case '.png': header("Content-Type: image/png; name=\"{$name}\""); break;
                case '.gif': header("Content-Type: image/gif; name=\"{$name}\""); break;
                default:
                    if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE') ||
                        strstr($_SERVER['HTTP_USER_AGENT'], 'Opera')
                    ) {
                        header("Content-Type: application/octetstream; name=\"{$name}\"");
                    } else {
                        header("Content-Type: application/octet-stream; name=\"{$name}\"");
                    }
            }
            header("Content-Disposition: inline; filename=\"{$name}\"");
            readfile($path);
            exit;
        default:
            global $_conf, $ini, $thumb, $uri;
            require_once 'HTML/Template/Flexy.php';
            require_once 'HTML/QuickForm.php';
            require_once 'HTML/QuickForm/Renderer/ObjectFlexy.php';

            // conf.phpで一括stripslashes()しているけど、HTML_QuickFormでも独自にstripslashes()するので。
            // これぞバッドノウハウ
            if (get_magic_quotes_gpc()) {
                array_walk_recursive($_REQUEST, 'addslashes_cb');
            }

            // QuickFormの初期化
            $_constants = array(
                't' => $thumb, 
                'u'   => $uri,
                's'  => '作成',
            );
            $_defaults = array(
                'q' => $ini["Thumb{$thumb}"]['quality'],
                'r'  => '0',
            );
            $qa = 'size=3 maxlength=3';
            if (strstr($_SERVER['HTTP_USER_AGENT'], 'DoCoMo') || strstr($_SERVER['HTTP_USER_AGENT'], 'DDIPocket')) {
                $qa .= ' istyle=4';
            } elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'UP.Browser') || strstr($_SERVER['HTTP_USER_AGENT'], 'KDDI')) {
                $qa .= ' format=*N';
            } elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'J-PHONE') || strstr($_SERVER['HTTP_USER_AGENT'], 'Vodafone')) {
                $qa .= ' mode=numeric';
            }
            $qf = &new HTML_QuickForm('imgmaker', 'get', 'ic2_mkthumb.php');
            $qf->setConstants($_constants);
            $qf->setDefaults($_defaults);
            $qf->addElement('hidden', 'u');
            $qf->addElement('hidden', 't');
            $qf->addElement('text', 'q', '品質', $qa);
            $qf->addElement('select', 'r', '回転', array('0' => 'なし', '90' => '右に90°', '270' => '左に90°', '180' => '180°'));
            $qf->addElement('checkbox', 'p', 'トリム');
            $qf->addElement('submit', 's');

            // FlexyとQurickForm_Rendererの初期化
            $_flexy_options = array(
                'locale' => 'ja',
                'compileDir' => $ini['General']['cachedir'] . '/' . $ini['General']['compiledir'],
                'templateDir' => P2EX_LIBRARY_DIR . '/ic2/templates',
                'numberFormat' => '', // ",0,'.',','" と等価
            );
            $flexy = &new HTML_Template_Flexy($_flexy_options);
            $rdr = &new HTML_QuickForm_Renderer_ObjectFlexy($flexy);
            $qf->accept($rdr);

            // 表示
            $flexy->setData('title', 'キャッシュ完了');
            if (!$_conf['ktai']) {
                $flexy->setData('pc', TRUE);
                $flexy->setData('skin', $GLOBALS['skin_name']);
                //$flexy->setData('stylesheets', array('css'));
                //$flexy->setData('javascripts', array('js'));
            } else {
                $flexy->setData('pc', FALSE);
                $flexy->setData('k_color', $GLOBALS['_exconf']['ubiq']);
            }
            if ($thumb == 2) {
                if ($ini['General']['inline'] == 1) {
                    $t = 2; $link = NULL;
                } else {
                    $t = 1; $link = $path;
                }
                $preview = $_SERVER['PHP_SELF'] . '?o=1&r=1&t=' . $t . '&uri=' . rawurlencode($uri);
                $flexy->setData('preview', $preview);
                $flexy->setData('link', $link);
                $flexy->setData('info', NULL);
            } else {
                $flexy->setData('preview', NULL);
                $flexy->setData('link', $path);
                $flexy->setData('info', NULL);
            }
            if (isset($_SERVER['HTTP_REFERER'])) {
                $flexy->setData('referer', $_SERVER['HTTP_REFERER']);
            } else {
                $flexy->setData('referer', NULL);
            }
            $flexy->setData('edit', extension_loaded('gd'));
            $flexy->setData('form', $rdr->toObject());
            $flexy->compile('preview.tpl.html');
            $flexy->output();
    }
    exit;
}

function ic2_error($code, $optmsg = '', $write_log = TRUE)
{
    global $uri, $redirect;

    ic2_removeTmpFile();

    $map = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        'x01' => 'IC2 - Aborned Image',
        'x02' => 'IC2 - Broken (or Not) Image',
        'x03' => 'IC2 - Too Large',
        'x04' => 'IC2 - Virus Infected',
        'x05' => 'IC2 - No More',
        'x06' => 'IC2 - ???',
    );

    $message = $code . ' ' . $map[$code];
    if ($optmsg) {
        $message .= '<br />' . $optmsg;
    }

    if ($write_log) {
        require_once (P2EX_LIBRARY_DIR . '/ic2/db_errors.class.php');
        $logger = &new IC2DB_Errors;
        $logger->uri     = $uri;
        $logger->errcode = $code;
        $logger->errmsg  = mb_convert_encoding($message, 'UTF-8', 'SJIS-win');
        $logger->occured = time();
        $logger->insert();
        $logger->ic2_errlog_lotate();
    }

    /*if (isset($map[$code]) && 100 <= $code && $code <= 505) {
        header("HTTP/1.0 {$code} {$map[$code]}");
    }*/

    if ($redirect) {
        $path = './img/' . strval($code) . '.png';
        $name = 'filename="' . strval($code) . '.png"';
        header('Content-Type: image/png; ' . $name);
        header('Content-Disposition: inline; ' . $name);
        readfile($path);
        exit;
    }
    echo <<<EOF
<html>
<head><title>ImageCache::Error</title></head>
<body>
<p>{$message}</p>
</body>
</html>
EOF;
    exit;
}

function ic2_finish($filepath, $thumb, $params, $force)
{
    global $thumbnailer;

    extract($params);

    if ($thumb == 0) {
        ic2_display($filepath);
    } else {
        $thumbpath = $thumbnailer->convert($size, $md5, $mime, $width, $height, $force);
        if (PEAR::isError($thumbpath)) {
            ic2_error('x02', $thumbpath->getMessage());
        }
        ic2_display($thumbpath);
    }
}


function ic2_removeTmpFile()
{
    global $debug, $tmpfile, $tmpchecker;

    if ($debug) {
        file_exists($tmpfile) && rename($tmpfile, $tmpfile.'.'.time());
        file_exists($tmpchecker) && rename($tmpchecker, $tmpchecker.'.'.time());
    } else {
        file_exists($tmpfile) && unlink($tmpfile);
        file_exists($tmpchecker) && unlink($tmpchecker);
    }
}


// }}}


?>