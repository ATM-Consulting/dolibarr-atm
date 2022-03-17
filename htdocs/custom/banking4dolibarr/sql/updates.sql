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

------------------------
-- Version 7.0.4
------------------------

-- delete old index
ALTER TABLE llx_banking4dolibarr_bank_record DROP FOREIGN KEY fk_b4d_bank_records_commission_currency;
ALTER TABLE llx_banking4dolibarr_bank_record DROP FOREIGN KEY fk_b4d_bank_records_id_account;
ALTER TABLE llx_banking4dolibarr_bank_record DROP FOREIGN KEY fk_b4d_bank_records_id_category;
ALTER TABLE llx_banking4dolibarr_bank_record DROP FOREIGN KEY fk_b4d_bank_records_original_currency;
ALTER TABLE llx_banking4dolibarr_bank_record DROP INDEX uk_b4d_bank_records;
ALTER TABLE llx_banking4dolibarr_bank_record DROP INDEX idx_b4d_bank_records_id_record;
ALTER TABLE llx_banking4dolibarr_bank_record DROP INDEX idx_b4d_bank_records_id_account;
ALTER TABLE llx_banking4dolibarr_bank_record DROP INDEX idx_b4d_bank_record_id_record;
ALTER TABLE llx_banking4dolibarr_bank_record DROP INDEX idx_b4d_bank_record_id_account;
ALTER TABLE llx_banking4dolibarr_bank_record_link DROP FOREIGN KEY fk_b4d_bank_record_links_fk_bank;
ALTER TABLE llx_banking4dolibarr_bank_record_link DROP FOREIGN KEY fk_b4d_bank_record_links_fk_bank_record;
ALTER TABLE llx_banking4dolibarr_bank_record_link DROP INDEX uk_b4d_bank_record_links;
ALTER TABLE llx_banking4dolibarr_bank_record_link DROP INDEX idx_b4d_bank_record_links_fk_bank;
ALTER TABLE llx_banking4dolibarr_bank_record_link DROP INDEX idx_b4d_bank_record_links_fk_bank_record;

ALTER TABLE llx_banking4dolibarr_bank_record_link DROP INDEX uk_b4d_bank_record_link;
ALTER TABLE llx_banking4dolibarr_bank_record_link ADD UNIQUE INDEX uk_b4d_bank_record_link(fk_bank, fk_bank_record);

------------------------
-- Version 7.0.6
------------------------

ALTER TABLE llx_banking4dolibarr_bank_record ADD COLUMN reconcile_date datetime NULL AFTER last_update_date;

------------------------
-- Version 7.0.18
------------------------

ALTER TABLE llx_banking4dolibarr_bank_record ADD COLUMN note text NULL AFTER comment;

------------------------
-- Version 7.0.30
------------------------

ALTER TABLE llx_banking4dolibarr_bank_record ADD COLUMN fk_duplicate_of integer NULL AFTER rowid;

ALTER TABLE llx_banking4dolibarr_bank_record ADD INDEX idx_b4d_bank_record_fk_duplicate_of(fk_duplicate_of);
ALTER TABLE llx_banking4dolibarr_bank_record ADD CONSTRAINT fk_b4d_bank_record_fk_duplicate_of 		FOREIGN KEY (fk_duplicate_of)		REFERENCES llx_banking4dolibarr_bank_record (rowid);

------------------------
-- Version 7.0.34
------------------------

ALTER TABLE llx_banking4dolibarr_bank_record ADD COLUMN import_key	varchar(14) NULL AFTER fk_user_modif;

------------------------
-- Version 7.0.58
------------------------

ALTER TABLE llx_banking4dolibarr_bank_record DROP COLUMN counter_party;
