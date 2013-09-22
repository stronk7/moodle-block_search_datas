<?php
// This file is part of block_search_datas,
// a contrib block for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Search datas main script.
 *
 * @package    block_search_datas
 * @copyright  2013 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/glossary/lib.php');
require_once($CFG->dirroot . '/mod/data/lib.php');

define('DATAMAXRESULTSPERPAGE', 100);  // Limit results per page.

$courseid = required_param('courseid', PARAM_INT);
$blockid  = required_param('blockid', PARAM_INT);
$query    = required_param('bsquery', PARAM_NOTAGS);
$page     = optional_param('page', 0, PARAM_INT);

function search_datas_search($query, $course, $blockconfig, $offset, &$countentries) {

    global $CFG, $USER, $DB;

    // TODO: Use the sql style in other search_xxx blocks.
    // TODO: Add support for groups?

    // Some differences in syntax for PostgreSQL.
    // TODO: Modify this to support also MSSQL and Oracle.
    if ($CFG->dbfamily == "postgres") {
        $LIKE = "ILIKE";   // Case-insensitive.
        $NOTLIKE = "NOT ILIKE";   // Case-insensitive.
        $REGEXP = "~*";
        $NOTREGEXP = "!~*";
    } else {
        $LIKE = "LIKE";
        $NOTLIKE = "NOT LIKE";
        $REGEXP = "REGEXP";
        $NOTREGEXP = "NOT REGEXP";
    }

    // Perform the search only in datas fulfilling mod/data:viewentry and (visible or moodle/course:viewhiddenactivities)
    $dataids = array();
    if (! $datas = get_all_instances_in_course('data', $course)) {
        notice(get_string('thereareno', 'moodle', get_string('modulenameplural', 'data')), "../../course/view.php?id=$course->id");
        die;
    }
    // Block configuration may be restricting the target modules.
    $restrictcms = array();
    if (!empty($blockconfig->restrictcms)) {
        $restrictcms = explode(',', $blockconfig->restrictcms);
    }
    foreach ($datas as $data) {
        $cm = get_coursemodule_from_instance("data", $data->id, $course->id);
        // Skip if not a configured one.
        if (!empty($restrictcms) && !in_array($cm->id, $restrictcms)) {
            continue;
        }

        $context = context_module::instance($cm->id);
        if ($cm->visible || has_capability('moodle/course:viewhiddenactivities', $context)) {
            if (has_capability('mod/data:viewentry', $context)) {
                $dataids[] = $data->id;
            }
        }
    }

    // We may be restricting the fields to search.
    $restrictfields = array();
    if (!empty($blockconfig->restrictfields)) {
        $restrictfields = explode(',', $blockconfig->restrictfields);
    }
    // Calculate the fields conditions.
    $restrictfieldssql = '';
    if (!empty($restrictfields)) {
        $restrictfieldssql = ' AND dc.fieldid IN (' . implode(', ', $restrictfields) . ')';
    }

    // Seach starts.
    $contentsearch = "";

    $searchterms = explode(" ",$query);

    foreach ($searchterms as $searchterm) {

        $searchterm = textlib::strtolower($searchterm);

        if ($contentsearch) {
            $contentsearch .= " AND ";
        }

        if (substr($searchterm,0,1) == "+") {
            $searchterm = substr($searchterm,1);
            $contentsearch .= " lower(dc.content) $REGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
        } else if (substr($searchterm,0,1) == "-") {
            $searchterm = substr($searchterm,1);
            $contentsearch .= " lower(dc.content) $NOTREGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
        } else {
            $contentsearch .= " lower(dc.content) $LIKE '%$searchterm%' ";
        }
    }

    // Add seach conditions in contents.
    $where = "AND ( ($contentsearch) ) ";

    // Main query, only to allowed datas and not hidden chapters.
    $sqlselect  = "SELECT DISTINCT dc.recordid AS recordid, d.id AS dataid";
    $sqlfrom    = "FROM {data_content} dc
                   JOIN {data_records} dr ON dr.id = dc.recordid
                   JOIN {data} d ON d.id = dr.dataid";
    $sqlwhere   = "WHERE d.course = $course->id
                     AND d.id IN (" . implode($dataids, ', ') . ")
                     AND (d.approval = '0' OR dr.approved = '1')
                     $restrictfieldssql
                     $where";
    $sqlorderby = "ORDER BY d.id, dr.id";

    // Set page limits.
    $limitfrom = $offset;
    $limitnum = 0;
    if ( $offset >= 0 ) {
        $limitnum = DATAMAXRESULTSPERPAGE;
    }

    $countentries = $DB->count_records_sql("select count(DISTINCT dc.recordid) $sqlfrom $sqlwhere", array());
    $allentries = $DB->get_records_sql("$sqlselect $sqlfrom $sqlwhere $sqlorderby", array(), $limitfrom, $limitnum);

    return $allentries;
}

//////////////////////////////////////////////////////////
// The main part of this script

$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/search_datas/search_datas.php', array(
        'courseid' => $courseid,
        'blockid' => $blockid,
        'bsquery' => $query,
        'page' => $page));

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourseid');
}

if (!$instance = $DB->get_record('block_instances', array('id' => $blockid))) {
    print_error('invalidblockid');
}

// Get block configuration
$blockconfig = block_instance('search_datas', $instance)->config;

require_course_login($course);

$strdatas = get_string('modulenameplural', 'data');
$searchdatas = get_string('datassearch', 'block_search_datas');
$searchresults = get_string('searchresults', 'block_search_datas');
$strresults = get_string('results', 'block_search_datas');
$ofabout = get_string('ofabout', 'block_search_datas');
$for = get_string('for', 'block_search_datas');
$seconds = get_string('seconds', 'block_search_datas');

$PAGE->navbar->add($strdatas, new moodle_url('/mod/data/index.php', array('id' => $course->id)));
$PAGE->navbar->add($searchresults);

$PAGE->set_title($searchresults);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

$start = (DATAMAXRESULTSPERPAGE * $page);

// Process the query.
$query = trim(strip_tags($query));

// Launch the SQL quey.
$dataresults = search_datas_search($query, $course, $blockconfig, $start, $countentries);

$coursefield = '<input type="hidden" name="courseid" value="'.$courseid.'"/>';
$blockfield = '<input type="hidden" name="blockid" value="'.$blockid.'"/>';
$pagefield = '<input type="hidden" name="page" value="0"/>';
$searchbox = '<input type="text" name="bsquery" size="20" maxlength="255" value="'.s($query).'"/>';
$submitbutton = '<input type="submit" name="submit" value="'.$searchdatas.'"/>';

$content = $coursefield.$blockfield.$pagefield.$searchbox.$submitbutton;

$form = '<form method="get" action="'.$CFG->wwwroot.'/blocks/search_datas/search_datas.php" name="form" id="form">'.$content.'</form>';

echo '<div style="margin-left: auto; margin-right: auto; width: 100%; text-align: center">' . $form . '</div>';

// Process $dataresults, if present.
$startindex = $start;
$endindex = $start + count($dataresults);

// Look if there are any fields to show in the results.
$resultfields = array();
if (!empty($blockconfig->resultfields)) {
    $resultfields = explode(',', $blockconfig->resultfields);
}

$countresults = $countentries;

// Print results page tip.
$page_bar = glossary_get_paging_bar($countresults, $page, DATAMAXRESULTSPERPAGE, "search_datas.php?bsquery=".urlencode(stripslashes($query))."&amp;courseid=$course->id&amp;blockid=$blockid&amp;");

// Iterate over results.
if (!empty($dataresults)) {
    // Print header
    echo '<p style="text-align: right">'.$strresults.' <b>'.($startindex+1).'</b> - <b>'.$endindex.'</b> '.$ofabout.'<b> '.$countresults.' </b>'.$for.'<b> "'.s($query).'"</b></p>';
    echo $page_bar;
    // Prepare each entry (hilight, footer...)
    echo '<ul>';
    foreach ($dataresults as $entry) {
        $data = $DB->get_record('data', array('id' => $entry->dataid));
        $cm = get_coursemodule_from_instance("data", $data->id, $course->id);
        // If no field has been specified, look for the first text | textarea available and use it
        if (empty($resultfields)) {
            $params = array(
                'dataid' => $data->id,
                'type1'  => 'text',
                'type2'  => 'textarea');
            if ($firstfield = $DB->get_record_sql('
                    SELECT df.id
                      FROM {data_fields} df
                     WHERE df.dataid = :dataid
                       AND (df.type = :type1 OR df.type = :type2)
                  ORDER BY df.id', $params, IGNORE_MULTIPLE)) {
                $resultfields = array($firstfield->id);
            }
        }
        // Prepare the configured contents to show
        $description = '';
        foreach ($resultfields as $field) {
            $fieldrec = $DB->get_record('data_fields', array('id' => $field));
            require_once($CFG->dirroot . '/mod/data/field/' . $fieldrec->type . '/field.class.php');
            $classname = 'data_field_' . $fieldrec->type;
            $field = new $classname($fieldrec);
            $description .= $field->display_browse_field($entry->recordid, null);
        }
        // If description continues empty, fallback to lang string
        if (empty($description)) {
            $description = get_string('viewrecord', 'block_search_datas');
        }

        //To show where each entry belongs to
        $result = "<li><a href=\"$CFG->wwwroot/mod/data/view.php?id=$cm->id\">".format_string($data->name,true)."</a>&nbsp;&raquo;&nbsp;<a href=\"$CFG->wwwroot/mod/data/view.php?d=$data->id&amp;rid=$entry->recordid\">".$description."</a></li>";
        echo $result;
    }
    echo '</ul>';
    echo $page_bar;
} else {
    echo '<br />';
    echo $OUTPUT->box(get_string("norecordsfound","block_search_datas"),'CENTER');
}

echo $OUTPUT->footer();
