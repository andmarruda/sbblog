<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
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
        $func = $this->gdLibrary[$file->extension()];
        if(!is_null($func)){
            $image = $this->gdLibrary[$file->extension()](Storage::path($uploaded));
            $dir = dirname(Storage::path($uploaded));
            $name = str_replace($file->extension(), 'webp', $this->name);
            imagewebp($image, $dir.'/'.$name);
            imagedestroy($image);
            Storage::delete($uploaded);
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
