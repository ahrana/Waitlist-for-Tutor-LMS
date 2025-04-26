<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WFTL_Admin_Interface {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'wp_ajax_wftl_notify_user', array( $this, 'handle_notify_user' ) );
        add_action( 'wp_ajax_wftl_delete_entry', array( $this, 'handle_delete_entry' ) );
        add_action( 'wp_ajax_wftl_export_waitlist', array( $this, 'handle_export_waitlist' ) );
        add_action( 'wp_ajax_wftl_notify_all', array( $this, 'handle_notify_all' ) );
    }

    public function add_admin_menu() {
        add_submenu_page(
            'tutor',
            esc_html__( 'Course Waitlists', 'waitlist-for-tutor-lms' ),
            esc_html__( 'Waitlists', 'waitlist-for-tutor-lms' ),
            'manage_options',
            'wftl-waitlists',
            array( $this, 'render_admin_page' )
        );
    }

    public function render_admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tutor_waitlist';
        
        $courses = $wpdb->get_results(
            $wpdb->prepare( "SELECT DISTINCT course_id FROM {$table_name} WHERE status IN ('waiting', 'notified')" ),
            ARRAY_A
        );
        
        // Get total waiting students
        $total_waiting = $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE status = %s", 'waiting' )
        );
        ?>
        <div class="wrap" style="background: #f9f9f9; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h1 style="color: #333; margin: 0;"><?php echo esc_html__( 'Course Waitlists', 'waitlist-for-tutor-lms' ); ?></h1>
                <span style="background: #e67e22; color: #fff; padding: 5px 15px; border-radius: 15px; font-size: 14px;">
                    <?php echo esc_html__( 'Total Students in Waiting Lists: ', 'waitlist-for-tutor-lms' ) . esc_html( $total_waiting ); ?>
                </span>
            </div>
            
            <?php foreach ( $courses as $course ) : 
                $course_id = absint( $course['course_id'] );
                $waitlist = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM {$table_name} WHERE course_id = %d AND status IN ('waiting', 'notified') ORDER BY date_added ASC",
                        $course_id
                    )
                );
                
                // Get all enrollments for this course (no pagination limit)
                $enrollments_list = tutor_utils()->get_enrolments( 'all', 0, 9999, '', $course_id, '', 'DESC' );
                $enrolled_waitlist = array();
                $active_waitlist = array();

                // Separate waitlist into enrolled and active waitlist
                foreach ( $waitlist as $entry ) {
                    $is_enrolled = false;
                    foreach ( $enrollments_list as $enrollment ) {
                        if ( $entry->user_email === $enrollment->user_email ) {
                            $is_enrolled = true;
                            $enrolled_waitlist[] = $entry;
                            break;
                        }
                    }
                    if ( ! $is_enrolled ) {
                        $active_waitlist[] = $entry;
                    }
                }
                ?>
                <div class="wftl-course-waitlist tutor-mt-32" style="background: #fff; padding: 20px; border-radius: 5px; border: 1px solid #ddd; margin-bottom: 20px;">
                    <h2 style="color: #0073aa; margin-top: 0;"><?php echo esc_html__( 'Course: ', 'waitlist-for-tutor-lms' ) . esc_html( get_the_title( $course_id ) . ' (' . $course_id . ')' ); ?></h2>
                    <div style="margin-bottom: 15px;">
                        <button class="button wftl-export-waitlist" 
                                data-course-id="<?php echo esc_attr( $course_id ); ?>"
                                style="margin-right: 10px; background: #0073aa; color: #fff; border: none;">
                            <?php echo esc_html__( 'Export Waitlist', 'waitlist-for-tutor-lms' ); ?>
                        </button>
                        <button class="button wftl-notify-all" 
                                data-course-id="<?php echo esc_attr( $course_id ); ?>"
                                style="background: #46b450; color: #fff; border: none;">
                            <?php echo esc_html__( 'Notify All', 'waitlist-for-tutor-lms' ); ?>
                        </button>
                    </div>

                    <!-- Active Waitlist Section -->
                    <h3 style="color: #e67e22; margin: 20px 0 10px;">Waitlist</h3>
                    <table class="wp-list-table widefat fixed striped" style="border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f1f1f1;">
                                <th style="padding: 12px; color: #333;"><?php echo esc_html__( 'Email', 'waitlist-for-tutor-lms' ); ?></th>
                                <th style="padding: 12px; color: #333;"><?php echo esc_html__( 'Date Added', 'waitlist-for-tutor-lms' ); ?></th>
                                <th style="padding: 12px; color: #333;"><?php echo esc_html__( 'Status', 'waitlist-for-tutor-lms' ); ?></th>
                                <th style="padding: 12px; color: #333;"><?php echo esc_html__( 'Actions', 'waitlist-for-tutor-lms' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( empty( $active_waitlist ) ) : ?>
                                <tr><td colspan="4" style="padding: 12px; text-align: center;">No active waitlist entries</td></tr>
                            <?php else : ?>
                                <?php foreach ( $active_waitlist as $entry ) : ?>
                                    <tr>
                                        <td style="padding: 12px;"><?php echo esc_html( $entry->user_email ); ?></td>
                                        <td style="padding: 12px;"><?php echo esc_html( $entry->date_added ); ?></td>
                                        <td style="padding: 12px;">
                                            <span style="display: inline-block; padding: 5px 10px; border-radius: 3px; color: #fff; <?php echo $entry->status === 'waiting' ? 'background: #e67e22;' : 'background: #27ae60;'; ?>">
                                                <?php echo esc_html( ucfirst( $entry->status ) ); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px;">
                                            <button class="button wftl-notify-user" 
                                                    data-entry-id="<?php echo esc_attr( $entry->id ); ?>"
                                                    data-course-id="<?php echo esc_attr( $course_id ); ?>"
                                                    style="margin-right: 5px;">
                                                <?php echo esc_html__( 'Notify', 'waitlist-for-tutor-lms' ); ?>
                                            </button>
                                            <button class="button wftl-delete-entry" 
                                                    data-entry-id="<?php echo esc_attr( $entry->id ); ?>"
                                                    style="background: #d63638; color: #fff; border: none;">
                                                <?php echo esc_html__( 'Delete', 'waitlist-for-tutor-lms' ); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Enrolled Users Section -->
                    <h3 style="color: #27ae60; margin: 20px 0 10px;">Enrolled Users (From Waitlist)</h3>
                    <table class="wp-list-table widefat fixed striped" style="border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f1f1f1;">
                                <th style="padding: 12px; color: #333;"><?php echo esc_html__( 'Email', 'waitlist-for-tutor-lms' ); ?></th>
                                <th style="padding: 12px; color: #333;"><?php echo esc_html__( 'Date Added to Waitlist', 'waitlist-for-tutor-lms' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( empty( $enrolled_waitlist ) ) : ?>
                                <tr><td colspan="2" style="padding: 12px; text-align: center;">No enrolled users from waitlist</td></tr>
                            <?php else : ?>
                                <?php foreach ( $enrolled_waitlist as $entry ) : ?>
                                    <tr>
                                        <td style="padding: 12px;"><?php echo esc_html( $entry->user_email ); ?></td>
                                        <td style="padding: 12px;"><?php echo esc_html( $entry->date_added ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
            <div style="margin-top: 40px; padding: 15px; background: #2c3e50; color: #ecf0f1; border-radius: 5px; text-align: center; font-size: 14px;">
                <span style="margin-right: 20px;">
                    <span class="dashicons dashicons-email" style="color: #9b59b6; margin-right: 5px;"></span>
                    <a href="mailto:cxranabd@gmail.com" style="color: #3498db; text-decoration: none;">Feature Request</a>
                </span>
                <span style="margin-right: 20px;">
                    <span class="dashicons dashicons-wordpress" style="color: #f1c40f; margin-right: 5px;"></span>
                    <a href="https://wordpress.org/plugins/video-for-tutor-lms/" target="_blank" style="color: #3498db; text-decoration: none;">More Tutor Plugin</a>
                </span>
                <span>
                    <span class="dashicons dashicons-admin-site" style="color: #2ecc71; margin-right: 5px;"></span>
                    <a href="https://cxrana.wordpress.com/" target="_blank" style="color: #3498db; text-decoration: none;">Website</a>
                </span>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            // Notify User
            $('.wftl-notify-user').click(function(e) {
                e.preventDefault();
                var $button = $(this);
                var entryId = $button.data('entry-id');
                var courseId = $button.data('course-id');
                
                $.ajax({
                    url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
                    method: 'POST',
                    data: {
                        action: 'wftl_notify_user',
                        entry_id: entryId,
                        course_id: courseId,
                        nonce: '<?php echo esc_js( wp_create_nonce( 'wftl_admin_nonce' ) ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $button.closest('tr').find('td:eq(2)').html('<span style="display: inline-block; padding: 5px 10px; border-radius: 3px; color: #fff; background: #27ae60;">Notified</span>');
                            alert('<?php echo esc_js( __( 'User notified successfully', 'waitlist-for-tutor-lms' ) ); ?>');
                        } else {
                            alert('<?php echo esc_js( __( 'Failed to notify user', 'waitlist-for-tutor-lms' ) ); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js( __( 'An error occurred', 'waitlist-for-tutor-lms' ) ); ?>');
                    }
                });
            });

            // Delete Entry
            $('.wftl-delete-entry').click(function(e) {
                e.preventDefault();
                if (confirm('<?php echo esc_js( __( 'Are you sure you want to delete this entry?', 'waitlist-for-tutor-lms' ) ); ?>')) {
                    var $button = $(this);
                    var entryId = $button.data('entry-id');
                    
                    $.ajax({
                        url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
                        method: 'POST',
                        data: {
                            action: 'wftl_delete_entry',
                            entry_id: entryId,
                            nonce: '<?php echo esc_js( wp_create_nonce( 'wftl_admin_nonce' ) ); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $button.closest('tr').remove();
                                alert('<?php echo esc_js( __( 'Entry deleted successfully', 'waitlist-for-tutor-lms' ) ); ?>');
                            } else {
                                alert('<?php echo esc_js( __( 'Failed to delete entry', 'waitlist-for-tutor-lms' ) ); ?>');
                            }
                        },
                        error: function() {
                            alert('<?php echo esc_js( __( 'An error occurred', 'waitlist-for-tutor-lms' ) ); ?>');
                        }
                    });
                }
            });

            // Export Waitlist
            $('.wftl-export-waitlist').click(function(e) {
                e.preventDefault();
                var courseId = $(this).data('course-id');
                window.location = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>?action=wftl_export_waitlist&course_id=' + courseId + '&nonce=<?php echo esc_js( wp_create_nonce( 'wftl_admin_nonce' ) ); ?>';
            });

            // Notify All
            $('.wftl-notify-all').click(function(e) {
                e.preventDefault();
                if (confirm('<?php echo esc_js( __( 'Are you sure you want to notify all users for this course?', 'waitlist-for-tutor-lms' ) ); ?>')) {
                    var $button = $(this);
                    var courseId = $button.data('course-id');
                    
                    $.ajax({
                        url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
                        method: 'POST',
                        data: {
                            action: 'wftl_notify_all',
                            course_id: courseId,
                            nonce: '<?php echo esc_js( wp_create_nonce( 'wftl_admin_nonce' ) ); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $button.closest('.wftl-course-waitlist').find('tbody tr').each(function() {
                                    $(this).find('td:eq(2)').html('<span style="display: inline-block; padding: 5px 10px; border-radius: 3px; color: #fff; background: #27ae60;">Notified</span>');
                                });
                                alert('<?php echo esc_js( __( 'All users notified successfully', 'waitlist-for-tutor-lms' ) ); ?>');
                            } else {
                                alert('<?php echo esc_js( __( 'Failed to notify all users', 'waitlist-for-tutor-lms' ) ); ?>');
                            }
                        },
                        error: function() {
                            alert('<?php echo esc_js( __( 'An error occurred', 'waitlist-for-tutor-lms' ) ); ?>');
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }

    public function handle_notify_user() {
        if ( ! check_ajax_referer( 'wftl_admin_nonce', 'nonce', false ) ) {
            wp_send_json_error();
        }
        
        $entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
        $course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
        
        if ( ! $entry_id || ! $course_id ) {
            wp_send_json_error();
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'tutor_waitlist';
        
        $entry = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $entry_id )
        );
        
        if ( $entry ) {
            $sent = WFTL_Waitlist_Handler::send_notification_email( $entry->user_email, $course_id );
            
            if ( $sent ) {
                $wpdb->update(
                    $table_name,
                    array( 'status' => 'notified' ),
                    array( 'id' => $entry_id ),
                    array( '%s' ),
                    array( '%d' )
                );
                wp_send_json_success();
            }
        }
        
        wp_send_json_error();
    }

    public function handle_delete_entry() {
        if ( ! check_ajax_referer( 'wftl_admin_nonce', 'nonce', false ) ) {
            wp_send_json_error();
        }
        
        $entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
        
        if ( ! $entry_id ) {
            wp_send_json_error();
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'tutor_waitlist';
        
        $result = $wpdb->delete(
            $table_name,
            array( 'id' => $entry_id ),
            array( '%d' )
        );
        
        if ( $result !== false ) {
            wp_send_json_success();
        }
        wp_send_json_error();
    }

    public function handle_export_waitlist() {
        if ( ! check_ajax_referer( 'wftl_admin_nonce', 'nonce', false ) ) {
            wp_die( esc_html__( 'Security check failed', 'waitlist-for-tutor-lms' ) );
        }
        
        $course_id = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;
        
        if ( ! $course_id ) {
            wp_die( esc_html__( 'Invalid course ID', 'waitlist-for-tutor-lms' ) );
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'tutor_waitlist';
        
        $waitlist = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT user_email, date_added, status FROM {$table_name} WHERE course_id = %d AND status IN ('waiting', 'notified') ORDER BY date_added ASC",
                $course_id
            ),
            ARRAY_A
        );
        
        if ( ! $waitlist ) {
            wp_die( esc_html__( 'No waitlist entries found', 'waitlist-for-tutor-lms' ) );
        }
        
        header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment; filename="waitlist-course-' . $course_id . '-' . date( 'Y-m-d' ) . '.csv"' );
        header( 'Cache-Control: no-cache, no-store, must-revalidate' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );
        
        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array( 'Email', 'Date Added', 'Status' ) );
        
        foreach ( $waitlist as $entry ) {
            fputcsv( $output, array(
                $entry['user_email'],
                $entry['date_added'],
                $entry['status']
            ) );
        }
        
        fclose( $output );
        exit;
    }

    public function handle_notify_all() {
        if ( ! check_ajax_referer( 'wftl_admin_nonce', 'nonce', false ) ) {
            wp_send_json_error();
        }
        
        $course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
        
        if ( ! $course_id ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Invalid course ID', 'waitlist-for-tutor-lms' ) ) );
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'tutor_waitlist';
        
        $waitlist = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE course_id = %d AND status IN ('waiting', 'notified')",
                $course_id
            )
        );
        
        if ( ! $waitlist ) {
            wp_send_json_error( array( 'message' => esc_html__( 'No waitlist entries found', 'waitlist-for-tutor-lms' ) ) );
        }
        
        $success = true;
        foreach ( $waitlist as $entry ) {
            $sent = WFTL_Waitlist_Handler::send_notification_email( $entry->user_email, $course_id );
            if ( $sent ) {
                $wpdb->update(
                    $table_name,
                    array( 'status' => 'notified' ),
                    array( 'id' => $entry->id ),
                    array( '%s' ),
                    array( '%d' )
                );
            } else {
                $success = false;
            }
        }
        
        if ( $success ) {
            wp_send_json_success( array( 'message' => esc_html__( 'All users notified successfully', 'waitlist-for-tutor-lms' ) ) );
        } else {
            wp_send_json_error( array( 'message' => esc_html__( 'Some notifications failed to send', 'waitlist-for-tutor-lms' ) ) );
        }
    }
}