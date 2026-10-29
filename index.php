<?php require_once __DIR__.'/plant-page.php';
if (!isset($_GET['plant']) && !isset($_GET['page']) && !isset($_GET['model'])) { require __DIR__.'/PlantSelect.php'; exit; }
// Route navigation requests to their corresponding VIS page.
// Keep index.php as the entry point so existing ?page=... links work.
$visPage = strtolower(trim((string) ($_GET['page'] ?? 'home')));
$visRoutes = [
    'variants' => 'VariantSelect.php',
    'grades'   => 'GradeSelect.php',
    'modules'  => 'ModuleSelect.php',
    'results'  => 'Results.php',
];
if (isset($visRoutes[$visPage])) {
    require __DIR__ . '/' . $visRoutes[$visPage];
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Select Model | Vehicle Information System</title>
  <base target="_top">

  <meta
    name="viewport"
    content="width=device-width, initial-scale=1"
  >

  <style>
    * {
      box-sizing: border-box;
    }

    :root {
      --page-background-image: none;
    }

    body {
      margin: 0;
      min-height: 100vh;
      padding: 40px 18px 55px;
      font-family: Arial, Helvetica, sans-serif;
      color: #172033;
      background-color: #eef4ff;
      background-image:
        linear-gradient(
          rgba(238, 244, 255, 0.88),
          rgba(248, 251, 255, 0.84) 50%,
          rgba(237, 247, 244, 0.88)
        ),
        var(--page-background-image);
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      background-attachment: fixed;
    }

    .page-container {
      width: 100%;
      max-width: 1180px;
      margin: 0 auto;
    }

    .company-logo-container {
      display: none;
      margin-bottom: 18px;
      text-align: center;
    }

    .company-logo-container.visible {
      display: block;
    }

    .company-logo {
      display: inline-block;
      max-width: 260px;
      max-height: 110px;
      object-fit: contain;
      filter:
        drop-shadow(
          0 6px 14px
          rgba(15, 50, 86, 0.16)
        );
    }

    .header {
      margin-bottom: 34px;
      text-align: center;
    }

    .header h1 {
      margin: 0 0 12px;
      color: #153f72;
      font-size: 42px;
    }

    .header p {
      margin: 0;
      color: #63748b;
      font-size: 18px;
    }

    .selection-card {
      padding: 38px;
      border: 1px solid #e0e7ef;
      border-radius: 22px;
      background: white;
      box-shadow:
        0 18px 50px
        rgba(31, 68, 112, 0.13);
    }

    .section-heading {
      margin: 0 0 8px;
      color: #153f72;
      text-align: center;
      font-size: 27px;
    }

    .section-description {
      margin: 0 0 28px;
      color: #718096;
      text-align: center;
      line-height: 1.5;
    }

    .model-grid {
      display: grid;
      grid-template-columns:
        repeat(3, minmax(0, 1fr));
      gap: 24px;
    }

    .model-card {
      overflow: hidden;
      width: 100%;
      padding: 0;
      border: 1px solid #d7e1ec;
      border-radius: 18px;
      color: inherit;
      background: white;
      text-align: left;
      cursor: pointer;
      box-shadow:
        0 8px 22px
        rgba(31, 68, 112, 0.09);
      transition:
        transform 0.2s ease,
        border-color 0.2s ease,
        box-shadow 0.2s ease;
    }

    .model-card:hover,
    .model-card:focus-visible {
      border-color: #2878c7;
      outline: none;
      transform: translateY(-5px);
      box-shadow:
        0 16px 32px
        rgba(31, 68, 112, 0.18);
    }

    .model-image-frame {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: clamp(260px, 22vw, 330px);
      overflow: hidden;
      padding: 4px 6px;
      background:
        linear-gradient(
          145deg,
          #eef4fa,
          #ffffff
        );
    }

    .model-image {
      display: block;
      width: 100%;
      height: 100%;
      object-fit: contain;
      object-position: center;
      transform: scale(1.18);
      transform-origin: center;
      transition: transform 0.25s ease;
    }

    .model-card:hover .model-image,
    .model-card:focus-visible .model-image {
      transform: scale(1.23);
    }

    /*
     * S70 has a longer sedan body, so it needs a little
     * less zoom than the X50 and X70 images.
     */
    .model-card-s70 .model-image {
      object-position: center 52%;
      transform: scale(1.06);
    }

    .model-card-s70:hover .model-image,
    .model-card-s70:focus-visible .model-image {
      transform: scale(1.10);
    }

    .no-image {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: 100%;
      border: 2px dashed #c9d5e2;
      border-radius: 12px;
      color: #7b8ba0;
      background: #f5f8fb;
      font-size: 14px;
      font-weight: 700;
      text-align: center;
    }

    .model-information {
      padding: 19px 20px 21px;
      border-top: 1px solid #e7edf4;
    }

    .model-name {
      display: block;
      margin-bottom: 7px;
      color: #153f72;
      font-size: 24px;
      font-weight: 800;
    }

    .model-action {
      display: flex;
      align-items: center;
      justify-content: space-between;
      color: #60758e;
      font-size: 14px;
      font-weight: 700;
    }

    .arrow {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      color: white;
      background: #194776;
      font-size: 18px;
    }

    .flow-information {
      margin-top: 28px;
      color: #718096;
      text-align: center;
    }

    .loading-message,
    .error-message,
    .empty-message {
      grid-column: 1 / -1;
      padding: 34px;
      border-radius: 14px;
      text-align: center;
    }

    .loading-message {
      color: #44556c;
      background: #f6f9fc;
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

    @media (max-width: 850px) {
      .model-grid {
        grid-template-columns:
          repeat(2, minmax(0, 1fr));
      }

      .model-image-frame {
        height: 280px;
      }

      .model-image {
        transform: scale(1.14);
      }

      .model-card:hover .model-image,
      .model-card:focus-visible .model-image {
        transform: scale(1.18);
      }

      .model-card-s70 .model-image {
        transform: scale(1.04);
      }

      .model-card-s70:hover .model-image,
      .model-card-s70:focus-visible .model-image {
        transform: scale(1.08);
      }
    }

    @media (max-width: 600px) {
      body {
        padding: 25px 12px 40px;
      }

      .header h1 {
        font-size: 31px;
      }

      .header p {
        font-size: 16px;
      }

      .selection-card {
        padding: 22px;
      }

      .section-heading {
        font-size: 23px;
      }

      .model-grid {
        grid-template-columns: 1fr;
      }

      .model-image-frame {
        height: 260px;
        padding: 2px 4px;
      }

      .model-image {
        transform: scale(1.10);
      }

      .model-card:hover .model-image,
      .model-card:focus-visible .model-image {
        transform: scale(1.14);
      }

      .model-card-s70 .model-image {
        transform: scale(1.02);
      }

      .model-card-s70:hover .model-image,
      .model-card-s70:focus-visible .model-image {
        transform: scale(1.06);
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
<script src="api-client.js?v=<?=filemtime(__DIR__.'/api-client.js')?>" defer></script>
</head>

<body class="catalogue-page">
  <div class="page-container">

    <header class="header catalogue-hero">
      <div class="catalogue-hero-copy">
        <p class="catalogue-eyebrow"><span></span> <?=htmlspecialchars(visPlants()[visPlant()],ENT_QUOTES)?> &nbsp; / &nbsp; Vehicle catalogue</p>
        <h1>Select your vehicle.</h1>
        <p class="catalogue-introduction">From model to assembly.<br>Find the production information you need.</p>
        <div class="catalogue-hero-step"><span>01</span> Model selection <i aria-hidden="true"></i> Your journey starts here</div>
      </div>
      <div class="catalogue-hero-brand">
        <div id="companyLogoContainer" class="company-logo-container"><img id="companyLogo" class="company-logo" alt="Company logo"></div>
        <span>VEHICLE INFORMATION SYSTEM</span>
        <svg class="catalogue-vehicle-outline" viewBox="0 0 380 135" fill="none" aria-hidden="true"><path d="m20 90 12-23 66-12 43-35h99l49 39 49 9 17 22v24h-33m-50 0H115m-49 0H20V90Z"/><path d="m113 55 33-26h88l34 30-155-4Zm66-26v28M33 82h40m238 1h33"/><circle cx="90" cy="109" r="25"/><circle cx="90" cy="109" r="14"/><circle cx="297" cy="109" r="25"/><circle cx="297" cy="109" r="14"/></svg>
      </div>
    </header>

    <main class="selection-card catalogue-selection">
      <div class="catalogue-section-top"><div><p class="catalogue-section-kicker">THE VEHICLE LINEUP</p><h2 class="section-heading">Available models</h2><p class="section-description">Choose a model to explore its variants and assembly details.</p></div><span id="modelCount" class="catalogue-model-count" role="status">Loading catalogue</span></div>
      <div
        id="modelGrid"
        class="model-grid"
      >
        <div class="loading-message">
          <span class="spinner"></span>
          Loading vehicle models...
        </div>
      </div>

      <footer class="flow-information catalogue-footer"><span><strong>PHN INDUSTRY</strong> &nbsp; / &nbsp; <?=htmlspecialchars(visPlants()[visPlant()],ENT_QUOTES)?> catalogue</span><a href="index.php" data-change-plant>Looking for another plant? <strong>Change plant &rarr;</strong></a></footer>
    </main>

  </div>

  <script>
    const WEB_APP_URL =
      <?php echo json_encode(rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'])),'/').'/index.php', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;


    document.addEventListener(
      'DOMContentLoaded',
      loadHomePage
    );


    function loadHomePage() {
      loadHomePageSettings();
      loadModelCards();
    }


    function loadHomePageSettings() {
      google.script.run
        .withSuccessHandler(
          applyHomePageSettings
        )
        .withFailureHandler(function (error) {
          console.error(
            'Unable to load logo/background:',
            error
          );
        })
        .getHomePageSettings();
    }


    function applyHomePageSettings(settings) {
      if (!settings) {
        return;
      }

      if (settings.backgroundUrl) {
        const backgroundValue =
          'url(' +
          JSON.stringify(
            settings.backgroundUrl
          ) +
          ')';

        document.documentElement.style
          .setProperty(
            '--page-background-image',
            backgroundValue
          );
      }

      if (settings.logoUrl) {
        const logoContainer =
          document.getElementById(
            'companyLogoContainer'
          );

        const logo =
          document.getElementById(
            'companyLogo'
          );

        logo.src =
          settings.logoUrl;

        logo.alt =
          settings.logoAlt ||
          'Company logo';

        logo.onerror = function () {
          logoContainer.classList.remove(
            'visible'
          );
        };

        logoContainer.classList.add(
          'visible'
        );
      }
    }


    function loadModelCards() {
      google.script.run
        .withSuccessHandler(
          displayModelCards
        )
        .withFailureHandler(function (error) {
          showModelError(
            error && error.message
              ? error.message
              : 'Unable to load vehicle models.'
          );
        })
        .getModelCards();
    }


    function displayModelCards(response) {
      const modelCount = response && Array.isArray(response.models) ? response.models.length : 0;
      document.getElementById('modelCount').textContent = modelCount + (modelCount === 1 ? ' model available' : ' models available');
      const modelGrid =
        document.getElementById(
          'modelGrid'
        );

      modelGrid.innerHTML = '';

      if (!response || !response.found) {
        const emptyMessage =
          document.createElement('div');

        emptyMessage.className =
          'empty-message';

        emptyMessage.textContent =
          response && response.message
            ? response.message
            : 'No active models were found.';

        modelGrid.appendChild(
          emptyMessage
        );

        return;
      }

      response.models.forEach(function (
        item
      ) {
        const card =
          document.createElement('button');

        card.type = 'button';
        card.className = 'model-card';

        if (
          String(item.model || '')
            .trim()
            .toUpperCase() === 'S70'
        ) {
          card.classList.add(
            'model-card-s70'
          );
        }

        card.setAttribute(
          'aria-label',
          'Select ' + item.displayName
        );

        card.onclick = function () {
          openVariantPage(item.model);
        };

        const imageFrame =
          document.createElement('div');

        imageFrame.className =
          'model-image-frame';

        if (item.imageUrl) {
          const image =
            document.createElement('img');

          image.className =
            'model-image';

          image.src =
            item.imageUrl;

          image.alt =
            item.displayName +
            ' vehicle model';

          image.loading =
            'lazy';

          image.onerror = function () {
            imageFrame.innerHTML =
              '<div class="no-image">' +
                'Picture unavailable' +
              '</div>';
          };

          imageFrame.appendChild(image);
        } else {
          imageFrame.innerHTML =
            '<div class="no-image">' +
              'Add model picture link' +
            '</div>';
        }

        const information =
          document.createElement('div');

        information.className =
          'model-information';

        const name =
          document.createElement('span');

        name.className =
          'model-name';

        name.textContent =
          item.displayName;

        const action =
          document.createElement('span');

        action.className =
          'model-action';

        const actionText =
          document.createElement('span');

        actionText.textContent =
          'Explore variants';

        const arrow =
          document.createElement('span');

        arrow.className =
          'arrow';

        arrow.textContent =
          '\u2197';

        action.appendChild(
          actionText
        );

        action.appendChild(
          arrow
        );

        const eyebrow = document.createElement('span');
        eyebrow.className = 'model-eyebrow';
        eyebrow.textContent = 'VEHICLE MODEL';
        information.appendChild(eyebrow);
        information.appendChild(name);
        information.appendChild(action);

        card.appendChild(imageFrame);
        card.appendChild(information);

        modelGrid.appendChild(card);
      });
    }


    function openVariantPage(model) {
      if (!model) {
        return;
      }

      const variantPageUrl =
        WEB_APP_URL +
        '?page=variants' +
        '&model=' +
        encodeURIComponent(model);

      window.top.location.href =
        window.visPlantUrl(variantPageUrl);
    }


    function showModelError(messageText) {
      const modelGrid =
        document.getElementById(
          'modelGrid'
        );

      modelGrid.innerHTML = '';

      const errorMessage =
        document.createElement('div');

      errorMessage.className =
        'error-message';

      errorMessage.textContent =
        messageText;

      modelGrid.appendChild(
        errorMessage
      );
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