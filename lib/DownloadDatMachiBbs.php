<?php
/**
 * rep2expack - まちBBS用datダウンロードクラス
 */

// {{{ DownloadDatMachiBbs

/**
 * まちBBSのofflaw.cgiから生datを取得する
 *
 * @link http://www.machi.to/offlaw.txt
 */
class DownloadDatMachiBbs implements DownloadDatInterface
{
    // {{{ invoke()

    /**
     * スレッドのdatをダウンロードし、保存する
     *
     * @param ThreadRead $thread
     * @return bool
     */
    static public function invoke(ThreadRead $thread)
    {
        global $_conf;

        // {{{ 既得datの取得レス数が適正かどうかを念のためチェック

        if (file_exists($thread->keydat)) {
            $dls = FileCtl::file_read_lines($thread->keydat);
            if (!$dls || count($dls) != $thread->gotnum) {
                // echo 'bad size!<br>';
                unlink($thread->keydat);
                $thread->gotnum = 0;
            }
        } else {
            $thread->gotnum = 0;
        }

        // }}}
        // {{{ offlaw.cgiからdatをダウンロード

        $host = $thread->host;
        $bbs = $thread->bbs;
        $key = $thread->key;

        if ($thread->gotnum == 0) {
            $option = '';
            $append = false;
        } else {
            $option = sprintf('%d-', $thread->gotnum + 1);
            $append = true;
        }

        // http://[SERVER]/bbs/offlaw.cgi/[BBS]/[KEY]/[OPTION];
        $url = "http://{$host}/bbs/offlaw.cgi/{$bbs}/{$key}/{$option}";

        $tempfile = $thread->keydat . '.tmp';
        FileCtl::mkdirFor($tempfile);
        if ($append) {
            touch($tempfile, filemtime($thread->keydat));
        } elseif (file_exists($tempfile)) {
            unlink($tempfile);
        }
        $response = P2Util::fileDownload($url, $tempfile);

        if ($response->isError()) {
            if (304 != $response->code) {
                $thread->diedat = true;
            }
            return false;
        }

        // }}}
        // {{{ ダウンロードした各行をチェックしつつローカルdatに書き込み

        $lines = file($tempfile);
        unlink($tempfile);

        if ($append) {
            $fp = fopen($thread->keydat, 'ab');
        } else {
            $fp = fopen($thread->keydat, 'wb');
        }
        if (!$fp) {
            p2die("cannot write file. ({$thread->keydat})");
        }
        flock($fp, LOCK_EX);

        foreach ($lines as $i => $line) {
            // 取得済みレス数をインクリメント
            $thread->gotnum++;
            // 行を分解、要素数チェック (厳密には === 6)
            $lar = explode('<>', rtrim($line));
            if (count($lar) >= 5) {
                // レス番号は保存しないので取り出す
                $resnum = (int)array_shift($lar);
                // レス番号と取得済みレス数が異なっていたらあぼーん扱い
                while ($thread->gotnum < $resnum) {
                    $abn = "あぼーん<>あぼーん<>あぼーん<>あぼーん<>";
                    if ($thread->gotnum == 1) {
                        $abn .= $lar[4]; // スレタイトル
                    }
                    $abn .= "\n";
                    fwrite($fp, $abn);
                    $thread->gotnum++;
                }
                // 行を書き込む
                fwrite($fp, implode('<>', $lar) . "\n");
            } else {
                $thread->gotnum--;
                $lineno = $i + 1;
                P2Util::pushInfoHtml("<p>rep2 info: dat書式エラー: line {$lineno} of {$url}.</p>");
                break;
            }
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        // }}}

        $thread->isonline = true;

        return true;
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
