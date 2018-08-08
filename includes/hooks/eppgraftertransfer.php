<?php
/*
 *  File: eppgraftertransfer.php
 *  
 *  EPPGR: A registrar module for WHMCS to serve the .gr Registry
 *  Copyright (C) 2018 Efthimios Mavrogeorgiadis
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

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