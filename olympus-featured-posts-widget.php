<?php
/**
 * Plugin Name: Featured Posts Widget
 * Plugin URI: https://wordpress.org/plugins/olympus-featured-posts-widget
 * Description: Add a selection of posts to your sidebar or another widget location.
 * Author: DannyCooper
 * Author URI: http://olympusthemes.com
 * Version: 1.0.1
 * Text Domain: olympus-featured-posts-widget
 * License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @package olympus-featured-posts-widget
 */

/**
 * Enqueue the plugin stylesheet
 */
function olympus_featured_posts_widget_styles() {
	if ( ! is_admin() ) {
		wp_enqueue_style( 'olympus-featured-posts-widget', plugin_dir_url( __FILE__ ) . 'css/style.css' );
	}
}
add_action( 'wp_enqueue_scripts', 'olympus_featured_posts_widget_styles', 11 );

/**
 * Load plugin textdomain.
 */
function olympus_featured_posts_widget_load_textdomain() {
	load_plugin_textdomain( 'olympus-featured-posts-widget', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'olympus_featured_posts_widget_load_textdomain' );

/**
 * Registers the Featured Posts Widget.
 */
class Olympus_Featured_Posts_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {

		$id_base = 'olympus_featured_posts_widget';
		$name = esc_html__( 'Featured Posts Widget', 'cashier' );
		$widget_options = array(
			'classname' => 'olympus-featured-posts-widget',
			'description' => esc_html__( 'Display your most important posts.', 'olympus-featured-posts-widget' ),
			'customize_selective_refresh' => true,
		);

		parent::__construct( $id_base, $name, $widget_options );

	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {

		$widget_title = isset( $instance['widget_title'] ) ? apply_filters( 'widget_title', $instance['widget_title'] ) : '';
		$widget_desc = isset( $instance['widget_desc'] ) ? $instance['widget_desc'] : '';
		$post_count = $instance['post_count'] ? $instance['post_count'] : 5 ;

		echo $args['before_widget']; // WPCS: XSS ok.

		if ( ! empty( $widget_title ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $widget_title ) . $args['after_title']; // WPCS: XSS ok.
		}

		if ( ! empty( $widget_desc ) ) {
			echo '<div class="widget-description">' . wp_kses_post( wpautop( $widget_desc ) ) . '</div>';
		}

		$this->featured_post_loop( $post_count );

		echo $args['after_widget']; // WPCS: XSS ok.
	}

	/**
	 * Generates the administration form for the widget.
	 *
	 * @param array $instance The array of keys and values for the widget.
	 */
	public function form( $instance ) {

		// Get the options into variables, escaping html characters on the way.
		$widget_title = isset( $instance['widget_title'] ) ? esc_attr( $instance['widget_title'] ) : 'Featured Posts';
		$widget_desc = isset( $instance['widget_desc'] ) ? esc_attr( $instance['widget_desc'] ) : '';
		$post_count = isset( $instance['post_count'] ) ? absint( $instance['post_count'] ) : '5';
		?>

		<p>
			<?php esc_html_e( 'Tag your posts as \'featured\' to make them display in this widget.', 'olympus-featured-posts-widget' ); ?>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'widget_title' ) ); ?>">
				<?php esc_attr_e( 'Title:', 'olympus-featured-posts-widget' ); ?>
			</label>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'widget_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'widget_title' ) ); ?>" value="<?php echo esc_attr( $widget_title ); ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'widget_desc' ) ); ?>">
				<?php esc_attr_e( 'Widget Text:', 'olympus-featured-posts-widget' ); ?>
			</label>
			<textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'widget_desc' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'widget_desc' ) ); ?>" rows="5" cols="20" ><?php echo wp_kses_post( $widget_desc ); ?></textarea>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'post_count' ) ); ?>">
				<?php esc_attr_e( 'Show how many posts?:', 'olympus-featured-posts-widget' ); ?>
			</label>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'post_count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'post_count' ) ); ?>" value="<?php echo absint( $post_count ); ?>" />
		</p>

		<?php
	}

	/**
	 * Processes the widget's options to be saved.
	 *
	 * @param array $new_instance The new instance of values to be generated via the update.
	 * @param array $old_instance The previous instance of values before the update.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		$instance['widget_title'] = strip_tags( $new_instance['widget_title'] );
		$instance['post_count'] = absint( $new_instance['post_count'] );
		$instance['widget_desc'] = $new_instance['widget_desc'];

		return $instance;
	}

	/**
	 * Loop through and display the featured posts
	 *
	 * @param int $post_count number of posts to display.
	 */
	public function featured_post_loop( $post_count = 5 ) {

		$query = new WP_Query( 'tag=featured&posts_per_page=' . $post_count );

		echo '<ul>';

		if ( $query->have_posts() ) :
			while ( $query->have_posts() ) :
				$query->the_post();
				?>

		<li class="olympus-featured-post">

			<span class="olympus-post-title">
				<a href="<?php the_permalink(); ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>">
					<?php the_title(); ?>
				</a>
			</span>

			<small class="olympus-post-date">
				<?php the_time( 'F jS, Y' ); ?>
			</small>

		</li>

			<?php
			endwhile;
		else :
		?>

			<p>
				<?php esc_html_e( 'Tag your posts as \'featured\' to make them display in this widget.', 'olympus-featured-posts-widget' ); ?>
			</p>

		<?php
		endif;

		wp_reset_postdata();

		echo '</ul>';

	}

} // end class.

/**
 * Register articles widget on widgets_init.
 */
function register_olympus_featured_posts_widget() {
	register_widget( 'Olympus_Featured_Posts_Widget' );
}
add_action( 'widgets_init', 'register_olympus_featured_posts_widget' );
