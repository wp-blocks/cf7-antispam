<?php

use PHPUnit\Framework\TestCase;

/**
 * Given a string with the html code of a form count the number of inputs and return the number
 *
 * @param $form_html
 *
 * @return int
 */
function cf7a_count_input( $form_html = '' ) {
	preg_match_all('/<input[^>]+>/', $form_html, $matches);
	return intval(count($matches[0]));
}

class CF7_AntiSpam_FrontendTest extends TestCase {

	/**
	 * @var CF7_AntiSpam_Frontend
	 */
	private $frontend;

	public function __construct( $name = null, $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );
		$this->frontend = new CF7_AntiSpam_Frontend(CF7ANTISPAM_NAME, CF7ANTISPAM_VERSION);
	}

	public function testCf7a_honeypot_count_and_validate() {

		$tests = array(
			0 => '<form><p><label> Your name<br />
<span class="wpcf7-form-control-wrap" data-name="your-name"><input size="40" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" autocomplete="name" aria-required="true" aria-invalid="false" value="" type="text" name="your-name" /></span> </label>
</p>
<p><label> Your email<br />
<span class="wpcf7-form-control-wrap" data-name="your-email"><input size="40" class="wpcf7-form-control wpcf7-text wpcf7-email wpcf7-validates-as-required wpcf7-validates-as-email" autocomplete="email" aria-required="true" aria-invalid="false" value="" type="email" name="your-email" /></span> </label>
</p>
<p><label> Subject<br />
<span class="wpcf7-form-control-wrap" data-name="your-subject"><input size="40" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" value="" type="text" name="your-subject" /></span> </label>
</p>
<p><label> Your message (optional)<br />
<span class="wpcf7-form-control-wrap" data-name="your-message"><textarea cols="40" rows="10" class="wpcf7-form-control wpcf7-textarea" aria-invalid="false" name="your-message"></textarea></span> </label>
</p>
<p><input class="wpcf7-form-control has-spinner wpcf7-submit" type="submit" value="Submit" />
</p></form>',
			1 => '<form><div class="row">
	<div class="column">
		<p><span class="wpcf7-form-control-wrap" data-name="your-name-1"><input size="40" class="wpcf7-form-control wpcf7-text" aria-invalid="false" placeholder="Your Name" value="" type="text" name="your-name-1" /></span>
		</p>
	</div>
	<div class="column">
		<p><span class="wpcf7-form-control-wrap" data-name="your-email-1"><input size="40" class="wpcf7-form-control wpcf7-text wpcf7-email wpcf7-validates-as-required wpcf7-validates-as-email" aria-required="true" aria-invalid="false" placeholder="Your Email" value="" type="email" name="your-email-1" /></span>
		</p>
	</div>
	<div class="column">
		<p><span class="wpcf7-form-control-wrap" data-name="your-email-1"><input size="40" class="wpcf7-form-control wpcf7-text wpcf7-email wpcf7-validates-as-required wpcf7-validates-as-email" aria-required="true" aria-invalid="false" placeholder="Your Email" value="" type="email" name="your-email-1" /></span>
		</p>
	</div>
</div>
<p><span class="wpcf7-form-control-wrap" data-name="your-message-1"><textarea cols="40" rows="6" class="wpcf7-form-control wpcf7-textarea" aria-invalid="false" placeholder="Message" name="your-message-1"></textarea></span><br />
<input class="wpcf7-form-control has-spinner wpcf7-submit" type="submit" value="Send" />
</p></form>',
			2 => '<form><div class="row">
	<div class="column">
		<p><span class="wpcf7-form-control-wrap" data-name="your-name-1"><input size="40" class="wpcf7-form-control wpcf7-text" aria-invalid="false" placeholder="Your Name" value="" type="text" name="your-name-1" /></span>
		</p>
	</div>
	<div class="column">
		<p><span class="wpcf7-form-control-wrap" data-name="your-email-1"><input size="40" class="wpcf7-form-control wpcf7-text wpcf7-email wpcf7-validates-as-required wpcf7-validates-as-email" aria-required="true" aria-invalid="false" placeholder="Your Email" value="" type="email" name="your-email-1" /></span>
		</p>
	</div>
</div>
<p><span class="wpcf7-form-control-wrap" data-name="your-message-1"><textarea cols="40" rows="6" class="wpcf7-form-control wpcf7-textarea" aria-invalid="false" placeholder="Message" name="your-message-1"></textarea></span><br />
<input class="wpcf7-form-control has-spinner wpcf7-submit" type="submit" value="Send" />
</p></form>',
			3 => '<form><div class="row">
	<div class="column">
		<p><span class="wpcf7-form-control-wrap" data-name="your-name-1"><input size="40" class="wpcf7-form-control wpcf7-text" aria-invalid="false" placeholder="Your Name" value="" type="text" name="your-name-1" /></span>
		</p>
	</div>
	<div class="column">
		<p><span class="wpcf7-form-control-wrap" data-name="your-email-1"><input size="40" class="wpcf7-form-control wpcf7-text wpcf7-email wpcf7-validates-as-required wpcf7-validates-as-email" aria-required="true" aria-invalid="false" placeholder="Your Email" value="" type="email" name="your-email-1" /></span>
		</p>
	</div>
	<div class="column">
		<p><span class="wpcf7-form-control-wrap" data-name="your-email-1"><input size="40" class="wpcf7-form-control wpcf7-text wpcf7-email wpcf7-validates-as-required wpcf7-validates-as-email" aria-required="true" aria-invalid="false" placeholder="Your Email" value="" type="email" name="your-email-1" /></span>
		</p>
	</div>
	<div class="column">
		<p><span class="wpcf7-form-control-wrap" data-name="your-email-1"><input size="40" class="wpcf7-form-control wpcf7-text wpcf7-email wpcf7-validates-as-required wpcf7-validates-as-email" aria-required="true" aria-invalid="false" placeholder="Your Email" value="" type="email" name="your-email-1" /></span>
		</p>
	</div>
	<div class="column">
		<p><span class="wpcf7-form-control-wrap" data-name="your-email-1"><input size="40" class="wpcf7-form-control wpcf7-text wpcf7-email wpcf7-validates-as-required wpcf7-validates-as-email" aria-required="true" aria-invalid="false" placeholder="Your Email" value="" type="email" name="your-email-1" /></span>
		</p>
	</div>
	<div class="column">
		<p><span class="wpcf7-form-control-wrap" data-name="your-email-1"><input size="40" class="wpcf7-form-control wpcf7-text wpcf7-email wpcf7-validates-as-required wpcf7-validates-as-email" aria-required="true" aria-invalid="false" placeholder="Your Email" value="" type="email" name="your-email-1" /></span>
		</p>
	</div>
</div>
<p><span class="wpcf7-form-control-wrap" data-name="your-message-1"><textarea cols="40" rows="6" class="wpcf7-form-control wpcf7-textarea" aria-invalid="false" placeholder="Message" name="your-message-1"></textarea></span><br />
<input class="wpcf7-form-control has-spinner wpcf7-submit" type="submit" value="Send" />
</p></form>'
		);

		foreach ($tests as $k => $test) {
			// count the number of inputs
			$input_count = cf7a_count_input($test);
			// add the honeypots
			$result = $this->frontend->cf7a_honeypot_add($test);

			// count the resulting input count
			$input_count_2 = cf7a_count_input($result);

			// print_r("\nCOUNT " .  $input_count . " - " . $input_count_2);
			$this->assertTrue( $input_count <= $input_count_2 && $input_count + 6 > $input_count_2 , "the number of honeypot added is correct" );

			// add again the honeypots
			$result2 = $this->frontend->cf7a_honeypot_add($result);

			// count the resulting input count
			$input_count_3 = cf7a_count_input($result2);
			$this->assertTrue( $input_count_2 <= $input_count_3 && $input_count_2 + 6 > $input_count_3 , "the number of honeypot added the second time is correct" );

			// print_r("\nCOUNT2 " . $input_count_2 . " - " . $input_count_3);

			// check for the validity of the html contained in the string $result
			$dom = new DOMDocument();
			$dom->loadHTML($result);


			// Check if all tags are correctly opened and closed
			$errors = libxml_get_errors();
			if (empty($errors)) {
				// Get all the HTML tags in the form
				$tags = $dom->getElementsByTagName('*');

				foreach ($tags as $tag) {
					$open_tag = $dom->saveHTML($tag);
					$closed_tag = str_replace('<', '</', $open_tag);
					$this->assertTrue( substr($open_tag, -2) !== '/>' && $dom->getElementsByTagName($closed_tag)->length === 0, "The form string is not valid");
				}
			} else {
				$this->assertNotEmpty($errors, "The form string is not valid");
			}

			// Check if the HTML string contains a form tag
			$form_tags = $dom->getElementsByTagName('form');
			$this->assertTrue( $form_tags->length === 1, "Error. Form $k returned invalid html after adding honeypots" );
		}
	}

}
