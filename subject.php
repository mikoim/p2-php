<?php
/*	p2 -  スレッドサブジェクト表示スクリプト
	フレーム分割画面、右上部分

	subject_new.php と兄弟なので、一緒に面倒をみること
*/

include_once './conf/conf.inc.php';  // 設定
require_once './p2util.class.php';	// p2用のユーティリティクラス
require_once './threadlist.class.php'; // スレッドリスト クラス
require_once './thread.class.php';	// スレッド クラス
require_once './filectl.class.php';

$debug = false;
$debug && include_once("./profiler.inc"); //
$debug && $prof = new Profiler( true ); //

authorize(); // ユーザ認証

//============================================================
// ■変数設定
//============================================================
$newtime = date('gis');

// ■ホスト、板、モード設定 =================================
if (isset($_GET['host'])) { $host = $_GET['host']; }
if (isset($_POST['host'])) { $host = $_POST['host']; }
if (isset($_GET['bbs'])) { $bbs = $_GET['bbs']; }
if (isset($_POST['bbs'])) { $bbs = $_POST['bbs']; }
if (isset($_GET['spmode'])) { $spmode = $_GET['spmode']; }
if (isset($_POST['spmode'])) { $spmode = $_POST['spmode']; }

if (isset($_GET['from'])) { $sb_disp_from = $_GET['from']; }
if (isset($_POST['from'])) { $sb_disp_from = $_POST['from']; }
if (!isset($sb_disp_from)) { $sb_disp_from = 1; }

// ■p2_setting 設定 ======================================
if (!empty($spmode)) {
	$p2_setting_txt = $_conf['pref_dir'].'/p2_setting_'.$spmode.'.txt';
} else {
	$datdir_host = P2Util::datdirOfHost($host);
	$p2_setting_txt = $datdir_host.'/'.$bbs.'/p2_setting.txt';
	$sb_keys_b_txt = $datdir_host.'/'.$bbs.'/p2_sb_keys_b.txt';
	$sb_keys_txt = $datdir_host.'/'.$bbs.'/p2_sb_keys.txt';

	if ($_GET['norefresh']) {
		if ($prepre_sb_cont = @file_get_contents($sb_keys_b_txt)) {
			$prepre_sb_keys = unserialize($prepre_sb_cont);
		}
	} else {
		if ($pre_sb_cont = @file_get_contents($sb_keys_txt)) {
			$pre_sb_keys = unserialize($pre_sb_cont);
		}
	}
		
}

// ■p2_setting 読み込み
$p2_setting = array();
if ($p2_setting_cont = @file_get_contents($p2_setting_txt)) {
	$p2_setting = unserialize($p2_setting_cont);
}

$viewnum_pre = $p2_setting['viewnum'];
$sort_pre = $p2_setting['sort'];
$itaj_pre = $p2_setting['itaj'];

if (isset($_GET['sb_view'])) { $sb_view = $_GET['sb_view']; }
if (isset($_POST['sb_view'])) { $sb_view = $_POST['sb_view']; }
if (empty($sb_view)) {$sb_view = "normal";}

if (isset($_GET['viewnum'])) { $p2_setting['viewnum'] = $_GET['viewnum']; }
if (isset($_POST['viewnum'])) { $p2_setting['viewnum'] = $_POST['viewnum']; }
if (!$p2_setting['viewnum']) { $p2_setting['viewnum'] = $_conf['display_threads_num']; } // デフォルト値

if (isset($_GET['sort'])) { $p2_setting['sort'] = $_GET['sort']; }
if (isset($_POST['sort'])) { $p2_setting['sort'] = $_POST['sort']; }

// ソートのデフォルト指定
if (empty($p2_setting['sort'])) {
	if (!empty($spmode)) {
		$p2_setting['sort'] = "midoku";	// 新着
	} else {
		if (!empty($_conf['sb_sort_ita'])) {
			$p2_setting['sort'] = $_conf['sb_sort_ita'];
		} else {
			$p2_setting['sort'] = "ikioi";	// 勢い
		}
	}
}

if (isset($_GET['itaj_en'])) { $p2_setting['itaj'] = base64_decode($_GET['itaj_en']); }

// ■表示スレッド数 ====================================
$threads_num_max = 2000;

if (empty($spmode) || $spmode == 'news') {
	$threads_num = $p2_setting['viewnum'];
} elseif ($spmode == 'recent') {
	$threads_num = $_conf['rct_rec_num'];
} elseif ($spmode == 'res_hist') {
	$threads_num = $_conf['res_hist_rec_num'];
} else {
	$threads_num = 2000;
}

if ($p2_setting['viewnum'] == 'all') { $threads_num = $threads_num_max; }
elseif ($sb_view == 'shinchaku') { $threads_num = $threads_num_max; }
elseif ($sb_view == 'edit') { $threads_num = $threads_num_max; }
elseif (isset($_GET['word'])) { $threads_num = $threads_num_max; }
elseif ($_conf['ktai']) { $threads_num = $threads_num_max; }

$abornoff_st = 'あぼーん解除';
$deletelog_st = 'ログを削除';

// {{{ ワードフィルタ ====================================
// 検索指定があれば
if (empty($_REQUEST['submit_refresh']) or !empty($_REQUEST['submit_kensaku'])) {
	if (isset($_GET['word'])) {
		$word = $_GET['word'];
	} elseif (isset($_POST['word'])) {
		$word = $_POST['word'];
	}
	if (isset($_GET['method'])) {
		$sb_filter_method = $_GET['method'];
	} elseif (isset($_POST['method'])) {
		$sb_filter_method = $_POST['method'];
	}

	if (preg_match('/^\.+$/', $word)) {
		$word = '';
	}
	if (strlen($word) > 0)  {
		require_once './strctl.class.php';
		$word_fm = StrCtl::wordForMatch($word, $sb_filter_method);
		if (P2_MBREGEX_AVAILABLE == 1) {
			$words_fm = @mb_split('\s+', $word_fm);
			$word_fm = @mb_ereg_replace('\s+', '|', $word_fm);
		} else {
			$words_fm = @preg_split('/\s+/', $word_fm);
			$word_fm = @preg_replace('/\s+/', '|', $word_fm);
		}
	}
}
// }}}

$nowtime = time();

//============================================================
// ■特殊な前置処理
//============================================================

// 削除
if (!empty($_GET['dele']) or ($_POST['submit'] == $deletelog_st)) {
	if ($host && $bbs) {
		include_once 'dele.inc.php';
		if ($_POST['checkedkeys']) {
			$dele_keys = $_POST['checkedkeys'];
		} else {
			$dele_keys = array($_GET['key']);
		}
		deleteLogs($host, $bbs, $dele_keys);
	}

// お気に入りスレッド
} elseif (isset($_GET['setfav']) && $_GET['key'] && $host && $bbs) {
	include_once 'setfav.inc.php';
	setFav($host, $bbs, $_GET['key'], $_GET['setfav']);

// 殿堂入り
} elseif (isset($_GET['setpal']) && $_GET['key'] && $host && $bbs) {
	include_once 'setpalace.inc.php';
	setPal($host, $bbs, $_GET['key'], $_GET['setpal']);

// あぼーんスレッド解除
} elseif (($_POST['submit'] == $abornoff_st) && $host && $bbs && $_POST['checkedkeys']) {
	include_once 'settaborn_off.inc.php';
	settaborn_off($host, $bbs, $_POST['checkedkeys']);

// スレッドあぼーん
} elseif (isset($_GET['taborn']) && $key && $host && $bbs) {
	include_once 'settaborn.inc.php';
	settaborn($host, $bbs, $key, $_GET['taborn']);
}

//============================================================
// ■メイン
//============================================================

$aThreadList = new ThreadList;

// ■板とモードのセット ===================================
if ($spmode) {
	if ($spmode == 'taborn' or $spmode == 'soko') {
		$aThreadList->setIta($host, $bbs, P2Util::getItaName($host, $bbs));
	}
	$aThreadList->setSpMode($spmode);	
} else {
	// if(!$p2_setting['itaj']){$p2_setting['itaj'] = P2Util::getItaName($host, $bbs);}
	$aThreadList->setIta($host, $bbs, $p2_setting['itaj']);
	
	//スレッドあぼーんリスト読込===================================
	$datdir_host = P2Util::datdirOfHost($aThreadList->host);
	$taborn_idx = $datdir_host."/".$aThreadList->bbs."/p2_threads_aborn.idx";

	$tabornlines = @file($taborn_idx);
	
	if (is_array($tabornlines)) {
		$ta_num = sizeof($tabornlines);
		foreach ($tabornlines as $l) {
			$data = explode('<>', rtrim($l));	
			$ta_keys[ $data[1] ] = true;
		}
	}

}

// ■ソースリスト読込 ==================================
$debug && $prof->startTimer("readList");
$lines = $aThreadList->readList();
$debug && $prof->startTimer("readList");

// ■お気にスレリスト 読込
$favlines = @file($_conf['favlist_file']);
if (is_array($favlines)) {
	foreach ($favlines as $l) {
		$data = explode('<>', rtrim($l));
		$fav_keys[ $data[1] ] = true;
	}
}

//============================================================
// ■それぞれの行解析
//============================================================

$linesize = sizeof($lines);

for ($x = 0; $x < $linesize; $x++) {

	$l = rtrim($lines[$x]);
	
	$aThread = new Thread;
	
	if ($aThreadList->spmode != 'taborn' and $aThreadList->spmode != 'soko') {
		$aThread->torder = $x + 1;
	}

	// ■データ読み込み
	// spmode
	if ($aThreadList->spmode) {
		switch ($aThreadList->spmode) {
	    case "recent": // 履歴
	        $aThread->getThreadInfoFromExtIdxLine($l);
			$aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
			if (!$aThread->itaj) {$aThread->itaj = $aThread->bbs;}
	        break;
	    case "res_hist": // 書き込み履歴
	        $aThread->getThreadInfoFromExtIdxLine($l);
			$aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
			if (!$aThread->itaj) {$aThread->itaj= $aThread->bbs;}
	        break;
	    case "fav": // お気に
	        $aThread->getThreadInfoFromExtIdxLine($l);
			$aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
			if (!$aThread->itaj) {$aThread->itaj = $aThread->bbs;}
	        break;
		case "taborn":	// スレッドあぼーん
			$la = explode('<>', $l);
			$aThread->key = $la[1];
			$aThread->host = $aThreadList->host;
			$aThread->bbs = $aThreadList->bbs;	
	        break;
		case "soko":	// dat倉庫
			$la = explode('<>', $l);
			$aThread->key = $la[1];
			$aThread->host = $aThreadList->host;
			$aThread->bbs = $aThreadList->bbs;	
	        break;
		case "palace":	// スレの殿堂
	        $aThread->getThreadInfoFromExtIdxLine($l);
			$aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
			if (!$aThread->itaj) {$aThread->itaj = $aThread->bbs;}
	        break;
	    case "news": // ニュースの勢い
	        $aThread->isonline = true;
			$aThread->key = $l['key'];
			$aThread->setTtitle($l['ttitle']);
			$aThread->rescount = $l['rescount'];
			$aThread->host = $l['host'];
			$aThread->bbs = $l['bbs'];

			$aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
			if (!$aThread->itaj) {$aThread->itaj = $aThread->bbs;}
	        break;
		}
		
	// subject (not spmode つまり普通の板)
	} else {
		$aThread->getThreadInfoFromSubjectTxtLine($l);
		$aThread->host = $aThreadList->host;
		$aThread->bbs = $aThreadList->bbs;
		if ($_GET['norefresh']) {
			if (!$prepre_sb_keys[$aThread->key]) { $aThread->new = true; }
		} else {
			if (!$pre_sb_keys[$aThread->key]) { $aThread->new = true; }
			$subject_keys[$aThread->key] = true;
		}
	}

	// hostもbbsもkeyも不明ならスキップ
	if (!($aThread->host && $aThread->bbs && $aThread->key)) {
		unset($aThread);
		continue;
	} 
	
	$debug && $prof->startTimer("word_filter_for_sb");
	// ワードフィルタ(for subject) ====================================
	if (!$aThreadList->spmode || $aThreadList->spmode == "news" and $word_fm) {
		$target = $aThread->ttitle;
		$sb_filter_match = true;
		if ($sb_filter_method == "and") {
			reset($words_fm);
			foreach ($words_fm as $word_fm_ao) {
				if (!StrCtl::filterMatch($word_fm_ao, $target)) {
					$sb_filter_match = false;
					break;
				}
			}
		} else {
			if (!StrCtl::filterMatch($word_fm, $target)) {
				$sb_filter_match = false;
			}
		}
		if (!$sb_filter_match) {
			unset($aThread);
			continue;
		} else {
			$mikke++;
			if ($_conf['ktai']) {
				$aThread->ttitle_ht = $aThread->ttitle_hd;
			} else {
				$aThread->ttitle_ht = StrCtl::filterMarking($word_fm, $aThread->ttitle_hd);
			}
		}
	}
	$debug && $prof->stopTimer("word_filter_for_sb");
	
	// ■スレッドあぼーんチェック =====================================
	$debug && $prof->startTimer("taborn_check_continue"); //
	if ($aThreadList->spmode != "taborn" and $ta_keys[$aThread->key]) { 
		unset($ta_keys[$aThread->key]);
		$debug && $prof->stopTimer("taborn_check_continue"); //
		continue; // あぼーんスレはスキップ
	}
	$debug && $prof->stopTimer("taborn_check_continue"); //
	
	$aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);
	// 既得スレッドデータをidxから取得
	$aThread->getThreadInfoFromIdx();


	$debug && $prof->startTimer('favlist_check');
	// favlistチェック =====================================
	// if ($x <= $threads_num) {
		if ($aThreadList->spmode != 'taborn' and $fav_keys[$aThread->key]) {
			$aThread->fav = 1;
			unset($fav_keys[$aThread->key]);
		}
	// }
	$debug && $prof->stopTimer('favlist_check');
	
	// ■ spmode(殿堂入り、newsを除く)なら	====================================
	if ($aThreadList->spmode && $aThreadList->spmode != "news" && $sb_view != "edit") { 
		
		// ■ subject.txtが未DLなら落としてデータを配列に格納
		if (!$subject_txts["$aThread->host/$aThread->bbs"]) {
			$datdir_host = P2Util::datdirOfHost($aThread->host);
			$subject_url = "http://{$aThread->host}/{$aThread->bbs}/subject.txt";
			$subjectfile = "{$datdir_host}/{$aThread->bbs}/subject.txt";
			FileCtl::mkdir_for($subjectfile); // 板ディレクトリが無ければ作る
			P2Util::subjectDownload($subject_url, $subjectfile);
			
			$debug && $prof->startTimer('subthre_read'); //
			if ($aThreadList->spmode == 'soko' or $aThreadList->spmode == 'taborn') {
			
				if (extension_loaded('zlib') and strstr($aThread->host, '.2ch.net')) {
					$sblines = @gzfile($subjectfile);
				} else {
					$sblines = @file($subjectfile);
				}
				if (is_array($sblines)) {
					$it = 1;
					foreach ($sblines as $asbl) {
						if (preg_match("/^([0-9]+)\.(dat|cgi)(,|<>)(.+) ?(\(|（)([0-9]+)(\)|）)/", $asbl, $matches)) {
							$akey = $matches[1];
							$subject_txts["$aThread->host/$aThread->bbs"][$akey]['ttitle'] = rtrim($matches[4]);
							$subject_txts["$aThread->host/$aThread->bbs"][$akey]['rescount'] = $matches[6];
							$subject_txts["$aThread->host/$aThread->bbs"][$akey]['torder'] = $it;
						}
						$it++;
					}
				}
				
			} else {
			
				if (extension_loaded('zlib') and strstr($aThread->host, '.2ch.net')) {
					$subject_txts["$aThread->host/$aThread->bbs"] = @gzfile($subjectfile);
				} else {
					$subject_txts["$aThread->host/$aThread->bbs"] = @file($subjectfile);
				}
				
			}
			$debug && $prof->stopTimer("subthre_read");//
		}

		$debug && $prof->startTimer("subthre_check");//
		// ■スレ情報取得 =============================
		if ($aThreadList->spmode == "soko" or $aThreadList->spmode == "taborn") {
		
			if ($subject_txts[$aThread->host."/".$aThread->bbs][$aThread->key]) {
			
				// 倉庫はオンラインを含まない
				if ($aThreadList->spmode == "soko") {
					unset($aThread);
					continue;
				} elseif ($aThreadList->spmode == "taborn") {
					// $aThread->getThreadInfoFromSubjectTxtLine($l); // subject.txt からスレ情報取得
					$aThread->isonline = true;
					$ttitle = $subject_txts["$aThread->host/$aThread->bbs"][$aThread->key]['ttitle'];
					$aThread->setTtitle($ttitle);
					$aThread->rescount = $subject_txts["$aThread->host/$aThread->bbs"][$aThread->key]['rescount'];
					if ($aThread->readnum) {
						$aThread->unum = $aThread->rescount - $aThread->readnum;
						// machi bbs はsageでsubjectの更新が行われないそうなので調整しておく
						if ($aThread->unum < 0) { $aThread->unum = 0; }
					}
					$aThread->torder = $subject_txts["$aThread->host/$aThread->bbs"][$aThread->key]['torder'];
				}

			}
			
		} else {
		
			if ($subject_txts[$aThread->host."/".$aThread->bbs]) {
				$it = 1;
				foreach ($subject_txts[$aThread->host."/".$aThread->bbs] as $l) {
					if (@preg_match("/^{$aThread->key}/",$l)) {
						// subject.txt からスレ情報取得
						$aThread->getThreadInfoFromSubjectTxtLine($l);
						break;
					}
					$it++;
				}
			}
		
		}
		$debug && $prof->stopTimer("subthre_check"); //
		
		if ($aThreadList->spmode == "taborn") {
			if (!$aThread->torder) { $aThread->torder = '-'; }
		}

		
		// ■新着のみ(for spmode) ===============================
		if ($sb_view == 'shinchaku' and !isset($_REQUEST['word'])) {
			if ($aThread->unum < 1) {
				unset($aThread);
				continue;
			}
		}
		
			
		// ■ワードフィルタ(for spmode) ==================================
		if ($word_fm) {
			$target = $aThread->ttitle;
			$sb_filter_match = true;
			if ($sb_filter_method == "and") {
				reset($words_fm);
				foreach ($words_fm as $word_fm_ao) {
					if (!StrCtl::filterMatch($word_fm_ao, $target)) {
						$sb_filter_match = false;
						break;
					}
				}
			} else {
				if (!StrCtl::filterMatch($word_fm, $target)) { $sb_filter_match = false; }
			}
			if (!$sb_filter_match) {
				unset($aThread);
				continue;
			} else {
				$mikke++;
				if ($_conf['ktai']) {
					$aThread->ttitle_ht = $aThread->ttitle_hd;
				} else {
					$$aThread->ttitle_ht = StrCtl::filterMarking($word_fm, $aThread->ttitle_hd);
				}
			}
		}
	}
	
	// subjexctからrescountが取れなかった場合は、gotnumを利用する。
	if ((!$aThread->rescount) and $aThread->gotnum) {
		$aThread->rescount = $aThread->gotnum;
	}
	if (!$aThread->ttitle_ht) { $aThread->ttitle_ht = $aThread->ttitle_hd; }
	
	// 新着あり
	if ($aThread->unum > 0) {
		$shinchaku_attayo = true;
		$shinchaku_num = $shinchaku_num + $aThread->unum; // 新着数set
	} elseif ($aThread->fav) { // お気にスレ
		;
	} elseif ($aThread->new) { // 新規スレ
		;
	// 既得スレ
	} elseif ($_conf['viewall_kitoku'] && $aThread->isKitoku()) {
		;
		
	} else {
		// 携帯、ニュースチェック以外で
		if (!$_conf['ktai'] and $spmode != "news") {
			// 指定数を越えていたらカット
			if ($x >= $threads_num) {
				unset($aThread);
				continue;
			}
		}
	}
	
	// 新着ソートの便宜上 （未取得スレッドの）unum をセット調整
	if (!isset($aThread->unum)) {
		if ($aThreadList->spmode == "recent" or $aThreadList->spmode == "res_hist" or $aThreadList->spmode == "taborn") {
			$aThread->unum = -0.1;
		} else {
			$aThread->unum = $_conf['sort_zero_adjust'];
		}
	}
	
	// 勢いのセット
	$aThread->setDayRes($nowtime);
	
	// 生存数set
	if ($aThread->isonline) { $online_num++; }
	
	// ■リストに追加 ==============================================
	$aThreadList->addThread($aThread);
	unset($aThread);
}

// ■既にdat落ちしているスレは自動的にあぼーんを解除する =========================
if (!$aThreadList->spmode and !$word and $aThreadList->threads and $ta_keys) {
	include_once 'settaborn_off.inc.php';
	//echo sizeof($ta_keys)."*<br>";
	$ta_vkeys = array_keys($ta_keys);
	settaborn_off($aThreadList->host, $aThreadList->bbs, $ta_vkeys);
	foreach ($ta_vkeys as $k) {
		$ta_num--;
		if ($k) {
			$ks .= "key:$k ";
		}
	}
	$ks && $_info_msg_ht .= "<div class=\"info\">　p2 info: DAT落ちしたスレッドあぼーんを自動解除しました - $ks</div>";
}

//============================================================
// ソート
//============================================================
if ($aThreadList->threads) {
	if ($p2_setting['sort'] == "midoku" or $_conf['ktai']) {
		if ($aThreadList->spmode == "soko") {
			usort($aThreadList->threads, "cmp_key");
		} else {
			usort($aThreadList->threads, "cmp_midoku");
		}
	}
	elseif ($p2_setting['sort'] == "res") { usort($aThreadList->threads, "cmp_res"); }
	elseif ($p2_setting['sort'] == "title") { usort($aThreadList->threads, "cmp_title"); }
	elseif ($p2_setting['sort'] == "ita") { usort($aThreadList->threads, "cmp_ita"); }
	elseif ($p2_setting['sort'] == "ikioi" || $p2_setting['sort'] == "spd") {
		if ($_conf['cmp_dayres_midoku']) {
			usort($aThreadList->threads, "cmp_dayres_midoku");
		} else {
			usort($aThreadList->threads, "cmp_dayres");
		}
	}
	elseif ($p2_setting['sort'] == "bd") { usort($aThreadList->threads, "cmp_key"); }
	elseif ($p2_setting['sort'] == "fav") { usort($aThreadList->threads, "cmp_fav"); }
	if ($p2_setting['sort'] == "no") {
		if ($aThreadList->spmode == "soko") {
			usort($aThreadList->threads, "cmp_key");
		} else {
			usort($aThreadList->threads, "cmp_no");
		}
	}
}

// ニュースチェック
if ($aThreadList->spmode == "news") {
	for ($i = 0; $i < $threads_num ; $i++) {
		if ($aThreadList->threads) {
			$newthreads[] = array_shift($aThreadList->threads);
		}
	}
	$aThreadList->threads = $newthreads;
	$aThreadList->num = sizeof($aThreadList->threads);
}

//===============================================================
// ■プリント
//===============================================================
// ■携帯
if ($_conf['ktai']) {
	
	// 倉庫にtorder付与 ================
	if ($aThreadList->spmode == "soko") {
		if ($aThreadList->threads) {
			$soko_torder = 1;
			$newthreads = array();
			foreach ($aThreadList->threads as $at) {
				$at->torder = $soko_torder++;
				$newthreads[] = $at;
			}
			$aThreadList->threads = $newthreads;
		}
	}

	// 表示数制限 =====	
	$aThreadList->num = sizeof($aThreadList->threads); // なんとなく念のため
	$sb_disp_all_num = $aThreadList->num;
	
	$disp_navi = P2Util::getListNaviRange($sb_disp_from , $_conf['k_sb_disp_range'], $sb_disp_all_num);

	$newthreads = array();
	for ($i = $disp_navi['from']; $i <= $disp_navi['end']; $i++) {
		if ($aThreadList->threads[$i-1]) {
			$newthreads[] = $aThreadList->threads[$i-1];
		}
	}
	$aThreadList->threads = $newthreads;
	$aThreadList->num = sizeof($aThreadList->threads);

	// ヘッダプリント
	include './sb_header_k.inc.php';
	
	require_once './sb_print_k.inc.php'; //スレッドサブジェクトメイン部分HTML表示関数
	sb_print_k($aThreadList);
	
	// フッタプリント
	include './sb_footer_k.inc.php';
		
} else {
	//============================================================
	// ヘッダHTMLを表示
	//============================================================
	include($sb_header_inc);
	flush();
	
	//============================================================
	// スレッドサブジェクトメイン部分HTML表示
	//============================================================
	require_once './sb_print.inc.php'; // スレッドサブジェクトメイン部分HTML表示関数

	$debug && $prof->startTimer("sb_print");
	sb_print($aThreadList);
	$debug && $prof->stopTimer("sb_print");
	
	$debug && $prof->printTimers(true);
	
	//============================================================
	// フッタHTML表示
	//============================================================
	include($sb_footer_inc);
}

//============================================================
// p2_setting 記録
//============================================================
if ($viewnum_pre != $p2_setting['viewnum'] or $sort_pre != $p2_setting['sort'] or $itaj_pre != $p2_setting['itaj']) {
	FileCtl::make_datafile($p2_setting_txt, $_conf['p2_perm']);
	if ($p2_setting) {$p2_setting_cont = serialize($p2_setting);}
	if ($p2_setting_cont) {
		$fp = fopen($p2_setting_txt, 'wb') or die("Error: {$p2_setting_txt} を更新できませんでした");
		@flock($fp, LOCK_EX);
		fputs($fp, $p2_setting_cont);
		@flock($fp, LOCK_UN);
		fclose($fp);
	}
}

//============================================================
// $subject_keys をシリアライズして保存
//============================================================
//if(file_exists($sb_keys_b_txt)){ unlink($sb_keys_b_txt); }
if ($subject_keys) {
	if (file_exists($sb_keys_txt)) {
		copy($sb_keys_txt, $sb_keys_b_txt);
	} else {
		FileCtl::make_datafile($sb_keys_txt, $_conf['p2_perm']);
	}
	if ($subject_keys) {$sb_keys_cont = serialize($subject_keys);}
	if ($sb_keys_cont) {
		$fp = fopen($sb_keys_txt, 'wb') or die("Error: {$sb_keys_txt} を更新できませんでした");
		@flock($fp, LOCK_EX);
		fputs($fp, $sb_keys_cont);
		@flock($fp, LOCK_UN);
		fclose($fp);
	}
}


//============================================================
// ■ソート関数
//============================================================

/**
 * 新着ソート
 */
function cmp_midoku($a, $b)
{
	if ($a->new == $b->new) {
		if (($a->unum == $b->unum) or ($a->unum < 0) && ($b->unum < 0)) {
			return ($a->torder > $b->torder) ? 1 : -1;
		} else {
	 	   return ($a->unum < $b->unum) ? 1 : -1;
		}
	} else {
		return ($a->new < $b->new) ? 1 : -1;
	}
}

/**
 * レス数 ソート
 */
function cmp_res ($a, $b)
{ 
    if ($a->rescount == $b->rescount) {
		return ($a->torder > $b->torder) ? 1 : -1;
    } else {
		return ($a->rescount < $b->rescount) ? 1 : -1;
	}
}

/**
 * タイトル ソート
 */
function cmp_title($a, $b)
{ 
    if ($a->ttitle == $b->ttitle) {
		return ($a->torder > $b->torder) ? 1 : -1;
    } else {
		return strcmp($a->ttitle,$b->ttitle);
	}
}

/**
 * 板 ソート
 */
function cmp_ita($a, $b)
{ 
    if ($a->itaj == $b->itaj) {
		return ($a->torder > $b->torder) ? 1 : -1;
    } else {
		return strcmp($a->itaj,$b->itaj);
	}
}

/**
 * お気に ソート
 */
function cmp_fav($a, $b)
{ 
    if ($a->fav == $b->fav) {
		return ($a->torder > $b->torder) ? 1 : -1;
    } else {
		return strcmp($b->fav, $a->fav);
	}
}

/**
 * 勢いソート（新着レス優先）
 */
function cmp_dayres_midoku($a, $b)
{
	if ($a->new == $b->new) {
		if (($a->unum == $b->unum) or ($a->unum >= 1) && ($b->unum >= 1)) {
			return ($a->dayres < $b->dayres) ? 1 : -1;
		} else {
			return ($a->unum < $b->unum) ? 1 : -1;
		}
	} else {
		return ($a->new < $b->new) ? 1 : -1;
	}
}

/**
 * 勢いソート
 */
function cmp_dayres($a, $b)
{
	if ($a->new == $b->new) {
		return ($a->dayres < $b->dayres) ? 1 : -1;
	} else {
		return ($a->new < $b->new) ? 1 : -1;
	}
}

/**
 * key ソート
 */
function cmp_key($a, $b)
{
    return ($a->key < $b->key) ? 1 : -1;
}

/**
 * No. ソート
 */
function cmp_no ($a, $b)
{ 
	return ($a->torder > $b->torder) ? 1 : -1;
} 

?>