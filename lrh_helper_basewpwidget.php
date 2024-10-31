<?php
// LRH Widget Class for WordPress
// Expands the WP_Widget class to handle some common tasks
// Version:1.0.6

if (!class_exists('LRH_WPWidget')) {

class LRH_WPWidget extends WP_Widget {

// Constructor
// Pass in the ClassName, Widget Name and Description

public function __construct($aRootID,$aClassName,$aWidgetName,$aWidgetDesc)
{
	$widget_ops=array
		('classname'   => $aClassName
		,'description' => $aWidgetDesc
		);
	$control_ops=array();

	$widget_ops=$this->GetWidgetOps($widget_ops);
	$control_ops=$this->GetControlOps($control_ops);

	// Call WP_Widget constructor
	parent::__construct($aRootID,$aWidgetName,$widget_ops,$control_ops);
}

public function GetWidgetOps(array $aOps)
{
	return $aOps;
}

public function GetControlOps(array $aOps)
{
	return $aOps;
}

// Assuming the plugin is in it's own file
// This will attempt to get the version
// You must pass the '__FILE__' from your plugin source
public function GetPluginVersion($aFile)
{
	if ($aFile=='') return '0.ERROR';
	if (!function_exists('get_plugin_data')) {
		if (file_exists(ABSPATH.'wp-admin/includes/plugin.php')) require_once(ABSPATH . 'wp-admin/includes/plugin.php'); //2.3+
		else return '1.ERROR';
	}
	$data=get_plugin_data($aFile);
	return $data['Version'];
}

public function GetPluginBaseVersion()
{
	return $this->GetPluginVersion(__FILE__);
}


// Add a managed option field
// Name is the name and key of the option field
// Label is the 'label' prompt
// Default is the default value
// Type is the form input type
// Extra is any additional information need to render the field
//  Type=textarea; Extra['rows']=8   number of rows

private $lw_fields=array();
public function RegisterOptionField($aName,$aLabel,$aDefault,$aType,array $aExtra=array())
{
	switch ($aType) {
		case 'yesnoselect':
			if (empty($aExtra['select']))
				$aExtra['select']=array('0'=>'No','1'=>'Yes');
			break;
		case 'textarea':
			if (empty($aExtra['rows']))
				$aExtra['rows']=6;
			break;
	}//sw

	$this->lw_fields[$aName]=array
		('n'=>$aName
		,'l'=>$aLabel
		,'d'=>$aDefault
		,'t'=>$aType
		,'v'=>$aDefault
		,'x'=>$aExtra
		,'h'=>''
		);
}

public function RegisterFieldHelp($aName,$aHelp)
{
	$this->lw_fields[$aName]['h']=$aHelp;
}

// Returns the options argument array for use in form()
public function GetDefaultOptions()
{
	$r=array();
	foreach($this->lw_fields as $k=>$f) {
		$r[$k]=$f['d'];
	}
	return $r;
}

public function AllowEmptyTitle()
{
	return false; //override as needed
}

// Given an instance arg from form()
// Merges, and loads current values into field list
// Returns the merged array in case form() needs it for further processing
public function FormLoadOptions(array $aInstance)
{
	$def=$this->GetDefaultOptions();
	$i=wp_parse_args($aInstance,$def);
	foreach($this->lw_fields as $k=>$f) {
		switch ($f['t']) {
			case 'textarea':
				$v=esc_textarea($i[$k]);
				break;
			default:
				$v=esc_attr($i[$k]);
		}
		$this->lw_fields[$k]['v']=$v;
	}
	return $i;
}

public function form_info($aInfo)
{
	$r='<p><small>'.$aInfo.'</small></p>'."\r\n";
	return $r;
}

public function form_help($aFieldName)
{
	$v=$this->lw_fields[$aFieldName]['h'];
	if ($v=='') return '';
	else        return '<br><small>'.$v.'</small>';
}

public function form_text($aFieldName)
{
	$fid=$this->get_field_id($aFieldName);
	$fnm=$this->get_field_name($aFieldName);
	$f=$this->lw_fields[$aFieldName];
	$v=$f['v'];

	$r='<p><label for="'.$fid.'">'.$f['l'].'</label>'
		.'<input class="widefat" id="'.$fid.'" name="'.$fnm.'"'
		.'type="text" value="'.$v.'"'
		.' />'
		.$this->form_help($aFieldName)
		.'</p>'
		."\r\n"
		;
	return $r;
}

public function form_textarea($aFieldName)
{
	$fid=$this->get_field_id($aFieldName);
	$fnm=$this->get_field_name($aFieldName);
	$f=$this->lw_fields[$aFieldName];
	$v=$f['v'];
	$rows=$f['x']['rows'];
	if ($rows<1) $rows=6;

	$r='<p><label for="'.$fid.'">'.$f['l'].'</label>'
		.'<textarea class="widefat" id="'.$fid.'" name="'.$fnm.'"'
		.' rows="'.$rows.'"'
		.'>'
		.$v
		.'</textarea>'
		.$this->form_help($aFieldName)
		.'</p>'
		."\r\n"
		;
	return $r;
}

public function form_select($aFieldName)
{
	$fid=$this->get_field_id($aFieldName);
	$fnm=$this->get_field_name($aFieldName);
	$f=$this->lw_fields[$aFieldName];
	$v=$f['v'];
	$sa=$f['x']['select'];
	if (count($sa)<1) return ''; //if no options, skip field

	$r='<p><label for="'.$fid.'">'.$f['l'].'</label>'
		.'<select class="widefat" id="'.$fid.'" name="'.$fnm.'"'
		.'>'
		;
	foreach($sa as $sv=>$d) {
		$r.='<option value="'.$sv.'" '
			.(($sv==$v)?'selected':'')
			.'>'.$d.'</option>'
			;
	}
	$r.='</select>'
		.$this->form_help($aFieldName)
		.'</p>'
		."\r\n"
		;
	return $r;
}

public function form_select_yesno($aFieldName)
{
	//this is the same as 'select', we just fill the options automatically
	return $this->form_select($aFieldName);
}

public function form_selectmulti($aFieldName)
{
	$fid=$this->get_field_id($aFieldName);
	$f=$this->lw_fields[$aFieldName];
	$v=$f['v'];
	$sa=$f['x']['select'];
	if (count($sa)<1) return ''; //if no options, skip field
	if (!is_array($v)) $v=explode(',',$v);

	$r='<p><label for="'.$fid.'">'.$f['l'].'</label>'
		.'<span style="display:block; overflow-y:scroll; height:100px;" class="widefat" id="'.$fid.'">'
		;
	foreach($sa as $sv=>$d) {
		$fid=$this->get_field_id($aFieldName.$sv);
		$fnm=$this->get_field_name($aFieldName.$sv);
		$r.='<input type="checkbox" id="'.$fid.'"'
			.' name="'.$fnm.'" '
			.' value="'.$sv.'"'
			;
		if (in_array($sv,$v)) $r.=' checked';
		$r.=' />'
			.'<label for="'.$fid.'">'
			.$d.'</label>'
			.'<br>'
			;
	}
	$r.='</span>'
		.$this->form_help($aFieldName)
		.'</p>'
		."\r\n"
		;
	return $r;
}

public function form_timestamp($aFieldName)
{
	$f=$this->lw_fields[$aFieldName];
	$v=$f['v'];
	if ($v=='') $v=current_time('mysql');
	list($year,$month,$day,$hour,$minute,$second)=preg_split('([^0-9])',$v);

	$fid=$this->get_field_id($aFieldName.'m');
	$fnm=$this->get_field_name($aFieldName.'m');
	$r='<p><label for="'.$fid.'">'.$f['l'].'</label><br>';
	$r.='<select id="'.$fid.'" name="'.$fnm.'"'
		.'>'
		;
	//Months
	global $wp_locale;
	for ($i=1; $i<13; $i=$i+1) {
		$r.='<option value="'.$i.'"'
			.(($i==$month)?' selected':'')
			.'>'.$wp_locale->get_month_abbrev($wp_locale->get_month($i))
			.'</option>'."\r\n";
	}
	$r.='</select> ';
	$fid=$this->get_field_id($aFieldName.'d');
	$fnm=$this->get_field_name($aFieldName.'d');
	$r.='<input id="'.$fid.'" name="'.$fnm.'"'
		.' size="1" maxlength="2" autocomplete="off"'
		.' type="text" value="'.$day.'"'
		.' />, ';
	$fid=$this->get_field_id($aFieldName.'y');
	$fnm=$this->get_field_name($aFieldName.'y');
	$r.='<input id="'.$fid.'" name="'.$fnm.'"'
		.' size="3" maxlength="4" autocomplete="off"'
		.' type="text" value="'.$year.'"'
		.' />@';
	$fid=$this->get_field_id($aFieldName.'h');
	$fnm=$this->get_field_name($aFieldName.'h');
	$r.='<input id="'.$fid.'" name="'.$fnm.'"'
		.' size="1" maxlength="2" autocomplete="off"'
		.' type="text" value="'.$hour.'"'
		.' />:';
	$fid=$this->get_field_id($aFieldName.'i');
	$fnm=$this->get_field_name($aFieldName.'i');
	$r.='<input id="'.$fid.'" name="'.$fnm.'"'
		.' size="1" maxlength="2" autocomplete="off"'
		.' type="text" value="'.$minute.'"'
		.' />';
	$r.=$this->form_help($aFieldName)
		.'</p>'
		."\r\n"
		;
	return $r;
}

// This will generate the admin form for the widget
// Override FormBeforeFields() to insert something before the
// registered fields.
// Override FormAfterFields() to insert after registered fields
public function form($instance)
{
	$i=$this->FormLoadOptions((array)$instance);
	$this->FormBeforeFields($i);

	foreach($this->lw_fields as $k=>$f) {
		switch ($f['t']) {
			case 'textarea':
				echo $this->form_textarea($k);
				break;
			case 'select':
				echo $this->form_select($k);
				break;
			case 'yesnoselect':
				echo $this->form_select_yesno($k);
				break;
			case 'selectmulti':
				echo $this->form_selectmulti($k);
				break;
			case 'timestamp':
				echo $this->form_timestamp($k);
				break;
			case 'text':
			default:
				echo $this->form_text($k);
				break;
		}//switch
	}//foreach

	$this->FormAfterFields($i);
}

public function FormBeforeFields($aInstance)
{
	//do whatever. $aInstance is already processed
}

public function FormAfterFields($aInstance)
{
	//do whatever. $aInstance is already processed
}

public function FormUpdate($aNew,$aOld)
{
	//override as needed
	return $aNew;
}

// Handles the WP_Widget call
function update($new_instance,$old_instance)
{
//	$instance = $old_instance;
	$instance = $new_instance; //lrh 2015-06-03
	foreach($this->lw_fields as $k=>$f) {
		$v=$new_instance[$k];
		switch ($f['t']) {
			case 'text':
				$v=trim(strip_tags($v));
				break;
			case 'textarea':
				if (!current_user_can('unfiltered_html'))
					$v=stripslashes(wp_filter_post_kses(addslashes($v))); // wp_filter_post_kses() expects slashed
				break;
			case 'timestamp':
				$d=lrhcvr($new_instance[$k.'y'],1970,2300)
					.'-'.lrhcvr($new_instance[$k.'m'],1,12)
					.'-'.lrhcvr($new_instance[$k.'d'],1,31)
					.' '.lrhcvr($new_instance[$k.'h'],0,23)
					.':'.lrhcvr($new_instance[$k.'i'],0,59)
					.':00';
				$v=date('Y-m-d H:i:s',strtotime($d));
				break;
			case 'selectmulti':
				$v=array();
				foreach($this->lw_fields[$k]['x']['select'] as $sv=>$sd) {
					$vv=$new_instance[$k.$sv];
					if ($vv!='') $v[]=$vv;
				}
				$v=implode(',',$v);
				break;
			default:
				$v=strip_tags($v);
				break;
		}//sw
		$instance[$k]=$v;
	}//for
	return $this->FormUpdate($instance,$old_instance);
}

function prepwidgetdata($instance)
{
	$instance=wp_parse_args($instance,$this->GetDefaultOptions());
	//Filter title now so it's available to methods
	if (empty($instance['title'])) {
		if (!$this->AllowEmptyTitle()) {
			$title=$this->name;
		} else {
			$title='';
		}
	} else {
		$title=$instance['title'];
	}
	$title = apply_filters(
		'widget_title'
		,$title
		,$instance
		,$this->id_base);
	$instance['title']=$title;
	return $instance;
}

// This can either be overridden, or called
function widget( $args, $instance )
{
	$instance=$this->prepwidgetdata($instance);
	if (!$this->oktoshowwidget($instance)) return;
	extract($args);

	echo $before_widget;
//	if ( !empty($instance['title']) )
		echo $before_title.$instance['title'].$after_title;
	$this->widgetcontent($instance);
	echo $after_widget;
}

// If using the above, this needs to be overridden
function widgetcontent($instance)
{
}

// If using the above, this allows testing to determine if
// the widget should be shown
// Override as needed
function oktoshowwidget($instance)
{
	return true;
}

}//class

function lrhcvr($v,$l,$h)
{
	if ($v<$l) return $l;
	else if ($v>$h) return $h;
	else return $v;
}

}//if
?>