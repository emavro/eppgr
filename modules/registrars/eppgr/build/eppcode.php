<?php
/*
 *  File: build/eppcode.php
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
include_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'eppgr.aes.php');
include_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'eppgr.php');

try {$eppgrdb = new PDO('mysql:host='.$db_host.';dbname='.$db_name.';charset=utf8', $db_username, $db_password);}
catch(PDOException $ex) {die('Could not connect to database: '.$ex->getMessage());}

$query = 'SHOW CREATE TABLE tbldomains';
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error eppcode#01!');}
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$charsets = explode('CHARSET=', $row['Create Table']);
$charset = 'latin1';
if (is_array($charsets) and count($charsets) and preg_match('/utf8/', $charsets[count($charsets)-1])) {
	$charset = 'utf8';
}

try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error eppcode#01a!');}

$query = "SELECT * FROM tbleppgrconfig WHERE id = 1";
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error eppcode#02!');}
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

$msg = '';
$types = array("text/comma-separated-values", "text/csv");
if (array_key_exists('client', $_REQUEST) and is_array($_REQUEST['client']) and count($_REQUEST['client']) == 1 and is_numeric($_REQUEST['client'][0]) and array_key_exists("datafile", $_FILES) and in_array($_FILES["datafile"]["type"], $types) and $_FILES["datafile"]["size"] <= 100000) {
	if ($_FILES["datafile"]["error"] > 0) {
		$msg = "Error: " . $_FILES["datafile"]["error"] . "<br />";
	}
	else {
		$domains = array();
		$handle = @fopen($_FILES["datafile"]["tmp_name"], "r");
		if ($handle) {
			while (!feof($handle)) {
				$line = trim(fgets($handle));
				if ($line and strpos($line, ';')) {
					$name = preg_replace('/[^\w\-\.\x80-\xFF]/', '', trim(substr($line, 0, strpos($line, ';'))));
					$pass = trim(substr($line, strpos($line, ';') + 1));
					if (substr($pass, 0, 1) == '"') {
						$pass = substr($pass, 1, strlen($pass) - 2);
						$newpass = '';
						for ($i = 0; $i < strlen($pass); $i++) {
							if (substr($pass, $i, 1) == '"') {
								$i++;
							}
							$newpass .= substr($pass, $i, 1);
						}
						$pass = $newpass;
					}
					if ($name and $pass) {
						eppgr_convertISOtoUTF8($name);
						eppgr_convertISOtoUTF8($pass);
						$domains[$name] = $pass;
					}
				}
			}
			fclose($handle);
		}
		$mainurl = str_replace('modules/registrars/eppgr/build/eppcode.php', '', eppgr_curPageURL());
		$url = $mainurl."includes/api.php";
		$postfields = array();
		$postfields["action"] = "addorder";
		$postfields["clientid"] = $_REQUEST['client'][0];
		eppgr_getAdminPass($config, $postfields);
		eppgr_getPaymentMethod($config, $postfields);
		$counter = 0;
		foreach ($domains as $name => $pass) {
			eppgr_convertUTF8toISO($config, $name);
			eppgr_convertUTF8toISO($config, $pass);
			$postfields["domaintype[$counter]"] = "transfer";
			$postfields["domain[$counter]"] = $name;
			$postfields["regperiod[$counter]"] = "2";
			$postfields["eppcode[$counter]"] = $pass;
			$counter++;
		}
		$results = eppgr_sendOrder($url, $postfields);
		if (array_key_exists("result", $results) and $results["result"] == "success") {
			$admin = (isset($customadminpath) and $customadminpath) ? $customadminpath : 'admin';
			$orderurl = $mainurl.$admin."/orders.php?action=view&id=".$results["orderid"];
			$msg = "Order has been placed successfully.<br />\n";
			$msg .= '<a href="'.$orderurl.'">'.$orderurl.'</a>';
		}
		elseif(array_key_exists("message", $results)) {
			$msg = $results["message"];
		}
	}
}
elseif ((!array_key_exists('client', $_REQUEST) or !is_array($_REQUEST['client']) or count($_REQUEST['client']) != 1 or !is_numeric($_REQUEST['client'][0])) and array_key_exists("datafile", $_FILES) and in_array($_FILES["datafile"]["type"], $types) and $_FILES["datafile"]["size"] <= 100000) {
	$msg = "Invalid client.";
}
elseif (array_key_exists("datafile", $_FILES) and $_FILES["datafile"]["name"]) {
	$msg = "Invalid file.";
}
elseif ((array_key_exists('client', $_REQUEST) and is_array($_REQUEST['client']) and count($_REQUEST['client'])) or
		(array_key_exists('domain', $_REQUEST) and is_array($_REQUEST['domain']) and count($_REQUEST['domain']))) {
	$where = array();
	if (array_key_exists('client', $_REQUEST) and is_array($_REQUEST['client']) and count($_REQUEST['client'])) {
		$client = $_REQUEST['client'];
		$all = false;
		for ($i = count($client) - 1; $i >= 0; $i--) {
			if (!is_numeric($client[$i])) {
				array_splice($client, $i, 1);
			}
			if ($client[$i] * 1 === 0) {
				$all = true;
			}
		}
		if (!$all) {
			$where[] = 'userid IN('.implode(',', $client).')';
		}
	}
	if (array_key_exists('domain', $_REQUEST) and is_array($_REQUEST['domain']) and count($_REQUEST['domain'])) {
		$domain = $_REQUEST['domain'];
		$all = false;
		for ($i = count($domain) - 1; $i >= 0; $i--) {
			if (!is_numeric($domain[$i])) {
				array_splice($domain, $i, 1);
			}
			if ($domain[$i] * 1 === 0) {
				$all = true;
			}
		}
		if (!$all) {
			$where[] = 'id IN('.implode(',', $domain).')';
		}
	}
	if (count($where)) {
		$where = array('('.implode(' OR ', $where).')');
	}
	$where[] = 'registrar = "eppgr"';
	$where[] = 'status = "Active"';
	$where = 'WHERE '.implode(' AND ', $where);

	$contents = '';
	$query = 'SELECT id FROM tbldomains '.$where.' ORDER BY domain ASC';
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppcode#03!');}
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		eppgr_convertISOtoUTF8($row);
		$command = 'GetNameservers';
		$config['domainid'] = $row['id'];
		$ret = eppgr_getServerResponse($config, $command);
		if (is_array($ret) and array_key_exists('pw', $ret) and $ret['pw']) {
			if (preg_match('/[";]/', $ret['pw'])) {
				$pass = '"';
				for ($i = 0; $i < strlen($ret['pw']); $i++) {
					if (substr($ret['pw'], $i, 1) == '"') {
						$pass .= substr($ret['pw'], $i, 1);
					}
					$pass .= substr($ret['pw'], $i, 1);
				}
				$pass .= '"';
			}
			else {
				$pass = $ret['pw'];
			}
			$contents .= '<tr><td>'.$ret['name'].'</td><td>'.$pass.'</td></tr>';
			$done = true;
		}
		if (array_key_exists('error', $config) and $config['error']) unset($config['error']);
	}
	if (!$done) {
		$contents = "<tr><td>No active domains found with these criteria.</td></tr>";
	}
			header('Content-type: application/vnd.ms-excel');
			header('Content-Disposition: attachment; filename="eppcode.xls"');
			header ("Content-Transfer-Encoding: binary");
			header("Pragma: no-cache");
			header("Expires: 0");
			echo <<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="el-gr" lang="el-gr"><head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>EPPGR Mass Transfers for WHMCS</title>
</head>
<body>
<table>
END;
echo $contents;
echo <<<END
</table>
</body>
</html>
END;
exit;
}

$query = 'SELECT * FROM tblclients ORDER BY lastname, firstname ASC';
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error eppcode#04!');}
$clients = array('All' => 0);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	$name = $row['lastname'].' '.$row['firstname'];
	$clients[$name] = $row['id'];
}

$query = 'SELECT * FROM tbldomains WHERE status = "Active" ORDER BY domain ASC';
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error eppcode#05!');}
$domains = array('All' => 0);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	$domains[$row['domain']] = $row['id'];
}

?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>EPPGR Mass Transfers for WHMCS</title>
		<script type="text/javascript" src="../../../../includes/jscript/jquery.js"></script>
		<script language="JavaScript" src="js/eppcode.js"></script>
		<link href="css/build.css" rel="stylesheet" type="text/css" />
	</head>
	<body>
		<div id="build_wrapper">
			<div id="title">
				<h3>EPPGR Mass Transfers for WHMCS</h3>
			</div>
			<form enctype="multipart/form-data" action="./eppcode.php" method="post">
				<div id="build_input">
					<select name="client[]" id="client" multiple="multiple" size=10>
						<?php foreach ($clients as $k => $v): ?>
						<option value="<?php echo $v; ?>"><?php echo $k; ?></option>
						<?php endforeach; ?>
					</select>
					<select name="domain[]" id="domain" multiple="multiple" size=10>
						<?php foreach ($domains as $k => $v): ?>
						<option value="<?php echo $v; ?>"><?php echo $k; ?></option>
						<?php endforeach; ?>
					</select><br />
					<input type="hidden" name="MAX_FILE_SIZE" value="100000" />
					<input type="file" id="datafile" name="datafile" size="40">
				</div>
				<div id="build_buttons">
					<input type="button" value="Clear" onclick="javascript: execClear(this);" />
					<input type="button" value="Submit" onclick="javascript: execSubmit(this);" />
				</div>
			</form>
			<div id="results">
				<?php echo $msg; ?>
			</div>
	</body>
</html>
