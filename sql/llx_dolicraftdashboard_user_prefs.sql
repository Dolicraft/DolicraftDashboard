-- Copyright (C) 2024-2026 Dolicraft
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <https://www.gnu.org/licenses/>.

CREATE TABLE llx_dolicraftdashboard_user_prefs (
  rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
  fk_user INTEGER NOT NULL,
  widget_key VARCHAR(100) NOT NULL,
  position INTEGER DEFAULT 0,
  is_visible TINYINT DEFAULT 1,
  entity INTEGER DEFAULT 1,
  UNIQUE KEY uk_user_widget (fk_user, widget_key, entity)
) ENGINE=InnoDB;
