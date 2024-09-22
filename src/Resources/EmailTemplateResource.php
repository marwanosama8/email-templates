<?php

namespace Visualbuilder\EmailTemplates\Resources;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use FilamentTiptapEditor\TiptapEditor;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Visualbuilder\EmailTemplates\Contracts\CreateMailableInterface;
use Visualbuilder\EmailTemplates\Contracts\FormHelperInterface;
use Visualbuilder\EmailTemplates\EmailTemplatesPlugin;
use Visualbuilder\EmailTemplates\Models\EmailTemplate;
use Visualbuilder\EmailTemplates\Resources\EmailTemplateResource\Pages;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static bool $isScopedToTenant = false;

    public static function getNavigationGroup(): ?string
    {
        return EmailTemplatesPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-email-templates.navigation.templates.sort');
    }

    public static function getModelLabel(): string
    {
        return __('vb-email-templates::email-templates.resource_name.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('vb-email-templates::email-templates.resource_name.plural');
    }

    public static function getCluster(): string
    {
        return config('filament-email-templates.navigation.templates.cluster');
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return config('filament-email-templates.navigation.templates.position');
    }

    public static function form(Form $form): Form
    {

        $formHelper = app(FormHelperInterface::class);
        $templates = $formHelper->getTemplateViewOptions();

        return $form->schema(
            [
                Section::make()
                    ->schema(
                        [
                            Grid::make(['default' => 1])
                                ->schema(
                                    [
                                        TextInput::make('name')
                                            ->live()
                                            ->label(__('vb-email-templates::email-templates.form-fields-labels.template-name'))
                                            ->hint(__('vb-email-templates::email-templates.form-fields-labels.template-name-hint'))
                                            ->required(),
                                    ]
                                ),

                            Grid::make(['default' => 1, 'sm' => 1, 'md' => 2])
                                ->schema(
                                    [
                                        Select::make('language')
                                            ->options($formHelper->getLanguageOptions())
                                            ->default(config('filament-email-templates.default_locale'))
                                            ->searchable()
                                            ->allowHtml(),
                                        Select::make(config('filament-email-templates.theme_table_name') . '_id')
                                            ->label(__('vb-email-templates::email-templates.form-fields-labels.theme'))
                                            ->relationship(name: 'theme', titleAttribute: 'name')
                                            ->native(false),
                                        TextInput::make('from.email')->default(config('mail.from.address'))
                                            ->label(__('vb-email-templates::email-templates.form-fields-labels.email-from'))
                                            ->email(),
                                        TextInput::make('from.name')->default(config('mail.from.name'))
                                            ->label(__('vb-email-templates::email-templates.form-fields-labels.email-from-name'))
                                            ->string(),

                                    ]
                                ),

                            Grid::make(['default' => 1])
                                ->schema(
                                    [
                                        TextInput::make('subject')
                                            ->label(__('vb-email-templates::email-templates.form-fields-labels.subject')),
                                        TextInput::make('preheader')
                                            ->label(__('vb-email-templates::email-templates.form-fields-labels.header'))
                                            ->hint(__('vb-email-templates::email-templates.form-fields-labels.header-hint')),

                                        TextInput::make('title')
                                            ->label(__('vb-email-templates::email-templates.form-fields-labels.title'))
                                            ->hint(__('vb-email-templates::email-templates.form-fields-labels.title-hint')),
                                        TextInput::make('content_width')
                                            ->default(600)
                                            ->numeric()
                                            ->required()
                                            ->maxValue(1200)
                                            ->minValue(480)
                                            ->label(__('vb-email-templates::email-templates.form-fields-labels.content_width'))
                                            ->hint(__('vb-email-templates::email-templates.form-fields-labels.content-width')),
                                        TiptapEditor::make('content')
                                            ->tools([])
                                            ->hint(function() {
                                                $allowedKeys = config('filament-email-templates.allowed_template_keys', []);
                                                
                                                // Build the hint content based on allowed keys
                                                $hintContent = '<p>';
                                                foreach ($allowedKeys as $key) {
                                                    $hintContent .= '- <code>##invoice.' . htmlspecialchars($key) . '##</code> => The ' . ucfirst(str_replace('_', ' ', $key)) . ' <br>';
                                                }
                                                $hintContent .= '</p>';
                                            
                                                return new HtmlString($hintContent);
                                            })
                                            


                                            ->label(__('vb-email-templates::email-templates.form-fields-labels.content'))
                                            ->profile('default')
                                            ->default("<p>Dear ##user.firstname##, </p>"),
                                        Radio::make('logo_type')
                                            ->label(__('vb-email-templates::email-templates.form-fields-labels.logo-type'))
                                            ->options([
                                                'my_logo' => __('vb-email-templates::email-templates.form-fields-labels.my_logo'),
                                                'paste_url' => __('vb-email-templates::email-templates.form-fields-labels.paste-url'),
                                            ])
                                            ->default('my_logo')
                                            ->inline()
                                            ->live(),

                                        TextInput::make('logo_url')
                                            ->label(__('vb-email-templates::email-templates.form-fields-labels.logo-url'))
                                            ->hint(__('vb-email-templates::email-templates.form-fields-labels.logo-url-hint'))
                                            ->placeholder('https://www.example.com/media/test.png')
                                            ->hidden(fn(Get $get) => $get('logo_type') !== 'paste_url')
                                            ->activeUrl()
                                            ->maxLength(191),
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('logo_width')
                                                    ->label(__('vb-email-templates::email-templates.form-fields-labels.logo-width'))
                                                    ->default('500')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(250)
                                                    ->maxValue(500),
                                                TextInput::make('logo_height')
                                                    ->label(__('vb-email-templates::email-templates.form-fields-labels.logo-height'))
                                                    ->default('126')
                                                    ->required()
                                                    ->numeric()
                                                    ->minValue(126)
                                                    ->maxValue(200),
                                            ]),
                                        Repeater::make('customer_services')
                                            ->minItems(1)
                                            ->label(__('vb-email-templates::email-templates.form-fields-customer_services'))
                                            ->schema([
                                                TextInput::make('key')
                                                    ->label(__('vb-email-templates::email-templates.form-fields-customer_services.email'))
                                                    ->default('support@yourcompany.com')
                                                    ->required(),
                                                TextInput::make('value')
                                                    ->label(__('vb-email-templates::email-templates.form-fields-customer_services.phone'))
                                                    ->default('+441273 455702')
                                                    ->required(),
                                            ]),
                                        Repeater::make('links')
                                            ->label(__('vb-email-templates::email-templates.form-fields-links'))
                                            ->minItems(1)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label(__('vb-email-templates::email-templates.form-fields-links.name'))
                                                    ->default('website')
                                                    ->required(),
                                                TextInput::make('url')
                                                    ->default('https://yourwebsite.com')
                                                    ->label(__('vb-email-templates::email-templates.form-fields-links.url'))
                                                    ->required(),
                                                TextInput::make('title')
                                                    ->default('Go To Website')
                                                    ->label(__('vb-email-templates::email-templates.form-fields-links.title'))
                                                    ->required(),
                                            ]),

                                    ]
                                ),

                        ]
                    ),
            ]
        );
    }
    public static function table(Table $table): Table
    {

        return $table
            ->query(EmailTemplate::query())
            ->columns(
                [
                    // TextColumn::make('id')
                    //     ->sortable()
                    //     ->searchable(),
                    TextColumn::make('name')
                        ->limit(50)
                        ->sortable()
                        ->searchable(),
                    TextColumn::make('language')
                        ->limit(50),
                    TextColumn::make('subject')
                        ->searchable()
                        ->limit(50),
                ]
            )
            ->filters(
                [
                ]
            )
            ->actions(
                [
                    // Action::make('create-mail-class')
                    //     ->label("Build Class")
                    //     //Only show the button if the file does not exist
                    //     ->visible(function (EmailTemplate $record) {
                    //         return !$record->mailable_exists;
                    //     })
                    //     ->icon('heroicon-o-document-text')
                    //     // ->action('createMailClass'),
                    //     ->action(function (EmailTemplate $record) {
                    //         $notify = app(CreateMailableInterface::class)->createMailable($record);
                    //         Notification::make()
                    //             ->title($notify->title)
                    //             ->icon($notify->icon)
                    //             ->iconColor($notify->icon_color)
                    //             ->duration(10000)
                    //             //Fix for bug where body hides the icon
                    //             ->body("<span style='overflow-wrap: anywhere;'>" . $notify->body . "</span>")
                    //             ->send();
                    //     }),
                    Tables\Actions\ViewAction::make('Preview')
                        ->icon('heroicon-o-magnifying-glass')
                        ->modalContent(fn(EmailTemplate $record): View => view(
                            'vb-email-templates::forms.components.iframe',
                            ['record' => $record],
                        ))->form(null)
                        ->modalHeading(fn(EmailTemplate $record): string => 'Preview Email: ' . $record->name)
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->slideOver(),
                    Tables\Actions\EditAction::make(),
                    // Tables\Actions\DeleteAction::make(),
                    // Tables\Actions\ForceDeleteAction::make()
                    //     ->before(function (EmailTemplate $record, EmailTemplateResource $emailTemplateResource) {
                    //         $emailTemplateResource->handleLogoDelete($record->logo);
                    //     }),
                    // Tables\Actions\RestoreAction::make(),
                ]
            )
            ->bulkActions(
                [
                ]
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailTemplates::route('/'),
            // 'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes(
                [
                    SoftDeletingScope::class,
                ]
            );
    }

    public function handleLogo(array $data): array
    {
        if ($data['logo_type'] == "paste_url" && $data['logo_url']) {
            $data['logo'] = $data['logo_url'];
        } else {
            $data['logo'] = null;
        }
        unset($data['logo_type']);
        return $data;
    }



    public function handleLogoDelete($logo)
    {
        if ($logo && !Str::isUrl($logo)) {
            $logoPath = storage_path('app/public/' . $logo);
            if (File::exists($logoPath)) {
                File::delete($logoPath);
            }
        }
    }
}
