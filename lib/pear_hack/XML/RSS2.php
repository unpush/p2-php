<?php
// vim: set expandtab tabstop=4 shiftwidth=4 fdm=marker:
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Martin Jansen <mj@php.net>                                  |
// |                                                                      |
// +----------------------------------------------------------------------+
//
// $Id:$
//

require_once 'XML/Parser.php';
require_once 'XML/RSS.php';

/**
* RSS parser class.
*
* This class is a parser for RSS 2.0, based on PEAR::XML_RSS RSS.php (v 1.14),
* which written by Martin Jansen <mj@php.net>
*
* @author Martin Jansen <mj@php.net>
* @version $Revision:$
* @access  public
*/
class XML_RSS2 extends XML_RSS
{
    // {{{ properties

    /**
     * @var array
     */
    var $parentTags = array('CHANNEL', 'ITEM', 'IMAGE', 'TEXTINPUT');

    /**
     * @var array
     */
    var $channelTags = array('TITLE', 'LINK', 'DESCRIPTION', 'IMAGE', 'ITEMS', 'TEXTINPUT',
                             'LANGUAGE', 'COPYRIGUT', 'MAMAGINGEDITOR', 'WABMASTER',
                             'PUBDATE', 'LASTBUILDDATE', 'CATEGORY', 'GENERATOR', 'DOCS',
                             'CLOWD', 'TTL', 'RATING', 'SKIPHOURS', 'SKIPDAIYS');

    /**
     * @var array
     */
    var $itemTags = array('TITLE', 'LINK', 'DESCRIPTION', 'PUBDATE',
                          'AUTHOR', 'CATEGORY', 'COMMENTS', 'ENCLOSURE', 'GUID', 'SOURCE');

    /**
     * @var array
     */
    var $imageTags = array('TITLE', 'URL', 'LINK', 'WIDTH', 'HEIGHT', 'DESCRIPTION');

    /**
     * @var array
     */
    var $textinputTags = array('TITLE', 'DESCRIPTION', 'NAME', 'LINK');

    /**
     * List of allowed module tags
     *
     * Currently Dublin Core Metadata and the blogChannel RSS module
     * are supported.
     *
     * @var array
     */
    var $moduleTags = array('DC:TITLE', 'DC:CREATOR', 'DC:SUBJECT', 'DC:DESCRIPTION',
                            'DC:PUBLISHER', 'DC:CONTRIBUTOR', 'DC:DATE', 'DC:TYPE',
                            'DC:FORMAT', 'DC:IDENTIFIER', 'DC:SOURCE', 'DC:LANGUAGE',
                            'DC:RELATION', 'DC:COVERAGE', 'DC:RIGHTS', 'DC:PUBDATE',
                            'BLOGCHANNEL:BLOGROLL', 'BLOGCHANNEL:MYSUBSCRIPTIONS',
                            'BLOGCHANNEL:MYSUBSCRIPTIONS', 'BLOGCHANNEL:CHANGES',
                            'CONTENT:ENCODED');

    // }}}
    // {{{ Constructor

    /**
     * Constructor
     *
     * @access public
     * @param mixed File pointer or name of the RDF file.
     * @return void
     */
    function XML_RSS2($handle = '')
    {
        $this->XML_RSS($handle);
    }

    // }}}

}
?>