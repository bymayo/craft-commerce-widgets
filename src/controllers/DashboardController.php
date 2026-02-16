<?php

namespace bymayo\commercewidgets\controllers;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use bymayo\commercewidgets\records\DashboardWidget;

use Craft;
use craft\web\Controller;
use yii\web\Response;

class DashboardController extends Controller
{

    public function actionIndex()
    {
        $userId = Craft::$app->getUser()->getId();
        $service = CommerceWidgets::$plugin->dashboardWidgets;

        $records = $service->getWidgetsForUser($userId);

        if (empty($records)) {
            $service->seedDefaultWidgets($userId);
            $records = $service->getWidgetsForUser($userId);
        }

        $widgets = [];
        foreach ($records as $record) {
            $widgetData = $this->_renderWidget($record);
            if ($widgetData) {
                $widgets[] = $widgetData;
            }
        }

        $availableTypes = $service->getAvailableWidgetTypes();
        $pluginName = CommerceWidgets::$plugin->getSettings()->pluginName ?: 'Commerce Widgets';

        return $this->renderTemplate('commerce-widgets/dashboard/index', [
            'widgets' => $widgets,
            'availableTypes' => $availableTypes,
            'pluginName' => $pluginName,
        ]);
    }

    public function actionAddWidget(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $type = Craft::$app->getRequest()->getRequiredBodyParam('type');
        $userId = Craft::$app->getUser()->getId();
        $service = CommerceWidgets::$plugin->dashboardWidgets;

        $availableClasses = array_column($service->getAvailableWidgetTypes(), 'class');
        if (!in_array($type, $availableClasses, true)) {
            return $this->asFailure('Invalid widget type.');
        }

        $record = $service->addWidget($userId, $type);
        $widgetData = $this->_renderWidget($record);

        return $this->asJson([
            'success' => true,
            'widget' => $widgetData,
        ]);
    }

    public function actionRemoveWidget(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $widgetId = Craft::$app->getRequest()->getRequiredBodyParam('widgetId');
        $userId = Craft::$app->getUser()->getId();

        $success = CommerceWidgets::$plugin->dashboardWidgets->removeWidget((int) $widgetId, $userId);

        return $this->asJson(['success' => $success]);
    }

    public function actionReorderWidgets(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $widgetIds = Craft::$app->getRequest()->getRequiredBodyParam('widgetIds');
        $userId = Craft::$app->getUser()->getId();

        $success = CommerceWidgets::$plugin->dashboardWidgets->reorderWidgets($widgetIds, $userId);

        return $this->asJson(['success' => $success]);
    }

    public function actionResizeWidget(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $widgetId = Craft::$app->getRequest()->getRequiredBodyParam('widgetId');
        $colspan = Craft::$app->getRequest()->getRequiredBodyParam('colspan');
        $userId = Craft::$app->getUser()->getId();

        $success = CommerceWidgets::$plugin->dashboardWidgets->resizeWidget((int) $widgetId, $userId, (int) $colspan);

        return $this->asJson(['success' => $success]);
    }

    public function actionGetWidgetSettings(): Response
    {
        $this->requireAcceptsJson();

        $widgetId = Craft::$app->getRequest()->getRequiredQueryParam('widgetId');
        $userId = Craft::$app->getUser()->getId();

        $record = DashboardWidget::findOne([
            'id' => $widgetId,
            'userId' => $userId,
        ]);

        if (!$record) {
            return $this->asJson(['success' => false]);
        }

        $type = $record->type;
        if (!class_exists($type)) {
            return $this->asJson(['success' => false]);
        }

        $settings = $record->settings ? json_decode($record->settings, true) : [];
        $widget = new $type($settings);
        $widget->id = $record->id;

        $view = Craft::$app->getView();

        $settingsHtml = '';
        $settingsJs = '';
        try {
            $view->startJsBuffer();
            $settingsHtml = $widget->getSettingsHtml() ?? '';
            $settingsJs = $view->clearJsBuffer(false);
        } catch (\Throwable $e) {
            $view->clearJsBuffer(false);
        }

        return $this->asJson([
            'success' => true,
            'settingsHtml' => $settingsHtml,
            'settingsJs' => $settingsJs,
            'colspan' => (int) $record->colspan,
        ]);
    }

    public function actionSaveWidgetSettings(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $widgetId = Craft::$app->getRequest()->getRequiredBodyParam('widgetId');
        $colspan = Craft::$app->getRequest()->getRequiredBodyParam('colspan');
        $settings = Craft::$app->getRequest()->getBodyParam('settings', []);
        $userId = Craft::$app->getUser()->getId();

        $service = CommerceWidgets::$plugin->dashboardWidgets;
        $success = $service->saveWidgetSettings((int) $widgetId, $userId, (int) $colspan, $settings);

        if (!$success) {
            return $this->asJson(['success' => false]);
        }

        $record = DashboardWidget::findOne([
            'id' => $widgetId,
            'userId' => $userId,
        ]);

        $widgetData = $this->_renderWidget($record);

        return $this->asJson([
            'success' => true,
            'widget' => $widgetData,
        ]);
    }

    private function _renderWidget($record): ?array
    {
        $type = $record->type;

        if (!class_exists($type)) {
            return null;
        }

        $settings = $record->settings ? json_decode($record->settings, true) : [];
        $widget = new $type($settings);
        $widget->id = $record->id;

        try {
            $html = $widget->getBodyHtml();
        } catch (\Throwable $e) {
            $html = '<p class="error">Widget failed to render.</p>';
        }

        return [
            'id' => $record->id,
            'type' => $type,
            'title' => $widget->getTitle(),
            'subtitle' => $widget->getSubtitle(),
            'colspan' => (int) $record->colspan,
            'html' => $html,
        ];
    }

}
