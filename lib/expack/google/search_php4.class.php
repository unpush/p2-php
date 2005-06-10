<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

require_once 'PEAR.php';
require_once 'SOAP/Client.php';
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
            return PEAR::raiseError('GoogleSearch.wsdl not found.');
        }

        $this->setConf($wsdl, $key);

        $soapClient = &new SOAP_Client($this->wsdl, TRUE);

        if (PEAR::isError($soapClient)) {
            return $soapClient;
        }

        $this->soapClient = $soapClient;

        return TRUE;
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
        $result = &$this->soapClient->call('doGoogleSearch', $params);
        return $result;
    }

    // }}}
}

?>
