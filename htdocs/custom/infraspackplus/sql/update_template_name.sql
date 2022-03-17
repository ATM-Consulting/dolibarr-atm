--	/************************************************
--	* Copyright (C) 2016-2020	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
--	*
--	* This program is free software: you can redistribute it and/or modify
--	* it under the terms of the GNU General Public License as published by
--	* the Free Software Foundation, either version 3 of the License, or
--	* (at your option) any later version.
--	*
--	* This program is distributed in the hope that it will be useful,
--	* but WITHOUT ANY WARRANTY; without even the implied warranty of
--	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
--	* GNU General Public License for more details.
--	*
--	* You should have received a copy of the GNU General Public License
--	* along with this program.  If not, see <http://www.gnu.org/licenses/>.
--	************************************************/

--	/************************************************
--	* 	\file		../infraspackplus/sql/update.sql
--	* 	\ingroup	InfraS
--	* 	\brief		SQL data for module InfraS
--	************************************************/

-- Data for table llx_document_model
DELETE FROM llx_document_model												WHERE libelle		like 'InfraSPlus-%';
-- Data for table llx_commande
UPDATE llx_commande				AS c SET c.model_pdf = 'InfraSPlus_C'		WHERE c.model_pdf	LIKE 'InfraSPlus\_commande';
UPDATE llx_commande				AS c SET c.model_pdf = 'InfraSPlus_OF'		WHERE c.model_pdf	LIKE 'InfraSPlus\_orderfab';
UPDATE llx_commande				AS c SET c.model_pdf = 'InfraSPlus_OM'		WHERE c.model_pdf	LIKE 'InfraSPlus\_ordermont';
UPDATE llx_commande				AS c SET c.model_pdf = 'InfraSPlus_CP'		WHERE c.model_pdf	LIKE 'InfraSPlus\_proforma';
UPDATE llx_commande				AS c SET c.model_pdf = 'InfraSPlus_CBC'		WHERE c.model_pdf	LIKE 'InfraSPlus\_commande\_bc';
UPDATE llx_commande				AS c SET c.model_pdf = 'InfraSPlus_CBL'		WHERE c.model_pdf	LIKE 'InfraSPlus\_commande\_bl';
-- Data for table llx_commande_fournisseur
UPDATE llx_commande_fournisseur	AS cf SET cf.model_pdf = 'InfraSPlus_CF'	WHERE cf.model_pdf	LIKE 'InfraSPlus\_supplier\_order';
-- Data for table llx_contrat
UPDATE llx_contrat				AS ct SET ct.model_pdf = 'InfraSPlus_CT'	WHERE ct.model_pdf	LIKE 'InfraSPlus\_contract';
-- Data for table llx_expedition
UPDATE llx_expedition			AS e SET e.model_pdf = 'InfraSPlus_BL'		WHERE e.model_pdf	LIKE 'InfraSPlus\_EX';
UPDATE llx_expedition			AS e SET e.model_pdf = 'InfraSPlus_BL'		WHERE e.model_pdf	LIKE 'InfraSPlus\_expedition';
UPDATE llx_expedition			AS e SET e.model_pdf = 'InfraSPlus_ET'		WHERE e.model_pdf	LIKE 'InfraSPlus\_etiquette';
-- Data for table llx_facture
UPDATE llx_facture				AS f SET f.model_pdf = 'InfraSPlus_F'		WHERE f.model_pdf	LIKE 'InfraSPlus\_invoice';
UPDATE llx_facture				AS f SET f.model_pdf = 'InfraSPlus_FL'		WHERE f.model_pdf	LIKE 'InfraSPlus\_invoice\_livraison';
-- Data for table llx_facture_fourn
UPDATE llx_facture_fourn		AS ff SET ff.model_pdf = 'InfraSPlus_FF'	WHERE ff.model_pdf	LIKE 'InfraSPlus\_supplier\_invoice';
-- Data for table llx_fichinter
UPDATE llx_fichinter			AS f SET f.model_pdf = 'InfraSPlus_FI'		WHERE f.model_pdf	LIKE 'InfraSPlus\_fichinter';
-- Data for table llx_product
UPDATE llx_product				AS p SET p.model_pdf = 'InfraSPlus_P'		WHERE p.model_pdf	LIKE 'InfraSPlus\_product';
UPDATE llx_product				AS p SET p.model_pdf = 'InfraSPlus_PBC'		WHERE p.model_pdf	LIKE 'InfraSPlus\_BC\_product';
-- Data for table llx_propal
UPDATE llx_propal				AS d SET d.model_pdf = 'InfraSPlus_D'		WHERE d.model_pdf	LIKE 'InfraSPlus\_devis';
UPDATE llx_propal				AS d SET d.model_pdf = 'InfraSPlus_DST'		WHERE d.model_pdf	LIKE 'InfraSPlus\_devis\_st';
-- Data for table llx_supplier_proposal
UPDATE llx_supplier_proposal	AS df SET df.model_pdf = 'InfraSPlus_DF'	WHERE df.model_pdf	LIKE 'InfraSPlus\_supplier\_proposal';
-- Data for table llx_const
UPDATE llx_const				AS co SET co.visible = '0'					WHERE co.name		LIKE 'INFRASPLUS\_%';
UPDATE llx_const				AS co SET co.note = 'InfraSPackPlus module'	WHERE co.name		LIKE 'INFRASPLUS\_%';
UPDATE llx_const				AS co SET co.visible = '0'					WHERE co.name		LIKE '%\_PUBLIC\_NOTE%';
UPDATE llx_const				AS co SET co.note = 'InfraSPackPlus module'	WHERE co.name		LIKE '%\_PUBLIC\_NOTE%';
UPDATE llx_const				AS co SET co.visible = '0'					WHERE co.name		LIKE '%\_FREE\_TEXT%';
UPDATE llx_const				AS co SET co.note = 'InfraSPackPlus module'	WHERE co.name		LIKE '%\_FREE\_TEXT%';