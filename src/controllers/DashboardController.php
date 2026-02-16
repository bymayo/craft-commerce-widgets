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

    public function actionIndex(?int $pageId = null)
    {
        $userId = Craft::$app->getUser()->getId();
        $pagesService = CommerceWidgets::$plugin->dashboardPages;
        $widgetsService = CommerceWidgets::$plugin->dashboardWidgets;

        $pages = $pagesService->getPagesForUser($userId);

        // Seed default page + widgets for first-time users
        if (empty($pages)) {
            $defaultPage = $pagesService->seedDefaultPage($userId);
            $widgetsService->seedDefaultWidgets($userId, $defaultPage->id);
            $pages = [$defaultPage];
        }

        // Determine active page
        $activePage = null;
        if ($pageId !== null) {
            $activePage = $pagesService->getPageById($pageId, $userId);
        }

        // Redirect to first page if no valid pageId
        if ($activePage === null) {
            return $this->redirect('commerce-widgets/page/' . $pages[0]->id);
        }

        $records = $widgetsService->getWidgetsForPage($activePage->id, $userId);

        $widgets = [];
        foreach ($records as $record) {
            $widgetData = $this->_renderWidget($record);
            if ($widgetData) {
                $widgets[] = $widgetData;
            }
        }

        // Ensure assets load even on empty pages
        Craft::$app->getView()->registerAssetBundle(CommerceWidgetsAsset::class);

        $availableTypes = $widgetsService->getAvailableWidgetTypes();
        $pluginName = CommerceWidgets::$plugin->getSettings()->pluginName ?: 'Commerce Widgets';

        return $this->renderTemplate('commerce-widgets/dashboard/index', [
            'widgets' => $widgets,
            'availableTypes' => $availableTypes,
            'pluginName' => $pluginName,
            'pages' => $pages,
            'activePage' => $activePage,
            'selectedSubnavItem' => 'page-' . $activePage->id,
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

        $pageId = Craft::$app->getRequest()->getBodyParam('pageId');
        $pagesService = CommerceWidgets::$plugin->dashboardPages;

        // Fall back to user's first page if pageId not provided
        if ($pageId) {
            $page = $pagesService->getPageById((int) $pageId, $userId);
        } else {
            $page = $pagesService->getDefaultPage($userId);
        }

        if (!$page) {
            return $this->asJson(['success' => false, 'error' => 'Invalid page.']);
        }

        $record = $service->addWidget($userId, $type, 1, [], (int) $page->id);
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

    public function actionAddPage(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $name = Craft::$app->getRequest()->getBodyParam('name', 'New Page');
        $userId = Craft::$app->getUser()->getId();

        $page = CommerceWidgets::$plugin->dashboardPages->addPage($userId, $name);

        return $this->asJson([
            'success' => true,
            'page' => [
                'id' => $page->id,
                'name' => $page->name,
                'url' => 'commerce-widgets/page/' . $page->id,
            ],
        ]);
    }

    public function actionRenamePage(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $pageId = Craft::$app->getRequest()->getRequiredBodyParam('pageId');
        $name = Craft::$app->getRequest()->getRequiredBodyParam('name');
        $userId = Craft::$app->getUser()->getId();

        $name = trim($name);
        if (empty($name)) {
            return $this->asJson(['success' => false, 'error' => 'Page name cannot be empty.']);
        }

        $success = CommerceWidgets::$plugin->dashboardPages->renamePage((int) $pageId, $userId, $name);

        return $this->asJson(['success' => $success]);
    }

    public function actionDeletePage(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $pageId = Craft::$app->getRequest()->getRequiredBodyParam('pageId');
        $userId = Craft::$app->getUser()->getId();

        $pagesService = CommerceWidgets::$plugin->dashboardPages;
        $success = $pagesService->deletePage((int) $pageId, $userId);

        if (!$success) {
            return $this->asJson([
                'success' => false,
                'error' => 'Cannot delete the last page.',
            ]);
        }

        $firstPage = $pagesService->getDefaultPage($userId);

        return $this->asJson([
            'success' => true,
            'redirectUrl' => $firstPage ? 'commerce-widgets/page/' . $firstPage->id : 'commerce-widgets',
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
