<?php

namespace App\Http\Middleware\BotmanMiddleware;

use App\GoogleTranslate;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Interfaces\MiddlewareInterface;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Middleware\ApiAi;

class ApiAiGoogleTranslate extends ApiAi implements MiddlewareInterface
{

    protected $translate;

    public function translate(GoogleTranslate $translate)
    {
        $this->translate = $translate;
        return $this;
    }

    /**
     * Perform the API.ai API call and cache it for the message.
     * @param  \BotMan\BotMan\Messages\Incoming\IncomingMessage $message
     * @return stdClass
     */
    protected function getResponse(IncomingMessage $message)
    {
        $text = $message->getText();
        $lang = $this->translate->getLang($text);

        if($lang != 'en') {
            $translated = $this->translate->translate($text, 'en');
            $text = $translated['text'];
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
