<?php
/**
 * rep2expack - ImageCache2
 */

require_once 'DB.php';
require_once 'DB/DataObject.php';
require_once P2EX_LIBRARY_DIR . '/ic2/loadconfig.inc.php';

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
        static $set_to_utf8 = false;

        // 設定の読み込み
        $ini = ic2_loadconfig();
        $this->_ini = $ini;
        if (!$ini['General']['dsn']) {
            die("<p><b>Error:</b> DSNが設定されていません。</p>");
        }

        // 拡張モジュールの読み込み
        list($dbextension, ) = explode(':', $ini['General']['dsn'], 2);
        if (!extension_loaded($dbextension)) {
            $extdir = ini_get('extension_dir');
            if (substr(PHP_OS, 0, 3) == 'WIN') {
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

        // クライアントの文字セットに UTF-8 を指定
        if (!$set_to_utf8) {
            switch (strtolower($dbextension)) {
            case 'mysql':
            case 'mysqli':
                $version = &$this->_db->getRow("SHOW VARIABLES LIKE 'version'", array(), DB_FETCHMODE_ORDERED);
                if (!DB::isError($version) && version_compare($version[1], '4.1.0') != -1) {
                    $charset = &$this->_db->getRow("SHOW VARIABLES LIKE 'character_set_database'", array(), DB_FETCHMODE_ORDERED);
                    if (!DB::isError($charset) && $charset[1] == 'latin1') {
                        $errmsg = "<p><b>Warning:</b> データベースの文字セットが latin1 に設定されています。</p>";
                        $errmsg .= "<p>mysqld の default-character-set が binary, ujis, utf8 等でないと日本語の文字が壊れるので ";
                        $errmsg .= "<a href=\"http://www.mysql.gr.jp/frame/modules/bwiki/?FAQ#content_1_40\">日本MySQLユーザ会のFAQ</a>";
                        $errmsg .= " を参考に my.cnf の設定を変えてください。</p>";
                        die($errmsg);
                    }
                }
                $this->_db->query("SET NAMES utf8");
                break;
            case 'pgsql':
                $this->_db->query("SET CLIENT_ENCODING TO 'UTF8'");
                break;
            }
            $set_to_utf8 = true;
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
            $k = $this->_db->quoteIdentifier($k);
            if (!$d || strtoupper($d) == 'DESC') {
                $order[] = $k . ' DESC';
            } else {
                $order[] = $k . ' ASC';
            }
        }
        if (!count($order)) {
            return FALSE;
        }
        return $this->orderBy(implode(', ', $order));
    }

    // }}}
}

/*
 * Local variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
