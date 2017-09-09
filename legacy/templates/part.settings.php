<div id="app-settings" 
sharing-allowed="<?php p(OC::$server->getConfig()->getAppValue('core', 'shareapi_enabled', 'yes')) ?>" 
active-table="active" 
session-timeout="<?php p(OC::$server->getConfig()->getSystemValue('session_lifetime', 60*15)) ?>"
root-folder="<?php p(OC::$SERVERROOT) ?>"
app-path="<?php p(OC::$server->getConfig()->getAppValue('passwords', 'app_path', OC::$SERVERROOT.'/apps')) ?>"
user-backend="<?php p(OC::$server->getUserSession()->getUser()->getBackendClassName()) ?>" 
instance-name="<?php p($theme->getName()) ?>" 
>
	<textarea id="session_lifetime" disabled="true"></textarea>
	<div id="CSVtableDIV">
		<textarea id="CSVcontent"></textarea>
		<textarea id="CSVcolumnCount"></textarea>
		<h3><?php p($l->t('Select options')); ?>:</h3>
		<div id="CSVbuttons">
			<button id="CSVheadersBtn"><?php p($l->t('File contains headers')); ?></button>
			<button id="CSVquotationmarksBtn"><?php p($l->t('Values are separated by quotation marks')); ?></button>
			<button id="CSVescapeslashBtn"><?php p($l->t('Characters %s and %s need to be escaped', array('\\', '"'))); ?></button>
			<button id="CSVsplit_rnBtn"><?php p($l->t('Lines are split on')); ?> \r\n</button>
		</div>
		<br><br><br>
		<h3 id="CSVpreviewTitle"><?php p($l->t('Preview')); ?> :</h3>
		<div id="CSVtableScroll">
			<table id="CSVtable">
				<!-- TABLE WILL BE POPULATED WITH JS -->
			</table>
		</div>
		<button id="selectAll"><?php p($l->t('Select all')); ?></button>
		<button id="selectNone"><?php p($l->t('Select none')); ?></button>
		<div id="CSVerrorfield"></div>
		<button id="importCancel"><?php p($l->t('Cancel')); ?></button>
		<button id="importStart"><?php p($l->t('Import')); ?></button>
	</div>
	<div id="CSVprogressDIV">
		<p id="CSVprogressTitle"><?php p($l->t('Import')); ?></p>
		<input id="CSVprogressActive"/>
		<input id="CSVprogressTotal"/>
		<p id="CSVprogressText1">0 / 0</p>
		<p id="CSVprogressText2">0 / 0</p>
		<div id="CSVprogressBar">
			<div id="CSVprogressDone"></div>
		</div>
	</div>
	<div id="app-settings-header">
		<button id="settingsbtn" class="settings-button" data-apps-slide-toggle="#app-settings-content"></button>
	</div>
	<div id="app-settings-content">

		<div id="app-settings-authentication">
			<h3><?php p($l->t('Extra authentication')); ?></h3>

			<label for="extra_password"><?php p($l->t('When entering app, require:')); ?></label>
			<select id="extra_password">
				<option value="none"><?php p($l->t('No extra password')); ?></option>
				<?php 
				$user_saml = OC::$server->getConfig()->getAppValue('user_saml', 'enabled', 'no');
				// block ownCloud/NextCloud authentication when using user_saml.
				if ($user_saml == 'no') { ?>
					<option value="owncloud"><?php 
						// easier for translators than '%s password'
						p(preg_replace('/owncloud/i', $theme->getName(), $l->t('ownCloud password'))); ?></option>
				<?php } ?>
				<option value="master"><?php p($l->t('Master password')); ?></option>
			</select>
			<div id="div_edit_master_password">
				<input class="button nav-btn" type="button" id="edit_masterkey" value="<?php p($l->t('Edit')); ?>">
			</div>
			<div id="div_extra_auth_password">
				<div id="show_lockbutton_div">
					<input class="checkbox" type="checkbox" id="show_lockbutton">
					<label for="show_lockbutton"><?php p($l->t('Show lock button')); ?></label>
				</div>
				<p><?php p($l->t('Stay authenticated for:')); ?></p>
				<input type="text" id="auth_timer" value="90">
				<label for="auth_timer" id="auth_timersettext"><?php p($l->t('seconds')); ?></label>
			</div>
		</div>

		<div id="div_master_password">
			<input type="password" id="new_masterkey1" placeholder="<?php p($l->t("Enter new password")); ?>">
			<br>
			<input type="password" id="new_masterkey2" placeholder="<?php p($l->t("Confirm new password")); ?>">
			<p>
				<?php p($l->t('Note: when you lose this password, you can never enter the %s app again!',  $theme->getName() . ' ' . $l->t("Passwords"))); ?>
			</p>
			<input class="button nav-btn" type="button" id="save_masterkey" value="<?php p($l->t('Save')); ?>">
		</div>

		<hr>
	
		<div id="app-settings-backup">
			<h3><?php p($l->t('Download Backup')); ?></h3>
			<button id="backupDL" class="button icon-history nav-btn" type="button"><?php p($l->t("Download Backup")); ?></button>
			<hr>
		</div>
		<?php if (\OC::$server->getConfig()->getAppValue('passwords', 'backup_allowed', 'false') == 'false') { ?>
			<div id="app-settings-backup_disallowed">
				<h3><?php p($l->t('Download Backup')); ?></h3>
				<p><?php p($l->t('Your administrator does not allow you to download backups')); ?>.</p>
				<hr>
			</div>
		<?php } ?>
		<div id="app-settings-csv">
			<h3><?php p($l->t('Import CSV File')); ?></h3>
			<input type="file" id="upload_csv" accept=".csv" >
		</div>
		<?php
			$instancename = $theme->getName();
			$passwordsname = $l->t("Passwords");
			$passwordsversion = OC::$server->getConfig()->getAppValue('passwords', 'installed_version', '');
		?>
		<hr>
	</div>
</div>
