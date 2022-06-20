
<!--
	Editorial by HTML5 UP
	html5up.net | @ajlkn
	Free for personal and commercial use under the CCA 3.0 license (html5up.net/license)
-->
<?php 
session_start();
ini_set("session.use_trans_sid",1);
ini_set("session.use_only_cookies",0);
ini_set("session.use_cookies",1);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('max_execution_time', 300); //300 seconds = 5 minutes. In case if your CURL is slow and is loading too much (Can be IPv6 problem)

if(isset($_POST['Username']) and isset($_POST['Password1']) and $_SESSION['discordId'] != null and $_SESSION['guildName'] != null){
	if(!chechAccountPasswdFormat())
		echo "<script>alert('帳號或密碼格式錯誤:1.帳號長度最多40個英文字母或數字 2.密碼長度不小於8個字母 3.不包含!`;及反斜線');</script>";
	else{
		if($_SESSION['doOnlyOnce'] == 0){//重複提交
			header("Location:reg.html");//導向回Oauth頁面
		}else{
			include_once('sql.php');

			$result = addAccount($_POST['Username'],$_POST['Password1'],$_SESSION['discordId']);//新增同時檢查重複
			//TODO
			switch($result){
				case 1://成功
					echo "<script>alert('註冊成功!');</script>";
					$_SESSION['doOnlyOnce'] = 0;//完成註冊
					include_once('pve_function.php');
					addPVEUser($_POST['Username']);//註冊PVE
					//header("Location:index.html");//導向回主頁面
					break;
				case 2://discord id已申請過
					echo "<script>alert('該Discord id已被註冊');</script>";
					$_SESSION['doOnlyOnce'] = 0;//完成註冊
					//header("Location:index.html");//導向回主頁面
					break;
				case 3://username已存在
					echo "<script>alert('該使用者名稱已被使用');</script>";
					break;
			}
			
		}
	}
}

function chechAccountPasswdFormat(){
	$name = $_POST['Username'];
	$passwd = $_POST['Password1'];
	if(strlen($name)>40)
		return false;
	if (preg_match('/[^A-Za-z0-9]/', $name))
		return false;
	if(strlen($passwd)<8 || strpos($_POST['Password1'], '!')!==false || strpos($_POST['Password1'], '`')!==false || strpos($_POST['Password1'], '\\')!==false || strpos($_POST['Password1'], ';')!==false)
		return false;
	return true;
}
?>

<html>
	<head>
		<title>註冊</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
		<link rel="stylesheet" href="assets/css/main.css" />
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
	</head>
	<body class="is-preload">

		<!-- Wrapper -->
			<div id="wrapper">

				<!-- Main -->
					<div id="main">
						<div class="inner">

							<!-- Header -->
								<header id="header">
									<a href="#" class="logo"><strong>SSO</strong></a>
									<ul class="icons">
										<!--
										<li><a href="#" class="icon brands fa-twitter"><span class="label">Twitter</span></a></li>
										<li><a href="#" class="icon brands fa-facebook-f"><span class="label">Facebook</span></a></li>
										<li><a href="#" class="icon brands fa-snapchat-ghost"><span class="label">Snapchat</span></a></li>
										<li><a href="#" class="icon brands fa-instagram"><span class="label">Instagram</span></a></li>
										<li><a href="#" class="icon brands fa-medium-m"><span class="label">Medium</span></a></li>
										-->
									</ul>
								</header>

							<!-- Content -->
								<section>
									<header class="main">
										<h1>註冊</h1>
									</header>

									<span class="image main"><img src="images/1.png" alt="" /></span>
									<hr class="major" />

									<h2>帳號註冊</h2>
									<?php
									if($_SESSION['discordId'] != null and $_SESSION['guildName'] != null){
										echo '<p>Discord id: ' . $_SESSION['discordId'] . '</p>';
										echo '<p>Guild name: ' . $_SESSION['guildName'] . '</p>';
										include_once('sql.php');
										if(isDiscordIdExist($_SESSION['discordId'])==0){
									?>
										<form class="row gtr-uniform" action="" method="post">
											<div class="col-12">
												<input type="text" name="Username" id="Username" value="" placeholder="Username" />
											</div>
											<div class="col-12">
												<input type="password" name="Password1" id="Password1" value="" placeholder="Password" />
											</div>
											<div class="col-12">
												<input type="password" name="Password2" id="Password2" value="" placeholder="Enter your password again" />
											</div>
											<div class="col-12">
												<input type="submit" name="submit_button" id="submit_button" value="創建帳號"/>
											</div>
										</form>
									<?php
										}else{
											echo '<p>該Discord id已註冊。</p>';
										}
									}else{
										header("Location:reg.html");//未登入
									}
									?>
								</section>
						</div>
					</div>

				<!-- Sidebar -->
					<div id="sidebar" class="sidebar">
						
					</div>

			</div>

		<!-- Scripts -->
			<script src="sidebar.js"></script>
			<script src="assets/js/jquery.min.js"></script>
			<script src="assets/js/browser.min.js"></script>
			<script src="assets/js/breakpoints.min.js"></script>
			<script src="assets/js/util.js"></script>
			<script src="assets/js/main.js"></script>
			<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
			
			<script>
				//Password check
				document.getElementById("submit_button").disabled = true; 
				var searchTimeout;
				document.getElementById('Password2').oninput = function () {
					if (searchTimeout != undefined) clearTimeout(searchTimeout);
					searchTimeout = setTimeout(checkPasswdIsSame, 250);
				};
				
				function checkPasswdIsSame(){
					var p1 = document.getElementById("Password1").value;
					var p2 = document.getElementById("Password2").value;
					
					if(p1.localeCompare(p2)==0){
						document.getElementById("submit_button").disabled = false;
					}else{
						document.getElementById("submit_button").disabled = true; 
					}
				}
			</script>
	</body>
</html>
