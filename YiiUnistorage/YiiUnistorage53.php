<?php

Yii::setPathOfAlias('YiiUnistorage', __DIR__);
Yii::import('YiiUnistorage.Models.*');

use Unistorage\Models\Files\File;
use Unistorage\Models\Files\RegularFile;

class YiiUnistorage53 extends \Unistorage\Unistorage
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
     * @return File|bool
     */
    public function safeApplyAction($file, $actionName, $actionParams = array())
    {
        try {
            $file = parent::applyAction($file, $actionName, $actionParams);
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
     * @return File|bool
     */
    public function safeApplyTemplate($file, $template)
    {
        try {
            $file = parent::applyTemplate($file, $template);
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
    private function getCacheKey($uniqueData)
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
                $file = $this->safeGetFile($file->resourceUri);
                $this->cacheFile($file, $cacheKey);
            } elseif ($unistorageCache->ttl - time() > 0) {
                // resetting in memcache
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
                $file = $this->safeGetFile($resourceUri);
                $this->cacheFile($file, $cacheKey);
            }
        }

        return $file;
    }

    /**
     * @param RegularFile $file
     * @param string $actionName
     * @param array $actionParams
     * @return File|bool
     */
    public function applyAction($file, $actionName, $actionParams = array())
    {
        $cacheKey = $this->getCacheKey(array(__FUNCTION__, $file->resourceUri, $actionName, $actionParams));
        if (!$this->useGetFileCache) {
            $resultFile = $this->safeApplyAction($file, $actionName, $actionParams);
        } elseif (false === $resultFile = $this->getCachedFile($cacheKey)) {
            $lastCacheState = $this->disableCaching();
            $resultFile = $this->safeApplyAction($file, $actionName, $actionParams);
            $this->useGetFileCache = $lastCacheState;
            $this->cacheFile($resultFile, $cacheKey);
        }

        return $resultFile;
    }

    /**
     * @param RegularFile $file
     * @param Unistorage\Models\Template $template
     * @return File|bool
     */
    public function applyTemplate($file, $template)
    {
        $cacheKey = $this->getCacheKey(array(__FUNCTION__, $file->resourceUri, $template));
        if (!$this->useGetFileCache) {
            $resultFile = $this->safeApplyTemplate($file, $template);
        } elseif (false === $resultFile = $this->getCachedFile($cacheKey)) {
            $lastCacheState = $this->disableCaching();
            $resultFile = $this->safeApplyTemplate($file, $template);
            $this->useGetFileCache = $lastCacheState;
            $this->cacheFile($resultFile, $cacheKey);
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
        } elseif (false === $zipFile = $this->getCachedFile($cacheKey)) {
            $lastCacheState = $this->disableCaching();
            $zipFile = $this->safeGetZipped($files, $zipFileName);
            $this->useGetFileCache = $lastCacheState;
            $this->cacheFile($zipFile, $cacheKey);
        }

        return $zipFile;
    }

    /**
     * @return bool last value
     */
    private function disableCaching()
    {
        $lastState = $this->useGetFileCache;
            $this->useGetFileCache = false;

        return $lastState;
    }
}

