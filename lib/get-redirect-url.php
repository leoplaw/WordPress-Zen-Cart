<?php 

/**
 * get_redirect_url()
 * Gets the address that the provided URL redirects to,
 * or FALSE if there's no redirect. 
 *
 * @param string $url
 * @return string
 * 
 * @author W-Shadow http://w-shadow.com/blog/2008/07/05/how-to-get-redirect-url-in-php/
 */


	function get_redirect_url($url){
		$redirect_url = null; 
	 
		$url_parts = @parse_url($url);
		if (!$url_parts) return false;
		if (!isset($url_parts['host'])) return false; //can't process relative URLs
		if (!isset($url_parts['path'])) $url_parts['path'] = '/';
	 
		$sock = fsockopen($url_parts['host'], (isset($url_parts['port']) ? (int)$url_parts['port'] : 80), $errno, $errstr, 30);
		if (!$sock) return false;
	 
		$request = "HEAD " . $url_parts['path'] . (isset($url_parts['query']) ? '?'.$url_parts['query'] : '') . " HTTP/1.1\r\n"; 
		$request .= 'Host: ' . $url_parts['host'] . "\r\n"; 
		$request .= "Connection: Close\r\n\r\n"; 
		fwrite($sock, $request);
		$response = '';
		while(!feof($sock)) $response .= fread($sock, 8192);
		fclose($sock);
	 
		if (preg_match('/^Location: (.+?)$/m', $response, $matches)){
			if ( substr($matches[1], 0, 1) == "/" )
				return $url_parts['scheme'] . "://" . $url_parts['host'] . trim($matches[1]);
			else
				return trim($matches[1]);
	 
		} else {
			return false;
		}
	 
	}
	 
	/**
	 * get_all_redirects()
	 * Follows and collects all redirects, in order, for the given URL. 
	 *
	 * @param string $url
	 * @return array
	 */
	function get_all_redirects($url){
		$redirects = array();
		while ($newurl = get_redirect_url($url)){
			if (in_array($newurl, $redirects)){
				break;
			}
			$redirects[] = $newurl;
			$url = $newurl;
		}
		return $redirects;
	}
	 
	/**
	 * get_final_url()
	 * Gets the address that the URL ultimately leads to. 
	 * Returns $url itself if it isn't a redirect.
	 *
	 * @param string $url
	 * @return string
	 */
	function get_final_url($url){
		$redirects = get_all_redirects($url);
		if (count($redirects)>0){
			return array_pop($redirects);
		} else {
			return $url;
		}
	}

	
/* Tried and tested function (follows javascript redirects as well):


function get_final_url( $url, $timeout = 5 )
{
	$url = str_replace( "&amp;", "&amp;", urldecode(trim($url)) );
 
	$cookie = tempnam ("/tmp", "CURLCOOKIE");
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1" );
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie );
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
	curl_setopt( $ch, CURLOPT_ENCODING, "" );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
	curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
	curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
	curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
	$content = curl_exec( $ch );
	$response = curl_getinfo( $ch );
	curl_close ( $ch );
 
	if ($response['http_code'] == 301 || $response['http_code'] == 302)
	{
		ini_set("user_agent", "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");
		$headers = get_headers($response['url']);
 
		$location = "";
		foreach( $headers as $value )
		{
			if ( substr( strtolower($value), 0, 9 ) == "location:" )
				return get_final_url( trim( substr( $value, 9, strlen($value) ) ) );
		}
	}
 
 
	if (	preg_match("/window\.location\.replace\('(.*)'\)/i", $content, $value) ||
			preg_match("/window\.location\=\"(.*)\"/i", $content, $value)
	)
	{
		return get_final_url ( $value[1] );
	}
	else
	{
		return $response['url'];
	}
}
 */
	
?>