<?php
/*
+ ----------------------------------------------------------------------------+
|     e107 website system
|
|     �Steve Dunstan 2001-2002
|     http://e107.org
|     jalist@e107.org
|
|     Released under the terms and conditions of the
|     GNU General Public License (http://gnu.org).
|
|     $Source: /cvs_backup/e107_0.8/e107_admin/includes/beginner.php,v $
|     $Revision: 1.3 $
|     $Date: 2008-08-25 10:46:31 $
|     $Author: e107steved $
+----------------------------------------------------------------------------+
*/

if (!defined('e107_INIT')) { exit; }

if($_GET['mode'] == "e_advanced"){
	$pref['adminstyle'] = "classis";
  	save_prefs();
	Header("Location:". e_ADMIN."admin.php");
}

	$buts = "";
	$text = "<div style='text-align:center;vertical-align:middle'><br /><br />
	<table style='".ADMIN_WIDTH.";margin-top:auto;margin-bottom:auto' >";

 	//	$newarray[28] = array(e_ADMIN."plugin.php", ADLAN_98, ADLAN_99, "Z", 2, E_16_PLUGMANAGER, E_32_PLUGMANAGER);

  	$selection = array(22,12,17,25,5,19,7,23,28,26);
 	// $selection = array(21,11,17,24,5,19,7,27,28,25);
	foreach($selection as $id)
	{
		$buts .= render_links($newarray[$id][0],$newarray[$id][1],$newarray[$id][2],$newarray[$id][3],$newarray[$id][6],'beginner');
	}

	$text .= $buts;
	$text .= render_clean();
	$text .= "\n</table><br /></div>";

	$text .= "<div class='smalltext' style='text-align:center'>".ADLAN_144." <a href='".e_SELF."?mode=e_advanced' >".ADLAN_145."</a>&nbsp;&nbsp;</div>";

	if($buts != '')
	{
		$ns->tablerender(ADLAN_47." ".ADMINNAME, $text);
	}






	$text = "<div style='text-align:center'>
	<table style='".ADMIN_WIDTH."'>";


	$text .= getPluginLinks(E_32_PLUGMANAGER, "classis");

	$text .= render_clean();
	$text .= "</table></div>";

	$ns->tablerender(ADLAN_CL_7, $text);

?>
