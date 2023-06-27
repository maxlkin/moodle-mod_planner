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
 * Resource module admin settings and defaults
 *
 * @copyright 2021 Brickfield Education Labs, www.brickfield.ie
 * @package    mod_planner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$pagetitle = get_string('generalsettings', 'admin');
$plannersettings = new admin_settingpage('modsettingplanner', $pagetitle, 'moodle/site:config');

if ($ADMIN->fulltree) {
    $name = new lang_string('upcomingemailtemplate', 'mod_planner');
    $description = new lang_string('upcomingemailtemplate_help', 'mod_planner');
    $default = get_string('upcomingemailtemplatedefault', 'mod_planner');
    $setting = new admin_setting_configtextarea(
        'planner/upcomingemail',
        $name,
        $description,
        $default
    );
    $setting->set_force_ltr(false);
    $plannersettings->add($setting);

    $name = new lang_string('missedemailtemplate', 'mod_planner');
    $description = new lang_string('missedemailtemplate_help', 'mod_planner');
    $default = get_string('missedemailtemplatedefault', 'mod_planner');
    $setting = new admin_setting_configtextarea(
        'planner/dueemail',
        $name,
        $description,
        $default
    );
    $setting->set_force_ltr(false);
    $plannersettings->add($setting);

    $plannersettings->add(
        new admin_setting_configtext(
            'planner/frequencyemail',
            get_string('frequencyemail', 'mod_planner'),
            get_string('frequencyemailhelp', 'mod_planner'),
            2,
            PARAM_INT,
            2
        )
    );
    $name = new lang_string('linkedactivityemailtemplate', 'mod_planner');
    $description = new lang_string('linkedactivityemailtemplate_help', 'mod_planner');
    $default = get_string('linkedactivityemailtemplatedefault', 'mod_planner');
    $setting = new admin_setting_configtextarea(
        'planner/deletedactivityemail',
        $name,
        $description,
        $default
    );
    $setting->set_force_ltr(false);
    $plannersettings->add($setting);

    $name = new lang_string('linkedactivitystudentemailtemplate', 'mod_planner');
    $description = new lang_string('linkedactivitystudentemailtemplate_help', 'mod_planner');
    $default = get_string('linkedactivitystudentemailtemplatedefault', 'mod_planner');
    $setting = new admin_setting_configtextarea(
        'planner/deletedactivitystudentemail',
        $name,
        $description,
        $default
    );
    $setting->set_force_ltr(false);
    $plannersettings->add($setting);

    $name = new lang_string('changedateemailtemplate', 'mod_planner');
    $description = new lang_string('changedateemailtemplate_help', 'mod_planner');
    $default = get_string('changedateemailtemplatedefault', 'mod_planner');
    $setting = new admin_setting_configtextarea(
        'planner/changedateemailtemplate',
        $name,
        $description,
        $default
    );
    $setting->set_force_ltr(false);
    $plannersettings->add($setting);

    for ($i = 1; $i <= 6; $i++) {
        $plannersettings->add(
            new admin_setting_configtext(
                'planner/step' . $i . 'name',
                get_string('stepnamesettings', 'mod_planner', $i),
                '',
                get_string('step' . $i . 'default', 'mod_planner'),
                PARAM_RAW,
                50
            )
        );
        $plannersettings->add(
            new admin_setting_configtext(
                'planner/step' . $i . 'timeallocation',
                get_string('steptimeallocationsettings', 'mod_planner', $i),
                get_string('enternumber', 'planner'),
                get_string('step'.$i.'defaultallocatin', 'mod_planner'),
                PARAM_INT,
                3
            )
        );
        $name = new lang_string('stepdescriptionsettings', 'mod_planner', $i);
        $setting = new admin_setting_confightmleditor('planner/step'.$i.'description', $name, '', '');
        $setting->set_force_ltr(false);
        $plannersettings->add($setting);
    }
}
if (has_capability('mod/planner:managetemplates', context_system::instance())) {
    $ADMIN->add(
        'modsettings',
        new admin_category('modplanner', get_string('modulename', 'planner'), $module->is_enabled() === false)
    );
    $ADMIN->add('modplanner', $plannersettings);
    $ADMIN->add(
        'modplanner',
        new admin_externalpage(
            'planner/template',
            get_string('manage_templates', 'planner'),
            new moodle_url('/mod/planner/template.php')
        )
    );
}

$settings = null;
