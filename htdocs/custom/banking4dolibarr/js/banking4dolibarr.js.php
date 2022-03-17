<?php
/* Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
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
 * \file       htdocs/core/js/datepicker.js.php
 * \brief      File that include javascript functions for datepickers
 */

if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled cause need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

session_cache_limiter(FALSE);

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");

// Define javascript type
header('Content-type: text/javascript; charset=UTF-8');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

$langs->load('main');
$langs->load('banking4dolibarr@banking4dolibarr');

$content_url = dol_escape_js(dol_buildpath('/banking4dolibarr/ajax/bank_record_content.php', 1), 2);
$duplicate_content_url = dol_escape_js(dol_buildpath('/banking4dolibarr/ajax/duplicate_records_content.php', 1), 2);
$unlink_url = dol_escape_js(dol_buildpath('/banking4dolibarr/ajax/manual_reconciliation_unlink.php', 1), 2);

$wait_text = dol_escape_js($langs->trans('Banking4DolibarrPleaseWait'), 2);

$class_fonts_awesome = !empty($conf->global->EASYA_VERSION) && version_compare(DOL_VERSION, "10.0.0") >= 0 ? 'fal' : 'fa';

?>

/***************************************************************************************
 * For get content of a bank record line
 ***************************************************************************************/
function b4d_set_error_row_content(row_id, nb_column, error) {
    b4d_set_text_row_content(row_id, '<span style="color: red;">' + error + '</span>', nb_column)
}

function b4d_set_text_row_content(row_id, nb_column, text) {
    b4d_set_row_content(row_id, '<tr class="oddeven row_content_' + row_id + '"><td colspan="' + nb_column + '">' + text + '</td></tr>')
}

function b4d_set_row_content(row_id, content) {
    b4d_del_row_content(row_id);
    $("#row_id_" + row_id).after(content);
}

function b4d_del_row_content(row_id) {
    $("tr.row_content_" + row_id).remove();
}

function b4d_update_tooltip() {
    $(".classfortooltip").tooltip({
        show: { collision: "flipfit", effect:'toggle', delay:50 },
        hide: { delay: 50 },
        tooltipClass: "mytooltip",
        content: function () {
            return $(this).prop('title');		/* To force to get title as is */
        }
    });
}

function b4d_open_content_line(id, row_id, index_label, nb_field, nb_column, has_comment) {
	var _this = $('#row_details_' + row_id);
	b4d_set_text_row_content(row_id, nb_column, '<span class="<?php print $class_fonts_awesome ?> fa-spinner fa-spin"></span> <?php print $wait_text ?>');
	$('#duplicate_details_' + row_id).addClass("far").removeClass("fas");
	_this.addClass("fa-minus-square").removeClass("fa-plus-square");
	$.ajax('<?php print $content_url ?>', {
		method: "POST",
		data: { id: id, row_id: row_id, index_label: index_label, nb_field: nb_field, has_comment: has_comment },
		dataType: "json"
	}).done(function(response) {
		if (typeof response.error === 'string') {
			b4d_set_error_row_content(row_id, nb_column, response.error);
		} else if (typeof response.content === 'string') {
			b4d_set_row_content(row_id, response.content);
			b4d_update_tooltip();
		}
	}).fail(function(jqxhr, textStatus, error) {
		b4d_set_error_row_content(row_id, nb_column, textStatus + " - " + error);
	});
}

function b4d_open_duplicate_line(id, row_id, nb_column, array_fields) {
	var _this = $('#duplicate_details_' + row_id);
	b4d_set_text_row_content(row_id, nb_column, '<span class="<?php print $class_fonts_awesome ?> fa-spinner fa-spin"></span> <?php print $wait_text ?>');
	$('#row_details_' + row_id).addClass("fa-plus-square").removeClass("fa-minus-square");
	_this.addClass("fas").removeClass("far");
	$.ajax('<?php print $duplicate_content_url ?>', {
		method: "POST",
		data: { id: id, row_id: row_id, array_fields: array_fields },
		dataType: "json"
	}).done(function(response) {
		if (typeof response.error === 'string') {
			b4d_set_error_row_content(row_id, nb_column, response.error);
		} else if (typeof response.content === 'string') {
			b4d_set_row_content(row_id, response.content);
			b4d_update_tooltip();
		}
	}).fail(function(jqxhr, textStatus, error) {
		b4d_set_error_row_content(row_id, nb_column, textStatus + " - " + error);
	});
}

function b4d_close_content_line(row_id) {
    b4d_del_row_content(row_id);
	$('#row_details_' + row_id).addClass("fa-plus-square").removeClass("fa-minus-square");
	$('#duplicate_details_' + row_id).addClass("far").removeClass("fas");
}

function b4d_unlink_manual_reconciliation(id, row_id, line_id) {
    $.ajax('<?php print $unlink_url ?>', {
        method: "POST",
        data: { id: id, row_id: row_id, line_id: line_id },
        dataType: "json"
    }).done(function(response) {
        if (typeof response.error === 'string') {
            b4d_display_error(response.error);
        } else if (typeof response.result === 'number' && response.result == 1) {
            $(".unlink_line_" + row_id + '_' + line_id).remove();
            if ($("tr.row_content_" + row_id).length == 0) {
                b4d_close_content_line(row_id);
                $('#row_details_' + row_id).removeClass('cursorpointer').addClass('disabled').removeProp('data-row-id');
            }
            if ($("#wrong_odate_line_" + row_id).length == 0) {
                $("#wrong_odate_" + row_id).remove();
            }
            if ($("#wrong_vdate_line_" + row_id).length == 0) {
                $("#wrong_vdate_" + row_id).remove();
            }
            if ($("#wrong_payment_type_line_" + row_id).length == 0) {
                $("#wrong_payment_type_" + row_id).remove();
            }console.log($(".reconcile_button_" + row_id));
            $(".reconcile_button_" + row_id).show();
        }
    }).fail(function(jqxhr, textStatus, error) {
        b4d_display_error(textStatus + " - " + error);
    });
}

function b4d_display_error(error_message) {
    /* jnotify(message, preset of message type, keepmessage) */
    $.jnotify(error_message, 'error', true, { remove: function(){} });
}