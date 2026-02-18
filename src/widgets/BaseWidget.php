<?php

namespace bymayo\commercewidgets\widgets;

use Craft;
use craft\base\Widget;

abstract class BaseWidget extends Widget
{
    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (\yii\base\UnknownPropertyException) {
        }
    }

    public static function isSelectable(): bool
    {
        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            return false;
        }

        return $user->admin || $user->can('commerceWidgets-accessWidgets');
    }
}
