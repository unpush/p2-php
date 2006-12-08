<?php
/**
 * rep2expack - ImageCache2
 */

require_once P2EX_LIBRARY_DIR . '/ic2/loadconfig.inc.php';
require_once P2EX_LIBRARY_DIR . '/ic2/database.class.php';

define('P2_IMAGECACHE_BLACKLIST_NOMORE', 0);
define('P2_IMAGECACHE_BLACKLIST_ABORN', 1);
define('P2_IMAGECACHE_BLACKLIST_VIRUS', 2);

class IC2DB_BlackList extends IC2DB_Skel
{
    // {{{ properties

    // }}}
    // {{{ constcurtor

    function IC2DB_BlackList()
    {
        $this->__construct();
    }

    function __construct()
    {
        parent::__construct();
        $this->__table = $this->_ini['General']['blacklist_table'];
    }

    // }}}
    // {{{ table()

    function table()
    {
        return array(
            'id'   => DB_DATAOBJECT_INT,
            'uri'  => DB_DATAOBJECT_STR,
            'size' => DB_DATAOBJECT_INT,
            'md5'  => DB_DATAOBJECT_STR,
            'type' => DB_DATAOBJECT_INT,
        );
    }

    // }}}
    // {{{ keys()

    function keys()
    {
        return array('uri');
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
