<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mpociot\BotMan\BotMan;

class ExChatBot extends Controller
{
    public function autoreply(Request $request)
    {
        Log::info(json_encode($request->headers->all()));
        Log::info(json_encode($request->all()));
        $config = config('services.botman');
        $botman = app('botman');
        // Simple respond method
        $botman->hears('Hello', function (BotMan $bot) {
            $bot->reply('Hi there :)');
        });
        $botman->listen();
    }

}
