<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
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
 *  \file       htdocs/prixmassecategorie/class/prixmassecategorie.class.php
 *  \ingroup    prixmassecategorie
 *  \brief      File of class to manage predefined products sets
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
/**
 * Class to manage products or services
 */
class PrixMasseCategorie extends CommonObject
{
	public $element='prixmassecategorie';
	public $table_element='prixmassecategorie';
	public $fk_element='fk_prixmassecategoriecategorie';
	public $picto = 'generic';
	public $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'id';

	/**
     * Object id
     * @var string
     */
	public $fk_object = 0;

    /**
     * Price level
     * @var int
     */
    public $level = 0;

	/**
     * Percent of price
     * @var double
     */
	public $percent = 0;

	/**
     * Target price
     * @var double
     */
	public $price_ht = 0;

	/**
     * Id of user who created modification
     * @var string
     */
	public $fk_user_author = 0;

	/**
     * Product set ref
     * @var string
     */
	public $id = 0;

	
	/**
     * Product set ref
     * @var string
     */
	public $mods = array();


	/**
	 * @var PrixMasseCategorieLine[]
	 */
	public $lines = array();

	const OPERATOR_AND  = 'and';
	const OPERATOR_BUT  = 'but';

	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $langs;

		$this->db = $db;
	}

	/**
	 *    Check that ref and label are ok
	 *
	 *    @return     int         >1 if OK, <=0 if KO
	 */
	function check()
	{
		$err = 0;

		if ($this->fk_object < 0)
		$err++;

		if ($err > 0)
		{
			return 0;
		}
		else
		{
			return 1;
		}
	}

    /**
     *    Check properties of product are ok (like name, barcode, ...).
     *    All properties must be already loaded on object (this->barcode, this->barcode_type_code, ...).
     *
     *    @return     int		0 if OK, <0 if KO
     */
    function verify()
    {
        $this->errors=array();

        $result = 0;

        return $result;
    }

	/**
	 *	Insert product into database
	 *
	 *	@param	User	$user     		User making insert
	 *  @param	int		$notrigger		Disable triggers
	 *	@return int			     		Id of product/service if OK, < 0 if KO
	 */
	function create($user,$products,$notrigger=0)
	{
		global $conf, $langs;

        $error=0;

		dol_syslog(get_class($this)."::create", LOG_DEBUG);

		$now=dol_now();

		$this->db->begin();

		// Check more parameters
		// If error, this->errors[] is filled
		$result = $this->verify();

		if ($result >= 0)
		{

			$this->fk_user_author = $user->id;

			// Produit non deja existant
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."prixmassecategorie (";
			$sql.= "datec";
			$sql.= ", tms";
			$sql.= ", entity";
			$sql.= ", fk_object";
            $sql.= ", level";
			$sql.= ", fk_user_author";
			$sql.= ", price_ht";
			$sql.= ", percent";
			$sql.= ") VALUES (";
			$sql.= "'".$this->db->idate($now)."'";
			$sql.= ", '".$this->db->idate($now)."'";
			$sql.= ", ".$conf->entity;
			$sql.= ", '".$this->db->escape($this->fk_object)."'";
            $sql.= ", ".$this->level;
			$sql.= ", ".$this->fk_user_author;
			$sql.= ", ".$this->price_ht;
			$sql.= ", ".$this->percent;
			$sql.= ")";

			dol_syslog(get_class($this)."::create", LOG_DEBUG);
			$result = $this->db->query($sql);
			if ( $result )
			{
				$id = $this->db->last_insert_id(MAIN_DB_PREFIX."prixmassecategorie");

				if ($id > 0)
				{
					$this->id = $id;

					if (count($products) > 0)
					{
						foreach ($products as $product)
						{
                            $max_level = !empty($conf->global->PRODUIT_MULTIPRICES) ? $conf->global->PRODUIT_MULTIPRICES_LIMIT : 0;

                            if ($this->level == 0 && $max_level > 0) {
                                for ($level = 1; $level <= $max_level; $level++) {

                                    if (isset($product->multiprices[$level])) {
                                        $price_ht_before = $product->multiprices[$level];
                                    } else {
                                        $price_ht_before = $product->price;
                                    }

                                    if ($this->price_ht > 0) {
                                       $product->updatePrice($this->price_ht, 'HT', $user, '', 0, $level);
                                    } else if ($this->percent != 0) {
                                        $price_ht_before = isset($product->multiprices[$level]) ? $product->multiprices[$level] : 0;
                                        $price_ht = $price_ht_before * (1 + $this->percent/100);
                                        $product->updatePrice($price_ht, 'HT', $user, '', 0, $level);
                                    }

                                    if (isset($product->multiprices[$level])) {
                                        $price_ht_after = $product->multiprices[$level];
                                    } else {
                                        $price_ht_after = $product->price;
                                    }

                                    $line = new PrixMasseCategorieLine($this->db);
                                    $line->fk_prixmassecategorie    = $this->id;
                                    $line->fk_product      			= $product->id;
                                    $line->level      			    = $level;
                                    $line->price_ht_before          = $price_ht_before;
                                    $line->price_ht_after           = $price_ht_after;

                                    if ($line->insert($user) < 0)
                                    {
                                        $error++;
                                    }
                                }
                            } else {
                                if (isset($product->multiprices[$this->level])) {
                                    $price_ht_before = $product->multiprices[$this->level];
                                } else {
                                    $price_ht_before = $product->price;
                                }

                                if ($this->price_ht > 0) {
                                    $product->updatePrice($this->price_ht, 'HT', $user, '', 0, $this->level);
                                } else if ($this->percent != 0) {
                                    $price_ht = $price_ht_before * (1 + $this->percent/100);
                                    $product->updatePrice($price_ht, 'HT', $user, '', 0, $this->level);
                                }

                                if (isset($product->multiprices[$this->level])) {
                                    $price_ht_after = $product->multiprices[$this->level];
                                } else {
                                    $price_ht_after = $product->price;
                                }

                                $line = new PrixMasseCategorieLine($this->db);
                                $line->fk_prixmassecategorie    = $this->id;
                                $line->fk_product      			= $product->id;
                                $line->level                    = $this->level;
                                $line->price_ht_before          = $price_ht_before;
                                $line->price_ht_after           = $price_ht_after;

                                if ($line->insert($user) < 0)
                                {
                                    $error++;
                                }
                            }
						}
					}

				}
				else
				{
					$error++;
					$this->error='ErrorFailedToGetInsertedId';
				}
			}
			else
			{
				$error++;
				$this->error=$this->db->lasterror();
			}


			if (! $error)
			{
				$this->db->commit();
				return $this->id;
			}
			else
			{
				$this->db->rollback();
				return -$error;
			}
        }
        else
       {
            $this->db->rollback();
            dol_syslog(get_class($this)."::Create fails verify ".join(',',$this->errors), LOG_WARNING);
            return -3;
        }

	}


	/**
	 *  Delete a product set from database (if not used)
	 *
	 *	@param      User	$user       Product id (usage of this is deprecated, delete should be called without parameters on a fetched object)
	 *  @param      int     $notrigger  Do not execute trigger
	 * 	@return		int					< 0 if KO, 0 = Not possible, > 0 if OK
	 */
	function delete(User $user, $notrigger=0)
	{
        global $conf, $langs;

        // Deprecation warning
		if ($id > 0) {
			dol_syslog(__METHOD__ . " with parameter is deprecated", LOG_WARNING);
		}

		$error=0;

		// Clean parameters
		if (empty($id)) $id=$this->id;


		// Check parameters
		if (empty($id))
		{
			$this->error = "Object must be fetched before calling delete";
			return -1;
		}

		$this->fetch($id);

		
		$this->db->begin();

		if (! $error) {
			if (count($this->lines) > 0) {
				foreach ($this->lines as $pid => $lines) {
					$product = new Product($this->db);
					if ($product->fetch($pid) > 0) {
					    if (count($lines)) {
					        foreach ($lines as $line) {
                                $price_ht_before = $line->price_ht_before;
                                if ($product->updatePrice($price_ht_before, 'HT', $user, '', 0, $line->level) > 0) {
                                    if ($line->delete($user) < 0) {
                                        $error++;
                                    }
                                } else {
                                    $error++;
                                }
                            }
                        }
					}
				}
			}
		}	

		if (! $error) {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."prixmassecategorie WHERE rowid=".$this->id;

            dol_syslog(get_class($this)."::delete", LOG_DEBUG);
            $this->db->query($sql);

			$this->db->commit();
			return 1;
		} else {
			foreach($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -$error;
		}

	}

    /**
     *  Load a product set in memory from database
     *
     * @param string $id Id of product/service to load
     * @param string $fk_object
     * @return int                        <0 if KO, 0 if not found, >0 if OK
     */
	function fetch($id = '', $fk_object='')
	{
		global $langs, $conf;

		dol_syslog(get_class($this)."::fetch fk_object=".$fk_object);

		// Check parameters
		if (!$id && ! $fk_object) {
			$this->error='ErrorWrongParameters';
			dol_print_error(get_class($this)."::fetch ".$this->error);
			return -1;
		}

		$sql = "SELECT rowid, percent, fk_object, fk_user_author, level, price_ht, datec, tms";
		$sql.= " FROM ".MAIN_DB_PREFIX."prixmassecategorie";
		$sql.= " WHERE entity IN (".getEntity($this->element).")";
		$sql.= !empty($id) ? " AND rowid = ".(int)$id : "";
		$sql.= !empty($fk_object) ? " AND fk_object = '".$this->db->escape($fk_object)."'" : "";
		$sql.= " ORDER BY rowid DESC";

		$resql = $this->db->query($sql);
		if ( $resql ) {
			if ($this->db->num_rows($resql) > 0) {
				if ($id) {
					if ($obj = $this->db->fetch_object($resql)) {
						$userstatic = new User($this->db);
						$userstatic->fetch($obj->fk_user_author);

						$this->id					= $obj->rowid;
						$this->percent				= $obj->percent;
                        $this->level				= $obj->level;
                        $this->price_ht				= $obj->price_ht;
						$this->fk_object			= $obj->fk_object;
						$this->fk_user_author		= $obj->fk_user_author;
						$this->fk_user				= $userstatic->getNomUrl();

						$this->date_creation			= $this->db->jdate($obj->datec);
						$this->date_modification		= $this->db->jdate($obj->tms);

						
						/*
						* Lines
						*/
						$result = $this->fetch_lines();
					}
				} else {
					$this->mods = array();

					while ($obj = $this->db->fetch_object($resql)) {
						$userstatic = new User($this->db);
						$userstatic->fetch($obj->fk_user_author);

						$mod = new stdClass();

						$mod->id					= $obj->rowid;
						$mod->percent				= $obj->percent;
                        $mod->level				    = $obj->level;
						$mod->fk_object				= $obj->fk_object;
						$mod->fk_user_author		= $obj->fk_user_author;
						$mod->price_ht				= $obj->price_ht;
						
						$mod->fk_user				= $userstatic->getNomUrl();

						$mod->date_creation			= $this->db->jdate($obj->datec);
						$mod->date_modification		= $this->db->jdate($obj->tms);

						$this->mods[] = $mod;
					}					
				}


				$this->db->free($resql);

				return 1;
			} else {
				return 0;
			}
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Load array lines
	 *
	 *	@return		int						<0 if KO, >0 if OK
	 */
	function fetch_lines()
	{
        // phpcs:enable
		$this->lines = array();

		$sql = "SELECT l.rowid, l.fk_prixmassecategorie, l.fk_product, l.level, l.price_ht_before, l.price_ht_after, ";
		$sql.= " l.datec, l.fk_user_author, l.tms";
		$sql.= " FROM ".MAIN_DB_PREFIX."prixmassecategoriedet as l";
		$sql.= " WHERE l.fk_prixmassecategorie = ".$this->id;

		dol_syslog(get_class($this)."::fetch_lines", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);

			$i = 0;
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($result);

				$line = new PrixMasseCategorieLine($this->db);

				$line->rowid            = $objp->rowid;
				$line->id               = $objp->rowid;
				$line->fk_prixmassecategorie      	= $objp->fk_prixmassecategorie;
				$line->fk_product      			= $objp->fk_product;
                $line->level      			= $objp->level;
				$line->price_ht_before             = $objp->price_ht_before;
				$line->price_ht_after            = $objp->price_ht_after;

				$line->user_author_id   = $objp->fk_user_author;
				$line->datec       		= $this->db->jdate($objp->datec);
				$line->tms       		= $this->db->jdate($objp->tms);

                $this->lines[$line->fk_product][] = $line;

				$i++;
			}

			$this->db->free($result);

			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -3;
		}
	}

	/**
	 *	Return label of status of object
	 *
	 *	@param      int	$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return     string      	Label of status
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($mode);

	}

	/**
	 *	Return label of a given status
	 *
	 *	@param      int		$status     Statut
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return     string      		Label of status
	 */
	function LibStatut($mode=0)
	{
		global $conf, $langs;

		$langs->load('prixmassecategorie@prixmassecategorie');

		return '';
	}

	/**
	 *  Load operators
	 *
	 *  @return array
	 */
	function getOperators()
	{
		global $langs, $conf;

		dol_syslog(get_class($this)."::getOperators");

		$operators = array(
			self::OPERATOR_AND => $langs->trans('ModAnd'),
			self::OPERATOR_BUT => $langs->trans('ModBut'),
		);

		return $operators;
	}

    /**
     *  Load levels
     *
     *  @return array
     */
    function getLevels()
    {
        global $langs, $conf;

        dol_syslog(get_class($this)."::getLevels");

        $levels = array();

        $levels[0] = $langs->trans('AllLevels');

        if (!empty($conf->global->PRODUIT_MULTIPRICES)) {
            for ($i = 1; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++) {
                $levels[$i] = $langs->trans('LevelI', $i);
            }
        }

        return $levels;
    }
}

/**
 *  Class to manage prixmasse lines
 */
class PrixMasseCategorieLine extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element='prixmassecategoriedet';

	public $table_element='prixmassecategoriedet';

	var $oldline;

	/**
	 * Id of prix masse
	 * @var int
	 */
	public $fk_prixmassecategoriecategorie;

	/**
	 * Id of object
	 * @var int
	 */
	public $fk_product;

    /**
     * Price level
     * @var int
     */
    public $level = 0;

	/**
     * Propal HT before price mod
     * @var double
     */
	public $price_ht_before = 0;

	/**
     * Propal HT after price mod
     * @var double
     */
	public $price_ht_after = 0;

	/**
	 * Creation date
	 * @var int
	 */
	public $datec;

	/**
	 * User author id
	 * @var int
	 */
	public $fk_user_author;


	/**
	 *      Constructor
	 *
	 *      @param     DoliDB	$db      handler d'acces base de donnee
	 */
	function __construct($db)
	{
		$this->db= $db;
	}

	/**
	 *  Load line prixmasse
	 *
	 *  @param  int		$rowid          Id line order
	 *  @return	int						<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		$sql = "SELECT l.rowid, l.fk_prixmassecategorie, l.level, l.price_ht_before, l.price_ht_after, l.fk_product, l.fk_user_author, l.datec, l.tms";
		$sql.= " FROM ".MAIN_DB_PREFIX."prixmassecategoriedet as l";
		$sql.= " WHERE l.rowid = ".$rowid;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);

		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);

			if ($num > 0)
			{
				$objp = $this->db->fetch_object($result);

				$this->rowid           	 	= $objp->rowid;
				$this->id               		= $objp->rowid;
				$this->fk_prixmassecategorie     = $objp->fk_prixmassecategorie;
				$this->fk_product     		= $objp->fk_product;
                $this->level     		    = $objp->level;
				$this->price_ht_after     	= $objp->price_ht_after;
				$this->price_ht_before     	= $objp->price_ht_before;
				$this->fk_user_author		= $objp->fk_user_author;
				$this->datec       		= $this->db->jdate($objp->datec);
				$this->tms       		= $this->db->jdate($objp->tms);	
				
				$this->db->free($result);

				return 1;
			}
			else
			{
				return 0;
			}			
		}
		else
		{
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * 	Delete line in database
	 *
	 *	@param      User	$user        	User that modify
	 *  @param      int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *	@return	 int  <0 si ko, >0 si ok
	 */
	function delete($user=null, $notrigger=0)
	{
		global $conf, $user, $langs;

		$error=0;

		$this->db->begin();

		$sql = 'DELETE FROM '.MAIN_DB_PREFIX."prixmassecategoriedet WHERE rowid=".$this->rowid;

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);

		$resql=$this->db->query($sql);
		if ($resql)
		{
			if (! $error && ! $notrigger)
			{
				// Call trigger
				$result=$this->call_trigger('LINEPRIXMASSECATEGORIE_DELETE',$user);
				if ($result < 0) $error++;
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			}

			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Insert line into database
	 *
	 *	@param      User	$user        	User that modify
	 *	@param      int		$notrigger		1 = disable triggers
	 *	@return		int						<0 if KO, >0 if OK
	 */
	function insert($user=null, $notrigger=0)
	{
		global $langs, $conf;

		$error = 0;

		dol_syslog(get_class($this)."::insert rang=".$this->rang);


		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."prixmassecategoriedet (";
		$sql.= " fk_prixmassecategorie,";
		$sql.= " fk_product,";
        $sql.= " level,";
		$sql.= " price_ht_before,";
		$sql.= " price_ht_after,";
		$sql.= " fk_user_author,";
		$sql.= " datec,";
		$sql.= " tms";
		$sql.= " )";
		$sql.= " VALUES (";
		$sql.= " ".$this->fk_prixmassecategorie.",";
		$sql.= " ".$this->fk_product.",";
        $sql.= " ".$this->level.",";
		$sql.= " ".$this->price_ht_before.",";
		$sql.= " ".$this->price_ht_after.",";
		$sql.= " ".$user->id.",";
		$sql.= "'" . $this->db->idate(dol_now()) . "',";
		$sql.= "'" . $this->db->idate(dol_now()) . "'";
		$sql.= ")";

		dol_syslog(get_class($this)."::insert", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->rowid=$this->db->last_insert_id(MAIN_DB_PREFIX.'prixmassecategoriedet');

			if (! $error && ! $notrigger)
			{
				// Call trigger
				$result=$this->call_trigger('LINEPRIXMASSECATEGORIE_INSERT',$user);
				if ($result < 0) $error++;
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			}

			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -2;
		}
	}
}
