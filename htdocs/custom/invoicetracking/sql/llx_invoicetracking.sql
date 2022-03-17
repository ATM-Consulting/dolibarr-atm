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

create table llx_invoicetracking
(
  rowid			integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture		integer NOT NULL,
  note                  varchar(255) NOT NULL,
  date_r		timestamp NULL DEFAULT NULL,
  date_n		timestamp NULL DEFAULT NULL,
  stage 		int(11),
  fk_user_modif		integer
)ENGINE=innodb;
