
(function($) {
  $(document).ready(function() {
    // Hide upload Widget.
    if (Drupal.settings.dragNDropUploads.hide == true) {
      $(Drupal.settings.dragNDropUploads.dropzones['default'].wrapper).hide();
    }
    // Attach standard dropzones.
    $.each(Drupal.settings.dragNDropUploads.dropzones, function() { Drupal.dragNDropUploads.attachDropzone(this) });
    // Attach WYSIWYG dropzones.
    if (Drupal.wysiwyg !== undefined) {
      $.each(Drupal.wysiwyg.instances, function() {
        Drupal.dragNDropUploads.wysiwygAttachDropzones(this.field);
        // Send null data for TinyMCE & Safari/Chrome issue.
        if (Drupal.wysiwyg.instances[this.field].editor == 'tinymce') {
          Drupal.wysiwyg.instances[this.field].insert('');
        }
      });
    }
  });

  Drupal.dragNDropUploads = {
    // Standard dropzone attachment function.
    attachDropzone: function(dropzone) {
      $(dropzone.selector || dropzone.wrapper).each(function() {
        $(this).addClass('dropzone dropzone-' + dropzone.id);
        // Safari/Chrome support.
        if ($.browser.safari) {
          $(this).bind("dragover", Drupal.dragNDropUploads.safariUpload);
        }
        // Firefox 3.6 support.
        else if ($.browser.mozilla && $.browser.version >= '1.9.2') {
          $(this).bind("dragover drop", function(e) { e.stopPropagation(); e.preventDefault(); });
          this.addEventListener("drop", Drupal.dragNDropUploads.firefoxUpload, false);
        }
        // Google Gears support.
        else if (window.google && google.gears) {
          $(this).bind("dragover drop", function(e) { e.stopPropagation(); e.preventDefault(); });
          // Firefox support.
          if ($.browser.mozilla || $.browser.safari) {
            this.addEventListener("drop", Drupal.dragNDropUploads.gearsUpload, false);
          }
          // Internet Explorer support.
          else if ($.browser.msie) {
            this.attachEvent("ondrop", Drupal.dragNDropUploads.gearsUpload, false);
          }
        }
      });
    },

    // WYSIWYG dropzone attachment function.
    wysiwygAttachDropzones: function(field) {
      dropzone = Drupal.settings.dragNDropUploads.dropzones['default'];
      switch (Drupal.wysiwyg.instances[field].editor) {
        // CKeditor support.
        case 'ckeditor':
          dropzone.iframe = (CKEDITOR.instances[field].container !== undefined) ? $(CKEDITOR.instances[field].container.$).find('iframe') : null;
          dropzone.selector = (CKEDITOR.instances[field].document !== undefined) ? CKEDITOR.instances[field].document.$ : null;
          break;
        // FCKeditor support.
        case 'fckeditor':
          dropzone.iframe = (typeof(FCKeditorAPI) !== 'undefined') ? $('#' + field + '___Frame') : null;
          dropzone.selector = (typeof(FCKeditorAPI) !== 'undefined') ? FCKeditorAPI.Instances[field].EditingArea.Document : null;
          dropzone.toolbar = (typeof(FCKeditorAPI) !== 'undefined') ? $(FCKeditorAPI.Instances[field].ToolbarSet._TargetElement) : null;
          break;
        // jWYSIWYG support.
        // No insert method.
        case 'jwysiwyg':
          dropzone.iframe = $("#" + field + "IFrame");
          dropzone.selector = dropzone.iframe.get(0).contentDocument;
          break;
        // NicEdit support.
        // No insert method.
        case 'nicedit':
          dropzone.selector = nicEditors.findEditor(field).elm;
          break;
        // TinyMCE support.
        case 'tinymce':
          dropzone.iframe = (tinyMCE.editors[field] !== undefined) ? $('#' + field + '_ifr') : null;
          dropzone.selector = (tinyMCE.editors[field] !== undefined) ? tinyMCE.editors[field].contentDocument : null;
          break;
        // Whizzywig support.
        //case 'whizzywig':
        //  dropzone.selector = $('#whizzy' + field).get(0).contentDocument;
        //  break;
        // WYMEditor support.
        case 'wymeditor':
          dropzone.iframe = $('#' + field + '-wrapper .wym_iframe IFRAME');
          dropzone.selector = dropzone.iframe.get(0).contentDocument;
          break;
        // YUI editor support.
        case 'yui':
          dropzone.iframe = (YAHOO.widget.EditorInfo._instances[field]._getDoc() !== false) ? $('#edit-body_editor') : null;
          dropzone.selector = (YAHOO.widget.EditorInfo._instances[field]._getDoc() !== false) ? YAHOO.widget.EditorInfo._instances[field]._getDoc() : null;
          break;
        case 'none':
          dropzone.selector = null;
          break;
      }
      // WYSIWYG selector unavailable, loop.
      if (dropzone.selector == null && Drupal.wysiwyg.instances[field].editor !== 'none') {
        setTimeout("Drupal.dragNDropUploads.wysiwygAttachDropzones('" + field + "')", 1000);
      }
      // Attach WYSIWYG dropzone.
      else if (Drupal.wysiwyg.instances[field].editor !== 'none') {
        Drupal.dragNDropUploads.attachDropzone(dropzone);
      }
    },

    // Safari/Chrome Upload function.
    safariUpload: function(e) {
      e.stopPropagation();
      e.preventDefault();
      id = ($(this).attr('class') !== undefined) ? $(this).attr('class').match(/dropzone-([^\s]*)/) : new Array('default', 'default');
      Drupal.settings.dragNDropUploads.dropzone = Drupal.settings.dragNDropUploads.dropzones[id[1]];
      Drupal.settings.dragNDropUploads.target = (Drupal.settings.dragNDropUploads.dropzone.target == true) ? $(this) : null;
      if ($('#dragndrop-uploads').find('input').length < 1) {
        var origFile = $(Drupal.settings.dragNDropUploads.dropzone.wrapper + ' .form-file:first');
        var dropFile = $(origFile).clone().prependTo('#dragndrop-uploads');
        // Upload and cleanup.
        $(dropFile).change(function() {
          $('#dragndrop-uploads').hide();
          $(origFile).replaceWith(dropFile);
          Drupal.settings.dragNDropUploads.trigger = true;
          $(Drupal.settings.dragNDropUploads.dropzone.wrapper + ' .form-submit[value="' + Drupal.settings.dragNDropUploads.dropzone.submit + '"]:first').trigger('mousedown');
          Drupal.dragNDropUploads.uploadProgress(null);
        });
        // Cleanup on no upload.
        $(dropFile).mousemove(function() {
          setTimeout(function() {
            $('#dragndrop-uploads').hide();
            $(dropFile).remove();
          }, '100');
        });
      }
      // Move dropzone underneath cursor.
      if (Drupal.settings.dragNDropUploads.offset == null) {
        $('#dragndrop-uploads').show();
        Drupal.settings.dragNDropUploads.offset = $('#dragndrop-uploads').offset();
      }
      $('#dragndrop-uploads').show().css({
        top: (
          e.pageY - Drupal.settings.dragNDropUploads.offset.top - 50 + (Drupal.settings.dragNDropUploads.dropzone.iframe !== undefined
            ? Drupal.settings.dragNDropUploads.dropzone.iframe.offset().top + (Drupal.settings.dragNDropUploads.dropzone.toolbar !== undefined
              ? Drupal.settings.dragNDropUploads.dropzone.toolbar.height() : 0
            ) : 0
          )) + "px",
        left: (
          e.pageX - Drupal.settings.dragNDropUploads.offset.left - 50 + (Drupal.settings.dragNDropUploads.dropzone.iframe !== undefined
            ? Drupal.settings.dragNDropUploads.dropzone.iframe.offset().left
            : 0
          )) + "px"
      });
    },

    // Firefox 3.6 Upload function.
    firefoxUpload: function(e) {
      id = ($(this).attr('class') !== undefined) ? $(this).attr('class').match(/dropzone-([^\s]*)/) : new Array('default', 'default');
      Drupal.settings.dragNDropUploads.dropzone = Drupal.settings.dragNDropUploads.dropzones[id[1]];
      if ($(Drupal.settings.dragNDropUploads.dropzone.wrapper + ' .form-file').length > 0) {
        Drupal.settings.dragNDropUploads.target = (Drupal.settings.dragNDropUploads.dropzone.target == true) ? $(this) : null;
        Drupal.settings.dragNDropUploads.trigger = true;
        ajaxField = Drupal.ajax[$(Drupal.settings.dragNDropUploads.dropzone.wrapper + ' .form-submit[value="' + Drupal.settings.dragNDropUploads.dropzone.submit + '"]:first').attr('id')];
        if (e.dataTransfer.files != null) {
          var file = e.dataTransfer.files[0];
          // Build RFC2388 string.
          var boundary = '------multipartformboundary' + (new Date).getTime();
          var data = 'Content-Type: multipart/form-data; boundary=' + boundary + '\r\n\r\n';
          data += '--' + boundary;
          $(':input:not(:submit), ' + ajaxField.selector).each(function() {
            data += '\r\nContent-Disposition: form-data; name="' + $(this).attr('name') + '"';
            if ($(this).attr('name') == $(Drupal.settings.dragNDropUploads.dropzone.wrapper + ' .form-file:first').attr('name')) {
              data += '; filename="' + file.fileName + '"\r\n';
              data += 'Content-Type: ' + file.mediaType + '\r\n\r\n';
              data += file.getAsBinary() + '\r\n'; // Append binary data.
            }
            else {
              data += '\r\n\r\n' + ($(this).attr('type') == 'checkbox' ? ($(this).attr('checked') == true ? 1 : 0) : $(this).val()) + '\r\n';
            }
            data += '--' + boundary; // Write boundary.
          });
          data += '--'; // Mark end of the request.
          // Send XMLHttpRequest.
          var xhr = new XMLHttpRequest();
          xhr.upload.onprogress = function(e) { Drupal.dragNDropUploads.uploadProgress(e) }
          xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
              response = Drupal.parseJson(xhr.responseText);
              Drupal.ajax[ajaxField.selector.substr(1)].success(response, 'success');
            }
          }
          xhr.open('POST', ajaxField.url, true);
          xhr.setRequestHeader('content-type', 'multipart/form-data; boundary=' + boundary);
          xhr.sendAsBinary(data);
        }
      }
    },

    // Google Gears Upload function.
    gearsUpload: function(e) {
      target = $(e.srcElement).parents().find('.dropzone').get(0) || this;
      id = ($(this).attr('class') !== undefined) ? $(this).attr('class').match(/dropzone-([^\s]*)/) : new Array('default', 'default');
      Drupal.settings.dragNDropUploads.dropzone = Drupal.settings.dragNDropUploads.dropzones[id[1]];
      if ($(Drupal.settings.dragNDropUploads.dropzone.wrapper + ' .form-file').length > 0) {
        Drupal.settings.dragNDropUploads.target = (Drupal.settings.dragNDropUploads.dropzone.target == true) ? $(target) : null;
        Drupal.settings.dragNDropUploads.trigger = true;
        ajaxField = Drupal.ajax[$(Drupal.settings.dragNDropUploads.dropzone.wrapper + ' .form-submit[value="' + Drupal.settings.dragNDropUploads.dropzone.submit + '"]:first').attr('id')];
        var desktop = google.gears.factory.create('beta.desktop');
        data = desktop.getDragData(e, 'application/x-gears-files');
        if (data.files != null) {
          var file = data.files[0];
          file.meta = desktop.extractMetaData(file.blob);
          // Build RFC2388 string.
          var boundary = '------multipartformboundary' + (new Date).getTime();
          var data = google.gears.factory.create('beta.blobbuilder');
          data.append('Content-Type: multipart/form-data; boundary=' + boundary + '\r\n\r\n');
          data.append('--' + boundary);
          $(':input:not(:submit), ' + ajaxField.selector).each(function() {
            data.append('\r\nContent-Disposition: form-data; name="' + $(this).attr('name') + '"');
            if ($(this).attr('name') == $(Drupal.settings.dragNDropUploads.dropzone.wrapper + ' .form-file:first').attr('name')) {
              data.append('; filename="' + file.name + '"\r\n');
              data.append('Content-Type: ' + file.meta.mimeType + '\r\n\r\n');
              data.append(file.blob); // Append binary data.
              data.append('\r\n');
            }
            else {
              data.append('\r\n\r\n' + ($(this).attr('type') == 'checkbox' ? ($(this).attr('checked') == true ? 1 : 0) : $(this).val()) + '\r\n');
            }
            data.append('--' + boundary); // Write boundary.
          });
          data.append('--'); // Mark end of the request.
          // Send XMLHttpRequest.
          var xhr = google.gears.factory.create('beta.httprequest');
          xhr.upload.onprogress = function(e) { Drupal.dragNDropUploads.uploadProgress(e) }
          xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
              response = Drupal.parseJson(xhr.responseText);
              Drupal.ajax[ajaxField.selector.substr(1)].success(response, 'success');
            }
          }
          xhr.open('POST', ajaxField.url);
          xhr.setRequestHeader('content-type', 'multipart/form-data; boundary=' + boundary);
          xhr.send(data.getAsBlob());
        }
      }
    },

    // Upload progress.
    uploadProgress: function(e) {
      if (Drupal.settings.dragNDropUploads.target !== null) {
        if (!Drupal.settings.dragNDropUploads.progressBar) {
          // Initialize/Reset progress bar.
          Drupal.settings.dragNDropUploads.progressBar = $('#progress').clone().insertAfter('#progress').show();
          $(Drupal.settings.dragNDropUploads.progressBar).find('.percentage').empty();
          // Position progress bar in the center of dropzone target.
          target = (Drupal.settings.dragNDropUploads.target.get(0).body == undefined) ? $(Drupal.settings.dragNDropUploads.target) : Drupal.settings.dragNDropUploads.dropzone.iframe;
          $(Drupal.settings.dragNDropUploads.progressBar)
            .css('width', target.width() / 2 + 'px')
            .css({
              'top': target.offset().top + ((Drupal.settings.dragNDropUploads.target.get(0).body !== undefined && Drupal.settings.dragNDropUploads.dropzone.toolbar !== undefined)
                ? Drupal.settings.dragNDropUploads.dropzone.toolbar.height() : 0) - $(Drupal.settings.dragNDropUploads.progressBar).offset().top + (target.height() / 2) - ($(Drupal.settings.dragNDropUploads.progressBar).height() / 2) + 'px',
              'left': target.offset().left - $(Drupal.settings.dragNDropUploads.progressBar).offset().left + (target.width() / 2) - ($(Drupal.settings.dragNDropUploads.progressBar).width() / 2) + 'px'
            });
        }
        // Update progress bar.
        if (e !== null) {
          percentage = Math.round((e.loaded / e.total) * 100);
          $(Drupal.settings.dragNDropUploads.progressBar)
            .find('.filled').css('width', percentage + '%').end()
            .find('.percentage').html(percentage + '%');
        }
      }
    }
  }

  // Post upload behaviour.
  Drupal.behaviors.dragNDropUploads = {
    attach: function(context) {
      if (Drupal.settings.dragNDropUploads.trigger) {
        // Remove progress bar.
        if (Drupal.settings.dragNDropUploads.progressBar) {
          $(Drupal.settings.dragNDropUploads.progressBar).remove();
          Drupal.settings.dragNDropUploads.progressBar = null;
        }
        // Return HTML reference to new upload.
        var output = $(context).find(Drupal.settings.dragNDropUploads.dropzone.result).val() || $(context).find(Drupal.settings.dragNDropUploads.dropzone.result).html();
        if (output !== '' && output !== null && Drupal.settings.dragNDropUploads.target !== null) {
          if ($(Drupal.settings.dragNDropUploads.target).get(0).tagName == 'TEXTAREA') {
            $(Drupal.settings.dragNDropUploads.target).val($(Drupal.settings.dragNDropUploads.target).val() + output);
          }
          // WYSIWYG API support.
          else if ($.isFunction(Drupal.wysiwyg.instances[Drupal.wysiwyg.activeId].insert)) {
            // Send null data for FCKeditor & Safari/Chrome issue.
            if ($.browser.safari && Drupal.wysiwyg.instances[Drupal.wysiwyg.activeId].editor == 'fckeditor') {
              Drupal.wysiwyg.instances[Drupal.wysiwyg.activeId].insert('');
            }
            Drupal.wysiwyg.instances[Drupal.wysiwyg.activeId].insert(output);
            // Cleanup references to local file.
            if ($.browser.mozilla) {
              $(Drupal.settings.dragNDropUploads.dropzone.selector.body).html(
                $(Drupal.settings.dragNDropUploads.dropzone.selector.body).html()
                  .replace(/<img[^>]+file:\/\/\/.*?>/g, '')
                  .replace(/<a[^>]+file:\/\/\/.*?<\/a>/g, '')
              );
            }
          }
        }
        // Add another item.
        if ($(Drupal.settings.dragNDropUploads.dropzone.wrapper + ' .form-file:first').length == 0) {
          if ($(Drupal.settings.dragNDropUploads.dropzone.wrapper + ' .form-submit:last').val() == 'Add another item') {
            $(Drupal.settings.dragNDropUploads.dropzone.wrapper + ' .form-submit:last').trigger('mousedown');
          }
        }
        // Reset variables.
        Drupal.settings.dragNDropUploads.target = null;
        Drupal.settings.dragNDropUploads.trigger = false;
      }
    }
  };
})(jQuery);
