<?php
/**
 *  rep2 - 書き込みフォーム
 */

if (!isset($popup)) {
    $popup = 0;
}
if (!isset($newthread_hidden_ht)) {
    $newthread_hidden_ht = '';
}
if (!isset($readnew_hidden_ht)) {
    $readnew_hidden_ht = '';
}

// 公式p2を使ったスレ立ては未実装 (P2Client.php)
if ($newthread_hidden_ht !== '') {
    $htm['p2res'] = '';
}

// レスアンカー
if ($q_resnum) {
	$hd['MESSAGE'] = "&gt;&gt;" . $q_resnum . "\r\n";
} else {
    $htm['k_br'] = '';
    if ($_conf['expack.editor.dpreview']) {
        $htm['kaiko_on_js_fmt'] = ' onfocus="%1$s" onkeyup="if(%2$s){%1$s}DPSetMsg()"';
    } else {
        $htm['kaiko_on_js_fmt'] = ' onfocus="%1$s" onkeyup="if(%2$s){%1$s}"';
    }
    $htm['name_label'] = '<label for="FROM">名前</label>：';
    $htm['mail_label'] = '<label for="mail">E-mail</label>：';
    $htm['name_extra_at'] = ' tabindex="1"';
    $htm['mail_extra_at'] = ' tabindex="2"';
    $htm['msg_extra_at'] = ' tabindex="3"';
    $htm['submit_extra_at'] = ' tabindex="4"';
	$hd['MESSAGE'] = "";
}

$ttitle_len = mb_strlen("$ttitle");
$ttitle = htmlspecialchars_decode($ttitle, ENT_QUOTES);
$ttitle = mb_convert_kana($ttitle, 'rnas');

if ($ttitle_len > 15) { // スレタイが15文字以上の場合は短縮
	$ttitle_pfi = mb_substr($ttitle, 0, 14) ."…";
} else {
	$ttitle_pfi = "$ttitle";
}

// +Wiki:sambaタイマー
if ($_conf['wiki.samba_timer']) {
    require_once P2_LIB_DIR . '/wiki/Samba.php';
    $samba = new Samba();
    $htm['samba'] .= $samba->createTimer($samba->getSamba($host, $bbs));
} else {
    $htm['samba'] = '';
}

// 下書き保存
$savedraft = '';
if ((!$_conf['ktai'] && $_conf['expack.editor.savedraft'] != 0) ||
    ($_conf['iphone'] && $_conf['expack.editor.mobile.savedraft'] != 0)) {
    $savedraft = <<<EOP
<input id="post_draft_button" type="button" value="下書き保存" onclick="DraftKakiko.saveDraftForm(this.form)">
<span id="post_draft_msg" class="autosave-info"></span>
EOP;
} elseif ($_conf['ktai'] && $_conf['expack.editor.mobile.savedraft']) {
    $savedraft = <<<EOP
<input type="submit" name="savedraft" value="下書保存">
EOP;
}

// 文字コード判定用文字列を先頭に仕込むことでmb_convert_variables()の自動判定を助ける
$htm['post_form'] = <<<EOP
<form id="resform" method="POST" action="./post.php" accept-charset="{$_conf['accept_charset']}"{$onsubmit_at}>
<b class="thre_title" title="{$ttitle}">&nbsp;{$ttitle_pfi}&nbsp;</b>
{$htm['maru_post']}
{$htm['name_label']}<input id="FROM" name="FROM" type="text" value="{$hd['FROM']}"{$name_size_at}{$htm['name_extra_at']}>
{$htm['mail_label']}<input id="mail" name="mail" type="text" value="{$hd['mail']}" size ="10" {$on_check_sage}{$htm['mail_extra_at']}>
{$htm['sage_cb']}
{$htm['options']}
{$htm['src_fix']}
{$htm['block_submit']}
<span id="write_reg_ato"></span>
<b class="thre_title" id="write_regulation"></b>
<span id="write_reg_byou"></span>
{$htm['beres']}
{$htm['p2res']}
{$htm['samba']}
{$htm['k_br']}{$savedraft}
{$upload_form}
<br>
<textarea id="MESSAGE" name="MESSAGE" style="width: 95%; height: 55%;" wrap="{$wrap_at}"{$htm['kaiko_on_js']}{$htm['msg_extra_at']}>{$hd['MESSAGE']}</textarea>

<input type="hidden" name="bbs" value="{$bbs}">
<input type="hidden" name="key" value="{$key}">
<input type="hidden" name="time" value="{$time}">

<input type="hidden" name="host" value="{$host}">
<input type="hidden" name="popup" value="{$popup}">
<input type="hidden" name="rescount" value="{$rescount}">
<input type="hidden" name="ttitle_en" value="{$ttitle_en}">
<input type="hidden" name="csrfid" value="{$csrfid}">
<input type="hidden" name="live" value="1">
{$newthread_hidden_ht}{$readnew_hidden_ht}
{$_conf['detect_hint_input_ht']}
</form>
EOP;

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
