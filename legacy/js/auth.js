$(document).ready(function() {

	// initialize authorization model
	var Auth = function(baseUrl) {
		this._baseUrl = baseUrl;
		this._auth = [];
	};

	Auth.prototype = {
		checkAuth: function(pass, type) {
			// this._baseUrl already ends with /auth, found in routes.php
			var result = 'error';
			var request = $.ajax({
				url: this._baseUrl,
				data: {'password' : pass, 'authtype' : type},
				method: 'POST'
			});
			
			request.done(function(msg) {

				// will be 'pass' or 'fail';
				result = msg;

				if (result == 'pass') {
					// create cookie on 'pass'
					setCookie();
					// has auth cookie, so go for it
					location.reload();
				} else if (result == 'fail') {
					if ($('#auth_pass').val() == $('#auth_pass').val().toUpperCase()) {
						$('#invalid_auth').text($('#invalid_auth').text() + t('passwords', ' Caps Lock might be on.'));	
					} else {
						$('#invalid_auth').text(t('passwords', 'This password is invalid. Please try again.'));
					}
					$('#invalid_auth').slideDown(200);
					setTimeout(function() {
						$('#invalid_auth').slideUp(500);
					}, 4500);
				}

			});
 
			request.fail(function( jqXHR, textStatus ) {
				alert( "Error while authenticating: " + textStatus );
			});
			
			return result;
		}
	};

	if (getCookie() == SHA512($('head').attr('data-user'))) {
		window.location = window.location; //OC.generateUrl('/apps/passwords/');
		return false;
	}

	$('#auth_btn').val(t('core', 'Continue'));
	$('#auth_pass').focus();
	
	$("#auth_form").on("submit", function() {
		if ($('#auth_pass').val() == '') {
			return false;
		}

		var auth = new Auth(OC.generateUrl('/apps/passwords/auth'));
		var auth_type =  $('#auth_pass').attr('auth-type');
		
		var authenticate = auth.checkAuth($('#auth_pass').val(), auth_type);

		return false;
	});

});

function setCookie() {
	// initialize settings
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
		getKey: function(key) {
			for (var k in this._settings)
			{
				if (k == key)
					return this._settings[k];
			}
		},
		getAll: function() {
			return this._settings;
		}
	};

	var settings = new Settings(OC.generateUrl('/apps/passwords/settings'));
	settings.load();
	var seconds = settings.getKey('auth_timer');
	var user = $('head').attr('data-user');
	var d = new Date();
	d.setTime(d.getTime() + (seconds * 1000));
	var expires = "expires="+ d.toUTCString();
	document.cookie = "oc_passwords_auth=" + SHA512(user) + "; " + expires;
}
function getCookie() {
	var name = "oc_passwords_auth=";
	var ca = document.cookie.split(';');
	for(var i = 0; i <ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') {
			c = c.substring(1);
		}
		if (c.indexOf(name) == 0) {
			return c.substring(name.length, c.length);
		}
	}
	return '';
}
