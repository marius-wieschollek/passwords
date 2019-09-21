<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCP\L10N;

use OCP\IUser;

/**
 * @since 8.2.0
 */
interface IFactory {

	public function get($app, $lang = null, $locale = null);

	public function findLanguage($app = null);

	public function findLocale($lang = null);

	public function findLanguageFromLocale(string $app = 'core', string $locale = null);

	public function findAvailableLanguages($app = null);

	public function findAvailableLocales();

	public function languageExists($app, $lang);

	public function localeExists($locale);

	public function createPluralFunction($string);

	public function getLanguageIterator(IUser $user = null): ILanguageIterator;
}
