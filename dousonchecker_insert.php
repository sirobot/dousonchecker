<?php
// 同村チェッカー共通部分の抽出
require_once('simplehtmldom/simple_html_dom.php');

$path = "/home/vage/siro_common/php";
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
require_once ('db_common.php');

// エピローグURLから挿入
function insert_vil_data($vil_url,$form_server){
	$html = file_get_html($vil_url);
	// 変数宣言
	$data;
	$edit_i = 0;

	// 村情報
	// どうやらtitleからぶん取ってくるしかない様子。
	// 陰謀(新版)もこの部分は共通
	$title = trim_convert($html->find('title',0)->plaintext);
	$vil_no = preg_replace("/(エピローグ|終了) \/ ([0-9]*) (.*)/s","\\2",$title,-1);
	$vil_name = preg_replace("/(エピローグ|終了) \/ [0-9]* (.+) \- 人狼議事.*/s","\\2",$title,-1);

	echo $title . "<br>";
	echo $vil_no . "<br>";
	echo $vil_name . "<br>";

	// 鯖名の特定
	$server_name = get_server_name($form_server);

	// プロローグ日付取得
	$SS00000_time = get_vil_create_time($vil_url,$form_server);

	// キャラ情報
	// 鯖によって若干処理が異なるので分岐
	// 2013/04/17 標準・RPなど現行で動いてない鯖の記述を削除
	if(strcmp($form_server,"Cafe") == 0){
		echo "Cafe<br>";
		// 陰謀(新版)
		// 処理の流れ：「gon.potofs」を区切りとして文字列を配列に分割
		// その配列の各要素に対してpreg_replace
		// $data[]にぶちこむ　ね、簡単でしょ☆
		$script = trim_convert($html->find('script',-1)->innertext);
		$script_pl = explode("gon.potofs",$script);
		// 前後要素の削除
		array_shift($script_pl);
		array_pop($script_pl);
		foreach($script_pl as $element){
			// 加工(edit_hoge)--------------------------------------
			$edit_character = preg_replace("/.*\"longname\": \"(.*?)\".*/","\\1",$element,-1);
			$edit_id = preg_replace("/.*pl\.sow\_auth\_id \= \"(.*?)\".*/","\\1",$element,-1);
			
			$data[] = array(
			'number' => $edit_i,
			'character' => $edit_character,
			'id' => $edit_id);
			$edit_i++;
		}
	}else{
		echo "その他<br>";
		foreach($html->find('table.vindex tr.i_active') as $element){
			// 加工(edit_hoge)--------------------------------------
			$edit_character =  trim_convert($element->children(0)->plaintext);
			$edit_id = trim_convert($element->children(1)->plaintext);

			$data[] = array(
			'number' => $edit_i,
			'character' => $edit_character,
			'id' => $edit_id);
			$edit_i++;
		}
	}
	
	echo "配列を出力する<br>";
	foreach($data as $column){
		var_dump($column);
		echo "<br>";
	}
	
	
	// SQLの挿入
	$db_conn = db_conn();
	if($db_conn !== false){
		// 村情報の登録
		// 一度登録した村は登録しない
		$exists_vil = mysql_query("SELECT * FROM douson_vil WHERE vil_server = '" . $server_name . "'
			AND vil_no = '" . $vil_no . "'");
		if(mysql_num_rows($exists_vil) > 0){
			// 既にデータのある村なのでスキップ
			echo $vil_no . "already exists<br>";
			return;
		}
		mysql_query("INSERT INTO douson_vil (vil_name,vil_url,vil_server,vil_no,vil_date) 
			VALUES ('" . mysql_real_escape_string($vil_name) . "','" . $vil_url . "','" . $server_name . "','" . $vil_no . "','" . $SS00000_time . "')");
		// idの保持
		$vil_id = mysql_insert_id();
		// PL情報の登録
		foreach($data as $userdata){
			mysql_query("INSERT INTO douson_user (user_id,vil_id,character_name) 
				VALUES ('" . $userdata['id'] . "','" . $vil_id . "','" . $userdata['character'] ."')");
		}
	}
	
	unset($html);
	unset($data);
}


// 村一覧のURLからエピローグURLを特定して回す
function insert_vil_all($form_url,$form_server,$form_first_vil,$form_last_vil){
	
	$oldlog_html = file_get_html($form_url);
	
	// 2013/05/01　陰謀新版正式対応
	if(strcmp($form_server,"Cafe") == 0 ){
		foreach($oldlog_html->find('table.vindex tbody tr') as $oldlog_vildata){
			echo "dore";
			// 村のURLを特定する
			// 村ID
			$vil_no = trim_convert($oldlog_vildata->children(0)->plaintext);
			$vil_no = preg_replace("/^([0-9]+).*/","\\1",$vil_no,-1);
			if($vil_no < $form_first_vil || $vil_no > $form_last_vil){
				echo "skip" . $vil_no . "<br>";
				continue;
			}
			// 陰謀新版の場合エピローグの取得は不要
			// 村URLを取得
			// http://cabala.halfmoon.jp/cafe/sow.cgi?cmd=oldlog
			$vil_url_host = preg_replace("/(.*)sow.cgi(.*)/","\\1",$form_url,-1);
			$vil_url_path = trim_convert($oldlog_vildata->children(0)->children(0)->href);
			$vil_url_path = preg_replace("/\.\/(.*)/","\\1",$vil_url_path,-1);
			$vil_url = $vil_url_host . $vil_url_path;

			echo $vil_url . "<br>";
			insert_vil_data($vil_url,$form_server);
		}
	}else{
		// RPAd以外？(標準、陰謀系はこれで取得できるのを確認済)
		foreach($oldlog_html->find('table.vindex tr.i_hover') as $oldlog_vildata){
			echo "are";
			// 村のURLを特定する
			// 村ID
			$vil_no = trim_convert($oldlog_vildata->children(0)->plaintext);
			if($vil_no < $form_first_vil || $vil_no > $form_last_vil){
				echo "skip" . $vil_no . "<br>";
				continue;
			}
			// 最終日＆エピローグ
			$vil_last_day = trim_convert($oldlog_vildata->children(3)->plaintext);
			// 廃村対策
			// ただし、進行中に廃村した場合は考慮してない
			if(strcmp($vil_last_day,"廃村") == 0){
				$vil_last_day = 1;
			}else{
				$vil_last_day = preg_replace("/([0-9]+)(日.*)/","\\1",$vil_last_day,-1);
				$vil_last_day++;
			}

			// 最終日から村URLを生成
			// 村URL
			// http://utage.sytes.net/wolf/sow.cgi?css=ririnra&cmd=oldlog
			// http://utage.sytes.net/wolf/sow.cgi?pageno=1&css=ririnra&cmd=oldlog&rowall=on
			// http://utage.sytes.net/wolf/sow.cgi?css=ririnra&turn=5&vid=212&mode=all&move=page&pageno=1
			$vil_url = preg_replace("/(.*cgi\?)(.*)/","\\1",$form_url,-1);
			$vil_url = $vil_url . "turn=" . trim_convert($vil_last_day) . "&vid=" . trim_convert($vil_no) . "&mode=all&move=page&pageno=1";

			echo $vil_url . "<br>";
			insert_vil_data($vil_url,$form_server);
		}
	}
}


function enc_convert($str){
	return mb_convert_encoding($str,'utf8','sjis-win');
}

// 余計な末尾スペースの削除
function trim_convert($str){
	return trim(enc_convert($str));
}

// 引数からservernameを判定
function get_server_name($form_server){
	// 置き換え
	if(strcmp($form_server,"Wolf") == 0){
		// 標準鯖
		$server_name = "標準";
	}else if(strcmp($form_server,"Cafe") == 0){
		// Cafe
		$server_name = "陰謀(陰謀の苑・Cabala Cafe)";
	}else if(strcmp($form_server,"ultimate") == 0){
		// 大乱闘
		$server_name = "大乱闘";
	}else if(strcmp($form_server,"AS") == 0){
		// 大乱闘
		$server_name = "大乱闘AS";
	}else if(strcmp($form_server,"Pan") == 0){
		// 似顔絵人狼
		$server_name = "似顔絵人狼";
	}else{
		$server_name = $form_server;
	}
	
	return $server_name;
}

// エピローグURLからプロローグ>>0:0を特定して日付の取得
// 既存URLに対しても実行できるように別関数化
// 返り値：日付(YYYY/MM/DD)
function get_vil_create_time($vil_url,$form_server){
	if(strcmp($form_server,"Cafe") == 0 ){
		// 陰謀州(新版)
		// 陰謀州は情報ページにアクセスできれば全ての情報が取得可能なため、プロローグURLの生成は行わない
		// <script>タグ内部を取得
		// "updateddt":    Date.create(1000 * 1365769542),
		$html = file_get_html($vil_url);
		$SS00000 = trim_convert($html->find('script',-1)->innertext);
		$SS00000_time = preg_replace("/.*\"updateddt\":    Date\.create\(1000 \* ([0-9]*)\)\,.*/","\\1",$SS00000,-1);
		// エポック秒変換
		$SS00000_time = date("Y/m/d",$SS00000_time);
		echo "debug:SS00000_time:" . $SS00000_time. "<br>";
		return $SS00000_time;
	}else{
		//エピローグURLからプロローグURLを生成する
		//↓エピローグURL例
		//./sow.cgi?css=cinema800&vid=133&turn=6&mode=all&move=page&pageno=1
		//turn=hogeをturn=0に置換
		$vil_prg_url = preg_replace("/turn=[0-9]+/","turn=0",$vil_url,-1);
		echo "debug:vil_prg_url:" . $vil_prg_url . "<br>";
		$html = file_get_html($vil_prg_url);
		// <p class="mes_date" turn="0"> (0) 2012/06/01(Fri) 09時半頃</p>
		$SS00000 = $html->find('p.mes_date',0)->innertext;
		echo "debug:SS00000:" . $SS00000 . "<br>";
		$SS00000_time = preg_replace("/.*(\d{4}\/\d{2}\/\d{2}).*/","\\1",$SS00000,-1);
		echo "debug:SS00000_time:" . $SS00000_time. "<br>";
		return $SS00000_time;
	}
}

?>
