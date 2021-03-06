<?php
/*
* Copyright (c) e107 Inc e107.org, Licensed under GNU GPL (http://www.gnu.org/licenses/gpl.txt)
* $Id: e_shortcode.php 12438 2011-12-05 15:12:56Z secretr $
*
* Featurebox shortcode batch class - shortcodes available site-wide. ie. equivalent to multiple .sc files.
*/
if (!defined('e107_INIT')) { exit; }

// Override support
if(file_exists(e_PLUGIN.'gallery/custom_header.php'))
{
	include(e_PLUGIN.'gallery/custom_header.php');
	return;
}

e107::js('gallery', 'jslib/lightbox/js/lightbox.js','jquery');
e107::css('gallery', 'jslib/lightbox/css/lightbox.css','jquery');

e107::js('gallery', 'jslib/jquery.cycle.all.js','jquery');
e107::css('gallery', 'gallery_style.css');

e107::css('inline', "
/* Gallery CSS */
a.lb-close			{ width:27px; height:27px; background:url(".SITEURLBASE.e_PLUGIN_ABS."gallery/images/close.png) no-repeat 0 0; }
.lb-loader			{ background:url(".SITEURLBASE.e_PLUGIN_ABS."gallery/images/loading.gif) no-repeat 50% 49%; }

",'jquery');


$gp = e107::getPlugPref('gallery');

e107::js('inline',"

$(document).ready(function() 
{
	
	$('#gallery-slideshow-content').cycle({
		fx: 		'".varset($gp['slideshow_effect'],'scrollHorz')."',
		next:		'.gal-next',
		prev: 		'.gal-prev',
		speed:		".varset($gp['slideshow_duration'],1000).",  // speed of the transition (any valid fx speed value) 
    	timeout:	".varset($gp['slideshow_freq'],4000).",
		slideExpr:	'.slide', 
		pause: 		1, // pause on hover - TODO pref
		
		activePagerClass: '.gallery-slide-jumper-selected',//,
		before: function(currSlideElement, nextSlideElement, options, forwardFlag)
		{
			var nx = $(nextSlideElement).attr('id').split('item-');
			var th = $(currSlideElement).attr('id').split('item-');
			$('#gallery-jumper-'+th[1]).removeClass('gallery-slide-jumper-selected');
			$('#gallery-jumper-'+nx[1]).addClass('gallery-slide-jumper-selected');						
		}
	});
	
	
	
	$('.gallery-slide-jumper').click(function() { 
		var nid = $(this).attr('id');
		var id = nid.split('-jumper-');
	
		var go = parseInt(id[1]) - 1;
    	$('#gallery-slideshow-content').cycle(go); 
    	return false; 
	}); 
	
	$('#img.lb-close').on('live', function(e) {
		$(this).attr('src','".e_PLUGIN."gallery/jslib/lightbox/images/close.png');
	}); 



});
");

	
unset($gp);


?>