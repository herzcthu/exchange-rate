<?php

namespace App;
use Google\Cloud\Translate\TranslateClient;

class GoogleTranslate
{
    private $translate;

    public function __construct(TranslateClient $translate)
    {
        $this->translate = $translate;
    }

    /**
     * @return array
     */
    public function translate($text, $targetLanguage): array
    {
        return $this->translate->translate($text, [
            'target' => $targetLanguage,
        ]);
    }

    /**
     * @return TranslateClient
     */
    public function getLang($text)
    {
        $lang = $this->translate->detectLanguage($text);
        return $lang['languageCode'];
    }

}
