<?php
/*
 *  File: eppgr.php
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
catch(PDOException $ex) {die('MySQL error cron01#01!');}
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$charsets = explode('CHARSET=', $row['Create Table']);
$charset = 'latin1';
if (is_array($charsets) and count($charsets) and preg_match('/utf8/', $charsets[count($charsets)-1])) {
	$charset = 'utf8';
}

try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error cron01#01a!');}

$query = "SELECT * FROM tbleppgrconfig WHERE id = 1";
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error cron01#02!');}
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

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>EPPGR - CRON01</title>
</head>
<body>
<?php
/*
 *  File: cron/eppgr.cron01.php
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
$domains = array();
$ids = array();
if (array_key_exists('all', $_REQUEST) and $_REQUEST['all'] == 1) {
	$query = "
			SELECT *
			FROM `tbldomains`
			WHERE
				`registrar` = 'eppgr' AND
				(
					`expirydate` > DATE(NOW()) OR
					`expirydate` = '0000-00-00'
				)
			ORDER BY `expirydate` ASC
	";
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error cron01#03!');}
	$actiontime = gmdate('Y-m-d H:i:s');
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$query = "INSERT INTO `tbleppgrdates` (`domainid`, `action`, `actiontime`,
			`registrationdate`, `oldexpirydate`, `oldnextduedate`, `oldnextinvoicedate`,
			`checked`) VALUES(".$row['id'].", 'all', '".$actiontime
			."', '".$row['registrationdate']."', '".$row['expirydate']."', '"
			.$row['nextduedate']."', '".$row['nextinvoicedate']."', 0)";
		try {$stmtextra = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error cron01#04!');}
	}
}
$query = "
		SELECT *
		FROM `tbleppgrdates` AS a
		LEFT JOIN `tbldomains` AS b ON
			a.`domainid` = b.`id`
		WHERE
			a.`checked` = 0
		GROUP BY `domainid`
";
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error cron01#05!');}
$domains = array();
$dates = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	if ($row['domain']) {
		$domains[$row['domainid']] = $row['domain'];
		$dates[$row['domainid']]['expirydate'] = $row['expirydate'];
		$dates[$row['domainid']]['nextduedate'] = $row['nextduedate'];
		$dates[$row['domainid']]['nextinvoicedate'] = $row['nextinvoicedate'];
	}
}
eppgr_convertISOtoUTF8($domains);

$eppgrdb = null;

if (!count($domains)) die('Nothing to be done.');

$final = array();
$dc = 0;
foreach ($domains as $k => $d) {
	if (!$d or array_key_exists($d, $final)) {
		continue;
	}
	$dc++;
	echo 'Processing domain '.$d.'.<br />'."\n";
	$command = 'GetNameservers';
	$config['domainid'] = $k;
	$ret = eppgr_getServerResponse($config, $command);
	if (is_array($ret) and array_key_exists('exDate', $ret) and $ret['exDate']) {
		preg_match('/^\s*(\d+-\d+-\d+)/', $ret['exDate'], $matches);
		$final[$d]['ex'] = trim($matches[1]);
		preg_match('/^\s*(\d+-\d+-\d+)/', $ret['crDate'], $matches);
		$final[$d]['cr'] = trim($matches[1]);
		echo '&nbsp;&nbsp;&nbsp;&nbsp;Creation Date: '.$final[$d]['cr'].'.<br />'."\n";
		echo '&nbsp;&nbsp;&nbsp;&nbsp;Expiration Date: '.$final[$d]['ex'].'.<br />'."\n";
	}
	else {
		echo '&nbsp;&nbsp;&nbsp;&nbsp;No details could be retrieved for domain '.$d.'.<br />'."\n";
	}
	if (array_key_exists('error', $config) and $config['error']) unset($config['error']);
	if ($dc == 5) {
		eppgr_Disconnect($config);
		emav_processFinal($config, $domains, $final, $db_host, $db_username, $db_password, $db_name);
		$dc = 0;
		$final = array();
	}
}
if ($dc) {
	eppgr_Disconnect($config);
	emav_processFinal($config, $domains, $final, $db_host, $db_username, $db_password, $db_name);
	$dc = 0;
	$final = array();
}

echo 'Command executed successfully.';

function emav_processFinal(&$config, &$domains, &$final, &$db_host, &$db_username, &$db_password, &$db_name) {
	if (count($final)) {
		try {$eppgrdb = new PDO('mysql:host='.$db_host.';dbname='.$db_name.';charset=utf8', $db_username, $db_password);}
		catch(PDOException $ex) {die('Could not connect to database: '.$ex->getMessage());}
	
		foreach ($domains as $k => $v) {
			if (!array_key_exists($v, $final)) {
				continue;
			}
			$ex = $final[$v]['ex'];
			$cr = $final[$v]['cr'];
			if (!$ex or !$cr) {
				continue;
			}
			$config['EarlyExpiration'] = (int) $config['EarlyExpiration'];
			if ($config['EarlyExpiration'] > 0)
				$nddate = "DATE_SUB('$ex', INTERVAL ".$config['EarlyExpiration']." DAY)";
			else $nddate = "'$ex'";
			if ($config['ExtendExpiration'] == 'on' or $config['ExtendExpiration'] == 1) {
				$myex = "DATE_ADD('$ex', INTERVAL 15 DAY)";
			}
			else {
				$myex = "'$ex'";
			}
			$query = "
				UPDATE `tbldomains`
				SET
					expirydate = $myex,
					nextduedate =
						IF(
							(
								'$ex' < nextduedate OR
								nextduedate = '0000-00-00'
							),
							$nddate,
							nextduedate
						), 
					nextinvoicedate =
						IF(
							(
								'$ex' < nextinvoicedate OR
								nextinvoicedate = '0000-00-00'
							),
							$nddate,
							nextinvoicedate
						)
				WHERE
					id = $k
			";
			try {$stmt = $eppgrdb->query($query);}
			catch(PDOException $ex) {die('MySQL error cron01#06!');}
			$query = "SELECT * FROM `tbldomains` WHERE id = $k";
			try {$stmt = $eppgrdb->query($query);}
			catch(PDOException $ex) {die('MySQL error cron01#07!');}
			$ddd = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!is_array($ddd) or !array_key_exists('id', $ddd) or !$ddd['id']) {
				return;
			}
			$query = "
				UPDATE `tbleppgrdates`
				SET
					`creationdate` = '$cr',
					`newexpirydate` = '{$ddd['expirydate']}',
					`newnextduedate` = '{$ddd['nextduedate']}',
					`newnextinvoicedate` = '{$ddd['nextinvoicedate']}',
					`checked` = 1
				WHERE
					`domainid` = $k AND
					`checked` = 0
			";
			try {$stmt = $eppgrdb->query($query);}
			catch(PDOException $ex) {die('MySQL error cron01#08!');}
			echo 'Domain ID '.$k.' ['.$v.'] has been modified as follows:<br />'."\n";
			echo '&nbsp;&nbsp;&nbsp;&nbsp;expirydate:<br />'."\n";
			echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
				.$dates[$k]['expirydate'].'&nbsp;=>&nbsp;'
				.$ddd['expirydate'].'<br />'."\n";
			echo '&nbsp;&nbsp;&nbsp;&nbsp;nextduedate:<br />'."\n";
			echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
				.$dates[$k]['nextduedate'].'&nbsp;=>&nbsp;'
				.$ddd['nextduedate'].'<br />'."\n";
			echo '&nbsp;&nbsp;&nbsp;&nbsp;nextinvoicedate:<br />'."\n";
			echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
				.$dates[$k]['nextinvoicedate'].'&nbsp;=>&nbsp;'
				.$ddd['nextinvoicedate'].'<br />'."\n";
		}
		$eppgrdb = null;
	}
}

?>
</body>
</html>