<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\BotManFactory;

class ExChatBot extends Controller
{
    public function autoreply()
    {
        Log::info(Request::all());
        $config = config('services.botman');
        $botman = BotManFactory::create($config);

        $botman->verifyServices(config('services.botman.chatbot_verify'));
        // Simple respond method
        $botman->hears('Hello', function (BotMan $bot) {
            $bot->reply('Hi there :)');
        });
        $botman->listen();
    }

}
