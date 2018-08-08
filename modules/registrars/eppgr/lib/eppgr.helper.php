<?php
/*
 *  File: lib/eppgr.helper.php
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

function parseCreateContactData(&$params, &$data, &$eppobj) {
	eppgr_getDisclose($params);
	if (array_key_exists('voice', $data) and $data['voice']) fixPhoneNumber($params, $data['voice'], true);
	if (array_key_exists('fax', $data) and $data['fax']) fixPhoneNumber($params, $data['fax']);
	$eppobj->data = array(
		'id'	=>	$data['id'],						//Obligatory
		'loc'	=>	array(								//Obligatory
			'name'	=>	$data['loc']['name'],			//Obligatory
			'org'	=>	$data['loc']['org'],			//Optional
			'str1'	=>	$data['loc']['str1'],			//Obligatory
			'str2'	=>	$data['loc']['str2'],			//Optional
			'str3'	=>	$data['loc']['str3'],			//Optional
			'city'	=>	$data['loc']['city'],			//Obligatory
			'sp'	=>	$data['loc']['sp'],				//Obligatory
			'pc'	=>	$data['loc']['pc'],				//Obligatory
			'cc'	=>	$data['loc']['cc'],				//Obligatory
		),
		'voice'		=>	$data['voice'],					//Obligatory
		'fax'		=>	$data['fax'],					//Optional
		'email'		=>	$data['email'],					//Obligatory
		'pw'		=>	$data['pw'],					//Obligatory
		'disclose'	=>	$params['disclose'],			//Optional
	);
}

function parseUpdateContactData(&$params, &$data, &$eppobj) {
	eppgr_getDisclose($params);
	if (array_key_exists('voice', $data) and $data['voice']) fixPhoneNumber($params, $data['voice'], true);
	if (array_key_exists('fax', $data) and $data['fax']) fixPhoneNumber($params, $data['fax']);
	$eppobj->data = array(
		'id'	=>	$data['id'],						//Obligatory
		'loc'	=>	array(								//Obligatory
			'name'	=>	$data['loc']['name'],			//Obligatory
			'str1'	=>	$data['loc']['str1'],			//Obligatory
			'str2'	=>	$data['loc']['str2'],			//Optional
			'str3'	=>	$data['loc']['str3'],			//Optional
			'city'	=>	$data['loc']['city'],			//Obligatory
			'sp'	=>	$data['loc']['sp'],				//Obligatory
			'pc'	=>	$data['loc']['pc'],				//Obligatory
			'cc'	=>	$data['loc']['cc'],				//Obligatory
		),
		'voice'		=>	$data['voice'],					//Obligatory
		'fax'		=>	$data['fax'],					//Optional
		'email'		=>	$data['email'],					//Obligatory
		'pw'		=>	$data['pw'],					//Obligatory
		'disclose'	=>	$params['disclose'],			//Optional
	);
}

function parseInfoContactData(&$params, &$data, &$eppobj) {
	$eppobj->data = array(
		'id'	=>	$data['id'],						//Obligatory
	);
}

function parseCheckContactData(&$params, &$data, &$eppobj) {
	$eppobj->data = array(
		'contact_ids'	=>	$data,						//Obligatory
	);
}

function parseCheckDomainData(&$params, &$data, &$eppobj) {
	$eppobj->data = array(
		'domain_ids'	=>	$data,						//Obligatory
	);
}

function parseCreateDomainData(&$params, &$data, &$eppobj) {
	$eppobj->data = array(
		'name'			=>	$data['name'],			//Obligatory
		'ns'			=>	$data['ns'],			//Optional
		'registrant'	=>	$data['registrant'],	//Obligatory for domain, Optional for dname
		'contact'		=>	$data['contact'],		//Optional
		'extension'		=>	array(					//Optional
			'reject'	=>	$data['reject'],		//Optional
			'comment'	=>	$data['comment'],		//Optional
			'use'		=>	$data['use'],			//Optional -- Obligatory for .com.gr, .net.gr, .org.gr, .edu.gr, .gov.gr
			'record'	=>	$data['record'],		//Optional: Optional values: domain || dname
		),
		'pw'			=>	$data['pw'],			//Obligatory
		'regperiod'		=>	$params['regperiod'],	//Obligatory for domain, Optional for dname
	);
}

function parseRnewDomainData(&$params, &$data, &$eppobj) {
	$name = $params['sld'].'.'.$params['tld'];
	$eppobj->data = array(
		'name'			=>	$name,						//Obligatory
		'curExpDate'	=>	$data['curExpDate'],		//Obligatory Form: YYYY-MM-DD
		'regperiod'		=>	$params['regperiod'],		//Obligatory
		'pw'			=>	$params['transfersecret'],	//Obligatory with transfer
		'newpw'			=>	$params['eppgrnewpw'],		//Obligatory with transfer
		'registrantid'	=>	$data['id'],				//Obligatory with transfer
	);
}

function parseUpdateDomainData(&$params, &$data, &$eppobj) {
	$name = $params['sld'].'.'.$params['tld'];
	$add = array(
		'ns'		=>	$data['ns']['add'],
		'contact'	=>	array(
			'admin'		=>	$data['contact']['add']['admin'],
			'tech'		=>	$data['contact']['add']['tech'],
			'billing'	=>	$data['contact']['add']['billing'],
		),
	);
	$rem = array(
		'ns'		=>	$data['ns']['rem'],
		'contact'	=>	array(
			'admin'		=>	$data['contact']['rem']['admin'],
			'tech'		=>	$data['contact']['rem']['tech'],
			'billing'	=>	$data['contact']['rem']['billing'],
		),
	);
	$eppobj->data = array(
		'name'			=>	$name,					//Obligatory
		'add'			=>	$add,					//Optional but one of add, rem, registrant/pw is obligatory
		'rem'			=>	$rem,					//Optional but one of add, rem, registrant/pw is obligatory
		'registrant'	=>	$data['registrant'],	//Optional but one of add, rem, registrant/pw is obligatory
		'pw'			=>	$data['pw'],			//Optional but one of add, rem, registrant/pw is obligatory
		'op'			=>	$data['op'],			//Optional but obligatory with registrant -- Optional values: ownerChange || ownerNameChange
	);
}

function parseTransDomainData(&$params, &$data, &$eppobj) {
	$name = $params['sld'].'.'.$params['tld'];
	$eppobj->data = array(
		'name'			=>	$name,						//Obligatory
		'pw'			=>	$params['transfersecret'],	//Obligatory
		'newpw'			=>	$params['eppgrnewpw'],		//Obligatory
		'registrantid'	=>	$data['id'],				//Obligatory
	);
}

function parseInfoDomainData(&$params, &$data, &$eppobj) {
	$name = $params['sld'].'.'.$params['tld'];
	$eppobj->data = array(
		'name'	=>	$name,						//Obligatory
		'pw'	=>	$params['transfersecret'],	//Optional
	);
}

function parseCreateHostData(&$params, &$data, &$eppobj) {
//	eppgr_punyHost($data['name']);
	$eppobj->data = array(
		'name'			=>	$data['name'],			//Obligatory
		'addr'			=>	array(					//Optional
			'v4'	=>	$data['v4'],				//Optional: Optional
			'v6'	=>	$data['v6'],				//Optional: Optional
		),
	);
}

function parseUpdateHostData(&$params, &$data, &$eppobj) {
//	eppgr_punyHost($data['name']);
	if (is_array($data) and array_key_exists('addv4', $data) and is_array($data['addv4']) and count($data['addv4'])) {
		for ($i = 0; $i < count($data['addv4']); $i++) fixIPV4($data['addv4'][$i]);
	}
	if (is_array($data) and array_key_exists('remv4', $data) and is_array($data['remv4']) and count($data['remv4'])) {
		for ($i = 0; $i < count($data['remv4']); $i++) fixIPV4($data['remv4'][$i]);
	}
	if (is_array($data) and array_key_exists('addv6', $data) and is_array($data['addv6']) and count($data['addv6'])) {
		for ($i = 0; $i < count($data['addv6']); $i++) fixIPV6($data['addv6'][$i]);
	}
	if (is_array($data) and array_key_exists('remv6', $data) and is_array($data['remv6']) and count($data['remv6'])) {
		for ($i = 0; $i < count($data['remv6']); $i++) fixIPV6($data['remv6'][$i]);
	}
	$eppobj->data = array(
		'name'		=>	$data['name'],				//Obligatory
		'addradd'	=>	array(						//Optional
			'v4'	=>	$data['addv4'],				//Optional: Optional
			'v6'	=>	$data['addv6'],				//Optional: Optional
		),
		'addrrem'	=>	array(						//Optional
			'v4'	=>	$data['remv4'],				//Optional: Optional
			'v6'	=>	$data['remv6'],				//Optional: Optional
		),
	);
}

function parseInfoHostData(&$params, &$data, &$eppobj) {
//	eppgr_punyHost($data['name']);
	$name = $data['name'];
	$eppobj->data = array(
		'name'	=>	$name,							//Obligatory
	);
}

function parseCheckHostData(&$params, &$data, &$eppobj) {
	if (is_array($data['name'])) $ids = $data['name'];
	else $ids = array($data['name']);
//	for ($i = 0; $i < count($ids); $i++) {
//		eppgr_punyHost($ids[$i]);
//	}
	$eppobj->data = array(
		'host_ids'	=>	$ids,						//Obligatory
	);
}

function parseDeleteHostData(&$params, &$data, &$eppobj) {
//	eppgr_punyHost($data['name']);
	$eppobj->data = array(
		'name'	=>	$data['name'],					//Obligatory
	);
}

function parseDeleteContactData(&$params, &$data, &$eppobj) {
	$eppobj->data = array(
		'id'	=>	$data['id'],					//Obligatory
	);
}

function parseDeleteDomainData(&$params, &$data, &$eppobj) {
	$eppobj->data = array(
		'name'		=>	$data['name'],				//Obligatory
		'pw'		=>	$data['pw'],				//Obligatory to delete
		'op'		=>	$data['op'],				//Obligatory
		'protocol'	=>	$data['protocol'],			//Obligatory to recall
	);
}

function parseInfoAccountData(&$params, &$data, &$eppobj) {
	$eppobj->data = array();
}

function fixIPV4(&$ip) {
	$ip = preg_replace('/[^0-9\.]/', '', $ip);
}

function fixIPV6(&$ip) {
	$ip = preg_replace('/[^0-9A-Fa-f:\.]/', '', $ip);
}

function fixPhoneNumber(&$params, &$v, $error=false) {
	$v = preg_replace('/[^0-9\+\.]/', '', $v);
	$v = preg_replace('/^(.+?)\+/', '', $v);
	$v = preg_replace('/(^\.+)|(\.+$)/', '', $v);
	if (!preg_match('/^\+[0-9]{1,3}\.[0-9]{1,14}$/', $v)) {
		$v = preg_replace('/[\.\+]/', '', $v);
		$v = preg_replace('/^0+/', '', $v);
		if (strlen($v) > 10) $prefix = substr($v, 0, strlen($v) - 10);
		else $prefix = '30';
		$v = '+'. $prefix . '.' . substr($v, -10);
	}
	if ($error and !preg_match('/^\+[0-9]{1,3}\.[0-9]{1,14}$/', $v)) {
		$params['error'][] = 'Phone numbers should be in the following form: +30.2310392993';
	}
}

function eppgr_getDisclose(&$params) {
	if (!isset($params['disclose'])) {
		if (isset($params['idprotection']) and $params['idprotection']) {
			$params['disclose'] = $params['idprotection'] ? 0 : 1;
		}
		else {
			$params['disclose'] = ($params['IDProtectAll'] == 'on' or $params['IDProtectAll'] == 1) ? 0 : 1;
		}
	}
}

?>