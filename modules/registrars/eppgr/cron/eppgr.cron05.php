<?php
/*
 *  File: cron/eppgr.cron05.php
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
catch(PDOException $ex) {die('MySQL error cron05#01!');}
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$charsets = explode('CHARSET=', $row['Create Table']);
$charset = 'latin1';
if (is_array($charsets) and count($charsets) and preg_match('/utf8/', $charsets[count($charsets)-1])) {
	$charset = 'utf8';
}

try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error cron05#01a!');}

$query = "SELECT * FROM tbleppgrconfig WHERE id = 1";
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error cron05#02!');}
$config = $stmt->fetch(PDO::FETCH_ASSOC);
unset($config['id']);
if (!is_array($config) or !array_key_exists('CronPassword', $config) or !$config['CronPassword']) die('No password found!');
$z = eppgr_getAESObject($config);
foreach ($config as $k => $v) {
	$z->AESDecode($config[$k]);
	$config[$k] = $z->cipher;
}
$config['certfile'] = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'cacert.pem');

$userpass = preg_replace('/\W/', '', $_REQUEST['pass']);
if ($config['CronPassword'] != $userpass) die('Restricted access!');
$config['language'] = 'el';

echo 'Processing began.<br />'."\n";

$done = false;
if (array_key_exists('all', $_REQUEST) and $_REQUEST['all'] == 1 and array_key_exists('days', $_REQUEST) and is_numeric($_REQUEST['days'])) {
	$query = 'SELECT * FROM tbldomains WHERE registrar = "eppgr" AND status = "Active"'
		.' AND DATE_ADD(expirydate, INTERVAL '.$_REQUEST['days'].' DAY) <= DATE(NOW())';
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error cron05#03!');}
	$domains = array();
	$ids = array();
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$ids[$row['domain']] = $row['id'];
		$domains[$row['domain']] = $row['expirydate'];
	}
	if (count($ids)) {
		$query = 'UPDATE tbldomains SET status = "Expired"'
			.' WHERE id IN('.implode(',', array_values($ids)).')';
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error cron05#04!');}
		echo 'The status of the following domains is now "Expired":<br />'."\n";
		foreach ($ids as $d => $id) {
			echo '['.$domains[$d].'] '.$d.'.<br />'."\n";
		}
		$done = true;
	}
}
elseif (array_key_exists('days', $_REQUEST) and is_numeric($_REQUEST['days'])) {
	$query = 'SELECT * FROM tbldomains'
		.' WHERE registrar = "eppgr" AND status = "Active"'
		.' AND DATE_ADD(expirydate, INTERVAL '.$_REQUEST['days'].' DAY) <= DATE(NOW())';
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error cron05#05!');}
	$first = true;
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		eppgr_convertISOtoUTF8($row);
		echo 'Processing domain '.$row['domain'].'.<br />'."\n";
		$command = 'GetNameservers';
		$config['domainid'] = $row['id'];
		$ret = eppgr_getServerResponse($config, $command);
		$expired = true;
		if (is_array($ret)) {
			$expdate = strtotime($ret['exDate']) + $_REQUEST['days'] * 24 * 60 * 60;
			if ($expdate - gmmktime() >= 0) {
				$expired = false;
			}
		}
		if ($expired) {
			$query = 'UPDATE tbldomains SET status = "Expired" WHERE id = '.$row['id'];
			try {$stmtextra = $eppgrdb->query($query);}
			catch(PDOException $ex) {die('MySQL error cron05#06!');}
			echo 'Status of domain '.$row['domain'].' is now "Expired" ['.gmdate('Y-m-d', strtotime($ret['exDate'])).'].<br />'."\n";
		}
		if (array_key_exists('error', $config) and $config['error']) unset($config['error']);
		$done = true;
	}
}
if (!$done) {
	echo 'Nothing to be done.<br />'."\n";
}
echo 'Finished processing.<br />'."\n";