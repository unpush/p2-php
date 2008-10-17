<?php
require_once P2_LIB_DIR . '/HTML/StripTags.php';

/**
 * HTML_StripTags for PHP5
 */
class HTML_StripTags2
{
    private $_allowed_tags       = array('', 'font', 'b', 'a', 'img');
    private $_allowed_empty_tags = array('br', 'hr');
    private $_allowed_attributes = array(
        'font' => array('color', 'size', 'face'),
        'a'    => array('href', 'target'),
        'br'   => array('clear'),
        'img'  => array('src', 'width', 'height', 'align', 'hspace', 'vspace')
    );

    private $_correct_markup = false;
    private $_elements_stack = array();

    private $_charset = 'Shift_JIS';
    private $_use_wildcard = false;
    private $_have_double_encode_flag = true;
    
    private $_allowed_tags_str   = '';
    
    public function __construct($options = null)
    {
        if (is_array($options)) {
            if (array_key_exists('allowed_tags', $options)) {
                $this->_allowed_tags = (array)$options['allowed_tags'];
            }
            if (array_key_exists('allowed_empty_tags', $options)) {
                $this->_allowed_empty_tags = (array)$options['allowed_empty_tags'];
            }
            if (array_key_exists('allowed_attributes', $options)) {
                $this->_allowed_attributes
                    = array_map('HTML_StripTags_multi_to_array',
                                (array)$options['allowed_attributes']);
            }
            if (array_key_exists('correct_markup', $options)) {
                $this->_correct_markup = (bool)$options['correct_markup'];
            }
            if (array_key_exists('charset', $options)) {
                $this->_charset = (string)$options['charset'];
            }
        }

        if ($this->_allowed_tags) {
            $this->_allowed_tags_str .= '<' . implode('><', $this->_allowed_tags) . '>';
        }
        if ($this->_allowed_empty_tags) {
            $this->_allowed_tags_str .= '<' . implode('><', $this->_allowed_empty_tags) . '>';
        }
        if ($this->_allowed_attributes) {
            if (!empty($this->_allowed_attributes['*'])) {
                $this->_use_wildcard = true;
                $wildcard = $this->_allowed_attributes['*'];
                foreach ($this->_allowed_attributes as &$value) {
                    $value = array_merge_recursive($wildcard, $value);
                }
            }
            $this->_allowed_attributes
                = array_map('HTML_StripTags_multi_to_lower',
                            array_change_key_case($this->_allowed_attributes));
        }
        if (version_compare(phpversion(), '5.2.3', 'lt')) {
            $this->_have_double_encode_flag = false;
        }
    }

    /**
     * @param   string  $msg  HTML
     * @return  string  HTML
     */
    public function cleanup($msg)
    {
        $msg = preg_replace_callback('/<(\\/?[A-Za-z][\\w:]*)(.*?)>/',
                                     array($this, 'stripAttributes'),
                                     strip_tags($msg, $this->_allowed_tags_str));
        while (count($this->_elements_stack)) {
            $msg .= '</' . array_pop($this->_elements_stack) . '>';
        }
        return $msg;
    }

    /**
     * callback for cleanup()
     *
     * @return  string  HTML
     */
    public function stripAttributes(array $matches)
    {
        $tag_name = $matches[1];

        if ($tag_name[0] == '/') {
            if (!$this->_correct_markup) {
                return '<' . $tag_name . '>';
            } elseif (count($this->_elements_stack)) {
                return '</' . array_pop($this->_elements_stack) . '>';
            } else {
                return '';
            }
        }

        $lcname = strtolower($tag_name);
        $attr_str = '';

        if (array_key_exists($lcname, $this->_allowed_attributes)) {
            $whitelist = $this->_allowed_attributes[$lcname];
        } elseif ($this->_use_wildcard) {
            $whitelist = $this->_allowed_attributes['*'];
        } else {
            $whitelist = null;
        }

        if ($whitelist &&
            preg_match_all('/\\s*([A-Za-z][\\w:]*)(\\s*=\\s*(([\'"]).*?\\4|\\S+)?)?/',
                           trim($matches[2], " \t\n\t\0\x0B/"), $attrs, PREG_SET_ORDER))
        {
            foreach ($attrs as $attr) {
                if (in_array(strtolower($attr[1]), $whitelist)) {
                    if (empty($attr[2])) {
                        //$attr_str .= ' ' . $attr[1] . '="' . $attr[1] . '"';
                        // for compatibility
                        $attr_str .= ' ' . $attr[1];
                    } else {
                        $value = (empty($attr[4])) ? $attr[3] : substr($attr[3], 1, -1);
                        $attr_str .= ' ' . $attr[1] . '="';
                        if ($this->_have_double_encode_flag) {
                            $attr_str .= htmlspecialchars($value, ENT_COMPAT,
                                                          $this->_charset, false);
                        } else {
                            $attr_str .= str_replace(array('"', '<', '>'),
                                                     array('&quot;', '&lt', '&gt;'),
                                                     $value);
                        }
                        $attr_str .= '"';
                    }
                }
            }
        }

        if (in_array($lcname, $this->_allowed_empty_tags)) {
            // <br /> ‚É•ÏŠ·‚·‚é‚ÆA‚©‚¦‚Á‚Äread_copy_k.php“à‚Ì•ÏŠ·‚Å•s“s‡‚ª¶‚¶‚½‚è‚·‚éB
            //return '<' . $tag_name . $attr_str . ' />';
            return '<' . $tag_name . $attr_str . '>';
        } else {
            if ($this->_correct_markup) {
                $this->_elements_stack[] = $tag_name;
            }
            return '<' . $tag_name . $attr_str . '>';
        }
    }
}
