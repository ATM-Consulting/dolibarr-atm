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

ALTER TABLE llx_demat4dolibarr_ededoc_file  ADD INDEX idx_d4d_ededocf_file_path(file_path);
ALTER TABLE llx_demat4dolibarr_ededoc_file  ADD INDEX idx_d4d_ededocf_document_id(document_id);
ALTER TABLE llx_demat4dolibarr_ededoc_file  ADD INDEX idx_d4d_ededocf_checksum(checksum);
ALTER TABLE llx_demat4dolibarr_ededoc_file  ADD UNIQUE KEY uk_d4d_ededocf(file_path, document_id, checksum);
