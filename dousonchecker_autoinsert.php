<?php
// 自動で「終了済の村一覧」より挿入を行う
// 引数：なし
// 

require_once('simplehtmldom/simple_html_dom.php');
require_once('dousonchecker_insert.php');	// 共通処理

// それぞれ最新村のIDを抽出
// 但し、IDにgapが生じている場合は、gap以降の村を取り直す。
$db_conn = db_conn();
if($db_conn !== false){
	$first_vil;	
	$result = mysql_query("SELECT dv1.vil_server,MIN(dv1.vil_no+1) as vil_no FROM douson_vil as dv1
	 WHERE (dv1.vil_no+1) NOT IN (SELECT dv2.vil_no FROM douson_vil as dv2 WHERE dv1.vil_server = dv2.vil_server)
	 GROUP BY dv1.vil_server");
	while($row = mysql_fetch_assoc($result)){
		$first_vil[$row['vil_server']] = $row['vil_no'];
	}
	// 村一覧のURL等の配列を生成(現行稼動州のみ)
	$array_giji;
	// 標準
	// 2011/12/03 標準鯖長期停止のためコメントアウト
	/*
	$array_giji[] = array(
		'url' => "http://utage.sytes.net/wolf/sow.cgi?cmd=oldlog",
		'server' => "Wolf",
		'first_vil' => $first_vil['標準']);
	*/
	// 陰謀
	// 2013/05/01 陰謀新版正式対応
	$array_giji[] = array(
		'url' => "http://cabala.halfmoon.jp/cafe/sow.cgi?cmd=oldlog",
		'server' => "Cafe",
		'first_vil' => $first_vil['陰謀(陰謀の苑・Cabala Cafe)']);
	// 大乱闘AS
	// 2012/01/21 大乱闘AS鯖長期停止のためコメントアウト
	/*
	$array_giji[] = array(
		'url' => "http://jinro.jksy.org/~nanakorobi/sow.cgi?cmd=oldlog",
		'server' => "AS",
		'first_vil' => $first_vil['大乱闘AS']);
	*/
	// RPBPr
	$array_giji[] = array(
		'url' => "http://perjury.rulez.jp/sow.cgi?cmd=oldlog",
		'server' => "RPBPr",
		'first_vil' => $first_vil['RPBPr']);
	// RPBx
	$array_giji[] = array(
		'url' => "http://xebec.x0.to/xebec/sow.cgi?cmd=oldlog",
		'server' => "RPBx",
		'first_vil' => $first_vil['RPBx']);
	// RPBc
	$array_giji[] = array(
		'url' => "http://crazy-crazy.sakura.ne.jp/crazy/sow.cgi?cmd=oldlog",
		'server' => "RPBc",
		'first_vil' => $first_vil['RPBc']);
	// 似顔絵人狼
	// 2011/12/03 似顔絵鯖長期停止のためコメントアウト
	/*
	$array_giji[] = array(
		'url' => "http://utage.sytes.net/pan/sow.cgi?cmd=oldlog",
		'server' => "Pan",
		'first_vil' => $first_vil['似顔絵人狼']);
	*/
	// 夢の形
	// 2013/05/02 追加
	$array_giji[] = array(
		'url' => "http://morphe.sakura.ne.jp/morphe/sow.cgi?cmd=oldlog",
		'server' => "morphe",
		'first_vil' => $first_vil['夢の形']);

	foreach($array_giji as $giji){
		insert_vil_all($giji['url'],$giji['server'],$giji['first_vil'],999);
	}
	echo "auto insert done.";

}
?>
