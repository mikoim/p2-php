<?php
/*
	+live - デフォルトスレッド表示 ../ShowThreadPc.php より読み込まれる
*/
        if ($_conf['backlink_block'] > 0) {
            // 被参照ブロック表示用にonclickを設定
            $tores .= "<div id=\"{$res_id}\" class=\"res\" onclick=\"toggleResBlk(event, this, " . $_conf['backlink_block_readmark'] . ")\">\n";
        } else {
            $tores .= "<div id=\"{$res_id}\" class=\"res\">\n";
        }
        $tores .= "<div class=\"res-header\">";

        if ($this->thread->onthefly) {
            $GLOBALS['newres_to_show_flag'] = true;
            //番号（オンザフライ時）
            $tores .= "<span class=\"ontheflyresorder spmSW\"{$spmeh}>{$i}</span> : ";
        } elseif ($i > $this->thread->readnum) {
            $GLOBALS['newres_to_show_flag'] = true;
            // 番号（新着レス時）
            $tores .= "<span style=\"color:{$STYLE['read_newres_color']}\" class=\"spmSW\"{$spmeh}>{$i}</span> : ";
        } elseif ($_conf['expack.spm.enabled']) {
            // 番号（SPM）
            $tores .= "<span class=\"spmSW\"{$spmeh}>{$i}</span> : ";
        } else {
            // 番号
            $tores .= "{$i} : ";
        }

// レスボタン
if (array_key_exists('live', $_GET) && $_GET['live']) {
	$tores .= "$res_button";
}

        // 名前
        $tores .= preg_replace('{<b>[ ]*</b>}i', '', "<span class=\"name\"><b>{$name}</b></span> : ");

        // メール
        if ($mail) {
            if (strpos($mail, 'sage') !== false && $STYLE['read_mail_sage_color']) {
                $tores .= "<span class=\"sage\">{$mail}</span> : ";
            } elseif ($STYLE['read_mail_color']) {
                $tores .= "<span class=\"mail\">{$mail}</span> : ";
            } else {
                $tores .= $mail . ' : ';
            }
        }

        $tores .= $date_id; // 日付とID
        if ($this->am_side_of_id) {
            $tores .= ' ' . $this->activeMona->getMona($msg_id);
        }
        $tores .= "</div>\n"; // res-headerを閉じる

        // 被レスリスト(縦形式)
        if ($_conf['backlink_list'] == 1 || $_conf['backlink_list'] > 2) {
            $tores .= $this->_quotebackListHtml($i, 1);
        }

        $tores .= "<div id=\"{$msg_id}\" class=\"{$msg_class}\">{$msg}</div>\n"; // 内容
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
        $tores .= "</div>\n";

//      $tores .= $rpop; // レスポップアップ用引用

?>