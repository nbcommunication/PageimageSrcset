# PageimageSrcset
Provides configurable `srcset` and `sizes` properties/methods for `Pageimage`.

## Overview

The main purpose of this module is to make srcset implementation as simple as possible in your template code. It does not handle images rendered in CKEditor or similar fields.

For an introduction to srcset and sizes, please read this [Mozilla article about responsive images](https://developer.mozilla.org/en-US/docs/Learn/HTML/Multimedia_and_embedding/Responsive_images).

### Pageimage::srcset()
```php
// The property, which uses the set rules in the module configuration
$srcset = $image->srcset;

// A method call, using a set rules string
// Delimiting with a newline (\n) would also work, but not as readable
$srcset = $image->srcset('320, 480, 640x480 768w, 1240, 2048 2x');

// The same as above but using an indexed/sequential array
$srcset = $image->srcset([
	'320',
	'480',
	'640x480 768w',
	'1240',
	'2048 2x',
]);

// The same as above but using an associative array
// No rule checking is performed
$srcset = $image->srcset([
	'320w' => [320],
	'480w' => [480],
	'768w' => [640, 480],
	'1240w' => [1240],
	'2x' => [2048],
]);

// Use the default set rules with portrait images generated for mobile/tablet devices
$srcset = $image->srcset(true);

// Return the srcset using all arguments
$srcset = $image->srcset('320, 480, 640x480 768w, 1240, 2048 2x', [
	'portrait' => '320, 640',
]);

// The set rules above are a demonstration, not a recommendation!
```
**Image variations are only created for set rules which require a smaller image than the `Pageimage` itself. On large sites this may still result in a _lot_ of images being generated. If you have limited storage, please use this module wisely.**

#### Portrait Mode
In many situations, the ratio of the image does not need to change at different screen sizes. However, images that cover the entire viewport are an exception to this and are often the ones that benefit most from srcset implementation.

The main problem with cover images is that they need to display *landscape* on desktop devices and *portrait* when this orientation is used on mobile and tablet devices.

You can automatically generate portrait images by enabling portrait mode. It is recommended that you use this in combination with [`Pageimage::focus()`](https://processwire.com/api/ref/pageimage/focus/) so that the portrait variations retain the correct subject.

The generated variations are HiDPI/Retina versions. Their height is determined by the portrait ratio (e.g. 9:16). Variations are always generated, regardless of whether the original image is smaller. Upscaling is disabled though, so you may find that some variations are actually smaller than they say they are in their filename.

The `sizes` attribute should be used when portrait mode is enabled. `Pageimage::sizes` will return `(orientation: portrait) and (max-width: {maxWidth}px) 50vw` by default, which handles the use of these images for retina devices. The maximum width used in this rule is the largest set width.

### Pageimage::sizes()
There is no option to configure default sizes because in most cases `100vw` is all you need, and you do not need to output this anyway as it is inferred when using the `srcset` attribute. You can use the method for custom sizes though:

```php
// The property
$sizes = $image->sizes;
// Returns 100vw in most cases
// Returns '(orientation: portrait) and (max-width: {maxWidth}px)50vw' if portrait mode enabled

// A method call, using a mixture of integer widths and media query rules
// Integer widths are treated as a min-width media query rule
$sizes = $image->sizes([
	480 => 50,
	'(orientation: portrait) and (max-width: 640px)' => 100,
	960 => 25,
]);
// (min-width: 480px) 50vw, (orientation: portrait) and (max-width: 640px) 100vw, (min-width: 960px) 25vw

// Determine widths by UIkit 'child-width' classes
$sizes = $image->sizes([
	'uk-child-width-1-2@s',
	'uk-child-width-1-3@l',
]);
// (min-width: 640px) 50vw, (min-width: 1200px) 33.33vw

// Determine widths by UIkit 'width' classes
$sizes = $image->sizes([
	'uk-width-1-2@m',
	'uk-width-1-3@xl',
]);
// (min-width: 960px) 50vw, (min-width: 1600px) 33.33vw

// Return the portrait size rule
$sizes = $image->sizes(true);
// (orientation: portrait) and (max-width: {maxWidth}px) 50vw

// The arguments above are a demonstration, not a recommendation!
```

### Pageimage::render()
This module extends the options available to this method with:
- `srcset`: When the module is installed, this will always be added, unless set to `false`. Any values in the formats described above can be passed.
- `sizes`: Only used if specified. Any values in the formats described above can be passed.
- `uk-img`: If passed, as either true or as a [valid uk-img value](https://getuikit.com/docs/image#component-options), then this attribute will be added. The following also happens:
	- The `src` attribute becomes `data-src` to enable lazy loading.
	- The `src` attribute is re-added with a blank placeholder `data:image/gif`.
	- The `srcset` attribute becomes `data-srcset`.
	- The `sizes` attribute becomes `data-sizes`.

Please refer to the [API Reference](https://processwire.com/api/ref/pageimage/render/) for more information about this method.

```php
// Render an image using the default set rules
echo $image->render();
// <img src='image.jpg' alt='' srcset='{default set rules}'>

// Render an image using custom set rules
echo $image->render(['srcset' => '480, 1240x640']);
// <img src='image.jpg' alt='' srcset='image.480x0-srcset.jpg 480w, image.1240x640-srcset.jpg 1240w'>

// Render an image using custom set rules and sizes
// Also use the `markup` argument
echo $image->render('<img class="image" src="{url}" alt="Image">', [
	'srcset' => '480, 1240',
	'sizes' => [1240 => 50],
]);
// <img class='image' src='image.jpg' alt='Image' srcset='image.480x0-srcset.jpg 480w, image.1240x640-srcset.jpg 1240w' sizes='(min-width: 1240px) 50vw'>

// Render an image using custom set rules and sizes
// Enable uk-img
echo $image->render([
	'srcset' => '480, 1240',
	'sizes' => ['uk-child-width-1-2@m'],
	'uk-img' => true,
]);
// <img data-src='image.jpg' alt='' src='data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==' data-uk-img data-srcset='image.480x0-srcset.jpg 480w, image.1240x640-srcset.jpg 1240w' data-sizes='(min-width: 960px) 50vw'>

// Render an image using portrait mode
// Default rule sets used: 320, 640, 768, 1024, 1366, 1600
// Portrait widths used: 320, 640, 768
// Original image is 1000px wide
// Not possible to use portrait mode and custom sets or portrait widths in render()
// Sizes attribute automatically added
echo $image->render(['srcset' => true]);
// <img src='image.jpg' alt='' srcset='image.320x569-srcset-hidpi.jpg 320w, image.640x1138-srcset-hidpi.jpg 640w, image.768x1365-srcset-hidpi.jpg 768w, image.jpg 1024w' sizes='(orientation: portrait) and (max-width: 768px) 50vw'>
```

## Configuration
To configure this module, go to Modules > Configure > PageimageSrcset.

### Set Rules
These are the default set rules that will be used when none are specified, e.g. when calling the property: `$image->srcset`.

Each set rule should be entered on a new line, in the format `{width}x{height} {inherentwidth}w|{resolution}x`.

Not all arguments are required - you will probably find that specifying the width is sufficient for most cases. Here's a few examples of valid set rules and the sets they generate:

Set Rule | Set Generated | Arguments Used
--- | --- | ---
320 | image.320x0-srcset.jpg 320w | `{width}`
480x540 | image.480x540-srcset.jpg 480w | `{width}x{height}`
640x480 768w | image.640x480-srcset.jpg 768w | `{width}x{height} {inherentwidth}w`
2048 2x | image.2048x0-srcset.jpg 2x | `{width} {resolution}x`

How you configure your rules is dependent on the needs of the site you are developing; there are no prescriptive rules that will meet the needs of most situations. [This article](https://medium.com/hceverything/applying-srcset-choosing-the-right-sizes-for-responsive-images-at-different-breakpoints-a0433450a4a3) gives a good overview of some of the things to consider.

When you save your rules, a preview of the sets generated and an equivalent method call will be displayed to the right. Invalid rules will not be used, and you will be notified of this.

### Portrait Mode

#### Set Widths
A comma limited list of widths to create HiDPI/Retina portrait variations for.

#### Crop Ratio
The portrait ratio that should be used to crop the image. The default of 9:16 should be fine for most circumstances as this is the standard portrait ratio of most devices. However, you can specify something different if you want. If you add a landscape ratio, it will be switched to portrait when used.

Any crops in the set rules (`{width}x{height}`) are ignored for portrait mode variations as this ratio is used instead.

### UIkit Widths
If your website theme uses UIkit, you can pass an array of UIkit width classes to `Pageimage::sizes` to be converted to sizes. The values stored here are used to do this. If you have customised the breakpoints on your theme, you should also customise them here.

Please note that only `1-` widths are evaluated by `Pageimage::sizes`, e.g. `uk-width-2-3` will not work.

### Remove Variations
If checked, the image variations generated by this module are cleared on Submit. On large sites, this may take a while. It makes sense to run this after you have made changes to the set rules.

### Image Suffix
You will see this field when **Remove Variations** is checked. The value is appended to the name of the images generated by this module and is used to identify variations. You should not encounter any issues with the default suffix, but if you find that it conflicts with any other functionality on your site, you can set a custom suffix instead.

### Debug Mode
When this is enabled, a range of information is logged to **pageimage-srcset**.

`PageimageSrcsetDebug.js` is also added to the `<head>` of your HTML pages. This will `console.log` a range of information about the images and nodes  using srcset on your page after a `window.onresize` event is triggered. This can assist you in debugging your implementation.

The browser will always use the highest resolution image it has loaded or has cached. You may need to disable browser caching to determine whether your set rules are working, and it makes sense to work from a small screen size and up. If you do it the other way, the browser is going to continue to use the higher resolution image it loaded first.

Debug mode will also limit the features provided by this module to the superuser account. Please remember to switch it off in production!

## UIkit Features
This module implements some additional features that are tailored towards UIkit being used as the front-end theme framework, but this is not required to use the module.

## Installation
1. Download the [zip file](https://github.com/nbcommunication/PageimageSrcset/archive/master.zip) at Github or clone the repo into your `site/modules` directory.
2. If you downloaded the zip file, extract it in your `sites/modules` directory.
3. In your admin, go to Modules > Refresh, then Modules > New, then click on the Install button for this module.

**ProcessWire >= 3.0.123 is required to use this module.**

## License
This project is licensed under the Mozilla Public License Version 2.0.
