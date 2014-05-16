<?php namespace YiiUnistorage\Models\Files;

use Unistorage\Models\Files\File;
use Unistorage\Models\Template;
use Unistorage\Unistorage;

class VideoFile extends \Unistorage\Models\Files\VideoFile
{
    /**
     * @param string $format
     * @param string $vCodec
     * @param string $aCodec
     * @param bool $lowPriority
     * @param Unistorage $unistorage
     * @return File|bool
     */
	public function convert($format, $vCodec, $aCodec, $lowPriority = false, $unistorage=null)
	{
		/** @var $unistorage \YiiUnistorage */
		$unistorage = \Yii::app()->getComponent('unistorage');
		return parent::convert($format, $vCodec, $aCodec, $lowPriority, $unistorage);
	}

    /**
     * @param string $format
     * @param bool $lowPriority
     * @param Unistorage $unistorage
     * @return File|bool
     */
	public function extractAudio($format, $lowPriority = false, $unistorage=null)
	{
		/** @var $unistorage \YiiUnistorage */
		$unistorage = \Yii::app()->getComponent('unistorage');
		return parent::extractAudio($format, $lowPriority, $unistorage);
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
     * @param string $format
     * @param integer $position
     * @param bool $lowPriority
     * @param Unistorage $unistorage
     * @return File|bool
     */
	public function captureFrame($format, $position, $lowPriority = false, $unistorage=null)
	{
		/** @var $unistorage \YiiUnistorage */
		$unistorage = \Yii::app()->getComponent('unistorage');
		return parent::captureFrame($format, $position, $lowPriority, $unistorage);
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

    /**
     * @param bool $lowPriority
     * @return File|bool
     */
    public function getWebmFile($lowPriority = false)
    {
        return $this->convert('webm', 'vp8', 'vorbis', $lowPriority);
    }

    /**
     * @param bool $lowPriority
     * @return File|bool
     */
    public function getMp4File($lowPriority = false)
    {
        return $this->convert('mp4', 'h264', 'aac', $lowPriority);
    }

    /**
     * @param bool $lowPriority
     * @return File|bool
     */
    public function getJpegFrame($lowPriority = false)
    {
        return $this->captureFrame('jpeg', floor($this->videoDuration/2), $lowPriority);
    }

    /**
     * @param bool $lowPriority
     * @return void
     */
    public function preConvert($lowPriority = false)
    {
        $this->getJpegFrame($lowPriority);
        $this->getWebmFile($lowPriority);
        $this->getMp4File($lowPriority);
    }
}
