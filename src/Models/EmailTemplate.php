<?php

namespace Visualbuilder\EmailTemplates\Models;

use App\Models\Company;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Visualbuilder\EmailTemplates\Database\Factories\EmailTemplateFactory;
use Visualbuilder\EmailTemplates\Facades\TokenHelper;
use Visualbuilder\EmailTemplates\Helpers\CreateMailableHelper;
use Visualbuilder\EmailTemplates\Helpers\TenancyHelpers;
use Visualbuilder\EmailTemplates\Models\Scopes\EmailTemplateScope;
use Visualbuilder\EmailTemplates\Models\Scopes\EmailTemplateThemeScope;

/**
 * @property int $id
 * @property string $key
 * @property array $from
 * @property string $name
 * @property string $view
 * @property object $cc
 * @property object $bcc
 * @property string $subject
 * @property string $title
 * @property string $preheader
 * @property string $language
 * @property string $content
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */

#[ScopedBy([EmailTemplateScope::class])]
class EmailTemplate extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        'from',
        'key',
        'name',
        'view',
        'subject',
        'title',
        'preheader',
        'content',
        'language',
        'logo',
        'logo_width',
        'logo_height',
        'content_width',
        'links',
        'customer_services',

    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'deleted_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'from' => 'array',
        'links' => 'array',
        'customer_services' => 'array',
    ];
    /**
     * @var string[]
     */
    protected $dates = ['deleted_at'];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['theme'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTableFromConfig();
    }

    protected static function boot()
    {
        parent::boot();

        // When an email template is updated
        static::updated(function ($template) {
            self::clearEmailTemplateCache($template->key, $template->language);
        });

        // When an email template is deleted
        static::deleted(function ($template) {
            self::clearEmailTemplateCache($template->key, $template->language);
        });
    }

    public function setTableFromConfig()
    {
        $this->table = config('filament-email-templates.table_name');
    }

    public static function findEmailByKey($key, $language = null)
    {
        // dd(config('filament-email-templates.default_locale'));
        try {
            // adding the tenant slug to unique the key
            $tenant  = TenancyHelpers::getTenantModelOutSideFilament();
            $cacheKey = "email_by_key_{$key}_{$language}_{$tenant->slug}";

            //For multi site domains this key will need to include the site_id
            return self::query()
                ->language($language ?? config('filament-email-templates.default_locale'))
                ->where("key", $key)
                ->firstOrFail();
        } catch (\Exception $e) {
            Log::error($e->getMessage() . $e->getFile());
            throw new Exception($e->getMessage() . $e->getFile());
        }
    }

    public static function clearEmailTemplateCache($key, $language)
    {
        $cacheKey = "email_by_key_{$key}_{$language}";
        Cache::forget($cacheKey);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public static function getSendToSelectOptions()
    {
        return collect(config('emailTemplate.recipients'));
    }

    /**
     * @return EmailTemplateFactory
     */
    protected static function newFactory()
    {
        return EmailTemplateFactory::new();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name ?? class_basename($this);
    }

    /**
     * Get the assigned theme or the default theme related to the current tenant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function theme()
    {
        return $this->belongsTo(EmailTemplateTheme::class, config('filament-email-templates.theme_table_name') . '_id')
            ->withDefault(function ($model) {
                return EmailTemplateTheme::where('is_default', true)
                    ->first();
            });
    }


    /**
     * Gets base64 encoded content - to add to an iframe
     *
     * @return string
     */
    public function getBase64EmailPreviewData()
    {
        /**
         * Iframes normally use src attribute to load content from a url
         * This means an extra http request
         *  Below method includes the content directly as base64 encoded
         */

        $data = $this->getEmailPreviewData();
        $content = view($this->view_path, ['data' => $data])->render();

        return base64_encode($content);
    }

    /**
     * @return array
     */
    public function getEmailPreviewData()
    {
        $models = self::createEmailPreviewData();

        return [
            'user' => $models->user,
            'content' => TokenHelper::replace($this->content ?? '', $models),
            'subject' => TokenHelper::replace($this->subject ?? '', $models),
            'preHeaderText' => TokenHelper::replace($this->preheader ?? '', $models),
            'title' => TokenHelper::replace($this->title ?? '', $models),
            'theme' => $this->theme->colours,
            'logo' => $this->logo,
            'configs'       => [
                'logo_width' =>  $this->logo_width,
                'logo_height' =>  $this->logo_height,
                'content_width' =>  $this->content_width,
                'links' =>  $this->links,
                'customer_services' =>  $this->customer_services,
                'company_name' =>  $this->emailable->legal_name,
            ]
        ];
    }

    /**
     * @return object
     */
    public static function createEmailPreviewData()
    {
        $models = (object)[];

        $userModel = config('filament-email-templates.recipients')[0];
        //Setup some data for previewing email template
        $models->user = $userModel::first();
        $models->tokenUrl = URL::to('/');
        $models->verificationUrl = URL::to('/');
        $models->expiresAt = now();
        /* Not used in preview but need to add something */
        $models->plainText = Str::random(32);

        return $models;
    }

    /**
     * Efficient method to return requested template locale or default language template in one query
     *
     * @param Builder $query
     * @param $language
     *
     * @return Builder
     */
    public function scopeLanguage(Builder $query, $language)
    {
        $languages = [$language, config('filament-email-templates.default_locale')];
        return $query->whereIn('language', $languages)
            ->orderByRaw(
                "(CASE WHEN language = ? THEN 1 ELSE 2 END)",
                [$language]
            );
    }

    /**
     * @return Attribute
     */
    public function viewPath(): Attribute
    {
        return new Attribute(
            get: fn() => config('filament-email-templates.template_view_path') . '.' . $this->view
        );
    }

    /**
     * @return bool
     */
    public function getMailableExistsAttribute(): bool
    {
        $className = Str::studly($this->key);
        $filePath = app_path(config('filament-email-templates.mailable_directory') . "/{$className}.php");

        return File::exists($filePath);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getMailableClass()
    {
        $className = Str::studly($this->key);
        $directory = str_replace('/', '\\', config('filament-email-templates.mailable_directory', 'Mail/Visualbuilder/EmailTemplates'));
        $fullClassName = "App\\" . rtrim($directory, '\\') . "\\{$className}";

        if (!class_exists($fullClassName)) {
            throw new \Exception("Mailable class {$fullClassName} does not exist.");
        }

        return $fullClassName;
    }


    public function getLogoAttribute(): string
    {
        //Get Database logo or config logo
        $logo = $this->attributes['logo'] ?? $this->emailable->avatar_url;

        // Return the logo if it's a full URL, otherwise, return the asset URL.
        return Str::isUrl($logo) ? $logo : asset($logo);
    }

    // new
    protected static function booted(): void
    {
        static::creating(function (EmailTemplate $model) {

            $currentTenant = filament()->getTenant();
            if (empty($model->emailable_type)) {
                $model->emailable_type = is_null($currentTenant) ? 'App\Models\User' : 'App\Models\Company';
            }
            if (empty($model->emailable_id)) {
                $model->emailable_id = is_null($currentTenant) ? auth()->id() : $currentTenant->id;
            }
        });
    }

    public function emailable()
    {
        return $this->morphTo();
    }
}
