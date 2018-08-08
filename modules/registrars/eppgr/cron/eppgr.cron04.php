<?php
/*
 *  File: cron/eppgr.cron04.php
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

ini_set('max_execution_time', 10000);

$config = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' .
		DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'configuration.php');
include $config;
include_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'eppgr.php');
include_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'eppgr.aes.php');
include_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lib'.DIRECTORY_SEPARATOR.'idna_convert.class.php');

try {$eppgrdb = new PDO('mysql:host='.$db_host.';dbname='.$db_name.';charset=utf8', $db_username, $db_password);}
catch(PDOException $ex) {die('Could not connect to database: '.$ex->getMessage());}

$query = 'SHOW CREATE TABLE tbldomains';
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error cron04#01!');}
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$charsets = explode('CHARSET=', $row['Create Table']);
$charset = 'latin1';
if (is_array($charsets) and count($charsets) and preg_match('/utf8/', $charsets[count($charsets)-1])) {
	$charset = 'utf8';
}

try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error cron04#01a!');}

$query = "SELECT * FROM tbleppgrconfig WHERE id = 1";
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error cron04#02!');}
$config = $stmt->fetch(PDO::FETCH_ASSOC);
unset($config['id']);
if (!is_array($config) or !array_key_exists('CronPassword', $config) or !$config['CronPassword']) die('No password found!');
$z = eppgr_getAESObject($config);
$properaes = preg_match('/^.+?\d+\D+\d+\-\d+\-\d+$/', $z->enckey);
foreach ($config as $k => $v) {
	$z->AESDecode($config[$k]);
	$config[$k] = $z->cipher;
}
$config['certfile'] = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'cacert.pem');

$userpass = preg_replace('/\W/', '', $_REQUEST['pass']);
if ($config['CronPassword'] != $userpass) die('Restricted access!');
$config['language'] = 'el';

$query = 'SELECT a.*, b.status, c.domain FROM tbleppgrorders AS a '
	.'LEFT JOIN tblorders As b ON a.order_id = b.id '
	.'LEFT JOIN tbldomains As c ON a.domain_id = c.id '
	.'WHERE a.checked = 0 AND b.status = "Active"';
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error cron04#03!');}
$counter = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	$counter++;
	eppgr_convertISOtoUTF8($row);
	$config['domainid'] = $row['domain_id'];
	$z->AESDecode($row['data']);
	$data = unserialize($z->cipher);
	if (strtolower($row['product']) == strtolower('eppgrHomograph')) {
		$command = 'RegisterDomain';
		$ret = eppgr_getServerResponse($config, $command, $data);
	    if (array_key_exists('error', $config) and count($config['error'])) {
			$error = eppgr_GetErrorString($config);
			eppgr_errorOrdersDB($eppgrdb, $error, $row, $z);
			echo '<br />'."\n"."Failed to activate ".$data['name']." for domain name ".$row['domain'].'.<br />'."\n";
			echo "Received error: ".$error.'<br />'."\n".'<br />'."\n";
			unset($config['error']);
		}
		else {
			echo "Homograph ".$data['name']." successfully activated for domain name ".$row['domain'].'.<br />'."\n";
			eppgr_updateOrdersDB($eppgrdb, $row);
		}
	}
	elseif (strtolower($row['product']) == strtolower('eppgrOwnerNameChange') or
			strtolower($row['product']) == strtolower('eppgrOwnerChange')) {
		if (strtolower($row['product']) == strtolower('eppgrOwnerNameChange')) {
			$txt['failed'] = 'registrant name';
			$txt['ok'] = 'Registrant name';
		}
		else {
			$txt['failed'] = 'registrant';
			$txt['ok'] = 'Registrant';
		}
		$command = 'SaveNameservers';
		$config['eppgrchgregistrant']['data'] = $data;
		$config['eppgrchgregistrant']['op'] = strtolower(substr(str_replace('eppgr', '', $row['product']),0,1)).substr(str_replace('eppgr', '', $row['product']),1);
		$ret = eppgr_getServerResponse($config, $command, $data);
	    if (array_key_exists('error', $config) and count($config['error'])) {
			$error = eppgr_GetErrorString($config);
			eppgr_errorOrdersDB($eppgrdb, $error, $row, $z);
			echo '<br />'."\n"."Failed to change ".$txt['failed']." for domain name ".$row['domain'].'.<br />'."\n";
			echo "Received error: ".$error.'<br />'."\n".'<br />'."\n";
			unset($config['error']);
		}
		else {
			echo $txt['ok']." changed for domain name ".$row['domain'].'.<br />'."\n";
			eppgr_updateOrdersDB($eppgrdb, $row);
			$query = "DELETE FROM `tbleppgrregistrants` WHERE `domain_id` = ".$config['domainid'];
			try {$stmtextra = $eppgrdb->query($query);}
			catch(PDOException $ex) {die('MySQL error cron04#04!');}
		}
	}
}
eppgr_Disconnect($config);

if (!$counter) {
	echo 'No work is pending.<br />'."\n";
}

echo 'Command executed successfully.';

function eppgr_updateOrdersDB(&$eppgrdb, &$row) {
	$query = 'UPDATE `tbleppgrorders` SET `checked` = 1 WHERE `id` = '.$row['id'];
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error cron04#05!');}
}

function eppgr_errorOrdersDB(&$eppgrdb, &$error, &$row, &$z) {
	$z->AESEncode($error);
	$query = 'UPDATE `tbleppgrorders` SET `error` = "'.$z->cipher.'" WHERE `id` = '.$row['id'];
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error cron04#06!');}
}

?>