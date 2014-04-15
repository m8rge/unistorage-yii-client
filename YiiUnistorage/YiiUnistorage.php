<?php

Yii::setPathOfAlias('YiiUnistorage', __DIR__);
Yii::import('YiiUnistorage.Models.*');

use Unistorage\Models\Files\File;
use Unistorage\Models\Files\RegularFile;

class YiiUnistorage extends \Unistorage\Unistorage
{
    /** @var bool use cache in getFile if true */
    public $useGetFileCache = true;

    public function __construct()
    {
    }

    public function init()
    {
    }

    /**
     * @param string $resourceUri
     * @return File|bool
     */
    public function safeGetFile($resourceUri)
    {
        try {
            $file = parent::getFile($resourceUri);
        } catch (Exception $e) {
            Yii::log($e, CLogger::LEVEL_ERROR, 'unistorage');
            return false;
        }
        return $file;
    }

    /**
     * @param RegularFile $file
     * @param string $actionName
     * @param array $actionParams
     * @param bool $lowPriority
     * @return File|bool
     */
    public function safeApplyAction($file, $actionName, $actionParams = array(), $lowPriority = false)
    {
        try {
            $file = parent::applyAction($file, $actionName, $actionParams, $lowPriority);
        } catch (Exception $e) {
            Yii::log(
                'Can\'t applyAction ' . print_r(array($file, $actionName, $actionParams), true) . $e,
                CLogger::LEVEL_ERROR,
                'unistorage'
            );
            return false;
        }
        return $file;
    }

    /**
     * @param RegularFile $file
     * @param Unistorage\Models\Template $template
     * @param bool $lowPriority
     * @return File|bool
     */
    public function safeApplyTemplate($file, $template, $lowPriority = false)
    {
        try {
            $file = parent::applyTemplate($file, $template, $lowPriority);
        } catch (Exception $e) {
            Yii::log(
                'Can\'t applyTemplate ' . print_r(array($file, $template), true) . $e,
                CLogger::LEVEL_ERROR,
                'unistorage'
            );
            return false;
        }
        return $file;
    }

    /**
     * @param RegularFile[] $files
     * @param string $zipFileName
     * @return Unistorage\Models\Files\ZipFile|bool
     */
    public function safeGetZipped($files, $zipFileName)
    {
        try {
            $file = parent::getZipped($files, $zipFileName);
        } catch (Exception $e) {
            Yii::log(
                'Can\'t getZipped ' . print_r(array($files, $zipFileName), true) . $e,
                CLogger::LEVEL_ERROR,
                'unistorage'
            );
            return false;
        }
        return $file;
    }

    /**
     * @param $uniqueData mixed
     * @return string
     */
    public function getCacheKey($uniqueData)
    {
        return md5(serialize($uniqueData));
    }

    /**
     * @param File $file
     * @param $cacheKey
     */
    public function cacheFile($file, $cacheKey)
    {
        if (is_object($file)) {
            if ($file instanceof \Unistorage\Models\Files\NonPermanentFile) {
                $ttl = $file->ttl;
            } else {
                $ttl = 0;
            }
            Yii::app()->cache->set($cacheKey, $file, $ttl);
            $unistorageCache = \UnistorageCache::model()->findByPk($cacheKey);
            if (empty($unistorageCache)) {
                $unistorageCache = new \UnistorageCache();
                $unistorageCache->cacheKey = $cacheKey;
            }
            if ($ttl != 0) {
                $unistorageCache->ttl = time() + $ttl;
            } else {
                $unistorageCache->ttl = 0;
            }
            $unistorageCache->object = serialize($file);
            $unistorageCache->save();
        }
    }

    /**
     * @param string $cacheKey
     * @return File|bool
     */
    public function getCachedFile($cacheKey)
    {
        if (false === $file = Yii::app()->cache->get($cacheKey)) {
            /** @var $unistorageCache \UnistorageCache */
            $unistorageCache = \UnistorageCache::model()->findByPk($cacheKey);
            if (empty($unistorageCache)) {
                return false;
            }
            /** @var $file File */
            $file = @unserialize($unistorageCache->object);
            if (empty($file)) {
                Yii::log("Can't unserialize {$unistorageCache->cacheKey}", CLogger::LEVEL_ERROR, 'unistorage');
                return false;
            }
            if ($unistorageCache->ttl != 0 && $unistorageCache->ttl < time()) {
                // file expired. Refreshing
                $file = $this->refreshFile($file->resourceUri, $cacheKey);
            } elseif ($unistorageCache->ttl - time() > 0) {
                // resetting in cache
                Yii::app()->cache->set($cacheKey, $file, $unistorageCache->ttl - time());
            }
        }

        return $file;
    }

    /**
     * @return string
     */
    protected function getFilesNamespace()
    {
        return 'YiiUnistorage\\Models\\Files\\';
    }

    /**
     * @param string $resourceUri
     * @return File|bool
     */
    public function getFile($resourceUri)
    {
        if (empty($resourceUri)) {
            Yii::log('resourceUri is empty', CLogger::LEVEL_ERROR, 'unistorage');
            return false;
        }

        $cacheKey = $this->getCacheKey(array(__FUNCTION__, $resourceUri));

        if (!$this->useGetFileCache) {
            $file = $this->safeGetFile($resourceUri);
        } else {
            if (false === $file = $this->getCachedFile($cacheKey)) {
                $file = $this->refreshFile($resourceUri, $cacheKey);
            }
        }

        return $file;
    }

    /**
     * @param RegularFile $file
     * @param string $actionName
     * @param array $actionParams
     * @param bool $lowPriority
     * @return File|bool
     */
    public function applyAction($file, $actionName, $actionParams = array(), $lowPriority = false)
    {
        $cacheKey = $this->getCacheKey(array(__FUNCTION__, $file->resourceUri, $actionName, $actionParams));
        if (!$this->useGetFileCache) {
            $resultFile = $this->safeApplyAction($file, $actionName, $actionParams, $lowPriority);
        } else {
            $resultFile = $this->getCachedFileOrCreateIt(
                $cacheKey,
                function () use ($file, $actionName, $actionParams, $lowPriority) {
                    return $this->safeApplyAction($file, $actionName, $actionParams, $lowPriority);
                }
            );
        }

        return $resultFile;
    }

    /**
     * @param RegularFile $file
     * @param Unistorage\Models\Template $template
     * @param bool $lowPriority
     * @return File|bool
     */
    public function applyTemplate($file, $template, $lowPriority = false)
    {
        $cacheKey = $this->getCacheKey(array(__FUNCTION__, $file->resourceUri, $template));
        if (!$this->useGetFileCache) {
            $resultFile = $this->safeApplyTemplate($file, $template, $lowPriority);
        } else {
            $resultFile = $this->getCachedFileOrCreateIt(
                $cacheKey,
                function () use ($file, $template, $lowPriority) {
                    return $this->safeApplyTemplate($file, $template, $lowPriority);
                }
            );
        }

        return $resultFile;
    }

    /**
     * @param RegularFile[] $files
     * @param string $zipFileName
     * @return Unistorage\Models\Files\ZipFile|bool
     */
    public function getZipped($files, $zipFileName)
    {
        $cacheKey = $this->getCacheKey(array(__FUNCTION__, $files, $zipFileName));
        if (!$this->useGetFileCache) {
            $zipFile = $this->safeGetZipped($files, $zipFileName);
        } else {
            $zipFile = $this->getCachedFileOrCreateIt(
                $cacheKey,
                function () use ($files, $zipFileName) {
                    return $this->safeGetZipped($files, $zipFileName);
                }
            );
        }

        return $zipFile;
    }

    /**
     * @param string $cacheKey
     * @param callable $createFileCallback must return File|false
     * @return bool|File
     */
    public function getCachedFileOrCreateIt($cacheKey, $createFileCallback)
    {
        $file = $this->getCachedFile($cacheKey);
        if ($file === false) {
            $lastCacheState = $this->useGetFileCache;
            $this->useGetFileCache = false;
            $file = call_user_func($createFileCallback);
            $this->useGetFileCache = $lastCacheState;
            $this->cacheFile($file, $cacheKey);
        }

        return $file;
    }

    /**
     * @param string $resourceUri
     * @param string $cacheKey
     * @return bool|File
     */
    public function refreshFile($resourceUri, $cacheKey)
    {
        $file = $this->safeGetFile($resourceUri);
        $this->cacheFile($file, $cacheKey);

        return $file;
    }
}

