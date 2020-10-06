<?php

namespace Studiobit\Base;

class PullSchema
{
    public static function OnGetDependentModule()
    {
        return Array(
            'MODULE_ID' => MODULE_ID,
            'USE' => Array('PUBLIC_SECTION')
        );
    }
}