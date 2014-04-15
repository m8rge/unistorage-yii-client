<?php namespace YiiUnistorage\Models\Files;

use Unistorage\Models\Files\File;
use Unistorage\Models\Template;
use Unistorage\Unistorage;

class RegularFile extends \Unistorage\Models\Files\RegularFile
{
    /**
     * @param Template $template
     * @param bool $lowPriority
     * @param Unistorage $unistorage
     * @return File|bool
     */
	public function apply($template, $lowPriority = false, $unistorage=null)
	{
		/** @var $unistorage \YiiUnistorage */
		$unistorage = \Yii::app()->getComponent('unistorage');
		return parent::apply($template, $lowPriority, $unistorage);
	}
}
