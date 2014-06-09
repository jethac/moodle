<?php
defined('MOODLE_INTERNAL') || die();
?>
<header role="banner" class="navbar navbar-fixed-top moodle-has-zindex">
    <nav role="navigation" class="navbar-inner">
        <div class="container-fluid">
            <a class="brand" href="<?php echo $CFG->wwwroot;?>"><?php echo $SITE->shortname; ?></a>


            <?php echo $OUTPUT->navbar_dropdowns(); ?>

        </div>
    </nav>
</header>