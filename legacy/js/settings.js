
// ADMIN SETTINGS
// app_path
//		Location of this app. This is needed to support other app folders like /owncloud/apps2
// backup_allowed
//		Allow unencrypted backups to be downloaded by users
// check_version
//		Allow to check installed app version with master version and latest release on github.com/fcturner/passwords
// days_orange
//		Days from which creation date (and password) gets orange color
// days_red
//		Days from which creation date (and password) gets red color
// disable_contextmenu
//		Disable context menu on whole app
// https_check
//		Check for secure connection before activating app
// icons_allowed
//		Allow users to view website icons, by sending IP to another server
// icons_service
//		Service used for website icons: Google (ggl), DuckDuckGo (ddg)

// USER SETTINGS
// auth_timer --> WILL BE SET IN APP ITSELF, AFTER AUTHENTICATION
//		lifetime of authentication, auth cookie will be deleted when it reaches 0
// extra_auth_type
//		Extra authentication to enter the app: none (none), ownCloud password (owncloud), master password (master)
// hide_attributes
//		Hide the attributes strength and last changed date
// hide_passwords
//		Hide passwords by showing them as '*****'
// hide_usernames
//		Hide usernames by showing them as '*****'
// icons_show
//		Show website icons, using service selected by admin
// icons_size
//		Set size of website icons: 16px, 24px or 32px (default)
// master_password
//		SHA-2 (512-bit) password that is needed to enter the app
// timer
//		Use countdown timer, user will be logged off when it reaches 0

$(document).ready(function() {

	var Settings = function(baseUrl) {
		this._baseUrl = baseUrl;
		this._settings = [];
	};

	Settings.prototype = {
		load: function() {
			var deferred = $.Deferred();
			var self = this;
			$.ajax({
				url: this._baseUrl,
				method: 'GET',
				async: false
			}).done(function( settings ) {
				self._settings = settings;
			}).fail(function() {
				deferred.reject();
			});
			return deferred.promise();
		},

		setUserKey: function(key, value) {
			var request = $.ajax({
				url: this._baseUrl + '/' + key + '/' + value,
				method: 'POST'
			});
			request.done(function(msg) {
				$('.msg-passwords').removeClass("msg_error");
				$('.msg-passwords').text('');
			});
			request.fail(function( jqXHR, textStatus ) {
				$('.msg-passwords').addClass("msg_error");
				$('.msg-passwords').text(t('passwords', 'Error while saving field') + ' ' + key + '!');
			});
		},
		setAdminKey: function(key, value) {
			var request = $.ajax({
				url: this._baseUrl + '/' + key + '/' + value + '/admin1/admin2',
				method: 'POST'
			});
			request.done(function(msg) {
				$('.msg-passwords').removeClass("msg_error");
				$('.msg-passwords').text('');
			});
			request.fail(function( jqXHR, textStatus ) {
				$('.msg-passwords').addClass("msg_error");
				$('.msg-passwords').text(t('passwords', 'Error while saving field') + ' ' + key + '!');
			});
		},
		getKey: function(key) {
			for (var k in this._settings)
			{
				if (k == key)
					return this._settings[k];
			}
		},
		getAll: function() {
			return this._settings;
		},
		sendmail: function(kind, website, sharewith, domain, fullurl, instancename) {
			var sharewithArr = [];
			if ($.isArray(sharewith)) {
				sharewithArr = sharewith;
			} else if (!sharewith) {
				sharewithArr = "";
			} else {
				sharewithArr = sharewith.split(', ');
			}
			var result = false;
			var request = $.ajax({
				url: generateUrl('/mail'),
				data: {
					'kind' : kind,
					'website' : website,
					'sharewith' : sharewithArr,
					'domain' : domain,
					'fullurl' : fullurl,
					'instancename' : instancename
				},
				method: 'POST',
				async: false
			});
			
			request.done(function(msg) {
				// will be true or false;
				result = msg;
			});
 
			request.fail(function( jqXHR, textStatus ) {
				//alert( "Error while authenticating: " + textStatus );
			});
			
			return result;
		}
	};



	var settings = new Settings(generateUrl('/settings'));
	settings.load();

// ADMIN SETTINGS

	// fill the boxes
	$('#check_version').prop('checked', (settings.getKey('check_version').toLowerCase() == 'true'));
	
	$('#app_path').val(settings.getKey('app_path'));

	$('#https_check').prop('checked', (settings.getKey('https_check').toLowerCase() == 'true'));
	$('#backup_allowed').prop('checked', (settings.getKey('backup_allowed').toLowerCase() == 'true'));
	$('#disable_contextmenu').prop('checked', (settings.getKey('disable_contextmenu').toLowerCase() == 'true'));
	
	$('#icons_allowed').prop('checked', (settings.getKey('icons_allowed').toLowerCase() == 'true'));
	if (settings.getKey('icons_service') == 'ddg') {
		$('#ddg_value').prop('checked', true); 
	}
	if (settings.getKey('icons_service') == 'ggl') {
		$('#ggl_value').prop('checked', true); 
	}
	updateIconService();

	$('#days_orange').val(settings.getKey('days_orange'));
	$('#days_red').val(settings.getKey('days_red'));
	updateOrange();
	updateRed();

	// Admin settings
	$('#check_version').change(function () {
		settings.setAdminKey('check_version', $(this).is(":checked"));
		if($(this).is(":checked")) {
			setTimeout("location.reload();", 2500);
		}
	});
	
	$('#app_path').keyup(function() {
		settings.setAdminKey('app_path', $(this).val());
	});

	$('#https_check').change(function () {
		settings.setAdminKey('https_check', $(this).is(":checked"));
	});

	$('#backup_allowed').change(function () {
		settings.setAdminKey('backup_allowed', $(this).is(":checked"));
	});

	$('#disable_contextmenu').change(function () {
		settings.setAdminKey('disable_contextmenu', $(this).is(":checked"));
	});

	$('#icons_allowed').change(function () {
		settings.setAdminKey('icons_allowed', $(this).is(":checked"));
		updateIconService();
	});

	$('#ddg_value').change(function () {
		settings.setAdminKey('icons_service', 'ddg');
	});

	$('#ggl_value').change(function () {
		settings.setAdminKey('icons_service', 'ggl');
	});

	$('#days_red').keyup(function() {
		var val = Number($('#days_red').val());
		if ((val > 0) && (val < 10000) && (val > Number($('#days_orange').val()))) {
			settings.setAdminKey('days_red', val);
			updateRed();
		}
	});

	$('#days_orange').keyup(function() {
		var val = Number($('#days_orange').val());
		if ((val > 0) && (val < 10000) && (val < Number($('#days_red').val())) && (Number($('#days_red').val()) > 0)) {
			settings.setAdminKey('days_orange', val);
			updateOrange();
		}
	});

	$('#masterreset').click(function() {
		var user = $('#masterresetid').val();
		settings.setAdminKey('resetmaster', user);
		$('#masterresetid').val('');
		settings.sendmail('masterpwreset', '', user, URLtoDomain(window.location.href), 'http://' + URLtoDomain(window.location.href), $('#password-settings').attr('instance-name'));
		OCdialogs.info(t('passwords', "The master passwords for user %s has been reset. If you've set up email, this user has been emailed about this too.").replace('%s', user), t('passwords', 'Master password'), function() { return false; }, true);
		return true;
	});

// PERSONAL SETTINGS
	
	// fill the boxes
	if (settings.getKey('icons_allowed').toLowerCase() == 'true') {
		$('#icons_show').prop('checked', (settings.getKey('icons_show').toLowerCase() == 'true'));
		var size = settings.getKey('icons_size');
		var service = settings.getKey('icons_service');
		$('#icons_size').val(size);
		$('#icons_size_preview tr').append(updatePreview(size, service));
	} else {
		$('#icons_show_div').remove();
	}

	$('#hide_usernames').prop('checked', (settings.getKey('hide_usernames').toLowerCase() == 'true'));
	$('#hide_passwords').prop('checked', (settings.getKey('hide_passwords').toLowerCase() == 'true'));
	$('#hide_attributes').prop('checked', (settings.getKey('hide_attributes').toLowerCase() == 'true'));
	$('#timer').val(settings.getKey('timer'));

	if ($('#timer').val() == 0) {
		$('#timer_bool').prop('checked', false);
		$('#timersettext').hide();
		$('#timer').hide();
	} else {
		$('#timer_bool').prop('checked', true);
		$('#timersettext').show();
		$('#timer').show();
		if ($('#timer').val() < 61) {
			$('#timersettext').text(t('passwords', 'seconds'));
		} else {
			$('#timersettext').text(t('passwords', 'seconds') + ' (' + int2time($('#timer').val()) + ' ' + t('passwords', 'minutes') + ')');
		}
	}

	// Personal settings
	$('#icons_show').change(function () {
		settings.setUserKey('icons_show', $(this).is(":checked"));
	});
	$('#icons_size').change(function () {
		var size = $(this).val();
		var service = settings.getKey('icons_service');
		settings.setUserKey('icons_size', size);
		$('#icons_size_preview tr').html('');
		$('#icons_size_preview tr').append(updatePreview(size, service));
	});

	$('#hide_usernames').change(function () {
		settings.setUserKey('hide_usernames', $(this).is(":checked"));
	});

	$('#hide_passwords').change(function () {
		settings.setUserKey('hide_passwords', $(this).is(":checked"));
	});

	$('#hide_attributes').change(function () {
		settings.setUserKey('hide_attributes', $(this).is(":checked"));
	});

	$('#hide_attributes').change(function () {
		settings.setUserKey('hide_attributes', $(this).is(":checked"));
	});

	$('#timer_bool').change(function () {
		if ($('#timer_bool').prop('checked')) {
			settings.setUserKey('timer', 60);
			$('#timersettext').show();
			$('#timer').show();
			$('#timer').val(60);
		} else {
			settings.setUserKey('timer', 0);
			$('#timersettext').hide();
			$('#timer').hide();
		}
	});
	$('#timer').keyup(function () {
		if ($('#timer').val() == '') {
			settings.setUserKey('timer', 0);
		} else {
			if (!isNumeric($('#timer').val())) {
				OCdialogs.alert(t('passwords', 'Fill in a number between %s and %s').replace('%s', '10').replace('%s', '3599'), t('passwords', 'Use inactivity countdown'), function() { return false; }, true);
				$('#timer').val(60);
				settings.setUserKey('timer', 60);
				return false;
			}
			if ($('#timer').val() > 3599) {
				$('#timer').val(3599);
			}
			if ($('#timer').val() < 61) {
				$('#timersettext').text(t('passwords', 'seconds'));
			} else {
				$('#timersettext').text(t('passwords', 'seconds') + ' (' + int2time($('#timer').val()) + ' ' + t('passwords', 'minutes') + ')');
			}
			settings.setUserKey('timer', $('#timer').val());
		}
	});

});

function updateRed() {
	$('#daysRed').text(
		t('passwords', 'Red') 
		+ ': ' 
		+ t('passwords', 'after') 
		+ ' ' 
		+ (Number($('#days_red').val()) + 1) 
		+ ' ' 
		+ t('passwords', 'days')
	);
}
function updateOrange() {
	$('#daysOrange').text(
		t('passwords', 'Orange') 
		+ ': ' 
		+ (Number($('#days_orange').val()) + 1) 
		+ ' ' 
		+ t('passwords', 'to')
	);
}
function updateIconService() {
	if ($('#icons_allowed').prop('checked')) {
		$('#ggl_value').prop("enabled", true);
		$('#ddg_value').prop("enabled", true);
		$('#ggl_value').prop("disabled", false);
		$('#ddg_value').prop("disabled", false);
	} else {
		$('#ggl_value').prop("checked", false);
		$('#ddg_value').prop("checked", false);
		$('#ggl_value').prop("enabled", false);
		$('#ddg_value').prop("enabled", false);
		$('#ggl_value').prop("disabled", true);
		$('#ddg_value').prop("disabled", true);
	}
}
function isNumeric(n) {
	return !isNaN(parseFloat(n)) && isFinite(n);
}
function int2time(integer) {
	if (integer !== undefined) {
		return new Date(null, null, null, null, null, integer).toTimeString().match(/\d{2}:\d{2}:\d{2}/)[0].substr(3, 5);
	}
}
function generateUrl(extra_path) {
	var serverroot = $('#password-settings').attr('root-folder');
	var approot = $('#password-settings').attr('app-path') + '/passwords';
	approot = approot.replace(/\/\//g, '/');
	var url = approot.replace(serverroot, '');
	var OCurl = OC.generateUrl(url + '/' + extra_path);
	OCurl = OCurl.replace(/\/\//g, '/');
	return OCurl;
}
function updatePreview(size, service) {
	if (service == 'ddg') {
		var tablerow = '<td><img style="width:' + size + 'px;height:' + size + 'px;" src="https://icons.duckduckgo.com/ip2/duckduckgo.com.ico">duckduckgo.com</td>';
	}
	if (service == 'ggl') {
		var tablerow = '<td><img style="width:' + size + 'px;height:' + size + 'px;" src="https://www.google.com/s2/favicons?domain=google.com">google.com</td>';
	}
	tablerow = tablerow + 
			'<td><img style="width:' + size + 'px;height:' + size + 'px;" src="https://www.google.com/s2/favicons?domain=facebook.com">facebook.com</td>' +
			'<td><img style="width:' + size + 'px;height:' + size + 'px;" src="https://www.google.com/s2/favicons?domain=wikipedia.org">wikipedia.org</td>' +
			'<td><img style="width:' + size + 'px;height:' + size + 'px;" src="https://www.google.com/s2/favicons?domain=owncloud.org">owncloud.org</td>' +
			'<td><img style="width:' + size + 'px;height:' + size + 'px;" src="https://www.google.com/s2/favicons?domain=nextcloud.com">nextcloud.com</td>';
	return tablerow;
}
function URLtoDomain(website) {

	var domain;
	// remove protocol (http, ftp, etc.) and get domain
	if (website.indexOf("://") > -1) {
		domain = website.split('/')[2];
	}
	else {
		domain = website.split('/')[0];
	}

	// remove port number
	domain = domain.split(':')[0];

	// remove unwanted wwww. for sorting purposes
	if (domain.substr(0, 4) == "www.") {
		domain = domain.substr(4, domain.length - 4);
	};

	return domain;
}
