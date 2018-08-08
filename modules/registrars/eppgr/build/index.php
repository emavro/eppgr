<?php
/*
 *  File: build/index.php
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
include_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'eppgr.php');

try {$eppgrdb = new PDO('mysql:host='.$db_host.';dbname='.$db_name.';charset=utf8', $db_username, $db_password);}
catch(PDOException $ex) {die('Could not connect to database: '.$ex->getMessage());}

$query = 'SHOW CREATE TABLE tbldomains';
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error index#01!');}
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$charsets = explode('CHARSET=', $row['Create Table']);
$charset = 'latin1';
if (is_array($charsets) and count($charsets) and preg_match('/utf8/', $charsets[count($charsets)-1])) {
	$charset = 'utf8';
}

try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error index#01a!');}

$query = "SELECT * FROM tbleppgrconfig WHERE id = 1";
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error index#02!');}
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

$reqfile = "http".((isset($_SERVER['HTTPS']) and $_SERVER['HTTPS']) ? "s" : "")."://".$_SERVER['SERVER_NAME'].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/'))."/build.php";
$twfile = str_replace('build/build.php', 'js/tw-sack.js', $reqfile);
$cronfile = str_replace('build/build.php', 'cron/eppgr.cron01.php?pass='.$config['CronPassword'], $reqfile);
$crons[] = 'php -q '.substr(dirname(__FILE__), 0, strrpos(dirname(__FILE__), '/')).DIRECTORY_SEPARATOR.'cron'.DIRECTORY_SEPARATOR.'eppgr.cron01.php?pass='.$config['CronPassword'];
$crons[] = 'lynx -accept_all_cookies -dump \''.$cronfile.'\'';
$crons[] = 'wget --delete-after \''.$cronfile.'\'';
$crons[] = 'curl --silent --compressed \''.$cronfile.'\'';

$query = "SELECT a.*, b.domain, b.userid FROM tbleppgrorders AS a "
	."LEFT JOIN tbldomains AS b ON a.domain_id = b.id "
	."WHERE a.checked = 0 ORDER BY id DESC";
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error index#03!');}
$pending = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	$z->AESDecode($row['data']);
	$row['data'] = unserialize($z->cipher);
	$pending[] = $row;
}
$url = preg_replace('/modules\/registrars\/eppgr\/build\/index\.php.*$/', '', eppgr_curPageURL());
if (isset($customadminpath) and $customadminpath) {
	$url .= $customadminpath . '/';
}
else {
	$url .= 'admin/';
}
$orderurl = $url . 'orders.php?action=view&id=';
$domainurl = $url . 'clientsdomains.php?userid=xxx&domainid=yyy';

?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>EPPGR Registrar Gateway for WHMCS</title>
		<script language="JavaScript" type="text/javascript">
			var request_file = "<?php echo $reqfile; ?>";
		</script>
		<script language="JavaScript" src="<?php echo $twfile; ?>"></script>
		<script type="text/javascript" src="../../../../includes/jscript/jquery.js"></script>
		<script language="JavaScript" src="js/build.js"></script>
		<link href="css/build.css" rel="stylesheet" type="text/css" />
	</head>
	<body>
		<div id="build_wrapper">
			<div id="title">
				<h3>EPPGR Registrar Gateway for WHMCS</h3>
			</div>
			<div id="cron">
				Cron jobs:<br />
				<pre><?php
				foreach ($crons as $cron) {
					echo "$cron\n";
				}
				?></pre>
			</div>
			<div id="build_buttons">
				<input type="button" id="build" value="Build Database" onclick="javascript: performTasks('build');" />
				<input type="button" id="complete" value="Complete Database" onclick="javascript: performTasks('complete');" />
				<input type="button" id="stop" value="Stop" disabled="disabled" onclick="javascript: stopdomains = true;" />
			</div>
			<div id="build_center"></div>
			<div id="build_info">
				<div id="build_total"></div>
				<div id="build_messages"></div>
			</div>
			<?php if(count($pending)): ?>
			<div id="cron">
				Pending orders:
			</div>
			<table id="penordtable" width="100%" style="text-align: center">
				<tr>
					<th><input type="checkbox" name="eppgrdeleteall" value="" onclick="javascript: selPenOrd();" /></th>
					<th>Order Number</th>
					<th>Domain</th>
					<th>Product</th>
					<th>Details</th>
					<th>Error</th>
				</tr>
				<?php foreach ($pending as $p): ?>
				<?php $domurl = str_replace('yyy', $p['domain_id'], str_replace('xxx', $p['userid'], $domainurl));?>
					<tr>
						<td><input type="checkbox" value="<?php echo $p['id']; ?>" name="eppgrdeletependingorder[]" /></td>
						<td><a href="<?php echo $orderurl.$p['order_id']; ?>"><?php echo $p['order_id']; ?></a></td>
						<td><a href="<?php echo $domurl; ?>"><?php echo $p['domain']; ?></a></td>
						<td><?php echo $p['product']; ?></td>
						<td><?php
						if (array_key_exists('record', $p['data']) and $p['data']['record']) {
							echo $p['data']['name'];
						}
						else {
							if (array_key_exists('org', $p['data']['loc']) and $p['data']['loc']['org']) {
								echo $p['data']['loc']['name'].'<br />'.$p['data']['loc']['org'];
							}
							else {
								echo $p['data']['loc']['name'];
							}
						}
						?></td>
						<td><?php
							if ($p['error']) {
								$z->AESDecode($p['error']);
								echo $z->cipher;
							}
						?></td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<td colspan="6">
						<input type="button" id="delpenord" value="Delete Selected Pending Orders" onclick="javascript: delPenOrd();" />
						<input type="button" id="execpenord" value="Execute All Pending Orders" onclick="javascript: execPenOrd();" />
					</td>
				</tr>
			</table>
			<?php endif; ?>
			<div id="cron">
				Contact analyzer:
			</div>
			<div id="build_input">
				<textarea id="contactstextarea" name="contacts" cols="60" rows="8"></textarea>
			</div>
			<div id="build_buttons">
				<input type="button" id="build" value="Analyze contacts" onclick="javascript: getContactData(0);" />
				<input type="button" id="build" value="Delete DB contacts" onclick="javascript: getContactData(1);" />
			</div>
			<div id="contacts_info"></div>
		</div>
	</body>
</html>
