<?php
/*
	+live - 実況用スレッド表示 ../ShowThreadPc.php より読み込まれる
*/

// オートリロードの板で新着レス先頭に目印ラインを挿入
$live_newline = "<table id=\"{$res_id}\" class=\"res\" cellspacing=\"2\" cellpadding=\"0\" style=\"border-top: {$STYLE['live_b_n']};\" width=\"100%\"><tr class=\"res-header\">";
$live_oldline = "<table id=\"{$res_id}\" class=\"res\" cellspacing=\"2\" cellpadding=\"0\" style=\"border-top: {$STYLE['live_b_l']};\" width=\"100%\"><tr class=\"res-header\">";
// 
$live_td = "<td class=\"live_res\" style=\"color:{$STYLE['read_color']}; font-size:{$STYLE['live_font-size']}; word-wrap: break-word;\" width=\"250px\" valign=\"top\">";
// 新着レスの番号色
$live_newnum = "<span class=\"spmSW\"{$spmeh}><b style=\"color:{$STYLE['read_newres_color']};\">{$i}</b></span>";
$live_oldnum = "<span class=\"spmSW\"{$spmeh}>{$i}</span>";

if ($this->thread->onthefly) {
	$GLOBALS['newres_to_show_flag'] = true;
	// 番号 (オンザフライ)
	$tores .= "{$live_oldline}{$live_td}<span class=\"ontheflyresorder spmSW\"{$spmeh}>{$i}</span>";
} elseif ($i == 1) {
	// 番号 (1)
	if ($this->thread->readnum > 1) {
		$tores .= "{$live_oldline}{$live_td}{$live_oldnum}";
	} else {
		$tores .= "{$live_oldline}{$live_td}{$live_newnum}";
	}
} elseif ($i == $this->thread->readnum +1) {
	$GLOBALS['newres_to_show_flag'] = true;
	// 番号 (新着レス時 先頭)
	if (array_key_exists('live', $_GET) && $_GET['live']) {
		$tores .= "{$live_newline}{$live_td}{$live_newnum}";
	} else {
		$tores .= "{$live_oldline}{$live_td}{$live_newnum}";
	}
} elseif ($i > $this->thread->readnum) {
	// 番号 (新着レス時 後続)
	$tores .= "{$live_oldline}{$live_td}{$live_newnum}";
} elseif ($_conf['expack.spm.enabled']) {
	// 番号 (SPM)
	$tores .= "{$live_oldline}{$live_td}{$live_oldnum}";
} else {
	// 番号
	$tores .= "{$live_oldline}{$live_td}{$i}";
}

// 名前
$tores .= preg_replace('{<b>[ ]*</b>}i', '', "&nbsp;<span class=\"name\"><b>{$name}</b></span> : ");

// ID
$tores .= "{$date_id}";

if ($this->am_side_of_id) {
	$tores .= ' ' . $this->activeMona->getMona($res_id);
}

// メール
$tores .= "&nbsp;{$mail}";

// 被レスリスト(縦形式)
if ($_conf['backlink_list'] == 1 || $_conf['backlink_list'] > 2) {
    $tores .= $this->_quotebackListHtml($i, 1);
}

$tores .= "</td>";

// 仕切 & レスボタン
$stall_05 = "<td width=\"5px\"  style=\"border-left: {$STYLE['live_b_s']};\">&nbsp;</td>";
$stall_30 = "<td width=\"32px\" style=\"border-left: {$STYLE['live_b_s']};\">&nbsp;{$res_button}</td>";
if (array_key_exists('live', $_GET) && $_GET['live']) {
	$tores .= "$stall_30";
} else {
	$tores .= "$stall_05";
}

// 内容
$tores .= "<td class=\"live_res\" id=\"{$msg_id}\" class=\"{$msg_class}\"{$res_dblclc} width=\"\" style=\"color:{$STYLE['read_color']}; font-size: {$STYLE['read_fontsize']}; word-wrap: break-word;\">{$msg}　";

if ($_conf['backlink_block'] > 0) {
    // 被参照ブロック表示用にonclickを設定
    $tores .= "<div onclick=\"toggleResBlk(event, this, " . $_conf['backlink_block_readmark'] . ")\">\n";
} else {
    $tores .= "<div>\n";
}

// 被レス展開用ブロック
if ($_conf['backlink_block'] > 0) {
    $backlinks = $this->_getBacklinkComment($i);
    if (strlen($backlinks)) {
        $tores .= '<div class="resblock"><img src="img/btn_plus.gif" width="15" height="15" align="left"></div>';
        $tores .= $backlinks;
    }
}
// 被レスリスト(横形式)
if ($_conf['backlink_list'] == 2 || $_conf['backlink_list'] > 2) {
    $tores .= $this->_quotebackListHtml($i, 2, false);
}

$tores .= "</div>";
$tores .= "</td>";

// テーブル終了
$tores .= "</tr></table>\n";

// レスポップアップ用引用
//$tores .= $rpop;

?>