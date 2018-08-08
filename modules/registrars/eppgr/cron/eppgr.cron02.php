<?php
/*
 *  File: cron/eppgr.cron02.php
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

$query = "SELECT * FROM tbleppgrconfig WHERE id = 1";
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error cron02#01!');}
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

$query = 'SHOW CREATE TABLE tblwhoislog';
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error cron02#02!');}
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$charsets = explode('CHARSET=', $row['Create Table']);
$charset = 'latin1';
if (is_array($charsets) and count($charsets) and preg_match('/utf8/', $charsets[count($charsets)-1])) {
	$charset = 'utf8';
}

try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error cron02#01a!');}

$query = "DELETE FROM tblwhoislog WHERE domain LIKE('eppgr-0-%') OR domain LIKE('eppgr-1-%')";
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error build#01b!');}

$query = "SELECT id, domain FROM tblwhoislog WHERE domain LIKE('xn--%')";
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error cron02#03!');}
$idn = new idna_convert();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	eppgr_convertISOtoUTF8($row['domain']);
	$row['domain'] = $idn->decode($row['domain']);
	eppgr_convertUTF8toISO($config, $row['domain']);
	$query = "UPDATE tblwhoislog SET domain = '".str_replace("'", "", $row['domain'])."' WHERE id = ".$row['id'];
#try {$stmtextra = $eppgrdb->query($query);}
#catch(PDOException $ex) {die('MySQL error cron02#01a!');}
	try {$stmtextra = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error cron02#04!');}
}


echo 'Command executed successfully.';

?>