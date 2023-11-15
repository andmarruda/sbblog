<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use sysborg\btd;
use SplFileInfo;

class ImageController extends Controller
{
    /**
     * Allowed file extension
     * @var             array
     */
    CONST ALLOWED_EXTENSION = ['jpg', 'jpeg', 'bmp', 'gif', 'png', 'webp'];

    /**
     * Allowd max file size
     * @var             int
     */
    CONST ALLOWED_SIZE = 2000000;

    /**
     * Functions of GDLibrary of every extension
     * @var             array
     */
    private array $gdLibrary = [
        'jpg'  => 'imagecreatefromjpeg',
        'jpeg' => 'imagecreatefromjpeg',
        'bmp'  => 'imagecreatefromwbmp',
        'gif'  => 'imagecreatefromgif',
        'png'  => 'imagecreatefrompng',
        'webp' => NULL,
    ];

    /**
     * Class's variables
     * @var             []
     */
    private array $vars = [
        'error' => NULL,
        'store' => 'public',
        'name'  => NULL,
        'file'  => NULL
    ];

    /**
     * Add file validation to the entry of validation and input desired
     * @version     1.0.0
     * @author      Anderson Arruda < contato@sysborg.com.br >
     * @param       array $validate
     * @param       string $fielname
     * @return      array
     */
    public static function validateFile(array $validate, string $fielname) : array
    {
        return [
            ...$validate,
            $fielname => 'required|max:'. self::ALLOWED_SIZE. '|mimes:'. btd::implodeAllowedExtensions(',')
        ];
    }

    /**
     * Convert file to webp
     * @version     1.0.0
     * @author      Anderson Arruda < contato@sysborg.com.br >
     * @param       string $filepath
     * @param       bool $deleteOriginal
     * @return      string
     */
    public static function imageToWebp(string $file, string $originalExtension, bool $deleteOriginal) : string
    {
        $filepath = Storage::path($file);
        $b = new btd($filepath);
        $newfile = str_replace('.'. $originalExtension, '.webp', $filepath);
        $b->save($newfile, 'webp');

        if($deleteOriginal)
            File::delete($file);

        return str_replace('.'. $originalExtension, '.webp', $file);
    }

    /**
     * Remove exists file
     * @version     1.0.0
     * @author      Anderson Arruda < contato@sysborg.com.br >
     * @param       string $file      
     * @return      bool
     */
    public static function deleteFile(string $file) : bool
    {
        return File::delete($file);
    }

    /**
     * Set uploaded file and check if it's valid after this converts to webp format
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       private ?\Illuminate\Http\UploadedFile $file
     * @return      void
     */
    public function __construct(private ?UploadedFile $file)
    {
        if(is_null($file))
            return;

        $this->vars['file'] = $file;

        if(!$file->isValid()){
            $this->vars['error'] = 'INVALID_FILE';
            return;
        }

        if(!in_array($file->extension(), self::ALLOWED_EXTENSION)){
            $this->vars['error'] = 'EXTENSION_ERROR';
            return;
        }

        if($file->getSize() > self::ALLOWED_SIZE){
            $this->vars['error'] = 'SIZE_ERROR';
            return;
        }

        $uploaded = $file->store($this->store);
        $this->vars['name'] = basename($uploaded);
        $this->convertWebp($uploaded);
    }

    /**
     * Convert any image to webp except webp
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       string $file
     * @return      void
     */
    public function convertWebp(string $file) : void
    {
        $ext=$this->getExtension($file);
        $func = $this->gdLibrary[$ext];
        if(!is_null($func)){
            $image = $func(Storage::path($file));
            $dir = dirname(Storage::path($file));
            $name = str_replace($ext, 'webp', basename($file));
            imagewebp($image, $dir.'/'.$name);
            imagedestroy($image);
            Storage::delete($file);
            $this->vars['name'] = $name;
        }
    }

    /**
     * Get extension for a stored file
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       string $file
     * @return      string
     */
    public function getExtension(string $file) : string
    {
        $info = new SplFileInfo(Storage::path($file));
        return $info->getExtension();
    }

    /**
     * Returns the allowed size in Megabytes
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          float
     */
    public function byteToMb() : float
    {
        return self::ALLOWED_SIZE * 0.000001;
    }

    /**
     * Get variables's informations
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       string $varname
     * @return      mixed
     */
    public function __get(string $varname) : mixed
    {
        return $this->vars[$varname] ?? '';
    }
}
