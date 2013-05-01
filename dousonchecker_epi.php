<?php
$path = "/home/vage/siro_common/php";
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
require_once "db_common.php";

// HTML出力(頭部分)
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
echo "<h1>エピローグ検索(β)：検索結果</h1>";


// formからのデータを読み込み

$url_myID = "";

// 自IDの取得
for($i=0;$i<5;$i++){
	if(isset($_GET['myID_'.$i]) && $_GET['myID_'.$i] !== ""){
		$form_myID[] = htmlspecialchars($_GET['myID_'.$i]);
		$url_myID = $url_myID . "&myID_" . $i . "=" . urlencode($_GET['myID_'.$i]);
	}
}

// 相手ID(textarea)を分割
$temp_neighborID = htmlspecialchars($_GET['epiCast']);
$temp_neighborID = trim($temp_neighborID);
$temp_neighborID = str_replace(array("\r\n","\r"), "\n", $temp_neighborID);
$temp_neighborID = explode("\n",$temp_neighborID);
// 相手IDを解析
foreach($temp_neighborID as $element){
	// 空行を無視
	if (strcmp($element,"\n") == 0 || strcmp($element,"") == 0){
		continue;
	}
	$edit_temp = preg_replace("/.* \((.*)\)、.*/","\\1",$element,-1);
	if(strcmp($edit_temp,$element) !== 0){
		$edit_id = $edit_temp;
		$form_neighborID[] =$edit_id;
		if(count($form_neighborID) > 20){
			// 上限20件のため、最後の1件を削除
			echo "一度に検索できる件数は20件までです。それ以降のデータの検索は行われません。</br>";
			array_pop($form_neighborID);
			break;
		}
	}else{
		// 解析不能
		echo "検索失敗</br>";
		echo "お手数ですがエピローグキャスト欄の入力をやり直してください</br>";
		show_return_link();
		return;
	}
}

if(!isset($form_neighborID)){
	echo "解析できるIDがありませんでした。</br>";
	echo "お手数ですがエピローグキャスト欄の入力をやり直してください</br>";
	show_return_link();
	return;
}

/*
echo "<pre>";
var_dump($form_myID);
var_dump($form_neighborID);
echo "</pre>";
*/

$db_conn = db_conn();
if($db_conn !== false){
	// 自ID複数入力対応
	foreach ($form_myID as $myID){
		if(isset($sql_myID)){
			$sql_myID = $sql_myID . " , '" . mysql_real_escape_string($myID) . "'";
		}else{
			$sql_myID = " '" . mysql_real_escape_string($myID) . "'";
		}
	}
	$sql_myID = "(" . $sql_myID . ")";
}


// １人ずつ同村チェックして、結果を配列に格納
foreach($form_neighborID as $neighborID){
	// SQL文の生成
	// 詳細オプションはほぼ無し、固定で生成
	/***
	SELECT  1
		FROM douson_user as tu1,douson_user as tu2
		WHERE tu1.vil_id = tu2.vil_id
		AND tu1.user_id IN ( 'siro' , 'mih') 
		AND tu2.user_id = 'yaten'
	***/

	$db_conn = db_conn();
	if($db_conn !== false){
		// SQL文
		$query = "SELECT 1 FROM douson_user as tu1,douson_user as tu2 WHERE tu1.vil_id = tu2.vil_id AND tu1.user_id IN" . $sql_myID .
		"AND tu2.user_id = '" . mysql_real_escape_string($neighborID) . "'";

		// 同村の有無をチェック
		$exists_douson = mysql_query($query);
		if(mysql_num_rows($exists_douson) > 0){
			// 同村しているので、リンク付きデータを生成
			$data[] = array(
				'id' => $neighborID,
				'link' => '<a href="' .
					"http://dunkel.halfmoon.jp/jbbs/dousonchecker/dousonchecker_search.php?" . 
					$url_myID . "&neighborID_0=" . urlencode($neighborID) .
					"&searchdata=%E5%90%8C%E6%9D%91%E3%83%81%E3%82%A7%E3%83%83%E3%82%AF&searchLike=like&searchWithMyID=withMyID&server%5B%5D=Wolf&server%5B%5D=Cafe&server%5B%5D=AS&server%5B%5D=RPBPr&server%5B%5D=RPBx&server%5B%5D=RPBc&server%5B%5D=Pan&server%5B%5D=ultimate&server%5B%5D=RPAd&server%5B%5D=RP&searchCharacterName=&searchVillageName=&searchVilDaysFrom=&searchVilDaysTo=&searchMode=pc" . '" target="_blank">○</a>');
		}else{
			// 同村していないので、リンクは作成しない
			$data[] = array(
				'id' => $neighborID,
				'link' => '×');
		}
	}
}	// end of foreach


show_return_link();
echo "</br>";
echo "○：同村履歴がある人(クリックすると詳細を表示します)</br></br>";

foreach($data as $playerdata){
	echo "<p>" . $playerdata['id'];
	echo "：";
	echo $playerdata['link'] . "</p>";
}

echo "</br>";

show_return_link();
echo "</body></html>";
function show_return_link(){
	// 戻り先URLの指定
	echo '<a href="dousonchecker_epi.html">戻る</a><br>';
}

?>