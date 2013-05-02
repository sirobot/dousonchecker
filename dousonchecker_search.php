<?php
$path = "/home/vage/siro_common/php";
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
require_once "db_common.php";

// formからのデータを読み込み

// 自IDの取得
for($i=0;$i<5;$i++){
	if(isset($_GET['myID_'.$i]) && $_GET['myID_'.$i] !== ""){
		$form_myID[] = htmlspecialchars($_GET['myID_'.$i]);
	}
}
// 相手IDの取得
for($i=0;$i<5;$i++){
	if(isset($_GET['neighborID_'.$i]) && $_GET['neighborID_'.$i] !== ""){
		$form_neighborID[] = htmlspecialchars($_GET['neighborID_'.$i]);
	}
}
$form_myIDShow = htmlspecialchars($_GET['myIDShow']);
$form_searchLike = htmlspecialchars($_GET['searchLike']);
$form_server = $_GET['server'];
$form_searchCharacter = htmlspecialchars($_GET['searchCharacter']);
$form_searchCharacterName = htmlspecialchars($_GET['searchCharacterName']);
$form_searchVillage = htmlspecialchars($_GET['searchVillage']);
$form_searchVillageName = htmlspecialchars($_GET['searchVillageName']);
$form_searchWithMyID = htmlspecialchars($_GET['searchWithMyID']);
$form_searchMode = htmlspecialchars($_GET['searchMode']);
$form_searchWithoutMaster = htmlspecialchars($_GET['searchWithoutMaster']);
$form_searchVilDays = htmlspecialchars($_GET['searchVilDays']);
$form_searchVilDaysFrom = htmlspecialchars($_GET['searchVilDaysFrom']);
$form_searchVilDaysTo = htmlspecialchars($_GET['searchVilDaysTo']);
$form_searchDistinct = htmlspecialchars($_GET['searchDistinct']);

if($form_searchMode == "sp"){
	// スマホ版HTML
	echo "<!DOCTYPE html>";
	echo '<html lang="ja">';
	echo "<head>";
	echo '<meta charset="UTF-8" />';
	echo '<title>同村チェッカー(議事)</title>';
	echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=0" />';
	echo '<meta name="format-detection" content="telephone=no" />';
	echo '<link rel="stylesheet" href="./css/default.css" />';
	echo '<link rel="stylesheet" href="./css/style.css" />';
	echo '<link rel="stylesheet" href="./css/jquery.mobile-1.0.1.min.css" />';
	echo '<script src="./js/jquery-1.7.1.min.js"></script>';
	echo '<script src="./js/jquery-custom-setting.js"></script>';
	echo '<script src="./js/jquery.mobile-1.0.1.min.js"></script>';
	echo '<script src="./js/share.js"></script>';
	echo "</head>";
	echo "<body>";
	echo '<div data-role="page">';
	echo '<div data-role="header">';
	echo '<h1>同村チェッカー(議事)：検索結果</h1>';
	echo '</div>';
	echo '<div data-role="content">';
}else{
	// PC版HTML
	echo "<html>";
	echo "<head>";
	echo '<meta content="text/html; charset=utf-8" http-equiv="Content-Type">';
	echo '<link href="../style.css" rel="stylesheet" type="text/css">';
	echo '<link rel="stylesheet" type="text/css" href="./js/jquery.tablesorter/themes/blue/style.css" />';
	echo '<script type="text/javascript" src="./js/jquery.tablesorter/jquery-latest.js"></script>';
	echo '<script type="text/javascript" src="./js/jquery.tablesorter/jquery.tablesorter.js"></script>';
	echo '<style type="text/css">';
	echo '.style1 {';
	echo '	border: 1px solid #333333;';
	echo '}';
	echo '</style>';
	echo '<script type="text/javascript">';
	echo '$(document).ready(function(){';
	echo '  $("#resultTable").tablesorter();';
	echo '});';
	echo '</script>';
	echo "<title>同村チェッカー(議事)</title>";
	echo "</head>";
	echo "<body>";
	echo "<h1>同村チェッカー(議事)：検索結果</h1>";
}

// 自IDに入力がなければエラー
if(!isset($form_myID)){
	echo "自分のIDを入力してください<br>";
	show_return_link($form_searchMode);
	echo '</body></html>';
	return;
}

// 2013/04/13 相手ID無入力の場合はID無効オプションがチェックされたものと判断する
if(!isset($form_neighborID)){
	$form_searchWithoutID = "withoutID";
}

// 対象サーバーがチェックされていなければエラー
if(count($form_server) == 0){
	echo "検索対象のサーバーを選択してください<br>";
	show_return_link($form_searchMode);
	echo '</body></html>';
	return;
}

// 無効な日付が入力されている場合はエラー
if($form_searchVilDays == "searchVilDays"){
	$fromday = strptime($form_searchVilDaysFrom,"%Y/%m/%d");
	if($fromday == false){
		echo "日付(From)が無効です<br>";
		show_return_link($form_searchMode);
		echo '</body></html>';
		return;
	}
	$fromday["tm_year"] = $fromday["tm_year"] + 1900;
	$fromday["tm_mon"] = $fromday["tm_mon"] + 1;
	if(!checkdate($fromday["tm_mon"],$fromday["tm_mday"],$fromday["tm_year"])){
		echo "日付(From)が無効です<br>";
		show_return_link($form_searchMode);
		echo '</body></html>';
		return;
	}
	$today = strptime($form_searchVilDaysTo,"%Y/%m/%d");
	if($today == false){
		echo "日付(To)が無効です<br>";
		show_return_link($form_searchMode);
		echo '</body></html>';
		return;
	}
	$today["tm_year"] = $today["tm_year"] + 1900;
	$today["tm_mon"] = $today["tm_mon"] + 1;
	if(!checkdate($today["tm_mon"],$today["tm_mday"],$today["tm_year"])){
		echo "日付(To )が無効です<br>";
		show_return_link($form_searchMode);
		echo '</body></html>';
		return;
	}
	if(mktime(0, 0, 0, $fromday["tm_mon"],$fromday["tm_mday"],$fromday["tm_year"]) 
		> mktime(0, 0, 0, $today["tm_mon"],$today["tm_mday"],$today["tm_year"])) {
		// From日付よりTo日付のほうが大きい場合
		echo "日付(From)が日付(To)より未来の日付です<br>";
		show_return_link($form_searchMode);
		echo '</body></html>';
		return;
	}
}

// 重複除外オプションが設定されていない場合(スマホ版)、無効と判断
if(!isset($form_searchDistinct)){
	$form_searchWithoutID = "";
}

$db_conn = db_conn();
if($db_conn !== false){
	// SQL文の作成
	// 基本のSQL文に色々くっつけていく
	
	// 自ID複数入力対応
	foreach ($form_myID as $myID){
		if(isset($sql_myID)){
			$sql_myID = $sql_myID . " , '" . mysql_real_escape_string($myID) . "'";
		}else{
			$sql_myID = " '" . mysql_real_escape_string($myID) . "'";
		}
	}
	$sql_myID = "(" . $sql_myID . ")";
	
	// 親SQL文1
	$sql_select_1 = " SELECT table1.*,table2.count FROM(";
	// 子SQL1 select(検索条件セット)
	$sql_select_table1 = " SELECT";
	// 重複除外オプション判定
	if($form_searchDistinct == 'distinct'){
		$sql_select_table1 = $sql_select_table1 . " DISTINCT";
	}
	$sql_select_table1 = $sql_select_table1 ." tu2.user_id,tu2.character_name as  neighborID,tv.vil_name,tv.vil_server,tv.vil_no,tv.vil_url,tv.vil_date";
	// 子SQL1 from
	$sql_from = " FROM douson_user as tu1,douson_user as tu2,douson_vil as tv";
	// 子SQL1 where(検索条件セット)
	$sql_where_table1 = " WHERE tu1.vil_id = tv.id AND tu2.vil_id = tv.id AND tu1.user_id IN "
				. $sql_myID . " AND (tu2.user_id";
	// 子SQL1 orderby(子1のみ)
	$sql_orderby_table1 = " ORDER BY tv.vil_server,tv.vil_no";
	// 親SQL文2
	$sql_select_2 = " ) AS table1,(";
	// 子SQL文2 select(カウント)
	$sql_select_table2 = " SELECT user_id , COUNT(*) AS count FROM (";
	// ここに子SQL文1を入れる
  	$sql_select_table2_2 = ") as fromtbl GROUP BY user_id";
  	// 親SQL文3
  	$sql_select_3 = ") AS table2 WHERE table1.user_id = table2.user_id";

	// 自IDを対象にする
	if($form_myIDShow == "show"){
		$sql_select_table1 = $sql_select_table1 . ",tu1.character_name as myID";
	}
	// あいまい・相手ID無指定検索有効
	if($form_searchWithoutID == "withoutID"){
		$sql_where_table1 = $sql_where_table1 . " LIKE '%')";
		if($form_searchWithMyID == 'withMyID'){
			// withMyIDの場合条件はこのままでよい
		}else{
			// withoutMyIDの場合、上記条件の検索結果から自分のIDのものを除外する
			$sql_where_table1 = $sql_where_table1 . " AND tu2.user_id NOT IN " . $sql_myID;
		}
	}else if($form_searchLike == "like"){
		foreach ($form_neighborID as $neighborID){
			if(isset($sql_neighborID)){
				$sql_neighborID = $sql_neighborID . " , '" . mysql_real_escape_string($neighborID) . "'";
			}else{
				$sql_neighborID = " '" . mysql_real_escape_string($neighborID) . "'";
			}
		}
		$sql_where_table1 = $sql_where_table1 . " IN (" . $sql_neighborID . ") )";
	}else{
		foreach ($form_neighborID as $neighborID){
			if(isset($sql_neighborID)){
				$sql_neighborID = $sql_neighborID . " OR tu2.user_id LIKE '%" . mysql_real_escape_string($neighborID) . "%'";
			}else{
				$sql_neighborID = " LIKE '%" . mysql_real_escape_string($neighborID) . "%'";
			}
		}
		$sql_where_table1 = $sql_where_table1 . $sql_neighborID . ")";
	}
	// サーバー指定
	if(count($form_server) == 10){
		// 全部チェックが入っている場合は条件は付けない
	}else{
		// サーバ指定をOR条件で付与
		$sql_where_table1 = $sql_where_table1 . " AND vil_server IN (";
		// どうやって置き換えるのがスマートかねえ。
		foreach($form_server as $server){
			$sql_where_table1 = $sql_where_table1 . "'" . get_server_name($server) . "',";
		}
		$sql_where_table1 = $sql_where_table1 . "'dummy')";
	}
	// キャラ名検索
	if($form_searchCharacter == 'searchChara'){
		// キャラ名部分一致
		if(strpos($form_searchCharacterName,"ジェレミー") !== false){
			// ジェレミー対応
			$sql_where_table1 = $sql_where_table1 . " AND tu2.character_name = ''";
		}else if(strpos($form_searchCharacterName,"へ") !== false || strpos($form_searchCharacterName,"ヘ") !== false){
			// ヘクター(ひらがな・カタカナの「へ」対応)
			//入力ママ
			$sql_where_table1 = $sql_where_table1 . " AND ( tu2.character_name LIKE '%" . mysql_real_escape_string($form_searchCharacterName) . "%'";
			//ひらがな置換
			$form_searchCharacterNameHiraHe = preg_replace("/(.*?)ヘ(.*?)/s","\\1へ\\2",$form_searchCharacterName,-1);
			$sql_where_table1 = $sql_where_table1 . "  OR   tu2.character_name LIKE '%" . mysql_real_escape_string($form_searchCharacterNameHiraHe) ."%'";
			//カタカナ置換
			$form_searchCharacterNameKataHe = preg_replace("/(.*?)へ(.*?)/s","\\1ヘ\\2",$form_searchCharacterName,-1);
			$sql_where_table1 = $sql_where_table1 . "  OR   tu2.character_name LIKE '%" . mysql_real_escape_string($form_searchCharacterNameKataHe) ."%' )";
		}else{
			$sql_where_table1 = $sql_where_table1 . " AND tu2.character_name LIKE '%" . mysql_real_escape_string($form_searchCharacterName) . "%'";
		}
	}
	// ダミーを検索結果から除外
	if($form_searchWithoutMaster == 'withoutMaster'){
		$sql_where_table1 = $sql_where_table1 . " AND tu2.user_id != 'master'";
	}
	// 村名検索
	if($form_searchVillage == 'searchVil'){
		// 村名部分一致
		$sql_where_table1 = $sql_where_table1 . " AND tv.vil_name LIKE '%" . mysql_real_escape_string($form_searchVillageName) . "%'";
	}
	// 村日付検索
	if($form_searchVilDays == 'searchVilDays'){
		// 村日付検索
		// $fromday["tm_mon"],$fromday["tm_mday"],$fromday["tm_year"]
		$sql_where_table1 = $sql_where_table1 . " AND tv.vil_date BETWEEN '" . $fromday["tm_year"] . "-" . $fromday["tm_mon"] . "-" . $fromday["tm_mday"] . "' AND '" . $today["tm_year"] . "-" . $today["tm_mon"] . "-" . $today["tm_mday"] . "'";
	}
	
	// SQLでデータをとってくる
	// 村情報の検索
	/* SELECT table1.*,table2.count 
		FROM
	( SELECT DISTINCT tu2.user_id,tu2.character_name as 		neighborID,tv.vil_name,tv.vil_server,tv.vil_no,tv.vil_url,tv.vil_date 
		FROM douson_user as tu1,douson_user as tu2,douson_vil as tv 
		WHERE tu1.vil_id = tv.id 
		AND tu2.vil_id = tv.id 
		AND tu1.user_id IN ( 'siro' , 'mih') 
		AND (tu2.user_id IN ( 'master') ) 
		ORDER BY tv.vil_server,tv.vil_no ) AS table1,
	( SELECT user_id , COUNT(*) AS count 
		FROM 
			( SELECT DISTINCT tu2.user_id,tu2.character_name as neighborID,tv.vil_name,tv.vil_server,tv.vil_no,tv.vil_url,tv.vil_date 
				FROM douson_user as tu1,douson_user as tu2,douson_vil as tv 
				WHERE tu1.vil_id = tv.id 
				AND tu2.vil_id = tv.id 
				AND tu1.user_id IN ( 'siro' , 'mih') 
				AND (tu2.user_id IN ( 'master') )) as fromtbl 
				GROUP BY user_id) AS table2 
		WHERE table1.user_id = table2.user_id */
	$result = mysql_query($sql_select_1 . $sql_select_table1 . $sql_from . $sql_where_table1 . $sql_orderby_table1
							. $sql_select_2 . $sql_select_table2 .$sql_select_table1 . $sql_from . $sql_where_table1 . $sql_select_table2_2 . $sql_select_3);	
		show_return_link($form_searchMode);
	if($form_searchMode == "sp"){
		// 情報表示(スマホ版)
		echo "<h2>検索結果：" . mysql_num_rows($result) . "件見つかりました。</h2>";
		echo "<h3>";
		foreach ($form_myID as $myID){
			echo " " . $myID . "さん";
		}
		echo "は、";
		if(isset($form_neighborID)){
			foreach ($form_neighborID as $neighborID){
				echo " " . $neighborID . "さん";
			}
		}
		echo "と以下の村で同村しているみたいです。</h3>";
		echo "<h4>(村名をタップすると詳細が表示されます)</h4>";
		while ($row = mysql_fetch_assoc($result)){
			echo '<div data-role="collapsible" data-content-theme="c">';
			echo '<h3>' . $row['vil_name'] . '</h3>';
			echo '<p>相手ID:' . $row['user_id'] . '</p>';
			echo '<p>相手キャラ:' . $row['neighborID'] . '</p>';
			if($form_myIDShow == "show"){
				echo '<p>自キャラ:' . $row['myID'] . '</p>';
			}
			echo '<p>サーバー(略称):' . $row['vil_server'] . '</p>';
			echo '<p>村番号:' . $row['vil_no'] . '</p>';
			echo '<p>村名(リンク):<a href="' . $row['vil_url'] . '" target="_blank">' . $row['vil_name'] . '</a></p>';
			echo '<p>同村回数:' . $row['count'] . '</p>';
			echo '<p>村建て日:' . $row['vil_date'] . '</p>';
			echo "</div>";
		}
		echo "<br><br>";
		show_return_link($form_searchMode);
		echo "</div>";
	}else{
		// 情報表示(PC版)
		echo "検索結果：" . mysql_num_rows($result) . "件見つかりました。<br>";
		foreach ($form_myID as $myID){
			echo " " . $myID . "さん";
		}
		echo "は、";
		if(isset($form_neighborID)){
			foreach ($form_neighborID as $neighborID){
				echo " " . $neighborID . "さん";
			}
		}
		echo "と以下の村で同村しているみたいです。<br>";
		echo '<table style="width: 100%" id="resultTable" class="tablesorter">';
		echo '<thead>';
		echo '<tr>';
		echo '	<th class="style1">ID</th>';
		echo '	<th class="style1">相手キャラクター名</th>';
		if($form_myIDShow == "show"){
			echo '	<th class="style1">自分キャラクター名</th>';
		}
		echo '	<th class="style1">サーバー(略称)</th>';
		echo '	<th class="style1">村番号</th>';
		echo '	<th class="style1">村名(リンク)</th>';
		echo '	<th class="style1">村建て日</th>';
		echo '	<th class="style1">同村回数</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		while ($row = mysql_fetch_assoc($result)) {
			echo '<tr>';
			echo '<td class="style1">' . $row['user_id'] . '</td>';
			echo '<td class="style1">' . $row['neighborID'] . '</td>';
			if($form_myIDShow == "show"){
				echo '<td class="style1">' . $row['myID'] . '</td>';
			}
			echo '<td class="style1">' . $row['vil_server'] . '</td>';
			echo '<td class="style1">' . $row['vil_no'] . '</td>';
			echo '<td class="style1"><a href="' . $row['vil_url'] . '" target="_blank">' . $row['vil_name'] . '</a></td>';
			echo '<td class="style1">' . $row['vil_date'] . '</td>';
			echo '<td class="style1">' . $row['count'] . '</td>';
			echo '</tr>';
	    	// echo $row['user_id'] . "   " . $row[character_name] . "   " . $row[vil_name] . "   " . $row[vil_server] . "   " . $row[vil_no] . "   " . $row[vil_url] . "   <br>";
		}
		echo '</tbody>';
		echo '</table>';
		echo "<br><br>";
		show_return_link($form_searchMode);
		echo "</body></html>";
	}
}else{
	echo "ごめんなさい、なんかエラーです。また後で試してください。<br><br><br>";
	echo "<br><br>";
	show_return_link($form_searchMode);
	echo "</body></html>";
}

function show_return_link($form_searchMode){
	// 戻り先URLの指定
	if($form_searchMode == "sp"){
		echo '<a href="dousonchecker_search_sp.html" data-rel=“back”>戻る</a><br>';
	}else if($form_searchMode == "pc_Adv"){
		echo '<a href="dousonchecker_search_adv.html">戻る</a><br>';
	}else{
		echo '<a href="dousonchecker_search.html">戻る</a><br>';
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
	}else if(strcmp($form_server,"morphe") == 0){
		// 似顔絵人狼
		$server_name = "夢の形";
	}else{
		$server_name = $form_server;
	}
	
	return $server_name;
}

?>
<br>
</body>
</html>