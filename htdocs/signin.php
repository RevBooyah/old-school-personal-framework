<?php

require_once("globals.php");


$PageTitle='Sign In';

require_once("header.php");

?>

<script>

$(document).ready(function() {
        $("#signinform").load("/forms/frmSignIn.php");
});



</script>

<article>
<h2>Sign In</h2>

<div id='signinwrap'>loading form...</div>

</article>

<?php
require_once("footer.php");
