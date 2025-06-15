# cli_pixels
cli_pixels is a PHP-CLI package that provides functionality to address pixels in the command line as if it were a computer screen.

# Commands
- **grad**: Displays an animated gradient for 10 seconds.

# Functions
- **set_screen_size(int $x=48, int $y=27, bool $apply=true):bool**: Sets the command window size to the specified width and height. Apply does not work on windows 11 so the user will have to resize the window themselves or a size smaller than 61x30 should be specified. Returns true on success and false on failure.

- **set_pixel(array $coordinates=['x'=>0,'y'=>0], array $data=['r'=>50,'g'=>50,'b'=>50]):bool**: Sets a specific pixel to a specific colour determined by the rgb data. Returns true on success and false on failure.

- **push(array|bool $pixelsArray=false):bool**: Pushes the current pixel data to the display, $pixelsArray can be set to a custom pixel array to push custom data, incorrect data may cause issues. Returns true on success and false on failure.

- **push(array|bool $pixelsArray=false):bool**: Pushes the current pixel data to the display, $pixelsArray can be set to a custom pixel array to push custom data, incorrect data may cause issues. Returns true on success and false on failure.

- **fill(array $data = ['r'=>50,'g'=>50,'b'=>50]):bool**: Sets all pixels to the same colour. Returns true on success or false on failure.

- **showRgbFrame(string $data, int $width=69, int $height=39):bool**: Takes in binary data (24 bit rgb) and displays it on the screen. Returns true on success and false on failure.

- **playVideo(string $input, int $width=69, int $height=39):bool**: Plays a video in the command line. Returns true on success and false on failure.

- **line(array $points, array $pixelData):bool**: Creates a line in the global pixel buffer, where each pixel has the provided pixel data, the $points variable takes an array consisting of 2 points with x and y positions such as this: ['x1'=>1,'y1'=>1,'x2'=>10,'y2=>15']. Returns true on success and false on failure.

- **circle(int $centerX, int $centerY, int $radius, array $pixelData):bool**: Creates a circle in the global pixel buffer with the provided pixel data. Returns true on success and false on failure.