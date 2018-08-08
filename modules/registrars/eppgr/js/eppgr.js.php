<?php
/*
 *  File: js/eppgr.js.php
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
catch(PDOException $ex) {die('MySQL error #1e01!');}
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

$query = "SELECT setting, value FROM tblconfiguration WHERE setting = 'Language' OR setting = 'Charset' ORDER BY setting ASC";
try {$stmt = $eppgrdb->query($query);}
catch(PDOException $ex) {die('MySQL error #1e02!');}
$settings = $stmt->fetch(PDO::FETCH_ASSOC);
$charset = $settings['value'];
$settings = $stmt->fetch(PDO::FETCH_ASSOC);
$deflang = $settings['value'];

$langcor = array('Greek' => 'el', 'English' => 'en');
$charcor = array('utf-8' => 'utf8', 'iso-8859-7' => 'iso', 'windows-1253' => 'iso');

$langfile = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $langcor[$deflang].'_'.$charcor[$charset].'.php';
$enfile = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'en_'.$charcor[$charset].'.php';
if (is_file($langfile) and is_readable($langfile)) require_once $langfile;

mt_srand();
$remaddr = explode('.', $_SERVER['REMOTE_ADDR']);
for ($i = 0; $i < count($remaddr); $i++) {
  $remaddr[$i] = chr(mt_rand(32,126)).chr(mt_rand(32,126)).chr(mt_rand(32,126)).sprintf('%03s', $remaddr[$i]);
}
$extra = implode('', $remaddr).chr(mt_rand(32,126)).chr(mt_rand(32,126)).chr(mt_rand(32,126));
for ($i = 0; $i < strlen($_COOKIE['PHPSESSID']); $i += 2) {
  $extra .= chr(mt_rand(32,126)).chr(mt_rand(32,126)).substr($_COOKIE['PHPSESSID'], $i, 2);
}
$extra .= chr(mt_rand(32,126)).chr(mt_rand(32,126));
$z->AESEncode($extra);
echo 'var deflang = "'.$deflang.'";'."\n";
echo 'var extra = "'.$z->cipher.'";'."\n";
echo 'var whois = "'.'http' . (eppgr_isSSL() ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . str_replace('js.', 'whois.', str_replace('js/', 'lib/', $_SERVER['SCRIPT_NAME'])).'";'."\n";
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
var cookie = document.cookie;
var ns;
var url;
var post;
var form;
var req = new Array();
var interval = 100;
var eppgr_div = 'eppgr_div';
var refreshIntervalId;
var waitdot = ' - ';
var waitmsg = '<?php echo $_LANG['eppgrpleasewait']; ?>';
var hide = -1;
var opacspeed = 10;

function whenCompletedMy() {
	clearInterval(refreshIntervalId);
	var regex = new XRegExp('<div id="' + eppgr_div + '.+?<\/div>', "smig");
	var match = regex.exec(ns.response);
	if (match == null) {
		var newdiv = ns.response;
	}
	else {
		var newdiv = match[0];
	}
	createNewDiv(newdiv);
}

function searchDomain() {
	var codeel = getElementTag('input', 'code');
	if (codeel) post['code'] = codeel.value;
	createNewDiv();
	refreshIntervalId = setInterval("showDots()", interval);
	for (var key in post) {
		if (is_array(post[key])) {
			for (var i = 0; i < post[key].length; i++) {
				ns.setVar(key+'['+i+']', post[key][i]);
			}
		}
		else {
			ns.setVar(key, post[key]);
		}
	}
	ns.setVar('extra', extra);
    ns.requestFile = url;
    ns.method = 'POST';
	ns.onCompletion = whenCompletedMy;
    ns.runAJAX();
}

function createNewDiv(inner) {
	if (!inner) inner = '';
	var old_eppgr_el = document.getElementById(eppgr_div);
	if (old_eppgr_el) old_eppgr_el.parentNode.removeChild(old_eppgr_el);
	var el = document.createElement('div');
	el.id = eppgr_div;
	if (!inner) {
		el.style.fontWeight = 'bold';
		el.style.fontSize = 'large';
	}
	else {
		var regex = new XRegExp('<div id="' + eppgr_div + '">(.+?)<\/div>', "smig");
		var match = regex.exec(inner);
		if (is_array(match) && match.length > 1) inner = match[1];
	}
	el.innerHTML = inner;
	insertAfter(form, el);
}

function showDots() {
	var el = document.getElementById(eppgr_div);
	if (!el.innerHTML) {
		el.innerHTML = waitmsg;
		el.style.textAlign = 'center';
		el.style.opacity = 1;
		el.style.filter = 'alpha(opacity=100)';
	}
	el.style.opacity = (parseFloat(el.style.opacity) * 100 + parseInt(opacspeed) * parseInt(hide)) / 100;
	el.style.filter = 'alpha(opacity='+(el.style.opacity*100)+')';
	if ((parseFloat(el.style.opacity) * 100 + parseInt(opacspeed) * parseInt(hide)) / 100 < 0) {
		el.style.opacity = 0;
		el.style.filter = 'alpha(opacity=0)';
		hide = 1;
	}
	else if ((parseFloat(el.style.opacity) * 100 + parseInt(opacspeed) * parseInt(hide)) / 100 > 1) {
		el.style.opacity = 1;
		el.style.filter = 'alpha(opacity=100)';
		hide = -1;
	}
}

function bulksearch(button) {
	ns = new sack();
	checkForm(button);
	post = {};
	post['bulkdomains'] = getElementTag('textarea', 'bulkdomains').value.replace(/[^\r\n\w\-\.\x80-\xFF\u0374-\u03FB]/g, '');
	searchDomain();
}

function bulktransfer(button) {
	bulksearch(button);
}

function domainsearch(button) {
	ns = new sack();
	checkForm(button);
	post = {};
	post['domain'] = getElementTag('input', 'domain').value.replace(/[^\w\-\.\x80-\xFF\u0374-\u03FB]/g, '');
	if (!post['domain']) {
		var domainerrornodomain = '<?php echo $_LANG["domainerrornodomain"]; ?>';
		if (!domainerrornodomain) domainerrornodomain = 'Domain name missing!';
		alert('<?php echo $_LANG["domainerrornodomain"]; ?>');
		return false;
	}
	post['tlds'] = new Array();
	var tlds = getTickedElements('tlds[]');
	if (!tlds.length) {
		if (post['domain'].match(/\.ελ$/)) {
			post['tlds'].push('.ελ');
			post['domain'] = post['domain'].replace(/\.ελ$/, '');
		}
		else {
			post['tlds'].push('.gr');
			post['domain'] = post['domain'].replace(/\.gr$/, '');
		}
	}
	else {
		for (var i = 0; i < tlds.length; i++) {
			post['tlds'][i] = tlds[i];
		}
	}
	searchDomain();
}

function domainsearchcart(button) {
	ns = new sack();
	checkForm(button);
	post = {};
	post['sld'] = getElementTag('input', 'sld').value.replace(/[^\w\-\.\x80-\xFF\u0374-\u03FB]/g, '');
	post['tld'] = getSelectedItemValue('tld').replace(/[^\w\-\.\x80-\xFF\u0374-\u03FB]/g, '');
	searchDomain();
}

function getTickedElements(name) {
	var ticks = document.getElementsByTagName('input');
	var ret = new Array();
	for (var i = 0; i < ticks.length; i++) {
		if (typeof(ticks[i].name) != 'undefined' && ticks[i].name == name && ticks[i].checked) ret.push(ticks[i].value.replace(/[^\w\-\.\x80-\xFF]/g, ''));
	}
	return ret;
}

function getElementTag(tag, name) {
	var tags = document.getElementsByTagName(tag);
	for (var i = 0; i < tags.length; i++) {
		if (typeof(tags[i].name) != 'undefined' && tags[i].name == name) return tags[i];
	}
	return 0;
}

function getCharset() {
	var tags = document.getElementsByTagName('meta');
	for (var i = 0; i < tags.length; i++) {
		if (typeof(tags[i].content) != 'undefined') return tags[i].content.match(/charset=(.+)($|;)/)[1].toLowerCase() == 'utf-8' ? 1 : 0;
	}
	return 0;
}

function getSelectedItemValue(name) {
	var select = getElementTag('select', name);
	if (select) return select[select.selectedIndex].value;
	else return 0;
}

function insertAfter(referenceNode, newNode) {
    referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
}

function checkForm (button) {
	if (typeof(form) == 'undefined') {
		form = button.form;

		var script = form.action.match(/\/(\w+)\.php($|\?)/);
		if (is_array(script) && script.length > 1 && script[1] == 'cart') url = whois + '?goto=' + script[1];
		else url = whois;
		if (form.action.match(/\?(.+)$/) && form.action.match(/\?(.+)$/)[1]) req.push(form.action.match(/\?(.+)$/)[1]);
		var langsel = getSelectedItemValue('language')
		if (langsel) req.push('language=' + langsel);
		else req.push('language=' + deflang);
		req.push('utf8=' + getCharset());
		if (url.match(/\?/)) var link = '&';
		else var link = '?';
		url += link + req.join('&');
	}
}

function submitViaEnter(evt) {
    evt = (evt) ? evt : event;
    var target = (evt.target) ? evt.target : evt.srcElement;
    var form = target.form;
    var charCode = (evt.charCode) ? evt.charCode :
        ((evt.which) ? evt.which : evt.keyCode);
    if (charCode == 13) {
		var inputs = form.getElementsByTagName('input');
		for (var i = 0; i < inputs.length; i++) {
			if (inputs[i].type == 'button') {
				inputs[i].click();
			}
		}
		return false;
    }
    return true;
}

function is_array(input){
    return typeof(input)=='object'&&(input instanceof Array);
}

function eppgrFixCart(button) {
	var regex = new XRegExp('domain=.+', "smig");
	button.form.action = button.form.action.replace(regex, 'domain='+button.value);
	if (typeof(url) != 'undefined') {
		url = url.replace(regex, 'domain='+button.value);
	}
}