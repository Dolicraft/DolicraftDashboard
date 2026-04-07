<?php
/* Copyright (C) 2024-2026 Dolicraft <contact@dolicraft.com>
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
 * \file    lib/dolicraftdashboard.lib.php
 * \ingroup dolicraftdashboard
 * \brief   Library files with common functions for DolicraftDashboard
 */

/**
 * Prepare admin pages header
 *
 * @return array Array of head tabs
 */
function dolicraftdashboardAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load('dolicraftdashboard@dolicraftdashboard');

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/dolicraftdashboard/index.php', 1);
	$head[$h][1] = '<i class="fas fa-chart-bar pictofixedwidth"></i>'.$langs->trans('Dashboard');
	$head[$h][2] = 'dashboard';
	$h++;

	$head[$h][0] = dol_buildpath('/dolicraftdashboard/admin/setup.php', 1);
	$head[$h][1] = '<i class="fas fa-cog pictofixedwidth"></i>'.$langs->trans('Configuration');
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath('/dolicraftdashboard/admin/about.php', 1);
	$head[$h][1] = '<i class="fas fa-info-circle pictofixedwidth"></i>'.$langs->trans('About');
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'dolicraftdashboard@dolicraftdashboard');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'dolicraftdashboard@dolicraftdashboard', 'remove');

	return $head;
}
