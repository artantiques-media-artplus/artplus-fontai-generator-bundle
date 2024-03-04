Dropzone.autoDiscover = false;

$(function()
{
  var previewTemplate = '<div class="dz-preview dz-file-preview">';
  previewTemplate += '<div class="dz-image"><img data-dz-thumbnail /></div>';
  previewTemplate += '<div class="dz-file-icon" data-dz-icon></div>';
  previewTemplate += '<div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>';
  previewTemplate += '<div class="dz-filename" data-dz-name></div>';
  previewTemplate += '<a class="dz-error-remove" href="#" data-dz-remove><svg class="icon icon-delete-circle"><use xlink:href="#icon-delete-circle"></use></svg></a>';
  previewTemplate += '</div>';

  $('#fcc-upload')
      .each(function()
      {
        var translations = {
          dictFallbackMessage: 'Váš prohlížeš nepodporuje drag\'n\'drop uploady.',
          dictFallbackText: 'Prosím použíjte klasický formulář.',
          dictFileTooBig: 'Soubor je moc veliký ({{filesize}}MiB). Maximální velikost je: {{maxFilesize}}MiB.',
          dictInvalidFileType: 'Tento typ souboru není podporován.',
          dictResponseError: 'Server odpověděl se statusem {{statusCode}}.',
          dictCancelUpload: 'Zrušit upload',
          dictCancelUploadConfirmation: 'Opravdu chcete zrušit nahrávání?',
          dictRemoveFile: '',
          dictRemoveFileConfirmation: null,
          dictMaxFilesExceeded: 'Nemůžete přidat další soubory.',
          dictDefaultMessage: '<svg class="icon icon-upload"><use xlink:href="#icon-upload"></use></svg><br /><br />Klikněte a vyberte soubory k nahrání,\n<br />nebo je sem přetáhněte z vašeho počítače.',
        };
        if (locale === 'en') {
          translations = {
            dictFallbackMessage: 'Your browser does not support drag\'n\'drop uploads.',
            dictFallbackText: 'Please use classic form.',
            dictFileTooBig: 'File is too large ({{filesize}}MiB). Maximum file size is: {{maxFilesize}}MiB.',
            dictInvalidFileType: 'This type of file is not supported.',
            dictResponseError: 'Server response with status code {{statusCode}}.',
            dictCancelUpload: 'Cancel upload',
            dictCancelUploadConfirmation: 'Do you really want to cancel file upload?',
            dictMaxFilesExceeded: 'You can\'t add more files.',
            dictDefaultMessage: '<svg class="icon icon-upload"><use xlink:href="#icon-upload"></use></svg><br /><br />Click and choose files to upload\n<br />or drag here from your computer.',
          };
        }
        var dropzone = new Dropzone(this, { ...translations,
          paramName: 'fcc_upload[file]',
          addRemoveLinks: false,
          previewTemplate: previewTemplate,
          success: function(file, response)
          {
            $('<input>')
                .attr('type', 'hidden')
                .attr('name', 'fcc_task[attachment][]')
                .val(response.filename)
                .appendTo($('form[name="fcc_task"]'));
          }
        });

        dropzone
            .on('addedfile', function(file)
            {
              var fileTypeIcon = 'file';
              switch (file.type)
              {
                case 'application/zip':
                  fileTypeIcon = 'zip';
                  break;
                case 'application/pdf':
                  fileTypeIcon = 'pdf';
                  break;
                case 'application/vnd.ms-excel':
                  fileTypeIcon = 'xls';
                  break;
                case 'application/vnd.ms-word':
                  fileTypeIcon = 'doc';
                  break;
              }

              $(file.previewElement)
                  .find('[data-dz-icon]')
                  .html('<svg class="icon icon-file-' + fileTypeIcon + '"><use xlink:href="#icon-file-' + fileTypeIcon + '"></use></svg>');
            });
      });

  $('.fontaiControlWidget button')
      .on('click', function()
      {
        var form = $('.fontaiControlWidget form:not(.dropzone)');

        if (form)
        {
          form.submit();
        }
      });
});