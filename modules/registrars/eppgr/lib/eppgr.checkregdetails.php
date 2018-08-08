<?php
/*
 *  File: lib/eppgr.checkregdetails.php
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

function eppgrCheckRegDetails($vars) {
	global $errormessage, $eppgrCancelOrderVar, $eppgrcart, $smarty;
	$eppgrCancelOrderVar = 0;

	if (!array_key_exists('domains', $vars) or !is_array($vars['domains']) or !count($vars['domains'])) {
		return;
	}

	$nongrdomains = array();
	foreach ($vars["domains"] as $domain) {
		if (!preg_match('/\.(gr|ελ)$/', $domain["domain"])) {
			$nongrdomains[] = $domain["domain"]; 
		} 
	}

	if (!count($nongrdomains)) {
		return;
	}

	if (preg_match('/^\d+$/', $vars['contact'])) {
    	$result = select_query("tblcontacts","*",array("id"=>$vars["contact"]));
    	$data = mysql_fetch_array($result);
	}
	elseif (preg_match('/^\d+$/', $_SESSION['uid'])) {
	    $result = select_query("tblclients","firstname,lastname,companyname,email,address1,address2,city,state,postcode,country,phonenumber",array("id"=>$_SESSION['uid']));
    	$data = mysql_fetch_array($result);
	}
	else {
		return;
	}

	$nonascii = false;
	$regex = '/[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2}/';
	foreach ($data as $k => $v) {
		if (preg_match($regex, $v)) {
			$nonascii = true;
			break;
		}
	}

	if ($nonascii) {
		$eppgrCancelOrderVar = 1;
		if ($smarty->_tpl_vars['language'] == 'Greek') {
			$errormessage .= "Τα στοιχεία που δηλώσατε δεν μπορούν να αποσταλούν"
			. " επειδή περιέχουν μη αποδεκτούς χαρακτήρες. Παρακαλούμε, <a href=\"cart.php?a=checkout\">δημιουργήστε"
			. " μια επαφή με λατινικούς χαρακτήρες</a> και χρησιμοποιήστε την για να";
			if (count($nongrdomains) > 1) {
				$errormessage .= " κατοχυρώσετε τα ονόματα χώρου ".implode(', ', $nongrdomains).".";
			}
			else {
				$errormessage .= " κατοχυρώσετε το όνομα χώρου ".$nongrdomains[0].".";
			}
			$errormessage .= "<br /><br />Εάν επιθυμείτε να κατοχυρώσετε ελληνικά ονόματα χώρου,"
			. " θα ήταν προτιμότερο να το κάνετε με ξεχωριστή παραγγελία προκειμένου να σας δοθεί"
			. " η δυνατότητα να αποστείλετε τα στοιχεία σας με ελληνικούς χαρακτήρες.";
		}
		else {
			$errormessage .= "Your client details are incompatible with your"
			. " registration application because they contain non-ASCII characters."
			. " Please, <a href=\"cart.php?a=checkout\">create a new contact using ASCII only characters</a> and use it";
			if (count($nongrdomains) > 1) {
				$errormessage .= " to register the following domain names: ".implode(', ', $nongrdomains).".";
			}
			else {
				$errormessage .= " to register the following domain name: ".$nongrdomains[0].".";
			}
			$errormessage .= "<br /><br />If you would like to register Greek domain names,"
			. " it would be preferable to do so submitting a different order so as to be able"
			. " to send your contact details using Greek characters.";
		}
		$eppgrcart = $_SESSION['cart'];
	}
}

function eppgrCancelOrder($vars) {
	global $errormessage, $eppgrCancelOrderVar, $eppgrcart;

	if ($eppgrCancelOrderVar and preg_match('/^\d+$/', $_SESSION['orderdetails']['OrderID'])) {
		if (preg_match('/^\d+$/', $_SESSION['uid'])) {
	    	$result = select_query("tblorders","userid",array("id"=>$_SESSION['orderdetails']['OrderID']));
    		$data = mysql_fetch_array($result);
    		if ($data['userid'] != $_SESSION['uid']) {
    			return;
			}
    	}
    	else {
    		return;
		}
		$http = 'http';
		if (array_key_exists('HTTPS', $_SERVER) and $_SERVER['HTTPS'] == 'on') {
			$http = 'https';
		}
		$mainsite = $http.'://'.$_SERVER['HTTP_HOST'].preg_replace('/\w+\.php$/', '', $_SERVER['PHP_SELF']);
		$url = $mainsite.'includes/api.php';
	    $result = select_query("tbladmins","username,password");
    	$data = mysql_fetch_array($result);
		$postfields["username"] = $data['username'];
		$postfields["password"] = $data['password'];
		$postfields["action"] = "cancelorder";
		$postfields["orderid"] = $_SESSION['orderdetails']['OrderID'];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 100);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		$data = curl_exec($ch);
		curl_close($ch);
		$data = explode(";",$data);
		foreach ($data AS $temp) {
			$temp = explode("=",$temp);
			$results[$temp[0]] = $temp[1];
		}

		$_SESSION['eppgrerrormessage'] = '';
		if ($results["result"]=="success") {
			$_SESSION['eppgrerrormessage'] .= $errormessage;
		} else {
			$_SESSION['eppgrerrormessage'] .= $results["message"].$errormessage;
		}
		$_SESSION['cart'] = $eppgrcart;
    	$result = select_query("tblconfiguration","value",array("setting"=>"AutoRedirectoInvoice"));
   		$data = mysql_fetch_array($result);
		$_SESSION['eppgrautoredirecttoinvoice'] = $data['value'];
		update_query("tblconfiguration",array("value"=>""),array("setting"=>"AutoRedirectoInvoice"));
	}
}

function eppgrShowError($vars) {
	global $smarty;
	if (array_key_exists('eppgrautoredirecttoinvoice', $_SESSION)) {
		update_query("tblconfiguration",array("value"=>$_SESSION['eppgrautoredirecttoinvoice']),array("setting"=>"AutoRedirectoInvoice"));
		unset($_SESSION['eppgrautoredirecttoinvoice']);
	}
	if (array_key_exists('eppgrerrormessage', $_SESSION)) {
		$smarty->_tpl_vars['LANG']['ordercompletebutnotpaid'] = $_SESSION['eppgrerrormessage'];
		if ($smarty->_tpl_vars['language'] == 'Greek') {
			$smarty->_tpl_vars['LANG']['orderconfirmation'] = "Ακύρωση παραγγελίας";
			$smarty->_tpl_vars['LANG']['orderreceived'] = "Για τη δική σας προστασία η παραγγελία σας ακυρώθηκε. Παρακαλούμε, προσπαθήστε πάλι.";
			$smarty->_tpl_vars['LANG']['ordernumberis'] = "Ο αριθμός της παραγγελίας που ακυρώθηκε είναι:";
		}
		else {
			$smarty->_tpl_vars['LANG']['orderconfirmation'] = "Order Cancellation";
			$smarty->_tpl_vars['LANG']['orderreceived'] = "For your own safety, your order has been cancelled. Please, try again.";
			$smarty->_tpl_vars['LANG']['ordernumberis'] = "Your Cancelled Order Number is:";
		}
		unset($_SESSION['eppgrerrormessage']);
	}
}

?>