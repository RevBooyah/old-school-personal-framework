<?php

require_once("globals.php");


$PageTitle='Sign In';


$jscript=<<<__ENDJSCRIPT__

<script type="text/javascript">
$(document).ready(function() {
        $("#signinwrap").load("/forms/frmSignIn.php");
});
</script>


__ENDJSCRIPT__;


require_once("header.php");

?>


<article>
<h2>Sign In</h2>

<div id='signinwrap'>loading form...</div>

</article>

<?php
require_once("footer.php");
