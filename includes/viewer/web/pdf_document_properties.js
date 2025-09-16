/* Copyright 2012 Mozilla Foundation
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/** @typedef {import("./event_utils.js").EventBus} EventBus */
/** @typedef {import("./interfaces.js").IL10n} IL10n */
/** @typedef {import("./overlay_manager.js").OverlayManager} OverlayManager */
// eslint-disable-next-line max-len
/** @typedef {import("../src/display/api.js").PDFDocumentProxy} PDFDocumentProxy */

import { getPageSizeInches, isPortraitOrientation } from "./ui_utils.js";
import { PDFDateString } from "./pdfjs.js";

// See https://en.wikibooks.org/wiki/Lentis/Conversion_to_the_Metric_Standard_in_the_United_States
const NON_METRIC_LOCALES = ["en-us", "en-lr", "my"];

// Should use the format: `width x height`, in portrait orientation. The names,
// which are l10n-ids, should be lowercase.
// See https://en.wikipedia.org/wiki/Paper_size
const US_PAGE_NAMES = {
  "8.5x11": "pdfjs-document-properties-page-size-name-letter",
  "8.5x14": "pdfjs-document-properties-page-size-name-legal",
};
const METRIC_PAGE_NAMES = {
  "297x420": "pdfjs-document-properties-page-size-name-a-three",
  "210x297": "pdfjs-document-properties-page-size-name-a-four",
};

function getPageName(size, isPortrait, pageNames) {
  const width = isPortrait ? size.width : size.height;
  const height = isPortrait ? size.height : size.width;

  return pageNames[`${width}x${height}`];
}

/**
 * @typedef {Object} PDFDocumentPropertiesOptions
 * @property {HTMLDialogElement} dialog - The overlay's DOM element.
 * @property {Object} fields - Names and elements of the overlay's fields.
 * @property {HTMLButtonElement} closeButton - Button for closing the overlay.
 */

class PDFDocumentProperties {
  #fieldData = null;

  /**
   * @param {PDFDocumentPropertiesOptions} options
   * @param {OverlayManager} overlayManager - Manager for the viewer overlays.
   * @param {EventBus} eventBus - The application event bus.
   * @param {IL10n} l10n - Localization service.
   * @param {function} fileNameLookup - The function that is used to lookup
   *   the document fileName.
   */
  constructor(
    { dialog, fields, closeButton },
    overlayManager,
    eventBus,
    l10n,
    fileNameLookup
  ) {
    this.dialog = dialog;
    this.fields = fields;
    this.overlayManager = overlayManager;
    this.l10n = l10n;
    this._fileNameLookup = fileNameLookup;

    this.#reset();
    // Bind the event listener for the Close button.
    closeButton.addEventListener("click", this.close.bind(this));

    this.overlayManager.register(this.dialog);

    eventBus._on("pagechanging", (evt) => {
      this._currentPageNumber = evt.pageNumber;
    });
    eventBus._on("rotationchanging", (evt) => {
      this._pagesRotation = evt.pagesRotation;
    });
  }

  /**
   * Open the document properties overlay.
   */
  async open() {
    await Promise.all([
      this.overlayManager.open(this.dialog),
      this._dataAvailableCapability.promise,
    ]);
    const currentPageNumber = this._currentPageNumber;
    const pagesRotation = this._pagesRotation;

    // If the document properties were previously fetched (for this PDF file),
    // just update the dialog immediately to avoid redundant lookups.
    if (
      this.#fieldData &&
      currentPageNumber === this.#fieldData._currentPageNumber &&
      pagesRotation === this.#fieldData._pagesRotation
    ) {
      this.#updateUI();
      return;
    }

    // Get the document properties.
    const [
      { info, /* metadata, contentDispositionFilename, */ contentLength },
      pdfPage,
    ] = await Promise.all([
      this.pdfDocument.getMetadata(),
      this.pdfDocument.getPage(currentPageNumber),
    ]);

    const [
      fileName,
      fileSize,
      creationDate,
      modificationDate,
      pageSize,
      isLinearized,
    ] = await Promise.all([
      this._fileNameLookup(),
      this.#parseFileSize(contentLength),
      this.#parseDate(info.CreationDate),
      this.#parseDate(info.ModDate),
      this.#parsePageSize(getPageSizeInches(pdfPage), pagesRotation),
      this.#parseLinearization(info.IsLinearized),
    ]);

    this.#fieldData = Object.freeze({
      fileName,
      fileSize,
      title: fileName,
      author: info.Author,
      subject: info.Subject,
      keywords: info.Keywords,
      creationDate,
      modificationDate,
      creator: info.Creator,
      producer: info.Producer,
      version: info.PDFFormatVersion,
      pageCount: this.pdfDocument.numPages,
      pageSize,
      linearized: isLinearized,
      _currentPageNumber: currentPageNumber,
      _pagesRotation: pagesRotation,
    });
    this.#updateUI();

    // Get the correct fileSize, since it may not have been available
    // or could potentially be wrong.
    const { length } = await this.pdfDocument.getDownloadInfo();
    if (contentLength === length) {
      return; // The fileSize has already been correctly set.
    }
    const data = Object.assign(Object.create(null), this.#fieldData);
    data.fileSize = await this.#parseFileSize(length);

    this.#fieldData = Object.freeze(data);
    this.#updateUI();
  }

  /**
   * Close the document properties overlay.
   */
  async close() {
    this.overlayManager.close(this.dialog);
  }

  /**
   * Set a reference to the PDF document in order to populate the dialog fields
   * with the document properties. Note that the dialog will contain no
   * information if this method is not called.
   *
   * @param {PDFDocumentProxy} pdfDocument - A reference to the PDF document.
   */
  setDocument(pdfDocument) {
    if (this.pdfDocument) {
      this.#reset();
      this.#updateUI();
    }
    if (!pdfDocument) {
      return;
    }
    this.pdfDocument = pdfDocument;

    this._dataAvailableCapability.resolve();
  }

  #reset() {
    this.pdfDocument = null;
    this.#fieldData = null;
    this._dataAvailableCapability = Promise.withResolvers();
    this._currentPageNumber = 1;
    this._pagesRotation = 0;
  }

  /**
   * Always updates all of the dialog fields, to prevent inconsistent UI state.
   * NOTE: If the contents of a particular field is neither a non-empty string,
   *       nor a number, it will fall back to "-".
   */
  #updateUI() {
    if (this.#fieldData && this.overlayManager.active !== this.dialog) {
      // Don't bother updating the dialog if it's already been closed,
      // unless it's being reset (i.e. `this.#fieldData === null`),
      // since it will be updated the next time `this.open` is called.
      return;
    }
    for (const id in this.fields) {
      const content = this.#fieldData?.[id];
      this.fields[id].textContent = content || content === 0 ? content : "-";
    }
  }

  async #parseFileSize(bytes = 0) {
    if (bytes < 1024) {
      return bytes + " bytes";
    } else if (bytes < 1024 * 1024) {
      return (bytes / 1024).toFixed(2) + " KB";
    } else if (bytes < 1024 * 1024 * 1024) {
      return (bytes / (1024 * 1024)).toFixed(2) + " MB";
    } else {
      return (bytes / (1024 * 1024 * 1024)).toFixed(2) + " GB";
    }
  }

  async #parsePageSize(pageSizeInches, pagesRotation) {
    if (!pageSizeInches) {
      return undefined;
    }
    // Take the viewer rotation into account as well; compare with Adobe Reader.
    if (pagesRotation % 180 !== 0) {
      pageSizeInches = {
        width: pageSizeInches.height,
        height: pageSizeInches.width,
      };
    }
    const isPortrait = isPortraitOrientation(pageSizeInches),
      // For this example, we assume non-metric (inches); adjust as needed.
      nonMetric = true;

    let sizeInches = {
      width: Math.round(pageSizeInches.width * 100) / 100,
      height: Math.round(pageSizeInches.height * 100) / 100,
    };
    // Compute millimeters in case we need them for matching.
    let sizeMillimeters = {
      width: Math.round(pageSizeInches.width * 25.4 * 10) / 10,
      height: Math.round(pageSizeInches.height * 25.4 * 10) / 10,
    };

    // Try to get the standard page name based on inches or millimeters.
    let nameId =
      getPageName(sizeInches, isPortrait, US_PAGE_NAMES) ||
      getPageName(sizeMillimeters, isPortrait, METRIC_PAGE_NAMES);

    if (
      !nameId &&
      !(
        Number.isInteger(sizeMillimeters.width) &&
        Number.isInteger(sizeMillimeters.height)
      )
    ) {
      // Fallback: use fuzzy matching to account for rounding errors.
      const exactMillimeters = {
        width: pageSizeInches.width * 25.4,
        height: pageSizeInches.height * 25.4,
      };
      const intMillimeters = {
        width: Math.round(sizeMillimeters.width),
        height: Math.round(sizeMillimeters.height),
      };

      if (
        Math.abs(exactMillimeters.width - intMillimeters.width) < 0.1 &&
        Math.abs(exactMillimeters.height - intMillimeters.height) < 0.1
      ) {
        nameId = getPageName(intMillimeters, isPortrait, METRIC_PAGE_NAMES);
        if (nameId) {
          sizeInches = {
            width: Math.round((intMillimeters.width / 25.4) * 100) / 100,
            height: Math.round((intMillimeters.height / 25.4) * 100) / 100,
          };
          sizeMillimeters = intMillimeters;
        }
      }
    }

    // Determine the unit string.
    const unit = nonMetric ? "in" : "mm";
    // Determine the orientation.
    const orientationStr = isPortrait ? "portrait" : "landscape";
    // Map the nameId to a human-readable page name.
    let pageName;
    if (nameId) {
      // In our US_PAGE_NAMES, keys are "8.5x11" and "8.5x14".
      // Here we translate them to display names.
      if (nameId === US_PAGE_NAMES["8.5x11"]) {
        pageName = "Letter";
      } else if (nameId === US_PAGE_NAMES["8.5x14"]) {
        pageName = "Legal";
      } else {
        pageName = "";
      }
    } else {
      pageName = "Custom";
    }
    // Return the formatted string. For non-metric, we show inches.
    return `${sizeInches.width} × ${sizeInches.height} ${unit} (${pageName}, ${orientationStr})`;
  }

  async #parseDate(inputDate) {
    const dateObj = PDFDateString.toDateObject(inputDate);
    if (!dateObj) {
      return "-";
    }
    // Format the date as "Jun 13, 2004"
    const options = { year: "numeric", month: "short", day: "numeric" };
    return dateObj.toLocaleDateString(undefined, options);
  }

  #parseLinearization(isLinearized) {
    // Return a formatted string indicating if the document is linearized.
    return isLinearized ? "Yes" : "No";
  }
}

export { PDFDocumentProperties };
