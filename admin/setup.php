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
 * \file    admin/setup.php
 * \ingroup dolicraftdashboard
 * \brief   DolicraftDashboard setup page
 */

// Load Dolibarr environment
$res = 0;
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

// Access control - admin only
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');

/*
 * Actions
 */

if ($action == 'update') {
	$error = 0;

	// Save period
	$period = GETPOST('DOLICRAFTDASHBOARD_PERIOD', 'alpha');
	if (!empty($period)) {
		dolibarr_set_const($db, 'DOLICRAFTDASHBOARD_PERIOD', $period, 'chaine', 0, '', $conf->entity);
	}

	// Save currency
	$currency = GETPOST('DOLICRAFTDASHBOARD_CURRENCY', 'alpha');
	if (!empty($currency)) {
		dolibarr_set_const($db, 'DOLICRAFTDASHBOARD_CURRENCY', $currency, 'chaine', 0, '', $conf->entity);
	}

	// Save replace default dashboard option
	$replaceDefault = GETPOST('DOLICRAFTDASHBOARD_REPLACE_DEFAULT', 'int') ? 1 : 0;
	dolibarr_set_const($db, 'DOLICRAFTDASHBOARD_REPLACE_DEFAULT', $replaceDefault, 'chaine', 0, '', $conf->entity);

	// Save widget toggles
	$widgets = array(
		'DOLICRAFTDASHBOARD_SHOW_REVENUE',
		'DOLICRAFTDASHBOARD_SHOW_INVOICES',
		'DOLICRAFTDASHBOARD_SHOW_PROPOSALS',
		'DOLICRAFTDASHBOARD_SHOW_ORDERS',
		'DOLICRAFTDASHBOARD_SHOW_CUSTOMERS',
		'DOLICRAFTDASHBOARD_SHOW_PRODUCTS',
		'DOLICRAFTDASHBOARD_SHOW_OVERDUE',
		'DOLICRAFTDASHBOARD_SHOW_TOPPRODUCTS',
		'DOLICRAFTDASHBOARD_SHOW_BANKBALANCE',
		'DOLICRAFTDASHBOARD_SHOW_AGENDA',
		'DOLICRAFTDASHBOARD_SHOW_SUPPLIERORDERS',
		'DOLICRAFTDASHBOARD_SHOW_LOWSTOCK',
		'DOLICRAFTDASHBOARD_SHOW_REVENUECHART',
	);

	foreach ($widgets as $widget) {
		$value = GETPOST($widget, 'int') ? 1 : 0;
		dolibarr_set_const($db, $widget, $value, 'chaine', 0, '', $conf->entity);
	}

	if (!$error) {
		setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
	} else {
		setEventMessages($langs->trans('Error'), null, 'errors');
	}
}

/*
 * View
 */

$page_name = 'DolicraftDashboardSetup';
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans('BackToModuleList').'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = dolicraftdashboardAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans('DolicraftDashboard'), -1, 'fa-chart-bar');

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

// General settings
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('Parameter').'</td>';
print '<td>'.$langs->trans('Value').'</td>';
print '</tr>';

// Replace default dashboard
$replaceDefault = getDolGlobalInt('DOLICRAFTDASHBOARD_REPLACE_DEFAULT', 0);
print '<tr class="oddeven">';
print '<td>';
print $langs->trans('DolicraftDashboardReplaceDefault');
print ' '.img_help(1, $langs->trans('DolicraftDashboardReplaceDefaultHelp'));
print '</td>';
print '<td>';
print '<input type="checkbox" name="DOLICRAFTDASHBOARD_REPLACE_DEFAULT" value="1"'.($replaceDefault ? ' checked' : '').' />';
print '</td></tr>';

// Default period
$currentPeriod = getDolGlobalString('DOLICRAFTDASHBOARD_PERIOD', 'current_month');
print '<tr class="oddeven">';
print '<td>'.$langs->trans('DolicraftDashboardDefaultPeriod').'</td>';
print '<td>';
print '<select name="DOLICRAFTDASHBOARD_PERIOD" class="flat minwidth200">';
$periods = array(
	'current_month'  => $langs->trans('CurrentMonth'),
	'current_year'   => $langs->trans('CurrentYear'),
	'last_30_days'   => $langs->trans('Last30Days'),
	'last_12_months' => $langs->trans('Last12Months'),
);
foreach ($periods as $key => $label) {
	print '<option value="'.$key.'"'.($currentPeriod == $key ? ' selected' : '').'>'.$label.'</option>';
}
print '</select>';
print '</td></tr>';

// Currency symbol
$currentCurrency = getDolGlobalString('DOLICRAFTDASHBOARD_CURRENCY', 'EUR');
print '<tr class="oddeven">';
print '<td>'.$langs->trans('DolicraftDashboardCurrency').'</td>';
print '<td>';
print '<input type="text" name="DOLICRAFTDASHBOARD_CURRENCY" value="'.dol_escape_htmltag($currentCurrency).'" class="flat minwidth100" />';
print '</td></tr>';

print '</table>';

print '<br>';

// Widget visibility settings
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('DolicraftDashboardWidgets').'</td>';
print '<td class="center">'.$langs->trans('Enabled').'</td>';
print '</tr>';

$widgets = array(
	'DOLICRAFTDASHBOARD_SHOW_REVENUE'        => 'DolicraftDashboardShowRevenue',
	'DOLICRAFTDASHBOARD_SHOW_INVOICES'       => 'DolicraftDashboardShowInvoices',
	'DOLICRAFTDASHBOARD_SHOW_PROPOSALS'      => 'DolicraftDashboardShowProposals',
	'DOLICRAFTDASHBOARD_SHOW_ORDERS'         => 'DolicraftDashboardShowOrders',
	'DOLICRAFTDASHBOARD_SHOW_CUSTOMERS'      => 'DolicraftDashboardShowCustomers',
	'DOLICRAFTDASHBOARD_SHOW_PRODUCTS'       => 'DolicraftDashboardShowProducts',
	'DOLICRAFTDASHBOARD_SHOW_OVERDUE'        => 'DolicraftDashboardShowOverdue',
	'DOLICRAFTDASHBOARD_SHOW_TOPPRODUCTS'    => 'DolicraftDashboardShowTopProducts',
	'DOLICRAFTDASHBOARD_SHOW_BANKBALANCE'    => 'DolicraftDashboardShowBankBalance',
	'DOLICRAFTDASHBOARD_SHOW_AGENDA'         => 'DolicraftDashboardShowAgenda',
	'DOLICRAFTDASHBOARD_SHOW_SUPPLIERORDERS' => 'DolicraftDashboardShowSupplierOrders',
	'DOLICRAFTDASHBOARD_SHOW_LOWSTOCK'       => 'DolicraftDashboardShowLowStock',
	'DOLICRAFTDASHBOARD_SHOW_REVENUECHART'   => 'DolicraftDashboardShowRevenueChart',
);

foreach ($widgets as $constname => $translabel) {
	$value = getDolGlobalInt($constname, 1);
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans($translabel).'</td>';
	print '<td class="center">';
	print '<input type="checkbox" name="'.$constname.'" value="1"'.($value ? ' checked' : '').' />';
	print '</td></tr>';
}

print '</table>';

print '<br>';

print '<div class="center">';
print '<input type="submit" class="button" value="'.$langs->trans('Save').'">';
print '</div>';

print '</form>';

print dol_get_fiche_end();

// Page end
llxFooter();
$db->close();
