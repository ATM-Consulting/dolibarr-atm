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
--	* 	\file		../infraspackplus/sql/update_dic.sql
--	* 	\ingroup	InfraS
--	* 	\brief		SQL data for module InfraS
--	************************************************/

-- Change for table llx_c_infraspackplus_mention
ALTER TABLE llx_c_infraspackplus_mention	CHANGE code		code	VARCHAR(32)	NOT NULL;
ALTER TABLE llx_c_infraspackplus_mention	CHANGE pos		pos		INT(32)		DEFAULT 0	NOT NULL;
ALTER TABLE llx_c_infraspackplus_mention	CHANGE active	active	TINYINT(11)	DEFAULT 1	NOT NULL;

-- Change for table llx_c_infraspackplus_note
ALTER TABLE llx_c_infraspackplus_note		CHANGE code		code	VARCHAR(32)	NOT NULL;
ALTER TABLE llx_c_infraspackplus_note		CHANGE pos		pos		INT(32)		DEFAULT 0	NOT NULL;
ALTER TABLE llx_c_infraspackplus_note		CHANGE active	active	TINYINT(11)	DEFAULT 1	NOT NULL;