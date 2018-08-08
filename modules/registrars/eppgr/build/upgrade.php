<?php
/*
 *  File: build/upgrade.php
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

try {$db = new PDO('mysql:host='.$db_host.';dbname='.$db_name.';charset=utf8', $db_username, $db_password);}
catch(PDOException $ex) {die('Could not connect to database: '.$ex->getMessage());}

$query = "DROP TABLE IF EXISTS `tbleppgrconfig`";
try {$stmt = $db->query($query);}
catch(PDOException $ex) {die('MySQL error: '.$ex->getMessage());}

$query = "DROP TABLE IF EXISTS `tbleppgrregistrants`";
try {$stmt = $db->query($query);}
catch(PDOException $ex) {die('MySQL error: '.$ex->getMessage());}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="el" xml:lang="el">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title>Αναβάθμιση εφαρμογής EPPGR for WHMCS</title>
</head>
<body style="text-align: center;">
Η απαραίτητες παρεμβάσεις στη βάση δεδομένων σας ολοκληρώθηκαν.<br />
Παρακαλώ, χρησιμοποιήστε την περιοχή διαχείρισης του WHMCS για να επισκεφθείτε τη σελίδα επαφών ενός ονόματος χώρου
με κατάληξη .gr/.ελ προκειμένου να ολοκληρωθεί η αναβάθμιση.

Καλές κατοχυρώσεις!
</body>
</html>