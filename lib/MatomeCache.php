<?php

// {{{ MatomeCache

/**
 * まとめ読みキャッシュデータクラス
 */
class MatomeCache
{
    // {{{ properties

    /**
     * まとめ読みの内容 (HTML)
     *
     * @var string
     */
    private $_content;

    /**
     * まとめ読みのメタデータ
     *
     * @var array
     */
    private $_metaData;

    /**
     * まとめ読みを圧縮保存するファイル名
     *
     * @var string
     */
    private $_tempFile;

    /**
     * まとめ読みを圧縮保存するストリーム
     *
     * @var stream
     */
    private $_stream;

    /**
     * まとめ読みキャッシュを残す数
     *
     * @var int
     */
    private $_maxNumEntries;

    /**
     * まとめ読みキャッシュが有効かどうか
     *
     * @var bool
     */
    private $_enabled;

    // }}}
    // {{{ __construct()

    /**
     * コンストラクタ
     *
     * プロパティを初期化し、一時ファイルを作成する。
     *
     * @param string $title
     * @param int $maxNumEntries
     */
    public function __construct($title, $maxNumEntries = -1)
    {
        global $_conf;

        if ($maxNumEntries == 0) {
            $this->_enabled = false;
            return;
        }

        // プロパティの初期化
        $this->_content = '';
        $this->_metaData = array(
            'time' => time(),
            'title' => $title,
            'threads' => array(),
            'size' => 0,
        );
        $this->_tempFile = null;
        $this->_stream = null;
        $this->_maxNumEntries = $maxNumEntries;
        $this->_enabled = true;

        // 一時ファイルを作成する
        /*
         * PHPで tmpnam() 関数が呼ばれると、C言語レベルでは
         *  PHP_FUNCTION(tempnam) -> php_open_temporary_fd() ->
         *  php_do_open_temporary_file() -> virtual_file_ex()
         * という流れで一時ファイル用ディレクトリの解決が行われる。
         * この際、virtual_file_ex() の use_realpath 引数に
         * CWD_REALPATH が指定されているので tempnam() の結果は
         * realpath() にかける必要がない。
        */
        $tempFile = tempnam($_conf['tmp_dir'], 'matome_');
        if (!$tempFile) {
            return;
        }

        // 一時ファイルを開き、ストリームフィルタを付加する
        $fp = fopen($tempFile, 'wb');
        if ($fp) {
            stream_filter_append($fp, 'zlib.deflate');
            stream_filter_append($fp, 'convert.base64-encode');
            $this->_tempFile = $tempFile;
            $this->_stream = $fp;
        } else {
            unlink($tempfile);
        }
    }

    // }}}
    // {{{ __destruct()

    /**
     * デストラクタ
     *
     * 内容を保存し、古いキャッシュを削除する。
     * スレッド情報が空の場合は保存しない。
     *
     * @param void
     */
    public function __destruct()
    {
        if (!$this->_enabled) {
            return;
        }

        // ストリームを閉じる
        if ($this->_stream) {
            fclose($this->_stream);
        }

        // レスがあるなら
        if (count($this->_metaData['threads'])) {
            // 内容を保存する
            if ($this->_tempFile) {
                MatomeCacheList::add($this->_tempFile, $this->_metaData, true);
            } else {
                MatomeCacheList::add($this->_content, $this->_metaData, false);
            }
            // 古いキャッシュを削除する。
            if ($this->_maxNumEntries > 0) {
                MatomeCacheList::trim($this->_maxNumEntries);
            }
        }

        // 一時ファイルを削除する
        if ($this->_tempFile) {
            unlink($this->_tempFile);
        }
    }

    // }}}
    // {{{ concat()

    /**
     * 内容を追加する
     *
     * @param string $content
     * @return void
     */
    public function concat($content)
    {
        if ($this->_enabled) {
            if ($this->_stream) {
                fwrite($this->_stream, $content);
            } else {
                $this->_content .= $content;
            }
            $this->_metaData['size'] += strlen($content);
        }
    }

    // }}}
    // {{{ addReadThread()

    /**
     * まとめ読みに含まれるスレッド情報を追加する
     *
     * @param ThreadRead $aThread
     * @return void
     */
    public function addReadThread(ThreadRead $aThread)
    {
        if ($this->_enabled) {
            $this->_metaData['threads'][] = array(
                'title' => $aThread->ttitle_hd,
                'host'=> $aThread->host,
                'bbs'=> $aThread->bbs,
                'key'=> $aThread->key,
                'ls' => sprintf('%d-%dn',
                                $aThread->resrange['start'],
                                $aThread->resrange['to']),
            );
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
