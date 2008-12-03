<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

/*
    2006/02/24 aki
        �傫�ȃf�[�^���������ɏ����R�X�g��������̂ŁADataPhp�`���͂����g��Ȃ������B
        �g���q .cgi ���ւƂ���B
    
    ������A�f�[�^�t�@�C����Web���璼�ڃA�N�Z�X����Ă��t�@�C�����e�������邱�Ƃ̂Ȃ��悤�ɁA
    php�`���̃t�@�C���Ńf�[�^����舵���N���X
    
    static���\�b�h�ŗ��p����B�t�@�C���̕ۑ��`���́A�ȉ��̂悤�Ȋ����B
    
    ���Hphp �^*
    �f�[�^
    *�^ �H��
*/
class DataPhp
{
    /**
     * @static
     * @access  private
     * @return  string
     */
    function getPre()
    {
        return "<?php /*\n";
    }

    /**
     * @static
     * @access  private
     * @return  string
     */
    function getHip()
    {
        return "\n*/ ?>";
    }

    /**
     * �f�[�^php�`���̃t�@�C����ǂݍ���
     * ������̃A���G�X�P�[�v���s��
     *
     * @static
     * @access  public
     * @return  string|false
     */
    function getDataPhpCont($data_php)
    {
        if (!$cont = @file_get_contents($data_php)) {
            // �ǂݍ��݃G���[�Ȃ�false�A����ۂȂ�""��Ԃ�
            return $cont;
            
        } else {
            $pre_quote = preg_quote(DataPhp::getPre());
            $hip_quote = preg_quote(DataPhp::getHip());
            // �擪���Ɩ������폜
            if (preg_match("{".$pre_quote."(.*?)".$hip_quote.".*}s", $cont, $m)) {
                $cont = $m[1];
            } else {
                return false;
            }

            // �A���G�X�P�[�v����
            $cont = DataPhp::unescapeDataPhp($cont);

            return $cont;
        }
    }
    
    /**
     * �f�[�^php�`���̃t�@�C�������C���œǂݍ���
     * ������̃A���G�X�P�[�v���s��
     *
     * @static
     * @access  public
     * @return  array|false
     */
    function fileDataPhp($data_php)
    {
        if (!$cont = DataPhp::getDataPhpCont($data_php)) {
            // �ǂݍ��݃G���[�Ȃ�false�A����ۂȂ��z���Ԃ�
            if ($cont === false) {
                return false;
            } else {
                return array();
            }
        } else {
            // �s�f�[�^�ɕϊ�
            $lines = array();
            
            $lines = explode("\n", $cont);
            $count = count($lines);
            
            $i = 1;
            foreach ($lines as $l) {
                if ($i != $count) {
                    $newlines[] = $l."\n";
                // �ŏI�s�Ȃ�
                } else {
                    // ����ۂłȂ���Βǉ�
                    if ($l !== "") {
                        $newlines[] = $l;
                    }
                    break;
                }
                $i++;
            }
            
            /*
            if ($lines) {
                // �����̋�s�͓��ʂɍ폜����
                $count = count($lines);
                if (rtrim($lines[$count-1]) == "") {
                    array_pop($lines);
                }
            }
            */
            
            return $newlines;
        }
    }

    /**
     * �f�[�^php�`���̃t�@�C���Ƀf�[�^���L�^����i���[�h��wb�j
     * ������̃G�X�P�[�v���s��
     *
     * @static
     * @access  public
     * @param   srting   $cont  �L�^����f�[�^������
     * @return  boolean
     */
    function writeDataPhp($data_php, &$cont, $perm = 0606)
    {
        // &<>/ �� &xxx; �ɃG�X�P�[�v����
        $new_cont = DataPhp::escapeDataPhp($cont);
        
        // �擪���Ɩ�����ǉ�
        $new_cont = DataPhp::getPre() . $new_cont . DataPhp::getHip();
        
        if (false === FileCtl::make_datafile($data_php, $perm)) {
            return false;
        }
        
        // ��������
        if (false === file_put_contents($data_php, $new_cont, LOCK_EX)) {
            //die("Error: �t�@�C�����X�V�ł��܂���ł���");
            return false;
        }
        
        return true;
    }
    
    /**
     * �f�[�^php�`���̃t�@�C���ŁA�����Ƀf�[�^��ǉ�����
     *
     * @static
     * @return  boolean
     */
    function putDataPhp($data_php, &$cont, $perm = 0606, $ncheck = false)
    {
        if ($cont === "") {
            return true;
        }
        
        $pre_quote = preg_quote(DataPhp::getPre());
        $hip_quote = preg_quote(DataPhp::getHip());

        $cont_esc = DataPhp::escapeDataPhp($cont);

        $old_cont = @file_get_contents($data_php);
        if ($old_cont) {
            // �t�@�C�����A�f�[�^php�`���ȊO�̏ꍇ�́A����������false��Ԃ�
            if (!preg_match("/^\s*<\?php\s\/\*/", $old_cont)) {
                trigger_error(__FUNCTION__ . '() file is broken.', E_USER_WARNING);
                return false;
            }
            
            $old_cut = preg_replace('{'.$hip_quote.'.*$}s', '', $old_cont);
            
            // �w��ɉ����āA�Â����e�̖��������s�łȂ���΁A���s��ǉ�����
            if ($ncheck) {
                if (substr($old_cut, -1) != "\n") {
                    $old_cut .= "\n";
                }
            }
            
            $new_cont = $old_cut . $cont_esc . DataPhp::getHip();
            
        // �f�[�^���e���܂��Ȃ���΁A�V�K�f�[�^php
        } else {
            $new_cont = DataPhp::getPre() . $cont_esc . DataPhp::getHip();
        }
        
        FileCtl::make_datafile($data_php, $perm);
        
        if (false === file_put_contents($data_php, $new_cont, LOCK_EX)) {
            // die("Error: �t�@�C�����X�V�ł��܂���ł���");
            return false;
        }
        
        return true;
    }
    
    /**
     * �f�[�^php�`���̃f�[�^���G�X�P�[�v����
     *
     * @static
     * @access  private
     * @return  string
     */
    function escapeDataPhp($str)
    {
        // &<>/ �� &xxx; �̃G�X�P�[�v������
        return str_replace(
            array('&',    '<',   '>',    '/'),
            array('&amp;', '&lt;', '&gt;', '&frasl;'),
            $str
        );
    }

    /**
     * �f�[�^php�`���̃f�[�^���A���G�X�P�[�v����
     *
     * @static
     * @access  private
     * @return  string
     */
    function unescapeDataPhp($str)
    {
        // &<>/ �� &xxx; �̃G�X�P�[�v�����ɖ߂�
        return str_replace(
            array('&amp;', '&lt;', '&gt;', '&frasl;'),
            array('&',    '<',   '>',    '/'),
            $str
        );
    }
}