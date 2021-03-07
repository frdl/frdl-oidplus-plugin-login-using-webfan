<?php

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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusPagePublicLoginWebfan extends OIDplusPagePluginPublic {

	public function action($actionID, $params) {
		throw new OIDplusException(_L('Unknown action ID'));
	}

	public function init($html=true) {
		// Nothing
	}

	public function gui($id, &$out, &$handled) {
		if ($id === 'oidplus:login_webfan') {
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
				"https://".OIDplus::baseConfig()->getValue('Webfan_OAUTH2_HPS1_SUBDOMAIN', 'frdl').".webfan.de/auth/authorize/?".
				"response_type=code&".
				"client_id=".urlencode(OIDplus::baseConfig()->getValue('Webfan_OAUTH2_CLIENT_ID'))."&".
				"scope=".implode(',', array('basic','user','realname','email','phone','address'))."&".
				"redirect_uri=".urlencode(OIDplus::webpath(__DIR__,false).'oauth.php')."&".
				"state=".urlencode($_COOKIE['csrf_token']);
			$out['text'] = '<p>'._L('Please wait...').'</p><script>window.location.href = '.js_escape($target).';</script>';
		}
	}

	public function publicSitemap(&$out) {
		$out[] = 'oidplus:login_webfan';
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		return true;
	}

	public function tree_search($request) {
		return false;
	}

	public function implementsFeature($id) {
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
