<?php
/**
 * Guided admin configuration for the Date Difference rule.
 *
 * @package DMMF
 */

namespace DMMF\Admin;

use DMMF\Configuration\RuleRepository;
use DMMF\Domain\DateRule;
use DMMF\Integration\ForminatorFormInspector;

final class RuleEditor {

	/** @var RuleRepository */
	private $rules;

	/** @var ForminatorFormInspector */
	private $inspector;

	/**
	 * @param RuleRepository           $rules Rule storage.
	 * @param ForminatorFormInspector $inspector Read-only Forminator boundary.
	 */
	public function __construct( RuleRepository $rules, ForminatorFormInspector $inspector ) {
		$this->rules     = $rules;
		$this->inspector = $inspector;
	}

	/**
	 * @return void
	 */
	public function register() {
		add_action( 'admin_post_dmmf_save_rule', array( $this, 'save_rule' ) );
		add_action( 'admin_post_dmmf_delete_rule', array( $this, 'delete_rule' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'admin_footer_text', array( $this, 'replace_admin_footer_text' ) );
		add_filter( 'update_footer', array( $this, 'replace_update_footer_text' ), 999 );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
	}

	/**
	 * Hides the generic WordPress thank-you text on this plugin's own screen.
	 *
	 * @param string $text Existing footer text.
	 * @return string
	 */
	public function replace_admin_footer_text( $text ) {
		return $this->is_plugin_screen() ? '' : $text;
	}

	/**
	 * Hides the WordPress version text together with the generic footer text.
	 *
	 * @param string $text Existing version text.
	 * @return string
	 */
	public function replace_update_footer_text( $text ) {
		return $this->is_plugin_screen() ? '' : $text;
	}

	/**
	 * Adds a screen-specific CSS hook without affecting other WordPress pages.
	 *
	 * @param string $classes Existing admin body classes.
	 * @return string
	 */
	public function admin_body_class( $classes ) {
		return $this->is_plugin_screen() ? $classes . ' dmmf-admin-screen' : $classes;
	}

	/**
	 * @return void
	 */
	public function enqueue_assets() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the page slug only controls a local admin asset.
		if ( 'dayquanta-date-difference-for-forminator' !== $page ) {
			return;
		}

		$asset_path = DMMF_PLUGIN_DIR . 'assets/js/admin-rule-editor.js';
		wp_enqueue_script(
			'dmmf-admin-rule-editor',
			plugins_url( 'assets/js/admin-rule-editor.js', DMMF_PLUGIN_FILE ),
			array(),
			is_readable( $asset_path ) ? (string) filemtime( $asset_path ) : DMMF_VERSION,
			true
		);
		wp_enqueue_style(
			'dmmf-admin-rule-editor',
			plugins_url( 'assets/css/admin-rule-editor.css', DMMF_PLUGIN_FILE ),
			array(),
			is_readable( DMMF_PLUGIN_DIR . 'assets/css/admin-rule-editor.css' ) ? (string) filemtime( DMMF_PLUGIN_DIR . 'assets/css/admin-rule-editor.css' ) : DMMF_VERSION
		);
	}

	/**
	 * @return void
	 */
	public function render_page() {
		$this->require_access();
		$forms       = $this->inspector->forms();
		$form_index  = $this->form_index( $forms );
		$stored_rules = $this->rules->all();
		$selected_id  = isset( $_GET['dmmf_form_id'] ) ? absint( wp_unslash( $_GET['dmmf_form_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- selecting a form only changes this admin view.
		$filter_id    = isset( $_GET['dmmf_rule_filter'] ) ? absint( wp_unslash( $_GET['dmmf_rule_filter'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- filtering only changes this admin view.
		$fields       = $selected_id ? $this->inspector->compatible_fields( $selected_id ) : array();
		$current      = $selected_id ? $this->rules->get_for_form( $selected_id ) : null;
		$visible_rules = $filter_id && isset( $stored_rules[ $filter_id ] ) ? array( $filter_id => $stored_rules[ $filter_id ] ) : ( $filter_id ? array() : $stored_rules );
		$visible_rule_count = count( $visible_rules );

		/* translators: %d: number of configured Date Difference rules. */
		$rule_count_text = sprintf( _n( '%d rule', '%d rules', $visible_rule_count, 'dayquanta-date-difference-for-forminator' ), $visible_rule_count );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'DayQuanta Date Difference for Forminator', 'dayquanta-date-difference-for-forminator' ); ?></h1>
			<p class="dmmf-intro"><?php esc_html_e( 'Create a reliable date difference. DayQuanta updates the selected output field live and recalculates it securely when the form is submitted.', 'dayquanta-date-difference-for-forminator' ); ?></p>
			<?php $this->render_notice(); ?>

			<section class="dmmf-panel dmmf-rule-builder">
				<div class="dmmf-panel-heading">
					<div>
						<p class="dmmf-eyebrow"><?php esc_html_e( 'Guided setup', 'dayquanta-date-difference-for-forminator' ); ?></p>
						<h2><?php esc_html_e( 'Create or update a Date Difference rule', 'dayquanta-date-difference-for-forminator' ); ?></h2>
						<p class="dmmf-panel-description"><?php esc_html_e( 'Connect two dates to an existing Number or Hidden field in one Forminator form.', 'dayquanta-date-difference-for-forminator' ); ?></p>
					</div>
					<span class="dmmf-step-count"><?php esc_html_e( '6 steps', 'dayquanta-date-difference-for-forminator' ); ?></span>
				</div>
			<ol class="dmmf-steps">
				<li><span class="dmmf-step-number">1</span><span><?php esc_html_e( 'Choose a Forminator form.', 'dayquanta-date-difference-for-forminator' ); ?></span></li>
				<li><span class="dmmf-step-number">2</span><span><?php esc_html_e( 'Choose the start date.', 'dayquanta-date-difference-for-forminator' ); ?></span></li>
				<li><span class="dmmf-step-number">3</span><span><?php esc_html_e( 'Choose the end date.', 'dayquanta-date-difference-for-forminator' ); ?></span></li>
				<li><span class="dmmf-step-number">4</span><span><?php esc_html_e( 'Choose the result field.', 'dayquanta-date-difference-for-forminator' ); ?></span></li>
				<li><span class="dmmf-step-number">5</span><span><?php esc_html_e( 'Choose how the dates are counted.', 'dayquanta-date-difference-for-forminator' ); ?></span></li>
				<li><span class="dmmf-step-number">6</span><span><?php esc_html_e( 'Save the rule.', 'dayquanta-date-difference-for-forminator' ); ?></span></li>
			</ol>

			<form class="dmmf-form-selector" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
				<input type="hidden" name="page" value="dayquanta-date-difference-for-forminator" />
				<?php if ( $filter_id ) : ?><input type="hidden" name="dmmf_rule_filter" value="<?php echo esc_attr( $filter_id ); ?>" /><?php endif; ?>
				<label for="dmmf-form-selector"><strong><?php esc_html_e( '1. Forminator form', 'dayquanta-date-difference-for-forminator' ); ?></strong></label><br />
				<select id="dmmf-form-selector" name="dmmf_form_id" onchange="this.form.submit()">
					<option value="0"><?php esc_html_e( 'Choose a form', 'dayquanta-date-difference-for-forminator' ); ?></option>
					<?php foreach ( $forms as $form ) : ?>
						<option value="<?php echo esc_attr( $form['id'] ); ?>" <?php selected( $selected_id, $form['id'] ); ?>><?php echo esc_html( $form['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<noscript><?php submit_button( __( 'Choose form', 'dayquanta-date-difference-for-forminator' ), 'secondary', 'submit', false ); ?></noscript>
			</form>

			<?php if ( $selected_id && ! isset( $form_index[ $selected_id ] ) ) : ?>
				<div class="notice notice-error inline"><p><?php esc_html_e( 'That Forminator form is not available.', 'dayquanta-date-difference-for-forminator' ); ?></p></div>
			<?php elseif ( $selected_id ) : ?>
				<?php $this->render_rule_form( $selected_id, $fields, $current ); ?>
			<?php else : ?>
				<div class="dmmf-empty-selection">
					<span class="dmmf-empty-selection-number" aria-hidden="true">1</span>
					<p><strong><?php esc_html_e( 'Select a Forminator form to continue.', 'dayquanta-date-difference-for-forminator' ); ?></strong><br /><?php esc_html_e( 'The supported date and output fields will appear here after you choose a form.', 'dayquanta-date-difference-for-forminator' ); ?></p>
				</div>
			<?php endif; ?>
			</section>

			<section class="dmmf-panel dmmf-configured-rules">
				<div class="dmmf-panel-heading dmmf-configured-heading">
					<div>
						<p class="dmmf-eyebrow"><?php esc_html_e( 'Your rules', 'dayquanta-date-difference-for-forminator' ); ?></p>
						<h2><?php esc_html_e( 'Configured rules', 'dayquanta-date-difference-for-forminator' ); ?></h2>
						<p class="dmmf-panel-description"><?php esc_html_e( 'Review, edit, or remove the date calculations already connected to your forms.', 'dayquanta-date-difference-for-forminator' ); ?></p>
					</div>
					<span class="dmmf-rule-count"><?php echo esc_html( $rule_count_text ); ?></span>
				</div>
			<form class="dmmf-rule-filter" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
				<input type="hidden" name="page" value="dayquanta-date-difference-for-forminator" />
				<?php if ( $selected_id ) : ?><input type="hidden" name="dmmf_form_id" value="<?php echo esc_attr( $selected_id ); ?>" /><?php endif; ?>
				<label for="dmmf-rule-filter"><strong><?php esc_html_e( 'Filter by form', 'dayquanta-date-difference-for-forminator' ); ?></strong></label>
				<select id="dmmf-rule-filter" name="dmmf_rule_filter" onchange="this.form.submit()">
					<option value="0"><?php esc_html_e( 'All forms', 'dayquanta-date-difference-for-forminator' ); ?></option>
					<?php foreach ( $forms as $form ) : ?>
						<option value="<?php echo esc_attr( $form['id'] ); ?>" <?php selected( $filter_id, $form['id'] ); ?>><?php echo esc_html( $form['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<noscript><?php submit_button( __( 'Filter', 'dayquanta-date-difference-for-forminator' ), 'secondary', 'submit', false ); ?></noscript>
			</form>

			<?php if ( ! $stored_rules ) : ?>
				<p><?php esc_html_e( 'No date difference rule has been configured yet.', 'dayquanta-date-difference-for-forminator' ); ?></p>
			<?php elseif ( ! $visible_rules ) : ?>
				<p><?php esc_html_e( 'No configured rule exists for the selected form.', 'dayquanta-date-difference-for-forminator' ); ?></p>
			<?php else : ?>
				<table class="widefat striped dmmf-rules-table">
					<thead><tr>
						<th><?php esc_html_e( 'Form', 'dayquanta-date-difference-for-forminator' ); ?></th>
						<th><?php esc_html_e( 'Rule', 'dayquanta-date-difference-for-forminator' ); ?></th>
						<th><?php esc_html_e( 'Output', 'dayquanta-date-difference-for-forminator' ); ?></th>
						<th><?php esc_html_e( 'Status', 'dayquanta-date-difference-for-forminator' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'dayquanta-date-difference-for-forminator' ); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $visible_rules as $rule ) : ?>
						<?php
						$is_compatible = $this->rule_is_compatible( $rule );
						/* translators: %d: Forminator form ID. */
						$missing_form_text = sprintf( __( 'Missing form #%d', 'dayquanta-date-difference-for-forminator' ), $rule['form_id'] );
						?>
						<tr>
							<td><?php echo esc_html( isset( $form_index[ $rule['form_id'] ] ) ? $form_index[ $rule['form_id'] ]['name'] : $missing_form_text ); ?></td>
							<td><?php echo esc_html( $this->rule_summary( $rule ) ); ?></td>
							<td><code><?php echo esc_html( $rule['fields']['output'] ); ?></code></td>
							<td><span class="dmmf-status <?php echo esc_attr( $is_compatible ? 'dmmf-status-active' : 'dmmf-status-review' ); ?>"><?php echo esc_html( $is_compatible ? __( 'Active', 'dayquanta-date-difference-for-forminator' ) : __( 'Needs review', 'dayquanta-date-difference-for-forminator' ) ); ?></span></td>
							<td>
								<a class="button" href="<?php echo esc_url( $this->page_url( $rule['form_id'] ) ); ?>"><?php esc_html_e( 'Edit', 'dayquanta-date-difference-for-forminator' ); ?></a>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline">
									<input type="hidden" name="action" value="dmmf_delete_rule" />
									<input type="hidden" name="form_id" value="<?php echo esc_attr( $rule['form_id'] ); ?>" />
									<?php wp_nonce_field( 'dmmf_delete_rule_' . $rule['form_id'] ); ?>
									<button type="submit" class="button button-link-delete" onclick="return window.confirm('<?php echo esc_js( __( 'Remove this date difference rule? The Forminator form and its submissions will not be deleted.', 'dayquanta-date-difference-for-forminator' ) ); ?>');"><?php esc_html_e( 'Remove', 'dayquanta-date-difference-for-forminator' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			</section>
		</div>
		<?php
	}

	/**
	 * @return void
	 */
	public function save_rule() {
		$this->require_access();
		check_admin_referer( 'dmmf_save_rule' );

		$form_id = isset( $_POST['form_id'] ) ? absint( wp_unslash( $_POST['form_id'] ) ) : 0;
		$start   = isset( $_POST['start_field'] ) ? sanitize_key( wp_unslash( $_POST['start_field'] ) ) : '';
		$end     = isset( $_POST['end_field'] ) ? sanitize_key( wp_unslash( $_POST['end_field'] ) ) : '';
		$output  = isset( $_POST['output_field'] ) ? sanitize_key( wp_unslash( $_POST['output_field'] ) ) : '';
		$details = $this->inspector->validate_rule_fields( $form_id, $start, $end, $output );

		if ( ! $details || $start === $end || $start === $output || $end === $output ) {
			$this->redirect( $form_id, $this->inspector->date_fields_have_different_formats( $form_id, $start, $end ) ? 'different-date-formats' : 'invalid-rule' );
		}

		$operation = isset( $_POST['operation'] ) && 'calendar_days' === sanitize_key( wp_unslash( $_POST['operation'] ) ) ? 'calendar_days' : 'nights';
		$rule      = DateRule::from_array(
			array(
				'form_id'       => $form_id,
				'fields'        => array( 'start' => $start, 'end' => $end, 'output' => $output ),
				'date_formats'  => array( 'start' => $details['start'], 'end' => $details['end'] ),
				'output_type'   => $details['output_type'],
				'operation'     => $operation,
				'inclusive_end' => 'calendar_days' === $operation && ! empty( $_POST['inclusive_end'] ),
				'error_message' => isset( $_POST['error_message'] ) ? sanitize_text_field( wp_unslash( $_POST['error_message'] ) ) : '',
			)
		);

		if ( ! $rule ) {
			$this->redirect( $form_id, 'invalid-rule' );
		}

		$this->rules->save( $rule );
		$this->redirect( $form_id, 'rule-saved' );
	}

	/**
	 * @return void
	 */
	public function delete_rule() {
		$this->require_access();
		$form_id = isset( $_POST['form_id'] ) ? absint( wp_unslash( $_POST['form_id'] ) ) : 0;
		check_admin_referer( 'dmmf_delete_rule_' . $form_id );
		$this->rules->delete_for_form( $form_id );
		$this->redirect( 0, 'rule-deleted' );
	}

	/**
	 * @param int                         $form_id Selected form ID.
	 * @param array<int, array<string,string>> $fields Compatible fields.
	 * @param array<string,mixed>|null    $current Existing rule.
	 * @return void
	 */
	private function render_rule_form( $form_id, array $fields, $current ) {
		$date_fields   = array_values( array_filter( $fields, static function ( $field ) { return 'date' === $field['type']; } ) );
		$output_fields = array_values( array_filter( $fields, static function ( $field ) { return in_array( $field['type'], array( 'number', 'hidden' ), true ); } ) );
		if ( count( $date_fields ) < 2 || ! $output_fields ) {
			?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'This form needs at least two supported Datepicker fields and one Number or Hidden field before it can use DayQuanta Date Difference.', 'dayquanta-date-difference-for-forminator' ); ?></p></div>
			<?php
			return;
		}

		$current = is_array( $current ) ? $current : array();
		?>
		<?php if ( $current && ! $this->rule_is_compatible( $current ) ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'The saved rule refers to a field that no longer exists or is no longer compatible. Choose the fields again before updating it.', 'dayquanta-date-difference-for-forminator' ); ?></p></div>
		<?php endif; ?>
		<form class="dmmf-rule-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="dmmf_save_rule" />
			<input type="hidden" name="form_id" value="<?php echo esc_attr( $form_id ); ?>" />
			<?php wp_nonce_field( 'dmmf_save_rule' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="dmmf-start-field"><?php esc_html_e( '2. Start date', 'dayquanta-date-difference-for-forminator' ); ?></label></th>
					<td>
						<?php $this->field_select( 'dmmf-start-field', 'start_field', $date_fields, $current['fields']['start'] ?? '' ); ?>
						<p class="description"><?php esc_html_e( 'Start and End must use the same Datepicker format. The format is shown beside each field.', 'dayquanta-date-difference-for-forminator' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dmmf-end-field"><?php esc_html_e( '3. End date', 'dayquanta-date-difference-for-forminator' ); ?></label></th>
					<td><?php $this->field_select( 'dmmf-end-field', 'end_field', $date_fields, $current['fields']['end'] ?? '' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="dmmf-output-field"><?php esc_html_e( '4. Result field', 'dayquanta-date-difference-for-forminator' ); ?></label></th>
					<td>
						<?php $this->field_select( 'dmmf-output-field', 'output_field', $output_fields, $current['fields']['output'] ?? '' ); ?>
						<p class="description"><?php esc_html_e( 'DayQuanta writes the calculated number into this existing Number or Hidden field.', 'dayquanta-date-difference-for-forminator' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dmmf-operation"><?php esc_html_e( '5. How to count', 'dayquanta-date-difference-for-forminator' ); ?></label></th>
					<td>
						<select id="dmmf-operation" name="operation">
							<option value="nights" <?php selected( $current['operation'] ?? 'nights', 'nights' ); ?>><?php esc_html_e( 'Nights - the end date is not counted', 'dayquanta-date-difference-for-forminator' ); ?></option>
							<option value="calendar_days" <?php selected( $current['operation'] ?? '', 'calendar_days' ); ?>><?php esc_html_e( 'Calendar days', 'dayquanta-date-difference-for-forminator' ); ?></option>
						</select>
						<span id="dmmf-inclusive-end-row"><br /><label><input type="checkbox" name="inclusive_end" value="1" <?php checked( ! empty( $current['inclusive_end'] ) ); ?> /> <?php esc_html_e( 'For calendar days, count the end date too', 'dayquanta-date-difference-for-forminator' ); ?></label></span>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dmmf-error-message"><?php esc_html_e( '6. Validation message', 'dayquanta-date-difference-for-forminator' ); ?></label></th>
					<td>
						<input id="dmmf-error-message" class="regular-text" type="text" name="error_message" value="<?php echo esc_attr( $current['error_message'] ?? '' ); ?>" placeholder="<?php echo esc_attr__( 'The end date must be the same as or later than the start date.', 'dayquanta-date-difference-for-forminator' ); ?>" />
						<p class="description"><?php esc_html_e( 'Optional. Leave empty to use the standard message.', 'dayquanta-date-difference-for-forminator' ); ?></p>
					</td>
				</tr>
			</table>

			<p id="dmmf-rule-preview" class="description"><strong><?php esc_html_e( 'Preview:', 'dayquanta-date-difference-for-forminator' ); ?></strong> <?php echo esc_html( $this->preview( $current ) ); ?></p>
			<?php submit_button( $current ? __( 'Update rule', 'dayquanta-date-difference-for-forminator' ) : __( 'Save rule', 'dayquanta-date-difference-for-forminator' ), 'primary', 'submit', false ); ?>
		</form>
		<?php
	}

	/**
	 * @param string                             $id Element ID.
	 * @param string                             $name Field name.
	 * @param array<int, array<string,string>> $fields Field choices.
	 * @param string                             $selected Selected field ID.
	 * @return void
	 */
	private function field_select( $id, $name, array $fields, $selected ) {
		?>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>">
			<?php foreach ( $fields as $field ) : ?>
				<?php $label = 'date' === $field['type'] ? sprintf( '%1$s (Datepicker: %2$s)', $field['label'], $field['date_format'] ) : sprintf( '%s (%s)', $field['label'], $field['type'] ); ?>
				<option value="<?php echo esc_attr( $field['id'] ); ?>" <?php selected( $selected, $field['id'] ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * @param array<int, array{id:int,name:string}> $forms Form list.
	 * @return array<int, array{id:int,name:string}>
	 */
	private function form_index( array $forms ) {
		$index = array();
		foreach ( $forms as $form ) {
			$index[ $form['id'] ] = $form;
		}
		return $index;
	}

	/**
	 * @param array<string,mixed> $rule Rule data.
	 * @return string
	 */
	private function rule_summary( array $rule ) {
		$label = 'calendar_days_inclusive' === $rule['mode'] ? __( 'calendar days, including end date', 'dayquanta-date-difference-for-forminator' ) : ( 'calendar_days' === $rule['mode'] ? __( 'calendar days', 'dayquanta-date-difference-for-forminator' ) : __( 'nights', 'dayquanta-date-difference-for-forminator' ) );
		return sprintf( '%1$s - %2$s = %3$s (%4$s)', $rule['fields']['end'], $rule['fields']['start'], $rule['fields']['output'], $label );
	}

	/**
	 * @param array<string,mixed> $rule Rule data.
	 * @return bool
	 */
	private function rule_is_compatible( array $rule ) {
		if ( empty( $rule['form_id'] ) || empty( $rule['fields'] ) || ! is_array( $rule['fields'] ) ) {
			return false;
		}

		return $this->inspector->rule_matches_current_form( $rule );
	}

	/**
	 * @param array<string,mixed> $current Existing rule if editing.
	 * @return string
	 */
	private function preview( array $current ) {
		if ( ! $current ) {
			return __( 'Choose the fields above, then save the rule.', 'dayquanta-date-difference-for-forminator' );
		}
		return $this->rule_summary( $current );
	}

	/**
	 * @return void
	 */
	private function render_notice() {
		$notice = isset( $_GET['dmmf_notice'] ) ? sanitize_key( wp_unslash( $_GET['dmmf_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a redirect status only.
		$messages = array(
			'rule-saved'   => array( 'success', __( 'Date difference rule saved.', 'dayquanta-date-difference-for-forminator' ) ),
			'rule-deleted' => array( 'success', __( 'Date difference rule removed.', 'dayquanta-date-difference-for-forminator' ) ),
			'different-date-formats' => array( 'error', __( 'Start and End must use the same Datepicker format. Choose two fields with matching formats and save again.', 'dayquanta-date-difference-for-forminator' ) ),
			'invalid-rule' => array( 'error', __( 'The selected fields are no longer compatible with this form. Choose the fields again and save.', 'dayquanta-date-difference-for-forminator' ) ),
		);
		if ( ! isset( $messages[ $notice ] ) ) {
			return;
		}
		?>
		<div class="notice notice-<?php echo esc_attr( $messages[ $notice ][0] ); ?> is-dismissible"><p><?php echo esc_html( $messages[ $notice ][1] ); ?></p></div>
		<?php
	}

	/**
	 * @param int    $form_id Form ID to retain in the editor.
	 * @param string $notice Redirect notice key.
	 * @return void
	 */
	private function redirect( $form_id, $notice ) {
		$args = array( 'page' => 'dayquanta-date-difference-for-forminator', 'dmmf_notice' => $notice );
		if ( $form_id ) {
			$args['dmmf_form_id'] = absint( $form_id );
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * @param int $form_id Form ID.
	 * @return string
	 */
	private function page_url( $form_id ) {
		return add_query_arg( array( 'page' => 'dayquanta-date-difference-for-forminator', 'dmmf_form_id' => absint( $form_id ) ), admin_url( 'admin.php' ) );
	}

	/**
	 * Checks whether the current request is the DayQuanta admin screen.
	 *
	 * @return bool
	 */
	private function is_plugin_screen() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- the page slug only selects an admin screen.
		if ( ! is_admin() || ! isset( $_GET['page'] ) || ! is_scalar( $_GET['page'] ) ) {
			return false;
		}

		return 'dayquanta-date-difference-for-forminator' === sanitize_key( wp_unslash( $_GET['page'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * @return void
	 */
	private function require_access() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage DayQuanta Date Difference.', 'dayquanta-date-difference-for-forminator' ) );
		}
	}
}
