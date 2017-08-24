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

        $botman = app('botman');
        $botman->verifyServices(env('CHATBOT_TOKEN'));
        // Simple respond method
        $botman->hears('Hello', function (BotMan $bot) {
            $bot->reply('Hi there :)');
        });

        $botman->hears("{name}", function (BotMan $bot, $name) {
            // Store information for the currently logged in user.
            // You can also pass a user-id / key as a second parameter.
//            $bot->userStorage()->save([
//                'name' => $name
//            ]);

            $bot->reply($name);
        });

        $botman->listen();

    }

}
