<?php

global $NAVBAR, $OUTPUT, $USER;
if($NAVBAR) {
    $NAVBAR->enqueue_navbar_button(
        'user',
        (isloggedin())? $OUTPUT->user_picture($USER, array('link' => false)) : false,
        true
    );
}


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
            <?php
            global $NAVBAR;
            if($NAVBAR)
                $NAVBAR->render_navbar_items();

            //echo $OUTPUT->login_info() ?>
        </div>
    </nav>
</header>
<?php

?>