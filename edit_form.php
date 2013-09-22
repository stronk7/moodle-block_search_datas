<?php
// This file is part of Moodle - http://moodle.org/
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
 * Form to edit configuration of block_search_datas.
 *
 * @package   block_search_datas
 * @copyright 2013 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_search_datas_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        global $CFG;

        // Fields for editing HTML block title and contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_restrictcms', get_string('restrictcms', 'block_search_datas'));
        $mform->setType('config_restrictcms', PARAM_TAGLIST);

        $mform->addElement('text', 'config_restrictfields', get_string('restrictfields', 'block_search_datas'));
        $mform->setType('config_restrictfields', PARAM_TAGLIST);

        $mform->addElement('text', 'config_resultfields', get_string('resultfields', 'block_search_datas'));
        $mform->setType('config_resultfields', PARAM_TAGLIST);
    }
}
