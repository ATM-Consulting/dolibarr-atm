<?php
/* Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
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
 * \file    htdocs/demat4dolibarr/class/ededocfile.class.php
 * \ingroup demat4dolibarr
 * \brief
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php';
if (!class_exists('ComposerAutoloaderInite5f8183b6b110d1bbf5388358e7ebc94', false)) dol_include_once('/demat4dolibarr/vendor/autoload.php');
dol_include_once('/demat4dolibarr/class/module_key/opendsimodulekeyd4d.class.php');
dol_include_once('/advancedictionaries/class/dictionary.class.php');
use OAuth\Common\Storage\DoliStorage;
use OAuth\OAuth2\Token\StdOAuth2Token;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


/**
 * Class EdeDocFile
 *
 * Put here description of your class
 */
class EdeDoc
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;
    /**
     * @var string Error
     */
    public $error = '';
    /**
     * @var array Errors
     */
    public $errors = array();

	/**
	 * Cache of job status list
	 * @var DictionaryLine[]
	 */
	static public $job_status_list;
	/**
	 * Cache of chorus status list
	 * @var DictionaryLine[]
	 */
	static public $chorus_status_list;

	/**
	 * @var Client  Client REST handler for get access token
	 */
	public $token_client;
	/**
	 * @var Client  Client REST handler
	 */
	public $client;
	/**
	 * @var string  Authentication API URL
	 */
	public $authentication_api_url;
	/**
	 * @var string  API URL
	 */
	public $api_url;
	/**
	 * @var string  Authorization basic
	 */
	public $authorization_basic;
	/**
	 * @var string  User username
	 */
	public $user_username;
	/**
	 * @var string  User password
	 */
	public $user_password;
	/**
	 * @var string  GUID of the document folder
	 */
	public $document_type_id;
	/**
	 * @var int      Max request for the excess warning
	 */
	public $max_request;

	/**
	 * @var array      Cache of billing mode (ID => codes)
	 */
	public static $billing_mode_ids;
	/**
	 * @var array      Cache of job status (codes => ID)
	 */
	public static $job_status_codes;
	/**
	 * @var array      Cache of chorus status (codes => ID)
	 */
	public static $chorus_status_codes;
	/**
	 * @var array      Cache of chorus status (codes => ID)
	 */
	public static $invoice_status_codes;

	const SERVICE_NAME = 'EdeDoc';

	const METHOD_GET = 'GET';
	const METHOD_HEAD = 'HEAD';
	const METHOD_DELETE = 'DELETE';
	const METHOD_PUT = 'PUT';
	const METHOD_PATCH = 'PATCH';
	const METHOD_POST = 'POST';
	const METHOD_OPTIONS = 'OPTIONS';

    /**
     * Constructor
     *
     * @param        DoliDB $db Database handler
     */
    public function __construct($db)
    {
	    global $conf;
        $this->db = $db;

		$result = OpenDsiModuleKeyD4D::decode($conf->global->DEMAT4DOLIBARR_MODULE_KEY);
		if (!empty($result['error'])) {
			setEventMessage($result['error'], 'errors');
		} else {
			$module_key_infos = $result['key'];
			$this->authentication_api_url = $module_key_infos->authentication_api_url;
			$this->api_url = $module_key_infos->api_url;
			$this->authorization_basic = $module_key_infos->api_key;
			$this->user_username = $module_key_infos->user_username;
			$this->user_password = $module_key_infos->user_password;
			$this->document_type_id = $module_key_infos->document_type_id;
			$this->max_request = $module_key_infos->pack_chorus;
		}
    }

	/**
	 *  Connect to the EDEDOC API
	 *
	 * @return	int		                <0 if KO, >0 if OK
	 */
    public function connection()
    {
	    global $conf, $langs;
	    dol_syslog(__METHOD__, LOG_DEBUG);
	    $this->errors = array();

	    if (empty($conf->global->DEMAT4DOLIBARR_PROVIDER_CODE) || empty($conf->global->DEMAT4DOLIBARR_MODULE_KEY)) {
		    $langs->load('demat4dolibarr@demat4dolibarr');
		    $this->errors[] = $langs->trans("Demat4DolibarrErrorModuleNotConfigured");
		    dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
		    return -1;
	    }

	    try {
		    $this->token_client = new Client([
			    // Base URI is used with relative requests
			    'base_uri' => $this->authentication_api_url,
			    // You can set any number of default request options.
			    'timeout' => $conf->global->DEMAT4DOLIBARR_API_TIMEOUT,
		    ]);
		    $this->client = new Client([
			    // Base URI is used with relative requests
			    'base_uri' => $this->api_url,
			    // You can set any number of default request options.
			    'timeout' => $conf->global->DEMAT4DOLIBARR_API_TIMEOUT,
		    ]);
	    } catch (Exception $e) {
		    $this->errors[] = $e->getMessage();
		    dol_syslog(__METHOD__ . " Error: " . $e, LOG_ERR);
		    return -1;
	    }

	    return 1;
    }

	/**
	 *  Get the access token for EdeDoc API
	 * @return	string|int		            <0 if KO, Access token for the API
	 */
	public function getAccessToken()
	{
		global $conf;
		dol_syslog(__METHOD__, LOG_DEBUG);

		$storage = new DoliStorage($this->db, $conf);

		try {
			// Check if we have auth token
			$token = $storage->retrieveAccessToken(self::SERVICE_NAME . '_' . $conf->entity);
		} catch (Exception $e) {
			if ('Token not found in db, are you sure you stored it?' != $e->getMessage()) {
				$this->errors[] = $e->getMessage();
				dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
				return -1;
			} else {
				// Retrieve access token from EdeDoc
				$token = $this->retrieveAccessToken();
				if (!is_object($token)) {
					return -1;
				}
			}
		}

		// Is token expired or will token expire in the next 30 seconds
		$expire = ($token->getEndOfLife() !== -9002 && $token->getEndOfLife() !== -9001 && time() > ($token->getEndOfLife() - 30));

		// Token expired so we refresh it
		if ($expire) {
			// Retrieve access token from EdeDoc
			$token = $this->retrieveAccessToken();
			if (!is_object($token)) {
				return -1;
			}
		}

		return $token->getAccessToken();
	}

	/**
	 *  Retrieve the access token from EdeDoc API
	 * @return	string|int		            <0 if KO, Access token for the API
	 */
	public function retrieveAccessToken()
	{
		global $conf, $langs;
		dol_syslog(__METHOD__, LOG_DEBUG);

		try {
			$response = $this->token_client->post('/provider/connect/token', [
				'headers' => ['Authorization' => 'Basic  ' . $this->authorization_basic],
				GuzzleHttp\RequestOptions::FORM_PARAMS => [
					'grant_type' => 'password',
					'username'=> $this->user_username,
					'password'=> $this->user_password,
					'scope'=> 'api.client.full',
				]
			]);
			$results = json_decode($response->getBody()->getContents(), true);
		} catch (RequestException $e) {
			$request = $e->getRequest();
			$response = $e->getResponse();

			$errors_details = array();
			if (isset($request)) $errors_details[] = $this->_requestToString($request);
			if (isset($response)) $errors_details[] = $this->_responseToString($response);
			else $errors_details[] = '<pre>' . dol_nl2br((string)$e) . '</pre>';

			if (!empty($conf->global->DEMAT4DOLIBARR_DEBUG)) {
				$this->errors = array_merge($this->errors, $errors_details);
			} else {
                if (isset($response)) {
                    $boby = $response->getBody();
                    $this->errors[] = '<b>' . $langs->trans('Demat4DolibarrResponseCode') . ': </b>' . $response->getStatusCode() . '<br>' .
                        '<b>' . $langs->trans('Demat4DolibarrResponseReasonPhrase') . ': </b>' . $response->getReasonPhrase() .
                        (!empty($boby) ? '<br>' . $boby : '');
                } else $this->errors[] = $e->getMessage();
			}

			dol_syslog(__METHOD__ . " Error: " . dol_htmlentitiesbr_decode(implode(', ', $errors_details)), LOG_ERR);
			return -1;
		} catch (Exception $e) {
			if (!empty($conf->global->DEMAT4DOLIBARR_DEBUG)) {
				$this->errors[] = (string)$e;
			} else {
				$this->errors[] = $e->getMessage();
			}

			dol_syslog(__METHOD__ . " Error: " . $e, LOG_ERR);
			return -1;
		}

		$storage = new DoliStorage($this->db, $conf);
		$token = new StdOAuth2Token();

		$token->setAccessToken($results['access_token']);
		$token->setLifetime($results['expires_in']);
		$token->setExtraParams($results);

		$storage->storeAccessToken(self::SERVICE_NAME . '_' . $conf->entity, $token);

		return $token;
	}

	/**
	 *  Delete the access token
	 * @return	int		                <0 if KO, >0 if OK
	 */
	public function deleteAccessToken()
	{
		global $conf;
		dol_syslog(__METHOD__, LOG_DEBUG);

		$storage = new DoliStorage($this->db, $conf);
		$storage->clearToken(self::SERVICE_NAME . '_' . $conf->entity);

		return 1;
	}

	/**
	 *  Send document to EdeDoc
	 *
	 * @param   string      $filepath   File path
	 * @return	int|string              <0 if KO, Document ID if OK
	 */
    function sendDocument($filepath)
    {
	    global $langs;
	    dol_syslog(__METHOD__ . " filepath=" . $filepath, LOG_DEBUG);

	    if (empty($this->document_type_id)) {
		    $langs->load('demat4dolibarr@demat4dolibarr');
		    $this->errors[] = $langs->trans("Demat4DolibarrErrorModuleNotConfigured");
		    dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
		    return -1;
	    }

	    $result = $this->_getDocumentIds($filepath);
	    if (is_numeric($result)) {
	    	return -1;
	    }
	    $documentId = is_array($result) ? $result['documentId'] : '';

	    if (empty($documentId)) {
		    $results = $this->_sendToApi(self::METHOD_POST, '/v1/archive/documents', [
			    GuzzleHttp\RequestOptions::MULTIPART => [
				    [
					    'name' => 'options',
					    'contents' => json_encode([
						    'documentTypeId' => $this->document_type_id,
					    ])
				    ],
				    [
					    'name' => 'document',
					    'filename' => basename($filepath),
					    'contents' => fopen($filepath, 'r')
				    ]
			    ],
		    ]);
		    if (!is_array($results)) {
			    return -1;
		    }

		    $documentId = $results['documentId'];
		    $result = $this->_setDocumentIds($filepath, $results);
            if ($result < 0) {
                return -1;
            }
	    }

	    return $documentId;
    }

	/**
	 *  Send attachment to EdeDoc
	 *
	 * @param   string  $documentId         Document GUID
	 * @param   string  $filepath           File path
	 * @return	int                         <0 if KO, >0 if OK
	 */
	function sendAttachment($documentId, $filepath)
	{
		global $langs;
		dol_syslog(__METHOD__ . " documentId=" . $documentId . " filepath=" . $filepath, LOG_DEBUG);

		if (empty($this->document_type_id)) {
			$langs->load('demat4dolibarr@demat4dolibarr');
			$this->errors[] = $langs->trans("Demat4DolibarrErrorModuleNotConfigured");
			dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$result = $this->_getDocumentIds($filepath, $documentId);
		if (is_numeric($result) && $result < 0) {
			return -1;
		}
		$documentId = is_array($result) ? $result['documentId'] : '';
		$attachmentId = is_array($result) ? $result['attachmentId'] : '';

		if (empty($documentId) || empty($attachmentId)) {
			$results = $this->_sendToApi(self::METHOD_POST, "/v1/archive/documents/$documentId/attachments", [
				GuzzleHttp\RequestOptions::MULTIPART => [
					[
						'name' => 'options',
						'contents' => json_encode([
							'documentTypeId' => $this->document_type_id,
						])
					],
					[
						'name' => 'document',
						'filename' => basename($filepath),
						'contents' => fopen($filepath, 'r')
					]
				],
			]);
			if (!is_array($results)) {
				return -1;
			}

			$result = $this->_setDocumentIds($filepath, $results);
			if ($result < 0) {
				return -1;
			}
		}

		return 1;
	}

	/**
	 *  Sent invoice to chorus thanks to EDEDOC
	 *
	 * @param   Facture     $invoice                Invoice handler
	 * @param   string      $document_filepath      File path of the document
	 * @param   array       $attachments_filepath   File path of the attachments
	 * @param   bool        $includeAttachments     Include the attachments to send to CHORUS
	 * @return	int                                 <0 if KO, >0 if OK
	 */
    function sendInvoiceToChorus(&$invoice, $document_filepath, $attachments_filepath = array(), $includeAttachments = false)
    {
	    global $conf, $langs, $mysoc, $user;
	    dol_syslog(__METHOD__ . " document_filepath=" . $document_filepath . " attachments_filepath=" . json_encode($attachments_filepath) . " invoice=" . json_encode($invoice), LOG_DEBUG);
	    $langs->load('demat4dolibarr@demat4dolibarr');
	    $this->error = '';
	    $this->errors = array();

	    // Check parameters
	    $error = 0;
	    if (!file_exists($document_filepath)) {
		    $this->errors[] = $langs->trans('Demat4DolibarrErrorFileNotFound', $document_filepath);
		    $error++;
	    }
	    if (empty($conf->global->DEMAT4DOLIBARR_PROVIDER_CODE)) {
		    $this->errors[] = $langs->trans("Demat4DolibarrErrorProviderCodeNotConfigured");
		    $error++;
	    } elseif (strlen($conf->global->DEMAT4DOLIBARR_PROVIDER_CODE) > 50) {
		    $this->errors[] = $langs->trans("Demat4DolibarrErrorProviderCodeTooLong", 50);
		    $error++;
	    }
	    if ($invoice->type != Facture::TYPE_STANDARD && $invoice->type != Facture::TYPE_CREDIT_NOTE && $invoice->type != Facture::TYPE_DEPOSIT) {
		    $this->errors[] = $langs->trans("Demat4DolibarrErrorInvoiceMustBeStandardOrDepositOrCreditNote");
		    $error++;
	    }
        require_once DOL_DOCUMENT_ROOT .'/societe/class/societe.class.php';
        $company = new Societe($this->db);
	   	$res = $company->fetch($invoice->socid);
	   	if (!($res > 0)) {
		    $this->errors[] = $langs->trans("Demat4DolibarrErrorCompanyNotFound", $invoice->socid);
		    if ($res < 0) {
		    	if (!empty($company->error)) $this->errors[] = $company->error;
			    $this->errors = array_merge($this->errors, $company->errors);
		    }
		    $error++;
	    }
	    $debtorId = trim($company->idprof2);
	    if (empty($debtorId)) {
		    $this->errors[] = $langs->trans("Demat4DolibarrErrorDebtorIdNotDefined");
		    $error++;
	    } elseif (strlen($debtorId) > 30) {
		    $this->errors[] = $langs->trans("Demat4DolibarrErrorDebtorIdTooLong", 30);
		    $error++;
	    }
	    $encasementAccount = '';
	    $encasementInstitution = '';
	    $encasementOrganisation = '';
	    if ($invoice->fk_account > 0) {
		    require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
		    $bankstatic = new Account($this->db);
		    $res = $bankstatic->fetch($invoice->fk_account);
		    if (!($res > 0)) {
			    $this->errors[] = $langs->trans("Demat4DolibarrErrorBankNotFound", $invoice->fk_account);
			    if ($res < 0) {
			    	if (!empty($bankstatic->error)) $this->errors[] = $bankstatic->error;
				    $this->errors = array_merge($this->errors, $bankstatic->errors);
			    }
			    $error++;
		    }
		    $encasementAccount = trim($bankstatic->iban);
		    $encasementInstitution = trim($bankstatic->bic);
		    $encasementOrganisation = trim($bankstatic->proprio);
		    if (empty($encasementAccount)) {
			    $this->errors[] = $langs->trans("Demat4DolibarrErrorEncasementAccountNotDefinedForThisBank");
			    $error++;
		    } elseif (strlen($encasementAccount) > 50) {
			    $this->errors[] = $langs->trans("Demat4DolibarrErrorEncasementAccountTooLongForThisBank", 50);
			    $error++;
		    }
		    if (empty($encasementInstitution)) {
			    $this->errors[] = $langs->trans("Demat4DolibarrErrorEncasementInstitutionNotDefinedForThisBank");
			    $error++;
		    } elseif (strlen($encasementInstitution) > 100) {
			    $this->errors[] = $langs->trans("Demat4DolibarrErrorEncasementInstitutionTooLongForThisBank", 100);
			    $error++;
		    }
		    if (empty($encasementOrganisation)) {
			    $this->errors[] = $langs->trans("Demat4DolibarrErrorEncasementOrganisationNotDefinedForThisBank");
			    $error++;
		    } elseif (strlen($encasementOrganisation) > 150) {
			    $this->errors[] = $langs->trans("Demat4DolibarrErrorEncasementOrganisationTooLongForThisBank", 150);
			    $error++;
		    }
	    }
	    $promiseCode = isset($invoice->array_options['options_d4d_promise_code']) ? trim($invoice->array_options['options_d4d_promise_code']) : '';
	    if (strlen($promiseCode) > 50) {
		    $this->errors[] = $langs->trans("Demat4DolibarrErrorPromiseCodeTooLong", 50);
		    $error++;
	    }
	    $debtorServiceCode = '';
	    $arrayidcontact = $invoice->getIdContact('external','CHORUS_SERVICE');
	    $nbContacts = count($arrayidcontact);
        if ($nbContacts == 1) {
	        $res = $invoice->fetch_contact($arrayidcontact[0]);
	        if (!($res > 0)) {
		        $this->errors[] = $langs->trans("Demat4DolibarrErrorChorusServiceContactNotFound", $arrayidcontact[0]);
		        if ($res < 0) {
			        if (!empty($invoice->contact->error)) $this->errors[] = $invoice->contact->error;
			        $this->errors = array_merge($this->errors, $invoice->contact->errors);
		        }
		        $error++;
	        }
	        $debtorServiceCode = isset($invoice->contact->array_options['options_d4d_service_code']) ? trim($invoice->contact->array_options['options_d4d_service_code']) : '';
	        if (empty($debtorServiceCode)) {
		        $this->errors[] = $langs->trans("Demat4DolibarrErrorChorusCodeServiceNotDefined");
		        $error++;
	        } elseif (strlen($debtorServiceCode) > 100) {
		        $this->errors[] = $langs->trans("Demat4DolibarrErrorChorusCodeServiceTooLong", 100);
		        $error++;
	        }
        } elseif ($nbContacts > 1) {
	        $this->errors[] = $langs->trans("Demat4DolibarrErrorTooManySelectedChorusServiceContact");
	        $error++;
        }
	    $contractNumber = isset($invoice->array_options['options_d4d_contract_number']) ? trim($invoice->array_options['options_d4d_contract_number']) : '';
	    if (strlen($contractNumber) > 50) {
		    $this->errors[] = $langs->trans("Demat4DolibarrErrorContractNumberTooLong", 50);
		    $error++;
	    }
	    $validatorTypeId = 0;
	    $validatorId = '';
	    $validatorName = '';
	    $validatorCountryCode = '';
	    $arrayidcontact = $invoice->getIdContact('external','CHORUS_VALIDATOR');
	    $nbContacts = count($arrayidcontact);
        if ($nbContacts == 1) {
	        $res = $invoice->fetch_contact($arrayidcontact[0]);
	        if (!($res > 0)) {
		        $this->errors[] = $langs->trans("Demat4DolibarrErrorChorusValidatorContactNotFound", $arrayidcontact[0]);
		        if ($res < 0) {
			        if (!empty($invoice->contact->error)) $this->errors[] = $invoice->contact->error;
			        $this->errors = array_merge($this->errors, $invoice->contact->errors);
		        }
		        $error++;
	        }
	        $validatorTypeId = intval(isset($invoice->contact->array_options['options_d4d_validator_type_id']) ? trim($invoice->contact->array_options['options_d4d_validator_type_id']) : '');
	        $validatorId = isset($invoice->contact->array_options['options_d4d_validator_id']) ? trim($invoice->contact->array_options['options_d4d_validator_id']) : '';
	        $validatorName = isset($invoice->contact->array_options['options_d4d_validator_name']) ? trim($invoice->contact->array_options['options_d4d_validator_name']) : '';
	        $validatorCountryCode = isset($invoice->contact->country_code) ? trim($invoice->contact->country_code) : '';
	        if ($validatorTypeId < 1 || $validatorTypeId > 6) {
		        $this->errors[] = $langs->trans("Demat4DolibarrErrorChorusValidatorTypeIdNotDefined");
		        $error++;
	        }
	        if (empty($validatorId)) {
		        $this->errors[] = $langs->trans("Demat4DolibarrErrorChorusValidatorIdNotDefined");
		        $error++;
	        } elseif (strlen($validatorId) > 50) {
		        $this->errors[] = $langs->trans("Demat4DolibarrErrorChorusValidatorIdTooLong", 50);
		        $error++;
	        }
	        if (empty($validatorName)) {
		        $this->errors[] = $langs->trans("Demat4DolibarrErrorChorusValidatorNameNotDefined");
		        $error++;
	        } elseif (strlen($validatorName) > 150) {
		        $this->errors[] = $langs->trans("Demat4DolibarrErrorChorusValidatorNameTooLong", 150);
		        $error++;
	        }
	        if (empty($validatorCountryCode)) {
		        $this->errors[] = $langs->trans("Demat4DolibarrErrorChorusValidatorCountryNotDefined");
		        $error++;
	        } elseif (strlen($validatorCountryCode) > 2) {
		        $this->errors[] = $langs->trans("Demat4DolibarrErrorChorusValidatorCountryTooLong", 2);
		        $error++;
	        }
        } elseif ($nbContacts > 1) {
	        $this->errors[] = $langs->trans("Demat4DolibarrErrorTooManySelectedChorusValidatorContact");
	        $error++;
        }
        $invoiceNumber = $invoice->ref;
	    if (strlen($invoiceNumber) > 20) {
		    $this->errors[] = $langs->trans("Demat4DolibarrErrorInvoiceNumberTooLong", 20);
		    $error++;
	    }
	    $invoiceType = $invoice->type == Facture::TYPE_STANDARD || $invoice->type == Facture::TYPE_DEPOSIT ? '380' : ($invoice->type == Facture::TYPE_CREDIT_NOTE ? '381' : '');
	    if (empty($invoiceType)) {
		    $this->errors[] = $langs->trans("Demat4DolibarrErrorInvoiceTypeNotSupported");
		    $error++;
	    }
	    $billingMode = '';
	    $res = $this->_loadBillingCodeCodes();
	    if ($res < 0) {
		    $error++;
	    } else {
		    $billingModeId = isset($invoice->array_options['options_d4d_billing_mode']) ? trim($invoice->array_options['options_d4d_billing_mode']) : '';
		    if (empty($billingModeId)) {
			    $this->errors[] = $langs->trans("Demat4DolibarrErrorBillingModeNotDefined");
			    $error++;
		    } elseif (!isset(self::$billing_mode_ids[$billingModeId])) {
			    $this->errors[] = $langs->trans("Demat4DolibarrErrorBillingModeNotFound", $billingModeId);
			    $error++;
		    } else {
			    $billingMode = self::$billing_mode_ids[$billingModeId];
		    }
	    }
	    if (!($invoice->date > 0)) {
		    $this->errors[] = $langs->trans("Demat4DolibarrErrorInvoiceDateNotDefined");
		    $error++;
	    }
	    $invoiceCurrency = $conf->multicurrency->enabled && !empty($invoice->multicurrency_code) ? $invoice->multicurrency_code : $mysoc->multicurrency_code;
	    if (strlen($invoiceCurrency) > 3) {
		    $this->errors[] = $langs->trans("Demat4DolibarrErrorInvoiceCurrencyTooLong", 3);
		    $error++;
	    }
		$originalInvoiceNumber = '';
	    if ($invoice->type == Facture::TYPE_CREDIT_NOTE) {
            require_once DOL_DOCUMENT_ROOT .'/compta/facture/class/facture.class.php';
            $sourceInvoice = new Facture($this->db);
		    $res = $sourceInvoice->fetch($invoice->fk_facture_source);
		    if (!($res > 0)) {
			    $this->errors[] = $langs->trans("Demat4DolibarrErrorOriginalInvoiceNotFound", $invoice->fk_facture_source);
			    if ($res < 0) {
				    if (!empty($sourceInvoice->error)) $this->errors[] = $sourceInvoice->error;
				    $this->errors = array_merge($this->errors, $sourceInvoice->errors);
			    }
			    $error++;
		    }
		    $originalInvoiceNumber = $sourceInvoice->ref;
		    if (strlen($originalInvoiceNumber) > 20) {
			    $this->errors[] = $langs->trans("Demat4DolibarrErrorOriginalInvoiceNumberTooLong", 20);
			    $error++;
		    }
	    }
	    $paymentCode = '';
		if ($invoice->mode_reglement_id > 0) {
			dol_include_once('/advancedictionaries/class/dictionary.class.php');
			$paymentCodeDictionary = Dictionary::getDictionary($this->db, 'demat4dolibarr', 'demat4dolibarrpaymentcode');
			$res = $paymentCodeDictionary->fetch_lines(1, array('mode_reglement' => array($invoice->mode_reglement_id)));
			if ($res < 0 || count($paymentCodeDictionary->lines) != 1) {
				$this->errors[] = $langs->trans("Demat4DolibarrErrorPaymentCodeChorusNotFound");
				if ($res < 0) {
					if (!empty($paymentCodeDictionary->error)) $this->errors[] = $paymentCodeDictionary->error;
					$this->errors = array_merge($this->errors, $paymentCodeDictionary->errors);
				}
				$error++;
			} else {
				$lines = array_values($paymentCodeDictionary->lines);
				$paymentCode = $lines[0]->fields['payment_code'];
			}
		} else {
			$this->errors[] = $langs->trans("Demat4DolibarrErrorModeReglementNotDefined");
            $error++;
		}

	    $totalpaye = $invoice->getSommePaiement();
	    $totalcreditnotes = $invoice->getSumCreditNotesUsed();
	    $totaldeposits = $invoice->getSumDepositsUsed();

	    // We can also use bcadd to avoid pb with floating points
	    // For example print 239.2 - 229.3 - 9.9; does not return 0.
	    // $resteapayer=bcadd($invoice->total_ttc,$totalpaye,$conf->global->MAIN_MAX_DECIMALS_TOT);
	    // $resteapayer=bcadd($resteapayer,$totalavoir,$conf->global->MAIN_MAX_DECIMALS_TOT);
	    $resteapayer = price2num($invoice->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits, 2);

	    $taxes = [];
	    $sign = 1;
	    if (isset($invoice->type) && $invoice->type == 2 && !empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign = -1;
	    foreach ($invoice->lines as $line) {
		    // Collecte des totaux par valeur de tva dans $taxes["taxAmount"]=total_tva et $taxes["baseHT"]=total_ht
		    $prev_progress = $line->get_prev_progress($invoice->id);
		    if ($prev_progress > 0 && !empty($line->situation_percent)) { // Compute progress from previous situation
			    if ($conf->multicurrency->enabled && $invoice->multicurrency_tx != 1) {
				    $tvaligne = $sign * $line->multicurrency_total_tva * ($line->situation_percent - $prev_progress) / $line->situation_percent;
				    $totalht = $sign * $line->multicurrency_total_ht * ($line->situation_percent - $prev_progress) / $line->situation_percent;
			    } else {
				    $tvaligne = $sign * $line->total_tva * ($line->situation_percent - $prev_progress) / $line->situation_percent;
				    $totalht = $sign * $line->total_ht * ($line->situation_percent - $prev_progress) / $line->situation_percent;
			    }
		    } else {
			    if ($conf->multicurrency->enabled && $invoice->multicurrency_tx != 1) {
				    $tvaligne = $sign * $line->multicurrency_total_tva;
				    $totalht = $sign * $line->multicurrency_total_ht;
			    } else {
				    $tvaligne = $sign * $line->total_tva;
				    $totalht = $sign * $line->total_ht;
			    }
		    }

		    if ($invoice->remise_percent) {
			    $tvaligne -= ($tvaligne * $invoice->remise_percent) / 100;
			    $totalht -= ($totalht * $invoice->remise_percent) / 100;
		    }

		    $vatrate = (string)$line->tva_tx;
		    if (($line->info_bits & 0x01) == 0x01) $vatrate .= '*';
		    if (!isset($taxes[$vatrate])) $taxes[$vatrate] = ['baseHT' => 0.0, 'taxRate' => floatval($line->tva_tx), 'taxAmount' => 0.0];
		    $taxes[$vatrate]['baseHT'] += $totalht;
		    $taxes[$vatrate]['taxAmount'] += $tvaligne;
	    }

	    $affectedUser = '';
	    if (strlen($affectedUser) > 100) {
		    $this->errors[] = $langs->trans("Demat4DolibarrErrorAffectedUserTooLong", 100);
		    $error++;
	    }

	    if ($error) {
		    dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
		    return -1;
	    }

	    // Send document to EDEDOC
	    $documentId = $this->sendDocument($document_filepath);
	    if (is_numeric($documentId) && $documentId < 0) {
		    return -1;
	    }

	    // Send attachments to EDEDOC
	    foreach ($attachments_filepath as $filepath) {
		    $result = $this->sendAttachment($documentId, $filepath);
		    if ($result < 0) {
			    return -1;
		    }
	    }

	    // Set data to send to EDEDOC
	    $data = [
		    'documentId' => $documentId,
		    'connectionType' => !empty($conf->global->DEMAT4DOLIBARR_TEST) ? 'Q' : 'P',
		    'includeAttachments' => !empty($attachments_filepath) && $includeAttachments ? 'true' : 'false',
		    'providerCode' => $conf->global->DEMAT4DOLIBARR_PROVIDER_CODE,
		    'encasementAccount' => $encasementAccount,
		    'encasementInstitution' => $encasementInstitution,
		    'encasementOrganisation' => $encasementOrganisation,
		    'debtorId' => $debtorId,
		    'promiseCode' => $promiseCode,
		    'debtorServiceCode' => $debtorServiceCode,
		    'contractNumber' => $contractNumber,
		    'validatorTypeId' => $validatorTypeId,
		    'validatorId' => $validatorId,
		    'validatorName' => $validatorName,
		    'validatorCountryCode' => $validatorCountryCode,
		    'invoiceNumber' => $invoiceNumber,
		    'invoiceType' => $invoiceType,
		    'billingMode' => $billingMode,
		    'invoiceDate' => dol_print_date($invoice->date, 'dayrfc'),
		    'invoiceCurrency' => $invoiceCurrency,
		    'originalInvoiceNumber' => $originalInvoiceNumber,
		    'paymentCode' => $paymentCode,
		    'totalHT' => (float)$invoice->total_ht,
		    'totalTTC' => (float)$invoice->total_ttc,
		    'totalNetToPay' => (float)$resteapayer,
		    'taxes' => array_values($taxes),
		    'affectedUser' => $affectedUser
	    ];

	    // Send invoice to EDEDOC
	    $results = $this->_sendToApi(self::METHOD_POST, '/v1/workflows/chorus', [ GuzzleHttp\RequestOptions::FORM_PARAMS => $data ]);
	    if (!is_array($results)) {
		    return -1;
	    }
	    $jobId = $results['jobId'];

	    // Set message of the event
	    $title = $langs->trans('Demat4DolibarrSendToChorusByEdeDocActionLabel', $invoice->ref);
	    $msg = $langs->trans('Demat4DolibarrSendToChorusByEdeDocActionMessage', $jobId, json_encode($data, JSON_PRETTY_PRINT));

	    // Create event of this sending to EDEDOC
	    $result = $this->_addActionSendInvoiceToChorus($invoice, $title, $msg, $user);
	    if ($result < 0) {
	    	return 2;
	    }

	    // Save job ID
	    $invoice->array_options['options_d4d_job_id'] = $jobId;
	    $res = $invoice->insertExtraFields();
	    if ($res < 0) {
		    $this->errors[] = $langs->trans("Demat4DolibarrErrorSaveJobId");
		    if (!empty($invoice->error)) $this->errors[] = $invoice->error;
		    $this->errors = array_merge($this->errors, $invoice->errors);
		    dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
		    return 2;
	   	}
//
//	    // Increment current number of request send
//	    $currentNumRequest = !empty($conf->global->DEMAT4DOLIBAR_CURRENT_NUM_REQUEST) && $conf->global->DEMAT4DOLIBAR_CURRENT_NUM_REQUEST > 0 ? $conf->global->DEMAT4DOLIBAR_CURRENT_NUM_REQUEST : 0;
//	    $res = dolibarr_set_const($this->db, 'DEMAT4DOLIBAR_CURRENT_NUM_REQUEST', $currentNumRequest + 1, 'chaine', 0, '', $conf->entity);
//	   	if (!($res > 0)) {
//		    $this->errors[] = $langs->trans("Demat4DolibarrErrorIncrementCurrentNumRequest", 100);
//	   		$this->errors[] = $this->db->lasterror();
//		    dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
//		    return 2;
//	   	}

	    // Get status of the job
	    $res = $this->getJobStatus($invoice);
	    if ($res < 0) {
		    return 2;
	   	}

	    dol_syslog(__METHOD__ . " success; " . $msg, LOG_DEBUG);
	    return 1;
    }

	/**
	 *  Get status of the chorus job from EDEDOC
	 *
	 * @param   Facture     $invoice    Invoice handler
	 * @return	int                     <0 if KO, >0 if OK, 0 if pass
	 */
    function getJobStatus(&$invoice)
    {
	    global $langs;
	    dol_syslog(__METHOD__ . " invoice=" . json_encode($invoice), LOG_DEBUG);
	    $langs->load('demat4dolibarr@demat4dolibarr');
	    $this->error = '';
	    $this->errors = array();

		$invoiceId = !empty($invoice->array_options['options_d4d_invoice_id']) ? $invoice->array_options['options_d4d_invoice_id'] : '';
		if (empty($invoiceId)) {
			$jobId = !empty($invoice->array_options['options_d4d_job_id']) ? $invoice->array_options['options_d4d_job_id'] : '';
			if (empty($jobId)) {
				return 0;
			}

			// Get status from EDEDOC
			$results = $this->_sendToApi(self::METHOD_GET, '/v1/workflows/chorus/' . $jobId);
		} else {
			// Get status from EDEDOC
			$results = $this->_sendToApi(self::METHOD_GET, '/v1/statistics/chorus/invoices/' . $invoiceId);
		}
		if (!is_array($results)) {
			return -1;
		}

		$invoiceInfo = $results['invoice'];
		$invoice->array_options['options_d4d_invoice_id'] = trim($invoiceInfo['invoiceId']);
		$invoice->array_options['options_d4d_invoice_create_on'] = trim($invoiceInfo['createdOn']);
		$invoice->array_options['options_d4d_chorus_id'] = trim($invoiceInfo['chorusJobId']);
		$res = $this->_loadChorusStatusCodes();
		if ($res < 0) {
			return -1;
		} else {
			$chorusStatus = trim($invoiceInfo['chorusJobStatus']);
			if (!empty($chorusStatus)) {
				if (!isset(self::$chorus_status_codes[$chorusStatus])) {
					$this->errors[] = $langs->trans("Demat4DolibarrErrorChorusStatusCodeNotFound", $chorusStatus);
					return -1;
				} else {
					$chorusStatus = self::$chorus_status_codes[$chorusStatus];
				}
			}
		}
		$invoice->array_options['options_d4d_chorus_status'] = $chorusStatus;
		$res = $this->_loadInvoiceStatusCodes();
		if ($res < 0) {
			return -1;
		} else {
			$invoiceStatus = trim($invoiceInfo['chorusInvoiceStatus']);
			if (!empty($invoiceStatus)) {
				if (!isset(self::$invoice_status_codes[$invoiceStatus])) {
					$this->errors[] = $langs->trans("Demat4DolibarrErrorInvoiceStatusCodeNotFound", $invoiceStatus);
					return -1;
				} else {
					$invoiceStatus = self::$invoice_status_codes[$invoiceStatus];
				}
			}
		}
		$invoice->array_options['options_d4d_invoice_status'] = $invoiceStatus;
		$invoice->array_options['options_d4d_chorus_invoice_id'] = $invoiceInfo['chorusInvoiceId'];
		$invoice->array_options['options_d4d_chorus_submit_date'] = trim($invoiceInfo['chorusJobSubmitDate']);
		$invoice->array_options['options_d4d_chorus_status_error_message'] = trim($invoiceInfo['errorMessage']);

		// workflow
		if (isset($results['job'])) {
			$jobInfo = $results['job'];
			$invoice->array_options['options_d4d_job_id'] = trim($jobInfo['jobId']);
			$invoice->array_options['options_d4d_job_workflow_name'] = trim($jobInfo['workflowName']);
			$res = $this->_loadJobStatusCodes();
			if ($res < 0) {
				return -1;
			} else {
				$jobStatus = trim($jobInfo['status']);
				if (!empty($jobStatus)) {
					if (!isset(self::$job_status_codes[$jobStatus])) {
						$this->errors[] = $langs->trans("Demat4DolibarrErrorJobStatusCodeNotFound", $jobStatus);
						return -1;
					} else {
						$jobStatus = self::$job_status_codes[$jobStatus];
					}
				}
			}
			$invoice->array_options['options_d4d_job_status'] = $jobStatus;
			$invoice->array_options['options_d4d_job_create_on'] = trim($jobInfo['createdOn']);
			$invoice->array_options['options_d4d_job_owner'] = trim($jobInfo['owner']);
			$invoice->array_options['options_d4d_job_suspension_reason'] = trim($jobInfo['suspensionReason']);
		} elseif (empty($invoice->array_options['options_d4d_job_id'])) {
			$invoice->array_options['options_d4d_job_id'] = '';
			$invoice->array_options['options_d4d_job_workflow_name'] = '';
			$invoice->array_options['options_d4d_job_status'] = '';
			$invoice->array_options['options_d4d_job_create_on'] = '';
			$invoice->array_options['options_d4d_job_owner'] = '';
			$invoice->array_options['options_d4d_job_suspension_reason'] = '';
		}

	    // Save job ID
	    $res = $invoice->insertExtraFields();
	    if ($res < 0) {
		    $this->errors[] = $langs->trans("Demat4DolibarrErrorSaveJobStatus");
		    if (!empty($invoice->error)) $this->errors[] = $invoice->error;
		    $this->errors = array_merge($this->errors, $invoice->errors);
		    dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
		    return -1;
	    }

	    dol_syslog(__METHOD__ . " success", LOG_DEBUG);
	    return 1;
    }

    function get_number_request_sent()
    {
        $nb_request_sent = 0;

        $sql = "SELECT COUNT(*) AS nb FROM " . MAIN_DB_PREFIX . "facture_extrafields";
        $sql .= " WHERE DATE_FORMAT(d4d_job_create_on, '%Y%m') = '" . dol_print_date(dol_now(), '%Y%m') . "'";

        $resql = $this->db->query($sql);
        if ($resql) {
            if ($obj = $this->db->fetch_object($resql)) {
                $nb_request_sent = $obj->nb;
            }
            $this->db->free($resql);
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        return $nb_request_sent;
    }

	/**
	 *  Refresh all status of the chorus job from EDEDOC for launch by cron
	 *
	 * @return	int|string		<0 if KO, Message if OK
	 */
    function cronRefreshAllJobStatus()
    {
	    global $langs;
	    dol_syslog(__METHOD__, LOG_DEBUG);
	    $langs->load('demat4dolibarr@demat4dolibarr');

	    $errors = array();

	    $error = 0;
		$invoice_success = array();

	    $sql = "SELECT fef.fk_object AS rowid";
	    $sql .= " FROM " . MAIN_DB_PREFIX . "facture as f";
	    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facture_extrafields as fef ON fef.fk_object = f.rowid";
	    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_demat4dolibarr_invoice_status as cd4dis ON cd4dis.rowid = fef.d4d_invoice_status";
	    $sql .= " WHERE fef.d4d_job_id IS NOT NULL";
	    $sql .= " AND (cd4dis.can_resend IS NULL OR cd4dis.can_resend != 1)";

	    $resql = $this->db->query($sql);
	    if ($resql) {
            // Connection to EDEDOC
            $result = $this->connection();
            if ($result < 0) {
                $this->error = $this->errorsToString();
                return -1;
            }

            while ($obj = $this->db->fetch_object($resql)) {
                require_once DOL_DOCUMENT_ROOT .'/compta/facture/class/facture.class.php';
                $invoice = new Facture($this->db);
			    $invoice->fetch($obj->rowid);

			    $result = $this->getJobStatus($invoice);
			    if ($result < 0) {
				    $errors[] = $langs->trans('Demat4DolibarrErrorInvoice', $invoice->ref, $obj->rowid);
				    if (!empty($this->error)) $errors[] = $this->error;
				    $errors = array_merge($errors, $this->errors);
				    $error++;
			    } elseif ($result > 0) {
				    $invoice_success[] = $invoice->ref;
			    }
		    }

		    $this->db->free($resql);
	    } else {
		    $this->error = "sql= $sql; " . $this->db->lasterror();
		    dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
		    return -1;
	    }

	    $output_msg = '';
	    if (!$error || count($invoice_success) > 0) {
		    $output_msg .= $langs->trans('Demat4DolibarrUpdateAllChorusStatusSuccess');
		    if (count($invoice_success) > 0) $output_msg .= implode(', ', $invoice_success);
	    }
	    if (count($errors) > 0) {
		    if (!empty($output_msg)) $output_msg .= '<br>';
		    $output_msg .= implode('<br>', $errors);
	    }

	    return $output_msg;
    }

	/**
	 * Get info of the document send to EdeDoc into database
	 *
	 * @param   string      $filepath           File path
	 * @param   string      $documentId         Document GUID if is a attachment
	 * @return	array|int		                <0 if KO, null if not found or Info for this documents array('documentId' => xxx, 'attachmentId' => xxx)
	 */
	public function _getDocumentIds($filepath, $documentId='')
	{
		global $conf, $langs;
		dol_syslog(__METHOD__ . " filepath=" . $filepath . " documentId=" . $documentId, LOG_DEBUG);

		$this->errors = array();
		$langs->load("demat4dolibarr@demat4dolibarr");

		// Clean parameters
		$filepath = trim($filepath);
		$documentId = trim($documentId);

		// Check parameters
		$error = 0;
		if (!file_exists($filepath)) {
			$this->errors[] = $langs->trans("Demat4DolibarrErrorFileNotFound", $filepath);
			$error++;
		}
		if (empty($this->document_type_id)) {
			$this->errors[] = $langs->trans("Demat4DolibarrErrorModuleNotConfigured");
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . " Errors : " . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$smallfilepath = str_replace($conf->facture->dir_output, '', $filepath);
		$checksum = sha1_file($filepath);

		if ($this->_purgeExpiredFile() < 0) {
			return -1;
		}

		// Get Ids
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "demat4dolibarr_ededoc_file";
		$sql .= " WHERE file_path = '" . $this->db->escape($smallfilepath) . "'";
		$sql .= " AND checksum = '" . $this->db->escape($checksum) . "'";
		$sql .= " AND document_type_id = '" . $this->db->escape($this->document_type_id) . "'";
		if (!empty($documentId)) $sql .= ", document_id = '" . $this->db->escape($documentId) . "'";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
			return -1;
		}

		if ($obj = $this->db->fetch_object($resql)) {
			return array('documentId' => $obj->document_id, 'attachmentId' => $obj->attachment_id);
		}

		return null;
	}

	/**
	 * Save info of the document send to EdeDoc into database
	 *
	 * @param   string  $filepath       File path
	 * @param   array   $edeDocInfo     EdeDoc info sent by EdeDoc
	 * @return	int                     <0 if KO, >0 if OK
	 */
	public function _setDocumentIds($filepath, $edeDocInfo)
	{
		global $conf, $langs;
		dol_syslog(__METHOD__ . " filepath=" . $filepath . " edeDocInfo=" . json_encode($edeDocInfo), LOG_DEBUG);

		$this->errors = array();
		$langs->load("demat4dolibarr@demat4dolibarr");

		// Clean parameters
		$filepath = trim($filepath);
		$documentId = trim($edeDocInfo['documentId']);
		$checksum = trim($edeDocInfo['checksum']);
		$expireDate = strtotime($edeDocInfo['maxArchiveDate']);
		$attachmentId = trim($edeDocInfo['attachmentId']);

		// Check parameters
		$error = 0;
		if (!file_exists($filepath)) {
			$this->errors[] = $langs->trans("Demat4DolibarrErrorFileNotFound", $filepath);
			$error++;
		}
		if (empty($this->document_type_id)) {
			$this->errors[] = $langs->trans("Demat4DolibarrErrorModuleNotConfigured");
			$error++;
		}
		if ($expireDate === false) {
			$this->errors[] = $langs->trans("ErrorBadFormat") . ' - ' . $langs->trans("Date") . ' : ' . $edeDocInfo['maxArchiveDate'];
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . " Errors : " . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$smallfilepath = str_replace($conf->facture->dir_output, '', $filepath);

		if ($this->_purgeExpiredFile() < 0) {
			return -1;
		}

		// Insert
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "demat4dolibarr_ededoc_file (";
		$sql .= "  file_path";
		$sql .= ", document_type_id";
		$sql .= ", document_id";
		$sql .= ", attachment_id";
		$sql .= ", checksum";
		$sql .= ", expire_date";
		$sql .= ")";
		$sql .= " VALUES (";
		$sql .= "  '" . $this->db->escape($smallfilepath) . "'";
		$sql .= ", '" . $this->db->escape($this->document_type_id) . "'";
		$sql .= ", '" . $this->db->escape($documentId) . "'";
		$sql .= ", '" . $this->db->escape($attachmentId) . "'";
		$sql .= ", '" . $this->db->escape($checksum) . "'";
		$sql .= ", '" . $this->db->idate($expireDate) . "'";
		$sql .= ")";
		$sql .= " ON DUPLICATE KEY UPDATE";
		$sql .= "  attachment_id = '" . $this->db->escape($attachmentId) . "'";
		$sql .= ", expire_date = '" . $this->db->idate($expireDate) . "'";

		$resql = $this->db->query($sql);
		if ($resql) {
			return 1;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
			return -1;
		}
	}

	/**
	 * Purge all expired files
	 *
	 * @return	int                 <0 if KO, >0 if OK
	 */
	public function _purgeExpiredFile()
	{
		global $langs;
		dol_syslog(__METHOD__, LOG_DEBUG);

		$this->errors = array();
		$langs->load("demat4dolibarr@demat4dolibarr");

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "demat4dolibarr_ededoc_file";
		$sql .= " WHERE expire_date < NOW()";
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
			return -1;
		}

		return 1;
	}

	/**
	 *  Send to the Api
	 *
	 * @param   string  $method     Method request
	 * @param   string  $url        Url request
	 * @param   array   $options    Options request
	 * @return	int                 <0 if KO, >0 if OK
	 */
    protected function _sendToApi($method, $url, $options = [])
    {
	    dol_syslog(__METHOD__ . " method=" . $method . " url=" . $url . " options=" . json_encode($options), LOG_DEBUG);
	    global $conf, $langs;

	    try {
		    $accessToken = $this->getAccessToken();
		    if (is_numeric($accessToken) && $accessToken < 0) {
		    	return -1;
		    }
		    $options['headers']['Authorization'] = 'Bearer ' . $accessToken;

		    switch ($method) {
			    case self::METHOD_GET:
				    $response = $this->client->get($url, $options);
			    	break;
			    case self::METHOD_HEAD:
				    $response = $this->client->head($url, $options);
			    	break;
			    case self::METHOD_DELETE:
				    $response = $this->client->delete($url, $options);
			    	break;
			    case self::METHOD_PUT:
				    $response = $this->client->put($url, $options);
			    	break;
			    case self::METHOD_PATCH:
				    $response = $this->client->patch($url, $options);
			    	break;
			    case self::METHOD_POST:
				    $response = $this->client->post($url, $options);
			    	break;
			    case self::METHOD_OPTIONS:
				    $response = $this->client->options($url, $options);
			    	break;
			    default:
				    $this->errors[] = 'Bad REST Method';
				    dol_syslog(__METHOD__ . " Errors: " . $this->errorsToString(), LOG_ERR);
			    	return -1;
		    }
		    return json_decode($response->getBody()->getContents(), true);
	    } catch (RequestException $e) {
		    $request = $e->getRequest();
		    $response = $e->getResponse();

		    $errors_details = array();
		    if (isset($request)) $errors_details[] = $this->_requestToString($request);
		    if (isset($response)) $errors_details[] = $this->_responseToString($response);
		    else $errors_details[] = '<pre>' . dol_nl2br((string)$e) . '</pre>';

		    if (!empty($conf->global->DEMAT4DOLIBARR_DEBUG)) {
			    $this->errors = array_merge($this->errors, $errors_details);
		    } else {
                if (isset($response)) {
                    $boby = $response->getBody();
                    $this->errors[] = '<b>' . $langs->trans('Demat4DolibarrResponseCode') . ': </b>' . $response->getStatusCode() . '<br>' .
                        '<b>' . $langs->trans('Demat4DolibarrResponseReasonPhrase') . ': </b>' . $response->getReasonPhrase() .
                        (!empty($boby) ? '<br>' . $boby : '');
                } else $this->errors[] = $e->getMessage();
            }

		    dol_syslog(__METHOD__ . " Error: " . dol_htmlentitiesbr_decode(implode(', ', $errors_details)), LOG_ERR);
		    return -1;
	    } catch (Exception $e) {
		    if (!empty($conf->global->DEMAT4DOLIBARR_DEBUG)) {
			    $this->errors[] = (string)$e;
		    } else {
			    $this->errors[] = $e->getMessage();
		    }

		    dol_syslog(__METHOD__ . " Error: " . $e, LOG_ERR);
		    return -1;
	    }
    }

	/**
	 *  Add action : Send invoice to CHORUS by EDEDOC
	 *
	 * @param   Facture     $invoice        Invoice handler
	 * @param   string      $title          Title of the action
	 * @param   string      $msg            Message of the action
	 * @param   User        $user           User that modifies
	 * @return  int                         <0 if KO, >0 if OK
	 */
	protected function _addActionSendInvoiceToChorus($invoice, $title, $msg, $user)
    {
	    return $this->_addAction('AC_D4D_ITEC', $invoice->socid, $invoice->id, $invoice->element, $title, $msg, $user);
    }

	/**
	 *  Add action
	 *
	 * @param   string      $type_code      Type code of the action
	 * @param   int         $socid          Company ID
	 * @param   int         $fk_element     Element linked ID
	 * @param   string      $elementtype    Element linked Type
	 * @param   string      $title          Title of the action
	 * @param   string      $msg            Message of the action
	 * @param   User        $user           User that modifies
	 * @return  int                         <0 if KO, >0 if OK
	 */
	protected function _addAction($type_code, $socid, $fk_element, $elementtype, $title, $msg, $user)
    {
	    dol_syslog(__METHOD__ . " type_code=" . $type_code . " socid=" . $socid . " fk_element=" . $fk_element . " title=" . $title . " msg=" . $msg . " user_id=" . $user->id, LOG_DEBUG);
	    $now = dol_now();
	    // Insertion action
	    require_once(DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php');
	    $actioncomm = new ActionComm($this->db);
	    $actioncomm->type_code = $type_code;
	    $actioncomm->label = $title;
	    $actioncomm->note = $msg;
	    $actioncomm->datep = $now;
	    $actioncomm->datef = $now;
	    $actioncomm->durationp = 0;
	    $actioncomm->punctual = 1;
	    $actioncomm->percentage = -1; // Not applicable
	    $actioncomm->contactid = 0;
	    $actioncomm->socid = $socid;
	    $actioncomm->author = $user; // User saving action
	    // $actioncomm->usertodo = $user; // User affected to action
	    $actioncomm->userdone = $user; // User doing action
	    $actioncomm->fk_element = $fk_element;
	    $actioncomm->elementtype = $elementtype;
	    $actioncomm->userownerid = $user->id;

	    $result = $actioncomm->create($user); // User qui saisit l'action
	    if ($result < 0) {
		    $this->error = $actioncomm->error;
		    $this->errors = $actioncomm->errors;
		    dol_syslog(__METHOD__ . " Errors: " . $this->errorsToString(), LOG_ERR);
	    }

	    return $result;
    }

	/**
	 *  Format the request to a string
	 *
	 * @param   RequestInterface    $request    Request handler
	 * @return	string		                    Formatted string of the request
	 */
    protected function _requestToString(RequestInterface $request)
    {
	    global $langs;

	    $out = '';
	    $out .= '<b>' . $langs->trans('Demat4DolibarrRequestData') . ': </b><br><hr>';
	    $out .= '<div style="max-width: 1024px;">';
	    $out .= '<b>' . $langs->trans('Demat4DolibarrRequestProtocolVersion') . ': </b>' . $request->getProtocolVersion() . '<br>';
	    $out .= '<b>' . $langs->trans('Demat4DolibarrRequestUri') . ': </b>' . $request->getUri() . '<br>';
	    $out .= '<b>' . $langs->trans('Demat4DolibarrRequestTarget') . ': </b>' . $request->getRequestTarget() . '<br>';
	    $out .= '<b>' . $langs->trans('Demat4DolibarrRequestMethod') . ': </b>' . $request->getMethod() . '<br>';
	    $out .= '<b>' . $langs->trans('Demat4DolibarrRequestHeaders') . ':</b><ul>';
	    foreach ($request->getHeaders() as $name => $values) {
		    $out .= '<li><b>' . $name . ': </b>' . implode(', ', $values) . '</li>';
	    }
	    $out .= '</ul>';
	    $out .= '<b>' . $langs->trans('Demat4DolibarrRequestBody') . ': </b>';
	    $out .= '<br><em>' . $request->getBody() . '</em><br>';
	    $out .= '</div>';
	    return $out;
    }

	/**
	 *  Format the response to a string
	 *
	 * @param   ResponseInterface   $response   Response handler
	 * @return	string		                    Formatted string of the response
	 */
    protected function _responseToString(ResponseInterface $response)
    {
	    global $langs;

	    $out = '';
	    $out .= '<b>' . $langs->trans('Demat4DolibarrResponseData') . ': </b><br><hr>';
	    $out .= '<div style="max-width: 1024px;">';
	    $out .= '<b>' . $langs->trans('Demat4DolibarrResponseProtocolVersion') . ': </b>' . $response->getProtocolVersion() . '<br>';
	    $out .= '<b>' . $langs->trans('Demat4DolibarrResponseCode') . ': </b>' . $response->getStatusCode() . '<br>';
	    $out .= '<b>' . $langs->trans('Demat4DolibarrResponseReasonPhrase') . ': </b>' . $response->getReasonPhrase() . '<br>';
	    $out .= '<b>' . $langs->trans('Demat4DolibarrResponseHeaders') . ':</b><ul>';
	    foreach ($response->getHeaders() as $name => $values) {
		    $out .= '<li><b>' . $name . ': </b>' . implode(', ', $values) . '</li>';
	    }
	    $out .= '</ul>';
	    $out .= '<b>' . $langs->trans('Demat4DolibarrResponseBody') . ': </b>';
	    $body = json_decode($response->getBody(), true);
	    if (is_array($body)) {
		    $out .= '<ul>';
		    foreach ($body as $name => $values) {
			    $out .= '<li><b>' . $name . ': </b>' . (is_array($values) || is_object($values) ? json_encode($values) : $values) . '</li>';
		    }
		    $out .= '</ul>';
	    } else {
		    $out .= '<br><em>' . $response->getBody() . '</em><br>';
	    }
	    $out .= '</div>';
	    return $out;
    }

	/**
	 * load the cache for billing mode
	 *
	 * @return  int                         <0 if KO, >0 if OK
	 */
	public function _loadBillingCodeCodes()
	{
		if (!isset(self::$billing_mode_ids)) {
			$dictionary = Dictionary::getDictionary($this->db, 'demat4dolibarr', 'demat4dolibarrbillingmode');
			$res = $dictionary->fetch_lines(-1);
			if ($res > 0) {
				self::$billing_mode_ids = array();
				foreach ($dictionary->lines as $line) {
					self::$billing_mode_ids[$line->id] = $line->fields['code'];
				}
			} else {
				$this->error = $dictionary->error;
				$this->errors = array_merge($this->errors, $dictionary->errors);
				return -1;
			}
		}

		return 1;
	}

	/**
	 * load the cache for job status
	 *
	 * @return  int                         <0 if KO, >0 if OK
	 */
	public function _loadJobStatusCodes()
	{
		if (!isset(self::$job_status_codes)) {
			$dictionary = Dictionary::getDictionary($this->db, 'demat4dolibarr', 'demat4dolibarrjobstatus');
			$res = $dictionary->fetch_lines(-1);
			if ($res > 0) {
				self::$job_status_codes = array();
				foreach ($dictionary->lines as $line) {
					self::$job_status_codes[$line->fields['code']] = $line->id;
				}
			} else {
				$this->error = $dictionary->error;
				$this->errors = array_merge($this->errors, $dictionary->errors);
				return -1;
			}
		}

		return 1;
	}

	/**
	 * load the cache for chorus status
	 *
	 * @return  int                         <0 if KO, >0 if OK
	 */
	public function _loadChorusStatusCodes()
	{
		if (!isset(self::$chorus_status_codes)) {
			$dictionary = Dictionary::getDictionary($this->db, 'demat4dolibarr', 'demat4dolibarrchorusstatus');
			$res = $dictionary->fetch_lines(-1);
			if ($res > 0) {
				self::$chorus_status_codes = array();
				foreach ($dictionary->lines as $line) {
					self::$chorus_status_codes[$line->fields['code']] = $line->id;
				}
			} else {
				$this->error = $dictionary->error;
				$this->errors = array_merge($this->errors, $dictionary->errors);
				return -1;
			}
		}

		return 1;
	}

	/**
	 * load the cache for invoice status
	 *
	 * @return  int                         <0 if KO, >0 if OK
	 */
	public function _loadInvoiceStatusCodes()
	{
		if (!isset(self::$invoice_status_codes)) {
			$dictionary = Dictionary::getDictionary($this->db, 'demat4dolibarr', 'demat4dolibarrinvoicestatus');
			$res = $dictionary->fetch_lines(-1);
			if ($res > 0) {
				self::$invoice_status_codes = array();
				foreach ($dictionary->lines as $line) {
					self::$invoice_status_codes[$line->fields['code']] = $line->id;
				}
			} else {
				$this->error = $dictionary->error;
				$this->errors = array_merge($this->errors, $dictionary->errors);
				return -1;
			}
		}

		return 1;
	}

	/**
	 * Method to output saved errors
	 *
	 * @param   string      $separator      Separator between each error
	 * @return	string		                String with errors
	 */
	public function errorsToString($separator = ', ')
	{
		return (is_array($this->errors) ? join($separator, $this->errors) : '');
	}
}