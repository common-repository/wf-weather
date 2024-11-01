<?php

  function wf_weather_getCachedJsonData($language, $dist_id) {

    $optionNameDistrict = 'wf_stw_forecast_data_district';
    $optionNameSouthtyrol = 'wf_stw_forecast_data_southtyrol';
    $generalOptions = get_option( 'wf-weather-general' );

    if (isset($generalOptions['cacheExpiration']))
      $cacheExpiration = $generalOptions['cacheExpiration'];
    else
      $cacheExpiration = 3600;

    $jsonData = false;
    if ($dist_id) {
      //getting data for specific district
      if(get_option($optionNameDistrict) && isset(get_option($optionNameDistrict)[$language][$dist_id])){
        //we have allready cached data for this district. Check for cache expiration
        if(current_time('timestamp') - get_option($optionNameDistrict)[$language][$dist_id]['timestamp'] > $cacheExpiration){
          //it's too old. Get fresh data and save it to DB
          $jsonData = wf_weather_getJsonData($language, $dist_id);
          if($jsonData)
            update_option($optionNameDistrict, array($language => array($dist_id => array('data' => $jsonData, 'timestamp' => current_time('timestamp')))));
        }
        $jsonData = get_option($optionNameDistrict)[$language][$dist_id]['data'];
      } else {
        //we have no cached data for this district. Get fresh data and save it to DB
        $jsonData = wf_weather_getJsonData($language, $dist_id);
        if($jsonData)
          update_option($optionNameDistrict, array($language => array($dist_id => array('data' => $jsonData, 'timestamp' => current_time('timestamp')))));
      }
    } else {
      //getting general south-tyrol data (no district)
      if(get_option($optionNameSouthtyrol) && isset(get_option($optionNameSouthtyrol)[$language])){
        //we have allready saved data. Check for cache expiration
        if(current_time('timestamp') - get_option($optionNameSouthtyrol)[$language]['timestamp'] > $cacheExpiration){
          //it's too old. Get fresh data and save it to DB
          $jsonData = wf_weather_getJsonData($language, null);
          if($jsonData)
            update_option($optionNameSouthtyrol, array($language => array('data' => $jsonData, 'timestamp' => current_time('timestamp'))));
        }
        $jsonData = get_option($optionNameSouthtyrol)[$language]['data'];
      } else {
        //we have no saved data. Get fresh data and save it to DB
        $jsonData = wf_weather_getJsonData($language, null);
        if($jsonData)
          update_option($optionNameSouthtyrol, array($language => array('data' => $jsonData, 'timestamp' => current_time('timestamp'))));
      }
    }
    return $jsonData;
  }

  function wf_weather_getJsonData($language, $dist_id) {

    $jsonData = false;

    $generalOptions = get_option( 'wf-weather-general' );

    if (isset($generalOptions['apiUsername']) && isset($generalOptions['apiPassword'])) {
      $apiUsername = $generalOptions['apiUsername'];
      $apiPassword = $generalOptions['apiPassword'];
    } else {
      return false;
    }


    $curl = curl_init();

    if ($dist_id) {
      $request_url = 'https://wetter.ws.siag.it/Agriculture_V1.svc/web/getLastBulletin?lang='.$language . '&dist=' . $dist_id;
    } else {
      $request_url = 'https://wetter.ws.siag.it/Weather_V1.svc/web/getLastProvBulletin?lang='.$language;
    }

    curl_setopt_array($curl, array(
        CURLOPT_HEADER => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_URL => $request_url,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => "$apiUsername:$apiPassword"
    ));
    if(!$response = curl_exec($curl)){
      trigger_error(curl_error($curl));
      return false;
    }
    if(curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200)
      return false;
    $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $body = substr($response, $header_size);

    $xmlNode = simplexml_load_string($body);
    $arrayData = wf_stw_weather_xmlToArray($xmlNode);

    if (isset($arrayData['provBulletin']))
      $jsonData = json_encode($arrayData['provBulletin']);
    elseif (isset($arrayData['agriculturalBulletin']))
      $jsonData = json_encode($arrayData['agriculturalBulletin']);

    return $jsonData;

  }

  function wf_stw_weather_xmlToArray($xml, $options = array()) {

	    $defaults = array(
	        'namespaceSeparator' => ':',//you may want this to be something other than a colon
	        'attributePrefix' => '@',   //to distinguish between attributes and nodes with the same name
	        'alwaysArray' => array(),   //array of xml tag names which should always become arrays
	        'autoArray' => true,        //only create arrays for tags which appear more than once
	        'textContent' => '$',       //key used for the text content of elements
	        'autoText' => true,         //skip textContent key if node has no attributes or child nodes
	        'keySearch' => false,       //optional search and replace on tag and attribute names
	        'keyReplace' => false       //replace values for above search values (as passed to str_replace())
	    );

	    $options = array_merge($defaults, $options);
	    $namespaces = $xml->getDocNamespaces();
	    $namespaces[''] = null; //add base (empty) namespace

	    //get attributes from all namespaces
	    $attributesArray = array();
	    foreach ($namespaces as $prefix => $namespace) {
	        foreach ($xml->attributes($namespace) as $attributeName => $attribute) {
	            //replace characters in attribute name
	            if ($options['keySearch']) $attributeName =
	                    str_replace($options['keySearch'], $options['keyReplace'], $attributeName);
	            $attributeKey = $options['attributePrefix']
	                    . ($prefix ? $prefix . $options['namespaceSeparator'] : '')
	                    . $attributeName;
	            $attributesArray[$attributeKey] = (string)$attribute;
	        }
	    }

	    //get child nodes from all namespaces
	    $tagsArray = array();
	    foreach ($namespaces as $prefix => $namespace) {
	        foreach ($xml->children($namespace) as $childXml) {
	            //recurse into child nodes
	            $childArray = wf_stw_weather_xmlToArray($childXml, $options);

              foreach ($childArray as $key => $value) {
                $childTagName = $key;
                $childProperties = $value;
              }

	            //replace characters in tag name
	            if ($options['keySearch']) $childTagName =
	                    str_replace($options['keySearch'], $options['keyReplace'], $childTagName);
	            //add namespace prefix, if any
	            if ($prefix) $childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;

	            if (!isset($tagsArray[$childTagName])) {
	                //only entry with this key
	                //test if tags of this type should always be arrays, no matter the element count
	                $tagsArray[$childTagName] =
	                        in_array($childTagName, $options['alwaysArray']) || !$options['autoArray']
	                        ? array($childProperties) : $childProperties;
	            } elseif (
	                is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName])
	                === range(0, count($tagsArray[$childTagName]) - 1)
	            ) {
	                //key already exists and is integer indexed array
	                $tagsArray[$childTagName][] = $childProperties;
	            } else {
	                //key exists so convert to integer indexed array with previous value in position 0
	                $tagsArray[$childTagName] = array($tagsArray[$childTagName], $childProperties);
	            }
	        }
	    }

	    //get text content of node
	    $textContentArray = array();
	    $plainText = trim((string)$xml);
	    if ($plainText !== '') $textContentArray[$options['textContent']] = $plainText;

	    //stick it all together
	    $propertiesArray = !$options['autoText'] || $attributesArray || $tagsArray || ($plainText === '')
	            ? array_merge($attributesArray, $tagsArray, $textContentArray) : $plainText;

	    //return node as array
	    return array(
	        $xml->getName() => $propertiesArray
	    );

	}

  function south_tyrol_weather_get_districts() {
    return [
        3 => [
            'label' => 'Val Venosta',
            'path' => 'weather/south-tyrol/val-venosta'
        ],
        2 =>  [
            'label' => 'Burgraviato',
            'path' => 'weather/south-tyrol/burgraviato'
        ],
        1 =>  [
            'label' => 'Bolzano, Oltradige e Bassa Atesina',
            'path' => 'weather/south-tyrol/bolzano-oltradige-bassa-atesina'
        ],
        4 =>  [
            'label' => 'Val d´Isarco e Val Sarentino',
            'path' => 'weather/south-tyrol/val-d-isarco-val-sarentino'
        ],
        5 =>  [
            'label' => 'Alta Val d\'Isarco',
            'path' => 'weather/south-tyrol/alta-val-d-isarco'
        ],
        6 =>  [
            'label' => 'Val Pusteria',
            'path' => 'weather/south-tyrol/val-pusteria'
        ],
        7 =>  [
            'label' => 'Ladinia - Dolomiti',
            'path' => 'weather/south-tyrol/ladinia-dolomiti'
        ]
    ];
  }

  function south_tyrol_weather_format_date($timestamp) {
    return format_date($timestamp, 'custom', 'D, j.n.Y');
  }

?>
