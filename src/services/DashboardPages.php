<?php

namespace bymayo\commercewidgets\services;

use bymayo\commercewidgets\records\DashboardPage;

use craft\base\Component;

class DashboardPages extends Component
{

    public function getPagesForUser(int $userId): array
    {
        return DashboardPage::find()
            ->where(['userId' => $userId])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();
    }

    public function getPageById(int $pageId, int $userId): ?DashboardPage
    {
        return DashboardPage::findOne([
            'id' => $pageId,
            'userId' => $userId,
        ]);
    }

    public function getDefaultPage(int $userId): ?DashboardPage
    {
        return DashboardPage::find()
            ->where(['userId' => $userId])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->one();
    }

    public function addPage(int $userId, string $name = 'New Page'): DashboardPage
    {
        $maxSort = DashboardPage::find()
            ->where(['userId' => $userId])
            ->max('sortOrder');

        $record = new DashboardPage();
        $record->userId = $userId;
        $record->name = $name;
        $record->sortOrder = ($maxSort ?? 0) + 1;
        $record->save();

        return $record;
    }

    public function renamePage(int $pageId, int $userId, string $name): bool
    {
        $updated = DashboardPage::updateAll(
            ['name' => $name],
            ['id' => $pageId, 'userId' => $userId]
        );

        return $updated > 0;
    }

    public function deletePage(int $pageId, int $userId): bool
    {
        $count = DashboardPage::find()
            ->where(['userId' => $userId])
            ->count();

        if ($count <= 1) {
            return false;
        }

        $record = DashboardPage::findOne([
            'id' => $pageId,
            'userId' => $userId,
        ]);

        if (!$record) {
            return false;
        }

        return (bool) $record->delete();
    }

    public function seedDefaultPage(int $userId): DashboardPage
    {
        return $this->addPage($userId, 'Overview');
    }

}
