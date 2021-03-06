<?php
/**
* Scenes 
*
* Scenes
*
* @package project
* @author Serge J. <jey@tut.by>
* @copyright http://www.atmatic.eu/ (c)
* @version 0.1 (wizard, 10:05:38 [May 24, 2012])
*/
Define('DEF_TYPE_OPTIONS', 'img=Image|html=HTML'); // options for 'TYPE'
Define('DEF_CONDITION_OPTIONS', '1=Equa|2=More|3=Less|4=Not equal'); // options for 'CONDITION'
//
//
class scenes extends module {
/**
* scenes
*
* Module class constructor
*
* @access private
*/
function scenes() {
  $this->name="scenes";
  $this->title="<#LANG_MODULE_SCENES#>";
  $this->module_category="<#LANG_SECTION_OBJECTS#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }

  $this->checkSettings();

  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  if (IsSet($this->scene_id)) {
   $out['IS_SET_SCENE_ID']=1;
  }
  if (IsSet($this->element_id)) {
   $out['IS_SET_ELEMENT_ID']=1;
  }
  if (IsSet($this->script_id)) {
   $out['IS_SET_SCRIPT_ID']=1;
  }
  if ($this->single_rec) {
   $out['SINGLE_REC']=1;
  }
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='scenes' || $this->data_source=='') {

  if ($this->view_mode=='import_elements') {

   global $id;
   global $file;

   $seen_elements=array();

   $data=unserialize(LoadFile($file));
   if (is_array($data['ELEMENTS'])) {
    $elements=$data['ELEMENTS'];

   $total=count($elements);
   for($i=0;$i<$total;$i++) {
    $states=$elements[$i]['STATES'];
    unset($elements[$i]['STATES']);
    $elements[$i]['SCENE_ID']=$id;
    $old_element_id=$elements[$i]['ID'];
    unset($elements[$i]['ID']);
    if ($elements[$i]['LINKED_ELEMENT_ID']) {
     $elements[$i]['LINKED_ELEMENT_ID']=(int)$seen_elements[$elements[$i]['LINKED_ELEMENT_ID']];
    }
    $elements[$i]['ID']=SQLInsert('elements', $elements[$i]);
    $seen_elements[$old_element_id]=$elements[$i]['ID'];
    $totalE=count($states);
    for($iE=0;$iE<$totalE;$iE++) {
     unset($states[$iE]['ID']);
     $states[$iE]['ELEMENT_ID']=$elements[$i]['ID'];
     if ($states[$iE]['IMAGE_DATA']) {
      $filename=ROOT.$states[$iE]['IMAGE'];
      SaveFile($filename, base64_decode($states[$iE]['IMAGE_DATA']));      
      unset($states[$iE]['IMAGE_DATA']);
     }
     SQLInsert('elm_states', $states[$iE]);
    }
   }
   for($i=0;$i<$total;$i++) {
    if ($elements[$i]['CONTAINER_ID']) {
     $elements[$i]['CONTAINER_ID']=(int)$seen_elements[$elements[$i]['CONTAINER_ID']];
     SQLUpdate('elements', $elements[$i]);
    }
   }

   }
   $this->redirect("?tab=".$this->tab."&view_mode=edit_scenes&id=".$id);

  }


  if ($this->view_mode=='multiple_elements') {
   global $selected;
   if ($selected[0]) {

  $res=array();
  $elements=SQLSelect("SELECT * FROM elements WHERE ID IN (".implode(',', $selected).") ORDER BY LINKED_ELEMENT_ID, CONTAINER_ID, ID");
  $total=count($elements);
  for($i=0;$i<$total;$i++) {
   $elm_id=$elements[$i]['ID'];
   //unset($elements[$i]['ID']);
   //unset($elements[$i]['CONTAINER_ID']);
   //unset($elements[$i]['LINKED_ELEMENT_ID']);
   unset($elements[$i]['SCENE_ID']);
   $states=SQLSelect("SELECT * FROM elm_states WHERE ELEMENT_ID='".(int)$elm_id."'");
   $totalE=count($states);
   for($iE=0;$iE<$totalE;$iE++) {
    unset($states[$iE]['ID']);
    unset($states[$iE]['ELEMENT_ID']);
    if ($states[$iE]['IMAGE']) {
     $states[$iE]['IMAGE_DATA']=base64_encode(LoadFile(ROOT.$states[$iE]['IMAGE']));
    }
   }
   $elements[$i]['STATES']=$states;
  }
  $res['ELEMENTS']=$elements;

  $data=serialize($res);

   $filename=urlencode('Elements'.date('H-i-s'));

   $ext = "elements";   // file extension
   $mime_type = (PMA_USR_BROWSER_AGENT == 'IE' || PMA_USR_BROWSER_AGENT == 'OPERA')
   ? 'application/octetstream'
   : 'application/octet-stream';
   header('Content-Type: ' . $mime_type);
   if (PMA_USR_BROWSER_AGENT == 'IE')
   {
      header('Content-Disposition: inline; filename="' . $filename . '.' . $ext . '"');
      header("Content-Transfer-Encoding: binary");
      header('Expires: 0');
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Pragma: public');
      print $data;
   } else {
      header('Content-Disposition: attachment; filename="' . $filename . '.' . $ext . '"');
      header("Content-Transfer-Encoding: binary");
      header('Expires: 0');
      header('Pragma: no-cache');
      print $data;
   }

   exit;


    exit;
   } else {
    $this->view_mode='edit_scenes';
   }
  }

  if ($this->view_mode=='' || $this->view_mode=='search_scenes') {
   $this->search_scenes($out);
  }
  if ($this->view_mode=='edit_scenes') {
   $this->edit_scenes($out, $this->id);
  }
  if ($this->view_mode=='delete_scenes') {
   $this->delete_scenes($this->id);
   $this->redirect("?data_source=scenes");
  }
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='elements') {
  if ($this->view_mode=='' || $this->view_mode=='search_elements') {
   $this->search_elements($out);
  }
  if ($this->view_mode=='edit_elements') {
   $this->edit_elements($out, $this->id);
  }
  if ($this->view_mode=='delete_elements') {
   $this->delete_elements($this->id);
   $this->redirect("?data_source=elements");
  }




 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='elm_states') {
  if ($this->view_mode=='' || $this->view_mode=='search_elm_states') {
   $this->search_elm_states($out);
  }
  if ($this->view_mode=='edit_elm_states') {
   $this->edit_elm_states($out, $this->id);
  }
  if ($this->view_mode=='delete_elm_states') {
   $this->delete_elm_states($this->id);
   $this->redirect("?data_source=elm_states");
  }
 }

 if ($this->view_mode=='clone' && $this->id) {
  $this->clone_scene($this->id);
 }
 if ($this->view_mode=='export' && $this->id) {
  $this->export_scene($this->id);
 }

 if ($this->view_mode=='import') {
  $this->import_scene();
 }



}

 /**
 * Title
 *
 * Description
 *
 * @access public
 */
 function export_scene($id) {
  $rec=SQLSelectOne("SELECT * FROM scenes WHERE ID='".(int)$id."'"); 
  //elements
  $elements=SQLSelect("SELECT * FROM elements WHERE SCENE_ID='".(int)$id."'");
  $total=count($elements);
  for($i=0;$i<$total;$i++) {
   $elm_id=$elements[$i]['ID'];
   unset($elements[$i]['ID']);
   unset($elements[$i]['SCENE_ID']);
   $states=SQLSelect("SELECT * FROM elm_states WHERE ELEMENT_ID='".(int)$elm_id."'");
   $totalE=count($states);
   for($iE=0;$iE<$totalE;$iE++) {
    unset($states[$iE]['ID']);
    unset($states[$iE]['ELEMENT_ID']);
   }
   $elements[$i]['STATES']=$states;
  }
  $rec['ELEMENTS']=$elements;
  unset($rec['ID']);

  $res=array();
  $res['SCENE_DATA']=$rec;
  if ($rec['BACKGROUND'] && file_exists(ROOT.$rec['BACKGROUND'])) {
   $res['BACKGROUND_IMAGE']=base64_encode(LoadFile(ROOT.$rec['BACKGROUND']));
  }
  if ($rec['WALLPAPER'] && file_exists(ROOT.$rec['WALLPAPER'])) {
   $res['WALLPAPER_IMAGE']=base64_encode(LoadFile(ROOT.$rec['WALLPAPER']));
  }


  $data=serialize($res);

   $filename=urlencode($rec['TITLE']);

   $ext = "scene";   // file extension
   $mime_type = (PMA_USR_BROWSER_AGENT == 'IE' || PMA_USR_BROWSER_AGENT == 'OPERA')
   ? 'application/octetstream'
   : 'application/octet-stream';
   header('Content-Type: ' . $mime_type);
   if (PMA_USR_BROWSER_AGENT == 'IE')
   {
      header('Content-Disposition: inline; filename="' . $filename . '.' . $ext . '"');
      header("Content-Transfer-Encoding: binary");
      header('Expires: 0');
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Pragma: public');
      print $data;
   } else {
      header('Content-Disposition: attachment; filename="' . $filename . '.' . $ext . '"');
      header("Content-Transfer-Encoding: binary");
      header('Expires: 0');
      header('Pragma: no-cache');
      print $data;
   }

   exit;


 }

/**
* Title
*
* Description
*
* @access public
*/
 function import_scene() {
  global $file;
  global $overwrite;

  $data=unserialize(LoadFile($file));

  if ($data['SCENE_DATA']) {
   $rec=$data['SCENE_DATA'];
   if (!$rec['WALLPAPER']) {
    unset($rec['WALLPAPER']);
   }
   $rec['TITLE'].=' (imported)';
   $elements=$rec['ELEMENTS'];
   unset($rec['ID']);
   unset($rec['ELEMENTS']);
   $rec['ID']=SQLInsert('scenes', $rec);
   $total=count($elements);
   for($i=0;$i<$total;$i++) {
    $states=$elements[$i]['STATES'];
    unset($elements[$i]['STATES']);
    unset($elements[$i]['ID']);
    $elements[$i]['SCENE_ID']=$rec['ID'];
    $elements[$i]['ID']=SQLInsert('elements', $elements[$i]);
    $totalE=count($states);
    for($iE=0;$iE<$totalE;$iE++) {
     unset($states[$iE]['ID']);
     $states[$iE]['ELEMENT_ID']=$elements[$i]['ID'];
     SQLInsert('elm_states', $states[$iE]);
    }
   }
   if ($data['BACKGROUND_IMAGE']) {
    $filename=ROOT.$rec['BACKGROUND'];
    SaveFile($filename, base64_decode($data['BACKGROUND_IMAGE']));
   }
   if ($data['WALLPAPER_IMAGE']) {
    $filename=ROOT.$rec['WALLPAPER'];
    SaveFile($filename, base64_decode($data['WALLPAPER_IMAGE']));
   }
   $this->redirect("?view_mode=edit_scenes&id=".$rec['ID']);
  }

  $this->redirect("?");
  
 }

/**
* Title
*
* Description
*
* @access public
*/
 function clone_scene($id) {
  $rec=SQLSelectOne("SELECT * FROM scenes WHERE ID='".(int)$id."'");
  $rec['TITLE'].=' (copy)';
  unset($rec['ID']);
  $rec['ID']=SQLInsert('scenes', $rec);

  //elements
  $elements=SQLSelect("SELECT * FROM elements WHERE SCENE_ID='".(int)$id."'");
  $total=count($elements);
  for($i=0;$i<$total;$i++) {
   $elm_id=$elements[$i]['ID'];
   unset($elements[$i]['ID']);
   $elements[$i]['SCENE_ID']=$rec['ID'];
   $elements[$i]['ID']=SQLInsert('elements', $elements[$i]);
   $states=SQLSelect("SELECT * FROM elm_states WHERE ELEMENT_ID='".(int)$elm_id."'");
   $totalE=count($states);
   for($iE=0;$iE<$totalE;$iE++) {
    unset($states[$iE]['ID']);
    $states[$iE]['ELEMENT_ID']=$elements[$i]['ID'];
    SQLInsert('elm_states', $states[$iE]);
   }
  }

  $this->redirect("?view_mode=edit_scenes&id=".$rec['ID']);
 }

/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {

 global $ajax;
 if ($ajax) {
    global $op;
    header ("HTTP/1.0: 200 OK\n");
    header ('Content-Type: text/html; charset=utf-8');
    if ($op=='checkAllStates') {
     global $scene_id;
     $qry="1";
     if (preg_match('/(\d+)\.html/', $_SERVER["REQUEST_URI"], $m)) {
      $qry.=" AND scenes.ID='".(int)$m[1]."'";
     } elseif ($scene_id) {
      $qry.=" AND scenes.ID='".(int)$scene_id."'";
     } else {
      $qry.=" AND scenes.HIDDEN!=1";
     }
     $states=SQLSelect("SELECT elm_states.ID, elm_states.TITLE, elm_states.HTML, elements.SCENE_ID, elm_states.SWITCH_SCENE, elements.TYPE FROM elm_states, elements, scenes WHERE elements.SCENE_ID=scenes.ID AND elm_states.ELEMENT_ID=elements.ID AND $qry ORDER BY elements.PRIORITY DESC, elm_states.PRIORITY DESC");
     $total=count($states);
     for($i=0;$i<$total;$i++) {
      $states[$i]['STATE']=$this->checkState($states[$i]['ID']);
      if ($states[$i]['HTML']!='') {
       if (preg_match('/\[#/is', $states[$i]['HTML'])) {
        $states[$i]['HTML']='';
       } else {
        $states[$i]['HTML']=processTitle($states[$i]['HTML'], $this);
       }
      }
     }
     echo json_encode($states);
    }
    if ($op=='click') {
     global $id;
     $state=SQLSelectOne("SELECT * FROM elm_states WHERE ID='".$id."'");
     $params=array('STATE'=>$state['TITLE']);
     if ($state['ACTION_OBJECT'] && $state['ACTION_METHOD']) {
      callMethod($state['ACTION_OBJECT'].'.'.$state['ACTION_METHOD'], $params);
     }
     if ($state['SCRIPT_ID']) {
      runScript($state['SCRIPT_ID'], $params);
     }

     $qry="1";
     $qry.=" AND elements.ID=".$state['ELEMENT_ID'];
     $states=SQLSelect("SELECT elm_states.ID, elm_states.TITLE, elm_states.HTML, elements.SCENE_ID, elm_states.SWITCH_SCENE, elements.TYPE FROM elm_states, elements, scenes WHERE elements.SCENE_ID=scenes.ID AND elm_states.ELEMENT_ID=elements.ID AND $qry ORDER BY elements.PRIORITY DESC, elm_states.PRIORITY DESC");
     $total=count($states);
     for($i=0;$i<$total;$i++) {
      $states[$i]['STATE']=$this->checkState($states[$i]['ID']);
      if ($states[$i]['HTML']!='') {
       $states[$i]['HTML']=processTitle($states[$i]['HTML'], $this);
      }
     }
     echo json_encode($states);

    }

    if ($op=='position') {
     global $id;
     global $posx;
     global $posy;
     global $width;
     global $height;
     if ($id && $posx && $posy && $width && $height) {
      $state=SQLSelectOne("SELECT * FROM elm_states WHERE ID='".$id."'");
      $state['WINDOW_POSX']=$posx;
      $state['WINDOW_POSY']=$posy;
      $state['WINDOW_WIDTH']=$width;
      $state['WINDOW_HEIGHT']=$height;
      SQLUpdate('elm_states', $state);
     }
     // 
     echo "OK";
    }
    exit;
 }

 $this->admin($out);

 $out['ALL_TYPES']=$this->getAllTypes();

}

 function checkSettings() {
  $settings=array(
   array(
    'NAME'=>'SCENES_WIDTH', 
    'TITLE'=>'Scene width', 
    'TYPE'=>'text',
    'DEFAULT'=>'803'
    ),
   array(
    'NAME'=>'SCENES_HEIGHT', 
    'TITLE'=>'Scene height',
    'TYPE'=>'text',
    'DEFAULT'=>'606'
    )
   );


   foreach($settings as $k=>$v) {
    $rec=SQLSelectOne("SELECT ID FROM settings WHERE NAME='".$v['NAME']."'");
    if (!$rec['ID']) {
     $rec['NAME']=$v['NAME'];
     $rec['VALUE']=$v['DEFAULT'];
     $rec['DEFAULTVALUE']=$v['DEFAULT'];
     $rec['TITLE']=$v['TITLE'];
     $rec['TYPE']=$v['TYPE'];
     $rec['ID']=SQLInsert('settings', $rec);
     Define('SETTINGS_'.$rec['NAME'], $v['DEFAULT']);
    }
   }

 }

/**
* scenes search
*
* @access public
*/
 function search_scenes(&$out) {
  require(DIR_MODULES.$this->name.'/scenes_search.inc.php');
 }
/**
* scenes edit/add
*
* @access public
*/
 function edit_scenes(&$out, $id) {
  require(DIR_MODULES.$this->name.'/scenes_edit.inc.php');
 }
/**
* scenes delete record
*
* @access public
*/
 function delete_scenes($id) {
  $rec=SQLSelectOne("SELECT * FROM scenes WHERE ID='$id'");
  // some action for related tables
  $elements=SQLSelect("SELECT ID FROM elements WHERE SCENE_ID='".(int)$rec['ID']."'");
  $total=count($elements);
  for($i=0;$i<$total;$i++) {
   $this->delete_elements($elements[$i]['ID']);
  }

  SQLExec("DELETE FROM scenes WHERE ID='".$rec['ID']."'");
 }
/**
* elements search
*
* @access public
*/
 function search_elements(&$out) {
  require(DIR_MODULES.$this->name.'/elements_search.inc.php');
 }
/**
* elements edit/add
*
* @access public
*/
 function edit_elements(&$out, $id) {
  require(DIR_MODULES.$this->name.'/elements_edit.inc.php');
 }
/**
* elements delete record
*
* @access public
*/
 function delete_elements($id) {
  $rec=SQLSelectOne("SELECT * FROM elements WHERE ID='$id'");
  // some action for related tables
  $states=SQLSelect("SELECT ID FROM elm_states WHERE ELEMENT_ID='".(int)$rec['ID']."'");
  $total=count($states);
  for($i=0;$i<$total;$i++) {
   $this->delete_elm_states($states[$i]['ID']);
  }
  SQLExec("DELETE FROM elements WHERE ID='".$rec['ID']."' OR (CONTAINER_ID>0 AND CONTAINER_ID='".$rec['ID']."')");
 }

/**
* Title
*
* Description
*
* @access public
*/
 function reorder_elements($id, $direction='up') {
  $element=SQLSelectOne("SELECT * FROM elements WHERE ID='".(int)$id."'");
  if ($element['CONTAINER_ID']) {
   $all_elements=SQLSelect("SELECT * FROM elements WHERE CONTAINER_ID=".$element['CONTAINER_ID']." ORDER BY PRIORITY DESC, TITLE");
  } else {
   $all_elements=SQLSelect("SELECT * FROM elements WHERE SCENE_ID=".$element['SCENE_ID']." AND CONTAINER_ID=0 ORDER BY PRIORITY DESC, TITLE");
  }

  $total=count($all_elements);


  for($i=0;$i<$total;$i++) {
   if ($all_elements[$i]['ID']==$id && $i>0 && $direction=='up') {
    $tmp=$all_elements[$i-1];
    $all_elements[$i-1]=$all_elements[$i];
    $all_elements[$i]=$tmp;
    break;
   }
   if ($all_elements[$i]['ID']==$id && $i<($total-1) && $direction=='down') {
    $tmp=$all_elements[$i+1];
    $all_elements[$i+1]=$all_elements[$i];
    $all_elements[$i]=$tmp;
    break;
   }
  }

  $priority=($total)*10;

  for($i=0;$i<$total;$i++) {
   $all_elements[$i]['PRIORITY']=$priority;
   $priority-=10;
   SQLUpdate('elements', $all_elements[$i]);
  }


  
 }

/**
* elm_states search
*
* @access public
*/
 function search_elm_states(&$out) {
  require(DIR_MODULES.$this->name.'/elm_states_search.inc.php');
 }
/**
* elm_states edit/add
*
* @access public
*/
 function edit_elm_states(&$out, $id) {
  require(DIR_MODULES.$this->name.'/elm_states_edit.inc.php');
 }
/**
* elm_states delete record
*
* @access public
*/
 function delete_elm_states($id) {
  $rec=SQLSelectOne("SELECT * FROM elm_states WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM elm_states WHERE ID='".$rec['ID']."'");
 }


/**
* Title
*
* Description
*
* @access public
*/
 function checkState($id) {
  $rec=SQLSelectOne("SELECT * FROM elm_states WHERE ID='".$id."'");

  if (!$rec['IS_DYNAMIC']) {
   $status=1;
  } elseif ($rec['IS_DYNAMIC']==1) {
   if ($rec['LINKED_OBJECT']!='' && $rec['LINKED_PROPERTY']!='') {
    $value=gg(trim($rec['LINKED_OBJECT']).'.'.trim($rec['LINKED_PROPERTY']));
   } elseif ($rec['LINKED_PROPERTY']!='') {
    $value=gg($rec['LINKED_PROPERTY']);
   } else {
    $value=-1;
   }

   if (($rec['CONDITION']==2 || $rec['CONDITION']==3) 
       && $rec['CONDITION_VALUE']!='' 
       && !is_numeric($rec['CONDITION_VALUE']) 
       && !preg_match('/^%/', $rec['CONDITION_VALUE'])) {
        $rec['CONDITION_VALUE']='%'.$rec['CONDITION_VALUE'].'%';
   }


   if (is_integer(strpos($rec['CONDITION_VALUE'], "%"))) {
    $rec['CONDITION_VALUE']=processTitle($rec['CONDITION_VALUE']);
   }

   if ($rec['CONDITION']==1 && $value==$rec['CONDITION_VALUE']) {
    $status=1;
   } elseif ($rec['CONDITION']==2 && (float)$value>(float)$rec['CONDITION_VALUE']) {
    $status=1;
   } elseif ($rec['CONDITION']==3 && (float)$value<(float)$rec['CONDITION_VALUE']) {
    $status=1;
   } elseif ($rec['CONDITION']==4 && $value!=$rec['CONDITION_VALUE']) {
    $status=1;
   } else {
    $status=0;
   }

  } elseif ($rec['IS_DYNAMIC']==2) {

   $display=0;

   if (is_integer(strpos($rec['CONDITION_ADVANCED'], "%"))) {
    $rec['CONDITION_ADVANCED']=processTitle($rec['CONDITION_ADVANCED']);
   }

                  try {
                   $code=$rec['CONDITION_ADVANCED'];
                   $success=eval($code);
                   if ($success===false) {
                    DebMes("Error in scene code: ".$code);
                   }
                  } catch(Exception $e){
                   DebMes('Error: exception '.get_class($e).', '.$e->getMessage().'.');
                  }

   $status=$display;

  }

  if ($rec['CURRENT_STATE']!=$status) {
   $rec['CURRENT_STATE']=$status;
   SQLExec('UPDATE elm_states SET CURRENT_STATE='.$rec['CURRENT_STATE'].' WHERE ID='.(int)$rec['ID']);
  }

  return $status;
 }


/**
* Title
*
* Description
*
* @access public
*/
 function getElements($qry='1') {
      $elements=SQLSelect("SELECT * FROM elements WHERE $qry ORDER BY PRIORITY DESC, TITLE");
      $totale=count($elements);
      for($ie=0;$ie<$totale;$ie++) {
       if ($elements[$ie]['PRIORITY']) {
        $elements[$ie]['ZINDEX']=round($elements[$ie]['PRIORITY']/10);
       }
       if ($elements[$ie]['TYPE']=='img') {
        $elements[$ie]['BACKGROUND']=0;
       }
       $positions[$elements[$ie]['ID']]['TOP']=$elements[$ie]['TOP'];
       $positions[$elements[$ie]['ID']]['LEFT']=$elements[$ie]['LEFT'];
       $states=SQLSelect("SELECT * FROM elm_states WHERE ELEMENT_ID='".$elements[$ie]['ID']."' ORDER BY PRIORITY DESC, TITLE");
       $total_s=count($states);
       for($is=0;$is<$total_s;$is++) {
        if ($states[$is]['HTML']!='') {
         $states[$is]['HTML']=processTitle($states[$is]['HTML']);
        }
       }
       $elements[$ie]['STATES']=$states;
       if ($elements[$ie]['TYPE']=='container') {
        $elements[$ie]['ELEMENTS']=$this->getElements("CONTAINER_ID=".(int)$elements[$ie]['ID']);
       }
      }
      for($ie=0;$ie<$totale;$ie++) {
       if ($elements[$ie]['LINKED_ELEMENT_ID']) {
        $elements[$ie]['TOP']=$positions[$elements[$ie]['LINKED_ELEMENT_ID']]['TOP']+$elements[$ie]['TOP'];
        $elements[$ie]['LEFT']=$positions[$elements[$ie]['LINKED_ELEMENT_ID']]['LEFT']+$elements[$ie]['LEFT'];
        $positions[$elements[$ie]['ID']]['TOP']=$elements[$ie]['TOP'];
        $positions[$elements[$ie]['ID']]['LEFT']=$elements[$ie]['LEFT'];
       }
      }
      return $elements;  
 }

/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
 @umask(0);
  if (!Is_Dir(ROOT."./cms/scenes")) {
   mkdir(ROOT."./cms/scenes", 0777);
  }
  if (!Is_Dir(ROOT."./cms/scenes/elements")) {
   mkdir(ROOT."./cms/scenes/elements", 0777);
  }
  if (!Is_Dir(ROOT."./cms/scenes/backgrounds")) {
   mkdir(ROOT."./cms/scenes/backgrounds", 0777);
  }
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS scenes');
  SQLExec('DROP TABLE IF EXISTS elements');
  SQLExec('DROP TABLE IF EXISTS elm_states');
  parent::uninstall();
 }


 function getAllTypes() {
  $path=ROOT.'cms/scenes/styles';
  $res_types=array();
  if ($handle = opendir($path)) {
   $style_recs=array();
   while (false !== ($entry = readdir($handle))) {
    if ($entry!='.' && $entry!='..' && is_dir($path.'/'.$entry)) {
     $type_rec=array('TITLE'=>$entry, 'STYLES'=>$this->getStyles($entry));
     if (file_exists($path.'/'.$entry.'/style.css')) {
      $type_rec['HAS_STYLE']=1;
     }
     $res_types[]=$type_rec;
    }
   }
  }
  closedir($handle);
  return $res_types;
 }

 function getStyles($type='') {

  $path=ROOT.'cms/scenes/styles/'.$type;

  if (is_dir($path)) {

   if ($handle = opendir($path)) {
    $style_recs=array();
    while (false !== ($entry = readdir($handle))) {
       if (preg_match('/(.+?)\.png$/is', $entry, $m)) {
        $style=$m[1];
        $style=preg_replace('/^i\_/', '', $style);

        if (preg_match('/^ign_/', $style)) {
         continue;
        }

        $has_low=0;
        if (preg_match('/\_lo$/', $style)) {
         $style=preg_replace('/\_lo$/', '', $style);
         $has_low=$entry;
        }
        $has_high=0;
        if (preg_match('/\_hi$/', $style)) {
         $style=preg_replace('/\_hi$/', '', $style);
         $has_high=$entry;
        }

        $has_on=0;
        if (preg_match('/\_on$/', $style)) {
         $style=preg_replace('/\_on$/', '', $style);
         $has_on=$entry;
        }
        $has_off=0;
        if (preg_match('/\_off$/', $style)) {
         $style=preg_replace('/\_off$/', '', $style);
         $has_off=$entry;
        }


        $styles_recs[$style]['TITLE']=$style;
        if ($has_low) {
         $styles_recs[$style]['HAS_LOW']=$has_low;
        }
        if ($has_high) {
         $styles_recs[$style]['HAS_HIGH']=$has_high;
        }
        if ($has_on) {
         $styles_recs[$style]['HAS_ON']=$has_on;
        }
        if ($has_off) {
         $styles_recs[$style]['HAS_OFF']=$has_off;
        }

        if (!$has_low && !$has_high && !$has_on && !$has_ff) {
         $styles_recs[$style]['HAS_DEFAULT']=$entry;
        }

       }
    }
    closedir($handle);

    if (is_array($styles_recs)) {
     foreach($styles_recs as $k=>$v) {
      if (!$styles_recs[$k]['IMAGE'] && file_exists($path.'/'.$v['TITLE'].'.png')) {
       $styles_recs[$k]['IMAGE']=$type.'/'.$v['TITLE'].'.png';
      }
      if (!$styles_recs[$k]['IMAGE'] && file_exists($path.'/i_'.$v['TITLE'].'.png')) {
       $styles_recs[$k]['IMAGE']=$type.'/i_'.$v['TITLE'].'.png';
      }

      if (!$styles_recs[$k]['IMAGE'] && file_exists($path.'/i_'.$v['TITLE'].'_on.png')) {
       $styles_recs[$k]['IMAGE']=$type.'/i_'.$v['TITLE'].'_on.png';
      }
     }
    }

    if (is_array($styles_recs)) {
     $res_styles=array();
     foreach($styles_recs as $k=>$v) {
      $res_styles[]=$v;
     }
    }

   }
   return $res_styles;
  }

  
 }

/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data) {
/*
scenes - Scenes
elements - Elements
elm_states - Element states
*/
  $data = <<<EOD
 scenes: ID int(10) unsigned NOT NULL auto_increment
 scenes: TITLE varchar(255) NOT NULL DEFAULT ''
 scenes: BACKGROUND varchar(255) NOT NULL DEFAULT ''
 scenes: WALLPAPER varchar(255) NOT NULL DEFAULT ''
 scenes: PRIORITY int(10) NOT NULL DEFAULT '0'
 scenes: HIDDEN int(3) NOT NULL DEFAULT '0'
 scenes: WALLPAPER_FIXED int(3) NOT NULL DEFAULT '0'
 scenes: WALLPAPER_NOREPEAT int(3) NOT NULL DEFAULT '0'

 elements: ID int(10) unsigned NOT NULL auto_increment
 elements: SCENE_ID int(10) NOT NULL DEFAULT '0'
 elements: TITLE varchar(255) NOT NULL DEFAULT ''
 elements: TYPE varchar(255) NOT NULL DEFAULT ''
 elements: CSS_STYLE varchar(255) NOT NULL DEFAULT ''
 elements: TOP int(10) NOT NULL DEFAULT '0'
 elements: LEFT int(10) NOT NULL DEFAULT '0'
 elements: WIDTH int(10) NOT NULL DEFAULT '0'
 elements: HEIGHT int(10) NOT NULL DEFAULT '0'
 elements: DX int(10) NOT NULL DEFAULT '0'
 elements: DY int(10) NOT NULL DEFAULT '0'
 elements: POSITION_TYPE int(3) NOT NULL DEFAULT '0'
 elements: LINKED_ELEMENT_ID int(10) NOT NULL DEFAULT '0'
 elements: CONTAINER_ID int(10) NOT NULL DEFAULT '0'
 elements: CROSS_SCENE int(3) NOT NULL DEFAULT '0'
 elements: BACKGROUND int(3) NOT NULL DEFAULT '0'
 elements: PRIORITY int(10) NOT NULL DEFAULT '0'
 elements: JAVASCRIPT text
 elements: CSS text

 elm_states: ID int(10) unsigned NOT NULL auto_increment
 elm_states: ELEMENT_ID int(10) NOT NULL DEFAULT '0'
 elm_states: TITLE varchar(255) NOT NULL DEFAULT ''
 elm_states: IMAGE varchar(255) NOT NULL DEFAULT ''
 elm_states: HTML text
 elm_states: IS_DYNAMIC int(3) NOT NULL DEFAULT '0'
 elm_states: CURRENT_STATE int(3) NOT NULL DEFAULT '0'
 elm_states: LINKED_OBJECT varchar(255) NOT NULL DEFAULT ''
 elm_states: LINKED_PROPERTY varchar(255) NOT NULL DEFAULT ''
 elm_states: ACTION_OBJECT varchar(255) NOT NULL DEFAULT ''
 elm_states: ACTION_METHOD varchar(255) NOT NULL DEFAULT ''
 elm_states: CONDITION int(3) NOT NULL DEFAULT '0'
 elm_states: CONDITION_VALUE varchar(255) NOT NULL DEFAULT ''
 elm_states: CONDITION_ADVANCED text
 elm_states: SCRIPT_ID int(10) NOT NULL DEFAULT '0'
 elm_states: MENU_ITEM_ID int(10) NOT NULL DEFAULT '0'
 elm_states: HOMEPAGE_ID int(10) NOT NULL DEFAULT '0'
 elm_states: EXT_URL varchar(255) NOT NULL DEFAULT ''
 elm_states: WINDOW_POSX int(10) NOT NULL DEFAULT '0'
 elm_states: WINDOW_POSY int(10) NOT NULL DEFAULT '0'
 elm_states: WINDOW_WIDTH int(10) NOT NULL DEFAULT '0'
 elm_states: WINDOW_HEIGHT int(10) NOT NULL DEFAULT '0'
 elm_states: SWITCH_SCENE int(3) NOT NULL DEFAULT '0'
 elm_states: CURRENT_STATUS int(3) NOT NULL DEFAULT '0'
 elm_states: PRIORITY int(10) NOT NULL DEFAULT '0'
EOD;

  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgTWF5IDI0LCAyMDEyIHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
?>