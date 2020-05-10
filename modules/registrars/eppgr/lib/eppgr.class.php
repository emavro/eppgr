<?php
/*
 *  File: lib/eppgr.class.php
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

class eppgr {
	protected $username		=	'';
	protected $password		=	'';
	protected $dataid		=	'';
	protected $language		=	'';
	protected $cltrid		=	'';
	protected $certfile		=	'';
	protected $url			=	'';
	protected $cookie		=	'';
	protected $cookiepath	=	'';
	protected $response		=	'';
	protected $responsedata	=	'';
	protected $xml			=	'';
	protected $error		=	'';
	public $data			=	array();
	protected $command		=	'';
	protected $eppgrdebug	=	false;
	protected $eppgrinline	=	false;
	protected $z			=	'';
	protected $myFile		=	'';
	public $debugdata		=	array();
	public $commtime		=	0;
	
	public function __construct($params, $data) {
		require_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'eppgr.aes.php');
		$this->setupEPP($params);
		$this->data = $data;
		if (!is_object($this->z)) {
			$this->z = eppgr_getAESObject($params);
			if (!is_object($this->z)) {
				return 0;
			}
		}
		$this->myFile = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'eppgr.debug.txt';
	}

	protected function set($var, $value) {
		$this->$var = $value;
	}

	public function setupEPP($params) {
		$cltrid = time().'_'.$_SERVER['REMOTE_ADDR'];
		foreach ($params as $k => $v) {
			$this->set($k, $v);
		}
		$this->set('cltrid', $cltrid);
	}

	public function getResponseData() {
		return $this->responsedata;
	}

	private function cipherLines() {
		$i = 0;
		$ret = '';
		while ($i < strlen($this->z->cipher)) {
			$ret .= substr($this->z->cipher, $i, 80) . "\n";
			$i += 80;
		}
		return $ret;
	}

	public function connect() {
		if ($this->eppgrdebug == 'on' or $this->eppgrdebug == 1) {
			$fh = fopen($this->myFile, 'a') or die("can't open file");
			ob_start();
		}
		$this->hello();
		if ($this->eppgrdebug == 'on' or $this->eppgrdebug == 1) { echo "\n\n".date("r", time())."\n".$this->xml; }
		$this->exchangeData();
		if ($this->eppgrdebug == 'on' or $this->eppgrdebug == 1) { echo "\n\n".date("r", time())."\n".$this->response; }

		if ($this->HelloOK()) {
			$this->login();
			if ($this->eppgrdebug == 'on' or $this->eppgrdebug == 1) {
				if ($this->LogPassword == 'on' or $this->LogPassword == 1) {
					echo "\n\n".date("r", time())."\n".$this->xml;
				}
				else {
					echo "\n\n".date("r", time())."\n".preg_replace('/(<pw><!\[CDATA\[).+?(\]\]><\/pw>)/', "$1"."XXXXXXXXX"."$2", $this->xml);
				}
			}
			$this->exchangeData();
			if ($this->eppgrdebug == 'on' or $this->eppgrdebug == 1) { echo "\n\n".date("r", time())."\n".$this->response; }
			$this->parseLoginResponseData();
		}
		if ($this->eppgrdebug == 'on' or $this->eppgrdebug == 1) {
			$content = ob_get_contents();
			$this->z->AESEncode($content);
			ob_end_clean();
			fwrite($fh, $this->cipherLines()."\n");
			fclose($fh);
		}
		return $this->noError();
	}

	public function disconnect() {
		if ($this->eppgrdebug == 'on' or $this->eppgrdebug == 1) {
			$fh = fopen($this->myFile, 'a') or die("can't open file");
			ob_start();
		}
		$this->responsedata = $this->response;
		$this->logout();
		if ($this->eppgrdebug == 'on' or $this->eppgrdebug == 1) { echo "\n\n".date("r", time())."\n".$this->xml; }
		$this->exchangeData();
		if ($this->eppgrdebug == 'on' or $this->eppgrdebug == 1) {
			echo "\n\n".date("r", time())."\n".$this->response;
			$content = ob_get_contents();
			$this->z->AESEncode($content);
			ob_end_clean();
			fwrite($fh, $this->cipherLines()."\n");
			fclose($fh);
		}
		if ($this->noError()) return true;
		else return false;
	}

	public function getEppgrInline() {
		return $this->eppgrinline;
	}

	public function executeCommand($command) {
		if ($this->eppgrdebug == 'on' or $this->eppgrdebug == 1) {
			$fh = fopen($this->myFile, 'a') or die("can't open file");
			ob_start();
		}
		$this->cleanArray($this->data);
		$this->$command();

		if ($this->eppgrinline == 'on' or $this->eppgrinline == 1) {
		    $started = $this->getMicroTime();
			$this->debugdata[] = "\n".$command.' started at '.date("r", time()).' ['.$started.']';
			$this->getPureData($this->data, $datasent, $command);
			$this->debugdata[] = 'Data sent:'."\n".$datasent;
		}

		if ($this->eppgrdebug == 'on' or $this->eppgrdebug == 1) {
			if ($this->LogPersonalData == 'on' or $this->LogPersonalData == 1) {
				echo "\n\n".date("r", time())."\n".$this->xml;
			}
			else {
				echo "\n\n".date("r", time())."\n";
				$xmlout = $this->xml;
				echo $xmlout;
			}
		}

		$this->exchangeData();

		if ($this->eppgrinline == 'on' or $this->eppgrinline == 1) {
		    $ended = $this->getMicroTime();
			$this->debugdata[] = $command.' ended at '.date("r", time()).' ['.$ended.']';
			$run = $ended - $started;
			$this->getPureResponse($this->response, $datareceived);
			$this->debugdata[] = 'Data received:'."\n".$datareceived."\n";
			$this->debugdata[] = $command.' ran for '.number_format($run, 2, '.', '').' secs'."\n\n";
		}

		if ($this->eppgrdebug == 'on' or $this->eppgrdebug == 1) {
			echo "\n\n".date("r", time())."\n".$this->response;
			$content = ob_get_contents();
			if (!($this->LogPersonalData == 'on' or $this->LogPersonalData == 1)) {
				$content = preg_replace('/(<(domain:(contact type=[^>]+|registrant))>)\w+_[^<]+(<\/domain:(contact|registrant)>)/', "$1"."XXXXXXXXX"."$4", $content);
				$content = preg_replace('/(<(domain:pw)>(<!\[CDATA\[)*)([^<]+?)((\]\]>)*<\/\2>)/', "$1"."XXXXXXXXX"."$5", $content);
				$content = preg_replace('/(<contact:('.join('|', $this->getPersonalFields()).')[^>]*>(<!\[CDATA\[)*)([^<]+?)((\]\]>)*<\/contact:\2>)/', "$1"."XXXXXXXXX"."$5", $content);
				$content = preg_replace('/([>"\'])'.$this->contactid.'_[^\1]+?\1/', "$1"."XXXXXXXXX"."$1", $content);
				$content = preg_replace('/(<(account:balance)\s*[^>]*>)[^>]*(<\/\2>)/', "$1"."XXXXXXXXX"."$3", $content);
			}
			$this->z->AESEncode($content);
			ob_end_clean();
			fwrite($fh, $this->cipherLines()."\n");
			fclose($fh);
		}
	}

	public function getMicroTime() {
	    list($usec, $sec) = explode(" ", microtime());
	    return ((float)$usec + (float)$sec);
	}

	public function getPureResponse(&$xml, &$ret) {
		$a = array();
		$error = $this->getError();
		if ($error) {
			$a[] = 'Error: '.$error;
		}
		preg_match('/<result code=["\'](\d+)["\']>/', $xml, $matches);
		$a[] = 'Result code ['.$matches[1].']';
		preg_match_all('/<msg\s*[^>]*>([^<]+)<\/msg>/', $xml, $matches);
		if (count($matches) > 1) {
			foreach ($matches[1] as $m) {
				if (trim($m)) {
					$a[] = trim($m);
				}
			}
		}
		preg_match_all('/:reason\s*[^>]*>([^<]+)</', $xml, $matches);
		if (count($matches) > 1) {
			foreach ($matches[1] as $m) {
				if (trim($m)) {
					$a[] = trim($m);
				}
			}
		}
		preg_match_all('/:comment\s*[^>]*>([^<]+)</', $xml, $matches);
		if (count($matches) > 1) {
			foreach ($matches[1] as $m) {
				if (trim($m)) {
					$a[] = trim($m);
				}
			}
		}
		$ret = implode("\n", $a);
	}

	public function getPersonalFields() {
		return array(
			'name',
			'org',
			'street',
			'str1',
			'str2',
			'str3',
			'city',
			'sp',
			'pc',
			'cc',
			'voice',
			'vx',
			'fax',
			'fx',
			'email',
			'pw',
			'newpw',
			'id',
			'registrantid',
			'registrant',
			'admin',
			'tech',
			'billing',
		);
	}
	
	public function getPureData(&$data, &$ret, $command) {
		if (is_array($data)) {
			foreach ($data as $k => $v) {
				if (is_array($v)) {
					$this->getPureData($v, $ret, $command);
				}
				else {
					$show = $v;
					if ($this->LogPersonalData != 'on' and $this->LogPersonalData != 1 and in_array($k, $this->getPersonalFields())) {
						if (!(preg_match('/(Domain|Host)/', $command) and ($k == 'name' | is_numeric($k)))) {
							$show = 'XXXXXXXXX';
						}
					}
					$ret .= $k.': '.$show."\n";
				}
			}
		}
		else {
			$ret .= $data."\n";
		}
		return $ret;
	}

	protected function HelloOK() {
		if (!$this->error and preg_match('/<svID>\.gr .+? ccTLD EPP Service<\/svID>/', $this->response)) return true;
		else return false;
	}

	public function noError() {
		if (!$this->error and preg_match('/<result code="1000">/', $this->response)) return true;
		else return false;
	}

	public function getError() {
		if ($this->error) return $this->error;
		else {
			$ok = array('1000', '1001', '1300', '1301', '1500');
			preg_match('/<result code=["\'](\d+)["\']>/', $this->response, $matches);
			if (count($matches) > 1 and !in_array($matches[1], $ok)) {
				preg_match('/<msg\s*[^>]*>([^<]+)<\/msg>/', $this->response, $ret);
				$err = $ret[1];
				preg_match_all('/<\w+:comment\s*[^>]*>([^<]+)<\/\w+:comment>/', $this->response, $ret);
				return $err.((is_array($ret[1]) and count($ret[1])) ? ' ['.join(' | ', $ret[1]).']' : '');
			}
			else return false;
		}
	}

	protected function hello() {
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<hello/>
		</epp>

XML;
	}

	protected function login() {
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
				<login>
					<clID>$this->username</clID>
					<pw><![CDATA[$this->password]]></pw>
					<options>
						<version>1.0</version>
						<lang>$this->language</lang>
					</options>
					<svcs>
						<objURI>urn:ietf:params:xml:ns:host-1.0</objURI>
						<objURI>urn:ietf:params:xml:ns:contact-1.0</objURI>
						<objURI>urn:ietf:params:xml:ns:domain-1.0</objURI>
					</svcs>
				</login>
				<clTRID>$this->cltrid</clTRID>
			</command>
		</epp>

XML;
	}

	protected function logout() {
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
				<logout/>
				<clTRID>$this->cltrid</clTRID>
			</command>
		</epp>

XML;
	}

	public function updateContact() {
		$this->command = 'update';
		$this->createContact();
		$this->command = '';
	}

	public function checkContact() {
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
				<check>
					<contact:check xmlns:contact="urn:ietf:params:xml:ns:contact-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd">

XML;
		foreach ($this->data['contact_ids'] as $c) {
			$this->xml .= '						<contact:id>'.$c.'</contact:id>'."\n";
		}
		$this->xml .= <<<XML
					</contact:check>
				</check>
				<clTRID>$this->cltrid</clTRID>
			</command>
		</epp>

XML;
	}

	public function createContact() {
		if ($this->command == 'update') {
			$command = 'update';
		}
		else {
			$command = 'create';
		}
		if (is_array($this->data) and array_key_exists('vx', $this->data))
			$vx = ' x="'.$this->data['vx'].'"';
		if (is_array($this->data) and array_key_exists('fx', $this->data))
			$fx = ' x='.$this->data['vx'].'"';
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
				<$command>
					<contact:$command xmlns:contact="urn:ietf:params:xml:ns:contact-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd">

XML;
		$this->xml .= '						<contact:id>'.$this->data['id'].'</contact:id>'."\n";
		if ($this->command == 'update')
			$this->xml .= '						<contact:chg>'."\n";
		$this->xml .= '						<contact:postalInfo type="loc">'."\n";
		if ($this->command != 'update' and is_array($this->data['loc']) and array_key_exists('name', $this->data['loc']))
			$this->xml .= '							<contact:name>'.$this->data['loc']['name'].'</contact:name>'."\n";
		if ($this->command != 'update' and is_array($this->data['loc']) and array_key_exists('org', $this->data['loc']))
			$this->xml .= '							<contact:org>'.$this->data['loc']['org'].'</contact:org>'."\n";
		$this->xml .= '							<contact:addr>'."\n";
		if (is_array($this->data['loc']) and (array_key_exists('str1', $this->data['loc']) or $this->command == 'update'))
			$this->xml .= '								<contact:street>'.$this->data['loc']['str1'].'</contact:street>'."\n";
		if (is_array($this->data['loc']) and (array_key_exists('str2', $this->data['loc']) or $this->command == 'update'))
			$this->xml .= '								<contact:street>'.$this->data['loc']['str2'].'</contact:street>'."\n";
		if (is_array($this->data['loc']) and (array_key_exists('str3', $this->data['loc']) or $this->command == 'update'))
			$this->xml .= '								<contact:street>'.$this->data['loc']['str3'].'</contact:street>'."\n";
		$this->xml .= '								<contact:city>'.$this->data['loc']['city'].'</contact:city>
								<contact:sp>'.$this->data['loc']['sp'].'</contact:sp>
								<contact:pc>'.$this->data['loc']['pc'].'</contact:pc>
								<contact:cc>'.$this->data['loc']['cc'].'</contact:cc>
							</contact:addr>
						</contact:postalInfo>'."\n";
		if (is_array($this->data['int']) and count($this->data['int'])) {
			$this->xml .= '						<contact:postalInfo type="int">'."\n";
		if ($this->command != 'update' and is_array($this->data['int']) and array_key_exists('name', $this->data['int']))
			$this->xml .= '							<contact:name>'.$this->data['int']['name'].'</contact:name>'."\n";
		if ($this->command != 'update' and array_key_exists('org', $this->data['int']))
				$this->xml .= '							<contact:org>'.$this->data['int']['org'].'</contact:org>'."\n";
			$this->xml .= '							<contact:addr>'."\n";
			if (array_key_exists('str1', $this->data['int']) or $this->command == 'update')
				$this->xml .= '								<contact:street>'.$this->data['int']['str1'].'</contact:street>'."\n";
			if (array_key_exists('str2', $this->data['int']) or $this->command == 'update')
				$this->xml .= '								<contact:street>'.$this->data['int']['str2'].'</contact:street>'."\n";
			if (array_key_exists('str3', $this->data['int']) or $this->command == 'update')
				$this->xml .= '								<contact:street>'.$this->data['int']['str3'].'</contact:street>'."\n";
			$this->xml .= '								<contact:city>'.$this->data['int']['city'].'</contact:city>
								<contact:sp>'.$this->data['int']['sp'].'</contact:sp>
								<contact:pc>'.$this->data['int']['pc'].'</contact:pc>
								<contact:cc>'.$this->data['int']['cc'].'</contact:cc>
							</contact:addr>
						</contact:postalInfo>'."\n";
		}
		$this->xml .= '						<contact:voice'.$vx.'>'.$this->data['voice'].'</contact:voice>'."\n";
		if (is_array($this->data) and (array_key_exists('fax', $this->data) or $this->command == 'update'))
			$this->xml .= '						<contact:fax'.$fx.'>'.$this->data['fax'].'</contact:fax>'."\n";
		$this->xml .= '						<contact:email>'.$this->data['email'].'</contact:email>'."\n";
		if ($this->command != 'update')
			$this->xml .= '						<contact:authInfo>
								<contact:pw><![CDATA['.$this->data['pw'].']]></contact:pw>
							</contact:authInfo>'."\n";
		$this->xml .= '						<contact:disclose flag="'.(isset($this->data['disclose']) ? 1 : 0).'">
							<contact:name type="loc" />'."\n";
		if (is_array($this->data['int']) and count($this->data['int'])) {
			$this->xml .= '							<contact:name type="int" />'."\n";
		}
		$this->xml .= '							<contact:org type="loc" />'."\n";
		if (is_array($this->data['int']) and count($this->data['int']) and array_key_exists('org', $this->data['int'])) {
			$this->xml .= '							<contact:org type="int" />'."\n";
		}
		$this->xml .= '							<contact:addr type="loc" />'."\n";
		if (is_array($this->data['int']) and count($this->data['int'])) {
			$this->xml .= '							<contact:addr type="int" />'."\n";
		}
		$this->xml .= '							<contact:voice />
							<contact:fax />
							<contact:email />
						</contact:disclose>'."\n";
		if ($this->command == 'update')
			$this->xml .= '						</contact:chg>'."\n";
		$this->xml .= <<<XML
					</contact:$command>
				</$command>
				<clTRID>$this->cltrid</clTRID>
			</command>
		</epp>

XML;
	}

	public function infoContact() {
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
				<info>
					<contact:info xmlns:contact="urn:ietf:params:xml:ns:contact-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd">

XML;
		$this->xml .= '						<contact:id>'.$this->data['id'].'</contact:id>'."\n";
		$this->xml .= <<<XML
					</contact:info>
				</info>
				<clTRID>$this->cltrid</clTRID>
			</command>
		</epp>

XML;
	}

	public function checkDomain() {
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
				<check>
					<domain:check xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">

XML;
		foreach ($this->data['domain_ids'] as $d) {
			$this->xml .= '						<domain:name>'.$d.'</domain:name>'."\n";
		}
		$this->xml .= <<<XML
					</domain:check>
				</check>
				<clTRID>$this->cltrid</clTRID>
			</command>
		</epp>

XML;
	}

	public function createDomain() {
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
				<create>
					<domain:create xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">

XML;
		$this->xml .= '						<domain:name>'.$this->data['name'].'</domain:name>'."\n";
		if (array_key_exists('regperiod', $this->data) and $this->data['regperiod']) {
			$this->xml .= '						<domain:period unit="y">'.$this->data['regperiod'].'</domain:period>'."\n";
		}
		if (is_array($this->data['ns']) and count($this->data['ns'])) {
			$this->xml .= '						<domain:ns>'."\n";
			foreach ($this->data['ns'] as $ns) {
				$this->xml .= '							<domain:hostObj>'.$ns.'</domain:hostObj>'."\n";
			}
			$this->xml .= '						</domain:ns>'."\n";
		}
		if (array_key_exists('registrant', $this->data) and $this->data['registrant']) {
			$this->xml .= '						<domain:registrant>'.$this->data['registrant'].'</domain:registrant>'."\n";
		}
		if (is_array($this->data['contact'])) {
			foreach ($this->data['contact'] as $k => $v) {
				$this->xml .= '						<domain:contact type="'.$k.'">'.$v.'</domain:contact>'."\n";
			}
		}
		$this->xml .= '						<domain:authInfo>
							<domain:pw><![CDATA['.$this->data['pw'].']]></domain:pw>
						</domain:authInfo>
					</domain:create>
				</create>'."\n";
		if (is_array($this->data['extension'])) {
			$this->xml .= '				<extension>
					<extdomain:create xmlns:extdomain="urn:ics-forth:params:xml:ns:extdomain-1.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ics-forth:params:xml:ns:extdomain-1.2 extdomain-1.2.xsd">'."\n";
			foreach ($this->data['extension'] as $k => $v) {
				$this->xml .= '						<extdomain:'.$k.'>'.$v.'</extdomain:'.$k.'>'."\n";
			}
			$this->xml .= '					</extdomain:create>
				</extension>'."\n";
		}
		$this->xml .= <<<XML
				<clTRID>$this->cltrid</clTRID>
			</command>
		</epp>

XML;
	}

	public function rnewDomain() {
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
				<renew>
					<domain:renew xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">

XML;
		$this->xml .= '						<domain:name>'.$this->data['name'].'</domain:name>
						<domain:curExpDate>'.$this->data['curExpDate'].'</domain:curExpDate>'."\n";
		$this->xml .= '						<domain:period unit="y">'.$this->data['regperiod'].'</domain:period>'."\n";
		$this->xml .= <<<XML
					</domain:renew>
				</renew>

XML;
		if (array_key_exists('registrantid', $this->data) and $this->data['registrantid']) {
			$this->xml .= '				<extension>'."\n";
			$this->xml .= '					<extdomain:renew xsi:schemaLocation="urn:ics-forth:params:xml:ns:extdomain-1.2 extdomain-1.2.xsd" xmlns:extdomain="urn:ics-forth:params:xml:ns:extdomain-1.2">'."\n";
			$this->xml .= '						<extdomain:registrantid>'.$this->data['registrantid'].'</extdomain:registrantid>'."\n";
			$this->xml .= '						<extdomain:currentPW><![CDATA['.$this->data['pw'].']]></extdomain:currentPW>'."\n";
			$this->xml .= '						<extdomain:newPW><![CDATA['.$this->data['newpw'].']]></extdomain:newPW>'."\n";
			$this->xml .= '					</extdomain:renew>'."\n";
			$this->xml .= '				</extension>'."\n";
		}
		$this->xml .= <<<XML
				<clTRID>$this->cltrid</clTRID>
			</command>
		</epp>

XML;
	}

	public function updateDomain() {
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
				<update>
					<domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">

XML;
		$this->xml .= '						<domain:name>'.$this->data['name'].'</domain:name>'."\n";
		if (is_array($this->data['add']) and count($this->data['add'])) {
			$this->xml .= '						<domain:add>'."\n";
			if (is_array($this->data['add']['ns']) and count($this->data['add']['ns'])) {
				$this->xml .= '							<domain:ns>'."\n";
				foreach ($this->data['add']['ns'] as $ns) {
					$this->xml .= '								<domain:hostObj>'.$ns.'</domain:hostObj>'."\n";
				}
				$this->xml .= '							</domain:ns>'."\n";
			}
			if (is_array($this->data['add']['contact']) and count($this->data['add']['contact'])) {
				foreach ($this->data['add']['contact'] as $k => $v) {
					$this->xml .= '							<domain:contact type="'.$k.'">'.$v.'</domain:contact>'."\n";
				}
			}
			$this->xml .= '						</domain:add>'."\n";
		}
		if (is_array($this->data['rem']) and count($this->data['rem'])) {
			$this->xml .= '						<domain:rem>'."\n";
			if (is_array($this->data['rem']['ns']) and count($this->data['rem']['ns'])) {
				$this->xml .= '							<domain:ns>'."\n";
				foreach ($this->data['rem']['ns'] as $ns) {
					$this->xml .= '								<domain:hostObj>'.$ns.'</domain:hostObj>'."\n";
				}
				$this->xml .= '							</domain:ns>'."\n";
			}
			if (is_array($this->data['rem']['contact']) and count($this->data['rem']['contact'])) {
				foreach ($this->data['rem']['contact'] as $k => $v) {
					$this->xml .= '							<domain:contact type="'.$k.'">'.$v.'</domain:contact>'."\n";
				}
			}
			$this->xml .= '						</domain:rem>'."\n";
		}
		if ((array_key_exists('registrant', $this->data) and $this->data['registrant']) or (array_key_exists('pw', $this->data) and $this->data['pw'])) {
			$this->xml .= '						<domain:chg>'."\n";
			if (array_key_exists('registrant', $this->data) and $this->data['registrant']) {
				$this->xml .= '							<domain:registrant>'.$this->data['registrant'].'</domain:registrant>'."\n";
			}
			if (array_key_exists('pw', $this->data) and $this->data['pw']) {
				$this->xml .= '							<domain:authInfo>
								<domain:pw><![CDATA['.$this->data['pw'].']]></domain:pw>
							</domain:authInfo>'."\n";
			}
			$this->xml .= '						</domain:chg>'."\n";
		}
		$this->xml .= '					</domain:update>
				</update>'."\n";
		if (array_key_exists('op', $this->data) and $this->data['op']) {
			$this->xml .= '				<extension>
					<extdomain:update xsi:schemaLocation="urn:ics-forth:params:xml:ns:extdomain-1.2 extdomain-1.2.xsd" xmlns:extdomain="urn:ics-forth:params:xml:ns:extdomain-1.2">
						<extdomain:op>'.$this->data['op'].'</extdomain:op>
					</extdomain:update>
				</extension>'."\n";
		}
		$this->xml .= <<<XML
				<clTRID>$this->cltrid</clTRID>
			</command>
		</epp>

XML;
	}

	public function transDomain() {
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
				<transfer op="request">
					<domain:transfer xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">

XML;
		$this->xml .= '						<domain:name>'.$this->data['name'].'</domain:name>
						<domain:authInfo>
							<domain:pw><![CDATA['.$this->data['pw'].']]></domain:pw>'."\n";
		$this->xml .= <<<XML
						</domain:authInfo>
					</domain:transfer>
				</transfer>
				<extension>
					<extdomain:transfer xmlns:extdomain="urn:ics-forth:params:xml:ns:extdomain-1.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ics-forth:params:xml:ns:extdomain-1.2 extdomain-1.2.xsd">

XML;
		$this->xml .= '						<extdomain:registrantid>'.$this->data['registrantid'].'</extdomain:registrantid>'."\n";
		$this->xml .= '						<extdomain:newPW><![CDATA['.$this->data['newpw'].']]></extdomain:newPW>'."\n";
		$this->xml .= <<<XML
					</extdomain:transfer>
				</extension>
				<clTRID>$this->cltrid</clTRID>
			</command>
		</epp>

XML;
	}

	public function infoDomain() {
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
				<info>
					<domain:info xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">

XML;
		$this->xml .= '						<domain:name hosts="all">'.$this->data['name'].'</domain:name>'."\n";
		if (array_key_exists('pw', $this->data) and $this->data['pw']) {
			$this->xml .= '						<domain:authInfo>'."\n";
			$this->xml .= '							<domain:pw><![CDATA['.$this->data['pw'].']]></domain:pw>'."\n";
			$this->xml .= '						</domain:authInfo>'."\n";
		}
		$this->xml .= <<<XML
					</domain:info>
				</info>
				<clTRID>$this->cltrid</clTRID>
			</command>
		</epp>

XML;
	}

	public function checkHost() {
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
				<check>
					<host:check xmlns:host="urn:ietf:params:xml:ns:host-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:host-1.0 host-1.0.xsd">

XML;
		foreach ($this->data['host_ids'] as $h) {
			$this->xml .= '						<host:name>'.$h.'</host:name>'."\n";
		}
		$this->xml .= <<<XML
					</host:check>
				</check>
				<clTRID>$this->cltrid</clTRID>
			</command>
		</epp>

XML;
	}

	public function createHost() {
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
				<create>
					<host:create xmlns:host="urn:ietf:params:xml:ns:host-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:host-1.0 host-1.0.xsd">

XML;
		$this->xml .= '						<host:name>'.$this->data['name'].'</host:name>'."\n";
		if (is_array($this->data['addr'])) {
			foreach ($this->data['addr'] as $k => $v) {
				if (is_array($v)) {
					foreach ($v as $ip) {
						$this->xml .= '						<host:addr ip="'.$k.'">'.$ip.'</host:addr>'."\n";
					}
				}
			}
		}
		$this->xml .= <<<XML
					</host:create>
				</create>
				<clTRID>$this->cltrid</clTRID>
			</command>
		</epp>

XML;
	}

	public function updateHost() {
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
				<update>
					<host:update xmlns:host="urn:ietf:params:xml:ns:host-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:host-1.0 host-1.0.xsd">
	
XML;
		$this->xml .= '						<host:name>'.$this->data['name'].'</host:name>'."\n";
		if (is_array($this->data['addradd']) and count($this->data['addradd'])) {
			$this->xml .= '						<host:add>'."\n";
			foreach ($this->data['addradd'] as $k => $v) {
				if (is_array($v)) {
					foreach ($v as $ip) {
						$this->xml .= '						<host:addr ip="'.$k.'">'.$ip.'</host:addr>'."\n";
					}
				}
			}
			$this->xml .= '						</host:add>'."\n";
		}
		if (is_array($this->data['addrrem']) and count($this->data['addrrem'])) {
			$this->xml .= '						<host:rem>'."\n";
			foreach ($this->data['addrrem'] as $k => $v) {
				if (is_array($v)) {
					foreach ($v as $ip) {
						$this->xml .= '						<host:addr ip="'.$k.'">'.$ip.'</host:addr>'."\n";
					}
				}
			}
			$this->xml .= '						</host:rem>'."\n";
		}
		$this->xml .= <<<XML
					</host:update>
				</update>
				<clTRID>$this->cltrid</clTRID>
			</command>
		</epp>

XML;
	}

	public function infoHost() {
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
				<info>
					<host:info xmlns:host="urn:ietf:params:xml:ns:host-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:host-1.0 host-1.0.xsd">

XML;
		$this->xml .= '						<host:name>'.$this->data['name'].'</host:name>'."\n";
		$this->xml .= <<<XML
					</host:info>
				</info>
				<clTRID>$this->cltrid</clTRID>
			</command>
		</epp>

XML;
	}

	public function deleteHost() {
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
				<delete>
					<host:delete xmlns:host="urn:ietf:params:xml:ns:host-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:host-1.0 host-1.0.xsd">

XML;
		$this->xml .= '						<host:name>'.$this->data['name'].'</host:name>'."\n";
		$this->xml .= <<<XML
					</host:delete>
				</delete>
				<clTRID>$this->cltrid</clTRID>
			</command>
		</epp>

XML;
	}

	public function deleteContact() {
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
				<delete>
					<contact:delete xmlns:contact="urn:ietf:params:xml:ns:contact-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd">

XML;
		$this->xml .= '						<contact:id>'.$this->data['id'].'</contact:id>'."\n";
		$this->xml .= <<<XML
					</contact:delete>
				</delete>
				<clTRID>$this->cltrid</clTRID>
			</command>
		</epp>

XML;
	}

	public function deleteDomain() {
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
				<delete>
					<domain:delete xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">

XML;
		$this->xml .= '						<domain:name>'.$this->data['name'].'</domain:name>'."\n";
		$this->xml .= <<<XML
					</domain:delete>
				</delete>
				<extension>
					<extdomain:delete xsi:schemaLocation="urn:ics-forth:params:xml:ns:extdomain-1.2 extdomain-1.2.xsd" xmlns:extdomain="urn:ics-forth:params:xml:ns:extdomain-1.2">

XML;
		if (array_key_exists('pw', $this->data) and $this->data['pw']) {
			$this->xml .= '						<extdomain:pw><![CDATA['.$this->data['pw'].']]></extdomain:pw>'."\n";
		}
		if (array_key_exists('protocol', $this->data) and $this->data['protocol']) {
			$this->xml .= '						<extdomain:protocol>'.$this->data['protocol'].'</extdomain:protocol>'."\n";
		}
		$this->xml .= '						<extdomain:op>'.$this->data['op'].'</extdomain:op>'."\n";
		$this->xml .= <<<XML
				    </extdomain:delete>
				</extension>
				<clTRID>$this->cltrid</clTRID>
			</command>
		</epp>

XML;
	}

	public function infoAccount() {
		$this->xml = <<<XML
		<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
			<command>
				<info>
					<account:info xmlns:account="urn:ics-forth:params:xml:ns:account-1.1" xsi:schemaLocation="urn:ics-forth:params:xml:ns:account-1.1 account-1.1.xsd"/>
				</info>
				<clTRID>$this->cltrid</clTRID>
			</command>
		</epp>

XML;
	}

	public function exchangeData() {
		if ($this->eppgrinline == 'on' or $this->eppgrinline == 1) {
			$started = $this->getMicroTime();
		}
		if ($this->eppgrdebug == 'on' or $this->eppgrdebug == 1) {
			$fh = fopen($this->myFile, 'a') or die("can't open file");
			ob_start();
			echo "\n\n".'Data sent at: '.date("r", time())."\n";
			$content = ob_get_contents();
			$this->z->AESEncode($content);
			ob_end_clean();
			fwrite($fh, $this->cipherLines()."\n");
			fclose($fh);
		}
		$data = $this->xml;
		$data = preg_replace('/>\s+/', '>', $data);
		$data = preg_replace('/\s+</', '<', $data);
		$sl = strpos($this->url, '/') + 2;
		$url = substr($this->url, $sl);
		$sl = strpos($url, '/');
		$host = substr($url, 0, $sl);
		$rest = substr($url, $sl);
		$l = strlen($data);
		$headers = <<<HEADERS
GET $rest HTTP/1.1
Host: $host
User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)
Pragma: nocache
Content-Type: application/epp+xml;charset=UTF-8
Content-Length: $l
HEADERS;
		if ($this->cookie) {
			$parts = explode('Pragma', $headers);
			$parts[0] .= "Cookie: JSESSIONID=".$this->cookie."; Path=".$this->cookiepath."; Secure; HttpOnly; SameSite=Strict\n";
			$headers = implode('Pragma', $parts);
		}
		$data = $headers . "\n\n" . $data;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 100);
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 
		curl_setopt($ch, CURLOPT_CAINFO, $this->certfile);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $data);
		$ret = curl_exec($ch);
		$error = curl_error($ch);
		if ($error) $this->error = 'cURL returned: ' . $error;
		curl_close($ch);
		if (!$this->error) {
			$parts = preg_split('/<\?xml/', $ret);
			if (!$this->cookie) {
				if (preg_match('/JSESSIONID=([^;]+);/', $parts[0], $matches)) $this->set('cookie', $matches[1]);
				if (preg_match('/Path=([^;]+);/', $parts[0], $matches)) $this->set('cookiepath', $matches[1]);
			}
			array_shift($parts);
			$this->response = '<?xml' . implode('<?xml', $parts);
			if ($this->eppgrdebug == 'on' or $this->eppgrdebug == 1) {
				$fh = fopen($this->myFile, 'a') or die("can't open file");
				ob_start();
				echo "\n\n".'Data received at: '.date("r", time())."\n";
				$content = ob_get_contents();
				$this->z->AESEncode($content);
				ob_end_clean();
				fwrite($fh, $this->cipherLines()."\n");
				fclose($fh);
			}
		}
		else {
			$fh = fopen($this->myFile, 'a') or die("can't open file");
			ob_start();
			echo 'cURL returned: ' . $error;
			$content = ob_get_contents();
			$this->z->AESEncode($content);
			ob_end_clean();
			fwrite($fh, $this->cipherLines()."\n");
			fclose($fh);
		}
		if ($this->eppgrinline == 'on' or $this->eppgrinline == 1) {
			$ended = $this->getMicroTime();
			$this->commtime += $ended - $started;
		}
	}

	public function getUpdateContactData(&$data) {
		$this->getCreateContactData($data);
	}

	public function getContactVariable(&$data) {
		$this->data = array(
			'id'	=>	$data['id'],					//Obligatory
			'loc'	=>	array(							//Obligatory
				'name'	=>	$data['loc']['name'],		//Obligatory
				'org'	=>	$data['loc']['org'],		//Optional
				'str1'	=>	$data['loc']['str1'],		//Obligatory
				'str2'	=>	$data['loc']['str2'],		//Optional
				'str3'	=>	$data['loc']['str3'],		//Optional
				'city'	=>	$data['loc']['city'],		//Obligatory
				'sp'	=>	$data['loc']['sp'],			//Obligatory
				'pc'	=>	$data['loc']['pc'],			//Obligatory
				'cc'	=>	$data['loc']['cc'],			//Obligatory
			),
			'int'	=>	array(							//Optional
				'name'	=>	$data['int']['name'],		//Optional: Obligatory
				'org'	=>	$data['int']['org'],		//Optional: Optional
				'str1'	=>	$data['int']['str1'],		//Optional: Obligatory
				'str2'	=>	$data['int']['str2'],		//Optional: Optional
				'str3'	=>	$data['int']['str3'],		//Optional: Optional
				'city'	=>	$data['int']['city'],		//Optional: Obligatory
				'sp'	=>	$data['int']['sp'],			//Optional: Obligatory
				'pc'	=>	$data['int']['pc'],			//Optional: Obligatory
				'cc'	=>	$data['int']['cc'],			//Optional: Obligatory
			),
			'voice'		=>	$data['voice'],				//Obligatory
			'vx'		=>	$data['vx'],				//Optional
			'fax'		=>	$data['fax'],				//Optional
			'fx'		=>	$data['fx'],				//Optional
			'email'		=>	$data['email'],				//Obligatory
			'pw'		=>	$data['pw'],				//Obligatory
		);
	}

	public function getCheckContactData(&$data) {
		if (array_key_exists('contact_ids', $data)) $this->data = $data;
		else {
			$this->data = array(
				'contact_ids'	=>	$data,				//Obligatory
			);
		}
	}

	public function getCreateContactData(&$data) {
		$this->getContactVariable($data);
	}

	public function getInfoContactData(&$data) {
		$this->data = array(
			'id'	=>	$data['id'],					//Obligatory
		);
	}

	public function getDomainVariable(&$data) {
		$this->data = array(
			'name'			=>	$data['name'],			//Obligatory
			'ns'			=>	array(),				//Optional
			'registrant'	=>	$data['registrant'],	//Obligatory for domain, Optional for dname
			'contact'		=>	array(),				//Optional
			'extension'		=>	array(					//Optional
//				'reject'	=>	'',						//Optional
//				'comment'	=>	'',						//Optional
				'use'		=>	'',						//Optional -- Obligatory for .com.gr, .net.gr, .org.gr, .edu.gr, .gov.gr
				'record'	=>	'',						//Optional: Optional values: domain || dname
			),
			'pw'			=>	$data['pw'],			//Obligatory
			'regperiod'		=>	$data['regperiod'],		//Obligatory for domain, Optional for dname
		);
	}

	public function getCheckDomainData(&$data) {
		if (array_key_exists('domain_ids', $data)) $this->data = $data;
		else {
			$this->data = array(
				'domain_ids'	=>	$data,				//Obligatory
			);
		}
	}

	public function getCreateDomainData(&$data) {
		$this->getDomainVariable($data);
		if (is_array($data['ns']) and count($data['ns'])) {
			foreach ($data['ns'] as $d) {
				$this->data['ns'][] = $d;
			}
			while (count($data['ns']) > 16) array_pop($data['ns']);
		}
		if (is_array($data['contact']) and count($data['contact'])) {
			foreach ($data['contact'] as $k => $v) {
				$this->data['contact'][$k] = $v;
			}
			while (count($data['contact']) > 10) array_pop($data['contact']);
		}
	}

	public function getRnewDomainData(&$data) {
		$this->data = array(
			'name'			=>	$data['name'],			//Obligatory
			'curExpDate'	=>	$data['curExpDate'],	//Obligatory Form: YYYY-MM-DD
			'regperiod'		=>	$data['regperiod'],		//Obligatory
			'pw'			=>	$data['pw'],			//Obligatory with transfer
			'newpw'			=>	$data['newpw'],			//Obligatory with transfer
			'registrantid'	=>	$data['registrantid'],	//Obligatory with transfer
		);
	}

	public function getUpdateDomainData(&$data) {
		$this->data = array(
			'name'			=>	$data['name'],			//Obligatory
			'add'			=>	array(),				//Optional but one of add, rem, registrant/pw is obligatory
			'rem'			=>	array(),				//Optional but one of add, rem, registrant/pw is obligatory
			'registrant'	=>	$data['registrant'],	//Optional but one of add, rem, registrant/pw is obligatory
			'pw'			=>	$data['pw'],			//Optional but one of add, rem, registrant/pw is obligatory
			'op'			=>	$data['op'],			//Optional but obligatory with registrant -- Optional values: ownerChange || ownerNameChange
		);
		if (is_array($data['add']['ns']) and count($data['add']['ns'])) {
			foreach ($data['add']['ns'] as $d) {
				$this->data['add']['ns'][] = $d;
			}
			while (count($data['add']['ns']) > 16) array_pop($data['add']['ns']);
		}
		if (is_array($data['rem']['ns']) and count($data['rem']['ns'])) {
			foreach ($data['rem']['ns'] as $d) {
				$this->data['rem']['ns'][] = $d;
			}
		}
		if (is_array($data['add']['contact']) and count($data['add']['contact'])) {
			foreach ($data['add']['contact'] as $k => $v) {
				$this->data['add']['contact'][$k] = $v;
			}
			while (count($data['add']['contact']) > 10) array_pop($data['add']['contact']);
		}
		if (is_array($data['rem']['contact']) and count($data['rem']['contact'])) {
			foreach ($data['rem']['contact'] as $k => $v) {
				$this->data['rem']['contact'][$k] = $v;
			}
		}
	}

	public function getTransDomainData(&$data) {
		$this->data = array(
			'name'			=>	$data['name'],			//Obligatory
			'pw'			=>	$data['pw'],			//Obligatory
			'newpw'			=>	$data['newpw'],			//Obligatory
			'registrantid'	=>	$data['registrantid'],	//Obligatory
		);
	}

	public function getInfoDomainData(&$data) {
		$this->data = array(
			'name'			=>	$data['name'],			//Obligatory
			'pw'			=>	$data['pw'],			//Optional
		);
	}

	public function getHostVariable(&$data) {
		$this->data = array(
			'name'		=>	$data['name'],				//Obligatory
			'addr'		=>	array(						//Optional
				'v4'	=>	array(),					//Optional: Optional
				'v6'	=>	array(),					//Optional: Optional
			),
		);
	}

	public function getCheckHostData(&$data) {
		if (array_key_exists('host_ids', $data)) $this->data = $data;
		else {
			$this->data = array(
				'host_ids'	=>	$data,					//Obligatory
			);
		}
	}

	public function getCreateHostData(&$data) {
		$this->getHostVariable($data);
		$this->cleanHostAddr($data['addr'], $this->data['addr']);
	}

	public function getUpdateHostData(&$data) {
		$this->data = array(
			'name'		=>	$data['name'],				//Obligatory
			'addradd'	=>	array(						//Optional
				'v4'	=>	array(),					//Optional: Optional
				'v6'	=>	array(),					//Optional: Optional
			),
			'addrrem'	=>	array(						//Optional
				'v4'	=>	array(),					//Optional: Optional
				'v6'	=>	array(),					//Optional: Optional
			),
		);
		$this->cleanHostAddr($data['addradd'], $this->data['addradd']);
		$this->cleanHostAddr($data['addrrem'], $this->data['addrrem']);
	}

	public function getInfoAccountData(&$data) {
		$this->data = array();
	}

	public function getInfoHostData(&$data) {
		$this->data = array(
			'name'			=>	$data['name'],			//Obligatory
		);
	}

	public function getDeleteHostData(&$data) {
		$this->data = array(
			'name'			=>	$data['name'],			//Obligatory
		);
	}

	public function getDeleteContactData(&$data) {
		$this->data = array(
			'id'			=>	$data['id'],			//Obligatory
		);
	}

	public function getDeleteDomainData(&$data) {
		$this->data = array(
			'name'			=>	$data['name'],			//Obligatory
			'pw'			=>	$data['pw'],			//Obligatory to delete
			'op'			=>	$data['op'],			//Obligatory
			'protocol'		=>	$data['protocol'],		//Obligatory to recall
		);
	}

	public function parseInfoAccountResponseData() {
		if ($this->error) return false;
		else {
			preg_match('/<result code=["\'](\d+)["\']>/', $this->response, $matches);
			if ($matches[1] == '1000') {
				$data = array();
				preg_match('/<account:roid>([^<]+)<\/account:roid>/', $this->response, $matches);
				$data['roid'] = $matches[1];
				preg_match('/<account:ddPaymentCode>([^<]+)<\/account:ddPaymentCode>/', $this->response, $matches);
				$data['ddPaymentCode'] = $matches[1];
				preg_match('/<account:caAllowed>([^<]+)<\/account:caAllowed>/', $this->response, $matches);
				$data['caAllowed'] = $matches[1];
				preg_match('/<account:balance currency="EUR">([^<]+)<\/account:balance>/', $this->response, $matches);
				$data['credits'] = $matches[1];
				preg_match('/<account:tbSuspendedOn>([^<]+)<\/account:tbSuspendedOn>/', $this->response, $matches);
				$data['tbSuspendedOn'] = $matches[1];
				preg_match('/<account:suspendedOn>([^<]+)<\/account:suspendedOn>/', $this->response, $matches);
				$data['suspendedOn'] = $matches[1];
				return $data;
			}
			else return $matches[1];
		}
	}

	public function parseInfoHostResponseData() {
		if ($this->error) return false;
		else {
			preg_match('/<result code=["\'](\d+)["\']>/', $this->response, $matches);
			if ($matches[1] == '1000') {
				$data = array();
				$this->getHostVariable($data);
				preg_match('/<host:name>([^<]+)<\/host:name>/', $this->response, $matches);
				$this->data['name'] = $matches[1];
				$parts = explode('<host:addr ip=', $this->response);
				for ($i = 1; $i < count($parts); $i++) {
					preg_match('/^["\'](v\d)["\']>([^<]+)<\/host:addr>/', $parts[$i], $matches);
					$this->data['addr'][$matches[1]][] = $matches[2];
				}
				return $this->data;
			}
			else return $matches[1];
		}
	}

	public function parseDeleteHostResponseData() {
		if ($this->error) return false;
		else {
			preg_match('/<result code=["\'](\d+)["\']>/', $this->response, $matches);
			if ($matches[1] == '1000') {
				preg_match('/<svTRID>([^<]+)<\/svTRID>/', $this->response, $matches);
				$data['svtrid'] = $matches[1];
				return $data;
			}
			else return $matches[1];
		}
	}

	public function parseDeleteContactResponseData() {
		if ($this->error) return false;
		else {
			preg_match('/<result code=["\'](\d+)["\']>/', $this->response, $matches);
			if ($matches[1] == '1000') {
				preg_match('/<svTRID>([^<]+)<\/svTRID>/', $this->response, $matches);
				$data['svtrid'] = $matches[1];
				return $data;
			}
			else return $matches[1];
		}
	}

	public function parseDeleteDomainResponseData() {
		if ($this->error) return false;
		else {
			preg_match('/<result code=["\'](\d+)["\']>/', $this->response, $matches);
			if ($matches[1] == '1000') {
				preg_match('/<svTRID>([^<]+)<\/svTRID>/', $this->response, $matches);
				$data['svtrid'] = $matches[1];
				return $data;
			}
			else return $matches[1];
		}
	}

	public function parseRnewDomainResponseData() {
		if ($this->error) return false;
		else {
			preg_match('/<result code=["\'](\d+)["\']>/', $this->response, $matches);
			if ($matches[1] == '1000') {
				preg_match('/<svTRID>([^<]+)<\/svTRID>/', $this->response, $matches);
				$data['svtrid'] = $matches[1];
				preg_match('/<d.main:exDate>([^<]+)<\/d.main:exDate>/', $this->response, $matches);
				$data['exDate'] = $matches[1];
				return $data;
			}
			else return $matches[1];
		}
	}

	public function parseTransDomainResponseData() {
		if ($this->error) return false;
		else {
			preg_match('/<result code=["\'](\d+)["\']>/', $this->response, $matches);
			if ($matches[1] == '1000') {
				preg_match('/<svTRID>([^<]+)<\/svTRID>/', $this->response, $matches);
				$data['svtrid'] = $matches[1];
				return $data;
			}
			else return $matches[1];
		}
	}

	public function parseCheckContactResponseData() {
		if ($this->error) return false;
		else return $this->findAvailability('contact', 'id');
	}

	public function findAvailability($tag, $name) {
		preg_match('/<result code=["\'](\d+)["\']>/', $this->response, $matches);
		if ($matches[1] == '1000') {
			$data = array();
			$parts = explode('<'.$tag.':'.$name.' avail=', $this->response);
			for ($i = 1; $i < count($parts); $i++) {
				preg_match('/^["\'](\d)["\']>([^<]+)<\/'.$tag.':'.$name.'>/', $parts[$i], $matches);
				preg_match('/<'.$tag.':reason>([^<]+)<\/'.$tag.':reason>/', $parts[$i], $r);
				if (!is_array($r) or count($r) < 2) $r[1] = '';
				$data[$matches[2]] = array('avail' => ($matches[1] ? true : false), 'reason' => $r[1]);
			}
			return $data;
		}
		else return $matches[1];
	}

	public function parseCreateContactResponseData() {
		if ($this->error) return false;
		else {
			preg_match('/<result code=["\'](\d+)["\']>/', $this->response, $matches);
			if ($matches[1] == '1000') {
				$data = array();
				preg_match('/<contact:id>([^<]+)<\/contact:id>/', $this->response, $matches);
				$data['id'] = $matches[1];
				preg_match('/<contact:crDate>([^<]+)<\/contact:crDate>/', $this->response, $matches);
				$data['crDate'] = $matches[1];
				preg_match('/<clTRID>([^<]+)<\/clTRID>/', $this->response, $matches);
				$data['cltrid'] = $matches[1];
				preg_match('/<svTRID>([^<]+)<\/svTRID>/', $this->response, $matches);
				$data['svtrid'] = $matches[1];
				return $data;
			}
			else return $matches[1];
		}
	}

	public function parseInfoContactResponseData() {
		if ($this->error) return false;
		else {
			preg_match('/<result code=["\'](\d+)["\']>/', $this->response, $matches);
			if ($matches[1] == '1000') {
				$data = array();
				$this->getContactVariable($data);
				$fields = array('id', 'voice', 'fax', 'email', 'pw');
				foreach ($fields as $f) {
					preg_match('/<contact:'.$f.'[^>]*>([^<]+)<\/contact:'.$f.'>/', $this->response, $matches);
					$this->data[$f] = $matches[1];
				}
				preg_match('/<contact:voice x=["\']([^"\']+)["\']>[^<]+<\/contact:voice>/', $this->response, $matches);
				$this->data['vx'] = $matches[1];
				preg_match('/<contact:fax x=["\']([^"\']+)["\']>[^<]+<\/contact:fax>/', $this->response, $matches);
				$this->data['fx'] = $matches[1];
				$parts = explode('<contact:postalInfo', $this->response);
				$this->getInfoContactStreets('loc', $parts[1]);
				if (count($parts) > 2) $this->getInfoContactStreets('int', $parts[2]);
				return $this->data;
			}
			else return $matches[1];
		}
	}

	protected function getInfoContactStreets($key, $part) {
		$fields = array('name', 'org', 'city', 'sp', 'pc', 'cc');
		foreach ($fields as $f) {
			preg_match('/<contact:'.$f.'[^>]*>([^<]+)<\/contact:'.$f.'>/', $part, $matches);
			$this->data[$key][$f] = $matches[1];
		}
		$streets = explode('<contact:street>', $part);
		for ($i = 1; $i < count($streets); $i++) {
			preg_match('/^([^<]+)<\/contact:street>/', $streets[$i], $matches);
			$this->data[$key]['str'.$i] = $matches[1];
		}
	}

	public function parseCheckHostResponseData() {
		if ($this->error) return false;
		else return $this->findAvailability('host', 'name');
	}

	public function parseUpdateHostResponseData() {
		if ($this->error) return false;
		else {
			preg_match('/<result code=["\'](\d+)["\']>/', $this->response, $matches);
			if ($matches[1] == '1000') {
				preg_match('/<svTRID>([^<]+)<\/svTRID>/', $this->response, $matches);
				$data['svtrid'] = $matches[1];
				return $data;
			}
			else return $matches[1];
		}
	}

	public function parseCreateHostResponseData() {
		if ($this->error) return false;
		else {
			preg_match('/<result code=["\'](\d+)["\']>/', $this->response, $matches);
			if ($matches[1] == '1000') {
				$data = array();
				preg_match('/<host:name>([^<]+)<\/host:name>/', $this->response, $matches);
				$data['name'] = $matches[1];
				preg_match('/<host:crDate>([^<]+)<\/host:crDate>/', $this->response, $matches);
				$data['crDate'] = $matches[1];
				preg_match('/<clTRID>([^<]+)<\/clTRID>/', $this->response, $matches);
				$data['cltrid'] = $matches[1];
				preg_match('/<svTRID>([^<]+)<\/svTRID>/', $this->response, $matches);
				$data['svtrid'] = $matches[1];
				return $data;
			}
			else return $matches[1];
		}
	}

	public function parseCheckDomainResponseData() {
		if ($this->error) return false;
		else return $this->findAvailability('domain', 'name');
	}

	public function parseInfoDomainResponseData() {
		if ($this->error) return false;
		else {
			preg_match('/<result code=["\'](\d+)["\']>/', $this->response, $matches);
			if ($matches[1] == '1000') {
				$data = array();
				$this->getDomainVariable($data);
				$fields = array('name', 'registrant', 'pw');
				foreach ($fields as $f) {
					preg_match('/<domain:'.$f.'[^>]*>([^<]+)<\/domain:'.$f.'>/', $this->response, $matches);
					$data[$f] = $matches[1];
				}
				$lockfields = array('delegationChangeLock', 'ownerChangeLock', 'ownerNameChangeLock', 'expirationLock');
				foreach ($lockfields as $lf) {
					if(preg_match('/<lock:type lock=["\']'.$lf.'["\']\s*\/>/', $this->response)) {
						$data['lock'][] = $lf;
					}
				}
				preg_match_all('/<domain:status s=["\'](\w+)["\']\s*\/>/', $this->response, $status, PREG_SET_ORDER);
				foreach ($status as $s) { $data['status'][] = $s[1]; }
				preg_match_all('/<domain:contact type=["\'](\w+)["\']>([^<]+)<\/domain:contact>/', $this->response, $contacts, PREG_SET_ORDER);
				foreach ($contacts as $c) { $data['contact'][$c[1]] = $c[2]; }
				preg_match_all('/<d.main:h.st.bj>([^<]+)<\/d.main:h.st.bj>/', $this->response, $ns, PREG_SET_ORDER);
				foreach ($ns as $n) { $data['ns'][] = $n[1]; }
				preg_match_all('/<extdomain:bundlename\s+(\w+)=\D(\d)\D\s+(\w+)=\W(\w+)\W>([^<]+)<\/extdomain:bundlename>/i', $this->response, $domains, PREG_SET_ORDER);
				foreach ($domains as $d) {
					$data['bundleName'][$d[5]][$d[1]] = $d[2];
					$data['bundleName'][$d[5]][$d[3]] = $d[4];
				}
				preg_match('/<extdomain:protocol>([^<]+)<\/extdomain:protocol>/', $this->response, $matches);
				$data['protocol'] = $matches[1];
				preg_match('/<domain:crDate>([^<]+)<\/domain:crDate>/', $this->response, $matches);
				$data['crDate'] = $matches[1];
				preg_match('/<d.main:exDate>([^<]+)<\/d.main:exDate>/', $this->response, $matches);
				$data['exDate'] = $matches[1];
				return $data;
			}
			else return $matches[1];
		}
	}

	public function parseCreateDomainResponseData() {
		if ($this->error) return false;
		else {
			preg_match('/<result code=["\'](\d+)["\']>/', $this->response, $matches);
			if ($matches[1] == '1000' or $matches[1] == '1001') {
				$data = array();
				preg_match('/<domain:name>([^<]+)<\/domain:name>/', $this->response, $matches);
				$data['name'] = $matches[1];
				preg_match('/<domain:crDate>([^<]+)<\/domain:crDate>/', $this->response, $matches);
				$data['crDate'] = $matches[1];
				preg_match('/<domain:exDate>([^<]+)<\/domain:exDate>/', $this->response, $matches);
				$data['exDate'] = $matches[1];
				if (preg_match('/<extdomain:protocol>([^<]+)<\/extdomain:protocol>/', $this->response, $matches)) {
					$data['protocol'] = $matches[1];
				}
				if (preg_match('/<extdomain:comment>([^<]+)<\/extdomain:comment>/', $this->response, $matches)) {
					$data['comment'] = $matches[1];
				}
				preg_match('/<clTRID>([^<]+)<\/clTRID>/', $this->response, $matches);
				$data['cltrid'] = $matches[1];
				preg_match('/<svTRID>([^<]+)<\/svTRID>/', $this->response, $matches);
				$data['svtrid'] = $matches[1];
				return $data;
			}
			else return $matches[1];
		}
	}

	public function parseUpdateDomainResponseData() {
		if ($this->error) return false;
		else {
			preg_match('/<result code=["\'](\d+)["\']>/', $this->response, $matches);
			if ($matches[1] == '1000') {
				preg_match('/<svTRID>([^<]+)<\/svTRID>/', $this->response, $matches);
				$data['svtrid'] = $matches[1];
				return $data;
			}
			else return $matches[1];
		}
	}

	public function parseUpdateContactResponseData() {
		if ($this->error) return false;
		else {
			preg_match('/<result code=["\'](\d+)["\']>/', $this->response, $matches);
			if ($matches[1] == '1000') {
				preg_match('/<svTRID>([^<]+)<\/svTRID>/', $this->response, $matches);
				$data['svtrid'] = $matches[1];
				return $data;
			}
			else return $matches[1];
		}
	}

	protected function parseLoginResponseData() {
		if ($this->error) return false;
		else {
			preg_match('/<result code=["\'](\d+)["\']>/', $this->response, $matches);
			if ($matches[1] != '1000') {
				preg_match('/<msg>([^<]+)<\/msg>/', $this->response, $matches);
				$this->error = $matches[1];
			}
		}
	}

	protected function cleanHostAddr(&$addr, &$data) {
		if (is_array($addr['v4']) and count($addr['v4'])) {
			foreach ($addr['v4'] as $d) {
				$data['v4'][] = $d;
			}
		}
		if (is_array($addr['v6']) and count($addr['v6'])) {
			foreach ($addr['v6'] as $d) {
				$data['v6'][] = $d;
			}
		}
		while (count($data['v4']) + count($data['v6']) > 32) {
			if (count($data['v4']) > count($data['v6'])) array_pop($data['v4']);
			else array_pop($data['v6']);
		}
	}

	protected function getData(){
		$data = array();
		if (is_array($this->data)) {
			$data = $this->data;
			$this->protectArray($data);
			unset($this->data);
		}
		return $data;
	}

	public function cleanArray(&$array) {
		foreach (array_keys($array) as $k) {
			if (is_array($array[$k])) {
				if (count($array[$k])) $this->cleanArray($array[$k]);
				if (!count($array[$k])) unset($array[$k]);
			}
			elseif (!trim($array[$k])) {
				unset($array[$k]);
			}
			else {
				$array[$k] = trim($array[$k]);
			}
		}
	}

	protected function protectArray(&$array) {
		foreach (array_keys($array) as $k) {
			if (is_array($array[$k])) {
				if (count($array[$k])) $this->protectArray($array[$k]);
			}
			elseif (!$array[$k]) htmlspecialchars($array[$k], ENT_QUOTES);
		}
	}

}

?>
