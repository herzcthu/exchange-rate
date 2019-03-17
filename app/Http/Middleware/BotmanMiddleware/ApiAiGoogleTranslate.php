<?php

namespace App\Http\Middleware\BotmanMiddleware;

use App\GoogleTranslate;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Interfaces\MiddlewareInterface;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Middleware\ApiAi;

class ApiAiGoogleTranslate extends ApiAi implements MiddlewareInterface
{

    /**
     * Perform the API.ai API call and cache it for the message.
     * @param  \BotMan\BotMan\Messages\Incoming\IncomingMessage $message
     * @return stdClass
     */
    protected function getResponse(IncomingMessage $message)
    {
        $translate = new GoogleTranslate();
        $text = $message->getText();
        $lang = $translate->getLang($text);

        if($lang != 'en') {
            $text = $translate->translate($text, 'en');
        }

        $response = $this->http->post($this->apiUrl, [], [
            'query' => [$text],
            'sessionId' => md5($message->getRecipient()),
            'lang' => 'en',
        ], [
            'Authorization: Bearer '.$this->token,
            'Content-Type: application/json; charset=utf-8',
        ], true);

        $this->response = json_decode($response->getContent());

        return $this->response;
    }

}
