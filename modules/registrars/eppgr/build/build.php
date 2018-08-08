<?php
/*
 *  File: build/build.php
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
catch(PDOException $ex) {die('MySQL error build#01!');}
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$charsets = explode('CHARSET=', $row['Create Table']);
$charset = 'latin1';
if (is_array($charsets) and count($charsets) and preg_match('/utf8/', $charsets[count($charsets)-1])) {
	$charset = 'utf8';
}

$query = 'SET NAMES '.$charset;
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error build#01a!');}

preg_match('/\w+/', $_REQUEST['task'], $matches);
$task = $matches[0];

switch($task) {
	case 'build':
	case 'complete':
	case 'contacts':
	case 'delorders':
	case 'execorders':
		eval($task."DB();");
	break;

	case 'delcontacts':
		eval("contactsDB(1);");
	break;

	case 'save':
		saveDomain();
	break;

	default:
	break;
}

$eppgrdb = null;
exit;

function outputResult(&$stmt) {
    echo '<script language="javascript" type="text/javascript">'."\n";
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		eppgr_convertISOtoUTF8($row['domain']);
		echo "var a = new Array('".$row['id']."', '".$row['domain']."');\n";
		echo "domains.push(a);\n";
	}
	echo "setDomainsList();\n";
    echo '</script>'."\n";
}

function setError($error) {
	eppgr_convertISOtoUTF8($error);
    echo '<script language="javascript" type="text/javascript">'."\n";
	echo 'error = "'.$error.'";'."\n";
    echo '</script>'."\n";
}

function buildDB($where="") {
	global $eppgrdb;
	if (!$where) {
		echo "Discarding database table `tbleppgrregistrants`... ";
		$query = "TRUNCATE TABLE `tbleppgrregistrants`";
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error build#02!');}
		echo " Database table is now empty.<br />";
	}
	$query = "SELECT id, domain FROM `tbldomains` WHERE registrar = 'eppgr'".$where." ORDER BY domain ASC";
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error build#03!');}
	if ($stmt->rowCount()!=1) $s = "";
	else $s = "s";
	if ($where) $new = " new";
	else $new = "";
	echo "Found ".$stmt->rowCount()."$new domain".$s." in your database.<br />";
	outputResult($stmt);
}

function completeDB() {
	global $eppgrdb;
	$query = "SELECT DISTINCT domain_id FROM `tbleppgrregistrants`";
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error build#04!');}
	$domainids = array();
	while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
		$domainids[] = $row[0];
	}
	$where = " AND id NOT IN (".implode(",", $domainids).")";
	buildDB($where);
}

function saveContact($config, $domainid, $type, $regid) {
	global $eppgrdb;
	echo "Found $type id {$regid}.<br />";
	eppgr_convertUTF8toISO($config, $regid);
	$query = "INSERT INTO `tbleppgrregistrants` (domain_id, type, registrant_id) "
			."VALUES ({$domainid}, '{$type}', '{$regid}') "
			."ON DUPLICATE KEY UPDATE registrant_id = '{$regid}'";
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error build#05!');}
	echo ucfirst($type)." id {$regid} has been successfully stored.<br />";
}

function setEarlyExpiration(&$ret, &$config) {
	global $eppgrdb;
	preg_match('/\d+\-\d+\-\d+/', $ret['exDate'], $matches);
	$exdate = $matches[0];
	$config['EarlyExpiration'] = (int) $config['EarlyExpiration'];
	$nddate = "DATE_SUB('$exdate', INTERVAL ".$config['EarlyExpiration']." DAY)";
	$query = "UPDATE `tbldomains` SET expirydate = '$exdate', nextduedate = $nddate, "
			."nextinvoicedate = $nddate WHERE id = ".$config['domainid'];
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error build#06!');}
}

function saveDomain() {
	$config = getConfig();
	$domainid = $_REQUEST['id'];
	$d = $_REQUEST['domain'];
	$config['domainid'] = $domainid;
	eppgr_convertISOtoUTF8($d);
	echo "Requesting information about ".$d."...<br />";
	$command = 'GetNameservers';
	$dot = strrpos($d, '.');
	$config['sld'] = substr($d, 0, $dot);
	$config['tld'] = substr($d, $dot+1);
	$ret = eppgr_getServerResponse($config, $command);
	if (array_key_exists('error', $config) and $config['error']) {
		eppgr_convertISOtoUTF8($config['error']);
		echo $config['error']."<br />";
		goToNextDomain();
		unset($config['error']);
		return;
	}
	if (is_array($ret) and $ret['registrant']) {
		eppgr_saveRegistrant($config, $domainid, 'registrant', $ret['registrant']);
		setEarlyExpiration($ret, $config);
	}
	if (is_array($ret) and array_key_exists('contact', $ret) and count($ret['contact'])) {
		$a = eppgr_getOrigContactTypes();
		foreach ($a as $b) {
			if (array_key_exists($b, $ret['contact']) and $ret['contact'][$b]) {
				eppgr_saveRegistrant($config, $domainid, $b, $ret['contact'][$b]);
			}
		}
	}
	if (array_key_exists('error', $config) and $config['error']) {
		setError($config['error']);
		unset($config['error']);
	}
	else {
		goToNextDomain();
	}
}

function goToNextDomain() {
    echo '<script language="javascript" type="text/javascript">'."\n";
	echo 'getNextDomain();'."\n";
    echo '</script>'."\n";
}

function getConfig() {
	global $eppgrdb;
	$query = "SELECT * FROM tbleppgrconfig WHERE id = 1";
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error build#07!');}
	$config = $stmt->fetch(PDO::FETCH_ASSOC);
	unset($config['id']);
	if (!is_array($config)) die('No config found!');
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
	$config['certfile'] = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'cacert.pem';

	$config['language'] = 'el';
	return $config;
}

function contactsDB($del=false) {
	global $eppgrdb;
	$config = getConfig();
	$where = '';
	if (array_key_exists('contacts', $_REQUEST) and trim($_REQUEST['contacts'])) {
		$domains = array();
		$cnt = array();
		$contacts = preg_split('/[\r\n]+/', trim($_REQUEST['contacts']));
		for ($i = count($contacts) - 1; $i >= 0; $i--) {
			eppgr_convertUTF8toISO($config, trim($contacts[$i]));
			if (!trim($contacts[$i]) or !eppgr_thisContactBelongsToMe($config, trim($contacts[$i])) or preg_match('/\W/', trim($contacts[$i]))) {
				$domains[] = preg_replace('/[^\w\-\.\x80-\xFF]/', '', $contacts[$i]);
			}
			else {
				$cnt[] = trim($contacts[$i]);
			}
		}
		$temp = array();
		if (count($domains)) {
			$temp[] = 'b.`domain` IN (\''.implode('\', \'', $domains).'\')';
		}
		if (count($cnt)) {
			$temp[] = 'a.`registrant_id` IN (\''.implode('\', \'', $cnt).'\')';
		}
		if (count($temp)) {
			$where = ' WHERE '.implode(' OR ', $temp);
		}
	}
	if ($del) {
		if ($where and count($domains)) {
			$query = "SELECT id FROM `tbldomains` WHERE `domain` IN ('".implode('\', \'', $domains)."')";
			try {$stmt = $eppgrdb->query($query);}
			catch(PDOException $ex) {die('MySQL error build#08!');}
			$query = "DELETE FROM `tbleppgrregistrants` WHERE `domain_id` = ";
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				try {$stmtextra = $eppgrdb->query($query.$row['id']);}
				catch(PDOException $ex) {die('MySQL error build#09!');}
			}
		}
		elseif ($where and count($cnt)) {
			$del = 2;
		}
		else {
			$query = "TRUNCATE TABLE `tbleppgrregistrants`";
			try {$stmt = $eppgrdb->query($query);}
			catch(PDOException $ex) {die('MySQL error build#10!');}
		}
	}
	else {
		$query = "SELECT a.*, b.domain FROM `tbleppgrregistrants` AS a LEFT JOIN `tbldomains` AS b ON a.domain_id = b.id".$where;
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error build#11!');}
	}
	outputContacts($config, $stmt, $del);
}

function eppgr_debase60($str) {
	$a = array(
		'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j',
		'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't',
		'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D',
		'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N',
		'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
		'Y', 'Z', '2', '3', '4', '5', '6', '7', '8', '9',
	);
	$num = explode('_', $str);
	$num = $num[1];
	$id = array_search(substr($num, -1), $a);
	$s = sprintf("%02s", array_search(substr($num, -2, 1), $a));
	$i = sprintf("%02s", array_search(substr($num, -3, 1), $a));
	$h = sprintf("%02s", array_search(substr($num, -4, 1), $a));
	$d = sprintf("%02s", array_search(substr($num, -5, 1), $a));
	$m = sprintf("%02s", array_search(substr($num, -6, 1), $a));
	$y = sprintf("%02s", array_search(substr($num, -7, 1), $a));
	$u = substr($num, 0, strlen($num) - 7);
	$domainid = 0;
	for ($i = 0; $i < strlen($u); $i++) {
		$domainid += array_search(substr($u, $i, 1), $a) * pow(60, $i);
	}
	return array($domainid, $d.'-'.$m.'-'.$y.' '.$h.':'.$d.':'.$s, $id);
}

function eppgr_getStrArray(&$array, &$ret) {
	if (is_array($array)) {
		foreach ($array as $k => $v) {
			if (is_array($array[$k])) eppgr_getStrArray($array[$k], $ret);
			else $ret[] = htmlspecialchars($v);
		}
	}
	else {
		$ret[] = htmlspecialchars($v);
	}
}

function outputContacts(&$config, &$stmt, $del) {
    echo '<script language="javascript" type="text/javascript">'."\n";
	if ($del) {
		if ($del == 2) {
			$msg = "Can\'t delete isolated contacts!";
		}
		else {
			$msg = 'Finished!';
		}
		echo "$('#contacts_info').html('".$msg."');";
	    echo '</script>'."\n";
		return;
	}
	global $eppgrdb;
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		eppgr_convertISOtoUTF8($row);
		$values = array();
		$v = unserialize(base64_decode($row['values']));
		unset($v['id']);
		eppgr_getStrArray($v, $values);
		$values = implode('<br />', $values);
		$b = array('', '', '');
		if (strlen($row['registrant_id']) > 11) {
			$b = eppgr_debase60($row['registrant_id']);
		}
		$x = array($row['registrant_id'], $row['type'], $row['domain'], $b[1], $values);
		echo "var a = new Array('".implode("', '", $x)."');\n";
		echo "contacts.push(a);\n";
	}
	echo "setContactsList();\n";
    echo '</script>'."\n";
}

function delordersDB() {
	global $eppgrdb;
	$config = getConfig();
	$tmp = array();
	if (array_key_exists('eppgrdeletependingorder', $_REQUEST) and
		is_array($_REQUEST['eppgrdeletependingorder']) and
		count($_REQUEST['eppgrdeletependingorder'])) {
		foreach ($_REQUEST['eppgrdeletependingorder'] as $o) {
			if (is_numeric($o)) {
				$tmp[] = $o;
			}
		}
	}
	if (count($tmp)) {
		$ids = implode(', ', $tmp);
		$query = "DELETE FROM `tbleppgrorders` WHERE `checked` = 0 AND `id` IN (".$ids.")";
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error build#12!');}
	}
    echo '<script language="javascript" type="text/javascript">'."\n";
	echo 'location.reload();'."\n";
    echo '</script>'."\n";
}

function execordersDB() {
	$config = getConfig();
	$mainurl = preg_replace('/build\/build\.php.*$/', '', eppgr_curPageURL());
	$url = $mainurl."cron/eppgr.cron04.php?pass=".$config['CronPassword'];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 100);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	$chret = curl_exec($ch);
	$error = curl_error($ch);
	curl_close($ch);

    echo '<script language="javascript" type="text/javascript">'."\n";
	if ($error) {
		echo 'alert("'.$error.'");'."\n";
	}
	else {
		echo 'alert("'.str_replace('<br />', '\n', $chret).'");'."\n";
	}
	echo 'location.reload();'."\n";
    echo '</script>'."\n";
}

?>