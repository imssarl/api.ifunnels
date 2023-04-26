<?php 

class Project_Pagebuilder_Screenshot {
	// private $access_key = '2e94ee';
	// private $secret_keyword = 'lksejhfefghug75765';

	private $access_key = "h40OiC9ShIzQreohJq9114QU561ihEzcRdYeNQ3JarCiVNdxk4";
	private $secret_keyword = "TOP_SECRET";

	/**
	 * @param $url
	 * @param $fileName
	 * @return $fileName
	 */
	public function make_screenshot($url, $fileName = '', $size = '1920x1080', $destination) {
		$screen = $this->screenshotmachine($url,
			[
				'dimension'     => $size,
				'device'        => 'desktop',
				'format'        => 'jpg',
				'cacheLimit'    => 0,
				'delay'			=> 0,
				'timeout'       => 300,
				'crop' 			=> true
			]);

		$ch = curl_init ($screen);

		if ( $ch ) {
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
			$image = curl_exec($ch);

			if ( $image ) {
				curl_close ($ch);

				$fp = fopen($destination . $fileName, 'w');

				if ( $fp ) {
					fwrite($fp, $image);
					fclose($fp);

					return $fileName;
				} 
				else {
					
					return false;
				}
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}	
	}

	/**
	 * @param $url
	 * @param $args
	 * @return string
	 */
	private function screenshotmachine($url, $args) {
		//access key
		$access_key = $this->access_key;

		//secret keyword
		$secret_key = md5($url . $this->secret_keyword);

		//encode URL
		$params['url'] = urlencode($url);

		$params += $args;

		//create the query string based on the options
		foreach($params as $key => $value) { $parts[] = "$key=$value"; }

		//compile query string
		$query = implode("&", $parts);

		//call API and return the image
		return "http://api.pagestead.com/api/v1/screenshot/create?api_key=$access_key&hash=$secret_key&$query";
	}

}