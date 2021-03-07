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

# More information about the OAuth2 implementation:
# - https://developers.google.com/identity/protocols/oauth2/openid-connect

require_once __DIR__ . '/../../../includes/oidplus.inc.php';

OIDplus::init(true);
set_exception_handler(array('OIDplusGui', 'html_exception_handler'));

if (!OIDplus::baseConfig()->getValue('Webfan_OAUTH2_ENABLED', false)) {
	throw new OIDplusException(_L('Webfan OAuth authentication is disabled on this system.'));
   return;
}

if (!isset($_GET['code'])) die();
if (!isset($_GET['state'])) die();

if ($_GET['state'] != $_COOKIE['csrf_token']) {
	die('Invalid CSRF token');
}

if (!function_exists('curl_init')) {
	die(_L('The "%1" PHP extension is not installed at your system. Please enable the PHP extension <code>%2</code>.','CURL','php_curl'));
}

$ch = curl_init();
if (ini_get('curl.cainfo') == '') curl_setopt($ch, CURLOPT_CAINFO, OIDplus::localpath() . '3p/certs/cacert.pem');
curl_setopt($ch, CURLOPT_URL,"https://".OIDplus::baseConfig()->getValue('Webfan_OAUTH2_HPS1_SUBDOMAIN', 'frdl').".webfan.de/auth/token/");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,
	"grant_type=authorization_code&".
	"code=".$_GET['code']."&".
	"redirect_uri=".urlencode(OIDplus::webpath(__DIR__,false).'oauth.php')."&".
	"client_id=".urlencode(OIDplus::baseConfig()->getValue('Webfan_OAUTH2_CLIENT_ID'))."&".
	"client_secret=".urlencode(OIDplus::baseConfig()->getValue('Webfan_OAUTH2_CLIENT_SECRET'))
);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$cont = curl_exec($ch);
curl_close($ch);

// Decode JWT "id_token"
// see https://medium.com/@darutk/understanding-id-token-5f83f50fa02e
// Note: We do not need to verify the signature because the token comes directly from Google
$data = json_decode($cont,true);
//$data=(array)$data;

if (isset($data['error']) || !isset($data['access_token'])) {
	throw new OIDplusException(_L('Error receiving the authentication token from %1: %2','Webfan',$data['error'].' '.$data['error_description']));
   return;
}
//$id_token = $data['id_token'];
$access_token = $data['access_token'];



list($header,$payload,$signature) = explode('.', $access_token);

$data = json_decode(base64_decode($payload),true);


		// Query user infos
		$ch = curl_init('https://'.OIDplus::baseConfig()->getValue('Webfan_OAUTH2_HPS1_SUBDOMAIN', 'frdl').'.webfan.de/auth/user/'); // Initialise cURL
		if (ini_get('curl.cainfo') == '') curl_setopt($ch, CURLOPT_CAINFO, OIDplus::localpath() . '3p/certs/cacert.pem');
		$data_string = '';
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Length: ' . strlen($data_string),
			"Authorization: Bearer ".$access_token
		));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$result = curl_exec($ch);
		curl_close($ch);
		$data = json_decode($result,true);



 if (!is_array($data) || !isset($data['email']) || !isset($data['email_verified']) || $data['email_verified'] != 'true') {
 	throw new OIDplusException('The email address was not verified. Please verify it first!');
   return;
 }
$email = $data['email'];

    $ra = new OIDplusRA($email);
	if (!$ra->existing()) {
		 $ra->register_ra(null); 
		$personal_name = $data['name']; 
		$ra_name = $data['displayName']; 
		OIDplus::db()->query("update ###ra set ra_name = ?, personal_name = ? where email = ?", array($ra_name, $personal_name, $email));
		OIDplus::logger()->log("[INFO]RA($email)!", "RA '$email' was created because of successful Webfan OAuth2 login");		
	}




	OIDplus::logger()->log("[OK]RA($email)!", "RA '$email' logged in via Webfan OAuth2");
	OIDplus::authUtils()::raLogin($email);

	OIDplus::db()->query("UPDATE ###ra set last_login = ".OIDplus::db()->sqlDate()." where email = ?", array($email));

	// Go back to OIDplus

	header('Location:'.OIDplus::webpath(null,false));
    die('<a href="'.OIDplus::webpath(null,false).'">'.OIDplus::webpath(null,false).'...</a>');
