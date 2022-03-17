-- ============================================================================
-- Copyright (C) 2019	 Open-DSI 	 <support@open-dsi.fr>
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
-- ===========================================================================

-- -----------------------------------------------
-- V7.0.0
-- -----------------------------------------------
INSERT INTO `llx_c_type_contact` (`rowid`, `element`, `source`, `code`, `libelle`, `active`, `module`) VALUES
(163000, 'facture', 'external', 'CHORUS_SERVICE', 'Service [CHORUS]', 1, 'demat4dolibarr'),
(163001, 'facture', 'external', 'CHORUS_VALIDATOR', 'Valideur [CHORUS]', 1, 'demat4dolibarr');

INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `color`, `picto`, `position`) VALUES
(163000, 'AC_D4D_ITEC', 'systemauto', 'Send to Chorus (automatically inserted events)', 'demat4dolibarr', 1, NULL, NULL, NULL, 20);

-- -----------------------------------------------
-- V7.0.5
-- -----------------------------------------------
INSERT INTO `llx_c_type_contact` (`rowid`, `element`, `source`, `code`, `libelle`, `active`, `module`) VALUES
(163002, 'commande', 'external', 'CHORUS_SERVICE', 'Service [CHORUS]', 1, 'demat4dolibarr'),
(163003, 'commande', 'external', 'CHORUS_VALIDATOR', 'Valideur [CHORUS]', 1, 'demat4dolibarr'),
(163004, 'contrat', 'external', 'CHORUS_SERVICE', 'Service [CHORUS]', 1, 'demat4dolibarr'),
(163005, 'contrat', 'external', 'CHORUS_VALIDATOR', 'Valideur [CHORUS]', 1, 'demat4dolibarr'),
(163006, 'shipping', 'external', 'CHORUS_SERVICE', 'Service [CHORUS]', 1, 'demat4dolibarr'),
(163007, 'shipping', 'external', 'CHORUS_VALIDATOR', 'Valideur [CHORUS]', 1, 'demat4dolibarr'),
(163008, 'facturerec', 'external', 'CHORUS_SERVICE', 'Service [CHORUS]', 1, 'demat4dolibarr'),
(163009, 'facturerec', 'external', 'CHORUS_VALIDATOR', 'Valideur [CHORUS]', 1, 'demat4dolibarr'),
(163010, 'fichinter', 'external', 'CHORUS_SERVICE', 'Service [CHORUS]', 1, 'demat4dolibarr'),
(163011, 'fichinter', 'external', 'CHORUS_VALIDATOR', 'Valideur [CHORUS]', 1, 'demat4dolibarr'),
(163012, 'project', 'external', 'CHORUS_SERVICE', 'Service [CHORUS]', 1, 'demat4dolibarr'),
(163013, 'project', 'external', 'CHORUS_VALIDATOR', 'Valideur [CHORUS]', 1, 'demat4dolibarr'),
(163014, 'propal', 'external', 'CHORUS_SERVICE', 'Service [CHORUS]', 1, 'demat4dolibarr'),
(163015, 'propal', 'external', 'CHORUS_VALIDATOR', 'Valideur [CHORUS]', 1, 'demat4dolibarr'),
(163016, 'requestmanager', 'external', 'CHORUS_SERVICE', 'Service [CHORUS]', 1, 'demat4dolibarr'),
(163017, 'requestmanager', 'external', 'CHORUS_VALIDATOR', 'Valideur [CHORUS]', 1, 'demat4dolibarr');
