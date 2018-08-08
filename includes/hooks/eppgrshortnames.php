<?php

function eppgr_processshortnames($vars) {
	require_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..'
		. DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
		. 'modules' . DIRECTORY_SEPARATOR . 'registrars' . DIRECTORY_SEPARATOR
		. 'eppgr' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR
		. 'eppgr.shortnames.php');
	return true;
}

add_hook("PreCalculateCartTotals",3,"eppgr_processshortnames");
