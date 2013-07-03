<?php
# Copyright 2013 Mike Thorn (github: WasabiVengeance). All rights reserved.
# Use of this source code is governed by a BSD-style
# license that can be found in the LICENSE file.

global $__ssm;
$__ssm = array(
	'do_user_info'=>true,
	'do_language'=>true,
	'hooks'=>array(),
	'default_language'=>array('en','us'),
);

# These defines can be used to force user/language parsing, even if done before on a previous load.
# useful for testing!
if(!defined(__ssm_force_user_info__))
	define(__ssm_force_user_info__,false);
if(!defined(__ssm_force_user_language__))
	define(__ssm_force_user_language__,false);

class ssm
{
	function call_hook($hook,$p0=null,$p1=null,$p2=null,$p3=null,$p4=null,$p5=null,$p6=null)
	{
		global $__ssm;
		if(isset($__ssm['hooks'][$hook]))
			$__ssm['hooks'][$hook]($p0,$p1,$p2,$p3,$p4,$p5,$p6);
	}
	
	function log($to_write)
	{
		global $__ssm;		
		if(isset($__ssm['hooks']['log']))
		{
			$to_write=(is_object($to_write) || is_array($to_write))?print_r($to_write,true):$to_write;
			$__ssm['hooks']['log']('SSM: '.$to_write);
		}
	}
	
	function init($config=array())
	{
		global $__ssm;
		
		foreach($config as $key=>$value)
		{
			if(is_array($value))
			{
				foreach($value as $subkey=>$subvalue)
				{
					if(is_numeric($subkey))
						$__ssm[$key][] = $subvalue;
					else
						$__ssm[$key][$subkey] = $subvalue;
				}
			}
			else
				$__ssm[$key] = $value;
		}
		
		@session_start();
		
		if(($do_user_info && !isset($_SESSION['user_info'])) || __ssm_force_user_info__)
		{
			ssm::init_user_info();
		}
		if(($do_language && !isset($_SESSION['user_language'])) || __ssm_force_user_language__)
		{
			ssm::init_user_language();
		}
		
		ssm::log('init complete: '.print_r($_SESSION,true));
	}
		
	public static function deinit()
	{
	}
	
	function init_user_language()
	{
		global $__ssm;
		
		ssm::log('initing user language');
		
		if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			$x = explode(",",$_SERVER['HTTP_ACCEPT_LANGUAGE']);
			$lang =array();
			foreach ($x as $val) {
				if(preg_match("/(.*);q=([0-1]{0,1}\.\d{0,4})/i",$val,$matches))
					$lang[strtolower($matches[1])] = (float)$matches[2];
				else
					$lang[strtolower($val)] = 1.0;
			}
			
			$_SESSION['user_language'] = array();
			foreach($lang as $code=>$pref_level)
			{
				$_SESSION['user_language'][] = explode('-',$code);
			}
		}
		else
		{
			
			$_SESSION['user_language'] = array(
				$__ssm['default_language']
			);
		}
		
	}
	
	function init_user_info()
	{
		global $_ssm;
		
		ssm::log('initing user info');
		
		$__ssm['agent'] = strtolower($_SERVER['HTTP_USER_AGENT']);
		#ssm::log('Agent is: '.$__ssm['agent']);
		
		$info = array(
			'os'=>'',
			'device'=>'',
			'engine'=>'',
		);
		
		if(strpos($__ssm['agent'],'khtml') !== false)
			$info['engine'] = 'webkit';
		else if(strpos($__ssm['agent'],'firefox/') !== false)
			$info['engine'] = 'gecko';
		else if(strpos($__ssm['agent'],'trident') !== false)
			$info['engine'] = 'trident';
		else if(strpos($__ssm['agent'],'presto') !== false || strpos($info,'opera') !== false)
			$info['engine'] = 'presto';
	

		if(strpos($__ssm['agent'],'windows') !== false)
			$info['os'] = 'windows';
		else if(strpos($__ssm['agent'],'macintosh') !== false)
			$info['os'] = 'macos';
		else if(strpos($__ssm['agent'],'linux') !== false)
			$info['os'] = 'linux';
			
		if(strpos($__ssm['agent'],'iphone') !== false || 
			strpos($__ssm['agent'],'ipod') !== false || (
			strpos($__ssm['agent'],'android') !== false 
			&&
			strpos($__ssm['agent'],'mobile') !== false
		))
			$info['device'] = 'phone';
		else if(strpos($__ssm['agent'],'ipad') !== false || strpos($__ssm['agent'],'android') !== false)
			$info['device'] = 'tablet';
		else if(strpos($__ssm['agent'],'linux') !== false)
			$info['device'] = 'desktop';

		$_SESSION['user_info'] = $info;
	}
}

?>