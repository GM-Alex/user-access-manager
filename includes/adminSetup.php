<div class=wrap>
<form method="post" action="<?php
echo $_SERVER["REQUEST_URI"]; ?>">
	<input type="hidden" value="reset_uam" name="action" />
    <h2><?php
echo TXT_SETUP; ?></h2>
    <table class="form-table">
    	<tbody>
    		<tr valign="top">
    			<th scope="row"><?php
echo TXT_RESET_UAM; ?></th>
    			<td><label for="uam_reset_yes"> <input type="radio"
    				id="uam_reset_yes" class="uam_reset_yes" name="uam_reset"
    				value="TRUE" /> <?php
echo TXT_YES; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp;
    			<label for="uam_reset_no"> <input type="radio" id="uam_reset_no"
    				class="uam_reset_no" name="uam_reset" value="FALSE"
    				checked="checked" /> <?php
echo TXT_NO; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp;<input
    				type="submit" class="button" name="uam_reset_submit"
    				value="<?php
echo TXT_RESET; ?>" /> <br />
    			<p style="color: red; font-size: 12px; font-weight: bold;"><?php
echo TXT_RESET_UAM_DESC; ?></p>
    			</td>
    		</tr>
    	</tbody>
    </table>
</form>
</div>