<?php
class cli_pixels{
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
            while(true){
                
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
                
                usleep(33000);
                //return;
            }
        }
    }
    public static function line(array $points, array $data){
        $lineCoordinates = self::internal_lineCoords($points['x1'], $points['y1'], $points['x2'], $points['y2']);
        foreach ($lineCoordinates as $coord) {
            list($x, $y) = $coord;
            self::set_pixel(array('x'=>$x,'y'=>$y),$data);
        }
    }
    private static function internal_lineCoords($x1, $y1, $x2, $y2){
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
    public static function circle($centerX, $centerY, $radius, $pixelData){
        
        $circleCoordinates = self::internal_circleCoords($centerX, $centerY, $radius);
        foreach ($circleCoordinates as $coord) {
            list($x, $y) = $coord;
            self::set_pixel(array('x'=>$x,'y'=>$y),$pixelData);
        }
    }
    private static function internal_circleCoords($centerX, $centerY, $radius){
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
    public static function set_pixel(array $coordinates = array('x'=>0,'y'=>0), array $data = array('r'=>50,'g'=>50,'b'=>50)):bool{
        $success = false;
        $setpixel = true;
        
        foreach(array('x','y') as $coord){
            if($coordinates[$coord] > $GLOBALS['cli_pixels']['screen_size'][$coord]-1){
                $setpixel = false;
            }
            if($coordinates[$coord] < 0){
                $setpixel = false;
            }
        }

        if($setpixel){
            $GLOBALS['cli_pixels']['pixels'][$coordinates['y']][$coordinates['x']] = $data;
        }
        $success = true;
        return $success;
    }
    public static function set_screen_size(int $x = 48, int $y = 27, bool $apply = true){
        $GLOBALS['cli_pixels']['screen_size']['x'] = $x;
        $GLOBALS['cli_pixels']['screen_size']['y'] = $y;
        if($apply){
            exec('mode con: cols=' . $x*2 . ' lines=' . $y+1);
        }
    }
    public static function push(array|bool $pixelsArray = false):bool{
        $success = false;
        if($pixelsArray === false){
            if(isset($GLOBALS['cli_pixels']['pixels'])){
                $push = $GLOBALS['cli_pixels']['pixels'];
            }
            else{
                goto end;
            }
        }
        else{
            $push = $pixelsArray;
        }

        if(is_array($push)){
            //$GLOBALS['cli_pixels']['pixels'][39][100]['r/g/b']
            $output = "";
            foreach($push as $row => $columns){
                foreach($columns as $column => $data){
                    $output .= "\033[48;2;" . round($data['r']) . ";" . round($data['g']) . ";" . round($data['b']) . "m  \033[0m";
                }
                $output .= "\n";
            }
            echo "\033[?25l\033[H";
            echo $output;
            echo "\033[?25h";
            $success = true;
        }
        end:
        return $success;
    }
    public static function fill(array $data = array('r'=>50,'g'=>50,'b'=>50)){
        $row = 0;
        while($row < $GLOBALS['cli_pixels']['screen_size']['y']){
            $col = 0;
            while($col < $GLOBALS['cli_pixels']['screen_size']['x']){
                $GLOBALS['cli_pixels']['pixels'][$row][$col] = $data;
                $col++;
            }
            $row++;
        }
    }
}