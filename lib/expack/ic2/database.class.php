<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

require_once 'DB.php';
require_once 'DB/DataObject.php';
require_once (P2EX_LIBRARY_DIR . '/ic2/loadconfig.inc.php');

class IC2DB_Skel extends DB_DataObject
{
    // {{{ properties

    var $_db;
    var $_ini;

    // }}}
    // {{{ constcurtor

    function IC2DB_Skel()
    {
        $this->__construct();
    }

    function __construct()
    {
        // 設定の読み込み
        $ini = ic2_loadconfig();
        $this->_ini = $ini;
        if (!$ini['General']['dsn']) {
            die("<p><b>Error:</b> DSNが設定されていません。</p>");
        }

        // 拡張モジュールの読み込み
        list($dbextension, ) = explode(':', $ini['General']['dsn']);
        if (!extension_loaded($dbextension)) {
            $extdir = ini_get('extension_dir');
            if (strstr(PHP_OS, 'WIN')) {
                $dbmodulename = 'php_' . $dbextension . '.dll';
            } else {
                $dbmodulename = $dbextension . '.so';
            }
            $dbmodulepath = $extdir . DIRECTORY_SEPARATOR . $dbmodulename;
            if (!file_exists($dbmodulepath)) {
                die("<p><b>Error:</b> {$dbmodulename}が{$extdir}にありません。</p>");
            } elseif (!@dl($dbmodulename)) {
                die("<p><b>Error:</b> {$dbmodulename}をロードできませんでした。</p>");
            }
        }

        // データベースへ接続
        $this->_database_dsn = $ini['General']['dsn'];
        $this->_db = &$this->getDatabaseConnection();
        if (DB::isError($this->_db)) {
            die($this->_db->getMessage());
        }
    }

    // }}}
    // {{{ whereAddQuoted()

    // WHERE句をつくる
    function whereAddQuoted($key, $cmp, $value, $logic = 'AND')
    {
        $types = $this->table();
        $col = $this->_db->quoteIdentifier($key);
        if ($types[$key] != DB_DATAOBJECT_INT) {
            $value = $this->_db->quoteSmart($value);
        }
        $cond = sprintf('%s %s %s', $col, $cmp, $value);
        return $this->whereAdd($cond, $logic);
    }

    // }}}
    // {{{ orderByArray()

    // ORDER BY句をつくる
    function orderByArray($sort)
    {
        $order = array();
        foreach ($sort as $k => $d) {
            if (!is_string($k)) {
                if ($d && is_string($d)) {
                    $k = $d;
                    $d = 'ASC';
                } else {
                    continue;
                }
            }
            if (!$d || strtoupper($d) == 'DESC') {
                $order[] = $this->_db->quoteIdentifier($k) . ' DESC';
            } else {
                $order[] = $this->_db->quoteIdentifier($k) . ' ASC';
            }
        }
        if (!count($order)) {
            return FALSE;
        }
        return $this->orderBy(implode(', ', $order));
    }

    // }}}
}

?>
