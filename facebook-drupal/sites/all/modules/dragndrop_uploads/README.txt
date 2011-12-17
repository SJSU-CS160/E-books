
The Drag'n'Drop Uploads module adds the ability to drag an image from your local
filesystem, drop it onto a node body textarea and have the file automatically
uploaded and referenced in your node.

Drag'n'Drop Uploads was written and is maintained by Stuart Clark (deciphered).
- http://stuar.tc/lark


Features
-------------------

* Upload widgets support:
  * Drupal core File module.
  * Drupal core Image module.
* Support for Field Formatters.
* Support for the WYSIWYG module.
* Multiple dropzones:
  * Customizable textarea(s)/WYSIWYG(s) dropzone.
  * Upload widget(s) dropzone.
* Ability to hide textarea/WYSIWYG dropzone upload widget.
* Upload progress bar.
* Native Web Browser support:
  * Apple Safari 4+.
  * Google Chrome 2+.
  * Mozilla Firefox 3.6+.
* Support for Google Gears:
  * Microsoft Internet Explorer 6.0+.
  * Mozilla Firefox 1.5+.


Todo
-------------------

* Add configurable multi-dropzone widget.
* Add support for multiple uploads.


Usage/Configuration
-------------------

Once installed, Drag'n'Drop Uploads needs to be configured for each Content Type
you wish to use, this can be done on the Content Type configuration page under
"Drag'n'Drop Uploads settings":
http://[www.yoursite.com/path/to/drupal]/admin/structure/types/manage/[node-type]

Note: An Upload widget must be enabled on the Content Type before the
configuration can be done.


Google Gears
-------------------

To enable Google Gears support, you need to download and save the following file
to the modules directory (/sites/all/modules/dragndrop_uploads/gears_init.js)

http://code.google.com/apis/gears/gears_init.js

