<?php
class cli_pixels{
    private static $screenSize = ['x'=>60,'y'=>30];
    private static $pixels = [];

    public static function command($line){
        $lines = explode(" ",$line);
        if($lines[0] === "grad"){
            self::set_screen_size();
            self::fill();
            $r = 0;
            $g = 0;
            $b = 0;
            $x = 48;
            $y = 27;
            $offset = 0;

            $totalFrames = 300; //10 seconds
            $frames = 0;
            while($frames < $totalFrames){
                
                for($yi=0; $yi<$y+1; $yi++){

                    $b = ($yi - ($y/2))**2;
                
                    for($xi=0; $xi<$x+1; $xi++){

                        $r = ($xi - ($x/2))**2;
                        
                        $xi = ($xi + $offset) % ($x + 1);

                        $g = 220 - ($r/2) - ($b/2);
                        if($g < 1){
                            $g = 0;
                        }

                        self::set_pixel(array("x"=>$xi,"y"=>$yi),array("r"=>$r,"g"=>$g,"b"=>$b));
                    }
                }

                $offset++;

                self::push();
                
                usleep(28000);//Delay left for 1 frame to be 33ms
                $frames++;
                //return;
            }
        }
    }

    public static function set_screen_size(int $x = 48, int $y = 27, bool $apply = true):bool{
        if($x < 0 | $x > 800){
            return false;
        }
        if($y < 0 | $y > 450){
            return false;
        }

        self::$screenSize['x'] = $x;
        self::$screenSize['y'] = $y;

        if($apply){
            exec('mode con: cols=' . $x*2 . ' lines=' . $y+1);
        }

        return true;
    }
    public static function set_pixel(array $coordinates = ['x'=>0,'y'=>0], array $data = ['r'=>50,'g'=>50,'b'=>50]):bool{
        foreach(['x','y'] as $coord){
            if($coordinates[$coord] > self::$screenSize[$coord]-1){
                return false;
            }
            if($coordinates[$coord] < 0){
                return false;
            }
        }

        if(!self::isValidPixelData($data)){
            return false;
        }

        self::$pixels[$coordinates['y']][$coordinates['x']] = $data;
        
        return true;
    }
    public static function push(array|bool $pixelsArray = false):bool{
        if($pixelsArray === false){
            if(!isset(self::$pixels) || !is_array(self::$pixels)){
                return false;
            }
            $push = self::$pixels;
        }
        else{
            $push = $pixelsArray;
        }

        //self::$pixels[39][100]['r/g/b']
        $output = "";
        foreach($push as $rowNum => $columns){
            if(!is_array($columns)){
                return false;
            }
            foreach($columns as $colNum=> $data){
                if(!is_array($data)){
                    return false;
                }
                $output .= "\033[48;2;" . round($data['r'] ?? 0) . ";" . round($data['g'] ?? 0) . ";" . round($data['b'] ?? 0) . "m  \033[0m";
            }
            $output .= "\n";
        }
        echo "\033[?25l\033[H";
        echo $output;
        echo "\033[?25h";

        return true;
    }
    public static function fill(array $data = ['r'=>50,'g'=>50,'b'=>50]):bool{
        if(!self::isValidPixelData($data)){
            return false;
        }

        $row = 0;
        while($row < self::$screenSize['y']){
            $col = 0;
            while($col < self::$screenSize['x']){
                self::$pixels[$row][$col] = $data;
                $col++;
            }
            $row++;
        }

        return true;
    }
    private static function isValidPixelData(array $data):bool{
        foreach(['r','g','b'] as $ch){
            if(!isset($data[$ch])){
                return false;
            }
            if(!is_int($data[$ch])){
                return false;
            }
            if($data[$ch] < 0 || $data[$ch] > 255){
                return false;
            }
        }
        
        return true;
    }

    public static function showRgbFrame(string $data, int $width=69, int $height=39):bool{
        if(strlen($data) === $width * $height * 3){
            // Convert data to pixels format
            $pixels = [];
            for($pixRow = 0; $pixRow < $height; $pixRow++){
                $pixels[$pixRow] = [];
                
                for($pixCol = 0; $pixCol < $width; $pixCol++){
                    $pixelIndex = ($pixRow * $width + $pixCol) * 3;

                    // Extract RGB values (each is 1 byte)
                    $pixels[$pixRow][$pixCol] = [
                        'r' => ord($data[$pixelIndex] ?? '0'), // string 0 => int 48
                        'g' => ord($data[$pixelIndex + 1] ?? '0'),
                        'b' => ord($data[$pixelIndex + 2] ?? '0')
                    ];
                }
            }

            return self::push($pixels);
        }

        return false;
    }
    public static function playVideo(string $input, int $width = 69, int $height = 39):bool{
        if(!file_exists($input)){
            echo "Input file does not exist\n";
            return false;
        }

        if(!class_exists('e_ffmpeg')){
            echo "FFmpeg not installed, please run: pkgmgr install e_ffmpeg\n";
            return false;
        }

        $ffmpeg = e_ffmpeg::path();
        if(!is_string($ffmpeg)){
            echo "Failed to locate ffmpeg.exe, is it installed?\n";
            return false;
        }

        $bytesPerFrame = $width * $height * 3;

        if($bytesPerFrame > 8192){//Max pipe buffer
            echo "Specified frame size is too large\n";
            return false;
        }
        
        $ffmpegCmd = $ffmpeg . ' -re -i ' .  escapeshellarg($input) . ' -filter:v "scale=' . $width . ':' . $height . '" -f rawvideo -pix_fmt rgb24 -loglevel quiet pipe:1';
        
        // Open FFmpeg process with pipes
        $process = proc_open($ffmpegCmd, [['pipe', 'r'],['pipe','w']], $pipes);
        
        if(!is_resource($process)){
            echo "Failed to open ffmpeg process\n";
            return false;
        }
        
        self::set_screen_size($width,$height);
        
        while(true){
            // Read from FFmpeg stdout
            $data = fread($pipes[1], $bytesPerFrame);
            if($data !== false){
                if(!self::showRgbFrame($data, $width, $height)){
                    echo "No more data\n";
                    break;
                }
            }
        }

        // Close
        fclose($pipes[0]);
        $exitCode = proc_close($process);
        
        if($exitCode !== 0){
            echo "Ffmpeg did not exit properly\n";
            return false;
        }
        
        return true;
    }

    public static function line(array $points, array $pixelData):bool{
        foreach(['x1','y1','x2','y2'] as $point){
            if(!isset($points[$point])){
                return false;
            }
            if(!is_int($points[$point])){
                return false;
            }
        }

        $lineCoordinates = self::internal_lineCoords($points['x1'], $points['y1'], $points['x2'], $points['y2']);
        foreach($lineCoordinates as $coord){
            list($x, $y) = $coord;
            if(!self::set_pixel(array('x'=>$x,'y'=>$y),$pixelData)){
                return false;
            }
        }

        return true;
    }
    private static function internal_lineCoords(int $x1, int $y1, int $x2, int $y2):array{
        $coordinates = array();
    
        $dx = abs($x2 - $x1);
        $dy = abs($y2 - $y1);
    
        $sx = ($x1 < $x2) ? 1 : -1;
        $sy = ($y1 < $y2) ? 1 : -1;
    
        $error = $dx - $dy;
    
        while (true) {
            $coordinates[] = array($x1, $y1);
    
            if ($x1 == $x2 && $y1 == $y2) {
                break;
            }
    
            $error2 = $error * 2;
    
            if ($error2 > -$dy) {
                $error -= $dy;
                $x1 += $sx;
            }
    
            if ($error2 < $dx) {
                $error += $dx;
                $y1 += $sy;
            }
        }
    
        return $coordinates;
    }
    public static function circle(int $centerX, int $centerY, int $radius, array $pixelData):bool{
        if(!self::isValidPixelData($pixelData)){
            return false;
        }

        $circleCoordinates = self::internal_circleCoords($centerX, $centerY, $radius);
        foreach ($circleCoordinates as $coord) {
            list($x, $y) = $coord;
            if(!self::set_pixel(array('x'=>$x,'y'=>$y),$pixelData)){
                return false;
            }
        }

        return true;
    }
    private static function internal_circleCoords(int $centerX, int $centerY, int $radius):array{
        $coordinates = array();

        $x = $radius - 1;
        $y = 0;
        $dx = 1;
        $dy = 1;
        $err = $dx - ($radius << 1);

        while ($x >= $y) {
            $coordinates[] = array($centerX + $x, $centerY + $y);
            $coordinates[] = array($centerX + $y, $centerY + $x);
            $coordinates[] = array($centerX - $y, $centerY + $x);
            $coordinates[] = array($centerX - $x, $centerY + $y);
            $coordinates[] = array($centerX - $x, $centerY - $y);
            $coordinates[] = array($centerX - $y, $centerY - $x);
            $coordinates[] = array($centerX + $y, $centerY - $x);
            $coordinates[] = array($centerX + $x, $centerY - $y);

            if ($err <= 0) {
                $y++;
                $err += $dy;
                $dy += 2;
            }

            if ($err > 0) {
                $x--;
                $dx += 2;
                $err += $dx - ($radius << 1);
            }
        }

        return $coordinates;
    }
}