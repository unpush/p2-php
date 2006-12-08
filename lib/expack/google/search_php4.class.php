<?php
/**
 * rep2expack - search 2ch using Google Web APIs
 */

require_once 'PEAR.php';
require_once 'SOAP/Client.php';
require_once 'SOAP/WSDL.php';
require_once dirname(__FILE__) . '/search.class.php';

class GoogleSearch_PHP4 extends GoogleSearch_Common
{
    // {{{ properties

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     *
     * @return void
     * @access public
     */
    function GoogleSearch_PHP4()
    {
    }

    // }}}
    // {{{ init()

    /**
     * SOAPクライアントのインスタンスを生成する
     *
     * @param string $wsdl  Google Search WSDLファイルのパス
     * @param string $key   Google Web APIs のライセンスキー
     * @return boolean
     * @access public
     */
    function init($wsdl, $key)
    {
        if (!file_exists($wsdl)) {
            //return PEAR::raiseError('GoogleSearch.wsdl not found.');

            /* SOAPサーバのURIを指定してSOAP_Clientクラスを使う
               @link http://www.googleduel.com/apiexample.php */
            $soapClient = &new SOAP_Client('http://api.google.com/search/beta2');
        } else {
            /* SOAP_ClientクラスにWSDLを指定する */
            //$soapClient = &new SOAP_Client($wsdl, true);

            /* SOAP_WSDLクラスにSOAP_Clientを継承したクラスを生成させる */
            $wsdl = &new SOAP_WSDL($wsdl);
            $soapClient = &$wsdl->getProxy();
        }

        $this->setConf($wsdl, $key);

        if (PEAR::isError($soapClient)) {
            return $soapClient;
        }

        $this->soapClient = $soapClient;

        return true;
    }

    // }}}
    // {{{ doSearch()

    /**
     * 検索を実行する
     *
     * @param string  $q  検索キーワード
     * @param integer $start  検索結果を取得する位置
     * @param integer $maxResults  検索結果を取得する最大数
     * @return object 検索結果
     * @access public
     */
    function &doSearch($q, $maxResults = 10, $start = 0)
    {
        $params = $this->prepareParams($q, $maxResults, $start);
        $result = &$this->soapClient->call('doGoogleSearch', $params, $this->options);
        return $result;
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
