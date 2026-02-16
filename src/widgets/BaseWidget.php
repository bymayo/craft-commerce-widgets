<?php

namespace bymayo\commercewidgets\widgets;

use Craft;
use craft\base\Widget;

abstract class BaseWidget extends Widget
{
    public static function isSelectable(): bool
    {
        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            return false;
        }

        return $user->admin || $user->can('commerceWidgets-addCmsDashboardWidgets');
    }
}
