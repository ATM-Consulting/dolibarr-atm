-- ============================================================================
-- Copyright (C) 2017 Mikael Carlavan  <contact@mika-carl.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ============================================================================

CREATE TABLE IF NOT EXISTS `llx_prixmassecategoriedet`(
  `rowid`          	int(11)  AUTO_INCREMENT,
  `fk_prixmassecategorie` 		int(11) DEFAULT 0 NOT NULL, 
  `fk_product` 		int(11) DEFAULT 0 NOT NULL,
    `level` 	int(11) DEFAULT 0 NOT NULL,
  `price_ht_before`  double(24,8) DEFAULT 0 NOT NULL,   
  `price_ht_after`  double(24,8) DEFAULT 0 NOT NULL,   
  `datec`				datetime NOT NULL,
  `fk_user_author` 	int(11) DEFAULT 0 NOT NULL,
  `tms`					timestamp NOT NULL, 
  PRIMARY KEY (`rowid`)    
)ENGINE=innodb DEFAULT CHARSET=utf8;



