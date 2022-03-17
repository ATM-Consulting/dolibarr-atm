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

CREATE TABLE llx_banking4dolibarr_bank_record (
  rowid                 integer         AUTO_INCREMENT PRIMARY KEY,
  fk_duplicate_of		integer			NULL,
  id_record				integer			NOT NULL,
  id_account			integer			NOT NULL,
  label					varchar(255)	NOT NULL,
  comment				text			NULL,
  note				    text			NULL,
  id_category			integer			NULL,
  record_date			date			NULL,
  rdate					date			NULL,
  bdate					date			NULL,
  vdate					date			NULL,
  date_scraped			datetime		NULL,
  record_type			varchar(64)		NULL,
  original_country		varchar(255)	NULL,
  original_amount		double(24,8)	NULL,
  original_currency		varchar(255)	NULL,
  commission			double(24,8)	NULL,
  commission_currency	varchar(255)	NULL,
  amount				double(24,8)	NOT NULL,
  coming				boolean			NULL,
  deleted_date			datetime		NULL,
  last_update_date		datetime		NOT NULL,
  status				smallint		NOT NULL,
  datec					datetime		NOT NULL,		-- date creation
  tms					timestamp,						-- date of last change
  fk_user_author	    integer         NOT NULL, 		-- user who created the record
  fk_user_modif		    integer,					    -- user who modified the record
  import_key			varchar(14),					-- import key
  datas					text			NOT NULL
) ENGINE=innodb;
