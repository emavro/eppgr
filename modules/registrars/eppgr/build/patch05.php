<?php
/*
 *  File: build/patch05.php
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
		'find'	=>	'eppgrdeactivate',
		'after'	=>	array(
			'<p>{$LANG.domaingeteppcodeexplanation}</p>'	=>	"\n".'<form method="post" action="{$smarty.server.PHP_SELF}?action=domaingetepp">'."\n",
			'{$domain}</strong>'	=>	'{if $eppgrAllowDeleteDomain || $eppgrAllowRecallDomain} [<input name="eppgrdeletedomain" type="checkbox" /> {$LANG.eppgrdeletedomain}]{/if}',
		),
		'befor'	=>	array(
			'<div class="errorbox">'	=>	"\n".'{if $eppgrBundleName|@count > 0}'."\n".'<div id="eppgrdeactivate">'."\n".'          {$LANG.eppgractivednames}:<br />'."\n".'          {if $eppgrAllowDeleteHomographs}<select name="eppgrdeactivate"><option value=""></option>{else}<ul>{/if}'."\n".'          {foreach from=$eppgrBundleName key=key item=value}'."\n".'            {foreach from=$value key=k item=v}'."\n".'              {if $eppgrAllowDeleteHomographs}<option value="{$k}">{$k} ({$key} -- {$v})</option>'."\n".'              {else}<li>{$k} ({$key} -- {$v})</li>{/if}'."\n".'            {/foreach}'."\n".'          {/foreach}'."\n".'          {if $eppgrAllowDeleteHomographs}</select>{else}</ul>{/if}'."\n".'</div>'."\n".'{/if}'."\n".'{if $eppgrExtraNames|@count > 0}'."\n".'<div id="eppgractivate">'."\n".'            {$LANG.eppgractivatedname}:<br />'."\n".'            <select name="eppgractivate">'."\n".'              <option value=""></option>'."\n".'              {foreach from=$eppgrExtraNames item=value}'."\n".'                <option value="{$value}">{$value}</option>'."\n".'              {/foreach}'."\n".'            </select><br />'."\n".'            {$LANG.eppgrenteractivatedname}:<br />'."\n".'            <input type="text" name="eppgractivatetext" size="50"><br/>'."\n".'</div>'."\n".'{/if}'."\n",
		),
		'chang'	=>	array(),
		'skpit'	=>	array(),
	),
	'portal/clientareadomaingetepp.tpl'	=>	array(
		'find'	=>	'eppgrdeactivate',
		'after'	=>	array(
			'<p>{$LANG.domaingeteppcodeexplanation}</p>'	=>	"\n".'<form method="post" action="{$smarty.server.PHP_SELF}?action=domaingetepp">'."\n",
			'{$domain}</strong>'	=>	'{if $eppgrAllowDeleteDomain || $eppgrAllowRecallDomain} [<input name="eppgrdeletedomain" type="checkbox" /> {$LANG.eppgrdeletedomain}]{/if}',
		),
		'befor'	=>	array(
			'<div class="errorbox">'	=>	"\n".'{if $eppgrBundleName|@count > 0}'."\n".'<div id="eppgrdeactivate">'."\n".'          {$LANG.eppgractivednames}:<br />'."\n".'          {if $eppgrAllowDeleteHomographs}<select name="eppgrdeactivate"><option value=""></option>{else}<ul>{/if}'."\n".'          {foreach from=$eppgrBundleName key=key item=value}'."\n".'            {foreach from=$value key=k item=v}'."\n".'              {if $eppgrAllowDeleteHomographs}<option value="{$k}">{$k} ({$key} -- {$v})</option>'."\n".'              {else}<li>{$k} ({$key} -- {$v})</li>{/if}'."\n".'            {/foreach}'."\n".'          {/foreach}'."\n".'          {if $eppgrAllowDeleteHomographs}</select>{else}</ul>{/if}'."\n".'</div>'."\n".'{/if}'."\n".'{if $eppgrExtraNames|@count > 0}'."\n".'<div id="eppgractivate">'."\n".'            {$LANG.eppgractivatedname}:<br />'."\n".'            <select name="eppgractivate">'."\n".'              <option value=""></option>'."\n".'              {foreach from=$eppgrExtraNames item=value}'."\n".'                <option value="{$value}">{$value}</option>'."\n".'              {/foreach}'."\n".'            </select><br />'."\n".'            {$LANG.eppgrenteractivatedname}:<br />'."\n".'            <input type="text" name="eppgractivatetext" size="50"><br/>'."\n".'</div>'."\n".'{/if}'."\n",
		),
		'chang'	=>	array(),
		'skpit'	=>	array(),
	),
	'default/clientareadomaingetepp.tpl'	=>	array(
		'find'	=>	'eppgrdeactivate',
		'after'	=>	array(
			'<p>{$LANG.domaingeteppcodeexplanation}</p>'	=>	"\n".'<form method="post" action="{$smarty.server.PHP_SELF}?action=domaingetepp">'."\n",
			'{$domain}</strong</p>'	=>	'{if $eppgrAllowDeleteDomain || $eppgrAllowRecallDomain} [<input name="eppgrdeletedomain" type="checkbox" /> {$LANG.eppgrdeletedomain}]{/if}',
		),
		'befor'	=>	array(
			'<p>{$LANG.domaingeteppcodeexplanation}</p>'	=>	"\n".'{if $eppgrBundleName|@count > 0}'."\n".'<div id="eppgrdeactivate">'."\n".'          {$LANG.eppgractivednames}:<br />'."\n".'          {if $eppgrAllowDeleteHomographs}<select name="eppgrdeactivate"><option value=""></option>{else}<ul>{/if}'."\n".'          {foreach from=$eppgrBundleName key=key item=value}'."\n".'            {foreach from=$value key=k item=v}'."\n".'              {if $eppgrAllowDeleteHomographs}<option value="{$k}">{$k} ({$key} -- {$v})</option>'."\n".'              {else}<li>{$k} ({$key} -- {$v})</li>{/if}'."\n".'            {/foreach}'."\n".'          {/foreach}'."\n".'          {if $eppgrAllowDeleteHomographs}</select>{else}</ul>{/if}'."\n".'</div>'."\n".'{/if}'."\n".'{if $eppgrExtraNames|@count > 0}'."\n".'<div id="eppgractivate">'."\n".'            {$LANG.eppgractivatedname}:<br />'."\n".'            <select name="eppgractivate">'."\n".'              <option value=""></option>'."\n".'              {foreach from=$eppgrExtraNames item=value}'."\n".'                <option value="{$value}">{$value}</option>'."\n".'              {/foreach}'."\n".'            </select><br />'."\n".'            {$LANG.eppgrenteractivatedname}:<br />'."\n".'            <input type="text" name="eppgractivatetext" size="50"><br/>'."\n".'</div>'."\n".'{/if}'."\n",
		),
		'chang'	=>	array(),
		'skpit'	=>	array(),
	),
);

require_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'patch.php');

?>