<?php

namespace Visualbuilder\EmailTemplates;


use Illuminate\Support\Facades\View;
use Visualbuilder\EmailTemplates\Contracts\TokenReplacementInterface;
use Visualbuilder\EmailTemplates\Models\EmailTemplate;


class DefaultTokenHelper implements TokenReplacementInterface
{
    /**
     * Replace tokens in the content with actual values from the models.
     *
     * @param  string  $content  The content with tokens to be replaced
     * @param  array  $models  The models containing the values for the tokens
     *
     * @return string The content with replaced tokens
     */
    public function replaceTokens(string $content, $models): string
    {

        /**
         * Replace singular tokens for password reset and validations
         * Add custom tokens in the config
         */
        foreach (config('filament-email-templates.known_tokens') as $key){
            if (isset($models->{$key})) {
                $content = str_replace("##$key##", $models->{$key}, $content);
            }
        }

        /**
         * Replace model-attribute tokens.
         * Will look for pattern ##model.attribute## and replace the value if found.
         * Eg ##user.name## or create your own accessors in a model
         */
        $allowedKeys = config('filament-email-templates.allowed_template_keys');
        // Replace tokens for model-attribute pairs like ##invoice.number##
        preg_match_all('/##(.*?)\.(.*?)##/', $content, $matches);
    
        if (count($matches) > 0 && count($matches[0]) > 0) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $modelKey = $matches[1][$i]; // e.g. 'invoice'
                $attributeKey = $matches[2][$i]; // e.g. 'number', 'customer.name'
    
                // Check if the attribute is allowed
                if (in_array($attributeKey, $allowedKeys)) {
                    // Replace with the actual value if it exists
                    $replacement = (isset($models->$modelKey) && isset($models->$modelKey->$attributeKey))
                        ? $models->$modelKey->$attributeKey
                        : "";
                    $content = str_replace($matches[0][$i], $replacement, $content);
                } else {
                    // Replace disallowed tokens with "##NOT AVAILABLE KEY##"
                    $content = str_replace($matches[0][$i], "##NOT AVAILABLE KEY##", $content);
                }
            }
        }
        /**
         * Replace config tokens.
         * Define which tokens are allowed in this config setting
         */
        $allowedConfigKeys = config('filament-email-templates.config_keys');

        preg_match_all('/##config\.(.*?)##/', $content, $matches);
        if (count($matches) > 0 && count($matches[0]) > 0) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $configKey = $matches[1][$i];
                if (in_array($configKey, $allowedConfigKeys)) {
                    $configValue = config($configKey);
                    if ($configValue !== null) {
                        $content = str_replace($matches[0][$i], $configValue, $content);
                    }
                }
            }
        }


        if(isset($models->emailTemplate)){
            $button = $this->buildEmailButton($content, $models->emailTemplate);
            $content = self::replaceButtonToken($content, $button);
        }


        return $content;
    }

    private function buildEmailButton($content, $emailTemplate)
    {
        $title = $url = '';
        if (preg_match('/(?<=##button).*?(?=#)/', $content, $matches)) {
            if ($check1 = preg_match("/(?<=url=').*?(?='\s)/", $matches[ 0 ], $url)) {
                $url = $url[ 0 ];
            }
            if ($check2 = preg_match("/(?<=title=').*?(?=')/", $matches[ 0 ], $title)) {
                $title = $title[ 0 ];
            }
            if ($check1 && $check2) {

                return View::make('vb-email-templates::email.parts._button', [
                    'url' => $url,
                    'title' => $title,
                    'data' => ['theme' => $emailTemplate->theme->colours],
                ])
                    ->render();
            }
        };

        return '';
    }

    private static function replaceButtonToken($content, $button)
    {
        $search = "/(?<=##button).*?(?=##)/";
        $replace = "";
        $content = preg_replace($search, $replace, $content);
        $content = str_replace('##button##', $button, $content);

        return $content;
    }
}
