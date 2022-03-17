-- ===========================================================================
-- Copyright (C) 2015-2016 Inovea Conseil  <info@inovea-conseil.com>
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
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- ===========================================================================


ALTER TABLE llx_invoicetracking ADD INDEX idx_invoicetracking_fk_user_modif (fk_user_modif);

ALTER TABLE llx_invoicetracking ADD CONSTRAINT fk_invoicetracking_fk_user_modif FOREIGN KEY (fk_user_modif) REFERENCES llx_user (rowid);
