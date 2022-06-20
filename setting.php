<?php
#-----------------------------------------
#Your Discord server id
define('ALLOWED_GUILDS',array("274000000000095521","414000000000089676"));

#-----------------------------------------
#OAUTH2 setting
define('OAUTH2_CLIENT_ID', 'edit');
define('OAUTH2_CLIENT_SECRET', 'edit');
define('OAUTH2_URL','edit');
#-----------------------------------------
#ZeroTier setting
define('ZEROTIER_ENABLE',False);//是否啟動ZeroTierDC模組
define('ZEROTIER_NETWORKID','edit');
define('ZEROTIER_TOKEN','edit');

#Every discord account's maximum devices.
define('ZT_MAX_JOIN',3);
#-----------------------------------------
#SQL server setting
define('SERVER', '127.0.0.1');
define('USERNAME', 'root');
define('PASSWD', 'rootPasswd');
define('DB', 'databaseName');
#-----------------------------------------
?>