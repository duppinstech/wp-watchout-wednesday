<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Add menu item
add_action('admin_menu', 'wow_add_admin_menu');
add_action('admin_init', 'wow_register_settings');

function wow_add_admin_menu() {
    add_options_page(
        'Watch Out Wednesday Settings',
        'Watch Out Wednesday',
        'manage_options',
        'wow-settings',
        'wow_settings_page_html'
    );
}

function wow_register_settings() {
    register_setting('wow_settings_group', 'wow_settings', [
        'type' => 'array',
        'sanitize_callback' => 'wow_sanitize_settings',
        'default' => [],
    ]);
}

// Render fields
function wow_render_enable_toggle() {
    $options = get_option('wow_settings');
    $enabled = $options['enabled'] ?? false;
    ?>
    <label class="wow-switch">
        <input type="checkbox" name="wow_settings[enabled]" value="1" <?php checked($enabled, 1); ?>>
        <span class="wow-slider round"></span>
    </label>
    <p class="description">Toggle to enable or disable automated post generation.</p>
    <?php
}

function wow_render_last_run_info() {
    $last_run = get_option('wow_last_run'); // stored separately
    
    if (!$last_run) {
        echo '<p>No run data available yet.</p>';
        return;
    }

    $date = $last_run['date'] ?? 'N/A';
    $url = $last_run['post_url'] ?? '';
    $vuln_count = intval($last_run['vulnerability_count'] ?? 0);

    echo '<p><strong>Date:</strong> ' . esc_html($date) . '</p>';
    if (!empty($url)) {
        echo '<p><strong>Post:</strong> <a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">View Post</a></p>';
    }
    echo '<p><strong>Vulnerabilities:</strong> ' . esc_html((string) $vuln_count) . '</p>';
}

function wow_render_intro_text() {
    $options = get_option('wow_settings');
    $intro = $options['intro_text'] ?? '';
    echo '<textarea name="wow_settings[intro_text]" rows="6" cols="60">' . esc_textarea($intro) . '</textarea>';
}

function wow_render_outro_text() {
    $options = get_option('wow_settings');
    $outro = $options['outro_text'] ?? '';
    echo '<textarea name="wow_settings[outro_text]" rows="4" cols="60">' . esc_textarea($outro) . '</textarea>';
}


function wow_render_email() {
    $options = get_option('wow_settings');
    echo '<input type="email" name="wow_settings[email]" value="' . esc_attr($options['email'] ?? '') . '" class="regular-text">';
}

function wow_render_api_key() {
    $options = get_option('wow_settings');
    $api_key = isset($options['wordfence_api_key']) ? $options['wordfence_api_key'] : '';
    echo '<input type="password" name="wow_settings[wordfence_api_key]" value="' . esc_attr($api_key) . '" class="regular-text" autocomplete="off">';
    echo '<p class="description">Wordfence Intelligence v3 API key.</p>';
}

function wow_render_api_endpoint() {
    $options = get_option('wow_settings');
    $endpoint = isset($options['wordfence_api_endpoint']) ? $options['wordfence_api_endpoint'] : 'https://www.wordfence.com/api/intelligence/v3/vulnerabilities/production';
    echo '<input type="url" name="wow_settings[wordfence_api_endpoint]" value="' . esc_attr($endpoint) . '" class="regular-text code">';
    echo '<p class="description">V3 feed URL. Keep default unless Wordfence gave you a custom endpoint.</p>';
}

function wow_render_category_dropdown() {
    $options = get_option('wow_settings');
    $selected_category = isset($options['category_id']) ? absint($options['category_id']) : 0;
    $categories = get_categories(array('hide_empty' => false));
    ?>
    <select name="wow_settings[category_id]">
        <option value="0" <?php selected($selected_category, 0); ?>>Uncategorized</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?php echo esc_attr($category->term_id); ?>" <?php selected($selected_category, $category->term_id); ?>>
                <?php echo esc_html($category->name); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description">Select the category for the generated posts.</p>
    <?php
}

function wow_render_image_url() {
    $options = get_option('wow_settings');
    $image_url = isset($options['image_url']) ? esc_url($options['image_url']) : '';
    ?>
    <input type="hidden" name="wow_settings[image_url]" id="wow_featured_image" value="<?php echo esc_attr($image_url); ?>" />
    <button type="button" class="button" id="wow_select_image_button">Select Featured Image</button>
    <p class="description">Select the default featured image for generated posts.</p>
    <div id="wow_image_preview">
        <?php if (!empty($image_url)) { ?>
            <img src="<?php echo esc_url($image_url); ?>" style="max-width: 300px; display:block; margin-top:10px;" />
        <?php } ?>
    </div>
    <?php
}

function wow_sanitize_settings($input) {
    $existing = get_option('wow_settings', array());
    $output = is_array($existing) ? $existing : array();

    if (array_key_exists('enabled', $input)) {
        $output['enabled'] = !empty($input['enabled']) ? 1 : 0;
    }
    if (array_key_exists('intro_text', $input)) {
        $output['intro_text'] = wp_kses_post($input['intro_text']);
    }
    if (array_key_exists('outro_text', $input)) {
        $output['outro_text'] = wp_kses_post($input['outro_text']);
    }
    if (array_key_exists('email', $input)) {
        $output['email'] = sanitize_email($input['email']);
    }
    if (array_key_exists('category_id', $input)) {
        $output['category_id'] = absint($input['category_id']);
    }
    if (array_key_exists('image_url', $input)) {
        $output['image_url'] = esc_url_raw($input['image_url']);
    }
    if (array_key_exists('wordfence_api_key', $input)) {
        $output['wordfence_api_key'] = sanitize_text_field($input['wordfence_api_key']);
    }
    if (array_key_exists('wordfence_api_endpoint', $input)) {
        $output['wordfence_api_endpoint'] = esc_url_raw($input['wordfence_api_endpoint']);
    } elseif (empty($output['wordfence_api_endpoint'])) {
        $output['wordfence_api_endpoint'] = 'https://www.wordfence.com/api/intelligence/v3/vulnerabilities/production';
    }

    return $output;
}

// HTML page
function wow_settings_page_html() {
    if (!current_user_can('manage_options')) return;

    ?>
    <div class="wrap">
        <h1>Watch Out Wednesday Settings</h1>
        <h2 class="nav-tab-wrapper" id="wow-settings-tabs">
            <a href="#wow-tab-content" class="nav-tab nav-tab-active">Content</a>
            <a href="#wow-tab-manual" class="nav-tab">Manual Run</a>
            <a href="#wow-tab-auth" class="nav-tab">Authentication</a>
        </h2>

        <div id="wow-tab-content" class="wow-tab-panel">
            <form method="post" action="options.php">
                <?php settings_fields('wow_settings_group'); ?>
                <table class="form-table" role="presentation">
                    <tr><th scope="row">Enable Script</th><td><?php wow_render_enable_toggle(); ?></td></tr>
                    <tr><th scope="row">Last Run Info</th><td><?php wow_render_last_run_info(); ?></td></tr>
                    <tr><th scope="row">Enter Intro Text</th><td><?php wow_render_intro_text(); ?></td></tr>
                    <tr><th scope="row">Enter Outro Text</th><td><?php wow_render_outro_text(); ?></td></tr>
                    <tr><th scope="row">Select Post Category</th><td><?php wow_render_category_dropdown(); ?></td></tr>
                    <tr><th scope="row">Default Featured Image</th><td><?php wow_render_image_url(); ?></td></tr>
                    <tr><th scope="row">Notification Email</th><td><?php wow_render_email(); ?></td></tr>
                </table>
                <?php submit_button('Save Content Settings'); ?>
            </form>
        </div>

        <div id="wow-tab-manual" class="wow-tab-panel" style="display:none;">
            <p>Run now, generate a specific missed Wednesday, or backfill multiple weeks in one click.</p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('wow_manual_run_action', 'wow_manual_run_nonce'); ?>
                <input type="hidden" name="action" value="wow_manual_run" />
                <?php submit_button('Run Current Week Now', 'secondary', 'wow_run_now', false); ?>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top: 1rem;">
                <?php wp_nonce_field('wow_manual_run_action', 'wow_manual_run_nonce'); ?>
                <input type="hidden" name="action" value="wow_manual_run" />
                <label for="wow_target_wednesday"><strong>Run a missed week (Wednesday date)</strong></label><br />
                <input type="date" id="wow_target_wednesday" name="wow_target_wednesday" required />
                <?php submit_button('Run Selected Week', 'secondary', 'wow_run_specific', false); ?>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top: 1rem;">
                <?php wp_nonce_field('wow_manual_run_action', 'wow_manual_run_nonce'); ?>
                <input type="hidden" name="action" value="wow_manual_run" />
                <label for="wow_bulk_count"><strong>Bulk backfill missed weeks</strong></label><br />
                <input type="number" min="1" max="260" id="wow_bulk_count" name="wow_bulk_count" value="10" required />
                <label for="wow_bulk_end_wednesday" style="margin-left: 1rem;">Ending Wednesday</label>
                <input type="date" id="wow_bulk_end_wednesday" name="wow_bulk_end_wednesday" />
                <?php submit_button('Run Bulk Backfill', 'secondary', 'wow_run_bulk', false); ?>
                <p class="description">Runs the selected number of Wednesdays going backwards from the ending date (or current relevant Wednesday if blank).</p>
            </form>
        </div>

        <div id="wow-tab-auth" class="wow-tab-panel" style="display:none;">
            <form method="post" action="options.php">
                <?php settings_fields('wow_settings_group'); ?>
                <table class="form-table" role="presentation">
                    <tr><th scope="row">Wordfence API Key</th><td><?php wow_render_api_key(); ?></td></tr>
                    <tr><th scope="row">Wordfence V3 Endpoint</th><td><?php wow_render_api_endpoint(); ?></td></tr>
                </table>
                <p class="description">Wordfence v3 feed setup documentation: <a href="https://www.wordfence.com/help/wordfence-intelligence/v3-accessing-and-consuming-the-vulnerability-data-feed/" target="_blank" rel="noopener noreferrer">View setup guide</a>.</p>
                <?php submit_button('Save Authentication Settings'); ?>
            </form>
        </div>
    </div>
    <?php
}

