<?php

function eppgr_processdiscount($vars) {
	require_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..'
		. DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
		. 'modules' . DIRECTORY_SEPARATOR . 'registrars' . DIRECTORY_SEPARATOR
		. 'eppgr' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR
		. 'eppgr.discount.php');
	return true;
}

add_hook("PreCalculateCartTotals",2,"eppgr_processdiscount");