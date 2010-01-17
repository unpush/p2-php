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
     * 内容を初期化し、キーを取得する。
     *
     * @param string $title
     * @param int $maxNumEntries
     */
    public function __construct($title, $maxNumEntries = -1)
    {
        $this->_content = '';
        $this->_metaData = array(
            'time' => time(),
            'title' => $title,
            'threads' => array(),
            'size' => null,
        );
        $this->_maxNumEntries = $maxNumEntries;
        if ($maxNumEntries == 0) {
            $this->_enabled = false;
        } else {
            $this->_enabled = true;
        }
    }

    // }}}
    // {{{ __destruct()

    /**
     * デストラクタ
     *
     * 内容を保存し、古いキャッシュを削除する。
     * スレッド情報が空の場合は新着レスなしとみなし、保存しない。
     *
     * @param void
     */
    public function __destruct()
    {
        if ($this->_enabled && count($this->_metaData['threads'])) {
            $this->_metaData['size'] = strlen($this->_content);
            MatomeCacheList::add($this->_content, $this->_metaData);
            if ($this->_maxNumEntries > 0) {
                MatomeCacheList::trim($this->_maxNumEntries);
            }
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
            $this->_content .= $content;
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
