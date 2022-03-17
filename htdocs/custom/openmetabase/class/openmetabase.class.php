<?php
/* Copyright (C) 2021      Open-DSI             <support@open-dsi.fr>
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
 * \file    htdocs/openmetabase/class/openmetabase.class.php
 * \ingroup openmetabase
 * \brief
 */

if (!class_exists('ComposerAutoloaderInite5f8183b6b110d1bbf5388358e7ebc94', false)) dol_include_once('/openmetabase/vendor/autoload.php');
use OAuth\Common\Storage\DoliStorage;
use OAuth\OAuth2\Token\StdOAuth2Token;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
//require '../../main.inc.php';
//require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
//require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
//require_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';
//require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

class OpenMetabase
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
     * @var Client  Client REST handler
     */
    public $client;

    public $dump_filename = '';
    public $dump_filepath = '';
    public $dump_compression = 'gz'; // 'gz' or 'bz' or 'none'

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
     * @param   DoliDB $db Database handler
     */
    public function __construct($_db = null)
    {
        global $conf, $db;
        $this->db = is_object($_db) ? $_db : $db;

        $this->dump_filename = 'openmetabase.sql';
        $this->dump_filepath = $conf->admin->dir_output . '/backup/' . $this->dump_filename;
    }

    /**
     *  Connect to the METABASE API
     *
     * @return	int		                <0 if KO, >0 if OK
     */
    public function connection()
    {
        global $conf, $langs;
        dol_syslog(__METHOD__, LOG_DEBUG);
        $this->errors = array();

        if (empty($conf->global->OPENMETABASE_API_URI_BASE) || empty($conf->global->OPENMETABASE_API_TOKEN)) {
            $langs->load('openmetabase@openmetabase');
            $this->errors[] = $langs->trans("OpenMetabaseErrorModuleNotConfigured");
            dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        try {
            $this->client = new Client([
                // Base URI is used with relative requests
                'base_uri' => $conf->global->OPENMETABASE_API_URI_BASE,
                // You can set any number of default request options.
                'timeout' => !empty($conf->global->OPENMETABASE_API_TIMEOUT) ? $conf->global->OPENMETABASE_API_TIMEOUT : 60,
            ]);
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            dol_syslog(__METHOD__ . " Error: " . $e, LOG_ERR);
            return -1;
        }

        return 1;
    }

    /**
     *  Dump Dolibarr database for send to metabase
     *
     * @return int          <0 if KO, >0 if OK
     */
    public function dumpDatabase()
    {
        global $conf, $langs, $dolibarr_main_db_name, $dolibarr_main_db_host;

        require_once DOL_DOCUMENT_ROOT . '/core/class/utils.class.php';
        $utils = new Utils($this->db);

		$dolibarr_main_db_host_save = $dolibarr_main_db_host;
		$ignore_option = " --ignore-table={$dolibarr_main_db_name}.";
		$ignore_tables = !empty($conf->global->OPENMETABASE_IGNORE_TABLE) ? array_filter(array_map('trim', explode(';', $conf->global->OPENMETABASE_IGNORE_TABLE))) : array('llx_blockedlog','llx_blockedlog_authority','llx_actioncomm','llx_actioncomm_extrafields','llx_actioncomm_reminder','llx_actioncomm_resources');
		if (!empty($ignore_tables)) $dolibarr_main_db_host .= $ignore_option . implode($ignore_option, $ignore_tables);

        $result = $utils->dumpDatabase($this->dump_compression, 'auto', 1, $this->dump_filename, 10);
		$dolibarr_main_db_host = $dolibarr_main_db_host_save;
        if ($result < 0 || !empty($utils->error)) {
            $this->errors[] = $utils->error;
            return -1;
        }
        $extension = $this->_getCompressedFileExtension($this->dump_compression);
        $filepath = $this->dump_filepath . $extension;
        if (!file_exists($filepath)) {
            $langs->load('errors');
            $this->errors[] = $langs->trans('ErrorFileNotFound', $filepath);
            return -1;
        }

        return 1;
    }

    /**
     *  Send database to OpenMetabase API
     *
     * @return	int              <0 if KO, >0 if OK
     */
    public function sendDatabase()
    {
        global $langs;

        $extension = $this->_getCompressedFileExtension($this->dump_compression);
        $filename = $this->dump_filename . $extension;
        $filepath = $this->dump_filepath . $extension;
        if (!file_exists($filepath)) {
            $langs->load('errors');
            $this->errors[] = $langs->trans('ErrorFileNotFound', $filepath);
            return -1;
        }

        try {
			$file = self::tryFopen($filepath, 'r');
		} catch (Exception $e) {
			$this->errors[] = $langs->trans('ErrorFailedToOpenFile', $filepath);
			return -1;
		}

        $content_type = $this->_getCompressedFileContentType($this->dump_compression);
        $results = $this->_sendToApi(self::METHOD_POST, '/metabase/import', [
            GuzzleHttp\RequestOptions::MULTIPART => [
                [
                    'name'     => 'file',
                    'filename' => $filename,
                    'contents' => $file,
                    'headers'  => [ 'Content-Type' => $content_type ],
                ]
            ]
        ]);
        if (!is_array($results)) {
            return -1;
        }

        return 1;
    }

    /**
     *  Restore database to OpenMetabase API
     *
     * @return	int              <0 if KO, >0 if OK
     */
    public function restoreDatabase()
    {
        $results = $this->_sendToApi(self::METHOD_GET, '/metabase/restore');
        if (!is_array($results)) {
            return -1;
        }

        return 1;
    }

    /**
     *  Process dump to metabase API
     *
     * @return	int				0 if OK, < 0 if KO (this function is used also by cron so only 0 is OK)
     */
    public function cronSendDatabaseToOpenMetabase()
    {
        global $const, $langs;

        require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
        $langs->load('openmetabase@openmetabase');
        $output = '';

        try {
            $error = 0;
            if (empty($const->global->OPENMETABASE_SENDING_DATABASE)) {
                dolibarr_set_const($this->db, 'OPENMETABASE_SENDING_DATABASE', dol_print_date(dol_now(), 'dayhour'), 'chaine', 1, 'Token the processing of the synchronization of the site by webhooks', 0);

                $result = $this->dumpDatabase();
                if ($result < 0) {
                    $output .= $langs->trans('OpenMetabaseErrorWhenDumpDatabase') . ":<br>";
                    $output .= '<span style="color: red;">' . $this->errorsToString('<br>') . '</span>' . "<br>";
                    $error++;
                }

                if (!$error) {
                    $result = $this->connection();
                    if ($result < 0) {
                        $output .= $langs->trans('OpenMetabaseErrorWhenConnectToOpenMetabaseApi') . ":<br>";
                        $output .= '<span style="color: red;">' . $this->errorsToString('<br>') . '</span>' . "<br>";
                        $error++;
                    }
                }
                if (!$error) {
                    $result = $this->sendDatabase();
                    if ($result < 0) {
                        $output .= $langs->trans('OpenMetabaseErrorWhenSendDatabaseToOpenMetabaseApi') . ":<br>";
                        $output .= '<span style="color: red;">' . $this->errorsToString('<br>') . '</span>' . "<br>";
                        $error++;
                    }
                }

                if (!$error) {
                    $result = $this->restoreDatabase();
                    if ($result < 0) {
                        $output .= $langs->trans('OpenMetabaseErrorWhenRestoreDatabaseToOpenMetabaseApi') . ":<br>";
                        $output .= '<span style="color: red;">' . $this->errorsToString('<br>') . '</span>' . "<br>";
                        $error++;
                    }
                }

                dolibarr_del_const($this->db, 'OPENMETABASE_SENDING_DATABASE', 0);

                if ($error) {
                    $this->error = $output;
                    $this->errors = array();
                    return -1;
                } else {
                    $output .= $langs->trans('OpenMetabaseSendDatabaseToOpenMetabaseApiSuccess');
                }
            } else {
                $output .= $langs->trans('OpenMetabaseAlreadySendingDatabaseToOpenMetabaseApi') . ' (' . $langs->trans('OpenMetabaseSince') . ' : ' . $const->global->OPENMETABASE_SENDING_DATABASE . ')';
            }

            $this->error = "";
            $this->errors = array();
            $this->output = $output;
            $this->result = array("commandbackuplastdone" => "", "commandbackuptorun" => "");

            return 0;
        } catch (Exception $e) {
            dolibarr_del_const($this->db, 'OPENMETABASE_SENDING_DATABASE', 0);
            $output .= $langs->trans('OpenMetabaseErrorWhenSendingDatabaseToOpenMetabaseApi') . ":<br>";
            $output .= '<span style="color: red;">' . $langs->trans('Error') . ': ' . $e->getMessage() . '</span>' . "<br>";
            $this->error = $output;
            $this->errors = array();
            return -1;
        }
    }

    /**
	 *  Send to the Api
	 *
	 * @param   string  $method     Method request
	 * @param   string  $url        Url request
	 * @param   array   $options    Options request
     * @param   string  $code       Specific code for the request
	 * @return	int                 <0 if KO, >0 if OK
	 */
    protected function _sendToApi($method, $url, $options = [], $code='')
    {
	    dol_syslog(__METHOD__ . " method=" . $method . " url=" . $url . " options=" . json_encode($options) . " code=" . $code, LOG_DEBUG);
	    global $conf, $langs;

	    try {
            if (!isset($this->client)) {
                $langs->load('openmetabase@openmetabase');
                $this->errors[] = $langs->trans("OpenMetabaseErrorConnectionNotInitialized");
                dol_syslog(__METHOD__ . " Error: " . $this->errorsToString(), LOG_ERR);
                return -1;
            }

            $api_uri = $conf->global->OPENMETABASE_API_URI_BASE;
		    $options['headers']['Authorization'] = 'Bearer ' . base64_encode($conf->global->OPENMETABASE_API_TOKEN);

		    switch ($method) {
                case self::METHOD_GET:
                    $response = $this->client->get($api_uri . $url, $options);
                    break;
                case self::METHOD_HEAD:
                    $response = $this->client->head($api_uri . $url, $options);
                    break;
                case self::METHOD_DELETE:
                    $response = $this->client->delete($api_uri . $url, $options);
                    break;
                case self::METHOD_PUT:
                    $response = $this->client->put($api_uri . $url, $options);
                    break;
                case self::METHOD_PATCH:
                    $response = $this->client->patch($api_uri . $url, $options);
                    break;
                case self::METHOD_POST:
                    $response = $this->client->post($api_uri . $url, $options);
                    break;
                case self::METHOD_OPTIONS:
                    $response = $this->client->options($api_uri . $url, $options);
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

		    if (!empty($conf->global->OPENMETABASE_DEBUG)) {
			    $this->errors = array_merge($this->errors, $errors_details);
		    } else {
                if (isset($response)) {
                    $boby = dol_trunc(dol_escape_htmltag($response->getBody()), $conf->global->OPENMETABASE_SHOW_ALL_REQUEST_BODY ? 0 : 500);
                    $this->errors[] = '<b>' . $langs->trans('OpenMetabaseResponseCode') . ': </b>' . $response->getStatusCode() . '<br>' .
                        '<b>' . $langs->trans('OpenMetabaseResponseReasonPhrase') . ': </b>' . $response->getReasonPhrase() .
                        (!empty($boby) ? '<br>' . $boby : '');
                } else $this->errors[] = $e->getMessage();
            }

		    dol_syslog(__METHOD__ . " Error: " . dol_htmlentitiesbr_decode(implode(', ', $errors_details)), LOG_ERR);
		    return -1;
	    } catch (Exception $e) {
		    if (!empty($conf->global->OPENMETABASE_DEBUG)) {
			    $this->errors[] = (string)$e;
		    } else {
			    $this->errors[] = $e->getMessage();
		    }

		    dol_syslog(__METHOD__ . " Error: " . $e, LOG_ERR);
		    return -1;
	    }
    }

	/**
	 *  Format the request to a string
	 *
	 * @param   RequestInterface    $request    Request handler
	 * @return	string		                    Formatted string of the request
	 */
    protected function _requestToString(RequestInterface $request)
    {
	    global $conf, $langs;

	    $out = '';
	    $out .= '<b>' . $langs->trans('OpenMetabaseRequestData') . ': </b><br><hr>';
	    $out .= '<div style="max-width: 1024px;">';
	    $out .= '<b>' . $langs->trans('OpenMetabaseRequestProtocolVersion') . ': </b>' . $request->getProtocolVersion() . '<br>';
	    $out .= '<b>' . $langs->trans('OpenMetabaseRequestUri') . ': </b>' . $request->getUri() . '<br>';
	    $out .= '<b>' . $langs->trans('OpenMetabaseRequestTarget') . ': </b>' . $request->getRequestTarget() . '<br>';
	    $out .= '<b>' . $langs->trans('OpenMetabaseRequestMethod') . ': </b>' . $request->getMethod() . '<br>';
	    $out .= '<b>' . $langs->trans('OpenMetabaseRequestHeaders') . ':</b><ul>';
	    foreach ($request->getHeaders() as $name => $values) {
		    $out .= '<li><b>' . $name . ': </b>' . implode(', ', $values) . '</li>';
	    }
	    $out .= '</ul>';
	    $out .= '<b>' . $langs->trans('OpenMetabaseRequestBody') . ': </b>';
	    $out .= '<br><em>' . dol_trunc(dol_escape_htmltag($request->getBody()), $conf->global->OPENMETABASE_SHOW_ALL_REQUEST_BODY ? 0 : 500) . '</em><br>';
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
	    global $conf, $langs;

	    $out = '';
	    $out .= '<b>' . $langs->trans('OpenMetabaseResponseData') . ': </b><br><hr>';
	    $out .= '<div style="max-width: 1024px;">';
	    $out .= '<b>' . $langs->trans('OpenMetabaseResponseProtocolVersion') . ': </b>' . $response->getProtocolVersion() . '<br>';
	    $out .= '<b>' . $langs->trans('OpenMetabaseResponseCode') . ': </b>' . $response->getStatusCode() . '<br>';
	    $out .= '<b>' . $langs->trans('OpenMetabaseResponseReasonPhrase') . ': </b>' . $response->getReasonPhrase() . '<br>';
	    $out .= '<b>' . $langs->trans('OpenMetabaseResponseHeaders') . ':</b><ul>';
	    foreach ($response->getHeaders() as $name => $values) {
		    $out .= '<li><b>' . $name . ': </b>' . implode(', ', $values) . '</li>';
	    }
	    $out .= '</ul>';
	    $out .= '<b>' . $langs->trans('OpenMetabaseResponseBody') . ': </b>';
	    $body = json_decode($response->getBody(), true);
	    if (is_array($body)) {
		    $out .= '<ul>';
		    foreach ($body as $name => $values) {
			    $out .= '<li><b>' . $name . ': </b>' . (is_array($values) || is_object($values) ? json_encode($values) : $values) . '</li>';
		    }
		    $out .= '</ul>';
	    } else {
		    $out .= '<br><em>' . dol_trunc(dol_escape_htmltag($response->getBody()), $conf->global->OPENMETABASE_SHOW_ALL_REQUEST_BODY ? 0 : 500) . '</em><br>';
	    }
	    $out .= '</div>';
	    return $out;
    }

    /**
     *  Add extension in function of the compression
     *
     * @param   string   $compression   Compression type
     * @return	string                  Extension
     */
    public function _getCompressedFileExtension($compression)
    {
        $extension = '';
        if ($this->dump_compression == 'gz') {
            $extension = '.gz';
        } elseif ($this->dump_compression == 'bz') {
            $extension = '.bz2';
        }

        return $extension;
    }

    /**
     *  Add content type in function of the compression
     *
     * @param   string   $compression   Compression type
     * @return	string                  Content type
     */
    public function _getCompressedFileContentType($compression)
    {
        $content_type = '';
        if ($this->dump_compression == 'gz') {
            $content_type = 'application/gzip';
        } elseif ($this->dump_compression == 'bz') {
            $content_type = 'application/x-bzip2';
        } elseif ($this->dump_compression == 'none') {
            $content_type = 'text/sql';
        }

        return $content_type;
    }

	/**
	 * Safely opens a PHP stream resource using a filename.
	 *
	 * When fopen fails, PHP normally raises a warning. This function adds an
	 * error handler that checks for errors and throws an exception instead.
	 *
	 * @param string $filename File to open
	 * @param string $mode     Mode used to open the file
	 *
	 * @return resource
	 *
	 * @throws \RuntimeException if the file cannot be opened
	 */
	public static function tryFopen($filename, $mode)
	{
		$ex = null;
		set_error_handler(function () use ($filename, $mode, &$ex) {
			$ex = new \RuntimeException(sprintf(
				'Unable to open %s using mode %s: %s',
				$filename,
				$mode,
				func_get_args()[1]
			));
		});

		$handle = fopen($filename, $mode);
		restore_error_handler();

		if ($ex) {
			/** @var $ex \RuntimeException */
			throw $ex;
		}

		return $handle;
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