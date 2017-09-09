<?php
namespace OCA\Passwords\Service;

use Exception;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

use OCP\Activity\IExtension;
use OCP\Activity\IManager;

use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordMapper;

class PasswordService {

	private $mapper;

	public function __construct(PasswordMapper $mapper){
		$this->mapper = $mapper;
	}

	public function findAll($userId, $api = false) {
		
		$result = $this->mapper->findAll($userId);
		$arr_enc = json_encode($result);
		$arr = json_decode($arr_enc, true);

		$serverKey = \OC::$server->getConfig()->getSystemValue('passwordsalt', '');
		
		$has_ldap = (\OC::$server->getUserSession()->getUser()->getBackendClassName() == 'LDAP');
		
		foreach ($arr as $row => $value) {
			
			// DECRYPT PASSWORDS AND PROPERTIES
			$userKey = $arr[$row]['user_id'];
			$userSuppliedKey = $arr[$row]['website'];
			$encryptedPass = $arr[$row]['pass'];
			$encryptedProperties = $arr[$row]['properties'];

			// notes for backwards compatibility with versions prior to v17
			$encryptedPassNotes = $arr[$row]['notes'];

			$e2 = new Encryption(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
			$key = Encryption::makeKey($userKey, $serverKey, $userSuppliedKey);
			$arr[$row]['properties'] = $e2->decrypt($encryptedProperties, $key);
			// notes for backwards compatibility with versions prior to v17
			$arr[$row]['notes'] = $e2->decrypt($encryptedPassNotes, $key);

			if ($userKey != $userId && $arr[$row]['id'] != 0) {
				// check for sharekey
				$sharekey_activeuser = $this->mapper->getShareKey($arr[$row]['id'], $userId);
				$pos = strrpos($arr[$row]['properties'], $sharekey_activeuser);
				if ($pos !== false) {
					$arr[$row]['pass'] = $e2->decrypt($encryptedPass, $key);
				} else {
					$arr[$row]['pass'] = 'oc_passwords_invalid_sharekey';
					\OCP\Util::writeLog('passwords', "No valid sharekey found for user '" . $userId . "' before decrypting passwords.id " . $arr[$row]['id'] . " (" . $userSuppliedKey . "), possibly revoked by owner '" . $userKey . "'", \OCP\Util::WARN);
				}
			} else {
				$arr[$row]['pass'] = $e2->decrypt($encryptedPass, $key);
			}
			
			// GET DISPLAY NAMES ON LDAP BACKEND
			if ($has_ldap && $arr[$row]['id'] == '0') {
				$uuid = $arr[$row]['user_id'];
				// check for valid GUID first: e.g. pattern A98C5A1E-A742-4808-96FA-6F409E799937
				// checked using https://regex101.com
				if (preg_match('/(^\{?[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}\}?)$/i', $uuid)) {
					// TO DO: fix looking for a valid way to get display names
					// $arr[$row]['website'] = \OC::$server->getUserManager()->get($uuid)->getDisplayName();
				}
			}
			
			// remove eligable share users when using API
			if ($arr[$row]['id'] == '0' && $api) {
				unset($arr[$row]);
			}
		}

		return $arr;
	}

	private function handleException ($e) {
		if ($e instanceof DoesNotExistException ||
			$e instanceof MultipleObjectsReturnedException) {
			throw new NotFoundException($e->getMessage());
		} else {
			throw $e;
		}
	}

	public function find($id, $userId) {
		try {
			$result = $this->mapper->find($id, $userId);
			$arr_enc = json_encode($result);
			$arr = json_decode($arr_enc, true);
			
			$serverKey = \OC::$server->getConfig()->getSystemValue('passwordsalt', '');

			$userKey = $arr['user_id'];
			$userSuppliedKey = $arr['website'];
			$encryptedPass = $arr['pass'];
			$encryptedProperties = $arr['properties'];

			// notes for backwards compatibility with versions prior to v17
			$encryptedPassNotes = $arr['notes'];

			$e2 = new Encryption(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
			$key = Encryption::makeKey($userKey, $serverKey, $userSuppliedKey);
			$arr['properties'] = $e2->decrypt($encryptedProperties, $key);
			// notes for backwards compatibility with versions prior to v17
			$arr['notes'] = $e2->decrypt($encryptedPassNotes, $key);

			if ($userKey != $userId && $arr['id'] != 0) {
				// check for sharekey
				$sharekey_activeuser = $this->mapper->getShareKey($arr['id'], $userId);
				$pos = strrpos($arr['properties'], $sharekey_activeuser);
				if ($pos !== false) {
					$arr['pass'] = $e2->decrypt($encryptedPass, $key);
				} else {
					$arr['pass'] = 'oc_passwords_invalid_sharekey';
					\OCP\Util::writeLog('passwords', "No valid sharekey found for user '" . $userId . "' while decrypting passwords.id: " . $arr['id'], \OCP\Util::WARN);
				}
			} else {
				$arr['pass'] = $e2->decrypt($encryptedPass, $key);
			}

			return $arr;

		// in order to be able to plug in different storage backends like files
		// for instance it is a good idea to turn storage related exceptions
		// into service related exceptions so controllers and service users
		// have to deal with only one type of exception
		} catch(Exception $e) {
			$this->handleException($e);
		}
	}

	public function create($website, $pass, $loginname, $address, $notes, $category, $deleted, $userId) {
        $website = strip_tags($website);
        $loginname = strip_tags($loginname);
        $address = strip_tags($address);
        $notes = strip_tags($notes);

		$properties = 
			'"loginname" : "' . strip_tags($loginname) . '", ' .
			'"address" : "' . strip_tags($address) . '", ' .
			'"strength" : "' . Calculations::pwstrength($pass) . '", ' .
			'"length" : "' . strlen($pass) . '", ' .
			'"lower" : "' . Calculations::strhaslower($pass) . '", ' .
			'"upper" : "' . Calculations::strhasupper($pass) . '", ' .
			'"number" : "' . Calculations::strhasnumber($pass) . '", ' .
			'"special" : "' . Calculations::strhasspecial($pass) . '", ' .
			'"category" : "' . $category . '", ' .
			'"datechanged" : "' . date('Y-m-d') . '", ' .
			'"notes" : "' . htmlspecialchars($notes) . '"';

		$userKey = $userId;
		$serverKey = \OC::$server->getConfig()->getSystemValue('passwordsalt', '');
		$userSuppliedKey = $website;
		$key = Encryption::makeKey($userKey, $serverKey, $userSuppliedKey);
		$e = new Encryption(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
		$encryptedPass = $e->encrypt($pass, $key);
		$encryptedProperties = $e->encrypt($properties, $key);

		$password = new Password();
		// for backwards compatibility with versions prior to v17
		// these values are now encrypted in $encryptedProperties
		$password->setLoginname('');
		$password->setAddress('');
		$password->setNotes('');
		$password->setCreationDate('1970-01-01');
		
		//add to Activity stream
		Activity::addPassword($userId, $website, $loginname);

		$password->setUserId($userId);
		$password->setWebsite($website);
		$password->setPass($encryptedPass);
		$password->setProperties($encryptedProperties);
		$password->setDeleted($deleted);
		return $this->mapper->insert($password);
	}
	
	public function sendmail($kind, $website, $sharewith, $domain, $fullurl, $instancename, $userId) {
		$has_ldap = (\OC::$server->getUserSession()->getUser()->getBackendClassName() == 'LDAP');
		try {
			if (count($sharewith) > 0 AND $sharewith != '') {
				$mailer = \OC::$server->getMailer();
				$senderaddress = \OC::$server->getConfig()->getSystemValue('mail_smtpname', 'noreply@' . $domain);
				$me_username = $userId;
				if (!$has_ldap) {
					$me_displayname = \OC::$server->getUserManager()->get($me_username)->getDisplayName();
				}
				$me_email = \OC::$server->getUserManager()->get($me_username)->getEMailAddress();
				if (!filter_var($me_email, FILTER_VALIDATE_EMAIL)) {
					$me_email = '';
				} else {
					$me_email = '(' . $me_email . ')';
				}
				for ($x = 0; $x < count($sharewith); $x++) {
					$username = $sharewith[$x];
					if (!$has_ldap) {
						$userdisplayname = \OC::$server->getUserManager()->get($username)->getDisplayName();
					}
					$useremail = \OC::$server->getUserManager()->get($username)->getEMailAddress();
					// You want to mail in the recipients language:
					$userlanguage = \OC::$server->getConfig()->getUserValue($username, 'core', 'lang', 'en');
					// object that holds users langage:
					$userlang = \OC::$server->getL10N('passwords', $userlanguage);

					$appname = $userlang->t("Passwords");
	
					if (filter_var($useremail, FILTER_VALIDATE_EMAIL)) {
						$message = $mailer->createMessage();
						$message->setFrom([$senderaddress => $instancename . ' ' . $appname]);
						$message->setTo([$useremail => $userdisplayname]);

						if ($kind == 'start') {
							$message->setSubject($userlang->t('A password has been shared with you'));
							$message->setPlainBody(
								$userdisplayname .
								',\n\n' .
								$userlang->t('A password has been shared with you') .
								'.\n\n' .
								$userlang->t('Sender') . ':\n' .
								$me_displayname . ' ' . $me_email .
								'\n' .
								$userlang->t('Website or company') . ':\n' .
								$website .
								'\n\n' .
								$userlang->t('Login on %s to view the password', $instancename . ' ' . $appname) . ': ' . $fullurl . '.'
							);
							$message->setHtmlBody(
								$userdisplayname .
								',<br><br>' .
								$userlang->t('A password has been shared with you') .
								'.<br><br><strong>' .
								$userlang->t('Sender') . ':</strong><br>' .
								$me_displayname . ' ' . $me_email .
								'<br><strong>' .
								$userlang->t('Website or company') . ':</strong><br>' .
								$website .
								'<br><br>' .
								$userlang->t('Login on %s to view the password', '<a href="' . $fullurl . '" target="_blank" rel="noopener noreferrer">' . $instancename . ' ' . $appname . '</a>') . '.'
							);

						} elseif ($kind == 'stop') {
							$message->setSubject($userlang->t('A password is no longer shared with you'));
							$message->setPlainBody(
								$userdisplayname .
								',\n\n' .
								$userlang->t('A password is no longer shared with you') . '. ' . $userlang->t('%s no longer shares the password for %s', array($me_displayname . ' ' . $me_email, $website)) . '.' .
								'\n\n' .
								$userlang->t('Login on %s to view your other passwords', $instancename . ' ' . $appname) . ': ' . $fullurl . '.'
							);
							$message->setHtmlBody(
								$userdisplayname .
								',<br><br>' .
								$userlang->t('A password is no longer shared with you') . '. ' . $userlang->t('%s no longer shares the password for %s', array($me_displayname . ' ' . $me_email, '<strong>' . $website . '</strong>')) . '.' .
								'<br><br>' .
								$userlang->t('Login on %s to view your other passwords', '<a href="' . $fullurl . '" target="_blank">' . $instancename . ' ' . $appname . '</a>') . '.'
							);
						} elseif ($kind == 'masterpwreset') {
							$message->setSubject($userlang->t('Your master password has been reset'));
							$message->setPlainBody(
								$userdisplayname .
								',\n\n' .
								$userlang->t('Your master password for %s has been reset. You can now login using your %s password and set a new master password within the %s app.', array($instancename . ' ' . $appname, $instancename, $appname)) .
								'\n\n' .
								$userlang->t('Click here to login on %s', $instancename . ' ' . $appname) . ': ' . $fullurl . '.'
							);
							$message->setHtmlBody(
								$userdisplayname .
								',<br><br>' .
								$userlang->t('Your master password for %s has been reset. You can now login using your %s password and set a new master password within the %s app.', array($instancename . ' ' . $appname, $instancename, $appname)) .
								'<br><br>' .
								$userlang->t('Click here to login on %s', '<a href="' . $fullurl . '" target="_blank">' . $instancename . ' ' . $appname . '</a>') . '.'
							);
						}

						$mailer->send($message);
					}
				}
			}
			return true;
		} catch(Exception $e) {
			$this->handleException($e);
		}
	}

	public function update($id, $website, $pass, $loginname, $address, $notes, $sharewith, $category, $deleted, $datechanged, $userId) {
        $website = strip_tags($website);
        $loginname = strip_tags($loginname);
        $address = strip_tags($address);
        $notes = strip_tags($notes);

		$isShared = $this->mapper->isShared($id);
		$isTrashed = $this->mapper->isTrashed($id);
		$sharedWithUsers = $this->mapper->sharedWithUsers($id);
		$alreadySharedWithThisUser = 0;

		// remove old sharekeys and shares of this password
		$removesharekey = $this->mapper->deleteSharesbyID($id);
		if (count($sharewith) > 0 AND $sharewith != '') {
			if (function_exists('random_bytes')) {
				// PHP 7 only
				$sharekey = bin2hex(random_bytes(32)); 
			} else {
				$sharekey = \OC::$server->getSecureRandom()->generate(64, 'abcdef0123456789');
			}
			// add new sharekeys to db
			for ($x = 0; $x < count($sharewith); $x++) {
				//add to Activity stream when clicked on Share
				$alreadySharedWithThisUser = $this->mapper->isSharedWithUser($id, $sharewith[$x]);
				if ($alreadySharedWithThisUser == 0) {
					Activity::addShare($userId, $website, $loginname, $sharewith[$x]);
				}
				// now add the share key for the user
				$addsharekey = $this->mapper->insertShare($id, $sharewith[$x], $sharekey);
			}
		} else {
			// not shared now
			if ($isShared == 1) {
				Activity::deleteShare($userId, $website, $loginname);
				// notify users to whom this was shared to too
				$arr_enc = json_encode($sharedWithUsers);
				$arr = json_decode($arr_enc, true);

				foreach ($arr as $row => $value) {
					// uses website column or else findEntities won't work
					$userSharedTo = $arr[$row]['website'];
					Activity::deleteShareForUser($userId, $userSharedTo, $website, $loginname);
				}
			} else {
				if ($isTrashed == 0 && $deleted == 1) {
					// not in trash, but will be now
					Activity::deletePassword($userId, $website, $loginname);
				} else if ($isTrashed == 1 && $deleted == 0) {
					// was in trash, but won't be now
					Activity::undeletePassword($userId, $website, $loginname);
				} else {
					// then it's just an edit
					Activity::editPassword($userId, $website, $loginname);
				}
				
			}
		}

		$properties = 
			'"loginname" : "' . strip_tags($loginname) . '", ' .
			'"address" : "' . strip_tags($address) . '", ' .
			'"strength" : "' . Calculations::pwstrength($pass) . '", ' .
			'"length" : "' . strlen($pass) . '", ' .
			'"lower" : "' . Calculations::strhaslower($pass) . '", ' .
			'"upper" : "' . Calculations::strhasupper($pass) . '", ' .
			'"number" : "' . Calculations::strhasnumber($pass) . '", ' .
			'"special" : "' . Calculations::strhasspecial($pass) . '", ' .
			'"category" : "' . $category . '", ' .
			'"datechanged" : "' . $datechanged . '", ' .
			'"notes" : "' . htmlspecialchars($notes) . '"';

		if (count($sharewith) > 0 AND $sharewith != '') {
			$properties = 
				$properties . ', ' .
				'"sharekey" : "' . $sharekey . '", ' .
				'"sharedwith" : "' . implode(",", $sharewith) . '"';
		}
		
		// fix for SQLite, overriding NULL value
		if ($deleted != '1') {
			$deleted = '0';
		}

		try {
			$userKey = $userId;
			$serverKey = \OC::$server->getConfig()->getSystemValue('passwordsalt', '');
			$userSuppliedKey = $website;
			$key = Encryption::makeKey($userKey, $serverKey, $userSuppliedKey);
			$e = new Encryption(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
			$encryptedPass = $e->encrypt($pass, $key);
			$encryptedProperties = $e->encrypt($properties, $key);
			
			$password = $this->mapper->find($id, $userId);

			// for backwards compatibility with versions prior to v17
			// these values are now encrypted in $encryptedProperties
			$password->setLoginname('');
			$password->setAddress('');
			$password->setNotes('');
			$password->setCreationDate('1970-01-01');

			$password->setWebsite($website);
			$password->setPass($encryptedPass);
			$password->setProperties($encryptedProperties);
			$password->setDeleted($deleted);

			return $this->mapper->update($password);
		} catch(Exception $e) {
			$this->handleException($e);
		}
	}

	public function delete($id, $userId) {
		try {
			$password = $this->mapper->find($id, $userId);
			$arr_enc = json_encode($password);
			$arr = json_decode($arr_enc, true);
			$this->mapper->delete($password);
			// add to activity stream
			Activity::deletePermanentPassword($userId, $arr['website']);
			return $password;
		} catch(Exception $e) {
			$this->handleException($e);
		}
	}
}

class Activity {
	
	public static function addPassword($userId, $website, $loginname) {
		$time = time();
		$new_activity = \OC::$server->getActivityManager()->generateEvent();
		$new_activity->setApp(\OCA\Passwords\Activity::PASSWORDS_APP);
		$new_activity->setType(\OCA\Passwords\Activity::TYPE_ADDED);
		$new_activity->setAffectedUser($userId);
		$new_activity->setSubject(\OCA\Passwords\Activity::SUBJECT_ADDED_USER_SELF, [$website, $loginname]);
		$new_activity->setTimestamp($time);
		\OC::$server->getActivityManager()->publish($new_activity);
	}
	
	public static function editPassword($userId, $website, $loginname) {
		$time = time();
		$new_activity = \OC::$server->getActivityManager()->generateEvent();
		$new_activity->setApp(\OCA\Passwords\Activity::PASSWORDS_APP);
		$new_activity->setType(\OCA\Passwords\Activity::TYPE_EDITED);
		$new_activity->setAffectedUser($userId);
		$new_activity->setSubject(\OCA\Passwords\Activity::SUBJECT_EDITED_USER_SELF, [$website, $loginname]);
		$new_activity->setTimestamp($time);
		\OC::$server->getActivityManager()->publish($new_activity);
	}
	
	public static function deletePassword($userId, $website, $loginname) {
		$time = time();
		$new_activity = \OC::$server->getActivityManager()->generateEvent();
		$new_activity->setApp(\OCA\Passwords\Activity::PASSWORDS_APP);
		$new_activity->setType(\OCA\Passwords\Activity::TYPE_DELETED);
		$new_activity->setAffectedUser($userId);
		$new_activity->setSubject(\OCA\Passwords\Activity::SUBJECT_DELETED_USER_SELF, [$website, $loginname]);
		$new_activity->setTimestamp($time);
		\OC::$server->getActivityManager()->publish($new_activity);
	}

	public static function deletePermanentPassword($userId, $website) {
		$time = time();
		$new_activity = \OC::$server->getActivityManager()->generateEvent();
		$new_activity->setApp(\OCA\Passwords\Activity::PASSWORDS_APP);
		$new_activity->setType(\OCA\Passwords\Activity::TYPE_DELETED_PERMANENT);
		$new_activity->setAffectedUser($userId);
		$new_activity->setSubject(\OCA\Passwords\Activity::SUBJECT_DELETED_PERMANENT_USER_SELF, [$website]);
		$new_activity->setTimestamp($time);
		\OC::$server->getActivityManager()->publish($new_activity);
	}

	public static function undeletePassword($userId, $website, $loginname) {
		$time = time();
		$new_activity = \OC::$server->getActivityManager()->generateEvent();
		$new_activity->setApp(\OCA\Passwords\Activity::PASSWORDS_APP);
		$new_activity->setType(\OCA\Passwords\Activity::TYPE_UNDELETED);
		$new_activity->setAffectedUser($userId);
		$new_activity->setSubject(\OCA\Passwords\Activity::SUBJECT_UNDELETED_USER_SELF, [$website, $loginname]);
		$new_activity->setTimestamp($time);
		\OC::$server->getActivityManager()->publish($new_activity);
	}
	
	public static function addShare($userId, $website, $loginname, $sharewith) {
		$time = time();
		$activity_owner = \OC::$server->getActivityManager()->generateEvent();
		$activity_owner->setApp(\OCA\Passwords\Activity::PASSWORDS_APP);
		$activity_owner->setType(\OCA\Passwords\Activity::TYPE_SHARED_BY_ME);
		$activity_owner->setAffectedUser($userId);
		$activity_owner->setSubject(\OCA\Passwords\Activity::SUBJECT_SHARED_WITH, [$website, $loginname, $sharewith]);
		$activity_owner->setTimestamp($time);
		\OC::$server->getActivityManager()->publish($activity_owner);

		$activity_receiver = \OC::$server->getActivityManager()->generateEvent();
		$activity_receiver->setApp(\OCA\Passwords\Activity::PASSWORDS_APP);
		$activity_receiver->setType(\OCA\Passwords\Activity::TYPE_SHARED_TO_ME);
		$activity_receiver->setAffectedUser($sharewith);
		$activity_receiver->setSubject(\OCA\Passwords\Activity::SUBJECT_SHARED_WITH_ME, [$userId, $website, $loginname]);
		$activity_receiver->setTimestamp($time);
		\OC::$server->getActivityManager()->publish($activity_receiver);
	}
	
	public static function deleteShare($userId, $website, $loginname) {
		$time = time();
		$activity_owner = \OC::$server->getActivityManager()->generateEvent();
		$activity_owner->setApp(\OCA\Passwords\Activity::PASSWORDS_APP);
		$activity_owner->setType(\OCA\Passwords\Activity::TYPE_SHARE_STOP);
		$activity_owner->setAffectedUser($userId);
		$activity_owner->setSubject(\OCA\Passwords\Activity::SUBJECT_UNSHARED, [$website, $loginname]);
		$activity_owner->setTimestamp($time);
		\OC::$server->getActivityManager()->publish($activity_owner);
	}

	public static function deleteShareForUser($userId, $userSharedTo, $website, $loginname) {
		$time = time();
		$activity_receiver = \OC::$server->getActivityManager()->generateEvent();
		$activity_receiver->setApp(\OCA\Passwords\Activity::PASSWORDS_APP);
		$activity_receiver->setType(\OCA\Passwords\Activity::TYPE_SHARE_STOP_BY_ME);
		$activity_receiver->setAffectedUser($userSharedTo);
		$activity_receiver->setSubject(\OCA\Passwords\Activity::SUBJECT_UNSHARED_BY_ME, [$userId, $website, $loginname]);
		$activity_receiver->setTimestamp($time);
		\OC::$server->getActivityManager()->publish($activity_receiver);
	}
}

class Calculations {

	public static function strhaslower($str) {
		return (strtoupper($str) != $str) ? 1 : 0;
	}
	public static function strhasupper($str) {
		return (strtolower($str) != $str) ? 1 : 0;
	}
	public static function strhasnumber($str) {
		return (preg_match('/[0-9]/', $str)) ? 1 : 0;
	}
	public static function strhasspecial($str) {
		for ($i = 0; $i <= strlen($str); $i++) {
			$number = 0;
			$number = Calculations::uniord(substr($str, $i, 1));
			switch(true) {
				case $number == 33:
				case $number >= 35 && $number <= 36:
				case $number == 38:
				case $number >= 40 && $number <= 41:
				case $number == 43:
				case $number >= 45 && $number <= 47:
				case $number >= 58 && $number <= 60:
				case $number >= 62 && $number <= 64:
				case $number == 95:
					return 1;
					break;
			}
		}
		// no special chars
		return 0;
	}
	public static function pwstrength($password) {

		$hasLowerCase = false;
		$hasUpperCase = false;
		$hasNumber = false;
		$hasSpecialChar1 = false;
		$hasSpecialChar2 = false;
		$hasSpecialChar3 = false;
		$hasSpecialChar4 = false;

		$passwordLength = strlen($password);

		$strength_calc = 0;

		// check length
		switch(true) {
			case $passwordLength >= 8:
				//$strength_calc = 1;
				break;
			case $passwordLength <= 4:
				// password smaller than 5 chars is always bad
				return 0;
				break;
		}

		// loop ONCE through password
		for ($i = 1; $i < $passwordLength + 1; $i++) {
			
			$charInStr = substr($password, $i, 1);
			$charInt = Calculations::uniord($charInStr);

			switch(true) {
				case $charInt >= 97 && $charInt <= 122:
					if (!$hasLowerCase) {
						$strength_calc = $strength_calc + 1;
						$hasLowerCase = true;
					}
					break;
				case $charInt >= 65 && $charInt <= 90:
					if (!$hasUpperCase) {
						$strength_calc = $strength_calc + 1;
						$hasUpperCase = true;
					}
					break;
				case $charInt >= 48 && $charInt <= 57:
					if (!$hasNumber) {
						$strength_calc = $strength_calc + 1;
						$hasNumber = true;
					}
					break;
				case $charInt >= 33 && $charInt <= 47:
					if (!$hasSpecialChar1) {
						$strength_calc = $strength_calc + 1;
						$hasSpecialChar1 = true;
					}
					break;
				case $charInt >= 58 && $charInt <= 64:
					if (!$hasSpecialChar2) {
						$strength_calc = $strength_calc + 1;
						$hasSpecialChar2 = true;
					}
					break;
				case $charInt >= 91 && $charInt <= 96:
					if (!$hasSpecialChar3) {
						$strength_calc = $strength_calc + 1;
						$hasSpecialChar3 = true;
					}
					break;
				case $charInt >= 123 && $charInt <= 255:
					if (!$hasSpecialChar4) {
						$strength_calc = $strength_calc + 1;
						$hasSpecialChar4 = true;
					}
					break;
			}

		}
		
		$strength_calc = $strength_calc + (floor($passwordLength / 8) * (($hasLowerCase ? 1 : 0) + ($hasUpperCase ? 1 : 0) + ($hasNumber ? 1 : 0) + ($hasSpecialChar1 ? 1 : 0) + ($hasSpecialChar2 ? 1 : 0) + ($hasSpecialChar3 ? 1 : 0) + ($hasSpecialChar4 ? 1 : 0)));
		
		$power = 6;
		$strength_calc = $strength_calc + round(pow($passwordLength, $power) / pow(10, $power + 1));

		return $strength_calc;
	}
	public static function uniord($c) {
		// http://stackoverflow.com/a/10333324
		// used to replace JS's 'charCodeAt' function
		if ($c == '') {
			return false;
		}
		$h = ord($c{0});
		if ($h <= 0x7F) {
			return $h;
		} else if ($h < 0xC2) {
			return false;
		} else if ($h <= 0xDF) {
			return ($h & 0x1F) << 6 | (ord($c{1}) & 0x3F);
		} else if ($h <= 0xEF) {
			return ($h & 0x0F) << 12 | (ord($c{1}) & 0x3F) << 6
									 | (ord($c{2}) & 0x3F);
		} else if ($h <= 0xF4) {
			return ($h & 0x0F) << 18 | (ord($c{1}) & 0x3F) << 12
									 | (ord($c{2}) & 0x3F) << 6
									 | (ord($c{3}) & 0x3F);
		} else {
			return false;
		}
	}
}

class Encryption {
	// http://stackoverflow.com/questions/5089841/two-way-encryption-i-need-to-store-passwords-that-can-be-retrieved?answertab=votes#tab-top

	/**
	 * A class to handle secure encryption and decryption of arbitrary data
	 *
	 *  Note that this is not just straight encryption. It also has a few other
	 *  features in it to make the encrypted data far more secure.  Note that any
	 *  other implementations used to decrypt data will have to do the same exact
	 *  operations.  
	 *
	 * Security Benefits:
	 *
	 * - Uses Key stretching
	 * - Hides the Initialization Vector
	 * - Does HMAC verification of source data
	 *
	 */

	public static function makeKey($userKey, $serverKey, $userSuppliedKey) {
		$key = hash_hmac('sha512', $userKey, $serverKey);
		$key = hash_hmac('sha512', $key, $userSuppliedKey);
		return $key;
	} 

	/**
	 * @var string $cipher The mcrypt cipher to use for this instance
	 */
	protected $cipher = '';
	
	/**
	 * @var int $mode The mcrypt cipher mode to use
	 */
	protected $mode = '';

	/**
	 * @var int $rounds The number of rounds to feed into PBKDF2 for key generation
	 */
	protected $rounds = 100;

	/**
	 * Constructor!
	 *
	 * @param string $cipher The MCRYPT_* cypher to use for this instance
	 * @param int    $mode   The MCRYPT_MODE_* mode to use for this instance
	 * @param int    $rounds The number of PBKDF2 rounds to do on the key
	 */
	public function __construct($cipher, $mode, $rounds = 100) {
		$this->cipher = $cipher;
		$this->mode = $mode;
		$this->rounds = (int) $rounds;
	}

	/**
	 * Decrypt the data with the provided key
	 *
	 * @param string $data The encrypted datat to decrypt
	 * @param string $key  The key to use for decryption
	 * 
	 * @returns string|false The returned string if decryption is successful
	 *                           false if it is not
	 */
	public function decrypt($data_hex, $key) {

		if ( !function_exists( 'hex2bin' ) ) {
			function hex2bin( $str ) {
				$sbin = "";
				$len = strlen( $str );
				for ( $i = 0; $i < $len; $i += 2 ) {
					$sbin .= pack( "H*", substr( $str, $i, 2 ) );
				}

				return $sbin;
			}
		}

		$data = hex2bin($data_hex);

		$salt = substr($data, 0, 128);
		$enc = substr($data, 128, -64);
		$mac = substr($data, -64);

		list ($cipherKey, $macKey, $iv) = $this->getKeys($salt, $key);

		//if (!hash_equals(hash_hmac('sha512', $enc, $macKey, true), $mac)) {
		if (!Encryption::hash_equals(hash_hmac('sha512', $enc, $macKey, true), $mac)) {
			 return false;
		}

		$dec = mcrypt_decrypt($this->cipher, $cipherKey, $enc, $this->mode, $iv);

		$data = $this->unpad($dec);

		return $data;
	}

	/**
	 * Encrypt the supplied data using the supplied key
	 * 
	 * @param string $data The data to encrypt
	 * @param string $key  The key to encrypt with
	 *
	 * @returns string The encrypted data
	 */
	public function encrypt($data, $key) {
		$salt = mcrypt_create_iv(128, MCRYPT_DEV_URANDOM);
		//list ($cipherKey, $macKey, $iv) = $this->getKeys($salt, $key);
		list ($cipherKey, $macKey, $iv) = Encryption::getKeys($salt, $key);
		//$data = $this->pad($data);
		$data = Encryption::pad($data);
		$enc = mcrypt_encrypt($this->cipher, $cipherKey, $data, $this->mode, $iv);

		$mac = hash_hmac('sha512', $enc, $macKey, true);

		$data = $salt . $enc . $mac;

		$data = bin2hex($salt . $enc . $mac);
		//$data = pack("H*" , $data);
		return $data;
		
		return $salt . $enc . $mac;

	}

	/**
	 * Generates a set of keys given a random salt and a master key
	 *
	 * @param string $salt A random string to change the keys each encryption
	 * @param string $key  The supplied key to encrypt with
	 *
	 * @returns array An array of keys (a cipher key, a mac key, and a IV)
	 */
	protected function getKeys($salt, $key) {
		$ivSize = mcrypt_get_iv_size($this->cipher, $this->mode);
		$keySize = mcrypt_get_key_size($this->cipher, $this->mode);
		$length = 2 * $keySize + $ivSize;

		//$key = $this->pbkdf2('sha512', $key, $salt, $this->rounds, $length);
		$key = Encryption::pbkdf2('sha512', $key, $salt, $this->rounds, $length);

		$cipherKey = substr($key, 0, $keySize);
		$macKey = substr($key, $keySize, $keySize);
		$iv = substr($key, 2 * $keySize);
		return array($cipherKey, $macKey, $iv);
	}

	function hash_equals($a, $b) {
		$key = mcrypt_create_iv(128, MCRYPT_DEV_URANDOM);
		return hash_hmac('sha512', $a, $key) === hash_hmac('sha512', $b, $key);
	}

	/**
	 * Stretch the key using the PBKDF2 algorithm
	 *
	 * @see http://en.wikipedia.org/wiki/PBKDF2
	 *
	 * @param string $algo   The algorithm to use
	 * @param string $key    The key to stretch
	 * @param string $salt   A random salt
	 * @param int    $rounds The number of rounds to derive
	 * @param int    $length The length of the output key
	 *
	 * @returns string The derived key.
	 */
	protected function pbkdf2($algo, $key, $salt, $rounds, $length) {
		$size   = strlen(hash($algo, '', true));
		$len    = ceil($length / $size);
		$result = '';
		for ($i = 1; $i <= $len; $i++) {
			$tmp = hash_hmac($algo, $salt . pack('N', $i), $key, true);
			$res = $tmp;
			for ($j = 1; $j < $rounds; $j++) {
				 $tmp  = hash_hmac($algo, $tmp, $key, true);
				 $res ^= $tmp;
			}
			$result .= $res;
		}
		return substr($result, 0, $length);
	}

	protected function pad($data) {
		$length = mcrypt_get_block_size($this->cipher, $this->mode);
		$padAmount = $length - strlen($data) % $length;
		if ($padAmount == 0) {
			$padAmount = $length;
		}
		return $data . str_repeat(chr($padAmount), $padAmount);
	}

	protected function unpad($data) {
		$length = mcrypt_get_block_size($this->cipher, $this->mode);
		$last = ord($data[strlen($data) - 1]);
		if ($last > $length) return false;
		if (substr($data, -1 * $last) !== str_repeat(chr($last), $last)) {
			return false;
		}
		return substr($data, 0, -1 * $last);
	}
}
