<?php
require_once P2_LIB_DIR . '/FileCtl.php';

// {{{ class BbsMap

/**
 * BbsMapクラス
 *
 * 板-ホストの対応表を作成し、それに基づいてホストの同期を行う
 */
class BbsMap
{
    // {{{ getCurrentHost()

    /**
     * 最新のホストを取得する
     *
     * @param   string  $host       ホスト名
     * @param   string  $bbs        板名
     * @param   bool    $autosync   移転を検出したときに自動で同期するか否か
     * @return  string  板に対応する最新のホスト。見つからなければ入力したホストをそのまま返す
     * @access  public
     * @static
     */
    function getCurrentHost($host, $bbs, $autosync = true)
    {
        static $synced = false;

        $map = BbsMap::_getMapping();
        if (!$map) {
            return $host;
        }
        $type = BbsMap::_detectHostType($host);

        // チェック
        if (isset($map[$type]) && isset($map[$type][$bbs])) {
            $new_host = $map[$type][$bbs]['host'];
            if ($host != $new_host && $autosync && !$synced) {
                // 移転を検出したらお気に板、お気にスレ、最近読んだスレを自動で同期
                $msg_fmt = '<p>p2 info: ホストの移転を検出しました。(%s/%s → %s/%s)<br>';
                $msg_fmt .= 'お気に板、お気にスレ、最近読んだスレを自動で同期します。</p>';
                $msg = sprintf($msg_fmt, $host, $bbs, $new_host, $bbs);
                P2Util::pushInfoHtml($msg);
                BbsMap::syncFav();
                $synced = true;
            }
            $host = $new_host;
        }

        return $host;
    }

    // }}}

    /**
     * 2chの板名からホスト名を取得する
     *
     * @static
     * @access  public
     * @param   string  $bbs    板名
     * @return  string|false
     */
    function get2chHostByBbs($bbs)
    {
        if (!$map = BbsMap::_getMapping()) {
            return false;
        }
        if (isset($map['2channel'])) {
            foreach ($map['2channel'] as $mapped_bbs => $v) {
                if ($mapped_bbs == $bbs) {
                    return $v['host'];
                }
            }
        }
        return false;
    }
    
    // {{{ getBbsName()

    /**
     * 板名LONGを取得する
     *
     * @param   string  $host   ホスト名
     * @param   string  $bbs    板名
     * @return  string  板メニューに記載されている板名
     * @access  public
     * @static
     */
    function getBbsName($host, $bbs)
    {
        $map = BbsMap::_getMapping();
        if (!$map) {
            return $bbs;
        }
        $type = BbsMap::_detectHostType($host);

        // チェック
        if (isset($map[$type]) && isset($map[$type][$bbs])) {
            $itaj = $map[$type][$bbs]['itaj'];
        } else {
            $itaj = $bbs;
        }

        return $itaj;
    }

    // }}}
    // {{{ syncBrd()

    /**
     * お気に板などのbrdファイルを同期する
     *
     * @static
     * @access  public
     * @param   string  $brd_path   brdファイルのパス
     * @param   boolean $noMsg      結果メッセージのpushを抑制するならtrue
     * @return  void
     */
    function syncBrd($brd_path, $noMsg = false)
    {
        global $_conf;
        static $done = array();

        // {{{ 読込

        if (isset($done[$brd_path])) {
            return;
        }
        $lines = BbsMap::_readData($brd_path);
        if (!$lines) {
            return;
        }
        $map = BbsMap::_getMapping();
        if (!$map) {
            return;
        }
        $neolines = array();
        $updated = false;

        // }}}
        // {{{ 同期

        foreach ($lines as $line) {
            $setitaj = false;
            $data = explode("\t", rtrim($line, "\n"));
            $hoge = $data[0]; // 予備?
            $host = $data[1];
            $bbs  = $data[2];
            $itaj = $data[3];
            $type = BbsMap::_detectHostType($host);

            if (isset($map[$type]) && isset($map[$type][$bbs])) {
                $newhost = $map[$type][$bbs]['host'];
                if ($itaj === '') {
                    $itaj = $map[$type][$bbs]['itaj'];
                    if ($itaj != $bbs) {
                        $setitaj = true;
                    } else {
                        $itaj = '';
                    }
                }
            } else {
                $newhost = $host;
            }

            if ($host != $newhost || $setitaj) {
                $neolines[] = "{$hoge}\t{$newhost}\t{$bbs}\t{$itaj}\n";
                $updated = true;
            } else {
                $neolines[] = $line;
            }
        }

        // }}}
        // {{{ 書込

        $name = basename($brd_path);
        $name_hs = htmlspecialchars($name, ENT_QUOTES);
        if ($updated) {
            BbsMap::_writeData($brd_path, $neolines);
            !$noMsg and P2Util::pushInfoHtml(sprintf('<p>p2 info: %s を同期しました。</p>', $name_hs));
        } else {
            !$noMsg and P2Util::pushInfoHtml(sprintf('<p>p2 info: %s は変更されませんでした。</p>', $name_hs));
        }
        $done[$brd_path] = true;

        // }}}
    }

    // }}}
    // {{{ syncIdx()

    /**
     * お気にスレなどのidxファイルを同期する
     *
     * @static
     * @access  public
     * @param   string  $idx_path   idxファイルのパス
     * @param   boolean $noMsg      結果メッセージのpushを抑制するならtrue
     * @return  void
     */
    function syncIdx($idx_path, $noMsg = false)
    {
        global $_conf;
        static $done = array();

        // {{{ 読込

        if (isset($done[$idx_path])) {
            return;
        }
        $lines = BbsMap::_readData($idx_path);
        if (!$lines) {
            return;
        }
        $map = BbsMap::_getMapping();
        if (!$map) {
            return;
        }
        $neolines = array();
        $updated = false;

        // }}}
        // {{{ 同期

        foreach ($lines as $line) {
            $data = explode('<>', rtrim($line, "\n"));
            $host = $data[10];
            $bbs  = $data[11];
            $type = BbsMap::_detectHostType($host);

            if (isset($map[$type]) && isset($map[$type][$bbs])) {
                $newhost = $map[$type][$bbs]['host'];
            } else {
                $newhost = $host;
            }

            if ($host != $newhost) {
                $data[10] = $newhost;
                $neolines[] = implode('<>', $data) . "\n";
                $updated = true;
            } else {
                $neolines[] = $line;
            }
        }

        // }}}
        // {{{ 書込

        $name = basename($idx_path);
        $name_hs = htmlspecialchars($name, ENT_QUOTES);
        if ($updated) {
            BbsMap::_writeData($idx_path, $neolines);
            !$noMsg and P2Util::pushInfoHtml(sprintf('<p>p2 info: %s を同期しました。</p>', $name_hs));
        } else {
            !$noMsg and P2Util::pushInfoHtml(sprintf('<p>p2 info: %s は変更されませんでした。</p>', $name_hs));
        }
        $done[$idx_path] = true;

        // }}}
    }

    // }}}
    // {{{ syncFav()

    /**
     * お気に板、お気にスレ、最近読んだスレを同期する
     *
     * @return  void
     * @access  public
     * @static
     */
    function syncFav()
    {
        global $_conf;
        
        $noMsg = $_conf['ktai'] ? true : false;
        
        BbsMap::syncBrd($_conf['favita_path'], $noMsg);
        BbsMap::syncIdx($_conf['favlist_file'], $noMsg);
        BbsMap::syncIdx($_conf['rct_file'], $noMsg);
    }

    // }}}
    // {{{ _getMapping()

    /**
     * 2ch公式メニューをパースし、板-ホストの対応表を作成する
     *
     * @return  array   site/bbs/(host,itaj) の多次元連想配列
     *                  ダウンロードに失敗したときは false
     * @access  private
     * @static
     */
    function _getMapping()
    {
        global $_conf;
        static $map = null;

        // {{{ 設定

        $bbsmenu_url = 'http://menu.2ch.net/bbsmenu.html';
        $map_cache_path = $_conf['cache_dir'] . '/host_bbs_map.txt';
        $map_cache_lifetime = 600; // TTLは少し短めに
        $errfmt = '<p>rep2 error: BbsMap: %s - %s をダウンロードできませんでした。</p>';

        // }}}
        // {{{ キャッシュ確認

        if (!is_null($map)) {
            return $map;
        } elseif (file_exists($map_cache_path)) {
            $mtime = filemtime($map_cache_path);
            $expires = $mtime + $map_cache_lifetime;
            if (time() < $expires) {
                $map_cahce = file_get_contents($map_cache_path);
                $map = unserialize($map_cahce);
                return $map;
            }
        } else {
            FileCtl::mkdirFor($map_cache_path);
        }
        touch($map_cache_path);
        clearstatcache();

        // }}}
        // {{{ メニューをダウンロード

        $params = array();
        $params['timeout'] = $_conf['fsockopen_time_limit'];
        //$params['readTimeout'] = array($_conf['fsockopen_time_limit'], 0);
        if (isset($mtime)) {
            $params['requestHeaders'] = array('If-Modified-Since' => gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
        }
        if ($_conf['proxy_use']) {
            $params['proxy_host'] = $_conf['proxy_host'];
            $params['proxy_port'] = $_conf['proxy_port'];
        }
        $req = new HTTP_Request($bbsmenu_url, $params);
        $req->setMethod('GET');
        $err = $req->sendRequest(true);

        // エラーを検証
        if (PEAR::isError($err)) {
            $errmsg = sprintf($errfmt, htmlspecialchars($err->getMessage()), htmlspecialchars($bbsmenu_url, ENT_QUOTES));
            P2Util::pushInfoHtml($errmsg);
            if (file_exists($map_cache_path)) {
                return unserialize(file_get_contents($map_cache_path));
            } else {
                return false;
            }
        }

        // レスポンスコードを検証
        $code = $req->getResponseCode();
        if ($code == 304) {
            $map_cahce = file_get_contents($map_cache_path);
            $map = unserialize($map_cahce);
            return $map;
        } elseif ($code != 200) {
            $errmsg = sprintf($errfmt, htmlspecialchars(strval($code)), htmlspecialchars($bbsmenu_url, ENT_QUOTES));
            P2Util::pushInfoHtml($errmsg);
            if (file_exists($map_cache_path)) {
                return unserialize(file_get_contents($map_cache_path));
            } else {
                return false;
            }
        }

        $res_body = $req->getResponseBody();

        // }}}
        // {{{ パース

        $regex = '!<A HREF=http://(\w+\.(?:2ch\.net|bbspink\.com|machi\.to|mathibbs\.com))/(\w+)/(?: TARGET=_blank)?>(.+?)</A>!';
        preg_match_all($regex, $res_body, $matches, PREG_SET_ORDER);

        $map = array();
        foreach ($matches as $match) {
            $host = $match[1];
            $bbs  = $match[2];
            $itaj = $match[3];
            $type = BbsMap::_detectHostType($host);
            
            !isset($map[$type]) and $map[$type] = array();
            $map[$type][$bbs] = array('host' => $host, 'itaj' => $itaj);
        }

        // }}}
        
        // キャッシュする
        $map_cache = serialize($map);
        if (false === FileCtl::filePutRename($map_cache_path, $map_cache)) {
            $errmsg = sprintf('p2 error: cannot write file. (%s)', htmlspecialchars($map_cache_path, ENT_QUOTES));
            P2Util::pushInfoHtml($errmsg);
            
            if (file_exists($map_cache_path)) {
                return unserialize(file_get_contents($map_cache_path));
            } else {
                return false;
            }
        }

        return $map;
    }

    // }}}
    // {{{ _readData()

    /**
     * 更新前のデータを読み込む
     *
     * @param   string  $path   読み込むファイルのパス
     * @return  array   ファイルの内容、読み出しに失敗したときは false
     * @access  private
     * @static
     */
    function _readData($path)
    {
        if (!file_exists($path)) {
            return false;
        }

        $lines = file($path);
        if (!$lines) {
            return false;
        }

        return $lines;
    }

    // }}}
    // {{{ _writeData()

    /**
     * 更新後のデータを書き込む
     *
     * @param   string  $path   書き込むファイルのパス
     * @param   array   $neolines   書き込むデータの配列
     * @return  void
     * @access  private
     * @static
     */
    function _writeData($path, $neolines)
    {
        if (is_array($neolines) && count($neolines) > 0) {
            $cont = implode('', $neolines);
        /*} elseif (is_scalar($neolines)) {
            $cont = strval($neolines);*/
        } else {
            $cont = '';
        }
        if (false === FileCtl::filePutRename($path, $cont)) {
            $errmsg = sprintf('Error: cannot write file. (%s)', htmlspecialchars($path, ENT_QUOTES));
            die($errmsg);
        }
    }

    // }}}
    // {{{ _detectHostType()

    /**
     * ホストの種類を判定する
     *
     * @param   string  $host   ホスト名
     * @return  string  ホストの種類
     * @access  private
     * @static
     */
    function _detectHostType($host)
    {
        if (P2Util::isHostBbsPink($host)) {
            $type = 'bbspink';
        } elseif (P2Util::isHost2chs($host)) {
            $type = '2channel';
        } elseif (P2Util::isHostMachiBbs($host)) {
            $type = 'machibbs';
        } elseif (P2Util::isHostJbbsShitaraba($host)) {
            $type = 'jbbs';
        } else {
            $type = $host;
        }
        return $type;
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
