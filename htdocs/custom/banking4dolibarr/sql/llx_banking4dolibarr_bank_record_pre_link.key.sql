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

ALTER TABLE llx_banking4dolibarr_bank_record_pre_link ADD INDEX idx_b4d_bank_record_pl_fk_bank_account(fk_bank_account);
ALTER TABLE llx_banking4dolibarr_bank_record_pre_link ADD INDEX idx_b4d_bank_record_pl_fk_bank_record(fk_bank_record);
ALTER TABLE llx_banking4dolibarr_bank_record_pre_link ADD UNIQUE INDEX uk_b4d_bank_record_pl_fk_list(fk_bank_record, element_type, element_id);
ALTER TABLE llx_banking4dolibarr_bank_record_pre_link ADD UNIQUE INDEX uk_b4d_bank_record_pl_fk_bank(fk_bank);

ALTER TABLE llx_banking4dolibarr_bank_record_pre_link ADD CONSTRAINT fk_b4d_bank_record_pl_fk_bank_account 	FOREIGN KEY (fk_bank_account)						REFERENCES llx_bank_account (rowid);
ALTER TABLE llx_banking4dolibarr_bank_record_pre_link ADD CONSTRAINT fk_b4d_bank_record_pl_fk_bank_record 	FOREIGN KEY (fk_bank_record)						REFERENCES llx_banking4dolibarr_bank_record (rowid);
ALTER TABLE llx_banking4dolibarr_bank_record_pre_link ADD CONSTRAINT fk_b4d_bank_record_pl_fk_bank 			FOREIGN KEY (fk_bank)				                REFERENCES llx_bank (rowid);
