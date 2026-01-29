/**
 * ZAO Bank Admin JavaScript
 */

(function($) {
	'use strict';

	$(document).ready(function() {

		// Initialize components
		ZAOBankAdmin.init();

	});

	const ZAOBankAdmin = {

		init: function() {
			this.bindEvents();
			this.initFlagReview();
		},

		bindEvents: function() {
			// Bind event handlers here
		},

		initFlagReview: function() {
			$('.zaobank-review-flag').on('click', function(e) {
				e.preventDefault();
				const flagId = $(this).data('flag-id');
				ZAOBankAdmin.showFlagReviewModal(flagId);
			});

			$('.zaobank-resolve-flag').on('click', function(e) {
				e.preventDefault();
				const flagId = $(this).data('flag-id');
				ZAOBankAdmin.resolveFlag(flagId);
			});
		},

		showFlagReviewModal: function(flagId) {
			// TODO: Implement modal for flag review
			// This would typically open a modal showing:
			// - The flagged content
			// - Reporter information
			// - Reason for flagging
			// - Options to resolve, escalate, or dismiss

			if (confirm('Review flag #' + flagId + '?\n\nOptions:\n- Resolve and restore\n- Keep hidden\n- Escalate to team')) {
				this.updateFlagStatus(flagId, 'under_review');
			}
		},

		resolveFlag: function(flagId) {
			if (!confirm('Mark this flag as resolved?')) {
				return;
			}

			this.updateFlagStatus(flagId, 'resolved');
		},

		updateFlagStatus: function(flagId, status) {
			$.ajax({
				url: zaobankAdmin.restUrl + 'flags/' + flagId,
				method: 'PUT',
				data: {
					status: status
				},
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', zaobankAdmin.restNonce);
				},
				success: function(response) {
					location.reload();
				},
				error: function(xhr) {
					alert('Error updating flag: ' + xhr.responseJSON.message);
				}
			});
		},

		// Utility function for API calls
		apiCall: function(endpoint, method, data, callback) {
			$.ajax({
				url: zaobankAdmin.restUrl + endpoint,
				method: method || 'GET',
				data: data || {},
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', zaobankAdmin.restNonce);
				},
				success: callback,
				error: function(xhr) {
					console.error('API Error:', xhr);
					if (xhr.responseJSON && xhr.responseJSON.message) {
						alert('Error: ' + xhr.responseJSON.message);
					}
				}
			});
		}
	};

	// Make globally accessible
	window.ZAOBankAdmin = ZAOBankAdmin;

})(jQuery);