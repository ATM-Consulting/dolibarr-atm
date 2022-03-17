<?php
/* Copyright (C) 2021 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    evenementchaineinfo/class/actions_evenementchaineinfo.class.php
 * \ingroup evenementchaineinfo
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

use Stripe\SubscriptionSchedule;

/**
 * Class ActionsEvenementChaineInfo
 */
class ActionsEvenementChaineInfo
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs, $db;

		$error = 0; // Error counter
		
		// Overwrite the buttons Event and Intervention in the page ticketcard
		if (in_array($parameters['currentcontext'], array('ticketcard')))	    // do something only for the context 'somecontext1' or 'somecontext2'
		{
			$db->query("SET SQL_MODE=''");

			$sql = "SELECT address, zip, town FROM " . MAIN_DB_PREFIX . "societe WHERE rowid = " . $object->fk_soc;
			$resql = $db->query($sql);
			if($resql && $resql->num_rows > 0){
				$resql = $db->fetch_object($resql);
				$lieu = urlencode($resql->address .', '. $resql->zip . ' ' . $resql->town);
			}
			
			$sql2 = "SELECT ec.fk_socpeople FROM " . MAIN_DB_PREFIX . "element_contact ec";
			$sql2.=" INNER JOIN " . MAIN_DB_PREFIX . "c_type_contact ctc ON ec.fk_c_type_contact = ctc.rowid";
			$sql2.=" INNER JOIN " . MAIN_DB_PREFIX . "ticket t ON ec.element_id = t.rowid";
			$sql2.=" WHERE ctc.element = 'ticket' AND ctc.source = 'external' AND t.ref = '" . $object->ref ."'";

			$resql2 = $db->query($sql2);
			if($resql2 && $resql2->num_rows > 0){
				$resql2 = $db->fetch_object($resql2);
				$contact = $resql2->fk_socpeople;
			}

			$description = urlencode($object->message);
			$assign = urlencode($object->fk_user_assign);
			$label = urlencode($object->subject);
			$epoch = '';
			if($object->array_options['options_dateplanifie']){
				$epoch = $object->array_options['options_dateplanifie'];
				$datep = date('YmdHi', $epoch);
				$datep = urlencode($datep);
			}

			$assigned_user = array(
				$user->id => array('id' => $user->id, 'mandatory' => 0, 'transparency' => 1),
			);
			if ($assign){
				$assigned_user[$object->fk_user_assign] = array('id' => $object->fk_user_assign, 'mandatory' => 0, 'transparency' => 1);
			}

			$assigned_user = base64_encode(json_encode($assigned_user));

			print <<<SCRIPT
				<script type="text/javascript">
						jQuery(document).ready(function () {
							var event = jQuery('a[href*="/comm/action/card.php?action=create"]');
							event.attr('href', event.attr('href') + '&label={$label}&note={$description}&location={$lieu}&datep={$datep}&socpeopleassigned[]={$contact}&ec_assigned_user={$assigned_user}&donotclearsession=1');
							var event = jQuery('a[href*="/fichinter/card.php?action=create"]');
							event.attr('href', event.attr('href') + '&description={$description}&note_public={$label}');
						});
				</script>
			SCRIPT;
		}
		// Create a button for the intervention in the Event
		if (in_array($parameters['currentcontext'], array('actioncard')))
		{
			// socid is needed otherwise fichinter ask it and forgot origin after form submit
			if ($object->socid > 0 && $object->fk_statut < Ticket::STATUS_CLOSED && $user->rights->ficheinter->creer) {
				print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/fichinter/card.php', 1).'?action=create&socid='.$object->socid.'&origin=ticket_ticket&originid='.$object->elementid.'&description='.$object->note_private.'&note_public='.$object->label.'">'.$langs->trans('TicketAddIntervention').'</a></div>';
			}
		}

		if (!$error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	public function doActions($parameters, &$object, &$action, $hookmanager){
		if (in_array($parameters['currentcontext'], array('actioncard'))) {
			if (GETPOSTISSET('ec_assigned_user')) {
				$_SESSION['assignedtouser'] = base64_decode(GETPOST('ec_assigned_user'));
			}
		 }
	 }
}
