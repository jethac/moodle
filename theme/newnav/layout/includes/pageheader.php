<?php
//echo $this->page->pagetype ;
$__useUserHeader =
    $this->page->pagetype == 'user-profile' ||
    $this->page->pagetype == 'user-editadvanced';

if ($__useUserHeader) {
// this is a terrible, terrible hack. this should not be here.
global $DB;
$userid = optional_param('id', 0, PARAM_INT);
$user = $DB->get_record('user', array('id' => $userid));


$context = $usercontext = context_user::instance($userid, MUST_EXIST);

if (has_capability('moodle/user:viewhiddendetails', $context)) {
    $hiddenfields = array();
} else {
    $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
}

if (has_capability('moodle/site:viewuseridentity', $context)) {
    $identityfields = array_flip(explode(',', $CFG->showuseridentity));
} else {
    $identityfields = array();
}

$__citycountry = "";

// this obviously only works for english.
if (!isset($hiddenfields['city']) && $user->city) {
    $__citycountry .= $user->city;
}
if (!isset($hiddenfields['country']) && $user->country) {
    $__citycountry .= ", " . get_string($user->country, 'countries');
}


$__userpicture = $OUTPUT->user_picture($user, array('size' => 72));
}
?>
    <header id="page-header" class="well clearfix">
        <?php
        if ($__useUserHeader) {
            if(!is_siteadmin()) {
            ?>
            <style type="text/css">
                .block_settings {
                    display: none !important;
                }
            </style>
            <?php
            }
        ?>

        <div class="profilepicture">
        <?php
            echo $__userpicture;
        ?>
        </div>
        <div class="profileheading">
            <h1><?php echo fullname($user); ?></h1>
        <?php
        } else {
            echo $html->heading;
        }


        if($__useUserHeader) {
            ?>
            <small class="user-meta"><?php echo $__citycountry; ?></small>
        </div>

        <?php
            $__shouldprintnavbar = false;

            $__canprintmsglink = isloggedin() && has_capability('moodle/site:sendmessage', $context)
                && !empty($CFG->messaging) && !isguestuser() && !isguestuser($user) && ($USER->id != $user->id);
            $__shouldprintnavbar = $__shouldprintnavbar || $__canprintmsglink;

            $__canprinteditlink = isloggedin() && has_capability('moodle/user:editprofile', $context) && !isguestuser() && !isguestuser($user) && $this->page->pagetype == 'user-profile';

            $__canprinteditlink = false;
            $__shouldprintnavbar = $__shouldprintnavbar || $__canprinteditlink;



            if($__shouldprintnavbar) {
        ?>
        <div id="course-navbar" class="well">
            <ul>
            <?php
            // Print messaging link if allowed.
            if ($__canprintmsglink) {
                ?>
                <li class="course-nav-item course-nav-item-messagebox">
                    <a href="<?php echo $CFG->wwwroot.'/message/index.php?id='.$user->id; ?>">
                        Message
                    </a>
                </li>
                <?php
            }
            ?>
            <?php
            if ($__canprinteditlink) {
                ?>
                <li class="course-nav-item course-nav-item-settings">
                    <a href="<?php echo $CFG->wwwroot.'/user/editadvanced.php?id='.$user->id; ?>">
                        Edit profile
                    </a>
                </li>
                <?php
            }
            ?>
            </ul>
        </div>
        <?php
        }

        }

/*
        ?>
        <pre><?php print_r($OUTPUT->navbar()); ?></pre>
        <?php
*/

        if($this->page->course->id != SITEID) {
        ?>
        <div id="course-navbar" class="well">
            <ul>
                <li class="course-nav-item course-nav-item-participants"><a href="http://192.168.100.39/m/stable_master_nav/user/index.php?id=2">Participants</a></li>
                <li class="course-nav-item course-nav-item-badges"><a href="http://192.168.100.39/m/stable_master_nav/badges/view.php?type=2&amp;id=2">Badges</a></li>
                <li class="course-nav-item course-nav-item-grades"><a href="http://192.168.100.39/m/stable_master_nav/grade/report/index.php?id=2">Grades</a></li>
                <li class="course-nav-item course-nav-item-settings"><a href="http://192.168.100.39/m/stable_master_nav/grade/report/index.php?id=2">Settings</a></li>
            </ul>
        </div>
        <?php
        }
        ?>
        <div id="page-navbar" class="clearfix">
            <nav class="breadcrumb-nav"><?php echo $OUTPUT->navbar(); ?></nav>
            <div class="breadcrumb-button"><?php echo $OUTPUT->page_heading_button(); ?></div>
        </div>
        <div id="course-header">
            <?php echo $OUTPUT->course_header(); ?>
        </div>
    </header>