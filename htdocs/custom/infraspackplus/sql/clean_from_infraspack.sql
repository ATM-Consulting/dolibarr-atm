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
--	* 	\file		../infraspackplus/sql/clean_from_infraspack.sql
--	* 	\ingroup	InfraS
--	* 	\brief		SQL data for module InfraS
--	************************************************/

-- Data for table llx_document_model
DELETE FROM llx_document_model WHERE nom like 'InfraS\_%';
-- Data for table llx_const
DELETE FROM llx_const WHERE name like 'INFRAS_PDF%';
DELETE FROM llx_const WHERE name like '%\_ADDON_PDF' AND value like 'InfraS\_%';
-- Data for table llx_commande
UPDATE llx_commande AS c SET c.model_pdf = '' WHERE c.model_pdf LIKE 'InfraS_commande';
UPDATE llx_commande AS c SET c.model_pdf = '' WHERE c.model_pdf LIKE 'InfraS_orderfab';
UPDATE llx_commande AS c SET c.model_pdf = '' WHERE c.model_pdf LIKE 'InfraS_proforma';
-- Data for table llx_commande_fournisseur
UPDATE llx_commande_fournisseur AS cf SET cf.model_pdf = '' WHERE cf.model_pdf LIKE 'InfraS_supplier_order';
-- Data for table llx_contrat
UPDATE llx_contrat AS ct SET ct.model_pdf = '' WHERE ct.model_pdf LIKE 'InfraS_contract';
-- Data for table llx_expedition
UPDATE llx_expedition AS e SET e.model_pdf = '' WHERE e.model_pdf LIKE 'InfraS_expedition';
-- Data for table llx_facture
UPDATE llx_facture AS f SET f.model_pdf = '' WHERE f.model_pdf LIKE 'InfraS_invoice';
-- Data for table llx_facture_fourn
UPDATE llx_facture_fourn AS ff SET ff.model_pdf = '' WHERE ff.model_pdf LIKE 'InfraS_supplier_invoice';
-- Data for table llx_fichinter
UPDATE llx_fichinter AS f SET f.model_pdf = '' WHERE f.model_pdf LIKE 'InfraS_fichinter';
-- Data for table llx_product
UPDATE llx_product AS p SET p.model_pdf = '' WHERE p.model_pdf LIKE 'InfraS_product';
-- Data for table llx_propal
UPDATE llx_propal AS d SET d.model_pdf = '' WHERE d.model_pdf LIKE 'InfraS_devis';
UPDATE llx_propal AS d SET d.model_pdf = '' WHERE d.model_pdf LIKE 'InfraS_devis_st';
-- Data for table llx_supplier_proposal
UPDATE llx_supplier_proposal AS df SET df.model_pdf = '' WHERE df.model_pdf LIKE 'InfraS_supplier_proposal';