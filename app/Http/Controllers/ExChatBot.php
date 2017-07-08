<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mpociot\BotMan\BotMan;

class ExChatBot extends Controller
{
    public function autoreply(Request $request)
    {
        $botman = app('botman');
        $botman->verifyServices(config('botman.facebook_app_secret'));
        // Simple respond method
        $botman->hears('Hello', function (BotMan $bot) {
            $bot->reply('Hi there :)');
        });
        $botman->listen();
    }

}
