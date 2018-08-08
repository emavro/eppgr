<html>
	<head>
		<link href="css/build.css" rel="stylesheet" type="text/css" />
	</head>
	<body>
		<div id="build_wrapper">
			<div id="">
				<h3>EPPGR Registrar Gateway for WHMCS</h3>
			</div>
			<div id="build_center">
<?php
/*
 *  File: build/patchhead.php
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

$config = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' .
		DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'configuration.php');
include $config;
include_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'eppgr.aes.php');

try {$eppgrdb = new PDO('mysql:host='.$db_host.';dbname='.$db_name.';charset=utf8', $db_username, $db_password);}
catch(PDOException $ex) {die('Could not connect to database: '.$ex->getMessage());}

$query = "SELECT * FROM tbleppgrconfig WHERE id = 1";
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error patch#01!');}
$config = $stmt->fetch(PDO::FETCH_ASSOC);
unset($config['id']);
if (!is_array($config) or !array_key_exists('CronPassword', $config) or !$config['CronPassword']) die('No password found!');
$z = eppgr_getAESObject($config);
foreach ($config as $k => $v) {
	$z->AESDecode($config[$k]);
	$config[$k] = $z->cipher;
}
$config['certfile'] = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'cacert.pem');

$query = "SELECT value FROM `tblconfiguration` WHERE setting = 'Template'";
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error patch#02!');}
$template = $stmt->fetch(PDO::FETCH_ASSOC);
if (is_array($template) and array_key_exists('value', $template)) {
	$template = $template['value'];
}
else {
	$template = '';
}

if ($template) {
	echo "Processing template $template...<br />";
}
else {
	die('Error: No template found!');
}

define('DS', DIRECTORY_SEPARATOR);
$tmplfolder = str_replace('modules/registrars/eppgr/build', 'templates'.DS.$template, dirname(__FILE__));

eppgr_check('folder', $tmplfolder);

function eppgr_check($type, $item) {
	if ($type == 'folder' and !is_dir($item)) {
		die('Error: '.ucfirst($type).' '.$item.' does not exist.');
	}
	if ($type == 'file' and !is_file($item)) {
		die('Error: '.ucfirst($type).' '.$item.' does not exist.');
	}
	if (!is_readable($item)) {
		die('Error: '.ucfirst($type).' '.$item.' is not readable.');
	}
	if (!is_writeable($item)) {
		die('Error: Cannot write files in '.$type.' '.$item);
	}
	echo ucfirst($type)." $item found!<br />";
}

function eppgr_patched($str, $file) {
	$contents = file_get_contents($file);
	if (!$contents) die('Error: File $file is empty!');
	if (strpos($contents, $str)) return true;
	else return false;
}

function eppgr_patch($array, $file) {
	$contents = file_get_contents($file);
	$rn = false;
	if (preg_match('/\r\n/', $contents)) $rn = true;
	$ret = true;
	$addreturn = false;
	foreach ($array['after'] as $fnd => $rpl) {
		if (substr($fnd, 0, 1) == '.' and substr($fnd, -1) == '|') {
			$addreturn = true;
			if (preg_match('/^\.'.str_replace('.', '\.', substr($fnd, 1, -1)).'\|.+$/m', $contents)) {
				$contents = preg_replace('/^\.'.str_replace('.', '\.', substr($fnd, 1, -1)).'\|.+$/m', $fnd.$rpl, $contents);
			}
			else {
				$contents .= "\n".$fnd.$rpl;
			}
		}
		else {
			eppgr_checkreplace($ret, $rn, $fnd, $rpl, $contents);
			$contents = str_replace($fnd, $fnd.$rpl, $contents);
		}
	}
	foreach ($array['befor'] as $fnd => $rpl) {
		eppgr_checkreplace($ret, $rn, $fnd, $rpl, $contents);
		$contents = str_replace($fnd, $rpl.$fnd, $contents);
	}
	foreach ($array['chang'] as $fnd => $rpl) {
		eppgr_checkreplace($ret, $rn, $fnd, $rpl, $contents);
		$contents = str_replace($fnd, $rpl, $contents);
	}
	foreach ($array['skpit'] as $fnd => $rpl) {
		eppgr_checkreplace($ret, $rn, $fnd, $fnd, $contents);
		$pos = strpos($contents, $fnd);
		if ($pos) {
			$pos += strlen($fnd);
			foreach ($rpl as $fndin => $rplin) {
				$newcont = substr($contents, $pos);
				eppgr_checkreplace($ret, $rn, $fndin, $rplin, $newcont);
				$contents = substr($contents, 0, $pos).str_replace($fndin, $rplin, $newcont);
			}
		}
	}
	if ($ret and $addreturn) $contents .= "\n";
	if (!$ret) echo "<span style=\"color: red;\">File $file cannot be patched!</span><br />";
	return $ret ? $contents : '';
}

function eppgr_checkreplace(&$ret, &$rn, &$fnd, &$rpl, &$contents) {
	if ($rn) $rpl = preg_replace('/\n/', "\r\n", $rpl);
	if (!strpos($contents, $fnd)) $ret = false;
}

function eppgr_getfile($tmplfolder, $filename) {
	if (strpos($filename, 'adddomain.tpl')) {
		return substr($tmplfolder, 0, strrpos($tmplfolder, DS)).DS.'orderforms'.DS.str_replace('/', DS, $filename);
	}
	else if ($filename == 'whoisservers.php') {
		$tmplfolder = substr($tmplfolder, 0, strrpos($tmplfolder, DS));
		return substr($tmplfolder, 0, strrpos($tmplfolder, DS)).DS.'includes'.DS.$filename;;
	}
	else if (strpos($filename, '/')) {
		if (strpos($tmplfolder, substr($filename, 0, strpos($filename, '/')))) {
			return $tmplfolder.DS.substr($filename, strpos($filename, '/') + 1);
		}
		else {
			return 0;
		}
	}
	else {
		return $tmplfolder.DS.$filename;
	}
}

function eppgr_writefile(&$array) {
	$fh = fopen($array['file'], 'w') or die("Error: Cannot open file ".$array['file']);
	fwrite($fh, $array['contents']);
	fclose($fh);
	echo "<span style=\"color: blue;\">File ".$array['file']." has been patched successfully!</span><br />";
}

function eppgr_isSSL(){

  if(isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 1) /* Apache */ {
     return TRUE;
  } elseif (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on') /* IIS */ {
     return TRUE;
  } elseif (isset($_SERVER['SERVER_PORT']) and $_SERVER['SERVER_PORT'] == 443) /* others */ {
     return TRUE;
  } else {
  return FALSE; /* just using http */
  }

}

?>