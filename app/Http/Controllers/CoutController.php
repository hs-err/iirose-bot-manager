<?php

namespace App\Http\Controllers;

use App\Bot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CoutController extends Controller
{
    public function cout(Request $request)
    {
        $text=Cache::get('bot_docker_echo_'.$request->input('c'), '未找到');
        if (!$text) {
            $text='无输出';
        }
        return str_ireplace("\n", '<br>', htmlentities($text));
        $box=$this->calculateTextBox(12, 0, App::storagePath() . '/app/UbuntuMono-BI.ttf', $text);
        $img = imagecreatetruecolor($box['width'], $box['height']);
        $color = imagecolorallocate($img, 255, 255, 255);
        imagefttext($img, 12, 0, $box['left'], $box['top'], $color, App::storagePath() . '/app/UbuntuMono-BI.ttf', $text);
        ob_start();
        imagepng($img);
        $op = ob_get_contents();
        ob_end_clean();
        $response = response()->make($op);
        $response->header('Content-Type', 'image/png');
        return $response;
    }
    private function calculateTextBox($font_size, $font_angle, $font_file, $text)
    {
        $box   = imagettfbbox($font_size, $font_angle, $font_file, $text);
        if (!$box) {
            return false;
        }
        $min_x = min(array($box[0], $box[2], $box[4], $box[6]));
        $max_x = max(array($box[0], $box[2], $box[4], $box[6]));
        $min_y = min(array($box[1], $box[3], $box[5], $box[7]));
        $max_y = max(array($box[1], $box[3], $box[5], $box[7]));
        $width  = ( $max_x - $min_x );
        $height = ( $max_y - $min_y );
        $left   = abs($min_x) + $width;
        $top    = abs($min_y) + $height;
        // to calculate the exact bounding box i write the text in a large image
        $img     = @imagecreatetruecolor($width << 2, $height << 2);
        $white   =  imagecolorallocate($img, 255, 255, 255);
        $black   =  imagecolorallocate($img, 0, 0, 0);
        imagefilledrectangle($img, 0, 0, imagesx($img), imagesy($img), $black);
        // for sure the text is completely in the image!
        imagettftext(
            $img,
            $font_size,
            $font_angle,
            $left,
            $top,
            $white,
            $font_file,
            $text
        );
        // start scanning (0=> black => empty)
        $rleft  = $w4 = $width<<2;
        $rright = 0;
        $rbottom   = 0;
        $rtop = $h4 = $height<<2;
        for ($x = 0; $x < $w4; $x++) {
            for ($y = 0; $y < $h4; $y++) {
                if (imagecolorat($img, $x, $y)) {
                    $rleft   = min($rleft, $x);
                    $rright  = max($rright, $x);
                    $rtop    = min($rtop, $y);
                    $rbottom = max($rbottom, $y);
                }
            }
        }
        // destroy img and serve the result
        imagedestroy($img);
        return array( 'left'   => $left - $rleft,
            'top'    => $top  - $rtop,
            'width'  => $rright - $rleft + 1,
            'height' => $rbottom - $rtop + 1 );
    }
}
