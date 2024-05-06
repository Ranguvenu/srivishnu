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
 * @package   theme_odl
 * @copyright  2018 eAbyas Info Solutons Pvt Ltd, India
 * @author     eAbyas  <info@eAbyas.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new theme_odl_admin_settingspage_tabs('themesettingodl', get_string('configtitle', 'theme_odl'));

    $page = new admin_settingpage('theme_odl_general', get_string('generalsettings', 'theme_odl'));

    // Preset.
    $name = 'theme_odl/preset';
    $title = get_string('preset', 'theme_odl');
    $description = get_string('preset_desc', 'theme_odl');
    $default = 'default.scss';

    $context = context_system::instance();
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'theme_odl', 'preset', 0, 'itemid, filepath, filename', false);

    $choices = [];
    foreach ($files as $file) {
        $choices[$file->get_filename()] = $file->get_filename();
    }
    // These are the built in presets.
    $choices['default.scss'] = 'default.scss';
    $choices['plain.scss'] = 'plain.scss';

    $setting = new admin_setting_configthemepreset($name, $title, $description, $default, $choices, 'odl');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Preset files setting.
    $name = 'theme_odl/presetfiles';
    $title = get_string('presetfiles','theme_odl');
    $description = get_string('presetfiles_desc', 'theme_odl');

    $setting = new admin_setting_configstoredfile($name, $title, $description, 'preset', 0,
        array('maxfiles' => 20, 'accepted_types' => array('.scss')));
    $page->add($setting);

    // Site Background image.
    $name = 'theme_odl/backgroundimage';
    $title = get_string('backgroundimage', 'theme_odl');
    $description = get_string('backgroundimage_desc', 'theme_odl');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'backgroundimage');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Login Background image.
    $name = 'theme_odl/loginbg';
    $title = get_string('loginbg', 'theme_odl');
    $description = get_string('loginbg_desc', 'theme_odl');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'loginbg');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Variable $body-color.
    // We use an empty default value because the default colour should come from the preset.
    $name = 'theme_odl/brandcolor';
    $title = get_string('brandcolor', 'theme_odl');
    $description = get_string('brandcolor_desc', 'theme_odl');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Must add the page after definiting all the settings!
    $settings->add($page);

    // Advanced settings.
    $page = new admin_settingpage('theme_odl_advanced', get_string('advancedsettings', 'theme_odl'));

    // Raw SCSS to include before the content.
    $setting = new admin_setting_scsscode('theme_odl/scsspre',
        get_string('rawscsspre', 'theme_odl'), get_string('rawscsspre_desc', 'theme_odl'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS to include after the content.
    $setting = new admin_setting_scsscode('theme_odl/scss', get_string('rawscss', 'theme_odl'),
        get_string('rawscss_desc', 'theme_odl'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
    
    $page = new admin_settingpage('theme_odl_custom', get_string('customsettings', 'theme_odl'));

    //Logo setting over site
    $name = 'theme_odl/logo';
    $title = get_string('logo', 'theme_odl');
    $description = get_string('logodesc', 'theme_odl');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    // custom favicon
    $name = 'theme_odl/faviconurl';
    $title = get_string('favicon', 'theme_odl');
    $description = get_string('favicondesc', 'theme_odl');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'faviconurl');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    // Login Page Logo.
    $name = 'theme_odl/loginlogo';
    $title = get_string('loginlogo', 'theme_odl');
    $description = get_string('loginlogo_desc', 'theme_odl');
    $default = '';
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'loginlogo');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    // Description under Login Page Logo.
    $name = 'theme_odl/logindesc';
    $title = get_string('logindesc', 'theme_odl');
    $description = get_string('logindesc_desc', 'theme_odl');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    // Description for buttons on Login Page.
    $name = 'theme_odl/helpdesc';
    $title = get_string('helpdesc', 'theme_odl');
    $description = get_string('helpdesc_desc', 'theme_odl');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    $name = 'theme_odl/contact';
    $title = get_string('contact', 'theme_odl');
    $description = get_string('contact_desc', 'theme_odl');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    $name = 'theme_odl/aboutus';
    $title = get_string('aboutus', 'theme_odl');
    $description = get_string('aboutus_desc', 'theme_odl');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    // Must add the page after definiting all the settings!
    //login page slider image1 
    $name = 'theme_odl/slider1';
    $title = get_string('slider1', 'theme_odl');
    $description = get_string('slider1desc', 'theme_odl');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'slider1');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    //login page slider image2 
    $name = 'theme_odl/slider2';
    $title = get_string('slider2', 'theme_odl');
    $description = get_string('slider2desc', 'theme_odl');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'slider2');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    //login page slider image3 
    $name = 'theme_odl/slider3';
    $title = get_string('slider3', 'theme_odl');
    $description = get_string('slider3desc', 'theme_odl');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'slider3');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    //login page slider image4 
    $name = 'theme_odl/slider4';
    $title = get_string('slider4', 'theme_odl');
    $description = get_string('slider4desc', 'theme_odl');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'slider4');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    //login page slider image5 
    $name = 'theme_odl/slider5';
    $title = get_string('slider5', 'theme_odl');
    $description = get_string('slider5desc', 'theme_odl');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'slider5');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    //fonts setting
    // $name = 'theme_odl/font';
    // $title = get_string('font', 'theme_odl');
    // $description = get_string('font_desc', 'theme_odl');
    // $default = 3;
    // $choices = array('Lato', 'Open Sans', 'PT Sans', 'Roboto', 'Maven Pro', 'Comfortaa');
    // $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    // $setting->set_updatedcallback('theme_reset_all_caches');
    // $page->add($setting);

    //==== footer settings =====
    // Footnote setting.
    $name = 'theme_odl/copyright';
    $title = get_string('copyright', 'theme_odl');
    $description = get_string('copyrightdesc', 'theme_odl');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    $name = 'theme_odl/facebook';
    $title = get_string('facebook', 'theme_odl');
    $description = get_string('facebookdesc', 'theme_odl');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    $name = 'theme_odl/twitter';
    $title = get_string('twitter', 'theme_odl');
    $description = get_string('twitterdesc', 'theme_odl');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    $name = 'theme_odl/linkedin';
    $title = get_string('linkedin', 'theme_odl');
    $description = get_string('linkedindesc', 'theme_odl');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    $name = 'theme_odl/youtube';
    $title = get_string('youtube', 'theme_odl');
    $description = get_string('youtubedesc', 'theme_odl');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    $name = 'theme_odl/instagram';
    $title = get_string('instagram', 'theme_odl');
    $description = get_string('instagramdesc', 'theme_odl');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
    
    $page = new admin_settingpage('theme_odl_color', get_string('colorsettings', 'theme_odl'));

    $name = 'theme_odl/theme_scheme';
    $title = get_string('theme_scheme', 'theme_odl');
    $description = get_string('theme_scheme_desc', 'theme_odl');
    $default = 'scheme1';
    $choices = array('scheme1' => get_string('scheme_1', 'theme_odl'),
                     'scheme2' => get_string('scheme_2', 'theme_odl'),
                     'scheme3' => get_string('scheme_3', 'theme_odl'),
                     'scheme4' => get_string('scheme_4', 'theme_odl'),
                     'scheme5' => get_string('scheme_5', 'theme_odl'),
                     0 => get_string('customscheme', 'theme_odl')
                 );
    
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    //Custom SCSS to change the Body bg color
    $name = 'theme_odl/bodybgcolor';
    $title = get_string('bodybgcolor', 'theme_odl');
    $description = get_string('bodybgcolor_desc', 'theme_odl');
    $default = '#f5f4f1';
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    $settings->add($page);
}