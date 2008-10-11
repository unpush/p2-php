<?php
/**
 * ImageCache2 - ƒ†[ƒUÝ’è“Ç‚Ýž‚ÝŠÖ”
 */

require_once 'DB/DataObject.php';

// {{{ ic2_loadconfig()

function ic2_loadconfig()
{
    static $ini = null;

    if (is_null($ini)) {
        include P2_CONF_DIR . '/conf_ic2.inc.php';

        $ini = array();
        $_ic2conf = preg_grep('/^expack\\.ic2\\.\\w+\\.\\w+$/', array_keys($_conf));
        foreach ($_ic2conf as $key) {
            $p = explode('.', $key);
            $cat = ucfirst($p[2]);
            $name = $p[3];
            if (!isset($ini[$cat])) {
                $ini[$cat] = array();
            }
            $ini[$cat][$name] = $_conf[$key];
        }

        // DB_DataObject‚ÌÝ’è
        $_dao_options = &PEAR::getStaticProperty('DB_DataObject', 'options');
        if (!is_array($_dao_options)) {
            $_dao_options = array();
        }
        $_dao_options['database'] = $ini['General']['dsn'];
        $_dao_options['debug'] = false;
        $_dao_options['quote_identifiers'] = true;
        $_dao_options['db_driver'] = 'DB';
    }

    return $ini;
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
