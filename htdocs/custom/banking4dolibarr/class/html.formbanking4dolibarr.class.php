<?php
/*  Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
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
 *	\file       banking4dolibarr/class/html.formbanking4dolibarr.class.php
 *  \ingroup    banking4dolibarr
 *	\brief      File of class with all html predefined components for Banking4Dolibarr
 */

/**
 *	Class to manage generation of HTML components
 *	Only common components for Banking4Dolibarr must be here.
 *
 */
class FormBanking4Dolibarr
{
    public $db;
    public $error;
    public $num;

	/**
	 * @var Form  Instance of the form
	 */
	public $form;

    /**
     * Constructor
     *
     * @param   DoliDB $db Database handler
     */
    public function __construct($db)
    {
    	global $form;

		$this->db = $db;
		$this->form = $form;
    	if (!is_object($this->form)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
			$this->form = new Form($this->db);
		}
    }

    /**
     *  HTML of the box for the refresh of any process in AJAX
     *
     * @param  	string		$page	                Url of the AJAX page.
     * @param  	string		$title	                Title of the box.
     * @param  	array		$data	                Data send to the AJAX page..
     * @param  	string		$finished_page	        Url redirection when the process if finished (if empty close the box).
     * @param  	string		$text	                Text of the box (can have {{status}} tag,).
     * @param  	string		$initial_status_text	Initial status text.
     * @param  	int			$height                 Force height of box
     * @param	int			$width	                Force width of box ('999' or '90%'). Ignored and forced to 95% on smartphones.
     * @param	string	    $form_id	            Id of the form if reload page with a form.
     * @return 	string      	    	            HTML AJAX code,
     */
    function processBox($page, $title, $data=array(), $finished_page="", $text="", $initial_status_text="0", $height=200, $width=500, $form_id='')
    {
        global $langs, $conf;

        if (empty($page) || empty($title)) {
            $langs->load('errors');
            return $langs->trans('ErrorBadParameters');
        }

        if (empty($text)) {
            $text = $langs->trans("Banking4DolibarrProcessBoxText");
        }
        if (empty($initial_status_text)) {
            $initial_status_text = "0";
        }

        $title = dol_escape_js(dol_escape_htmltag($title), 2);
        $data = json_encode(is_array($data) ? $data : array());
        $finished_page = !empty($finished_page) ? $finished_page : '';
        $text = dol_escape_js($text, 2);
        $initial_status_text = dol_escape_js($initial_status_text, 2);
        $height = $height > 0 ? $height : 200;
        $width = $width > 0 ? $width : 500;
        if ($conf->browser->layout == 'phone') $width = '95%';
        $form_id = dol_escape_js($form_id, 2);
        $finished_text = dol_escape_js($langs->trans('Banking4DolibarrProcessBoxFinishedWithSuccessText'), 2);
        $finished_button_text = dol_escape_js($langs->trans('Banking4DolibarrCloseBox'), 2);

        // Show JQuery process box.
        $process_box = <<<SCRIPT
    <div id="process_box" title="$title" style="display: none;"></div>
    <!-- begin ajax process_box page=$page -->
    <script type="text/javascript">
        jQuery(document).ready(function() {
            var finished_page = "$finished_page";
            var process_box_div = $("#process_box");
            var process_box_text = "$text";
            process_box_div.dialog({
                autoOpen: true,
    			open: function() {
                    var page = "$page";
                    var data = $data;
                    set_process_box_status_text("$initial_status_text");

                    launch_process_request(page, data);
                },
    			close: function() {
                    close_process_box();
                },
                resizable: false,
                height: "$height",
                width: "$width",
                modal: true,
                closeOnEscape: false,
                buttons: {
                    "$finished_button_text": function() {
                        $(this).dialog("close");
                    }
                }
            });
            function set_process_box_status_text(status) {
                set_process_box_text(process_box_text.replace('{{status}}', status), false);
            }
            function set_process_box_error_text(error) {
                set_process_box_text('<span style="color: red;">' + error + '</span>', true);
            }
            function set_process_box_text(text, add) {
                if (add) {
                    process_box_div.append('<br>' + text);
                } else {
                    process_box_div.empty();
                    process_box_div.html(text);
                }
            }
            function close_process_box() {
                if (finished_page.length > 0) {
                    window.location.replace(finished_page);
                } else {
                    var form_id = "$form_id";
                    if (form_id.length == 0) {
                        document.location.reload(true);
                    } else {
                        $("#" + form_id).submit();
                    }
                }
            }
            function launch_process_request(page, data) {
                $.ajax(page, {
                    method: "POST",
                    data: data,
                    dataType: "json"
                }).done(function(response) {
                    if (typeof response === 'undefined' || response === null) {
                        set_process_box_error_text('Error empty response');
                    } else {
                        if (typeof response.page === 'string') page = response.page;
                        if (typeof response.data === 'object') data = response.data;
                        if (typeof response.text === 'string') process_box_text = response.text;
                        if (typeof response.location === 'string') finished_page = response.location;

                        if (typeof response.status === 'string') {
                            set_process_box_status_text(response.status);
                        }

                        if (typeof response.error === 'string') {
                            set_process_box_error_text(response.error);
                            response.keep_window_open = true;
                        }

                        if (page.length > 0 && typeof response.error !== 'string') {
                            launch_process_request(page, data);
                        } else {
                            if (typeof response.keep_window_open !== 'boolean' || !response.keep_window_open) {
                                if (finished_page.length > 0) {
                                    window.location.replace(finished_page);
                                } else {
                                    process_box_div.dialog("close");
                                }
                            } else {
                                if (typeof response.error !== 'string' || response.error.length == 0) {
                                    set_process_box_text('<span style="color: green;">$finished_text</span>', true);
                                }
                            }
                        }
                    }
                }).fail(function(jqxhr, textStatus, error) {
                    set_process_box_error_text(textStatus + " - " + error);
                });
            }
        });
    </script>
    <!-- end ajax process_box -->
SCRIPT;

        return $process_box;
    }

    /**
     *  HTML of the box for the manual reconciliation of any process in AJAX
     *
     * @param  	string		    $page	                Url of the AJAX page.
     * @param  	string		    $title	                Title of the box.
     * @param  	int|string		$height                 Force height of box
     * @param	int|string	    $width	                Force width of box ('999' or '90%'). Ignored and forced to 95% on smartphones.
     * @param	string	        $form_id	            Id of the form if reload page with a form.
     * @return 	string      	        	            HTML AJAX code,
     */
    function manualReconciliationBox($page, $title, $height="90%", $width="90%", $form_id='')
    {
        global $langs, $conf;

        if (empty($page) || empty($title)) {
            $langs->load('errors');
            return $langs->trans('ErrorBadParameters');
        }

        $page = dol_escape_js($page, 2);
        $title = dol_escape_js(dol_escape_htmltag($title), 2);
        $height = is_numeric($height) && $height > 0 || !empty($height) ? $height : 160;
        $width = is_numeric($width) && $width > 0 || !empty($width) ? $width : 500;
        if ($conf->browser->layout == 'phone') $width = '95%';
        $form_id = dol_escape_js($form_id, 2);

        // Show JQuery manual reconciliation box.
        $manual_reconciliation_box = <<<SCRIPT
    <div id="manual_reconciliation_box" style="display: none;" title="$title"><iframe id="manual_reconciliation_box_iframe" src="" style="width: 100%; height: 98%; border: none;" title="$title"></iframe></div>
    <!-- begin ajax manual_reconciliation_box page=$page -->
    <script type="text/javascript">
        jQuery(document).ready(function() {
            manual_reconciliation_box = $("#manual_reconciliation_box");
            manual_reconciliation_box_iframe = $("#manual_reconciliation_box_iframe");
            manual_reconciliation_box.dialog({
                autoOpen: false,
                open: function() {
                    b4d_resize_manual_reconciliation_box("$width", "$height");
                    var parameters = manual_reconciliation_box.data('parameters');
                    manual_reconciliation_box_iframe.attr('src', "$page?id=" + parameters.id + "&row_id=" + parameters.row_id);
                },
                close: function() {
                    b4d_close_manual_reconciliation_box();
                },
                resizable: false,
                height: "$height",
                width: "$width",
                modal: true,
                closeOnEscape: false
            });
        });

        function b4d_open_manual_reconciliation_box(id, row_id) {
            manual_reconciliation_box.data('parameters', {'id': id, 'row_id': row_id}).dialog('open');
        }
        
        function b4d_close_manual_reconciliation_box() {
            var form_id = "$form_id";
            if (form_id.length == 0) {
                document.location.reload(true);
            } else {
                $("#" + form_id).submit();
            }
        }

        function b4d_resize_manual_reconciliation_box(width, height) {
            // manual_reconciliation_box.parent().css({ 'position' : '' });
            if (height.indexOf('%') >= 0) {
                height = Math.max(160, Math.floor(window.innerHeight * parseInt(height.substr(0, height.indexOf('%'))) / 100));
            }

            b4d_unfix_manual_reconciliation_box();
            manual_reconciliation_box.dialog({'width': width, 'height': height});
            b4d_fix_manual_reconciliation_box(height);
        }
        
        function b4d_fix_manual_reconciliation_box(height) {
            var top = Math.floor(window.innerHeight - height) / 2;
            manual_reconciliation_box.parent().css({ 'position': 'fixed', 'top': top + 'px'});
        }
        
        function b4d_unfix_manual_reconciliation_box() {
            manual_reconciliation_box.parent().css({ 'position': '' });
        }
    </script>
    <!-- end ajax manual_reconciliation_box -->
SCRIPT;

        return $manual_reconciliation_box;
    }

	/**
	 *	Return multiselect list of users
	 *
	 *  @param	array|int	    $selected       List of user id or user object of user preselected. If -1, we use id of current user.
	 *  @param  string	        $htmlname       Field name in form
	 *  @param  array	        $exclude        Array list of users id to exclude
	 * 	@param	int		        $disabled		If select list must be disabled
	 *  @param  array|string	$include        Array list of users id to include or 'hierarchy' to have only supervised users or 'hierarchyme' to have supervised + me
	 * 	@param	array	        $enableonly		Array list of users id to be enabled. All other must be disabled
	 *  @param	int		        $force_entity	0 or Id of environment to force
	 *  @param	int		        $maxlength		Maximum length of string into list (0=no limit)
	 *  @param	int		        $showstatus		0=show user status only if status is disabled, 1=always show user status into label, -1=never show user status
	 *  @param	string	        $morefilter		Add more filters into sql request
	 *  @param	integer	        $show_every		0=default list, 1=add also a value "Everybody" at beginning of list
	 *  @param	string	        $enableonlytext	If option $enableonlytext is set, we use this text to explain into label why record is disabled. Not used if enableonly is empty.
	 *  @param	string	        $morecss		More css
	 *  @param  int             $noactive       Show only active users (this will also happened whatever is this option if USER_HIDE_INACTIVE_IN_COMBOBOX is on).
	 * 	@return	string					        HTML select string
	 *  @see select_dolgroups
	 */
	function multiselect_dolusers($selected=array(), $htmlname='userid', $exclude=null, $disabled=0, $include='', $enableonly=array(), $force_entity=0, $maxlength=0, $showstatus=0, $morefilter='', $show_every=0, $enableonlytext='', $morecss='', $noactive=0)
	{
		global $conf, $user;

		$out = '';

		$selected_values = array();
		if (is_array($selected)) {
			foreach ($selected as $u) {
				$selected_values[] = is_object($u) ? $u->id : $u;
			}
		} elseif ($selected == -1) {
			$selected_values[] = $user->id;
		}

		$out .= $this->multiselect_javascript_code($selected_values, $htmlname);

		$save_conf = $conf->use_javascript_ajax;
		$conf->use_javascript_ajax = 0;
		$out .= $this->form->select_dolusers('', $htmlname, 0, $exclude, $disabled, $include, $enableonly, $force_entity, $maxlength, $showstatus, $morefilter, $show_every, $enableonlytext, $morecss, $noactive);
		$conf->use_javascript_ajax = $save_conf;

		return $out;
	}

	/**
	 *	Return multiselect list of groups
	 *
	 *  @param	array	$selected       List of ID group preselected
	 *  @param  string	$htmlname       Field name in form
	 *  @param  string	$exclude        Array list of groups id to exclude
	 * 	@param	int		$disabled		If select list must be disabled
	 *  @param  string	$include        Array list of groups id to include
	 * 	@param	int		$enableonly		Array list of groups id to be enabled. All other must be disabled
	 * 	@param	int		$force_entity	0 or Id of environment to force
	 *  @return	string
	 *  @see select_dolusers
	 */
	function multiselect_dolgroups($selected=array(), $htmlname='groupid', $exclude='', $disabled=0, $include='', $enableonly=0, $force_entity=0)
	{
		global $conf;

		$out = '';

		$out .= $this->multiselect_javascript_code($selected, $htmlname);

		$save_conf = $conf->use_javascript_ajax;
		$conf->use_javascript_ajax = 0;
		$out .= $this->form->select_dolgroups('', $htmlname, 0, $exclude, $disabled, $include, $enableonly, $force_entity);
		$conf->use_javascript_ajax = $save_conf;

		return $out;
	}

	/**
	 *	Return multiselect javascript code
	 *
	 *  @param	array	$selected       Preselected values
	 *  @param  string	$htmlname       Field name in form
	 *  @param	string	$elemtype		Type of element we show ('category', ...)
	 *  @return	string
	 */
	function multiselect_javascript_code($selected, $htmlname, $elemtype='')
	{
		global $conf;

		$out = '';

		// Add code for jquery to use multiselect
		if (! empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) || defined('REQUIRE_JQUERY_MULTISELECT'))
		{
			$selected = array_values($selected);
			$tmpplugin=empty($conf->global->MAIN_USE_JQUERY_MULTISELECT)?constant('REQUIRE_JQUERY_MULTISELECT'):$conf->global->MAIN_USE_JQUERY_MULTISELECT;
			$out.='<!-- JS CODE TO ENABLE '.$tmpplugin.' for id '.$htmlname.' -->
       			<script type="text/javascript">
   	    			function formatResult(record) {'."\n";
			if ($elemtype == 'category')
			{
				$out.='	//return \'<span><img src="'.DOL_URL_ROOT.'/theme/eldy/img/object_category.png'.'"> <a href="'.DOL_URL_ROOT.'/categories/viewcat.php?type=0&id=\'+record.id+\'">\'+record.text+\'</a></span>\';
   								  	return \'<span><img src="'.DOL_URL_ROOT.'/theme/eldy/img/object_category.png'.'"> \'+record.text+\'</span>\';';
			}
			else
			{
				$out.='return record.text;';
			}
			$out.= '	};
       				function formatSelection(record) {'."\n";
			if ($elemtype == 'category')
			{
				$out.='	//return \'<span><img src="'.DOL_URL_ROOT.'/theme/eldy/img/object_category.png'.'"> <a href="'.DOL_URL_ROOT.'/categories/viewcat.php?type=0&id=\'+record.id+\'">\'+record.text+\'</a></span>\';
   								  	return \'<span><img src="'.DOL_URL_ROOT.'/theme/eldy/img/object_category.png'.'"> \'+record.text+\'</span>\';';
			}
			else
			{
				$out.='return record.text;';
			}
			$out.= '	};
   	    			$(document).ready(function () {
   	    			    $(\'#'.$htmlname.'\').attr("name", "'.$htmlname.'[]");
   	    			    $(\'#'.$htmlname.'\').attr("multiple", "multiple");
   	    			    //$.map('.json_encode($selected).', function(val, i) {
   	    			        $(\'#'.$htmlname.'\').val('.json_encode($selected).');
   	    			    //});
   	    			
       					$(\'#'.$htmlname.'\').'.$tmpplugin.'({
       						dir: \'ltr\',
   							// Specify format function for dropdown item
   							formatResult: formatResult,
       					 	templateResult: formatResult,		/* For 4.0 */
   							// Specify format function for selected item
   							formatSelection: formatSelection,
       					 	templateResult: formatSelection		/* For 4.0 */
       					});
       				});
       			</script>';
		}

		return $out;
	}
}

