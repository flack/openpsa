<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: imagefilter.php 26716 2010-10-24 11:43:40Z adrenalin $
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
 * This class is originally known as net_siriux_photos_filter.
 *
 * Usage note: This class is not loaded automatically for the sake of performance.
 * If you want to utilize it, you have to require_once it first:
 *
 * <code>
 * require_once(MIDCOM_ROOT . 'lib/helper/imagefilter.php');
 * </code>
 *
 * @todo Image type automatic best-web-format conversion
 * @todo Add support for working on attachments
 * @author Eero af Heurlin, Nemein Oy <eero.afheurlin@nemein.com>
 * @author Nico Kaiser <nico@siriux.net>
 * @author Torben Nehmer <torben@nehmer.net>
 * @version 1.1 (2005-11-03)
 * @package midcom
 */
class midcom_helper_imagefilter
{
    /**
     * The file currently being processed.
     *
     * @var string
     * @access private
     */
    var $_filename = null;

    /**
     * The quality to use for JPEG manipulation, this is not
     * yet settable from the outside.
     *
     * Stored as a valid imagemagick option, defaults to '-quality 90' right now.
     *
     * @var string
     * @access private
     */
    var $_quality = "-quality 90";

    function _imagemagick_available()
    {
        static $return = -1;
        if ($return !== -1)
        {
            return $return;
        }
        $convert_cmd = escapeshellcmd("{$GLOBALS['midcom_config']['utility_imagemagick_base']}convert -version");
        $output = array();
        $ret = null;
        exec($convert_cmd, $output, $ret);
        if ($ret !== 0 && $ret !== 1)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("ImageMagick, '{$convert_cmd}' (part of ImageMagick suite) returned failure", MIDCOM_LOG_ERROR);
            debug_pop();
            $return = false;
            return $return;
        }
        $return = true;
        return $return;
    }

    function _jpegtran_available()
    {
        static $return = -1;
        if ($return !== -1)
        {
            return $return;
        }
        if (empty($GLOBALS['midcom_config']['utility_jpegtran']))
        {
            $return = false;
            return $return;
        }
        $convert_cmd = escapeshellcmd("{$GLOBALS['midcom_config']['utility_jpegtran']} -h");
        $output = array();
        $ret = null;
        exec($convert_cmd, $output, $ret);
        if (   $ret !== 0
            /* jpegtran does not have valid help switch, so lets check for generic error from program (command not found etc trhows different error code) */
            && $ret !== 1)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("jpegtran, '{$convert_cmd}' (part of libjpeg suite) could not be executed", MIDCOM_LOG_ERROR);
            debug_pop();
            $return = false;
            return $return;
        }
        $return = true;
        return $return;
    }

    /**
     * Sets the filename of the image currently being edited.
     *
     * This must be the full path to the file, the fill will be
     * replaced with the modified image.
     *
     * The process will check for write permission at this point,
     * A return value of false will indicate some problem, see the
     * MidCOM Debug Log for details.
     *
     * @todo Use ImageMagick Identify to check for a valid image.
     *
     * @param string filename The file to be edited.
     * @return boolean true, if the file is valid, false otherwise.
     */
    function set_file($filename)
    {
        if (!$this->_imagemagick_available())
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("ImageMagick is not available, can't do any operations", MIDCOM_LOG_ERROR);
            debug_pop();
            $_MIDCOM->uimessages->add('midcom.helper.imagefilter', "ImageMagick is not available, can't process commands", 'error');
            return false;
        }
        if (! is_writeable($filename))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("The File {$filename} is not writeable.", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $this->_filename = $filename;
        return true;
    }

    /**
     * This function will process a string-based filter chain.
     *
     * This is a semicolon-separated list of commands, which will be evaluated
     * using process_command each.
     *
     * Processing stops as soon as one filter command fails.
     *
     * @param string chain The filter chain to be processed (filter1();filter2();...)
     * @return boolean true, if all filters have been successfully applied, false otherwise.
     */
    function process_chain($chain)
    {
        $filters = explode(";", $chain);

        foreach ($filters as $cmd)
        {
            if ($cmd == '')
            {
                // Skip empty commands
                continue;
            }

            if (! $this->process_command($cmd))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Execution of {$cmd} failed, aborting now.");
                debug_pop();
                $_MIDCOM->uimessages->add('midcom.helper.imagefilter', "Execution of {$cmd} failed", 'error');
                return false;
            }
        }

        return true;
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
     * result in a NULL operation) and will ignore excessive arguments.
     *
     * @param string cmd The command to be executed.
     * @return boolean true, if the filter executed successfully, false otherwise.
     */
    function process_command($cmd)
    {
        $i = preg_match('/([a-z_]*)\(([^)]*)\)/', $cmd, $matches);
        if (! $i)
        {
            debug_add("Failed to parse command {$cmd}, aborting.", MIDCOM_LOG_INFO);
            return false;
        }
        $command = $matches[1];
        $args = explode(',', $matches[2]);

        debug_print_r("Have to execute {$command} with these arguments:", $args);

        switch ($command)
        {
            case 'gamma':
                if (   array_key_exists(0, $args)
                    && $args[0] > 0)
                {
                    $gamma = (double) $args[0];
                }
                else
                {
                    $gamma = 1.2;
                }
                debug_pop();
                return $this->gamma($gamma);

            case 'exifrotate':
                debug_pop();
                return $this->exifrotate();

            case 'rotate':
                if (   array_key_exists(0, $args)
                    && $args[0] > 0
                    && $args[0] < 360)
                {
                    $rotate = (double) $args[0];
                }
                else
                {
                    $rotate = 0;
                }
                debug_pop();
                return $this->rotate($rotate);

            case 'resize':
                if (count($args) == 0)
                {
                    debug_add('No arguments given, doing nothing.', MIDCOM_LOG_INFO);
                    return true;
                }
                $x = (int) $args[0];
                if (   array_key_exists(1, $args)
                    && $args[1] > 0)
                {
                    $y = (int) $args[1];
                }
                else
                {
                    $y = 0;
                }
                debug_pop();
                return $this->rescale($x, $y);

            case 'convert':
                if (count($args) == 0)
                {
                    return $this->convert();
                }
                else
                {
                    return $this->convert($args[0]);
                }

            case 'squarethumb':
                switch (count($args))
                {
                    case 1:
                        return $this->squarethumb($args[0]);

                    case 2:
                        return $this->squarethumb($args[0], $args[1]);

                    default:
                        return true;
                }

            case 'crop':
                switch (count($args))
                {
                    case 3:
                        return $this->crop($args[0], $args[1], $args[2]);

                    case 2:
                        return $this->crop($args[0], $args[1]);

                    case 1:
                        return $this->crop($args[0], $args[0]);

                    default:
                        return true;
                }

            case 'fill':
                if (count($args) === 3)
                {
                    return $this->fill($args[0], $args[1], $args[2]);
                }

                if (count($args) === 4)
                {
                    return $this->fill($args[0], $args[1], $args[2], $args[3]);
                }

                return true;

            case 'none':
                debug_pop();
                return true;

            default:
                debug_add('This is no known command, we try to find a callback.');
                debug_pop();
                return $this->execute_user_callback($command, $args);
        }
    }

    /*********** INTERNAL HELPERS ******************/

    /**
     * Returns the name of a temporary file to be used to write
     * the transformed image to. Has to be managed by the callee.
     */
    function _get_tempfile()
    {
        return tempnam($GLOBALS['midcom_config']['midcom_tempdir'], "midcom_helper_imagefilter");
    }

    /**
     * This will replace the original file with the processed copy
     * of $tmpfile, deleting the temporary file afterwards.
     */
    function _process_tempfile($tmpname)
    {
        $src = fopen($tmpname, "r");
        $dst = fopen($this->_filename, "w+");
        while (! feof($src))
        {
            $buffer = fread($src, 131072); /* 128 kB */
            fwrite($dst, $buffer, 131072);
        }
        fclose($src);
        fclose($dst);
        unlink($tmpname);
    }

    /*********** IMAGE MANIPULATION STUFF *****************/

    /**
     * Executes a custom image manipulation callback.
     * The command name is the function that is searched. The function must
     * use the following signature:
     *
     * boolean callback (string $infile, $string outfile, array $parameters);
     *
     * infile is the file to be manipulated, outfile the name where
     * the result has to be stored in (a temporary file usually) and
     * the parameters passed to the command originally. It should return
     * true on a successful transformation, false otherwise.
     *
     * @param string $command  The name of the callback to execute
     * @param array $args      The arguments passed to the callback
     * @return The return code of the callback.
     */
    function execute_user_callback($command, $args) {
        if (! function_exists($command))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("The function {$command} could not be found, aborting", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $tmpfile = $this->_get_tempfile();

        if (! $command($this->_filename, $tmpfile, $args))
        {
            unlink($tmpfile);
            return false;
        }

        $this->_process_tempfile($tmpfile);
        return true;
    }

    /**
     * Gamma-Adjustment of the image.
     *
     * Filter Syntax: gamma($gamma)
     *
     * Where $gamma is a positive floating point number, e.g. 1.2
     *
     * @param float $gamma Gamma adjustment value.
     * @return boolean true on success.
     */
    function gamma($gamma)
    {
        $cmd = "{$GLOBALS['midcom_config']['utility_imagemagick_base']}mogrify {$this->_quality} -gamma "
            . escapeshellarg($gamma) . " " . escapeshellarg($this->_filename);

        exec($cmd, $output, $exit_code);

        if ($exit_code === 0)
        {
            return true;
        }
        else
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("ImageMagick failed to convert the image, it returned with {$exit_code}, see LOG_DEBUG for details.", MIDCOM_LOG_ERROR);
            debug_print_r('The generated output was:', $output);
            debug_add("Command was: [{$cmd}]");
            debug_pop();
            return false;
        }
    }

    /**
     * This function converts the image to the specified image format.
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
    function convert($format = 'jpg')
    {
        $tempfile = $this->_get_tempfile();

        $cmd = "{$GLOBALS['midcom_config']['utility_imagemagick_base']}convert {$this->_quality} "
            . escapeshellarg("{$this->_filename}[0]") . " {$format}:{$tempfile}";

        exec($cmd, $output, $exit_code);

        if ($exit_code === 0)
        {
            $this->_process_tempfile($tempfile);
            return true;
        }
        else
        {
            unlink($tempfile);
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("ImageMagick failed to convert the image, it returned with {$exit_code}, see LOG_DEBUG for details.", MIDCOM_LOG_ERROR);
            debug_print_r('The generated output was:', $output);
            debug_add("Command was: [{$cmd}]");
            debug_pop();
            return false;
        }
    }

    /**
     * Automatic rotation for the image using EXIF tags.
     *
     * NOTE: This REQUIRES php_read_exif to be present. It will fail
     * if it is missing.
     *
     * Filter Syntax: exifrotate()
     *
     * @return boolean true on success.
     */
    function exifrotate()
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        if (! function_exists("read_exif_data"))
        {
            debug_add("read_exif_data required for exifrotate.", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        // Silence this, gives warnings on images that do not contain EXIF data
        try
        {
            $exif = @read_exif_data($this->_filename);
        }
        catch (Exception $e)
        {
            debug_add("Could not read EXIF data: " . $e->getMessage() . ", skipping.", MIDCOM_LOG_WARN);
            debug_pop();
            return true;
        }
        if (!is_array($exif))
        {
            debug_add("Could not read EXIF data, skipping.", MIDCOM_LOG_WARN);
            debug_pop();
            return true;
        }
        if (!array_key_exists('Orientation', $exif))
        {
            debug_add("EXIF information misses the orientation tag. Skipping.", MIDCOM_LOG_INFO);
            debug_pop();
            return true;
        }
        if ($exif["Orientation"] == 1)
        {
            debug_add("No rotation necessary.");
            debug_pop();
            return true;
        }

        $do_unlink = false;
        $imagesize = getimagesize($this->_filename);

        if (   $imagesize[2] == 2
            && $this->_jpegtran_available())
        {
            /* jpegtran */
            switch ($exif["Orientation"])
            {
                case 2: $rotate = "-flip horizontal"; break;
                case 3: $rotate = "-rotate 180"; break;
                case 4: $rotate = "-flip vertical"; break;
                case 5: $rotate = "-transpose"; break;
                case 6: $rotate = "-rotate 90"; break;
                case 7: $rotate = "-transverse"; break;
                case 8: $rotate = "-rotate 270"; break;
                default:
                    debug_add("Unsupported EXIF-Rotation tag encountered, ingoring: " . $exif["Orientation"],
                        MIDCOM_LOG_INFO);
                    debug_pop();
                    return true;
            }

            $tmpfile = $this->_get_tempfile();
            $do_unlink = true;
            $cmd = "{$GLOBALS['midcom_config']['utility_jpegtran']} -copy all {$rotate} -outfile {$tmpfile} " . escapeshellarg($this->_filename);
        }
        else
        {
            /* Mogrify */
            debug_add("jpegtran not found, falling back to mogrify.");

            switch ($exif["Orientation"])
            {
                case 2: $rotate = "-flip"; break;
                case 3: $rotate = "-rotate 180"; break;
                case 4: $rotate = "-flip"; break;
                case 5: $rotate = "-rotate 90 -flip"; break;
                case 6: $rotate = "-rotate 90"; break;
                case 7: $rotate = "-rotate 270 -flop"; break;
                case 8: $rotate = "-rotate 270"; break;
                default:
                    debug_add("Unsupported EXIF-Rotation tag encountered, ingoring: " . $exif["Orientation"],
                        MIDCOM_LOG_INFO);
                    debug_pop();
                    return true;
            }

            $cmd = "{$GLOBALS['midcom_config']['utility_imagemagick_base']}mogrify {$this->_quality} {$rotate} "
                . escapeshellarg($this->_filename);
        }

        debug_add("executing: {$cmd}");
        exec($cmd, $output, $exit_code);

        if ($exit_code !== 0)
        {
            debug_add("ImageMagick/jpegtran failed to convert the image, see LOG_DEBUG for details.", MIDCOM_LOG_ERROR);
            debug_print_r("Imagemagick/jpegtran returned with {$exit_code} and produced this output:", $output);
            debug_add("Command was: {$cmd}");
            if ($do_unlink)
            {
                unlink($tmpfile);
            }
            debug_pop();
            return false;
        }

        if ($do_unlink)
        {
            $this->_process_tempfile($tmpfile);
        }
        debug_pop();
        return true;
    }

    /**
     * Manual image rotation
     *
     * Filter Syntax: rotate($rotate)
     *
     * Where $rotate is a positive floating point number greater then 0
     * and less then 360; if omitted, a NULL operation is done.
     *
     * @param float $rotate Degrees of rotation clockwise, negative amounts possible
     * @return boolean true on success.
     */
    function rotate($rotate)
    {
        // Do some normalizing on the argument
        while ($rotate < 0)
        {
            $rotate += 360;
        }
        while ($rotate > 360)
        {
            $rotate -= 360;
        }
        if ($rotate == 0 || $rotate == 360)
        {
            debug_add("Rotate is {$rotate}, we're happy as-is.");
            // We're happy as-is :)
            return true;
        }
        debug_push_class(__CLASS__, __FUNCTION__);

        $do_unlink = false;
        $imagesize = getimagesize($this->_filename);

        // Try lossless jpegtran rotation if possible
        if (   $imagesize[2] == 2
            && ($rotate == 90 || $rotate == 180 || $rotate == 270)
            && $this->_jpegtran_available())
        {
            $tmpfile = $this->_get_tempfile();
            $do_unlink = true;
            $cmd = "{$GLOBALS['midcom_config']['utility_jpegtran']} -copy all -rotate {$rotate} -outfile {$tmpfile} " . escapeshellarg($this->_filename);
        }
        else
        {
            /* Mogrify */
            debug_add("jpegtran not found or rotation incompatible, falling back to mogrify.");

            $cmd = "{$GLOBALS['midcom_config']['utility_imagemagick_base']}mogrify {$this->_quality} -rotate {$rotate} " . escapeshellarg($this->_filename);
        }

        debug_add("We have to rotate clockwise by {$rotate} degrees, do_unlink: {$do_unlink}");
        exec($cmd, $output, $exit_code);

        if ($exit_code !== 0)
        {
            debug_add("ImageMagick/jpegtran failed to convert the image, see LOG_DEBUG for details.", MIDCOM_LOG_ERROR);
            debug_print_r("Imagemagick/jpegtran returned with {$exit_code} and produced this output:", $output);
            debug_add("Command was: {$cmd}");
            if ($do_unlink)
            {
                unlink($tmpfile);
            }
            debug_pop();
            return false;
        }

        if ($do_unlink)
        {
            $this->_process_tempfile($tmpfile);
        }
        debug_pop();
        return true;
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
     * @return boolean true on success.
     */
    function rescale($x, $y)
    {
        if ($x == 0 && $y == 0)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Both x and y are 0, skipping operation.", MIDCOM_LOG_INFO);
            debug_pop();
            return true;
        }

        if ($x == 0)
        {
            $geo = "x{$y}>";
        }
        else if ($y == 0)
        {
            $geo = "{$x}x>";
        }
        else
        {
            $geo = "{$x}x{$y}>";
        }
        $geo = "-geometry " . escapeshellarg($geo);

        $cmd = "{$GLOBALS['midcom_config']['utility_imagemagick_base']}mogrify {$this->_quality} {$geo} "
            . escapeshellarg($this->_filename);

        $output = null;
        $exit_code = 0;
        exec($cmd, $output, $exit_code);

        if ($exit_code !== 0)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("ImageMagick failed to convert the image, see LOG_DEBUG for details.", MIDCOM_LOG_ERROR);
            debug_print_r("Imagemagick returned with {$exit_code} and produced this output:", $output, MIDCOM_LOG_ERROR);
            debug_add("Command was: {$cmd}", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        return true;
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
     * @return boolean true on success.
     */
    function squarethumb($x, $gravity = 'center')
    {
        return $this->crop($x, $x, $gravity);
    }

    /**
     * Crop an image to given proportions
     *
     * @access public
     * @param int $x Width
     * @param int $y Height
     * @return boolean true on success.
     */
    function crop($x, $y, $gravity = 'center')
    {
        $data = @getimagesize($this->_filename);
        $data = null;

        // 1a. If got...
        if ($data)
        {
            $size_x = $data[0];
            $size_y = $data[1];
        }
        else
        {
            // If image data was not available, try to get it with idenfity program
            $cmd = "{$GLOBALS['midcom_config']['utility_imagemagick_base']}identify -verbose {$this->_filename}";
            exec($cmd, $output, $exit_code);

            if ($exit_code !== 0)
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("ImageMagick failed to get image info, see LOG_DEBUG for details.", MIDCOM_LOG_ERROR);
                debug_print_r("Imagemagick returned with {$exit_code} and produced this output:", $output, MIDCOM_LOG_ERROR);
                debug_add("Command was: {$cmd}", MIDCOM_LOG_ERROR);
                debug_pop();
            }

            $output = implode("\n", $output);

            if (!preg_match('/Geometry:\s([0-9]+)x([0-9]+)/i', $output, $regs))
            {
                return false;
            }

            $size_x = (int) $regs[1];
            $size_y = (int) $regs[2];
        }

        // Get resize ratio in relations to the original
        $ratio = str_replace(',', '.', 100 * max($x / $size_x, $y / $size_y));
        $cmd = "{$GLOBALS['midcom_config']['utility_imagemagick_base']}mogrify {$this->_quality} -resize {$ratio}% -gravity {$gravity} -crop {$x}x{$y}+0+0 +repage " . escapeshellarg($this->_filename);
        exec($cmd, $output, $exit_code);

        if ($exit_code !== 0)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("ImageMagick failed to convert the image, see LOG_DEBUG for details.", MIDCOM_LOG_ERROR);
            debug_print_r("Imagemagick returned with {$exit_code} and produced this output:", $output, MIDCOM_LOG_ERROR);
            debug_add("Command was: {$cmd}", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        return true;
    }

    /**
     * Resize image and apply fill to match given size
     *
     * @access public
     * @param int $x Width
     * @param int $y Height
     * @param string $color Color
     * @param string $gravity Gravity point
     * @return boolean true on success.
     */
    function fill($x, $y, $color, $gravity = 'center')
    {
        // Currently accepting only hex colors
        if (!preg_match('/^#?([0-9a-f]{3}){1,2}$/', $color))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Given color ({$color}) is not hex RGB.", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        $cmd = "{$GLOBALS['midcom_config']['utility_imagemagick_base']}mogrify {$this->_quality} -resize '{$x}x{$y}' -background '{$color}' -gravity {$gravity} -extent {$x}x{$y} +repage " . escapeshellarg($this->_filename);

        $output = null;
        $exit_code = 0;
        exec($cmd, $output, $exit_code);

        if ($exit_code !== 0)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("ImageMagick failed to convert the image, see LOG_DEBUG for details.", MIDCOM_LOG_ERROR);
            debug_print_r("Imagemagick returned with {$exit_code} and produced this output:", $output, MIDCOM_LOG_ERROR);
            debug_add("Command was: {$cmd}", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        return true;
    }
}
?>