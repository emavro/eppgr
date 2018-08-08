<?php
/*
 *  File: lib/eppgr.dnames.php
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

try {$eppgrdb = new PDO('mysql:host='.$db_host.';dbname='.$db_name.';charset=utf8', $db_username, $db_password);}
catch(PDOException $ex) {die('Could not connect to database: '.$ex->getMessage());}

$query = 'SHOW CREATE TABLE tbldomains';
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error dnames#01!');}
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$charsets = explode('CHARSET=', $row['Create Table']);
$charset = 'latin1';
if (is_array($charsets) and count($charsets) and preg_match('/utf8/', $charsets[count($charsets)-1])) {
	$charset = 'utf8';
}

try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error dnames#01a!');}

$query = "SELECT * FROM tbleppgrconfig WHERE id = 1";
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error dnames#02!');}
$config = $stmt->fetch(PDO::FETCH_ASSOC);
unset($config['id']);
if (!is_array($config) or !array_key_exists('CronPassword', $config) or !$config['CronPassword']) die('No password found!');
$z = eppgr_getAESObject($config);
foreach ($config as $k => $v) {
	$z->AESDecode($config[$k]);
	$config[$k] = $z->cipher;
}
$config['certfile'] = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'cacert.pem');
$config['language'] = 'el';

if ($config['RenewDNames'] != 'on' and $config['RenewDNames'] != 1) {
	return;
}

if (array_key_exists('passthrough', $_REQUEST) and $_REQUEST['passthrough'] == 1) {
	return;
}

$query = 'SELECT * FROM `tblproducts` WHERE `name` = "eppgrHomograph"';
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error dnames#03!');}
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$productid = $row['id'];

//eppgr_cleanSessionArrays($GLOBALS['HTTP_SESSION_VARS']['cart'], $productid);
eppgr_cleanSessionArrays($_SESSION['cart'], $productid);

if (!array_key_exists('renewals', $vars) or !is_array($vars['renewals']) or !count($vars['renewals'])) {
	return true;
}

$ids = implode(' OR `id` = ', array_keys($vars['renewals']));
$query = "SELECT `id`, `domain` FROM tbldomains WHERE `registrar` = 'eppgr' AND `id` = ".$ids;
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error dnames#04!');}
$charge = 0;
$domains = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	$command = 'GetNameservers';
	$config['domainid'] = $row['id'];
	$ret = eppgr_getServerResponse($config, $command);
	$bundlednum = eppgr_countBundleNames($ret);
	if ($bundlednum > 0) {
		$charge += ($bundlednum - 1) * ($vars['renewals'][$row['id']] / 2);
	}
}

//	$vars['renewals'][$row['id']] *= $charge;
//eppgr_fixSessionArrays($GLOBALS['HTTP_SESSION_VARS']['cart'], $charge, $productid);
if ($charge > 0) {
	eppgr_fixSessionArrays($_SESSION['cart'], $charge, $productid);
}

function eppgr_fixSessionArrays(&$array, $charge, $productid) {
//	$counter = eppgr_countSessionArrays($array, $productid);
	while ($charge > 0) {
		$array['products'][] = array('pid' => $productid);
		$charge--;
	}
	return true;
}

function eppgr_cleanSessionArrays(&$array, $productid) {
	$keys = array_keys($array['products']);
	sort($keys);
	for ($i = count($keys) - 1; $i >= 0; $i--) {
		if ($array['products'][$keys[$i]]['pid'] == $productid) {
			array_splice($array['products'], $keys[$i], 1);
		}
	}
}

/*
function eppgr_countSessionArrays(&$array, &$productid) {
	if (array_key_exists('products', $array) and
		is_array($array['products']) and
		count($array['products'])) {
		$counter = 0;
		foreach ($array['products'] as $p) {
			if ($p['pid'] == $productid) {
				$counter++;
			}
		}
		return $counter;
	}
	return 0;
}
*/
?>