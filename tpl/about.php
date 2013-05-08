<?php
/**
 * about.php
 * 
 * Shows the about page at the admin panel.
 * 
 * PHP versions 5
 * 
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@googlemail.com>
 * @copyright 2008-2013 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

?>
<div class="wrap">
	<h2><?php echo TXT_UAM_ABOUT; ?></h2>
    <div id="poststuff">
        <div class="postbox">
        	<h3 class="hndle"><?php echo TXT_UAM_HOW_TO_SUPPORT; ?></h3>
        	<div class="inside">
        		<p><?php echo TXT_UAM_SEND_REPORTS; ?></p>
        		<p><?php echo TXT_UAM_MAKE_TRANSLATION; ?></p>
    			<p>
    				<?php echo TXT_UAM_DONATE; ?><br/>
    				<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=1947052">
    					<img style="margin:4px 0;" alt="Make payments with PayPal - it's fast, free and secure!" name="submit" src="https://www.paypal.com/en_US/i/btn/x-click-but21.gif" />
    				</a>
    			</p>
    			<p><?php echo TXT_UAM_PLACE_LINK; ?></p>
        	</div>
        </div>
        <div class="postbox">
        	<h3 class="hndle"><?php echo TXT_UAM_THANKS; ?></h3>
        	<div class="inside">
        		<p>
        		    <strong><?php echo TXT_UAM_SPECIAL_THANKS; ?></strong><br/>
        		    <br/>
        		    <?php echo TXT_UAM_THANKS_TO; ?> Luke Crouch, Juan Rodriguez, mkosel, Jan, GeorgWP, Ron Harding, Zina, Erik Franz&eacute;n, Ivan Marevic, J&uuml;rgen Wiesenbauer, Patric Schwarz, Mark LeRoy, Huska, macbidule, Helmut, -sCo-, Hadi Mostafapour, Diego Valobra, PoleeK, Konsult, Mesut Soylu, ranwaldo, Robert Egger, akiko.pusu, r3d pill, michel.weimerskirch, arjenbreur, jpr105 <?php echo TXT_UAM_THANKS_OTHERS; ?>.
        		</p>
        	</div>
        </div>
    </div>
</div>