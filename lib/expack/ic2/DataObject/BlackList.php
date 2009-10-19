<?php
require_once P2EX_LIB_DIR . '/ic2/loadconfig.inc.php';
require_once P2EX_LIB_DIR . '/ic2/DataObject/Common.php';

// {{{ constants

define('P2_IMAGECACHE_BLACKLIST_NOMORE', 0);
define('P2_IMAGECACHE_BLACKLIST_ABORN', 1);
define('P2_IMAGECACHE_BLACKLIST_VIRUS', 2);

// }}}
// {{{ IC2_DataObject_BlackList

class IC2_DataObject_BlackList extends IC2_DataObject_Common
{
    // {{{ constants

    const NOMORE = 0;
    const ABORN  = 1;
    const VIRUS  = 2;

    // }}}
    // {{{ constcurtor

    public function __construct()
    {
        parent::__construct();
        $this->__table = $this->_ini['General']['blacklist_table'];
    }

    // }}}
    // {{{ table()

    public function table()
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

    public function keys()
    {
        return array('uri');
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
