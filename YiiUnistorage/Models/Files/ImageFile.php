<?php namespace YiiUnistorage\Models\Files;

use Unistorage\Models\Files\File;
use Unistorage\Models\Template;
use Unistorage\Unistorage;

class ImageFile extends \Unistorage\Models\Files\ImageFile
{
    /**
     * @param string $mode
     * @param int $width
     * @param int $height
     * @param bool $lowPriority
     * @param Unistorage $unistorage
     * @return File|bool
     */
	public function resize($mode, $width, $height, $lowPriority = false, $unistorage=null)
	{
		/** @var $unistorage \YiiUnistorage */
		$unistorage = \Yii::app()->getComponent('unistorage');
		return parent::resize($mode, $width, $height, $lowPriority, $unistorage);
	}

    /**
     * @param string $format
     * @param bool $lowPriority
     * @param Unistorage $unistorage
     * @return File|bool
     */
	public function convert($format, $lowPriority = false, $unistorage=null)
	{
		/** @var $unistorage \YiiUnistorage */
		$unistorage = \Yii::app()->getComponent('unistorage');
		return parent::convert($format, $lowPriority, $unistorage);
	}

    /**
     * @param bool $lowPriority
     * @param Unistorage $unistorage
     * @return File|bool
     */
	public function grayscale($lowPriority = false, $unistorage=null)
	{
		/** @var $unistorage \YiiUnistorage */
		$unistorage = \Yii::app()->getComponent('unistorage');
		return parent::grayscale($lowPriority, $unistorage);
	}

    /**
     * @param int $angle 90, 180, 270. CCW
     * @param bool $lowPriority
     * @param Unistorage $unistorage
     * @return File|bool
     */
	public function rotate($angle, $lowPriority = false, $unistorage=null)
	{
		/** @var $unistorage \YiiUnistorage */
		$unistorage = \Yii::app()->getComponent('unistorage');
		return parent::rotate($angle, $lowPriority, $unistorage);
	}

    /**
     * $wmWidth, $wmHeight, $horizontalPadding, $verticalPadding my have following format:
     * <ul>
     * <li> (\d+)px - number calculates in pixels
     * <li> (\d+) - number calculates in percents
     * </ul>
     * @param ImageFile $watermark
     * @param string $wmWidth watermark width
     * @param string $wmHeight watermark height
     * @param string $horizontalPadding padding of watermark
     * @param string $verticalPadding padding of watermark
     * @param string $corner one of ImageFile::CORNER_*
     * @param bool $lowPriority
     * @param Unistorage $unistorage
     * @return File|bool
     */
    public function watermark(
        $watermark,
        $wmWidth,
        $wmHeight,
        $horizontalPadding,
        $verticalPadding,
        $corner,
        $lowPriority = false,
        $unistorage = null
    ) {
        /** @var $unistorage \YiiUnistorage */
        $unistorage = \Yii::app()->getComponent('unistorage');
        return parent::watermark(
            $watermark,
            $wmWidth,
            $wmHeight,
            $horizontalPadding,
            $verticalPadding,
            $corner,
            $lowPriority,
            $unistorage
        );
    }

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
