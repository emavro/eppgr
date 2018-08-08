<?php
/*
 *  File: build/patch06.php
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

require_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'patchhead.php');

$files = array(
	'clientareadomaincontactinfo.tpl'	=>	array(
		'find'	=>	'contactchange',
		'after'	=>	array(
			'action=domaincontacts">'	=>	"\n".'<input type="radio" name="contactchange" value="noownershipchange" checked /> {$LANG.eppgrnoownershipchange}<br />'."\n".'<input type="radio" name="contactchange" value="ownerNameChange" /> {$LANG.eppgrownernamechange}<br />'."\n".'<input type="radio" name="contactchange" value="ownerChange" /> {$LANG.eppgrownerchange}<br />'."\n",
		),
		'befor'	=>	array(),
		'chang'	=>	array(),
		'skpit'	=>	array(),
	),
);

require_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'patch.php');

?>