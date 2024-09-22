<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Visualbuilder\EmailTemplates\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmailTemplateSeeder extends Seeder
{
    public function run()
    {
        DB::table(config('filament-email-templates.table_name'))->delete();

        $roles = ['user', 'super_company', 'company'];

        foreach ($roles as $role) {
            $users = User::role($role)->get();

            if ($users->isNotEmpty()) {
                foreach ($users as $user) {
                    if ($role === 'company') {
                        $company = $user->companies()->first();
                        if ($company) {
                            $userId = $company->id;
                            $userTemplates = $this->prepareTemplatesForRole($this->getEmailTemplates($company), $role, $userId);
                            EmailTemplate::factory()->createMany($userTemplates);
                        }
                    } elseif ($role === 'super_company') {
                        $companies = $user->companies()->get();
                        foreach ($companies as $company) {
                            $userId = $company->id;
                            $userTemplates = $this->prepareTemplatesForRole($this->getEmailTemplates($company), $role, $userId);
                            EmailTemplate::factory()->createMany($userTemplates);
                        }
                    } else {
                        $userId = $user->id;
                        $userTemplates = $this->prepareTemplatesForRole($this->getEmailTemplates($user), $role, $userId);
                        EmailTemplate::factory()->createMany($userTemplates);
                    }
                }
            }
        }
    }


    private function getEmailTemplates($data)
    {
        return [
            [
                'logo_width' => '500',
                'logo_height' => '126',

                'content_width' => '600',

                'customer_services' => [
                    [
                        'key' => 'Customer Support Email',
                        'value' => $data->contact_email

                    ],
                    [
                        'key' => 'Phone',
                        'value' => '+99 111 999 111'

                    ],
                ],

                'links' => [
                    ['name' => 'Website', 'url' => $data->website_url, 'title' => 'Goto website'],
                    ['name' => 'Privacy Policy', 'url' => 'https://yourwebsite.com/privacy-policy', 'title' => 'View Privacy Policy'],
                ],
                'key'       => 'invoice-email',
                'from'      => ['email' => $data->contact_email, 'name' => $data->legal_name],
                'name'      => 'Customer Invoice',
                'title'     => "Your Invoice from {$data->legal_name}",
                'subject'   => "Invoice for your recent purchase from {$data->legal_name}",
                'preheader' => 'Here is your invoice',
                'content'   => "<p>Dear ##invoice.customer_name##,</p>
                                <p>Thank you for your recent purchase from {$data->legal_name}. Attached is your invoice for the transaction.</p>
                                <p>Invoice Details:</p>
                                <p>Invoice Number: ##invoice.number##</p>
                                <p>Date: ##invoice.date_origin##</p>
                                <p>If you have any questions regarding this invoice, please feel free to contact us at {$data->contact_email}.</p>
                                <p>Kind Regards,<br>{$data->legal_name}</p>"
            ],
            [
                'logo_width' => '500',
                'logo_height' => '126',

                //Content Width in Pixels
                'content_width' => '600',

                //Contact details included in default email templates

                'customer_services' => [
                    [
                        'key' => 'Customer Support Email',
                        'value' => $data->contact_email

                    ],
                    [
                        'key' => 'Phone',
                        'value' => '+99 11 9911 111'

                    ],
                ],


                //Footer Links
                'links' => [
                    ['name' => 'Website', 'url' => $data->website_url, 'title' => 'Goto website'],
                    ['name' => 'Privacy Policy', 'url' => 'https://yourwebsite.com/privacy-policy', 'title' => 'View Privacy Policy'],
                ],
                'key'       => 'invoice-reminder-email',
                'from'      => ['email' => $data->contact_email, 'name' => $data->legal_name],
                'name'      => 'Invoice Payment Reminder',
                'title'     => "Reminder: Invoice from {$data->contact_email}",
                'subject'   => "Reminder: Payment due for your invoice from {$data->contact_email}",
                'preheader' => 'Don’t forget to pay your invoice',
                'content'   => "<p>Dear ##invoice.customer_name##,</p>
                                <p>This is a friendly reminder that your invoice from {$data->legal_name} is still outstanding.</p>
                                <p>Invoice Details:</p>
                                <p>Invoice Number: ##invoice.number##</p>
                                <p>Date: ##invoice.date_origin##</p>
                                <p>Due Date: ##invoice.pay_date##</p>
                                <p>Please make the payment at your earliest convenience. If you have already settled this invoice, please disregard this message.</p>
                                <p>If you have any questions or need assistance, please contact us at {$data->contact_email}.</p>
                                <p>Kind Regards,<br>{$data->legal_name}</p>"
            ],
        ];
    }

    private function prepareTemplatesForRole(array $templates, string $role, int $userId)
    {
        $emailableType = $role === 'user' ? 'App\Models\User' : 'App\Models\Company';

        return array_map(function ($template) use ($emailableType, $userId) {
            return array_merge($template, [
                'emailable_type' => $emailableType,
                'emailable_id'   => $userId,
            ]);
        }, $templates);
    }
}
