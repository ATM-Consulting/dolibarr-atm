-- ===================================================================
-- Copyright (C) 2019 Open-DSI  <support@open-dsi.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
-- ===================================================================

CREATE TABLE llx_demat4dolibarr_ededoc_file (
  file_path varchar(255) NOT NULL,
  document_id varchar(36) NOT NULL,
  attachment_id varchar(36) NOT NULL,
  checksum varchar(40) NOT NULL,
  expire_date datetime NOT NULL
) ENGINE=innodb;
