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
 * \file    class/actions_dolicraftdashboard.class.php
 * \ingroup dolicraftdashboard
 * \brief   Hook class for DolicraftDashboard module
 */

class ActionsDolicraftdashboard
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * @var string Resprints
	 */
	public $resprints = '';

	/**
	 * @var array Results
	 */
	public $results = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Hook printCommonFooter - inject JS redirect on Dolibarr homepage
	 *
	 * This hook is called at the very end of every page, inside the HTML body,
	 * so JavaScript will execute properly (not sanitized like infoadmin).
	 *
	 * @param array  $parameters Hook parameters
	 * @param object $object     Object
	 * @param string $action     Current action
	 * @return int               0=OK
	 */
	public function printCommonFooter($parameters, &$object, &$action)
	{
		global $conf;

		if (!getDolGlobalInt('DOLICRAFTDASHBOARD_REPLACE_DEFAULT', 0)) {
			return 0;
		}

		// Only redirect from the native Dolibarr homepage
		$self = $_SERVER['PHP_SELF'];
		$isHomepage = (
			preg_match('#/index\.php$#', $self)
			&& strpos($self, '/custom/') === false
			&& strpos($self, '/admin/') === false
			&& strpos($self, '/install/') === false
		);

		if ($isHomepage) {
			$target = dol_buildpath('/dolicraftdashboard/index.php', 1);
			print "\n" . '<script>window.location.replace("' . dol_escape_js($target) . '");</script>' . "\n";
		}

		return 0;
	}
}
