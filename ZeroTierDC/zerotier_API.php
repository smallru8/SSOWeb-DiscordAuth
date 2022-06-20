<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

include_once("setting.php");

function getRoutingTable(){
	$prox_ch = curl_init();
	curl_setopt($prox_ch, CURLOPT_URL, "https://my.zerotier.com/api/v1/network/".ZEROTIER_NETWORKID);
	$http_headers = array();
	$http_headers[] = 'Content-type: application/json';
	$http_headers[] = 'Authorization: bearer '.ZEROTIER_TOKEN;
	curl_setopt($prox_ch, CURLOPT_HTTPHEADER, $http_headers);
	curl_setopt($prox_ch, CURLOPT_HEADER, true);
	curl_setopt($prox_ch, CURLOPT_RETURNTRANSFER, true);
	
	$response = curl_exec($prox_ch);

	curl_close($prox_ch);
	unset($prox_ch);
	
	$split_action_response = explode("\r\n\r\n", $response, 2);
	$header_response = $split_action_response[0];
	$body_response = $split_action_response[1];;
	
	$response = json_decode($body_response, true);
	
	$ret = array();
	foreach($response["config"]["routes"] as $route){
		if(array_key_exists("via", $route))
			$ret[$route["target"]] = $route["via"];
		else
			$ret[$route["target"]] = "(LAN)";
	}
	
	return $ret;
}

function getNodeIdListbyDiscordId($discordId){
	$prox_ch = curl_init();
	curl_setopt($prox_ch, CURLOPT_URL, "https://my.zerotier.com/api/v1/network/".ZEROTIER_NETWORKID."/member");
	$http_headers = array();
	$http_headers[] = 'Content-type: application/json';
	$http_headers[] = 'Authorization: bearer '.ZEROTIER_TOKEN;
	curl_setopt($prox_ch, CURLOPT_HTTPHEADER, $http_headers);
	curl_setopt($prox_ch, CURLOPT_HEADER, true);
	curl_setopt($prox_ch, CURLOPT_RETURNTRANSFER, true);
	
	$response = curl_exec($prox_ch);

	curl_close($prox_ch);
	unset($prox_ch);
	
	$split_action_response = explode("\r\n\r\n", $response, 2);
	$header_response = $split_action_response[0];
	$body_response = $split_action_response[1];;
	
	$response_array = json_decode($body_response, true);
	
	$ret = array();
	foreach($response_array as $node){
		if($node["description"]==$discordId)
			$ret[$node["name"]] = $node["nodeId"];
	}
	
	return $ret;
}

function joinNetwork($nodeId,$name,$discordId){
	$prox_ch = curl_init();
	curl_setopt($prox_ch, CURLOPT_URL, "https://my.zerotier.com/api/v1/network/".ZEROTIER_NETWORKID."/member/".$nodeId);
	$http_headers = array();
	$http_headers[] = 'Content-type: application/json';
	$http_headers[] = 'Authorization: bearer '.ZEROTIER_TOKEN;
	curl_setopt($prox_ch, CURLOPT_HTTPHEADER, $http_headers);
	curl_setopt($prox_ch, CURLOPT_HEADER, true);
	curl_setopt($prox_ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($prox_ch, CURLOPT_CUSTOMREQUEST, "POST");
	
	$data = array(
    "hidden" => false,
    "name" => $name,
    "description"=> $discordId,
    "config" => array (
        "activeBridge" => false,
        "authorized" => true,
        "noAutoAssignIps" => false
		)
	);
	
	$postdata = json_encode($data);
	curl_setopt($prox_ch, CURLOPT_POSTFIELDS, $postdata);
	
	curl_exec($prox_ch);
	curl_close($prox_ch);
	unset($prox_ch);
}

function removeNode($nodeId,$discordId){
	if(in_array($nodeId,getNodeIdListbyDiscordId($discordId))){
		$prox_ch = curl_init();
		curl_setopt($prox_ch, CURLOPT_URL, "https://my.zerotier.com/api/v1/network/".ZEROTIER_NETWORKID."/member/".$nodeId);
		$http_headers = array();
		$http_headers[] = 'Content-type: application/json';
		$http_headers[] = 'Authorization: bearer '.ZEROTIER_TOKEN;
		curl_setopt($prox_ch, CURLOPT_HTTPHEADER, $http_headers);
		curl_setopt($prox_ch, CURLOPT_HEADER, true);
		curl_setopt($prox_ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($prox_ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_exec($prox_ch);
		curl_close($prox_ch);
		unset($prox_ch);
	}
}

?>