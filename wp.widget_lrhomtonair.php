<?php
/*
Plugin Name: LRH/OMT OnAir
Plugin URI: http://lrh.net/wpblog_lrh
Description: Retrieves the OMT/iMediaTouch OnAir xml file and displays the active cut.
Author: Larry Houbre, Jr.
Version: 1.4.0
*/

/* This is some common code used by most LRH WP Widgets */
$fbase='/lrh_helper_basewpwidget.php';
if (file_exists($bfn=WP_PLUGIN_DIR.'/lrhcommon'.$fbase))
	require_once($bfn);
else
	require(WP_PLUGIN_DIR.'/'.dirname(plugin_basename(__FILE__)).$fbase);

define('cwidgetbaseid_lrhomtonair','lrhomtonair');
define('cwidgetname_lrhomtonair','LRH OMT On Air');
define('cwidgetoption_lrhomtonair_defaultprogram','lrhomtonair_dp');
define('cwidgetoption_lrhomtonair_defaultprogram_value',__('Live DJ',cwidgetbaseid_lrhomtonair));
define('cwidgetoption_lrhomtonair_xmlurl','lrhomtonair_xu');
define('cwidgetoption_lrhomtonair_usediv','lrhomtonair_ud');
define('cwidgetoption_lrhomtonair_useajax','lrhomtonair_ua');
define('cwidgetoption_lrhomtonair_useintervalsecs','lrhomtonair_is');
define('cwidgetoption_lrhomtonair_useintervalsecs_value','20');
define('cwidgetoption_lrhomtonair_ajaxload','lrhomtonair_al');
define('cwidgetoption_lrhomtonair_ajaxload_value',__('Loading...',cwidgetbaseid_lrhomtonair));
define('cwidgetoption_lrhomtonair_foot','lrhomtonair_ft');
define('cwidgetoption_lrhomtonair_showalbum','lrhomtonair_shwalb');
define('cwidgetoption_lrhomtonair_cats','lrhomtonair_cats');
define('cwidgetoption_lrhomtonair_prevcount','lrhomtonair_prevcnt');

define('cwidgetajax_lrhomtonair_request','lrhomtonair_ar');
define('cwidgetajax_lrhomtonair_nonce','lrhomtonair_an');


class lrh_widget_omtonair extends LRH_WPWidget {

function __construct()
{
	$n=__(cwidgetname_lrhomtonair,cwidgetbaseid_lrhomtonair);
	$d=__('Retrieves the OMT/iMediaTouch OnAir xml file and displays the active cut.',cwidgetbaseid_lrhomtonair);
	parent::__construct('bi_'.cwidgetbaseid_lrhomtonair,cwidgetbaseid_lrhomtonair,$n,$d);

	//Register option fields
	$this->RegisterOptionField('title'
					,__('Title:',cwidgetbaseid_lrhomtonair)
					,'','text');
	$this->RegisterOptionField(cwidgetoption_lrhomtonair_defaultprogram
					,__('Default Program:',cwidgetbaseid_lrhomtonair)
					,'','text');
	$this->RegisterOptionField(cwidgetoption_lrhomtonair_xmlurl
					,__('Location of OMT XML file: (required)',cwidgetbaseid_lrhomtonair)
					,'','text');
	$this->RegisterOptionField(cwidgetoption_lrhomtonair_useajax
					,__('Use Ajax:',cwidgetbaseid_lrhomtonair)
					,'0','yesnoselect');
	$this->RegisterOptionField(cwidgetoption_lrhomtonair_useintervalsecs
					,__('Ajax update interval, seconds:',cwidgetbaseid_lrhomtonair)
					,cwidgetoption_lrhomtonair_useintervalsecs_value,'text');
	$this->RegisterOptionField(cwidgetoption_lrhomtonair_ajaxload
					,__('"Loading" notice:',cwidgetbaseid_lrhomtonair)
					,'','text');
	$this->RegisterOptionField(cwidgetoption_lrhomtonair_usediv
					,__('Output as non-list:',cwidgetbaseid_lrhomtonair)
					,'0','yesnoselect');
	$this->RegisterOptionField(cwidgetoption_lrhomtonair_foot
					,__('Footer text:',cwidgetbaseid_lrhomtonair)
					,'','text');
	$this->RegisterOptionField(cwidgetoption_lrhomtonair_showalbum
					,__('Show album:',cwidgetbaseid_lrhomtonair)
					,'1','yesnoselect');
	$this->RegisterOptionField(cwidgetoption_lrhomtonair_cats
					,__('Categorys:',cwidgetbaseid_lrhomtonair)
					,'','text');
	$this->RegisterOptionField(cwidgetoption_lrhomtonair_prevcount
					,__('Show recently played:',cwidgetbaseid_lrhomtonair)
					,'0','text');

	$this->RegisterFieldHelp(cwidgetoption_lrhomtonair_defaultprogram
			,__('Used when there is no cut playing, or no data available. Default is ',cwidgetbaseid_lrhomtonair)
				.cwidgetoption_lrhomtonair_defaultprogram_value);
	$this->RegisterFieldHelp(cwidgetoption_lrhomtonair_xmlurl
			,__('Use file:://path if the file resides on the host system.',cwidgetbaseid_lrhomtonair));
	$this->RegisterFieldHelp(cwidgetoption_lrhomtonair_useajax
			,__('Keeps status updated, every interval seconds.',cwidgetbaseid_lrhomtonair));
	$this->RegisterFieldHelp(cwidgetoption_lrhomtonair_useintervalsecs
			,__('When using ajax, update this often, in seconds. Default is ',cwidgetbaseid_lrhomtonair)
				.cwidgetoption_lrhomtonair_useintervalsecs_value);
	$this->RegisterFieldHelp(cwidgetoption_lrhomtonair_ajaxload
			,__('Text shown while waiting for initial ajax response.',cwidgetbaseid_lrhomtonair));
	$this->RegisterFieldHelp(cwidgetoption_lrhomtonair_usediv
			,__('Do not use an unnumbered list in display text, use div instead.',cwidgetbaseid_lrhomtonair));
	$this->RegisterFieldHelp(cwidgetoption_lrhomtonair_cats
			,__('[optional] List categorys to show, comma separated.'
				.' (Or, start list with a minus "-" to NOT show these categorys.)'
			,cwidgetbaseid_lrhomtonair)
			);
	$this->RegisterFieldHelp(cwidgetoption_lrhomtonair_prevcount
			,__('Number of recently played cuts to show, normally none. '
				.' (Set to -1 to show one if nothing playing can be displayed)'
			,cwidgetbaseid_lrhomtonair)
			);

	// Make sure jQuery is available
	// already always included in WP 2.x ??
	wp_enqueue_script('jquery');

	// add in ajax handler in case we need it
	add_action('wp_ajax_'.cwidgetajax_lrhomtonair_request,array($this,'ajaxhandler'));
	add_action('wp_ajax_nopriv_'.cwidgetajax_lrhomtonair_request,array($this,'ajaxhandler'));
}

function widget( $args, $instance ) {
	// If no URL, don't bother
	$URL=$instance[cwidgetoption_lrhomtonair_xmlurl];
	if (empty($URL)) return;

	extract($args);
	$title = apply_filters('widget_title'
		,empty($instance['title']) ? $this->name : $instance['title']
		,$instance
		,$this->id_base
		);

		$useajax=('1'==$instance[cwidgetoption_lrhomtonair_useajax]);
		$footer=$instance[cwidgetoption_lrhomtonair_foot];
		$usediv=('1'==$instance[cwidgetoption_lrhomtonair_usediv]);

		if ($useajax) {
			$jurl=admin_url('admin-ajax.php');
			$nonce=wp_create_nonce(cwidgetbaseid_lrhomtonair);
			$show=(empty($instance[cwidgetoption_lrhomtonair_ajaxload]))?cwidgetoption_lrhomtonair_ajaxload_value:$instance[cwidgetoption_lrhomtonair_ajaxload];
			$interval=$instance[cwidgetoption_lrhomtonair_useintervalsecs];
			$interval=(0==$interval)?cwidgetoption_lrhomtonair_useintervalsecs_value:$interval;
			$interval=intval(1000*$interval); //millisecs
			$script=''
			. '<script type="text/javascript">'

			. 'function lrhomtonairajax(){'."\r\n"
			. ' jQuery.ajax({'."\r\n"
			. '  type:"POST",'."\r\n"
			. '  url:"'.$jurl.'",'."\r\n"
			. '  data:{action:"'.cwidgetajax_lrhomtonair_request.'"'."\r\n"
			. '   ,'.cwidgetajax_lrhomtonair_nonce.':"'.$nonce.'"'."\r\n"
			. '   },'."\r\n"
			. '  success:function(response){'."\r\n"
			. '   jQuery("#'.$widget_id.'_onair").html(response);'."\r\n"
			. '   setTimeout("lrhomtonairajax(jQuery)",'.$interval.');'."\r\n"
			. '   },'."\r\n"
			. '  error:function(x,t){'."\r\n"
			. '   jQuery("#'.$widget_id.'_onair").html(t);'."\r\n"
			. '   setTimeout("lrhomtonairajax(jQuery)",2000);'."\r\n"
			. '   }'."\r\n"
			. ' });'."\r\n"

			. '};'."\r\n"

			. 'jQuery(document).ready(function(){'."\r\n"
//				. 'jQuery("#'.$widget_id.'_onair").html("'.$show.'");'."\r\n"
				. 'lrhomtonairajax();'."\r\n"
				. '});'."\r\n"

			. '</script>';
		} else {
			$show=$this->GetDisplayText();
			$script='';
		}

	echo '<!-- '.cwidgetbaseid_lrhomtonair.' '.$this->GetPluginVersion(__FILE__).' -->';
	echo $before_widget;
	if ( $title )
		echo $before_title.$title.$after_title;
	if ($usediv) echo '<div';
	else         echo '<ul';
	echo " id='{$widget_id}_onair' class='".cwidgetbaseid_lrhomtonair."_content'>$show</";
	if ($usediv) echo 'div>';
	else         echo 'ul>';
	if ($footer) {
		echo (($usediv)?"<div":"<p")
			." class='".cwidgetbaseid_lrhomtonair."_footer'>"
			.$footer.(($usediv)?'</div>':'</p>');
	}
	echo $after_widget;
	print $script;
}

function FormBeforeFields($aInstance)
{
	parent::FormBeforeFields($aInstance);
	echo $this->form_info
		(__('Version',cwidgetbaseid_lrhomtonair)
			.': '.$this->GetPluginVersion(__FILE__)
		);
}

function ajaxhandler()
{
	check_ajax_referer(cwidgetbaseid_lrhomtonair,cwidgetajax_lrhomtonair_nonce);
	echo $this->GetDisplayText();
	die();
}

function GetOnAirData($aUrl,$aCats)
{
//echo "|$aUrl|<br>|$aCats|<br>";
	if ('file://'==substr($aUrl,0,7)) {
		$aUrl=substr($aUrl,7);
		if (file_exists($aUrl)) {
			$content=file_get_contents($aUrl);
			if ($content=='') return $this->DataError('File:no content'); //nothing
		} else {
			return $this->DataError('File:not found'); //nothing
		}
	} else {
		//must be an http:// location
		if ('http://'!=substr($aUrl,0,7)) $aUrl='http://'.$aUrl;
		$content=wp_remote_retrieve_body(wp_remote_get($aUrl));
		if ($content=='') return $this->DataError('Http:no content'); //nothing
	}
	//convert xml to data array
	try {
		@$x=new SimpleXMLElement($content);
	} catch (Exception $e) {
		return $this->DataError('XML error:'.$e->getMessage(),$content); //trouble
	}

	$docats=(''!=$aCats);
	if ($docats) {
		$notcats=('-'==substr($aCats,0,1));
		$cats=($notcats)?substr($aCats,1):$aCats;
		$cats=explode(',',$cats);
		$docats=0<count($cats);
	}
//echo "<pre>|$aCats|$docats|$notcat|".print_r($cats,true)."|<br></pre>";

	// We want to extract and rearrange the list to allow easier
	// access to the currently playing, and the previously played
	// in most recent order
	$res=array('prev'=>array(),'now'=>array(),'next'=>array());
	$fe='UTF-8'; $te='ISO-8859-1//TRANSLIT';
	foreach($x as $e){
		$ok=true;
		//we might be filtering by category
		$cat=$e->attributes()->Category.'';
		if ($docats) {
			$ok=in_array($cat,$cats);
			if ($notcats) $ok=!$ok;
//echo "<pre>|$ok|$cat|<br></pre>";
		}
		if ($ok) {
			//figure out which list it should go in...
			$t=$e->attributes()->Type.'';
			switch($t) {
				case "Playing": $l='now'; break;
				case "Not Played": $l='next'; break;
				default: $l='prev'; //Played or unknonwn
			}//switch
			//build item
			$item=array
				('Title'     => iconv($fe,$te,$e->attributes()->Title.'')
				,'Artist'    => iconv($fe,$te,$e->attributes()->Artist.'')
				,'Album'     => iconv($fe,$te,$e->attributes()->Album.'')
				,'Category'  => $cat
//				,'Type'      => $t
//				,'Event'     => $e->attributes()->Event+0 //convert to number
//				,'Id'        => $e->attributes()->Id.''
//				,'Duration'  =>$e->attributes()->Duration.''
//				,'Starttime' =>$e->attributes()->StartTime.''
				);
			//add to list
			$res[$l][]=$item;
		}
	}
	//sort prev to most recent first
	krsort($res['prev']);
	return $res;
}

function DataError($aMsg,$aMsg2='')
{
	if (WP_DEBUG) {
		//for debugging
		return array
			('prev'=>array()
			,'now'=>array(array('Title'=>$aMsg,'Artist'=>$aMsg2))
			,'next'=>array()
			);
	}
	return null;
}

function FormatItem(array $aItem,$aShowAlbum,$aUseDiv)
{
	$show='';
	if (isset($aItem['Title'])) $show.="<div class='omtonair_title'>{$aItem['Title']}</div>";
	if (isset($aItem['Artist'])) $show.="<div class='omtonair_artist'>{$aItem['Artist']}</div>";
	if ($aShowAlbum && isset($aItem['Album'])) $show.="<div class='omtonair_album'>{$aItem['Album']}</div>";
	$show=($aUseDiv)?"<div>$show</div>":"<li>$show</li>";
	return $show;
}

function GetDisplayText()
{
	$a=$this->get_settings();
	$s=$a[$this->number];
	$aUrl              =$s[cwidgetoption_lrhomtonair_xmlurl];
	$aDefaultProgram   =$s[cwidgetoption_lrhwumdonair_defaultprogram];
	$aShowAlbum        =('1'==$s[cwidgetoption_lrhomtonair_showalbum]);
	$aCats             =trim($s[cwidgetoption_lrhomtonair_cats]);
	$aPrevCnt          =intval($s[cwidgetoption_lrhomtonair_prevcount]);
	$aUseDiv           =('1'==$s[cwidgetoption_lrhomtonair_usediv]);

	$ONAIR=$this->GetOnAirData($aUrl,$aCats);
	$pc=count($ONAIR['prev']);
//echo "<pre>|$aUrl|$aDefaultProgram|$aShowAlbum|$aPrevCnt|$aUseDiv|$aCats|$pc|\r\n</pre>";
//echo "<pre>ONAIR DATA:\r\n"; print_r($ONAIR); echo "</pre>";
	if (isset($ONAIR['now'][0])) {
		$show=$this->FormatItem($ONAIR['now'][0],$aShowAlbum,$aUseDiv);
	} else {
		if ( (0==$aPrevCnt) || (0==$pc) ) {
			$show=$aDefaultProgram;
			if (empty($show)) $show=cwidgetoption_lrhomtonair_defaultprogram_value;
			$show=$this->FormatItem(array('Title'=>$show),$aShowAlbum,$aUseDiv);
		} else if (-1==$pc) {
			reset($ONAIR['prev']);
			$show=$this->FormatItem(current($ONAIR['prev']),$aShowAlbum,$aUseDiv);
		}
	}
	if ( (0<$aPrevCnt) && (0<$pc) ) {
		$cnt=$aPrevCnt;
		foreach($ONAIR['prev'] as $item) {
			$show.=$this->FormatItem($item,$aShowAlbum,$aUseDiv);
			if (0==--$cnt) break;
		}
	}
	if (WP_DEBUG) $show.='<br>'.date('Y-m-d H:i:s');
	return $show;
}

}//class


add_action('widgets_init'
	,create_function('','return register_widget("lrh_widget_omtonair");')
	);

?>
