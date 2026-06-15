(function ($) {
	'use strict';

	$(document).ready(function () {
		var $fetchAccounts  = $('#owgr-fetch-accounts');
		var $fetchLocations = $('#owgr-fetch-locations');
		var $accountSelect  = $('#owgr-account-select');
		var $locationSelect = $('#owgr-location-select');
		var $accountWrap    = $('#owgr-accounts-wrap');
		var $locationWrap   = $('#owgr-locations-wrap');
		var $accountInput   = $('#owgr_account_id');
		var $locationInput  = $('#owgr_location_id');

		function logError(message) {
			alert(message || owgr_admin.strings.error);
		}

		$fetchAccounts.on('click', function () {
			$fetchAccounts.prop('disabled', true).text(owgr_admin.strings.loading);

			$.post(owgr_admin.ajax_url, {
				action: 'owgr_fetch_accounts',
				nonce: owgr_admin.nonce
			}, function (response) {
				$fetchAccounts.prop('disabled', false).text('Fetch Accounts');

				if (!response.success) {
					logError(response.data);
					return;
				}

				$accountSelect.empty().append('<option value="">' + 'Select an account' + '</option>');
				$.each(response.data, function (i, account) {
					$accountSelect.append('<option value="' + account.id + '">' + account.name + '</option>');
				});

				$accountWrap.show();
				$fetchLocations.prop('disabled', false);
			});
		});

		$accountSelect.on('change', function () {
			$accountInput.val($(this).val());
			$locationSelect.empty();
			$locationWrap.hide();
			$locationInput.val('');
		});

		$fetchLocations.on('click', function () {
			var accountId = $accountInput.val();
			if (!accountId) {
				alert('Select an account first.');
				return;
			}

			$fetchLocations.prop('disabled', true).text(owgr_admin.strings.loading);

			$.post(owgr_admin.ajax_url, {
				action: 'owgr_fetch_locations',
				nonce: owgr_admin.nonce,
				account_id: accountId
			}, function (response) {
				$fetchLocations.prop('disabled', false).text('Fetch Locations');

				if (!response.success) {
					logError(response.data);
					return;
				}

				$locationSelect.empty().append('<option value="">' + 'Select a location' + '</option>');
				$.each(response.data, function (i, location) {
					$locationSelect.append('<option value="' + location.id + '">' + location.name + '</option>');
				});

				$locationWrap.show();
			});
		});

		$locationSelect.on('change', function () {
			$locationInput.val($(this).val());
		});
	});
})(jQuery);
