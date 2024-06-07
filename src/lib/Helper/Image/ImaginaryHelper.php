<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Helper\Image;

use Exception;
use OCA\Passwords\Exception\Image\ImageConversionException;
use OCA\Passwords\Exception\Image\Imaginary\ImaginaryCommunicationException;
use OCA\Passwords\Exception\Image\Imaginary\NotConfiguredException;
use OCA\Passwords\Helper\Image\Imaginary\ImaginaryImage;
use OCA\Passwords\Services\ConfigurationService;
use OCP\Http\Client\IClientService;

class ImaginaryHelper extends AbstractImageHelper {

    public function __construct(
        protected ConfigurationService $config,
        protected ImagickHelper        $imagickHelper,
        protected IClientService       $httpClientService
    ) {
        parent::__construct($config);
    }

    /**
     * @param ImaginaryImage $image
     * @param int            $minWidth
     * @param int            $minHeight
     * @param int            $maxWidth
     * @param int            $maxHeight
     *
     * @return ImaginaryImage
     * @throws Exception
     */
    public function advancedResizeImage($image, int $minWidth, int $minHeight, int $maxWidth, int $maxHeight): ImaginaryImage {
        $info = $this->sendRequest($image, 'info');

        $size = $this->getBestImageFit($info['width'], $info['height'], $minWidth, $minHeight, $maxWidth, $maxHeight);

        $this->sendRequest($image, 'resize', ['type' => 'png', 'quality' => 9, 'width' => $size['width'], 'height' => $size['height']]);
        if($size['cropNeeded']) {
            $this->sendRequest(
                $image,
                'extract',
                [
                    'type'       => 'png',
                    'quality'    => 9,
                    'width'      => $size['cropWidth'],
                    'height'     => $size['cropHeight'],
                    'areawidth'  => $size['cropWidth'],
                    'areaheight' => $size['cropHeight'],
                    'top'        => $size['cropX'],
                    'left'       => $size['cropY']
                ]
            );
        }

        return $image;
    }

    /**
     * @param ImaginaryImage $image
     * @param int            $size
     *
     * @return ImaginaryImage
     * @throws Exception
     */
    public function simpleResizeImage($image, int $size) {
        return $this->sendRequest($image, 'resize', ['type' => 'png', 'quality' => 9, 'width' => $size, 'height' => $size]);
    }

    /**
     * @param ImaginaryImage $image
     *
     * @return ImaginaryImage
     * @throws Exception
     */
    public function cropImageRectangular($image): ImaginaryImage {
        $info = $this->sendRequest($image, 'info');
        if($info['width'] === $info['height'] || $info['width'] < $info['height']) {
            $size = $info['width'];
        } else {
            $size = $info['height'];
        }

        return $this->sendRequest($image, 'smartcrop', ['width' => $size, 'type' => 'png', 'quality' => 9, 'aspectratio' => '1:1']);
    }

    /**
     * @param string $imageBlob
     *
     * @return ImaginaryImage
     * @throws ImageConversionException
     */
    public function getImageFromBlob($imageBlob) {
        $tempFile = $this->config->getTempDir().uniqid();

        if($this->imagickHelper->isAvailable()) {
            $size = getimagesizefromstring($imageBlob);
            if($size && in_array($size['mime'], ['image/icon', 'image/vnd.microsoft.icon'])) {
                $imageBlob = $this->convertIcoToPng($imageBlob);
            }
        }

        return new ImaginaryImage($imageBlob, $tempFile);
    }

    /**
     * @param ImaginaryImage $image
     *
     * @return bool
     */
    public function destroyImage($image): bool {
        $image->destroy();

        return true;
    }

    /**
     * @param $image
     *
     * @return mixed
     * @throws Exception
     */
    public function exportJpeg($image): string {
        $image = $this->sendRequest($image, 'convert', ['type' => 'jpeg', 'quality' => 90]);

        return $image->getData();
    }

    /**
     * @param $image
     *
     * @return string
     * @throws Exception
     */
    public function exportPng($image): string {
        $image = $this->sendRequest($image, 'convert', ['type' => 'png', 'compression' => 9]);

        return $image->getData();
    }

    /**
     * @param string $format
     *
     * @return bool
     */
    public function supportsFormat(string $format): bool {
        $format           = strtolower($format);
        $supportedFormats = ['bmp', 'x-bitmap', 'png', 'jpeg', 'gif', 'heic', 'heif', 'svg+xml', 'tiff', 'webp'];

        if($this->imagickHelper->isAvailable()) {
            $supportedFormats[] = 'icon';
            $supportedFormats[] = 'vnd.microsoft.icon';
        }

        return in_array($format, $supportedFormats);
    }

    /**
     * @param string $data
     *
     * @return string
     * @throws ImageConversionException
     */
    public function convertIcoToPng($data): string {
        if($this->imagickHelper->isAvailable()) {
            return $this->imagickHelper->convertIcoToPng($data);
        }

        return $data;
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool {
        $imaginaryUrl = $this->config->getSystemValue('preview_imaginary_url', 'invalid');

        return $imaginaryUrl !== 'invalid';
    }

    /**
     * @param ImaginaryImage $image
     * @param string         $operation
     * @param array          $params
     *
     * @return ImaginaryImage|array
     * @throws ImaginaryCommunicationException|NotConfiguredException
     */
    protected function sendRequest(ImaginaryImage $image, string $operation, array $params = []): ImaginaryImage|array {
        $imaginaryEndpointUrl = $this->getImaginaryUrl($operation);

        $httpClient = $this->httpClientService->newClient();
        if($operation !== 'info') {
            $params['stripmeta']  = 'true';
            $params['norotation'] = 'true';
        }

        try {
            $response = $httpClient->post(
                $imaginaryEndpointUrl, [
                'query'           => $params,
                'stream'          => true,
                'body'            => $image->getResource(),
                'nextcloud'       => ['allow_local_address' => true],
                'timeout'         => 30,
                'connect_timeout' => 3,
            ]);
        } catch(\Exception $e) {
            throw new ImaginaryCommunicationException('Imaginary request failed', $e);
        }

        if($response->getStatusCode() !== 200) {
            throw new ImaginaryCommunicationException('Imaginary request failed: '.json_decode($response->getBody())['message']);
        }

        $body = stream_get_contents($response->getBody());
        if($operation === 'info') {
            return json_decode($body, true);
        }

        $image->update($body);

        return $image;
    }

    /**
     * @param string $operation
     *
     * @return string
     * @throws NotConfiguredException
     */
    protected function getImaginaryUrl(string $operation): string {
        $imaginaryUrl = $this->config->getSystemValue('preview_imaginary_url', 'invalid');
        if($imaginaryUrl === 'invalid' || !is_string($imaginaryUrl)) {
            throw new NotConfiguredException();
        }
        $imaginaryUrl = rtrim($imaginaryUrl, '/');

        return $imaginaryUrl.'/'.$operation;
    }
}