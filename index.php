<html>
<head>
    <title>Mr. Monitor</title>
	<link rel="stylesheet" href="stylesheets/screen.css" />
    <!-- Refresh every 5 minutes: -->
    <meta http-equiv="refresh" content="300">
</head>
<body>
<?php
    // Screensaver for at night:
    if(date('H') < 18 && date('H') > 7):
?>
<?php
    if(file_exists('result.csv'))
    {
        // Get the number of lines:
        $linecount = -1;
        $file = fopen('result.csv', "r");
        while(!feof($file)){
          $line = fgets($file);
          $linecount++;
        }
        fclose($file);

        $file = fopen('result.csv', 'r');
        $headers = fgetcsv($file);
        $happy = true;
        $error = false;
        while($url = fgetcsv($file))
        {
            $info = array_combine($headers, $url);
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
            if($info['time_first'] > 0.5 || $info['time_total'] > 2)
            {
                $happy = false;
                $warning = true;
                $info['message'] = 'Site is slow';
            }
            if($warning) {
                $class .= ' warning';
            }
            if($linecount > 25) { $class .= ' count50'; }
            if($linecount > 50) { $class .= ' count100'; }
            if($linecount > 100) { $class .= ' count200'; }
            if($linecount > 200) { $class .= ' count400'; }
            ?>
                <div class="site <?php echo $class; ?>">
                    <p class="url"><?php echo str_replace(array('http://', 'www.'), '', $info['url']); ?></p>
                    <p class="code"><?php echo $info['code']; ?></p>
                    <?php if(!empty($info['time_first']) && !empty($info['time_total'])): ?>
                    <p class="time"><?php echo
                            number_format($info['time_first'], 3, ',', '.'). '/' .
                            number_format($info['time_total'], 3, ',', '.'); ?></p>
                    <?php endif; ?>
                    <p class="message"><?php echo $info['message']; ?></p>
                </div>
            <?php
        }
        fclose($file);

        if($happy)
        {
            echo '<div id="happy">☺</div>';
        }

        if($error)
        {
            ?>
                <div id="error">
					<audio src="error.mp3" autoplay="true"></audio>
                </div>
            <?php
        }

    }
?>
<?php else: ?>
        <style type="text/css">
            html { background: #000; }
        </style>
<?php endif; ?>
</body>
</html>