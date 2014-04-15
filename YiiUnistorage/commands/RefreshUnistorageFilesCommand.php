<?php

class RefreshUnistorageFilesCommand extends CConsoleCommand
{
    public function actionIndex($refreshHours = 1)
    {
        $limit = 500;
        $offset = 0;
        $c = new CDbCriteria(
            array(
                'condition' => 'ttl < :time',
                'params' => array(
                    ':time' => time() + 3600 * $refreshHours
                ),
                'offset' => $offset,
                'limit' => $limit,
            )
        );
        $criteria = $c;

        /** @var $unistorageClient YiiUnistorage */
        $unistorageClient = Yii::app()->getComponent('unistorage');
        $unistorageClient->useGetFileCache = false;
        $processedFiles = array();

        /** @var UnistorageCache[] $rows */
        while ($rows = UnistorageCache::model()->findAll($criteria)) {
            foreach ($rows as $cache) {
                /** @var $file \Unistorage\Models\Files\File */
                $file = @unserialize($cache->object);
                if (!in_array($cache->cacheKey, $processedFiles)) {
                    $processedFiles[] = $cache->cacheKey;
                    $file = $unistorageClient->getFile($file->resourceUri);
                    $unistorageClient->cacheFile($file, $cache->cacheKey);
                }
            }
            $criteria = clone $c;
            $criteria->addNotInCondition('cacheKey', $processedFiles);
        }
    }
}
