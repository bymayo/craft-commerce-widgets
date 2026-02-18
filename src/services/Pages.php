<?php

namespace bymayo\commercewidgets\services;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\records\Pages as PagesRecord;
use bymayo\commercewidgets\records\Widget;
use bymayo\commercewidgets\widgets\BaseWidget;

use Craft;
use craft\base\Component;

class Pages extends Component
{

    // Pages
    // =========================================================================

    public function getPagesForUser(int $userId): array
    {
        return PagesRecord::find()
            ->where(['userId' => $userId])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();
    }

    public function getPageById(int $pageId, int $userId): ?PagesRecord
    {
        return PagesRecord::findOne([
            'id' => $pageId,
            'userId' => $userId,
        ]);
    }

    public function getDefaultPage(int $userId): ?PagesRecord
    {
        return PagesRecord::find()
            ->where(['userId' => $userId])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->one();
    }

    public function addPage(int $userId, string $name = 'New Page'): PagesRecord
    {
        $maxSort = PagesRecord::find()
            ->where(['userId' => $userId])
            ->max('sortOrder');

        $record = new PagesRecord();
        $record->userId = $userId;
        $record->name = $name;
        $record->sortOrder = ($maxSort ?? 0) + 1;
        $record->save();

        return $record;
    }

    public function renamePage(int $pageId, int $userId, string $name): bool
    {
        $updated = PagesRecord::updateAll(
            ['name' => $name],
            ['id' => $pageId, 'userId' => $userId]
        );

        return $updated > 0;
    }

    public function deletePage(int $pageId, int $userId): bool
    {
        $count = PagesRecord::find()
            ->where(['userId' => $userId])
            ->count();

        if ($count <= 1) {
            return false;
        }

        $record = PagesRecord::findOne([
            'id' => $pageId,
            'userId' => $userId,
        ]);

        if (!$record) {
            return false;
        }

        return (bool) $record->delete();
    }

    public function seedDefaultPage(int $userId): PagesRecord
    {
        return $this->addPage($userId, 'Overview');
    }

    // Widgets
    // =========================================================================

    public function getAvailableWidgetTypes(): array
    {
        $types = [];
        $dir = Craft::getAlias('@bymayo/commercewidgets/widgets');

        foreach (glob($dir . '/*.php') as $file) {
            $className = 'bymayo\\commercewidgets\\widgets\\' . basename($file, '.php');

            if (!class_exists($className) || !is_subclass_of($className, BaseWidget::class)) {
                continue;
            }

            $fullName = property_exists($className, 'displayName') ? $className::$displayName : $className::displayName();
            $prefix = CommerceWidgets::$plugin->helpers->getPluginName() . ' - ';
            $name = str_starts_with($fullName, $prefix) ? substr($fullName, strlen($prefix)) : $fullName;

            $types[] = [
                'class' => $className,
                'name' => $name,
            ];
        }

        usort($types, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $types;
    }

    public function getWidgetsForPage(int $pageId, int $userId): array
    {
        return Widget::find()
            ->where(['pageId' => $pageId, 'userId' => $userId])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();
    }

    public function getWidgetsForUser(int $userId): array
    {
        return Widget::find()
            ->where(['userId' => $userId])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();
    }

    public function addWidget(int $userId, string $type, int $colspan = 1, array $settings = [], ?int $pageId = null): Widget
    {
        $maxSort = Widget::find()
            ->where(['pageId' => $pageId, 'userId' => $userId])
            ->max('sortOrder');

        $record = new Widget();
        $record->userId = $userId;
        $record->pageId = $pageId;
        $record->type = $type;
        $record->colspan = $colspan;
        $record->sortOrder = ($maxSort ?? 0) + 1;
        $record->settings = !empty($settings) ? json_encode($settings) : null;
        $record->save();

        return $record;
    }

    public function removeWidget(int $widgetId, int $userId): bool
    {
        $record = Widget::find()
            ->where(['id' => $widgetId, 'userId' => $userId])
            ->one();

        if (!$record) {
            return false;
        }

        return (bool) $record->delete();
    }

    public function reorderWidgets(array $widgetIds, int $userId): bool
    {
        foreach ($widgetIds as $order => $widgetId) {
            Widget::updateAll(
                ['sortOrder' => $order + 1],
                ['id' => $widgetId, 'userId' => $userId]
            );
        }

        return true;
    }

    public function resizeWidget(int $widgetId, int $userId, int $colspan): bool
    {
        $updated = Widget::updateAll(
            ['colspan' => $colspan],
            ['id' => $widgetId, 'userId' => $userId]
        );

        return $updated > 0;
    }

    public function saveWidgetSettings(int $widgetId, int $userId, int $colspan, array $settings): bool
    {
        $record = Widget::findOne([
            'id' => $widgetId,
            'userId' => $userId,
        ]);

        if (!$record) {
            return false;
        }

        $record->colspan = $colspan;
        $record->settings = !empty($settings) ? json_encode($settings) : null;

        return $record->save();
    }

    public function seedDefaultWidgets(int $userId, int $pageId): void
    {
        $settings = CommerceWidgets::$plugin->getSettings();
        $widgetClasses = $settings->defaultPageWidgets;

        if (empty($widgetClasses)) {
            return;
        }

        foreach ($widgetClasses as $className) {
            $fullClass = 'bymayo\\commercewidgets\\widgets\\' . $className;
            if (class_exists($fullClass) && is_subclass_of($fullClass, BaseWidget::class)) {
                $this->addWidget($userId, $fullClass, 1, [], $pageId);
            }
        }
    }

}
