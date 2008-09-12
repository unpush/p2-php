<?php
// {{{ Google_Search

/**
 * Google検索クラスを生成するクラス
 *
 * @static
 */
class Google_Search
{
    // {{{ factory()

    /**
     * SOAPクライアントを初期化する
     *
     * @param string $wsdl  Google Search WSDLファイルのパス
     * @param string $key   Google Key
     * @return object
     */
    static public function factory($wsdl, $key)
    {
        global $_conf;

        if (extension_loaded('soap') && empty($_conf['expack.google.force_pear'])) {
            require_once dirname(__FILE__) . '/Search/SoapExtension.php';
            $google = new Google_Search_SoapExtension;
        } else {
            p2die('SOAP extension required', '現在、PEAR SOAPでGoogle検索はできません。PHPのSOAP拡張機能を有効にしてください。');
            require_once dirname(__FILE__) . '/Search/PearSoap.php';
            $google = new Google_Search_PearSoap;
        }

        $available = $google->init($wsdl, $key);
        if (PEAR::isError($available)) {
            return $available;
        }

        return $google;
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
