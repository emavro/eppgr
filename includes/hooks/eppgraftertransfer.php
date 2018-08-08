<?php
function eppgrAfterTransfer($vars) {
	$config = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'configuration.php');
	if (is_file($config) and is_readable($config)) {
		include $config;
	}
	else {
		return;
	}

	try {$db = new PDO('mysql:host='.$db_host.';dbname='.$db_name.';charset=utf8', $db_username, $db_password);}
	catch(PDOException $ex) {die('Could not connect to database: '.$ex->getMessage());}

	if (is_array($vars) and array_key_exists('params', $vars) and is_array($vars['params']) and array_key_exists('domainid', $vars['params']) and is_numeric($vars['params']['domainid'])) {
		$query = "UPDATE `tbldomains` SET `status` = 'Active' WHERE `id` = ".$vars['params']['domainid']." AND `registrar` = 'eppgr'";
		try {$stmt = $db->query($query);}
		catch(PDOException $ex) {die('MySQL error aftertransfer#01: '.$ex->getMessage());}
	}
}

add_hook("AfterRegistrarTransfer",1,"eppgrAfterTransfer");