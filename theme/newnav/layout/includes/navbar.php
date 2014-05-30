<?php

?>
<header role="banner" class="navbar navbar-fixed-top<?php echo $html->navbarclass ?> moodle-has-zindex">
    <nav role="navigation" class="navbar-inner">
        <div class="container-fluid">
        <?php
        	if ($html->hascustomlogo)
        	{
        		echo $html->customlogolink;
		?>
		<?php
        	} else{
        ?>
            <a class="brand" href="<?php echo $CFG->wwwroot;?>"><?php echo $SITE->shortname; ?></a>

        <?php
        	}
        ?>
            <a class="btn btn-navbar" data-toggle="workaround-collapse" data-target=".nav-collapse-custom">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </a>
            <a class="btn btn-navbar-user btn-navbar" data-toggle="workaround-collapse" data-target=".nav-collapse-login">
                <?php echo $this->user_picture($USER, array('link' => false));?>
            </a>
            <div class="nav-collapse nav-collapse-custom collapse">
                <?php echo $OUTPUT->custom_menu(); ?>
            </div>
            <div class="nav-collapse nav-collapse-login collapse">
                <ul class="nav pull-right">
                    <li class="navbar-text"><?php echo $OUTPUT->login_info() ?></li>
                </ul>
            </div>
        </div>
    </nav>
</header>
<?php

?>