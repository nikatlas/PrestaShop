<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\Image\Uploader;

use Gender;
use ImageManager;
use PrestaShop\PrestaShop\Core\Domain\Title\Exception\TitleImageUploadingException;
use PrestaShop\PrestaShop\Core\Image\Uploader\Exception\ImageUploadException;
use PrestaShop\PrestaShop\Core\Image\Uploader\Exception\UploadedImageConstraintException;
use PrestaShop\PrestaShop\Core\Image\Uploader\ImageUploaderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Uploads title logo image
 */
class TitleImageUploader extends AbstractImageUploader implements ImageUploaderInterface
{
    /**
     * @var int|null
     */
    protected $imageHeight;
    /**
     * @var int|null
     */
    protected $imageWidth;

    /**
     * {@inheritdoc}
     *
     * @throws UploadedImageConstraintException
     * @throws ImageUploadException
     */
    public function upload($titleId, UploadedFile $image)
    {
        $this->checkImageIsAllowedForUpload($image);
        $tempImageName = $this->createTemporaryImage($image);
        $this->deleteOldImage($titleId);

        $destination = _PS_GENDERS_DIR_ . $titleId . '.jpg';
        $this->uploadFromTemp($tempImageName, $destination);

        // Copy new image
        if (!ImageManager::resize(
            $destination,
            $destination,
            $this->imageWidth,
            $this->imageHeight
        )) {
            throw new TitleImageUploadingException(
                'An error occurred while uploading the image. Check your directory permissions.',
                TitleImageUploadingException::UNEXPECTED_ERROR
            );
        }
    }

    /**
     * Deletes old image
     *
     * @param int $id
     */
    protected function deleteOldImage(int $id): void
    {
        $title = new Gender($id);
        $title->deleteImage();

        // Remove thumbnail
        if (!file_exists(_PS_GENDERS_DIR_ . $id . '.jpg')) {
            $currentFile = _PS_TMP_IMG_DIR_ . 'genders_mini_' . $id . '.jpg';

            if (file_exists($currentFile)) {
                unlink($currentFile);
            }
        }
    }

    public function setImageHeight(?int $height): self
    {
        $this->imageHeight = $height;

        return $this;
    }

    public function setImageWidth(?int $width): self
    {
        $this->imageWidth = $width;

        return $this;
    }
}
