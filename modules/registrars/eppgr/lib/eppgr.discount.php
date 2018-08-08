<?php
/*
 *  File: lib/eppgr.discount.php
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
require_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'eppgr.php');
require_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'eppgr.aes.php');

try {$eppgrdb = new PDO('mysql:host='.$db_host.';dbname='.$db_name.';charset=utf8', $db_username, $db_password);}
catch(PDOException $ex) {die('Could not connect to database: '.$ex->getMessage());}

$query = 'SHOW CREATE TABLE tbldomains';
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error discount#01!');}
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$charsets = explode('CHARSET=', $row['Create Table']);
$charset = 'latin1';
if (is_array($charsets) and count($charsets) and preg_match('/utf8/', $charsets[count($charsets)-1])) {
	$charset = 'utf8';
}

try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error discount#01a!');}

$query = "SELECT * FROM tbleppgrconfig WHERE id = 1";
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error discount#02!');}
$config = $stmt->fetch(PDO::FETCH_ASSOC);
unset($config['id']);
if (!is_array($config) or !array_key_exists('CronPassword', $config) or !$config['CronPassword']) die('No password found!');
$z = eppgr_getAESObject($config);
if (!is_object($z)) {
	return 0;
}
foreach ($config as $k => $v) {
	$z->AESDecode($config[$k]);
	if ($k == 'CronPassword') {
		$config[$k] = $v;
	}
	else {
		$config[$k] = $z->cipher;
	}
}
$config['certfile'] = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'cacert.pem');
$config['language'] = 'el';

if (!$config['Discounts']) {
	return;
}

$userid = $_SESSION['uid'];

if (!is_numeric($userid)) {
	$userid = 0;
}

$pieces = preg_split('/@/', str_replace(' ', '', $config['Discounts']));

$temp = explode(',', $pieces[1]);
if (!count($temp)) {
	return;
}

$coupons = array();
$coups = array();
foreach ($temp as $t) {
	$p = explode('>', $t);
	array_push($coups, $p[0]);
	$query = 'SELECT `id`, `appliesto`, `requires` FROM `tblpromotions` WHERE `code` = "'.$p[0].'" '
		.'AND (`expirationdate` = "0000-00-00" OR `expirationdate` >= DATE(NOW())) '
		.'AND (`startdate` = "0000-00-00" OR `startdate` <= DATE(NOW()))';
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error discount#03!');}
	$row = $stmt->fetch(PDO::FETCH_BOTH);
	$id = $row['id'];
	if ($id and eppgr_applyRequire($row)) {
		$coupons[$p[1]] = $p[0];
	}
}

if (!count($coupons) or ((eppgr_inArrayPromo($coups) or eppgr_inArrayPromocode($coups)) and array_key_exists('removepromo', $_SESSION['eppgr']))) {
	unset($_SESSION['eppgr']['removepromo']);
}

if (eppgr_inArrayPromo($coups) or eppgr_inArrayPromocode($coups)) {
	eppgr_cleanPromo();
}

if ((is_array($_SESSION['eppgr']) and array_key_exists('removepromo', $_SESSION['eppgr'])) or eppgr_existsPromo() or eppgr_existsPromocode() or !count($coupons) or !$userid) {
	return;
}

if (!array_key_exists('removepromo', $_SESSION['eppgr'])) {
	$_SESSION['eppgr']['removepromo'] = 1;
}

if (is_numeric($pieces[0])) {
	$domains = array();
}
else {
	$domains = preg_split('/,/', str_replace('.', '', $pieces[0]));
}

if (count($domains)) {
	if (count($domains) == 1 and $domains[0] == '*') {
		$where = '';
	}
	else {
		$where = ' AND (`domain` LIKE "%.';
		$where .= implode('" OR `domain` LIKE "%.', $domains);
		$where .= '")';
	}
	$query = 'SELECT COUNT(*) FROM `tbldomains` '
	. 'WHERE `userid` = '.$userid.' AND `expirydate` >= DATE(NOW())'.$where;
}
else {
	$query = 'SELECT SUM(`amount`) FROM `tblorders` '
	. 'WHERE `userid` = '.$userid.' AND `status` = "Active" '
	. 'AND `date` >= DATE(DATE_SUB(NOW(), INTERVAL '.$pieces[0].' DAY))';
}

try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error discount#04!');}
$total = $stmt->fetch(PDO::FETCH_NUM);
$total = $total[0];

if ($total) {
	$promo = '';
	foreach ($coupons as $k => $v) {
		if ($total > $k) {
			$promo = $v;
		}
	}
	if ($promo) {
		eppgr_setPromo($promo);
	}
}

function eppgr_inArrayPromo($array) {
	if (array_key_exists('cart', $_SESSION) and is_array($_SESSION['cart']) and
		array_key_exists('promo', $_SESSION['cart']) and $_SESSION['cart']['promo'] and
		in_array($_SESSION['cart']['promo'], $array)) {
		return true;
	}
	else {
		return false;
	}
}

function eppgr_inArrayPromocode($array) {
	if (array_key_exists('promocode', $GLOBALS) and $GLOBALS['promocode'] and
		in_array($GLOBALS['promocode'], $array)) {
		return true;
	}
	else {
		return false;
	}
}

function eppgr_existsPromo() {
	if (array_key_exists('cart', $_SESSION) and is_array($_SESSION['cart']) and
		array_key_exists('promo', $_SESSION['cart']) and $_SESSION['cart']['promo']) {
		return true;
	}
	else {
		return false;
	}
}

function eppgr_existsPromocode() {
	if (array_key_exists('promocode', $GLOBALS) and $GLOBALS['promocode']) {
		return true;
	}
	else {
		return false;
	}
}

function eppgr_setPromo($promo) {
	$GLOBALS['_POST']['validatepromo'] = 'true';
	$GLOBALS['_POST']['promocode'] = $promo;
	$GLOBALS['_REQUEST']['validatepromo'] = 'true';
	$GLOBALS['_REQUEST']['promocode'] = $promo;
	$GLOBALS['_SERVER']['HTTP_POST_VARS']['validatepromo'] = 'true';
	$GLOBALS['_SERVER']['HTTP_POST_VARS']['promocode'] = $promo;
	$GLOBALS['promocode'] = $promo;
	$GLOBALS['smartyvalues']['promotioncode'] = $promo;
	$GLOBALS['_SESSION']['cart']['promo'] = $promo;
}

function eppgr_cleanPromo() {
	if (array_key_exists('promocode', $GLOBALS['_POST'])) {
		unset($GLOBALS['_POST']['promocode']);
	}
	if (array_key_exists('promocode', $GLOBALS['_REQUEST'])) {
		unset($GLOBALS['_REQUEST']['promocode']);
	}
	if (array_key_exists('promocode', $GLOBALS['_SERVER']['HTTP_POST_VARS'])) {
		unset($GLOBALS['_SERVER']['HTTP_POST_VARS']['promocode']);
	}
	if (array_key_exists('promocode', $GLOBALS)) {
		unset($GLOBALS['promocode']);
	}
	if (array_key_exists('promotioncode', $GLOBALS['smartyvalues'])) {
		unset($GLOBALS['smartyvalues']['promotioncode']);
	}
	if (array_key_exists('promo', $GLOBALS['HTTP_SESSION_VARS']['cart'])) {
		unset($GLOBALS['HTTP_SESSION_VARS']['cart']['promo']);
	}
	if (array_key_exists('promo', $GLOBALS['_SESSION']['cart'])) {
		unset($GLOBALS['_SESSION']['cart']['promo']);
	}
}

function eppgr_applyRequire($row) {
	$r = eppgr_apreq($row, 'requires');
	$a = eppgr_apreq($row, 'appliesto');
	return ($r and $a);
}

function eppgr_apreq($row, $field) {
	global $eppgrdb;
	$ret = false;
	if (!$row[$field]) {
		return true;
	}
	$req = preg_split('/,/', $row[$field]);
	$cart = false;
	$order = false;
	if (array_key_exists('domains', $_SESSION['cart'])) {
		$cart = true;
	}
	elseif (
			array_key_exists('orderdetails', $_SESSION) and
			is_array($_SESSION['orderdetails']) and
			array_key_exists('Domains', $_SESSION['orderdetails'])
		) {
		$order = true;
	}

	foreach ($req as $r) {
		if (is_numeric($r) and array_key_exists('cart', $_SESSION) and array_key_exists('products', $_SESSION['cart'])) {
			foreach ($_SESSION['cart']['products'] as $p) {
				if ($r == $p['pid']) {
					$ret = true;
					break 2;
				}
			}
		}
		elseif (array_key_exists('cart', $_SESSION) and ($cart or $order)) {
			if ($order) {
				$domains = $_SESSION['orderdetails']['Domains'];
			}
			else {
				$domains = $_SESSION['cart']['domains'];
			}
			foreach ($domains as $d) {
				if ($order) {
					$query = "SELECT * FROM tbldomains WHERE id = ".$d;
					try {$stmt = $eppgrdb->query($query);}
					catch(PDOException $ex) {die('MySQL error discount#05!');}
					$row = $stmt->fetch(PDO::FETCH_BOTH);
					$tld = 'D'.substr($row['domain'], strpos($row['domain'], '.'));
				}
				else {
					$tld = 'D'.substr($d['domain'], strpos($d['domain'], '.'));
				}
				$tr = preg_replace('/D\.\w+\./', 'D.', $r);
				if ($tr == $tld) {
					$ret = true;
					break 2;
				}
			}
		}
	}
	return $ret;
}