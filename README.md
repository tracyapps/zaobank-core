# ZAO Bank - WordPress Time Banking Plugin

A WordPress-based time banking system built for communities with porous regional boundaries and strong care ethics for all marginalized folks.

## Overview

ZAO Bank enables communities to exchange time and skills through a trust-based economy where relationships matter more than rankings. The system prioritizes safety without carcerality, memory without surveillance, and accountability with grace.

## Core Principles

- **Themes express meaning; plugins express behavior** - ZAO Bank logic lives in a plugin, themes consume the API
- **Time is currency; relationships are the point** - No numeric rankings or leaderboards
- **Regions are context, not permission** - They filter views and suggest proximity but never gate participation
- **Safety first, punishment last** - Flags reduce exposure immediately; human review determines outcomes

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher
- **Required Plugins:**
    - Advanced Custom Fields (ACF) Pro
    - Advanced Access Manager (AAM) - for granular permissions
    - Formidable Forms Pro (optional, for custom forms)

## Installation

### 1. Upload the Plugin

```bash
# Upload to wp-content/plugins/ directory
cd wp-content/plugins/
# Extract or clone the plugin
```

### 2. Install Dependencies

Ensure the following plugins are installed and activated:
- ACF Pro
- Advanced Access Manager (AAM)
- Formidable Forms Pro (optional)

### 3. Activate the Plugin

1. Go to WordPress Admin → Plugins
2. Find "ZAO Bank Core"
3. Click "Activate"

The plugin will automatically:
- Create necessary database tables
- Register custom post types and taxonomies
- Set up default capabilities
- Create REST API endpoints

### 4. Configure Regions

1. Go to Jobs → Regions
2. Create your regional hierarchy
3. Example structure:
   ```
   North America
   ├── Midwest
   │   ├── Chicago
   │   └── Milwaukee
   └── Pacific Northwest
       └── Seattle
   ```

### 5. Configure AAM Permissions (Optional)

Use Advanced Access Manager to create custom roles or modify existing ones:
- **Limited Member - `member_limited`** - Can view, but not post (all self-registered and flagged accounts)
- **Verified Member - `member`** - Can create and claim jobs
- **Leadership Team - `leadership_team`** - Can review flags, moderate content, and verify (upgrade) `member` accounts


### 6. Create Frontend Pages

Create WordPress pages with shortcodes for the user-facing interface. You can organize pages under an `/app/` parent for easy access control:

| Page | Shortcode |
|------|-----------|
| Dashboard | `[zaobank_dashboard]` |
| Browse Jobs | `[zaobank_jobs]` |
| Post a Job | `[zaobank_job_form]` |
| My Jobs | `[zaobank_my_jobs]` |
| Community | `[zaobank_community]` |
| Profile | `[zaobank_profile]` |
| Edit Profile | `[zaobank_profile_edit]` |
| Messages | `[zaobank_messages]` |
| Exchange History | `[zaobank_exchanges]` |
| Appreciations | `[zaobank_appreciations]` |

See [Theme Integration](#theme-integration) section for advanced setup with custom templates.

### 7. Configure AAM for /app/ Section (Recommended)

For sites that want public pages but protected timebank features:

1. Create an `/app/` parent page
2. Create child pages under `/app/` for each shortcode
3. Use AAM to restrict `/app/*` to logged-in users
4. Add the page slugs filter to your theme (see [Theme Integration](#theme-integration))

## Architecture

### Database Tables

The plugin creates the following custom tables:

- **wp_zaobank_exchanges** - Immutable records of completed time exchanges
- **wp_zaobank_user_regions** - User-region affinity for smart filtering
- **wp_zaobank_appreciations** - Public and private appreciation messages
- **wp_zaobank_messages** - 1:1 messages between users (with `message_type` for direct vs. job update messages)
- **wp_zaobank_private_notes** - Personal memory aids (never visible to others)
- **wp_zaobank_archived_conversations** - Per-user conversation archive state
- **wp_zaobank_flags** - Content moderation flags

### Custom Post Types

- **timebank_job** - Jobs posted by community members

### Taxonomies

- **zaobank_region** - Hierarchical regions for filtering and context
- **zaobank_job_type** - Non-hierarchical job type tags (e.g., gardening, tutoring, tech support)

## REST API Endpoints

All endpoints are namespaced under `/wp-json/zaobank/v1/`

### Jobs

```
GET    /jobs                 - List jobs (supports filtering)
POST   /jobs                 - Create a job
GET    /jobs/{id}            - Get single job
PUT    /jobs/{id}            - Update job (owner only)
DELETE /jobs/{id}            - Delete/archive job (owner only)
POST   /jobs/{id}/claim      - Claim a job
POST   /jobs/{id}/complete   - Complete job and record exchange (accepts optional `hours` override)
POST   /jobs/{id}/release    - Release a claimed job (provider only)
GET    /jobs/mine            - Get current user's jobs
GET    /job-types            - List all job type terms
```

### User

```
GET    /me/balance           - Get current user's time balance
GET    /me/exchanges         - Get current user's exchange history (optional `filter=all|earned|spent`)
GET    /me/worked-with       - Get people the user has worked with (exchange summary + latest private note)
GET    /me/saved-profiles    - Get saved profiles (address book)
POST   /me/saved-profiles    - Save a profile to your address book
DELETE /me/saved-profiles/{id} - Remove a saved profile
GET    /me/profile           - Get current user's profile
PUT    /me/profile           - Update current user's profile
GET    /me/statistics        - Get current user's statistics
GET    /users/{id}           - Get user's public profile
GET    /community/users      - Community directory (filters: `q`, `skill`, `skill_tags`, `region`, `sort`, `page`, `per_page`)
```

### Regions

```
GET    /regions              - Get all regions (hierarchical)
```

### Appreciations

```
POST   /appreciations                    - Create appreciation
GET    /users/{id}/appreciations         - Get user's appreciations
GET    /me/appreciations/given           - Get appreciations you've given (auth)
```

### Users

```
GET    /users/search          - Search verified users for messaging (params: `q`, `limit`)
```

### Messages

```
GET    /me/messages           - Get current user's messages (supports `with_user`, `message_type` filters)
POST   /messages              - Send a message
POST   /messages/{id}/read    - Mark a single message as read
POST   /me/messages/read-all  - Mark all messages from a user as read (param: `with_user`)
POST   /me/messages/archive   - Archive a conversation (param: `other_user_id`)
```

### Private Notes

```
GET    /me/notes              - Get current user's private notes (optional `subject_user_id` filter)
POST   /me/notes              - Create a private note (params: `subject_user_id`, `tag_slug`, `note`)
DELETE /me/notes/{id}         - Delete a private note (owner only)
```

### Flags (Moderation)

```
POST   /flags                - Create a flag (report content)
GET    /flags                - Get flags for review (admin only)
PUT    /flags/{id}           - Update flag status (admin only)
```

## REST API Usage Examples

### Authentication

All authenticated endpoints require a nonce in the header:

```javascript
fetch('/wp-json/zaobank/v1/me/balance', {
  headers: {
    'X-WP-Nonce': wpApiSettings.nonce
  }
})
```

### Update Profile

```javascript
fetch('/wp-json/zaobank/v1/me/profile', {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': wpApiSettings.nonce
  },
  body: JSON.stringify({
    display_name: "Jane Doe",
    user_pronouns: "she/her",
    user_bio: "Community organizer and plant lover"
  })
})
  .then(response => response.json())
  .then(data => console.log('Profile updated:', data.profile));
```

### Create a Job

```javascript
const jobData = {
  title: "Help with gardening",
  description: "Need help planting vegetables in my backyard",
  hours: 2.5,
  location: "123 Main St, Milwaukee",
  regions: [15, 16], // Region term IDs
  skills_required: "Gardening, Physical work",
  preferred_date: "2024-05-15",
  flexible_timing: true
};

fetch('/wp-json/zaobank/v1/jobs', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': wpApiSettings.nonce
  },
  body: JSON.stringify(jobData)
})
  .then(response => response.json())
  .then(data => console.log('Job created:', data));
```

### Claim a Job

```javascript
fetch('/wp-json/zaobank/v1/jobs/123/claim', {
  method: 'POST',
  headers: {
    'X-WP-Nonce': wpApiSettings.nonce
  }
})
  .then(response => response.json())
  .then(data => console.log('Job claimed:', data));
```

### Get User Balance

```javascript
fetch('/wp-json/zaobank/v1/me/balance', {
  headers: {
    'X-WP-Nonce': wpApiSettings.nonce
  }
})
  .then(response => response.json())
  .then(data => {
    console.log('Hours earned:', data.hours_earned);
    console.log('Hours spent:', data.hours_spent);
    console.log('Balance:', data.balance);
  });
```

### Send a Message

```javascript
fetch('/wp-json/zaobank/v1/messages', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': wpApiSettings.nonce
  },
  body: JSON.stringify({
    to_user_id: 42,
    message: "Hey, I can help with your gardening job!",
    exchange_id: 5  // optional, links message to a specific exchange
  })
})
  .then(response => response.json())
  .then(data => console.log('Message sent:', data));
```

### Get Messages (Conversation with a Specific User)

```javascript
fetch('/wp-json/zaobank/v1/me/messages?with_user=42', {
  headers: {
    'X-WP-Nonce': wpApiSettings.nonce
  }
})
  .then(response => response.json())
  .then(data => {
    data.messages.forEach(msg => {
      console.log(msg.from_user_name + ': ' + msg.message);
      console.log('Avatar:', msg.from_user_avatar);
    });
  });
```

### Search Users (for Messaging)

```javascript
fetch('/wp-json/zaobank/v1/users/search?q=jane', {
  headers: {
    'X-WP-Nonce': wpApiSettings.nonce
  }
})
  .then(response => response.json())
  .then(data => console.log('Matches:', data.users));
```

Search results are limited to roles configured in **Settings → ZAO Bank → Member Access Roles**.

### Mark a Message as Read

```javascript
fetch('/wp-json/zaobank/v1/messages/15/read', {
  method: 'POST',
  headers: {
    'X-WP-Nonce': wpApiSettings.nonce
  }
})
  .then(response => response.json())
  .then(data => console.log('Marked as read:', data));
```

## ACF Field Groups

The plugin registers two ACF field groups:

### Job Details
- Hours Required (number)
- Provider (user)
- Completed At (datetime)
- Visibility (select)
- Location (text)
- Virtual Option (true/false)
- Skills Required (text)
- Preferred Date (date)
- Flexible Timing (true/false)

### User Profile
- Profile Image (image, return format: ID) - Custom profile photo, replaces Gravatar
- Pronouns (text)
- Skills I Can Offer (textarea)
- Skill Tags (checkbox)
- Availability (text)
- Bio (textarea)
- Primary Region (taxonomy)
- Personality Tags (checkbox)
- Contact Preferences (checkbox) - Email, Phone, Text, Signal, Discord, ZAO Bank Messages
- Phone Number (text)
- Discord User ID (text) - Links to `https://discord.com/users/{id}` on profile

## Security Features

### Rate Limiting

The plugin includes built-in rate limiting:
- Job creation: 10 per hour
- Flagging: 3 per item per 24 hours

### Content Visibility

Flagged content is automatically hidden from public view until reviewed by moderators.

### Private Notes

Private notes are strictly scoped to the author and are never:
- Visible to administrators
- Aggregated or analyzed
- Exposed via broad REST queries

## Formidable Forms Integration

You can create custom forms using Formidable Forms Pro for:
- Job posting forms with custom fields
- User registration forms with profile fields
- Contact forms for user-to-user communication

### Example: Job Posting Form

1. Create a new form in Formidable Forms
2. Add fields matching the job data structure
3. Use form actions to submit data via REST API

## Safety & Moderation

### Flagging System

Users can flag:
- Jobs
- Appreciations
- Messages
- Other users

When content is flagged:
1. **Immediate action**: Content is automatically hidden
2. **Human review**: Moderators review the flag
3. **Resolution**: Content can be restored or kept hidden

### Moderation Dashboard

Access via: Admin → ZAO Bank → Flags & Moderation

Features:
- View all open flags
- Review flagged content in context
- Restore or keep hidden
- Add internal notes
- Contact users privately

## Theme Integration

ZAO Bank provides multiple ways to integrate with your theme, from simple shortcode pages to fully customized templates.

### Template Override System

Override any plugin template by placing a copy in your theme. The plugin checks these locations in order:

1. `{theme}/zaobank/templates/{template}.php`
2. `{theme}/zaobank/{template}.php`
3. `{theme}/app/{template}.php` (for /app section themes)
4. `{plugin}/public/templates/{template}.php` (fallback)

**Example**: To customize the dashboard template:

```bash
# Copy plugin template to theme
cp wp-content/plugins/zaobank-core/public/templates/dashboard.php \
   wp-content/themes/your-theme/zaobank/templates/dashboard.php
```

Then edit the theme copy to customize the layout while keeping the data attributes and component structure intact.

### Available Templates

| Template | Description |
|----------|-------------|
| `dashboard.php` | User dashboard with balance and activity |
| `jobs-list.php` | Browse available jobs listing |
| `job-single.php` | Single job detail view |
| `job-form.php` | Create/edit job form |
| `my-jobs.php` | User's posted and claimed jobs |
| `community.php` | Community directory + address book |
| `profile.php` | User profile view |
| `profile-edit.php` | Profile edit form |
| `messages.php` | Conversations list / job updates view |
| `more.php` | More menu (messages, job notifications, profile edit shortcuts) |
| `conversation.php` | Single conversation thread |
| `exchanges.php` | Exchange history (shows appreciation status + people worked with) |
| `appreciations.php` | Appreciations list |
| `components/bottom-nav.php` | Mobile bottom navigation |

### Theme Helper Functions

The plugin provides global functions for use in theme templates:

```php
// Get all page URLs for navigation
$urls = zaobank_get_urls();
echo $urls['dashboard'];  // https://site.com/app/dashboard/

// Check if current page is in the app section
if (zaobank_is_app_section()) {
    get_header('app');  // Use a custom app header
}

// Get which template is being used
$template = zaobank_current_template();  // 'dashboard', 'jobs-list', etc.

// Get current user's time balance
$balance = zaobank_user_balance();

// Get unread message count
$unread = zaobank_unread_count();

// Render a template directly (bypasses shortcode auth checks)
zaobank_render_template('dashboard');
zaobank_render_template('jobs-list', ['region' => 5]);

// Output the bottom navigation
zaobank_bottom_nav();

// Get list of available templates
$templates = zaobank_available_templates();
```

### Page Slugs for /app/ Structure

Configure all timebank pages as children of `/app/` for easy AAM protection:

```php
// In your theme's functions.php
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

Then create these pages in WordPress Admin under an `/app/` parent page.

### Custom Page Templates

For full control over the page layout, create WordPress page templates:

**Option 1: Generic App Template**

```php
<?php
/**
 * Template Name: ZAO Bank - App Page
 */
get_header('app');  // Your custom app header
?>
<main class="zaobank-app-main">
    <?php the_content(); // Renders the shortcode ?>
</main>
<?php
zaobank_bottom_nav();
get_footer('app');
```

**Note:** In the ZAO theme we also support a hybrid pattern: if the page content is empty and the page slug matches a ZAOBank page (e.g., `/app/community`), the app template auto-renders the matching ZAOBank template. Shortcodes still work normally, so you can choose either method.

**Option 2: Custom Template with Direct Rendering**

```php
<?php
/**
 * Template Name: ZAO Bank - Dashboard
 */
get_header('app');

$user = wp_get_current_user();
$urls = zaobank_get_urls();
?>
<main class="zaobank-app-main">
    <h1>Welcome, <?php echo esc_html($user->display_name); ?></h1>

    <!-- Render just the dashboard content -->
    <?php zaobank_render_template('dashboard'); ?>
</main>
<?php
zaobank_bottom_nav();
get_footer('app');
```

### Body Classes

The plugin adds helpful body classes for styling:

- `zaobank-app-page` - On any page using a ZAO Bank shortcode
- `zaobank-template-{name}` - Specific template (e.g., `zaobank-template-dashboard`)

```css
/* Style app pages differently */
body.zaobank-app-page .site-header {
    display: none;  /* Hide main site header in app */
}
```

### Custom Template Paths Filter

Add additional template lookup locations:

```php
add_filter('zaobank_template_paths', function($paths, $template_name) {
    // Add custom location at the beginning (highest priority)
    array_unshift($paths,
        get_stylesheet_directory() . '/custom-zaobank/' . $template_name . '.php'
    );
    return $paths;
}, 10, 2);
```

## Customization

### Hooks & Filters

```php
// Modify job data before creation
add_filter('zaobank_before_create_job', function($data) {
    // Modify $data
    return $data;
});

// After job is completed
add_action('zaobank_job_completed', function($job_id, $exchange_id) {
    // Custom logic
}, 10, 2);

// Modify appreciation tags
add_filter('zaobank_appreciation_tags', function($tags) {
    $tags[] = 'custom-tag';
    return $tags;
});
```

### Custom Capabilities

The plugin creates these capabilities:
- `edit_timebank_job`
- `edit_timebank_jobs`
- `edit_others_timebank_jobs`
- `publish_timebank_jobs`
- `read_private_timebank_jobs`
- `review_zaobank_flags`
- `manage_zaobank_flags`
- `manage_zaobank_users`
- `manage_zaobank_regions`

## Theme Development

### Displaying Jobs

```php
<?php
$jobs = ZAOBank_Jobs::get_available_jobs(array(
    'posts_per_page' => 10,
    'region' => get_query_var('region')
));

foreach ($jobs as $job) {
    ?>
    <div class="job-card">
        <h3><?php echo esc_html($job['title']); ?></h3>
        <p><?php echo esc_html($job['description']); ?></p>
        <span class="hours"><?php echo ZAOBank_Helpers::format_hours($job['hours']); ?></span>
        <span class="requester"><?php echo esc_html($job['requester_name']); ?></span>
    </div>
    <?php
}
?>
```

### Displaying User Balance

```php
<?php
if (is_user_logged_in()) {
    $balance = ZAOBank_Exchanges::get_user_balance(get_current_user_id());
    echo ZAOBank_Helpers::format_balance($balance['balance']);
}
?>
```

## Configuration Options

Available via Settings → ZAO Bank:

- **Enable Regions** - Turn regional filtering on/off
- **Auto-hide Flagged Content** - Automatically hide flagged content
- **Flag Threshold** - Number of flags before auto-hiding
- **Appreciation Tags** - Positive tags for appreciations
- **Skill Tags** - Tags used for community skill filters and profile skill tags
- **Personality Tags** - Tags used to describe working style on profiles
- **Private Note Tags** - Memory aid tags for private notes (used in “People You’ve Worked With” notes)
- **Flag Reasons** - Available reasons for flagging content
- **Member Access Roles** - Roles allowed to access member-only actions (messaging, jobs, requests, profile edits)

## Troubleshooting

### Database Tables Not Created

If activation doesn't create tables:
1. Deactivate the plugin
2. Delete the option `zaobank_db_version`
3. Reactivate the plugin

### REST API Not Working

1. Check permalink settings (must not be "Plain")
2. Verify nonce is being sent correctly
3. Check user capabilities

### ACF Fields Not Showing

1. Ensure ACF Pro is activated
2. Check that the post type matches
3. Verify user has permission to edit

## Shortcodes

ZAO Bank provides shortcodes for building mobile-first, responsive pages:

### Available Shortcodes

| Shortcode | Description | Parameters |
|-----------|-------------|------------|
| `[zaobank_dashboard]` | User dashboard with balance, stats, activity | - |
| `[zaobank_jobs]` | Browse available jobs with filters; also renders single job view when `?job_id=` is in the URL | `region`, `status` |
| `[zaobank_job]` | Single job detail view (standalone) | `id` or `?job_id=` URL param |
| `[zaobank_job_form]` | Create/edit job form | `id` for edit mode |
| `[zaobank_my_jobs]` | User's posted and claimed jobs | - |
| `[zaobank_community]` | Community directory + address book (saved profiles + worked-with) | - |
| `[zaobank_profile]` | User profile view | `user_id` (optional, defaults to current user) |
| `[zaobank_profile_edit]` | Profile edit form | - |
| `[zaobank_messages]` | Conversations list; renders conversation view when `?user_id=` is in the URL, job updates view when `?view=updates` | - |
| `[zaobank_more]` | More menu (messages, job notifications, profile edit shortcuts) | `view` (optional, `messages` or `updates`) |
| `[zaobank_conversation]` | Single conversation thread (standalone) | `user_id` |
| `[zaobank_exchanges]` | Exchange history | - |
| `[zaobank_appreciations]` | Appreciations received/given | `user_id` (optional) |

### Setting Up Pages

**Option A: Default Page Structure**

Create WordPress pages for each section:
- `/timebank-dashboard/` → `[zaobank_dashboard]`
- `/timebank-jobs/` → `[zaobank_jobs]` (also handles single job view via `?job_id=X`)
- `/timebank-new-job/` → `[zaobank_job_form]`
- `/timebank-my-jobs/` → `[zaobank_my_jobs]`
- `/timebank-community/` → `[zaobank_community]`
- `/timebank-profile/` → `[zaobank_profile]`
- `/timebank-profile-edit/` → `[zaobank_profile_edit]`
- `/timebank-messages/` → `[zaobank_messages]` (also handles `?user_id=X` for conversation view and `?view=updates` for job updates)
- `/timebank-more/` → `[zaobank_more]` (messages, job notifications, profile edit shortcuts)
- `/timebank-exchanges/` → `[zaobank_exchanges]`
- `/timebank-appreciations/` → `[zaobank_appreciations]`

**Option B: /app/ Structure (Recommended)**

Create an `/app/` parent page with child pages. This enables easy AAM protection with a wildcard rule:

```
/app/                    (parent page)
  /app/dashboard/        [zaobank_dashboard]
  /app/jobs/             [zaobank_jobs] (also handles /app/jobs/?job_id=X)
  /app/new-job/          [zaobank_job_form]
  /app/my-jobs/          [zaobank_my_jobs]
  /app/community/        [zaobank_community]
  /app/profile/          [zaobank_profile]
  /app/profile-edit/     [zaobank_profile_edit]
  /app/messages/         [zaobank_messages]
  /app/more/             [zaobank_more]
  /app/exchanges/        [zaobank_exchanges]
  /app/appreciations/    [zaobank_appreciations]
```

Add the filter in your theme's `functions.php`:

```php
add_filter('zaobank_page_slugs', function($slugs) {
    return array(
        'dashboard'     => 'app/dashboard',
        'jobs'          => 'app/jobs',
        'job_form'      => 'app/new-job',
        'my_jobs'       => 'app/my-jobs',
        'profile'       => 'app/profile',
        'profile_edit'  => 'app/profile-edit',
        'messages'      => 'app/messages',
        'exchanges'     => 'app/exchanges',
        'appreciations' => 'app/appreciations',
    );
});
```

Then in AAM, restrict `/app/*` to logged-in users only.

### Mobile-First Design

All templates feature:
- **Touch-friendly tap targets** (minimum 44px)
- **Bottom navigation** on mobile/tablet (hidden on desktop 1024px+)
- **Responsive breakpoints**: Mobile (<768px), Tablet (768px+), Desktop (1024px+)
- **Progressive enhancement** - Works without JavaScript, enhanced with JS

## Development

### File Structure

```
zaobank-core/
├── admin/
│   ├── class-zaobank-admin.php
│   └── partials/
├── assets/
│   ├── css/
│   │   └── zaobank-public.css      # Mobile-first responsive styles
│   └── js/
│       └── zaobank-public.js       # Component-based JavaScript
├── includes/
│   ├── class-zaobank-*.php         # Business logic classes
│   ├── class-zaobank-shortcodes.php # Shortcode registration
│   └── rest-api/
├── public/
│   ├── class-zaobank-public.php
│   └── templates/                   # Shortcode templates
│       ├── dashboard.php
│       ├── jobs-list.php
│       ├── job-single.php
│       ├── job-form.php
│       ├── my-jobs.php
│       ├── profile.php
│       ├── profile-edit.php
│       ├── messages.php
│       ├── conversation.php
│       ├── exchanges.php
│       ├── appreciations.php
│       └── components/
│           ├── bottom-nav.php
│           └── subpage-tabs.php
└── zaobank-core.php
```

### Contributing

This plugin follows WordPress Coding Standards.

## License

GPL v2 or later

## Support

For support and documentation, visit https://zaobank.org

## Credits

Built with care for church communities that prioritize:
- Hospitality over borders
- Safety without carcerality
- Memory without surveillance
- Accountability with grace
