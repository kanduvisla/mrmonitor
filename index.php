<html>
<head>
    <title>Mr. Monitor</title>
    <meta charset="utf-8"/>
	<link rel="stylesheet" href="stylesheets/screen.css" />
    <!-- Refresh every 5 minutes: -->
    <meta http-equiv="refresh" content="300">
</head>
<body>
<?php
    function sortArrayByUrl($arr)
    {
        usort($arr, function($a, $b){
            return strcmp(str_replace('www.', '', $a['url']), str_replace('www.', '', $b['url']));
        });
    }

    if(file_exists('results.csv'))
    {
        // Get the number of lines:
        $linecount = -1;
        $file = fopen('results.csv', "r");
        while(!feof($file)){
          $line = fgets($file);
          $linecount++;
        }
        fclose($file);

        $file = fopen('results.csv', 'r');
        $headers = fgetcsv($file);
        $happy = true;
        $error = false;
        $results0 = array();
        $results1 = array();
        $results2 = array();
        while($url = fgetcsv($file))
        {
            $info = array_combine($headers, $url);
            if($info['success'] == 0) { $results0[] = $info; }
            if($info['success'] == 1) { $results1[] = $info; }
            if($info['success'] == 2) { $results2[] = $info; }
        }
        fclose($file);
        // Then by name:
        sortArrayByUrl($results0);
        sortArrayByUrl($results1);
        sortArrayByUrl($results2);
        $results = array_merge($results0, $results2, $results1);
        foreach($results as $info)
        {
            // $info['success'] == '1' ? 'success' : 'error';
            $class = '';
            switch($info['success']) {
                case '0' :
                    $class = 'error';
                    break;
                case '1' :
                    $class = 'success';
                    break;
                case '2' :
                    $class = 'warning';
                    break;
            }
            $warning = false;
            if($info['success'] == '0') {
                $happy = false;
                $error = true;
            }
            // Check for slow speed:
            if($info['time_first'] > 0.5)
            {
                $happy = false;
                $warning = true;
            }
            if($warning) {
                $class .= ' warning';
            }
            if($linecount > 25) { $class .= ' count50'; }
            if($linecount > 50) { $class .= ' count100'; }
            if($linecount > 100) { $class .= ' count200'; }
            if($linecount > 200) { $class .= ' count400'; }
            $message = implode(', ', array_filter(explode('||', $info['message'])));

            ?>
                <div class="site <?php echo $class; ?>">
                    <p class="url"><?php echo str_replace(array('http://', 'www.'), '', $info['url']); ?></p>
                    <p class="code"><?php echo $info['code']; ?></p>
                    <?php if(!empty($info['time_first']) && !empty($info['time_total'])): ?>
                    <p class="time"><?php echo
                            number_format($info['time_first'], 3, ',', '.'). ' sec. to fb'; ?></p>
                    <?php endif; ?>
                    <p class="message"><?php echo $message; ?></p>
                </div>
            <?php
        }

        if($happy)
        {
            echo '<div id="happy">☺</div>';
        }

        if($error)
        {
            /*
            ?>
                <div id="error">
					<audio src="error.mp3" autoplay="true"></audio>
                </div>
            <?php
            */
        }

    }
?>
</body>
</html>
