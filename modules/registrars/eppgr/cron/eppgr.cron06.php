<?php
/*
 *  File: cron/eppgr.cron06.php
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
catch(PDOException $ex) {die('MySQL error cron06#01!');}
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$charsets = explode('CHARSET=', $row['Create Table']);
$charset = 'latin1';
if (is_array($charsets) and count($charsets) and preg_match('/utf8/', $charsets[count($charsets)-1])) {
	$charset = 'utf8';
}

try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error cron06#01a!');}

$query = "SELECT * FROM tbleppgrconfig WHERE id = 1";
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error cron06#02!');}
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
$config['language'] = 'en';

echo 'Processing began.<br />'."\n";

if (!array_key_exists('days', $_REQUEST) or !is_numeric($_REQUEST['days'])) {
	echo 'Nothing to be done.<br />'."\n";
	echo 'Finished processing.<br />'."\n";
}

$done = false;

$query = 'SELECT value FROM `tblconfiguration` WHERE setting = "CreateInvoiceDaysBefore"';
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error cron06#03!');}
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$cidb = $row['value'] + $_REQUEST['days'];

$query = 'SELECT value FROM `tblconfiguration` WHERE setting = "DomainRenewalNotices"';
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error cron06#04!');}
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$drn = explode(',', $row['value']);
for ($i = 0; $i < count($drn); $i++) {
	$drn[$i] = 'DATE_SUB(nextduedate, INTERVAL '.($drn[$i] + $_REQUEST['days']).' DAY) = DATE(NOW())';
}
$drn = implode(' OR ', $drn);

$query = 'SELECT * FROM tbldomains'
	.' WHERE registrar = "eppgr" AND status = "Active" '
	.' AND (DATE_SUB(nextduedate, INTERVAL '.$cidb.' DAY) = DATE(NOW()) OR '.$drn.')';
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error cron06#05!');}
$first = true;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	eppgr_convertISOtoUTF8($row);
	echo 'Processing domain '.$row['domain'].'.<br />'."\n";
	$command = 'GetNameservers';
	$config['domainid'] = $row['id'];
	$ret = eppgr_getServerResponse($config, $command);
	if (array_key_exists('error', $config)) {
		$cancel = false;
		if (is_array($config['error']) and count($config['error'])) {
			foreach ($config['error'] as $error) {
				if (strtolower(trim($error)) == 'domain does not belong to registrar') $cancel = true;
				echo '['.$row['domain'].'] '.$error.'<br />'."\n";
			}
		}
		elseif (!is_array($config['error']) and $config['error']) {
			if (strtolower(trim($row['domain'])) == 'domain does not belong to registrar') $cancel = true;
			echo '['.$row['domain'].'] '.$config['error'].'<br />'."\n";
		}
		unset($config['error']);
		if ($cancel) {
			$query = 'UPDATE tbldomains SET status = "Cancelled" WHERE id = '.$row['id'];
			try {$stmtextra = $eppgrdb->query($query);}
			catch(PDOException $ex) {die('MySQL error cron06#06!');}
			echo 'Status of domain '.$row['domain'].' is now "Cancelled".<br />'."\n";
		}
	}
	$done = true;
}

if (!$done) {
	echo 'Nothing to be done.<br />'."\n";
}
echo 'Finished processing.<br />'."\n";