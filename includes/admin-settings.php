<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'wow_add_admin_menu' );
add_action( 'admin_init', 'wow_register_settings' );

function wow_add_admin_menu() {
    add_options_page(
        __( 'Watch Out Wednesday Settings', 'watch-out-wednesday' ),
        __( 'Watch Out Wednesday', 'watch-out-wednesday' ),
        'manage_options',
        'wow-settings',
        'wow_settings_page_html'
    );
}

function wow_register_settings() {
    $args = array(
        'type'              => 'array',
        'sanitize_callback' => 'wow_sanitize_settings',
        'default'           => array(),
        'autoload'          => false,
    );
    register_setting( 'wow_settings_group', 'wow_settings', $args );
}

function wow_render_enable_toggle() {
    $options = get_option( 'wow_settings', array() );
    $enabled = ! empty( $options['enabled'] );
    ?>
    <input type="hidden" name="wow_settings[enabled]" value="0">
    <span class="wow-switch">
        <input id="wow-enabled" type="checkbox" name="wow_settings[enabled]" value="1" <?php checked( $enabled, true ); ?> aria-describedby="wow-enabled-description">
        <span class="wow-slider round"></span>
    </span>
    <p class="description" id="wow-enabled-description"><?php esc_html_e( 'Turn on to publish the Watch Out Wednesday post every Wednesday.', 'watch-out-wednesday' ); ?></p>
    <?php
}

function wow_render_next_run() {
    $next = function_exists( 'wp_next_scheduled' ) ? wp_next_scheduled( 'wow_weekly_cron_event' ) : false;
    if ( $next ) {
        echo '<p>' . esc_html( wp_date( 'l, F j, Y g:i a', (int) $next ) ) . '</p>';
        echo '<p class="description">' . esc_html__( 'Shown in the site timezone. The time is not configurable. Turning weekly posts on during Wednesday waits until the following Wednesday.', 'watch-out-wednesday' ) . '</p>';
        return;
    }

    echo '<p>' . esc_html__( 'Not scheduled. Turn on weekly posts and save.', 'watch-out-wednesday' ) . '</p>';
}

function wow_render_last_run_info() {
    $last_run = get_option( 'wow_last_run' );

    if ( ! $last_run ) {
        echo '<p>' . esc_html__( 'No run data available yet.', 'watch-out-wednesday' ) . '</p>';
        return;
    }

    $date       = isset( $last_run['date'] ) ? $last_run['date'] : __( 'N/A', 'watch-out-wednesday' );
    $url        = isset( $last_run['post_url'] ) ? $last_run['post_url'] : '';
    $vuln_count = isset( $last_run['vulnerability_count'] ) ? (int) $last_run['vulnerability_count'] : 0;

    echo '<p><strong>' . esc_html__( 'Date:', 'watch-out-wednesday' ) . '</strong> ' . esc_html( $date ) . '</p>';
    if ( ! empty( $url ) ) {
        echo '<p><strong>' . esc_html__( 'Post:', 'watch-out-wednesday' ) . '</strong> <a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View Post', 'watch-out-wednesday' ) . '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'watch-out-wednesday' ) . '</span></a></p>';
    }
    echo '<p><strong>' . esc_html__( 'Vulnerabilities:', 'watch-out-wednesday' ) . '</strong> ' . esc_html( (string) $vuln_count ) . '</p>';
}

function wow_render_intro_text() {
    $options = get_option( 'wow_settings', array() );
    $intro   = isset( $options['intro_text'] ) ? $options['intro_text'] : '';
    echo '<textarea id="wow-intro" name="wow_settings[intro_text]" rows="6" cols="60" aria-describedby="wow-intro-description">' . esc_textarea( $intro ) . '</textarea>';
    echo '<p class="description" id="wow-intro-description">' . esc_html__( 'Shown at the top of every generated post. Basic HTML is kept.', 'watch-out-wednesday' ) . '</p>';
}

function wow_render_outro_text() {
    $options = get_option( 'wow_settings', array() );
    $outro   = isset( $options['outro_text'] ) ? $options['outro_text'] : '';
    echo '<textarea id="wow-outro" name="wow_settings[outro_text]" rows="4" cols="60" aria-describedby="wow-outro-description">' . esc_textarea( $outro ) . '</textarea>';
    echo '<p class="description" id="wow-outro-description">' . esc_html__( 'Shown at the bottom of every generated post. Basic HTML is kept.', 'watch-out-wednesday' ) . '</p>';
}

function wow_render_email() {
    $options = get_option( 'wow_settings', array() );
    $email   = isset( $options['email'] ) ? $options['email'] : '';
    echo '<input id="wow-email" type="email" name="wow_settings[email]" value="' . esc_attr( $email ) . '" class="regular-text" aria-describedby="wow-email-description">';
    echo '<p class="description" id="wow-email-description">' . esc_html__( 'Sent when a run publishes or updates a post. Leave blank to skip that message. If a run fails, the error is still emailed to the site admin address. A bulk backfill sends one summary email.', 'watch-out-wednesday' ) . '</p>';
}

function wow_render_api_key() {
    $options = get_option( 'wow_settings', array() );
    $stored  = ! empty( $options['wordfence_api_key'] );
    echo '<input id="wow-api-key" type="password" name="wow_settings[wordfence_api_key]" value="" class="regular-text" autocomplete="off" aria-describedby="wow-api-key-description">';
    echo '<p class="description" id="wow-api-key-description">';
    if ( $stored ) {
        esc_html_e( 'A key is saved. Leave this blank to keep it. The key is yours, from your Wordfence account. Duppins Technology is not billed. The published default limit is one request per 30 minutes.', 'watch-out-wednesday' );
    } else {
        esc_html_e( 'Paste your Wordfence Intelligence v3 API key. Watch Out Wednesday does not include a key. Duppins Technology is not billed. The published default limit is one request per 30 minutes.', 'watch-out-wednesday' );
    }
    echo '</p>';
    echo '<label for="wow-api-key-clear"><input id="wow-api-key-clear" type="checkbox" name="wow_settings[wordfence_api_key_clear]" value="1"> ' . esc_html__( 'Remove the saved API key', 'watch-out-wednesday' ) . '</label>';
}

function wow_render_api_endpoint() {
    $options  = get_option( 'wow_settings', array() );
    $endpoint = ! empty( $options['wordfence_api_endpoint'] ) ? $options['wordfence_api_endpoint'] : wow_default_wordfence_endpoint();
    echo '<input id="wow-api-endpoint" type="url" name="wow_settings[wordfence_api_endpoint]" value="' . esc_attr( $endpoint ) . '" class="regular-text code" aria-describedby="wow-api-endpoint-description">';
    echo '<p class="description" id="wow-api-endpoint-description">' . esc_html__( 'Public https URL. Keep the default unless Wordfence gave you a custom endpoint. Each run downloads the full feed (about 123 MB in Wordfence’s February 2026 notice). A 128 MB PHP memory limit can fail the run. The feed is not stored in WordPress.', 'watch-out-wednesday' ) . '</p>';
}

function wow_render_category_dropdown() {
    $options           = get_option( 'wow_settings', array() );
    $selected_category = isset( $options['category_id'] ) ? absint( $options['category_id'] ) : 0;
    $categories        = get_categories( array( 'hide_empty' => false ) );
    ?>
    <select id="wow-category" name="wow_settings[category_id]" aria-describedby="wow-category-description">
        <option value="0" <?php selected( $selected_category, 0 ); ?>><?php esc_html_e( 'Site default category', 'watch-out-wednesday' ); ?></option>
        <?php foreach ( $categories as $category ) : ?>
            <option value="<?php echo esc_attr( $category->term_id ); ?>" <?php selected( $selected_category, $category->term_id ); ?>>
                <?php echo esc_html( $category->name ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description" id="wow-category-description"><?php esc_html_e( 'Site default category uses the category WordPress assigns to new posts. Choose a named category to force that category.', 'watch-out-wednesday' ); ?></p>
    <?php
}

function wow_render_image_url() {
    $options   = get_option( 'wow_settings', array() );
    $image_url = isset( $options['image_url'] ) ? esc_url( $options['image_url'] ) : '';
    $image_id  = ( '' !== $image_url && function_exists( 'attachment_url_to_postid' ) ) ? (int) attachment_url_to_postid( $image_url ) : 0;
    $alt       = $image_id ? get_the_title( $image_id ) : __( 'Selected featured image', 'watch-out-wednesday' );
    ?>
    <input type="hidden" name="wow_settings[image_url]" id="wow_featured_image" value="<?php echo esc_attr( $image_url ); ?>" />
    <button type="button" class="button" id="wow_select_image_button"><?php esc_html_e( 'Select Featured Image', 'watch-out-wednesday' ); ?></button>
    <button type="button" class="button" id="wow_remove_image_button"><?php esc_html_e( 'Remove featured image', 'watch-out-wednesday' ); ?></button>
    <p class="description" id="wow-image-description"><?php esc_html_e( 'Choose an image from the Media Library. The plugin uses that attachment as the featured image. A URL that is not in the library is ignored.', 'watch-out-wednesday' ); ?></p>
    <?php if ( '' !== $image_url && ! $image_id ) : ?>
        <p class="description"><?php esc_html_e( 'This URL is not in the Media Library, so the featured image will be skipped.', 'watch-out-wednesday' ); ?></p>
    <?php endif; ?>
    <div id="wow_image_preview">
        <?php if ( ! empty( $image_url ) ) : ?>
            <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $alt ); ?>" style="max-width: 300px; display:block; margin-top:10px;" />
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Merge one settings form into the stored option.
 *
 * @param array $input Submitted values.
 * @return array
 */
function wow_sanitize_settings( $input ) {
    if ( function_exists( 'delete_transient' ) ) {
        delete_transient( 'wow_autoload_checked' );
    }

    $existing = get_option( 'wow_settings', array() );
    $output   = is_array( $existing ) ? $existing : array();
    if ( ! is_array( $input ) ) {
        $input = array();
    }

    if ( array_key_exists( 'enabled', $input ) ) {
        $output['enabled'] = ! empty( $input['enabled'] ) ? 1 : 0;
    }
    if ( array_key_exists( 'intro_text', $input ) ) {
        $output['intro_text'] = wp_kses_post( $input['intro_text'] );
    }
    if ( array_key_exists( 'outro_text', $input ) ) {
        $output['outro_text'] = wp_kses_post( $input['outro_text'] );
    }
    if ( array_key_exists( 'email', $input ) ) {
        $raw = trim( (string) $input['email'] );
        if ( '' === $raw ) {
            $output['email'] = '';
        } else {
            $clean = sanitize_email( $raw );
            if ( '' === $clean || ! is_email( $clean ) ) {
                add_settings_error(
                    'wow_settings',
                    'wow_email',
                    __( 'That notification email is not valid. The previous address was kept.', 'watch-out-wednesday' )
                );
            } else {
                $output['email'] = $clean;
            }
        }
    }
    if ( array_key_exists( 'category_id', $input ) ) {
        $output['category_id'] = absint( $input['category_id'] );
    }
    if ( array_key_exists( 'image_url', $input ) ) {
        $output['image_url'] = esc_url_raw( $input['image_url'] );
        if ( '' !== $output['image_url'] && function_exists( 'attachment_url_to_postid' ) && ! attachment_url_to_postid( $output['image_url'] ) ) {
            add_settings_error(
                'wow_settings',
                'wow_image',
                __( 'That featured image URL is not in the Media Library. The post will publish without it until you choose an attachment.', 'watch-out-wednesday' )
            );
        }
    }
    if ( ! empty( $input['wordfence_api_key_clear'] ) ) {
        $output['wordfence_api_key'] = '';
    } elseif ( array_key_exists( 'wordfence_api_key', $input ) ) {
        $submitted = sanitize_text_field( $input['wordfence_api_key'] );
        if ( '' !== $submitted ) {
            $output['wordfence_api_key'] = $submitted;
        }
    }
    if ( array_key_exists( 'wordfence_api_endpoint', $input ) ) {
        $endpoint = esc_url_raw( $input['wordfence_api_endpoint'] );
        if ( '' === $endpoint ) {
            $output['wordfence_api_endpoint'] = wow_default_wordfence_endpoint();
        } elseif ( ! wow_is_public_https_url( $endpoint ) ) {
            add_settings_error(
                'wow_settings',
                'wow_endpoint',
                __( 'The endpoint must be a public https URL. The previous endpoint was kept.', 'watch-out-wednesday' )
            );
            if ( empty( $output['wordfence_api_endpoint'] ) ) {
                $output['wordfence_api_endpoint'] = wow_default_wordfence_endpoint();
            }
        } else {
            $output['wordfence_api_endpoint'] = $endpoint;
        }
    } elseif ( empty( $output['wordfence_api_endpoint'] ) ) {
        $output['wordfence_api_endpoint'] = wow_default_wordfence_endpoint();
    }

    return $output;
}

function wow_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $options = get_option( 'wow_settings', array() );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Watch Out Wednesday Settings', 'watch-out-wednesday' ); ?></h1>
        <?php settings_errors( 'wow_settings' ); ?>
        <?php if ( empty( $options['wordfence_api_key'] ) ) : ?>
            <div class="notice notice-warning"><p><?php esc_html_e( 'Wordfence API key is missing. Open the Authentication tab and save a v3 key before running.', 'watch-out-wednesday' ); ?></p></div>
        <?php endif; ?>
        <div class="nav-tab-wrapper" id="wow-settings-tabs" role="tablist">
            <a href="#wow-tab-content" id="wow-tab-link-content" class="nav-tab nav-tab-active" role="tab" aria-selected="true" aria-controls="wow-tab-content"><?php esc_html_e( 'Content', 'watch-out-wednesday' ); ?></a>
            <a href="#wow-tab-manual" id="wow-tab-link-manual" class="nav-tab" role="tab" aria-selected="false" aria-controls="wow-tab-manual"><?php esc_html_e( 'Manual Run', 'watch-out-wednesday' ); ?></a>
            <a href="#wow-tab-auth" id="wow-tab-link-auth" class="nav-tab" role="tab" aria-selected="false" aria-controls="wow-tab-auth"><?php esc_html_e( 'Authentication', 'watch-out-wednesday' ); ?></a>
        </div>

        <div id="wow-tab-content" class="wow-tab-panel" role="tabpanel" aria-labelledby="wow-tab-link-content">
            <form method="post" action="options.php">
                <?php settings_fields( 'wow_settings_group' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wow-enabled"><?php esc_html_e( 'Enable weekly posts', 'watch-out-wednesday' ); ?></label></th>
                        <td><?php wow_render_enable_toggle(); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Next weekly run', 'watch-out-wednesday' ); ?></th>
                        <td><?php wow_render_next_run(); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Last run', 'watch-out-wednesday' ); ?></th>
                        <td><?php wow_render_last_run_info(); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wow-intro"><?php esc_html_e( 'Intro', 'watch-out-wednesday' ); ?></label></th>
                        <td><?php wow_render_intro_text(); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wow-outro"><?php esc_html_e( 'Outro', 'watch-out-wednesday' ); ?></label></th>
                        <td><?php wow_render_outro_text(); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wow-category"><?php esc_html_e( 'Post category', 'watch-out-wednesday' ); ?></label></th>
                        <td><?php wow_render_category_dropdown(); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wow_featured_image"><?php esc_html_e( 'Default featured image', 'watch-out-wednesday' ); ?></label></th>
                        <td><?php wow_render_image_url(); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wow-email"><?php esc_html_e( 'Notification email', 'watch-out-wednesday' ); ?></label></th>
                        <td><?php wow_render_email(); ?></td>
                    </tr>
                </table>
                <?php submit_button( __( 'Save Content Settings', 'watch-out-wednesday' ) ); ?>
            </form>
        </div>

        <div id="wow-tab-manual" class="wow-tab-panel" role="tabpanel" aria-labelledby="wow-tab-link-manual">
            <p><?php esc_html_e( 'Run the current week, publish one missed Wednesday, or backfill several past weeks. Each run publishes the post. A week with no vulnerabilities is saved as a draft.', 'watch-out-wednesday' ); ?></p>
            <p><?php esc_html_e( 'The weekly run is the next Wednesday at 00:00 in the site timezone, then every 7 days. A post dated a Wednesday includes vulnerabilities published or updated from the previous Wednesday through that Tuesday, using the current Wordfence feed. The date you enter must be a Wednesday. Before Wednesday, Run Current Week Now uses the coming Wednesday and WordPress holds the post as scheduled until then.', 'watch-out-wednesday' ); ?></p>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'wow_manual_run_action', 'wow_manual_run_nonce' ); ?>
                <input type="hidden" name="action" value="wow_manual_run" />
                <?php submit_button( __( 'Run Current Week Now', 'watch-out-wednesday' ), 'primary', 'wow_run_now', false ); ?>
            </form>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 1rem;">
                <?php wp_nonce_field( 'wow_manual_run_action', 'wow_manual_run_nonce' ); ?>
                <input type="hidden" name="action" value="wow_manual_run" />
                <label for="wow_target_wednesday"><strong><?php esc_html_e( 'Run a missed week (Wednesday date)', 'watch-out-wednesday' ); ?></strong></label><br />
                <input type="date" id="wow_target_wednesday" name="wow_target_wednesday" required aria-describedby="wow-target-description" />
                <p class="description" id="wow-target-description"><?php esc_html_e( 'The date must be a Wednesday.', 'watch-out-wednesday' ); ?></p>
                <?php submit_button( __( 'Run Selected Week', 'watch-out-wednesday' ), 'secondary', 'wow_run_specific', false ); ?>
            </form>

            <form id="wow-bulk-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 1rem;">
                <?php wp_nonce_field( 'wow_manual_run_action', 'wow_manual_run_nonce' ); ?>
                <input type="hidden" name="action" value="wow_manual_run" />
                <label for="wow_bulk_count"><strong><?php esc_html_e( 'Bulk backfill missed weeks', 'watch-out-wednesday' ); ?></strong></label><br />
                <input type="number" min="1" max="260" id="wow_bulk_count" name="wow_bulk_count" value="10" required />
                <label for="wow_bulk_end_wednesday" style="margin-left: 1rem;"><?php esc_html_e( 'Ending Wednesday', 'watch-out-wednesday' ); ?></label>
                <input type="date" id="wow_bulk_end_wednesday" name="wow_bulk_end_wednesday" />
                <?php submit_button( __( 'Run Bulk Backfill', 'watch-out-wednesday' ), 'secondary', 'wow_run_bulk', false ); ?>
                <p class="description"><?php esc_html_e( 'Walks backward one Wednesday at a time from the ending date. Leave the date blank to start at the current relevant Wednesday. Each week is filled from the current Wordfence feed, not from a snapshot taken that week. A week with no matches is saved as a draft. A large backfill may stop early so the request can finish. The notice names any failed weeks and tells you how to run the rest. One summary email is sent.', 'watch-out-wednesday' ); ?></p>
            </form>
        </div>

        <div id="wow-tab-auth" class="wow-tab-panel" role="tabpanel" aria-labelledby="wow-tab-link-auth">
            <form method="post" action="options.php">
                <?php settings_fields( 'wow_settings_group' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wow-api-key"><?php esc_html_e( 'Wordfence API key', 'watch-out-wednesday' ); ?></label></th>
                        <td><?php wow_render_api_key(); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wow-api-endpoint"><?php esc_html_e( 'Wordfence v3 endpoint', 'watch-out-wednesday' ); ?></label></th>
                        <td><?php wow_render_api_endpoint(); ?></td>
                    </tr>
                </table>
                <p class="description"><?php esc_html_e( 'A run publishes a copy of Wordfence Intelligence data. You need your own key, and your use of the feed is covered by the Wordfence Intelligence terms.', 'watch-out-wednesday' ); ?> <a href="https://www.wordfence.com/help/wordfence-intelligence/v3-accessing-and-consuming-the-vulnerability-data-feed/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View setup guide', 'watch-out-wednesday' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'watch-out-wednesday' ); ?></span></a> <a href="https://www.wordfence.com/wordfence-intelligence-terms-and-conditions/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Intelligence terms', 'watch-out-wednesday' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'watch-out-wednesday' ); ?></span></a></p>
                <?php submit_button( __( 'Save Authentication Settings', 'watch-out-wednesday' ) ); ?>
            </form>
        </div>
    </div>
    <?php
}
