<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Settings;

use OCA\Passwords\AppInfo\Application;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

/**
 * Class AdminSection
 *
 * @package Settings
 */
class AdminSection implements IIconSection {

    /**
     * @var IURLGenerator
     */
    protected $urlGenerator;

    /**
     * @var IL10N
     */
    protected $localisation;

    /**
     * AdminSection constructor.
     *
     * @param IL10N         $localisation
     * @param IURLGenerator $urlGenerator
     */
    public function __construct(IL10N $localisation, IURLGenerator $urlGenerator) {
        $this->localisation = $localisation;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Returns the relative path to an 16*16 icon describing the section.
     * e.g. '/core/img/places/files.svg'
     *
     * @returns string
     * @since 12
     */
    public function getIcon(): string {
        return $this->urlGenerator->imagePath(Application::APP_NAME, 'app-dark.svg');
    }

    /**
     * Returns the ID of the section. It is supposed to be a lower case string,
     * e.g. 'passwords'
     *
     * @returns string
     * @since 9.1
     */
    public function getID(): string {
        return Application::APP_NAME;
    }

    /**
     * Returns the translated name as it should be displayed, e.g. 'Passwords'.
     * Use the L10N service to translate it.
     *
     * @return string
     * @since 9.1
     */
    public function getName(): string {
        return $this->localisation->t('Passwords');
    }

    /**
     * @return int whether the form should be rather on the top or bottom of
     * the settings navigation. The sections are arranged in ascending order of
     * the priority values. It is required to return a value between 0 and 99.
     *
     * E.g.: 70
     * @since 9.1
     */
    public function getPriority(): int {
        return 50;
    }
}