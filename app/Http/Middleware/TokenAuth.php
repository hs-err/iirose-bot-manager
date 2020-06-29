<?php


namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use App\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class TokenAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  Closure $next
     * @return Response
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $token = $request->cookie('iirose');
        if (!$token) {
            Auth::logout();
        } else {
            $user = User::where('remember_token', '=', $token)->first();
            if ($user) {
                Auth::login($user);
            } else {
                Auth::logout();
            }
        }
        return $next($request);
    }
}
