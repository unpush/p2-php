<?php
/**
 * rep2expack - flock() ベースの汎用ロック
 */

// {{{ P2Lock

/**
 * 簡易ロッククラス
 */
class P2Lock
{
    // {{{ properties

    /**
     * ロックファイルのパス
     *
     * @var string
     */
    protected $_filename;

    /**
     * ロックファイルのハンドル
     *
     * @var resource
     */
    protected $_fh;

    /**
     * ロックファイルを自動で削除するかどうか
     *
     * @var bool
     */
    protected $_remove;

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     *
     * @param  string $name     ロック名（≒排他処理したいファイル名）
     * @param  bool   $remove   ロックファイルを自動で削除するかどうか
     * @param  string $suffix   ロックファイル名の接尾辞
     */
    public function __construct($name, $remove = true, $suffix = '.lck')
    {
        $this->_filename = p2_realpath($name . $suffix);
        $this->_remove = $remove;

        FileCtl::mkdir_for($this->_filename);

        $this->_fh = fopen($this->_filename, 'wb');
        if (!$this->_fh) {
            p2die("cannot create lockfile ({$this->_filename}).");
        }
        if (!flock($this->_fh, LOCK_EX)) {
            p2die("cannot get lock ({$this->_filename}).");
        }
    }

    // }}}
    // {{{ destructor

    /**
     * デストラクタ
     */
    public function __destruct()
    {
        if (is_resource($this->_fh)) {
            flock($this->_fh, LOCK_UN);
            fclose($this->_fh);
            $this->_fh = null;
        }

        if ($this->_remove && file_exists($this->_filename)) {
            unlink($this->_filename);
        }
    }

    // }}}
    // {{{ free()

    /**
     * 明示的にロックを開放する
     */
    public function free()
    {
        $this->__destruct();
    }

    // }}}
    // {{{ remove()

    /**
     * 明示的にロックを開放し、ロックファイルを強制削除する
     *
     * unlink()はstat()のキャッシュを自動的にクリアするので
     * clearstatcache()する必要はない
     */
    public function remove()
    {
        $this->__destruct();
        if (file_exists($this->_filename)) {
            unlink($this->_filename);
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
