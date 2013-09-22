<?php
// This file is part of block_search_datas,
// one contrib block for Moodle - http://moodle.org/
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
 * Search datas block main file.
 *
 * This block enables searching within all the datas in a given course.
 *
 * @package    block_search_datas
 * @copyright  2013 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_search_datas extends block_base {
    function init() {
        $this->title = get_string('pluginname','block_search_datas');
    }

    function has_config() {return false;}

    function applicable_formats() {
        return (array('site-index' => true, 'course-view-weeks' => true, 'course-view-topics' => true));
    }

    function get_content() {
        global $CFG, $USER, $COURSE, $DB;

        if ($this->content !== NULL) {
            return $this->content;
        }

        if ($COURSE->id == $this->page->course->id) {
            $course = $COURSE;
        } else {
            $course = $DB->get_record('course', array('id' => $this->page->course->id));
        }

        // Course not found, we won't do anything in the block
        if (empty($course)) {
            return '';
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }

        $searchdatas = get_string('datassearch', 'block_search_datas');

        $this->content->text  = '<div class="searchform">';
        $this->content->text .= '<form action="' . $CFG->wwwroot . '/blocks/search_datas/search_datas.php" style="display:inline">';
        $this->content->text .= '<fieldset class="invisiblefieldset">';
        $this->content->text .= '<input name="courseid" type="hidden" value="' . $course->id . '" />';
        $this->content->text .= '<input name="blockid" type="hidden" value="' . $this->instance->id . '" />';
        $this->content->text .= '<input name="page" type="hidden" value="0" />';
        $this->content->text .= '<label class="accesshide" for="searchdatasquery">' . $searchdatas . '</label>';
        $this->content->text .= '<input id="searchdatasquery" name="bsquery" size="20" maxlength="255" value="" />';
        $this->content->text .= '<br /><input type="submit" name="submit" value="' . $searchdatas . '"/>';
        $this->content->text .= '</fieldset></form></div>';

        return $this->content;
    }
}
