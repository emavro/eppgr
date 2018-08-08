<?php
/*
 *  File: build/patch04.php
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
	'classic/clientareadomaingetepp.tpl'	=>	array(
		'find'	=>	'?action=domaingetepp',
		'after'	=>	array(
			'{/if}</div>'	=>	"\n\n".'<p>&nbsp;</p>'."\n".'<p class="heading2">{$LANG.eppgrseteppcode}</p>'."\n\n".'<form method="post" action="{$smarty.server.PHP_SELF}?action=domaingetepp">'."\n".'{$LANG.eppgrseteppcodefield}:* <input type="text" name="neweppcode" size="50">'."\n".'<br/>'."\n".'* {$LANG.eppgrseteppcodefoot}'."\n".'<input type="hidden" name="updateeppcode" value="yes" />'."\n".'<input type="hidden" name="domainid" value="{$domainid}">'."\n".'<p align="center"><input type="submit" value="{$LANG.clientareasavechanges}" /></p>'."\n".'</form>'."\n",
		),
		'befor'	=>	array(),
		'chang'	=>	array(),
		'skpit'	=>	array(),
	),
	'portal/clientareadomaingetepp.tpl'	=>	array(
		'find'	=>	'?action=domaingetepp',
		'after'	=>	array(
			'{/if}</div>'	=>	"\n\n".'<p>&nbsp;</p>'."\n".'<p class="heading2">{$LANG.eppgrseteppcode}</p>'."\n\n".'<form method="post" action="{$smarty.server.PHP_SELF}?action=domaingetepp">'."\n".'{$LANG.eppgrseteppcodefield}:* <input type="text" name="neweppcode" size="50">'."\n".'<br/>'."\n".'* {$LANG.eppgrseteppcodefoot}'."\n".'<input type="hidden" name="updateeppcode" value="yes" />'."\n".'<input type="hidden" name="domainid" value="{$domainid}">'."\n".'<p align="center"><input type="submit" value="{$LANG.clientareasavechanges}" /></p>'."\n".'</form>'."\n",
		),
		'befor'	=>	array(),
		'chang'	=>	array(),
		'skpit'	=>	array(),
	),
	'default/clientareadomaingetepp.tpl'	=>	array(
		'find'	=>	'?action=domaingetepp',
		'after'	=>	array(),
		'befor'	=>	array(
			'<form'	=>	"\n\n".'<p>&nbsp;</p>'."\n".'<p class="heading2">{$LANG.eppgrseteppcode}</p>'."\n\n".'<form method="post" action="{$smarty.server.PHP_SELF}?action=domaingetepp">'."\n".'{$LANG.eppgrseteppcodefield}:* <input type="text" name="neweppcode" size="50">'."\n".'<br/>'."\n".'* {$LANG.eppgrseteppcodefoot}'."\n".'<input type="hidden" name="updateeppcode" value="yes" />'."\n".'<input type="hidden" name="domainid" value="{$domainid}">'."\n".'<p align="center"><input type="submit" value="{$LANG.clientareasavechanges}" /></p>'."\n".'</form>'."\n",
		),
		'chang'	=>	array(),
		'skpit'	=>	array(),
	),
);

require_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'patch.php');

?>