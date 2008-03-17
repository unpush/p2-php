<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=4 fdm=marker: */
/* mi: charset=Shift_JIS */

require_once 'PEAR.php';
require_once dirname(__FILE__) . '/search.class.php';

class GoogleSearch_PHP5 extends GoogleSearch_Common
{
    // {{{ constructor

    /**
     * コンストラクタ
     *
     * @return void
     * @access public
     */
    public function __construct()
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
    public function init($wsdl, $key)
    {
        if (!file_exists($wsdl)) {
            return PEAR::raiseError('GoogleSearch.wsdl not found.');
        }
        if (!extension_loaded('soap')) {
            return PEAR::raiseError('SOAP extension not loaded.');
        }

        $this->setConf($wsdl, $key);

        try {
            $this->soapClient = &new SoapClient($wsdl, $this->options);
        } catch (SoapFault $e) {
            $errfmt = 'SOAP Fault: (faultcode: %s; faultstring: %s;)';
            $errmsg = sprintf($errfmt, $e->faultcode, $e->faultstring);
            return PEAR::raiseError($errmsg);
        }

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
    public function doSearch($q, $maxResults = 10, $start = 0)
    {
        $params = $this->prepareParams($q, $maxResults, $start);
        try {
            $result = call_user_func_array(array($this->soapClient, 'doGoogleSearch'), $params);
        } catch (SoapFault $e) {
            $errfmt = 'SOAP Fault: (faultcode: %s; faultstring: %s;)';
            $errmsg = sprintf($errfmt, $e->faultcode, $e->faultstring);
            return PEAR::raiseError($errmsg);
        }
        return $result;
    }

    // }}}
}
