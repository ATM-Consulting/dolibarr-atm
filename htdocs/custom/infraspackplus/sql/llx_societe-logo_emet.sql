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
--	* 	\file		../infraspackplus/sql/llx_societe-logo.sql
--	* 	\ingroup	InfraS
--	* 	\brief		Modifie SQL table for module InfraS
--	************************************************/

--	Create new field 'logo_emet' for societe address (ignore error if exist)
ALTER TABLE llx_societe ADD COLUMN logo_emet varchar(255) NULL DEFAULT NULL;