<?php

/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015-2018 Inovea Conseil	<info@inovea-conseil.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       dev/skeletons/invoicetracking.class.php
 *  \ingroup    mymodule othermodule1 othermodule2
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *                Initialy built by build_class_from_table on 2015-03-25 15:28
 */
// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php";
require_once DOL_DOCUMENT_ROOT . "/core/modules/facture/modules_facture.php";
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/translate.class.php';

/**
 *    Put here description of your class
 */
class invoicetracking extends CommonObject
{

    var $db;       //!< To store db handler
    var $error;       //!< To return error code (or message)
    var $errors = array();    //!< To return several error codes (or messages)
    var $element = 'invoicetracking';   //!< Id that identify managed objects
    var $table_element = 'invoicetracking';  //!< Name of table without prefix where object is stored
    var $id;
    var $fk_facture;
    var $note;
    var $date_r = '';
    var $date_n = '';
    var $stage = '';
    var $fk_user_modif;

    /**
     *  Constructor
     *
     * @param DoliDb $db Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
    }

    /**
     *  Create object into database
     *
     * @param User $user User that creates
     * @param int $notrigger 0=launch triggers after, 1=disable triggers
     * @return int                 <0 if KO, Id of created object if OK
     */
    function create($user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        // Clean parameters

        if (isset($this->fk_facture))
            $this->fk_facture = trim($this->fk_facture);
        if (isset($this->note))
            $this->note = trim($this->note);
        if (isset($this->date_n))
            $this->date_n = date('Y-m-d', $this->date_n);
        if (isset($this->stage))
            $this->stage = trim($this->stage);
        if (isset($this->fk_user_modif))
            $this->fk_user_modif = trim($this->fk_user_modif);


        // Check parameters
        // Put here code to add control on parameters values
        // Insert request
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "invoicetracking(";

        $sql .= "fk_facture,";
        $sql .= "note,";
        $sql .= "date_r,";
        $sql .= "date_n,";
        $sql .= "stage,";
        $sql .= "fk_user_modif";


        $sql .= ") VALUES (";

        $sql .= " " . (!isset($this->fk_facture) ? 'NULL' : "'" . $this->fk_facture . "'") . ",";
        $sql .= " " . (!isset($this->note) ? 'NULL' : "'" . $this->db->escape($this->note) . "'") . ",";
        $sql .= " NOW(),";
        $sql .= " " . (!isset($this->date_n) ? 'NULL' : "'" . $this->date_n . "'") . ",";
        $sql .= " " . (!isset($this->stage) ? 'NULL' : "'" . $this->stage . "'") . ",";
        $sql .= " " . (!isset($this->fk_user_modif) ? 'NULL' : "'" . $this->fk_user_modif . "'") . "";


        $sql .= ")";

        $this->db->begin();

        dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "invoicetracking");

            if (!$notrigger) {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.
                //// Call triggers
                //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                //$interface=new Interfaces($this->db);
                //$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
                //if ($result < 0) { $error++; $this->errors=$interface->errors; }
                //// End call triggers
            }
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }

            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return $this->id;
        }
    }

    /**
     *  Load object in memory from the database
     *
     * @param int $id Id object
     * @return int            <0 if KO, >0 if OK
     */
    function fetch($id)
    {
        global $langs;
        $sql = "SELECT";
        $sql .= " t.rowid,";

        $sql .= " t.fk_facture,";
        $sql .= " t.note,";
        $sql .= " t.date_r,";
        $sql .= " t.date_n,";
        $sql .= " t.stage,";
        $sql .= " t.fk_user_modif";
        $sql .= " FROM " . MAIN_DB_PREFIX . "invoicetracking as t";
        $sql .= " WHERE t.rowid = " . $id;

        dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;

                $this->fk_facture = $obj->fk_facture;
                $this->note = $obj->note;
                $this->date_r = $this->db->jdate($obj->date_r);
                $this->date_n = $this->db->jdate($obj->date_n);
                $this->stage = $obj->stage;
                $this->fk_user_modif = $obj->fk_user_modif;
            }

            $this->db->free($resql);

            return 1;
        } else {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *  Update object into database
     *
     * @param User $user User that modifies
     * @param int $notrigger 0=launch triggers after, 1=disable triggers
     * @return int                 <0 if KO, >0 if OK
     */
    function update($user = 0, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        $f = new Facture($this->db);
        $f->fetch($this->fk_facture);
        $soc = new Societe($this->db);
        $soc->fetch($f->socid);
        $langsout = new Translate('', $conf);
        if (empty($soc->default_lang))
            $ll = $langs->defaultlang;
        else $ll = $soc->default_lang;

        $langsout->setDefaultLang($ll);
        $langsout->load("main");


        $pathdoc = dol_buildpath("invoicetracking/core/modules/facture/doc/");
        $f->generateDocument('remind:' . $pathdoc, $langsout, 0, 0, 0, array('number' => $this->stage));

        // Clean parameters

        if (isset($this->fk_facture))
            $this->fk_facture = trim($this->fk_facture);
        if (isset($this->note))
            $this->note = trim($this->note);
        if (isset($this->date_n))
            $this->date_n = date('Y-m-d', $this->date_n);
        if (isset($this->stage))
            $this->stage = trim($this->stage);
        if (isset($this->fk_user_modif))
            $this->fk_user_modif = trim($this->fk_user_modif);

        // Check parameters
        // Put here code to add a control on parameters values
        // Update request
        $sql = "UPDATE " . MAIN_DB_PREFIX . "invoicetracking SET";

        $sql .= " fk_facture=" . (isset($this->fk_facture) ? $this->fk_facture : "null") . ",";
        $sql .= " note=" . (isset($this->note) ? "'" . $this->db->escape($this->note) . "'" : "null") . ",";
        $sql .= " date_r=NOW(),";
        $sql .= " date_n=" . (!isset($this->date_n) ? '' : "'" . $this->date_n . "'") . ",";
        $sql .= " stage=" . (!isset($this->stage) ? '' : "'" . $this->stage . "'") . ",";
        $sql .= " fk_user_modif=" . (isset($this->fk_user_modif) ? $this->fk_user_modif : "null") . "";


        $sql .= " WHERE rowid=" . $this->id;

        $this->db->begin();


        dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error) {
            if (!$notrigger) {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.
                //// Call triggers
                //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                //$interface=new Interfaces($this->db);
                //$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
                //if ($result < 0) { $error++; $this->errors=$interface->errors; }
                //// End call triggers
            }
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }

            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return 1;
        }
    }

    /**
     *  Delete object in database
     *
     * @param User $user User that deletes
     * @param int $notrigger 0=launch triggers after, 1=disable triggers
     * @return    int                     <0 if KO, >0 if OK
     */
    function delete($user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        $this->db->begin();

        if (!$error) {
            if (!$notrigger) {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.
                //// Call triggers
                //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                //$interface=new Interfaces($this->db);
                //$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
                //if ($result < 0) { $error++; $this->errors=$interface->errors; }
                //// End call triggers
            }
        }

        if (!$error) {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "invoicetracking";
            $sql .= " WHERE rowid=" . $this->id;

            dol_syslog(get_class($this) . "::delete sql=" . $sql);
            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->errors[] = "Error " . $this->db->lasterror();
            }
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }

            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return 1;
        }
    }

    /**
     *    Load an object from its id and create a new one in database
     *
     * @param int $fromid Id of object to clone
     * @return    int                    New id of clone
     */
    function createFromClone($fromid)
    {
        global $user, $langs;

        $error = 0;

        $object = new invoicetracking($this->db);

        $this->db->begin();

        // Load source object
        $object->fetch($fromid);
        $object->id = 0;
        $object->statut = 0;

        // Clear fields
        // ...
        // Create clone
        $result = $object->create($user);

        // Other options
        if ($result < 0) {
            $this->error = $object->error;
            $error++;
        }

        if (!$error) {
        }

        // End
        if (!$error) {
            $this->db->commit();
            return $object->id;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *    Initialise object with example values
     *    Id must be 0 if object instance is a specimen
     *
     * @return    void
     */
    function initAsSpecimen()
    {
        $this->id = 0;

        $this->fk_facture = '';
        $this->note = '';
        $this->date_r = '';
        $this->date_n = '';
        $this->fk_user_modif = '';
    }

    /**
     *    Return list of inovice (eventually filtered on user) into an array
     *
     * @param int $shortlist 0=Return array[id]=ref, 1=Return array[](id=>id,ref=>ref,name=>name)
     * @param int $draft 0=not draft, 1=draft
     * @param int $notcurrentuser 0=all user, 1=not current user
     * @param int $socid Id third pary
     * @param int $limit For pagination
     * @param int $offset For pagination
     * @param string $sortfield Sort criteria
     * @param string $sortorder Sort order
     * @return    int                            -1 if KO, array with result if OK
     */
    function liste_array_facture($shortlist = 0, $draft = 0, $notcurrentuser = 0, $socid = 0, $limit = 0, $offset = 0, $sortfield = 'p.datef', $sortorder = 'DESC')
    {
        global $conf, $user;

        $ga = array();

        $sql = "SELECT s.rowid, s.nom as name, s.client,";
        if ((int) DOL_VERSION >= 10) {
            if((int) DOL_VERSION >= 14){
                $sql .= " p.rowid as factid, p.fk_statut, p.total_ttc, p.ref, p.remise, ";
            }
            else {
                $sql .= " p.rowid as factid, p.fk_statut, p.total, p.ref, p.remise, ";
            }
        } else {
            $sql .= " p.rowid as factid, p.fk_statut, p.total, p.facnumber, p.remise, ";
        }
        $sql .= " p.datef as dp, p.date_lim_reglement as datelimite";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", sc.fk_soc, sc.fk_user";
        $sql .= " FROM " . MAIN_DB_PREFIX . "societe as s, " . MAIN_DB_PREFIX . "facture as p, " . MAIN_DB_PREFIX . "c_propalst as c";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
        $sql .= " WHERE p.entity = " . $conf->entity;
        $sql .= " AND p.fk_soc = s.rowid";
        $sql .= " AND p.type != 2";
        $sql .= " AND p.fk_statut = c.id";
        if (!$user->rights->societe->client->voir && !$socid) { //restriction
            $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " . $user->id;
        }

        if ($socid)
            $sql .= " AND s.rowid = " . $socid;
        if ($draft)
            $sql .= " AND p.fk_statut = 1 ";
        if ($notcurrentuser > 0)
            $sql .= " AND p.fk_user_author <> " . $user->id;
        $sql .= $this->db->order($sortfield, $sortorder);
        $sql .= $this->db->plimit($limit, $offset);

        $result = $this->db->query($sql);
        if ($result) {
            $num = $this->db->num_rows($result);
            if ($num) {
                $i = 0;
                while ($i < $num) {
                    $obj = $this->db->fetch_object($result);

                    if ($shortlist == 1) {
                        if ((int) DOL_VERSION >= 10) {
                            $ga[$obj->factid] = $obj->ref;
                        } else {
                            $ga[$obj->factid] = $obj->facnumber;
                        }
                    } else if ($shortlist == 2) {
                        if ((int) DOL_VERSION >= 10) {
                            $ga[$obj->factid] = $obj->ref . ' (' . $obj->name . ')';
                        } else {
                            $ga[$obj->factid] = $obj->facnumber . ' (' . $obj->name . ')';
                        }
                    } else {
                        $ga[$i]['id'] = $obj->factid;
                        if ((int) DOL_VERSION >= 10) {
                            $ga[$i]['ref'] = $obj->ref;
                        } else {
                            $ga[$i]['ref'] = $obj->facnumber;
                        }
                        $ga[$i]['name'] = $obj->name;
                    }

                    $i++;
                }
            }

            return $ga;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }

    function verify()
    {
        $this->errors = array();

        $result = 0;
        $this->fk_facture = trim($this->fk_facture);
        $this->fk_facture = $this->fk_facture; // For backward compatibility
        $this->note = trim($this->note);
        $this->stage = trim($this->stage);
        $this->date_n = trim($this->date_n);
        //echo "P".$this->fk_facture."--N".$this->note."--D".$this->date_n;
        if (!$this->fk_facture || !$this->date_n) {
            $this->errors[] = 'ErrorRequiredField';
            $result = -2;
        }

        return $result;
    }

    /**
     *  Create object into database
     *
     * @param User $user User that creates
     * @param int $notrigger 0=launch triggers after, 1=disable triggers
     * @return int                 <0 if KO, Id of created object if OK
     */
    function saveRemindEmail($user, $stage = 0, $f, $notrigger = 0, $datereminder = null, $note = null)
    {
        global $conf, $langs, $outputlangs;
        $error = 0;

        $this->fk_facture = trim($f->id);
        $this->note = $langs->trans("Globalreminderbyemail");
        if (!empty($note)) {
            $this->note = $note;
        }

        if (!empty($datereminder)) {
            $this->date_n = date('Y-m-d', strtotime($datereminder));
        } else {
            $this->date_n = date('Y-m-d', strtotime($conf->global->ITREMINDDATE));
        }

        $this->stage = $stage;
        if (isset($user->id))
            $this->fk_user_modif = trim($user->id);


        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "invoicetracking(";

        $sql .= "fk_facture,";
        $sql .= "note,";
        $sql .= "date_r,";
        $sql .= "date_n,";
        $sql .= "stage,";
        $sql .= "fk_user_modif";


        $sql .= ") VALUES (";

        $sql .= " " . (!isset($this->fk_facture) ? 'NULL' : "'" . $this->fk_facture . "'") . ",";
        $sql .= " " . (!isset($this->note) ? 'NULL' : "'" . $this->note . "'") . ",";
        $sql .= " NOW(),";
        $sql .= " " . (!isset($this->date_n) ? 'NULL' : "'" . $this->date_n . "'") . ",";
        $sql .= " " . (!isset($this->stage) ? 'NULL' : "'" . $this->stage . "'") . ",";
        $sql .= " " . (!isset($this->fk_user_modif) ? 'NULL' : "'" . $this->fk_user_modif . "'") . "";


        $sql .= ")";

        $this->db->begin();

        dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "invoicetracking");

            if (!$notrigger) {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.
                //// Call triggers
                //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                //$interface=new Interfaces($this->db);
                //$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
                //if ($result < 0) { $error++; $this->errors=$interface->errors; }
                //// End call triggers
            }
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }

            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            //$f = new Facture($db);
            // $result = $f->fetch($objs->factid);
            // if ($result > 0) { // Invoice was found
        }
    }

    /**
     *  Create object into database
     *
     * @param User $user User that creates
     * @param int $notrigger 0=launch triggers after, 1=disable triggers
     * @return int                 <0 if KO, Id of created object if OK
     */
    function saveRemindMail($user, $stage = 0, $f, $notrigger = 0)
    {
        global $conf, $langs, $outputlangs;
        $error = 0;


        $this->fk_facture = trim($f->id);
        $this->note = $langs->trans("Globalreminder");
        $this->date_n = date('Y-m-d', strtotime($conf->global->ITREMINDDATE));
        $this->stage = $stage;
        if (isset($user->id))
            $this->fk_user_modif = trim($user->id);


        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "invoicetracking(";

        $sql .= "fk_facture,";
        $sql .= "note,";
        $sql .= "date_r,";
        $sql .= "date_n,";
        $sql .= "stage,";
        $sql .= "fk_user_modif";


        $sql .= ") VALUES (";

        $sql .= " " . (!isset($this->fk_facture) ? 'NULL' : "'" . $this->fk_facture . "'") . ",";
        $sql .= " " . (!isset($this->note) ? 'NULL' : "'" . $this->note . "'") . ",";
        $sql .= " NOW(),";
        $sql .= " " . (!isset($this->date_n) ? 'NULL' : "'" . $this->date_n . "'") . ",";
        $sql .= " " . (!isset($this->stage) ? 'NULL' : "'" . $this->stage . "'") . ",";
        $sql .= " " . (!isset($this->fk_user_modif) ? 'NULL' : "'" . $this->fk_user_modif . "'") . "";


        $sql .= ")";

        $this->db->begin();

        dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "invoicetracking");

            if (!$notrigger) {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.
                //// Call triggers
                //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                //$interface=new Interfaces($this->db);
                //$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
                //if ($result < 0) { $error++; $this->errors=$interface->errors; }
                //// End call triggers
            }
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }

            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            //$f = new Facture($db);
            // $result = $f->fetch($objs->factid);
            // if ($result > 0) { // Invoice was found
        }
    }


    function createFullAuto($test = "", $idf = null)
    {
        global $conf, $langs, $outputlangs, $user;
        $error = 0;
        $sql = "SELECT p.rowid as factid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "facture as p";
        $sql .= " WHERE p.entity = " . $conf->entity;
        $sql .= " AND p.paye=0 AND fk_statut > 0";
        if ($test == "test") {
            if ($idf > 0) {
                $sql .= " AND p.rowid=" . $idf;
            }
        }

        $result = $this->db->query($sql);
        if ($result) {
            $num = $this->db->num_rows($result);
            if ($num) {
                $i = 0;

                while ($i < $num) {
                    $objs = $this->db->fetch_object($result);
                    $object = new Facture($this->db);
                    $result3 = $object->fetch($objs->factid);
                    if ($result3 > 0) { // Invoice was found
                        if ($object->statut != Facture::STATUS_VALIDATED || $object->array_options['options_reminder'] == 1) {
                            //echo $langs->trans('NottoReminded');
                            continue;
                        }

                        // Read document
                        // TODO Use future field $object->fullpathdoc to know where is stored default file
                        // TODO If not defined, use $object->modelpdf (or defaut invoice config) to know what is template to use to regenerate doc.
                        $filename = dol_sanitizeFileName($object->ref) . '.pdf';
                        $filedir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($object->ref);
                        $file = $filedir . '/' . $filename;
                        $mime = 'application/pdf';

                        if (dol_is_file($file)) {
                            $sql2 = "SELECT * FROM " . MAIN_DB_PREFIX . "invoicetracking as t WHERE fk_facture=" . $objs->factid . " ORDER BY rowid DESC LIMIT 1";
                            $result2 = $this->db->query($sql2);
                            if ($result2) {
                                $objtracking = $this->db->fetch_object($result2);
                            } else {
                                $objtracking = 0;
                            }

                            (is_object($objtracking)) ? $stage = ($objtracking->stage + 1) : $stage = 0;
                            $continue = 0;
                            if ($stage == 0) {
                                $autotracking = "AUTOTRACKING" . $stage;
                                $itreminderdate = "ITREMINDDATE" . $stage;
                                $stage2 = $stage + 1;
                                if ($stage == 3) {
                                    $stage2 = 3;
                                }

                                $itreminderdate2 = "ITREMINDDATE" . $stage2;

                                if (empty($conf->global->$autotracking))
                                    $continue = 1;

                                $d1 = new DateTime();
                                $d1->setTimestamp($object->date_lim_reglement);
                                $d2 = new DateTime(date('d-m-Y', time()));
                                $dr = new DateTime();

                                $interval = $d1->diff($d2);
                                //echo $langs->trans('Interval') . " " . $interval->format('%R%a days') . '</br>';

                                if ($interval->format('%R%a days') != $conf->global->$itreminderdate)
                                    $continue = 1;


                                if ($continue == 1) {
                                    $stage = 1;
                                    $autotracking = "AUTOTRACKING" . $stage;
                                    $itreminderdate = "ITREMINDDATE" . $stage;
                                    $stage2 = $stage + 1;
                                    if ($stage == 3) {
                                        $stage2 = 3;
                                    }

                                    $itreminderdate2 = "ITREMINDDATE" . $stage2;

                                    if (empty($conf->global->$autotracking))
                                        continue;

                                    $d1 = new DateTime();
                                    $d1->setTimestamp($object->date_lim_reglement);
                                    $d2 = new DateTime(date('d-m-Y', time()));
                                    $dr = new DateTime();

                                    $interval = $d1->diff($d2);
                                    if ($interval->format('%R%a days') != $conf->global->$itreminderdate) {
                                        continue;
                                    }
                                }
                            } else {
                                $autotracking = "AUTOTRACKING" . $stage;
                                $itreminderdate = "ITREMINDDATE" . $stage;
                                $stage2 = $stage + 1;
                                if ($stage == 3) {
                                    $stage2 = 3;
                                }

                                $itreminderdate2 = "ITREMINDDATE" . $stage2;

                                if ($test == "test") {
                                    echo $stage . "<br />";
                                }

                                if (empty($conf->global->$autotracking)) {
                                    if ($test == "test")
                                        echo $langs->trans('DisallowStage') . "<br/>";
                                    continue;
                                }

                                $d1 = new DateTime();
                                $d1->setTimestamp($object->date_lim_reglement);
                                $d2 = new DateTime(date('d-m-Y', time()));
                                $dr = new DateTime();

                                $interval = $d1->diff($d2);

                                if ($interval->format('%R%a days') != $conf->global->$itreminderdate) {
                                    continue;
                                }
                            }

                            $d1->add(DateInterval::createFromDateString($conf->global->$itreminderdate2));


                            $sendto = $lastn = $firstn = "";
                            $sendtocc = '';
                            $contactr = $object->liste_contact(-1, 'external', 0, 'REMINDER');
                            if (!empty($contactr)) {
                                $j = 0;
                                while ($j < count($contactr)) {
                                    if (empty($sendto) && !empty($contactr[$j]['email'])) {
                                        $sendto = $contactr[$j]['email'];
                                        $lastn = $contactr[$j]['lastname'];
                                        $firstn = $contactr[$j]['firstname'];
                                    } else if (!empty($sendto) && !empty($contactr[$j]['email'])) {
                                        if (!empty($sendtocc))
                                            $sendtocc .= ',';
                                        $sendtocc .= $contactr[$j]['email'];
                                    }
                                    $j++;
                                }
                            }
                            if (empty($sendto)) {
                                $contactf = $object->liste_contact(-1, 'external', 0, 'BILLING');
                                if (!empty($contactf)) {
                                    $j = 0;
                                    while ($j < count($contactf)) {
                                        if (empty($sendto) && !empty($contactf[$j]['email'])) {
                                            $sendto = $contactf[$j]['email'];
                                            $lastn = $contactf[$j]['lastname'];
                                            $firstn = $contactf[$j]['firstname'];
                                        } else if (!empty($sendto) && !empty($contactf[$j]['email'])) {
                                            if (!empty($sendtocc))
                                                $sendtocc .= ',';
                                            $sendtocc .= $contactf[$j]['email'];
                                        }
                                        $j++;
                                    }
                                }
                            }

                            if (empty($sendto)) {
                                $object->fetch_thirdparty();
                                $sendto = $object->thirdparty->email;
                            }

                            if (empty($sendto)) {
                                $nbignored++;
                            }

                            if (dol_strlen($sendto)) {
                                $langs->load("commercial");
                                $from = $user->getFullName($langs) . ' <' . $user->email . '>';
                                if (!empty($conf->global->ITSENDERMAIL)) {
                                    $from = $conf->global->ITSENDERMAIL;
                                }
                                $replyto = $from;

                                $money = $conf->global->MAIN_MONNAIE;
                                if (!empty($object->multicurrency_code)) ;
                                $money = $object->multicurrency_code;


                                $soc = new Societe($this->db);
                                $soc->fetch($object->socid);
                                //echo "<pre>".print_r($langs,1)."</pre>";
                                $langsout = new Translate('', $conf);
                                if (empty($soc->default_lang))
                                    $ll = $langs->defaultlang;
                                else $ll = $soc->default_lang;

                                $langsout->setDefaultLang($ll);
                                $langsout->load("main");

                                self::addFees($object, $stage, $langsout);

                                $s = "ITSUBJECT" . $stage . "_" . $ll;
                                $ma = "ITCONTENT" . $stage . "_" . $ll;

                                $subject = $conf->global->$s;
                                $message = $conf->global->$ma;

                                (empty(GETPOST('sendtocc')) ? null : GETPOST('sentocc'));
                                $sendtobcc = (empty($conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO) ? '' : $conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO);

                                $totalpaye = $object->getSommePaiement();
                                $totalcreditnotes = $object->getSumCreditNotesUsed();
                                $totaldeposits = $object->getSumDepositsUsed();
                                if ($object->multicurrency_total_ttc > 0) {
                                    $totalpaye = $object->getSommePaiement(1);
                                    $totalcreditnotes = $object->getSumCreditNotesUsed(1);
                                    $totaldeposits = $object->getSumDepositsUsed(1);
                                }

                                $totalttc = $object->total_ttc;
                                if ($object->multicurrency_total_ttc > 0)
                                    $totalttc = $object->multicurrency_total_ttc;

                                $resteapayer = price2num($totalttc - $totalpaye - $totalcreditnotes - $totaldeposits, 'MT');
                                $ad = "ADDING" . $stage;
                                $ad2 = floatval($conf->global->$ad) / 100;
                                $adtype = "select_ADDING" . $stage;
                                if ($conf->global->$adtype == 1 && !empty($conf->global->$ad)) {
                                    if ($stage > 0 && !empty($conf->global->FEESSUP)) {
                                        $majo = $resteapayer + ($resteapayer * $ad2) + $conf->global->FEESSUP;
                                    } else {
                                        $majo = $resteapayer + ($resteapayer * $ad2);
                                    }

                                    $majotype = "%";
                                } else {
                                    if ($stage > 0 && !empty($conf->global->FEESSUP)) {
                                        $majo = $resteapayer + floatval($conf->global->$ad) + $conf->global->FEESSUP;
                                    } else {
                                        $majo = $resteapayer + floatval($conf->global->$ad);
                                    }

                                    $majotype = $conf->global->MAIN_MONNAIE;
                                }

                                $substitutionarray = array(
                                    '__REF_FACTURE__' => $object->ref,
                                    '__DATE_FACTURE__' => dol_print_date($object->date, 'daytext', 'tzserver', $langsout),
                                    '__TOTAL_TTC__' => price($totalttc),
                                    '__DATE_ECHEANCE__' => dol_print_date($object->date_lim_reglement, 'daytext', 'tzserver', $langsout),
                                    '__MONEY__' => $langs->trans('Currency' . $money),
                                    '__FIRSTNAME__' => $firstn,
                                    '__NAME__' => $lastn,
                                    '__DETTE__' => price($resteapayer),
                                    '__ID__' => $object->id,
                                    '__EMAIL__' => $object->thirdparty->email,
                                    '__MAJORATION__' => floatval($conf->global->$ad),
                                    '__MAJORATIONTYPE__' => $majotype,
                                    '__MAJORATION_TOTAL__' => round($majo, 2),
                                    '__FRAISSUP__' => $conf->global->FEESSUP,
                                    '__RETARD__' => preg_replace("/[^0-9]/", "", $conf->global->$itreminderdate),
                                    '__USER_SIGNATURE__' => (($user->signature && empty($conf->global->MAIN_MAIL_DO_NOT_USE_SIGN)) ? $user->signature : ''),); // Done into actions_sendmails
                                // Set the online payment url link into __ONLINE_PAYMENT_URL__ key
                                require_once DOL_DOCUMENT_ROOT . '/core/lib/payments.lib.php';
                                //$langsout->load('paypal');
                                $typeforonlinepayment = 'free';
                                $url = getOnlinePaymentUrl(0, 'invoice', $object->ref);
                                $paymenturl = $url;

                                $substitutionarray['__ONLINE_PAYMENT_URL__'] = $paymenturl;

                                if ($test == "test") {
                                    echo '__REF_FACTURE__ ' . $object->ref . "<br />";
                                    echo '__DATE_FACTURE__ ' . dol_print_date($object->date, 'daytext', 'tzserver', $langsout) . "<br />";
                                    echo '__TOTAL_TTC__ ' . price($totalttc) . "<br />";
                                    echo '__DATE_ECHEANCE__ ' . dol_print_date($object->date_lim_reglement, 'daytext', 'tzserver', $langsout) . "<br />";
                                    echo '__FIRSTNAME__ ' . $firstn . "<br />";
                                    echo '__NAME__ ' . $lastn . "<br />";
                                    echo '__DETTE__ ' . price($resteapayer) . "<br />";
                                    echo '__ID__ ' . $object->id . "<br />";
                                    echo '__EMAIL__ ' . $object->thirdparty->email . "<br />";
                                    echo '__MAJORATION__ ' . floatval($conf->global->$ad) . "<br />";
                                    echo '__MAJORATIONTYPE__ ' . $majotype . "<br />";
                                    echo '__MAJORATION_TOTAL__ ' . round($majo, 2) . "<br />";
                                    echo '__FRAISSUP__ ' . $conf->global->FEESSUP . "<br />";
                                    echo '__RETARD__ ' . preg_replace("/[^0-9]/", "", $conf->global->$itreminderdate) . "<br />";
                                    echo '__USER_SIGNATURE__' . (($user->signature && empty($conf->global->MAIN_MAIL_DO_NOT_USE_SIGN)) ? $user->signature : '') . "<br />";
                                    echo '__ONLINE_PAYMENT_URL__ ' . $paymenturl . "<br />";
                                    // Done into actions_sendmails
                                }

                                $subject = make_substitutions($subject, $substitutionarray);
                                $message = make_substitutions($message, $substitutionarray);

                                $actiontypecode = 'AC_EMAIL';
                                $actionmsg = $langs->transnoentities('MailSentBy') . ' ' . $from . ' ' . $langs->transnoentities('To') . ' ' . $sendto;
                                if ($message) {
                                    if ($sendtocc)
                                        $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('Bcc') . ": " . $sendtocc);
                                    $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic') . ": " . $subject);
                                    $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
                                    $actionmsg = dol_concatdesc($actionmsg, $message);
                                }

                                // Create form object
                                $attachedfiles = array('paths' => array($file), 'names' => array($filename), 'mimes' => array($mime));
                                $filepath = $attachedfiles['paths'];
                                $filename = $attachedfiles['names'];
                                $mimetype = $attachedfiles['mimes'];

                                if ($conf->global->INVOICETRACKING_COMMERCIAL_CC) {
                                    //prepare sender, receiver, cc and bcc on email
                                    $emailComm = "";
                                    $commercialArray = [];

                                    $contactInvoice = $object->liste_contact(-1, 'internal', 0, 'SALESREPFOL');
                                    if (empty($contactInvoice)) {
                                        $contactInvoice = $object->liste_contact(-1, 'internal', 0, 'SALESREPFOLL');
                                    }
                                    if (!empty($contactInvoice)) {
                                        $j = 0;
                                        while ($j < count($contactInvoice) && empty($emailComm)) {
                                            if (!empty($contactInvoice[$j]['email'])) {
                                                $emailComm = $contactInvoice[$j]['email'];
                                            }
                                            $j++;
                                        }
                                        if (!empty($emailComm)) {
                                            if (!empty($sendtocc))
                                                $sendtocc .= ',';
                                            $sendtocc .= $emailComm;
                                        }
                                    }

                                    if (empty($emailComm)) {
                                        $sql2 = "SELECT fk_user as rowid FROM " . MAIN_DB_PREFIX . "societe_commerciaux as sc WHERE sc.fk_soc = " . $object->socid;
                                        $resql2 = $this->db->query($sql2);

                                        if ($resql2) {
                                            $obj = $this->db->fetch_object($resql2);
                                            while ($obj != null) {
                                                $commercial = new User($this->db);
                                                $commercial->fetch($obj->rowid);
                                                $commercialArray[] = $commercial;
                                                $obj = $this->db->fetch_object($resql2);
                                            }
                                        }

                                        if (count($commercialArray) > 0) {
                                            $j = 0;
                                            while ($j < count($commercialArray) && empty($emailComm)) {
                                                if (!empty($commercialArray[$j]->email)) {
                                                    $emailComm = $commercialArray[$j]->email;
                                                }
                                                $j++;
                                            }
                                            if (!empty($emailComm)) {
                                                if (!empty($sendtocc))
                                                    $sendtocc .= ',';
                                                $sendtocc .= $emailComm;
                                            }
                                        }
                                    }

                                    if (empty($emailComm)) {
                                        $sql2 = "SELECT email FROM " . MAIN_DB_PREFIX . "user as u JOIN " . MAIN_DB_PREFIX . "facture as f ON u.rowid=f.fk_user_author WHERE f.rowid = " . $object->id;
                                        $resql2 = $this->db->query($sql2);
                                        if ($resql2) {
                                            $obj = $this->db->fetch_object($resql2);
                                            if (!empty($obj->email)) {
                                                if (!empty($sendtocc))
                                                    $sendtocc .= ',';
                                                $sendtocc .= $obj->email;
                                            }
                                        }
                                    }
                                }

                                // Send mail
                                require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
                                $mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc, $sendtobcc, $deliveryreceipt, -1);
                                if ($mailfile->error) {
                                    $resultmasssend .= '<div class="error">' . $mailfile->error . '</div>';
                                } else {
                                    $result4 = $mailfile->sendfile();
                                    if ($result4) {
                                        if (!$m) {
                                            $ms2 = new invoicetracking($this->db);
                                            $ms2->saveRemindEmail($user, $stage, $object, 0, $d1->format('Y-m-d'), $langs->trans('Globalautoreminder'));
                                        }

                                        $resultmasssend .= $langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($from, 2), $mailfile->getValidAddress($sendto, 2));  // Must not contain "

                                        $error = 0;

                                        // Initialisation donnees
                                        $object->sendtoid = 0;
                                        $object->actiontypecode = $actiontypecode;
                                        $object->actionmsg = $actionmsg;  // Long text
                                        $object->actionmsg2 = $actionmsg2; // Short text
                                        $object->fk_element = $object->id;
                                        $object->elementtype = $object->element;


                                        // Appel des triggers
                                        include_once DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php";
                                        $interface = new Interfaces($this->db);
                                        $result5 = $interface->run_triggers('BILL_SENTBYMAIL', $object, $user, $langs, $conf);

                                        // Fin appel triggers

                                        if (!$error) {
                                            $resultmasssend .= $langs->trans("MailSent") . ': ' . $sendto . "<br>";
                                        } else {
                                            dol_print_error($this->db);
                                        }

                                        $nbsent++;
                                    } else {
                                        $langs->load("other");
                                        if ($mailfile->error) {
                                            $resultmasssend .= $langs->trans('ErrorFailedToSendMail', $from, $sendto);
                                            $resultmasssend .= '<br><div class="error">' . $mailfile->error . '</div>';
                                        } else {
                                            $resultmasssend .= '<div class="warning">No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS</div>';
                                        }
                                    }
                                }
                            }
                        } else {
                            $nbignored++;
                            $langs->load("other");
                            $resultmasssend .= '<div class="error">' . $langs->trans('ErrorCantReadFile', $file) . '</div>';
                            dol_syslog('Failed to read file: ' . $file, LOG_WARNING);
                        }
                    }

                    $i++;
                }
            }
        }
        return (($nbignored == 0) ? 0 : 'nb ignored :' . $nbignored);
    }

    function itsendmassmail($save = null)
    {
        global $conf, $langs, $user;
        $countToSend = count((array)$_POST['toSendmail']);

        if (empty($countToSend)) {
            $error++;
            setEventMessage("InvoiceNotCheckedMail", "warnings");
        }

        if (!$error) {
            $nbsent = 0;
            $nbignored = 0;

            $pdf = pdf_getInstance();
            if (class_exists('TCPDF')) {
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
            }

            for ($i = 0; $i < $countToSend; $i++) {
                $object = new Facture($this->db);
                $result = $object->fetch($_POST['toSendmail'][$i]);

                //echo print_r($result);
                if ($result > 0) { // Invoice was found
                    if ($object->statut != Facture::STATUS_VALIDATED) {
                        continue; // Payment done or started or canceled
                    }

                    if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION))
                        $pdf->SetCompression(false);

                    $soc = new Societe($this->db);
                    $soc->fetch($object->socid);
                    $langsout = new Translate('', $conf);
                    if (empty($soc->default_lang))
                        $ll = $langs->defaultlang;
                    else $ll = $soc->default_lang;

                    $langsout->setDefaultLang($ll);
                    $langsout->load("main");

                    self::addFees($object, $_POST['stage_' . $_POST['toSendmail'][$i]], $langsout);
                    $pathdoc = dol_buildpath("invoicetracking/core/modules/facture/doc/");
                    $object->generateDocument('remind:' . $pathdoc, $langsout, 0, 0, 0, array('number' => $_POST['stage_' . $_POST['toSendmail'][$i]]));


                    //$file = $conf->facture->dir_output . '/' . $object->ref . '/' . $object->ref . '.pdf';
                    $file = $conf->facture->dir_output . '/temp/massgeneration/' . $object->ref . '.pdf';

                    if (!empty($conf->global->savereminder)) {
                        $dest = $conf->facture->dir_output . '/' . $object->ref . '/' . $object->ref . '_' . $langs->trans('Reminder') . '-' . dol_print_date(time(), 'dayrfc') . '.pdf';
                        copy($file, $dest);
                    }

                    // Charge un document PDF depuis un fichier.
                    $pagecount = $pdf->setSourceFile($file);
                    for ($j = 1; $j <= $pagecount; $j++) {
                        $tplidx = $pdf->importPage($j);
                        $s = $pdf->getTemplatesize($tplidx);
                        $pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
                        $pdf->useTemplate($tplidx);
                    }

                    $ms = new invoicetracking($this->db);
                    if (empty($save))
                        $ms->saveRemindMail($user, $_POST['stage_' . $_POST['toSendmail'][$i]], $object);

                    if (dol_is_file($file)) {
                        $object->fetch_thirdparty();

                        $langs->load("commercial");

                        $totalpaye = $object->getSommePaiement();
                        $totalcreditnotes = $object->getSumCreditNotesUsed();
                        $totaldeposits = $object->getSumDepositsUsed();
                        if ($object->multicurrency_total_ttc > 0) {
                            $totalpaye = $object->getSommePaiement(1);
                            $totalcreditnotes = $object->getSumCreditNotesUsed(1);
                            $totaldeposits = $object->getSumDepositsUsed(1);
                        }

                        $totalttc = $object->total_ttc;
                        if ($object->multicurrency_total_ttc > 0)
                            $totalttc = $object->multicurrency_total_ttc;

                        $money = $conf->global->MAIN_MONNAIE;
                        if (!empty($object->multicurrency_code)) ;
                        $money = $object->multicurrency_code;

                        $resteapayer = price2num($totalttc - $totalpaye - $totalcreditnotes - $totaldeposits, 'MT');

                        $ad = "ADDING" . $_POST['stage_' . $_POST['toSendmail'][$i]];
                        $ad2 = floatval($conf->global->$ad) / 100;
                        $adtype = "select_ADDING" . $_POST['stage_' . $_POST['toSendmail'][$i]];
                        if ($conf->global->$adtype == 1 && !empty($conf->global->$ad)) {
                            if ($_POST['stage_' . $_POST['toSendmail'][$i]] > 0 && !empty($conf->global->FEESSUP)) {
                                $majo = $resteapayer + ($resteapayer * $ad2) + $conf->global->FEESSUP;
                            } else {
                                $majo = $resteapayer + ($resteapayer * $ad2);
                            }

                            $majotype = "%";
                        } else {
                            if ($_POST['stage_' . $_POST['toSendmail'][$i]] > 0 && !empty($conf->global->FEESSUP)) {
                                $majo = $resteapayer + floatval($conf->global->$ad) + $conf->global->FEESSUP;
                            } else {
                                $majo = $resteapayer + floatval($conf->global->$ad);
                            }

                            $majotype = $conf->global->MAIN_MONNAIE;
                        }

                        $it = "ITREMINDDATE" . $_POST['stage_' . $_POST['toSendmail'][$i]];

                        $nbsent++;
                    } else {
                        $nbignored++;
                        $langs->load("other");
                        $resultmasssend .= '<div class="error">' . $langs->trans('ErrorCantReadFile', $file) . '</div>';
                        dol_syslog('Failed to read file: ' . $file, LOG_WARNING);
                    }
                }
            }

            // Create output dir if not exists
            $diroutputpdf = $conf->facture->dir_output . '/temp/massgeneration/' . $user->id;

            dol_mkdir($diroutputpdf);

            $filename = strtolower(dol_sanitizeFileName($langs->transnoentities("Invoices"))) . '_' . strtolower(dol_sanitizeFileName($langs->transnoentities("Reminder")));


            if ($pagecount) {
                $now = dol_now();
                $file = $diroutputpdf . '/' . $filename . '_' . dol_print_date($now, 'dayrfc') . '.pdf';
                $pdf->Output($file, 'F');
                if (!empty($conf->global->MAIN_UMASK))
                    @chmod($file, octdec($conf->global->MAIN_UMASK));

                $langs->load("exports");
                setEventMessage($langs->trans('FileSuccessfullyBuilt', $filename . '_' . dol_print_date($now, 'dayhourlog')));
            }


            if ($nbsent) {
                $action = ''; // Do not show form post if there was at least one successfull sent
                setEventMessage($nbsent . '/' . $countToSend . ' ' . $langs->trans("RemindPrint"));
                $return = array(0 => 1, 1 => $filename . '_' . dol_print_date($now, 'dayhourlog'), 2 => $file);
                return $return;
            } /*else {
                //setEventMessage($langs->trans("NoRemindSent"), 'warnings');  // May be object has no generated PDF file
                return array();
            }*/
        }
    }

    function itsendmassemail($m = null)
    {
        global $conf, $langs, $user;
        if (!isset($user->email) && !isset($conf->global->ITSENDERMAIL)) {
            $error++;
            setEventMessage("NoSenderEmailDefined");
        }
        $countToSend = count((array)$_POST['toSendemail']);
        if (empty($countToSend)) {
            $error++;
            setEventMessage("InvoiceNotCheckedEmail", "warnings");
        }

        if (!$error) {
            $nbsent = 0;
            $nbignored = 0;
            for ($i = 0; $i < $countToSend; $i++) {
                $object = new Facture($this->db);
                $result = $object->fetch($_POST['toSendemail'][$i]);
                //$pathdoc = dol_buildpath("invoicetracking/core/modules/facture/doc/");
                //$object->generateDocument("", $langs, 0, 0, 0);
                if ($result > 0) { // Invoice was found
                    if ($object->statut != Facture::STATUS_VALIDATED) {
                        continue; // Payment done or started or canceled
                    }

                    self::addFees($object, $_POST['stage_' . $_POST['toSendemail'][$i]], $langs);

                    // Read document
                    // TODO Use future field $object->fullpathdoc to know where is stored default file
                    // TODO If not defined, use $object->modelpdf (or defaut invoice config) to know what is template to use to regenerate doc.
                    $filename = dol_sanitizeFileName($object->ref) . '.pdf';
                    $filedir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($object->ref);
                    $file = $filedir . '/' . $filename;
                    $mime = 'application/pdf';

                    if (dol_is_file($file)) {
                        $sendto = $lastn = $firstn = "";
                        $sendtocc = '';
                        $invoice = new Facture($db);
                        $soc = new Societe($db);
                        $invoice->fetch($object->fk_facture);
                        $soc->fetch($invoice->socid);
                        $contactr = $object->liste_contact(-1, 'external', 0, 'REMINDER');
                        if (!empty($contactr)) {
                            $j = 0;
                            while ($j < count($contactr)) {
                                if (empty($sendto) && !empty($contactr[$j]['email'])) {
                                    $sendto = $contactr[$j]['email'];
                                    $lastn = $contactr[$j]['lastname'];
                                    $firstn = $contactr[$j]['firstname'];
                                } else if (!empty($sendto) && !empty($contactr[$j]['email'])) {
                                    if (!empty($sendtocc))
                                        $sendtocc .= ',';
                                    $sendtocc .= $contactr[$j]['email'];
                                }
                                $j++;
                            }
                        }

                        if (empty($sendto)) {
                            $contactf = $object->liste_contact(-1, 'external', 0, 'BILLING');
                            if (!empty($contactf)) {
                                $j = 0;
                                while ($j < count($contactf)) {
                                    if (empty($sendto) && !empty($contactf[$j]['email'])) {
                                        $sendto = $contactf[$j]['email'];
                                        $lastn = $contactf[$j]['lastname'];
                                        $firstn = $contactf[$j]['firstname'];
                                    } else if (!empty($sendto) && !empty($contactf[$j]['email'])) {
                                        if (!empty($sendtocc))
                                            $sendtocc .= ',';
                                        $sendtocc .= $contactf[$j]['email'];
                                    }
                                    $j++;
                                }
                            }
                        }

                        if (empty($sendto)) {
                            $object->fetch_thirdparty();
                            $sendto = $object->thirdparty->email;
                        }

                        if (empty($sendto))
                            $nbignored++;
                        if (dol_strlen($sendto)) {
                            $langs->load("commercial");

                            $from = $user->getFullName($langs) . ' <' . $user->email . '>';
                            if (!empty($conf->global->ITSENDERMAIL)) {
                                $from = $conf->global->ITSENDERMAIL;
                            }

                            $replyto = $from;
                            //echo $_POST['stage_'.$_POST['toSend'][$i]];
                            $soc = new Societe($this->db);
                            $soc->fetch($object->socid);

                            $langsout = new Translate('', $conf);
                            if (empty($soc->default_lang))
                                $ll = $langs->defaultlang;
                            else $ll = $soc->default_lang;

                            $langsout->setDefaultLang($ll);
                            $langsout->load("main");
                            $s = "ITSUBJECT" . $_POST['stage_' . $_POST['toSendemail'][$i]] . "_" . $ll;
                            $ma = "ITCONTENT" . $_POST['stage_' . $_POST['toSendemail'][$i]] . "_" . $ll;

                            $subject = $conf->global->$s;
                            $message = $conf->global->$ma;

                            (empty(GETPOST('sendtocc')) ? null : GETPOST('sentocc'));
                            $sendtobcc = (empty($conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO) ? '' : $conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO);


                            $totalpaye = $object->getSommePaiement();
                            $totalcreditnotes = $object->getSumCreditNotesUsed();
                            $totaldeposits = $object->getSumDepositsUsed();
                            if ($object->multicurrency_total_ttc > 0) {
                                $totalpaye = $object->getSommePaiement(1);
                                $totalcreditnotes = $object->getSumCreditNotesUsed(1);
                                $totaldeposits = $object->getSumDepositsUsed(1);
                            }

                            $totalttc = $object->total_ttc;
                            if ($object->multicurrency_total_ttc > 0)
                                $totalttc = $object->multicurrency_total_ttc;

                            $money = $conf->global->MAIN_MONNAIE;
                            if (!empty($object->multicurrency_code)) ;
                            $money = $object->multicurrency_code;

                            $resteapayer = price2num($totalttc - $totalpaye - $totalcreditnotes - $totaldeposits, 'MT');
                            // Make substitution
                            $ad = "ADDING" . $_POST['stage_' . $_POST['toSendemail'][$i]];
                            $ad2 = floatval($conf->global->$ad) / 100;
                            $adtype = "select_ADDING" . $_POST['stage_' . $_POST['toSendemail'][$i]];

                            if ($conf->global->$adtype == 1 && !empty($conf->global->$ad)) {
                                if ($stage > 0 && !empty($conf->global->FEESSUP)) {
                                    $majo = $resteapayer + ($resteapayer * $ad2) + $conf->global->FEESSUP;
                                } else {
                                    $majo = $resteapayer + ($resteapayer * $ad2);
                                }

                                $majotype = "%";
                            } else {
                                if ($stage > 0 && !empty($conf->global->FEESSUP)) {
                                    $majo = $resteapayer + floatval($conf->global->$ad) + $conf->global->FEESSUP;
                                } else {
                                    $majo = $resteapayer + floatval($conf->global->$ad);
                                }

                                $majotype = $conf->global->MAIN_MONNAIE;
                            }

                            $it = "ITREMINDDATE" . $_POST['stage_' . $_POST['toSendemail'][$i]];

                            // echo "<pre>".print_r($subject,1)."</pre>";exit;


                            $substitutionarray = array(
                                '__REF_FACTURE__' => $object->ref,
                                '__DATE_FACTURE__' => dol_print_date($object->date, 'daytext', 'tzserver', $langsout),
                                '__TOTAL_TTC__' => price($totalttc),
                                '__MONEY__' => $langs->trans('Currency' . $money),
                                '__DATE_ECHEANCE__' => dol_print_date($object->date_lim_reglement, 'daytext', 'tzserver', $langsout),
                                '__DETTE__' => price($resteapayer),
                                '__MAJORATION__' => $conf->global->$ad,
                                '__MAJORATIONTYPE__' => $majotype,
                                '__FIRSTNAME__' => $firstn,
                                '__NAME__' => $lastn,
                                '__ID__' => $object->id,
                                '__EMAIL__' => $object->thirdparty->email,
                                '__MAJORATION_TOTAL__' => round($majo, 2),
                                '__FRAISSUP__' => $conf->global->FEESSUP,
                                '__RETARD__' => preg_replace("/[^0-9]/", "", $conf->global->$it),
                                '__USER_SIGNATURE__' => (($user->signature && empty($conf->global->MAIN_MAIL_DO_NOT_USE_SIGN)) ? $user->signature : ''),
                                );
                            // Done into actions_sendmails
                            // Set the online payment url link into __ONLINE_PAYMENT_URL__ key
                            require_once DOL_DOCUMENT_ROOT . '/core/lib/payments.lib.php';
                            //$langsout->load('paypal');
                            $typeforonlinepayment = 'free';
                            $url = getOnlinePaymentUrl(0, 'invoice', $object->ref);
                            $paymenturl = $url;

                            $substitutionarray['__ONLINE_PAYMENT_URL__'] = $paymenturl;

                            $subject = make_substitutions($subject, $substitutionarray);
                            $message = make_substitutions($message, $substitutionarray);

                            $actiontypecode = 'AC_EMAIL';
                            $actionmsg = $langs->transnoentities('MailSentBy') . ' ' . $from . ' ' . $langs->transnoentities('To') . ' ' . $sendto;
                            if ($message) {
                                if ($sendtocc)
                                    $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('Bcc') . ": " . $sendtocc);
                                $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic') . ": " . $subject);
                                $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
                                $actionmsg = dol_concatdesc($actionmsg, $message);
                            }

                            // Create form object
                            $attachedfiles = array('paths' => array($file), 'names' => array($filename), 'mimes' => array($mime));
                            $filepath = $attachedfiles['paths'];
                            $filename = $attachedfiles['names'];
                            $mimetype = $attachedfiles['mimes'];

                            //echo "<pre>".print_r($langsout,1)."</pre>";exit;

                            if ($conf->global->INVOICETRACKING_COMMERCIAL_CC) {
                                //prepare sender, receiver, cc and bcc on email
                                $emailComm = "";
                                $commercialArray = [];

                                $contactInvoice = $object->liste_contact(-1, 'internal', 0, 'SALESREPFOL');
                                if (empty($contactInvoice)) {
                                    $contactInvoice = $object->liste_contact(-1, 'internal', 0, 'SALESREPFOLL');
                                }
                                if (!empty($contactInvoice)) {
                                    $j = 0;
                                    while ($j < count($contactInvoice) && empty($emailComm)) {
                                        if (!empty($contactInvoice[$j]['email'])) {
                                            $emailComm = $contactInvoice[$j]['email'];
                                        }
                                        $j++;
                                    }
                                    if (!empty($emailComm)) {
                                        if (!empty($sendtocc))
                                            $sendtocc .= ',';
                                        $sendtocc .= $emailComm;
                                    }
                                }

                                if (empty($emailComm)) {
                                    $sql2 = "SELECT fk_user as rowid FROM " . MAIN_DB_PREFIX . "societe_commerciaux as sc WHERE sc.fk_soc = " . $object->socid;
                                    $resql2 = $this->db->query($sql2);

                                    if ($resql2) {
                                        $obj = $this->db->fetch_object($resql2);
                                        while ($obj != null) {
                                            $commercial = new User($this->db);
                                            $commercial->fetch($obj->rowid);
                                            $commercialArray[] = $commercial;
                                            $obj = $this->db->fetch_object($resql2);
                                        }
                                    }

                                    if (count($commercialArray) > 0) {
                                        $j = 0;
                                        while ($j < count($commercialArray) && empty($emailComm)) {
                                            if (!empty($commercialArray[$j]->email)) {
                                                $emailComm = $commercialArray[$j]->email;
                                            }
                                            $j++;
                                        }
                                        if (!empty($emailComm)) {
                                            if (!empty($sendtocc))
                                                $sendtocc .= ',';
                                            $sendtocc .= $emailComm;
                                        }
                                    }
                                }

                                if (empty($emailComm)) {
                                    $sql2 = "SELECT email FROM " . MAIN_DB_PREFIX . "user as u JOIN " . MAIN_DB_PREFIX . "facture as f ON u.rowid=f.fk_user_author WHERE f.rowid = " . $object->id;
                                    $resql2 = $this->db->query($sql2);
                                    if ($resql2) {
                                        $obj = $this->db->fetch_object($resql2);
                                        if (!empty($obj->email)) {
                                            if (!empty($sendtocc))
                                                $sendtocc .= ',';
                                            $sendtocc .= $obj->email;
                                        }
                                    }
                                }
                            }
                            // Send mail
                            require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
                            $mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc, $sendtobcc, $deliveryreceipt, -1);

                            if ($mailfile->error) {
                                $resultmasssend .= '<div class="error">' . $mailfile->error . '</div>';
                            } else {
                                $result = $mailfile->sendfile();
                                if ($result) {
                                    if (!$m) {
                                        $ms2 = new invoicetracking($this->db);
                                        $ms2->saveRemindEmail($user, $_POST['stage_' . $_POST['toSendemail'][$i]], $object);
                                    }

                                    $resultmasssend .= $langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($from, 2), $mailfile->getValidAddress($sendto, 2));  // Must not contain "

                                    $error = 0;

                                    // Initialisation donnees
                                    $object->sendtoid = 0;
                                    $object->actiontypecode = $actiontypecode;
                                    $object->actionmsg = $actionmsg;  // Long text
                                    $object->actionmsg2 = $actionmsg2; // Short text
                                    $object->fk_element = $object->id;
                                    $object->elementtype = $object->element;


                                    // Appel des triggers
                                    include_once DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php";
                                    $interface = new Interfaces($this->db);
                                    $result = $interface->run_triggers('BILL_SENTBYMAIL', $object, $user, $langs, $conf);
                                    // Fin appel triggers

                                    if (!$error) {
                                        $resultmasssend .= $langs->trans("MailSent") . ': ' . $sendto . "<br>";
                                    } else {
                                        dol_print_error($this->db);
                                    }

                                    $nbsent++;
                                } else {
                                    $langs->load("other");
                                    if ($mailfile->error) {
                                        $resultmasssend .= $langs->trans('ErrorFailedToSendMail', $from, $sendto);
                                        $resultmasssend .= '<br><div class="error">' . $mailfile->error . '</div>';
                                    } else {
                                        $resultmasssend .= '<div class="warning">No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS</div>';
                                    }
                                }
                            }
                        }
                    } else {
                        $nbignored++;
                        $langs->load("other");
                        $resultmasssend .= '<div class="error">' . $langs->trans('ErrorCantReadFile', $file) . '</div>';
                        dol_syslog('Failed to read file: ' . $file, LOG_WARNING);
                    }
                }
            }

            if ($nbsent) {
                $action = ''; // Do not show form post if there was at least one successfull sent
                setEventMessage($nbsent . '/' . $countToSend . ' ' . $langs->trans("RemindSent"));
            } /*else {
                setEventMessage($langs->trans("NoRemindSent"), 'warnings');  // May be object has no generated PDF file
            }*/
        }
    }

    function generateTracking($obj, $stage)
    {

        $filename = dol_sanitizeFileName($obj->ref) . '.pdf';
        $filedir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($obj->ref);
    }

    function addFees($object, $stage, $lang)
    {
        global $user, $conf, $langs;

        $authorizedCountry = ["Suisse"];
        $country = explode(":", $conf->global->MAIN_INFO_SOCIETE_COUNTRY);

        if (! in_array($country[2], $authorizedCountry)) {
            return 0;
        }

        $lang->load('invoicetracking@invoicetracking');
        $totalpaye = 0;
        $po = "prod_ADDING" . $stage;
        require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

        if (is_a($object, 'Facture') && ($conf->global->$po == 1)) {
            // On verifie si la facture a des paiements
            $sql = 'SELECT pf.amount';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'paiement_facture as pf';
            $sql .= ' WHERE pf.fk_facture = ' . $object->id;

            $result = $this->db->query($sql);
            if ($result) {
                $i = 0;
                $num = $this->db->num_rows($result);

                while ($i < $num) {
                    $objp = $this->db->fetch_object($result);
                    $totalpaye += $objp->amount;
                    $i++;
                }
            } else {
                dol_print_error($this->db, '');
            }

            // echo "<pre>".print_r($totalpaye,1)."</pre>";exit();

            if ($totalpaye == 0) {
                if ((int) DOL_VERSION > 9) {
                    $object->setDraft($user);
                } else {
                    $object->set_draft($user);
                }
                $ad = "ADDING" . $stage;
                $typead = "select_ADDING" . $stage;
                //  function addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil=0, $info_bits=0, $fk_remise_except='', $price_base_type='HT', $pu_ttc=0, $type=self::TYPE_STANDARD, $rang=-1, $special_code=0, $origin='', $origin_id=0, $fk_parent_line=0, $fk_fournprice=null, $pa_ht=0, $label='', $array_options=0, $situation_percent=100, $fk_prev_id=0, $fk_unit = null, $pu_ht_devise = 0)
                $desc = $lang->trans('AddFeesTextOnInvoice', dol_print_date(time(), 'daytext'));
                if ($conf->global->$typead == 1) {
                    $pu = $object->total_ht * $conf->global->$ad / 100;
                } else {
                    $pu = $conf->global->$ad;
                }               //
                $prod = new Product($this->db);

                $prod->fetch($conf->global->addingprod);
                $object->addline($desc, $pu, 1, $prod->tva_tx, 0, 0, $conf->global->addingprod);
                $object->update_price();
                $object->validate($user);
                $object->generateDocument($object->modelpdf, $langs);
            }
        }
    }

    function selectReminder($stage)
    {
        switch ($stage) {
            case '':
                $level = "-";
                break;
            case 0:
                $level = "ITBeforereminder";
                break;
            case 1:
                $level = "ITSimplereminder";
                break;
            case 2:
                $level = "ITStamplereminder";
                break;
            case 3:
            case 4:
                $level = "ITLastreminder";
                break;
            default:
                $level = "-";
        }
        return $level;
    }
}
