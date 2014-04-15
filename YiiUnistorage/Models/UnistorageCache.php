<?php

/**
 * @property string $cacheKey
 * @property int $ttl
 * @property string $object
 */
class UnistorageCache extends CActiveRecord
{
	/**
	 * @param string $className
	 * @return UnistorageCache
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public function tableName()
	{
		return 'unistoragecache';
	}

	public function rules()
	{
		return array(
			array('cacheKey', 'length', 'min'=>32, 'max'=>32),
			array('ttl', 'numerical', 'integerOnly'=>true, 'min'=>0),
			array('object', 'safe'),
		);
	}
}
