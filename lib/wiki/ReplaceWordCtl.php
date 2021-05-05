<?php
/*
ReplaceImageURL(url)        ���C���֐�
save(array)                 �f�[�^��ۑ�
load()                      �f�[�^��ǂݍ���ŕԂ�(�����I�Ɏ��s�����)
clear()                     �f�[�^���폜
*/
class ReplaceWordCtl
{
    protected $isLoaded = false;
    protected $data = array();
    protected $data_filtered = array();
    protected $data_nocache = array();
    public function setup()
    {
        if (!$this->isLoaded) {
            $this->load();
            $this->isLoaded = true;
        }
    }

    // �t�@�C������Ԃ�
    public function filename($cont)
    {
        return 'p2_replace_' . $cont . '.txt';
    }

    // �t�@�C�����폜
    public function clear($cont)
    {
        global $_conf;

        $path = $_conf['pref_dir'] . '/' . $this->filename($cont);

        return @unlink($path);
    }

    // �S�Ẵf�[�^��ǂݍ���
    public function load()
    {
        $this->loadFile('name');
        $this->loadFile('mail');
        $this->loadFile('date');
        $this->loadFile('msg');

        return $this->data;
    }

    // �t�@�C����ǂݍ���
    public function loadFile($cont): void
    {
        global $_conf;

        $path = $_conf['pref_dir'].'/'.$this->filename($cont);
        $this->data_nocache[$cont] = false;
        if ($lines = @file($path)) {
            $check_mode = $_conf['ktai'] ? 1 : 2;
            foreach ($lines as $l) {
                if (substr($l, 0, 1) === ';' || substr($l, 0, 1) === "'" ||
                    substr($l, 0, 1) === '#' || substr($l, 0, 2) === '//') {
                    //"#" ";" "'" "//"����n�܂�s�̓R�����g
                    continue;
                }
                $lar = explode("\t", trim($l));
                // Match�͕K�v����Replace�͋�ł��ǂ�
                if (strlen($lar[0]) == 0)  continue;

                $ar = array(
                    'match'   => $lar[0], // �Ώە�����
                    'replace' => $lar[1], // �u��������
                    'mode'    => $lar[2]  // ���[�h(0:����, 1:PC, 2:�g��)
                );

                $this->data[$cont][] = $ar;
                if ($lar[2] != $check_mode) {
                    $this->data_filtered[$cont][] = $ar;
                    // replace�Ƀ��X�ŗL�̕ϐ�$i($id, $id_base64)���܂܂��ꍇ
                    if (!$this->data_nocache[$cont] && strpos($lar[1], '$' !== FALSE)) {
                        $this->data_nocache[$cont] = true;
                    }
                }
            }
        }
    }

    // �t�@�C����ۑ�
    public function save($data)
    {
        global $_conf;

        $path = $_conf['pref_dir'] . '/' . $this->filename($cont);

        $newdata = '';

        foreach ($data as $na_info) {
            $a[0] = strtr(trim($na_info['match']  , "\t\r\n"), "\t\r\n", "   ");
            $a[1] = strtr(trim($na_info['replace'], "\t\r\n"), "\t\r\n", "   ");
            $a[2] = strtr(trim($na_info['mode']   , "\t\r\n"), "\t\r\n", "   ");
            if ($na_info['del'] || ($a[0] === '' || $a[1] === '')) {
                continue;
            }
            $newdata .= implode("\t", $a) . "\n";
        }
        return FileCtl::file_write_contents($path, $newdata);
    }

    /*
    $cont:�Ώ�
          name:���O
          mail:���[��
          date:���t���̑�
          msg:���b�Z�[�W
    $aThread
          Thread�N���X�I�u�W�F�N�g���w��(showthread.inc.php�Ȃ�$this->thread)
    $ares:���X�̓��e
    $i:���X�ԍ�
    */
    public function replace($cont, $aThread, $ares, $i)
    {
        // �L���b�V��
        /*
        �L���b�V�����L���ɂȂ����
        �Ereplace��$i, $id, $id_base64���g���ĂȂ�
        �������g���ƒu�����[�h�̌��ʂ͓����f�[�^�ł����X�ԍ����ƂɈقȂ錋�ʂɂȂ邽�߁A�L���b�V���ł��Ȃ��Ȃ�B

        �L���b�V���̓����₷���̓��[���������O�������{���������������������t���Ƃ������Ƃ���B
        */
        static $cache = array('name' => array(), 'mail' => array(), 'date' => array(), 'msg' => array());

        $this->setup();

        $resar   = $aThread->explodeDatLine($ares);

        switch ($cont) {
            case 'name':
                $word = $resar[0];
                break;
            case 'mail':
                $word = $resar[1];
                break;
            case 'date':
                $word = $resar[2];
                break;
            case 'msg':
                $word = $resar[3];
                break;
            // �G���[
            default:
                // ���̂܂ܕԂ�
                return $word;
        }

        // �u���ݒ肪�����ꍇ�͂��̂܂ܕԂ�
        if (!isset($this->data_filtered[$cont])) {
            return $word;
        }
        // �L���b�V���\�ȏꍇ
        if (!$this->data_nocache[$cont]) {
            // �L���b�V��
            // sha1���g���Ƒ����Ȃ邪��m���ŏՓ˂���
            // sha1�̌v�Z���ʎ��̂��L���b�V�����Ă������Ȃ�Ȃ�����
            $cache_ = &$cache[$cont][sha1($word)];
            // �L���b�V��������΂����Ԃ�
            if (isset($cache_)) {
                return $cache_;
            }
        }

        preg_match('|ID: ?([0-9A-Za-z/.+]{8,11})|',$resar[2], $matches);
        $replace_pairs = array(
            '$ttitle_hd' => $aThread->ttitle_hd,
            '$host'      => $aThread->host,
            '$bbs'       => $aThread->bbs,
            '$key'       => $aThread->key,
            '$id'        => $matches[1],
            '$id_base64' => base64_encode($matches[1]),
            '$i'         => $i
        );
        foreach ($this->data_filtered[$cont] as $v) {
            /* Match�p�̕ϐ��W�J(�p�r���v�������΂Ȃ��̂ŃR�����g�A�E�g)
            $v['match'] = str_replace ('$i',         $i, $v['match']);
            $v['match'] = str_replace ('$ttitle',    $aThread->ttitle, $v['match']);
            $v['match'] = str_replace ('$ttitle_hd', $aThread->ttitle_hd, $v['match']);
            $v['match'] = str_replace ('$host',      $aThread->host, $v['match']);
            $v['match'] = str_replace ('$bbs',       $aThread->bbs,  $v['match']);
            $v['match'] = str_replace ('$key',       $aThread->key,  $v['match']);
            $v['match'] = str_replace ('$name',      $name,  $v['match']);
            $v['match'] = str_replace ('$mail',      $mail,  $v['match']);
            $v['match'] = str_replace ('$date_id',   $date_id,  $v['match']);
            $v['match'] = str_replace ('$msg',       $msg,  $v['match']);
            $v['match'] = str_replace ('$id_base64', base64_encode($id),  $v['match']);
            $v['match'] = str_replace ('$id',        $id,  $v['match']);
            */
            /*
            ���ꎩ�̂ɐ��K�\���������Ă�����ǂ����悤�B
            �����I�Ɏg���̂�$i, $host, $bbs, $key, $date_id���炢��������Ȃ����낤���ǁB
            */
            $v['replace'] = strtr($v['replace'], $replace_pairs);
            $word = @preg_replace ('{'.$v['match'].'}', $v['replace'], $word);
        }

        // �L���b�V���\�Ȃ�L���b�V������
        if (!$this->data_nocache[$cont]) {
            $cache_ = $word;
        }
        return $word;
    }
}
