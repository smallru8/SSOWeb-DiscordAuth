<?php
session_start();
ini_set("session.use_trans_sid",1);
ini_set("session.use_only_cookies",0);
ini_set("session.use_cookies",1);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('max_execution_time', 300); //300 seconds = 5 minutes. In case if your CURL is slow and is loading too much (Can be IPv6 problem)
include_once("../setting.php");

if(!ZEROTIER_ENABLE)//未啟用ZeroTier服務
	header("Location:../index.html");
else if($_SESSION['discordId'] != null and $_SESSION['guildName'] != null){
	include_once("zerotier_API.php");
	$userNodes_arr = getNodeIdListbyDiscordId($_SESSION['discordId']);
	//echo "<script>alert('$userNodes_arr');</script>";
	if(isset($_POST['ztid']) and count($userNodes_arr)<ZT_MAX_JOIN){//加入請求
		$ztname = $_POST['ztid'];
		if(isset($_POST['ztname']))
			$ztname = $_POST['ztname'];
		
		joinNetwork($_POST['ztid'],$ztname,$_SESSION['discordId']);
			
	}
	if(isset($_POST['deleteZTID'])){//退出請求
		removeNode($_POST['deleteZTID'],$_SESSION['discordId']);
	}
	$userNodes_arr = getNodeIdListbyDiscordId($_SESSION['discordId']);
?>

<html>
	<head>
		<title>SKUnion</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
		<link rel="stylesheet" href="../assets/css/main.css" />
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
		<script> 
			$(function(){
			$("#sidebar").load("../sidebar.html"); 
			});
		</script> 
	</head>
	<body class="is-preload">

		<!-- Wrapper -->
			<div id="wrapper">

				<!-- Main -->
					<div id="main">
						<div class="inner">

							<!-- Header -->
								<header id="header">
									<a href="" class="logo"><strong>JOIN ZEROTIER</strong></a>
									<ul class="icons">

									</ul>
								</header>

							<!-- Content -->
								<section>
									<header class="main">
										<h1>Zerotier</h1>
										
									</header>
									
									<h2>Join Zerotier Network</h2>
									<p>申請加入後，在你的ZeroTier用戶端加入<font color="blue"><?php echo ZEROTIER_NETWORKID; ?></font>這個網路</p>
										<?php
										if(count($userNodes_arr)<ZT_MAX_JOIN)
										{
										?>
										<form class="row gtr-uniform" method="post" action="">
											<div class="col-12">
												<h3>Your devidce name:</h3>
												<input type="text" name="ztname" id="ztname" value="" placeholder="" />
												<h3>Your Zerotier Id:</h3>
												<input type="text" name="ztid" id="ztid" value="" placeholder="" />
											</div>
											<div class="col-12">
												<ul class="actions">
													<li><input type="submit" value="Join" class="primary" /></li>
												</ul>
											</div>
										</form>
										<?php
										}else{
											echo '<p>已達加入上限</p>';
										}
										?>
									<hr class="major" />
									<h2>Your devices</h2>
									<form class="row gtr-uniform" method="post" action="">
										<div class="col-12">
											<select name="deleteZTID" id="deleteZTID">
												<option value="">- Devices -</option>
												
												<?php
													foreach($userNodes_arr as $key => $value){
												?>
														<option value="<?php echo $value; ?>"><?php echo $key; ?></option>	
												<?php
													}
												?>
												
											</select>
										</div>
										<div class="col-12">
											<ul class="actions">
												<li><input type="submit" value="Delete device" class="primary" /></li>
											</ul>
										</div>
									</form>
									<hr class="major" />
									<h2 id="routingtable">Routing table(同步ZeroTier資訊)</h2>
									<div class="table-wrapper">
										<table>
											<thead>
												<tr>
													<th>Destination</th>
													<th>Gateway</th>
												</tr>
											</thead>
											<tbody>
												<?php
												$routingTable = getRoutingTable();
												foreach($routingTable as $target => $via){
												?>
													<tr>
														<td><?php echo $target; ?></td>
														<td><?php echo $via; ?></td>
													</tr>
												<?php		
												}
												?>
											</tbody>
										</table>
									</div>
								</section>
								
						</div>
					</div>

				<!-- Sidebar -->
					<div id="sidebar" class="sidebar">
						
					</div>

			</div>

		<!-- Scripts -->
			
			<script src="../assets/js/browser.min.js"></script>
			<script src="../assets/js/breakpoints.min.js"></script>
			<script src="../assets/js/util.js"></script>
			<script src="../assets/js/main.js"></script>
	</body>
</html>

<?php
}else{
	header("Location:../reg.html");//未登入
}

?>