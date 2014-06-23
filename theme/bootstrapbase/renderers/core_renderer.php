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
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_bootstrapbase
 * @copyright  2012 Bas Brands, www.basbrands.nl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class theme_bootstrapbase_core_renderer extends core_renderer {

    /** @var custom_menu_item language The language menu if created */
    protected $language = null;

    /*
     * This renders a notification message.
     * Uses bootstrap compatible html.
     */
    public function notification($message, $classes = 'notifyproblem') {
        $message = clean_text($message);
        $type = '';

        if (($classes == 'notifyproblem') || ($classes == 'notifytiny')) {
            $type = 'alert alert-error';
        }
        if ($classes == 'notifysuccess') {
            $type = 'alert alert-success';
        }
        if ($classes == 'notifymessage') {
            $type = 'alert alert-info';
        }
        if ($classes == 'redirectmessage') {
            $type = 'alert alert-block alert-info';
        }
        return "<div class=\"$type\">$message</div>";
    }

    /*
     * This renders the navbar.
     * Uses bootstrap compatible html.
     */
    public function navbar() {
        $items = $this->page->navbar->get_items();
        if (empty($items)) {
            return '';
        }
        $breadcrumbs = array();
        foreach ($items as $item) {
            $item->hideicon = true;
            $breadcrumbs[] = $this->render($item);
        }
        $divider = '<span class="divider">'.get_separator().'</span>';
        $list_items = '<li>'.join(" $divider</li><li>", $breadcrumbs).'</li>';
        $title = '<span class="accesshide">'.get_string('pagepath').'</span>';
        return $title . "<ul class=\"breadcrumb\">$list_items</ul>";
    }

    /*
     * Helper function for constructing a custom_menu object.
     *
     * Overriding the custom_menu function ensures the custom menu is
     * always shown, even if no menu items are configured in the global
     * theme settings page.
     *
     * @return HTML fragment.
     */
    public function custom_menu($custommenuitems = '') {
        global $CFG;

        // Construct the custom menu based off the parameter given + admin settings if present.
        if (empty($custommenuitems) && !empty($CFG->custommenuitems)) {
            $custommenuitems = $CFG->custommenuitems;
        }
        $menu = new custom_menu($custommenuitems, current_language());

        // Add the language menu if necessary.
        // TODO: eliminate this duplicated logic, it belongs in core, not
        // here. See MDL-39565.
        $addlangmenu = true;
        $langs = get_string_manager()->get_list_of_translations();
        if (count($langs) < 2
            or empty($CFG->langmenu)
            or ($this->page->course != SITEID and !empty($this->page->course->lang))) {
            $addlangmenu = false;
        }

        if (!$menu->has_children() && $addlangmenu === false) {
            return '';
        }

        if ($addlangmenu) {
            $strlang =  get_string('language');
            $currentlang = current_language();
            if (isset($langs[$currentlang])) {
                $currentlang = $langs[$currentlang];
            } else {
                $currentlang = $strlang;
            }
            $this->language = $menu->add($currentlang, new moodle_url('#'), $strlang, 10000);
            foreach ($langs as $langtype => $langname) {
                $this->language->add($langname, new moodle_url($this->page->url, array('lang' => $langtype)), $langname);
            }
        }

        return $this->render_custom_menu($menu);
    }

    /**
     * This renders the bootstrap top menu, and is needed to enable the Bootstrap style navigation.
     *
     * Language menu logic has moved to the helper, custom_menu(), to 1) satisfy best practices and 2) to allow us to re-use
     * this renderer for the user_menu.
     *
     * The wrapping ul.nav is no longer emitted; when used as part of a {@link bootstrap_navbar_item} the navbar item needs to be
     * able to control alignment on that element (see MDL-45893).
     *
     * @param custom_menu $menu The menu to render.
     * @return string HTML fragment.
     */
    protected function render_custom_menu(custom_menu $menu) {
        $content = '';
        foreach ($menu->get_children() as $item) {
            $content .= $this->render_custom_menu_item($item, 1);
        }

        return $content;
    }

    /**
     * Renders a given user_menu using the custom_menu renderer.
     * @param user_menu $menu The menu to render.
     * @return string HTML fragment.
     */
    protected function render_user_menu(user_menu $menu) {
        return $this->render_custom_menu($menu);
    }

    /**
     * This code renders the custom menu items for the bootstrap dropdown menu.
     * @param custom_menu_item $menunode A node to render.
     * @param int $level The nesting level of the node in question.
     * @return string HTML fragment.
     */
    protected function render_custom_menu_item(custom_menu_item $menunode, $level = 0 ) {
        static $submenucount = 0;

        $classes = '';
        if (strlen(trim($menunode->get_class_suffix())) > 0) {
            $classes = 'menuitem-' . trim($menunode->get_class_suffix());
        }

        $content = '';
        if ($menunode->has_children()) {

            if ($level == 1) {
                $class = 'dropdown';
            } else {
                $class = 'dropdown-submenu';
            }

            if ($menunode === $this->language) {
                $class .= ' langmenu';
            }
            $content = html_writer::start_tag('li', array('class' => $class . ' ' . $classes));
            // If the child has menus render it as a sub menu.
            $submenucount++;
            $dropdowntoggletag = 'a';
            $dropdowntoggleattrs = array(
                'class' => 'dropdown-toggle',
                'data-toggle' => 'dropdown',
                'title' => $menunode->get_title()
            );
            if ($menunode->get_url() !== null) {
                $dropdowntoggleattrs['url'] = $menunode->get_url();
            } else {
                $dropdowntoggletag = 'div';
            }
            $content .= html_writer::start_tag(
                $dropdowntoggletag,
                $dropdowntoggleattrs
            );
            $content .= $menunode->get_text();
            if ($level == 1) {
                $content .= '<b class="caret"></b>';
            }
            $content .= html_writer::end_tag($dropdowntoggletag);
            $content .= '<ul class="dropdown-menu">';
            foreach ($menunode->get_children() as $menunode) {
                $content .= $this->render_custom_menu_item($menunode, 0);
            }
            $content .= '</ul>';
        } else {
            // The node doesn't have children so produce a final menuitem.
            // Also, if the node's text matches '####', add a class so we can treat it as a divider.
            if (preg_match("/^#+$/", $menunode->get_text())) {
                // This is a divider.
                $content = '<li class="divider">&nbsp;</li>';
            } else {
                $content = html_writer::start_tag('li', array('class' => $classes));
                if ($menunode->get_url() !== null) {
                    $url = $menunode->get_url();
                } else {
                    $url = '#';
                }
                $content .= html_writer::link($url, $menunode->get_text(), array('title' => $menunode->get_title(), 'class' => 'nochildren'));
                $content .= html_writer::end_tag('li');
            }
        }
        return $content;
    }

    /**
     * Constructs and then renders a Bootstrap navbar (not to be confused with our breadcrumbs, which we also call 'navbar').
     * @return string HTML fragment.
     */
    public function bootstrap_header($extraclasses = null) {
        global $SITE, $USER;

        $navbar = new bootstrap_navbar($SITE->shortname);
        $navbar->extraclasses($extraclasses);

        // Enqueue custom menu (only if one's actually defined!)
        $custommenu = $this->custom_menu('', true);
        if (strlen(trim($custommenu)) > 0) {
            $navbar->enqueue(new bootstrap_navbar_item(
                'custom',
                array(
                    'menu' => $custommenu
                )
            ));
        }

        // Enqueue user menu.
        // Get the bare minimum flags relating to user pictures and then retrieve the pictures themselves.
        $userflags = array(
            'loggedin' => isloggedin(),
            'as_otheruser' => \core\session\manager::is_loggedinas()
        );
        if ($userflags['loggedin']) {
            if ($userflags['as_otheruser']) {
                $realuser = \core\session\manager::get_realuser();
                $userflags['otheruser_avatar'] = $this->user_picture($USER, array('link' => false));
                $userflags['avatar'] = $this->user_picture($realuser, array('link' => false));
            } else {
                $userflags['avatar'] = $this->user_picture($USER, array('link' => false));
            }
        }
        $navbar->enqueue(new bootstrap_navbar_item(
            'user',
            array(
                'button' => $userflags['loggedin'] ? $userflags['avatar'] : null,
                'menu' => $this->user_dropdown(null, true),
                'alignment' => 'right',
                'collapses' => $userflags['loggedin']
            )
        ));

        // Enqueue page heading menu (again, only if one's defined).
        $pageheading = $this->page_heading_menu();
        if (strlen($pageheading) > 0) {
            $navbar->enqueue(new bootstrap_navbar_item(
                'headermenu',
                array(
                    'menu' => $pageheading,
                    'collapses' => false,
                    'alignment' => 'right'
                )
            ));
        }

        return $this->render($navbar);
    }

    /**
     * Renders a Bootstrap navbar.
     *
     * @param bootstrap_navbar $navbar The navbar to render.
     * @return string HTML fragment.
     */
    protected function render_bootstrap_navbar(bootstrap_navbar $navbar) {
        global $CFG;

        $innerstr = '';

        // Render brand.
        $innerstr .= html_writer::tag(
            'a',
            $navbar->brand(),
            array('class' => 'brand', 'href' => $CFG->wwwroot)
        );

        // Render menus.
        $buttonstr = '';
        $menustr = '';
        foreach ($navbar->menus() as $key => $menu) {
            $name = $menu->name();
            $opts = $menu->settings();
            $navclasses = 'nav pull-' . $opts['alignment'];
            $menuclasses = '';
            if ($opts['collapses']) {

                if (strlen($opts['button']) != 0) {
                    $buttonstr .= html_writer::tag(
                        'a',
                        $opts['button'],
                        array(
                            'class' => 'btn btn-navbar btn-navbar-' . $name,
                            'data-toggle' => 'workaround-collapse',
                            'data-target' => '.nav-collapse-' . $name
                        )
                    );
                } else {
                    $buttonstr .= html_writer::tag(
                        'a',
                        bootstrap_navbar_item::DEFAULT_BUTTON,
                        array(
                            'class' => 'btn btn-navbar default btn-navbar-' . $name,
                            'data-toggle' => 'workaround-collapse',
                            'data-target' => '.nav-collapse-' . $name
                        )
                    );

                }

                $menuclasses = 'nav-collapse nav-collapse-' . $name;
            }
            $menustr .= html_writer::tag(
                'div',
                html_writer::tag(
                    'ul',
                    $opts['menu'],
                    array('class' => $navclasses)
                ),
                array('class' => $menuclasses)
            );
        }
        $innerstr .= $buttonstr . $menustr;

        // Wrap output in a <header />.
        $outputstr = html_writer::tag(
            'header',
            html_writer::tag(
                'nav',
                html_writer::tag(
                    'div',
                    $innerstr,
                    array('class' => 'container-fluid')
                ),
                array('class' => 'navbar-inner', 'role' => 'navigation')
            ),
            array('class' => 'navbar navbar-fixed-top moodle-has-zindex' . $navbar->extraclasses(), 'role' => 'banner')
        );

        return $outputstr;
    }

    /**
     * Renders tabtree
     *
     * @param tabtree $tabtree
     * @return string
     */
    protected function render_tabtree(tabtree $tabtree) {
        if (empty($tabtree->subtree)) {
            return '';
        }
        $firstrow = $secondrow = '';
        foreach ($tabtree->subtree as $tab) {
            $firstrow .= $this->render($tab);
            if (($tab->selected || $tab->activated) && !empty($tab->subtree) && $tab->subtree !== array()) {
                $secondrow = $this->tabtree($tab->subtree);
            }
        }
        return html_writer::tag('ul', $firstrow, array('class' => 'nav nav-tabs')) . $secondrow;
    }

    /**
     * Renders tabobject (part of tabtree)
     *
     * This function is called from {@link core_renderer::render_tabtree()}
     * and also it calls itself when printing the $tabobject subtree recursively.
     *
     * @param tabobject $tabobject
     * @return string HTML fragment
     */
    protected function render_tabobject(tabobject $tab) {
        if ($tab->selected or $tab->activated) {
            return html_writer::tag('li', html_writer::tag('a', $tab->text), array('class' => 'active'));
        } else if ($tab->inactive) {
            return html_writer::tag('li', html_writer::tag('a', $tab->text), array('class' => 'disabled'));
        } else {
            if (!($tab->link instanceof moodle_url)) {
                // backward compartibility when link was passed as quoted string
                $link = "<a href=\"$tab->link\" title=\"$tab->title\">$tab->text</a>";
            } else {
                $link = html_writer::link($tab->link, $tab->text, array('title' => $tab->title));
            }
            return html_writer::tag('li', $link);
        }
    }
}

/**
 * Overridden core maintenance renderer.
 *
 * This renderer gets used instead of the standard core_renderer during maintenance
 * tasks such as installation and upgrade.
 * We override it in order to style those scenarios consistently with the regular
 * bootstrap look and feel.
 *
 * @package    theme_bootstrapbase
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_bootstrapbase_core_renderer_maintenance extends core_renderer_maintenance {
    /**
     * Renders notifications for maintenance scripts.
     *
     * We need to override this method in the same way we do for the core_renderer maintenance method
     * found above.
     * Please note this isn't required of every function, only functions used during maintenance.
     * In this case notification is used to print errors and we want pretty errors.
     *
     * @param string $message
     * @param string $classes
     * @return string
     */
    public function notification($message, $classes = 'notifyproblem') {
        $message = clean_text($message);
        $type = '';

        if (($classes == 'notifyproblem') || ($classes == 'notifytiny')) {
            $type = 'alert alert-error';
        }
        if ($classes == 'notifysuccess') {
            $type = 'alert alert-success';
        }
        if ($classes == 'notifymessage') {
            $type = 'alert alert-info';
        }
        if ($classes == 'redirectmessage') {
            $type = 'alert alert-block alert-info';
        }
        return "<div class=\"$type\">$message</div>";
    }
}

/**
 * A menu for enqueueing in a {@link bootstrap_navbar}; not renderable by itself.
 *
 * @copyright 2014 Jetha Chan
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.8
 * @category output
 */
class bootstrap_navbar_item {

    /**
     * This is a HTML fragment used for navbar items that have an undefined button.
     */
    const DEFAULT_BUTTON = '<span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>';

    /** @var string An internal name for this menu, used to enqueue it and also for CSS. */
    protected $name = '';
    /** @var array An array of strings and HTML fragments that control the behaviour of this menu. */
    protected $settings;
    /** @var array Default strings and HTML fragments. */
    static protected $defaultsettings = array(
        'alignment' => 'left',
        'collapses' => true,
        'button' => '',
        'menu' => ''
    );

    /**
     * Returns the name of this menu.
     * @return string The name of this menu.
     */
    public function name() {
        return $this->name;
    }

    /**
     * Returns (and optionally sets) the settings currently associated with this menu.
     * @param array $settings An optionally-incomplete settings array to merge into and displace the existing settings object.
     * @return array The settings currently associated with the menu.
     */
    public function settings(array $settings = null) {
        if (!empty($settings)) {
            $this->settings = array_merge(self::$defaultsettings, $settings);
        }

        return $this->settings;
    }

    /**
     * Constructs a menu.
     * @param string $name The name this menu should go by.
     * @param array $settings (optional) An array of settings.
     */
    public function __construct($name, $settings = null) {
        $this->name = $name;
        if (empty($settings)) {
            $this->settings = self::$defaultsettings;
        } else {
            $this->settings = array_merge(self::$defaultsettings, $settings);
        }
    }
}

/**
 * A Bootstrap-compliant navbar, comprising a site "brand" and a list of {@link bootstrap_navbar_item} objects.
 *
 * @copyright 2014 Jetha Chan
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.8
 * @category output
 */
class bootstrap_navbar implements renderable {

    /** @var string The "brand" associated with this navbar (and by extension the site). */
    protected $brand;
    /** @var array An array of {@link bootstrap_navbar_item} objects for rendering. */
    protected $menus;
    /** @var string A string of extra CSS classes to be applied. */
    protected $extraclasses;

    /**
     * Gets and sets the "brand" (if a new one is provided) associated with this navbar.
     * @param string $brand The new brand to use (optional).
     * @return string The current brand.
     */
    public function brand($brand = null) {
        if (!empty($brand)) {
            $this->brand = $brand;
        }
        return $this->brand;
    }

    /**
     * Gets and sets the menus (if a new array of menus is provided) associated with this navbar.
     * @param array $menus The new menus to use (optional).
     * @return array $menus The current menus.
     */
    public function menus($menus = null) {
        if (!empty($menus)) {
            $this->menus = $menus;
        }
        return $this->menus;
    }

    /**
     * Gets and sets the extra CSS classes (if a new string of extra classes is provided) associated with this navbar.
     * @param string $extraclasses The new classes to use (optional).
     * @return string The current classes.
     */
    public function extraclasses($extraclasses = null) {
        if (!empty($extraclasses)) {
            $this->extraclasses = $extraclasses;
        }
        return $this->extraclasses;
    }

    /**
     * Constructs the navbar given a specified brand.
     * @param string $brand The new brand to use (optional).
     */
    public function __construct($brand = null) {
        if (empty($brand)) {
            $this->brand = 'bootstrap_navbar';
        } else {
            $this->brand = $brand;
        }
        $this->menus = array();
        $this->extraclasses = '';
    }

    /**
     * Enqueue a {@link bootstrap_navbar_item} in this navbar.
     * @param bootstrap_navbar_item $item The item to enqueue.
     * @return void
     */
    public function enqueue(bootstrap_navbar_item $item) {
        if (empty($item)) {
            return;
        }
        $this->menus[$item->name()] = $item;
    }
}
