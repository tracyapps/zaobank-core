/**
 * ZAO Bank Public JavaScript
 * Mobile-first, REST API powered
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		ZAOBank.init();
	});

	const ZAOBank = {

		// Current page state
		state: {
			currentPage: 1,
			totalPages: 1,
			loading: false,
			filters: {}
		},

		init: function() {
			this.bindEvents();
			this.initComponents();
			this.initBottomNav();
		},

		bindEvents: function() {
			// Job actions
			$(document).on('click', '.zaobank-claim-job', this.handleClaimJob.bind(this));
			$(document).on('click', '.zaobank-complete-job', this.handleCompleteJob.bind(this));

			// Flag content
			$(document).on('click', '.zaobank-flag-content', this.handleFlagContent.bind(this));

			// Filters
			$(document).on('change', '[data-filter="region"]', this.handleRegionFilter.bind(this));
			$(document).on('input', '[data-filter="search"]', this.debounce(this.handleSearch.bind(this), 300));

			// Tabs
			$(document).on('click', '.zaobank-tab', this.handleTabClick.bind(this));

			// Load more
			$(document).on('click', '[data-action="load-more"]', this.handleLoadMore.bind(this));

			// Forms
			$(document).on('submit', '#zaobank-job-form', this.handleJobFormSubmit.bind(this));
			$(document).on('submit', '#zaobank-profile-form', this.handleProfileFormSubmit.bind(this));
			$(document).on('submit', '[data-component="message-form"]', this.handleMessageSubmit.bind(this));

			// Appreciation
			$(document).on('click', '.zaobank-give-appreciation', this.handleGiveAppreciation.bind(this));
		},

		initComponents: function() {
			// Initialize based on page component
			const components = {
				'dashboard': this.initDashboard,
				'jobs-list': this.initJobsList,
				'job-single': this.initJobSingle,
				'job-form': this.initJobForm,
				'my-jobs': this.initMyJobs,
				'profile': this.initProfile,
				'profile-edit': this.initProfileEdit,
				'messages': this.initMessages,
				'conversation': this.initConversation,
				'exchanges': this.initExchanges,
				'appreciations': this.initAppreciations
			};

			$('[data-component]').each(function() {
				const component = $(this).data('component');
				if (components[component]) {
					components[component].call(ZAOBank, $(this));
				}
			});

			// Load regions for all region selects
			this.loadRegions();
		},

		initBottomNav: function() {
			// Active state is handled by PHP, but we can add any dynamic updates here
		},

		// =========================================================================
		// Dashboard
		// =========================================================================

		initDashboard: function($container) {
			this.loadUserBalance();
			this.loadUserStatistics();
			this.loadRecentActivity();
		},

		loadUserBalance: function() {
			const $display = $('.zaobank-balance-display');
			if (!$display.length || !zaobank.isLoggedIn) return;

			this.apiCall('me/balance', 'GET', {}, function(response) {
				const balance = response.balance;
				const className = balance >= 0 ? 'positive' : 'negative';
				const sign = balance >= 0 ? '+' : '';

				$display.attr('data-loading', 'false').html(`
					<div class="zaobank-balance ${className}">
						${sign}${balance.toFixed(1)} hours
					</div>
					<div class="zaobank-balance-detail">
						<div>Earned: ${response.hours_earned.toFixed(1)} hrs</div>
						<div>Spent: ${response.hours_spent.toFixed(1)} hrs</div>
					</div>
				`);
			});
		},

		loadUserStatistics: function() {
			const $stats = $('[data-component="stats"]');
			if (!$stats.length || !zaobank.isLoggedIn) return;

			this.apiCall('me/statistics', 'GET', {}, function(response) {
				$stats.attr('data-loading', 'false');
				$('[data-stat="jobs_requested"]').text(response.jobs_requested);
				$('[data-stat="jobs_completed"]').text(response.jobs_completed);
				$('[data-stat="appreciations_received"]').text(response.appreciations_received);
			});
		},

		loadRecentActivity: function() {
			const $activity = $('[data-component="activity"]');
			if (!$activity.length || !zaobank.isLoggedIn) return;

			// Load recent exchanges as activity
			this.apiCall('me/exchanges', 'GET', { per_page: 5 }, function(response) {
				$activity.attr('data-loading', 'false');

				if (!response.exchanges || response.exchanges.length === 0) {
					$activity.html('<p class="zaobank-loading-placeholder">No recent activity</p>');
					return;
				}

				let html = '';
				response.exchanges.forEach(function(exchange) {
					const isEarned = exchange.provider_user_id === zaobank.userId;
					html += `
						<div class="zaobank-activity-item">
							<div class="zaobank-activity-icon">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									${isEarned
										? '<line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>'
										: '<line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/>'
									}
								</svg>
							</div>
							<div class="zaobank-activity-content">
								<p class="zaobank-activity-text">
									${isEarned ? 'Earned' : 'Spent'} <strong>${exchange.hours} hrs</strong>
									${isEarned ? 'from' : 'to'} ${ZAOBank.escapeHtml(exchange.other_user_name || 'User')}
								</p>
								<span class="zaobank-activity-time">${ZAOBank.formatDate(exchange.created_at)}</span>
							</div>
						</div>
					`;
				});

				$activity.html(html);
			});
		},

		// =========================================================================
		// Jobs List
		// =========================================================================

		initJobsList: function($container) {
			this.state.filters = {
				region: '',
				search: ''
			};
			this.loadJobs();
		},

		loadJobs: function(append = false) {
			const $container = $('#zaobank-jobs-list');
			const $loadMore = $('.zaobank-load-more');
			const $empty = $('.zaobank-empty-state');

			if (this.state.loading) return;
			this.state.loading = true;

			if (!append) {
				this.state.currentPage = 1;
				$container.attr('data-loading', 'true');
			}

			const params = {
				page: this.state.currentPage,
				per_page: 12,
				status: 'available'
			};

			if (this.state.filters.region) {
				params.region = this.state.filters.region;
			}

			if (this.state.filters.search) {
				params.search = this.state.filters.search;
			}

			this.apiCall('jobs', 'GET', params, function(response) {
				ZAOBank.state.loading = false;
				ZAOBank.state.totalPages = response.pages || 1;

				$container.attr('data-loading', 'false');

				if (!response.jobs || response.jobs.length === 0) {
					if (!append) {
						$container.empty();
						$empty.show();
						$loadMore.hide();
					}
					return;
				}

				$empty.hide();

				const html = response.jobs.map(function(job) {
					return ZAOBank.renderJobCard(job);
				}).join('');

				if (append) {
					$container.append(html);
				} else {
					$container.html(html);
				}

				// Show/hide load more
				if (ZAOBank.state.currentPage < ZAOBank.state.totalPages) {
					$loadMore.show();
				} else {
					$loadMore.hide();
				}
			}, function() {
				ZAOBank.state.loading = false;
				$container.attr('data-loading', 'false');
			});
		},

		renderJobCard: function(job) {
			const template = $('#zaobank-job-card-template').html();
			if (!template) {
				return this.renderJobCardFallback(job);
			}

			const status = this.getJobStatus(job);
			const canClaim = zaobank.isLoggedIn && !job.provider_id && job.requester_id !== zaobank.userId;

			return this.renderTemplate(template, {
				id: job.id,
				title: this.escapeHtml(job.title),
				excerpt: this.escapeHtml(this.truncate(job.description, 100)),
				hours: job.hours,
				location: job.location ? this.escapeHtml(job.location) : '',
				status_class: status.class,
				status_label: status.label,
				requester_name: this.escapeHtml(job.requester_name),
				requester_avatar: job.requester_avatar || this.getDefaultAvatar(),
				can_claim: canClaim
			});
		},

		renderJobCardFallback: function(job) {
			const status = this.getJobStatus(job);
			const canClaim = zaobank.isLoggedIn && !job.provider_id && job.requester_id !== zaobank.userId;

			return `
				<article class="zaobank-card zaobank-job-card" data-job-id="${job.id}">
					<div class="zaobank-card-body">
						<div class="zaobank-job-header">
							<h3 class="zaobank-job-title">
								<a href="?job_id=${job.id}">${this.escapeHtml(job.title)}</a>
							</h3>
							<span class="zaobank-badge zaobank-badge-${status.class}">${status.label}</span>
						</div>
						<p class="zaobank-job-excerpt">${this.escapeHtml(this.truncate(job.description, 100))}</p>
						<div class="zaobank-job-meta">
							<span class="zaobank-job-hours">${job.hours} hours</span>
							${job.location ? `<span class="zaobank-job-location">${this.escapeHtml(job.location)}</span>` : ''}
						</div>
						<div class="zaobank-job-footer">
							<div class="zaobank-job-poster">
								<span>${this.escapeHtml(job.requester_name)}</span>
							</div>
							${canClaim ? `<button type="button" class="zaobank-btn zaobank-btn-primary zaobank-btn-sm zaobank-claim-job" data-job-id="${job.id}">Claim</button>` : ''}
						</div>
					</div>
				</article>
			`;
		},

		getJobStatus: function(job) {
			if (job.completed_at) {
				return { label: 'Completed', class: 'completed' };
			}
			if (job.provider_id) {
				return { label: 'In Progress', class: 'in-progress' };
			}
			return { label: 'Available', class: 'available' };
		},

		handleRegionFilter: function(e) {
			this.state.filters.region = $(e.currentTarget).val();
			this.loadJobs();
		},

		handleSearch: function(e) {
			this.state.filters.search = $(e.currentTarget).val();
			this.loadJobs();
		},

		handleLoadMore: function(e) {
			e.preventDefault();
			this.state.currentPage++;
			this.loadJobs(true);
		},

		// =========================================================================
		// Single Job
		// =========================================================================

		initJobSingle: function($container) {
			const jobId = $container.data('job-id');
			if (!jobId) return;

			this.loadJobDetails(jobId);
		},

		loadJobDetails: function(jobId) {
			const $content = $('.zaobank-job-content');

			this.apiCall('jobs/' + jobId, 'GET', {}, function(response) {
				$content.attr('data-loading', 'false');

				const job = response;
				const template = $('#zaobank-job-single-template').html();

				if (!template) {
					$content.html('<p>Error loading job details</p>');
					return;
				}

				const status = ZAOBank.getJobStatus(job);
				const canClaim = zaobank.isLoggedIn && !job.provider_id && job.requester_id !== zaobank.userId;
				const canComplete = zaobank.isLoggedIn && job.provider_id && !job.completed_at && job.requester_id === zaobank.userId;
				const canEdit = zaobank.isLoggedIn && job.requester_id === zaobank.userId && !job.provider_id;
				const canMessage = zaobank.isLoggedIn && job.requester_id !== zaobank.userId;

				const html = ZAOBank.renderTemplate(template, {
					id: job.id,
					title: ZAOBank.escapeHtml(job.title),
					description: ZAOBank.escapeHtml(job.description).replace(/\n/g, '<br>'),
					hours: job.hours,
					location: job.location ? ZAOBank.escapeHtml(job.location) : '',
					preferred_date: job.preferred_date || '',
					skills_required: job.skills_required ? job.skills_required.split(',').map(s => s.trim()) : [],
					status_class: status.class,
					status_label: status.label,
					requester_id: job.requester_id,
					requester_name: ZAOBank.escapeHtml(job.requester_name),
					requester_avatar: job.requester_avatar || ZAOBank.getDefaultAvatar(),
					requester_since: ZAOBank.formatDate(job.requester_registered, true),
					provider_id: job.provider_id || '',
					provider_name: job.provider_name ? ZAOBank.escapeHtml(job.provider_name) : '',
					provider_avatar: job.provider_avatar || ZAOBank.getDefaultAvatar(),
					can_claim: canClaim,
					can_complete: canComplete,
					can_edit: canEdit,
					can_message: canMessage
				});

				$content.html(html);
			}, function() {
				$content.attr('data-loading', 'false').html('<p class="zaobank-error">Error loading job details</p>');
			});
		},

		handleClaimJob: function(e) {
			e.preventDefault();
			const $button = $(e.currentTarget);
			const jobId = $button.data('job-id');

			if (!confirm('Claim this job? You will be committing to complete it.')) {
				return;
			}

			$button.prop('disabled', true).text('Claiming...');

			this.apiCall('jobs/' + jobId + '/claim', 'POST', {}, function(response) {
				ZAOBank.showToast('Job claimed successfully!', 'success');
				setTimeout(function() {
					location.reload();
				}, 1000);
			}, function() {
				$button.prop('disabled', false).text('Claim');
			});
		},

		handleCompleteJob: function(e) {
			e.preventDefault();
			const $button = $(e.currentTarget);
			const jobId = $button.data('job-id');

			if (!confirm('Mark this job as complete? This will record the time exchange.')) {
				return;
			}

			$button.prop('disabled', true).text('Processing...');

			this.apiCall('jobs/' + jobId + '/complete', 'POST', {}, function(response) {
				ZAOBank.showToast('Job completed! Exchange recorded.', 'success');
				setTimeout(function() {
					location.reload();
				}, 1000);
			}, function() {
				$button.prop('disabled', false).text('Mark Complete');
			});
		},

		// =========================================================================
		// Job Form
		// =========================================================================

		initJobForm: function($container) {
			const jobId = $container.data('job-id');

			if (jobId) {
				this.loadJobForEdit(jobId);
			}
		},

		loadJobForEdit: function(jobId) {
			const $form = $('#zaobank-job-form');

			this.apiCall('jobs/' + jobId, 'GET', {}, function(response) {
				$form.attr('data-loading', 'false');

				const job = response;
				$form.find('[name="title"]').val(job.title);
				$form.find('[name="description"]').val(job.description);
				$form.find('[name="hours"]').val(job.hours);
				$form.find('[name="location"]').val(job.location || '');
				$form.find('[name="preferred_date"]').val(job.preferred_date || '');
				$form.find('[name="flexible_timing"]').prop('checked', job.flexible_timing);
				$form.find('[name="skills_required"]').val(job.skills_required || '');

				if (job.region_id) {
					$form.find('[name="region"]').val(job.region_id);
				}
			}, function() {
				$form.attr('data-loading', 'false');
				ZAOBank.showToast('Error loading job data', 'error');
			});
		},

		handleJobFormSubmit: function(e) {
			e.preventDefault();
			const $form = $(e.currentTarget);
			const $button = $form.find('[type="submit"]');
			const jobId = $form.find('[name="job_id"]').val();
			const isEdit = jobId && jobId !== '0';

			// Validate
			if (!this.validateForm($form)) {
				return;
			}

			$button.prop('disabled', true);

			const data = {
				title: $form.find('[name="title"]').val(),
				description: $form.find('[name="description"]').val(),
				hours: parseFloat($form.find('[name="hours"]').val()),
				location: $form.find('[name="location"]').val(),
				preferred_date: $form.find('[name="preferred_date"]').val(),
				flexible_timing: $form.find('[name="flexible_timing"]').is(':checked'),
				skills_required: $form.find('[name="skills_required"]').val(),
				region: $form.find('[name="region"]').val()
			};

			const endpoint = isEdit ? 'jobs/' + jobId : 'jobs';
			const method = isEdit ? 'PUT' : 'POST';

			this.apiCall(endpoint, method, data, function(response) {
				ZAOBank.showToast(isEdit ? 'Job updated!' : 'Job posted!', 'success');
				setTimeout(function() {
					window.location.href = response.redirect_url || window.location.href.split('?')[0];
				}, 1000);
			}, function() {
				$button.prop('disabled', false);
			});
		},

		// =========================================================================
		// My Jobs
		// =========================================================================

		initMyJobs: function($container) {
			this.loadMyJobs('posted');
			this.loadMyJobs('claimed');
		},

		loadMyJobs: function(type) {
			const $list = $(`[data-list="${type}"]`);
			const $empty = $(`[data-empty="${type}"]`);
			const $count = $(`[data-count="${type}"]`);

			this.apiCall('jobs/mine', 'GET', { type: type }, function(response) {
				$list.attr('data-loading', 'false');

				const jobs = response.jobs || [];
				$count.text(jobs.length);

				if (jobs.length === 0) {
					$list.empty();
					$empty.show();
					return;
				}

				$empty.hide();

				const template = $('#zaobank-my-job-card-template').html();
				const html = jobs.map(function(job) {
					const status = ZAOBank.getJobStatus(job);
					const canComplete = job.provider_id && !job.completed_at && job.requester_id === zaobank.userId;
					const canEdit = job.requester_id === zaobank.userId && !job.provider_id;

					return ZAOBank.renderTemplate(template, {
						id: job.id,
						title: ZAOBank.escapeHtml(job.title),
						hours: job.hours,
						created_date: ZAOBank.formatDate(job.created_at),
						status_class: status.class,
						status_label: status.label,
						provider_name: job.provider_name ? ZAOBank.escapeHtml(job.provider_name) : '',
						provider_avatar: job.provider_avatar || ZAOBank.getDefaultAvatar(),
						can_complete: canComplete,
						can_edit: canEdit
					});
				}).join('');

				$list.html(html);
			}, function() {
				$list.attr('data-loading', 'false');
			});
		},

		// =========================================================================
		// Profile
		// =========================================================================

		initProfile: function($container) {
			const userId = $container.data('user-id');
			const isOwn = $container.data('own') === true || $container.data('own') === 'true';

			this.loadProfile(userId, isOwn);
		},

		loadProfile: function(userId, isOwn) {
			const $content = $('.zaobank-profile-content');
			const endpoint = isOwn ? 'me/profile' : 'users/' + userId;

			this.apiCall(endpoint, 'GET', {}, function(response) {
				$content.attr('data-loading', 'false');

				const profile = response.profile || response;
				const template = $('#zaobank-profile-template').html();

				if (!template) {
					$content.html('<p class="zaobank-error">Error loading profile</p>');
					return;
				}

				const html = ZAOBank.renderTemplate(template, {
					id: profile.id,
					name: ZAOBank.escapeHtml(profile.name),
					avatar_url: profile.avatar_url || ZAOBank.getDefaultAvatar(96),
					member_since: ZAOBank.formatDate(profile.registered, true),
					bio: profile.bio ? ZAOBank.escapeHtml(profile.bio) : '',
					skills: profile.skills ? ZAOBank.escapeHtml(profile.skills) : '',
					availability: profile.availability ? ZAOBank.escapeHtml(profile.availability) : '',
					primary_region: profile.primary_region || null,
					profile_tags: profile.profile_tags || [],
					is_own: isOwn
				});

				$content.html(html);

				// Load appreciations preview
				ZAOBank.loadAppreciationsPreview(profile.id);
			}, function() {
				$content.attr('data-loading', 'false').html('<p class="zaobank-error">Error loading profile</p>');
			});
		},

		loadAppreciationsPreview: function(userId) {
			const $preview = $('[data-component="appreciations-preview"]');
			if (!$preview.length) return;

			this.apiCall('users/' + userId + '/appreciations', 'GET', { per_page: 3 }, function(response) {
				if (!response.appreciations || response.appreciations.length === 0) {
					$preview.html('<p class="zaobank-loading-placeholder">No appreciations yet</p>');
					return;
				}

				// Show tag summary
				const tags = {};
				response.appreciations.forEach(function(app) {
					if (app.tag_slug) {
						tags[app.tag_slug] = (tags[app.tag_slug] || 0) + 1;
					}
				});

				let html = '<div class="zaobank-tags">';
				for (const tag in tags) {
					html += `<div class="zaobank-tag-badge"><span class="zaobank-tag">${ZAOBank.escapeHtml(tag)}</span><span class="zaobank-tag-count">x${tags[tag]}</span></div>`;
				}
				html += '</div>';

				$preview.html(html);
			});
		},

		// =========================================================================
		// Profile Edit
		// =========================================================================

		initProfileEdit: function($container) {
			this.loadProfileForEdit();
		},

		loadProfileForEdit: function() {
			const $form = $('#zaobank-profile-form');

			this.apiCall('me/profile', 'GET', {}, function(response) {
				$form.attr('data-loading', 'false');

				const profile = response.profile || response;
				$form.find('[name="user_bio"]').val(profile.bio || '');
				$form.find('[name="user_skills"]').val(profile.skills || '');
				$form.find('[name="user_availability"]').val(profile.availability || '');
				$form.find('[name="user_phone"]').val(profile.phone || '');

				if (profile.primary_region && profile.primary_region.id) {
					$form.find('[name="user_primary_region"]').val(profile.primary_region.id);
				}

				// Check profile tags
				if (profile.profile_tags && Array.isArray(profile.profile_tags)) {
					profile.profile_tags.forEach(function(tag) {
						$form.find(`[name="user_profile_tags[]"][value="${tag}"]`).prop('checked', true);
					});
				}

				// Check contact preferences
				if (profile.contact_preferences && Array.isArray(profile.contact_preferences)) {
					profile.contact_preferences.forEach(function(pref) {
						$form.find(`[name="user_contact_preferences[]"][value="${pref}"]`).prop('checked', true);
					});
				}
			}, function() {
				$form.attr('data-loading', 'false');
			});
		},

		handleProfileFormSubmit: function(e) {
			e.preventDefault();
			const $form = $(e.currentTarget);
			const $button = $form.find('[type="submit"]');

			$button.prop('disabled', true).text('Saving...');

			const data = {
				user_bio: $form.find('[name="user_bio"]').val(),
				user_skills: $form.find('[name="user_skills"]').val(),
				user_availability: $form.find('[name="user_availability"]').val(),
				user_phone: $form.find('[name="user_phone"]').val(),
				user_primary_region: $form.find('[name="user_primary_region"]').val(),
				user_profile_tags: $form.find('[name="user_profile_tags[]"]:checked').map(function() {
					return $(this).val();
				}).get(),
				user_contact_preferences: $form.find('[name="user_contact_preferences[]"]:checked').map(function() {
					return $(this).val();
				}).get()
			};

			this.apiCall('me/profile', 'PUT', data, function(response) {
				$button.prop('disabled', false).text('Save Changes');
				ZAOBank.showToast('Profile updated!', 'success');
			}, function() {
				$button.prop('disabled', false).text('Save Changes');
			});
		},

		// =========================================================================
		// Messages
		// =========================================================================

		initMessages: function($container) {
			this.loadConversations();
		},

		loadConversations: function() {
			const $list = $('.zaobank-conversations-list');
			const $empty = $('.zaobank-empty-state');

			this.apiCall('me/messages', 'GET', { grouped: true }, function(response) {
				$list.attr('data-loading', 'false');

				// Group messages by conversation partner
				const conversations = ZAOBank.groupMessagesIntoConversations(response.messages || []);

				if (conversations.length === 0) {
					$list.empty();
					$empty.show();
					return;
				}

				$empty.hide();

				const template = $('#zaobank-conversation-item-template').html();
				const html = conversations.map(function(conv) {
					return ZAOBank.renderTemplate(template, {
						other_user_id: conv.other_user_id,
						other_user_name: ZAOBank.escapeHtml(conv.other_user_name),
						other_user_avatar: conv.other_user_avatar || ZAOBank.getDefaultAvatar(),
						last_message_preview: ZAOBank.escapeHtml(ZAOBank.truncate(conv.last_message, 50)),
						last_message_time: ZAOBank.formatRelativeTime(conv.last_message_time),
						has_unread: conv.unread_count > 0,
						unread_count: conv.unread_count
					});
				}).join('');

				$list.html(html);
			}, function() {
				$list.attr('data-loading', 'false');
			});
		},

		groupMessagesIntoConversations: function(messages) {
			const convMap = {};

			messages.forEach(function(msg) {
				const otherId = msg.from_user_id === zaobank.userId ? msg.to_user_id : msg.from_user_id;
				const otherName = msg.from_user_id === zaobank.userId ? msg.to_user_name : msg.from_user_name;

				if (!convMap[otherId]) {
					convMap[otherId] = {
						other_user_id: otherId,
						other_user_name: otherName,
						other_user_avatar: null,
						last_message: msg.message,
						last_message_time: msg.created_at,
						unread_count: 0
					};
				}

				if (!msg.is_read && msg.to_user_id === zaobank.userId) {
					convMap[otherId].unread_count++;
				}
			});

			return Object.values(convMap);
		},

		// =========================================================================
		// Conversation
		// =========================================================================

		initConversation: function($container) {
			const userId = $container.data('user-id');
			if (!userId) return;

			this.loadConversationMessages(userId);
			this.initMessageComposer();
		},

		loadConversationMessages: function(userId) {
			const $list = $('.zaobank-messages-list');

			this.apiCall('me/messages', 'GET', { with_user: userId }, function(response) {
				$list.attr('data-loading', 'false');

				const messages = response.messages || [];

				if (messages.length === 0) {
					$list.html('<p class="zaobank-loading-placeholder" style="text-align: center;">Start a conversation!</p>');
					return;
				}

				const template = $('#zaobank-message-template').html();
				const html = messages.map(function(msg) {
					return ZAOBank.renderTemplate(template, {
						message: ZAOBank.escapeHtml(msg.message),
						time: ZAOBank.formatTime(msg.created_at),
						is_own: msg.from_user_id === zaobank.userId
					});
				}).join('');

				$list.html(html);

				// Scroll to bottom
				const $container = $('.zaobank-messages-container');
				$container.scrollTop($container[0].scrollHeight);

				// Mark as read
				messages.forEach(function(msg) {
					if (!msg.is_read && msg.to_user_id === zaobank.userId) {
						ZAOBank.apiCall('messages/' + msg.id + '/read', 'POST', {});
					}
				});
			}, function() {
				$list.attr('data-loading', 'false');
			});
		},

		initMessageComposer: function() {
			const $textarea = $('.zaobank-composer-input');

			// Auto-resize textarea
			$textarea.on('input', function() {
				this.style.height = 'auto';
				this.style.height = Math.min(this.scrollHeight, 120) + 'px';
			});
		},

		handleMessageSubmit: function(e) {
			e.preventDefault();
			const $form = $(e.currentTarget);
			const $input = $form.find('[name="message"]');
			const $button = $form.find('[type="submit"]');
			const message = $input.val().trim();
			const userId = $('[data-component="conversation"]').data('user-id');

			if (!message) return;

			$button.prop('disabled', true);

			this.apiCall('messages', 'POST', {
				to_user_id: userId,
				message: message
			}, function(response) {
				$input.val('').trigger('input');
				$button.prop('disabled', false);

				// Add message to list
				const template = $('#zaobank-message-template').html();
				const html = ZAOBank.renderTemplate(template, {
					message: ZAOBank.escapeHtml(message),
					time: 'Just now',
					is_own: true
				});

				$('.zaobank-messages-list').append(html);

				// Scroll to bottom
				const $container = $('.zaobank-messages-container');
				$container.scrollTop($container[0].scrollHeight);
			}, function() {
				$button.prop('disabled', false);
			});
		},

		// =========================================================================
		// Exchanges
		// =========================================================================

		initExchanges: function($container) {
			this.loadUserBalance();
			this.loadExchanges();
		},

		loadExchanges: function(filter = 'all', append = false) {
			const $list = $('.zaobank-exchanges-list');
			const $loadMore = $('.zaobank-load-more');
			const $empty = $('.zaobank-empty-state');

			if (!append) {
				this.state.currentPage = 1;
				$list.attr('data-loading', 'true');
			}

			this.apiCall('me/exchanges', 'GET', {
				page: this.state.currentPage,
				per_page: 20,
				filter: filter
			}, function(response) {
				$list.attr('data-loading', 'false');
				ZAOBank.state.totalPages = response.pages || 1;

				const exchanges = response.exchanges || [];

				if (exchanges.length === 0 && !append) {
					$list.empty();
					$empty.show();
					$loadMore.hide();
					return;
				}

				$empty.hide();

				const template = $('#zaobank-exchange-item-template').html();
				const html = exchanges.map(function(exchange) {
					const isEarned = exchange.provider_user_id === zaobank.userId;

					return ZAOBank.renderTemplate(template, {
						id: exchange.id,
						job_id: exchange.job_id,
						job_title: ZAOBank.escapeHtml(exchange.job_title || 'Job'),
						hours: exchange.hours,
						is_earned: isEarned,
						type: isEarned ? 'earned' : 'spent',
						other_user_id: isEarned ? exchange.requester_user_id : exchange.provider_user_id,
						other_user_name: ZAOBank.escapeHtml(exchange.other_user_name || 'User'),
						other_user_avatar: ZAOBank.getDefaultAvatar(),
						date: ZAOBank.formatDate(exchange.created_at),
						has_appreciation: exchange.has_appreciation
					});
				}).join('');

				if (append) {
					$list.append(html);
				} else {
					$list.html(html);
				}

				if (ZAOBank.state.currentPage < ZAOBank.state.totalPages) {
					$loadMore.show();
				} else {
					$loadMore.hide();
				}
			}, function() {
				$list.attr('data-loading', 'false');
			});
		},

		// =========================================================================
		// Appreciations
		// =========================================================================

		initAppreciations: function($container) {
			const userId = $container.data('user-id');
			this.loadAppreciations(userId, 'received');
		},

		loadAppreciations: function(userId, type) {
			const $list = $(`[data-list="${type}"]`);
			const $empty = $(`[data-empty="${type}"]`);
			const $count = $(`[data-count="${type}"]`);
			const $tagsSummary = $('.zaobank-appreciation-tags-summary');

			const endpoint = type === 'received' ? `users/${userId}/appreciations` : 'me/appreciations/given';

			this.apiCall(endpoint, 'GET', {}, function(response) {
				$list.attr('data-loading', 'false');
				$tagsSummary.attr('data-loading', 'false');

				const appreciations = response.appreciations || [];
				if ($count.length) {
					$count.text(appreciations.length);
				}

				if (appreciations.length === 0) {
					$list.empty();
					$empty.show();
					$tagsSummary.html('<p class="zaobank-loading-placeholder">No tags yet</p>');
					return;
				}

				$empty.hide();

				// Build tags summary
				const tags = {};
				appreciations.forEach(function(app) {
					if (app.tag_slug) {
						tags[app.tag_slug] = (tags[app.tag_slug] || 0) + 1;
					}
				});

				let tagsHtml = '';
				for (const tag in tags) {
					tagsHtml += `<div class="zaobank-tag-badge"><span class="zaobank-tag">${ZAOBank.escapeHtml(tag)}</span><span class="zaobank-tag-count">x${tags[tag]}</span></div>`;
				}
				$tagsSummary.html(tagsHtml || '<p class="zaobank-loading-placeholder">No tags</p>');

				// Build list
				const template = $('#zaobank-appreciation-item-template').html();
				const html = appreciations.map(function(app) {
					return ZAOBank.renderTemplate(template, {
						from_user_id: app.from_user_id,
						from_user_name: ZAOBank.escapeHtml(app.from_user_name || 'User'),
						from_user_avatar: ZAOBank.getDefaultAvatar(),
						tag_slug: app.tag_slug || '',
						tag_label: app.tag_slug ? ZAOBank.escapeHtml(app.tag_slug) : '',
						message: app.message ? ZAOBank.escapeHtml(app.message) : '',
						date: ZAOBank.formatDate(app.created_at),
						job_id: app.job_id || '',
						job_title: app.job_title ? ZAOBank.escapeHtml(app.job_title) : ''
					});
				}).join('');

				$list.html(html);
			}, function() {
				$list.attr('data-loading', 'false');
				$tagsSummary.attr('data-loading', 'false');
			});
		},

		handleGiveAppreciation: function(e) {
			e.preventDefault();
			const $button = $(e.currentTarget);
			const exchangeId = $button.data('exchange-id');
			const toUserId = $button.data('user-id');

			// Simple prompt for now - could be enhanced with a modal
			const tags = ['helpful', 'reliable', 'kind', 'skilled', 'punctual'];
			const tag = prompt('Choose a tag for your appreciation:\n\n' + tags.join(', '));

			if (!tag) return;

			const message = prompt('Add an optional message (or leave blank):') || '';

			$button.prop('disabled', true).text('Sending...');

			this.apiCall('appreciations', 'POST', {
				exchange_id: exchangeId,
				to_user_id: toUserId,
				tag_slug: tag.toLowerCase().trim(),
				message: message,
				is_public: true
			}, function(response) {
				ZAOBank.showToast('Appreciation sent!', 'success');
				$button.closest('.zaobank-exchange-actions').remove();
			}, function() {
				$button.prop('disabled', false).text('Give Appreciation');
			});
		},

		// =========================================================================
		// Flag Content
		// =========================================================================

		handleFlagContent: function(e) {
			e.preventDefault();
			const $button = $(e.currentTarget);
			const itemType = $button.data('item-type');
			const itemId = $button.data('item-id');

			const reasons = ['spam', 'inappropriate', 'harassment', 'other'];
			const reason = prompt('Reason for flagging:\n\n' + reasons.join(', '));

			if (!reason) return;

			const note = prompt('Additional context (optional):') || '';

			this.apiCall('flags', 'POST', {
				flagged_item_type: itemType,
				flagged_item_id: itemId,
				reason_slug: reason.toLowerCase().trim(),
				context_note: note
			}, function(response) {
				ZAOBank.showToast('Report submitted. Thank you.', 'success');
			});
		},

		// =========================================================================
		// Tabs
		// =========================================================================

		handleTabClick: function(e) {
			const $tab = $(e.currentTarget);
			const tabId = $tab.data('tab') || $tab.data('filter');
			const $container = $tab.closest('.zaobank-container');

			// Update active tab
			$tab.siblings('.zaobank-tab').removeClass('active');
			$tab.addClass('active');

			// Update panels
			$container.find('.zaobank-tab-panel').removeClass('active');
			$container.find(`[data-panel="${tabId}"]`).addClass('active');

			// Handle filter tabs (exchanges)
			if ($tab.data('filter')) {
				this.loadExchanges($tab.data('filter'));
			}

			// Handle appreciations tabs
			if (tabId === 'given' || tabId === 'received') {
				const userId = $container.data('user-id');
				this.loadAppreciations(userId, tabId);
			}
		},

		// =========================================================================
		// Regions
		// =========================================================================

		loadRegions: function() {
			const $selects = $('[name="region"], [name="user_primary_region"], [data-filter="region"]');
			if (!$selects.length) return;

			this.apiCall('regions', 'GET', {}, function(response) {
				const regions = response.regions || response || [];

				$selects.each(function() {
					const $select = $(this);
					const currentVal = $select.val();

					ZAOBank.buildRegionOptions($select, regions);

					if (currentVal) {
						$select.val(currentVal);
					}
				});
			});
		},

		buildRegionOptions: function($select, regions, prefix = '') {
			regions.forEach(function(region) {
				$select.append(
					$('<option>', {
						value: region.term_id || region.id,
						text: prefix + region.name
					})
				);

				if (region.children && region.children.length) {
					ZAOBank.buildRegionOptions($select, region.children, prefix + '— ');
				}
			});
		},

		// =========================================================================
		// Form Validation
		// =========================================================================

		validateForm: function($form) {
			let isValid = true;

			$form.find('[required]').each(function() {
				const $field = $(this);
				const value = $field.val().trim();

				if (!value) {
					isValid = false;
					$field.addClass('zaobank-field-error');
					ZAOBank.showToast('Please fill in all required fields', 'error');
				} else {
					$field.removeClass('zaobank-field-error');
				}
			});

			return isValid;
		},

		// =========================================================================
		// Toast Notifications
		// =========================================================================

		showToast: function(message, type = 'info') {
			let $container = $('.zaobank-toast-container');

			if (!$container.length) {
				$container = $('<div class="zaobank-toast-container"></div>');
				$('body').append($container);
			}

			const $toast = $(`
				<div class="zaobank-toast ${type}">
					<p class="zaobank-toast-message">${this.escapeHtml(message)}</p>
					<button type="button" class="zaobank-toast-close" aria-label="Close">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<line x1="18" y1="6" x2="6" y2="18"/>
							<line x1="6" y1="6" x2="18" y2="18"/>
						</svg>
					</button>
				</div>
			`);

			$container.append($toast);

			// Auto-remove after 5 seconds
			setTimeout(function() {
				$toast.fadeOut(300, function() {
					$(this).remove();
				});
			}, 5000);

			// Close button
			$toast.find('.zaobank-toast-close').on('click', function() {
				$toast.fadeOut(300, function() {
					$(this).remove();
				});
			});
		},

		// =========================================================================
		// API Helper
		// =========================================================================

		apiCall: function(endpoint, method, data, successCallback, errorCallback) {
			$.ajax({
				url: zaobank.restUrl + endpoint,
				method: method || 'GET',
				data: method === 'GET' ? data : JSON.stringify(data),
				contentType: method === 'GET' ? undefined : 'application/json',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', zaobank.restNonce);
				},
				success: function(response) {
					if (successCallback) {
						successCallback(response);
					}
				},
				error: function(xhr) {
					console.error('API Error:', xhr);
					if (xhr.responseJSON && xhr.responseJSON.message) {
						ZAOBank.showToast('Error: ' + xhr.responseJSON.message, 'error');
					} else {
						ZAOBank.showToast('An error occurred. Please try again.', 'error');
					}
					if (errorCallback) {
						errorCallback(xhr);
					}
				}
			});
		},

		// =========================================================================
		// Template Rendering
		// =========================================================================

		renderTemplate: function(template, data) {
			let html = template;

			// Handle conditionals {{#if key}}...{{/if}}
			html = html.replace(/\{\{#if\s+(\w+(?:\.\w+)*)\}\}([\s\S]*?)\{\{\/if\}\}/g, function(match, key, content) {
				const value = ZAOBank.getNestedValue(data, key);
				if (value && (!Array.isArray(value) || value.length > 0)) {
					return content;
				}
				return '';
			});

			// Handle unless {{#unless key}}...{{/unless}}
			html = html.replace(/\{\{#unless\s+(\w+(?:\.\w+)*)\}\}([\s\S]*?)\{\{\/unless\}\}/g, function(match, key, content) {
				const value = ZAOBank.getNestedValue(data, key);
				if (!value) {
					return content;
				}
				return '';
			});

			// Handle each {{#each key}}...{{/each}}
			html = html.replace(/\{\{#each\s+(\w+(?:\.\w+)*)\}\}([\s\S]*?)\{\{\/each\}\}/g, function(match, key, content) {
				const arr = ZAOBank.getNestedValue(data, key);
				if (!Array.isArray(arr)) return '';
				return arr.map(function(item) {
					return content.replace(/\{\{this\}\}/g, ZAOBank.escapeHtml(item));
				}).join('');
			});

			// Handle simple replacements {{key}}
			html = html.replace(/\{\{(\w+(?:\.\w+)*)\}\}/g, function(match, key) {
				const value = ZAOBank.getNestedValue(data, key);
				return value !== undefined && value !== null ? value : '';
			});

			return html;
		},

		getNestedValue: function(obj, key) {
			return key.split('.').reduce(function(o, k) {
				return o && o[k] !== undefined ? o[k] : null;
			}, obj);
		},

		// =========================================================================
		// Utility Functions
		// =========================================================================

		escapeHtml: function(text) {
			if (!text) return '';
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		},

		truncate: function(text, length) {
			if (!text) return '';
			if (text.length <= length) return text;
			return text.substring(0, length) + '...';
		},

		formatDate: function(dateString, yearOnly = false) {
			if (!dateString) return '';
			const date = new Date(dateString);
			if (yearOnly) {
				return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short' });
			}
			return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
		},

		formatTime: function(dateString) {
			if (!dateString) return '';
			const date = new Date(dateString);
			return date.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
		},

		formatRelativeTime: function(dateString) {
			if (!dateString) return '';
			const date = new Date(dateString);
			const now = new Date();
			const diff = Math.floor((now - date) / 1000);

			if (diff < 60) return 'Just now';
			if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
			if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
			if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
			return this.formatDate(dateString);
		},

		getDefaultAvatar: function(size = 48) {
			return `https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&s=${size}`;
		},

		debounce: function(func, wait) {
			let timeout;
			return function executedFunction() {
				const context = this;
				const args = arguments;
				clearTimeout(timeout);
				timeout = setTimeout(function() {
					func.apply(context, args);
				}, wait);
			};
		}
	};

	// Make globally accessible
	window.ZAOBank = ZAOBank;

})(jQuery);
