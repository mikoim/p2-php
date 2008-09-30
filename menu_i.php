<?php
/**
 * rep2 - iPhone/iPod Touch専用メニュー (要iui)
 *
 * @link http://code.google.com/p/iui/
 */

include_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/menu_iphone.inc.php';

$_login->authorize(); //ユーザ認証

if (isset($_GET['cateid'])) {
    menu_iphone_ajax('menu_iphone_show_board_menu', (int)$_GET['cateid']);
    exit;
}

if (isset($_POST['word'])) {
    $word = menu_iphone_unicode_urldecode($_POST['word']);
    if (preg_match('/^\.+$/', $word)) {
        $word = '';
    }

    if (strlen($word) > 0) {
        // and検索
        include_once P2_LIB_DIR . '/strctl.class.php';
        $word = StrCtl::wordForMatch($word, 'and');
        if (P2_MBREGEX_AVAILABLE == 1) {
            $GLOBALS['words_fm'] = @mb_split('\s+', $word);
            $GLOBALS['word_fm'] = @mb_ereg_replace('\s+', '|', $word);
        } else {
            $GLOBALS['words_fm'] = @preg_split('/\s+/', $word);
            $GLOBALS['word_fm'] = @preg_replace('/\s+/', '|', $word);
        }

        menu_iphone_ajax('menu_iphone_show_matched_boards', $word);
    } else {
        header('Content-Type: application/xml; charset=UTF-8');
        echo mb_convert_encoding('<div class="panel">無効なキーワードです。</div>', 'UTF-8', 'CP932');
    }
    exit;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja">
<head>
    <meta http-equiv="Content-Type" content="application/xhtml+xml; charset=Shift_JIS" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=yes" />
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW" />
    <title>rep2</title>
    <script type="application/x-javascript" src="iui/iui.js"></script>
    <script type="application/x-javascript" src="js/menu_i.js"></script>
    <link rel="stylesheet" type="text/css" href="iui/iui.css" />
    <link rel="stylesheet" type="text/css" href="css/menu_i.css" />
    <link rel="apple-touch-icon" type="image/png" href="img/touch-icon/p2-serif.png" />
</head>
<body>

<div class="toolbar">
    <h1 id="pageTitle"></h1>
    <a id="backButton" class="button" style="z-index:2" href="#"></a>
    <a class="button leftButton" href="#boardSearch">板</a>
    <a class="button" href="#threadSearch">ｽﾚ</a>
</div>

<!-- {{{ トップメニュー -->
<ul id="top" title="rep2" selected="true">
<?php if ($_info_msg_ht) { ?>
    <li><a href="#info_msg" class="color-r">エラー</a></li>
<?php } ?>
    <?php echo menu_iphone_open_in_tab(); ?>

    <li class="group">リスト</li>
<?php if ($_conf['expack.misc.multi_favs']) { ?>
    <li><a href="#fav">お気にスレ</a></li>
<?php } else { ?>
    <?php /* <li><a href="subject.php?spmode=fav&amp;sb_view=shinchaku" target="_self">お気にスレの新着</a></li> */ ?>
    <li><a href="subject.php?spmode=fav" target="_self">お気にスレ</a></li>
<?php } ?>
    <li><a href="#favita">お気に板</a></li>
    <li><a href="menu_i.php?cateid=0">板リスト</a></li>
    <li><a href="subject.php?spmode=palace&amp;norefresh=1" target="_self">スレの殿堂</a></li>

    <li class="group">履歴</li>
    <?php /* <li><a href="subject.php?spmode=recent&amp;sb_view=shinchaku" target="_self">最近読んだスレの新着</a></li> */ ?>
    <li><a href="subject.php?spmode=recent" target="_self">最近読んだスレ</a></li>
<?php if ($_conf['res_hist_rec_num']) { ?>
    <li><a href="subject.php?spmode=res_hist" target="_self">書き込み履歴</a></li>
<?php if ($_conf['res_write_rec']) { ?>
    <li><a href="read_res_hist.php" target="_self">書き込み履歴の内容</a></li>
<?php } } ?>

    <li class="group">expack</li>
<?php if ($_conf['expack.rss.enabled']) { ?>
    <li><a href="#rss">RSS</a></li>
<?php } ?>
<?php if ($_conf['expack.ic2.enabled'] == 2 || $_conf['expack.ic2.enabled'] == 3) { ?>
    <li><a href="iv2.php" target="_self">画像キャッシュ一覧</a></li>
<?php } ?>
    <li><a href="#tgrep">スレッド検索</a></li>

    <li class="group">管理</li>
    <li><a href="editpref.php" target="_self">設定管理</a></li>
    <li><a href="setting.php" target="_self">ログイン管理</a></li>
    <li><a href="#login_info">ログイン情報</a></li>
</ul>
<!-- }}} -->

<?php
// エラー
if ($_info_msg_ht) { 
    echo '<div id="info_msg" class="panel" title="エラー">', $_info_msg_ht, '</div>';
}

if ($_conf['expack.misc.multi_favs']) {
    // {{{ お気にスレ

    $favlist = FavSetManager::getFavSetTitles('m_favlist_set');
    $fav_elems = '';
    $fav_new_elems = '';
    $fav_elem_prefix = '';

    foreach ($favlist as $no => $name) {
        if (strlen($name) == 0) {
            $name = ($no ? "お気にスレ{$no}" : 'お気にスレ');
        }
        $fav_url = "subject.php?spmode=fav&amp;m_favlist_set={$no}";
        $fav_elems .= "<li><a href=\"{$fav_url}\" target=\"_self\">{$name}</a></li>";
        //$fav_new_elems .= "<li><a href=\"{$fav_url}&amp;sb_view=shinchaku\" target=\"_self\">{$name}</a></li>";
    }

    echo '<ul id="fav" title="お気にスレ">';
    echo menu_iphone_open_in_tab();
    //echo '<li class="group">新着</li>';
    //echo $fav_new_elems;
    //echo '<li class="group">全て</li>';
    echo $fav_elems;
    echo "</ul>\n";

    // }}}
    // {{{ お気に板

    $favita = FavSetManager::getFavSetTitles('m_favita_set');

    echo '<ul id="favita" title="お気に板">';

    foreach ($favita as $no => $name) {
        if (strlen($name) == 0) {
            $favita[$no] = $name = ($no ? "お気に板{$no}" : 'お気に板');
        }
        echo "<li><a href=\"#favita{$no}\">{$name}</a></li>";
    }

    echo "</ul>\n";

    $orig_favita_path = $_conf['favita_path'];

    foreach ($favita as $no => $name) {
        $_conf['favita_path'] = $_conf['pref_dir'] . '/'
            . ($no ? "p2_favita{$no}.brd" : 'p2_favita.brd');
        menu_iphone_show_favorite_boards($name, $no);
    }

    $_conf['favita_path'] = $orig_favita_path;

    // }}}
    // {{{ RSS

    if ($_conf['expack.rss.enabled']) { 
        $rss = FavSetManager::getFavSetTitles('m_rss_set');

        echo '<ul id="rss" title="RSS">';

        foreach ($rss as $no => $name) {
            if (strlen($name) == 0) {
                $rss[$no] = $name = ($no ? "RSS{$no}" : 'RSS');
            }
            echo "<li><a href=\"#rss{$no}\">{$name}</a></li>";
        }

        echo "</ul>\n";

        $orig_rss_setting_path = $_conf['expack.rss.setting_path'];

        foreach ($rss as $no => $name) {
            $_conf['expack.rss.setting_path'] = $_conf['pref_dir'] . '/'
                    . ($no ? "p2_rss{$no}.txt" : 'p2_rss.txt');
            menu_iphone_show_feed_list($name, $no);
        }

        $_conf['expack.rss.setting_pat'] = $orig_rss_setting_path;
    }

    // }}}
} else {
    menu_iphone_show_favorite_boards('お気に板');

    if ($_conf['expack.rss.enabled']) { 
        menu_iphone_show_feed_list('RSS');
    }
}
?>

<!-- {{{ ログイン情報 -->
<div id="login_info" class="panel" title="ログイン情報">
<h2>認証ユーザ</h2>
<p><strong><?php echo $_login->user; ?></strong> - <?php echo date('Y/m/d (D) G:i:s'); ?></p>
<?php if ($_conf['login_log_rec'] && $_conf['last_login_log_show']) { ?>
<h2>前回のログイン</h2>
<pre style="word-wrap:break-word;word-break:break-all"><?php
if (($log = P2Util::getLastAccessLog($_conf['login_log_file'])) !== false) {
    $log_hd = array_map('htmlspecialchars', $log);
    echo <<<EOP
<strong>DATE:</strong> {$log_hd['date']}
<strong>USER:</strong> {$log_hd['user']}
<strong>  IP:</strong> {$log_hd['ip']}
<strong>HOST:</strong> {$log_hd['host']}
<strong>  UA:</strong> {$log_hd['ua']}
<strong>REFERER:</strong> {$log_hd['referer']}
EOP;
}
?></pre>
<?php } ?>
</div>
<!-- }}} -->

<!-- {{{ スレッド検索 -->
<ul id="tgrep" title="スレッド検索">
    <li><a href="#tgrep_info">スレッド検索について</a></li>
    <li class="group">クイックサーチ</li>
    <?php include_once P2EX_LIB_DIR . '/tgrep/menu_quick.inc.php'; ?>
    <li class="group">検索履歴</li>
    <?php include_once P2EX_LIB_DIR . '/tgrep/menu_recent.inc.php'; ?>
</ul>
<!-- }}} -->

<!-- {{{ スレッド検索について -->
<div id="tgrep_info" class="panel" title="tGrepについて">
<ul>
    <li>rep2 機能拡張パックのスレッド検索は tGrep (<a href="http://page2.xrea.jp/tgrep/" target="_blank">http://page2.xrea.jp/tgrep/</a>) を利用しています。</li>
    <li>iPhoneではメニュー右上の「ｽﾚ」ボタンをタップして現れるダイアログから検索します。</li>
    <li>キーワードはスペース区切りで3つまで指定でき、すべてを含むものが抽出されます。</li>
    <li>2つ目以降のキーワードで頭に - (半角マイナス) をつけると、それを含まないものが抽出されます。</li>
    <li>&quot; または &#39; で囲まれた部分は一つのキーワードとして扱われます。</li>
    <li>キーワードの全角半角、大文字小文字は無視されます。</li>
    <li>データベースの更新は3時間に1回で、レス数などは更新時点での値です。</li>
</ul>
</div>
<!-- }}} -->

<!-- {{{ 板検索ダイアログ -->
<form id="boardSearch" class="dialog"
  method="post" action="menu_i.php"
  accept-charset="<?php echo $_conf['accept_charset']; ?>">
<fieldset>
    <h1>板検索</h1>
    <a class="button leftButton" type="cancel">取消</a>
    <a class="button blueButton" type="submit">検索</a>
    <label>word:</label>
    <input type="text" name="word" />
</fieldset>
</form>
<!-- }}} -->

<!-- {{{ スレッド検索ダイアログ -->
<form id="threadSearch" class="dialog"
  method="post" action="tgrepc.php"
  accept-charset="<?php echo $_conf['accept_charset']; ?>">
<fieldset>
    <h1>スレッド検索</h1>
    <a class="button leftButton" type="cancel">取消</a>
    <a class="button blueButton" type="submit">検索</a>
    <label>word:</label>
    <input type="text" name="iq" />
</fieldset>
</form>
<!-- }}} -->

</body>
</html>
<?php

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
