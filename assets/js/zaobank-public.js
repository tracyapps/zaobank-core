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
			filters: {},
			community: {
				page: 1,
				totalPages: 1,
				filters: {},
				savedIds: [],
				savedLoaded: false,
				addressTab: 'worked-with'
			},
			moderation: {
				usersPage: 1,
				usersTotalPages: 1,
				flagsPage: 1,
				flagsTotalPages: 1,
				usersFilters: { q: '', role: '' },
				flagsFilters: { status: 'open', type: '' }
			},
			reporting: {
				itemType: '',
				itemId: 0,
				flaggedUserId: 0,
				triggerSelector: ''
			}
		},

		init: function() {
			this.ensureFlagDrawer();
			this.bindEvents();
			this.initComponents();
			this.initBottomNav();
		},

		bindEvents: function() {
			// Job actions
			$(document).on('click', '.zaobank-claim-job', this.handleClaimJob.bind(this));
			$(document).on('click', '.zaobank-complete-job', this.handleCompleteJob.bind(this));
			$(document).on('click', '.zaobank-release-job', this.handleReleaseJob.bind(this));
			$(document).on('click', '.zaobank-delete-job', this.handleDeleteJob.bind(this));

			// Flag content
			$(document).on('click', '.zaobank-flag-content', this.handleFlagContent.bind(this));
			$(document).on('click', '[data-action="flag-drawer-close"], [data-action="flag-drawer-cancel"]', this.handleCloseFlagDrawer.bind(this));
			$(document).on('click', '.zaobank-report-drawer-overlay', this.handleCloseFlagDrawer.bind(this));
			$(document).on('submit', '#zaobank-flag-report-form', this.handleFlagReportSubmit.bind(this));
			$(document).on('keydown', this.handleGlobalKeydown.bind(this));

			// Filters
			$(document).on('change', '[data-filter="region"]', this.handleRegionFilter.bind(this));
			$(document).on('input', '[data-filter="search"]', this.debounce(this.handleSearch.bind(this), 300));
			$(document).on('change', '[data-filter="sort"]', this.handleJobsSortChange.bind(this));
			$(document).on('change', '[data-filter="per_page"]', this.handleJobsPerPageChange.bind(this));

			// Community filters
			$(document).on('input', '[data-community-filter="search"]', this.debounce(this.handleCommunityFilterChange.bind(this), 300));
			$(document).on('input', '[data-community-filter="skill"]', this.debounce(this.handleCommunityFilterChange.bind(this), 300));
			$(document).on('change', '[data-community-filter="region"], [data-community-filter="sort"], [data-community-filter="per_page"]', this.handleCommunityFilterChange.bind(this));
			$(document).on('change', '[name="community_skill_tags[]"]', this.handleCommunityFilterChange.bind(this));
			$(document).on('click', '[data-action="community-reset"]', this.handleCommunityReset.bind(this));

			// Tabs
			$(document).on('click', '.zaobank-tab', this.handleTabClick.bind(this));
			$(document).on('click', '.zaobank-address-tab', this.handleAddressBookTab.bind(this));

			// Load more
			$(document).on('click', '[data-action="load-more"]', this.handleLoadMore.bind(this));
			$(document).on('click', '[data-action="community-load-more"]', this.handleCommunityLoadMore.bind(this));

			// Forms
			$(document).on('submit', '#zaobank-job-form', this.handleJobFormSubmit.bind(this));
			$(document).on('submit', '#zaobank-profile-form', this.handleProfileFormSubmit.bind(this));
			$(document).on('submit', '[data-component="message-form"]', this.handleMessageSubmit.bind(this));

			// Appreciation
			$(document).on('click', '.zaobank-give-appreciation', this.handleGiveAppreciation.bind(this));
			$(document).on('click', '.zaobank-cancel-appreciation', this.handleCancelAppreciation.bind(this));
			$(document).on('click', '.zaobank-submit-appreciation', this.handleSubmitAppreciation.bind(this));
			$(document).on('click', '.zaobank-save-note', this.handleSaveNote.bind(this));
			$(document).on('click', '.zaobank-request-skill', this.handleOpenCommunityRequest.bind(this));
			$(document).on('click', '.zaobank-cancel-request', this.handleCancelCommunityRequest.bind(this));
			$(document).on('click', '.zaobank-submit-request', this.handleSubmitCommunityRequest.bind(this));
			$(document).on('click', '.zaobank-save-profile', this.handleSaveProfile.bind(this));
			$(document).on('click', '.zaobank-remove-saved', this.handleRemoveSavedProfile.bind(this));

			// Message actions
			$(document).on('click', '.zaobank-mark-read', this.handleMarkConversationRead.bind(this));
			$(document).on('click', '.zaobank-archive-conversation', this.handleArchiveConversation.bind(this));
			$(document).on('input', '[data-action="message-user-search"]', this.debounce(this.handleMessageUserSearch.bind(this), 250));

			// Moderation
			$(document).on('input', '[data-mod-filter="search"]', this.debounce(this.handleModSearchChange.bind(this), 300));
			$(document).on('change', '[data-mod-filter="role"]', this.handleModRoleFilterChange.bind(this));
			$(document).on('change', '[data-mod-filter="flag-status"]', this.handleModFlagStatusChange.bind(this));
			$(document).on('change', '[data-mod-filter="flag-type"]', this.handleModFlagTypeChange.bind(this));
			$(document).on('change', '.zaobank-mod-role-select', this.handleModRoleChange.bind(this));
			$(document).on('click', '[data-action="mod-flag-review"]', this.handleModFlagReview.bind(this));
			$(document).on('click', '[data-action="mod-flag-delete"]', this.handleModFlagDelete.bind(this));
			$(document).on('click', '[data-action="mod-flag-resolve"]', this.handleModFlagResolveOpen.bind(this));
			$(document).on('click', '[data-action="mod-flag-confirm-resolve"]', this.handleModFlagResolveConfirm.bind(this));
			$(document).on('click', '[data-action="mod-flag-cancel-resolve"]', this.handleModFlagResolveCancel.bind(this));
			$(document).on('click', '[data-action="mod-flag-restore"]', this.handleModFlagRestore.bind(this));
			$(document).on('click', '.zaobank-save-mod-settings', this.handleSaveModSettings.bind(this));
			$(document).on('click', '[data-action="mod-load-more-users"]', this.handleModLoadMoreUsers.bind(this));
			$(document).on('click', '[data-action="mod-load-more-flags"]', this.handleModLoadMoreFlags.bind(this));

		},

		initComponents: function() {
			// Initialize based on page component
			const components = {
				'dashboard': this.initDashboard,
				'jobs-list': this.initJobsList,
				'job-single': this.initJobSingle,
				'job-form': this.initJobForm,
				'my-jobs': this.initMyJobs,
				'community': this.initCommunity,
				'profile': this.initProfile,
				'profile-edit': this.initProfileEdit,
				'messages': this.initMessages,
				'conversation': this.initConversation,
				'exchanges': this.initExchanges,
				'appreciations': this.initAppreciations,
				'moderation': this.initModeration
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
					const currentUserId = Number(zaobank.userId || 0);
					const providerId = Number(exchange.provider_id || exchange.provider_user_id || 0);
					const requesterId = Number(exchange.requester_id || exchange.requester_user_id || 0);
					const isEarned = providerId === currentUserId;
					const otherUserName = isEarned
						? (exchange.requester_name || exchange.other_user_name || 'User')
						: (exchange.provider_name || exchange.other_user_name || 'User');
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
									${isEarned ? 'from' : 'to'} ${ZAOBank.escapeHtml(otherUserName)}
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
				search: '',
				job_types: [],
				sort: 'recent',
				per_page: 12
			};
			this.loadJobTypes();
			this.initFilterPanel();
			this.loadJobs();
		},

		loadJobTypes: function() {
			var self = this;
			this.apiCall('job-types', 'GET', {}, function(response) {
				var types = response.job_types || [];

				// Populate filter panel checkboxes
				var $list = $('#zaobank-job-type-list');
				if ($list.length && types.length) {
					var html = types.map(function(type) {
						return '<label class="zaobank-checkbox-label">' +
							'<input type="checkbox" name="job_type_filter" value="' + type.id + '">' +
							'<span>' + self.escapeHtml(type.name) + '</span>' +
							'</label>';
					}).join('');
					$list.html(html);
				}

				// Populate job form checkboxes
				var $formList = $('#zaobank-job-type-form-list');
				if ($formList.length && types.length) {
					var formHtml = types.map(function(type) {
						return '<label class="zaobank-checkbox-label">' +
							'<input type="checkbox" name="job_types[]" value="' + type.id + '">' +
							'<span>' + self.escapeHtml(type.name) + '</span>' +
							'</label>';
					}).join('');
					$formList.html(formHtml);
				}
			});
		},

		initFilterPanel: function() {
			var self = this;

			$(document).on('click', '#zaobank-filter-toggle', function(e) {
				e.preventDefault();
				$('.zaobank-filter-panel').addClass('open');
				$('.zaobank-filter-panel-overlay').addClass('open');
			});

			$(document).on('click', '.zaobank-filter-panel-close, .zaobank-filter-panel-overlay', function(e) {
				e.preventDefault();
				$('.zaobank-filter-panel').removeClass('open');
				$('.zaobank-filter-panel-overlay').removeClass('open');
			});

			$(document).on('change', '[name="job_type_filter"]', function() {
				self.state.filters.job_types = $('[name="job_type_filter"]:checked').map(function() {
					return parseInt($(this).val(), 10);
				}).get();
				self.loadJobs();
			});
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
				per_page: this.state.filters.per_page || 12,
				status: 'available',
				sort: this.state.filters.sort || 'recent'
			};

			if (this.state.filters.region) {
				params.region = this.state.filters.region;
			}

			if (this.state.filters.search) {
				params.search = this.state.filters.search;
			}

			if (this.state.filters.job_types && this.state.filters.job_types.length) {
				params.job_types = this.state.filters.job_types;
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
						$('[data-role="jobs-summary"]').text('Showing 0-0 of 0');
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

				const total = response.total || 0;
				const perPage = params.per_page;
				const start = total === 0 ? 0 : ((ZAOBank.state.currentPage - 1) * perPage + 1);
				const end = total === 0 ? 0 : Math.min(total, ZAOBank.state.currentPage * perPage);
				$('[data-role="jobs-summary"]').text(`Showing ${start}-${end} of ${total}`);

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
			const currentUserId = Number(zaobank.userId || 0);
			const requesterId = Number(job.requester_id || 0);
			const providerId = Number(job.provider_id || 0);
			const canClaim = zaobank.isLoggedIn && zaobank.hasMemberAccess && !providerId && requesterId !== currentUserId;
			const isFlagged = !!job.is_flagged || (job.visibility && String(job.visibility).toLowerCase() !== 'public');

			return this.renderTemplate(template, {
				id: job.id,
				title: this.escapeHtml(job.title),
				excerpt: this.escapeHtml(this.truncate(job.description, 100)),
				hours: job.hours,
				location: job.location ? this.escapeHtml(job.location) : '',
				virtual_ok: !!job.virtual_ok,
				status_class: status.class,
				status_label: status.label,
				requester_id: job.requester_id,
				requester_name: this.escapeHtml(job.requester_name),
				requester_pronouns: job.requester_pronouns ? this.escapeHtml(job.requester_pronouns) : '',
				requester_avatar: job.requester_avatar || this.getDefaultAvatar(),
				job_types: job.job_types || [],
				can_claim: canClaim,
				is_flagged: isFlagged,
				flagged_class: isFlagged ? 'zaobank-flagged-content' : ''
			});
		},

		renderJobCardFallback: function(job) {
			const status = this.getJobStatus(job);
			const currentUserId = Number(zaobank.userId || 0);
			const requesterId = Number(job.requester_id || 0);
			const providerId = Number(job.provider_id || 0);
			const canClaim = zaobank.isLoggedIn && zaobank.hasMemberAccess && !providerId && requesterId !== currentUserId;
			const isFlagged = !!job.is_flagged || (job.visibility && String(job.visibility).toLowerCase() !== 'public');
			const flaggedClass = isFlagged ? 'zaobank-flagged-content' : '';

			return `
				<article class="zaobank-card zaobank-job-card ${flaggedClass}" data-job-id="${job.id}">
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
							${job.virtual_ok ? `<span class="zaobank-job-virtual">Virtual ok</span>` : ''}
						</div>
						<div class="zaobank-job-footer">
							<div class="zaobank-job-poster">
								<span>${this.escapeHtml(job.requester_name)}</span>
								${job.requester_pronouns ? `<span class="zaobank-name-pronouns">(${this.escapeHtml(job.requester_pronouns)})</span>` : ''}
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

		handleJobsSortChange: function(e) {
			this.state.filters.sort = $(e.currentTarget).val();
			this.loadJobs();
		},

		handleJobsPerPageChange: function(e) {
			const value = parseInt($(e.currentTarget).val(), 10) || 12;
			this.state.filters.per_page = value;
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
		// Community
		// =========================================================================

		initCommunity: function($container) {
			const view = $container.data('view') || 'community';
			this.state.community.page = 1;
			this.state.community.totalPages = 1;
			this.state.community.addressTab = 'worked-with';
			this.initCommunityFilterPanel();

			if (zaobank.hasMemberAccess) {
				this.loadSavedProfiles(true);
			}

			const presetTag = ZAOBank.getQueryParam('skill_tag');
			if (presetTag) {
				const $checkbox = $(`[name="community_skill_tags[]"][value="${presetTag}"]`);
				if ($checkbox.length) {
					$checkbox.prop('checked', true);
				}
			}

			if (view === 'address-book') {
				this.setAddressBookTab('worked-with');
				this.loadWorkedWith();
			} else {
				this.loadCommunity(false);
			}
		},

		initCommunityFilterPanel: function() {
			$(document).on('click', '#zaobank-community-filter-toggle', function(e) {
				e.preventDefault();
				$('.zaobank-community-filter-panel').addClass('open');
				$('.zaobank-community-filter-overlay').addClass('open');
			});

			$(document).on('click', '.zaobank-community-filter-close, .zaobank-community-filter-overlay', function(e) {
				e.preventDefault();
				$('.zaobank-community-filter-panel').removeClass('open');
				$('.zaobank-community-filter-overlay').removeClass('open');
			});
		},

		handleCommunityFilterChange: function() {
			this.state.community.page = 1;
			this.loadCommunity(false);
		},

		handleCommunityReset: function(e) {
			e.preventDefault();
			$('[name="community_skill_tags[]"]').prop('checked', false);
			this.state.community.page = 1;
			this.loadCommunity(false);

			const url = new URL(window.location.href);
			url.searchParams.delete('skill_tag');
			window.history.replaceState({}, '', url.toString());
		},

		handleCommunityLoadMore: function(e) {
			e.preventDefault();
			this.state.community.page++;
			this.loadCommunity(true);
		},

		getCommunityFilters: function() {
			const skillTags = $('[name="community_skill_tags[]"]:checked').map(function() {
				return $(this).val();
			}).get();

			const regionValue = $('[data-community-filter="region"]').val();
			const regionId = regionValue ? parseInt(regionValue, 10) : '';

			return {
				search: $('[data-community-filter="search"]').val() || '',
				skill: $('[data-community-filter="skill"]').val() || '',
				region: Number.isFinite(regionId) ? regionId : '',
				sort: $('[data-community-filter="sort"]').val() || 'recent',
				per_page: parseInt($('[data-community-filter="per_page"]').val(), 10) || 12,
				skill_tags: skillTags
			};
		},

		loadCommunity: function(append = false) {
			const $list = $('.zaobank-community-list');
			const $loadMore = $('.zaobank-community-load-more');
			const $empty = $('[data-empty="community"]');

			if (!append) {
				$list.attr('data-loading', 'true');
			}

			const filters = this.getCommunityFilters();
			this.state.community.filters = filters;
			this.updateCommunityResetButton(filters.skill_tags);

			const params = {
				page: this.state.community.page,
				per_page: filters.per_page,
				q: filters.search,
				skill: filters.skill,
				sort: filters.sort,
				skill_tags: filters.skill_tags
			};

			if (filters.region) {
				params.region = filters.region;
			}

			this.apiCall('community/users', 'GET', params, function(response) {
				$list.attr('data-loading', 'false');
				ZAOBank.state.community.totalPages = response.pages || 1;

				const users = response.users || [];

				if (users.length === 0 && !append) {
					$list.empty();
					$empty.show();
					$loadMore.hide();
					$('[data-role="community-summary"]').text('Showing 0-0 of 0');
					return;
				}

				$empty.hide();

				const template = $('#zaobank-community-card-template').html();
				const html = users.map(function(user) {
					const isSaved = (ZAOBank.state.community.savedIds || []).indexOf(user.id) !== -1;
					const isFlagged = !!user.is_flagged;
					return ZAOBank.renderTemplate(template, {
						id: user.id,
						name: ZAOBank.escapeHtml(user.display_name || user.name || 'Member'),
						pronouns: user.pronouns ? ZAOBank.escapeHtml(user.pronouns) : '',
						avatar_url: user.avatar_url || ZAOBank.getDefaultAvatar(64),
						skills: user.skills ? ZAOBank.escapeHtml(user.skills) : '',
						availability: user.availability ? ZAOBank.escapeHtml(user.availability) : '',
						region: user.primary_region ? ZAOBank.escapeHtml(user.primary_region.name || '') : '',
						skill_tags: Array.isArray(user.skill_tags) ? user.skill_tags.map(function(tag) {
							return { label: ZAOBank.formatTagLabel(tag), slug: tag };
						}) : [],
						can_request: zaobank.isLoggedIn && zaobank.hasMemberAccess,
						can_save: zaobank.isLoggedIn && zaobank.hasMemberAccess,
						is_saved: isSaved,
						save_label: isSaved ? 'Saved' : 'Save',
						is_flagged: isFlagged,
						flagged_class: isFlagged ? 'zaobank-flagged-content' : ''
					});
				}).join('');

				if (append) {
					$list.append(html);
				} else {
					$list.html(html);
				}

				const total = response.total || 0;
				const perPage = filters.per_page;
				const start = total === 0 ? 0 : ((ZAOBank.state.community.page - 1) * perPage + 1);
				const end = total === 0 ? 0 : Math.min(total, ZAOBank.state.community.page * perPage);
				$('[data-role="community-summary"]').text(`Showing ${start}-${end} of ${total}`);

				if (ZAOBank.state.community.page < ZAOBank.state.community.totalPages) {
					$loadMore.show();
				} else {
					$loadMore.hide();
				}
			}, function() {
				$list.attr('data-loading', 'false');
			});
		},

		updateCommunityResetButton: function(skillTags) {
			const $button = $('[data-action="community-reset"]');
			if (!$button.length) return;

			const tags = Array.isArray(skillTags) ? skillTags.filter(Boolean) : [];
			if (tags.length === 0) {
				$button.hide();
				return;
			}

			const label = tags.length === 1
				? `Clear “${ZAOBank.formatTagLabel(tags[0])}”`
				: 'Clear tag filters';

			$button.find('[data-role="community-reset-label"]').text(label);
			$button.show();
		},

		setAddressBookTab: function(tabId) {
			const $container = $('.zaobank-community-page');
			$container.find('.zaobank-address-tab').removeClass('active');
			$container.find(`.zaobank-address-tab[data-address-tab="${tabId}"]`).addClass('active');
			$container.find('.zaobank-address-panel').removeClass('active');
			$container.find(`.zaobank-address-panel[data-address-panel="${tabId}"]`).addClass('active');
			this.state.community.addressTab = tabId;
		},

		handleAddressBookTab: function(e) {
			e.preventDefault();
			const $tab = $(e.currentTarget);
			const tabId = $tab.data('address-tab');
			if (!tabId) return;

			this.setAddressBookTab(tabId);

			if (tabId === 'worked-with') {
				this.loadWorkedWith();
			} else if (tabId === 'saved') {
				this.loadSavedProfiles(true);
			}
		},

		loadSavedProfiles: function(renderList = true) {
			if (!zaobank.hasMemberAccess) {
				return;
			}

			const $list = $('.zaobank-saved-profiles-list');
			const $empty = $('[data-empty="saved-profiles"]');

			if (renderList && $list.length) {
				$list.attr('data-loading', 'true');
			}

			this.apiCall('me/saved-profiles', 'GET', {}, function(response) {
				ZAOBank.state.community.savedIds = response.ids || [];
				ZAOBank.state.community.savedLoaded = true;
				ZAOBank.updateSavedButtons();

				if (!$list.length || !renderList) {
					return;
				}

				$list.attr('data-loading', 'false');

				const users = response.users || [];
				if (users.length === 0) {
					$list.empty();
					$empty.show();
					return;
				}

				$empty.hide();
				const template = $('#zaobank-saved-profile-card-template').html();
				const html = users.map(function(user) {
					return ZAOBank.renderTemplate(template, {
						id: user.id,
						name: ZAOBank.escapeHtml(user.display_name || user.name || 'Member'),
						pronouns: user.pronouns ? ZAOBank.escapeHtml(user.pronouns) : '',
						avatar_url: user.avatar_url || ZAOBank.getDefaultAvatar(64),
						skills: user.skills ? ZAOBank.escapeHtml(user.skills) : '',
						availability: user.availability ? ZAOBank.escapeHtml(user.availability) : '',
						region: user.primary_region ? ZAOBank.escapeHtml(user.primary_region.name || '') : '',
						skill_tags: Array.isArray(user.skill_tags) ? user.skill_tags.map(function(tag) {
							return { label: ZAOBank.formatTagLabel(tag), slug: tag };
						}) : [],
						can_request: zaobank.isLoggedIn && zaobank.hasMemberAccess
					});
				}).join('');

				$list.html(html);
			}, function() {
				if ($list.length && renderList) {
					$list.attr('data-loading', 'false');
				}
			});
		},

		updateSavedButtons: function() {
			const savedIds = ZAOBank.state.community.savedIds || [];
			$('.zaobank-save-profile').each(function() {
				const $button = $(this);
				const $card = $button.closest('.zaobank-community-card');
				const userId = parseInt($card.data('user-id'), 10);
				const isSaved = savedIds.indexOf(userId) !== -1;
				$button.attr('data-saved', isSaved ? 'true' : 'false');
				$button.toggleClass('is-saved', isSaved);
				const $label = $button.find('.zaobank-save-label');
				if ($label.length) {
					$label.text(isSaved ? 'Saved' : 'Save');
				} else {
					$button.text(isSaved ? 'Saved' : 'Save');
				}
			});
		},

		handleOpenCommunityRequest: function(e) {
			e.preventDefault();
			if (!zaobank.hasMemberAccess) {
				ZAOBank.showToast('Requests are available to verified members only.', 'error');
				return;
			}
			const $button = $(e.currentTarget);
			const $card = $button.closest('.zaobank-community-card');
			$card.find('.zaobank-community-actions').hide();
			$card.find('.zaobank-community-request-form').removeAttr('hidden');
		},

		handleCancelCommunityRequest: function(e) {
			e.preventDefault();
			const $button = $(e.currentTarget);
			const $card = $button.closest('.zaobank-community-card');
			const $form = $card.find('.zaobank-community-request-form');
			$form.find('textarea, input').val('');
			$form.attr('hidden', true);
			$card.find('.zaobank-community-actions').show();
		},

		handleSubmitCommunityRequest: function(e) {
			e.preventDefault();
			if (!zaobank.hasMemberAccess) {
				ZAOBank.showToast('Requests are available to verified members only.', 'error');
				return;
			}
			const $button = $(e.currentTarget);
			const $form = $button.closest('.zaobank-community-request-form');
			const $card = $button.closest('.zaobank-community-card');
			const toUserId = $card.data('user-id');
			const hours = $form.find('[name="request_hours"]').val();
			const details = $form.find('[name="request_details"]').val().trim();

			if (!details) {
				ZAOBank.showToast('Please share a short description of your request.', 'error');
				return;
			}

			if ($form.data('processing')) {
				return;
			}

			$form.data('processing', true);
			$button.prop('disabled', true).text('Sending...');

			const message = `Skill request:\nEstimated hours: ${hours || 'Not specified'}\n\n${details}`;

			this.apiCall('messages', 'POST', {
				to_user_id: toUserId,
				message: message
			}, function() {
				ZAOBank.showToast('Request sent!', 'success');
				$form.attr('hidden', true);
				$card.find('.zaobank-community-actions').show();
				$form.find('textarea, input').val('');
				$form.data('processing', false);
				$button.prop('disabled', false).text('Send Request');
			}, function() {
				$form.data('processing', false);
				$button.prop('disabled', false).text('Send Request');
			});
		},

		handleSaveProfile: function(e) {
			e.preventDefault();
			if (!zaobank.hasMemberAccess) {
				ZAOBank.showToast('Saving profiles is available to verified members only.', 'error');
				return;
			}

			const $button = $(e.currentTarget);
			const $card = $button.closest('.zaobank-community-card');
			const userId = parseInt($card.data('user-id'), 10);
			const isSaved = $button.attr('data-saved') === 'true';

			if (!userId) return;

			$button.prop('disabled', true);

			if (isSaved) {
				this.apiCall('me/saved-profiles/' + userId, 'DELETE', {}, function(response) {
					ZAOBank.state.community.savedIds = response.ids || [];
					ZAOBank.updateSavedButtons();
					ZAOBank.loadSavedProfiles(true);
					$button.prop('disabled', false);
				}, function() {
					$button.prop('disabled', false);
				});
				return;
			}

			this.apiCall('me/saved-profiles', 'POST', { user_id: userId }, function(response) {
				ZAOBank.state.community.savedIds = response.ids || [];
				ZAOBank.updateSavedButtons();
				ZAOBank.loadSavedProfiles(true);
				$button.prop('disabled', false);
			}, function() {
				$button.prop('disabled', false);
			});
		},

		handleRemoveSavedProfile: function(e) {
			e.preventDefault();
			if (!zaobank.hasMemberAccess) {
				ZAOBank.showToast('Saving profiles is available to verified members only.', 'error');
				return;
			}

			const $button = $(e.currentTarget);
			const $card = $button.closest('.zaobank-community-card');
			const userId = parseInt($card.data('user-id'), 10);

			if (!userId) return;

			$button.prop('disabled', true);

			this.apiCall('me/saved-profiles/' + userId, 'DELETE', {}, function(response) {
				ZAOBank.state.community.savedIds = response.ids || [];
				ZAOBank.updateSavedButtons();
				ZAOBank.loadSavedProfiles(true);
			}, function() {
				$button.prop('disabled', false);
			});
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
				const currentUserId = Number(zaobank.userId || 0);
				const requesterId = Number(job.requester_id || 0);
				const providerId = Number(job.provider_id || 0);
				const canClaim = zaobank.isLoggedIn && zaobank.hasMemberAccess && !providerId && requesterId !== currentUserId;
				const canComplete = zaobank.isLoggedIn && zaobank.hasMemberAccess && providerId && !job.completed_at && requesterId === currentUserId;
				const canEdit = zaobank.isLoggedIn && zaobank.hasMemberAccess && requesterId === currentUserId && !providerId;
				const canRelease = zaobank.isLoggedIn && zaobank.hasMemberAccess && providerId === currentUserId && !job.completed_at;
				const canMessage = zaobank.isLoggedIn && zaobank.hasMemberAccess && requesterId !== currentUserId;
				const canReport = requesterId !== currentUserId;
				const showFeedbackPrompt = !!job.completed_at && requesterId === currentUserId && providerId;

				const html = ZAOBank.renderTemplate(template, {
					id: job.id,
					title: ZAOBank.escapeHtml(job.title),
					description: ZAOBank.escapeHtml(job.description).replace(/\n/g, '<br>'),
					hours: job.hours,
					location: job.location ? ZAOBank.escapeHtml(job.location) : '',
					preferred_date: job.preferred_date || '',
					virtual_ok: !!job.virtual_ok,
					skills_required: job.skills_required ? job.skills_required.split(',').map(s => s.trim()) : [],
					status_class: status.class,
					status_label: status.label,
					requester_id: job.requester_id,
					requester_name: ZAOBank.escapeHtml(job.requester_name),
					requester_pronouns: job.requester_pronouns ? ZAOBank.escapeHtml(job.requester_pronouns) : '',
					requester_avatar: job.requester_avatar || ZAOBank.getDefaultAvatar(),
					requester_since: ZAOBank.formatDate(job.requester_registered, true),
					provider_id: providerId || '',
					provider_name: job.provider_name ? ZAOBank.escapeHtml(job.provider_name) : '',
					provider_pronouns: job.provider_pronouns ? ZAOBank.escapeHtml(job.provider_pronouns) : '',
					provider_avatar: job.provider_avatar || ZAOBank.getDefaultAvatar(),
					exchange_id: job.exchange_id || '',
					can_claim: canClaim,
					can_complete: canComplete,
					can_edit: canEdit,
					can_release: canRelease,
					can_message: canMessage,
					can_report: canReport,
					show_feedback_prompt: showFeedbackPrompt
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

			if ($button.data('processing')) {
				return;
			}
			$button.data('processing', true);

			if (!confirm('Mark this job as complete? This will record the time exchange.')) {
				$button.data('processing', false);
				return;
			}

			// Get job details for hours
			this.apiCall('jobs/' + jobId, 'GET', {}, function(job) {
				const currentHours = job.hours || 1;
				const hoursInput = prompt('Adjust hours if needed (current: ' + currentHours + '):', currentHours);
				const providerId = job.provider_id;

				if (hoursInput === null) {
					$button.data('processing', false);
					return;
				}

				const hours = parseFloat(hoursInput) || currentHours;

				$button.prop('disabled', true).text('Processing...');

				ZAOBank.apiCall('jobs/' + jobId + '/complete', 'POST', { hours: hours }, function(response) {
					ZAOBank.showToast('Job completed! Exchange recorded.', 'success');
					const exchangeId = response.exchange && response.exchange.id ? response.exchange.id : '';
					const $actions = $button.closest('.zaobank-job-actions');
					const exchangesUrl = $('.zaobank-job-single').data('exchanges-url');
					const appreciationsUrl = $('.zaobank-job-single').data('appreciations-url');

					$button.remove();

					if (exchangeId && exchangesUrl) {
						const feedbackLink = `
							<a href="${exchangesUrl}?exchange_id=${exchangeId}" class="zaobank-btn zaobank-btn-primary zaobank-btn-lg zaobank-btn-block zaobank-job-feedback">
								Leave Appreciation
							</a>
						`;
						$actions.prepend(feedbackLink);
					}

					if (appreciationsUrl) {
						const viewLink = `
							<a href="${appreciationsUrl}?tab=given" class="zaobank-btn zaobank-btn-outline zaobank-btn-block zaobank-job-feedback-view">
								View Appreciations
							</a>
						`;
						$actions.append(viewLink);
					}
				}, function() {
					$button.prop('disabled', false).text('Mark Complete');
					$button.data('processing', false);
				});
			}, function() {
				$button.data('processing', false);
			});
		},

		handleReleaseJob: function(e) {
			e.preventDefault();
			const $button = $(e.currentTarget);
			const jobId = $button.data('job-id');

			if (!confirm('Release this job? It will become available for others to claim.')) {
				return;
			}

			const reason = prompt('Reason for releasing (optional):') || '';

			$button.prop('disabled', true).text('Releasing...');

			this.apiCall('jobs/' + jobId + '/release', 'POST', { reason: reason }, function(response) {
				ZAOBank.showToast('Job released successfully.', 'success');
				setTimeout(function() {
					location.reload();
				}, 1000);
			}, function() {
				$button.prop('disabled', false).text('Release This Job');
			});
		},

		handleDeleteJob: function(e) {
			e.preventDefault();
			const $button = $(e.currentTarget);
			const jobId = $button.data('job-id');

			if (!confirm('Are you sure you want to delete this job? This cannot be undone.')) {
				return;
			}

			$button.prop('disabled', true).text('Deleting...');

			this.apiCall('jobs/' + jobId, 'DELETE', {}, function(response) {
				ZAOBank.showToast('Job deleted.', 'success');
				setTimeout(function() {
					window.history.back();
				}, 1000);
			}, function() {
				$button.prop('disabled', false).text('Delete Job');
			});
		},

		// =========================================================================
		// Job Form
		// =========================================================================

		initJobForm: function($container) {
			const jobId = $container.data('job-id');
			this.loadJobTypes();

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
				$form.find('[name="virtual_ok"]').prop('checked', !!job.virtual_ok);
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
			if (!zaobank.hasMemberAccess) {
				ZAOBank.showToast('Job posting is available to verified members only.', 'error');
				return;
			}
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
				virtual_ok: $form.find('[name="virtual_ok"]').is(':checked'),
				skills_required: $form.find('[name="skills_required"]').val(),
				region: $form.find('[name="region"]').val(),
				job_types: $form.find('[name="job_types[]"]:checked').map(function() {
					return parseInt($(this).val(), 10);
				}).get()
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
					const currentUserId = Number(zaobank.userId || 0);
					const requesterId = Number(job.requester_id || 0);
					const providerId = Number(job.provider_id || 0);
					const canComplete = zaobank.hasMemberAccess && providerId && !job.completed_at && requesterId === currentUserId;
					const canEdit = zaobank.hasMemberAccess && requesterId === currentUserId && !providerId;
					const canRelease = zaobank.hasMemberAccess && providerId === currentUserId && !job.completed_at;
					return ZAOBank.renderTemplate(template, {
						id: job.id,
						title: ZAOBank.escapeHtml(job.title),
						hours: job.hours,
						created_date: ZAOBank.formatDate(job.created_at),
						status_class: status.class,
						status_label: status.label,
						provider_name: job.provider_name ? ZAOBank.escapeHtml(job.provider_name) : '',
						provider_pronouns: job.provider_pronouns ? ZAOBank.escapeHtml(job.provider_pronouns) : '',
						provider_avatar: job.provider_avatar || ZAOBank.getDefaultAvatar(),
						can_complete: canComplete,
						can_edit: canEdit,
						can_release: canRelease
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
					name: ZAOBank.escapeHtml(profile.name || profile.display_name || ''),
					display_name: ZAOBank.escapeHtml(profile.display_name || profile.name || ''),
					pronouns: profile.pronouns ? ZAOBank.escapeHtml(profile.pronouns) : '',
					avatar_url: profile.avatar_url || ZAOBank.getDefaultAvatar(96),
					member_since: ZAOBank.formatDate(profile.registered, true),
					bio: profile.bio ? ZAOBank.escapeHtml(profile.bio) : '',
					skills: profile.skills ? ZAOBank.escapeHtml(profile.skills) : '',
					availability: profile.availability ? ZAOBank.escapeHtml(profile.availability) : '',
					primary_region: profile.primary_region || null,
					skill_tags: Array.isArray(profile.skill_tags) ? profile.skill_tags.map(function(tag) {
						return {
							label: ZAOBank.formatTagLabel(tag),
							slug: tag
						};
					}) : [],
					profile_tags: Array.isArray(profile.profile_tags) ? profile.profile_tags.map(function(tag) {
						return ZAOBank.formatTagLabel(tag);
					}) : [],
					is_own: isOwn,
					discord_id: profile.discord_id || '',
					discord_url: profile.discord_id ? 'https://discord.com/users/' + profile.discord_id : '',
					has_signal: profile.has_signal || false,
					show_connect: !!(profile.discord_id || profile.has_signal)
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
			this.initAvatarUpload();
		},

		initAvatarUpload: function() {
			var mediaFrame;

			$(document).on('click', '#zaobank-upload-avatar', function(e) {
				e.preventDefault();

				if (mediaFrame) {
					mediaFrame.open();
					return;
				}

				mediaFrame = wp.media({
					title: 'Choose Profile Photo',
					button: { text: 'Use This Photo' },
					multiple: false,
					library: { type: 'image' }
				});

				mediaFrame.on('select', function() {
					var attachment = mediaFrame.state().get('selection').first().toJSON();
					var url = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
					$('#profile-avatar').attr('src', url);
					$('#profile-image-id').val(attachment.id);
					$('#zaobank-remove-avatar').show();
				});

				mediaFrame.open();
			});

			$(document).on('click', '#zaobank-remove-avatar', function(e) {
				e.preventDefault();
				$('#profile-image-id').val('0');
				$('#profile-avatar').attr('src', ZAOBank.getDefaultAvatar(96));
				$(this).hide();
			});
		},

		loadProfileForEdit: function() {
			const $form = $('#zaobank-profile-form');

			this.apiCall('me/profile', 'GET', {}, function(response) {
				$form.attr('data-loading', 'false');

				const profile = response.profile || response;
				$form.find('[name="display_name"]').val(profile.display_name || profile.name || '');
				$form.find('[name="user_pronouns"]').val(profile.pronouns || '');
				$form.find('[name="user_bio"]').val(profile.bio || '');
				$form.find('[name="user_skills"]').val(profile.skills || '');
				$form.find('[name="user_availability"]').val(profile.availability || '');
				$form.find('[name="user_available_for_requests"]').prop('checked', profile.available_for_requests !== false);
				$form.find('[name="user_phone"]').val(profile.phone || '');
				$form.find('[name="user_discord_id"]').val(profile.discord_id || '');

				// Update avatar preview
				if (profile.avatar_url) {
					$('#profile-avatar').attr('src', profile.avatar_url);
				}
				if (profile.profile_image_id) {
					$('#profile-image-id').val(profile.profile_image_id);
					$('#zaobank-remove-avatar').show();
				}

				if (profile.primary_region && profile.primary_region.id) {
					$form.find('[name="user_primary_region"]').val(profile.primary_region.id);
				}

				// Check profile tags
				if (profile.profile_tags && Array.isArray(profile.profile_tags)) {
					profile.profile_tags.forEach(function(tag) {
						$form.find(`[name="user_profile_tags[]"][value="${tag}"]`).prop('checked', true);
					});
				}

				// Check skill tags
				if (profile.skill_tags && Array.isArray(profile.skill_tags)) {
					profile.skill_tags.forEach(function(tag) {
						$form.find(`[name="user_skill_tags[]"][value="${tag}"]`).prop('checked', true);
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
			if (!zaobank.hasMemberAccess) {
				ZAOBank.showToast('Profile edits are available to verified members only.', 'error');
				return;
			}
			const $form = $(e.currentTarget);
			const $button = $form.find('[type="submit"]');

			$button.prop('disabled', true).text('Saving...');

			const profileImageVal = $form.find('[name="user_profile_image"]').val();
			const data = {
				display_name: $form.find('[name="display_name"]').val(),
				user_pronouns: $form.find('[name="user_pronouns"]').val(),
				user_bio: $form.find('[name="user_bio"]').val(),
				user_skills: $form.find('[name="user_skills"]').val(),
				user_availability: $form.find('[name="user_availability"]').val(),
				user_available_for_requests: $form.find('[name="user_available_for_requests"]').is(':checked') ? 1 : 0,
				user_phone: $form.find('[name="user_phone"]').val(),
				user_discord_id: $form.find('[name="user_discord_id"]').val(),
				user_primary_region: $form.find('[name="user_primary_region"]').val(),
				user_profile_tags: $form.find('[name="user_profile_tags[]"]:checked').map(function() {
					return $(this).val();
				}).get(),
				user_skill_tags: $form.find('[name="user_skill_tags[]"]:checked').map(function() {
					return $(this).val();
				}).get(),
				user_contact_preferences: $form.find('[name="user_contact_preferences[]"]:checked').map(function() {
					return $(this).val();
				}).get()
			};

			if (profileImageVal !== '') {
				data.user_profile_image = parseInt(profileImageVal, 10);
			}

			this.apiCall('me/profile', 'PUT', data, function(response) {
				$button.prop('disabled', false).text('Save Changes');
				ZAOBank.showToast('Profile updated!', 'success');

				const profile = response.profile || response;
				if (profile && profile.avatar_url) {
					$('.zaobank-header-avatar img').attr('src', profile.avatar_url);
				}
			}, function() {
				$button.prop('disabled', false).text('Save Changes');
			});
		},

		// =========================================================================
		// Messages
		// =========================================================================

		initMessages: function($container) {
			if (!zaobank.hasMemberAccess) {
				const $list = $('.zaobank-conversations-list');
				if ($list.length) {
					$list.attr('data-loading', 'false').html('<p class="zaobank-loading-placeholder" style="text-align: center;">Messaging is available to verified members.</p>');
				}
				return;
			}

			const view = $container.data('view');
			if (view === 'updates') {
				this.loadJobUpdates();
			} else {
				this.loadConversations();
			}

			this.initMessageSearch();
		},

		initMessageSearch: function() {
			const $results = $('.zaobank-message-search-results');
			if ($results.length) {
				$results.empty();
			}
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
						other_user_pronouns: conv.other_user_pronouns ? ZAOBank.escapeHtml(conv.other_user_pronouns) : '',
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
				const currentUserId = Number(zaobank.userId || 0);
				const fromId = Number(msg.from_user_id || 0);
				const toId = Number(msg.to_user_id || 0);
				const otherId = fromId === currentUserId ? toId : fromId;
				const otherName = fromId === currentUserId ? msg.to_user_name : msg.from_user_name;
				const otherPronouns = fromId === currentUserId ? msg.to_user_pronouns : msg.from_user_pronouns;
				const otherAvatar = fromId === currentUserId ? msg.to_user_avatar : msg.from_user_avatar;
				const messageTime = ZAOBank.parseDate(msg.created_at);

				if (!convMap[otherId]) {
					convMap[otherId] = {
						other_user_id: otherId,
						other_user_name: otherName,
						other_user_pronouns: otherPronouns || '',
						other_user_avatar: otherAvatar || null,
						last_message: msg.message,
						last_message_time: msg.created_at,
						unread_count: 0
					};
				} else {
					const existingTime = ZAOBank.parseDate(convMap[otherId].last_message_time);
					if (messageTime > existingTime) {
						convMap[otherId].last_message = msg.message;
						convMap[otherId].last_message_time = msg.created_at;
					}
					if (!convMap[otherId].other_user_avatar && otherAvatar) {
						convMap[otherId].other_user_avatar = otherAvatar;
					}
					if (!convMap[otherId].other_user_pronouns && otherPronouns) {
						convMap[otherId].other_user_pronouns = otherPronouns;
					}
				}

				if (!msg.is_read && toId === currentUserId) {
					convMap[otherId].unread_count++;
				}
			});

			return Object.values(convMap).sort(function(a, b) {
				return ZAOBank.parseDate(b.last_message_time) - ZAOBank.parseDate(a.last_message_time);
			});
		},

		loadJobUpdates: function() {
			const $list = $('.zaobank-conversations-list');
			const $empty = $('.zaobank-empty-state');

			this.apiCall('me/messages', 'GET', { message_type: 'job_update' }, function(response) {
				$list.attr('data-loading', 'false');

				const messages = response.messages || [];

				if (messages.length === 0) {
					$list.empty();
					$empty.show();
					return;
				}

				$empty.hide();

				const currentUserId = Number(zaobank.userId || 0);
				const unreadCount = messages.filter(function(msg) {
					return !msg.is_read && Number(msg.to_user_id || 0) === currentUserId;
				}).length;

				const template = $('#zaobank-job-update-template').html();
				const html = messages.map(function(msg) {
					const fromId = Number(msg.from_user_id || 0);
					const otherId = fromId === currentUserId ? msg.to_user_id : msg.from_user_id;
					const otherName = fromId === currentUserId ? msg.to_user_name : msg.from_user_name;
					const otherPronouns = fromId === currentUserId ? msg.to_user_pronouns : msg.from_user_pronouns;
					const otherAvatar = fromId === currentUserId ? msg.to_user_avatar : msg.from_user_avatar;

					return ZAOBank.renderTemplate(template, {
						other_user_avatar: otherAvatar || ZAOBank.getDefaultAvatar(),
						other_user_name: ZAOBank.escapeHtml(otherName),
						other_user_pronouns: otherPronouns ? ZAOBank.escapeHtml(otherPronouns) : '',
						message: ZAOBank.escapeHtml(msg.message),
						time: ZAOBank.formatRelativeTime(msg.created_at),
						job_id: msg.job_id || '',
						job_title: msg.job_title ? ZAOBank.escapeHtml(msg.job_title) : ''
					});
				}).join('');

				$list.html(html);

				if (unreadCount > 0 && zaobank.hasMemberAccess) {
					ZAOBank.apiCall('me/messages/read-type', 'POST', { message_type: 'job_update' }, function() {
						ZAOBank.updateUnreadBadge(-unreadCount);
					});
				}
			}, function() {
				$list.attr('data-loading', 'false');
			});
		},

		handleMarkConversationRead: function(e) {
			e.preventDefault();
			e.stopPropagation();
			const $button = $(e.currentTarget);
			const userId = $button.data('user-id');
			const $badge = $button.closest('.zaobank-conversation-item-wrapper').find('.zaobank-conversation-badge');
			const badgeCount = parseInt($badge.attr('data-unread-count') || $badge.text().replace('+', ''), 10) || 0;

			this.apiCall('me/messages/read-all', 'POST', { with_user: userId }, function() {
				$button.closest('.zaobank-conversation-item-wrapper').find('.zaobank-conversation-item').removeClass('unread');
				$button.closest('.zaobank-conversation-item-wrapper').find('.zaobank-conversation-badge').remove();
				$button.remove();
				ZAOBank.updateUnreadBadge(-badgeCount);
				ZAOBank.showToast('Marked as read', 'success');
			});
		},

		handleArchiveConversation: function(e) {
			e.preventDefault();
			e.stopPropagation();
			const $button = $(e.currentTarget);
			const userId = $button.data('user-id');
			const $badge = $button.closest('.zaobank-conversation-item-wrapper').find('.zaobank-conversation-badge');
			const badgeCount = parseInt($badge.attr('data-unread-count') || $badge.text().replace('+', ''), 10) || 0;

			if (!confirm('Archive this conversation? It will be hidden from your list.')) {
				return;
			}

			this.apiCall('me/messages/archive', 'POST', { other_user_id: userId }, function() {
				$button.closest('.zaobank-conversation-item-wrapper').fadeOut(300, function() {
					$(this).remove();
				});
				ZAOBank.updateUnreadBadge(-badgeCount);
				ZAOBank.showToast('Conversation archived', 'success');
			});
		},

		// =========================================================================
		// Conversation
		// =========================================================================

		initConversation: function($container) {
			const userId = $container.data('user-id');
			if (!userId) return;
			if (!zaobank.hasMemberAccess) {
				$('.zaobank-messages-list').attr('data-loading', 'false').html('<p class="zaobank-loading-placeholder" style="text-align: center;">Messaging is available to verified members.</p>');
				return;
			}

			this.loadConversationMessages(userId);
			this.initMessageComposer();
		},

		loadConversationMessages: function(userId) {
			const $list = $('.zaobank-messages-list');
			if (!zaobank.hasMemberAccess) {
				$list.attr('data-loading', 'false').html('<p class="zaobank-loading-placeholder" style="text-align: center;">Messaging is available to verified members.</p>');
				return;
			}

			this.apiCall('me/messages', 'GET', { with_user: userId }, function(response) {
				$list.attr('data-loading', 'false');

				const messages = response.messages || [];
				const sortedMessages = messages.slice().sort(function(a, b) {
					return ZAOBank.parseDate(a.created_at) - ZAOBank.parseDate(b.created_at);
				});

				if (sortedMessages.length === 0) {
					$list.html('<p class="zaobank-loading-placeholder" style="text-align: center;">Start a conversation!</p>');
					return;
				}

				const template = $('#zaobank-message-template').html();
				const html = sortedMessages.map(function(msg) {
					const isOwn = Number(msg.from_user_id || 0) === Number(zaobank.userId || 0);
					return ZAOBank.renderTemplate(template, {
						id: msg.id,
						message: ZAOBank.escapeHtml(msg.message),
						time: ZAOBank.formatTime(msg.created_at),
						is_own: isOwn
					});
				}).join('');

				$list.html(html);

				const currentUserId = Number(zaobank.userId || 0);
				const unreadCount = sortedMessages.filter(function(msg) {
					return !msg.is_read && Number(msg.to_user_id || 0) === currentUserId;
				}).length;

				if (unreadCount > 0 && zaobank.hasMemberAccess) {
					ZAOBank.apiCall('me/messages/read-all', 'POST', { with_user: userId }, function() {
						ZAOBank.updateUnreadBadge(-unreadCount);
					});
				}

				// Scroll to bottom
				const $container = $('.zaobank-messages-container');
				$container.scrollTop($container[0].scrollHeight);
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
			if (!zaobank.hasMemberAccess) {
				ZAOBank.showToast('Messaging is available to verified members only.', 'error');
				return;
			}
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

		handleMessageUserSearch: function(e) {
			const $input = $(e.currentTarget);
			const query = $input.val().trim();
			const $results = $('.zaobank-message-search-results');

			if (query.length < 2) {
				$results.empty();
				return;
			}

			$results.attr('data-loading', 'true');

			this.apiCall('users/search', 'GET', { q: query }, function(response) {
				$results.attr('data-loading', 'false');
				const users = response.users || [];

				if (!users.length) {
					$results.html('<p class="zaobank-empty-hint">No members found.</p>');
					return;
				}

				const template = $('#zaobank-message-search-item-template').html();
				const html = users.map(function(user) {
					return ZAOBank.renderTemplate(template, {
						id: user.id,
						name: ZAOBank.escapeHtml(user.name),
						pronouns: user.pronouns ? ZAOBank.escapeHtml(user.pronouns) : '',
						avatar_url: user.avatar_url || ZAOBank.getDefaultAvatar(40)
					});
				}).join('');

				$results.html(html);
			}, function() {
				$results.attr('data-loading', 'false');
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
				const appreciationTags = ZAOBank.getAppreciationTags();
				const hasAppreciationTags = appreciationTags.length > 0;
				const highlightExchangeId = ZAOBank.getQueryParam('exchange_id');

				const html = exchanges.map(function(exchange) {
					const currentUserId = Number(zaobank.userId || 0);
					const providerId = Number(exchange.provider_id || exchange.provider_user_id || 0);
					const requesterId = Number(exchange.requester_id || exchange.requester_user_id || 0);
					const isEarned = providerId === currentUserId;
					const otherUserId = isEarned ? requesterId : providerId;
					const otherUserName = isEarned
						? (exchange.requester_name || exchange.other_user_name || 'User')
						: (exchange.provider_name || exchange.other_user_name || 'User');
					const otherUserPronouns = isEarned
						? (exchange.requester_pronouns || exchange.other_user_pronouns || '')
						: (exchange.provider_pronouns || exchange.other_user_pronouns || '');
					const otherUserAvatar = isEarned
						? (exchange.requester_avatar || ZAOBank.getDefaultAvatar(24))
						: (exchange.provider_avatar || ZAOBank.getDefaultAvatar(24));

					return ZAOBank.renderTemplate(template, {
						id: exchange.id,
						job_id: exchange.job_id,
						job_title: ZAOBank.escapeHtml(exchange.job_title || 'Job'),
						hours: exchange.hours,
						is_earned: isEarned,
						type: isEarned ? 'earned' : 'spent',
						other_user_id: otherUserId,
						other_user_name: ZAOBank.escapeHtml(otherUserName),
						other_user_pronouns: otherUserPronouns ? ZAOBank.escapeHtml(otherUserPronouns) : '',
						other_user_avatar: otherUserAvatar,
						date: ZAOBank.formatDate(exchange.created_at),
						has_appreciation: exchange.has_appreciation,
						appreciation_given: exchange.appreciation_given,
						appreciation_received: exchange.appreciation_received,
						has_appreciation_tags: hasAppreciationTags,
						appreciation_tags: appreciationTags
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

				if (highlightExchangeId) {
					const $highlightCard = $list.find(`.zaobank-exchange-card[data-exchange-id="${highlightExchangeId}"]`);
					if ($highlightCard.length) {
						$highlightCard[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
						if ($highlightCard.find('.zaobank-exchange-actions').length) {
							ZAOBank.openAppreciationForm($highlightCard);
						}
					}
				}
			}, function() {
				$list.attr('data-loading', 'false');
			});
		},

		loadWorkedWith: function() {
			const $list = $('.zaobank-worked-with-list');
			const $empty = $('[data-empty="worked-with"]');

			if (!$list.length) return;

			this.apiCall('me/worked-with', 'GET', {}, function(response) {
				$list.attr('data-loading', 'false');

				const people = response.people || [];

				if (people.length === 0) {
					$list.empty();
					$empty.show();
					return;
				}

				$empty.hide();

				const template = $('#zaobank-worked-with-item-template').html();
				const noteTags = ZAOBank.getPrivateNoteTags();
				const hasNoteTags = noteTags.length > 0;

				const html = people.map(function(person) {
					const latestNote = person.latest_note || null;
					const latestTag = latestNote ? latestNote.tag_slug : '';
					const latestText = latestNote ? latestNote.note : '';
					const latestTextHtml = latestText ? `<p data-role="latest-note-text">${ZAOBank.escapeHtml(latestText)}</p>` : '';

					return ZAOBank.renderTemplate(template, {
						other_user_id: person.other_user_id,
						other_user_name: ZAOBank.escapeHtml(person.other_user_name || 'User'),
						other_user_pronouns: person.other_user_pronouns ? ZAOBank.escapeHtml(person.other_user_pronouns) : '',
						other_user_avatar: person.other_user_avatar || ZAOBank.getDefaultAvatar(48),
						total_exchanges: person.total_exchanges || 0,
						total_hours: person.total_hours || 0,
						jobs_provided: person.jobs_provided || 0,
						jobs_received: person.jobs_received || 0,
						last_exchange_at: person.last_exchange_at ? ZAOBank.formatDate(person.last_exchange_at) : '',
						has_latest_note: !!latestNote,
						latest_note_tag: latestTag ? ZAOBank.escapeHtml(latestTag) : '',
						latest_note_tag_label: latestTag ? ZAOBank.escapeHtml(ZAOBank.formatTagLabel(latestTag)) : '',
						latest_note_text: latestText ? ZAOBank.escapeHtml(latestText) : '',
						latest_note_text_html: latestTextHtml,
						has_note_tags: hasNoteTags,
						can_message: zaobank.isLoggedIn && zaobank.hasMemberAccess,
						note_tags: noteTags
					});
				}).join('');

				$list.html(html);
			}, function() {
				$list.attr('data-loading', 'false');
			});
		},

		// =========================================================================
		// Appreciations
		// =========================================================================

		initAppreciations: function($container) {
			const userId = $container.data('user-id');
			const initialTab = ZAOBank.getQueryParam('tab') === 'given' ? 'given' : 'received';
			this.loadAppreciations(userId, initialTab);

			if (initialTab === 'given') {
				const $tab = $container.find(`.zaobank-tab[data-tab="${initialTab}"]`);
				$tab.addClass('active').siblings('.zaobank-tab').removeClass('active');
				$container.find('.zaobank-tab-panel').removeClass('active');
				$container.find(`[data-panel="${initialTab}"]`).addClass('active');
			}
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
						from_user_pronouns: app.from_user_pronouns ? ZAOBank.escapeHtml(app.from_user_pronouns) : '',
						from_user_avatar: app.from_user_avatar || ZAOBank.getDefaultAvatar(),
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

		openAppreciationForm: function($card) {
			$card.find('.zaobank-exchange-actions').hide();
			$card.find('.zaobank-appreciation-form').removeAttr('hidden');
		},

		handleGiveAppreciation: function(e) {
			e.preventDefault();
			const $button = $(e.currentTarget);
			const $card = $button.closest('.zaobank-exchange-card');
			ZAOBank.openAppreciationForm($card);
		},

		handleCancelAppreciation: function(e) {
			e.preventDefault();
			const $button = $(e.currentTarget);
			const $card = $button.closest('.zaobank-exchange-card');
			const $form = $card.find('.zaobank-appreciation-form');
			$form.find('input[type="checkbox"]').prop('checked', false);
			$form.find('textarea').val('');
			$form.attr('hidden', true);
			$card.find('.zaobank-exchange-actions').show();
		},

		handleSubmitAppreciation: function(e) {
			e.preventDefault();
			const $button = $(e.currentTarget);
			const $form = $button.closest('.zaobank-appreciation-form');
			const exchangeId = $form.data('exchange-id');
			const toUserId = $form.data('user-id');
			const message = $form.find('[name="appreciation_message"]').val().trim();
			const tags = $form.find('[name="appreciation_tags[]"]:checked').map(function() {
				return $(this).val();
			}).get();

			if (!tags.length) {
				ZAOBank.showToast('Select at least one appreciation tag.', 'error');
				return;
			}

			if ($form.data('processing')) {
				return;
			}

			$form.data('processing', true);
			$button.prop('disabled', true).text('Sending...');

			const uniqueTags = Array.from(new Set(tags.map(function(tag) {
				return String(tag).toLowerCase().trim();
			})));

			const sendNext = function(index) {
				if (index >= uniqueTags.length) {
					ZAOBank.showToast('Appreciation sent!', 'success');
					const $card = $form.closest('.zaobank-exchange-card');
					$card.find('.zaobank-exchange-actions, .zaobank-appreciation-form').remove();
					var viewUrl = (zaobank.appreciationsUrl || '') + '?tab=given';
					$card.find('.zaobank-card-body').append(
						'<div class="zaobank-exchange-status">' +
						'<a href="' + ZAOBank.escapeHtml(viewUrl) + '" class="zaobank-btn zaobank-btn-outline zaobank-btn-sm">' +
						'<svg class="zaobank-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
						'<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>' +
						'</svg> View Appreciation</a></div>'
					);
					$form.data('processing', false);
					return;
				}

				ZAOBank.apiCall('appreciations', 'POST', {
					exchange_id: exchangeId,
					to_user_id: toUserId,
					tag_slug: uniqueTags[index],
					message: message,
					is_public: true
				}, function() {
					sendNext(index + 1);
				}, function() {
					$button.prop('disabled', false).text('Send Appreciation');
					$form.data('processing', false);
				});
			};

			sendNext(0);
		},

		handleSaveNote: function(e) {
			e.preventDefault();
			const $button = $(e.currentTarget);
			const $card = $button.closest('.zaobank-worked-with-card');
			const userId = $card.data('user-id');
			const tag = $card.find('[name="note_tag"]').val();
			const note = $card.find('[name="note_text"]').val().trim();

			if (!tag) {
				ZAOBank.showToast('Select a note tag first.', 'error');
				return;
			}

			$button.prop('disabled', true).text('Saving...');

			this.apiCall('me/notes', 'POST', {
				subject_user_id: userId,
				tag_slug: String(tag).toLowerCase().trim(),
				note: note
			}, function() {
				ZAOBank.showToast('Note saved', 'success');
				$button.prop('disabled', false).text('Save Note');
				$card.find('[data-role="latest-note-tag"]').text(ZAOBank.formatTagLabel(tag));
				$card.find('[data-role="latest-note-text"]').text(note ? note : '');
				$card.find('[data-role="latest-note-wrapper"]').removeAttr('hidden');
				$card.find('[name="note_text"]').val('');
			}, function() {
				$button.prop('disabled', false).text('Save Note');
			});
		},

		// =========================================================================
		// Flag Content
		// =========================================================================

		handleFlagContent: function(e) {
			e.preventDefault();
			const $button = $(e.currentTarget);
			const itemType = String($button.data('item-type') || '').toLowerCase();
			const itemId = parseInt($button.data('item-id'), 10) || 0;

			if (!itemType || !itemId) {
				this.showToast('Unable to open report form for this item.', 'error');
				return;
			}

			this.openFlagDrawer({
				itemType: itemType,
				itemId: itemId,
				flaggedUserId: itemType === 'user' ? itemId : 0,
				triggerSelector: this.getFlagTriggerSelector($button)
			});
		},

		handleFlagReportSubmit: function(e) {
			e.preventDefault();
			const $form = $(e.currentTarget);
			const $submit = $form.find('[type="submit"]');
			const $reason = $form.find('[name="reason_slug"]');
			const $note = $form.find('[name="context_note"]');

			const itemType = String($form.find('[name="flagged_item_type"]').val() || '').toLowerCase();
			const itemId = parseInt($form.find('[name="flagged_item_id"]').val(), 10) || 0;
			const flaggedUserId = parseInt($form.find('[name="flagged_user_id"]').val(), 10) || 0;
			const reasonSlug = String($reason.val() || '').trim();
			const contextNote = String($note.val() || '').trim();

			if (!reasonSlug) {
				this.showToast('Please select a reason.', 'error');
				$reason.trigger('focus');
				return;
			}

			$submit.prop('disabled', true).text('Sending report...');

			const payload = {
				flagged_item_type: itemType,
				flagged_item_id: itemId,
				reason_slug: reasonSlug,
				context_note: contextNote
			};

			if (flaggedUserId > 0) {
				payload.flagged_user_id = flaggedUserId;
			}

			this.apiCall('flags', 'POST', payload, function() {
				ZAOBank.closeFlagDrawer();
				ZAOBank.markFlagTriggerSubmitted();
				ZAOBank.showToast('Report sent. A moderator will review it shortly.', 'success');
				ZAOBank.forceRefresh('report_submitted');
			}, function() {
				$submit.prop('disabled', false).text('Send Report');
			});
		},

		handleCloseFlagDrawer: function(e) {
			e.preventDefault();
			this.closeFlagDrawer();
		},

		handleGlobalKeydown: function(e) {
			if (e.key === 'Escape' && $('.zaobank-report-drawer').hasClass('open')) {
				e.preventDefault();
				this.closeFlagDrawer();
			}
		},

		ensureFlagDrawer: function() {
			if ($('#zaobank-report-drawer').length) {
				return;
			}

			const reasons = this.getFlagReasons();
			const reasonOptions = reasons.map(function(reason) {
				return '<option value="' + ZAOBank.escapeHtml(reason.slug) + '">' +
					ZAOBank.escapeHtml(reason.label) +
					'</option>';
			}).join('');

			const autoHideMessage = zaobank.autoHideFlagged
				? 'If it meets threshold rules, the content may be temporarily hidden while review is in progress.'
				: 'Content will remain visible while moderators investigate.';

			const html = [
				'<div class="zaobank-report-drawer-overlay" hidden></div>',
				'<aside id="zaobank-report-drawer" class="zaobank-report-drawer" hidden role="dialog" aria-modal="true" aria-labelledby="zaobank-report-title">',
				'  <div class="zaobank-report-drawer-header">',
				'    <h2 id="zaobank-report-title">Report Content</h2>',
				'    <button type="button" class="zaobank-btn zaobank-btn-ghost zaobank-btn-sm" data-action="flag-drawer-close" aria-label="Close report form">',
				'      <svg class="zaobank-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">',
				'        <line x1="18" y1="6" x2="6" y2="18"></line>',
				'        <line x1="6" y1="6" x2="18" y2="18"></line>',
				'      </svg>',
				'    </button>',
				'  </div>',
				'  <div class="zaobank-report-drawer-body">',
				'    <div class="zaobank-report-drawer-instructions">',
				'      <p>Your report goes to a human moderation team. We review each report and decide whether to remove or restore content.</p>',
				'      <p>' + this.escapeHtml(autoHideMessage) + '</p>',
				'    </div>',
				'    <form id="zaobank-flag-report-form" class="zaobank-form">',
				'      <input type="hidden" name="flagged_item_type" value="">',
				'      <input type="hidden" name="flagged_item_id" value="0">',
				'      <input type="hidden" name="flagged_user_id" value="0">',
				'      <div class="zaobank-form-group">',
				'        <label class="zaobank-label zaobank-required" for="zaobank-flag-reason">Select a reason</label>',
				'        <select class="zaobank-select" id="zaobank-flag-reason" name="reason_slug" required>',
				'          <option value="">Choose a reason</option>',
				reasonOptions,
				'        </select>',
				'      </div>',
				'      <div class="zaobank-form-group">',
				'        <label class="zaobank-label" for="zaobank-flag-context">Additional details (optional)</label>',
				'        <textarea class="zaobank-textarea" id="zaobank-flag-context" name="context_note" rows="4" placeholder="Add context that will help moderators review this report."></textarea>',
				'      </div>',
				'      <div class="zaobank-form-actions">',
				'        <button type="button" class="zaobank-btn zaobank-btn-outline" data-action="flag-drawer-cancel">Cancel</button>',
				'        <button type="submit" class="zaobank-btn zaobank-btn-primary">Send Report</button>',
				'      </div>',
				'    </form>',
				'  </div>',
				'</aside>'
			].join('');

			$('body').append(html);
		},

		openFlagDrawer: function(config) {
			this.ensureFlagDrawer();

			this.state.reporting.itemType = config.itemType;
			this.state.reporting.itemId = config.itemId;
			this.state.reporting.flaggedUserId = config.flaggedUserId || 0;
			this.state.reporting.triggerSelector = config.triggerSelector || '';

			const $drawer = $('#zaobank-report-drawer');
			const $overlay = $('.zaobank-report-drawer-overlay');
			const $form = $('#zaobank-flag-report-form');

			$form.find('[name="flagged_item_type"]').val(config.itemType);
			$form.find('[name="flagged_item_id"]').val(config.itemId);
			$form.find('[name="flagged_user_id"]').val(config.flaggedUserId || 0);
			$form.find('[name="reason_slug"]').val('');
			$form.find('[name="context_note"]').val('');
			$form.find('[type="submit"]').prop('disabled', false).text('Send Report');

			$overlay.removeAttr('hidden').addClass('open');
			$drawer.removeAttr('hidden').addClass('open');
			$('body').addClass('zaobank-report-drawer-open');

			setTimeout(function() {
				$form.find('[name="reason_slug"]').trigger('focus');
			}, 50);
		},

		closeFlagDrawer: function() {
			const $drawer = $('#zaobank-report-drawer');
			const $overlay = $('.zaobank-report-drawer-overlay');

			$overlay.removeClass('open').attr('hidden', '');
			$drawer.removeClass('open').attr('hidden', '');
			$('body').removeClass('zaobank-report-drawer-open');
		},

		getFlagReasons: function() {
			const rawReasons = Array.isArray(zaobank.flagReasons) ? zaobank.flagReasons : [];
			const parsed = rawReasons.map(function(reason) {
				if (reason && typeof reason === 'object') {
					const slug = String(reason.slug || '').trim();
					const label = String(reason.label || '').trim();
					if (slug && label) {
						return { slug: slug, label: label };
					}
				}

				const raw = String(reason || '').trim();
				if (!raw) {
					return null;
				}

				const slug = raw
					.toLowerCase()
					.replace(/[^a-z0-9]+/g, '-')
					.replace(/^-+|-+$/g, '');

				return {
					slug: slug,
					label: this.formatTagLabel(raw)
				};
			}.bind(this)).filter(Boolean);

			if (parsed.length) {
				return parsed;
			}

			return [
				{ slug: 'inappropriate-content', label: 'Inappropriate Content' },
				{ slug: 'harassment', label: 'Harassment' },
				{ slug: 'spam', label: 'Spam' },
				{ slug: 'safety-concern', label: 'Safety Concern' },
				{ slug: 'other', label: 'Other' }
			];
		},

		getFlagTriggerSelector: function($button) {
			const itemType = String($button.data('item-type') || '').toLowerCase();
			const itemId = parseInt($button.data('item-id'), 10) || 0;
			return '.zaobank-flag-content[data-item-type="' + itemType + '"][data-item-id="' + itemId + '"]';
		},

		markFlagTriggerSubmitted: function() {
			const selector = this.state.reporting.triggerSelector;
			if (!selector) {
				return;
			}

			$(selector).each(function() {
				const $button = $(this);
				$button.prop('disabled', true).addClass('is-reported').attr('aria-disabled', 'true');
				if ($button.is('button')) {
					$button.text('Reported');
				}
			});
		},

		forceRefresh: function(reason) {
			const url = new URL(window.location.href);
			url.searchParams.set('zaobank_refresh', Date.now().toString());
			if (reason) {
				url.searchParams.set('zaobank_reason', reason);
			}
			window.location.replace(url.toString());
		},

		// =========================================================================
		// Tabs
		// =========================================================================

		handleTabClick: function(e) {
			const $tab = $(e.currentTarget);
			const tabId = $tab.data('tab') || $tab.data('filter');
			const $container = $tab.closest('.zaobank-container');
			if (!tabId) {
				return;
			}

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

			// Handle community tabs
			if ($container.data('component') === 'community') {
				if (tabId === 'address-book') {
					this.setAddressBookTab(this.state.community.addressTab || 'worked-with');
					if (this.state.community.addressTab === 'saved') {
						this.loadSavedProfiles(true);
					} else {
						this.loadWorkedWith();
					}
				} else if (tabId === 'community') {
					this.state.community.page = 1;
					this.loadCommunity(false);
				}
			}
		},

		// =========================================================================
		// Regions
		// =========================================================================

		loadRegions: function() {
			const $selects = $('[name="region"], [name="user_primary_region"], [data-filter="region"], [data-community-filter="region"]');
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

			// Regex fragment: match content that does NOT contain nested block openers
			// This ensures we only process innermost blocks each pass
			var noNest = '(?:(?!\\{\\{#(?:if|unless|each)\\s)[\\s\\S])*?';

			var reEach = new RegExp('\\{\\{#each\\s+(\\w+(?:\\.\\w+)*)\\}\\}(' + noNest + ')\\{\\{\\/each\\}\\}', 'g');
			var reIf = new RegExp('\\{\\{#if\\s+(\\w+(?:\\.\\w+)*)\\}\\}(' + noNest + ')\\{\\{\\/if\\}\\}', 'g');
			var reUnless = new RegExp('\\{\\{#unless\\s+(\\w+(?:\\.\\w+)*)\\}\\}(' + noNest + ')\\{\\{\\/unless\\}\\}', 'g');

			// Process block helpers iteratively (innermost first) to handle nesting
			var changed = true;
			var maxPasses = 10;
			while (changed && maxPasses-- > 0) {
				changed = false;
				var prev = html;

				// Handle {{#each key}}...{{/each}}
				reEach.lastIndex = 0;
				html = html.replace(reEach, function(match, key, content) {
					changed = true;
					const arr = ZAOBank.getNestedValue(data, key);
					if (!Array.isArray(arr)) return '';
					return arr.map(function(item) {
						var result = content;
						if (typeof item === 'object' && item !== null) {
							result = result.replace(/\{\{this\.(\w+)\}\}/g, function(m, prop) {
								return item[prop] !== undefined ? ZAOBank.escapeHtml(String(item[prop])) : '';
							});
						}
						result = result.replace(/\{\{this\}\}/g, ZAOBank.escapeHtml(String(item)));
						return result;
					}).join('');
				});

				// Handle {{#if key}}...{{else}}...{{/if}} and {{#if key}}...{{/if}}
				reIf.lastIndex = 0;
				html = html.replace(reIf, function(match, key, content) {
					changed = true;
					const value = ZAOBank.getNestedValue(data, key);
					const parts = content.split('{{else}}');
					if (value && (!Array.isArray(value) || value.length > 0)) {
						return parts[0];
					}
					return parts.length > 1 ? parts[1] : '';
				});

				// Handle {{#unless key}}...{{else}}...{{/unless}} and {{#unless key}}...{{/unless}}
				reUnless.lastIndex = 0;
				html = html.replace(reUnless, function(match, key, content) {
					changed = true;
					const value = ZAOBank.getNestedValue(data, key);
					const parts = content.split('{{else}}');
					if (!value || (Array.isArray(value) && value.length === 0)) {
						return parts[0];
					}
					return parts.length > 1 ? parts[1] : '';
				});

				if (html === prev) changed = false;
			}

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

		parseDate: function(dateString) {
			if (!dateString) return new Date(0);
			return new Date(String(dateString).replace(' ', 'T'));
		},

		formatTagLabel: function(tag) {
			if (!tag) return '';
			return String(tag)
				.replace(/[_-]+/g, ' ')
				.replace(/\b\w/g, function(char) {
					return char.toUpperCase();
				});
		},

		getAppreciationTags: function() {
			const rawTags = Array.isArray(zaobank.appreciationTags) ? zaobank.appreciationTags : [];
			const uniqueTags = Array.from(new Set(rawTags.map(function(tag) {
				return String(tag);
			})));
			return uniqueTags.map(function(tag) {
				return {
					slug: String(tag),
					label: ZAOBank.formatTagLabel(tag)
				};
			});
		},

		getPrivateNoteTags: function() {
			const rawTags = Array.isArray(zaobank.privateNoteTags) ? zaobank.privateNoteTags : [];
			const uniqueTags = Array.from(new Set(rawTags.map(function(tag) {
				return String(tag);
			})));
			return uniqueTags.map(function(tag) {
				return {
					slug: String(tag),
					label: ZAOBank.formatTagLabel(tag)
				};
			});
		},

		getQueryParam: function(key) {
			const params = new URLSearchParams(window.location.search || '');
			const value = params.get(key);
			return value ? value : '';
		},

		updateUnreadBadge: function(delta) {
			const $badges = $('.zaobank-header-badge, .zaobank-nav-badge');
			if (!$badges.length || !delta) return;

			$badges.each(function() {
				const $badge = $(this);
				const rawCount = $badge.attr('data-unread-count');
				let current = rawCount ? parseInt(rawCount, 10) : parseInt($badge.text().replace('+', ''), 10);

				if (isNaN(current)) return;

				const next = Math.max(0, current + delta);
				if (next <= 0) {
					$badge.remove();
					return;
				}

				$badge.attr('data-unread-count', next);
				$badge.text(next > 99 ? '99+' : next);
			});
		},

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
			const date = this.parseDate(dateString);
			if (yearOnly) {
				return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short' });
			}
			return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
		},

		formatTime: function(dateString) {
			if (!dateString) return '';
			const date = this.parseDate(dateString);
			return date.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
		},

		formatRelativeTime: function(dateString) {
			if (!dateString) return '';
			const date = this.parseDate(dateString);
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

		// =========================================================================
		// Moderation Dashboard
		// =========================================================================

		initModeration: function($container) {
			var view = $container.data('view') || 'users';

			if (view === 'users') {
				this.loadModUsers();
			} else if (view === 'flags') {
				this.loadModFlags();
			} else if (view === 'settings') {
				this.loadModSettings();
			}

			// Mark mod alerts as read when visiting moderation page
			if (zaobank.hasModAccess && zaobank.modUnreadCount > 0) {
				this.apiCall('moderation/alerts/read', 'POST', {}, function() {
					$('.zaobank-mod-badge').remove();
				});
			}
		},

		loadModUsers: function(append) {
			var $list = $('.zaobank-moderation-users-list');
			var $loadMore = $('[data-target="users"]');
			var $empty = $('[data-empty="mod-users"]');
			var self = this;

			if (!append) {
				this.state.moderation.usersPage = 1;
				$list.attr('data-loading', 'true').html(
					'<div class="zaobank-loading-state"><div class="zaobank-spinner"></div><p>Loading users...</p></div>'
				);
				$empty.hide();
			}

			var params = {
				page: this.state.moderation.usersPage,
				per_page: 20,
				q: this.state.moderation.usersFilters.q,
				role: this.state.moderation.usersFilters.role,
			};

			this.apiCall('moderation/users', 'GET', params, function(response) {
				$list.attr('data-loading', 'false');
				var html = '';

				if (response.users && response.users.length) {
					html = response.users.map(function(user) {
						return self.renderModUserCard(user);
					}).join('');
				}

				if (append) {
					$list.append(html);
				} else {
					$list.html(html);
				}

				self.state.moderation.usersTotalPages = response.pages || 1;

				if (self.state.moderation.usersPage < self.state.moderation.usersTotalPages) {
					$loadMore.show();
				} else {
					$loadMore.hide();
				}

				if (!response.users || !response.users.length) {
					if (!append) {
						$list.empty();
						$empty.show();
					}
				} else {
					$empty.hide();
				}
			});
		},

		renderModUserCard: function(user) {
			var template = $('#zaobank-mod-user-card-template').html();
			if (!template) return '';

			var isAdmin = user.role === 'administrator';

			return this.renderTemplate(template, {
				id: user.id,
				display_name: this.escapeHtml(user.display_name),
				email: this.escapeHtml(user.email),
				avatar_url: user.avatar_url,
				registered_date: this.formatDate(user.user_registered),
				role: user.role,
				flag_count: user.flag_count || 0,
				is_member: user.role === 'member',
				is_limited: user.role === 'member_limited',
				is_leadership: user.role === 'leadership_team',
				is_admin: isAdmin,
				show_leadership: !isAdmin
			});
		},

		loadModFlags: function(append) {
			var $list = $('.zaobank-moderation-flags-list');
			var $loadMore = $('[data-target="flags"]');
			var $empty = $('[data-empty="mod-flags"]');
			var self = this;

			if (!append) {
				this.state.moderation.flagsPage = 1;
				$list.attr('data-loading', 'true').html(
					'<div class="zaobank-loading-state"><div class="zaobank-spinner"></div><p>Loading flags...</p></div>'
				);
				$empty.hide();
			}

			var params = {
				page: this.state.moderation.flagsPage,
				per_page: 20,
				status: this.state.moderation.flagsFilters.status,
				type: this.state.moderation.flagsFilters.type,
			};

			this.apiCall('moderation/flags', 'GET', params, function(response) {
				$list.attr('data-loading', 'false');
				var html = '';

				if (response.flags && response.flags.length) {
					html = response.flags.map(function(flag) {
						return self.renderModFlagCard(flag);
					}).join('');
				}

				if (append) {
					$list.append(html);
				} else {
					$list.html(html);
				}

				self.state.moderation.flagsTotalPages = response.pages || 1;

				if (self.state.moderation.flagsPage < self.state.moderation.flagsTotalPages) {
					$loadMore.show();
				} else {
					$loadMore.hide();
				}

				if (!response.flags || !response.flags.length) {
					if (!append) {
						$list.empty();
						$empty.show();
					}
				} else {
					$empty.hide();
				}
			});
		},

		renderModFlagCard: function(flag) {
			var template = $('#zaobank-mod-flag-card-template').html();
			if (!template) return '';

			var statusLabels = {
				'open': 'Open',
				'under_review': 'Under Review',
				'resolved': 'Resolved',
				'removed': 'Removed',
				'restored': 'Restored'
			};

			var canVerify = flag.status === 'open';
			var canResolve = flag.status === 'open' || flag.status === 'under_review';
			var canDelete = flag.status !== 'removed';
			var canRestore = flag.status !== 'restored';

			return this.renderTemplate(template, {
				id: flag.id,
				flagged_item_type: flag.flagged_item_type,
				flagged_item_id: flag.flagged_item_id,
				flagged_user_id: flag.flagged_user_id || '',
				flagged_user_name: flag.flagged_user_name || '',
				reporter_name: flag.reporter_name || '',
				reason_label: flag.reason_label || flag.reason_slug,
				context_note: flag.context_note ? this.escapeHtml(flag.context_note) : '',
				item_preview: flag.item_preview ? this.escapeHtml(flag.item_preview) : '',
				status: flag.status,
				status_label: statusLabels[flag.status] || flag.status,
				status_class: flag.status,
				created_at: this.formatDate(flag.created_at),
				resolution_note: flag.resolution_note ? this.escapeHtml(flag.resolution_note) : '',
				can_review: canVerify,
				can_delete: canDelete,
				can_restore: canRestore,
				can_resolve: canResolve
			});
		},

		loadModSettings: function() {
			var self = this;
			this.apiCall('moderation/settings', 'GET', {}, function(response) {
				$('[data-setting="auto_downgrade_threshold"]').val(response.auto_downgrade_threshold || 3);
				$('[data-setting="flag_threshold"]').val(response.flag_threshold || 1);
				$('[data-setting="auto_hide_flagged"]').prop('checked', response.auto_hide_flagged);
			});
		},

		// Moderation filter handlers
		handleModSearchChange: function(e) {
			this.state.moderation.usersFilters.q = $(e.currentTarget).val();
			this.loadModUsers();
		},

		handleModRoleFilterChange: function(e) {
			this.state.moderation.usersFilters.role = $(e.currentTarget).val();
			this.loadModUsers();
		},

		handleModFlagStatusChange: function(e) {
			this.state.moderation.flagsFilters.status = $(e.currentTarget).val();
			this.loadModFlags();
		},

		handleModFlagTypeChange: function(e) {
			this.state.moderation.flagsFilters.type = $(e.currentTarget).val();
			this.loadModFlags();
		},

		handleModLoadMoreUsers: function() {
			this.state.moderation.usersPage++;
			this.loadModUsers(true);
		},

		handleModLoadMoreFlags: function() {
			this.state.moderation.flagsPage++;
			this.loadModFlags(true);
		},

		// Role change handler
		handleModRoleChange: function(e) {
			var $select = $(e.currentTarget);
			var userId = $select.data('user-id');
			var newRole = $select.val();
			var currentRole = $select.data('current-role');

			if (newRole === currentRole) return;

			var roleLabels = {
				'member': 'Member',
				'member_limited': 'Limited',
				'leadership_team': 'Leadership'
			};
			var label = roleLabels[newRole] || newRole;

			if (!confirm('Change this user\'s role to "' + label + '"?')) {
				$select.val(currentRole);
				return;
			}

			this.apiCall('moderation/users/' + userId + '/role', 'PUT', {
				role: newRole
			}, function() {
				$select.data('current-role', newRole);
				ZAOBank.showToast('Role updated.', 'success');
			}, function() {
				$select.val(currentRole);
			});
		},

		// Flag action handlers
		handleModFlagReview: function(e) {
			var flagId = $(e.currentTarget).data('flag-id');
			var self = this;

			if (!confirm('Mark this report as "Under Review"? This records that a moderator has started a manual check.')) return;

			this.apiCall('moderation/flags/' + flagId, 'PUT', {
				action: 'under_review'
			}, function() {
				ZAOBank.showToast('Confirmed: report is now under review.', 'success');
				self.loadModFlags();
			});
		},

		handleModFlagDelete: function(e) {
			var $button = $(e.currentTarget);
			var $card = $button.closest('.zaobank-mod-flag-card');
			var flagId = $button.data('flag-id');
			var itemType = String($card.data('item-type') || 'content');
			var note = 'Removed by moderator after verification.';
			var self = this;

			var confirmText = 'Delete this flagged ' + itemType + ' from front-end visibility?\n\n' +
				'This action is logged and can be reversed with Restore if the report was incorrect.';
			if (!confirm(confirmText)) return;

			this.apiCall('moderation/flags/' + flagId, 'PUT', {
				action: 'remove',
				resolution_note: note
			}, function() {
				ZAOBank.showToast('Confirmed: content was removed and logged.', 'success');
				self.loadModFlags();
			});
		},

		handleModFlagResolveOpen: function(e) {
			var $card = $(e.currentTarget).closest('.zaobank-mod-flag-card');
			$card.find('.zaobank-mod-flag-resolve-form').removeAttr('hidden');
			$card.find('.zaobank-mod-flag-actions').hide();
		},

		handleModFlagResolveConfirm: function(e) {
			var $card = $(e.currentTarget).closest('.zaobank-mod-flag-card');
			var flagId = $card.data('flag-id');
			var note = String($card.find('.zaobank-textarea').val() || '').trim();
			var self = this;

			if (!confirm('Close this report as reviewed without deleting content?')) return;

			this.apiCall('moderation/flags/' + flagId, 'PUT', {
				action: 'resolve',
				resolution_note: note
			}, function() {
				ZAOBank.showToast('Confirmed: report closed.', 'success');
				self.loadModFlags();
			});
		},

		handleModFlagResolveCancel: function(e) {
			var $card = $(e.currentTarget).closest('.zaobank-mod-flag-card');
			$card.find('.zaobank-mod-flag-resolve-form').attr('hidden', '');
			$card.find('.zaobank-mod-flag-actions').show();
		},

		handleModFlagRestore: function(e) {
			var flagId = $(e.currentTarget).data('flag-id');
			var self = this;

			if (!confirm('Restore this content?\n\nUse this when a report was incorrect. The restore action is logged.')) return;

			this.apiCall('moderation/flags/' + flagId, 'PUT', {
				action: 'restore',
				resolution_note: 'Restored after moderator verification.'
			}, function() {
				ZAOBank.showToast('Confirmed: content was restored.', 'success');
				self.loadModFlags();
			});
		},

		handleSaveModSettings: function() {
			var settings = {
				auto_downgrade_threshold: parseInt($('[data-setting="auto_downgrade_threshold"]').val()) || 3,
				flag_threshold: parseInt($('[data-setting="flag_threshold"]').val()) || 1,
				auto_hide_flagged: $('[data-setting="auto_hide_flagged"]').is(':checked')
			};

			var $status = $('.zaobank-mod-settings-status');

			this.apiCall('moderation/settings', 'PUT', settings, function() {
				ZAOBank.showToast('Settings saved.', 'success');
				$status.text('Settings saved.').show();
				setTimeout(function() { $status.fadeOut(); }, 3000);
			});
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
