<?php
/**
 * Copyright (C) 2019-2025 Paladin Business Solutions
 */

ob_start() ;
require_once('includes/ringcentral-php-functions.inc');

show_errors();

page_header();  // set back to 1 when recaptchas are set in the .ENV file

function show_form($message, $label = "", $print_again = false) { ?>

    <form action="" method="post">
        <table class="CustomTable">
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <img src="images/rc-logo.png"/>
                    <h2><?php echo app_name(); ?></h2>
					<?php
					if ($print_again == true) {
//                        echo "<p class='msg_bad'>" . $message . "</strong></font>";
						echo_plain_text($message, "red", "large");
					} else {
//                        echo "<p class='msg_good'>" . $message . "</p>";
						echo_plain_text($message, "#008EC2", "large");
					} ?>
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <label for="daysPast">How many days in the past to do want to look into the audit trail:</label>
                    <input type="number" id="daysPast" name="daysPast" min="1" max="100" step="1" value="10">
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <input type="submit" class="submit_button" value="   List events   " name="list_events">
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <hr>
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
					<?php app_version(); ?>
                </td>
            </tr>
        </table>
    </form>
	<?php
}

/* ============= */
/*  --- MAIN --- */
/* ============= */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	header("Location: list_events.php?daysPast=$_POST[daysPast]");
} else {
	$message = "Please click the button to generate a list of events.";
	show_form($message);
}

ob_end_flush();

page_footer();
