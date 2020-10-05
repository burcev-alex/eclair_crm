<?
namespace Studiobit\Project\Handlers;

use Studiobit\Project as Project;

class Pull
{
    public static function OnGetDependentModule()
    {
        return Array(
            'MODULE_ID' => Project\MODULE_ID,
            'USE' => Array('PUBLIC_SECTION')
        );
    }
}