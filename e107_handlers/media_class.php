<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2010 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * Administration - Media Management Class
 *
 * $Source: /cvs_backup/e107_0.8/e107_handlers/plugin_class.php,v $
 * $Revision: 11315 $
 * $Date: 2010-02-10 10:18:01 -0800 (Wed, 10 Feb 2010) $
 * $Author: secretr $
 *
*/


if (!defined('e107_INIT')) { exit; }

class e_media
{
	/**
	 * Import files from specified path into media database. 
	 * @param string $cat Category nickname
	 * @param string $epath path to file.
	 * @param string $fmask [optional] filetypes eg. .jpg|.gif
	 * @return 
	 */
	public function import($cat,$epath,$fmask='')
	{
		if(!vartrue($cat)){ return;}
	
		if(!is_readable($epath))
		{
			return;
		}
	
		$fl = e107::getFile();
		$tp = e107::getParser();
		$sql = e107::getDb();
		$mes = e107::getMessage();
	
		$fl->setFileInfo('all');
		$img_array = $fl->get_files($epath,$fmask,'',2);
	
		if(!count($img_array)){ return;}
		
		//print_a($img_array);
		//return;
	
		foreach($img_array as $f)
		{
			$fullpath = $tp->createConstants($f['path'].$f['fname'],1);
	
			$insert = array(
				'media_caption'		=> $f['fname'],
				'media_description'	=> '',
				'media_category'	=> $cat,
				'media_datestamp'	=> $f['modified'],
				'media_url'	=> $fullpath,
				'media_userclass'	=> 0,
				'media_name'	=> $f['fname'],
				'media_author'	=> USERID,
				'media_size'	=> $f['fsize'],
				'media_dimensions'	=> $f['img-width']." x ".$f['img-height'],
				'media_usedby'	=> '',
				'media_tags'	=> '',
				'media_type'	=> $f['mime']
			);
	
			if(!$sql->db_Select('core_media','media_url',"media_url = '".$fullpath."' LIMIT 1"))
			{
				if($sql->db_Insert("core_media",$insert))
				{
					$mes->add("Importing Media: ".$f['fname'], E_MESSAGE_SUCCESS);
				}
				else
				{
					$mes->add("Importing Media: ".$f['fname'], E_MESSAGE_ERROR);	
				}
			}
		}
	}	
	
	
	/**
	 * Import icons into media-manager from specified path.
	 * @param string $path
	 * @return 
	 */
	public function importIcons($path)
	{
		$iconsrch = array(16,32,48,64);

		foreach($iconsrch as $size)
		{
			$types = "_".$size.".png|_".$size.".PNG";
			
			$this->import('_icon_'.$size, $path, $types);
		}
	}
	
	
	
	
	/**
	 * Remove Media from media table
	 * @param string $cat [optional] remove a full category of media
	 * @return 
	 */
	function removeCat($cat)
	{
		$tp = e107::getParser();
		$sql = e107::getDb();
		$mes = e107::getMessage();
						
		if(vartrue($cat))
		{
			$status = ($sql->db_Delete('core_media',"media_cat = '".$cat."'")) ? TRUE : FALSE;
			$mes->add("Removing Media in Category: ".$cat, E_MESSAGE_DEBUG);
			return $status;	
		}	
	}



	/**
	 * Remove Media from media table
	 * @param string $epath remove media in the specified path.
	 * @param string $type [optional] image|icon
	 * @return 
	 */
	function removePath($epath, $type='image')
	{
		$tp = e107::getParser();
		$sql = e107::getDb();
		$mes = e107::getMessage();
		
		$qry = ($type == 'icon') ? " AND media_category REGEXP '_icon_16|_icon_32|_icon_48|_icon_64' " : " AND NOT media_category REGEXP '_icon_16|_icon_32|_icon_48|_icon_64' ";
								
		if(vartrue($epath))
		{
			$path = $tp->createConstants($epath, 'rel');
			$status = ($sql->db_Delete('core_media',"media_url LIKE '".$path."%'".$qry)) ? TRUE : FALSE;
			$message = ($type == 'image') ?  "Removing Media with path: ".$path : "Removing Icons with path: ".$path;
			$mes->add($message, E_MESSAGE_DEBUG);
			return $status;	
		}			
	}
	
	
	
	/**
	 * Return a list if icons in the specified path
	 * @param string $epath
	 * @return array
	 */
	function listIcons($epath)
	{
		if(!$epath) return;
		
		$ret = array();
		$sql = e107::getDb();
		$tp = e107::getParser();
		
		$path = $tp->createConstants($epath, 'rel');
	
		$status = ($sql->db_Select_gen("SELECT * FROM `#core_media` WHERE `media_url` LIKE '".$path."%' AND media_category REGEXP '_icon_16|_icon_32|_icon_48|_icon_64' ")) ? TRUE : FALSE;		
		while ($row = $sql->db_Fetch())
		{
			$ret[] = $row['media_url'];
		}
		
		return $ret;	
	}


	
}



?>