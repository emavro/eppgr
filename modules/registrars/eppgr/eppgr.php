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

function eppgr_getConfigArray() {
	$configarray = array(
	 "Username" => array( "Type" => "text", "Size" => "20", "Description" => "Enter your EPP Registrar username here", ),
	 "Password" => array( "Type" => "password", "Size" => "20", "Description" => "Enter your EPP Registrar password here", ),
	 "Testing" => array( "Type" => "yesno", "Description" => "Use the Registry's testing environment", ),
	 "TestUsername" => array( "Type" => "text", "Size" => "20", "Description" => "Enter your EPP Registrar username for the testing environment here if it is different from your regular Username", ),
	 "TestPassword" => array( "Type" => "password", "Size" => "20", "Description" => "Enter your EPP Registrar password for the testing environment here if it is different from your regular Password", ),
	 "ClientPrefix" => array( "Type" => "text", "Size" => "5", "Description" => "Enter your EPP Client prefix here (e.g. 380)", ),
	 "CronPassword" => array( "Type" => "text", "Size" => "20", "Description" => "Enter your Cron Password here (alphanumeric characters only)", ),
	 "RenewDNames" => array( "Type" => "yesno", "Description" => "Renewals should take into account activated homographs", ),
	 "AllowDeleteDomain" => array( "Type" => "yesno", "Description" => "Allow users to permanently delete their domain names", ),
	 "AllowDeleteHomographs" => array( "Type" => "yesno", "Description" => "Allow users to permanently delete their homographs", ),
	 "DisplayWhoisInfo" => array( "Type" => "yesno", "Description" => "Offer your clients the option to display Private WHOIS information if the domain they are looking for exists", ),
	 "DeleteAlienContacts" => array( "Type" => "yesno", "Description" => "Automatically replace contacts that do not belong to you with the registrant's details (e.g. after a domain transfer)", ),
	 "EarlyExpiration" => array( "Type" => "text", "Size" => "5", "Description" => "How many days earlier the domain should be set to expire", ),
	 "CheckForExpiredTransfers" => array( "Type" => "yesno", "Description" => "Check if an expired domain is being transferred to you", ),
	 "ExtendExpiration" => array( "Type" => "yesno", "Description" => "Add 15 days to expiry date", ),
	 "CopyAdminToTech" => array( "Type" => "yesno", "Description" => "Copy Admin details to Tech when registering a new domain", ),
	 "CopyAdminToBill" => array( "Type" => "yesno", "Description" => "Copy Admin details to Billing when registering a new domain", ),
	 "WHOISExtraDomains" => array( "Type" => "text", "Size" => "100", "Description" => "Enter a number of prefixes and suffixes here to offer extra search results to your clients", ),
	 "WHOISOnly" => array( "Type" => "yesno", "Description" => "Use Greek WHOIS server only to search for availability", ),
	 "DomainCheck" => array( "Type" => "yesno", "Description" => "Use plainDomainCheck instead of plainWhois", ),
	 "IDProtectAll" => array( "Type" => "yesno", "Description" => "Set ID Protection on for all contacts upon domain registration", ),
	 "Discounts" => array( "Type" => "text", "Size" => "100", "Description" => "Enter your discount policy here", ),
	 "CreditEmails" => array( "Type" => "text", "Size" => "100", "Description" => "Enter the email addresses to receive updates on credits (separate with comma [,])", ),
	 "CreditEmailsAfter" => array( "Type" => "text", "Size" => "5", "Description" => "Enter the number of credits after which you need to be emailed", ),
	 "CreditsAfterCreateDomain" => array( "Type" => "yesno", "Description" => "Get info about your account credits after attempting to create a domain", ),
	 "CreditsAfterRenewDomain" => array( "Type" => "yesno", "Description" => "Get info about your account credits after attempting to renew a domain", ),
	 "CheckShortNames" => array( "Type" => "yesno", "Description" => "Check short names before registration/renewal", ),
	 "NoShortName" => array( "Type" => "yesno", "Description" => "Block short name registration/renewal", ),
	 "EPPGRInline" => array( "Type" => "yesno", "Description" => "Debug EPPGR in modules/registrars/eppgr/lib/eppgr.mydebug.txt", ),
	 "EPPGRDebug" => array( "Type" => "yesno", "Description" => "Debug EPPGR in modules/registrars/eppgr/lib/eppgr.debug.txt", ),
	 "SystemModuleDebugLog" => array( "Type" => "yesno", "Description" => "Record raw API data on the module debugging tool of WHMCS", ),
	 "LogPersonalData" => array( "Type" => "yesno", "Description" => "Display personal data in the debugging and log records", ),
	 "LogPassword" => array( "Type" => "yesno", "Description" => "Display your EPP Registrar password in the debugging records", ),
	 "LogEmails" => array( "Type" => "text", "Size" => "100", "Description" => "Enter the email addresses to receive debugging logs (separate with comma [,])", ),
	 "PostLogs" => array( "Type" => "yesno", "Description" => "Post debugging logs instead of saving them in modules/registrars/eppgr/lib/eppgr.mydebug.txt", ),
	);
	return $configarray;
}

function eppgr_cleanParams(&$params) {
	global $eppgrdb, $eppgrDBChecked;
	if (isset($eppgrdb)) {
		unset($GLOBALS['eppgrdb']);
	}
	if (isset($eppgrDBChecked)) {
		unset($GLOBALS['eppgrDBChecked']);
	}
	$params['url'] = '';
	$params['eppgrtranscheck01'] = '';
	$params['eppgrtranscheck02'] = '';
}

function eppgr_Debug(&$params, $func) {
	if (eppgr_isChecked($params, 'EPPGRInline') or eppgr_isChecked($params, 'eppgrinline')) {
	    list($usec, $sec) = explode(" ", microtime());
	    $t = ((float)$usec + (float)$sec);
		if ($func == 'EPPGR') {
			$params['eppgrdebugdata'][] = $func.' ended at '.date("r", time()).' ['.$t.']';
			$params['eppgrended'] = $t;
		}
		elseif (!array_key_exists('eppgrstarted', $params) or !$params['eppgrstarted']) {
			$params['eppgrdebugdata'][] = $func.' started at '.date("r", time()).' ['.$t.']';
			$params['eppgrstarted'] = $t;
		}
	}
}

function eppgr_GetNameservers($params) {
	global $_LANG;
	$action = 'GetNameservers';
	eppgr_setLogEntry($params, 'request', 'token', (isset($_REQUEST['token']) ? $_REQUEST['token'] : $_LANG['eppgrmissing']));
	eppgr_setLogEntry($params, 'request', 'domainid', (isset($_REQUEST['id']) ? $_REQUEST['id'] : $_LANG['eppgrmissing']));
	eppgr_setLogEntry($params, 'request', 'disclose', (isset($params['disclose']) ? $params['disclose'] : $_LANG['eppgrmissing']));
	eppgr_setLogEntry($params, 'request', 'idprotection', (array_key_exists('idprotection', $_REQUEST) ? $_REQUEST['idprotection'] : $_LANG['eppgrmissing']));
	eppgr_cleanParams($params);
	eppgr_Debug($params, $action);
	eppgr_getDB($params);
	$updatecontacts = false;
	if (isset($_REQUEST['token']) and isset($_REQUEST['id']) and is_numeric($_REQUEST['id'])) {
		eppgr_GetIDProtection($params, $_REQUEST['id']);
		if (($params['disclose'] and array_key_exists('idprotection', $_REQUEST)) or
			(!$params['disclose'] and !array_key_exists('idprotection', $_REQUEST))) {
			$params['disclose'] = 1 - $params['disclose'];
			$idprotection = $params['disclose'] ? '' : 'on';
			$eppgrdb = eppgr_getDB($params);
			$query = "INSERT INTO `tbleppgrdomains` (`domain_id`, `idprotection`) "
				. "VALUES ({$_REQUEST['id']}, '$idprotection') "
				. "ON DUPLICATE KEY UPDATE `idprotection` = '$idprotection';";
			try {$stmt = $eppgrdb->query($query);}
			catch(PDOException $ex) {die('MySQL error eppgr#01!');}
			$updatecontacts = true;
			$params['IDProtectAll'] = '';
		}
	}
	$ret = eppgr_getDomainInfo($params, $data);
	eppgr_setLogEntry($params, 'response', 'domainname', (array_key_exists('name', $ret) ? $ret['name'] : $_LANG['eppgrmissing']));
	eppgr_convertUTF8toISO($params, $ret['ns']);
	for ($i = 0; $i < count($ret['ns']); $i++) {
		$values['ns'.($i+1)] = trim($ret['ns'][$i]);
	}
	if ($updatecontacts) {
		$query = "SELECT * FROM `tbleppgrregistrants` WHERE `domain_id` = ".$_REQUEST['id'];
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error eppgr#02!');}
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$savecontact = unserialize(base64_decode($row['values']));
			$command = 'UpToDateContact';
			eppgr_getServerResponse($params, $command, $savecontact);
			if (array_key_exists('error', $params) and count($params['error'])) break;
		}
	}
	eppgr_getValuesError($params, $values);
	eppgr_Disconnect($params);
	eppgr_MakeLogModuleCall($params, $values, $action);
	return $values;
}

function eppgr_setLogEntry(&$params, $log, $key, $entry, $hide = false) {
	eppgr_hideData($params, $entry, $key, $hide);
	$params['eppgr'][$log.'Array'][$key] = $entry;
}

function eppgr_MakeLogModuleCall(&$params, &$values, $action) {
	if (function_exists('logModuleCall') and eppgr_isChecked($params, 'SystemModuleDebugLog')) {
		logModuleCall('eppgr', $action, eppgr_GetRequestData($params, $values), eppgr_GetResponseData($params, $values));
	}
}

function eppgr_GetRequestData(&$params, $values) {
	global $_LANG;
	$ret = array('requestValues' => $_LANG['eppgrmissing']);
	if (array_key_exists('eppgr', $params) and is_array($params['eppgr']) and array_key_exists('requestArray', $params['eppgr']) and
	    is_array($params['eppgr']['requestArray']) and !empty($params['eppgr']['requestArray'])) {
		$ret = $params['eppgr']['requestArray'];
	}
	eppgr_hideData($params, $ret, 0);
	return json_encode($ret, JSON_UNESCAPED_UNICODE);
}

function eppgr_GetResponseData(&$params, $values) {
	global $_LANG;
	$ret = array('responseValues' => $_LANG['eppgrmissing']);
	if (array_key_exists('eppgr', $params) and is_array($params['eppgr']) and array_key_exists('responseArray', $params['eppgr']) and
	    is_array($params['eppgr']['responseArray']) and !empty($params['eppgr']['responseArray']) and
	    isset($values) and is_array($values) and !empty($values)) {
		$ret = array_merge($params['eppgr']['responseArray'], $values);
	}
	elseif (array_key_exists('eppgr', $params) and is_array($params['eppgr']) and array_key_exists('responseArray', $params['eppgr']) and
	    is_array($params['eppgr']['responseArray']) and !empty($params['eppgr']['responseArray'])) {
		$ret = $params['eppgr']['responseArray'];
	}
	elseif (isset($values) and is_array($values) and !empty($values)) {
		$ret = $values;
	}
	eppgr_hideData($params, $ret, 0);
	return json_encode($ret, JSON_UNESCAPED_UNICODE);
}

function eppgr_isChecked(&$params, $option) {
	return (array_key_exists($option, $params) and ($params[$option] == 'on' or $params[$option] == 1));
}

function eppgr_isNotChecked(&$params, $option) {
	return !eppgr_isChecked($params, $option);
}

function eppgr_getFiedsToHide() {
	return array(
		'name',
		'org',
		'str1',
		'str2',
		'str3',
		'city',
		'sp',
		'pc',
		'cc',
		'voice',
		'fax',
		'email',
		'pw',
		'First Name',
		'Last Name',
		'Email',
		'Company Name',
		'Street 1',
		'Street 2',
		'Address 1',
		'Address 2',
		'City',
		'State',
		'Postal Code',
		'Postcode',
		'Country',
		'Phone Number',
		'Fax',
		'street',
		'newpw',
		'id',
		'registrantid',
		'registrant',
		'admin',
		'tech',
		'billing',
		'eppcode',
	);
}

function eppgr_hideData(&$params, &$array, $key, $hide) {
	if (is_array($array)) {
		foreach (array_keys($array) as $k) {
			eppgr_hideData($params, $array[$k], $k, $hide);
		}
	}
	elseif (($hide or ($key and !is_numeric($key) and in_array($key, eppgr_getFiedsToHide()))) and eppgr_isNotChecked($params, 'LogPersonalData')) {
		$array = 'XXXXXXXXX';
	}
}

function eppgr_GetIDProtection(&$params, $domain_id) {
	$eppgrdb = eppgr_getDB($params);
	$query = "SELECT `idprotection` FROM `tbleppgrdomains` WHERE `domain_id` = ".$domain_id;
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppgr#03!');}
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	if (is_array($row) and array_key_exists('idprotection', $row)) {
		$params['disclose'] = ($row['idprotection'] ? 0 : 1) * 1;
	}
	elseif (array_key_exists('idprotection', $params)) {
		$params['disclose'] = ($params['idprotection'] ? 0 : 1) * 1;
	}
	else {
		$params['disclose'] = ($params['IDProtectAll'] ? 0 : 1) * 1;
	}
}

function eppgr_SetDisclose(&$params, $domain_id) {
	if (!array_key_exists('disclose', $params)) {
		eppgr_GetIDProtection($params, $domain_id);
		$params['disclose'] = ($params['disclose'] and eppgr_isChecked($params, 'IDProtectAll')) ? 0 : $params['disclose'];
	}
}

function eppgr_SaveNameservers($params) {
	$action = 'SaveNameservers';
	eppgr_cleanParams($params);
	eppgr_Debug($params, $action);
	eppgr_getDB($params);
	$command = 'SaveNameservers';
	$ret = eppgr_getServerResponse($params, $command);
    eppgr_getValuesError($params, $values);
	eppgr_Disconnect($params);
	eppgr_MakeLogModuleCall($params, $values, $action);
	return $values;
}

function eppgr_ShortNameOK(&$params) {
	eppgr_getDB($params);
	global $_LANG;
	$ret = true;
	if (eppgr_isChecked($params, 'NoShortName') and mb_ereg_match("^.{2}\.((com|org|net|gov|edu)\.)*(gr|ελ)$", $params['sld'].'.'.$params['tld'])) {
		$ret = false;
		$params['error'][] = $_LANG['eppgrnoshortname'];
		eppgr_setLogEntry($params, 'response', 'Error', $params['error'][count($params['error'])-1]);
	}
	return $ret;
}

function eppgr_RegPeriodOK(&$params) {
	eppgr_getDB($params);
	global $_LANG;
	if (!$params['regperiod'] or
		$params['regperiod'] % 2 or
		$params['regperiod'] > 10 or
		$params['regperiod'] < 2) {
		$params['error'][] = $_LANG['eppgrwrongregperiod'] . $params['regperiod'];
		eppgr_setLogEntry($params, 'response', 'Error', $params['error'][count($params['error'])-1]);
		return false;
	}
	eppgr_setLogEntry($params, 'request', 'registrationPeriod', $params['regperiod']);
	return true;
}

function eppgr_SLDOK(&$params) {
	eppgr_getDB($params);
	global $_LANG;
	$ret = true;
	require 'lib' . DIRECTORY_SEPARATOR . 'idna_convert.class.php';
	eppgr_convertISOtoUTF8($params['sld']);
	$domain = new idna_convert();
	$sldlen = strlen($domain->encode($params['sld']));
	if ($sldlen > 63) {
		$ret = false;
		$params['error'][] = $_LANG['eppgrwrongsldlength'] . $params['sld']. ' ['. $sldlen . ']';
		eppgr_setLogEntry($params, 'response', 'Error', $params['error'][count($params['error'])-1]);
	}
	return $ret;
}

function eppgr_RegisterDomain($params) {
	$action = 'RegisterDomain';
	eppgr_cleanParams($params);
	eppgr_Debug($params, $action);
	eppgr_getDB($params);
	if (!eppgr_RegPeriodOK($params) or !eppgr_ShortNameOK($params)) {
	    eppgr_getValuesError($params, $values);
		eppgr_MakeLogModuleCall($params, $values, $action);
		return $values;
	}
	eppgr_correctParams($params);
	$command = 'RegisterDomain';
	$ret = eppgr_getServerResponse($params, $command);
    eppgr_getValuesError($params, $values);
	eppgr_Disconnect($params);
	eppgr_MakeLogModuleCall($params, $values, $action);
	return $values;
}

function eppgr_alienDomainHasExpired15(&$params, &$data) {
	global $_LANG;
	if (!array_key_exists('domainHasExpired15', $params)) {
		$error = array();
		if (!eppgr_transDomain($params, $data)) {
			eppgr_setLogEntry($params, 'response', 'transferError', $params['error'][count($params['error'])-1]);
			$error = $params['error'];
			unset($params['error']);
		}
		$ret = eppgr_getDomainInfo($params, $data);
		eppgr_setLogEntry($params, 'response', 'domainname', (array_key_exists('name', $ret) ? $ret['name'] : $_LANG['eppgrmissing']));
		if (!eppgr_thisDomainBelongsToMe($params, $ret) and
			is_array($ret) and
			array_key_exists('registrant', $ret) and
			!$ret['registrant']) {
			global $_LANG;
			$params['error'][] = $_LANG['eppgrwrongtransfersecret'];
			eppgr_setLogEntry($params, 'response', 'transferError', $params['error'][count($params['error'])-1]);
		}
		if (is_array($ret)) {
			$exDate = strtotime($ret['exDate']);
			$data['curExpDate'] = gmdate('Y-m-d', $exDate);
		}
		if (is_array($ret) and
			array_key_exists('status', $ret) and
			is_array($ret['status']) and
			in_array('pendingDelete', $ret['status'])) {
			$params['domainHasExpired15'] = true;
			$params['domainExDate'] = gmdate('Y-m-d', $exDate + 16 * 24 * 60 * 60);
		}
		else {
			$params['domainHasExpired15'] = false;
		}
		if (!eppgr_thisDomainBelongsToMe($params)) {
			$params['error'] = array_merge($params['error'], $error);
			if (!count($params['error'])) {
				unset($params['error']);
			}
		}
	}
	return (!eppgr_thisDomainBelongsToMe($params) and $params['domainHasExpired15']);
}

function eppgr_getContactID(&$params) {
	if (array_key_exists('ClientPrefix', $params) and $params['ClientPrefix']) {
		return $params['ClientPrefix'];
	}
	return $params['contactid'];
}

function eppgr_TransferDomain($params) {
	global $_LANG;
	$action = 'TransferDomain';
	eppgr_cleanParams($params);
	eppgr_Debug($params, $action);
	eppgr_getDB($params);
	$params['eppgrtranscheck01'] = eppgr_getRandomPassword();
	$z = eppgr_getAESObject($params);
	if (!is_object($z)) {
		return 0;
	}
	$z->AESEncode($params['eppgrtranscheck01']);
	$params['eppgrtranscheck02'] = $z->cipher;
	$params['regperiod'] = 2;
	eppgr_setLogEntry($params, 'response', 'registrationPeriod', $params['regperiod']);
	eppgr_setLogEntry($params, 'response', 'transferPassword', $params['transfersecret'], true);
	if (eppgr_isChecked($params, 'CheckForExpiredTransfers') and eppgr_alienDomainHasExpired15($params, $data)) {
		eppgr_Disconnect($params);
		if (array_key_exists('error', $params) and count($params['error'])) {
			eppgr_getValuesError($params, $values);
			eppgr_MakeLogModuleCall($params, $values, $action);
			return $values;
		}
		eppgr_setLogEntry($params, 'response', 'transferError', $_LANG['eppgrdomainexpired']);
		eppgr_MakeLogModuleCall($params, $values, $action);
		return $values;
	}
	unset($data);
	if (array_key_exists('error', $params) and count($params['error'])) {
		unset($params['error']);
	}
	eppgr_getNewTransferSecret($params);
	$command = 'TransferDomain';
	if (eppgr_getNewContactDetails($params, $data)) {
		$ret = eppgr_getServerResponse($params, $command, $data);
	}
	eppgr_getValuesError($params, $values);
	eppgr_Disconnect($params);
	eppgr_MakeLogModuleCall($params, $values, $action);
	return $values;
}

function eppgr_getAESObject(&$params) {
	if (!array_key_exists('ClientPrefix', $params) or !$params['ClientPrefix']) {
		$params['error'][] = $_LANG['eppgrnoclientprefixfound'];
		return 0;
	}
	if (!array_key_exists('CronPassword', $params) or !$params['CronPassword']) {
		$params['error'][] = $_LANG['eppgrnocronpasswordfound'];
		return 0;
	}
	return new emavAES($params['ClientPrefix'], $params['CronPassword']);
}

function eppgr_getNewTransferSecret(&$params) {
	do { $newpass = eppgr_getRandomPassword(); } while ($params['transfersecret'] == $newpass);
	$params['eppgrnewpw'] = $newpass;
	eppgr_setLogEntry($params, 'response', 'newDomainPassword', $params['eppgrnewpw'], true);
}

function eppgr_getDomainInfo(&$params, &$data) {
	if (!array_key_exists('eppgrdomains', $params) or
		!is_array($params['eppgrdomains']) or
		!count($params['eppgrdomains']) or
		!array_key_exists($id, $params['eppgrdomains'])) {
		$command = 'GetNameservers';
		$params['eppgrdomains'][$params['domainid']] = eppgr_getServerResponse($params, $command);
	}
	return $params['eppgrdomains'][$params['domainid']];
}

function eppgr_countBundleNames(&$ret) {
	$total = 0;
	if (is_array($ret) and
		array_key_exists('bundleName', $ret) and
		is_array($ret['bundleName']) and
		count($ret['bundleName'])) {
		foreach ($ret['bundleName'] as $b) {
			foreach ($b as $k => $v) {
				$total += $v['chargeable'];
			}
		}
	}
	return $total;
}

function eppgr_RenewDomain($params) {
	global $_LANG;
	$action = 'RenewDomain';
	eppgr_cleanParams($params);
	eppgr_Debug($params, $action);
	eppgr_getDB($params);
	if (!eppgr_RegPeriodOK($params) or !eppgr_ShortNameOK($params)) {
		eppgr_getValuesError($params, $values);
		eppgr_MakeLogModuleCall($params, $values, $action);
		return $values;
	}
	if (eppgr_isChecked($params, 'CheckForExpiredTransfers')) {
		eppgr_alienDomainHasExpired15($params, $data);
		if (eppgr_thisDomainBelongsToMe($params) and
			array_key_exists('error', $params) and
			count($params['error'])) {
			eppgr_setLogEntry($params, 'response', 'renewDomainNotice', $params['error'][count($params['error'])-1]);
			unset($params['error']);
		}
	    if (array_key_exists('error', $params) and count($params['error'])) {
			eppgr_getValuesError($params, $values);
			eppgr_Disconnect($params);
			eppgr_MakeLogModuleCall($params, $values, $action);
			return $values;
		}
		if (eppgr_alienDomainHasExpired15($params, $data)) {
			eppgr_getNewTransferSecret($params);
			eppgr_getNewContactDetails($params, $data);
		}
	}
	else {
		$ret = eppgr_getDomainInfo($params, $data);
		eppgr_setLogEntry($params, 'response', 'domainname', (array_key_exists('name', $ret) ? $ret['name'] : $_LANG['eppgrmissing']));
		if (is_array($ret)) {
			$exDate = strtotime($ret['exDate']);
			$data['curExpDate'] = gmdate('Y-m-d', $exDate);
		}
	}
	$command = 'RenewDomain';
	$ret = eppgr_getServerResponse($params, $command, $data);
	eppgr_Disconnect($params);
	eppgr_getValuesError($params, $values);
	eppgr_MakeLogModuleCall($params, $values, $action);
	return $values;
}

function eppgr_getFirstLast($name, &$first, &$last) {
	$l = strrpos($name, ' ');
	$first = trim(substr($name, 0, $l));
	$last = trim(substr($name, $l));
}

function eppgr_thisContactBelongsToMe(&$params, $contactid) {
	$prefix = substr($contactid, 0, strpos($contactid, '_'));
	return $prefix == eppgr_getContactID($params) ? true : false;
}

function eppgr_thisDomainBelongsToMe(&$params, $ret = array()) {
	if (!array_key_exists('domainBelongsToMe', $params)) {
		if (is_array($ret) and
			array_key_exists('registrant', $ret) and
			$ret['registrant'] and
			eppgr_thisContactBelongsToMe($params, $ret['registrant'])) {
			$params['domainBelongsToMe'] = true;
		}
		else {
			$params['domainBelongsToMe'] = false;
		}
	}
	return $params['domainBelongsToMe'];
}

function eppgr_GetAllSavedContacts(&$params) {
	$eppgrdb = eppgr_getDB($params);
	if (!array_key_exists('domainid', $params) or !$params['domainid']) {
		$query = "SELECT `id` FROM `tbldomains` WHERE `domain` = '".$params['sld'].'.'.$params['tld']."' ORDER BY `id` DESC LIMIT 0,1";
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error eppgr#39!');}
		$id = $stmt->fetch(PDO::FETCH_COLUMN);
	}
	else {
		$id = $params['domainid'];
	}
	$query = "SELECT * FROM `tbleppgrregistrants` WHERE `domain_id` = ".$id;
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppgr#05!');}
	$cc = array();
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$cc[$row['type']] = unserialize(base64_decode($row['values']));
	}
	return $cc;
}

function eppgr_GetContactDetails($params) {
	global $_LANG;
	$action = 'GetContactDetails';
	eppgr_setLogEntry($params, 'request', 'domainid', (isset($params['domainid']) ? $params['domainid'] : $_LANG['eppgrmissing']));
	eppgr_setLogEntry($params, 'request', 'domainname', (isset($params['domainname']) ? $params['domainname'] : $_LANG['eppgrmissing']));
	eppgr_cleanParams($params);
	eppgr_Debug($params, $action);
	eppgr_getDB($params);
	$cc = eppgr_GetAllSavedContacts($params);
	if (!count($cc)) {
		$ret = eppgr_getDomainInfo($params, $data);
		eppgr_setLogEntry($params, 'response', 'domainname', (array_key_exists('name', $ret) ? $ret['name'] : $_LANG['eppgrmissing']));
		if (array_key_exists('error', $params) and count($params['error'])) {
			eppgr_getValuesError($params, $values);
			eppgr_MakeLogModuleCall($params, $values, $action);
			return $values;
		}
		if (!is_array($ret) or !array_key_exists('registrant', $ret) or !$ret['registrant']) {
			$params['error'][] = $_LANG['eppgrnocontactsfound'];
			eppgr_getValuesError($params, $values);
			eppgr_MakeLogModuleCall($params, $values, $action);
			return $values;
		}

		eppgr_addToDBRegistrant($params, $ret);
		$a = eppgr_getOrigContactTypes();

		$contacts = array();
		$contacts['registrant'] = $ret['registrant'];
		$contactdata = array();
		foreach ($a as $b) {
			if (is_array($ret['contact']) and array_key_exists($b, $ret['contact']) and $ret['contact'][$b]) {
				$contacts[$b] = eppgr_checkDBForRegistrant($params, $contactdata, $ret['contact'][$b], $b, $contacts['registrant']);
				if (!$contacts[$b]) {
					$contacts[$b] = $ret['contact'][$b];
					$params['eppgrmsgs'] = array_merge((array) $params['eppgrmsgs'], (array) $params['error']);
					unset($params['error']);
				}
			}
			else $contacts[$b] = '';
		}
		if (count($contactdata)) {
			$command = 'SaveNameservers';
			if (array_key_exists('contactdetails', $params) and is_array($params['contactdetails']) and count($params['contactdetails'])) {
				unset($params['contactdetails']);
			}
			$conret = eppgr_getServerResponse($params, $command, $contactdata);
			if (array_key_exists('error', $params) and count($params['error'])) {
				$params['eppgrmsgs'] = array_merge((array) $params['eppgrmsgs'], (array) $params['error']);
				unset($params['error']);
				foreach ($a as $b) {
					$contacts[$b] = $ret['contact'][$b];
				}
			}
			else {
				foreach ($a as $b) {
					if (array_key_exists($b, $contacts) and $contacts[$b]) {
						eppgr_saveRegistrant($params, $params['domainid'], strtolower($b), $contacts[$b], $params['eppgr']['contacts'][$contacts[$b]]);
					}
				}
			}
		}
	}
	else {
		eppgr_fixParams($params);
		$a = eppgr_getContactTypes();
		foreach ($a as $b) {
			$contacts[$b] = $cc[$b]['id'];
		}
	}
	$values = array();
	foreach ($contacts as $k => $v) {
		if ($v and eppgr_thisContactBelongsToMe($params, $v)) {
			if (!count($cc)) {
				$ct = eppgr_getContactById($params, $v);
			}
			else {
				$ct = $cc[$k];
			}
			eppgr_getContactValues($params, $ct, $values, ucfirst($k));
		}
		elseif ($v and !eppgr_thisContactBelongsToMe($params, $v)) {
			global $_LANG;
			$values[ucfirst($k)] = array(
				'First Name'	=>	$v,
				'Last Name'		=>	$_LANG['eppgrforeigncontact'],
				'Email'			=>	'',
				'Company Name'	=>	'',
				'Street 1'		=>	'',
				'Street 2'		=>	'',
				'City'			=>	'',
				'State'			=>	'',
				'Postal Code'	=>	'',
				'Country'		=>	'',
				'Phone Number'	=>	'',
				'Fax'			=>	'',
			);
		}
		else {
			$values[ucfirst($k)] = array(
				'First Name'	=>	'',
				'Last Name'		=>	'',
				'Email'			=>	'',
				'Company Name'	=>	'',
				'Street 1'		=>	'',
				'Street 2'		=>	'',
				'City'			=>	'',
				'State'			=>	'',
				'Postal Code'	=>	'',
				'Country'		=>	'',
				'Phone Number'	=>	'',
				'Fax'			=>	'',
			);
		}
	}
	eppgr_Disconnect($params);
	eppgr_getValuesError($params, $values);
	eppgr_MakeLogModuleCall($params, $values, $action);
	return $values;
}

function eppgr_getContactValues(&$params, &$ct, &$values, $tag) {
	eppgr_getFirstLast($ct['loc']['name'], $first, $last);
	$values[$tag] = array(
		'First Name'	=>	htmlspecialchars($first),
		'Last Name'		=>	htmlspecialchars($last),
		'Email'			=>	htmlspecialchars($ct['email']),
		'Company Name'	=>	htmlspecialchars($ct['loc']['org']),
		'Street 1'		=>	htmlspecialchars($ct['loc']['str1']),
		'Street 2'		=>	htmlspecialchars($ct['loc']['str2']),
		'City'			=>	htmlspecialchars($ct['loc']['city']),
		'State'			=>	htmlspecialchars($ct['loc']['sp']),
		'Postal Code'	=>	htmlspecialchars($ct['loc']['pc']),
		'Country'		=>	eppgr_getCountry($params, htmlspecialchars($ct['loc']['cc'])),
		'Phone Number'	=>	htmlspecialchars($ct['voice']),
		'Fax'			=>	htmlspecialchars($ct['fax']),
	);
	eppgr_convertUTF8toISO($params, $values[$tag]);
}

function eppgr_convertUTF8toISO(&$params, &$array) {
	if (!isset($params['charset']) or !$params['charset']) {
		eppgr_getDB($params);
	}
	if (strtoupper($params['charset']) == 'UTF-8') return;
	if (is_array($array)) {
		foreach ($array as $k => $v) {
			if (is_array($array[$k])) eppgr_convertUTF8toISO($params, $array[$k]);
			elseif ($array[$k] and eppgr_isUTF8($array[$k])) $array[$k] = mb_convert_encoding($array[$k], 'ISO-8859-7', 'UTF-8');
		}
	}
	elseif ($array and eppgr_isUTF8($array)) $array = mb_convert_encoding($array, 'ISO-8859-7', 'UTF-8');
}

function eppgr_convertISOtoUTF8(&$array) {
	if (is_array($array)) {
		foreach ($array as $k => $v) {
			if (is_array($array[$k])) eppgr_convertISOtoUTF8($array[$k]);
			elseif ($array[$k] and !eppgr_isUTF8($array[$k])) $array[$k] = mb_convert_encoding($array[$k], 'UTF-8', 'ISO-8859-7');
		}
	}
	elseif ($array and !eppgr_isUTF8($array)) $array = mb_convert_encoding($array, 'UTF-8', 'ISO-8859-7');
}

function eppgr_getUTF8Pattern() {
	return '/^(
              [\x09\x0A\x0D\x20-\x7E]            # ASCII
            | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
            |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
            |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
            |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
            )+$/x';
}

function eppgr_isUTF8($text) {
	$pattern = eppgr_getUTF8Pattern();
	$str = explode(' ', $text);
	$utf8 = true;
	for ($i = 0; $i < count($str); $i++) {
		if (!$str[$i]) continue;
		preg_match_all($pattern, $str[$i], $matches);
	    if (!count($matches[0]) or strlen($str[$i]) != strlen($matches[0][0])) {
			$utf8 = false;
			break;
		}
	}
	return $utf8;
}

function eppgr_addressOK(&$params, &$address) {
	$ok = true;
	if (!array_key_exists('voice', $address) or !trim($address['voice'])) {
		$ok = false;
	}
	if (!array_key_exists('email', $address) or !trim($address['email'])) {
		$ok = false;
	}
	if (!array_key_exists('loc', $address) or !is_array($address['loc'])) {
		$ok = false;
	}
	if (!array_key_exists('name', $address['loc']) or !trim($address['loc']['name'])) {
		$ok = false;
	}
	if (!array_key_exists('city', $address['loc']) or !trim($address['loc']['city'])) {
		$ok = false;
	}
	if (!array_key_exists('sp', $address['loc']) or !trim($address['loc']['sp'])) {
		$ok = false;
	}
	if (!array_key_exists('pc', $address['loc']) or !trim($address['loc']['pc'])) {
		$ok = false;
	}
	if (!array_key_exists('cc', $address['loc']) or !trim($address['loc']['cc'])) {
		$ok = false;
	}
	return $ok;
}

function eppgr_addressOKFromRequest(&$params, &$v, $k) {
	$ok = true;
	if (!array_key_exists('firstname', $v) or !trim($v['firstname'])){
		$params['error'][] = $k.': You need to provide a first name for your contact.';
		$ok = false;
	}
	if (!array_key_exists('lastname', $v) or !trim($v['lastname'])) {
		$params['error'][] = $k.': You need to provide a last name for your contact.';
		$ok = false;
	}
	if (!array_key_exists('city', $v) or !trim($v['city'])) {
		$params['error'][] = $k.': You need to provide a city for your contact.';
		$ok = false;
	}
	if (!array_key_exists('state', $v) or !trim($v['state'])) {
		$params['error'][] = $k.': You need to provide a state for your contact.';
		$ok = false;
	}
	if (!array_key_exists('postcode', $v) or !trim($v['postcode'])) {
		$params['error'][] = $k.': You need to provide a postal code for your contact.';
		$ok = false;
	}
	if (!array_key_exists('country', $v) or !trim($v['country'])) {
		$params['error'][] = $k.': You need to provide a country for your contact.';
		$ok = false;
	}
	if (!array_key_exists('phonenumber', $v) or !trim($v['phonenumber'])) {
		$params['error'][] = $k.': You need to provide a phone number for your contact.';
		$ok = false;
	}
	if (!array_key_exists('email', $v) or !trim($v['email'])) {
		$params['error'][] = $k.': You need to provide an e-mail address for your contact.';
		$ok = false;
	}
	return $ok;
}

function eppgr_getContactById(&$params, &$id) {
	if (!array_key_exists('eppgr', $params) or !is_array($params['eppgr']) or
		!count($params['eppgr']) or !array_key_exists('contacts', $params['eppgr']) or
		!is_array($params['eppgr']['contacts']) or !count($params['eppgr']['contacts']) or
		!array_key_exists($id, $params['eppgr']['contacts'])) {
		if (eppgr_thisContactBelongsToMe($params, $id)) {
			$command = 'GetContactDetails';
			$c = array('id' => $id);
			$ct = eppgr_getServerResponse($params, $command, $c);
			if (array_key_exists('error', $params) and count($params['error'])) {
				$params['eppgr']['contacts'][$id]['id'] = $id;
				$params['eppgr']['contacts'][$id]['email'] = eppgr_GetErrorString($params);
				unset($params['error']);
			}
			else {
				$params['eppgr']['contacts'][$id] = $ct;
			}
		}
		else {
			$params['eppgr']['contacts'][$id]['id'] = $id;
		}
	}
	return $params['eppgr']['contacts'][$id];
}

function eppgr_checkDBForRegistrant(&$params, &$data, $regid, $type, $registrant) {
	$utf8contact = $regid;
	eppgr_convertUTF8toISO($params, $regid);
	$eppgrdb = eppgr_getDB($params);
	$query = "SELECT * FROM `tbleppgrregistrants` WHERE `registrant_id` = '{$regid}'";
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppgr#06!');}
	$regid = $utf8contact;
	$mycontact = eppgr_thisContactBelongsToMe($params, $regid);
	$values = $stmt->fetch(PDO::FETCH_ASSOC);
	$values = unserialize(base64_decode($values['values']));
	if ((is_object($stmt) and $mycontact and $stmt->rowCount() > 1) or
		(!$mycontact and eppgr_isChecked($params, 'DeleteAlienContacts')) or
		!is_array($values)) {
		if ($mycontact) {
			$ct = eppgr_getContactById($params, $regid);
		}
		else {
			$ct = eppgr_getContactById($params, $registrant);
		}

		if (eppgr_addressOK($params, $ct)) {
			if (eppgr_getNewContactDetails($params, $ct)) {
				$command = 'SaveContactDetails';
				$ret = eppgr_getServerResponse($params, $command, $ct);
			}
			if (array_key_exists('error', $params) and count($params['error'])) return false;

			$data['contact']['rem'][strtolower($type)] = $regid;
			$data['contact']['add'][strtolower($type)] = $ct['id'];

			return $ct['id'];
		}
		else return $regid;
	}
	else return $regid;
}

function eppgr_addToDBRegistrant(&$params, $ret) {
	if (!is_array($ret)) return;
	if (array_key_exists('registrant', $ret) and $ret['registrant']) {
		if (!array_key_exists('eppgrupdatecontacts', $params) or in_array($ret['registrant'], $params['eppgrupdatecontacts'])) {
			eppgr_convertUTF8toISO($params, $ret['registrant']);
			eppgr_saveRegistrant($params, $params['domainid'], 'registrant', $ret['registrant']);
		}
	}
	if (array_key_exists('contact', $ret)) {
		$a = eppgr_getOrigContactTypes();
		foreach ($a as $b) {
			if (array_key_exists($b, $ret['contact']) and $ret['contact'][$b]) {
				if (!array_key_exists('eppgrupdatecontacts', $params) or in_array($ret['contact'][$b], $params['eppgrupdatecontacts'])) {
					eppgr_convertUTF8toISO($params, $ret['contact'][$b]);
					eppgr_saveRegistrant($params, $params['domainid'], $b, $ret['contact'][$b]);
				}
			}
		}
	}
}

function eppgr_getValuesError(&$params, &$values) {
	if ((array_key_exists('error', $params) and count($params['error'])) or
		(array_key_exists('eppgrmsgs', $params) and count($params['eppgrmsgs']))) {
		$values['error'] = eppgr_GetErrorString($params);
		$merged = array_merge((array)$params['error'], (array)$params['eppgrmsgs']);
		eppgr_setLogEntry($params, 'response', 'Error', ((is_array($merged) and count($merged) > 1) ? $merged : $values['error']));
	}
}

function eppgr_SaveContactDetails($params) {
	$action = 'SaveContactDetails';
	eppgr_cleanParams($params);
	eppgr_Debug($params, $action);
	eppgr_getDB($params);
	$command = 'SaveNameservers';
	$ret = eppgr_getServerResponse($params, $command);
	eppgr_getValuesError($params, $values);
	eppgr_Disconnect($params);
	eppgr_MakeLogModuleCall($params, $values, $action);
	return $values;
}

function eppgr_GetEPPCode($params) {
	$action = 'GetEPPCode';
	eppgr_cleanParams($params);
	eppgr_Debug($params, $action);
	eppgr_getDB($params);
	eppgr_loadLangFiles($params);
	global $smartyvalues, $_LANG;
	$ret = eppgr_getDomainInfo($params, $data);
	$smartyvalues['eppgrAllowDeleteHomographs'] = (eppgr_isChecked($params, 'AllowDeleteHomographs') ? 1 : 0);
	$smartyvalues['eppgrAllowDeleteDomain'] = (eppgr_isChecked($params, 'AllowDeleteDomain') ? 1 : 0);
	$smartyvalues['eppgrAllowRecallDomain'] = (is_array($ret) and array_key_exists('status', $ret) and
		is_array($ret['status']) and in_array('pendingCreate', $ret['status'])) ? 1 : 0;
	$retrieveInfoDomain = false;
	if (array_key_exists('updateeppcode', $_REQUEST) and
		$_REQUEST['updateeppcode'] and
		$_REQUEST['updateeppcode'] == 'yes' and
		$_REQUEST['neweppcode'] and
		!preg_match('/\W/', $_REQUEST['neweppcode'])) {
		$retrieveInfoDomain = true;
		$command = 'SaveNameservers';
		$data['pw'] = $_REQUEST['neweppcode'];
		eppgr_convertISOtoUTF8($params['sld']);
		eppgr_convertISOtoUTF8($data);
		$ret = eppgr_getServerResponse($params, $command, $data);
	    if (array_key_exists('error', $params) and count($params['error'])) {
			unset($params['error']);
		}
	}
	elseif (((array_key_exists('eppgractivate', $_REQUEST) and
		$_REQUEST['eppgractivate']) or
		(array_key_exists('eppgractivatetext', $_REQUEST) and
		$_REQUEST['eppgractivatetext'])) and
		$_REQUEST['updateeppcode'] == 'yes') {
		$data['name'] = preg_replace('/[^\w\-\.\x80-\xFF]/', '', $_REQUEST['eppgractivate']);
		if (array_key_exists('eppgractivatetext', $_REQUEST) and $_REQUEST['eppgractivatetext']) {
			$data['name'] = preg_replace('/[^\w\-\.\x80-\xFF]/', '', $_REQUEST['eppgractivatetext']);
		}
		eppgr_getHomophones($data['name'], $sld, $tld, $hp, $tp, 1);
		eppgr_getSLDTLD($ret['name'], $mainsld, $maintld);
		$match = true;
		if ($mainsld == $sld or $tld != $maintld or
			(!preg_match('/[\x80-\xFF]/', $sld) and !preg_match('/[\x80-\xFF]/', $mainsld))) {
			$match = false;
		}
		if ($match and preg_match('/[\x80-\xFF]/', $sld) and preg_match('/[\x80-\xFF]/', $mainsld)) {
			$match = eppgr_checkDName($mainsld, $sld, $tp);
		}
		elseif ($match and (preg_match('/[\x80-\xFF]/', $sld) xor preg_match('/[\x80-\xFF]/', $mainsld))) {
			$match = eppgr_checkDName($sld, $mainsld, $hp);
		}
		if ($match) {
			$data['pw'] = $ret['pw'];
			$data['record'] = 'dname';
			$dtext = serialize($data);
			$z = eppgr_getAESObject($params);
			if (!is_object($z)) {
				return 0;
			}
			$z->AESEncode($dtext);
			$product = 'eppgrHomograph';
			eppgr_placeOrder($params, $postfields, $z->cipher, $product, $data, $ret['exDate']);
		}
		else {
			$params['eppgrmsgs'][] = $_LANG['eppgrnomatchingdnames'];
		}
	}
	elseif (eppgr_isChecked($params, 'AllowDeleteHomographs') and
		array_key_exists('eppgrdeactivate', $_REQUEST) and
		$_REQUEST['eppgrdeactivate'] and
		$_REQUEST['updateeppcode'] == 'yes') {
		$retrieveInfoDomain = true;
		$data['name'] = preg_replace('/[^\w\-\.\x80-\xFF]/', '', $_REQUEST['eppgrdeactivate']);
		$data['pw'] = $ret['pw'];
		$data['op'] = 'deleteHomograph';
		eppgr_convertISOtoUTF8($data);
		$command = 'DelDomain';
		$ret = eppgr_getServerResponse($params, $command, $data);
	    if (array_key_exists('error', $params) and count($params['error'])) {
			unset($params['error']);
		}
	}
	elseif (array_key_exists('eppgrdeletedomain', $_REQUEST) and
		$_REQUEST['eppgrdeletedomain'] and
		$_REQUEST['updateeppcode'] == 'yes') {
		$retrieveInfoDomain = true;
		if (is_array($ret) and array_key_exists('status', $ret) and
			is_array($ret['status']) and in_array('pendingCreate', $ret['status'])) {
			if (array_key_exists('bundleName', $ret) and
				is_array($ret['bundleName'])) {
				if (count($ret['bundleName']) == 1) {
					$data['name'] = $ret['name'];
				}
				elseif (count($ret['bundleName']) == 2) {
					foreach ($ret['bundleName'] as $k => $v) {
						if ($v['recordType'] != 'domain') {
							$data['name'] = $k;
						}
					}
				}
				else {
					$data['name'] = '';
					$params['eppgrmsgs'][] = $_LANG['eppgrdeletednamesbeforerecall'];
				}
			}
			elseif (is_array($ret)) {
				$data['name'] = $ret['name'];
			}
			if ($data['name']) {
				$data['protocol'] = $ret['protocol'];
				$data['op'] = 'recallApplication';
			}
		}
		elseif (is_array($ret) and eppgr_isChecked($params, 'AllowDeleteDomain')) {
			$data['name'] = $ret['name'];
			$data['pw'] = $ret['pw'];
			$data['op'] = 'deleteDomain';
		}
		if (array_key_exists('op', $data) and $data['op']) {
			eppgr_convertISOtoUTF8($data);
			$command = 'DelDomain';
			$ret = eppgr_getServerResponse($params, $command, $data);
		    if (array_key_exists('error', $params) and count($params['error'])) {
				unset($params['error']);
			}
		}
	}
	if ($retrieveInfoDomain) {
		$command = 'GetEPPCode';
		$ret = eppgr_getServerResponse($params, $command);
	}
	if (is_array($ret) and array_key_exists('name', $ret) and $ret['name'] and eppgr_isChecked($params, 'RenewDNames')) {
		$dnames = array();
		eppgr_getHomophones($ret['name'], $sld, $tld, $hp, $tp);
		if (preg_match('/[\x80-\xFF]/', $sld)) {
			$dnames = array_merge(eppgr_getExtraNames($sld, $tld, $tp), $dnames);
		}
		if (!mb_strlen(str_replace(array_merge(array_keys($hp), array('-',0,1,2,3,4,5,6,7,8,9)), '', $sld))) {
			$dnames = array_merge(eppgr_getExtraNames($sld, $tld, $hp), $dnames);
		}
		if (count($dnames)) {
			if (array_key_exists('bundleName', $ret) and
				is_array($ret['bundleName']) and
				count($ret['bundleName'])) {
				$dnames = array_values(array_diff($dnames, array_keys($ret['bundleName'])));
			}
			$smartyvalues['eppgrExtraNames'] = array_values(array_diff($dnames, array($ret['name'])));
		}
	}
	if (is_array($ret) and array_key_exists('bundleName', $ret) and count($ret['bundleName'])) {
		foreach ($ret['bundleName'] as $k => $v) {
			if ($v['recordType'] != 'domain') {
				$smartyvalues['eppgrBundleName'][$v['recordType']][$k] = $v['chargeable'] ? $_LANG['eppgrchargeable'] : $_LANG['eppgrnotchargeable'];
			}
		}
	}
	eppgr_convertUTF8toISO($params, $ret);
    $values['eppcode'] = $ret['pw'];
	eppgr_setLogEntry($params, 'response', 'EPPCode', $values['eppcode'], true);
    eppgr_getValuesError($params, $values);
	eppgr_Disconnect($params);
	eppgr_MakeLogModuleCall($params, $values, $action);
    return $values;
}

function eppgr_curPageURL() {
	$pageURL = 'http';
	if (array_key_exists("HTTPS", $_SERVER) and $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	}
	else {
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	return $pageURL;
}

function eppgr_getSLDTLD(&$name, &$sld, &$tld) {
	$dot = strpos($name, '.');
	$sld = substr($name, 0, $dot);
	$tld = substr($name, $dot + 1);
}

function eppgr_getHomophones(&$name, &$sld, &$tld, &$hp, &$tp, $full = 0) {
	$homo = array(
		'gr'	=>	array(
			'α'	=>	array('a',),
			'β'	=>	array('b',),
			'ε'	=>	array('e',),
			'ζ'	=>	array('z',),
			'η'	=>	array('h', 'n'),
			'ι'	=>	array('i',),
			'κ'	=>	array('k',),
			'μ'	=>	array('m',),
			'ν'	=>	array('n', 'v'),
			'ο'	=>	array('o',),
			'ρ'	=>	array('p',),
			'τ'	=>	array('t',),
			'υ'	=>	array('y', 'u'),
			'χ'	=>	array('x',),
		),
		'en'	=>	array(
			'a'	=>	array('α',),
			'b'	=>	array('β',),
			'e'	=>	array('ε',),
			'h'	=>	array('η',),
			'i'	=>	array('ι',),
			'k'	=>	array('κ',),
			'm'	=>	array('μ',),
			'n'	=>	array('η', 'ν'),
			'o'	=>	array('ο',),
			'p'	=>	array('ρ',),
			't'	=>	array('τ',),
			'u'	=>	array('υ',),
			'v'	=>	array('ν',),
			'x'	=>	array('χ',),
			'y'	=>	array('υ',),
			'z'	=>	array('ζ',),
		),
	);
	eppgr_getSLDTLD($name, $sld, $tld);
	$hp = $homo['en'];
	if (preg_match('/[\x80-\xFF]/', $sld)) {
		$hp = $homo['gr'];
		$tp = array(
			'α'	=>	array('α','ά',),
			'ε'	=>	array('ε','έ',),
			'η'	=>	array('η','ή',),
			'ι'	=>	array('ι','ί','ϊ','ΐ',),
			'ο'	=>	array('ο','ό',),
			'υ'	=>	array('υ','ύ','ϋ','ΰ',),
			'ω'	=>	array('ω','ώ',),
		);
		if ($full) {
			$tp = array(
				'α'	=>	array('α','ά','ἀ','ἁ','ἂ','ἃ','ἄ','ἅ','ἆ','ἇ','ὰ','ά','ᾀ','ᾁ','ᾂ','ᾃ','ᾄ','ᾅ','ᾆ','ᾇ','ᾰ','ᾱ','ᾲ','ᾳ','ᾴ','ᾶ','ᾷ',),
				'ε'	=>	array('ε','έ','ἐ','ἑ','ἒ','ἓ','ἔ','ἕ','ὲ','έ',),
				'η'	=>	array('η','ή','ἠ','ἡ','ἢ','ἣ','ἤ','ἥ','ἦ','ἧ','ὴ','ή','ᾐ','ᾑ','ᾒ','ᾓ','ᾔ','ᾕ','ᾖ','ᾗ','ῂ','ῃ','ῄ','ῆ','ῇ',),
				'ι'	=>	array('ι','ί','ϊ','ΐ','ἰ','ἱ','ἲ','ἳ','ἴ','ἵ','ἶ','ἷ','ὶ','ί','ῐ','ῑ','ῒ','ΐ','ῖ','ῗ',),
				'ο'	=>	array('ο','ό','ὀ','ὁ','ὂ','ὃ','ὄ','ὅ','ὸ','ό',),
				'ρ'	=>	array('ῤ','ῥ',),
				'υ'	=>	array('υ','ύ','ϋ','ΰ','ὐ','ὑ','ὒ','ὓ','ὔ','ὕ','ὖ','ὗ','ὺ','ύ','ῠ','ῡ','ῢ','ΰ','ῦ','ῧ',),
				'ω'	=>	array('ω','ώ','ὠ','ὡ','ὢ','ὣ','ὤ','ὥ','ὦ','ὧ','ὼ','ώ','ᾠ','ᾡ','ᾢ','ᾣ','ᾤ','ᾥ','ᾦ','ᾧ','ῲ','ῳ','ῴ','ῶ','ῷ',),
			);
		}
	}
}

function eppgr_checkDName(&$from, &$to, &$hp) {
	$pattern = str_replace(')+$', '', str_replace('^(', '', eppgr_getUTF8Pattern()));
	$ret = false;
	if (preg_match_all($pattern, $to, $tochars) and
		preg_match_all($pattern, $from, $fromchars) and
		count($tochars[0]) == count($fromchars[0])) {
		$ret = true;
		$tochars = $tochars[0];
		$fromchars = $fromchars[0];
		for ($i = 0; $i < count($fromchars); $i++) {
			$c = $fromchars[$i];
			if (array_key_exists($c, $hp) and !in_array($tochars[$i], $hp[$c])) {
				$ret = false;
				break;
			}
		}
	}
	return $ret;
}

function eppgr_getExtraNames($sld, $tld, $hp) {
	$pattern = str_replace(')+$', '', str_replace('^(', '', eppgr_getUTF8Pattern()));
	$dnames = array('');
	if (preg_match_all($pattern, $sld, $chars)) {
		$chars = $chars[0];
		$counter = 0;
		for ($i = 0; $i < count($chars); $i++) {
			$c = $chars[$i];
			$tmp = array();
			if (array_key_exists($c, $hp)) {
				foreach ($hp[$c] as $hc) {
					foreach ($dnames as $d) {
						$tmp[] = $d.$hc;
					}
				}
			}
			else {
				foreach ($dnames as $d) {
					$tmp[] = $d.$c;
				}
			}
			$dnames = $tmp;
			$counter++;
			if ($counter > 1000) {
				break;
			}
		}
	}
	for ($i = 0; $i < count($dnames); $i++) {
		$dnames[$i] .= '.'.$tld;
	}
	natsort($dnames);
	return $dnames;
}

function eppgr_fixNameserver(&$params) {
	if (is_array($params) and array_key_exists('nameserver', $params) and !is_array($params['nameserver']) and preg_match('/^\./', trim($params['nameserver']))) {
		$params['nameserver'] = substr(trim($params['nameserver']), 1);
	}
}

function eppgr_RegisterNameserver($params) {
	$action = 'RegisterNameserver';
	eppgr_cleanParams($params);
	eppgr_Debug($params, $action);
	eppgr_getDB($params);
	eppgr_fixNameserver($params);
	$command = 'RegisterNameserver';
	$ret = eppgr_getServerResponse($params, $command);
    eppgr_getValuesError($params, $values);
	eppgr_Disconnect($params);
	eppgr_MakeLogModuleCall($params, $values, $action);
    return $values;
}

function eppgr_ModifyNameserver($params) {
	$action = 'ModifyNameserver';
	eppgr_cleanParams($params);
	eppgr_Debug($params, $action);
	eppgr_getDB($params);
	eppgr_fixNameserver($params);
	$command = 'ModifyNameserver';
	$ret = eppgr_getServerResponse($params, $command);
    eppgr_getValuesError($params, $values);
	eppgr_Disconnect($params);
	eppgr_MakeLogModuleCall($params, $values, $action);
    return $values;
}

function eppgr_DeleteNameserver($params) {
	$action = 'DeleteNameserver';
	eppgr_cleanParams($params);
	eppgr_Debug($params, $action);
	eppgr_getDB($params);
	eppgr_fixNameserver($params);
	$command = 'DeleteNameserver';
	$ret = eppgr_getServerResponse($params, $command);
    eppgr_getValuesError($params, $values);
	eppgr_Disconnect($params);
	eppgr_MakeLogModuleCall($params, $values, $action);
    return $values;
}

function eppgr_GetErrorString(&$params) {
	$ret = array_merge((array)$params['error'], (array)$params['eppgrmsgs']);
	return implode(' | ', $ret);
}

function eppgr_Disconnect(&$params) {
	global $eppconnected, $eppobj, $_LANG;
	if ($eppconnected) {
		$eppobj->disconnect();
		$eppconnected = 0;
	}
	if (eppgr_isChecked($params, 'EPPGRInline') or eppgr_isChecked($params, 'eppgrinline')) {
		if (eppgr_isNotChecked($params, 'PostLogs')) {
			$myFile = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'lib'. DIRECTORY_SEPARATOR . "eppgr.mydebug.txt";
			$fh = fopen($myFile, 'a') or die("can't open file");
		}
		ob_start();
		$coms = '';
		if (is_array($eppobj->debugdata) and count($eppobj->debugdata)) {
			$coms = implode("\n", $eppobj->debugdata);
		}
		if (!is_array($params['eppgrdebugdata'])) {
			$params['eppgrdebugdata'] = array();
		}
		$tc = $eppobj->commtime;
	}
	unset($GLOBALS['eppobj']);
	if (eppgr_isChecked($params, 'EPPGRInline') or eppgr_isChecked($params, 'eppgrinline')) {
		eppgr_Debug($params, 'EPPGR');
		eppgr_convertUTF8toISO($params, $params['eppgrdebugdata']);
		eppgr_convertUTF8toISO($params, $coms);
		$run = $params['eppgrended'] - $params['eppgrstarted'];
		echo "\n\n".'<!-- EPPGR DEBUG BEGINS -->'."\n".'<!--'."\n\n";
		echo implode("\n", $params['eppgrdebugdata'])."\n".$coms;
		echo 'Total communication time '.number_format($tc, 2, '.', '').' secs'."\n";
		echo 'EPPGR ran for '.number_format($run, 2, '.', '').' secs';
		echo "\n\n".'-->'."\n".'<!-- EPPGR DEBUG ENDS -->'."\n\n";
		$content = ob_get_contents();
		ob_end_clean();
		if (eppgr_isNotChecked($params, 'PostLogs')) {
			fwrite($fh, $content);
			fclose($fh);
		}
		if (eppgr_isChecked($params, 'PostLogs')) {
			if (!eppgr_getEmailAddresses($params, $from, $emails, 'LogEmails')) {
				eppgr_setLogEntry($params, 'response', 'mailSentFailed', $_LANG['eppgrmaillogfailed']);
				return;
			}
			$backtrace = debug_backtrace();
			$to = implode(', ', $emails);
			$subject = 'EPPGR Debug ['.$params['ClientPrefix'].']';
			if (count($backtrace) > 1 and is_array($backtrace[1]) and array_key_exists('function', $backtrace[1]) and $backtrace[1]['function']) {
				$subject .= ': '.str_replace('eppgr_', '', $backtrace[1]['function']);
			}
			elseif (count($backtrace) == 1 and is_array($backtrace[0]) and array_key_exists('file', $backtrace[0]) and $backtrace[0]['file']) {
				$subject .= ': '.strtoupper(preg_replace('/^.+\.([^\.]+?)\.php$/', '$1', $backtrace[0]['file']));
			}
			if (preg_match('/:/', $subject) and array_key_exists('domainname', $params) and $params['domainname']) {
				$subject .= ' for '.$params['domainname'];
			}
			$headers = 'From: ' . $from . "\r\n" .
				'Reply-To: ' . $from . "\r\n" .
				'Content-type: text/plain; charset=' . strtoupper($params['charset']) . "\r\n" .
				'X-Mailer: PHP/' . phpversion();
			if (mail($to, $subject, $content, $headers)) {
				eppgr_setLogEntry($params, 'response', 'mailSent', $_LANG['eppgrmaillogsuccess']);
			}
		}
	}
}

function eppgr_getLang(&$params) {
	if (array_key_exists('Language', $_SESSION) and $_SESSION['Language'] == 'Greek') $params['language'] = 'el';
	else $params['language'] = 'en';
}

function eppgr_fixParams(&$params) {
	if (!array_key_exists('url', $params) or !$params['url']) {
		eppgr_getLang($params);
		$params['certfile'] = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'cacert.pem';
		$test = '';
		if (eppgr_isChecked($params, 'Testing')) {
			$test = 'Test';
		}
		$params['url'] = $params[$test.'EPPHost'];
		$params['username'] = $params[$test.'Username'] ? $params[$test.'Username'] : $params['Username'];
		$params['password'] = $params[$test.'Password'] ? $params[$test.'Password'] : $params['Password'];
		$params['contactid'] = $params['ClientPrefix'];
		$params['eppgrdebug'] = $params['EPPGRDebug'];
		$params['eppgrinline'] = $params['EPPGRInline'];
		unset($params['Username']);
		unset($params['Password']);
		unset($params['EPPGRDebug']);
		unset($params['EPPGRInline']);
	}
}

function eppgr_GetEppobj(&$params, $data=array()) {
	global $eppobj;
	if (!isset($eppobj) or !is_object($eppobj)) {
		require_once 'lib' . DIRECTORY_SEPARATOR . 'eppgr.helper.php';
		require_once 'lib' . DIRECTORY_SEPARATOR . 'eppgr.class.php';

		$eppobj = new eppgr($params, $data);
	}
	return $eppobj;
}

function eppgr_getServerResponse(&$params, &$command, &$data=array()) {
	global $eppconnected, $_LANG, $smartyvalues;
	if (!eppgr_getProperCommand($command)) {
		$params['error'][] = 'Command '.$command.' not found!';
		return;
	}

	eppgr_fixParams($params);
	$eppobj = eppgr_GetEppobj($params, $data);

	if (!$eppconnected) {
		$eppobj->connect();
		$eppconnected = 1;
	}

	if (function_exists('eppgr_'.$command)) {
		eval('$check = eppgr_'.$command.'($params, $data);');
	}
	else {
		$check = true;
	}
	if ($check) {
		eval('parse'.ucfirst($command).'Data($params, $data, $eppobj);');
		if (array_key_exists('error', $params)) {
			$ret = false;
		}
		elseif (is_int($eppconnected) and $eppconnected > 0) {
			$eppobj->executeCommand($command);
			if (!eppgr_errorFound($params, $eppobj, $command.'1')) {
				eval('$ret = $eppobj->parse'.$command.'ResponseData();');
			}
		}
		else {
			$params['error'][] = $command.'2: '.$eppobj->getError();
			$ret = false;
		}
	}
	else {
		$ret = false;
	}
	if ($ret and function_exists('eppgr_'.$command.'After')) {
		eval('$check = eppgr_'.$command.'After($params, $data, $ret);');
	}
	if (array_key_exists('error', $params) and count($params['error'])) {
		eppgr_convertUTF8toISO($params, $params['error']);
	}
	return $ret;
}

function eppgr_errorFound(&$params, &$obj, $pfx) {
	$error = $obj->getError();
	if ($error) {
		$params['error'][] = $pfx.': '.$error;
		return true;
	}
	else return false;
}

function eppgr_correctParams(&$params) {
	$a = eppgr_getFields();
	$b = eppgr_getContactTypes();
	foreach($params as $k => $v) {
		if (in_array($k, $b)) continue;
		if (in_array($k, $a)) {
			$params['registrant'][$k] = $v;
			unset($params[$k]);
		}
		elseif (preg_match('/^admin/', $k)) {
			$c = str_replace('admin', '', $k);
			$params['admin'][$c] = $v;
			unset($params[$k]);
		}
		elseif (preg_match('/^tech/', $k)) {
			$c = str_replace('tech', '', $k);
			$params['tech'][$c] = $v;
			unset($params[$k]);
		}
		elseif (preg_match('/^billing/', $k)) {
			$c = str_replace('billing', '', $k);
			$params['billing'][$c] = $v;
			unset($params[$k]);
		}
	}
	foreach ($b as $c) {
		if (is_array($params[$c])) {
			eppgr_correctNames($params, $params[$c]);
			eppgr_convertISOtoUTF8($params[$c]);
		}
	}
}

function eppgr_correctNames(&$params, &$assoc) {
	$eppgrdb = eppgr_getDB($params);
	$query = 'SELECT b.userid, b.contactid FROM tbldomains AS a '
			.'LEFT JOIN tblorders AS b ON a.orderid = b.id '
			.'WHERE a.id = '.addslashes($params['domainid']);
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppgr#07!');}
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if ($row['contactid']) {
		$table = 'tblcontacts';
		$id = $row['contactid'];
	}
	else {
		$table = 'tblclients';
		$id = $row['userid'];
	}
	$query = 'SELECT * FROM '.$table.' WHERE id = '.addslashes($id);
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppgr#08!');}
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	$row['table'] = $table;
	$row['contact_id'] = $id;
	unset($row['id']);
	$assoc = $row;
}

function eppgr_getContactTables() {
	return 	array('tblclients', 'tblcontacts');
}

function eppgr_getContactTypes() {
	return array('registrant', 'admin', 'tech', 'billing');
}

function eppgr_getFields() {
	return array('firstname', 'lastname', 'companyname', 'email', 'address1', 'address2',
			   'city', 'state', 'postcode', 'country', 'phonenumber');
}

function eppgr_getProperCommand(&$command) {
	$a = array(
		'CheckHost'			    =>	'checkHost',
		'GetNameserverDetails'	=>	'infoHost',
		'GetNameservers'		=>	'infoDomain',
		'SaveNameservers'		=>	'updateDomain',
		'CheckDomain'			=>	'checkDomain',
		'RegisterDomain'		=>	'createDomain',
		'TransferDomain'		=>	'transDomain',
		'RenewDomain'			=>	'rnewDomain',
		'CheckContact'			=>	'checkContact',
		'GetContactDetails'		=>	'infoContact',
		'SaveContactDetails'	=>	'createContact',
		'GetEPPCode'			=>	'infoDomain',
		'RegisterNameserver'	=>	'createHost',
		'ModifyNameserver'		=>	'updateHost',
		'DeleteNameserver'		=>	'deleteHost',
		'UpToDateContact'		=>	'updateContact',
		'DelHost'			    =>	'deleteHost',
		'DelContact'			=>	'deleteContact',
		'DelDomain'				=>	'deleteDomain',
		'infoAccount'			=>	'infoAccount',
	);
	
	if (array_key_exists($command, $a) and $a[$command]) {
		$command = $a[$command];
		return true;
	}
	else return false;
}

function eppgr_infoContactAfter(&$params, &$data, &$ret) {
	if (is_array($ret) and count($ret) and array_key_exists('id', $ret) and $ret['id']) {
		$params['eppgr']['contacts'][$ret['id']] = $ret;
	}
}

function eppgr_rnewDomainAfter(&$params, &$data, &$ret) {
	if (eppgr_isChecked($params, 'CreditsAfterRenewDomain')) {
		eppgr_emailCredits($params);
	}
	eppgr_setEarlyExpiration($params, $data, $ret, 'rnewDomain');
	return true;
}

function eppgr_transDomainAfter(&$params, &$data, &$ret) {
	$data['registrant'] = $data['registrantid'];
	eppgr_addToDBRegistrant($params, $data);
	eppgr_setEarlyExpiration($params, $data, $ret, 'transDomain');
	return true;
}

function eppgr_transDomain(&$params, &$data) {
	global $_LANG;
	$ret = eppgr_getDomainInfo($params, $data);
	if (eppgr_thisDomainBelongsToMe($params, $ret)) {
		$params['error'][] = $_LANG['eppgrdomainalreadymoved'];
		eppgr_setLogEntry($params, 'response', 'transferError', $params['error'][count($params['error'])-1]);
		return false;
	}
	elseif(array_key_exists('eppgrdomains', $params) and is_array($params['eppgrdomains']) and
		   count($params['eppgrdomains']) and array_key_exists($params['domainid'], $params['eppgrdomains'])) {
		unset($params['eppgrdomains'][$params['domainid']]);
	}
	if (array_key_exists('transfersecret', $params) and $params['transfersecret']) {
		return true;
	}
	$eppgrdb = eppgr_getDB($params);
	$query = "SELECT `domain` FROM `tbldomains` WHERE `id` = " . $params['domainid'];
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppgr#09!');}
	if (!$stmt->rowCount()) {
		$params['error'][] = $_LANG['eppgrnodomainfound'];
		eppgr_setLogEntry($params, 'response', 'transferError', $params['error'][count($params['error'])-1]);
		return false;
	}
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	$domain = $row['domain'];
	$newsecret = '';
	$query = "SELECT transfersecret FROM tblorders "
			."WHERE transfersecret REGEXP '[[:punct:]]+".$domain."[[:punct:]]+' "
			."ORDER BY id DESC LIMIT 0,1";
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppgr#10!');}
	if ($stmt->rowCount()) {
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$secrets = unserialize($row['transfersecret']);
		if (is_array($secrets) and array_key_exists($domain, $secrets) and $secrets[$domain]) {
			$newsecret = $secrets[$domain];
			eppgr_convertISOtoUTF8($newsecret);
		}
	}
	eppgr_convertISOtoUTF8($domain);
	eppgr_getSLDTLD($domain, $params['sld'], $params['tld']);
	if (
			!$newsecret and
			array_key_exists('cart', $_SESSION) and
			is_array($_SESSION['cart']) and
			array_key_exists('domains', $_SESSION['cart']) and
			count($_SESSION['cart']['domains'])
		) {
		foreach ($_SESSION['cart']['domains'] as $d) {
			if (
					is_array($d) and
					array_key_exists('type', $d) and
					strtolower($d['type']) == 'transfer' and
					array_key_exists('domain', $d) and
					$d['domain'] == $domain and
					array_key_exists('eppcode', $d)
				) {
				$newsecret = $d['eppcode'];
				break;
			}
		}
	}

	if ($newsecret) $params['transfersecret'] = $newsecret;
	if (!$params['transfersecret']) {
		$params['error'][] = $_LANG['eppgrnotransfersecretfound'];
		eppgr_setLogEntry($params, 'response', 'transferError', $params['error'][count($params['error'])-1]);
		return false;
	}
	return true;
}

function eppgr_updateDomainAfter(&$params, &$data, &$ret) {
	if (array_key_exists('ns', $data) and is_array($data['ns']) and array_key_exists('rem', $data['ns']) and is_array($data['ns']['rem']) and count($data['ns']['rem'])) {
		foreach ($data['ns']['rem'] as $d) {
			eppgr_setLogEntry($params, 'response', 'nsRemoved', $d);
		}
	}
	elseif (array_key_exists('contact', $data) and is_array($data['contact'])) {
		$done = array();
		if (array_key_exists('rem', $data['contact']) and is_array($data['contact']['rem']) and count($data['contact']['rem'])) {
			foreach ($data['contact']['rem'] as $d) {
				if (in_array($d, $done) or !eppgr_thisContactBelongsToMe($params, $d)) continue;
				else $done[] = $d;
				$d = array('id' => $d);
				eppgr_deleteContactAfter($params, $d, $tempret);
				eppgr_setLogEntry($params, 'response', 'removedContact', $d['id'], true);
			}
		}
		$ret = eppgr_getDomainInfo($params, $data);
		if (array_key_exists('error', $params) and count($params['error'])) return false;
		if (!is_array($ret) or !array_key_exists('registrant', $ret) or !$ret['registrant']) {
			global $_LANG;
			$params['error'][] = $_LANG['eppgrnocontactsfound'];
			return false;
		}
		if (array_key_exists('add', $data['contact']) and is_array($data['contact']['add']) and count($data['contact']['add'])) {
			foreach ($data['contact']['add'] as $k => $v) {
				$params['eppgrupdatecontacts'][] = $v;
			}
		}
		eppgr_addToDBRegistrant($params, $ret);
	}
	elseif (array_key_exists('op', $data) and $params['op']) {
		$eppgrdb = eppgr_getDB($params);
		$query = "DELETE FROM `tbleppgrregistrants` WHERE domain_id = '".$params['domainid']."'";
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error eppgr#11!');}
		$ret = eppgr_getDomainInfo($params, $data);
		if (array_key_exists('error', $params) and count($params['error'])) return false;
		if (!is_array($ret) or !array_key_exists('registrant', $ret) or !$ret['registrant']) {
			global $_LANG;
			$params['error'][] = $_LANG['eppgrnocontactsfound'];
			return false;
		}
		eppgr_addToDBRegistrant($params, $ret);
	}
	return true;
}

function eppgr_deleteContactAfter(&$params, &$data, &$ret) {
	eppgr_convertUTF8toISO($params, $data['id']);
	$eppgrdb = eppgr_getDB($params);
	$query = "DELETE FROM `tbleppgrregistrants` WHERE registrant_id = '".$data['id']."' AND domain_id = '".$params['domainid']."'";
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppgr#!');}
	return true;
}

function eppgr_updateDomain(&$params, &$data) {
	global $_LANG;
	eppgr_infoDomain($params, $data);
	$eppgrdb = eppgr_getDB($params);
	$newns = array();
	for ($i = 1; $i < 10; $i++) {
		if (array_key_exists('ns'.$i, $params) and trim($params['ns'.$i])) {
			$newns[] = trim($params['ns'.$i]);
			eppgr_setLogEntry($params, 'request', 'ns'.$i, $params['ns'.$i]);
		}
	}
	if (array_key_exists('ns1', $params)) {
		eppgr_convertISOtoUTF8($newns);
		$ret = eppgr_getDomainInfo($params, $data);
		eppgr_setLogEntry($params, 'response', 'domainname', (array_key_exists('name', $ret) ? $ret['name'] : $_LANG['eppgrmissing']));
		if (array_key_exists('ns', $ret) and is_array($ret['ns']) and count($ret['ns']) and trim($ret['ns'][0])) {
			$data['ns'] = eppgr_findDiffNS($ret['ns'], $newns);
		}
		else {
			$data['ns']['add'] = $newns;
		}
		foreach ($data['ns']['add'] as $ns) {
			$command = 'RegisterNameserver';
			$ns = array('name' => $ns);
			$ret = eppgr_getServerResponse($params, $command, $ns);
			if (array_key_exists('error', $params) and count($params['error']) == 1 and
				($params['error'][0] == 'Σε Χρήση.' or $params['error'][0] == 'In Use.' or
				 $params['error'][0] == 'Σε χρήση.' or $params['error'][0] == 'In use.' or
				 $params['error'][0] == 'Σε xρήση.' or $params['error'][0] == 'Σε xρήση' or
				 $params['error'][0] == 'Σε χρήση' or $params['error'][0] == 'In use' or
				 $params['error'][0] == 'Σε Χρήση' or $params['error'][0] == 'In Use')) {
				eppgr_setLogEntry($params, 'response', 'nsAddNotice['.$ns['name'].']', $params['error'][count($params['error'])-1]);
				eppgr_setLogEntry($params, 'response', 'nsAdded', $ns['name']);
				unset($params['error']);
			}
			elseif (array_key_exists('error', $params) and count($params['error'])) {
				eppgr_setLogEntry($params, 'response', 'nsAddError['.$ns['name'].']', $params['error'][count($params['error'])-1]);
				return false;
			}
			else {
				eppgr_setLogEntry($params, 'response', 'nsAdded', $ns['name']);
			}
		}
		if (!count($data['ns']['add']) and !count($data['ns']['rem'])) {
			$params['error'][] = $_LANG['eppgrnochangefound'];
			eppgr_setLogEntry($params, 'response', 'nsMessage', $_LANG['eppgrnochangefound']);
		}
	}
	if (array_key_exists('contactdetails', $params) and is_array($params['contactdetails']) and count($params['contactdetails'])) {
		$eppobj = eppgr_GetEppobj($params);
		eppgr_fixContactDetails($params['contactdetails'], $params);
		eppgr_convertISOtoUTF8($params['contactdetails']);
		$eppobj->cleanArray($params['contactdetails']);
		$cc = eppgr_GetAllSavedContacts($params);
		eppgr_convertISOtoUTF8($cc);
		$eppobj->cleanArray($cc);
		foreach ($cc as $k => $v) {
			$id = is_array($v) ? $v['id'] : $v;
			if (!eppgr_thisContactBelongsToMe($params, $id) and trim($params['contactdetails'][$k]['firstname']) == $id) {
				unset($cc[$k]);
				unset($params['contactdetails'][$k]);
			}
			else {
				eppgr_getContactValues($params, $v, $ret, $k);
			}
		}

		$data['contact'] = eppgr_findDiffContacts($params, $ret, $params['contactdetails']);
		if (array_key_exists('error', $params) and count($params['error'])) return false;
		$add = array();
		foreach ($data['contact']['add'] as $key) {
			if (eppgr_getNewContactDetails($params, $params['contactdetails'][$key])) {
				$command = 'SaveContactDetails';
				eppgr_getServerResponse($params, $command, $params['contactdetails'][$key]);
			}
			if (array_key_exists('error', $params) and count($params['error'])) {
				eppgr_setLogEntry($params, 'response', 'createContactNotice', $params['error'][count($params['error'])-1]);
				return false;
			}
			$add[$key] = $params['contactdetails'][$key]['id'];
			eppgr_deleteContactDB($params, $params['contactdetails'][$key]['id']);
		}
		if (count($add)) {
			eppgr_setLogEntry($params, 'response', 'createdContact', $add, true);
		}
		$data['contact']['add'] = $add;
		$rem = array();
		foreach ($data['contact']['rem'] as $key) {
			if ($key != 'registrant' and !array_key_exists($key, $params['contactdetails'])) {
				$rem[$key] = $cc[$key]['id'];
			}
		}
		$data['contact']['rem'] = $rem;
		$a = eppgr_getOrigContactTypes();
		$update = array('registrant');
		foreach ($a as $b) {
			if (!array_key_exists($b, $data['contact']['add']) and !array_key_exists($b, $data['contact']['rem'])
				and array_key_exists($b, $ret) and $ret[$b])
				$update[] = $b;
		}
		foreach ($update as $u) {
			$savecontact = $params['contactdetails'][$u];
			eppgr_setAddressDetails($params, $savecontact, $savecontact['loc'], $u, false);
			$savecontact['id'] = $cc[$u]['id'];
			$pw = $cc[$u]['pw'];
			unset($cc[$u]['pw']);
			$cc[$u]['loc']['cc'] = strtoupper($cc[$u]['loc']['cc']);
			ksort($cc[$u]);
			ksort($savecontact);
			if ($u != 'registrant' and (trim($savecontact['loc']['name']) != trim($cc[$u]['loc']['name']) or
				trim($savecontact['loc']['org']) != trim($cc[$u]['loc']['org']))) {
				if (eppgr_getNewContactDetails($params, $savecontact)) {
					$command = 'SaveContactDetails';
					eppgr_getServerResponse($params, $command, $savecontact);
				}
				if (array_key_exists('error', $params) and count($params['error'])) {
					eppgr_setLogEntry($params, 'response', 'updateContactNotice', $params['error'][count($params['error'])-1]);
					return false;
				}
				eppgr_setLogEntry($params, 'response', 'updatedContact['.$u.']', $cc[$u], true);
				eppgr_deleteContactDB($params, $savecontact['id']);
				$data['contact']['add'][$u] = $savecontact['id'];
				$data['contact']['rem'][$u] = $cc[$u]['id'];
			}
			elseif (serialize($savecontact) != serialize($cc[$u])) {
				$savecontact['id'] = $cc[$u]['id'];
				if ($u == 'registrant') {
					if (array_key_exists('contactchange', $_REQUEST) and $_REQUEST['contactchange'] and
						(strtolower($_REQUEST['contactchange']) == strtolower('ownerChange') or
						strtolower($_REQUEST['contactchange']) == strtolower('ownerNameChange')) and
						(trim($savecontact['loc']['name']) != trim($cc[$u]['loc']['name']) or
						trim($savecontact['loc']['org']) != trim($cc[$u]['loc']['org']))) {
						$dtext = serialize($savecontact);
						$z = eppgr_getAESObject($params);
						if (!is_object($z)) {
							return 0;
						}
						$z->AESEncode($dtext);
						$product = 'eppgr'.ucfirst($_REQUEST['contactchange']);
						eppgr_placeOrder($params, $postfields, $z->cipher, $product);
						eppgr_setLogEntry($params, 'response', 'placedOrder', $_LANG['eppgrregorderplaced']);
					}
					unset($savecontact['firstname']);
					unset($savecontact['lastname']);
					unset($savecontact['companyname']);
					eppgr_setLogEntry($params, 'response', 'noRegistrantChange', $_LANG['eppgrregdetailsnochange']);
					$savecontact['noerror'] = 'yes';
				}
				$savecontact['id'] = $cc[$u]['id'];
				$savecontact['pw'] = $pw;
				$command = 'UpToDateContact';
				eppgr_getServerResponse($params, $command, $savecontact);
				if (array_key_exists('error', $params) and count($params['error'])) return false;
				$params['eppgrupdatecontacts'][] = $savecontact['id'];
				$savecontact['loc']['name'] = $cc[$u]['loc']['name'];
				$savecontact['loc']['org'] = $cc[$u]['loc']['org'];
				$savecontact['pw'] = $pw;
				eppgr_saveRegistrant($params, $params['domainid'], $u, $savecontact['id'], $savecontact);
				eppgr_setLogEntry($params, 'response', 'updatedContact['.$u.']', $cc[$u], true);
			}
		}
		if ((!is_array($data['contact']['add']) or !count($data['contact']['add'])) and
			(!is_array($data['contact']['rem']) or !count($data['contact']['rem']))) {
			eppgr_setLogEntry($params, 'response', 'contactMessage', $_LANG['eppgrnocontactsaddrem']);
			return false;
		}
	}
	if (array_key_exists('eppgrchgregistrant', $params) and is_array($params['eppgrchgregistrant']) and count($params['eppgrchgregistrant'])) {
		eppgr_infoDomain($params, $data);
		eppgr_convertISOtoUTF8($data);
		if (eppgr_getNewContactDetails($params, $params['eppgrchgregistrant']['data'])) {
			$command = 'SaveContactDetails';
			eppgr_getServerResponse($params, $command, $params['eppgrchgregistrant']['data']);
		}
		if (array_key_exists('error', $params) and count($params['error'])) {
			eppgr_setLogEntry($params, 'response', 'changeRegistrantNotice', $params['error'][count($params['error'])-1]);
			return false;
		}
		eppgr_setLogEntry($params, 'response', 'updatedRegistrant', $params['eppgrchgregistrant']['data'], true);
		$data['registrant'] = $params['eppgrchgregistrant']['data']['id'];
		$data['op'] = $params['eppgrchgregistrant']['op'];
	}
	return true;
}

function eppgr_getAdminPass(&$params, &$postfields) {
	$eppgrdb = eppgr_getDB($params);
	$query = 'SELECT * FROM `tbladmins`';
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppgr#13!');}
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	$postfields["username"] = $row['username'];
	$postfields["password"] = $row['password'];
}

function eppgr_getPaymentMethod(&$params, &$postfields) {
	$eppgrdb = eppgr_getDB($params);
	$query = 'SELECT * FROM `tblpaymentgateways` WHERE `order` = 1';
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {
		$query = 'SELECT * FROM `tblpaymentgateways`';
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error eppgr#14!');}
	}
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	$postfields["paymentmethod"] = $row['gateway'];
}

function eppgr_placeOrder(&$params, &$postfields, &$dtext, $product, $data = array(), $exDate = '') {
	global $_LANG;
	$mainurl = preg_replace('/clientarea\.php.+$/', '', eppgr_curPageURL());
	$url = $mainurl."includes/api.php";
	$eppgrdb = eppgr_getDB($params);
	eppgr_getAdminPass($params, $postfields);

	$query = 'SELECT * FROM `tbldomains` WHERE `id` = '.$params['domainid'];
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppgr#15!');}
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	$postfields["clientid"] = $row['userid'];
	$postfields["domain"] = $row['domain'];
	if (array_key_exists('name', $data)) {
		$postfields["domain"] .= ' / '.$data['name'];
	}

	eppgr_getPaymentMethod($params, $postfields);

	$query = 'SELECT * FROM `tblproducts` WHERE `name` = "'.$product.'"';
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppgr#16!');}
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	$postfields["pid"] = $row['id'];

	if ($exDate) {
		$query = 'SELECT * FROM `tblpricing` WHERE `type` = "product" AND `relid` = '.$postfields["pid"];
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error eppgr#17!');}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$postfields["priceoverride"] = $row['monthly'];

		$enddate = strtotime($exDate);
		$diff = abs($enddate - time());
		$years = floor($diff / (365 * 24 * 60 * 60));
		$timeleft = $diff - ($years * 365 * 24 * 60 * 60);
		$curyear = (int) date('Y');
		$curmonth = (int) date('m');
		if ($curmonth > 2) {
			$curyear++;
		}
		$endyear = (int) date('Y', $enddate);
		$endmonth = (int) date('m', $enddate);
		if ($endmonth < 3) {
			$endyear--;
		}
		if ($curyear < $endyear) {
			for ($yyy = $curyear; $yyy <= $endyear; $yyy++) {
				$leap = date('L', strtotime($yyy.'-01-01T12:00:00.0Z'));
				$timeleft -= $leap ? 24 * 60 * 60 : 0;
			}
		}
		elseif ($curyear == $endyear) {
			$leap = date('L', strtotime($curyear.'-01-01T12:00:00.0Z'));
			$timeleft -= $leap ? 24 * 60 * 60 : 0;
		}
		if ($timeleft > 0) {
			$years++;
		}
		if ($years % 2) {
			$years++;
		}
		$postfields["priceoverride"] *= $years / 2;
	}

	$postfields["action"] = "addorder";
	$postfields["billingcycle"] = "onetime";
	$postfields["passthrough"] = 1;

	$results = eppgr_sendOrder($url, $postfields);

	if (array_key_exists("result", $results) and $results["result"] == "success") {
		eppgr_convertUTF8toISO($params, $dtext);
		$query = 'INSERT INTO `tbleppgrorders` (`order_id`, `domain_id`, `product`, `data`, `error`, `checked`) '
			.'VALUES('.$results['orderid'].', '.addslashes($params['domainid'])
			.', "'.$product.'", "'.$dtext.'", "", 0)';
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error eppgr#18!');}
		$query = 'SELECT * FROM `tblorders` WHERE `id` = '.$results['orderid'];
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error eppgr#19!');}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$invurl = $mainurl.'viewinvoice.php?id='.$row['invoiceid'];
		$params['eppgrmsgs'][] = $_LANG['eppgrgotoinvoice'].' | '.
			'<a href="'.$invurl.'">'.$invurl.'</a>';
	}
	elseif (array_key_exists("message", $results)) {
		$params['eppgrmsgs'][] = $results["message"];
	}
}

function eppgr_sendOrder(&$url, &$postfields) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 100);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	$chret = curl_exec($ch);
	curl_close($ch);

	$chret = explode(";", $chret);
	$results = array();
	foreach ($chret AS $temp) {
		$temp = explode("=",$temp);
		if (count($temp) >= 2) {
			$results[$temp[0]] = $temp[1];
		}
	}
	return $results;
}

function eppgr_deleteContactDB(&$params, $contactid) {
	$eppgrdb = eppgr_getDB($params);
	$query = "DELETE FROM `tbleppgrregistrants` WHERE `registrant_id` = '".$contactid."'";
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppgr#20!');}
}

function eppgr_getCleanVar($var) {
	$var = htmlspecialchars($var);
	if (!get_magic_quotes_gpc()) $var = addslashes($var);
	return trim($var);
}

function eppgr_fixContDetAndLog(&$params, &$new, &$data, $nk, $origkey, $newkey = false) {
	if (!$newkey) {
		$newkey = $origkey;
	}
	$new[$nk][$newkey] = eppgr_getCleanVar($data[$origkey]);
}

function eppgr_fixContactDetails(&$contactdetails, &$params) {
	$new = array();
	foreach ($contactdetails as $k => $v) {
		$nk = strtolower($k);
		$new[$nk] = array();

		if (is_array($params) and array_key_exists('contactdetails', $params) and
			is_array($params['contactdetails']) and array_key_exists($k, $params['contactdetails']) and
			is_array($params['contactdetails'][$k])) {
			$data =& $params['contactdetails'][$k];
		}
		else {
			$data =& $_REQUEST['contactdetails'][$k];
		}

		eppgr_setLogEntry($params, 'request', $nk, $data, true);

		$changes = array(
			'firstname'	=>	array('First Name'),
			'lastname'	=>	array('Last Name'),
			'companyname'	=>	array('Company Name'),
			'address1'	=>	array('Street 1', 'Address 1'),
			'address2'	=>	array('Street 2', 'Address 2'),
			'city'		=>	array('City'),
			'state'		=>	array('State'),
			'postcode'	=>	array('Postal Code', 'Postcode'),
			'country'	=>	array('Country'),
			'phonenumber'	=>	array('Phone Number'),
			'fax'		=>	array('Fax'),
			'email'		=>	array('Email'),
		);
		
		foreach ($changes as $key => $values) {
			foreach ($values as $value) {
				if (array_key_exists($value, $v)) {
					eppgr_fixContDetAndLog($params, $new, $data, $nk, $value, $key);
					continue 2;
				}
			}
			if (array_key_exists($key, $v)) {
				eppgr_fixContDetAndLog($params, $new, $data, $nk, $key);
			}
		}

		$new[$nk]['country'] = eppgr_findCountry($params, $new[$nk]['country']);
	}
	$contactdetails = $new;
}

function eppgr_getCountry(&$params, $key) {
	require realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'countries.php');
	$lang = $params['language'];
	if (!$lang or !array_key_exists($lang, $cc)) $lang = 'en';
	$key = strtoupper($key);
	if ($key and array_key_exists($key, $cc[$lang]) and $cc[$lang][$key]) return $cc[$lang][$key];
	else return $key;
}

function eppgr_findCountry(&$params, $input) {
	if (!$input) return '';
	if (strlen($input) == 2) return strtoupper($input);
	require realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'countries.php');
	$lang = $params['language'];
	if (!$lang or !array_key_exists($lang, $cc)) $lang = 'en';
	if ($lang == 'el') {
		eppgr_convertISOtoUTF8($input);
		$accented = array('ά','έ','ή','ί','ό','ύ','ώ','ϊ','ϋ','ΐ','ΰ','ς','Ά','Έ','Ή','Ί','Ό','Ύ','Ώ','Ϊ','Ϋ',
						  'α','β','γ','δ','ε','ζ','η','θ','ι','κ','λ','μ','ν','ξ','ο','π','ρ','σ','τ','υ','φ','χ','ψ','ω');
		$plain = array('Α','Ε','Η','Ι','Ο','Υ','Ω','Ι','Υ','Ι','Υ','Σ','Α','Ε','Η','Ι','Ο','Υ','Ω','Ι','Υ',
						  'Α','Β','Γ','Δ','Ε','Ζ','Η','Θ','Ι','Κ','Λ','Μ','Ν','Ξ','Ο','Π','Ρ','Σ','Τ','Υ','Φ','Χ','Ψ','Ω');
		for ($i = 0; $i < count($plain); $i++) {
			$input = str_replace($accented[$i], $plain[$i], $input);
		}
	}
	$input = mb_strtoupper($input);
	if ($input == 'ΕΛΛΑΣ' or $input == 'ΕΛΛΑΔΑ' or $input == 'GREECE' or $input == 'HELLAS') return 'GR';
	$c = $cc[$lang];
	$c = array_flip($c);
	if (in_array($input, array_keys($c))) return $c[$input];
	foreach ($c as $k => $v) {
		if (preg_match('/'.$input.'/', $k)) return $v;
	}
	return 'GR';
}

function eppgr_updateHost(&$params, &$host) {
	global $_LANG;
	eppgr_loadLangFiles($params);
	eppgr_convertISOtoUTF8($host);
	$command = 'CheckHost';
	if (!$host or !is_array($host)) {
		eppgr_convertISOtoUTF8($params['nameserver']);
		$host = array();
		$host['name'] = trim($params['nameserver']);
		$ipkinds = array('new' => 'add', 'current' => 'rem');
		foreach ($ipkinds as $key => $value) {
			$ipaddress = strtoupper(trim($params[$key.'ipaddress']));
			$ipv = eppgr_getIPVersion($ipaddress);
			if (!$ipv) {
				$params['error'][] = $_LANG['eppgrnovalid'.$key.'ipaddressfound'];
				return false;
			}
			$host[$value.$ipv] = array($ipaddress);
		}
	}
	$name = $host['name'];
	$ret = eppgr_getServerResponse($params, $command, $host);
	if (!$ret) return false;
	elseif (!is_array($ret)) {
		$params['error'][] = $ret;
		return false;
	}
	elseif (!array_key_exists($name, $ret)) {
		$params['error'][] = $_LANG['eppgrhostnotfound'];
		return false;
	}
	elseif ($ret[$name]['avail']) {
		$command = 'RegisterNameserver';
		$newhost = array();
		$newhost['name'] = $host['name'];
		if (is_array($host) and array_key_exists('remv4', $host) and $host['remv4']) {
			$newhost['v4'] = $host['remv4'];
		}
		elseif (is_array($host) and array_key_exists('remv6', $host) and $host['remv6']) {
			$newhost['v6'] = $host['remv6'];
		}
		else {
			$params['error'][] = $_LANG['eppgrnovalidcurrentipaddressfound'];
			return false;
		}
		$ret = eppgr_getServerResponse($params, $command, $newhost);
		return true;
	}
	else return true;
}

function eppgr_findDiffNS(&$old, &$new) {
	$rem = array_values(array_diff($old, $new));
	$add = array_values(array_diff($new, $old));
	return array('add' => $add, 'rem' => $rem);
}

function eppgr_findDiffContacts(&$params, &$old, &$new) {
	$n = array();
	foreach ($new as $k => $v) {
		if (eppgr_addressOKFromRequest($params, $v, $k)) {
			$n[] = strtolower($k);
		}
		else {
			if (isset($old[strtolower($k)])) {
				unset($old[strtolower($k)]);
			}
			unset($new[$k]);
		}
	}
	$o = array();
	foreach ($old as $k => $v) {
		if ($v) $o[] = strtolower($k);
	}
	$r = array_values(array_diff($o, $n));
	$a = array_values(array_diff($n, $o));
	$rem = array();
	$add = array();
	foreach ($r as $s) {
		if (strtolower($s) != 'registrant') $rem[] = strtolower($s);
	}
	foreach ($a as $b) {
		if (strtolower($b) != 'registrant') $add[] = strtolower($b);
	}
	return array('add' => $add, 'rem' => $rem);
}

function eppgr_infoDomain(&$params, &$data) {
	global $_LANG;
	if (!array_key_exists('domainid', $params) and array_key_exists('domainid', $_REQUEST) and $_REQUEST['domainid']) {
		preg_match('/\d+/', $_REQUEST['domainid'], $matches);
		$params['domainid'] = $matches[0];
	}
	elseif (!array_key_exists('domainid', $params)) die('No domain could be found!');
	$eppgrdb = eppgr_getDB($params);
	$query = "SELECT domain FROM `tbldomains` WHERE id = ".$params['domainid'];
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppgr#21!');}
	$domain = $stmt->fetch(PDO::FETCH_ASSOC);
	eppgr_getSLDTLD($domain['domain'], $params['sld'], $params['tld']);
	eppgr_convertISOtoUTF8($params['sld']);
	eppgr_convertISOtoUTF8($params['tld']);
	$data['name'] = $params['sld'].'.'.$params['tld'];
	eppgr_setLogEntry($params, 'request', 'domainname', (array_key_exists('name', $data) ? $data['name'] : $_LANG['eppgrmissing']));
	return true;
}

function eppgr_getOrigContactTypes() {
	return array('admin', 'tech', 'billing');
}

function eppgr_setEarlyExpiration(&$params, &$data, &$ret, $command) {
	if (!is_array($ret)) return;
	$eppgrdb = eppgr_getDB($params);
	$query = "SELECT * FROM `tbldomains` WHERE id = ".$params['domainid'];
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppgr#22!');}
	$ddd = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!is_array($ddd) or !array_key_exists('id', $ddd) or !$ddd['id']) {
		return;
	}
	$action = str_replace('rnew', 'renew', str_replace('Domain', '', $command));
	$actiontime = gmdate('Y-m-d H:i:s');
	$query = "INSERT INTO `tbleppgrdates` (`domainid`, `action`, `actiontime`,
		`registrationdate`, `oldexpirydate`, `oldnextduedate`, `oldnextinvoicedate`,
		`checked`) VALUES(".$params['domainid'].", '".$action."', '".$actiontime
		."', '".$ddd['registrationdate']."', '".$ddd['expirydate']."', '"
		.$ddd['nextduedate']."', '".$ddd['nextinvoicedate']."', 0)";
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppgr#23!');}
}

function eppgr_infoDomainAfter(&$params, &$data, &$ret) {
	global $_LANG, $smartyvalues;
	$transfer = false;
	if (array_key_exists('eppgrtranscheck01', $params) and $params['eppgrtranscheck01']) {
		$z = eppgr_getAESObject($params);
		if (!is_object($z)) {
			return 0;
		}
		$z->AESEncode($params['eppgrtranscheck01']);
		if (array_key_exists('eppgrtranscheck02', $params) and $params['eppgrtranscheck02'] and $params['eppgrtranscheck02'] == $z->cipher) {
			$transfer = true;
		}
	}
	if (!preg_match('/^'.$params['contactid'].'_/', $ret['registrant']) and !$transfer) {
		$params['error'][] = 'Domain does not belong to registrar';
		return false;
	}
	eppgr_addToDBRegistrant($params, $data);
	return true;
}

function eppgr_getEmailAddresses(&$params, &$from, &$emails, $option) {
	global $_LANG;
	$from = '';
	$regex = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/";
	$eppgrdb = eppgr_getDB($params);
	$query = "SELECT `email` FROM `tbladmins`";
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppgr#23a!');}
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if (preg_match($regex, trim($row['email']))) {
			$emails[] = trim($row['email']);
			if (!$from) {
				$from = trim($row['email']);
			}
		}
	}
	if (!count($emails)) {
		eppgr_setLogEntry($params, 'response', 'emailCredits', $_LANG['eppgrnoadminemailsfound']);
		return false;
	}
	if ($params[$option]) {
		$ce = explode(',', trim($params[$option]));
		$cleaned = false;
		foreach ($ce as $email_address) {
			if (preg_match($regex, trim($email_address))) {
				if (!$cleaned) {
					$emails = array();
					$cleaned = true;
				}
				$emails[] = trim($email_address);
			}
		}
	}
	if (!count($emails)) {
		eppgr_setLogEntry($params, 'response', 'emailCredits', $_LANG['eppgrnorecipientemailsfound']);
		return false;
	}
	return true;
}

function eppgr_emailCredits(&$params) {
	global $_LANG;
	if (!eppgr_getEmailAddresses($params, $from, $emails, 'CreditEmails')) {
		eppgr_setLogEntry($params, 'response', 'mailSentFailed', $_LANG['eppgrmailcreditfailed']);
		return;
	}
	$command = 'infoAccount';
	$ret = eppgr_getServerResponse($params, $command);
	if (is_array($ret) and count($ret) and array_key_exists('credits', $ret)) {
		eppgr_setLogEntry($params, 'response', 'creditsFound', $ret['credits'], true);
		if ($params['CreditEmailsAfter'] != '' and is_numeric($params['CreditEmailsAfter']) and $ret['credits'] * 1 > $params['CreditEmailsAfter'] * 1) {
			return;
		}
		$to = implode(', ', $emails);
		$subject = $_LANG['eppgrcreditssubject'];
		$body[] = $_LANG['eppgrcredits'].$ret['credits'].'€';
		if ($ret['caAllowed'] == 'true') {
			$body[] = $_LANG['eppgrcreditsallowed']."\r\n";
		}
		else {
			$body[] = $_LANG['eppgrcreditsnotallowed']."\r\n";
		}
		$body[] = $_LANG['eppgrcreditsroid'].$ret['roid'];
		$body[] = $_LANG['eppgrcreditsdias'].$ret['ddPaymentCode'];
		if (array_key_exists('tbSuspendedOn', $ret) and $ret['tbSuspendedOn']) {
			$body[] = $_LANG['eppgrcreditstbsuspended'].$ret['tbSuspendedOn'];
		}
		if (array_key_exists('suspendedOn', $ret) and $ret['suspendedOn']) {
			$body[] = $_LANG['eppgrcreditssuspended'].$ret['suspendedOn'];
		}
		$body = implode("\r\n", $body);
		$headers = 'From: ' . $from . "\r\n" .
			'Reply-To: ' . $from . "\r\n" .
			'Content-type: text/plain; charset=' . strtoupper($params['charset']) . "\r\n" .
			'X-Mailer: PHP/' . phpversion();
		if (mail($to, $subject, $body, $headers)) {
			eppgr_setLogEntry($params, 'response', 'mailSent', $_LANG['eppgrmailcreditsuccess']);
		}
	}
	else {
		eppgr_setLogEntry($params, 'response', 'noInfoFound', $_LANG['eppgrnocreditinfo']);
	}
}

function eppgr_createDomainAfter(&$params, &$data, &$ret) {
	if (eppgr_isChecked($params, 'CreditsAfterCreateDomain')) {
		eppgr_emailCredits($params);
	}
	if (is_array($data) and array_key_exists('record', $data) and $data['record'] == 'dname') {
		return true;
	}
	eppgr_setEarlyExpiration($params, $data, $ret, 'createDomain');
	if (is_array($data) and is_array($ret) and array_key_exists('name', $data) and
		array_key_exists('name', $ret) and $data['name'] and $ret['name'] and $data['name'] != $ret['name']) {
		$eppgrdb = eppgr_getDB($params);
		$query = "UPDATE `tbldomains` SET `domain` = '".$ret['name']."' WHERE `id` = ".$params['domainid'];
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error eppgr#24!');}
	}
	return true;
}

function eppgr_getDomainName(&$params, $domainid) {
	$eppgrdb = eppgr_getDB($params);
	$query = "SELECT domain FROM `tbldomains` WHERE id = $domainid";
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppgr#25!');}
	$name = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!is_array($name) or !array_key_exists('domain', $name) or !$name['domain']) die('No domain found!');
	return mb_strtolower($name['domain'], $params['charset']);
}

function eppgr_createDomain(&$params, &$data) {
	global $_LANG;
	if (is_array($data) and count($data)) {
		return true;
	}
	$data['name'] = eppgr_getDomainName($params, $params['domainid']);
	eppgr_setLogEntry($params, 'request', 'domainid', $params['domainid']);
	eppgr_setLogEntry($params, 'response', 'domainname', ($data['name'] ? $data['name'] : $_LANG['eppgrmissing']));
	eppgr_convertISOtoUTF8($data['name']);
	if (eppgr_isChecked($params, 'CopyAdminToTech')) $params['tech'] = $params['admin'];
	if (eppgr_isChecked($params, 'CopyAdminToBill')) $params['billing'] = $params['admin'];
	eppgr_harvestAddresses($params);
	eppgr_checkContacts($params);
	eppgr_checkUse($params);
	if (array_key_exists('error', $params)) {
		eppgr_setLogEntry($params, 'response', 'createDomainError', $params['error'][count($params['error'])-1]);
		return false;
	}
	$gethosts = true;
	eppgr_checkHosts($params);
	if (array_key_exists('error', $params) and count($params['error'])) {
		$gethosts = false;
	}
	$data['registrant'] = $params['registrant']['id'];
	$data['pw'] = eppgr_getRandomPassword();
	eppgr_setLogEntry($params, 'response', 'setRegistrant', $data['registrant'], true);
	eppgr_setLogEntry($params, 'response', 'setDomainPassword', $data['pw'], true);
	if ($gethosts) {
		$ns = array();
		for ($i = 1; $i < 10; $i++) {
			if (array_key_exists('ns'.$i, $params) and trim($params['ns'.$i])) {
				$ns[] = $params['ns'.$i];
				eppgr_setLogEntry($params, 'response', 'setNs'.$i, $params['ns'.$i]);
			}
		}
		$data['ns'] = $ns;
	}
	else {
		unset($params['error']);
		$data['ns'][] = '';
	}
	$a = eppgr_getOrigContactTypes();
	$contact = array();
	foreach ($a as $b) {
		if (array_key_exists($b, $params)) {
			$contact[$b] = $params[$b]['id'];
			eppgr_setLogEntry($params, 'response', 'setContact'.$b, $params[$b]['id'], true);
		}
	}
	$data['contact'] = $contact;
	$data['use'] = $params['use'];
	return true;
}

function eppgr_saveRegistrant(&$params, $domainid, $type, $regid, $contact=array()) {
	if (!count($contact)) {
		$contact = eppgr_getContactById($params, $regid);
		if (array_key_exists('error', $params) and count($params['error'])) return false;
	}
	eppgr_convertUTF8toISO($params, $regid);
	eppgr_convertUTF8toISO($params, $contact);
	$eppobj = eppgr_GetEppobj($params);
	$eppobj->cleanArray($contact);
	$eppgrdb = eppgr_getDB($params);
	$query = "INSERT INTO `tbleppgrregistrants` (`domain_id`, `type`, `registrant_id`, `values`) "
			."VALUES ({$domainid}, '{$type}', '{$regid}', '".base64_encode(serialize($contact))."') "
			."ON DUPLICATE KEY UPDATE `registrant_id` = '{$regid}', `values` = '".base64_encode(serialize($contact))."'";
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppgr#26!');}
}

function eppgr_checkUse(&$params) {
	$eppgrdb = eppgr_getDB($params);
	$query = 'SELECT value FROM tbldomainsadditionalfields WHERE domainid = '.addslashes($params['domainid']).' ORDER BY id DESC';
	try {$stmt = $eppgrdb->query($query);}
	catch(PDOException $ex) {die('MySQL error eppgr#27!');}
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	if (is_array($row)) eppgr_convertISOtoUTF8($row['value']);
	else $row['value'] = '';
	$params['use'] = $row['value'];
}

function eppgr_checkHosts(&$params) {
	eppgr_convertISOtoUTF8($params['ns']);
	for ($i = 1; $i < 10; $i++) {
		if (array_key_exists('ns'.$i, $params) and trim($params['ns'.$i])) {
			$command = 'RegisterNameserver';
			$data = array('name' => $params['ns'.$i]);
			eppgr_getServerResponse($params, $command, $data);
			if (array_key_exists('error', $params) and count($params['error']) == 1 and
				($params['error'][0] == 'Σε Χρήση.' or $params['error'][0] == 'In Use.' or
				 $params['error'][0] == 'Σε χρήση.' or $params['error'][0] == 'In use.' or
				 $params['error'][0] == 'Σε χρήση' or $params['error'][0] == 'In use' or
				$params['error'][0] == 'Σε Χρήση' or $params['error'][0] == 'In Use')) {
				eppgr_setLogEntry($params, 'response', 'existsNs', $params['ns'.$i]);
				unset($params['error']);
			}
			elseif (array_key_exists('error', $params) and count($params['error'])) {
				eppgr_setLogEntry($params, 'response', 'createNsNotice['.$data['name'].']', $params['error'][count($params['error'])-1]);
				return false;
			}
			eppgr_setLogEntry($params, 'response', 'createdNs', $params['ns'.$i]);
		}
	}
	return $data;
}

function eppgr_deleteHost(&$params, &$host) {
	global $_LANG;
	eppgr_loadLangFiles($params);
	eppgr_convertISOtoUTF8($host);
	$command = 'CheckHost';
	if (!$host or !is_array($host)) {
		eppgr_convertISOtoUTF8($params['nameserver']);
		$host = array('name' => trim($params['nameserver']));
	}
	$name = $host['name'];
	$ret = eppgr_getServerResponse($params, $command, $host);
	if (!$ret) {
		return false;
	}
	elseif (!is_array($ret)) {
		$params['error'][] = $ret;
		return false;
	}
	elseif (!array_key_exists($name, $ret)) {
		$params['error'][] = $_LANG['eppgrhostnotfound'];
		return false;
	}
	elseif ($ret[$name]['avail']) {
		$params['error'][] = $_LANG['eppgrhostdoesnotexist'];
		return false;
	}
	else return true;
}

function eppgr_getIPVersion($ipaddress) {
	$ipv = false;
	if (eppgr_isIPV4($ipaddress)) {
		$ipv = 'v4';
	}
	elseif (eppgr_isIPV6($ipaddress)) {
		$ipv = 'v6';
	}
	return $ipv;
}

function eppgr_isIPV4($ipaddress) {
	return preg_match('/^\d{1,3}(\.\d{1,3}){3}$/', $ipaddress);
}

function eppgr_isIPV6($ipaddress) {
	return preg_match('/^((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?$/', $ipaddress);
}

function eppgr_createHost(&$params, &$host) {
	eppgr_convertISOtoUTF8($host);
	$command = 'CheckHost';
	if (!$host or !is_array($host)) {
		eppgr_convertISOtoUTF8($params['nameserver']);
		$host = array();
		$host['name'] = trim($params['nameserver']);
		$ipaddress = strtoupper(trim($params['ipaddress']));
		$ipv = eppgr_getIPVersion($ipaddress);
		if (!$ipv) {
			$params['error'][] = 'No valid IP found.';
			return false;
		}
		$host[$ipv] = array($ipaddress);
	}
	$name = $host['name'];
	$ret = eppgr_getServerResponse($params, $command, $host);
	if (!$ret) return false;
	elseif (!is_array($ret)) {
		$params['error'][] = $ret;
		return false;
	}
	elseif (!array_key_exists($name, $ret)) {
		$params['error'][] = 'Host not found.';
		return false;
	}
	elseif (!$ret[$name]['avail']) {
		$params['error'][] = $ret[$name]['reason'];
		return false;
	}
	else return true;
}

function eppgr_checkContacts(&$params) {
	$types = eppgr_getContactTypes();
	foreach ($types as $a) {
		if (!array_key_exists($a, $params) or !is_array($params[$a])) continue;
		if (!array_key_exists('id', array_keys($params[$a]))) {
			if (eppgr_getNewContactDetails($params, $params[$a])) {
				$command = 'SaveContactDetails';
				$ret = eppgr_getServerResponse($params, $command, $params[$a]);
			}
			if (array_key_exists('error', $params) and count($params['error'])) {
				eppgr_setLogEntry($params, 'response', 'createContactNotice', $params['error'][count($params['error'])-1]);
				break;
			}
			$response = array();
			$hide = eppgr_getFiedsToHide();
			foreach ($params[$a] as $key => $value) {
				if (in_array($key, $hide) or in_array($key, array('contact_id', 'loc', 'int'))) {
					$response[$key] = $params[$a][$key];
				}
			}
			eppgr_setLogEntry($params, 'response', 'createdContact['.$a.']', $response, true);
		}
	}
	return true;
}

function eppgr_createContact(&$params, &$contact) {
	eppgr_convertISOtoUTF8($contact);
	if (!is_array($contact) or !is_array($params)) die('Wrong parameters #1');
	if (!array_key_exists('loc', $contact)) {
		eppgr_setAddressDetails($params, $contact, $d);
		$contact['loc'] = $d;
	}
	if (!eppgr_addressOK($params, $contact)) {
		return false;
	}
	eppgr_SetDisclose($params, $params['domainid']);
	return true;
}

function eppgr_updateContact(&$params, &$contact) {
	eppgr_convertISOtoUTF8($contact);
	if (!is_array($contact) or !is_array($params)) die('Wrong parameters #1');
	if (!array_key_exists('loc', $contact)) {
		eppgr_setAddressDetails($params, $contact, $d);
		$contact['loc'] = $d;
	}
	if (!eppgr_addressOK($params, $contact)) {
		return false;
	}
	eppgr_SetDisclose($params, $params['domainid']);
	$d = $contact;
	if (!eppgr_contactExists($d, $params)) {
		return false;
	}
	else {
		return true;
	}
}

function eppgr_contactExists(&$contact, &$params) {
	$command = 'CheckContact';
	$contact_ids = array($contact['id']);
	$contacts = eppgr_getServerResponse($params, $command, $contact_ids);
	return $contacts[$contact['id']]['avail'] ? false : true;
}

function eppgr_base60($num) {
	$a = array(
		'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j',
		'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't',
		'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D',
		'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N',
		'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
		'Y', 'Z', '2', '3', '4', '5', '6', '7', '8', '9',
	);
	$num = (int) $num;
	$ret = '';
	if ($num >= 60) {
		do {
			$ret .= $a[$num%60];
			$num = floor($num/60);
		} while ($num >= 60);
		$ret .= $a[$num];
	}
	else $ret .= $a[$num];
	return strrev($ret);
}

function eppgr_getTimestamp(&$domainid) {
	global $eppgrcontactcounter;
	$t = time();
	if (!isset($eppgrcontactcounter)) $eppgrcontactcounter = 0;
	$suffix = eppgr_base60($domainid);
	$timestamp = date("y", $t);
	$suffix .= eppgr_base60($timestamp);
	$timestamp = date("m", $t);
	$suffix .= eppgr_base60($timestamp);
	$timestamp = date("d", $t);
	$suffix .= eppgr_base60($timestamp);
	$timestamp = date("H", $t);
	$suffix .= eppgr_base60($timestamp);
	$timestamp = date("i", $t);
	$suffix .= eppgr_base60($timestamp);
	$timestamp = date("s", $t);
	$suffix .= eppgr_base60($timestamp);
	$suffix .= eppgr_base60($eppgrcontactcounter);
	$eppgrcontactcounter++;
	return $suffix;
}

function eppgr_getNewContactDetails(&$params, &$contact) {
	$contactid = eppgr_getContactID($params);
	$domainid = $params['domainid'];
	$suffix = eppgr_getTimestamp($domainid);
	$contact['id'] = $contactid.'_'.$suffix;
	$counter = 0;
	while (eppgr_contactExists($contact, $params)) {
		$suffix = eppgr_getTimestamp($domainid);
		$contact['id'] = $contactid.'_'.$suffix;
		if ($counter > 10) {
			$params['error'][] = "Could not create contact. Please, try again.";
			return false;
		}
		$counter++;
	}
	$contact['pw'] = eppgr_getRandomPassword();
	return true;
}

// Taken from http://www.totallyphp.co.uk/code/create_a_random_password.htm
function eppgr_getRandomPassword() {
	$chars0 = "ABCDEFGHJKMNPQRSTUVWXYZ";
	$chars1 = "abcdefghijkmnopqrstuvwxyz";
	$chars2 = "23456789";
	$chars3 = '~!@#$%^&*:;-+=?';
	srand((double)microtime()*1000000);
	$i = 0;
	$pass = '' ;
	
	while ($i <= 11) {
	    $n = rand() % 4;
	    $pass = $pass . eppgr_getPassChar(${"chars" . $n});
	    $i++;
	}
	
	while ($i <= 15) {
	    $n = $i - 12;
	    $pass = $pass . eppgr_getPassChar(${"chars" . $n});
	    $i++;
	}
	
	$array = preg_split('//', $pass);
	array_pop($array);
	array_shift($array);
	shuffle($array);
	
	return implode($array);
}

function eppgr_getPassChar($chars) {
        $num = rand() % (strlen($chars) - 1);
        return substr($chars, $num, 1);
}

function eppgr_harvestAddresses(&$params) {
	if (!is_array($params['registrant']) or !count($params['registrant'])) {
		$params['error'][] = 'No registrant details found!';
		return;
	}
	else {
		$params['registrant']['loc'] = array();
		eppgr_setAddressDetails($params, $params['registrant'], $params['registrant']['loc'], 'registrant');
	}
	if (!array_key_exists('error', $params) and is_array($params['admin']) and count($params['admin'])) {
		$params['admin']['loc'] = array();
		eppgr_setAddressDetails($params, $params['admin'], $params['admin']['loc'], 'admin');
	}
	if (!array_key_exists('error', $params) and is_array($params['tech']) and count($params['tech'])) {
		$params['tech']['loc'] = array();
		eppgr_setAddressDetails($params, $params['tech'], $params['tech']['loc'], 'tech');
	}
	if (!array_key_exists('error', $params) and is_array($params['billing']) and count($params['billing'])) {
		$params['billing']['loc'] = array();
		eppgr_setAddressDetails($params, $params['billing'], $params['billing']['loc'], 'billing');
	}
}

function eppgr_setAddressDetails(&$params, &$arsearch, &$arsave, $contact='contact', $onerror=true) {
	if (array_key_exists('firstname', $arsearch) and array_key_exists('lastname', $arsearch) and
		$arsearch['firstname'] and $arsearch['lastname']) {
		$arsave['name'] = trim($arsearch['firstname']) . ' ' . trim($arsearch['lastname']);
	}
	elseif(array_key_exists('noerror', $arsearch) and $arsearch['noerror'] == 'yes') {
		unset($arsearch['noerror']);
	}
	elseif($onerror) {
		$params['error'][] = 'You need to provide your full name in your '. $contact . ' details.';
		eppgr_setLogEntry($params, 'response', 'ContactError['.$contact.']', $params['error'][count($params['error'])-1]);
	}
	if (array_key_exists('companyname', $arsearch) and $arsearch['companyname']) {
		$arsave['org'] = trim($arsearch['companyname']);
	}
	if (array_key_exists('address1', $arsearch) and $arsearch['address1']){
		$arsave['str1'] = trim($arsearch['address1']);
	}
	if (array_key_exists('address2', $arsearch) and $arsearch['address2']){
		$arsave['str2'] = trim($arsearch['address2']);
	}
	if (array_key_exists('city', $arsearch) and $arsearch['city']){
		$arsave['city'] = trim($arsearch['city']);
	}
	elseif($onerror) {
		$params['error'][] = 'You need to provide your city in your '. $contact . ' details.';
		eppgr_setLogEntry($params, 'response', 'ContactError['.$contact.']', $params['error'][count($params['error'])-1]);
	}
	if (array_key_exists('state', $arsearch) and $arsearch['state']){
		$arsave['sp'] = trim($arsearch['state']);
	}
	elseif($onerror) {
		$params['error'][] = 'You need to provide your state/area in your '. $contact . ' details.';
		eppgr_setLogEntry($params, 'response', 'ContactError['.$contact.']', $params['error'][count($params['error'])-1]);
	}
	if (array_key_exists('postcode', $arsearch) and $arsearch['postcode']){
		$arsave['pc'] = trim($arsearch['postcode']);
	}
	elseif($onerror) {
		$params['error'][] = 'You need to provide your postcode in your '. $contact . ' details.';
		eppgr_setLogEntry($params, 'response', 'ContactError['.$contact.']', $params['error'][count($params['error'])-1]);
	}
	if (array_key_exists('country', $arsearch) and $arsearch['country']) {
		$arsave['cc'] = trim($arsearch['country']);
	}
	elseif($onerror) {
		$params['error'][] = 'You need to provide your country in your '. $contact . ' details.';
		eppgr_setLogEntry($params, 'response', 'ContactError['.$contact.']', $params['error'][count($params['error'])-1]);
	}
	if (array_key_exists('phonenumber', $arsearch) and $arsearch['phonenumber']){
		$arsearch['voice'] = trim($arsearch['phonenumber']);
	}
	elseif($onerror) {
		$params['error'][] = 'You need to provide your phone number in your '. $contact . ' details.';
		eppgr_setLogEntry($params, 'response', 'ContactError['.$contact.']', $params['error'][count($params['error'])-1]);
	}
	if (array_key_exists('fax', $arsearch) and $arsearch['fax']){
		$arsearch['fax'] = trim($arsearch['fax']);
	}
	if ((!array_key_exists('email', $arsearch) or !$arsearch['email']) and $onerror){
		$params['error'][] = 'You need to provide your e-mail in your '. $contact . ' details.';
		eppgr_setLogEntry($params, 'response', 'ContactError['.$contact.']', $params['error'][count($params['error'])-1]);
	}
	unset($arsearch['firstname']);
	unset($arsearch['lastname']);
	unset($arsearch['companyname']);
	unset($arsearch['address1']);
	unset($arsearch['address2']);
	unset($arsearch['city']);
	unset($arsearch['state']);
	unset($arsearch['postcode']);
	unset($arsearch['country']);
	unset($arsearch['phonenumber']);
}

function eppgr_getDB(&$params) {
	global $eppgrdb;
	if (!isset($eppgrdb) or !is_resource($eppgrdb)){
		$config = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' .
				DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'configuration.php');
		include $config;

		try {$eppgrdb = new PDO('mysql:host='.$db_host.';dbname='.$db_name.';charset=utf8', $db_username, $db_password);}
		catch(PDOException $ex) {die('Could not connect to database: '.$ex->getMessage());}

		$query = 'SHOW CREATE TABLE tblclients';
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error eppgr#28!');}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$charsets = explode('CHARSET=', $row['Create Table']);
		$charset = 'latin1';
		if (is_array($charsets) and count($charsets) and preg_match('/utf8/', $charsets[count($charsets)-1])) {
			$charset = 'utf8';
		}

		$query = 'SET NAMES '.$charset;
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error eppgr#28a!');}
	}
	eppgr_checkDB($params);
	return $eppgrdb;
}

function eppgr_loadLangFiles(&$params) {
	global $_LANG, $smartyvalues;
	if (is_array($_LANG) and !array_key_exists('eppgrseteppcode', $_LANG)) {
		$langfile = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $params['language'].'_'.(strtoupper($params['charset']) == 'UTF-8' ? 'utf8' : 'iso').'.php';
		if (is_file($langfile) and is_readable($langfile)) require_once $langfile;
		if (is_array($smartyvalues) and array_key_exists('LANG', $smartyvalues)) {
			foreach ($_LANG as $key => $value) {
				if (preg_match('/eppgr/', $key)) $smartyvalues["LANG"][$key] = $value;
			}
		}
		else $smartyvalues["LANG"] = $_LANG;
	}
}

function eppgr_checkDB(&$params) {
	global $eppgrdb, $eppgrDBChecked;
	if (!isset($eppgrDBChecked) or $eppgrDBChecked != 1){
		require_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'eppgr.aes.php');
		$z = eppgr_getAESObject($params);
		if (!is_object($z)) {
			return 0;
		}

		$params['EPPHost'] = 'https://regepp.ics.forth.gr:700/epp/proxy';
		$params['PlainWHOIS'] = 'https://grwhois.ics.forth.gr:800/plainwhois/plainWhois?domainName=';
		$params['PlainDomainCheck'] = 'https://grwhois.ics.forth.gr:800/plainDomainCheck?domainName=';
		$params['TestEPPHost'] = 'https://uat-regepp.ics.forth.gr:700/epp/proxy';
		$params['TestPlainWHOIS'] = 'https://uat-grwhois.ics.forth.gr:800/plainwhois/plainWhois?domainName=';
		$params['TestPlainDomainCheck'] = 'https://uat-grwhois.ics.forth.gr:800/plainDomainCheck?domainName=';

		eppgr_fixParams($params);
		$eppgrDBChecked = 1;
		$query = "SELECT value FROM `tblconfiguration` WHERE `setting` = 'Charset'";
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error eppgr#29!');}
		$chr = $stmt->fetch(PDO::FETCH_ASSOC);
		$params['charset'] = $chr['value'];
		eppgr_loadLangFiles($params);

		if (preg_match('#/clientsdomains\.php#', $_SERVER['SCRIPT_FILENAME'])) {
			if (strtoupper($params['charset']) == 'UTF-8') $charset = 'utf8';
			else $charset = 'latin1';
			$query = "CREATE TABLE IF NOT EXISTS `tbleppgrorders` ( "
					."`id` int(10) NOT NULL auto_increment, "
					."`order_id` int(10) NOT NULL, "
					."`domain_id` int(10) NOT NULL, "
					."`product` varchar(50) NOT NULL, "
					."`data` text NOT NULL, "
					."`error` text NOT NULL, "
					."`checked` tinyint NOT NULL DEFAULT '0', "
					."PRIMARY KEY  (`id`), "
					."UNIQUE KEY `order` (`order_id`) "
					.") ENGINE=MyISAM  DEFAULT CHARSET=".$charset." COLLATE=".$charset."_general_ci;";
			try {$stmt = $eppgrdb->query($query);}
			catch(PDOException $ex) {die('MySQL error eppgr#30!');}
			$query = "CREATE TABLE IF NOT EXISTS `tbleppgrdates` ( "
					."`id` int(10) NOT NULL auto_increment, "
					."`domainid` int(10) NOT NULL, "
					."`action` varchar(10) NOT NULL, "
					."`actiontime` datetime NOT NULL, "
					."`creationdate` date NOT NULL DEFAULT '0000-00-00', "
					."`registrationdate` date NOT NULL DEFAULT '0000-00-00', "
					."`oldexpirydate` date NOT NULL DEFAULT '0000-00-00', "
					."`newexpirydate` date NOT NULL DEFAULT '0000-00-00', "
					."`oldnextduedate` date NOT NULL DEFAULT '0000-00-00', "
					."`newnextduedate` date NOT NULL DEFAULT '0000-00-00', "
					."`oldnextinvoicedate` date NOT NULL DEFAULT '0000-00-00', "
					."`newnextinvoicedate` date NOT NULL DEFAULT '0000-00-00', "
					."`checked` tinyint NOT NULL DEFAULT '0', "
					."PRIMARY KEY  (`id`) "
					.") ENGINE=MyISAM  DEFAULT CHARSET=".$charset." COLLATE=".$charset."_general_ci;";
			try {$stmt = $eppgrdb->query($query);}
			catch(PDOException $ex) {die('MySQL error eppgr#31!');}
			$query = "CREATE TABLE IF NOT EXISTS `tbleppgrdomains` ( "
					."`id` int(10) NOT NULL auto_increment, "
					."`domain_id` int(10) NOT NULL, "
					."`idprotection` text NOT NULL, "
					."PRIMARY KEY  (`id`), "
					."UNIQUE KEY `domain` (`domain_id`) "
					.") ENGINE=MyISAM  DEFAULT CHARSET=".$charset." COLLATE=".$charset."_general_ci;";
			try {$stmt = $eppgrdb->query($query);}
			catch(PDOException $ex) {die('MySQL error eppgr#32!');}
			$query = "CREATE TABLE IF NOT EXISTS `tbleppgrregistrants` ( "
					."`id` int(10) NOT NULL auto_increment, "
					."`domain_id` int(10) NOT NULL, "
					."`type` varchar(10) NOT NULL, "
					."`registrant_id` varchar(255) NOT NULL, "
					."`values` text NOT NULL, "
					."PRIMARY KEY  (`id`), "
					."UNIQUE KEY `contact` (`domain_id`,`type`) "
					.") ENGINE=MyISAM  DEFAULT CHARSET=".$charset." COLLATE=".$charset."_general_ci;";
			try {$stmt = $eppgrdb->query($query);}
			catch(PDOException $ex) {die('MySQL error eppgr#33!');}
			$query = "CREATE TABLE IF NOT EXISTS `tbleppgrwhoislog` ( "
					."`id` int(10) NOT NULL auto_increment, "
					."`date` datetime NOT NULL, "
					."`domain` text NOT NULL, "
					."`ip` text NOT NULL, "
					."PRIMARY KEY  (`id`) "
					.") ENGINE=MyISAM  DEFAULT CHARSET=".$charset." COLLATE=".$charset."_general_ci;";
			try {$stmt = $eppgrdb->query($query);}
			catch(PDOException $ex) {die('MySQL error eppgr#34!');}
			$fields = array('username', 'password', 'url', 'contactid', 'CronPassword', 'RenewDNames', 'ClientPrefix',
					'AllowDeleteDomain', 'AllowDeleteHomographs', 'DisplayWhoisInfo', 'DeleteAlienContacts',
					'EarlyExpiration', 'CheckForExpiredTransfers', 'ExtendExpiration', 'charset', 'CopyAdminToTech',
					'CopyAdminToBill', 'WHOISExtraDomains', 'WHOISOnly', 'DomainCheck', 'IDProtectAll', 'Discounts',
					'CreditEmails', 'CreditEmailsAfter', 'CreditsAfterCreateDomain', 'CreditsAfterRenewDomain',
					'eppgrinline', 'eppgrdebug', 'PlainWHOIS', 'PlainDomainCheck', 'Testing', 'TestUsername',
					'TestPassword', 'TestEPPHost', 'TestPlainWHOIS', 'TestPlainDomainCheck', 'CheckShortNames',
					'NoShortName', 'SystemModuleDebugLog', 'LogPersonalData', 'LogPassword', 'LogEmails', 'PostLogs');
			foreach ($fields as $f) {
				$q .= "`$f` varchar(255) NOT NULL, ";
			}
			$query = "CREATE TABLE IF NOT EXISTS `tbleppgrconfig` ( "
					."`id` int(10) NOT NULL auto_increment, $q"
					."PRIMARY KEY  (`id`) "
					.") ENGINE=MyISAM  DEFAULT CHARSET=".$charset." COLLATE=".$charset."_general_ci;";
			try {$stmt = $eppgrdb->query($query);}
			catch(PDOException $ex) {die('MySQL error eppgr#35!');}
			$query = "SELECT * FROM tbleppgrconfig WHERE id = 1";
			try {$stmt = $eppgrdb->query($query);}
			catch(PDOException $ex) {die('MySQL error eppgr#36!');}
			$dbconfig = $stmt->fetch(PDO::FETCH_ASSOC);
			$updateconfig = false;
			$fff = array();
			$dbl = array();
			for ($i = 0; $i < count($fields); $i++) {
				$z->AESEncode($params[$fields[$i]]);
				if ($fields[$i] == 'CronPassword') {
					$z->cipher = $params[$fields[$i]];
				}
				$fff[] = $z->cipher;
				$dbl[] = $fields[$i] . " = '" . $z->cipher . "'";
				if ($z->cipher != $dbconfig[$fields[$i]]) {
					$updateconfig = true;
				}
			}
			if (eppgr_isChecked($params, 'Testing')) {
				$params['url'] = $params['TestEPPHost'];
				$params['username'] = $params['TestUsername'] ? $params['TestUsername'] : $params['username'];
				$params['password'] = $params['TestPassword'] ? $params['TestPassword'] : $params['password'];
			}
			if ($updateconfig) {
				$query = "INSERT INTO `tbleppgrconfig` (`id`, `".implode('`, `', $fields)."`) "
						."VALUES (1, '".implode("', '", $fff)."') "
						."ON DUPLICATE KEY UPDATE ".implode(', ', $dbl);
				try {$stmt = $eppgrdb->query($query);}
				catch(PDOException $ex) {die('MySQL error eppgr#37!');}
			}
		}
	}
	if (!array_key_exists('charset', $params)) {
		$query = "SELECT value FROM `tblconfiguration` WHERE `setting` = 'Charset'";
		try {$stmt = $eppgrdb->query($query);}
		catch(PDOException $ex) {die('MySQL error eppgr#38!');}
		$chr = $stmt->fetch(PDO::FETCH_ASSOC);
		$params['charset'] = $chr['value'];
	}
}
