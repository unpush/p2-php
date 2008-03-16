<?php
/**
 * rep2expack - ImageCache2 - ƒ†[ƒUÝ’è“Ç‚Ýž‚ÝŠÖ”
 */
function ic2_loadconfig()
{
    static $ini = null;
    if (is_null($ini)) {
        $ini = array();
        include 'conf/conf_ic2.inc.php';
        /*if (isset($_conf['expack.ic2.general.dsn'])) {
            $dsn = $_conf['expack.ic2.general.dsn'];
            if (preg_match('|^(sqlite:///)(.+)|', $dsn, $matches)) {
                include_once 'File/Util.php';
                $dsn = 'sqlite:///' . File_Util::realPath($matches[2], '/');
                $_conf['expack.ic2.general.dsn'] = $dsn;
            }
        }*/
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
    }
    return $ini;
}

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
