<?php

// Renders a set of parameters (Highcharts-friendly) as a Google Chart

class Api_GoogleChart {


    static function build( $data, $params = array()) {

        // Useful stuff
        $httpd = @$_SERVER['HTTPS'];
        $types = array('line'=>'lc','column'=>'bv','map'=>'map');

        // 1. Hydrate parameters
        $paramDefaults = array(
            'width' => 320,
            'height' => 240,
            'colors' => array('#3399CC','#F00000','#00F000','#AA8800','#0088AA'),
            'chart' => array('defaultSeriesType'=>'line')
        );

        $fields = array_keys($data[0]);
        $paramDefaults['column'] = $fields[0];
        $paramDefaults['series'] = array();
        $paramDefaults['xaxis'] = array('title'=>$fields[0]);
        $paramDefaults['yaxis'] = @array('title'=>$fields[1]);
        foreach (array_diff($fields,array($fields[0])) as $field) {
            $paramDefaults['series'][] = array('data'=>$field);
        }
        $params = array_merge($paramDefaults, $params);
        
        // 2. Create base chart
        $chart = new Api_GoogleChart_GChart();
        
        /**
         * This will use Extended Encoding.
         * {@link http://code.google.com/apis/chart/image/docs/data_formats.html#extended}
         */
        $chart->setEncodingType('e');
        
        $chart->setDimensions($params['width'], $params['height']);
        $type = $params['chart']['defaultSeriesType'];

        // 3. Add series, title, legend, etc.
        if (!isset($params['series'][0])) {
            $params['series'] = array($params['series']);
        }

        // Transfer colors into series
        foreach ($params['colors'] as $i=>$color) {
            if (isset($params['series'][$i]) && !isset($params['series'][$i]['color'])) {
                $params['series'][$i]['color'] = $color;
            }
        }
        unset($params['colors']);

        if (isset($params['title'])) {
            if ('string' !== gettype($params['title'])) {
                $params['title'] = $params['title']['text'];
            }
            $chart->setTitle($params['title']);
        }

        $params['series'] = array_reverse($params['series']);

        if (!empty($params['trend'])) {
            self::_addTrends( $data, $params['series'], $params['trend']);
        }


        // 4. Build data

        $ymin = PHP_INT_MAX;
        $ymax = -1 * PHP_INT_MAX;

        $fields = array();
        $ydata = array();
        foreach ($params['series'] as $series) {
                $fields[] = $series['data'];
                
                $xdata = array();
                $row = array();
                
                foreach ($data as $record) {
                    $val = $record[$series['data']];                                        
                    $row[] = $val;
                    
                    if (isset($params['column'])) {
                        $xdata[] = $record[$params['column']];
                    }
                    
                    if (is_numeric($val)) {
                        $ymin = min($ymin, $val);
                        $ymax = max($ymax, $val);
                    }
                }
                
                foreach ($row as &$value) {
                    /**
                     * We divide by 4065 because that is the largest value that the Google Chart API can
                     * take for the Extended Encoding Format.
                     * 
                     * The largest resulting value should be the integer 4065.
                     * 
                     * @link http://code.google.com/apis/chart/image/docs/data_formats.html#extended
                     */
                    $value = ($ymax > 0) ? (int)(($value * 4065) / $ymax) : 0;
                }                    
                
                //$chart->addDataSet($ydata);
                $ydata[] = $row;
                if (isset($series['type'])) { $type = $series['type']; }

        }
        $params['min'] = isset($params['min']) ? $params['min'] : $ymin;
        $params['max'] = isset($params['max']) ? $params['max'] : $ymax;

        // Normalize
        foreach ($ydata as $row) {
            /*
            foreach ($row as &$val) {
                $val = (float)($val);
            }
            //*/
            $chart->addDataSet($row);
        }


        // 5. Set axes and ranges (based on data)
        // TODO: Allow formats
        $visaxes = array();
        if (!empty($params['xaxis'])) {
            $visaxes[] = 'x';
            if ('3hour' == $params['xaxis']) {
                $chart->addAxisLabel(0, explode('|','12AM|3|6|9|12|3|6|9|12AM'));
            } else if ('4hour' == $params['xaxis']) {
                $chart->addAxisLabel(0, explode('|','12AM|4|8|12|16|20|12AM'));
            } else {
                $chart->addAxisLabel(0, $xdata);
            }
        }
        if (!empty($params['yaxis'])) {
            $visaxes[] = 'y';
            $chart->addAxisLabel(1,array((float)round($params['min'],2),(float)round($params['max'],2)));
            //$chart->addAxisRange(1,$params['min'],$params['max']);
        }


        // 6. Set colors
        // TODO: Allow full rgb.txt/hex3 colors
        $colors = array();
        foreach ($params['series'] as $series) {
            $color = false;
            $color = $series['color'];
            if (0===strpos($color,'#')) {
                $color = substr($color,1);
                $colors[] = $color;
            }
        }

        // 7. Set line widths
        $lineWidths = array();
        foreach ($params['series'] as $series) {
            if (isset($series['lineWidth'])) {
                $lineWidth = floor(preg_replace('/[^0-9\.]/','',$series['lineWidth']));
                $lineWidths[] = $lineWidth;
            }
        }


        $chart->setVisibleAxes($visaxes);

        $chart->setDataRange($params['min'],$params['max']);
        $chart->setProperty('cht', $types[$type]);
        $chart->setColors($colors);
        $chart->setProperty('chls',urlencode( implode('|',$lineWidths)) );
        

        if (!empty($params['legend'])) {
            $chart->setLegend( $fields );
        }


        if ('map' == $type) {
            //cht=map:fixed=<bottom_border_lat>,<left_border_long>,<top_border_lat>,<right_border_long>
            $types[$type]="t";
            $chart->setProperty('cht', $types[$type]);
            $chart->setProperty('chtm','world');
            $chart->setProperty('chs','440x220');
            $chart->setProperty('chco',implode(',',$colors));
            $chart->setProperty('chld',implode('|',$xdata));
            $chart->setProperty('chco','FFFFFF,DEFED1,267114');
            $chart->setProperty('chf','bg,s,EAF7FE');
        }


        // 6. Dump
        return $chart;
    }

    static function render( $data, $params = array() ) {

        $chart = self::build($data, $params);
        
        try {
            $chart->renderImage(true);
        } catch (Exception $e) {
            header('Location: ' . $chart->getUrl() );
        }

    }

    static function debug( $data, $params = array()) {
        $chart = self::build($data, $params);
        return $chart->getUrl();
    }

    static function renderRedirect( $data, $params = array()) {

        $chart = self::build($data, $params);
        header('Location: ' . $chart->getUrl() );

    }

    static function _addTrends( &$data, &$allSeries, $trend ) {

        $sourceSeries = $allSeries;
        foreach ($sourceSeries as $n=>$series) {

            $oldName = $series['data'];
            $newName = $series['data'] . '_' . $trend;
            $split = ceil(($trend-1)/2);
            $maxrecord = count($data)-1;

            // 1. Build trends
            for ($i=0;$i<=$maxrecord;$i++) {
                $stack = array();
                for ($j=max(0,$i-$split);$j<=min($maxrecord,$i+$split);$j++) {
                    $stack[] = $data[$j][$oldName];
                }
                if (count($stack)) {
                    $data[$i][$newName] = round(array_sum($stack)/count($stack),2);
                }
            }

            // 2. Append new series definition to end
            $newSeries = $series;
            $newSeries['data'] = $newName;
            $newSeries['lineWidth'] = '3px';

            $allSeries[$n]['lineWidth'] = '1px';
            $allSeries[$n]['color'] = self::brighten($allSeries[$n]['color']);
            
            // TODO: Modify width of lines
            $allSeries[] = $newSeries;
        }
    }

    // Render clear PNG pixel
    static function pixel() {
        header('Cache-Control: no-cache');
        header('Content-Type: image/png');
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
        exit;
    }

    static function brighten($rgb) {
        if (preg_match('/([0-9a-f][0-9a-f])([0-9a-f][0-9a-f])([0-9a-f][0-9a-f])/i',$rgb,$triad)) {
            list($r,$g, $b) = array_splice($triad,1);
            $r = str_pad(dechex(round((255 + hexdec($r)) / 2)),2,'0',STR_PAD_LEFT);
            $g = str_pad(dechex(round((255 + hexdec($g)) / 2)),2,'0',STR_PAD_LEFT);
            $b = str_pad(dechex(round((255 + hexdec($b)) / 2)),2,'0',STR_PAD_LEFT);
            $color = "#$r$g$b";
        }
        return $color;
    }

}
