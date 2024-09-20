<?php

namespace Database\Seeders;

use App\Models\User;
use Visualbuilder\EmailTemplates\Models\EmailTemplateTheme;
use Illuminate\Database\Seeder;

class EmailTemplateThemeSeeder extends Seeder
{
    public function run()
    {
        $themes = $this->getThemes();
        $roles = ['user', 'super_company', 'company'];
    
        foreach ($roles as $role) {
            $users = User::role($role)->get();
    
            if ($users->isNotEmpty()) {
                foreach ($users as $user) {
                    if ($role === 'company') {
                        
                        $company = $user->companies()->first(); 
                        if ($company) {
                            $userId = $company->id;
                            $userThemes = $this->prepareThemesForRole($themes, $role, $userId);
                            EmailTemplateTheme::factory()->createMany($userThemes);
                        }
                    } elseif ($role === 'super_company') {
                        $companies = $user->companies()->get();
                        foreach ($companies as $company) {
                            $userId = $company->id;
                            $userThemes = $this->prepareThemesForRole($themes, $role, $userId);
                            EmailTemplateTheme::factory()->createMany($userThemes);
                        }
                    } else {
                        $userId = $user->id;
                        $userThemes = $this->prepareThemesForRole($themes, $role, $userId);
                        EmailTemplateTheme::factory()->createMany($userThemes);
                    }
                }
            }
        }
    }
    
    private function getThemes()
    {
        return [
            [
                'name' => 'Modern Bold',
                'colours' => [
                    'header_bg_color'  => '#1E88E5',
                    'content_bg_color' => '#FFFFFB',
                    'body_bg_color'    => '#f4f4f4',
                    'body_color'       => '#333333',
                    'footer_bg_color'  => '#34495E',
                    'footer_color'     => '#FFFFFB',
                    'callout_bg_color' => '#FFC107',
                    'callout_color'    => '#212121',
                    'button_bg_color'  => '#FFC107',
                    'button_color'     => '#2A2A11',
                    'anchor_color'     => '#1E88E5',
                ],
                'is_default' => 1,
            ],
            [
                'name' => 'Pastel',
                'colours' => [
                    'header_bg_color'  => '#B8B8D1',
                    'body_bg_color'    => '#f4f4f4',
                    'content_bg_color' => '#FFFFFB',
                    'footer_bg_color'  => '#5B5F97',
                    'callout_bg_color' => '#B8B8D1',
                    'button_bg_color'  => '#FFC145',
                    'body_color'       => '#333333',
                    'callout_color'    => '#000000',
                    'button_color'     => '#2A2A11',
                    'anchor_color'     => '#4c05a1',
                ],
                'is_default' => 0,
            ],
            [
                'name' => 'Elegant Contrast',
                'colours' => [
                    'header_bg_color'  => '#8E24AA',
                    'body_bg_color'    => '#f4f4f4',
                    'content_bg_color' => '#FFFFFB',
                    'footer_bg_color'  => '#6A1B9A',
                    'callout_bg_color' => '#E91E63',
                    'button_bg_color'  => '#FFEB3B',
                    'body_color'       => '#333333',
                    'callout_color'    => '#FFFFFF',
                    'button_color'     => '#2A2A11',
                    'anchor_color'     => '#8E24AA',
                ],
                'is_default' => 0,
            ],
            [
                'name' => 'Earthy & Calm',
                'colours' => [
                    'header_bg_color'  => '#43A047',
                    'body_bg_color'    => '#f4f4f4',
                    'content_bg_color' => '#FFFFFB',
                    'footer_bg_color'  => '#2E7D32',
                    'callout_bg_color' => '#FF7043',
                    'button_bg_color'  => '#FFEB3B',
                    'body_color'       => '#333333',
                    'callout_color'    => '#212121',
                    'button_color'     => '#2A2A11',
                    'anchor_color'     => '#43A047',
                ],
                'is_default' => 0,
            ],
        ];
    }

    private function prepareThemesForRole(array $themes, string $role, int $userId)
    {
        // Determine the emailable type based on the role
        $emailableType = $role === 'user' ? 'App\Models\User' : 'App\Models\Company';

        // Prepare themes for the user or company
        return array_map(function ($theme) use ($emailableType, $userId) {
            return array_merge($theme, [
                'emailable_type' => $emailableType,
                'emailable_id'   => $userId,
            ]);
        }, $themes);
    }
}
