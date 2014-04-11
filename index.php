<html>
<head>
    <title>Mr. Monitor</title>
	<link rel="stylesheet" href="stylesheets/screen.css" />
    <!-- Refresh every 5 minutes: -->
    <meta http-equiv="refresh" content="300">
</head>
<body>
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
            $class = $info['success'] == '1' ? 'success' : 'error';
            if($info['success'] != '1') {
                $happy = false;
                $error = true;
            }
            // Check for slow speed:
            if($info['time_first'] > 0.5 || $info['time_total'] > 2)
            {
                $class .= ' slow';
                $happy = false;
            }
            if($linecount > 25) { $class .= ' count50'; }
            if($linecount > 50) { $class .= ' count100'; }
            if($linecount > 100) { $class .= ' count200'; }
            if($linecount > 200) { $class .= ' count400'; }
            ?>
                <div class="site <?php echo $class; ?>">
                    <p class="url"><?php echo str_replace(array('http://', 'www.'), '', $info['url']); ?></p>
                    <p class="code"><?php echo $info['code']; ?></p>
                    <p class="time"><?php echo
                            number_format($info['time_first'], 3, ',', '.'). '/' .
                            number_format($info['time_total'], 3, ',', '.'); ?></p>
                </div>
            <?php
        }
        fclose($file);

        if($happy)
        {
            ?>
                <div id="happy">☺</div>
            <?php
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
</body>
</html>