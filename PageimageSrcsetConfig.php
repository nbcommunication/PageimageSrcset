<?php namespace ProcessWire;

/**
 * Pageimage Srcset Configuration
 *
 */

class PageimageSrcsetConfig extends ModuleConfig {

	/**
	 * Returns default values for module variables
	 *
	 * @return array
	 *
	 */
	public function getDefaults() {
		return [
			"portraitSets" => "320, 640, 768",
			"portraitRatio" => "9:16",
			"ukWidth_s" => 640,
			"ukWidth_m" => 960,
			"ukWidth_l" => 1200,
			"ukWidth_xl" => 1600,
			"suffix" => "srcset",
		];
	}

	/**
	 * Returns inputs for module configuration
	 *
	 * @return InputfieldWrapper
	 *
	 */
	public function getInputfields() {

		$config = $this->wire("config");
		$modules = $this->wire("modules");

		$inputfields = parent::getInputfields();

		if($modules->isInstalled("ProcessWireAPI")) {
			// Add prism for code colouring
			$urlProcessWireAPI = $config->urls("ProcessWireAPI");
			$config->styles->add("$urlProcessWireAPI/prism.css");
			$config->scripts->add("$urlProcessWireAPI/prism.js");
		}

		// Get the module this configures
		$module = $modules->get(str_replace("Config", "", $this->className));

		$preview = "";
		$setRule = "{width}x{height} {inherentwidth}w|{resolution}x";
		if($module->defaultSets) {

			// Get generated sets
			$attr = [];
			foreach($module->getSets() as $rule => $dimensions) {
				$attr[] = "image.{$dimensions[0]}x{$dimensions[1]}-$module->suffix.jpg $rule";
			}

			// Get defaultSets as a string and order correctly
			$args = explode("\n", $module->defaultSets);
			usort($args, function($a, $b) {
				$s = $this->wire("sanitizer");
				return $s->int(explode(" ", $a)[0]) <=> $s->int(explode(" ", $b)[0]);
			});

			$preview .=  "// " . $this->_("Sets generated") . "\n" . implode("\n", $attr) . "\n\n" .
				"// " . $this->_("Equivalent method call") . "\n" .
				'$srcset = $image->srcset("'. implode(", ", $args) . '");' . "\n";

		} else {

			$preview .= "/* " . $this->_("Please configure the default set rules") . "\n\n" .
				$this->_("Set rules should use the following format") . ":\n" .
				"$setRule\n" .
				$this->_("Only `width` is required.") . "\n\n" .
				$this->_("Example") . ":\n" .
				implode("\n", ["320", "640", "768x480 960w", "1024", "2048 2x"]) . "\n" .
				"*/";
		}
	
		$inputfields->add([
			"type" => "textarea",
			"name" => "defaultSets",
			"label" => $this->_("Set Rules"),
			"placeholder" => $setRule,
			"required" => true,
			"notes" => $this->_("Each set rule should be entered on a new line."),
			"icon" => "arrows-alt",
			"rows" => substr_count($preview, "\n"), // Adjust the textarea based on preview
			"columnWidth" => 50,
		]);

		$inputfields->add([
			"type" => "markup",
			"name" => "previewSets",
			"label" => $this->_("Preview"),
			"value" => "<pre class='language-php'><code class='language-php'>$preview</code></pre>",
			"notes" => $this->_("A set will only be generated if the original image is wider or higher than the set dimensions."),
			"icon" => "eye",
			"columnWidth" => 50,
		]);


		// Portrait Mode
		$fieldset = $modules->get("InputfieldFieldset");
		$fieldset->label = $this->_("Portrait Mode");
		$fieldset->icon = "mobile";
		$fieldset->description = sprintf($this->_("If portrait mode is called on %s, the following settings will be used:"), "`Pageimage::srcset()`");
		$fieldset->notes = $this->_("Portrait mode is to be used for viewport covering images that display as *landscape* on desktop devices, but *portrait* on mobile and tablet devices in this orientation.");
		$fieldset->collapsed = 1;

		$fieldset->add([
			"type" => "text",
			"name" => "portraitSets",
			"label" => $this->_("Set Widths"),
			"description" => $this->_("The widths that should used to generate portrait variations."),
			"notes" => $this->_("Please enter a comma separated list."),
			"columnWidth" => 50,
		]);

		$fieldset->add([
			"type" => "text",
			"name" => "portraitRatio",
			"label" => $this->_("Crop Ratio"),
			"description" => $this->_("The portrait ratio that should be used to crop the image."),
			"notes" => $this->_("If a landscape ratio is entered it will be switched to portrait when used."),
			"columnWidth" => 50,
		]);

		$inputfields->add($fieldset);


		// UIkit Widths
		$uk = "UIkit";
		$fieldset = $modules->get("InputfieldFieldset");
		$fieldset->label = sprintf($this->_("%s Widths"), $uk);
		$fieldset->description = sprintf(
			$this->_('These widths are used to determine sizes if %1$s width classes (e.g. %2$s) are passed to %3$s.'),
			$uk,
			"uk-width-1-2@s",
			"`Pageimage::sizes()`"
		);
		$fieldset->notes = $this->_("These values should only be altered if they are customized in your website theme.");
		$fieldset->icon = "cube";
		$fieldset->collapsed = 1;

		foreach([
			"s" => $this->_("Small"),
			"m" => $this->_("Medium"),
			"l" => $this->_("Large"),
			"xl" => $this->_("Extra Large"),
		] as $size => $title) {
			$fieldset->add([
				"type" => "integer",
				"name" => "ukWidth_$size",
				"label" => "$title (@$size)",
				"columnWidth" => 25,
				"size" => 0,
			]);
		}

		$inputfields->add($fieldset);


		// Image Suffix
		$inputfields->add([
			"type" => "text",
			"name" => "suffix",
			"label" => $this->_("Image Suffix"),
			"description" => $this->_("This is appended to the name of the images generated by this module and is used to remove old variations if the set rules change."),
			"notes" => $this->_("Please enter another suffix if the default conflicts in any way."),
			"icon" => "terminal",
			"collapsed" => 1,
		]);


		// Debug Mode
		$inputfields->add([
			"type" => "checkbox",
			"name" => "debug",
			"label" => $this->_("Debug Mode"),
			"notes" => $this->_("When enabled, error messages and other useful information will be logged.") .
				"\n" . sprintf(
					$this->_('%1$s will also be inserted inside the %2$s. This will %3$s %4$s data when the %5$s event is fired.'),
					"`PageimageSrcsetDebug.js`",
					"`<head>`",
					"`console.log`",
					"srcset",
					"`window.onresize`"
				),
			"icon" => "search-plus",
			"collapsed" => 2,
		]);

		return $inputfields;
	}
}
