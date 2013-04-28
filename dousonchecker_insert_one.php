<?php
// 村エピローグより挿入を行う
// 引数：終了済みの村の一覧　のURL
//		 鯖ID

require_once('simplehtmldom/simple_html_dom.php');
require_once('dousonchecker_insert.php');	// 共通処理
require_once('dousonchecker_config.php');	// パスワード読み込み

// formからのデータを読み込み
$form_url = $_POST['vilurl'];
$form_admin = $_POST['adminpassword'];
$form_server = trim_convert(htmlspecialchars($_POST['serverid']));

if(strcmp($form_admin,INSERT_ONE_PASS) == 0){
	insert_vil_data($form_url,$form_server);
	echo "insert one done.";
}else{
	echo "パスワードが正しくない";
}

?>
