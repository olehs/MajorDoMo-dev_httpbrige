<?php
/**
* BroadlinkHTTPBrige 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 16:06:00 [Jun 28, 2016])
*/
//
//
class dev_httpbrige extends module {
/**
* dev_httpbrige
*
* Module class constructor
*
* @access private
*/
function dev_httpbrige() {
  $this->name="dev_httpbrige";
  $this->title="BroadlinkHTTPBrige";
  $this->module_category="<#LANG_SECTION_DEVICES#>";
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
  $out['TAB']=$this->tab;
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
 $this->getConfig();
 $out['API_URL']=$this->config['API_URL'];
 if (!$out['API_URL']) {
  $out['API_URL']='http://';
 }
 $out['API_KEY']=$this->config['API_KEY'];
 $out['API_USERNAME']=$this->config['API_USERNAME'];
 $out['API_PASSWORD']=$this->config['API_PASSWORD'];
 if ($this->view_mode=='update_settings') {
   global $api_url;
   $this->config['API_URL']=$api_url;
   global $api_key;
   $this->config['API_KEY']=$api_key;
   global $api_username;
   $this->config['API_USERNAME']=$api_username;
   global $api_password;
   $this->config['API_PASSWORD']=$api_password;
   $this->saveConfig();
   $this->redirect("?");
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='dev_httpbrige_devices' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_dev_httpbrige_devices') {
   $this->search_dev_httpbrige_devices($out);
  }
  if ($this->view_mode=='edit_dev_httpbrige_devices') {
   $this->edit_dev_httpbrige_devices($out, $this->id);
  }
  if ($this->view_mode=='delete_dev_httpbrige_devices') {
   $this->delete_dev_httpbrige_devices($this->id);
   $this->redirect("?");
  }
 }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}
/**
* dev_httpbrige_devices search
*
* @access public
*/
 function search_dev_httpbrige_devices(&$out) {
  require(DIR_MODULES.$this->name.'/dev_httpbrige_devices_search.inc.php');
 }
/**
* dev_httpbrige_devices edit/add
*
* @access public
*/
 function edit_dev_httpbrige_devices(&$out, $id) {
  require(DIR_MODULES.$this->name.'/dev_httpbrige_devices_edit.inc.php');
 }
/**
* dev_httpbrige_devices delete record
*
* @access public
*/
 function delete_dev_httpbrige_devices($id) {
  $rec=SQLSelectOne("SELECT * FROM dev_httpbrige_devices WHERE ID='$id'");
  if ($rec['TYPE'] == 'sp2' || $rec['TYPE'] == 'spmini' || $rec['TYPE'] == 'sp3') {
	removeLinkedProperty($rec['LINKED_OBJECT'], 'status', $this->name);
  }
  if ($rec['TYPE'] == 'sp3') {
	removeLinkedProperty($rec['LINKED_OBJECT'], 'lightstatus', $this->name);
  }
  SQLExec("DELETE FROM dev_httpbrige_devices WHERE ID='".$rec['ID']."'");
 }
 function propertySetHandle($object, $property, $value) {
  $this->getConfig();
   $table='dev_httpbrige_devices';
   $properties=SQLSelect("SELECT * FROM $table WHERE LINKED_OBJECT LIKE '".DBSafe($object)."'");
   $total=count($properties);
   if ($total) {
    for($i=0;$i<$total;$i++) {
     //to-do
		if ($properties[$i]['TYPE'] == 'sp2' || $properties[$i]['TYPE'] == 'spmini' || $properties[$i]['TYPE'] == 'sp3') {
			if ($property == 'status') {
				if (gg($properties[$i]['LINKED_OBJECT'].'.'.'status') == 1 ) {
					$api_command=$this->config['API_URL'].'/?devMAC='.$properties[$i]['MAC'].'&action=on';
					getUrl($api_command);
				} else {
					$api_command=$this->config['API_URL'].'/?devMAC='.$properties[$i]['MAC'].'&action=off';
					getUrl($api_command);
				}
			}
			if ($property == 'lightstatus') {
				if (gg($properties[$i]['LINKED_OBJECT'].'.'.'status') == 1 ) {
					$api_command=$this->config['API_URL'].'/?devMAC='.$properties[$i]['MAC'].'&action=lighton';
					getUrl($api_command);
				} else {
					$api_command=$this->config['API_URL'].'/?devMAC='.$properties[$i]['MAC'].'&action=lightoff';
					getUrl($api_command);
				}
			}
		}
    }
   }
 }
 
function processSubscription($event_name, $details='') {
  if ($event_name=='HOURLY') {
		$this->check_params();
  }
 }
 
 function check_params() {
	$db_rec=SQLSelect("SELECT * FROM dev_httpbrige_devices");
 	for ($i = 1; $i <= count($db_rec); $i++) {
		$rec=$db_rec[$i-1];
		$this->getConfig();
		if ($rec['TYPE']=='rm') {
			$ctx = stream_context_create(array('http' => array('timeout'=>2)));
			$response = file_get_contents($this->config['API_URL'].'/?devMAC='.$rec['MAC'], 0, $ctx);
			if(isset($response) && $response!='') {
				sg($rec['LINKED_OBJECT'].'.temperature', (float)$response);
			}
		}
		if ($rec['TYPE']=='rm3') {
		}
		if ($rec['TYPE']=='a1') {
			$ctx = stream_context_create(array('http' => array('timeout'=>2)));
			$response = file_get_contents($this->config['API_URL'].'/?devMAC='.$rec['MAC'], 0, $ctx);
			if(isset($response) && $response!='') { 
				$json = json_decode($response);	
				sg($rec['LINKED_OBJECT'].'.temperature', (float)$json->{'temperature'});
				sg($rec['LINKED_OBJECT'].'.humidity', (float)$json->{'humidity'});
				sg($rec['LINKED_OBJECT'].'.noise', (int)$json->{'noisy'});
				sg($rec['LINKED_OBJECT'].'.luminosity', (int)$json->{'light'});
				sg($rec['LINKED_OBJECT'].'.air', (int)$json->{'air'});	
			}
		}
		if ($rec['TYPE']=='sp2') {
			$ctx = stream_context_create(array('http' => array('timeout'=>2)));
			$response = file_get_contents($this->config['API_URL'].'/?devMAC='.$rec['MAC'], 0, $ctx);
			if(isset($response) && $response!='') {
				sg($rec['LINKED_OBJECT'].'.status', (int)$response);
			}
			$response ='';
			$response = file_get_contents($this->config['API_URL'].'/?devMAC='.$rec['MAC'].'&action=power ', 0, $ctx);
			if(isset($response) && $response!='') {
				sg($rec['LINKED_OBJECT'].'.power', $response);
			}
		}
		if ($rec['TYPE']=='spmini') {
			$ctx = stream_context_create(array('http' => array('timeout'=>2)));
			$response = file_get_contents($this->config['API_URL'].'/?devMAC='.$rec['MAC'], 0, $ctx);
			if(isset($response) && $response!='') {
				sg($rec['LINKED_OBJECT'].'.status', (int)$response);
			}
		}
		if ($rec['TYPE']=='sp3') {
			$ctx = stream_context_create(array('http' => array('timeout'=>2)));
			$response = file_get_contents($this->config['API_URL'].'/?devMAC='.$rec['MAC'], 0, $ctx);
			if(isset($response) && $response!='') {
				sg($rec['LINKED_OBJECT'].'.status', (int)$response);
			}
			$response ='';
			$response = file_get_contents($this->config['API_URL'].'/?devMAC='.$rec['MAC'].'&action=lightstatus', 0, $ctx);
			if(isset($response) && $response!='') {
				sg($rec['LINKED_OBJECT'].'.lightstatus', $response);
			}
		}
		if(isset($response) && $response!='') {
			$rec['UPDATED']=date('Y-m-d H:i:s');
			SQLUpdate('dev_httpbrige_devices', $rec);
		}
	}
 }
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
 subscribeToEvent($this->name, 'HOURLY');
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
  SQLExec('DROP TABLE IF EXISTS dev_httpbrige_devices');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall() {
/*
dev_httpbrige_devices - 
*/
  $data = <<<EOD
 dev_httpbrige_devices: ID int(10) unsigned NOT NULL auto_increment
 dev_httpbrige_devices: TYPE varchar(10) NOT NULL DEFAULT ''
 dev_httpbrige_devices: TITLE varchar(100) NOT NULL DEFAULT ''
 dev_httpbrige_devices: MAC varchar(20) NOT NULL DEFAULT ''
 dev_httpbrige_devices: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 dev_httpbrige_devices: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 dev_httpbrige_devices: LINKED_METHOD varchar(100) NOT NULL DEFAULT ''
 dev_httpbrige_devices: UPDATED datetime
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgSnVuIDI4LCAyMDE2IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
