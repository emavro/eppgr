<?php
/*
 *  File: lib/eppgr.whois.php
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

$eppgrnow = date('Y-m-d H:i:s', time() - 1);

$config = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' .
		DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'configuration.php');
include $config;
include_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'eppgr.php');
include_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'eppgr.aes.php');
include_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'idna_convert.class.php');

try {$eppgrdb = new PDO('mysql:host='.$db_host.';dbname='.$db_name.';charset=utf8', $db_username, $db_password);}
catch(PDOException $ex) {die('Could not connect to database: '.$ex->getMessage());}

$query = "SELECT * FROM tbleppgrconfig WHERE id = 1";
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error whois#01!');}
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
$config['certfile'] = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'cacert.pem';
$config['language'] = 'en';
$command = 'CheckDomain';
$browsers = '(Chrome)|(Safari)|(Konqueror)';

eppgr_Debug($config, 'EPPGRWHOIS');

if (array_key_exists('tlds', $_POST) and is_array($_POST['tlds'])) {
	$query = "SELECT value FROM tblconfiguration WHERE setting = 'BulkDomainSearchEnabled'";
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error whois#02!');}
	$bdse = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($bdse['value'] == 'on' or $bdse['value'] == 1) {
		$query = "SELECT value FROM tblconfiguration WHERE setting = 'BulkCheckTLDs'";
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error whois#03!');}
		$extratlds = $stmt->fetch(PDO::FETCH_ASSOC);
		if ($extratlds['value']) {
			$etlds = explode(',', $extratlds['value']);
			foreach ($etlds as $etld) {
				if (!in_array($etld, $_POST['tlds']) and
					((preg_match('/[\x80-\xFF]+/', $_POST['domain']) and preg_match('/\.(gr|ελ)$/', $etld) and !preg_match('/\.co\.(gr|ελ)$/', $etld)) or
					(!preg_match('/[\x80-\xFF]+/', $_POST['domain']) and (!preg_match('/\.(gr|ελ)$/', $etld) or preg_match('/\.co\.(gr|ελ)$/', $etld))))) {
					$_POST['tlds'][] = $etld;
				}
			}
		}
	}
}

$userpass = preg_replace('/\W/', '', $_REQUEST['pass']);
if ($config['CronPassword'] != $userpass) {
	@session_start();
	$_SESSION['domaincheckerwhois'] = array();
	session_write_close();
	if (!array_key_exists('extra', $_REQUEST) or !$_REQUEST['extra']) {
		echo 'You are not allowed to access this page.';
		exit;
	}
	$extra = preg_replace('/\W/', '', $_REQUEST['extra']);
	$z->AESDecode($extra);
	$extra = explode('::', $z->cipher);
	$remaddr = explode('.', $_SERVER['REMOTE_ADDR']);
	for ($i = 3, $j = 0; $i < 27; $i += 6, $j++) {
		if (substr($z->cipher, $i, 3) != sprintf('%03s', $remaddr[$j])) {
			echo 'You are not allowed to access this page.';
			exit;
		}
	}
	$sess = '';
	for ($i = 2; $i < strlen(substr($z->cipher, 27, -2)); $i += 4) {
		$sess .= substr(substr($z->cipher, 27, -2), $i, 2);
	}
	if ($sess != $_COOKIE['PHPSESSID']) {
		echo 'You are not allowed to access this page.';
		exit;
	}
	if (array_key_exists('goto', $_REQUEST) and $_REQUEST['goto'] == 'cart') {
		$query = str_replace('goto=cart', '', trim($_SERVER['QUERY_STRING']));
		if ($query) $query = '?' . substr($query, 1);
		if (array_key_exists('language', $_POST) and $_POST['language']) {
			$addlang = 'language='.urlencode($_POST['language']);
			if ($query) $query .= '&' . $addlang;
			else $query = '?' . $addlang;
		}
		$trans = $_REQUEST['domain'] == 'transfer' ? 1 : 0;
		$url = eppgr_getCleanURL().'cart.php'.$query;
		$post = array();
		$utf8 = '';
		if (array_key_exists('sld', $_REQUEST) and $_REQUEST['sld']) {
			$domain = trim($_POST['sld']);
			preg_match('/[\w\-\.]+/', $domain, $matches);
			preg_match('/[\w\-\.\x80-\xFF]+/', $domain, $matches);
			$domain = $matches[0];
			preg_match('/[\w\.]+/', $_POST['tld'], $ttt);
			$tld = $ttt[0];
			if (count($matches) and preg_match('/\.(gr|ελ)$/', $tld) and !preg_match('/\.co\.(gr|ελ)$/', $tld) and preg_match('/[\x80-\xFF]+/', $domain)) {
				$z->AESEncode($matches[0]);
				$domain = 'eppgr-'.$trans.'-encoded-'.$z->cipher;
			}
			$post[] = 'sld='.$domain;
			$post[] = 'tld='.$tld;
			if ($_POST['code']) $post[] = 'code='.urlencode($_POST['code']);
		}
		if (count($post)) $post = implode('&', $post);
		else $post = '';
		$ret = preg_replace('/^.+(<div id="eppgr_div".+?<\/div>).+$/is', "$1", eppgr_communicateWithServer($url, $post));
		if (!preg_match('/'.$browsers.'/', $_SERVER['HTTP_USER_AGENT']) or strtoupper($config['charset']) == 'UTF-8') eppgr_convertISOtoUTF8($ret);
		else eppgr_convertUTF8toISO($config, $ret);
		if ($ret['error']) echo $ret['error'];
		else {
			if (!preg_match('/'.$browsers.'/', $_SERVER['HTTP_USER_AGENT']) or strtoupper($config['charset']) == 'UTF-8') eppgr_convertISOtoUTF8($matches[0]);
			else eppgr_convertUTF8toISO($config, $matches[0]);
			$ret['page'] = str_replace('eppgr-1-encoded-'.$z->cipher, $matches[0], $ret['page']);
			$ret['page'] = str_replace('eppgr-0-encoded-'.$z->cipher, $matches[0], $ret['page']);
			if (preg_match('/[\x80-\xFF]+/', $matches[0])) {
				preg_match_all('/<tr>\s*<td>\s*.+?\.(\w+)<\/td>/isu', $ret['page'], $checkotherdoms);
				foreach ($checkotherdoms[1] as $ccc) {
					if ($ccc != 'gr') {
						$ret['page'] = preg_replace('/\s*<tr>\s*<td>\s*[^>]+?\.'.$ccc.'<\/td>.+?<\/tr>\s*/isu', '', $ret['page']);
					}
				}
			$ret['page'] = preg_replace('/\s*<tr>\s*<td>\s*[^>]+?\.co\.gr<\/td>.+?<\/tr>\s*/isu', '', $ret['page']);
			}
			$config['WHOISExtraDomains'] = str_replace(' ', '', $config['WHOISExtraDomains']);
			if (!preg_match('/textgreen/', $ret['page']) and $config['WHOISExtraDomains'] and !$trans) {
				$origcipher = $z->cipher;
				eppgr_getExtraDomains($matches[0], $ret['page'], array('tlds[]='.$tld), $trans);
				$z->cipher = $origcipher;
				$ret['page'] = preg_replace('/<tr>\s+<td>\s*(<input[^>]+>).+?<\/td>\s*(<td>.+?<\/td>\s*<td[^>]*>)/s', "<tr>$2$1 ", $ret['page']);
			}
			echo $ret['page'];
		}
		updateWhoIsLog($z->cipher.$tld, $matches[0].$tld);
		exit;
	}
	$search = '';
	if (array_key_exists('search', $_REQUEST)) {
		preg_match('/\w+/', $_REQUEST['search'], $matches);
		$search = $matches[0];
	}
	$trans = $search == 'bulktransfer' ? 1 : 0;
	switch($search) {
		case 'bulk':
		case 'bulktransfer':
			eppgr_bulkDomains($search, $trans);
		break;
	
		default:
			eppgr_oneDomain($trans);
		break;
	}
}
else {
	$whois = true;
	if (array_key_exists('nowhois', $_REQUEST) and $_REQUEST['nowhois'] == 1) {
		$whois = false;
	}
	$trans = false;
	$domain = trim($_REQUEST['domainName']);
	preg_match('/[\w\-\.]+/', $domain, $matches);
	if (strlen($matches[0]) == strlen($domain)) $utf8 = 0;
	else $utf8 = 1;
	preg_match('/[\w\-\.\x80-\xFF]+/', $domain, $matches);
	$domain = $matches[0];
	$cipher = '';
	if (preg_match('/^eppgr-(\d)-encoded-/', $domain, $ttt)) {
		$whois = false;
		$trans = $ttt[1];
		$cipher = str_replace('eppgr-'.$trans.'-encoded-', '', $domain);
		$firstdot = strpos($cipher, '.');
		$tld = substr($cipher, $firstdot);
		$cipher = substr($cipher, 0, $firstdot);
		$z->AESDecode($cipher);
		$domain = $z->cipher.$tld;
	}
	elseif (preg_match('/^xn--/', $domain)) {
		$idn = new idna_convert();
		$domain = $idn->decode($domain);
	}
	eppgr_convertISOtoUTF8($domain);
	$domain = mb_strtolower($domain, "UTF-8");
	$post = '';

	if ($config['WHOISOnly'] == 'on' or $config['WHOISOnly'] == 1 or $_REQUEST['whoisonly'] == 1) {
		$url = eppgr_getSearchURL($config, $domain);
		$ret = eppgr_communicateWithServer($url, $post);
		eppgr_convertUTF8toISO($config, $ret);
		echo $ret['page'];
		return;
	}

	$command = 'CheckDomain';
	$data['name'] = $domain;
	$ret = eppgr_getServerResponse($config, $command, $data);
	$cleandomain = str_replace('ς', 'σ', $domain);
	$reason = $ret[$cleandomain]['reason'];
	$domain_exists = preg_match('/in use/', strtolower($reason));
	$domain_pending = preg_match('/other application pending/', strtolower($reason));
	$domain_avail = $ret[$cleandomain]['avail'] ? true : false;
	if (!$domain_avail) $domain_avail = preg_match('/domain conditionally available/', strtolower($reason));

	if ($trans) {
		if ($config['DomainCheck'] == 'on' or $config['DomainCheck'] == 1 or $_REQUEST['domaincheck'] == 1) {
			echo $domain_exists ? 'Domain cannot be provisioned.' : 'Domain can be provisioned.';
		}
		else {
			echo $domain_exists ? 'Domain exists.' : 'Domain does not exist.';
		}
	}
	elseif ($domain_avail) {
		if ($config['DomainCheck'] == 'on' or $config['DomainCheck'] == 1 or $_REQUEST['domaincheck'] == 1) {
			echo 'Domain can be provisioned.';
		}
		else {
			echo 'Domain does not exist.';
		}
	}
	else if (($domain_exists or $domain_pending) and ($config['DisplayWhoisInfo'] == 'on' or $config['DisplayWhoisInfo'] == 1) and $whois) {
		$url = eppgr_getSearchURL($config, $domain, true);
		$ret = eppgr_communicateWithServer($url, $post);
		eppgr_convertUTF8toISO($config, $ret);
		if (preg_match('/Protocol/', $ret['page'])) {
			echo $ret['page'];
		}
		else {
			echo "WHOIS data inconsistent with domain name status.<br />\n".$domain.": ".$reason;
		}
	}
	else if ($domain_exists or $domain_pending) {
		echo $reason;
	}
	else if (($config['DisplayWhoisInfo'] == 'on' or $config['DisplayWhoisInfo'] == 1) and $whois) {
		$url = eppgr_getSearchURL($config, $domain, true);
		$ret = eppgr_communicateWithServer($url, $post);
		eppgr_convertUTF8toISO($config, $ret);
		if (preg_match('/Protocol/', $ret['page']) || (preg_match('/not exist/', $ret['page']) && $domain_exists)) {
			echo $ret['page'];
		}
		else {
			echo "Unacceptable domain name.<br />\nReason: ".$reason;
		}
	}
	else {
		echo "Unacceptable domain name.<br />\nReason: ".$reason;
	}
	$config['domainname'] = $domain;
}

eppgr_Disconnect($config);

function eppgr_getSearchURL(&$config, $domain, $whois = false) {
	$test = '';
	if ($config['Testing'] == 'on' or $config['Testing'] == 1 or $_REQUEST['testing'] == 1) {
		$test = 'Test';
	}
	if (array_key_exists('testing', $_REQUEST) and $_REQUEST['testing'] === 0) {
		$test = '';
	}
	if (($config['DomainCheck'] == 'on' or $config['DomainCheck'] == 1 or $_REQUEST['domaincheck'] == 1) and !$whois) {
		$url = $config[$test.'PlainDomainCheck'];
	}
	else {
		$url = $config[$test.'PlainWHOIS'];
	}
	return $url.$domain;
}

function eppgr_getCleanURL() {
	$url = "http".(eppgr_isSSL() ? "s" : "")."://".$_SERVER['SERVER_NAME'].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/'));
	return str_replace('modules/registrars/eppgr/lib', '', $url);
}

function eppgr_bulkDomains($search, $trans, $formorig=array()) {
	global $z, $config, $browsers;
	if (!count($formorig)) {
		$domains = trim($_POST['bulkdomains']);
		$domains = preg_replace('/[\n\r]/', ' ', $domains);
		while (strpos($domains, '  ')) {
			$domains = str_replace('  ', ' ', $domains);
		}
		$domains = explode(' ', trim($domains));
	}
	else {
		$domains = $formorig;
	}
	for ($i = 0; $i < count($domains); $i++) {
		$domain =& $domains[$i];
		$domain = trim($domain);
		preg_match('/[\w\-\.]+/', $domain, $matches);
		if (strlen($matches[0]) == strlen($domain)) $utf8 = 0;
		else $utf8 = 1;
		preg_match('/[\w\-\.\x80-\xFF]+/', $domain, $matches);
		$domain = $matches[0];
		if (count($matches) and preg_match('/\.(gr|ελ)$/', $domain) and !preg_match('/\.co\.(gr|ελ)$/', $domain)) {
			$dot = strpos($domain, '.');
			$body = substr($domain, 0, $dot);
			$tld = substr($domain, $dot);
			$z->AESEncode($body);
			$domain = 'eppgr-'.$trans.'-encoded-'.$z->cipher.$tld;
		}
	}
	$url = eppgr_getCleanURL().'domainchecker.php?search='.$search;
	$post = 'bulkdomains='.urlencode(implode("\r\n", $domains));
	if ($_POST['code']) $post .= '&code='.$_POST['code'];
	$ret = preg_replace('/^.+(<div id="eppgr_div".+?<\/div>).+$/is', "$1", eppgr_communicateWithServer($url, $post));
	if (!preg_match('/'.$browsers.'/', $_SERVER['HTTP_USER_AGENT']) or strtoupper($config['charset']) == 'UTF-8') eppgr_convertISOtoUTF8($ret);
	else eppgr_convertUTF8toISO($config, $ret);
	if (count($formorig) and $ret['error']) return $ret;
	if ($ret['error']) echo $ret['error'];
	else {
		for ($i = 0; $i < count($domains); $i++) {
			$domain =& $domains[$i];
			if (preg_match('/eppgr-\d-encoded-/', $domain)) {
				$cipher = preg_replace('/eppgr-\d-encoded-/', '', $domain);
				$firstdot = strpos($cipher, '.');
				$tld = substr($cipher, $firstdot);
				$cipher = substr($cipher, 0, $firstdot);
				$z->AESDecode($cipher);
				if (!preg_match('/'.$browsers.'/', $_SERVER['HTTP_USER_AGENT']) or strtoupper($config['charset']) == 'UTF-8') eppgr_convertISOtoUTF8($z->cipher);
				else eppgr_convertUTF8toISO($config, $z->cipher);
				$origdomain = $z->cipher.$tld;
				$ret['page'] = str_replace($domain, $origdomain, $ret['page']);
				updateWhoIsLog($cipher.$tld, $origdomain);
			}
			else {
				updateWhoIsLog($domain, $domain);
			}
		}
		if (count($formorig)) return $ret;
		echo $ret['page'];
	}
	exit;
}

function eppgr_oneDomain($trans) {
	global $z, $config, $browsers;
	$domain = trim($_POST['domain']);
	preg_match('/[\w\-\.]+/', $domain, $matches);
	if (strlen($matches[0]) == strlen($domain)) $utf8 = 0;
	else $utf8 = 1;
	preg_match('/[\w\-\.\x80-\xFF]+/', $domain, $matches);
	$domain = $matches[0];
	$url = eppgr_getCleanURL().'domainchecker.php';
	$post = '';
	if (array_key_exists('tlds', $_POST) and is_array($_POST['tlds'])) {
		foreach ($_POST['tlds'] as $tld) {
			preg_match('/[\w\.]+/', $tld, $ttt);
			$post .= '&tlds[]='.$ttt[0];
		}
	}
	if ($_POST['code']) $post .= '&code='.$_POST['code'];
	if (count($matches) and preg_match('/\.gr(&|$)/', $post)) {
		$otherdomains = '';
		if (preg_match_all('/(tlds\[\]=\.(\w+\.)*(\w+)(?<!\.gr)&)/', $post.'&', $alien)) {
			foreach ($alien[0] as $aldom) {
				$otherdomains .= $aldom;
			}
			$otherdomains = '&' . $otherdomains;
			if (substr($otherdomains, -1) == '&') $otherdomains = substr($otherdomains, 0, -1);
			$post = preg_replace('/tlds\[\]=\.(\w+\.)*(\w+)(?<!\.gr)&/', '', $post.'&');
			if (substr($post, -1) == '&') $post = substr($post, 0, -1);
		}
		if (preg_match('/tlds\[\]=\.co\.gr&/', $post.'&')) {
			$otherdomains = '&tlds[]=.co.gr' . $otherdomains;
			$post = str_replace('tlds[]=.co.gr&', '', $post.'&');
			if (substr($post, -1) == '&') $post = substr($post, 0, -1);
		}
		$z->AESEncode($domain);
		$post = 'domain=eppgr-'.$trans.'-encoded-'.$z->cipher.$post;
		$ret = preg_replace('/^.+(<div id="eppgr_div".+?<\/div>).+$/is', "$1", eppgr_communicateWithServer($url, $post));
		if (!preg_match('/'.$browsers.'/', $_SERVER['HTTP_USER_AGENT']) or strtoupper($config['charset']) == 'UTF-8') eppgr_convertISOtoUTF8($ret);
		else eppgr_convertUTF8toISO($config, $ret);
		$page = '';
		if ($ret['error']) echo $ret['error'];
		else {
			if (!preg_match('/'.$browsers.'/', $_SERVER['HTTP_USER_AGENT']) or strtoupper($config['charset']) == 'UTF-8') eppgr_convertISOtoUTF8($domain);
			else eppgr_convertUTF8toISO($config, $domain);
			$ret['page'] = str_replace('eppgr-0-encoded-'.$z->cipher, $domain, $ret['page']);
			$ret['page'] = str_replace('eppgr-1-encoded-'.$z->cipher, $domain, $ret['page']);
			$page = $ret['page'];
			$post = explode('&', $post);
			foreach ($post as $tld) {
				if (preg_match('/^tlds\[\]=(.+)$/', $tld, $zzz)){
					updateWhoIsLog($z->cipher.$zzz[1], $domain.$zzz[1]);
				}
			}
			$config['WHOISExtraDomains'] = str_replace(' ', '', $config['WHOISExtraDomains']);
			if (!preg_match('/textgreen/', $page) and $config['WHOISExtraDomains'] and !$trans) {
				eppgr_getExtraDomains($domain, $page, $post, $trans);
			}
		}
		if ($otherdomains) {
			$otherdomains = 'domain='.$domain.$otherdomains;
			$ret = preg_replace('/^.+(<div id="eppgr_div".+?<\/div>).+$/is', "$1", eppgr_communicateWithServer($url, $otherdomains));
			if (!preg_match('/'.$browsers.'/', $_SERVER['HTTP_USER_AGENT']) or strtoupper($config['charset']) == 'UTF-8') eppgr_convertISOtoUTF8($ret);
			else eppgr_convertUTF8toISO($config, $ret);
			if ($ret['error']) echo $ret['error'];
			else if (strpos($ret['page'], 'textgreen') or strpos($ret['page'], 'textred')) {
				if (preg_match('/<table[^>]+class=[\'"]data[\'"]/', $ret['page'])) {
					preg_match('/<table[^>]+class=[\'"]data[\'"].+?<\/tr>(.+?)(<\/tbody>)*\s*?<\/table>/sm', $ret['page'], $more);
					preg_match('/<table[^>]+class=[\'"]data[\'"].+?<\/tr>(.+?)(<\/tbody>)*\s*?<\/table>/sm', $page, $orig);
				}
				else {
					preg_match('/<table[^>]+class=[\'"]clientareatable[\'"].+?<\/tr>(.+?)(<\/tbody>)*\s*?<\/table>/sm', $ret['page'], $more);
					preg_match('/<table[^>]+class=[\'"]clientareatable[\'"].+?<\/tr>(.+?)(<\/tbody>)*\s*?<\/table>/sm', $page, $orig);
				}
				if (!preg_match('/'.$browsers.'/', $_SERVER['HTTP_USER_AGENT']) or strtoupper($config['charset']) == 'UTF-8') eppgr_convertISOtoUTF8($orig[1]);
				else eppgr_convertUTF8toISO($config, $orig[1]);
				echo str_replace($orig[1], $orig[1].$more[1], $page);
				$otherdomains = explode('&', $otherdomains);
				foreach ($otherdomains as $tld) {
					if (preg_match('/^tlds\[\]=(.+)$/', $tld, $zzz)){
						updateWhoIsLog($domain.$zzz[1], $domain.$zzz[1]);
					}
				}
			}
			else echo $page;
		}
		else echo $page;
		exit;
	}
	else {
		$post = 'domain='.$domain.$post;
		$ret = eppgr_communicateWithServer($url, $post);
		if (!preg_match('/'.$browsers.'/', $_SERVER['HTTP_USER_AGENT']) or strtoupper($config['charset']) == 'UTF-8') eppgr_convertISOtoUTF8($ret);
		else eppgr_convertUTF8toISO($config, $ret);
		if ($ret['error']) echo $ret['error'];
		else echo $ret['page'];
		$post = explode('&', $post);
		foreach ($post as $tld) {
			if (preg_match('/^tlds\[\]=(.+)$/', $tld, $zzz)){
				updateWhoIsLog($domain.$zzz[1], $domain.$zzz[1]);
			}
		}
		exit;
	}
}

function eppgr_getExtraDomains($domain, &$page, $post, $trans) {
	global $config, $browsers;
	eppgr_convertISOtoUTF8($config['WHOISExtraDomains']);
	$prefsufs = explode(',', $config['WHOISExtraDomains']);
	$greekdomain = preg_match('/[\x80-\xFF]+/', $domain);
	for ($ps = count($prefsufs) - 1; $ps >= 0; $ps--) {
		$greekprefsuf = preg_match('/[\x80-\xFF]+/', $prefsufs[$ps]);
		if ($greekdomain xor $greekprefsuf) array_splice($prefsufs, $ps, 1);
	}
	if (!count($prefsufs)) return;
	$extradomains = array();
	$extratlds = array();
	foreach ($post as $p) {
		preg_match('/tlds\[\]=(.+)/', $p, $ppp);
		if (is_array($ppp) and array_key_exists(1, $ppp) and $ppp[1]) $extratlds[] = $ppp[1];
	}
	foreach ($prefsufs as $prefsuf) {
		if (preg_match('/^_/', $prefsuf)) {
			foreach ($extratlds as $extratld) {
				$newdomain = $domain . substr($prefsuf, 1) . $extratld;
				if (!in_array($newdomain, $extradomains)) $extradomains[] = $newdomain;
			}
		}
		elseif (preg_match('/_$/', $prefsuf)) {
			foreach ($extratlds as $extratld) {
				$newdomain = substr($prefsuf, 0, -1) . $domain . $extratld;
				if (!in_array($newdomain, $extradomains)) $extradomains[] = $newdomain;
			}
		}
		else {
			foreach ($extratlds as $extratld) {
				$newdomain = $domain . $prefsuf . $extratld;
				if (!in_array($newdomain, $extradomains)) $extradomains[] = $newdomain;
				$newdomain = $prefsuf . $domain . $extratld;
				if (!in_array($newdomain, $extradomains)) $extradomains[] = $newdomain;
			}
		}
	}
	$search = 'bulkregister';
	$ret = eppgr_bulkDomains($search, $trans, $extradomains);
	if ($ret['error']) echo $ret['error'];
	else {
		$trpieces = explode('</tr>', $ret['page']);
		for ($tr = count($trpieces) - 1; $tr >= 0; $tr--) {
			if (!trim($trpieces[$tr])) continue;
			if (preg_match('/<\s*td\s+[^>]*class\s*=\s*[\'"]textred[\'"]/', $trpieces[$tr])) array_splice($trpieces, $tr, 1);
		}
		$ret['page'] = implode('</tr>', $trpieces);
		if (preg_match('/<table[^>]+class=[\'"]data[\'"]/', $ret['page'])) {
			preg_match('/<table[^>]+class=[\'"]data[\'"].+?<\/tr>(.+?)(<\/tbody>)*\s*?<\/table>/sm', $ret['page'], $more);
			preg_match('/<table[^>]+class=[\'"]data[\'"].+?<\/tr>(.+?)(<\/tbody>)*\s*?<\/table>/sm', $page, $orig);
			if (!is_array($orig) or !count($orig)) {
				preg_match('/<table[^>]+class=[\'"]clientareatable[\'"].+?<\/tr>(.+?)(<\/tbody>)*\s*?<\/table>/sm', $page, $orig);
			}
		}
		else {
			preg_match('/<table[^>]+class=[\'"]clientareatable[\'"].+?<\/tr>(.+?)(<\/tbody>)*\s*?<\/table>/sm', $ret['page'], $more);
			preg_match('/<table[^>]+class=[\'"]clientareatable[\'"].+?<\/tr>(.+?)(<\/tbody>)*\s*?<\/table>/sm', $page, $orig);
			if (!is_array($orig) or !count($orig)) {
				preg_match('/<table[^>]+class=[\'"]data[\'"].+?<\/tr>(.+?)(<\/tbody>)*\s*?<\/table>/sm', $page, $orig);
			}
		}
		if (!preg_match('/'.$browsers.'/', $_SERVER['HTTP_USER_AGENT']) or strtoupper($config['charset']) == 'UTF-8') eppgr_convertISOtoUTF8($more[1]);
		else eppgr_convertUTF8toISO($config, $more[1]);
		$page = str_replace($orig[1], $orig[1].$more[1], $page);
	}
}

function eppgr_communicateWithServer($url, $post='') {
	global $config;
	$eppobj = eppgr_GetEppobj($config);
	eppgr_Debug($config, $url.' '.$post);
	if ($eppobj->getEppgrInline() == 'on' or $eppobj->getEppgrInline() == 1) {
		$started = $eppobj->getMicroTime();
	}
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 100);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
	if (eppgr_isSSL()) {
		$certfile = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cacert.pem');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE); 
		curl_setopt($ch, CURLOPT_CAINFO, $certfile);
	}
	if ($post) {
		curl_setopt($ch, CURLOPT_POST, 1); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	$ret = curl_exec($ch);
	$error = curl_error($ch);
	curl_close($ch);
	if ($eppobj->getEppgrInline() == 'on' or $eppobj->getEppgrInline() == 1) {
		$ended = $eppobj->getMicroTime();
		$eppobj->commtime += $ended - $started;
	}
	return array('page' => $ret, 'error' => $error);
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

function updateWhoIsLog($cipher, $domain) {
	global $config, $eppgrdb, $eppgrnow, $charset;
	if ($config['charset'] == 'UTF-8') eppgr_convertISOtoUTF8($config, $domain);
	else eppgr_convertUTF8toISO($config, $domain);
	if (!$charset or $charset != 'utf8' or $charset != 'latin1') {
		$query = 'SHOW CREATE TABLE tblwhoislog';
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error whois#04!');}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$charsets = explode('CHARSET=', $row['Create Table']);
		$charset = 'latin1';
		if (is_array($charsets) and count($charsets) and preg_match('/utf8/', $charsets[count($charsets)-1])) {
			$charset = 'utf8';
		}
		$query = "SET NAMES '" . $charset . "'";
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error whois#05!');}
	}
	$ip = preg_replace('/[^\d\.]/', '', $_SERVER['REMOTE_ADDR']);
	if ($domain and $ip) {
		$regcipher = 'eppgr-0-encoded-'.$cipher;
		$trncipher = 'eppgr-1-encoded-'.$cipher;
		$query = "SELECT * FROM tblwhoislog WHERE (domain LIKE('".$domain."') OR domain LIKE('".$regcipher."') OR domain LIKE('".$trncipher."')) AND date >= '$eppgrnow' ORDER BY id DESC LIMIT 1";
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error whois#06!');}
		if ($stmt->rowCount()) {
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			$eppgrdate = $row['date'];
			$query = "UPDATE tblwhoislog SET domain = '$domain', ip = '$ip' WHERE id = ".$row['id'];
			try {$stmt = $eppgrdb->query($query);}
			catch(PDOException $ex) {die('MySQL error whois#07!');}
		}
		else {
			$eppgrdate = date('Y-m-d H:i:s');
			$query = "INSERT INTO tblwhoislog (`date`, `domain`, `ip`) VALUES('$eppgrdate', '$domain', '$ip')";
			try {$stmt = $eppgrdb->query($query);}
			catch(PDOException $ex) {die('MySQL error whois#08!');}
		}
		@session_start();
		$_SESSION['domaincheckerwhois'][] = $domain;
		session_write_close();
	}
}
?>