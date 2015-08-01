<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */
if (php_sapi_name() != 'cli' || isset($_SERVER['REQUEST_METHOD']) || !isset($_SERVER['argc'])) {
	header("Status: 404 Not Found");
	header('HTTP/1.0 404 Not Found');
	$_SERVER['REDIRECT_STATUS'] = 404;
	echo "<h1>404 Not Found</h1>";
	echo "The page that you have requested could not be found.";
	exit();
}
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
if (isset($argv)) {
	foreach ($argv as $arg) {
		$argList = explode('=', $arg);
		if (isset($argList[0]) && isset($argList[1])) {
			$_GET[$argList[0]] = $argList[1];
		}
	}
}
if (init('id') == '') {
	throw new Exception(__('L\'id ne peut etre vide', __FILE__));
}
$camera = camera::byId(init('id'));
if (!is_object($camera)) {
	throw new Exception(__('L\'équipement est introuvable : ', __FILE__) . init('id'));
}
if ($camera->getEqType_name() != 'camera') {
	throw new Exception(__('Cet équipement n\'est pas de type camera : ', __FILE__) . $camera->getEqType_name());
}
$output_dir = calculPath(config::byKey('recordDir', 'camera'));
if (!file_exists($output_dir)) {
	if (!mkdir($output_dir, 0777, true)) {
		throw new Exception(__('Impossible de creer le dossier : ', __FILE__) . $output_dir);
	}
}
if (!is_writable($output_dir)) {
	throw new Exception(__('Impossible d\'écrire dans le dossier : ', __FILE__) . $output_dir);
}

$limit = 1800;
if (is_numeric(init('recordTime')) && init('recordTime') > 0 && init('recordTime') < 1800) {
	$limit = init('recordTime');
}
$continue = true;
$i = 0;
$recordState = $camera->getCmd(null, 'recordState');
$recordState->event(1);
$camera->refreshWidget();
while ($continue) {
	$i++;
	$camera->takeSnapshot();
	sleep(1);
	if ($i > $limit) {
		$continue = false;
	}
}
$recordState->event(0);
$camera->refreshWidget();
die();