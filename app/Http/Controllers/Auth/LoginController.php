<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\LoginToken;
use App\Providers\RouteServiceProvider;
use App\User;
use App\Utils\RoseUtils;
use App\Work;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use TheSeer\Tokenizer\Token;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function username()
    {
        return 'name';
    }

    protected function validateLogin(Request $request)
    {
        $request->validate(
            [
            $this->username() => 'required|string'
            ]
        );
    }

    protected function attemptLogin(Request $request)
    {
        if ($request->get('refer')) {
            Cookie::queue('after_login', $request->get('refer'));
        }
        $remember = $request->filled('remember');
        if ($request->input('password')) {
            /**
 * @var LoginToken $login_token 
*/
            LoginToken::where('time', '<', time() - 233)->delete();
            $login_token = LoginToken::where('username', '=', $request->input('name'))
                ->where('token', '=', $request->input('password'))->first();
            if ($login_token) {
                if (!$login_token->uid) {
                    throw ValidationException::withMessages(
                        [
                        'password' => '你别跑，你告诉我你这个token哪来的！'
                        ]
                    );
                }
                $user = User::where('uid', '=', $login_token->uid)->first();
                if ($user) {
                    $token = Str::random(32);
                    $user->name = $login_token->username;
                    $user->remember_token = $token;
                    $user->save();
                    Cookie::queue('iirose', $token);
                    Auth::guard()->login($user);
                } else {
                    $token = Str::random(32);
                    $user = new User();
                    $user->name = $login_token->username;
                    $user->uid = $login_token->uid;
                    $user->remember_token = $token;
                    $user->save();
                    Cookie::queue('iirose', $token);
                    Auth::guard()->login($user);
                }
                $login_token->delete();
                return true;
            } else {
                throw ValidationException::withMessages(
                    [
                    'password' => '验证码错误！'
                    ]
                );
            }
        } else {
            $token = Str::random(6);
            $rose_user = RoseUtils::getInfo($request->input('name'));
            var_dump($rose_user);
            if (!$rose_user) {
                throw ValidationException::withMessages(
                    [
                    'name' => '等等，连自己名字都不知道！'
                    ]
                );
            }
            $login_token = new LoginToken();
            $login_token->username = $request->input('name');
            $login_token->time = time();
            $login_token->token = $token;
            $login_token->uid = $rose_user->user_id;
            $login_token->save();
            RoseUtils::sendPersonChat(
                $rose_user->user_id, '你好，你的iirose-bot验证码为： ' . $token . ' ，有效期为233秒，打死也不要告诉别人哦~ '
                . route(
                    'fastauth', [
                    'name' => $request->input('name'),
                    'token' => $token,
                    ]
                ) . '#.png'
            );
            throw ValidationException::withMessages(
                [
                'password' => '验证码已发送！'
                ]
            );
        }
    }

    public function fastauth(Request $request)
    {
        if (!$request->input('name')) {
            return $this->image('name参数缺失');
        }
        if (!$request->input('token')) {
            return $this->image('token参数缺失');
        }
        /**
 * @var LoginToken $login_token 
*/
        LoginToken::where('time', '<', time() - 233)->delete();
        $login_token = LoginToken::where('username', '=', $request->input('name'))
            ->where('token', '=', $request->input('token'))->first();
        if ($login_token) {
            if (!$login_token->uid) {
                return $this->image('你别跑，你告诉我你这个token哪来的！');
            }
            $user = User::where('uid', '=', $login_token->uid)->first();
            if ($user) {
                $token = Str::random(32);
                $user->name = $login_token->username;
                $user->remember_token = $token;
                $user->save();
                Cookie::queue('iirose', $token);
                Auth::guard()->login($user);
            } else {
                $token = Str::random(32);
                $user = new User();
                $user->name = $login_token->username;
                $user->uid = $login_token->uid;
                $user->remember_token = $token;
                $user->save();
                Cookie::queue('iirose', $token);
                Auth::guard()->login($user);
            }
            return $this->image('登录成功~右键在新标签页打开');
        } else {
            return $this->image('你别跑，你告诉我你怎么看到这个图片的！');
        }
    }

    public function image($data)
    {
        $box=$this->calculateTextBox(12, 0, App::storagePath() . '/app/UbuntuMono-BI.ttf', $data);
        $img = imagecreatetruecolor($box['width'], $box['height']);
        $color = imagecolorallocate($img, 255, 255, 255);
        imagefttext($img, 12, 0, $box['left'], $box['top'], $color, App::storagePath() . '/app/UbuntuMono-BI.ttf', $data);
        ob_start();
        imagepng($img);
        $op = ob_get_contents();
        ob_end_clean();
        $response = response()->make($op);
        $response->header('Content-Type', 'image/png')
            ->header('refresh', 'url=' . $this->redirectTo());
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
    protected function authenticated(Request $request, User $user)
    {
    }

    protected function redirectTo()
    {
        return Cookie::get('after_login', route('home'));
    }
}
