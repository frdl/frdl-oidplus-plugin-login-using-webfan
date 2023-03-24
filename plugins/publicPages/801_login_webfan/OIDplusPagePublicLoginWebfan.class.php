<?php
namespace Frdlweb;
use ViaThinkSoft\OIDplus\OIDplus;
use ViaThinkSoft\OIDplus\OIDplusConfig;
use ViaThinkSoft\OIDplus\OIDplusObjectTypePlugin;
use ViaThinkSoft\OIDplus\OIDplusPagePluginPublic;
use ViaThinkSoft\OIDplus\OIDplusObject;
use ViaThinkSoft\OIDplus\OIDplusException; 
/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
use Wehowski\Helpers\ArrayHelper;

 

class OIDplusPagePublicLoginWebfan extends OIDplusPagePluginPublic {

	public function action(string $actionID, array $params): array {
		throw new OIDplusException(_L('Unknown action ID'));
	}

	public function init($html=true) {
		// Nothing
		/*
			header("Content-Security-Policy: default-src 'self' blob: * https://pagead2.googlesyndication.com/ https://fonts.gstatic.com https://www.google.com/ https://www.gstatic.com/ https://cdnjs.cloudflare.com/  https://registry.frdl.de/ https://webfan.de/; ".
	       "style-src 'self' 'unsafe-inline' * https://pagead2.googlesyndication.com/ https://cdnjs.cloudflare.com/  https://registry.frdl.de/ https://webfan.de/ https://".$_SERVER['SERVER_NAME']."/; ".
	       "img-src blob: data: http: https:; ".
	       "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: * https://pagead2.googlesyndication.com/ https://www.google.com/ https://www.gstatic.com/ https://cdnjs.cloudflare.com/ https://polyfill.io/ https://cdn.frdl.de/ https://registry.frdl.de/ https://webfan.de/ https://".$_SERVER['SERVER_NAME']."/; ".
	       "frame-ancestors 'none'; ".
	       "object-src 'none'");
		   */
		
	}

	public function gui($id, &$out, &$handled) {

		 if ($id === 'oidplus:weid_info') {
			 	$handled = true;
			$target = 'https://weid.info';
			$out['text'] = '<p>'._L('Please wait...').'</p><p><a href="'.$target.'">Goto '.$target.'...</a></p><script>window.location.href = '.js_escape($target).';</script>';
		 }elseif($id === 'oidplus:webfan_goto_webfan_home') {
		// 'oidplus:webfan_goto_frdl_home'
					$handled = true;
			$target = '?goto=oid%3A1.3.6.1.4.1.37553.8.1.8';
			$out['text'] = '<p>'._L('Please wait...').'</p><p><a href="'.$target.'">Goto '.$target.'...</a></p><script>window.location.href = '.js_escape($target).';</script>';
		}elseif ($id === 'oidplus:login_webfan') {
			$handled = true;
			$out['title'] = _L('Login using Webfan');
		//	$out['icon']  = OIDplus::webpath(__DIR__).'icon_big.png';
            $out['icon']  = OIDplus::baseConfig()->getValue('Webfan_OAUTH2_ICON_URL', 'https://webfan.de/favicon.ico');
			
			if (!OIDplus::baseConfig()->getValue('Webfan_OAUTH2_ENABLED', false)) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = _L('Webfan OAuth authentication is disabled on this system.');
				return;
			}
	
		

			$target =
				OIDplus::baseConfig()->getValue('Webfan_Nextcloud_Url', 'https://webfan.de').
				"/apps/oauth2/authorize".
                                "?response_type=code&".
				"client_id=".urlencode(OIDplus::baseConfig()->getValue('Webfan_OAUTH2_CLIENT_ID'))."&".
				//"scope=".implode(',', array('basic','user','realname','email','phone','address'))."&".
				"scope=".implode(',', array('email'))."&".
			//	"redirect_uri=".urlencode(OIDplus::webpath(__DIR__,false).'oauth.php')."&"
					//https://webfan.de/apps/registry/plugins/frdl/publicPages/801_login_webfan/oauth.php
	"redirect_uri=".urlencode(OIDplus::baseConfig()->getValue('Webfan_OAUTH2_REDIRECT_URI', 'https://registry.frdl.de/plugins/frdl/publicPages/801_login_webfan/oauth.php'))."&"
				."state=".urlencode($_COOKIE['csrf_token'])
					;
			$out['text'] = '<p>'._L('Please wait...').'</p><script>window.location.href = '.js_escape($target).';</script>';
		}
	}

	public function publicSitemap(&$out) {
		$out[] = 'oidplus:login_webfan';
	}

	public function tree(array &$json, ?string $ra_email = null, bool $nonjs = false, string $req_goto = ''): bool {
		$tree_icon =OIDplus::baseConfig()->getValue('Webfan_OAUTH2_ICON_URL', 'https://webfan.de/favicon.ico'); // default icon (folder)
		$weid_icon ='https://weid.info/favicon.ico'; 
			
		

		$item= [
			'id' => 'oidplus:resources$Tools/Whois.html',
			'icon' => 'plugins/viathinksoft/publicPages/300_search/img/main_icon16.png',
			'text' =>  'Whois Lookup',
		];
		array_unshift($json, $item);
		
		
		$item= [
			'id' => 'oidplus:webfan_goto_webfan_home',
			'icon' => $tree_icon,
			'text' =>  'Webfan Objects',
		];
		array_unshift($json, $item);
				

//	die(print_r($json,true));
			
		$item= [
			'id' => 'oidplus:weid_info',
			'icon' => $weid_icon,
			'text' =>  'WEID Documentation',
		];
		array_unshift($json, $item);
				
	
		//if(count(\OIDplus::authUtils()->loggedInRaList()))return true;
	if(count(OIDplus::authUtils()->loggedInRaList())){
		
	}else{
		
		$item= [
			'id' => 'oidplus:login_webfan',
			'icon' => $tree_icon,
			'text' =>  str_replace('Google', 'Webfan', _L('Login using Google')),
		];
		array_unshift($json, $item);
	}
			
			
	/*
		$item= [
			'id' => 'oidplus:system',
			'icon' => $tree_icon,
			'text' =>  'Home',
		];
		array_unshift($json, $item);		
		
		$json =(array)	ArrayHelper::insert(
			$json,
			$item, 
		 'Login using Google'
		); 
			*/
		//die(print_r(ArrayHelper::getHash( 'Webfan',$json)[0],true));
		
		
	  return true;
	}
	public function tree_search($request) {
		return false;
	}

	public function implementsFeature(string $id): bool {
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.5') return true; // alternativeLoginMethods
		return false;
	}

	public function alternativeLoginMethods() {
		$logins = array();
		if (OIDplus::baseConfig()->getValue('Webfan_OAUTH2_ENABLED', false)) {
			$logins[] = array(
				'oidplus:login_webfan',
				str_replace('Google', 'Webfan', _L('Login using Google')),
				//OIDplus::webpath(__DIR__).'treeicon.png'
				OIDplus::baseConfig()->getValue('Webfan_OAUTH2_ICON_URL', 'https://webfan.de/favicon.ico')
			);
			
			
		}
		return $logins;
	}
}
