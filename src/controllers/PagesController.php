<?php

namespace bymayo\commercewidgets\controllers;

use bymayo\commercewidgets\CommerceWidgets;
use bymayo\commercewidgets\assetbundles\commercewidgets\CommerceWidgetsAsset;

use bymayo\commercewidgets\records\Widget;

use Craft;
use craft\web\Controller;
use yii\caching\TagDependency;
use yii\web\Response;

class PagesController extends Controller
{

    public function actionIndex(?int $pageId = null)
    {
        $userId = Craft::$app->getUser()->getId();
        $service = CommerceWidgets::$plugin->pages;

        $pages = $service->getPagesForUser($userId);

        // Seed default page + widgets for first-time users
        if (empty($pages)) {
            $defaultPage = $service->seedDefaultPage($userId);
            $service->seedDefaultWidgets($userId, $defaultPage->id);
            $pages = [$defaultPage];
        }

        // Determine active page
        $activePage = null;
        if ($pageId !== null) {
            $activePage = $service->getPageById($pageId, $userId);
        }

        // Redirect to first page if no valid pageId
        if ($activePage === null) {
            return $this->redirect('commerce-widgets/page/' . $pages[0]->id);
        }

        $records = $service->getWidgetsForPage($activePage->id, $userId);

        $widgets = [];
        foreach ($records as $record) {
            $widgetData = $this->_renderWidget($record);
            if ($widgetData) {
                $widgets[] = $widgetData;
            }
        }

        // Ensure assets load even on empty pages
        Craft::$app->getView()->registerAssetBundle(CommerceWidgetsAsset::class);

        $availableTypes = $service->getAvailableWidgetTypes();
        $settings = CommerceWidgets::$plugin->getSettings();
        $pluginName = CommerceWidgets::$plugin->helpers->getPluginName();

        $user = Craft::$app->getUser()->getIdentity();
        $canManagePages = $user && ($user->admin || $user->can('commerceWidgets-managePages'));

        // Track when cached data was last refreshed (shares tag with widget data)
        $cacheDuration = (int) ($settings->cacheDuration ?? 3600);
        $cachedAt = null;
        if ($cacheDuration > 0) {
            $cache = Craft::$app->getCache();
            $cacheKey = 'commerce-widgets-cached-at';
            $cachedAt = $cache->get($cacheKey);
            if ($cachedAt === false) {
                $cachedAt = time();
                $cache->set($cacheKey, $cachedAt, $cacheDuration, new TagDependency(['tags' => 'commerce-widgets']));
            }
        }

        return $this->renderTemplate('commerce-widgets/pages/index', [
            'widgets' => $widgets,
            'availableTypes' => $availableTypes,
            'pluginName' => $pluginName,
            'pages' => $pages,
            'activePage' => $activePage,
            'selectedSubnavItem' => 'page-' . $activePage->id,
            'enablePages' => $settings->enablePages,
            'canManagePages' => $canManagePages,
            'cachedAt' => $cachedAt,
        ]);
    }

    public function actionRefreshData(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        TagDependency::invalidate(Craft::$app->getCache(), 'commerce-widgets');

        return $this->asJson(['success' => true]);
    }

    public function actionAddWidget(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $type = Craft::$app->getRequest()->getRequiredBodyParam('type');
        $userId = Craft::$app->getUser()->getId();
        $service = CommerceWidgets::$plugin->pages;

        $availableClasses = array_column($service->getAvailableWidgetTypes(), 'class');
        if (!in_array($type, $availableClasses, true)) {
            return $this->asFailure('Invalid widget type.');
        }

        $pageId = Craft::$app->getRequest()->getBodyParam('pageId');

        // Fall back to user's first page if pageId not provided
        if ($pageId) {
            $page = $service->getPageById((int) $pageId, $userId);
        } else {
            $page = $service->getDefaultPage($userId);
        }

        if (!$page) {
            return $this->asJson(['success' => false, 'error' => 'Invalid page.']);
        }

        $record = $service->addWidget($userId, $type, 1, [], (int) $page->id);
        $widgetData = $this->_renderWidget($record, true);

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

        $success = CommerceWidgets::$plugin->pages->removeWidget((int) $widgetId, $userId);

        return $this->asJson(['success' => $success]);
    }

    public function actionReorderWidgets(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $widgetIds = Craft::$app->getRequest()->getRequiredBodyParam('widgetIds');
        $userId = Craft::$app->getUser()->getId();

        $success = CommerceWidgets::$plugin->pages->reorderWidgets($widgetIds, $userId);

        return $this->asJson(['success' => $success]);
    }

    public function actionResizeWidget(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $widgetId = Craft::$app->getRequest()->getRequiredBodyParam('widgetId');
        $colspan = Craft::$app->getRequest()->getRequiredBodyParam('colspan');
        $userId = Craft::$app->getUser()->getId();

        $success = CommerceWidgets::$plugin->pages->resizeWidget((int) $widgetId, $userId, (int) $colspan);

        return $this->asJson(['success' => $success]);
    }

    public function actionGetWidgetSettings(): Response
    {
        $this->requireAcceptsJson();

        $widgetId = Craft::$app->getRequest()->getRequiredQueryParam('widgetId');
        $userId = Craft::$app->getUser()->getId();

        $record = Widget::findOne([
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

        $service = CommerceWidgets::$plugin->pages;
        $success = $service->saveWidgetSettings((int) $widgetId, $userId, (int) $colspan, $settings);

        if (!$success) {
            return $this->asJson(['success' => false]);
        }

        $record = Widget::findOne([
            'id' => $widgetId,
            'userId' => $userId,
        ]);

        $widgetData = $this->_renderWidget($record, true);

        return $this->asJson([
            'success' => true,
            'widget' => $widgetData,
        ]);
    }

    public function actionAddPage(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requirePermission('commerceWidgets-managePages');

        $name = Craft::$app->getRequest()->getBodyParam('name', 'New Page');
        $userId = Craft::$app->getUser()->getId();

        $page = CommerceWidgets::$plugin->pages->addPage($userId, $name);

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
        $this->requirePermission('commerceWidgets-managePages');

        $pageId = Craft::$app->getRequest()->getRequiredBodyParam('pageId');
        $name = Craft::$app->getRequest()->getRequiredBodyParam('name');
        $userId = Craft::$app->getUser()->getId();

        $name = trim($name);
        if (empty($name)) {
            return $this->asJson(['success' => false, 'error' => 'Page name cannot be empty.']);
        }

        $success = CommerceWidgets::$plugin->pages->renamePage((int) $pageId, $userId, $name);

        return $this->asJson(['success' => $success]);
    }

    public function actionDeletePage(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requirePermission('commerceWidgets-managePages');

        $pageId = Craft::$app->getRequest()->getRequiredBodyParam('pageId');
        $userId = Craft::$app->getUser()->getId();

        $service = CommerceWidgets::$plugin->pages;
        $success = $service->deletePage((int) $pageId, $userId);

        if (!$success) {
            return $this->asJson([
                'success' => false,
                'error' => 'Cannot delete the last page.',
            ]);
        }

        $firstPage = $service->getDefaultPage($userId);

        return $this->asJson([
            'success' => true,
            'redirectUrl' => $firstPage ? 'commerce-widgets/page/' . $firstPage->id : 'commerce-widgets',
        ]);
    }

    private function _renderWidget($record, bool $bufferJs = false): ?array
    {
        $type = $record->type;

        if (!class_exists($type)) {
            return null;
        }

        $settings = $record->settings ? json_decode($record->settings, true) : [];
        $widget = new $type($settings);
        $widget->id = $record->id;

        $view = Craft::$app->getView();
        $bodyJs = '';

        try {
            if ($bufferJs) {
                $view->startJsBuffer();
            }
            $html = $widget->getBodyHtml();
            if ($bufferJs) {
                $bodyJs = $view->clearJsBuffer(false);
            }
        } catch (\Throwable $e) {
            if ($bufferJs) {
                $view->clearJsBuffer(false);
            }
            $html = '<p class="error">Widget failed to render.</p>';
        }

        return [
            'id' => $record->id,
            'type' => $type,
            'title' => $widget->getTitle(),
            'subtitle' => $widget->getSubtitle(),
            'colspan' => (int) $record->colspan,
            'html' => $html,
            'bodyJs' => $bodyJs,
        ];
    }

}
