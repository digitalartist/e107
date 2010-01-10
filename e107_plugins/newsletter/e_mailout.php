<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2009 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * Administration - Site Maintenance
 *
 * $Source: /cvs_backup/e107_0.8/e107_plugins/newsletter/e_mailout.php,v $
 * $Revision: 1.3 $
 * $Date: 2010-01-10 03:56:28 $
 * $Author: e107coders $
 *
*/


if (!defined('e107_INIT')) { exit; }


include_lan(e_PLUGIN.'/newsletter/languages/English_admin_newsletter.php');

/* 
Class for event calendar mailout function

Allows admins to send mail to those subscribed to calendar events
*/
// These variables determine the circumstances under which this class is loaded (only used during loading, and may be overwritten later)
	$mailerIncludeWithDefault = TRUE;			// Mandatory - if false, show only when mailout for this specific plugin is enabled 
	$mailerExcludeDefault = FALSE;				// Mandatory - if TRUE, when this plugin's mailout is active, the default (core) isn't loaded

class newsletter_mailout
{
	protected $mailCount = 0;
	protected $mailRead = 0;
	public $mailerSource = 'newsletter';			// Plugin name (core mailer is special case) Must be directory for this file
	public $mailerName = NLLAN_48;					// Text to identify the source of selector (displayed on left of admin page)
	public $mailerEnabled = TRUE;					// Mandatory - set to FALSE to disable this plugin (e.g. due to permissions restrictions)
	private $selectorActive = FALSE;				// Set TRUE if we've got a valid selector to start returning entries
	private	$targets = array();						// Used to store potential recipients
	private $ourDB;


	// Constructor
	public function __construct()
	{
		// BAD FOR PERFORMANCE
		//$this->e107 = e107::getInstance();
		//$this->adminHandler = e107::getRegistry('_mailout_admin');		// Get the mailer admin object - we want to use some of its functions
	}
  
  
	/**
	 * Return data representing the user's selection criteria as entered in the $_POST array.
	 * 
	 * This is stored in the DB with a saved email. (Just return an empty string or array if this is undesirable)
	 * The returned value is passed back to selectInit() and showSelect when needed.
	 *
	 * @return string Selection data - comma-separated list of category IDs
	 */
	public function returnSelectors()
	{
		$res = array();
		if (is_array($_POST['nl_category_sel']))
		{
			foreach ($_POST['nl_category_sel'] as $k => $v)
			{
				$res[] = intval($v);
			}
		}
		return implode(',',$res);
	}


	/**
	 * Called to initialise data selection routine.
	 * Needs to save any queries or other information into internal variables, do initial DB queries as appropriate.
	 * Could in principle read all addresses and buffer them for later routines, if this is more convenient
	 *
	 * @param string $selectVals - array of selection criteria as returned by returnSelectors()
	 *
	 * @return integer Return number of records available (or 1 if unknown) on success, FALSE on failure
	 */
	public function selectInit($selectVals = FALSE)
	{
		$sql = e107::getDb();
		
		
		if (($selectVals === FALSE) || ($selectVals == ''))
		{
			return 0;				// No valid selector - so no valid records
		}

		$qry = "SELECT newsletter_id,newsletter_subscribers FROM `#newsletter` WHERE (`newsletter_parent`=0) AND (`newsletter_id` IN ({$selectVals}))";
//		echo "Selector {$selectVals} query: ".$qry.'<br />';
		if (!($sql->db_Select_gen($qry))) return FALSE;
		$this->selectorActive = TRUE;
		$this->mail_count = 1;			// We have no idea of how many subscribers without reading all relevant DB records
		$this->mail_read = 0;
		$this->ourDB = new db();		// We'll need our own database object
		return $this->mail_count;
	}



	/**
	 * Return an email address to add to the recipients list. Return FALSE if no more addresses to add 
	 *
	 * @return array|boolean FALSE if no more addresses available; else an array:
	 *	'mail_recipient_id' - non-zero if a registered user, zero if a non-registered user. (Always non-zero from this class)
	 *	'mail_recipient_name' - user name
	 *	'mail_recipient_email' - email address to use
	 *	'mail_target_info' - array of info which might be substituted into email, usually using the codes defined by the editor. 
	 * 		Array key is the code within '|...|', value is the string for substitution
	 */
	public function selectAdd()
	{
		
		$sql = e107::getDb();
		
		
		if (!$this->selectorActive) return FALSE;
		
		while ($this->selectorActive)
		{
			if (count($this->targets) == 0)
			{	// Read in and process another newletter mailing list
				if (!($row = $sql->db_Fetch(MYSQL_ASSOC)))
				{
					$this->selectorActive = FALSE;
					return FALSE;		// Run out of DB records
				}
				$this->targets = explode(chr(1), $row['newsletter_subscribers']);
				unset($row);
			}
			foreach ($this->targets as $k => $v)
			{
				if ($uid = intval(trim($v)))
				{	// Got a user ID here - look them up and add their data
					if ($this->ourDB->db_Select('user', 'user_name,user_email,user_lastvisit', '`user_id`='.$uid))
					{
						$row = $this->ourDB->db_Fetch();
						$ret = array('mail_recipient_id' => $uid,
									 'mail_recipient_name' => $row['user_name'],		// Should this use realname?
									 'mail_recipient_email' => $row['user_email'],
									 'mail_target_info' => array(
										'USERID' => $uid,
										'DISPLAYNAME' => $row['user_name'],
										'SIGNUP_LINK' => $row['user_sess'],
										'USERNAME' => $row['user_loginname'],
										'USERLASTVISIT' => $row['user_lastvisit']
										)
									 );
						$this->mail_read++;
						unset($this->targets[$k]);
						return $ret;
					}
				}
				unset($this->targets[$k]);
			}
		}
	}


	/**
	 * Called once all email addresses read, to do any housekeeping needed
	 * @return none
	 */
	public function select_close()
	{	
		// Nothing to do here
	}
  

	/**
	 * Called to show current selection criteria, and optionally allow edit
	 * 
	 * @param boolean $allow_edit is TRUE to allow user to change the selection; FALSE to just display current settings
	 * @param string $selectVals is the current selection information - in the same format as returned by returnSelectors()
	 *
	 * @return array Returns array which is displayed in a table cell
	 */
	public function showSelect($allow_edit = FALSE, $selectVals = FALSE)
	{
		$sql = e107::getDb();
		$frm = e107::getForm();
				
		$selects = array_flip(explode(',', $selectVals));

		if ($sql->db_Select('newsletter', 'newsletter_id, newsletter_title', '`newsletter_parent`=0'))
		{
			$c=0;
			while ($row = $sql->db_Fetch(MYSQL_ASSOC))
			{
				$checked = (isset($selects[$row['newsletter_id']])) ? " checked='checked'" : '';
				
				if ($allow_edit)
				{
					$var[$c]['caption'] = $row['newsletter_title'];
					$var[$c]['html'] = $frm->checkbox('nl_category_sel[]',$row['newsletter_id'] ,$checked);
				}
				elseif($checked)
				{
					$var[$c]['caption'] = $row['newsletter_title'];
					$var[$c]['html'] = NLLAN_49;
				}
				$c++;
			}
		}
		else
		{
			$var[$c]['caption'] = NLLAN_50;
			$var[$c]['html'] = '';
		}
		
		return $var; 
	}
}



?>