<?php

namespace App\Traits;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\File;
use PragmaRX\Google2FA\Support\Base32;
use Intervention\Image\Facades\Image;
use App\Model\TrustedBusiness;
/**
 * Trait UtilityTrait
 *
 * @package App\Traits
 */
trait UtilityTrait
{

    /**
     * @param string $plain_text
     * @param string $hashed
     *
     * @return mixed
     */
    public static function hash_check(string $plain_text, string $hashed) {
        return app('hash')->check($plain_text, $hashed);
    }


    /**
     * @param string $plain_text
     *
     * @return mixed
     */
    public static function b_crypt(string $plain_text) {
        return app('')->make($plain_text);
    }


    /**
     * @return \Laravel\Lumen\Application|mixed
     */
    public static function request() {
        return app('request');
    }


    /**
     * @param $url
     *
     * @return bool
     */
    public static function urlValidator($url) :bool {
        $url = preg_match(
            '_^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]-*)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]-*)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$_iuS',
            $url
        );
        if($url)
            return true;
        return false;
    }


    /**
     * @param $file
     * @param string $dir
     *
     * @return mixed
     */
    public static function imageUploader($file, string $dir) :string {
        $dir = app()->basePath($dir);

        if( false === File::exists($dir) )
            File::makeDirectory($dir, 0777, true);

        $filename = static::fileNameExtractor($file);

        return $file->move($dir, $filename);
    }


    /**
     * @param $file
     *
     * @return mixed|string
     */
    public static function fileNameExtractor($file) :string {
        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION );

        $filename = $filename . time() . '.' . $extension;

        return $filename;
    }


    /**
     * @param string|null $message
     * @param $data
     * @param int $code
     * @param string $status
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function response(string $message = null, $data = null, int $code, string $status) {
        return response()->json([
            'status' => $status,
            'code' => $code,
            'data' => $data,
            'message' => $message
        ]);
    }


    /**
     * @param $data
     * @param bool $encode
     *
     * @return string
     */
    public static function responseJwtEncoder($data, $encode = true) {
        if( $encode )
            return JWT::encode($data, env('JWT_SECRET_KEY'));
        return $data;
    }

    /**
     * Generate a secret key in Base32 format
     *
     * @return string
     */
    private function generateSecret()
    {
        $randomBytes = random_bytes(10);

        return Base32::encodeUpper($randomBytes);
    }

    /**
     * Generate a random string
     *
     * @return string
     */
    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function uploadBusinessLogo($logo, $business_id, $format)  {

        $target_path_orig = app()->basePath('public/image/uploads/business_logo/original/');
        $target_path_thumb = app()->basePath('public/image/uploads/business_logo/thumbnail/');

        if( false === File::exists($target_path_orig) )
            File::makeDirectory($target_path_orig, 0777, true);

        $save_orig = Image::make($logo)->save($target_path_orig . $business_id.'.'.$format);

    
        if( false === File::exists($target_path_thumb) )
            File::makeDirectory($target_path_thumb, 0777, true);

        $data = '';
        if (preg_match('/^data:image\/(\w+);base64,/', $logo)) {
            $data = substr($logo, strpos($logo, ',') + 1);
            $data = base64_decode($data);
        }  

        $logo = Image::make($logo);
        if($logo->height() > 1000){
            $new_height = $logo->height() * 0.5;
            $logo->heighten($new_height);
            
        }

        if($logo->width() > 1000) {
            $new_width = $logo->width() * 0.5;
            $logo->widen($new_width);
        }
    
        $save_thumb = $logo->encode($format,50)->save($target_path_thumb . $business_id.'.'.$format);
        if($save_orig && $save_thumb){
            $business = TrustedBusiness::find($business_id);
            $business->business_logo = $business_id.'.'.$format;
            if($business->save()){
                return true;    
            }
        }
        return false;
    }

}