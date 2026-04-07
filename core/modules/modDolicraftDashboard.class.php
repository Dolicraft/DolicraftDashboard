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
 * \defgroup    dolicraftdashboard     Module DolicraftDashboard
 * \brief       DolicraftDashboard module descriptor.
 *
 * \file        core/modules/modDolicraftDashboard.class.php
 * \ingroup     dolicraftdashboard
 * \brief       Description and activation file for the module DolicraftDashboard
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module DolicraftDashboard
 */
class modDolicraftDashboard extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		// Id for module (must be unique).
		$this->numero = 500700;

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'dolicraftdashboard';

		// Family can be 'base' | 'crm' | 'financial' | 'hr' | 'projects' | 'products' | 'ecm' | 'technic' | 'interface' | 'other'
		$this->family = 'interface';

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';

		// Gives the possibility for the module, to provide his own family info and position of this family
		// $this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));

		// Module label (no space allowed), used if translation string 'ModuleDolicraftDashboardName' not found
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleDolicraftDashboardDesc' not found
		$this->description = 'Module500700Desc';

		// Used only if file README.md and target for 'docs' not defined
		$this->descriptionlong = '';

		// Editor name
		$this->editor_name = 'Dolicraft';
		// Editor url
		$this->editor_url = 'https://dolicraft.com';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0.0';

		// Url to the file with your last numberversion of this module
		// $this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled
		$this->const_name = 'MAIN_MODULE_DOLICRAFTDASHBOARD';

		// Name of image file used for this module.
		$this->picto = 'chart-bar';

		// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory (core/triggers)
			'triggers' => 0,
			// Set this to 1 if module has its own login method file (core/login)
			'login' => 0,
			// Set this to 1 if module has its own substitution function file (core/substitutions)
			'substitutions' => 0,
			// Set this to 1 if module has its own menus handler directory (core/menus)
			'menus' => 0,
			// Set this to 1 if module overwrite template dir (core/tpl)
			'tpl' => 0,
			// Set this to 1 if module has its own barcode directory (core/modules/barcode)
			'barcode' => 0,
			// Set this to 1 if module has its own models directory (core/modules/xxx)
			'models' => 0,
			// Set this to 1 if module has its own printing directory (core/modules/printing)
			'printing' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => array('/dolicraftdashboard/css/dolicraftdashboard.css'),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array('/dolicraftdashboard/js/dolicraftdashboard.js'),
			// Set here all hooks context managed by module. To find available hook context, make a search on 'initHooks(' into source code.
			'hooks' => array(
				'data' => array('index', 'main'),
				'entity' => '0',
			),
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		);

		// Data directories to create when module is enabled.
		$this->dirs = array('/dolicraftdashboard/temp');

		// Config pages. Put here list of php page, stored into dolicraftdashboard/admin directory, to use to setup module.
		$this->config_page_url = array('setup.php@dolicraftdashboard');

		// Dependencies
		$this->hidden = false;
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array('dolicraftdashboard@dolicraftdashboard');

		// Min version of PHP required by module
		$this->phpmin = array(7, 4);

		// Min version of Dolibarr required by module
		$this->need_dolibarr_version = array(16, 0);

		// Constants
		$this->const = array();

		// Array to add new pages in new tabs
		$this->tabs = array();

		// Dictionaries
		$this->dictionaries = array();

		// Boxes/Widgets
		$this->boxes = array();

		// Cronjobs
		$this->cronjobs = array();

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;

		// Permission id (must not be already used)
		$this->rights[$r][0] = 500701;
		// Permission label
		$this->rights[$r][1] = 'View dashboard';
		// Permission type ('a'=admin,'r'=read,'w'=write,'d'=delete)
		$this->rights[$r][3] = 0;
		// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read';
		// In what context permission can be limited
		$this->rights[$r][5] = '';
		$r++;

		// Main menu entries to add
		$this->menu = array();
		$r = 0;

		// Top menu
		$this->menu[$r++] = array(
			'fk_menu'  => '',
			'type'     => 'top',
			'titre'    => 'DolicraftDashboard',
			'prefix'   => img_picto('', 'fa-chart-bar', 'class="fa paddingright pictofixedwidth"'),
			'mainmenu' => 'dolicraftdashboard',
			'leftmenu' => '',
			'url'      => '/dolicraftdashboard/index.php',
			'langs'    => 'dolicraftdashboard@dolicraftdashboard',
			'position' => 1000 + $r,
			'enabled'  => '$conf->dolicraftdashboard->enabled',
			'perms'    => '$user->rights->dolicraftdashboard->read',
			'target'   => '',
			'user'     => 0,
		);

		// Left menu: Dashboard
		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=dolicraftdashboard',
			'type'     => 'left',
			'titre'    => 'Dashboard',
			'mainmenu' => 'dolicraftdashboard',
			'leftmenu' => 'dolicraftdashboard_dashboard',
			'url'      => '/dolicraftdashboard/index.php',
			'langs'    => 'dolicraftdashboard@dolicraftdashboard',
			'position' => 1000 + $r,
			'enabled'  => '$conf->dolicraftdashboard->enabled',
			'perms'    => '$user->rights->dolicraftdashboard->read',
			'target'   => '',
			'user'     => 0,
		);

		// Left menu: Configuration
		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=dolicraftdashboard',
			'type'     => 'left',
			'titre'    => 'Configuration',
			'mainmenu' => 'dolicraftdashboard',
			'leftmenu' => 'dolicraftdashboard_setup',
			'url'      => '/dolicraftdashboard/admin/setup.php',
			'langs'    => 'dolicraftdashboard@dolicraftdashboard',
			'position' => 1000 + $r,
			'enabled'  => '$conf->dolicraftdashboard->enabled',
			'perms'    => '$user->admin',
			'target'   => '',
			'user'     => 0,
		);

		// Left menu: About
		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=dolicraftdashboard',
			'type'     => 'left',
			'titre'    => 'About',
			'mainmenu' => 'dolicraftdashboard',
			'leftmenu' => 'dolicraftdashboard_about',
			'url'      => '/dolicraftdashboard/admin/about.php',
			'langs'    => 'dolicraftdashboard@dolicraftdashboard',
			'position' => 1000 + $r,
			'enabled'  => '$conf->dolicraftdashboard->enabled',
			'perms'    => '$user->rights->dolicraftdashboard->read',
			'target'   => '',
			'user'     => 0,
		);
	}

	/**
	 * Function called when module is enabled.
	 * The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 * It also creates data directories
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return int 1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		$result = $this->_load_tables('/dolicraftdashboard/sql/');

		$sql = array();

		return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return int 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}
}
