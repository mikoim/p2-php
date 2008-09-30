<?php
/*
    p2 - スレッド表示スクリプト - 新着まとめ読み（携帯）
    フレーム分割画面、右下部分
*/

include_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/threadlist.class.php';
require_once P2_LIB_DIR . '/thread.class.php';
require_once P2_LIB_DIR . '/threadread.class.php';
require_once P2_LIB_DIR . '/ngabornctl.class.php';
require_once P2_LIB_DIR . '/read_new.inc.php';

$_login->authorize(); // ユーザ認証

// まとめよみのキャッシュ読み
if (!empty($_GET['cview'])) {
    $cnum = (isset($_GET['cnum'])) ? intval($_GET['cnum']) : NULL;
    if ($cont = getMatomeCache($cnum)) {
        echo $cont;
    } else {
        header('Content-Type: text/plain; charset=Shift_JIS');
        echo 'p2 error: 新着まとめ読みのキャッシュがないよ';
    }
    exit;
}

// 省メモリ設定
// 1 にするとキャッシュをメモリでなく一時ファイルに保持する
// （どちらでも最終的にはファイルに書き込まれる）
if (!defined('P2_READ_NEW_SAVE_MEMORY')) {
    define('P2_READ_NEW_SAVE_MEMORY', 0);
}

//==================================================================
// 変数
//==================================================================
$GLOBALS['rnum_all_range'] = $_conf['k_rnum_range'];

$sb_view = "shinchaku";
$newtime = date("gis");

//=================================================
// 板の指定
//=================================================
if (isset($_GET['host'])) { $host = $_GET['host']; }
if (isset($_POST['host'])) { $host = $_POST['host']; }
if (isset($_GET['bbs'])) { $bbs = $_GET['bbs']; }
if (isset($_POST['bbs'])) { $bbs = $_POST['bbs']; }
if (isset($_GET['spmode'])) { $spmode = $_GET['spmode']; }
if (isset($_POST['spmode'])) { $spmode = $_POST['spmode']; }

if ((!isset($host) || !isset($bbs)) && !isset($spmode)) {
    die('p2 error: 必要な引数が指定されていません');
}

// 未読数制限
if (isset($_POST['unum_limit'])) {
    $unum_limit = (int)$_POST['unum_limit'];
} elseif (isset($_GET['unum_limit'])) {
    $unum_limit = (int)$_GET['unum_limit'];
} else {
    $unum_limit = 0;
}

//=================================================
// あぼーん&NGワード設定読み込み
//=================================================
$GLOBALS['ngaborns'] = NgAbornCtl::loadNgAborns();

//====================================================================
// メイン
//====================================================================

if (P2_READ_NEW_SAVE_MEMORY) {
    register_shutdown_function('saveMatomeCacheFromTmpFile');
    $read_new_tmp_fh = tmpfile();
    if (!is_resource($read_new_tmp_fh)) {
        die('Error: cannot make tmpfile.');
    }
} else {
    register_shutdown_function('saveMatomeCache');
    $read_new_html = '';
}
ob_start();

$aThreadList =& new ThreadList();

// 板とモードのセット ===================================
if ($spmode) {
    if ($spmode == "taborn" or $spmode == "soko") {
        $aThreadList->setIta($host, $bbs, P2Util::getItaName($host, $bbs));
    }
    $aThreadList->setSpMode($spmode);
} else {
    $aThreadList->setIta($host, $bbs, P2Util::getItaName($host, $bbs));

    // スレッドあぼーんリスト読込
    $idx_host_dir = P2Util::idxDirOfHost($host);
    $taborn_file = $idx_host_dir.'/'.$bbs.'/p2_threads_aborn.idx';

    if ($tabornlines = @file($taborn_file)) {
        $ta_num = sizeOf($tabornlines);
        foreach ($tabornlines as $l) {
            $tarray = explode('<>', rtrim($l));
            $ta_keys[ $tarray[1] ] = true;
        }
    }
}

// ソースリスト読込
$lines = $aThreadList->readList();

// ページヘッダ表示 ===================================
$ptitle_hd = htmlspecialchars($aThreadList->ptitle, ENT_QUOTES);
$ptitle_ht = "{$ptitle_hd} の 新着まとめ読み";

// &amp;sb_view={$sb_view}
if ($aThreadList->spmode) {
    $sb_ht = <<<EOP
<a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}{$_conf['k_at_a']}">{$ptitle_hd}</a>
EOP;
    $sb_ht_btm = <<<EOP
<a {$_conf['accesskey']}="{$_conf['k_accesskey']['up']}" href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}{$_conf['k_at_a']}">{$_conf['k_accesskey']['up']}.{$ptitle_hd}</a>
EOP;
} else {
    $sb_ht = <<<EOP
<a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}{$_conf['k_at_a']}">{$ptitle_hd}</a>
EOP;
    $sb_ht_btm = <<<EOP
<a {$_conf['accesskey']}="{$_conf['k_accesskey']['up']}" href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}{$_conf['k_at_a']}">{$_conf['k_accesskey']['up']}.{$ptitle_hd}</a>
EOP;
}

// iPhone
if ($_conf['iphone']) {
    $_conf['extra_headers_ht'] .= <<<EOS
<script type="text/javascript" src="js/respopup_iphone.js"></script>
EOS;
    // ImageCache2
    if ($_conf['expack.ic2.enabled']) {
        $_conf['extra_headers_ht'] .= <<<EOS
<link rel="stylesheet" type="text/css" href="css/ic2_iphone.css">
<script type="text/javascript" src="js/ic2_iphone.js"></script>
EOS;
    }
}

// ========================================================
// include_once P2_LIB_DIR . '/read_header.inc.php';

echo $_conf['doctype'];
echo <<<EOHEADER
<html>
<head>
{$_conf['meta_charset_ht']}
{$_conf['extra_headers_ht']}
<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
<title>{$ptitle_ht}</title>\n
EOHEADER;

echo "</head><body{$_conf['k_colors']}>";

if ($_conf['iphone']) {
    P2Util::printOpenInTab(array(
        ".//div[@class=&quot;res&quot; or @class=&quot;read_new_footer&quot;]//a[starts-with(@href, &quot;{$_conf['read_php']}?&quot;) or starts-with(@href, &quot;{$_conf['subject_php']}?&quot;)]",
        ".//div[@class=&quot;read_new_footer&quot;]//a[starts-with(@href, &quot;spm_k.php?&quot;)]"
    ));
}

echo <<<EOP
<div>{$sb_ht}の新まとめ
<a class="button" id="above" name="above" {$_conf['accesskey']}="{$_conf['k_accesskey']['bottom']}" href="#bottom">{$_conf['k_accesskey']['bottom']}.▼</a></div>\n
EOP;

echo $_info_msg_ht;
$_info_msg_ht = "";

//==============================================================
// それぞれの行解析
//==============================================================

$linesize = sizeof($lines);

for ($x = 0; $x < $linesize; $x++) {

    if (isset($GLOBALS['rnum_all_range']) and $GLOBALS['rnum_all_range'] <= 0) {
        break;
    }

    $l = $lines[$x];
    $aThread =& new ThreadRead();

    $aThread->torder = $x + 1;

    // データ読み込み
    if ($aThreadList->spmode) {
        switch ($aThreadList->spmode) {
        case "recent":    // 履歴
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "res_hist":    // 書き込み履歴
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "fav":    // お気に
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "taborn":    // スレッドあぼーん
            $aThread->getThreadInfoFromExtIdxLine($l);
            $aThread->host = $aThreadList->host;
            $aThread->bbs = $aThreadList->bbs;
            break;
        case "palace":    // 殿堂入り
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        }
    // subject (not spmode)
    } else {
        $aThread->getThreadInfoFromSubjectTxtLine($l);
        $aThread->host = $aThreadList->host;
        $aThread->bbs = $aThreadList->bbs;
    }

    // hostもbbsも不明ならスキップ
    if (!($aThread->host && $aThread->bbs)) {
        unset($aThread);
        continue;
    }

    $aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);
    $aThread->getThreadInfoFromIdx(); // 既得スレッドデータをidxから取得

    // 新着のみ(for subject) =========================================
    if (!$aThreadList->spmode and $sb_view == "shinchaku" and !$_GET['word']) {
        if ($aThread->unum < 1) {
            unset($aThread);
            continue;
        }
    }

    // スレッドあぼーんチェック =====================================
    if ($aThreadList->spmode != "taborn" and $ta_keys[$aThread->key]) {
        unset($ta_keys[$aThread->key]);
        continue; // あぼーんスレはスキップ
    }

    // spmode(殿堂入りを除く)なら ====================================
    if ($aThreadList->spmode && $sb_view != "edit") {

        // subject.txtが未DLなら落としてデータを配列に格納
        if (!$subject_txts["$aThread->host/$aThread->bbs"]) {

            require_once P2_LIB_DIR . '/SubjectTxt.class.php';
            $aSubjectTxt =& new SubjectTxt($aThread->host, $aThread->bbs);

            $subject_txts["$aThread->host/$aThread->bbs"] = $aSubjectTxt->subject_lines;
        }

        // スレ情報取得 =============================
        if ($subject_txts["$aThread->host/$aThread->bbs"]) {
            foreach ($subject_txts["$aThread->host/$aThread->bbs"] as $l) {
                if (@preg_match("/^{$aThread->key}/", $l)) {
                    $aThread->getThreadInfoFromSubjectTxtLine($l); // subject.txt からスレ情報取得
                    break;
                }
            }
        }

        // 新着のみ(for spmode) ===============================
        if ($sb_view == "shinchaku" and !$_GET['word']) {
            if ($aThread->unum < 1) {
                unset($aThread);
                continue;
            }
        }
    }

    // 未読数制限
    if ($unum_limit > 0 && $aThread->unum >= $unum_limit) {
        unset($aThread);
        continue;
    }

    if ($aThread->isonline) { $online_num++; } // 生存数set

    echo $_info_msg_ht;
    $_info_msg_ht = "";

    if (P2_READ_NEW_SAVE_MEMORY) {
        fwrite($read_new_tmp_fh, ob_get_flush());
    } else {
        $read_new_html .= ob_get_flush();
    }
    ob_start();

    if (($aThread->readnum < 1) || $aThread->unum) {
        readNew($aThread);
    } elseif ($aThread->diedat) {
        echo $aThread->getdat_error_msg_ht;
        echo "<hr>\n";
    }

    if (P2_READ_NEW_SAVE_MEMORY) {
        fwrite($read_new_tmp_fh, ob_get_flush());
    } else {
        $read_new_html .= ob_get_flush();
    }
    ob_start();

    // リストに追加 ========================================
    // $aThreadList->addThread($aThread);
    $aThreadList->num++;
    unset($aThread);
}

//$aThread =& new ThreadRead();

//======================================================================
// スレッドの新着部分を読み込んで表示する
//======================================================================
function readNew(&$aThread)
{
    global $_conf, $newthre_num, $STYLE;
    global $_info_msg_ht, $spmode;

    $newthre_num++;

    //==========================================================
    // idxの読み込み
    //==========================================================

    //hostを分解してidxファイルのパスを求める
    $aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);

    //FileCtl::mkdir_for($aThread->keyidx); // 板ディレクトリが無ければ作る //この操作はおそらく不要

    $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
    if (!$aThread->itaj) { $aThread->itaj = $aThread->bbs; }

    // idxファイルがあれば読み込む
    if (is_readable($aThread->keyidx)) {
        $lines = @file($aThread->keyidx);
        $data = explode('<>', rtrim($lines[0]));
    }
    $aThread->getThreadInfoFromIdx();

    //==================================================================
    // DATのダウンロード
    //==================================================================
    if (!($word and file_exists($aThread->keydat))) {
        $aThread->downloadDat();
    }

    // DATを読み込み
    $aThread->readDat();
    $aThread->setTitleFromLocal(); // ローカルからタイトルを取得して設定

    //===========================================================
    // 表示レス番の範囲を設定
    //===========================================================
    // 取得済みなら
    if ($aThread->isKitoku()) {
        $from_num = $aThread->readnum +1 - $_conf['respointer'] - $_conf['before_respointer_new'];
        if ($from_num > $aThread->rescount) {
            $from_num = $aThread->rescount - $_conf['respointer'] - $_conf['before_respointer_new'];
        }
        if ($from_num < 1) {
            $from_num = 1;
        }

        //if (!$aThread->ls) {
            $aThread->ls = "$from_num-";
        //}
    }

    $aThread->lsToPoint();

    //==================================================================
    // ヘッダ 表示
    //==================================================================
    $motothre_url = $aThread->getMotoThread();

    $ttitle_en = base64_encode($aThread->ttitle);
    $ttitle_en_q = "&amp;ttitle_en=".$ttitle_en;
    $bbs_q = "&amp;bbs=".$aThread->bbs;
    $key_q = "&amp;key=".$aThread->key;
    $popup_q = "&amp;popup=1";

    // include_once P2_LIB_DIR . '/read_header.inc.php';

    $prev_thre_num = $newthre_num - 1;
    $next_thre_num = $newthre_num + 1;
    if ($prev_thre_num != 0) {
        $prev_thre_ht = "<a class=\"button\" href=\"#ntt{$prev_thre_num}\">▲</a>";
    }
    //$next_thre_ht = "<a href=\"#ntt{$next_thre_num}\">▼</a> ";
    $next_thre_ht = "<a class=\"button\" href=\"#ntt_bt{$newthre_num}\">▼</a> ";

    $itaj_hd = htmlspecialchars($aThread->itaj, ENT_QUOTES);

    if ($spmode) {
        $read_header_itaj_ht = " ({$itaj_hd})";
    }

    echo $_info_msg_ht;
    $_info_msg_ht = "";

    $read_header_ht = <<<EOP
<hr><div id="ntt{$newthre_num}" name="ntt{$newthre_num}"><font color="{$STYLE['mobile_read_ttitle_color']}"><b>{$aThread->ttitle_hd}</b></font>{$read_header_itaj_ht} {$next_thre_ht}</div>
EOP;
    if (!$_conf['iphone']) {
        $read_header_ht .= '<hr>';
    }

    //==================================================================
    // ローカルDatを読み込んでHTML表示
    //==================================================================
    $aThread->resrange['nofirst'] = true;
    $GLOBALS['newres_to_show_flag'] = false;
    if ($aThread->rescount) {
        //$aThread->datToHtml(); // dat を html に変換表示
        include_once P2_LIB_DIR . '/showthread.class.php';
        include_once P2_LIB_DIR . '/showthreadk.class.php';
        $aShowThread =& new ShowThreadK($aThread);

        $read_cont_ht .= $aShowThread->getDatToHtml();

        unset($aShowThread);
    }

    //==================================================================
    // フッタ 表示
    //==================================================================
    //include($read_footer_inc);

    //----------------------------------------------
    // $read_footer_navi_new  続きを読む 新着レスの表示
    $newtime = date("gis");  // リンクをクリックしても再読込しない仕様に対抗するダミークエリー

    $info_st = "情";
    $delete_st = "削";
    $prev_st = "前";
    $next_st = "次";

    // 表示範囲
    if ($aThread->resrange['start'] == $aThread->resrange['to']) {
        $read_range_on = $aThread->resrange['start'];
    } else {
        $read_range_on = "{$aThread->resrange['start']}-{$aThread->resrange['to']}";
    }
    $read_range_ht = "{$read_range_on}/{$aThread->rescount}";

    $read_footer_navi_new = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->rescount}-&amp;nt={$newtime}{$_conf['k_at_a']}#r{$aThread->rescount}\">新着ﾚｽの表示</a>";

    if (!empty($_conf['disable_res'])) {
        $dores_ht = <<<EOP
<a href="{$motothre_url}" target="_blank">ﾚｽ</a>
EOP;
    } else {
        $dores_ht = <<<EOP
<a href="post_form.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;rescount={$aThread->rescount}{$ttitle_en_q}{$_conf['k_at_a']}">ﾚｽ</a>
EOP;
    }

    $spm_ht = <<<EOP
<a class="button" href="spm_k.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->ls}&spm_default={$aThread->resrange['to']}&amp;from_read_new=1{$_conf['k_at_a']}">特</a>
EOP;

    // ツールバー部分HTML =======
    if ($spmode) {
        $toolbar_itaj_ht = <<<EOP
(<a href="{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}{$key_q}{$_conf['k_at_a']}">{$itaj_hd}</a>)
EOP;
    }
    $toolbar_right_ht .= <<<EOTOOLBAR
<a href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$_conf['k_at_a']}">{$info_st}</a>
<a href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}&amp;dele=true{$_conf['k_at_a']}">{$delete_st}</a>
<a href="{$motothre_url}" target="_blank">元ｽﾚ</a>\n
EOTOOLBAR;

    $read_footer_ht = <<<EOP
<div id="ntt_bt{$newthre_num}" name="ntt_bt{$newthre_num}" class="read_new_footer">
{$read_range_ht}
{$spm_ht}<br>
<a href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;offline=1&amp;rescount={$aThread->rescount}{$_conf['k_at_a']}#r{$aThread->rescount}">{$aThread->ttitle_hd}</a> {$toolbar_itaj_ht}
<a class="button" href="#ntt{$newthre_num}">▲</a>
</div>
<hr>\n
EOP;

    // 透明あぼーんや表示数制限で新しいレス表示がない場合はスキップ
    if ($GLOBALS['newres_to_show_flag']) {
        echo $read_header_ht;
        echo $read_cont_ht;
        echo $read_footer_ht;
    }

    //==================================================================
    // key.idxの値設定
    //==================================================================
    if ($aThread->rescount) {

        $aThread->readnum = min($aThread->rescount, max(0, $data[5], $aThread->resrange['to']));

        $newline = $aThread->readnum + 1; // $newlineは廃止予定だが、旧互換用に念のため

        $sar = array($aThread->ttitle, $aThread->key, $data[2], $aThread->rescount, $aThread->modified,
                    $aThread->readnum, $data[6], $data[7], $data[8], $newline,
                    $data[10], $data[11], $aThread->datochiok);
        P2Util::recKeyIdx($aThread->keyidx, $sar); // key.idxに記録
    }

    unset($aThread);
}

//==================================================================
// ページフッタ表示
//==================================================================
$newthre_num++;

if (!$aThreadList->num) {
    $GLOBALS['matome_naipo'] = TRUE;
    echo "新着ﾚｽはないぽ";
    echo "<hr>";
}

if ($unum_limit > 0) {
    $unum_limit_at_a = "&amp;unum_limit={$unum_limit}";
} else {
    $unum_limit_at_a = '';
}

if (!isset($GLOBALS['rnum_all_range']) or $GLOBALS['rnum_all_range'] > 0 or !empty($GLOBALS['limit_to_eq_to'])) {
    if (!empty($GLOBALS['limit_to_eq_to'])) {
        $str = '新着まとめの更新/続き';
    } else {
        $str = '新まとめを更新';
    }
    echo <<<EOP
<div>
{$sb_ht_btm}の<a href="{$_conf['read_new_k_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}&amp;nt={$newtime}{$unum_limit_at_a}{$_conf['k_at_a']}" {$_conf['accesskey']}="{$_conf['k_accesskey']['next']}">{$_conf['k_accesskey']['next']}.{$str}</a>
<a class="button" id="bottom" name="bottom" {$_conf['accesskey']}="{$_conf['k_accesskey']['above']}" href="#above">{$_conf['k_accesskey']['above']}.▲</a>
</div>\n
EOP;
} else {
    echo <<<EOP
<div>
{$sb_ht_btm}の<a href="{$_conf['read_new_k_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}&amp;nt={$newtime}&amp;norefresh=1{$unum_limit_at_a}{$_conf['k_at_a']}" {$_conf['accesskey']}="{$_conf['k_accesskey']['next']}">{$_conf['k_accesskey']['next']}.新まとめの続き</a>
<a class="button" id="bottom" name="bottom" {$_conf['accesskey']}="{$_conf['k_accesskey']['above']}" href="#above">{$_conf['k_accesskey']['above']}.▲</a>
</div>\n
EOP;
}

echo '<hr>'.$_conf['k_to_index_ht']."\n";

// iPhone & ImageCache2
if ($_conf['iphone'] && $_conf['expack.ic2.enabled']) {
    require_once P2EX_LIB_DIR . '/ic2/loadconfig.inc.php';
    $ic2conf = ic2_loadconfig();
    if ($ic2conf['Thumb1']['width'] > 80) {
        include P2EX_LIB_DIR . '/ic2/templates/info-v.tpl.html';
    } else {
        include P2EX_LIB_DIR . '/ic2/templates/info-h.tpl.html';
    }
}

echo '</body></html>';

if (P2_READ_NEW_SAVE_MEMORY) {
    fwrite($read_new_tmp_fh, ob_get_flush());
} else {
    $read_new_html .= ob_get_flush();
}

// NGあぼーんを記録
NgAbornCtl::saveNgAborns();
