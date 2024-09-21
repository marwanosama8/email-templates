<?php

namespace Visualbuilder\EmailTemplates\Traits;

use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Visualbuilder\EmailTemplates\Facades\TokenHelper;
use Visualbuilder\EmailTemplates\Models\EmailTemplate;
use Visualbuilder\EmailTemplates\Models\Scopes\EmailTemplateThemeScope;

trait BuildGenericEmail
{

        public $emailTemplate;

        /**
         * Build the message.
         *
         * @return $this
         */
        public function build()
        {
                $this->emailTemplate = EmailTemplate::findEmailByKey($this->template, App::currentLocale());
                if ($this->attachment ?? false) {
                        $this->attachData(
                                $this->attachment['file'],
                                $this->attachment['filename'],
                                [
                                        'mime' => 'application/pdf'
                                ]
                        );
                }
                $data = [
                        'content'       => TokenHelper::replace($this->emailTemplate->content, $this),
                        'preHeaderText' => TokenHelper::replace($this->emailTemplate->preheader, $this),
                        'title'         => TokenHelper::replace($this->emailTemplate->title, $this),
                        'theme'         => $this->emailTemplate->theme->colours,
                        'logo'          => $this->emailTemplate->logo,
                        'configs'       => [
                                'logo_width' =>  $this->emailTemplate->logo_width,
                                'logo_height' =>  $this->emailTemplate->logo_height,
                                'content_width' =>  $this->emailTemplate->content_width,
                                'links' =>  $this->emailTemplate->links,
                                'customer_services' =>  $this->emailTemplate->customer_services,
                                'company_name' =>  $this->emailTemplate->emailable->legal_name,
                        ]
                ];
                return $this->from($this->emailTemplate->from['email'], $this->emailTemplate->from['name'])
                        ->view($this->emailTemplate->view_path)
                        ->subject(TokenHelper::replace($this->emailTemplate->subject, $this))
                        ->to($this->sendTo)
                        ->with(['data' => $data]);
        }
}
