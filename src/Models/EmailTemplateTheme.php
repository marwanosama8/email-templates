<?php

namespace Visualbuilder\EmailTemplates\Models;

use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Visualbuilder\EmailTemplates\Database\Factories\EmailTemplateThemeFactory;
use Visualbuilder\EmailTemplates\Helpers\CreateMailableHelper;
use Visualbuilder\EmailTemplates\Models\Scopes\EmailTemplateThemeScope;

#[ScopedBy([EmailTemplateThemeScope::class])]
class EmailTemplateTheme extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'colours',
        'is_default',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'colours' => 'array',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $dates = ['deleted_at'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTableFromConfig();
    }

    public function setTableFromConfig()
    {
        $this->table = config('filament-email-templates.theme_table_name');
    }

    protected static function newFactory()
    {
        return EmailTemplateThemeFactory::new();
    }

    //new 
    protected static function booted(): void
    {
        static::creating(function (EmailTemplateTheme $model) {

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
