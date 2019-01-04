<?php
/**
 * Commerce Widgets plugin for Craft CMS 3.x
 *
 * Description
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

namespace bymayo\commercewidgets\models;

use bymayo\commercewidgets\CommerceWidgets;

use Craft;
use craft\base\Model;

/**
 * @author    ByMayo
 * @package   CommerceWidgets
 * @since     2.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $cacheDuration = 3600;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['cacheDuration', 'integer']
        ];
    }
}
