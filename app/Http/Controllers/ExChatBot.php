<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Mpociot\BotMan\BotMan;

class ExChatBot extends Controller
{
    public function autoreply()
    {
        Log::info(Request::all());
        $botman = app('botman');

        $botman->verifyServices(config('services.botman.chatbot_verify'));
        // Simple respond method
        $botman->hears('Hello', function (BotMan $bot) {
            $bot->reply('Hi there :)');
        });
        $botman->listen();
    }

}
