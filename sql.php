<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

include_once("setting.php");

//新增帳戶
//正常完成 return 1
//discord id已申請過 return 2
//username已存在 return 3
function addAccount($username,$passwd,$discordId){
	if(isDiscordIdExist($discordId)!=0)
		return 2;
	if(isAccountExist($username))
		return 3;
	exec("python3 pbkdf2_SHA256.py \"".$passwd."\"",$output,$ret);//輸出ldap passwd格式
	
	$passwd_hash = $output[0];
	
	$account_dn = 'cn='.$username.',dc=skunion,dc=org';
	
	$conn = new mysqli(SERVER, USERNAME, PASSWD, DB);
	$stmt = $conn->prepare("INSERT INTO ldap_entries (dn,keyval,discord)VALUES(?,0,?)");//新增LDAP帳號
	$stmt->bind_param("ss",$account_dn,$discordId);
	$stmt->execute();
	$stmt->close();
	
	$stmt = $conn->prepare("UPDATE ldap_entries SET ldap_entries.keyval = ldap_entries.id WHERE ldap_entries.discord = ?");//更新keyval
	$stmt->bind_param("s",$discordId);
	$stmt->execute();
	$stmt->close();
	
	$stmt = $conn->prepare("UPDATE persons SET password = ? WHERE name = ?");//更新LDAP密碼
	$stmt->bind_param("ss",$passwd_hash,$username);
	$stmt->execute();
	$stmt->close();
	
	mysqli_close($conn);
	return 1;
}

//變更密碼
//變更成功 return 1
//帳號無註冊 return 2
function changePasswd($passwd,$discordId){
	$keyval = isDiscordIdExist($discordId);
	if($keyval == 0)
		return 2;
	exec("python3 pbkdf2_SHA256.py \"".$passwd."\"",$output,$ret);//輸出ldap passwd格式
	$passwd_hash = $output[0];
	$conn = new mysqli(SERVER, USERNAME, PASSWD, DB);
	$stmt = $conn->prepare("UPDATE persons SET password = ? WHERE id = ?");//更新LDAP密碼
	$stmt->bind_param("ss",$passwd_hash,$keyval);
	$stmt->execute();
	$stmt->close();
	mysqli_close($conn);
	return 1;
}

//使用者帳號
function getName($discordId){
	$conn = new mysqli(SERVER, USERNAME, PASSWD, DB);
	$stmt = $conn->prepare("SELECT persons.name FROM persons WHERE id IN (SELECT ldap_entries.keyval FROM ldap_entries WHERE discord=?)");
	$stmt->bind_param("s", $discordId);
	$stmt->execute();
	$result = $stmt->get_result();
	$stmt->close();
	mysqli_close($conn);
	
	if($result->num_rows > 0){//Have record
		$row = $result->fetch_assoc();
		mysqli_free_result($result);
		return $row["name"];
	}
	mysqli_free_result($result);
	return null;
}

function getNamebyLdapId($ldapid){
	$conn = new mysqli(SERVER, USERNAME, PASSWD, DB);
	$stmt = $conn->prepare("SELECT persons.name FROM persons WHERE id=?");
	$stmt->bind_param("s", $ldapid);
	$stmt->execute();
	$result = $stmt->get_result();
	$stmt->close();
	mysqli_close($conn);
	
	if($result->num_rows > 0){//Have record
		$row = $result->fetch_assoc();
		mysqli_free_result($result);
		return $row["name"];
	}
	mysqli_free_result($result);
	return null;
}

//是否已經註冊過，回傳keyval或0
function isDiscordIdExist($discordId){
	$conn = new mysqli(SERVER, USERNAME, PASSWD, DB);
	$stmt = $conn->prepare("SELECT keyval FROM ldap_entries WHERE discord=?");
	$stmt->bind_param("s", $discordId);
	$stmt->execute();
	$result = $stmt->get_result();
	$stmt->close();
	mysqli_close($conn);
	if($result->num_rows > 0){//Already have record
		$row = $result->fetch_assoc();
		mysqli_free_result($result);
		return $row["keyval"];
	}
	mysqli_free_result($result);
	return 0;
}

//帳號是否重複
function isAccountExist($account){
	$conn = new mysqli(SERVER, USERNAME, PASSWD, DB);
	$stmt = $conn->prepare("SELECT name FROM persons WHERE name=?");
	$stmt->bind_param("s", $account);
	$stmt->execute();
	$result = $stmt->get_result();
	$stmt->close();
	mysqli_close($conn);
	if($result->num_rows > 0){//Already have record
		mysqli_free_result($result);
		return true;
	}
	mysqli_free_result($result);
	return false;
}

#以下為ProxmoxVE專用

function getVMS_ID($ldapId){
	$conn = new mysqli(SERVER, USERNAME, PASSWD, DB);
	$stmt = $conn->prepare("SELECT Id FROM PVEVMS WHERE LDAP_ID=?");
	$stmt->bind_param("s", $ldapId);
	$stmt->execute();
	$result = $stmt->get_result();
	$stmt->close();
	mysqli_close($conn);
	$ret_arr = array();
	$i = 0;
	while($row = mysqli_fetch_assoc($result)){
		$ret_arr[$i] = $row["Id"];
		++$i;
	}
	mysqli_free_result($result);
	
	return $ret_arr;
}

function getLDAP_IDbyVMID($vmid){
	$conn = new mysqli(SERVER, USERNAME, PASSWD, DB);
	$stmt = $conn->prepare("SELECT LDAP_ID FROM PVEVMS WHERE Id=?");
	$stmt->bind_param("s", $vmid);
	$stmt->execute();
	$result = $stmt->get_result();
	$stmt->close();
	mysqli_close($conn);
	$row = mysqli_fetch_assoc($result);
	mysqli_free_result($result);
	return $row["LDAP_ID"];
}

//return vmid
function addVMS($ldapId,$nodeId,$type){
	$conn = new mysqli(SERVER, USERNAME, PASSWD, DB);
	$stmt = $conn->prepare("INSERT INTO PVEVMS (LDAP_ID,available,PVENodeId,type)VALUES(?,0,?,?)");
	$stmt->bind_param("sss", $ldapId,$nodeId,$type);
	$stmt->execute();
	$stmt->close();
	$stmt = $conn->prepare("SELECT Id FROM PVEVMS WHERE LDAP_ID=? AND available=0");
	$stmt->bind_param("s", $ldapId);
	$stmt->execute();
	$result = $stmt->get_result();
	$stmt->close();
	
	while($row = mysqli_fetch_assoc($result)){
		mysqli_free_result($result);
		$vmid = $row["Id"];
		$stmt = $conn->prepare("UPDATE PVEVMS SET available=1 WHERE Id=?");
		$stmt->bind_param("s", $vmid);
		$stmt->execute();
		$stmt->close();
		mysqli_close($conn);
		return $vmid;
	}
	mysqli_free_result($result);
	mysqli_close($conn);
	return null;
}

function getPVENodeIdbyVMID($vmid){
	$conn = new mysqli(SERVER, USERNAME, PASSWD, DB);
	$stmt = $conn->prepare("SELECT PVENodeId FROM PVEVMS WHERE Id=?");
	$stmt->bind_param("s", $vmid);
	$stmt->execute();
	$result = $stmt->get_result();
	$stmt->close();
	
	if($row = mysqli_fetch_assoc($result)){
		mysqli_free_result($result);
		mysqli_close($conn);
		return $row["PVENodeId"];
	}
}

function getVMTypebyVMID($vmid){
	$conn = new mysqli(SERVER, USERNAME, PASSWD, DB);
	$stmt = $conn->prepare("SELECT type FROM PVEVMS WHERE Id=?");
	$stmt->bind_param("s", $vmid);
	$stmt->execute();
	$result = $stmt->get_result();
	$stmt->close();
	
	if($row = mysqli_fetch_assoc($result)){
		mysqli_free_result($result);
		mysqli_close($conn);
		return $row["type"];
	}else{
		mysqli_free_result($result);
		mysqli_close($conn);
	}
}

function removeVMS($ldapId,$vmid){
	$conn = new mysqli(SERVER, USERNAME, PASSWD, DB);
	$stmt = $conn->prepare("DELETE FROM PVEVMS WHERE Id=? AND LDAP_ID=?");
	$stmt->bind_param("ss", $vmid,$ldapId);
	$stmt->execute();
	$stmt->close();
	mysqli_close($conn);
}

#Minecraft白名單系統

function getMinecraftIdList($ldapId,$isSelf){
	$conn = new mysqli(SERVER, USERNAME, PASSWD, DB);
	$ret_arr = array();
	$stmt = $conn->prepare("SELECT MinecraftName FROM MinecraftWhiteList WHERE LDAP_ID=? and Relationship=?");
	$relationship = "self";
	$stmt->bind_param("is", $ldapId,$relationship);
	
	if(!$isSelf){//friend
		$relationship = "friend";
	}
	
	$stmt->execute();
	$result = $stmt->get_result();
	$stmt->close();
	
	$i = 0;
	while($row = mysqli_fetch_assoc($result)){
		$ret_arr[$i] = $row["MinecraftName"];
		++$i;
	}
	mysqli_free_result($result);
	mysqli_close($conn);
	
	return $ret_arr;
}

function addMinecraftId($ldapId,$minecraftId,$UUID,$isSelf){
	$conn = new mysqli(SERVER, USERNAME, PASSWD, DB);
	$relationship = "self";
	$stmt = null;
	if(!$isSelf){
		$relationship = "friend";
		$stmt = $conn->prepare("INSERT INTO MinecraftWhiteList (LDAP_ID,Relationship,MinecraftName,MinecraftUUID) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE MinecraftName=?,MinecraftUUID=?");
		$stmt->bind_param("isssss", $ldapId,$relationship,$minecraftId,$UUID,$minecraftId,$UUID);
	}else{//self
		$stmt = $conn->prepare("INSERT INTO MinecraftWhiteList (LDAP_ID,Relationship,MinecraftName,MinecraftUUID) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE MinecraftName=?,MinecraftUUID=?,Relationship=?,LDAP_ID=?");
		$stmt->bind_param("isssssss", $ldapId,$relationship,$minecraftId,$UUID,$minecraftId,$UUID,$relationship,$ldapId);
	}
	
	//$stmt = $conn->prepare("INSERT INTO MinecraftWhiteList (LDAP_ID,Relationship,MinecraftName,MinecraftUUID) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE MinecraftName=?,MinecraftUUID=?");
	//$stmt->bind_param("isssss", $ldapId,$relationship,$minecraftId,$UUID,$minecraftId,$UUID);
	$stmt->execute();
	$stmt->close();
	mysqli_close($conn);
}

function removeMinecraftId($ldapId,$minecraftId){
	$conn = new mysqli(SERVER, USERNAME, PASSWD, DB);
	$stmt = $conn->prepare("DELETE FROM MinecraftWhiteList WHERE LDAP_ID=? and MinecraftName=?");
	$stmt->bind_param("is", $ldapId,$minecraftId);
	$stmt->execute();
	$stmt->close();
	mysqli_close($conn);
}

function getWhiteList(){
	$conn = new mysqli(SERVER, USERNAME, PASSWD, DB);
	$ret_arr = array();
	$stmt = $conn->prepare("SELECT MinecraftName,MinecraftUUID FROM MinecraftWhiteList");
	$stmt->execute();
	$result = $stmt->get_result();
	$stmt->close();
	
	while($row = mysqli_fetch_assoc($result)){
		$ret_arr[$row["MinecraftName"]] = $row["MinecraftUUID"];
	}
	mysqli_free_result($result);
	mysqli_close($conn);
	
	return $ret_arr;
}

?>