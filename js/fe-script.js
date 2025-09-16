// Import the module version of PDF.js and assign to global scope.
import * as pdfjsLib from "./pdf.mjs";
globalThis.pdfjsLib = pdfjsLib;

jQuery(document).ready(function ($) {
  // Ensure pdfjsLib is available
  if (typeof pdfjsLib === "undefined") {
    console.error("PDF.js library is not loaded.");
    return;
  }

  // Use the localized pdfUrl from PHP.
  var pdfUrl = drossmedia_pdf_upload_url.pdfUrl;
  if (!pdfUrl) {
    console.log("No PDF URL provided via localization.");
    return;
  }

  // Use the CDN-hosted worker; update the version if needed.
  pdfjsLib.GlobalWorkerOptions.workerSrc =
    "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.7.107/pdf.worker.min.js";

  // Load the PDF document.
  pdfjsLib
    .getDocument({ url: pdfUrl })
    .promise.then(function (pdfDocument) {
      // Fetch PDF metadata.
      pdfDocument
        .getMetadata()
        .then(function (data) {
          var info = data.info || {};
          console.log("::info", data);

          // Extract metadata properties.
          const creationDate = parseDate(info.CreationDate) || "";
          const modificationDate = parseDate(info.ModDate) || "";
          var title = info.Title || "";
          var description = info.Subject || "";
          const author = info.Author;
          // Prepare the data for AJAX submission.
          var metadata = {
            action: "drossmedia_save_pdf_file",
            drossmedia_pdf_file_nonce: drossmedia_pdf_upload_url.nonce,
            pdf_url: pdfUrl,
            drossmedia_pdf_title: title,
            creation_date: creationDate,
            modification_date: modificationDate,
            description: description,
            author: author,
            post_id: drossmedia_pdf_upload_url.post_id,
          };

          // Save the extracted metadata via AJAX.
          $.ajax({
            url: drossmedia_pdf_upload_url.ajax_url,
            type: "POST",
            dataType: "json",
            data: metadata,
            success: function (response) {
              console.log("Metadata saved successfully:", response);
            },
            error: function (jqXHR, textStatus, errorThrown) {
              console.error("Error saving metadata:", textStatus, errorThrown);
            },
          });
        })
        .catch(function (error) {
          console.error("Error fetching PDF metadata:", error);
        });
    })
    .catch(function (error) {
      console.error("Error loading PDF:", error);
    });

  function parseDate(inputDate) {
    const dateObj = pdfjsLib.PDFDateString.toDateObject(inputDate);
    if (!dateObj) {
      return "-";
    }
    // Format the date as "Jun 13, 2004"
    const options = { year: "numeric", month: "short", day: "numeric" };
    return dateObj.toLocaleDateString(undefined, options);
  }
});
