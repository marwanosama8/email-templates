<?php

namespace Visualbuilder\EmailTemplates\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Visualbuilder\EmailTemplates\Helpers\CreateMailableHelper;
use Visualbuilder\EmailTemplates\Helpers\TenancyHelpers;

class EmailTemplateScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $currentTenant = TenancyHelpers::getTenantModelOutSideFilament();

        if (!is_null($currentTenant)) {
            $builder->where('emailable_type', get_class($currentTenant))
                ->where('emailable_id', $currentTenant->id);
        } else {
            $builder->where('created_at', '>', now()->subYears(5));
        }
    }
}
