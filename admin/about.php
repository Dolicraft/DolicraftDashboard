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
 * \file    admin/about.php
 * \ingroup dolicraftdashboard
 * \brief   About page for module DolicraftDashboard
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php using relative path
if (!$res && file_exists('../main.inc.php')) {
	$res = @include '../main.inc.php';
}
if (!$res && file_exists('../../main.inc.php')) {
	$res = @include '../../main.inc.php';
}
if (!$res && file_exists('../../../main.inc.php')) {
	$res = @include '../../../main.inc.php';
}
if (!$res) {
	die('Include of main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/dolicraftdashboard/lib/dolicraftdashboard.lib.php');

// Translations
$langs->loadLangs(array('admin', 'dolicraftdashboard@dolicraftdashboard'));

// Access control
if (!$user->admin) {
	accessforbidden();
}

/*
 * View
 */

$page_name = 'DolicraftDashboardAbout';
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans('BackToModuleList').'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = dolicraftdashboardAdminPrepareHead();
print dol_get_fiche_head($head, 'about', $langs->trans('DolicraftDashboard'), -1, 'fa-chart-bar');

// About content
print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent tableforfield">';

print '<tr class="liste_titre"><td colspan="2"><strong>'.$langs->trans('ModuleInfo').'</strong></td></tr>';

print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('ModuleName').'</td>';
print '<td><strong>DolicraftDashboard</strong></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans('Version').'</td>';
print '<td><span class="badge badge-status4 badge-status">1.0.0</span></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans('License').'</td>';
print '<td><a href="https://www.gnu.org/licenses/gpl-3.0.html" target="_blank" rel="noopener noreferrer">GPL v3+</a></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans('Price').'</td>';
print '<td><span class="badge badge-status4 badge-status">'.$langs->trans('Free').'</span></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans('Author').'</td>';
print '<td><a href="https://dolicraft.com" target="_blank" rel="noopener noreferrer">Dolicraft</a></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans('Contact').'</td>';
print '<td><a href="mailto:contact@dolicraft.com">contact@dolicraft.com</a></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans('Description').'</td>';
print '<td>'.$langs->trans('DolicraftDashboardAboutDesc').'</td></tr>';

print '</table>';

print '<br>';

// Features section
print '<table class="border centpercent tableforfield">';
print '<tr class="liste_titre"><td colspan="2"><strong>'.$langs->trans('Features').'</strong></td></tr>';

$features = array(
	'Real-time KPI monitoring',
	'Revenue and sales tracking',
	'Invoice and proposal analytics',
	'Customer growth metrics',
	'Product performance overview',
	'Responsive dashboard layout',
	'Configurable widgets',
	'Period-based filtering',
);

foreach ($features as $feature) {
	print '<tr class="oddeven"><td class="titlefield"><i class="fas fa-check-circle" style="color: #28a745;"></i></td>';
	print '<td>'.$feature.'</td></tr>';
}

print '</table>';

print '</div>';

print dol_get_fiche_end();

// Page end
llxFooter();
$db->close();
