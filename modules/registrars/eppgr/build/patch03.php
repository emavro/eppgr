<?php
/*
 *  File: build/patch03.php
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
	'header.tpl'					=>	array(
		'find'	=>	'eppgr.js.php',
		'after'	=>	array(
			'<script type="text/javascript" src="includes/jscript/jquery.js"></script>'	=>	"\n".'<script type="text/javascript" src="modules/registrars/eppgr/js/tw-sack.js"></script>'."\n".'<script type="text/javascript" src="modules/registrars/eppgr/js/xregexp-min.js"></script>'."\n".'<script type="text/javascript" src="modules/registrars/eppgr/js/eppgr.js.php"></script>'."\n",
		),
		'befor'	=>	array(),
		'chang'	=>	array(),
		'skpit'	=>	array(),
	),
	'domainchecker.tpl'				=>	array(
		'find'	=>	'javascript: domainsearch(this);',
		'after'	=>	array(
			'<input type="text" name="domain"'	=>	' onkeypress="return submitViaEnter(event)"',
		),
		'befor'	=>	array(
			'{if $lookup}'	=>	'<div id="eppgr_div">'."\n",
		),
		'chang'	=>	array(
			'<input type="submit" id="Submit" value="{$LANG.domainlookupbutton}"'	=>	'<input type="button" id="Submit" value="{$LANG.domainlookupbutton}" onclick="javascript: domainsearch(this);"',
		),
		'skpit'	=>	array(
			'<td>{if $tldpricelist.renew}{$tldpricelist.renew}{else}{$LANG.domainregnotavailable}{/if}</td>'	=>	array(
				'{/if}'	=>	'{/if}</div>',
			),
		),
	),
	'bulkdomainchecker.tpl'			=>	array(
		'find'	=>	'javascript: bulksearch(this);',
		'after'	=>	array(),
		'befor'	=>	array(
			'{if $availabilityresults}'	=>	'<div id="eppgr_div">'."\n",
		),
		'chang'	=>	array(
			'<input type="submit" id="Submit" value="{$LANG.domainlookupbutton}"'	=>	'<input type="button" id="Submit" value="{$LANG.domainlookupbutton}" onclick="javascript: bulksearch(this);"',
		),
		'skpit'	=>	array(
			'<td>{if $tldpricelist.renew}{$tldpricelist.renew}{else}{$LANG.domainregnotavailable}{/if}</td>'	=>	array(
				'{/if}'	=>	'{/if}</div>',
			),
		),
	),
	'bulkdomaintransfer.tpl'		=>	array(
		'find'	=>	'javascript: bulktransfer(this);',
		'after'	=>	array(),
		'befor'	=>	array(
			'{if $availabilityresults}'	=>	'<div id="eppgr_div">'."\n",
		),
		'chang'	=>	array(
			'<input type="submit" id="Submit" value="{$LANG.domainlookupbutton}"'	=>	'<input type="button" id="Submit" value="{$LANG.domainlookupbutton}" onclick="javascript: bulktransfer(this);"',
		),
		'skpit'	=>	array(
			'<td>{if $tldpricelist.renew}{$tldpricelist.renew}{else}{$LANG.domainregnotavailable}{/if}</td>'	=>	array(
				'{/if}'	=>	'{/if}</div>',
			),
		),
	),
	'cart/adddomain.tpl'			=>	array(
		'find'	=>	'javascript: domainsearchcart(this);',
		'after'	=>	array(
			'<input type="text" name="sld"'	=>	' onkeypress="return submitViaEnter(event)"',
		),
		'befor'	=>	array(
			'{if $availabilityresults}'	=>	'<div id="eppgr_div">'."\n",
			'<p align="right"><input type="button"'	=>	'</div>'."\n",
		),
		'chang'	=>	array(
			'<input type="submit" value="{$LANG.checkavailability}"'	=>	'<input type="button" value="{$LANG.checkavailability}" onclick="javascript: domainsearchcart(this);"',
		),
		'skpit'	=>	array(),
	),
	'default/adddomain.tpl'			=>	array(
		'find'	=>	'javascript: domainsearchcart(this);',
		'after'	=>	array(
			'<input type="text" name="sld"'	=>	' onkeypress="return submitViaEnter(event)"',
		),
		'befor'	=>	array(
			'{if $availabilityresults}'	=>	'<div id="eppgr_div">'."\n",
		),
		'chang'	=>	array(
			'<form method="post" action="{$smarty.server.PHP_SELF}?a=add">'	=> '<form method="post" action="{$smarty.server.PHP_SELF}?a=add&domain={if $domain eq "transfer"}transfer{else}register{/if}">',
			'<input type="radio" name="domain"'	=>	'<input type="radio" name="domainbutton" onclick="javascript: eppgrFixCart(this);"',
			'<input type="submit" value="{$LANG.checkavailability}"'	=>	'<input type="button" value="{$LANG.checkavailability}" onclick="javascript: domainsearchcart(this);"',
		),
		'skpit'	=>	array(
			'{if $availabilityresults}'	=>	array(
				'</td></tr></table>'	=>	'</div>'."\n".'</td></tr></table>',
			),
		),
	),
	'boxes/adddomain.tpl'			=>	array(
		'find'	=>	'javascript: domainsearchcart(this);',
		'after'	=>	array(
			'<input type="text" name="sld"'	=>	' onkeypress="return submitViaEnter(event)"',
		),
		'befor'	=>	array(
			'{if $availabilityresults}'	=>	'<div id="eppgr_div">'."\n",
			'<p align="right"><input type="button"'	=>	'</div>'."\n",
		),
		'chang'	=>	array(
			'<form method="post" action="{$smarty.server.PHP_SELF}?a=add">'	=> '<form method="post" action="{$smarty.server.PHP_SELF}?a=add&domain={if $domain eq "transfer"}transfer{else}register{/if}">',
			'<input type="radio" name="domain"'	=>	'<input type="radio" name="domainbutton" onclick="javascript: eppgrFixCart(this);"',
			'<input type="submit" value="{$LANG.ordercontinuebutton}"'	=>	'<input type="submit" value="{$LANG.ordercontinuebutton}" onclick="javascript: domainsearchcart(this);"',
		),
		'skpit'	=>	array(),
	),
	'singlepage/adddomain.tpl'		=>	array(
		'find'	=>	'javascript: domainsearchcart(this);',
		'after'	=>	array(
			'<input type="text" name="sld"'	=>	' onkeypress="return submitViaEnter(event)"',
		),
		'befor'	=>	array(
			'{if $availabilityresults}'	=>	'<div id="eppgr_div">'."\n",
		),
		'chang'	=>	array(
			'<input type="submit" value="{$LANG.checkavailability}"'	=>	'<input type="submit" value="{$LANG.checkavailability}" onclick="javascript: domainsearchcart(this);"',
		),
		'skpit'	=>	array(
			'<p align="center"><input type="submit" value="{$LANG.ordercontinuebutton}" /></p>'	=>	array(
				'</form>'	=>	'</div>'."\n".'</form>',
			),
		),
	),
	'web20cart/adddomain.tpl'		=>	array(
		'find'	=>	'javascript: domainsearchcart(this);',
		'after'	=>	array(
			'<input type="text" name="sld"'	=>	' onkeypress="return submitViaEnter(event)"',
		),
		'befor'	=>	array(
			'{if $availabilityresults}'	=>	'<div id="eppgr_div">'."\n",
		),
		'chang'	=>	array(
			'<input type="submit" value="{$LANG.checkavailability}"'	=>	'<input type="button" value="{$LANG.checkavailability}" onclick="javascript: domainsearchcart(this);"',
		),
		'skpit'	=>	array(
			'<input type="submit" value="{$LANG.addtocart}"'	=>	array(
				'<p align="center">'	=>	'</div>'."\n".'<p align="center">',
			),
		),
	),
);

require_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'patch.php');

?>