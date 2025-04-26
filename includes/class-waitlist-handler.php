<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WFTL_Waitlist_Handler {
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'tutor_course/single/entry/after', array( $this, 'add_waitlist_button' ), 20 );
        add_action( 'wp_ajax_wftl_join_waitlist', array( $this, 'handle_waitlist_submission' ) );
        add_action( 'wp_ajax_nopriv_wftl_join_waitlist', array( $this, 'handle_waitlist_submission' ) );
    }

    public function enqueue_scripts() {
        wp_enqueue_style( 'wftl-style', WFTL_URL . 'assets/css/waitlist.css', array(), WFTL_VERSION );
        wp_enqueue_script( 'wftl-script', WFTL_URL . 'assets/js/waitlist.js', array( 'jquery' ), WFTL_VERSION, true );
        wp_localize_script( 'wftl-script', 'wftl_ajax', array(
            'ajax_url' => esc_url( admin_url( 'admin-ajax.php' ) ),
            'nonce'    => wp_create_nonce( 'wftl_nonce' ),
        ) );
    }

    public function add_waitlist_button() {
        if ( tutor_utils()->is_course_fully_booked( null ) ) {
            $course_id = get_the_ID();
            ?>
            <div class="wftl-waitlist-container tutor-mt-20">
                <button class="tutor-btn tutor-btn-outline-primary tutor-btn-block wftl-join-waitlist" 
                        data-course-id="<?php echo esc_attr( $course_id ); ?>">
                    <?php esc_html_e( 'Join Waitlist', 'waitlist-for-tutor-lms' ); ?>
                </button>
                
                <div class="wftl-waitlist-form" style="display: none;">
                    <form class="wftl-waitlist-form-inner tutor-mt-16">
                        <input type="email" name="waitlist_email" class="tutor-form-control" 
                               placeholder="<?php echo esc_attr__( 'Enter your email', 'waitlist-for-tutor-lms' ); ?>" required>
                        <button type="submit" class="tutor-btn tutor-btn-primary tutor-mt-12">
                            <?php esc_html_e( 'Submit', 'waitlist-for-tutor-lms' ); ?>
                        </button>
                    </form>
                </div>
            </div>
            <?php
        }
    }

    public function handle_waitlist_submission() {
        if ( ! check_ajax_referer( 'wftl_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Nonce verification failed', 'waitlist-for-tutor-lms' ) ) );
        }
        
        $course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
        $email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        
        if ( ! $course_id || ! $email ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Missing required fields', 'waitlist-for-tutor-lms' ) ) );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'tutor_waitlist';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'course_id'  => $course_id,
                'user_email' => $email,
                'date_added' => current_time( 'mysql' ),
                'status'     => 'waiting',
            ),
            array( '%d', '%s', '%s', '%s' )
        );

        if ( false !== $result ) {
            wp_send_json_success( array( 'message' => esc_html__( 'Successfully added to waitlist', 'waitlist-for-tutor-lms' ) ) );
        } else {
            wp_send_json_error( array( 'message' => esc_html__( 'Failed to join waitlist: ', 'waitlist-for-tutor-lms' ) . esc_html( $wpdb->last_error ) ) );
        }
    }

    public static function send_notification_email( $email, $course_id ) {
        $course_title = html_entity_decode( get_the_title( $course_id ), ENT_QUOTES, 'UTF-8' );
        $subject = sprintf( esc_html__( 'A spot is available in %s', 'waitlist-for-tutor-lms' ), esc_html( $course_title ) );
        $message = "Hello,\n\n" .
                   "A spot has become available in the course: " . $course_title . ". " .
                   "Please enroll soon to secure your place!\n\n" .
                   "Enroll Now: " . get_permalink( $course_id );
        
        $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
        return wp_mail( sanitize_email( $email ), $subject, $message, $headers );
    }
}