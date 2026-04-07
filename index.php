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
 * \file    index.php
 * \ingroup dolicraftdashboard
 * \brief   Main dashboard page for DolicraftDashboard module
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists('main.inc.php')) {
	$res = @include 'main.inc.php';
}
if (!$res && file_exists('../main.inc.php')) {
	$res = @include '../main.inc.php';
}
if (!$res && file_exists('../../main.inc.php')) {
	$res = @include '../../main.inc.php';
}
if (!$res) {
	die('Include of main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
dol_include_once('/dolicraftdashboard/lib/dolicraftdashboard.lib.php');

// Translations
$langs->loadLangs(array('main', 'bills', 'orders', 'propal', 'companies', 'products', 'dolicraftdashboard@dolicraftdashboard'));

// Access control
if (!$user->hasRight('dolicraftdashboard', 'read')) {
	accessforbidden();
}

// Parameters
$period = GETPOST('period', 'alpha');
if (empty($period)) {
	$period = getDolGlobalString('DOLICRAFTDASHBOARD_PERIOD', 'current_month');
}

/*
 * Calculate period dates
 */
$now = dol_now();
$nowyear = (int) dol_print_date($now, '%Y');
$nowmonth = (int) dol_print_date($now, '%m');
$nowday = (int) dol_print_date($now, '%d');

switch ($period) {
	case 'current_month':
		$date_start = dol_mktime(0, 0, 0, $nowmonth, 1, $nowyear);
		$date_end = dol_mktime(23, 59, 59, $nowmonth, (int) date('t', dol_mktime(0, 0, 0, $nowmonth, 1, $nowyear)), $nowyear);
		// Previous period: previous month
		$prev_start = dol_time_plus_duree($date_start, -1, 'm');
		$prev_end = dol_time_plus_duree($date_start, 0, 's') - 1;
		$period_label = $langs->trans('CurrentMonth');
		break;

	case 'current_year':
		$date_start = dol_mktime(0, 0, 0, 1, 1, $nowyear);
		$date_end = dol_mktime(23, 59, 59, 12, 31, $nowyear);
		// Previous period: previous year
		$prev_start = dol_mktime(0, 0, 0, 1, 1, $nowyear - 1);
		$prev_end = dol_mktime(23, 59, 59, 12, 31, $nowyear - 1);
		$period_label = $langs->trans('CurrentYear');
		break;

	case 'last_30_days':
		$date_end = dol_mktime(23, 59, 59, $nowmonth, $nowday, $nowyear);
		$date_start = dol_time_plus_duree($date_end, -30, 'd');
		// Previous period: 30 days before that
		$prev_end = $date_start - 1;
		$prev_start = dol_time_plus_duree($prev_end, -30, 'd');
		$period_label = $langs->trans('Last30Days');
		break;

	case 'last_12_months':
		$date_end = dol_mktime(23, 59, 59, $nowmonth, $nowday, $nowyear);
		$date_start = dol_time_plus_duree($date_end, -12, 'm');
		// Previous period: 12 months before that
		$prev_end = $date_start - 1;
		$prev_start = dol_time_plus_duree($prev_end, -12, 'm');
		$period_label = $langs->trans('Last12Months');
		break;

	default:
		$date_start = dol_mktime(0, 0, 0, $nowmonth, 1, $nowyear);
		$date_end = dol_mktime(23, 59, 59, $nowmonth, (int) date('t', dol_mktime(0, 0, 0, $nowmonth, 1, $nowyear)), $nowyear);
		$prev_start = dol_time_plus_duree($date_start, -1, 'm');
		$prev_end = $date_start - 1;
		$period_label = $langs->trans('CurrentMonth');
		break;
}

$date_start_sql = $db->idate($date_start);
$date_end_sql = $db->idate($date_end);
$prev_start_sql = $db->idate($prev_start);
$prev_end_sql = $db->idate($prev_end);


/*
 * Fetch KPI data
 */

// ---- Card 1: Revenue (Chiffre d'affaires) ----
$revenue_current = 0;
$revenue_previous = 0;

if (getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_REVENUE', 1)) {
	$sql = "SELECT SUM(f.total_ttc) as total";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql .= " WHERE f.datef BETWEEN '".$db->escape($date_start_sql)."' AND '".$db->escape($date_end_sql)."'";
	$sql .= " AND f.paye = 1 AND f.entity = ".((int) $conf->entity)." AND f.type = 0";
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$revenue_current = (float) ($obj->total ?? 0);
		$db->free($resql);
	}

	$sql = "SELECT SUM(f.total_ttc) as total";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql .= " WHERE f.datef BETWEEN '".$db->escape($prev_start_sql)."' AND '".$db->escape($prev_end_sql)."'";
	$sql .= " AND f.paye = 1 AND f.entity = ".((int) $conf->entity)." AND f.type = 0";
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$revenue_previous = (float) ($obj->total ?? 0);
		$db->free($resql);
	}
}

// ---- Card 2: Outstanding invoices (Factures impayees) ----
$unpaid_count = 0;
$unpaid_total = 0;

if (getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_INVOICES', 1)) {
	$sql = "SELECT COUNT(f.rowid) as nb, SUM(f.total_ttc) as total";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql .= " WHERE f.paye = 0 AND f.fk_statut = 1";
	$sql .= " AND f.entity = ".((int) $conf->entity)." AND f.type = 0";
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$unpaid_count = (int) ($obj->nb ?? 0);
		$unpaid_total = (float) ($obj->total ?? 0);
		$db->free($resql);
	}
}

// ---- Card 3: Proposals (Devis en cours) ----
$propal_count = 0;
$propal_total = 0;

if (getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_PROPOSALS', 1)) {
	$sql = "SELECT COUNT(p.rowid) as nb, SUM(p.total_ttc) as total";
	$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
	$sql .= " WHERE p.fk_statut = 1";
	$sql .= " AND p.entity = ".((int) $conf->entity);
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$propal_count = (int) ($obj->nb ?? 0);
		$propal_total = (float) ($obj->total ?? 0);
		$db->free($resql);
	}
}

// ---- Card 4: Orders (Commandes en cours) ----
$order_count = 0;
$order_total = 0;

if (getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_ORDERS', 1)) {
	$sql = "SELECT COUNT(c.rowid) as nb, SUM(c.total_ttc) as total";
	$sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
	$sql .= " WHERE c.fk_statut IN (1, 2)";
	$sql .= " AND c.entity = ".((int) $conf->entity);
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$order_count = (int) ($obj->nb ?? 0);
		$order_total = (float) ($obj->total ?? 0);
		$db->free($resql);
	}
}

// ---- Card 5: New customers (Nouveaux clients) ----
$new_customers_current = 0;
$new_customers_previous = 0;

if (getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_CUSTOMERS', 1)) {
	$sql = "SELECT COUNT(s.rowid) as nb";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql .= " WHERE s.client IN (1, 3)";
	$sql .= " AND s.datec BETWEEN '".$db->escape($date_start_sql)."' AND '".$db->escape($date_end_sql)."'";
	$sql .= " AND s.entity = ".((int) $conf->entity);
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$new_customers_current = (int) ($obj->nb ?? 0);
		$db->free($resql);
	}

	$sql = "SELECT COUNT(s.rowid) as nb";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql .= " WHERE s.client IN (1, 3)";
	$sql .= " AND s.datec BETWEEN '".$db->escape($prev_start_sql)."' AND '".$db->escape($prev_end_sql)."'";
	$sql .= " AND s.entity = ".((int) $conf->entity);
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$new_customers_previous = (int) ($obj->nb ?? 0);
		$db->free($resql);
	}
}

// ---- Card 6: Products sold (Produits vendus) ----
$products_sold = 0;

if (getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_PRODUCTS', 1)) {
	$sql = "SELECT SUM(fd.qty) as total_qty";
	$sql .= " FROM ".MAIN_DB_PREFIX."facturedet as fd";
	$sql .= " JOIN ".MAIN_DB_PREFIX."facture as f ON fd.fk_facture = f.rowid";
	$sql .= " WHERE f.datef BETWEEN '".$db->escape($date_start_sql)."' AND '".$db->escape($date_end_sql)."'";
	$sql .= " AND f.entity = ".((int) $conf->entity)." AND f.type = 0 AND f.fk_statut > 0";
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$products_sold = (float) ($obj->total_qty ?? 0);
		$db->free($resql);
	}
}

// ---- NEW: Overdue invoices ----
$overdue_count = 0;
$overdue_total = 0;

if (getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_OVERDUE', 1)) {
	$sql = "SELECT COUNT(f.rowid) as nb, SUM(f.total_ttc) as total";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql .= " WHERE f.paye = 0 AND f.fk_statut = 1 AND f.type = 0";
	$sql .= " AND f.date_lim_reglement < '".$db->idate($now)."'";
	$sql .= " AND f.entity = ".((int) $conf->entity);
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$overdue_count = (int) ($obj->nb ?? 0);
		$overdue_total = (float) ($obj->total ?? 0);
		$db->free($resql);
	}
}

// ---- NEW: Top 5 products sold ----
$top_products = array();

if (getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_TOPPRODUCTS', 1)) {
	$sql = "SELECT p.rowid, p.ref, p.label, SUM(fd.qty) as qty_sold, SUM(fd.total_ttc) as revenue";
	$sql .= " FROM ".MAIN_DB_PREFIX."facturedet as fd";
	$sql .= " JOIN ".MAIN_DB_PREFIX."facture as f ON fd.fk_facture = f.rowid";
	$sql .= " JOIN ".MAIN_DB_PREFIX."product as p ON fd.fk_product = p.rowid";
	$sql .= " WHERE f.datef BETWEEN '".$db->escape($date_start_sql)."' AND '".$db->escape($date_end_sql)."'";
	$sql .= " AND f.entity = ".((int) $conf->entity)." AND f.type = 0 AND f.fk_statut > 0";
	$sql .= " AND fd.fk_product > 0";
	$sql .= " GROUP BY p.rowid, p.ref, p.label";
	$sql .= " ORDER BY revenue DESC";
	$sql .= " LIMIT 5";
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$top_products[] = $obj;
		}
		$db->free($resql);
	}
}

// ---- NEW: Bank balance ----
$bank_accounts = array();
$bank_total = 0;

if (getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_BANKBALANCE', 1)) {
	$sql = "SELECT ba.rowid, ba.label, ba.number, ba.currency_code,";
	$sql .= " (SELECT SUM(bl.amount) FROM ".MAIN_DB_PREFIX."bank as bl WHERE bl.fk_account = ba.rowid) as balance";
	$sql .= " FROM ".MAIN_DB_PREFIX."bank_account as ba";
	$sql .= " WHERE ba.entity = ".((int) $conf->entity);
	$sql .= " AND ba.clos = 0";
	$sql .= " ORDER BY ba.label";
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$bank_accounts[] = $obj;
			$bank_total += (float) ($obj->balance ?? 0);
		}
		$db->free($resql);
	}
}

// ---- NEW: Today's agenda ----
$today_events = array();

if (getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_AGENDA', 1)) {
	$today_start = dol_mktime(0, 0, 0, $nowmonth, $nowday, $nowyear);
	$today_end = dol_mktime(23, 59, 59, $nowmonth, $nowday, $nowyear);
	$sql = "SELECT a.id, a.label, a.datep, a.datef, a.percent";
	$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
	$sql .= " WHERE a.entity = ".((int) $conf->entity);
	$sql .= " AND a.datep BETWEEN '".$db->escape($db->idate($today_start))."' AND '".$db->escape($db->idate($today_end))."'";
	$sql .= " ORDER BY a.datep ASC";
	$sql .= " LIMIT 10";
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$today_events[] = $obj;
		}
		$db->free($resql);
	}
}

// ---- NEW: Supplier orders pending ----
$supplier_orders_count = 0;
$supplier_orders_total = 0;

if (getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_SUPPLIERORDERS', 1)) {
	$sql = "SELECT COUNT(cf.rowid) as nb, SUM(cf.total_ttc) as total";
	$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as cf";
	$sql .= " WHERE cf.fk_statut IN (1, 2, 3)";
	$sql .= " AND cf.entity = ".((int) $conf->entity);
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$supplier_orders_count = (int) ($obj->nb ?? 0);
		$supplier_orders_total = (float) ($obj->total ?? 0);
		$db->free($resql);
	}
}

// ---- NEW: Low stock products ----
$low_stock_products = array();

if (getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_LOWSTOCK', 1)) {
	$sql = "SELECT p.rowid, p.ref, p.label, p.seuil_stock_alerte, p.stock as stock_reel";
	$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
	$sql .= " WHERE p.entity = ".((int) $conf->entity);
	$sql .= " AND p.seuil_stock_alerte > 0";
	$sql .= " AND p.stock <= p.seuil_stock_alerte";
	$sql .= " AND p.fk_product_type = 0";
	$sql .= " ORDER BY (p.stock - p.seuil_stock_alerte) ASC";
	$sql .= " LIMIT 10";
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$low_stock_products[] = $obj;
		}
		$db->free($resql);
	}
}

// ---- Section 2: Revenue chart - Last 12 months ----
$chart_data = array();
$chart_max = 0;

$sql = "SELECT YEAR(f.datef) as y, MONTH(f.datef) as m, SUM(f.total_ttc) as total";
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql .= " WHERE f.type = 0 AND f.fk_statut > 0 AND f.paye = 1";
$sql .= " AND f.entity = ".((int) $conf->entity);
$sql .= " AND f.datef >= '".$db->escape($db->idate(dol_time_plus_duree($now, -12, 'm')))."'";
$sql .= " GROUP BY YEAR(f.datef), MONTH(f.datef)";
$sql .= " ORDER BY y ASC, m ASC";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$month_key = sprintf('%04d-%02d', $obj->y, $obj->m);
		$chart_data[$month_key] = (float) $obj->total;
		if ((float) $obj->total > $chart_max) {
			$chart_max = (float) $obj->total;
		}
	}
	$db->free($resql);
}

// Fill missing months with 0
$chart_months = array();
for ($i = 11; $i >= 0; $i--) {
	$ts = dol_time_plus_duree($now, -$i, 'm');
	$key = dol_print_date($ts, '%Y-%m');
	$chart_months[$key] = isset($chart_data[$key]) ? $chart_data[$key] : 0;
	if ($chart_months[$key] > $chart_max) {
		$chart_max = $chart_months[$key];
	}
}

// ---- Section 3 Left: Top 10 clients by revenue ----
$top_clients = array();
$top_clients_total = 0;

$sql = "SELECT s.rowid, s.nom, SUM(f.total_ttc) as revenue";
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql .= " JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
$sql .= " WHERE f.type = 0 AND f.fk_statut > 0 AND f.paye = 1";
$sql .= " AND f.entity = ".((int) $conf->entity);
$sql .= " AND f.datef BETWEEN '".$db->escape($date_start_sql)."' AND '".$db->escape($date_end_sql)."'";
$sql .= " GROUP BY s.rowid, s.nom";
$sql .= " ORDER BY revenue DESC";
$sql .= " LIMIT 10";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$top_clients[] = $obj;
		$top_clients_total += (float) $obj->revenue;
	}
	$db->free($resql);
}

// ---- Section 3 Right: Latest invoices ----
$latest_invoices = array();

$sql = "SELECT f.rowid, f.ref, f.total_ttc, f.datef, f.fk_statut, f.paye, s.nom as socname, s.rowid as socid";
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
$sql .= " WHERE f.entity = ".((int) $conf->entity)." AND f.type = 0";
$sql .= " ORDER BY f.datec DESC";
$sql .= " LIMIT 10";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$latest_invoices[] = $obj;
	}
	$db->free($resql);
}

// ---- Section 3 Right: Latest proposals ----
$latest_proposals = array();

$sql = "SELECT p.rowid, p.ref, p.total_ttc, p.datep, p.fk_statut, s.nom as socname, s.rowid as socid";
$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
$sql .= " WHERE p.entity = ".((int) $conf->entity);
$sql .= " ORDER BY p.datec DESC";
$sql .= " LIMIT 10";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$latest_proposals[] = $obj;
	}
	$db->free($resql);
}

// ---- Section 4: Quick stats ----

// Conversion rate: proposals signed / total proposals
$propal_signed = 0;
$propal_all = 0;
$sql = "SELECT COUNT(p.rowid) as nb FROM ".MAIN_DB_PREFIX."propal as p WHERE p.entity = ".((int) $conf->entity);
$resql = $db->query($sql);
if ($resql) {
	$obj = $db->fetch_object($resql);
	$propal_all = (int) ($obj->nb ?? 0);
	$db->free($resql);
}

$sql = "SELECT COUNT(p.rowid) as nb FROM ".MAIN_DB_PREFIX."propal as p WHERE p.fk_statut = 2 AND p.entity = ".((int) $conf->entity);
$resql = $db->query($sql);
if ($resql) {
	$obj = $db->fetch_object($resql);
	$propal_signed = (int) ($obj->nb ?? 0);
	$db->free($resql);
}

$conversion_rate = ($propal_all > 0) ? round(($propal_signed / $propal_all) * 100, 1) : 0;

// Average invoice amount
$avg_invoice = 0;
$sql = "SELECT AVG(f.total_ttc) as avg_amount FROM ".MAIN_DB_PREFIX."facture as f";
$sql .= " WHERE f.type = 0 AND f.fk_statut > 0 AND f.entity = ".((int) $conf->entity);
$resql = $db->query($sql);
if ($resql) {
	$obj = $db->fetch_object($resql);
	$avg_invoice = (float) ($obj->avg_amount ?? 0);
	$db->free($resql);
}

// Average payment delay (days between datef and payment date)
$avg_payment_delay = 0;
$sql = "SELECT AVG(DATEDIFF(pf.datep, f.datef)) as avg_delay";
$sql .= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf";
$sql .= " JOIN ".MAIN_DB_PREFIX."facture as f ON pf.fk_facture = f.rowid";
$sql .= " JOIN ".MAIN_DB_PREFIX."paiement as p ON pf.fk_paiement = p.rowid";
$sql .= " WHERE f.entity = ".((int) $conf->entity)." AND f.type = 0";
$resql = $db->query($sql);
if ($resql) {
	$obj = $db->fetch_object($resql);
	$avg_payment_delay = round((float) ($obj->avg_delay ?? 0), 1);
	$db->free($resql);
}

// Total clients
$total_clients = 0;
$sql = "SELECT COUNT(s.rowid) as nb FROM ".MAIN_DB_PREFIX."societe as s WHERE s.client IN (1,3) AND s.entity = ".((int) $conf->entity);
$resql = $db->query($sql);
if ($resql) {
	$obj = $db->fetch_object($resql);
	$total_clients = (int) ($obj->nb ?? 0);
	$db->free($resql);
}

// Total products
$total_products = 0;
$sql = "SELECT COUNT(p.rowid) as nb FROM ".MAIN_DB_PREFIX."product as p WHERE p.entity = ".((int) $conf->entity);
$resql = $db->query($sql);
if ($resql) {
	$obj = $db->fetch_object($resql);
	$total_products = (int) ($obj->nb ?? 0);
	$db->free($resql);
}

// Total active proposals
$active_proposals = 0;
$sql = "SELECT COUNT(p.rowid) as nb FROM ".MAIN_DB_PREFIX."propal as p WHERE p.fk_statut = 1 AND p.entity = ".((int) $conf->entity);
$resql = $db->query($sql);
if ($resql) {
	$obj = $db->fetch_object($resql);
	$active_proposals = (int) ($obj->nb ?? 0);
	$db->free($resql);
}


/*
 * Helper functions
 */

/**
 * Compute trend percentage between current and previous value
 *
 * @param  float $current  Current period value
 * @param  float $previous Previous period value
 * @return array Array with 'pct' (float), 'direction' (up/down/neutral), 'class' (CSS class)
 */
function dolicraftdashboard_trend($current, $previous)
{
	if ($previous == 0 && $current == 0) {
		return array('pct' => 0, 'direction' => 'neutral', 'class' => 'dolicraftdashboard-trend-neutral');
	}
	if ($previous == 0) {
		return array('pct' => 100, 'direction' => 'up', 'class' => 'dolicraftdashboard-trend-up');
	}

	$pct = round((($current - $previous) / abs($previous)) * 100, 1);

	if ($pct > 0) {
		return array('pct' => $pct, 'direction' => 'up', 'class' => 'dolicraftdashboard-trend-up');
	} elseif ($pct < 0) {
		return array('pct' => abs($pct), 'direction' => 'down', 'class' => 'dolicraftdashboard-trend-down');
	}

	return array('pct' => 0, 'direction' => 'neutral', 'class' => 'dolicraftdashboard-trend-neutral');
}

/**
 * Get status badge HTML for invoice status
 *
 * @param  int    $status Invoice status
 * @param  int    $paye   Payment flag
 * @param  object $langs  Langs object
 * @return string         HTML badge
 */
function dolicraftdashboard_invoice_badge($status, $paye, $langs)
{
	if ($paye == 1) {
		return '<span class="badge badge-status4">'.$langs->trans('BillStatusPaid').'</span>';
	}
	switch ((int) $status) {
		case 0:
			return '<span class="badge badge-status0">'.$langs->trans('BillStatusDraft').'</span>';
		case 1:
			return '<span class="badge badge-status1">'.$langs->trans('BillStatusNotPaid').'</span>';
		case 2:
			return '<span class="badge badge-status6">'.$langs->trans('BillStatusStarted').'</span>';
		case 3:
			return '<span class="badge badge-status8">'.$langs->trans('BillStatusCanceled').'</span>';
		default:
			return '<span class="badge badge-status0">-</span>';
	}
}

/**
 * Get status badge HTML for proposal status
 *
 * @param  int    $status Proposal status
 * @param  object $langs  Langs object
 * @return string         HTML badge
 */
function dolicraftdashboard_propal_badge($status, $langs)
{
	switch ((int) $status) {
		case 0:
			return '<span class="badge badge-status0">'.$langs->trans('PropalStatusDraft').'</span>';
		case 1:
			return '<span class="badge badge-status1">'.$langs->trans('PropalStatusValidated').'</span>';
		case 2:
			return '<span class="badge badge-status4">'.$langs->trans('PropalStatusSigned').'</span>';
		case 3:
			return '<span class="badge badge-status8">'.$langs->trans('PropalStatusNotSigned').'</span>';
		case 4:
			return '<span class="badge badge-status6">'.$langs->trans('PropalStatusBilled').'</span>';
		default:
			return '<span class="badge badge-status0">-</span>';
	}
}


/*
 * View
 */

$currency = getDolGlobalString('DOLICRAFTDASHBOARD_CURRENCY', $conf->currency);

llxHeader('', $langs->trans('DolicraftDashboard'));

print load_fiche_titre($langs->trans('DolicraftDashboard'), '', 'title_accountancy');

$head = dolicraftdashboardAdminPrepareHead();
print dol_get_fiche_head($head, 'dashboard', $langs->trans('DolicraftDashboard'), -1, 'dolicraftdashboard@dolicraftdashboard');

// Period selector
print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="3"><span class="fas fa-calendar pictofixedwidth"></span>'.$langs->trans('Period').'</td></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('Period').'</td><td>';
print '<select name="period" class="flat minwidth200" onchange="this.form.submit();">';
$periods = array(
	'current_month'  => $langs->trans('CurrentMonth'),
	'current_year'   => $langs->trans('CurrentYear'),
	'last_30_days'   => $langs->trans('Last30Days'),
	'last_12_months' => $langs->trans('Last12Months'),
);
foreach ($periods as $key => $label) {
	print '<option value="'.$key.'"'.($period == $key ? ' selected' : '').'>'.$label.'</option>';
}
print '</select>';
print '</td><td class="opacitymedium">'.dol_print_date($date_start, 'day').' - '.dol_print_date($date_end, 'day').'</td></tr>';
print '</table>';
print '</form>';

print '<br>';

// =============================================
// Load user widget preferences
// =============================================
$userPrefs = array();
$sql = "SELECT widget_key, position, is_visible FROM ".MAIN_DB_PREFIX."dolicraftdashboard_user_prefs";
$sql .= " WHERE fk_user = ".((int) $user->id)." AND entity = ".((int) $conf->entity);
$sql .= " ORDER BY position ASC";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$userPrefs[$obj->widget_key] = array('position' => (int) $obj->position, 'visible' => (int) $obj->is_visible);
	}
	$db->free($resql);
}

// =============================================
// Widget registry with default order
// =============================================
$widgetRegistry = array(
	// KPI widgets (first row)
	'revenue' => array('type' => 'kpi', 'default_pos' => 0, 'label' => 'Turnover'),
	'unpaid' => array('type' => 'kpi', 'default_pos' => 1, 'label' => 'BillsCustomersUnpaid'),
	'proposals' => array('type' => 'kpi', 'default_pos' => 2, 'label' => 'ProposalsOpened'),
	'orders' => array('type' => 'kpi', 'default_pos' => 3, 'label' => 'OrdersToProcess'),
	// KPI widgets (second row)
	'new_customers' => array('type' => 'kpi', 'default_pos' => 4, 'label' => 'NewCustomers'),
	'products_sold' => array('type' => 'kpi', 'default_pos' => 5, 'label' => 'ProductsSold'),
	'conversion' => array('type' => 'kpi', 'default_pos' => 6, 'label' => 'ConversionRate'),
	'payment_delay' => array('type' => 'kpi', 'default_pos' => 7, 'label' => 'AveragePaymentDelay'),
	// Additional KPIs
	'overdue' => array('type' => 'kpi', 'default_pos' => 8, 'label' => 'OverdueInvoices'),
	'bank_balance' => array('type' => 'kpi', 'default_pos' => 9, 'label' => 'BankBalance'),
	'supplier_orders' => array('type' => 'kpi', 'default_pos' => 10, 'label' => 'SupplierOrdersPending'),
	// Chart
	'revenue_chart' => array('type' => 'block', 'default_pos' => 11, 'label' => 'RevenueEvolution'),
	// Tables
	'top_clients' => array('type' => 'block', 'default_pos' => 12, 'label' => 'TopCustomers'),
	'latest_invoices' => array('type' => 'block', 'default_pos' => 13, 'label' => 'LatestInvoices'),
	'latest_proposals' => array('type' => 'block', 'default_pos' => 14, 'label' => 'LatestProposals'),
	'top_products' => array('type' => 'block', 'default_pos' => 15, 'label' => 'TopProducts'),
	'low_stock' => array('type' => 'block', 'default_pos' => 16, 'label' => 'LowStockAlert'),
	'agenda' => array('type' => 'block', 'default_pos' => 17, 'label' => 'TodayAgenda'),
	'stats' => array('type' => 'block', 'default_pos' => 18, 'label' => 'Statistics'),
);

// Build ordered list based on user prefs
function dolicraftdashboard_sort_widgets($widgetRegistry, $userPrefs)
{
	$ordered = array();
	foreach ($widgetRegistry as $key => $info) {
		$pos = isset($userPrefs[$key]) ? $userPrefs[$key]['position'] : $info['default_pos'];
		$vis = isset($userPrefs[$key]) ? $userPrefs[$key]['visible'] : 1;
		$ordered[$key] = array_merge($info, array('position' => $pos, 'visible' => $vis));
	}
	uasort($ordered, function ($a, $b) {
		return $a['position'] - $b['position'];
	});
	return $ordered;
}

$orderedWidgets = dolicraftdashboard_sort_widgets($widgetRegistry, $userPrefs);

$ajaxWidgetUrl = dol_buildpath('/dolicraftdashboard/ajax/widget_prefs.php', 1);

// Hidden widgets panel (show chips for hidden widgets so user can re-enable them)
$hiddenWidgets = array_filter($orderedWidgets, function ($w) {
	return !$w['visible'];
});
if (!empty($hiddenWidgets)) {
	print '<div class="dolicraft-hidden-panel" id="dolicraft-hidden-panel">';
	print '<span class="opacitymedium"><span class="fas fa-eye-slash pictofixedwidth"></span>'.$langs->trans('HiddenWidgets').' : </span>';
	foreach ($hiddenWidgets as $hKey => $hInfo) {
		print '<span class="dolicraft-hidden-chip" data-widget-key="'.dol_escape_htmltag($hKey).'" onclick="dolicraftShowWidget(\''.dol_escape_js($hKey).'\')">';
		print '<span class="fas fa-plus-circle"></span> '.$langs->trans($hInfo['label']);
		print '</span>';
	}
	print '</div>';
}

print '<div id="dolicraft-dashboard-container" data-ajax-url="'.dol_escape_htmltag($ajaxWidgetUrl).'" data-token="'.newToken().'">';

// Reset button
print '<div style="text-align:right;margin-bottom:8px;">';
print '<a id="dolicraft-reset-layout" class="dolicraft-reset-btn" style="cursor:pointer;"><span class="fas fa-undo pictofixedwidth"></span>'.$langs->trans('ResetLayout').'</a>';
print '</div>';

// =============================================
// KPI Cards - draggable widget zone
// =============================================

print '<div class="dolicraft-widget-zone dolicraft-kpi-zone noborder centpercent" data-zone="kpi" style="display:grid;grid-template-columns:repeat(4,1fr);gap:0;">';

foreach ($orderedWidgets as $wKey => $wInfo) {
	if ($wInfo['type'] !== 'kpi') {
		continue;
	}

	$wVisible = $wInfo['visible'];
	$hiddenClass = $wVisible ? '' : ' dolicraft-widget-hidden';
	$hiddenStyle = $wVisible ? '' : ' style="display:none;"';
	$eyeIcon = $wVisible ? 'eye' : 'eye-slash';

	// Determine widget content based on key
	$widgetContent = '';
	switch ($wKey) {
		case 'revenue':
			if (!getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_REVENUE', 1)) {
				continue 2;
			}
			$trend = dolicraftdashboard_trend($revenue_current, $revenue_previous);
			$trendIcon = ($trend['direction'] == 'up') ? '<span style="color:#10b981;">&#9650; +'.$trend['pct'].'%</span>' : (($trend['direction'] == 'down') ? '<span style="color:#ef4444;">&#9660; -'.$trend['pct'].'%</span>' : '<span class="opacitymedium">-</span>');
			$widgetContent .= '<div class="opacitymedium" style="margin-bottom:5px;"><span class="fas fa-coins pictofixedwidth" style="color:#10b981;"></span>'.$langs->trans('Turnover').'</div>';
			$widgetContent .= '<div style="font-size:1.6em;font-weight:700;color:#10b981;">'.price($revenue_current, 0, $langs, 1, -1, -1, $currency).'</div>';
			$widgetContent .= '<div style="font-size:0.8em;margin-top:4px;">'.$trendIcon.' <span class="opacitymedium">'.$langs->trans('vs').' '.$langs->trans('PreviousPeriod').'</span></div>';
			break;

		case 'unpaid':
			if (!getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_INVOICES', 1)) {
				continue 2;
			}
			$widgetContent .= '<div class="opacitymedium" style="margin-bottom:5px;"><span class="fas fa-exclamation-circle pictofixedwidth" style="color:#ef4444;"></span>'.$langs->trans('BillsCustomersUnpaid').'</div>';
			$widgetContent .= '<div style="font-size:1.6em;font-weight:700;color:#ef4444;">'.price($unpaid_total, 0, $langs, 1, -1, -1, $currency).'</div>';
			$widgetContent .= '<div style="font-size:0.8em;margin-top:4px;"><span class="badge badge-status8">'.$unpaid_count.'</span> <span class="opacitymedium">'.$langs->trans('Invoices').'</span></div>';
			break;

		case 'proposals':
			if (!getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_PROPOSALS', 1)) {
				continue 2;
			}
			$widgetContent .= '<div class="opacitymedium" style="margin-bottom:5px;"><span class="fas fa-file-alt pictofixedwidth" style="color:#3b82f6;"></span>'.$langs->trans('ProposalsOpened').'</div>';
			$widgetContent .= '<div style="font-size:1.6em;font-weight:700;color:#3b82f6;">'.price($propal_total, 0, $langs, 1, -1, -1, $currency).'</div>';
			$widgetContent .= '<div style="font-size:0.8em;margin-top:4px;"><span class="badge badge-status1">'.$propal_count.'</span> <span class="opacitymedium">'.$langs->trans('Proposals').'</span></div>';
			break;

		case 'orders':
			if (!getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_ORDERS', 1)) {
				continue 2;
			}
			$widgetContent .= '<div class="opacitymedium" style="margin-bottom:5px;"><span class="fas fa-shopping-cart pictofixedwidth" style="color:#8b5cf6;"></span>'.$langs->trans('OrdersToProcess').'</div>';
			$widgetContent .= '<div style="font-size:1.6em;font-weight:700;color:#8b5cf6;">'.price($order_total, 0, $langs, 1, -1, -1, $currency).'</div>';
			$widgetContent .= '<div style="font-size:0.8em;margin-top:4px;"><span class="badge badge-status4">'.$order_count.'</span> <span class="opacitymedium">'.$langs->trans('Orders').'</span></div>';
			break;

		case 'new_customers':
			if (!getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_CUSTOMERS', 1)) {
				continue 2;
			}
			$trend_cust = dolicraftdashboard_trend($new_customers_current, $new_customers_previous);
			$trendCustIcon = ($trend_cust['direction'] == 'up') ? '<span style="color:#10b981;">&#9650; +'.$trend_cust['pct'].'%</span>' : (($trend_cust['direction'] == 'down') ? '<span style="color:#ef4444;">&#9660; -'.$trend_cust['pct'].'%</span>' : '<span class="opacitymedium">-</span>');
			$widgetContent .= '<div class="opacitymedium" style="margin-bottom:5px;"><span class="fas fa-user-plus pictofixedwidth" style="color:#f59e0b;"></span>'.$langs->trans('NewCustomers').'</div>';
			$widgetContent .= '<div style="font-size:1.6em;font-weight:700;color:#f59e0b;">'.$new_customers_current.'</div>';
			$widgetContent .= '<div style="font-size:0.8em;margin-top:4px;">'.$trendCustIcon.' <span class="opacitymedium">'.$langs->trans('vs').' '.$langs->trans('PreviousPeriod').'</span></div>';
			break;

		case 'products_sold':
			if (!getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_PRODUCTS', 1)) {
				continue 2;
			}
			$widgetContent .= '<div class="opacitymedium" style="margin-bottom:5px;"><span class="fas fa-box pictofixedwidth" style="color:#06b6d4;"></span>'.$langs->trans('ProductsSold').'</div>';
			$widgetContent .= '<div style="font-size:1.6em;font-weight:700;color:#06b6d4;">'.number_format($products_sold, 0, ',', ' ').'</div>';
			$widgetContent .= '<div style="font-size:0.8em;margin-top:4px;"><span class="opacitymedium">'.$period_label.'</span></div>';
			break;

		case 'conversion':
			$conversion_color = ($conversion_rate >= 50) ? '#10b981' : (($conversion_rate >= 25) ? '#f59e0b' : '#ef4444');
			$widgetContent .= '<div class="opacitymedium" style="margin-bottom:5px;"><span class="fas fa-percentage pictofixedwidth" style="color:'.$conversion_color.';"></span>'.$langs->trans('ConversionRate').'</div>';
			$widgetContent .= '<div style="font-size:1.6em;font-weight:700;color:'.$conversion_color.';">'.$conversion_rate.'%</div>';
			$widgetContent .= '<div style="font-size:0.8em;margin-top:4px;"><span class="opacitymedium">'.$propal_signed.' / '.$propal_all.' '.$langs->trans('Proposals').'</span></div>';
			break;

		case 'payment_delay':
			$delay_color = ($avg_payment_delay <= 30) ? '#10b981' : (($avg_payment_delay <= 60) ? '#f59e0b' : '#ef4444');
			$widgetContent .= '<div class="opacitymedium" style="margin-bottom:5px;"><span class="fas fa-hourglass-half pictofixedwidth" style="color:'.$delay_color.';"></span>'.$langs->trans('AveragePaymentDelay').'</div>';
			$widgetContent .= '<div style="font-size:1.6em;font-weight:700;color:'.$delay_color.';">'.$avg_payment_delay.'<span style="font-size:0.5em;"> '.$langs->trans('Days').'</span></div>';
			$widgetContent .= '<div style="font-size:0.8em;margin-top:4px;"><span class="opacitymedium">'.$langs->trans('AverageInvoice').' : '.price(round($avg_invoice, 2), 0, $langs, 1, -1, -1, $currency).'</span></div>';
			break;

		case 'overdue':
			if (!getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_OVERDUE', 1)) {
				continue 2;
			}
			$widgetContent .= '<div class="opacitymedium" style="margin-bottom:5px;"><span class="fas fa-clock pictofixedwidth" style="color:#dc2626;"></span>'.$langs->trans('OverdueInvoices').'</div>';
			$widgetContent .= '<div style="font-size:1.6em;font-weight:700;color:#dc2626;">'.price($overdue_total, 0, $langs, 1, -1, -1, $currency).'</div>';
			$widgetContent .= '<div style="font-size:0.8em;margin-top:4px;"><span class="badge badge-status8">'.$overdue_count.'</span> <span class="opacitymedium">'.$langs->trans('LateInvoices').'</span></div>';
			break;

		case 'bank_balance':
			if (!getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_BANKBALANCE', 1)) {
				continue 2;
			}
			$bank_color = ($bank_total >= 0) ? '#10b981' : '#ef4444';
			$widgetContent .= '<div class="opacitymedium" style="margin-bottom:5px;"><span class="fas fa-university pictofixedwidth" style="color:#0ea5e9;"></span>'.$langs->trans('BankBalance').'</div>';
			$widgetContent .= '<div style="font-size:1.6em;font-weight:700;color:'.$bank_color.';">'.price($bank_total, 0, $langs, 1, -1, -1, $currency).'</div>';
			$widgetContent .= '<div style="font-size:0.8em;margin-top:4px;"><span class="opacitymedium">'.count($bank_accounts).' '.$langs->trans('BankAccounts').'</span></div>';
			break;

		case 'supplier_orders':
			if (!getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_SUPPLIERORDERS', 1)) {
				continue 2;
			}
			$widgetContent .= '<div class="opacitymedium" style="margin-bottom:5px;"><span class="fas fa-truck pictofixedwidth" style="color:#f97316;"></span>'.$langs->trans('SupplierOrdersPending').'</div>';
			$widgetContent .= '<div style="font-size:1.6em;font-weight:700;color:#f97316;">'.price($supplier_orders_total, 0, $langs, 1, -1, -1, $currency).'</div>';
			$widgetContent .= '<div style="font-size:0.8em;margin-top:4px;"><span class="badge badge-status1">'.$supplier_orders_count.'</span> <span class="opacitymedium">'.$langs->trans('SupplierOrders').'</span></div>';
			break;

		default:
			continue 2;
	}

	print '<div class="dolicraft-widget'.$hiddenClass.'" data-widget="'.dol_escape_htmltag($wKey).'"'.$hiddenStyle.'>';
	print '<div class="dolicraft-widget-header">';
	print '<span class="fas fa-grip-vertical dolicraft-drag-handle"></span>';
	print '<span class="dolicraft-widget-title">'.$langs->trans($wInfo['label']).'</span>';
	print '<button class="dolicraft-widget-toggle" title="'.$langs->trans('HideWidget').'" data-toggle-key="'.dol_escape_htmltag($wKey).'"><span class="fas fa-'.$eyeIcon.'"></span></button>';
	print '</div>';
	print '<div class="dolicraft-widget-content" style="text-align:center;padding:15px;">';
	print $widgetContent;
	print '</div>';
	print '</div>';
}

print '</div>'; // End KPI zone

print '<br>';

// =============================================
// Block widgets zone (charts, tables, etc.)
// =============================================

print '<div class="dolicraft-widget-zone dolicraft-block-zone" data-zone="block">';

foreach ($orderedWidgets as $wKey => $wInfo) {
	if ($wInfo['type'] !== 'block') {
		continue;
	}

	$wVisible = $wInfo['visible'];
	$hiddenClass = $wVisible ? '' : ' dolicraft-widget-hidden';
	$hiddenStyle = $wVisible ? '' : ' style="display:none;"';
	$eyeIcon = $wVisible ? 'eye' : 'eye-slash';

	// Build widget content based on key
	ob_start();

	switch ($wKey) {
		case 'revenue_chart':
			// Revenue chart (pure CSS horizontal bars)
			print load_fiche_titre($langs->trans('RevenueEvolution').' - '.$langs->trans('Last12Months'), '', 'graph');
			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<td style="width:100px;">'.$langs->trans('Month').'</td>';
			print '<td>'.$langs->trans('Amount').'</td>';
			print '<td class="right" style="width:120px;">'.$langs->trans('AmountTTC').'</td>';
			print '</tr>';
			if (empty($chart_months) || $chart_max == 0) {
				print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans('NoDataAvailable').'</td></tr>';
			} else {
				foreach ($chart_months as $month_key => $amount) {
					$width_pct = ($chart_max > 0) ? round(($amount / $chart_max) * 100, 1) : 0;
					$parts = explode('-', $month_key);
					$month_ts = dol_mktime(0, 0, 0, (int) $parts[1], 1, (int) $parts[0]);
					$month_label = dol_print_date($month_ts, '%b %Y');
					print '<tr class="oddeven">';
					print '<td class="nowraponall" style="font-weight:500;">'.$month_label.'</td>';
					print '<td>';
					if ($amount > 0) {
						print '<div style="background:#e5e7eb;border-radius:4px;height:22px;overflow:hidden;">';
						print '<div style="width:'.$width_pct.'%;height:100%;background:linear-gradient(90deg,#10b981,#34d399);border-radius:4px;"></div>';
						print '</div>';
					} else {
						print '<div style="background:#f3f4f6;border-radius:4px;height:22px;"></div>';
					}
					print '</td>';
					print '<td class="right nowraponall" style="font-weight:600;">'.price($amount, 0, $langs, 1, 0, 0, $currency).'</td>';
					print '</tr>';
				}
			}
			print '</table>';
			print '</div>';
			break;

		case 'top_clients':
			print load_fiche_titre($langs->trans('TopCustomers').' (Top 10)', '', 'company');
			if (empty($top_clients)) {
				print '<table class="noborder centpercent"><tr class="oddeven"><td class="opacitymedium">'.$langs->trans('NoDataAvailable').'</td></tr></table>';
			} else {
				$top_max = (float) ($top_clients[0]->revenue ?? 0);
				$societe_static = new Societe($db);
				print '<table class="noborder centpercent">';
				print '<tr class="liste_titre">';
				print '<td>#</td>';
				print '<td>'.$langs->trans('Company').'</td>';
				print '<td class="right">'.$langs->trans('AmountTTC').'</td>';
				print '<td class="right">%</td>';
				print '</tr>';
				$rank = 0;
				foreach ($top_clients as $client) {
					$rank++;
					$client_revenue = (float) $client->revenue;
					$pct_of_total = ($top_clients_total > 0) ? round(($client_revenue / $top_clients_total) * 100, 1) : 0;
					$societe_static->id = $client->rowid;
					$societe_static->name = $client->nom;
					$societe_static->client = 1;
					print '<tr class="oddeven">';
					print '<td style="width:25px;">'.$rank.'</td>';
					print '<td>'.$societe_static->getNomUrl(1).'</td>';
					print '<td class="right nowraponall">'.price($client_revenue, 0, $langs, 1, -1, -1, $currency).'</td>';
					print '<td class="right opacitymedium">'.$pct_of_total.'%</td>';
					print '</tr>';
				}
				print '</table>';
			}
			break;

		case 'latest_invoices':
			print load_fiche_titre($langs->trans('LatestInvoices').' (10)', '', 'bill');
			if (empty($latest_invoices)) {
				print '<table class="noborder centpercent"><tr class="oddeven"><td class="opacitymedium">'.$langs->trans('NoDataAvailable').'</td></tr></table>';
			} else {
				$facture_static = new Facture($db);
				$societe_static = new Societe($db);
				print '<table class="noborder centpercent">';
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans('Ref').'</td>';
				print '<td>'.$langs->trans('Customer').'</td>';
				print '<td class="right">'.$langs->trans('AmountTTC').'</td>';
				print '<td class="center">'.$langs->trans('Date').'</td>';
				print '<td class="right">'.$langs->trans('Status').'</td>';
				print '</tr>';
				foreach ($latest_invoices as $inv) {
					$facture_static->id = $inv->rowid;
					$facture_static->ref = $inv->ref;
					$facture_static->statut = $inv->fk_statut;
					$facture_static->status = $inv->fk_statut;
					$facture_static->paye = $inv->paye;
					$societe_static->id = $inv->socid;
					$societe_static->name = $inv->socname;
					$societe_static->client = 1;
					print '<tr class="oddeven">';
					print '<td class="nowraponall">'.$facture_static->getNomUrl(1).'</td>';
					print '<td class="tdoverflowmax150">'.$societe_static->getNomUrl(1).'</td>';
					print '<td class="right nowraponall">'.price($inv->total_ttc, 0, $langs, 1, -1, -1, $currency).'</td>';
					print '<td class="center nowraponall">'.dol_print_date($db->jdate($inv->datef), 'day').'</td>';
					print '<td class="right nowraponall">'.dolicraftdashboard_invoice_badge($inv->fk_statut, $inv->paye, $langs).'</td>';
					print '</tr>';
				}
				print '</table>';
			}
			break;

		case 'latest_proposals':
			print load_fiche_titre($langs->trans('LatestProposals').' (10)', '', 'propal');
			if (empty($latest_proposals)) {
				print '<table class="noborder centpercent"><tr class="oddeven"><td class="opacitymedium">'.$langs->trans('NoDataAvailable').'</td></tr></table>';
			} else {
				$propal_static = new Propal($db);
				$societe_static = new Societe($db);
				print '<table class="noborder centpercent">';
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans('Ref').'</td>';
				print '<td>'.$langs->trans('Customer').'</td>';
				print '<td class="right">'.$langs->trans('AmountTTC').'</td>';
				print '<td class="center">'.$langs->trans('Date').'</td>';
				print '<td class="right">'.$langs->trans('Status').'</td>';
				print '</tr>';
				foreach ($latest_proposals as $prop) {
					$propal_static->id = $prop->rowid;
					$propal_static->ref = $prop->ref;
					$propal_static->statut = $prop->fk_statut;
					$propal_static->status = $prop->fk_statut;
					$societe_static->id = $prop->socid;
					$societe_static->name = $prop->socname;
					$societe_static->client = 1;
					print '<tr class="oddeven">';
					print '<td class="nowraponall">'.$propal_static->getNomUrl(1).'</td>';
					print '<td class="tdoverflowmax150">'.$societe_static->getNomUrl(1).'</td>';
					print '<td class="right nowraponall">'.price($prop->total_ttc, 0, $langs, 1, -1, -1, $currency).'</td>';
					print '<td class="center nowraponall">'.dol_print_date($db->jdate($prop->datep), 'day').'</td>';
					print '<td class="right nowraponall">'.dolicraftdashboard_propal_badge($prop->fk_statut, $langs).'</td>';
					print '</tr>';
				}
				print '</table>';
			}
			break;

		case 'top_products':
			if (!getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_TOPPRODUCTS', 1) || empty($top_products)) {
				ob_end_clean();
				continue 2;
			}
			require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
			$product_static = new Product($db);
			print load_fiche_titre($langs->trans('TopProducts').' (Top 5)', '', 'product');
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans('Ref').'</td>';
			print '<td>'.$langs->trans('Label').'</td>';
			print '<td class="right">'.$langs->trans('Qty').'</td>';
			print '<td class="right">'.$langs->trans('AmountTTC').'</td>';
			print '</tr>';
			foreach ($top_products as $prod) {
				$product_static->id = $prod->rowid;
				$product_static->ref = $prod->ref;
				$product_static->label = $prod->label;
				$product_static->type = 0;
				print '<tr class="oddeven">';
				print '<td class="nowraponall">'.$product_static->getNomUrl(1).'</td>';
				print '<td class="tdoverflowmax150">'.dol_escape_htmltag($prod->label).'</td>';
				print '<td class="right">'.number_format((float) $prod->qty_sold, 0, ',', ' ').'</td>';
				print '<td class="right nowraponall">'.price($prod->revenue, 0, $langs, 1, -1, -1, $currency).'</td>';
				print '</tr>';
			}
			print '</table>';
			break;

		case 'low_stock':
			if (!getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_LOWSTOCK', 1) || empty($low_stock_products)) {
				ob_end_clean();
				continue 2;
			}
			require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
			if (!isset($product_static)) {
				$product_static = new Product($db);
			}
			print load_fiche_titre($langs->trans('LowStockAlert'), '', 'warning');
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans('Ref').'</td>';
			print '<td>'.$langs->trans('Label').'</td>';
			print '<td class="right">'.$langs->trans('Stock').'</td>';
			print '<td class="right">'.$langs->trans('StockLimit').'</td>';
			print '</tr>';
			foreach ($low_stock_products as $prod) {
				$product_static->id = $prod->rowid;
				$product_static->ref = $prod->ref;
				$product_static->label = $prod->label;
				$product_static->type = 0;
				$stockColor = ((float) $prod->stock_reel <= 0) ? '#dc2626' : '#f59e0b';
				print '<tr class="oddeven">';
				print '<td class="nowraponall">'.$product_static->getNomUrl(1).'</td>';
				print '<td class="tdoverflowmax150">'.dol_escape_htmltag($prod->label).'</td>';
				print '<td class="right" style="font-weight:600;color:'.$stockColor.';">'.(int) $prod->stock_reel.'</td>';
				print '<td class="right opacitymedium">'.(int) $prod->seuil_stock_alerte.'</td>';
				print '</tr>';
			}
			print '</table>';
			break;

		case 'agenda':
			if (!getDolGlobalInt('DOLICRAFTDASHBOARD_SHOW_AGENDA', 1) || empty($today_events)) {
				ob_end_clean();
				continue 2;
			}
			print load_fiche_titre($langs->trans('TodayAgenda').' ('.count($today_events).')', '', 'action');
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans('Time').'</td>';
			print '<td>'.$langs->trans('Label').'</td>';
			print '<td class="right">'.$langs->trans('Status').'</td>';
			print '</tr>';
			foreach ($today_events as $ev) {
				$pct = (int) ($ev->percent ?? 0);
				if ($pct == -1) {
					$badge = '<span class="badge badge-status0">'.$langs->trans('NotApplicable').'</span>';
				} elseif ($pct == 100) {
					$badge = '<span class="badge badge-status4">'.$langs->trans('Done').'</span>';
				} elseif ($pct > 0) {
					$badge = '<span class="badge badge-status1">'.$pct.'%</span>';
				} else {
					$badge = '<span class="badge badge-status0">'.$langs->trans('ToDo').'</span>';
				}
				print '<tr class="oddeven">';
				print '<td class="nowraponall">'.dol_print_date($db->jdate($ev->datep), 'hour').'</td>';
				print '<td>'.dol_escape_htmltag(dol_trunc($ev->label, 40)).'</td>';
				print '<td class="right">'.$badge.'</td>';
				print '</tr>';
			}
			print '</table>';
			break;

		case 'stats':
			print load_fiche_titre($langs->trans('Statistics'), '', 'object_list');
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre"><td>'.$langs->trans('Parameter').'</td><td class="right">'.$langs->trans('Value').'</td></tr>';
			print '<tr class="oddeven"><td><span class="fas fa-users pictofixedwidth"></span>'.$langs->trans('CustomersTotal').'</td>';
			print '<td class="right" style="font-weight:600;">'.$total_clients.'</td></tr>';
			print '<tr class="oddeven"><td><span class="fas fa-cubes pictofixedwidth"></span>'.$langs->trans('ProductsTotal').'</td>';
			print '<td class="right" style="font-weight:600;">'.$total_products.'</td></tr>';
			print '<tr class="oddeven"><td><span class="fas fa-file-alt pictofixedwidth"></span>'.$langs->trans('ProposalsOpened').'</td>';
			print '<td class="right" style="font-weight:600;">'.$active_proposals.'</td></tr>';
			print '</table>';
			break;

		default:
			ob_end_clean();
			continue 2;
	}

	$blockContent = ob_get_clean();

	print '<div class="dolicraft-widget'.$hiddenClass.'" data-widget="'.dol_escape_htmltag($wKey).'"'.$hiddenStyle.'>';
	print '<div class="dolicraft-widget-header">';
	print '<span class="fas fa-grip-vertical dolicraft-drag-handle"></span>';
	print '<span class="dolicraft-widget-title">'.$langs->trans($wInfo['label']).'</span>';
	print '<button class="dolicraft-widget-toggle" title="'.$langs->trans('HideWidget').'" data-toggle-key="'.dol_escape_htmltag($wKey).'"><span class="fas fa-'.$eyeIcon.'"></span></button>';
	print '</div>';
	print '<div class="dolicraft-widget-content">';
	print $blockContent;
	print '</div>';
	print '</div>';
}

print '</div>'; // End block zone

print '</div>'; // End dolicraft-dashboard-container
print '<div class="clearboth"></div>';

print dol_get_fiche_end();

// Footer
print '<div class="center opacitymedium" style="padding:10px;font-size:0.75em;">';
print 'DolicraftDashboard v1.0.0 - <a href="https://dolicraft.com" target="_blank" rel="noopener">Dolicraft</a> - GPL v3+';
print '</div>';

// =============================================
// Drag-and-drop JavaScript
// =============================================
print '<script>
(function() {
	"use strict";

	var container = document.getElementById("dolicraft-dashboard-container");
	if (!container) return;

	var ajaxUrl = container.getAttribute("data-ajax-url");
	var csrfToken = container.getAttribute("data-token");

	// Translated widget labels for JS
	var widgetLabels = {';

foreach ($widgetRegistry as $wKey => $wInfo) {
	print '"'.dol_escape_js($wKey).'": "'.dol_escape_js($langs->transnoentitiesnoconv($wInfo['label'])).'",';
}

print '};

	// Drag and drop state
	var draggedEl = null;
	var draggedZone = null;

	// Event delegation for toggle buttons (click anywhere in container)
	container.addEventListener("click", function(e) {
		var toggleBtn = e.target.closest(".dolicraft-widget-toggle[data-toggle-key]");
		if (toggleBtn) {
			e.preventDefault();
			e.stopPropagation();
			var key = toggleBtn.getAttribute("data-toggle-key");
			if (key) {
				dolicraftToggleWidget(key);
			}
		}
	});

	// Init drag handles on all widget zones
	var zones = container.querySelectorAll(".dolicraft-widget-zone");
	zones.forEach(function(zone) {
		zone.addEventListener("dragover", function(e) {
			e.preventDefault();
			e.dataTransfer.dropEffect = "move";
			var target = getWidgetFromPoint(zone, e.clientY, e.clientX);
			if (target && target !== draggedEl) {
				var rect = target.getBoundingClientRect();
				var midY = rect.top + rect.height / 2;
				if (e.clientY < midY) {
					zone.insertBefore(draggedEl, target);
				} else {
					zone.insertBefore(draggedEl, target.nextSibling);
				}
			}
		});

		zone.addEventListener("drop", function(e) {
			e.preventDefault();
			savePositions();
		});

		// Make widgets draggable only from handle
		var widgets = zone.querySelectorAll(".dolicraft-widget");
		widgets.forEach(function(w) {
			var handle = w.querySelector(".dolicraft-drag-handle");
			if (handle) {
				handle.addEventListener("mousedown", function() {
					w.setAttribute("draggable", "true");
				});
				handle.addEventListener("mouseup", function() {
					w.removeAttribute("draggable");
				});
			}

			w.addEventListener("dragstart", function(e) {
				draggedEl = w;
				draggedZone = zone;
				w.style.opacity = "0.4";
				e.dataTransfer.effectAllowed = "move";
				e.dataTransfer.setData("text/plain", w.getAttribute("data-widget"));
			});

			w.addEventListener("dragend", function() {
				w.style.opacity = "1";
				w.removeAttribute("draggable");
				draggedEl = null;
				draggedZone = null;
			});
		});
	});

	function getWidgetFromPoint(zone, y, x) {
		var widgets = zone.querySelectorAll(".dolicraft-widget:not(.dolicraft-widget-hidden)");
		var closest = null;
		var closestDist = Infinity;
		widgets.forEach(function(w) {
			if (w === draggedEl) return;
			var rect = w.getBoundingClientRect();
			var dist = Math.abs(rect.top + rect.height / 2 - y);
			if (dist < closestDist) {
				closestDist = dist;
				closest = w;
			}
		});
		return closest;
	}

	function savePositions() {
		var allWidgets = container.querySelectorAll(".dolicraft-widget");
		var widgets = [];
		var pos = 0;
		allWidgets.forEach(function(w) {
			widgets.push({
				key: w.getAttribute("data-widget"),
				position: pos,
				visible: w.classList.contains("dolicraft-widget-hidden") ? 0 : 1
			});
			pos++;
		});

		var xhr = new XMLHttpRequest();
		xhr.open("POST", ajaxUrl, true);
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xhr.send("action=save_order&token=" + encodeURIComponent(csrfToken) + "&widgets=" + encodeURIComponent(JSON.stringify(widgets)));
	}

	// Toggle widget visibility
	window.dolicraftToggleWidget = function(key) {
		var widget = container.querySelector("[data-widget=\"" + key + "\"]");
		if (!widget) return;

		widget.classList.add("dolicraft-widget-hidden");
		widget.style.display = "none";
		widget.querySelector(".dolicraft-widget-toggle .fas").className = "fas fa-eye-slash";

		// Add chip to hidden panel
		var panel = document.getElementById("dolicraft-hidden-panel");
		if (!panel) {
			panel = document.createElement("div");
			panel.className = "dolicraft-hidden-panel";
			panel.id = "dolicraft-hidden-panel";
			panel.innerHTML = \'<span class="opacitymedium"><span class="fas fa-eye-slash pictofixedwidth"></span>'.$langs->trans('HiddenWidgets').' : </span>\';
			container.parentNode.insertBefore(panel, container);
		}

		var existingChip = panel.querySelector("[data-widget-key=\"" + key + "\"]");
		if (!existingChip) {
			var chip = document.createElement("span");
			chip.className = "dolicraft-hidden-chip";
			chip.setAttribute("data-widget-key", key);
			chip.onclick = function() { dolicraftShowWidget(key); };
			chip.innerHTML = \'<span class="fas fa-plus-circle"></span> \' + (widgetLabels[key] || key);
			panel.appendChild(chip);
		}

		savePositions();
	};

	// Show hidden widget
	window.dolicraftShowWidget = function(key) {
		var widget = container.querySelector("[data-widget=\"" + key + "\"]");
		if (!widget) return;

		widget.classList.remove("dolicraft-widget-hidden");
		widget.style.display = "";
		widget.querySelector(".dolicraft-widget-toggle .fas").className = "fas fa-eye";

		// Remove chip from hidden panel
		var panel = document.getElementById("dolicraft-hidden-panel");
		if (panel) {
			var chip = panel.querySelector("[data-widget-key=\"" + key + "\"]");
			if (chip) chip.remove();

			// Hide panel if no more chips
			var remainingChips = panel.querySelectorAll(".dolicraft-hidden-chip");
			if (remainingChips.length === 0) {
				panel.style.display = "none";
			}
		}

		savePositions();
	};

	// Reset layout
	var resetBtn = document.getElementById("dolicraft-reset-layout");
	if (resetBtn) {
		resetBtn.addEventListener("click", function() {
			var xhr = new XMLHttpRequest();
			xhr.open("POST", ajaxUrl, true);
			xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			xhr.onload = function() {
				window.location.reload();
			};
			xhr.send("action=reset&token=" + encodeURIComponent(csrfToken));
		});
	}
})();
</script>';

// =============================================
// Drag-and-drop CSS styles
// =============================================
print '<style>
.dolicraft-hidden-panel {
	background: #f8fafc;
	border: 1px dashed #cbd5e1;
	border-radius: 6px;
	padding: 8px 12px;
	margin-bottom: 12px;
	display: flex;
	align-items: center;
	flex-wrap: wrap;
	gap: 6px;
}
.dolicraft-hidden-chip {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	background: #e2e8f0;
	color: #334155;
	padding: 3px 10px;
	border-radius: 12px;
	font-size: 0.8em;
	cursor: pointer;
	transition: background 0.15s;
}
.dolicraft-hidden-chip:hover {
	background: #cbd5e1;
}
.dolicraft-reset-btn {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	color: #64748b;
	font-size: 0.8em;
	cursor: pointer;
	transition: color 0.15s;
}
.dolicraft-reset-btn:hover {
	color: #334155;
}
.dolicraft-widget {
	position: relative;
	border: 1px solid #e5e7eb;
	border-radius: 6px;
	background: #fff;
	margin-bottom: 8px;
	transition: box-shadow 0.15s, opacity 0.15s;
}
.dolicraft-widget:hover {
	box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.dolicraft-widget-header {
	display: flex;
	align-items: center;
	gap: 6px;
	padding: 4px 10px;
	background: #f8fafc;
	border-bottom: 1px solid #e5e7eb;
	border-radius: 6px 6px 0 0;
	font-size: 0.75em;
	color: #94a3b8;
}
.dolicraft-drag-handle {
	cursor: grab;
	color: #cbd5e1;
	font-size: 0.9em;
}
.dolicraft-drag-handle:active {
	cursor: grabbing;
}
.dolicraft-widget-title {
	flex: 1;
	font-weight: 500;
}
.dolicraft-widget-toggle {
	background: none;
	border: none;
	cursor: pointer;
	color: #94a3b8;
	padding: 2px 4px;
	font-size: 1em;
	transition: color 0.15s;
}
.dolicraft-widget-toggle:hover {
	color: #64748b;
}
.dolicraft-widget-content {
	padding: 0;
}
.dolicraft-kpi-zone .dolicraft-widget {
	margin-bottom: 0;
	border-radius: 0;
	border-right: 1px solid #e5e7eb;
}
.dolicraft-kpi-zone .dolicraft-widget:last-child {
	border-right: none;
}
.dolicraft-kpi-zone .dolicraft-widget-header {
	border-radius: 0;
}
.dolicraft-block-zone .dolicraft-widget {
	margin-bottom: 12px;
}
.dolicraft-block-zone .dolicraft-widget-content {
	padding: 0;
}
</style>';

llxFooter();
$db->close();
