<?php require_once __DIR__.'/plant-page.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8">
  <base target="_top">

  <meta
    name="viewport"
    content="width=device-width, initial-scale=1"
  >

  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      padding: 22px 16px 50px;
      font-family: Arial, Helvetica, sans-serif;
      color: #172033;
      background: #f2f6fb;
    }

    .page-container {
      width: 100%;
      max-width: 1260px;
      margin: 0 auto;
    }

    .back-button {
      min-height: 46px;
      margin-bottom: 24px;
      padding: 0 20px;
      border: 1px solid #194776;
      border-radius: 10px;
      color: #194776;
      background: white;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
    }

    .back-button:hover {
      color: white;
      background: #194776;
    }

    .page-heading {
      margin-bottom: 24px;
      padding: 28px;
      border-radius: 18px;
      color: white;
      background:
        linear-gradient(
          135deg,
          #153f72,
          #28679e
        );
      box-shadow:
        0 12px 35px
        rgba(24, 66, 108, 0.18);
    }

    .page-heading h1 {
      margin: 0 0 10px;
      font-size: 34px;
    }

    .selection-path {
      margin: 0;
      color: #dcecff;
      font-size: 18px;
    }

    .underbody-card {
      overflow: hidden;
      margin-bottom: 28px;
      border: 1px solid #dce5ef;
      border-radius: 18px;
      background: white;
      box-shadow:
        0 10px 30px
        rgba(31, 68, 112, 0.10);
    }

    .underbody-header {
      padding: 20px 25px;
      color: white;
      background: #194776;
    }

    .underbody-header h2 {
      margin: 0;
      font-size: 24px;
    }

    .underbody-picture {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 420px;
      padding: 24px;
      background: #eef3f8;
    }

    .underbody-picture img {
      display: block;
      max-width: 100%;
      max-height: 760px;
      object-fit: contain;
      border-radius: 10px;
      background: white;
      cursor: zoom-in;
    }

    .underbody-information {
      padding: 20px 25px;
    }

    .underbody-information h3 {
      margin: 0;
      color: #194776;
      font-size: 22px;
    }

    .module-section {
      padding: 28px;
      border: 1px solid #dce5ef;
      border-radius: 18px;
      background: white;
      box-shadow:
        0 10px 30px
        rgba(31, 68, 112, 0.10);
    }

    .module-section h2 {
      margin: 0 0 8px;
      color: #194776;
      text-align: center;
      font-size: 26px;
    }

    .module-instruction {
      margin: 0 0 24px;
      color: #67788f;
      text-align: center;
    }

    .module-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 18px;
    }

    .module-button {
      min-height: 105px;
      padding: 18px;
      border: 1px solid #bfd0e2;
      border-radius: 14px;
      color: #194776;
      background: #f6faff;
      font-size: 18px;
      font-weight: 700;
      cursor: pointer;
      transition: 0.2s ease;
    }

    .module-button:hover {
      color: white;
      background: #194776;
      border-color: #194776;
      transform: translateY(-3px);
      box-shadow:
        0 10px 22px
        rgba(25, 71, 118, 0.20);
    }

    .loading-message,
    .error-message,
    .empty-message {
      padding: 35px;
      border-radius: 14px;
      text-align: center;
      background: white;
    }

    .error-message {
      color: #b42318;
      background: #fff1f1;
      border: 1px solid #ffc7c7;
    }

    .empty-message {
      color: #805b10;
      background: #fff9e9;
      border: 1px solid #eed895;
    }

    .spinner {
      display: inline-block;
      width: 24px;
      height: 24px;
      margin-right: 10px;
      vertical-align: middle;
      border: 4px solid #d6e4f2;
      border-top-color: #194776;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    .image-modal {
      position: fixed;
      inset: 0;
      z-index: 1000;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 24px;
      background: rgba(9, 24, 42, 0.88);
    }

    .image-modal.open {
      display: flex;
    }

    .modal-image {
      max-width: 96vw;
      max-height: 92vh;
      object-fit: contain;
      border-radius: 10px;
      background: white;
    }

    .modal-close {
      position: fixed;
      top: 18px;
      right: 22px;
      width: 44px;
      height: 44px;
      border: 1px solid rgba(255,255,255,0.45);
      border-radius: 50%;
      color: white;
      background: rgba(255,255,255,0.12);
      font-size: 27px;
      cursor: pointer;
    }

    @media (max-width: 800px) {
      body {
        padding: 16px 10px 35px;
      }

      .page-heading {
        padding: 22px;
      }

      .page-heading h1 {
        font-size: 27px;
      }

      .underbody-picture {
        min-height: 260px;
        padding: 12px;
      }

      .module-section {
        padding: 20px 14px;
      }

      .module-grid {
        grid-template-columns: 1fr;
      }
    }
  
    /* Floating Go to Top button */
    .global-back-to-top-button {
      position: fixed;
      right: 26px;
      bottom: 26px;
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      min-width: 112px;
      min-height: 48px;
      padding: 0 17px;
      border: 2px solid #ffffff;
      border-radius: 999px;
      color: #ffffff;
      background: #194776;
      box-shadow:
        0 10px 28px
        rgba(25, 71, 118, 0.30);
      font-family: Arial, Helvetica, sans-serif;
      font-size: 14px;
      font-weight: 800;
      cursor: pointer;
      opacity: 0;
      visibility: hidden;
      transform: translateY(14px);
      transition:
        opacity 0.2s ease,
        visibility 0.2s ease,
        transform 0.2s ease,
        background 0.2s ease;
    }

    .global-back-to-top-button.show {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .global-back-to-top-button:hover,
    .global-back-to-top-button:focus-visible {
      outline: none;
      background: #28679e;
    }

    .global-back-to-top-arrow {
      font-size: 21px;
      line-height: 1;
    }

    @media (max-width: 700px) {
      .global-back-to-top-button {
        right: 14px;
        bottom: 14px;
        min-width: 52px;
        min-height: 52px;
        padding: 0;
      }

      .global-back-to-top-text {
        display: none;
      }
    }

  </style>

  <?php require __DIR__ . '/theme.php'; ?>
<link rel="stylesheet" href="model-selection.css?v=<?=filemtime(__DIR__.'/model-selection.css')?>">
<link rel="stylesheet" href="catalogue-details.css?v=<?=filemtime(__DIR__.'/catalogue-details.css')?>">
<script src="api-client.js?v=<?=filemtime(__DIR__.'/api-client.js')?>" defer></script>
</head>

<body class="catalogue-detail detail-modules">

  <div class="page-container">

    <button
      class="back-button"
      type="button"
      onclick="returnVariants()"
    >
      ← Back to Previous Selection
    </button>

    <header class="page-heading"><p class="detail-eyebrow"><span>04</span> Module selection <i aria-hidden="true"></i> <?=htmlspecialchars(visPlants()[visPlant()],ENT_QUOTES)?></p>
      <h1>Full Underbody Assembly</h1>

      <p
        id="selectionPath"
        class="selection-path"
      ></p>
    </header>

    <main id="content">
      <div class="loading-message">
        <span class="spinner"></span>
        Loading full underbody assembly...
      </div>
    </main>

  </div>

  <div
    id="imageModal"
    class="image-modal"
    onclick="closeImageModal(event)"
  >
    <button
      class="modal-close"
      type="button"
      onclick="closeImageModal(event)"
    >
      ×
    </button>

    <img
      id="modalImage"
      class="modal-image"
      alt="Full underbody assembly"
    >
  </div>

  <script>
    const WEB_APP_URL =
      <?php echo json_encode(rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'])),'/').'/index.php', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    const SELECTED_MODEL =
      <?php echo json_encode(($_GET['model'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    const SELECTED_VARIANT =
      <?php echo json_encode(($_GET['variant'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    const SELECTED_GRADE =
      <?php echo json_encode(($_GET['grade'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;


    document.addEventListener(
      'DOMContentLoaded',
      loadVariantPage
    );


    function loadVariantPage() {
      document.getElementById(
        'selectionPath'
      ).textContent =
        SELECTED_MODEL +
        ' → ' +
        SELECTED_VARIANT +
        ' → ' +
        SELECTED_GRADE;

      if (
        !SELECTED_MODEL ||
        !SELECTED_VARIANT ||
        !SELECTED_GRADE
      ) {
        showError(
          'Model, variant or grade is missing.'
        );

        return;
      }

      google.script.run
        .withSuccessHandler(displayVariantPage)
        .withFailureHandler(function (error) {
          showError(
            error && error.message
              ? error.message
              : 'Unable to load the variant information.'
          );
        })
        .getVariantSelection(
          SELECTED_MODEL,
          SELECTED_VARIANT,
          SELECTED_GRADE
        );
    }


    function displayVariantPage(response) {
      const content =
        document.getElementById('content');

      content.innerHTML = '';

      if (!response || !response.found) {
        showError(
          response && response.message
            ? response.message
            : 'No variant information was found.'
        );

        return;
      }

      const underbodyCard =
        document.createElement('section');

      underbodyCard.className =
        'underbody-card';

      const underbodyHeader =
        document.createElement('div');

      underbodyHeader.className =
        'underbody-header';

      const title =
        document.createElement('h2');

      title.textContent =
        response.underbody &&
        response.underbody.pictureName
          ? response.underbody.pictureName
          : 'Full Underbody Assembly';

      underbodyHeader.appendChild(title);
      underbodyCard.appendChild(
        underbodyHeader
      );

      const pictureArea =
        document.createElement('div');

      pictureArea.className =
        'underbody-picture';

      if (
        response.underbody &&
        response.underbody.imageUrl
      ) {
        const image =
          document.createElement('img');

        image.src =
          response.underbody.imageUrl;

        image.alt =
          response.underbody.pictureName ||
          'Full Underbody Assembly';

        image.title =
          'Click to enlarge';

        image.onclick = function () {
          openImageModal(
            response.underbody.imageUrl
          );
        };

        image.onerror = function () {
          pictureArea.innerHTML =
            '<div class="error-message">' +
              'Picture unavailable. Check the Drive link and permission.' +
            '</div>';
        };

        pictureArea.appendChild(image);
      } else {
        pictureArea.innerHTML =
          '<div class="empty-message">' +
            'Add an ALL row with the full underbody picture ' +
            'inside the Overview sheet.' +
          '</div>';
      }

      underbodyCard.appendChild(
        pictureArea
      );

      const underbodyInformation =
        document.createElement('div');

      underbodyInformation.className =
        'underbody-information';

      const underbodyName =
        document.createElement('h3');

      underbodyName.textContent =
        response.underbody &&
        response.underbody.pictureName
          ? response.underbody.pictureName
          : 'Full Underbody Assembly';

      underbodyInformation.appendChild(
        underbodyName
      );

      underbodyCard.appendChild(
        underbodyInformation
      );

      content.appendChild(
        underbodyCard
      );

      const moduleSection =
        document.createElement('section');

      moduleSection.className =
        'module-section';

      const moduleHeading =
        document.createElement('h2');

      moduleHeading.textContent =
        'Select Module';

      const moduleInstruction =
        document.createElement('p');

      moduleInstruction.className =
        'module-instruction';

      moduleInstruction.textContent =
        'Choose an assembly to view its overview pictures.';

      const moduleGrid =
        document.createElement('div');

      moduleGrid.className =
        'module-grid';

      response.modules.forEach(function (
        module
      ) {
        const button =
          document.createElement('button');

        button.className =
          'module-button';

        button.type =
          'button';

        button.textContent =
          module.label;

        button.onclick = function () {
          openResultsPage(
            module.code
          );
        };

        moduleGrid.appendChild(button);
      });

      moduleSection.appendChild(
        moduleHeading
      );

      moduleSection.appendChild(
        moduleInstruction
      );

      moduleSection.appendChild(
        moduleGrid
      );

      content.appendChild(
        moduleSection
      );
    }


    function openResultsPage(moduleCode) {
      const resultsUrl =
        WEB_APP_URL +
        '?page=results' +
        '&model=' +
        encodeURIComponent(
          SELECTED_MODEL
        ) +
        '&variant=' +
        encodeURIComponent(
          SELECTED_VARIANT
        ) +
        '&grade=' +
        encodeURIComponent(
          SELECTED_GRADE
        ) +
        '&module=' +
        encodeURIComponent(
          moduleCode
        );

      window.top.location.href =
        window.visPlantUrl(resultsUrl);
    }


    function openImageModal(imageUrl) {
      const modal =
        document.getElementById(
          'imageModal'
        );

      document.getElementById(
        'modalImage'
      ).src =
        imageUrl;

      modal.classList.add('open');
      document.body.style.overflow =
        'hidden';
    }


    function closeImageModal(event) {
      if (
        event &&
        event.target &&
        event.target.id === 'modalImage'
      ) {
        return;
      }

      document.getElementById(
        'imageModal'
      ).classList.remove('open');

      document.getElementById(
        'modalImage'
      ).src = '';

      document.body.style.overflow = '';
    }


    document.addEventListener(
      'keydown',
      function (event) {
        if (event.key === 'Escape') {
          closeImageModal();
        }
      }
    );


    function showError(messageText) {
      const content =
        document.getElementById('content');

      content.innerHTML = '';

      const message =
        document.createElement('div');

      message.className =
        'error-message';

      message.textContent =
        messageText;

      content.appendChild(message);
    }


    function returnVariants() {
      if (
        normaliseValue(SELECTED_GRADE) !==
        'STANDARD'
      ) {
        const gradePageUrl =
          WEB_APP_URL +
          '?page=grades' +
          '&model=' +
          encodeURIComponent(
            SELECTED_MODEL
          ) +
          '&variant=' +
          encodeURIComponent(
            SELECTED_VARIANT
          );

        window.top.location.href =
          window.visPlantUrl(gradePageUrl);

        return;
      }

      const variantPageUrl =
        WEB_APP_URL +
        '?page=variants' +
        '&model=' +
        encodeURIComponent(
          SELECTED_MODEL
        );

      window.top.location.href =
        window.visPlantUrl(variantPageUrl);
    }


    function normaliseValue(value) {
      return String(value || '')
        .trim()
        .toUpperCase();
    }
  </script>


  <button
    id="globalBackToTopButton"
    class="global-back-to-top-button"
    type="button"
    aria-label="Go to top of page"
    title="Go to top"
  >
    <span class="global-back-to-top-arrow">↑</span>
    <span class="global-back-to-top-text">Go to Top</span>
  </button>

  <script>
    (function () {
      const button =
        document.getElementById(
          'globalBackToTopButton'
        );

      if (!button) {
        return;
      }

      function updateBackToTopButton() {
        const pageCanScroll =
          document.documentElement.scrollHeight >
          window.innerHeight + 80;

        const hasScrolledDown =
          window.scrollY > 320;

        button.classList.toggle(
          'show',
          pageCanScroll && hasScrolledDown
        );
      }

      button.addEventListener(
        'click',
        function () {
          window.scrollTo({
            top: 0,
            behavior: 'smooth'
          });
        }
      );

      window.addEventListener(
        'scroll',
        updateBackToTopButton,
        { passive: true }
      );

      window.addEventListener(
        'resize',
        updateBackToTopButton
      );

      document.addEventListener(
        'DOMContentLoaded',
        updateBackToTopButton
      );

      updateBackToTopButton();
    })();
  </script>

<script src="vis-last-updated.js?v=<?=filemtime(__DIR__.'/vis-last-updated.js')?>" defer></script>
</body>
</html>
