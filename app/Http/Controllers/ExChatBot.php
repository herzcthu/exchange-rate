<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mpociot\BotMan\BotMan;

class ExChatBot extends Controller
{
    protected $botman;

    public function __construct(BotMan $botMan)
    {
        $this->botman = $botMan;
        $this->botman->verifyServices(config('botman.facebook_app_secret'));
    }

    public function autoreply(Request $request)
    {
        $this->botman->hears('hello', function (BotMan $bot) {
            $bot->reply('Hello yourself.');
        });

        $this->botman->listen();
    }

}
