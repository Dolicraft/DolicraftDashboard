<?php
/* Copyright (C) 2024-2026 Dolicraft
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    ajax/widget_prefs.php
 * \brief   AJAX endpoint for DolicraftDashboard widget preferences (order, visibility, reset)
 */

define('NOTOKENRENEWAL', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);
define('NOREQUIREAJAX', 1);

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	http_response_code(500);
	echo json_encode(array('error' => 'Include of main fails'));
	exit;
}

// Security check
if (!$user->hasRight('dolicraftdashboard', 'read')) {
	http_response_code(403);
	header('Content-Type: application/json');
	echo json_encode(array('error' => 'Access denied'));
	exit;
}

header('Content-Type: application/json');

$action = GETPOST('action', 'aZ09');

/*
 * Actions
 */

if ($action == 'save_order') {
	$widgetsJson = GETPOST('widgets', 'none');
	$widgets = json_decode($widgetsJson, true);

	if (!is_array($widgets)) {
		echo json_encode(array('error' => 'Invalid widgets data'));
		exit;
	}

	$db->begin();

	$error = 0;
	foreach ($widgets as $w) {
		$key = $db->escape($w['key']);
		$position = (int) $w['position'];
		$visible = (int) $w['visible'];
		$fk_user = (int) $user->id;
		$entity = (int) $conf->entity;

		$sql = "REPLACE INTO ".MAIN_DB_PREFIX."dolicraftdashboard_user_prefs";
		$sql .= " (fk_user, widget_key, position, is_visible, entity)";
		$sql .= " VALUES (".$fk_user.", '".$key."', ".$position.", ".$visible.", ".$entity.")";

		$resql = $db->query($sql);
		if (!$resql) {
			$error++;
			break;
		}
	}

	if ($error) {
		$db->rollback();
		echo json_encode(array('error' => 'Database error'));
	} else {
		$db->commit();
		echo json_encode(array('success' => true));
	}
} elseif ($action == 'toggle_widget') {
	$widgetKey = GETPOST('widget_key', 'alphanohtml');
	$visible = GETPOST('visible', 'int');

	if (empty($widgetKey)) {
		echo json_encode(array('error' => 'Missing widget_key'));
		exit;
	}

	$fk_user = (int) $user->id;
	$entity = (int) $conf->entity;
	$widgetKeyEsc = $db->escape($widgetKey);

	// Get current position, default to 999 if not found
	$position = 999;
	$sql = "SELECT position FROM ".MAIN_DB_PREFIX."dolicraftdashboard_user_prefs";
	$sql .= " WHERE fk_user = ".$fk_user;
	$sql .= " AND widget_key = '".$widgetKeyEsc."'";
	$sql .= " AND entity = ".$entity;
	$resql = $db->query($sql);
	if ($resql && $db->num_rows($resql) > 0) {
		$obj = $db->fetch_object($resql);
		$position = (int) $obj->position;
	}

	$sql = "REPLACE INTO ".MAIN_DB_PREFIX."dolicraftdashboard_user_prefs";
	$sql .= " (fk_user, widget_key, position, is_visible, entity)";
	$sql .= " VALUES (".$fk_user.", '".$widgetKeyEsc."', ".$position.", ".((int) $visible).", ".$entity.")";

	$resql = $db->query($sql);
	if ($resql) {
		echo json_encode(array('success' => true));
	} else {
		echo json_encode(array('error' => 'Database error'));
	}
} elseif ($action == 'reset') {
	$fk_user = (int) $user->id;
	$entity = (int) $conf->entity;

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."dolicraftdashboard_user_prefs";
	$sql .= " WHERE fk_user = ".$fk_user." AND entity = ".$entity;

	$resql = $db->query($sql);
	if ($resql) {
		echo json_encode(array('success' => true));
	} else {
		echo json_encode(array('error' => 'Database error'));
	}
} elseif ($action == 'get_prefs') {
	$fk_user = (int) $user->id;
	$entity = (int) $conf->entity;

	$sql = "SELECT widget_key, position, is_visible FROM ".MAIN_DB_PREFIX."dolicraftdashboard_user_prefs";
	$sql .= " WHERE fk_user = ".$fk_user." AND entity = ".$entity;
	$sql .= " ORDER BY position ASC";

	$resql = $db->query($sql);
	$prefs = array();
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$prefs[] = array(
				'key' => $obj->widget_key,
				'position' => (int) $obj->position,
				'visible' => (int) $obj->is_visible
			);
		}
		echo json_encode(array('success' => true, 'prefs' => $prefs));
	} else {
		echo json_encode(array('error' => 'Database error'));
	}
} else {
	echo json_encode(array('error' => 'Unknown action'));
}
