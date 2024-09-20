<?php

namespace Visualbuilder\EmailTemplates\Helpers;

use Visualbuilder\EmailTemplates\Contracts\FormHelperInterface;

class TenancyHelpers
{
    // you should add middleware in tenantMiddleware() that placed in tenant panel provider,and thid middleware logic must store the tenant id in session
    public static function getTenantModelOutSideFilament()
    {
        $tenantData = session('tenant_data');
    
        if ($tenantData) {
            $tenantClass = $tenantData['class'];
            $tenantId = $tenantData['id'];
    
            $tenantInstance = $tenantClass::find($tenantId);
    
            return $tenantInstance;
        }
    
        return null;
    }
    
}
