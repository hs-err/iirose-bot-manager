<?php

namespace App\Http\Controllers;

use App\Bot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $bot = Bot::find(Auth::id());
        if (!$bot) {
            $bot = new Bot();
            $bot->id = Auth::id();
            $bot->save();
        }
        return view('home')->with(
            [
            'bot' => $bot
            ]
        );
    }

    public function update(Request $request)
    {
        $bot = Bot::findorFail(Auth::id());
        $bot->username = $request->input('username');
        $bot->password = $request->input('password');
        $bot->room = $request->input('room');
        $bot->plugins = $request->input('plugins');
        $bot->config = $request->input('config');
        $bot->save();
        return view('home')->with(
            [
            'bot' => $bot
            ]
        );
    }
}
