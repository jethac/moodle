    <header id="page-header" class="well clearfix">
        <?php
        echo $html->heading;

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