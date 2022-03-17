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

ALTER TABLE llx_banking4dolibarr_bank_record ADD UNIQUE INDEX uk_b4d_bank_record(id_record, id_account);
ALTER TABLE llx_banking4dolibarr_bank_record ADD INDEX idx_b4d_bank_record_fk_duplicate_of(fk_duplicate_of);
ALTER TABLE llx_banking4dolibarr_bank_record ADD INDEX idx_b4d_bank_record_id_account(id_account);
ALTER TABLE llx_banking4dolibarr_bank_record ADD INDEX idx_b4d_bank_record_id_category(id_category);
ALTER TABLE llx_banking4dolibarr_bank_record ADD INDEX idx_b4d_bank_record_original_currency(original_currency);
ALTER TABLE llx_banking4dolibarr_bank_record ADD INDEX idx_b4d_bank_record_commission_currency(commission_currency);

ALTER TABLE llx_banking4dolibarr_bank_record ADD CONSTRAINT fk_b4d_bank_record_fk_duplicate_of 		FOREIGN KEY (fk_duplicate_of)		REFERENCES llx_banking4dolibarr_bank_record (rowid);
ALTER TABLE llx_banking4dolibarr_bank_record ADD CONSTRAINT fk_b4d_bank_record_id_account 			FOREIGN KEY (id_account)			REFERENCES llx_c_banking4dolibarr_bank_account (rowid);
ALTER TABLE llx_banking4dolibarr_bank_record ADD CONSTRAINT fk_b4d_bank_record_id_category 			FOREIGN KEY (id_category)			REFERENCES llx_c_banking4dolibarr_bank_record_category (rowid);
ALTER TABLE llx_banking4dolibarr_bank_record ADD CONSTRAINT fk_b4d_bank_record_original_currency 	FOREIGN KEY (original_currency)		REFERENCES llx_c_currencies (code_iso);
ALTER TABLE llx_banking4dolibarr_bank_record ADD CONSTRAINT fk_b4d_bank_record_commission_currency 	FOREIGN KEY (commission_currency)	REFERENCES llx_c_currencies (code_iso);
