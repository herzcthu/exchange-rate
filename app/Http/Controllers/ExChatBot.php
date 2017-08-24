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

        $botman->hears('Who am I?', function($bot) {
            $user = $bot->getUser();
            $bot->reply('Hello '.$user->getFirstName().' '.$user->getLastName());
            $bot->reply('Your username is: '.$user->getUsername());
            $bot->reply('Your ID is: '.$user->getId());
        });

        $botman->hears("{message}([?\.၊။])", function (BotMan $bot, $message) {
            // Store information for the currently logged in user.
            // You can also pass a user-id / key as a second parameter.
            $bot->userStorage()->save([
                'message' => $message
            ]);

            $bot->reply($message);
        });

        $botman->fallback(function($bot) {
            $bot->reply('Sorry, I did not understand these commands.');
        });

        $botman->listen();

    }

}
