# ZAO Bank Developer Documentation

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [Frontend Templates & Shortcodes](#frontend-templates--shortcodes)
3. [Database Schema](#database-schema)
4. [REST API Reference](#rest-api-reference)
5. [Hooks & Filters](#hooks--filters)
6. [Security Considerations](#security-considerations)
7. [Extending the Plugin](#extending-the-plugin)
8. [Testing](#testing)

## Architecture Overview

### Plugin Structure

```
zaobank-core/
├── admin/                          # Admin-specific code
│   ├── class-zaobank-admin.php
│   └── partials/                   # Admin page templates
├── assets/                         # CSS, JS, images
│   ├── css/
│   │   └── zaobank-public.css     # Mobile-first responsive styles
│   └── js/
│       └── zaobank-public.js      # Component-based JavaScript
├── includes/                       # Core plugin code
│   ├── class-zaobank-*.php        # Business logic classes
│   ├── class-zaobank-shortcodes.php # Shortcode registration & rendering
│   └── rest-api/                   # REST API controllers
├── public/                         # Public-facing code
│   ├── class-zaobank-public.php
│   └── templates/                  # Shortcode templates
│       ├── dashboard.php           # User dashboard
│       ├── jobs-list.php           # Browse jobs
│       ├── job-single.php          # Job detail view
│       ├── job-form.php            # Create/edit job
│       ├── my-jobs.php             # User's jobs
│       ├── profile.php             # User profile
│       ├── profile-edit.php        # Profile edit form
│       ├── messages.php            # Conversations list
│       ├── conversation.php        # Single conversation
│       ├── exchanges.php           # Exchange history
│       ├── appreciations.php       # Appreciations list
│       └── components/
│           ├── bottom-nav.php      # Mobile bottom navigation
│           └── subpage-tabs.php   # Reusable subpage tab navigation
├── languages/                      # Translation files
├── zaobank-core.php               # Main plugin file + theme template tags
├── uninstall.php                  # Cleanup on uninstall
├── README.md
└── DEVELOPER.md                   # This file
```

### Design Patterns

1. **Separation of Concerns**: Business logic in `includes/`, presentation in `admin/` and `public/`
2. **REST-First**: All data operations accessible via REST API
3. **Immutable Records**: Exchanges are never modified, only created
4. **Derived State**: Balances calculated from exchanges, never stored
5. **Privacy by Design**: Private notes are strictly scoped, never aggregated
6. **Theme Override System**: Templates can be overridden in themes without modifying plugin
7. **Template Tags**: Global helper functions for easy theme integration

### Data Flow

```
User Action → REST API → Security Check → Business Logic → Database → Response
```

## Frontend Templates & Shortcodes

### Overview

The plugin provides a complete mobile-first frontend using shortcodes. Templates are REST API powered with progressive enhancement.
Frontend assets are versioned with `filemtime()` in `public/class-zaobank-public.php`, so script/style updates invalidate browser caches without manual plugin version bumps.

### Template Override System

Themes can override any plugin template by placing a copy in the theme directory. The `locate_template()` method checks these locations in order:

```php
// Template lookup order (first match wins)
1. {child-theme}/zaobank/templates/{template}.php
2. {child-theme}/zaobank/{template}.php
3. {child-theme}/app/{template}.php
4. {parent-theme}/zaobank/templates/{template}.php
5. {parent-theme}/zaobank/{template}.php
6. {parent-theme}/app/{template}.php
7. {plugin}/public/templates/{template}.php
```

#### Checking for Theme Overrides

```php
$shortcodes = ZAOBank_Shortcodes::instance();

// Check if theme has an override
if ($shortcodes->has_theme_override('dashboard')) {
    // Theme is using custom dashboard template
}

// Get paths for a template
$paths = ZAOBank_Shortcodes::get_template_paths('dashboard');
// Returns: ['theme_path' => '...', 'plugin_path' => '...']
```

#### Adding Custom Template Paths

```php
add_filter('zaobank_template_paths', function($paths, $template_name) {
    // Add custom location at highest priority
    array_unshift($paths,
        get_stylesheet_directory() . '/custom-zaobank/' . $template_name . '.php'
    );
    return $paths;
}, 10, 2);
```

### Shortcode Reference

| Shortcode | Template File | Description | Auth Required |
|-----------|---------------|-------------|---------------|
| `[zaobank_dashboard]` | `dashboard.php` | User dashboard with balance, stats, activity | Yes |
| `[zaobank_jobs]` | `jobs-list.php` or `job-single.php` | Browse available jobs; renders single job view when `?job_id=` is in the URL | No |
| `[zaobank_job id="X"]` | `job-single.php` | Single job detail view (standalone) | No |
| `[zaobank_job_form]` | `job-form.php` | Create/edit job form | Yes |
| `[zaobank_my_jobs]` | `my-jobs.php` | User's posted and claimed jobs | Yes |
| `[zaobank_community]` | `community.php` | Community directory and worked-with history | Yes |
| `[zaobank_profile]` | `profile.php` | User profile (own or other) | Partial |
| `[zaobank_profile_edit]` | `profile-edit.php` | Profile edit form | Yes |
| `[zaobank_messages]` | `messages.php` or `conversation.php` | Conversations list; delegates to conversation view on `?user_id=`, job updates view on `?view=updates` | Yes |
| `[zaobank_more]` | `more.php` | More menu (messages, job notifications, profile edit shortcuts) | Yes |
| `[zaobank_conversation user_id="X"]` | `conversation.php` | Single conversation thread (standalone) | Yes |
| `[zaobank_exchanges]` | `exchanges.php` | Exchange history | Yes |
| `[zaobank_appreciations]` | `appreciations.php` | Appreciations received/given | Partial |

### Shortcode Parameters

#### `[zaobank_jobs]`
- `region` (int): Filter by region ID
- `status` (string): Filter by status (default: "available")
- **URL routing**: When `?job_id=X` is present in the URL, this shortcode automatically delegates to `[zaobank_job]` and renders the single job view instead of the list. This allows one page to handle both the jobs list and individual job views without needing a separate page for `[zaobank_job]`.

#### `[zaobank_job]`
- `id` (int): Job ID. Also accepts `?job_id=X` URL parameter.
- Can be used standalone on a dedicated page, or accessed automatically through `[zaobank_jobs]` via the URL parameter routing described above.

#### `[zaobank_job_form]`
- `id` (int): Job ID for edit mode. Also accepts `?job_id=X` URL parameter.

#### `[zaobank_profile]`
- `user_id` (int): User to display. Defaults to current user. Also accepts `?user_id=X`.

#### `[zaobank_community]`
- `view` (string): Optional view override (`community` or `worked-with`). Defaults to `community`.

#### `[zaobank_messages]`
- **URL routing**: When `?user_id=X` is present, delegates to `[zaobank_conversation]` and renders the conversation view. When `?view=updates` is present, shows the job updates view instead of the conversations list. This allows one page to handle conversations list, individual conversations, and job notifications.

#### `[zaobank_more]`
- `view` (string): Optional view override (`messages` or `updates`). Defaults to `messages`, and also supports `?view=updates` in the URL.

#### `[zaobank_conversation]`
- `user_id` (int): Other user in conversation. Also accepts `?user_id=X`.

#### `[zaobank_appreciations]`
- `user_id` (int): User whose appreciations to show. Defaults to current user.

### Template Architecture

Templates use a component-based architecture with data attributes:

```html
<!-- Container declares component type -->
<div class="zaobank-container" data-component="jobs-list">

    <!-- Loading states use data-loading -->
    <div class="zaobank-jobs-list" data-loading="true">
        <div class="zaobank-loading-state">...</div>
    </div>

    <!-- Empty states hidden by default -->
    <div class="zaobank-empty-state" style="display: none;">...</div>

</div>

<!-- Templates embedded in page -->
<script type="text/template" id="zaobank-job-card-template">
    <article class="zaobank-card" data-job-id="{{id}}">
        <h3>{{title}}</h3>
        {{#if location}}<p>{{location}}</p>{{/if}}
    </article>
</script>
```

### JavaScript Component System

Components are auto-initialized based on `data-component` attribute:

```javascript
// Component registration in zaobank-public.js
const components = {
    'dashboard': this.initDashboard,
    'jobs-list': this.initJobsList,
    'job-single': this.initJobSingle,
    // ... etc
};

$('[data-component]').each(function() {
    const component = $(this).data('component');
    if (components[component]) {
        components[component].call(ZAOBank, $(this));
    }
});
```

### Template Rendering

Simple Handlebars-like template syntax:

```javascript
// Simple variable
{{variable_name}}

// Conditionals
{{#if has_value}}Show this{{/if}}
{{#unless is_empty}}Show if not empty{{/unless}}

// Loops (primitives)
{{#each items}}
    <span>{{this}}</span>
{{/each}}

// Loops (objects)
{{#each job_types}}
    <span>{{this.name}}</span>
{{/each}}

// Nested properties
{{user.name}}
```

**Important**: The template engine does **not** support `{{else}}`. Use separate `{{#if}}` and `{{#unless}}` blocks instead:

```handlebars
{{!-- WRONG: {{else}} is not supported --}}
{{#if is_own}}Edit{{else}}Message{{/if}}

{{!-- CORRECT: use separate blocks --}}
{{#if is_own}}Edit{{/if}}
{{#unless is_own}}Message{{/unless}}
```

Usage:

```javascript
ZAOBank.renderTemplate(templateHtml, {
    title: 'Job Title',
    hours: 2.5,
    location: 'Downtown',
    skills: ['gardening', 'lifting'],
    job_types: [{ id: 1, name: 'Gardening', slug: 'gardening' }]
});
```

### CSS Architecture

Mobile-first responsive design with CSS custom properties:

```css
/* Design tokens */
:root {
    --zaobank-primary: #2271b1;
    --zaobank-touch-target: 44px;
    --zaobank-bottom-nav-height: 64px;
}

/* Mobile base (default) */
.zaobank-btn {
    min-height: var(--zaobank-touch-target);
    width: 100%;
}

/* Tablet (768px+) */
@media (min-width: 768px) {
    .zaobank-jobs-list {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Desktop (1024px+) */
@media (min-width: 1024px) {
    .zaobank-bottom-nav { display: none; }
    .zaobank-jobs-list {
        grid-template-columns: repeat(3, 1fr);
    }
}
```

### Bottom Navigation

Fixed bottom navigation for mobile/tablet. Hidden on desktop (1024px+).

```php
// In shortcode templates (within plugin context)
include ZAOBANK_PLUGIN_DIR . 'public/templates/components/bottom-nav.php';

// In theme templates (recommended)
zaobank_bottom_nav();
```

Navigation items:
1. **Home** - Dashboard
2. **Jobs** - Browse jobs
3. **New** (center, prominent) - Create job
4. **Messages** - With unread badge
5. **Profile** - User profile

Active state determined by current URL matching page slugs.

### Subpage Tabs

Reusable tab navigation for sub-sections within a feature area. Used on the jobs pages (jobs-list, my-jobs, job-form) and the messages/exchanges pages (messages, exchanges, job updates).

```php
// Set up tabs array before including the component
$tabs = array(
    array('label' => __('all jobs', 'zaobank'), 'url' => $urls['jobs'], 'current' => true),
    array('label' => __('my jobs', 'zaobank'), 'url' => $urls['my_jobs']),
    array('label' => __('post a job', 'zaobank'), 'url' => $urls['job_form']),
);
include ZAOBANK_PLUGIN_DIR . 'public/templates/components/subpage-tabs.php';
```

Each tab item supports:
- `label` (string, required): Display text
- `url` (string, required): Link URL
- `current` (bool, optional): Whether this tab is the active page

The component renders a `<nav class="zaobank-subpage-tabs">` element with `role="tablist"`.

### Page URL Configuration

Default page slugs (filterable):

```php
add_filter('zaobank_page_slugs', function($slugs) {
    // Default slugs
    return array(
        'dashboard' => 'timebank-dashboard',
        'jobs' => 'timebank-jobs',
        'job_form' => 'timebank-new-job',
        'my_jobs' => 'timebank-my-jobs',
        'community' => 'timebank-community',
        'profile' => 'timebank-profile',
        'profile_edit' => 'timebank-profile-edit',
        'messages' => 'timebank-messages',
        'exchanges' => 'timebank-exchanges',
        'appreciations' => 'timebank-appreciations'
    );
});
```

Get URLs in templates:

```php
$urls = ZAOBank_Shortcodes::get_page_urls();
echo $urls['dashboard']; // Full URL to dashboard page
```

**ZAO Theme Convenience:** `app/page-app.php` supports a hybrid pattern. If a page has a ZAOBank shortcode, it renders the content normally. If the content is empty and the page slug matches a configured ZAOBank page (e.g., `/app/community`), it auto-renders the matching ZAOBank template. This keeps shortcode support while allowing theme-level template control.

### Toast Notifications

Show user feedback:

```javascript
// Success
ZAOBank.showToast('Job posted successfully!', 'success');

// Error
ZAOBank.showToast('Something went wrong', 'error');

// Info (default)
ZAOBank.showToast('Loading...');
```

### Unread Message Badge

Get unread count:

```php
$unread = ZAOBank_Shortcodes::get_unread_message_count();
```

### Theme Template Tags (Helper Functions)

The plugin provides global functions for use in theme templates:

#### Navigation & URLs

```php
// Get all page URLs
$urls = zaobank_get_urls();
echo $urls['dashboard'];     // Full URL to dashboard
echo $urls['jobs'];          // Full URL to jobs list
echo $urls['messages'];      // Full URL to messages

// Check if on app section
if (zaobank_is_app_section('app')) {
    get_header('app');
} else {
    get_header();
}

// Get current template name
$template = zaobank_current_template();
// Returns: 'dashboard', 'jobs-list', 'profile', etc. or false
```

#### User Data

```php
// Get current user's time balance
$balance = zaobank_user_balance();  // float, e.g., 12.5

// Get unread message count
$unread = zaobank_unread_count();   // int, e.g., 3
```

#### Template Rendering

```php
// Render template directly (outputs HTML)
// Use when auth is handled externally (e.g., AAM)
zaobank_render_template('dashboard');
zaobank_render_template('jobs-list', ['region' => 5]);

// Get template as string
$html = zaobank_get_template('dashboard');

// Output bottom navigation
zaobank_bottom_nav();

// Get list of available templates
$templates = zaobank_available_templates();
// Returns: ['dashboard' => 'User dashboard...', 'jobs-list' => '...', ...]
```

#### Page Detection

```php
// Check if current page is a ZAO Bank page
if (zaobank_is_app_page()) {
    // Page contains a zaobank shortcode
}

// Check if within /app/ section (or custom parent)
if (zaobank_is_app_section()) {
    // Current page is /app/ or child of /app/
}

if (zaobank_is_app_section('timebank')) {
    // Current page is /timebank/ or child of /timebank/
}
```

#### Example: Custom App Header

```php
<?php
// header-app.php in your theme
$urls = zaobank_get_urls();
$unread = zaobank_unread_count();
?>
<header class="app-header">
    <a href="<?php echo esc_url($urls['dashboard']); ?>">Home</a>

    <a href="<?php echo esc_url($urls['messages']); ?>">
        Messages
        <?php if ($unread > 0) : ?>
            <span class="badge"><?php echo $unread; ?></span>
        <?php endif; ?>
    </a>

    <span class="balance">
        Balance: <?php echo zaobank_user_balance(); ?> hours
    </span>
</header>
```

### Creating Custom Templates

1. Create template file in `public/templates/`:

```php
<?php
// public/templates/my-custom.php
if (!defined('ABSPATH')) exit;

$urls = ZAOBank_Shortcodes::get_page_urls();
?>

<div class="zaobank-container" data-component="my-custom">
    <!-- Template content -->
</div>

<?php if (is_user_logged_in()) : ?>
    <?php include ZAOBANK_PLUGIN_DIR . 'public/templates/components/bottom-nav.php'; ?>
<?php endif; ?>
```

2. Register shortcode:

```php
add_action('init', function() {
    add_shortcode('zaobank_my_custom', function($atts) {
        if (!is_user_logged_in()) {
            return '<p>Please log in.</p>';
        }

        ob_start();
        include ZAOBANK_PLUGIN_DIR . 'public/templates/my-custom.php';
        return ob_get_clean();
    });
});
```

3. Add JavaScript component:

```javascript
// Add to components object
'my-custom': function($container) {
    // Initialize component
    this.apiCall('my-endpoint', 'GET', {}, function(response) {
        // Handle response
    });
}
```

### WordPress Page Templates

For full control over page layout, create WordPress page templates in your theme.

#### Generic App Page Template

```php
<?php
/**
 * Template Name: ZAO Bank - App Page
 * Template Post Type: page
 */

get_header('app');

while (have_posts()) :
    the_post();
    the_content();  // Renders the shortcode
endwhile;

get_footer('app');
```

#### Custom Template with Direct Rendering

Bypass shortcode auth checks when auth is handled externally (e.g., AAM):

```php
<?php
/**
 * Template Name: ZAO Bank - Dashboard
 * Template Post Type: page
 */

get_header('app');

$user = wp_get_current_user();
$urls = zaobank_get_urls();
$balance = zaobank_user_balance();
?>

<div class="dashboard-wrapper">
    <h1>Welcome, <?php echo esc_html($user->display_name); ?></h1>
    <p>Your balance: <?php echo $balance; ?> hours</p>

    <!-- Render dashboard content directly -->
    <?php zaobank_render_template('dashboard'); ?>
</div>

<?php
zaobank_bottom_nav();
get_footer('app');
```

#### Template with Custom Data

```php
<?php
/**
 * Template Name: ZAO Bank - Jobs by Region
 */

get_header('app');

// Get region from URL parameter
$region_id = isset($_GET['region']) ? absint($_GET['region']) : 0;

// Render jobs list with custom args
zaobank_render_template('jobs-list', [
    'region' => $region_id,
    'status' => 'available'
]);

zaobank_bottom_nav();
get_footer('app');
```

### App/Theme Structure Example

Recommended theme structure for /app/ integration:

```
your-theme/
├── header.php              # Main site header
├── header-app.php          # Simplified app header
├── footer.php              # Main site footer
├── footer-app.php          # App footer (includes bottom nav)
├── functions.php           # Add page slugs filter here
├── app/                    # Optional: organize app templates
│   ├── app-styles.css      # App-specific styles
│   ├── page-app.php        # Generic app page template
│   ├── page-dashboard.php  # Custom dashboard template
│   └── components/
│       └── bottom-nav.php  # Custom bottom nav override
└── zaobank/                # Alternative location for overrides
    └── templates/
        └── jobs-list.php   # Custom jobs list
```

### Localized Data

JavaScript receives these variables via `wp_localize_script`:

```javascript
// Available as global `zaobank` object
{
    restUrl: '/wp-json/zaobank/v1/',
    restNonce: 'abc123...',
    userId: 42,
    isLoggedIn: true,
    hasMemberAccess: true,
    appreciationTags: ['reliable', 'kind'],
    privateNoteTags: ['easy-to-work-with']
}
```

## Admin Settings

Settings are available under **Settings → ZAO Bank** and stored as options:

- `zaobank_enable_regions` (bool) — Enable regional filtering and organization
- `zaobank_auto_hide_flagged` (bool) — Auto-hide content after threshold
- `zaobank_flag_threshold` (int) — Flag count before auto-hide
- `zaobank_appreciation_tags` (array) — Allowed appreciation tags
- `zaobank_skill_tags` (array) — Skill tags shown in Community filters and on profiles
- `zaobank_profile_tags` (array) — Personality tags shown on profiles
- `zaobank_private_note_tags` (array) — Allowed private note tags
- `zaobank_flag_reasons` (array) — Allowed flag reasons
- `zaobank_message_search_roles` (array) — Member access roles for messaging, jobs, requests, and profile edits (legacy option key)

## Database Schema

### wp_zaobank_exchanges

Canonical record of all time exchanges (immutable).

```sql
CREATE TABLE wp_zaobank_exchanges (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    job_id bigint(20) UNSIGNED NOT NULL,
    provider_user_id bigint(20) UNSIGNED NOT NULL,
    requester_user_id bigint(20) UNSIGNED NOT NULL,
    hours decimal(10,2) NOT NULL,
    region_term_id bigint(20) UNSIGNED DEFAULT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY job_id (job_id),
    KEY provider_user_id (provider_user_id),
    KEY requester_user_id (requester_user_id)
);
```

**Important**: Never UPDATE or DELETE from this table. Exchanges are permanent records.

### wp_zaobank_user_regions

Tracks user affinity to regions for smart filtering.

```sql
CREATE TABLE wp_zaobank_user_regions (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id bigint(20) UNSIGNED NOT NULL,
    region_term_id bigint(20) UNSIGNED NOT NULL,
    affinity_score int(11) DEFAULT 0,
    last_seen_at datetime NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY user_region (user_id, region_term_id)
);
```

Affinity score increments each time a user interacts with that region.

### wp_zaobank_appreciations

Public and private appreciations.

```sql
CREATE TABLE wp_zaobank_appreciations (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    exchange_id bigint(20) UNSIGNED NOT NULL,
    from_user_id bigint(20) UNSIGNED NOT NULL,
    to_user_id bigint(20) UNSIGNED NOT NULL,
    tag_slug varchar(100) NOT NULL,
    message text,
    is_public tinyint(1) DEFAULT 0,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

### wp_zaobank_messages

1:1 messages between users, optionally linked to a specific exchange or job for context.

```sql
CREATE TABLE wp_zaobank_messages (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    exchange_id bigint(20) UNSIGNED DEFAULT NULL,
    from_user_id bigint(20) UNSIGNED NOT NULL,
    to_user_id bigint(20) UNSIGNED NOT NULL,
    message text NOT NULL,
    is_read tinyint(1) DEFAULT 0,
    message_type varchar(20) NOT NULL DEFAULT 'direct',
    job_id bigint(20) UNSIGNED DEFAULT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY exchange_id (exchange_id),
    KEY from_user_id (from_user_id),
    KEY to_user_id (to_user_id),
    KEY is_read (is_read)
);
```

**Field Notes**:
- `exchange_id`: Optional foreign key to `wp_zaobank_exchanges`. Provides context when a message relates to a specific job exchange.
- `is_read`: Set to `0` on creation. Only the recipient (`to_user_id`) can mark as read.
- `message`: Sanitized with `wp_kses_post()` to allow safe HTML.
- `message_type`: Either `'direct'` (default, user-to-user messages) or `'job_update'` (system-generated notifications for job claims, releases, completions, and appreciations).
- `job_id`: Optional foreign key to a `timebank_job` post. Set on `job_update` messages to link the notification to its job.

### wp_zaobank_private_notes

**CRITICAL PRIVACY**: Personal memory aids, never visible to anyone except author.

```sql
CREATE TABLE wp_zaobank_private_notes (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    author_user_id bigint(20) UNSIGNED NOT NULL,
    subject_user_id bigint(20) UNSIGNED NOT NULL,
    tag_slug varchar(100) NOT NULL,
    note text,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY author_user_id (author_user_id)  -- Never index on subject_user_id
);
```

**Security Rules**:
- ALWAYS query with `WHERE author_user_id = current_user`
- NEVER expose via broad API queries
- NEVER aggregate or analyze
- NEVER visible to admins

### wp_zaobank_archived_conversations

Per-user conversation archive state. When a user archives a conversation, the other user's ID is recorded here. The conversations list query excludes archived conversations for the current user.

```sql
CREATE TABLE wp_zaobank_archived_conversations (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id bigint(20) UNSIGNED NOT NULL,
    other_user_id bigint(20) UNSIGNED NOT NULL,
    archived_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY user_conversation (user_id, other_user_id)
);
```

**Notes**:
- Uses `$wpdb->replace()` so re-archiving is idempotent.
- Archiving hides the conversation from the list; messages are not deleted.
- If the other user sends a new message, the conversation reappears (not yet implemented — currently stays archived).

### wp_zaobank_flags

Content moderation flags.

```sql
CREATE TABLE wp_zaobank_flags (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    flagged_item_type varchar(50) NOT NULL,
    flagged_item_id bigint(20) UNSIGNED NOT NULL,
    flagged_user_id bigint(20) UNSIGNED DEFAULT NULL,
    reporter_user_id bigint(20) UNSIGNED NOT NULL,
    reason_slug varchar(100) NOT NULL,
    context_note text,
    status varchar(50) DEFAULT 'open',
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at datetime DEFAULT NULL,
    reviewer_user_id bigint(20) UNSIGNED DEFAULT NULL,
    resolution_note text,
    PRIMARY KEY (id)
);
```

## REST API Reference

### Base URL
```
/wp-json/zaobank/v1/
```

### Authentication
All authenticated endpoints require the `X-WP-Nonce` header:

```javascript
fetch('/wp-json/zaobank/v1/jobs', {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
})
```

### Jobs Endpoints

#### GET /jobs
List all available jobs.

**Parameters**:
- `page` (int): Page number (default: 1)
- `per_page` (int): Items per page (default: 20, max: 100)
- `region` (int): Filter by region ID
- `status` (string): Filter by status (available|claimed|completed)
- `job_types` (array of int): Filter by job type term IDs

**Response**:
```json
{
    "jobs": [...],
    "total": 42,
    "pages": 3
}
```

#### GET /jobs/mine
List jobs posted or claimed by the current user (authenticated).

**Parameters**:
- `type` (string): `posted` or `claimed` (defaults to `posted`)
- `include_completed` (bool): Include completed jobs in the response (default: `false`)

**Response**:
```json
{
    "jobs": [...],
    "total": 7
}
```

#### POST /jobs
Create a new job (authenticated).

**Body**:
```json
{
    "title": "string (required)",
    "description": "string",
    "hours": 2.5,  // number (required)
    "location": "string",
    "virtual_ok": true,
    "regions": [1, 2],  // array of region IDs
    "job_types": [3, 7],  // array of job type term IDs
    "skills_required": "string",
    "preferred_date": "2024-05-15",
    "flexible_timing": true
}
```

#### POST /jobs/{id}/claim
Claim a job (authenticated).

**Response**:
```json
{
    "message": "Job claimed successfully",
    "job": {...}
}
```

#### POST /jobs/{id}/complete
Complete a job and record exchange (authenticated, job owner only).

**Body** (optional):
```json
{
    "hours": 3.0
}
```

- `hours` (float, optional): Override the job's estimated hours with the actual hours worked. If omitted, uses the original job hours.

**Response**:
```json
{
    "message": "Job completed successfully",
    "job": {...},
    "exchange": {...}
}
```

A `job_update` message is automatically sent to the provider notifying them of the completion and hours credited.
The `job` payload includes `exchange_id` once completed to link into appreciation flows.

#### POST /jobs/{id}/release
Release a claimed job back to available status (authenticated, provider only).

**Body** (optional):
```json
{
    "reason": "Schedule conflict, unable to complete"
}
```

- `reason` (string, optional): Reason for releasing the job.

**Response**:
```json
{
    "message": "Job released successfully",
    "job": {...}
}
```

A `job_update` message is automatically sent to the job owner with the release reason.

### Job Types Endpoints

#### GET /job-types
List all job type terms (public, no auth required).

**Response**:
```json
[
    {
        "id": 3,
        "name": "Gardening",
        "slug": "gardening",
        "count": 5
    }
]
```

### User Endpoints

#### GET /me/balance
Get current user's time balance (authenticated).

**Response**:
```json
{
    "hours_earned": 10.5,
    "hours_spent": 8.0,
    "balance": 2.5
}
```

#### GET /me/exchanges
Get current user's exchange history (authenticated).

**Parameters**:
- `filter` (string, optional): `all` (default), `earned`, or `spent`

**Response**:
```json
{
    "exchanges": [
        {
            "id": 12,
            "job_id": 5,
            "job_title": "Help with gardening",
            "provider_id": 42,
            "provider_user_id": 42,
            "provider_name": "Jane Doe",
            "provider_avatar": "https://example.com/wp-content/uploads/2026/01/photo.jpg",
            "requester_id": 7,
            "requester_user_id": 7,
            "requester_name": "John Smith",
            "requester_avatar": "https://example.com/wp-content/uploads/2026/01/photo.jpg",
            "hours": 3,
            "region": { "id": 5, "name": "Milwaukee", "slug": "milwaukee" },
            "created_at": "2026-01-28 14:30:00",
            "has_appreciation": true
        }
    ],
    "total": 15,
    "pages": 1
}
```

**Notes**:
- `has_appreciation` is used in the exchange history UI to show the "Appreciation sent" status badge and hide the give-appreciation actions.

#### GET /me/worked-with
Get a summary of people the current user has exchanged with (authenticated).

**Response**:
```json
{
    "people": [
        {
            "other_user_id": 7,
            "other_user_name": "John Smith",
            "other_user_avatar": "https://example.com/wp-content/uploads/2026/01/photo.jpg",
            "total_exchanges": 4,
            "total_hours": 6,
            "jobs_provided": 2,
            "jobs_received": 2,
            "last_exchange_at": "2026-01-30 09:12:00",
            "latest_note": {
                "id": 11,
                "author_user_id": 42,
                "subject_user_id": 7,
                "subject_user_name": "John Smith",
                "tag_slug": "reliable",
                "note": "Great communicator.",
                "created_at": "2026-01-31 12:00:00"
            }
        }
    ]
}
```

**Notes**:
- `latest_note` is the most recent private note authored by the current user for that person (always private to the author).

#### GET /me/saved-profiles
Get saved profiles for the current user (authenticated, member access).

**Response**:
```json
{
    "users": [
        {
            "id": 7,
            "name": "John Smith",
            "display_name": "John Smith",
            "avatar_url": "https://example.com/wp-content/uploads/2026/01/photo.jpg",
            "skills": "Woodworking, carpentry",
            "skill_tags": ["woodworking"],
            "availability": "Weekends",
            "available_for_requests": true,
            "profile_tags": ["reliable"],
            "primary_region": { "id": 2, "name": "Madison", "slug": "madison" }
        }
    ],
    "ids": [7]
}
```

#### POST /me/saved-profiles
Save a profile to the current user's address book (authenticated, member access).

**Body**:
```json
{ "user_id": 7 }
```

#### DELETE /me/saved-profiles/{id}
Remove a saved profile from the current user's address book (authenticated, member access).

#### GET /community/users
List community members available for requests (authenticated).

**Parameters**:
- `q` (string, optional): Search by name, email, or skills
- `skill` (string, optional): Filter by skill keyword
- `skill_tags` (array|string, optional): Filter by skill tag slugs
- `region` (int, optional): Filter by primary region ID
- `sort` (string, optional): `recent` (default) or `name`
- `page` (int, optional): Page number (default: 1)
- `per_page` (int, optional): Items per page (default: 12)

**Response**:
```json
{
    "users": [
        {
            "id": 42,
            "name": "Jane Doe",
            "display_name": "Jane Doe",
            "pronouns": "she/her",
            "avatar_url": "https://example.com/wp-content/uploads/2026/01/photo.jpg",
            "skills": "Gardening, tutoring",
            "skill_tags": ["gardening", "tutoring"],
            "availability": "Weekday evenings",
            "available_for_requests": true,
            "profile_tags": ["reliable", "creative"],
            "primary_region": { "id": 5, "name": "Milwaukee", "slug": "milwaukee" }
        }
    ],
    "total": 15,
    "pages": 2
}
```

**Notes**:
- Results are limited to users whose roles are in **Settings → ZAO Bank → Member Access Roles**.
- Users who turn off `available_for_requests` are excluded from community results.

#### GET /me/profile
Get current user's profile (authenticated).

**Response**:
```json
{
    "id": 42,
    "name": "Jane Doe",
    "display_name": "Jane Doe",
    "pronouns": "she/her",
    "email": "jane@example.com",
    "avatar_url": "https://example.com/wp-content/uploads/2026/01/photo.jpg",
    "skills": "Gardening, tutoring",
    "skill_tags": ["gardening", "tutoring"],
    "availability": "Weekday evenings",
    "available_for_requests": true,
    "bio": "Community organizer and plant lover",
    "profile_tags": ["reliable", "creative"],
    "registered": "2025-01-15 10:30:00",
    "primary_region": { "id": 5, "name": "Milwaukee", "slug": "milwaukee" },
    "discord_id": "123456789012345678",
    "has_signal": true,
    "contact_preferences": ["email", "signal", "discord", "platform-message"],
    "phone": "414-555-1234"
}
```

**Notes**:
- `discord_id` and `has_signal` are included on both own and public profiles
- `contact_preferences`, `phone`, and `email` are only included on own profile (excluded from `GET /users/{id}`)
- `has_signal` is derived from whether `signal` is in the user's contact preferences
- `avatar_url` is included on both own and public profiles. Uses ACF `user_profile_image` attachment if set, falls back to Gravatar.
- `profile_image_id` (attachment ID) is only included on own profile

#### PUT /me/profile
Update current user's profile (authenticated).

**Body** (all fields optional):
```json
{
    "display_name": "string",
    "user_pronouns": "string",
    "user_bio": "string",
    "user_skills": "string",
    "user_availability": "string",
    "user_available_for_requests": true,
    "user_phone": "string",
    "user_discord_id": "string",
    "user_primary_region": 5,
    "user_profile_image": 456,
    "user_profile_tags": ["reliable", "creative"],
    "user_skill_tags": ["gardening", "tutoring"],
    "user_contact_preferences": ["email", "signal", "discord", "platform-message"]
}
```

#### GET /users/search
Search verified users for messaging (authenticated).

**Parameters**:
- `q` (string, required): Search query (min 2 characters)
- `limit` (int, optional): Max results (default 10, max 25)

**Response**:
```json
{
    "users": [
        {
            "id": 42,
            "name": "Jane Doe",
            "avatar_url": "https://example.com/wp-content/uploads/2026/01/photo.jpg"
        }
    ]
}
```

**Notes**:
- Results are filtered by roles from the **Settings → ZAO Bank → Member Access Roles** option (and can be overridden with the `zaobank_member_access_roles` or `zaobank_message_search_roles` filters).
- Current user is excluded from results.

### Messages Endpoints

#### GET /me/messages
Get current user's messages (authenticated).

**Parameters**:
- `with_user` (int, optional): Filter to only messages exchanged with a specific user ID (conversation view)
- `message_type` (string, optional): Filter by message type — `'direct'` (default user messages) or `'job_update'` (system notifications). Omit to get all types.

**Response**:
```json
{
    "messages": [
        {
            "id": 1,
            "exchange_id": null,
            "from_user_id": 42,
            "from_user_name": "Jane Doe",
            "from_user_avatar": "https://example.com/wp-content/uploads/2026/01/photo.jpg",
            "to_user_id": 7,
            "to_user_name": "John Smith",
            "to_user_avatar": "https://example.com/wp-content/uploads/2026/01/photo.jpg",
            "message": "Hey, I can help with your gardening job!",
            "is_read": false,
            "created_at": "2026-01-28 14:30:00"
        }
    ]
}
```

#### POST /messages
Send a new message (authenticated).

**Body**:
```json
{
    "to_user_id": 42,
    "message": "string (required)",
    "exchange_id": 5
}
```

- `to_user_id` (int, required): Recipient user ID
- `message` (string, required): Message content (sanitized with `wp_kses_post`)
- `exchange_id` (int, optional): Link message to a specific exchange for context

**Response** (201):
```json
{
    "message": "Message sent successfully",
    "message_id": 15
}
```

#### POST /messages/{id}/read
Mark a single message as read (authenticated). Only the recipient can mark a message as read.

**Response**:
```json
{
    "message": "Message marked as read"
}
```

**Error** (403 - if current user is not the recipient):
```json
{
    "code": "forbidden",
    "message": "You cannot mark this message as read"
}
```

#### POST /me/messages/read-all
Mark all messages from a specific user as read (authenticated). Bulk operation for marking an entire conversation as read from the conversations list.

**Body**:
```json
{
    "with_user": 42
}
```

- `with_user` (int, required): The other user's ID whose messages should be marked as read.

**Response**:
```json
{
    "success": true,
    "message": "Messages marked as read"
}
```

#### POST /me/messages/read-type
Mark all messages of a given type as read for the current user (authenticated). Useful for clearing job notifications.

**Body**:
```json
{
    "message_type": "job_update"
}
```

- `message_type` (string, required): Message type to mark as read.

**Response**:
```json
{
    "success": true,
    "message": "Messages marked as read",
    "count": 4
}
```

#### POST /me/messages/archive
Archive a conversation (authenticated). Hides the conversation from the conversations list without deleting messages.

**Body**:
```json
{
    "other_user_id": 42
}
```

- `other_user_id` (int, required): The other user's ID in the conversation to archive.

**Response**:
```json
{
    "success": true,
    "message": "Conversation archived"
}
```

### Appreciations Endpoints

#### GET /users/{id}/appreciations
Get public appreciations for a user.

**Response**:
```json
{
    "appreciations": [
        {
            "id": 12,
            "exchange_id": 5,
            "from_user_id": 42,
            "from_user_name": "Jane Doe",
            "from_user_avatar": "https://example.com/wp-content/uploads/2026/01/photo.jpg",
            "to_user_id": 7,
            "to_user_name": "John Smith",
            "tag_slug": "helpful",
            "message": "Thanks for the help!",
            "is_public": true,
            "job_id": 99,
            "job_title": "Help with gardening",
            "created_at": "2026-01-28 14:30:00"
        }
    ]
}
```

#### GET /me/appreciations/given
Get appreciations given by the current user (authenticated).

**Response**:
```json
{
    "appreciations": [...]
}
```

#### POST /appreciations
Create an appreciation (authenticated).

**Body**:
```json
{
    "exchange_id": 5,
    "to_user_id": 7,
    "tag_slug": "helpful",
    "message": "Thanks for the help!",
    "is_public": true
}
```

### Private Notes Endpoints

Private notes are personal memory aids visible only to the author. See [Security Rules](#wp_zaobank_private_notes) above.

#### GET /me/notes
Get current user's private notes (authenticated).

**Parameters**:
- `subject_user_id` (int, optional): Filter notes about a specific user.

**Response**:
```json
{
    "notes": [
        {
            "id": 1,
            "subject_user_id": 42,
            "tag_slug": "job-completion",
            "note": "Very reliable, arrived on time",
            "created_at": "2026-01-28 14:30:00"
        }
    ]
}
```

#### POST /me/notes
Create a private note (authenticated).

**Body**:
```json
{
    "subject_user_id": 42,
    "tag_slug": "job-completion",
    "note": "Very reliable, arrived on time"
}
```

- `subject_user_id` (int, required): The user the note is about.
- `tag_slug` (string, required): Tag category (e.g., `'job-completion'`, or any configured private note tag).
- `note` (string, optional): The note content.

**Response** (201):
```json
{
    "success": true,
    "note_id": 5
}
```

#### DELETE /me/notes/{id}
Delete a private note (authenticated, owner only).

**Response**:
```json
{
    "success": true,
    "message": "Note deleted"
}
```

### Flags Endpoints

#### POST /flags
Create a flag (authenticated).

**Body**:
```json
{
    "flagged_item_type": "job|appreciation|message|user",
    "flagged_item_id": 123,
    "reason_slug": "inappropriate-content",
    "context_note": "Additional context"
}
```

## Hooks & Filters

### Action Hooks

```php
// Before job creation
do_action('zaobank_before_create_job', $data, $user_id);

// After job creation
do_action('zaobank_after_create_job', $job_id, $data);

// After job claim
do_action('zaobank_job_claimed', $job_id, $provider_id);

// After job completion
do_action('zaobank_job_completed', $job_id, $exchange_id);

// After exchange creation
do_action('zaobank_exchange_created', $exchange_id, $data);

// After flag creation
do_action('zaobank_flag_created', $flag_id, $data);
```

### Filter Hooks

```php
// Modify job data before creation
$data = apply_filters('zaobank_before_create_job_data', $data, $user_id);

// Modify appreciation tags
$tags = apply_filters('zaobank_appreciation_tags', $tags);

// Modify private note tags
$tags = apply_filters('zaobank_private_note_tags', $tags);

// Modify flag reasons
$reasons = apply_filters('zaobank_flag_reasons', $reasons);

// Modify REST response for jobs
$response = apply_filters('zaobank_rest_job_response', $response, $job_id);

// Modify balance calculation
$balance = apply_filters('zaobank_user_balance', $balance, $user_id);

// Modify page slugs for URL generation
$slugs = apply_filters('zaobank_page_slugs', $slugs);

// Modify template lookup paths
$paths = apply_filters('zaobank_template_paths', $paths, $template_name);
```

### Example: Configure /app/ Page Structure

```php
add_filter('zaobank_page_slugs', function($slugs) {
    return array(
        'dashboard'     => 'app/dashboard',
        'jobs'          => 'app/jobs',
        'job_form'      => 'app/new-job',
        'my_jobs'       => 'app/my-jobs',
        'community'     => 'app/community',
        'profile'       => 'app/profile',
        'profile_edit'  => 'app/profile-edit',
        'messages'      => 'app/messages',
        'exchanges'     => 'app/exchanges',
        'appreciations' => 'app/appreciations',
    );
});
```

### Example: Add Custom Template Location

```php
add_filter('zaobank_template_paths', function($paths, $template_name) {
    // Add mu-plugins location for network-wide overrides
    array_unshift($paths,
        WPMU_PLUGIN_DIR . '/zaobank-templates/' . $template_name . '.php'
    );
    return $paths;
}, 10, 2);
```

### Example: Add Custom Job Validation

```php
add_filter('zaobank_before_create_job_data', function($data, $user_id) {
    // Add custom validation
    if (strlen($data['title']) < 10) {
        return new WP_Error(
            'title_too_short',
            'Job title must be at least 10 characters'
        );
    }
    
    return $data;
}, 10, 2);
```

### Example: Send Email on Job Completion

```php
add_action('zaobank_job_completed', function($job_id, $exchange_id) {
    $exchange = ZAOBank_Exchanges::get_exchange($exchange_id);
    $provider_email = get_the_author_meta('user_email', $exchange['provider_id']);
    
    wp_mail(
        $provider_email,
        'Job Completed!',
        "Your time exchange has been recorded: {$exchange['hours']} hours"
    );
}, 10, 2);
```

## Security Considerations

### Rate Limiting

The plugin includes built-in rate limiting:

```php
$rate_check = ZAOBank_Security::check_rate_limit(
    $action,      // Action identifier
    $user_id,     // User ID (null for current user)
    $limit,       // Max requests (default: 10)
    $period       // Time period in seconds (default: 3600)
);

if (is_wp_error($rate_check)) {
    // Rate limit exceeded
}
```

### Permission Checks

Always verify permissions before operations:

```php
// Check if user can create jobs
if (!ZAOBank_Security::can_create_job($user_id)) {
    return new WP_Error('forbidden', 'Insufficient permissions');
}

// Check if user can edit specific job
if (!ZAOBank_Security::can_edit_job($job_id, $user_id)) {
    return new WP_Error('forbidden', 'Cannot edit this job');
}

// Check if user can review flags
if (!ZAOBank_Security::can_review_flags($user_id)) {
    return new WP_Error('forbidden', 'Cannot review flags');
}
```

### Input Sanitization

Always sanitize user input:

```php
$data = ZAOBank_Security::sanitize_job_data($request->get_params());
```

### Nonce Verification

For form submissions:

```php
if (!wp_verify_nonce($_POST['_wpnonce'], 'zaobank_action')) {
    wp_die('Security check failed');
}
```

## Extending the Plugin

### Taxonomies

The plugin registers two taxonomies on the `timebank_job` post type:

| Taxonomy | Slug | Type | REST Base |
|----------|------|------|-----------|
| `zaobank_region` | `zaobank_region` | Hierarchical (category-like) | `regions` |
| `zaobank_job_type` | `zaobank_job_type` | Non-hierarchical (tag-like) | `job-types` |

Both are admin-manageable (`show_ui: true`, `show_admin_column: true`). Job types can be assigned to jobs via the REST API or the job form, and are used for filtering on the jobs list page.

### ACF Field Groups

The plugin registers two ACF field groups programmatically:

| Group | Key | Location |
|-------|-----|----------|
| Job Fields | `group_zaobank_job` | `timebank_job` post type |
| User Profile Fields | `group_zaobank_user_profile` | User edit/profile screens |

**User Profile Fields** include:
- `user_profile_image` (image, return format: ID) — Custom profile photo, replaces Gravatar when set. Stored as user meta. Minimum 96x96px, accepts jpg/jpeg/png/gif/webp.
- `user_pronouns`, `user_bio`, `user_skills`, `user_availability`, `user_phone`, `user_discord_id`, `user_primary_region`, `user_profile_tags`, `user_contact_preferences`

The profile image is selected via the WordPress media modal (`wp.media`) on the profile edit page. The attachment ID is stored as user meta and resolved to a URL via `wp_get_attachment_image_url()`. Use `ZAOBank_Helpers::get_user_avatar_url($user_id)` to get the resolved URL with Gravatar fallback.

### Adding Custom Job Fields

1. Register ACF fields:

```php
add_action('acf/init', function() {
    acf_add_local_field(array(
        'key' => 'field_custom',
        'label' => 'Custom Field',
        'name' => 'custom_field',
        'type' => 'text',
        'parent' => 'group_zaobank_job'
    ));
});
```

2. Include in REST API response:

```php
add_filter('zaobank_rest_job_response', function($response, $job_id) {
    $response['custom_field'] = get_post_meta($job_id, 'custom_field', true);
    return $response;
}, 10, 2);
```

### Adding Custom REST Endpoints

```php
add_action('rest_api_init', function() {
    register_rest_route('zaobank/v1', '/custom-endpoint', array(
        'methods' => 'GET',
        'callback' => 'my_custom_callback',
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ));
});
```

### Creating Custom Formidable Forms

1. Create form in Formidable Forms Pro
2. Add custom action to submit via REST API:

```php
add_action('frm_after_create_entry', function($entry_id, $form_id) {
    if ($form_id != YOUR_FORM_ID) return;
    
    $entry = FrmEntry::getOne($entry_id);
    
    // Submit to ZAO Bank API
    $data = array(
        'title' => $entry->metas['title'],
        'hours' => $entry->metas['hours'],
        // ... more fields
    );
    
    $job_id = ZAOBank_Jobs::create_job($data);
}, 10, 2);
```

## Testing

### Manual Testing Checklist

- [ ] Create a job
- [ ] Claim a job
- [ ] Complete a job (with hours adjustment prompt)
- [ ] Complete a job with appreciation and private notes
- [ ] Release a claimed job (as provider, with reason)
- [ ] Update/edit a job (as owner)
- [ ] Delete a job (unclaimed → trash, claimed → archive)
- [ ] Verify exchange is recorded
- [ ] Check balance calculation
- [ ] Send a message from profile page → opens conversation
- [ ] Verify messages `?user_id=` routing to conversation view
- [ ] Verify messages `?view=updates` routing to job updates view
- [ ] Mark conversation as read from conversations list
- [ ] Archive a conversation from conversations list
- [ ] Report a message (flag icon on other user's messages)
- [ ] View job update notifications (release/complete/appreciation)
- [ ] Subpage tabs navigate correctly (messages ↔ exchanges ↔ job updates)
- [ ] Create and delete private notes
- [ ] Flag content
- [ ] Review flagged content
- [ ] Test region filtering
- [ ] Test job type filtering
- [ ] Test profile image upload and display
- [ ] Verify profile buttons (own vs. other user)
- [ ] Test rate limiting
- [ ] Test permission checks

### Testing REST API with cURL

```bash
# Get jobs
curl -X GET "https://yoursite.com/wp-json/zaobank/v1/jobs"

# Create job (with authentication)
curl -X POST "https://yoursite.com/wp-json/zaobank/v1/jobs" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Job","hours":2.5}'

# Get balance
curl -X GET "https://yoursite.com/wp-json/zaobank/v1/me/balance" \
  -H "X-WP-Nonce: YOUR_NONCE"
```

### Testing with WordPress REST API Test Suite

```php
class ZAOBank_REST_Test extends WP_Test_REST_TestCase {
    public function test_jobs_endpoint() {
        $request = new WP_REST_Request('GET', '/zaobank/v1/jobs');
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $this->assertArrayHasKey('jobs', $response->get_data());
    }
}
```

## Performance Considerations

### Database Queries

1. **Use Indexes**: All foreign keys are indexed
2. **Limit Results**: Always use pagination
3. **Cache Results**: Use WordPress transients for expensive queries

```php
$cache_key = 'zaobank_user_balance_' . $user_id;
$balance = get_transient($cache_key);

if (false === $balance) {
    $balance = ZAOBank_Exchanges::get_user_balance($user_id);
    set_transient($cache_key, $balance, HOUR_IN_SECONDS);
}
```

### Caching Strategy

- Balance calculations: 1 hour
- Job listings: 5 minutes
- Region lists: 24 hours

Clear caches on relevant updates:

```php
add_action('zaobank_exchange_created', function($exchange_id, $data) {
    // Clear balance cache for both users
    delete_transient('zaobank_user_balance_' . $data['provider_user_id']);
    delete_transient('zaobank_user_balance_' . $data['requester_user_id']);
}, 10, 2);
```

## Troubleshooting

### Common Issues

**Issue**: REST API returns 404  
**Solution**: Flush permalinks (Settings → Permalinks → Save)

**Issue**: Nonce verification fails  
**Solution**: Check that cookies are enabled and user is logged in

**Issue**: Balance calculation is wrong  
**Solution**: Check for orphaned exchange records, verify all exchanges have valid user IDs

**Issue**: ACF fields not showing  
**Solution**: Verify ACF Pro is active and field group location rules are correct

## Contributing

When contributing code:

1. Follow WordPress Coding Standards
2. Add PHPDoc comments
3. Include error handling
4. Test with WordPress debug mode enabled
5. Respect privacy principles (especially for private notes)

## License

GPL v2 or later
