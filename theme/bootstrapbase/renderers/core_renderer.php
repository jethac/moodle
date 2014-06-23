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
     * Overriding the custom_menu function ensures the custom menu is
     * always shown, even if no menu items are configured in the global
     * theme settings page.
     */
    public function custom_menu($custommenuitems = '', $omit_list_tag = false) {
        global $CFG;

        if (!empty($CFG->custommenuitems)) {
            $custommenuitems .= $CFG->custommenuitems;
        }
        $custommenu = new custom_menu($custommenuitems, current_language());
        return $this->render_custom_menu($custommenu, $omit_list_tag);
    }

    /*
     * This renders the bootstrap top menu.
     *
     * This renderer is needed to enable the Bootstrap style navigation.
     */
    protected function render_custom_menu(custom_menu $menu, $omit_list_tag = false) {
        global $CFG;

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

        $content = '';
        if ($omit_list_tag != true) {
            $content .= '<ul class="nav">';
        }
        foreach ($menu->get_children() as $item) {
            $content .= $this->render_custom_menu_item($item, 1);
        }

        if ($omit_list_tag != true) {
            $content .= '</ul>';
        }

        return $content;
    }

    protected function render_user_menu(user_menu $menu) {
        global $CFG;

        $username = $menu->user_node()->get_text();

        // Avatar.
        $avatar = $this->user_picture($menu->user(), array('link' => false));

        $content = '';
        if ($menu->guest()) {

            $menu->user_node()->set_text(
                html_writer::tag(
                    'span',
                    $avatar . html_writer::tag(
                        'span',
                        $username,
                        array('class' => 'usertext')
                    ),
                    array('class' => 'userbutton')
                )
            );

        } else {

            $extratext = '';

            // If the user has switched role or is from an MNet provider, amend text of user node.
            if (!empty(trim($menu->as_role()))) {
                $extratext .= html_writer::tag(
                    'span', 
                    get_string(
                        'roleviewas',
                        'moodle',
                        html_writer::tag(
                            'span',
                            $menu->as_role(),
                            array('class' => 'value')
                        )
                    ),
                    array('class' => 'role role-' . strtolower(trim(preg_replace('#[ -]+#', '-', $menu->as_role()))))
                );
            }
            if (!empty($menu->mnet_idprovider())) {
                $extratext .= html_writer::tag(
                    'span',
                    get_string(
                        'loggedinfrom',
                        'moodle',
                        html_writer::tag(
                            'span',
                            $menu->mnet_idprovider()->name,
                            array('class' => 'value')
                        )
                    ),
                    array('class' => 'mnet mnet-' . strtolower(trim(preg_replace('#[ -]+#', '-', $menu->mnet_idprovider()->name))))
                );
            }
            $menu->user_node()->set_text(
                html_writer::tag(
                    'span',
                    $avatar . html_writer::tag(
                        'span',
                        $username . $extratext,
                        array('class' => 'usertext')
                    ),
                    array('class' => 'userbutton')
                )
            );
        }

        $content = '';
        foreach ($menu->get_children() as $item) {
            $content .= $this->render_custom_menu_item($item, 1);
        }

        return $content;

    }

    /*
     * This code renders the custom menu items for the
     * bootstrap dropdown menu.
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
            if ($menunode->get_url() !== null) {
                $url = $menunode->get_url();
            } else {
                $url = '#cm_submenu_'.$submenucount;
            }
            $content .= html_writer::start_tag('a', array('href'=>$url, 'class'=>'dropdown-toggle', 'data-toggle'=>'dropdown', 'title'=>$menunode->get_title()));
            $content .= $menunode->get_text();
            if ($level == 1) {
                $content .= '<b class="caret"></b>';
            }
            $content .= '</a>';
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
                $content .= html_writer::link($url, $menunode->get_text(), array('title' => $menunode->get_title()));
                $content .= html_writer::end_tag('li');
            }
        }
        return $content;
    }

    /**
     * Constructs and then renders a Bootstrap navbar (not to be confused with our breadcrumbs, which we also call 'navbar').
     * @return string HTML fragment. 
     */
    public function bootstrap_navbar() {
        global $SITE, $USER;

        $navbar = new bootstrap_navbar($SITE->shortname);

        // Enqueue menus.
        // - custom menu
        $navbar->enqueue(new bootstrap_navbar_item(
            'custom',
            array(
                'menu' => $this->custom_menu('', true)
            )
        ));
        // - user menu
        $navbar->enqueue(new bootstrap_navbar_item(
            'user',
            array(
                'button' => $this->user_picture($USER, array('link' => false)),
                'menu' => $this->user_dropdown(),
                'alignment' => 'right'
            )
        ));
        // - page heading menu
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
            $nav_classes = 'nav pull-' . $opts['alignment'];
            $menu_classes = '';
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

                $menu_classes = 'nav-collapse nav-collapse-' . $name;
            }
            $menustr .= html_writer::tag(
                'div',
                html_writer::tag(
                    'ul',
                    $opts['menu'],
                    array('class' => $nav_classes)
                ),
                array('class' => $menu_classes)
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
            array('class' => 'navbar navbar-fixed-top moodle-has-zindex', 'role' => 'banner')
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
 * A menu for enqueueing in a Bootstrap-compliant navbar; not renderable by
 * itself.
 */
class bootstrap_navbar_item {

    const DEFAULT_BUTTON = '<span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>';

    var $name = '';
    public function name() {
        return $this->name;
    }

    var $settings;
    var $default_settings = array(
        'alignment' => 'left',
        'collapses' => true,
        'button' => '',
        'menu' => '' // replace with get_string localized message
    );
    public function settings() {
        return $this->settings;
    }
    public function __construct($name, array $settings) {
        $this->name = $name;
        $this->settings = array_merge($this->default_settings, $settings);
    }
}

/** 
 * A Bootstrap-compliant navbar, comprising a site "brand" and a list of menus,
 * each of which is a bootstrap_navbar_item object.
 */
class bootstrap_navbar implements renderable {

    var $brand;
    public function brand() {
        return $this->brand;
    }

    var $menus;
    public function menus() {
        return $this->menus;
    }

    public function __construct($brand) {
        $this->brand = $brand;
        $this->menus = array();
    }

    public function enqueue(bootstrap_navbar_item $item) {
        $this->menus[$item->name()] = $item;
    }
}