<html>
<head>
	<meta name="viewport" content="width=320, height=480, initial-scale=1.0, maximum-scale=2.0, user-scalable=yes">
	<meta charset="utf-8">
	<title>mission_5-2-open(Web掲示板)</title>
</head>
<body>
<h1 align="center" style="color:#4169e1;" >≪Web掲示板≫</h1>

<?php
$editnum0="0"; //新規投稿か編集投稿か判別するための初期値
$name0="お名前"; //投稿フォームの名前欄の初期値
$comment0="コメント"; //投稿フォームのコメント欄の初期値
$flag1=0; //削除モードのときデータベース内に処理番号を見つけたかどうか判別
$flag2=0; //編集モードのときデータベース内に処理番号を見つけたかどうか判別
$process=""; //処理内容をフォームの下に表示させるための変数

//データベース接続
$dsn="データベース名";
$user="ユーザー名";
$password="パスワード";
$pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

//テーブル初回だけ作成
$sql = "CREATE TABLE IF NOT EXISTS (テーブル名)"
."("."id INT AUTO_INCREMENT PRIMARY KEY,"
."name char(32),"
."comment TEXT,"
."time DATETIME,"
."password TEXT"
.");";
$stmt= $pdo->query($sql);

//空欄やスペースのみ、編集・削除番号に全角数字が入力されていないかチェックする
function checkIt($It, $type){
	$pattern1="^[1-9]|^[1-9]+[0-9]+$";
	$pattern2="^[０-９]+$";
	$pattern3="^(　)+$";

	if($type=="STR"){
		if(ctype_space($It)){
			$error="が入力されていません。";
			return $error;
		}
		elseif($It==""){
			$error="が入力されていません。";
			return $error;
		}
		elseif(mb_ereg_match($pattern3,$It)){
			$error="が入力されていません。";
			return $error;
		}
		else{
			$checkIt =1;
			return $checkIt;
		}
	}
	elseif($type=="INT"){
		if(mb_ereg_match($pattern1,$It)){
			$checkIt =1;
			return $checkIt;
		}
		elseif(mb_ereg_match($pattern2,$It)){
			$error="半角数字で入力してください。";
			return $error;
		}
		else{
			$error="番号が正しくありません。";
			return $error;
		}
	}

}

//編集モード
if (isset($_POST["edit"]) && isset($_POST["password"])){
	$edit=$_POST["edit"];
	$password=$_POST["password"];
	$editcheck = checkIt($edit, "INT");
	if($editcheck ==1){
		$passcheck=checkIt($password, "STR");
		if($passcheck ==1){
			$sql ="SELECT * FROM (テーブル名)";
			$stmt= $pdo->query($sql);
			$result =$stmt->fetchAll();
			foreach($result as $row){
				if($row["id"]==$edit){
					if($row["password"] == $password){
						$editnum0=$row["id"];
						$name0=$row["name"];
						$comment0=$row["comment"];
						$flag2=1;
						$process="投稿番号[".$row["id"]."]を編集します。";
					}
					else{
						$process="パスワードが違います。";
						$flag2=1;
					}
				}
			}
			if($flag2==0){
				$process="投稿番号[".$edit."]はありません。";
			}
		}
		else{
			$process="パスワードを入力してください。";
		}
	}
	else{
		$process=$editcheck;
	}
}

//投稿モード
elseif (isset($_POST["name"]) && isset($_POST["comment"]) && isset($_POST["password"])){
	$name = $_POST["name"];
	$comment=$_POST["comment"];
	$password=$_POST["password"];
	$namecheck = checkIt($name, "STR");
	if($namecheck ==1){
		$commentcheck = checkIt($comment, "STR");
		if($commentcheck ==1){
			$passcheck = checkIt($password, "INT");
			if($passcheck ==1){
				if($_POST["editnum"] ==0){ //新規投稿
					$time=date("Y/m/d H:i:s");
					$sql = $pdo -> prepare("INSERT INTO (テーブル名)(name,comment,time,password)VALUES(:name, :comment, :time, :password)");
					$sql->bindParam(":name", $name, PDO::PARAM_STR);
					$sql->bindParam(":comment", $comment, PDO::PARAM_STR);
					$sql->bindParam(":time", $time, PDO::PARAM_STR);
					$sql->bindParam(":password", $password, PDO::PARAM_STR);
					$sql->execute();
					$process=$name."「".$comment."」を受け付けました。(".$time.")<br>";
				}
				else{  //編集投稿
					$id=$_POST["editnum"];
					$time=date("Y/m/d H:i:s");
					$sql="UPDATE (テーブル名) set name=:name, comment=:comment, time=:time, password=:password WHERE id=:id";
					$stmt = $pdo->prepare($sql);
					$stmt->bindParam(":name", $name, PDO::PARAM_STR);
					$stmt->bindParam(":comment", $comment, PDO::PARAM_STR);
					$stmt->bindParam(":time", $time, PDO::PARAM_STR);
					$stmt->bindParam(":password", $password, PDO::PARAM_STR);
					$stmt->bindParam(":id", $id, PDO::PARAM_INT);
					$stmt->execute();
					$process="[".$id."]を「".$name.": ".$comment."」に編集しました。(".$time.")";
				}
			}
			else{
				$process="パスワードを入力してください。";
			}
		}
		else{
			$process="コメント".$commentcheck;
		}
	}
	else{
		$process="名前".$namecheck;
	}
}

//削除モード
elseif(isset($_POST["delet"]) && isset($_POST["password"])){
	$delet=$_POST["delet"];
	$password=$_POST["password"];
	$deletcheck = checkIt($delet, "INT");
	if($deletcheck ==1){
		$sql ="SELECT * FROM (テーブル名)";
		$stmt= $pdo->query($sql);
		$result =$stmt->fetchAll();
		foreach($result as $row){
			if($row["id"]==$delet){
				if($row["password"] == $password){
					$sql ="DELETE FROM (テーブル名) where id=:id";
					$stmt =$pdo->prepare($sql);
					$stmt->bindParam(":id", $delet, PDO::PARAM_INT);
					$stmt->execute();
					$process="投稿番号[".$delet."]を削除しました。<br>";
					$flag1=1;
				}
				else{
					$process="パスワードが違います。";
					$flag1=1;
				}
			}
		}
		if($flag1==0){
			$process ="投稿番号[".$delet."]はありません。<br>";
		}

	}
	else{
		$process=$deletcheck;
	}
}

?>

<table cellspacing="10" align="center" width="70%">
	<tr><form method="POST" action="/mission_5-2-open.php">
		<th align="right" width="50%">
			お名前:
		</th>
		<td align="left" width="50%">
			<input type="text" name="name" value=<?= $name0 ?>>
		</td>
	</tr>
	<tr>
		<th align="right" width="50%">
			コメント:
		</th>
		<td align="left" width="50%">
			<textarea name="comment"><?=$comment0 ?></textarea>
		</td>
	</tr>
	<tr>
		<th align="right" width="50%">
			パスワード:
		</th>
		<td align="left" width="50%">
			<input type="password" name="password">
			<input type="hidden" name="editnum" value=<?=$editnum0 ?>>
		</td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<button type="submit">送信</button></form>
		</td>
	</tr>
</table>

<table cellspacing="10" align="center" width="70%" >
	<tr><form method="POST" action="/mission_5-2-open.php">
		<th align="right" width="50%">
			削除番号:
		</th>
		<td align="left" width="50%">
			<input type="int" name="delet" value="1">
		</td>
	</tr>
	<tr>
		<th align="right" width="50%">
			パスワード:
		</th>
		<td align="left" width="50%">
			<input type="password" name="password">
		</td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<button type="submit">削除</button></form>
		</td>
	</tr>
</table>

<table cellspacing="10" align="center" width="70%">
	<tr><form method="POST" action="/mission_5-2-open.php">
		<th align="right" width="50%">
			編集番号:
		</th>
		<td align="left" width="50%">
			<input type="int" name="edit" value="1">
		</td>
	</tr>
	<tr>
		<th align="right" width="50%">
			パスワード:
		</th>
		<td align="left" width="50%">
			<input type="password" name="password">
		</td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<button type="submit">編集</button></form>
		</td>
	</tr>
</table>

<p align="center" style="color:#4169e1;"><?= $process ?></p>

<hr>

<?php
//データベースの中身を表示
$sql="SELECT*FROM (テーブル名);";
$stmt=$pdo->prepare($sql);
$stmt->execute();
?>

<table cellpadding="10" width="50%" align="center">
	<tr>
		<th>投稿番号</th>
		<th>名前</th>
		<th>コメント</th>
		<th>日時</th>
	</tr>
	<?php
	while($row=$stmt->fetch(PDO::FETCH_ASSOC)){ ?>
		<tr align="center">
			<td><?= $row["id"]."." ?></td>
			<td><?= $row["name"] ?></td>
			<td><?= $row["comment"] ?></td>
			<td><?= $row["time"] ?></td>
		</tr>
	<?php }?>
</table>
</body>
</html>
