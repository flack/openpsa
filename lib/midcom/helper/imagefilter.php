<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Image Filter Class, used to post process all images, check the documentation
 * of the individual methods.
 *
 * This class always works on existing files at this time. It supports scripting
 * support by using the process_chain / process_command helper functions, which
 * allow the application to let the user specify a list of filters that should
 * be applied during upload.
 *
 * @todo Image type automatic best-web-format conversion
 * @author Eero af Heurlin, Nemein Oy <eero.afheurlin@nemein.com>
 * @author Nico Kaiser <nico@siriux.net>
 * @author Torben Nehmer <torben@nehmer.net>
 * @package midcom.helper
 */
class midcom_helper_imagefilter
{
    /**
     * The file currently being processed.
     *
     * @var string
     */
    private $_filename;

    /**
     * The quality to use for JPEG manipulation, this is not
     * yet settable from the outside.
     *
     * Stored as a valid imagemagick option, defaults to '-quality 90' right now.
     *
     * @var string
     */
    private $_quality = "-quality 90";

    public function __construct(midcom_db_attachment $input)
    {
        if (!self::imagemagick_available()) {
            throw new midcom_error("ImageMagick is not available, can't process commands");
        }

        $this->_filename = $this->create_tmp_copy($input);
    }

    public function __destruct()
    {
        if (file_exists($this->_filename)) {
            unlink($this->_filename);
        }
    }

    /**
     * Creates a working copy to filesystem from given attachment object
     *
     * @return string tmp file name
     */
    private function create_tmp_copy(midcom_db_attachment $input) : string
    {
        $src = $input->open('r');
        if (!$src) {
            throw new midcom_error("Could not open attachment #{$input->id} for reading");
        }

        $tmpname = $this->_get_tempfile();
        $dst = fopen($tmpname, 'w+');
        if (!$dst) {
            throw new midcom_error("Could not open file '{$tmpname}' for writing");
        }
        stream_copy_to_stream($src, $dst);
        $input->close();
        fclose($dst);

        return $tmpname;
    }

    public function write(midcom_db_attachment $target) : bool
    {
        $src = fopen($this->_filename, 'r');
        if (!$src) {
            debug_add("Could not open file '{$this->_filename}' for reading", MIDCOM_LOG_ERROR);
            return false;
        }
        $stat = $target->copy_from_handle($src);
        if (!$stat) {
            debug_add("copy_from_handle() failed", MIDCOM_LOG_ERROR);
        }
        fclose($src);
        return $stat;
    }

    public static function imagemagick_available() : bool
    {
        static $return = -1;
        if ($return === -1) {
            $convert_cmd = escapeshellcmd(midcom::get()->config->get('utility_imagemagick_base') . "convert -version");
            $output = [];
            $ret = null;
            exec($convert_cmd, $output, $ret);
            $return = in_array($ret, [0, 1], true);
            if (!$return) {
                debug_add("ImageMagick, '{$convert_cmd}' (part of ImageMagick suite) returned failure", MIDCOM_LOG_ERROR);
            }
        }

        return $return;
    }

    private function _jpegtran_available() : bool
    {
        static $return = -1;
        if ($return !== -1) {
            return $return;
        }
        $return = false;
        if ($cmd = midcom::get()->config->get('utility_jpegtran')) {
            $return = true;
            if (!file_exists($cmd)) {
                $find_cmd = escapeshellcmd('which ' . midcom::get()->config->get('utility_jpegtran'));
                $output = [];
                $ret = null;
                exec($find_cmd, $output, $ret);

                if ($ret !== 0) {
                    debug_add("jpegtran (part of libjpeg suite) was not found", MIDCOM_LOG_ERROR);
                    $return = false;
                }
            }
        }

        return $return;
    }

    /**
     * Get the filename
     *
     * @return string
     */
    public function get_file()
    {
        return $this->_filename;
    }

    /**
     * Process a string-based filter chain.
     *
     * This is a semicolon-separated list of commands, which will be evaluated
     * using process_command each.
     *
     * Processing stops as soon as one filter command fails.
     *
     * @param string $chain The filter chain to be processed (filter1();filter2();...)
     */
    public function process_chain(string $chain)
    {
        $filters = array_filter(explode(";", $chain));
        array_map([$this, 'process_command'], $filters);
    }

    /**
     * This is the main function evaluator of the filter class.
     *
     * It will take a function style command like
     * resize(90,90), arguments are separated by commas, there is no
     * quoting at the moment. All arguments are parsed as strings,
     * function names need to be [a-z_]
     *
     * Execution will relay to the corresponding filter function.
     *
     * All filters will use defaults for missing arguments (which can
     * result in a null operation) and will ignore excessive arguments.
     */
    private function process_command(string $cmd)
    {
        if (!preg_match('/([a-z_:]*)\(([^)]*)\)/', $cmd, $matches)) {
            throw new midcom_error("Failed to parse command {$cmd}");
        }

        $command = $matches[1];
        $args = explode(',', $matches[2]);

        debug_print_r("Have to execute {$command} with these arguments:", $args);
        if (is_callable([$this, $command])) {
            $this->$command(...$args);
        } elseif ($command != 'none') {
            debug_add('This is no known command, we try to find a callback.');
            $this->execute_user_callback($command, $args);
        }
    }

    /*********** INTERNAL HELPERS ******************/
    private function _run_command(string $cmd, $tempfile = null) : array
    {
        debug_add("executing: {$cmd}");
        $output = null;
        $exit_code = 0;
        exec($cmd . ' 2>&1', $output, $exit_code);

        if ($exit_code !== 0) {
            if ($tempfile !== null) {
                unlink($tempfile);
            }
            $command = basename(preg_replace('/ .+/', '', $cmd));
            debug_print_r("Command [{$cmd}] returned {$exit_code}, the generated output was:", $output, MIDCOM_LOG_ERROR);
            throw new midcom_error($command . " returned error code {$exit_code}");
        }
        if ($tempfile !== null) {
            $this->_process_tempfile($tempfile);
        }
        return $output;
    }

    /**
     * Returns the name of a temporary file to be used to write
     * the transformed image to. Has to be managed by the callee.
     */
    private function _get_tempfile()
    {
        return tempnam(midcom::get()->config->get('midcom_tempdir'), "midcom_helper_imagefilter");
    }

    /**
     * This will replace the original file with the processed copy
     * of $tmpfile, deleting the temporary file afterwards.
     */
    private function _process_tempfile(string $tmpname)
    {
        copy($tmpname, $this->_filename);
        unlink($tmpname);
    }

    /*********** IMAGE MANIPULATION STUFF *****************/

    /**
     * Executes a custom image manipulation callback.
     * The command name is the function that is searched. The function must
     * use the following signature:
     *
     * boolean callback (string $infile, string $outfile, array $parameters);
     *
     * infile is the file to be manipulated, outfile the name where
     * the result has to be stored in (a temporary file usually) and
     * the parameters passed to the command originally. It should return
     * true on a successful transformation, false otherwise.
     */
    private function execute_user_callback(callable $command, array $args)
    {
        $tmpfile = $this->_get_tempfile();

        if (!$command($this->_filename, $tmpfile, $args)) {
            unlink($tmpfile);
            throw new midcom_error("The function {$command} returned false");
        }

        $this->_process_tempfile($tmpfile);
    }

    /**
     * Gamma-Adjustment of the image.
     *
     * Filter Syntax: gamma($gamma)
     *
     * Where $gamma is a positive floating point number, e.g. 1.2
     *
     * @param float $gamma Gamma adjustment value.
     */
    public function gamma($gamma = 1.2)
    {
        $gamma = (double) $gamma;

        $cmd = midcom::get()->config->get('utility_imagemagick_base') . "mogrify {$this->_quality} -gamma "
            . escapeshellarg($gamma) . " " . escapeshellarg($this->_filename);
        $this->_run_command($cmd);
    }

    /**
     * Convert the image to the specified image format.
     *
     * It must be a suitable extension to use with the Imagemagick convert
     * utility. The file will not be renamed, you have to do this yourself.
     *
     * This call will always convert the first page only of the supplied image, otherwise,
     * Imagemagick would spam the temp directory in case we are working on a PDF file or
     * whatever.
     *
     * @param string $format The format to convert to. This must be a valid conversion targed
     *     recognized by Imagemagick, it defaults to 'jpg'.
     */
    public function convert(string $format = 'jpg')
    {
        $tempfile = $this->_get_tempfile();

        $cmd = midcom::get()->config->get('utility_imagemagick_base') . "convert {$this->_quality} "
            . escapeshellarg("{$this->_filename}[0]") . " {$format}:{$tempfile}";

        $this->_run_command($cmd, $tempfile);
    }

    /**
     * Automatic rotation for the image using EXIF tags.
     *
     * NOTE: This REQUIRES php_read_exif to be present. It will fail
     * if it is missing.
     *
     * Filter Syntax: exifrotate()
     */
    public function exifrotate()
    {
        if (!function_exists("exif_read_data")) {
            throw new midcom_error("exif_read_data required for exifrotate.");
        }
        // Silence this, gives warnings on images that do not contain EXIF data
        $exif = @exif_read_data($this->_filename);
        if (empty($exif['Orientation'])) {
            debug_add("EXIF information missing or without orientation tag. Skipping.", MIDCOM_LOG_INFO);
            return;
        }
        if ($exif["Orientation"] == 1) {
            debug_add("No rotation necessary.");
            return;
        }

        $tmpfile = null;
        $imagesize = getimagesize($this->_filename);

        if (   $imagesize[2] == IMAGETYPE_JPEG
            && $this->_jpegtran_available()) {
            /* jpegtran */
            $operations = [
                2 => "-flip horizontal",
                3 => "-rotate 180",
                4 => "-flip vertical",
                5 => "-transpose",
                6 => "-rotate 90",
                7 => "-transverse",
                8 => "-rotate 270"
            ];

            $tmpfile = $this->_get_tempfile();
            $cmd = midcom::get()->config->get('utility_jpegtran') . " -outfile {$tmpfile} -copy all";
        } else {
            /* Mogrify */
            debug_add("jpegtran not found, falling back to mogrify.");

            $operations = [
                2 => "-flip",
                3 => "-rotate 180",
                4 => "-flip",
                5 => "-rotate 90 -flip",
                6 => "-rotate 90",
                7 => "-rotate 270 -flop",
                8 => "-rotate 270"
            ];

            $cmd = midcom::get()->config->get('utility_imagemagick_base') . "mogrify {$this->_quality}";
        }
        if (!array_key_exists($exif["Orientation"], $operations)) {
            debug_add("Unsupported EXIF-Rotation tag encountered, ignoring: " . $exif["Orientation"], MIDCOM_LOG_INFO);
            return;
        }
        $cmd .= ' ' . $operations[$exif["Orientation"]] . ' ' . escapeshellarg($this->_filename);

        $this->_run_command($cmd, $tmpfile);
    }

    /**
     * Manual image rotation
     *
     * Filter Syntax: rotate($rotate)
     *
     * Where $rotate is a positive floating point number greater than 0
     * and less than 360; if omitted, a null operation is done.
     *
     * @param float $rotate Degrees of rotation clockwise, negative amounts possible
     */
    public function rotate($rotate = 0)
    {
        // Do some normalizing on the argument
        while ($rotate < 0) {
            $rotate += 360;
        }
        while ($rotate > 360) {
            $rotate -= 360;
        }
        if (in_array($rotate, [0, 360])) {
            debug_add("Rotate is {$rotate}, we're happy as-is.");
            return;
        }

        $tmpfile = null;
        $imagesize = getimagesize($this->_filename);

        // Try lossless jpegtran rotation if possible
        if (   $imagesize[2] == IMAGETYPE_JPEG
            && ($rotate % 90 == 0)
            && $this->_jpegtran_available()) {
            $tmpfile = $this->_get_tempfile();
            $cmd = midcom::get()->config->get('utility_jpegtran') . " -copy all -rotate {$rotate} -outfile {$tmpfile} " . escapeshellarg($this->_filename);
        } else {
            /* Mogrify */
            debug_add("jpegtran not found or rotation incompatible, falling back to mogrify.");

            $cmd = midcom::get()->config->get('utility_imagemagick_base') . "mogrify {$this->_quality} -rotate {$rotate} " . escapeshellarg($this->_filename);
        }

        debug_add("We have to rotate clockwise by {$rotate} degrees");

        $this->_run_command($cmd, $tmpfile);
    }

    /**
     * Image Resize: This will resize the original image.
     *
     * Filter Syntax: resize ($x, $y)
     *
     * The width of the image in pixels, it will be scaled in
     * proportion. One of the two parameters can be 0, meaning that
     * the width/height of the image is adjusted to the other, given
     * value.
     *
     * The resize will be done only if it would not increase the image
     * as this won't gain you any benefit.
     *
     * @param int $x Width
     * @param int $y Height
     */
    public function resize($x = 0, $y = 0)
    {
        $x = (int) $x;
        $y = (int) $y;

        if ($x == 0 && $y == 0) {
            debug_add("Both x and y are 0, skipping operation.", MIDCOM_LOG_INFO);
            return;
        }

        $geo = '';
        if ($x != 0) {
            $geo .= $x;
        }
        $geo .= 'x';
        if ($y != 0) {
            $geo .= $y;
        }
        $geo .= '>';

        $cmd = midcom::get()->config->get('utility_imagemagick_base') . "mogrify {$this->_quality} -geometry "
            . escapeshellarg($geo) . ' ' . escapeshellarg($this->_filename);

        $this->_run_command($cmd);
    }

    /**
     * Square Thumbnails: This will resize&crop the original image.
     *
     * Filter Syntax: squarethumb ($x)
     *
     * The filter will adapt picture to given width, point gravity
     * to the centre and crop leftovers.
     *
     * This is a one parameter shorthand for cropping, provided for
     * backwards compatibility
     *
     * @param int $x Width
     */
    public function squarethumb($x = 0, string $gravity = 'center')
    {
        $this->crop($x, $x, $gravity);
    }

    /**
     * Crop an image to given proportions
     *
     * @param int $x Width
     * @param int $y Height
     */
    public function crop($x = 0, $y = 0, string $gravity = 'center')
    {
        if ($x == 0) {
            return;
        }

        if ($y == 0) {
            $y = $x;
        }

        $data = @getimagesize($this->_filename);

        // 1a. If got...
        if ($data) {
            $size_x = $data[0];
            $size_y = $data[1];
        } else {
            // If image data was not available, try to get it with identify program
            $cmd = midcom::get()->config->get('utility_imagemagick_base') . "identify -verbose {$this->_filename}";

            $output = $this->_run_command($cmd);
            $output = implode("\n", $output);

            if (!preg_match('/Geometry:\s([0-9]+)x([0-9]+)/i', $output, $regs)) {
                throw new midcom_error('Could not read geometry data');
            }

            $size_x = (int) $regs[1];
            $size_y = (int) $regs[2];
        }

        // Get resize ratio in relations to the original
        $ratio = str_replace(',', '.', 100 * max($x / $size_x, $y / $size_y));
        $cmd = midcom::get()->config->get('utility_imagemagick_base') . "mogrify {$this->_quality} -resize {$ratio}% -gravity {$gravity} -crop {$x}x{$y}+0+0 +repage " . escapeshellarg($this->_filename);
        $this->_run_command($cmd);
    }

    /**
     * Resize image and apply fill to match given size
     *
     * @param int $x Width
     * @param int $y Height
     * @param string $color Color
     * @param string $gravity Gravity point
     */
    public function fill($x = null, $y = null, $color = null, string $gravity = 'center')
    {
        if (   empty($x)
            || empty($y)
            || empty($color)) {
            //This is a bit silly, but here for backwards compatibility...
            return;
        }

        // Currently accepting only hex colors
        if (!preg_match('/^#?([0-9a-f]{3}){1,2}$/', $color)) {
            throw new midcom_error("Given color ({$color}) is not hex RGB.");
        }

        $cmd = midcom::get()->config->get('utility_imagemagick_base') . "mogrify {$this->_quality} -resize '{$x}x{$y}' -background '{$color}' -gravity {$gravity} -extent {$x}x{$y} +repage " . escapeshellarg($this->_filename);

        $this->_run_command($cmd);
    }
}
