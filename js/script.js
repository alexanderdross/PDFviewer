jQuery(document).ready(function ($) {
  var mediaUploader;
  $("#drossmedia_meta_tags").select2({
    tags: true, // Allow users to create new tags
    tokenSeparators: [","], // Use comma as separator for new tags
    placeholder: "Enter meta tags", // Placeholder text for the field
    width: "resolve", // Ensure proper width handling
  });
  // Open media uploader when clicking the "Upload PDF" button.
  $("#drossmedia_pdf_upload_container").on(
    "click",
    "#drossmedia_upload_pdf_button",
    function (e) {
      e.preventDefault();
      // If an instance already exists, open it.
      if (mediaUploader) {
        mediaUploader.open();
        return;
      }

      // Create a new media uploader instance.
      mediaUploader = wp.media({
        title: drossmedia_pdf_upload_data.title,
        button: {
          text: drossmedia_pdf_upload_data.uploadedText,
        },
        multiple: false,
      });
      let pdfFile = {};
      // When a file is selected, update the preview and hidden input.
      mediaUploader.on("select", function () {
        var attachment = mediaUploader
          .state()
          .get("selection")
          .first()
          .toJSON();
        if (attachment.mime === "application/pdf") {
          // Update the hidden input with the new PDF URL.
          var pdfDocument = {
            url: attachment.url,
            info: {
              title: attachment.title || "Untitled PDF",
              // You can add additional metadata here if needed.
            },
            getDownloadInfo: function () {
              return Promise.reject(
                new Error("getDownloadInfo not available from attachment")
              );
            },
            getPageLayout: function () {
              return Promise.reject(
                new Error("getPageLayout not available from attachment")
              );
            },
            getOpenAction: function () {
              return Promise.reject(
                new Error("getOpenAction not available from attachment")
              );
            },
          };
          console.log();
          // pdfFile = JSON.stringify(pdfDocument);
          $("#drossmedia_pdf_url").val(pdfDocument.url);
          $("#drossmedia_pdf_title").val(pdfDocument.title);

          // Build the new preview HTML that includes the Upload PDF button.
          var previewHtml =
            '<p><button type="button" class="button" id="drossmedia_upload_pdf_button">' +
            drossmedia_pdf_upload_data.uploadedText +
            "</button></p>";

          previewHtml +=
            '<iframe src="' +
            pdfDocument.url +
            '" width="100%" height="500"></iframe>';

          // Changed the id and name from drossmedia_pdf_file to drossmedia_pdf_url and added a second hidden input for the title.
          previewHtml +=
            "<p>" +
            '<input type="hidden" id="drossmedia_pdf_url" name="drossmedia_pdf_url" value="' +
            pdfDocument.url +
            '" />' +
            '<input type="hidden" id="drossmedia_pdf_title" name="drossmedia_pdf_title" value="' +
            pdfDocument.info.title +
            '" />' +
            "</p>";
          // Replace the container's HTML with the updated preview.
          $("#drossmedia_pdf_upload_container").html(previewHtml);

          // Reset mediaUploader so a new instance can be created next time.
          mediaUploader = null;
        } else {
          alert("Please select a valid PDF file.");
        }
      });

      mediaUploader.open();
    }
  );
});
