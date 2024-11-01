<?php
  function wf_weather_forecast_handler( $atts ) {
    $a = shortcode_atts( array(
        'district' => null,
        'lang' => null
    ), $atts );

    if(!isset($a['lang'])) {
      if (function_exists('wf_get_language')) {
        if(wf_get_language() == 'it' || wf_get_language() == 'de')
          $a['lang'] = wf_get_language();
        else
          $a['lang'] = 'de';
      }
    }

    if(isset($a['district']))
      $data = wf_weather_getCachedJsonData($a['lang'],$a['district']);
    else {
      $data = wf_weather_getCachedJsonData($a['lang'],null);
    }
    $jsonData = json_decode($data);

    $weatherForecastData = null;

    if(isset($jsonData->forecast)) {
      //data for specific district
      $i = 1; foreach ($jsonData->forecast as $key => $value) :
        if(isset($value->date))
          $weatherForecastData[$key]['date'] = $value->date;
        if(isset($value->symbol->description))
          $weatherForecastData[$key]['symbol']['description'] = $value->symbol->description;
        if(isset($value->symbol->imageURL))
          $weatherForecastData[$key]['symbol']['imageURL'] = $value->symbol->imageURL;
        if(isset($value->temperature->max))
          $weatherForecastData[$key]['temperature']['max'] = $value->temperature->max;
        if(isset($value->temperature->min))
          $weatherForecastData[$key]['temperature']['min'] = $value->temperature->min;
        if(isset($value->freeze))
          $weatherForecastData[$key]['freeze'] = $value->freeze;
        if(isset($value->rainFrom))
          $weatherForecastData[$key]['rainFrom'] = $value->rainFrom;
        if(isset($value->rainTo))
          $weatherForecastData[$key]['rainTo'] = $value->rainTo;
        if(isset($value->part1))
          $weatherForecastData[$key]['part1'] = $value->part1;
        if(isset($value->part2))
          $weatherForecastData[$key]['part2'] = $value->part2;
        if(isset($value->part3))
          $weatherForecastData[$key]['part3'] = $value->part3;
        if(isset($value->part4))
          $weatherForecastData[$key]['part4'] = $value->part4;
        if(isset($value->thunderStorm))
          $weatherForecastData[$key]['thunderStorm'] = $value->thunderStorm;
      endforeach;
    }elseif(isset($jsonData->dayForecast)){
      //data for south tyrol (global)
      $i = 1; foreach ($jsonData->dayForecast as $key => $value) :
        if(isset($value->date))
          $weatherForecastData[$key]['date'] = $value->date;
        if(isset($value->symbol->description))
          $weatherForecastData[$key]['symbol']['description'] = $value->symbol->description;
        if(isset($value->symbol->imageURL))
          $weatherForecastData[$key]['symbol']['imageURL'] = $value->symbol->imageURL;
        if(isset($value->tempMax->max))
          $weatherForecastData[$key]['temperature']['max'] = $value->tempMax->max;
        if(isset($value->tempMin->min))
          $weatherForecastData[$key]['temperature']['min'] = $value->tempMin->min;
      endforeach;
    }

    if(isset($weatherForecastData)) {
      ob_start();
      ?>
      <div class="wf-weather-forecast col-3">
        <h2 class="wf-title"><?php _e('Outlook for the next days', 'wf-weather'); ?></h2>
        <div class="container">
          <?php $i = 1; foreach ($weatherForecastData as $key => $value) : ?>
            <div class="forecast">
              <span class="title" title="<?php echo mysql2date('l, j. M. Y', $value['date']); ?>"><?php echo mysql2date('l', $value['date']); ?></span>
              <div class="section general">
                <span class="data"><?php echo $value['symbol']['description']; ?></span>
                <img src="<?php echo $value['symbol']['imageURL']; ?>" />
              </div>
              <div class="clear"></div>
              <?php if(isset($value['temperature']['min']) && isset($value['temperature']['max'])) : ?>
                <div class="section temperature">
                  <span class="title"><?php _e('Temperature', 'wf-weather') ?></span>
                  <span class="data"></span><span class="temp min"><?php echo $value['temperature']['min']; ?></span> | <span class="temp max"><?php echo $value['temperature']['max']; ?></span>
                </div>
                <div class="clear"></div>
              <?php endif; ?>
              <?php if(isset($value['rainFrom']) && isset($value['rainTo'])) : ?>
                <div class="section rainfall">
                  <span class="title"><?php _e('Rainfall', 'wf-weather') ?></span>
                  <span class="data rainfall"><?php echo $value['rainFrom']; ?> - <?php echo $value['rainTo']; ?> <?php _e('mm', 'wf-weather') ?></span>
                  <div class="rainfall-probability">
                    <span class="title"><?php _e('Probability', 'wf-weather') ?></span>
                    <?php if(isset($value['part1'])) : ?>
                      <span class="data bar-container part1"><span class="bar" style="width:<?php echo round((100/3)*$value['part1']); ?>%;"></span><span class="percentage"><?php _e('6 am','wf-weather') ?></span></span>
                    <?php endif; ?>
                    <?php if(isset($value['part2'])) : ?>
                    <span class="data bar-container part2"><span class="bar" style="width:<?php echo round((100/3)*$value['part2']); ?>%;"></span><span class="percentage"><?php _e('12 am','wf-weather') ?></span></span>
                    <?php endif; ?>
                    <?php if(isset($value['part3'])) : ?>
                    <span class="data bar-container part3"><span class="bar" style="width:<?php echo round((100/3)*$value['part3']); ?>%;"></span><span class="percentage"><?php _e('6 pm','wf-weather') ?></span></span>
                    <?php endif; ?>
                    <?php if(isset($value['part4'])) : ?>
                    <span class="data bar-container part4"><span class="bar" style="width:<?php echo round((100/3)*$value['part4']); ?>%;"></span><span class="percentage"><?php _e('12 pm','wf-weather') ?></span></span>
                  <?php endif; ?>
                  </div>
                </div>
                <div class="clear"></div>
              <?php endif; ?>
              <?php if(isset($value['thunderStorm'])) : ?>
                <div class="section thunderstorm">
                  <span class="title"><?php _e('Thunderstorm', 'wf-weather') ?></span>
                  <span class="data bar-container thunderstorm"><span class="bar" style="width:<?php echo round((100/3)*$value['thunderStorm']); ?>%;"></span><span class="percentage"><?php echo $value['thunderStorm']; ?>/3</span></span>
                </div>
                <div class="clear"></div>
              <?php endif; ?>
              <?php if(isset($value['freeze'])) : ?>
                <div class="section freeze">
                  <span class="title"><?php _e('Freeze', 'wf-weather') ?></span>
                  <span class="data bar-container freeze"><span class="bar" style="width:<?php echo round((100/3)*$value['freeze']); ?>%;"></span><span class="percentage"><?php echo $value['freeze']; ?>/3</span></span>
                </div>
              <?php endif; ?>
            </div>
          <?php if ($i++ == 3) break; endforeach; ?>
        </div>
      </div>
      <div class="copyright"><small><p><a href="<?php _e('http://www.provinz.bz.it/wetter', 'wf-weather') ?>" target="_blank"><?php _e('© Landeswetterdienst Südtirol', 'wf-weather') ?></a></p></small></div>
      <?php
      $output_string = ob_get_contents();
      ob_end_clean();
      return $output_string;
    }
  }

  function wf_weather_text_handler( $atts ) {
    $a = shortcode_atts( array(
        'lang' => null,
        'day' => 'today',
    ), $atts );

    if(!isset($a['lang'])) {
      if (function_exists('wf_get_language')) {
        if(wf_get_language() == 'it' || wf_get_language() == 'de')
          $a['lang'] = wf_get_language();
        else
          $a['lang'] = 'de';
      }
    }

    $data = wf_weather_getCachedJsonData($a['lang'],null);

    $jsonData = json_decode($data);

    $weatherTextData = null;

    if(isset($jsonData->today)){
      //data for south tyrol (global)
      $weatherTextData['today']['date'] = $jsonData->today->date;
      $weatherTextData['today']['title'] = $jsonData->today->title;
      $weatherTextData['today']['conditions'] = $jsonData->today->conditions;
      $weatherTextData['today']['weather'] = $jsonData->today->weather;
      $weatherTextData['today']['temperatures'] = $jsonData->today->temperatures;
      $weatherTextData['today']['imageURL'] = $jsonData->today->imageURL;
      //data for south tyrol (global - tomorrow)
      $weatherTextData['tomorrow']['date'] = $jsonData->tomorrow->date;
      $weatherTextData['tomorrow']['title'] = $jsonData->tomorrow->title;
      $weatherTextData['tomorrow']['conditions'] = $jsonData->tomorrow->conditions;
      $weatherTextData['tomorrow']['weather'] = $jsonData->tomorrow->weather;
      $weatherTextData['tomorrow']['temperatures'] = $jsonData->tomorrow->temperatures;
      $weatherTextData['tomorrow']['imageURL'] = $jsonData->tomorrow->imageURL;
    }

    if(isset($weatherTextData)) {
      if($a['day'] == 'tomorrow') {
        $weatherText = $weatherTextData['tomorrow'];
        $weatherHeadline = __('Tomorrow’s weather', 'wf-weather');
      } else {
        $weatherText = $weatherTextData['today'];
        $weatherHeadline = __('Today’s weather', 'wf-weather');
      }
      ob_start();
      ?>
      <div class="wf-weather-text">
        <img src="<?php echo $weatherText['imageURL']; ?>" alt="<?php echo $weatherHeadline; ?>" class="today">
        <div class="title">
          <h2 class="wf-title"><?php echo $weatherText['title']; ?></h2>
          <span class="date" title="<?php echo mysql2date('l, j. M. Y', $weatherText['date']); ?>"><?php echo mysql2date('l, j. M.', $weatherText['date']); ?></span>
        </div>
        <h3><?php _e('General weather situation', 'wf-weather'); ?></h3>
        <?php if(isset($weatherText['conditions'])) : ?><p><?php echo $weatherText['conditions']; ?></p><?php endif; ?>
        <h3><?php echo $weatherHeadline; ?></h3>
        <p>
          <?php if(isset($weatherText['weather'])) : ?><?php echo $weatherText['weather']; ?><?php endif; ?>
          <?php if(isset($weatherText['temperatures'])) : ?><?php echo $weatherText['temperatures']; ?><?php endif; ?>
        </p>
      </div>
      <?php
      $output_string = ob_get_contents();
      ob_end_clean();
      return $output_string;
    }
  }

  add_shortcode( 'wf_stw_weather_forecast', 'wf_weather_forecast_handler' );
  add_shortcode( 'wf_weather_forecast', 'wf_weather_forecast_handler' );
  add_shortcode( 'wf_weather_text', 'wf_weather_text_handler' );
 ?>
