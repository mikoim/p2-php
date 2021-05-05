<?php
/*
ReplaceImageURL(url)        メイン関数
save(array)                 データを保存
load()                      データを読み込んで返す(自動的に実行される)
clear()                     データを削除
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

    // ファイル名を返す
    public function filename($cont)
    {
        return 'p2_replace_' . $cont . '.txt';
    }

    // ファイルを削除
    public function clear($cont)
    {
        global $_conf;

        $path = $_conf['pref_dir'] . '/' . $this->filename($cont);

        return @unlink($path);
    }

    // 全てのデータを読み込む
    public function load()
    {
        $this->loadFile('name');
        $this->loadFile('mail');
        $this->loadFile('date');
        $this->loadFile('msg');

        return $this->data;
    }

    // ファイルを読み込む
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
                    //"#" ";" "'" "//"から始まる行はコメント
                    continue;
                }
                $lar = explode("\t", trim($l));
                // Matchは必要だがReplaceは空でも良い
                if (strlen($lar[0]) == 0)  continue;

                $ar = array(
                    'match'   => $lar[0], // 対象文字列
                    'replace' => $lar[1], // 置換文字列
                    'mode'    => $lar[2]  // モード(0:両方, 1:PC, 2:携帯)
                );

                $this->data[$cont][] = $ar;
                if ($lar[2] != $check_mode) {
                    $this->data_filtered[$cont][] = $ar;
                    // replaceにレス固有の変数$i($id, $id_base64)が含まれる場合
                    if (!$this->data_nocache[$cont] && strpos($lar[1], '$' !== FALSE)) {
                        $this->data_nocache[$cont] = true;
                    }
                }
            }
        }
    }

    // ファイルを保存
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
    $cont:対象
          name:名前
          mail:メール
          date:日付その他
          msg:メッセージ
    $aThread
          Threadクラスオブジェクトを指定(showthread.inc.phpなら$this->thread)
    $ares:レスの内容
    $i:レス番号
    */
    public function replace($cont, $aThread, $ares, $i)
    {
        // キャッシュ
        /*
        キャッシュが有効になる条件
        ・replaceで$i, $id, $id_base64を使ってない
        これらを使うと置換ワードの結果は同じデータでもレス番号ごとに異なる結果になるため、キャッシュできなくなる。

        キャッシュの働きやすさはメール欄＞名前欄＞＞本文＞＞＞＞＞＞＞＞日付欄といったところ。
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
            // エラー
            default:
                // そのまま返す
                return $word;
        }

        // 置換設定が無い場合はそのまま返す
        if (!isset($this->data_filtered[$cont])) {
            return $word;
        }
        // キャッシュ可能な場合
        if (!$this->data_nocache[$cont]) {
            // キャッシュ
            // sha1を使うと速くなるが低確率で衝突する
            // sha1の計算結果自体をキャッシュしても速くならなかった
            $cache_ = &$cache[$cont][sha1($word)];
            // キャッシュがあればそれを返す
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
            /* Match用の変数展開(用途が思い浮かばないのでコメントアウト)
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
            これ自体に正規表現が入っていたらどうしよう。
            実質的に使うのは$i, $host, $bbs, $key, $date_idくらいだから問題ないだろうけど。
            */
            $v['replace'] = strtr($v['replace'], $replace_pairs);
            $word = @preg_replace ('{'.$v['match'].'}', $v['replace'], $word);
        }

        // キャッシュ可能ならキャッシュする
        if (!$this->data_nocache[$cont]) {
            $cache_ = $word;
        }
        return $word;
    }
}
