<?php
/* Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file		accountingex/admin/account.php
 * \ingroup		Accounting Expert
 * \brief		List accounting account
 */

// Dolibarr environment
$res = @include ("../main.inc.php");
if (! $res && file_exists("../main.inc.php"))
	$res = @include ("../main.inc.php");
if (! $res && file_exists("../../main.inc.php"))
	$res = @include ("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php"))
	$res = @include ("../../../main.inc.php");
if (! $res)
	die("Include of main fails");
	
	// Class
dol_include_once("/accountingex/core/lib/account.lib.php");
dol_include_once("/accountingex/class/accountingaccount.class.php");
dol_include_once("/accountingex/class/html.formventilation.class.php");

// Langs
$langs->load("compta");
$langs->load("accountingex@accountingex");

$mesg = '';
$action = GETPOST('action');
$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'int');

// Security check
if ($user->societe_id > 0)
	accessforbidden();
if (! $user->rights->accountingex->admin)
	accessforbidden();

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$limit = $conf->liste_limit;
$page = GETPOST("page", 'int');
if ($page == - 1) {
	$page = 0;
}
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield)
	$sortfield = "aa.account_number";
if (! $sortorder)
	$sortorder = "ASC";

if ($action == 'delete') {
	$formconfirm = $html->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $id, $langs->trans('DeleteAccount'), $langs->trans('ConfirmDeleteAccount'), 'confirm_delete', '', 0, 1);
	print $formconfirm;
}

$accounting = new AccountingAccount($db);

if ($action == 'disable') {
	$accounting = $accounting->fetch($id);
	if (! empty($accounting->id)) {
		$result = $accounting->account_desactivate($id);
	}
	
	$action = 'update';
	if ($result < 0) {
		setEventMessage($accounting->error, 'errors');
	}
} else if ($action == 'enable') {
	
	$accounting = $accounting->fetch($id);
	
	if (! empty($accounting->id)) {
		$result = $accounting->account_activate($id);
	}
	$action = 'update';
	if ($result < 0) {
		setEventMessage($accounting->error, 'errors');
	}
}

/*
 * View
 *
 */
llxHeader('', $langs->trans("ListAccounts"));

$pcgver = $conf->global->CHARTOFACCOUNTS;

$sql = "SELECT aa.rowid, aa.fk_pcg_version, aa.pcg_type, aa.pcg_subtype, aa.account_number, aa.account_parent , aa.label, aa.active ";
$sql .= " FROM " . MAIN_DB_PREFIX . "accountingaccount as aa, " . MAIN_DB_PREFIX . "accounting_system as asy";
$sql .= " WHERE aa.fk_pcg_version = asy.pcg_version";
$sql .= " AND asy.rowid = " . $pcgver;

if (strlen(trim($_GET["search_account"]))) {
	$sql .= " AND aa.account_number like '%" . $_GET["search_account"] . "%'";
}
if (strlen(trim($_GET["search_label"]))) {
	$sql .= " AND aa.label like '%" . $_GET["search_label"] . "%'";
}
if (strlen(trim($_GET["search_accountparent"]))) {
	$sql .= " AND aa.account_parent like '%" . $_GET["search_accountparent"] . "%'";
}
if (strlen(trim($_GET["search_pcgtype"]))) {
	$sql .= " AND aa.pcg_type like '%" . $_GET["search_pcgtype"] . "%'";
}
if (strlen(trim($_GET["search_pcgsubtype"]))) {
	$sql .= " AND aa.pcg_subtype like '%" . $_GET["search_pcgsubtype"] . "%'";
}

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

dol_syslog('accountingex/admin/account.php:: $sql=' . $sql);
$result = $db->query($sql);

if ($result) {
	$num = $db->num_rows($result);
	
	print_barre_liste($langs->trans('ListAccounts'), $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', $num);
	
	$i = 0;
	
	print '<form method="GET" action="' . $_SERVER["PHP_SELF"] . '">';
	
	print '<br/>';
	
	print '<a class="butAction" href="./fiche.php?action=create">' . $langs->trans("Addanaccount") . '</a>';
	print '<a class="butAction" href="./importaccounts.php">' . $langs->trans("ImportAccount") . '</a>';
	print '<br/><br/>';
	
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("AccountNumber"), "account.php", "aa.account_number", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Label"), "account.php", "aa.label", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Accountparent"), "account.php", "aa.account_parent", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Pcgtype"), "account.php", "aa.pcg_type", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Pcgsubtype"), "account.php", "aa.pcg_subtype", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Active"), "account.php", "aa.active", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("&nbsp;");
	print '</tr>';
	
	print '<tr class="liste_titre">';
	print '<td class="liste_titre"><input type="text" class="flat" size="15" name="search_account" value="' . GETPOST("search_account") . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="15" name="search_label" value="' . GETPOST("search_label") . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="15" name="search_accountparent" value="' . GETPOST("search_accountparent") . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="15" name="search_pcgtype" value="' . GETPOST("search_pcgtype") . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="15" name="search_pcgsubtype" value="' . GETPOST("search_pcgsubtype") . '"></td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" name="button_search" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
	print '</td>';
	print '</tr>';
	
	$var = True;
	
	while ( $i < min($num, $limit) ) {
		$obj = $db->fetch_object($resql);
		
		$var = ! $var;
		print '<tr ' . $bc[$var] . '>';
		print '<td><a href="./fiche.php?id=' . $obj->rowid . '">' . $obj->account_number . '</td>';
		print '<td>' . $obj->label . '</td>';
		print '<td>' . $obj->account_parent . '</td>';
		print '<td>' . $obj->pcg_type . '</td>';
		print '<td>' . $obj->pcg_subtype . '</td>';
		print '<td>';
		if (empty($obj->active)) {
			print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $obj->rowid . '&action=enable">';
			print img_picto($langs->trans("Disabled"), 'switch_off');
			print '</a>';
		} else {
			print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $obj->rowid . '&action=disable">';
			print img_picto($langs->trans("Activated"), 'switch_on');
			print '</a>';
		}
		print '</td>';
		
		print '<td>';
		if ($user->rights->accountingex->admin) {
			print '<a href="./fiche.php?action=update&id=' . $obj->rowid . '">';
			print img_edit();
			print '</a>&nbsp;';
			print '<a href="./fiche.php?action=delete&id=' . $obj->rowid . '">';
			print img_delete();
			print '</a>';
		}
		print '</td>' . "\n";
		
		print "</tr>\n";
		$i ++;
	}
	
	print "</table>";
	print '</form>';
} else {
	dol_print_error($db);
}

llxFooter();
$db->close();