const SPREADSHEET_ID =
  '132LL0P7mC0pbIWbOeDksokRl6GjCqgjX1HlmliU-3F8';

// Requested Google Drive thumbnail width for detailed diagrams.
const DRIVE_IMAGE_WIDTH = 4000;

const FRONT_PAGE_SHEET = 'FRONT_PAGE';
const VARIANT_DIFFERENCE_SHEET = 'VARIANT_DIFFERENCE';
const MODEL_SELECTION_SHEET = 'MODEL_SELECTION';
const SITE_SETTINGS_SHEET = 'SITE_SETTINGS';
const OVERVIEW_DATABASE_SHEET = 'OVERVIEW_DATABASE';
const GRADE_SELECTION_SHEET = 'GRADE_SELECTION';
const GRADE_COMPARISON_SHEET = 'GRADE_COMPARISON';
const VARIANT_COLOUR_SHEET = 'VARIANT_COLOUR';
const VARIANT_TAGGING_SHEET = 'VARIANT_TAGGING';
const PAPER_COLOUR_INFO_SHEET = 'PAPER_COLOUR_INFO';


/**
 * Opens the Home page or Results page.
 */
function doGet(e) {
  const parameters =
    e && e.parameter ? e.parameter : {};

  const page =
    String(parameters.page || 'home')
      .toLowerCase();

  const pageFiles = {
    home: 'Index',
    variants: 'VariantSelect',
    grades: 'GradeSelect',
    modules: 'ModuleSelect',
    results: 'Results'
  };

  const fileName =
    pageFiles[page] || 'Index';

  const template =
    HtmlService.createTemplateFromFile(fileName);

  template.webAppUrl =
    ScriptApp.getService().getUrl();

  template.selectedModel =
    parameters.model || '';

  template.selectedVariant =
    parameters.variant || '';

  template.selectedGrade =
    parameters.grade || '';

  template.selectedModule =
    parameters.module || '';

  return template
    .evaluate()
    .setTitle('Vehicle Information System')
    .addMetaTag(
      'viewport',
      'width=device-width, initial-scale=1'
    )
    .setXFrameOptionsMode(
      HtmlService.XFrameOptionsMode.ALLOWALL
    );
}


/**
 * Loads the company logo and first-page background
 * from the SITE_SETTINGS sheet.
 *
 * Required headings:
 * Setting, Image Link, Alt Text, Active
 */
function getHomePageSettings() {
  const spreadsheet =
    SpreadsheetApp.openById(SPREADSHEET_ID);

  const sheet =
    spreadsheet.getSheetByName(
      SITE_SETTINGS_SHEET
    );

  if (!sheet) {
    return {
      found: false,
      logoUrl: '',
      logoAlt: 'Company logo',
      backgroundUrl: ''
    };
  }

  const data =
    sheet.getDataRange().getDisplayValues();

  if (data.length < 2) {
    return {
      found: false,
      logoUrl: '',
      logoAlt: 'Company logo',
      backgroundUrl: ''
    };
  }

  const headers =
    data[0].map(normaliseHeader_);

  const columns = {
    setting: findColumn_(headers, [
      'setting',
      'setting name',
      'type'
    ]),

    imageLink: findColumn_(headers, [
      'image link',
      'image url',
      'picture link',
      'picture url',
      'link'
    ]),

    altText: findColumn_(headers, [
      'alt text',
      'alternative text',
      'label',
      'name'
    ]),

    active: findColumn_(headers, [
      'active'
    ])
  };

  checkRequiredColumns_(
    columns,
    [
      'setting',
      'imageLink',
      'altText',
      'active'
    ],
    SITE_SETTINGS_SHEET
  );

  let logoUrl = '';
  let logoAlt = 'Company logo';
  let backgroundUrl = '';

  for (
    let rowIndex = 1;
    rowIndex < data.length;
    rowIndex++
  ) {
    const row = data[rowIndex];

    if (!isActive_(row[columns.active])) {
      continue;
    }

    const setting =
      normaliseValue_(
        row[columns.setting]
      );

    const imageLink =
      String(
        row[columns.imageLink] || ''
      ).trim();

    const imageUrl =
      convertToDisplayImageUrl_(
        imageLink
      );

    if (
      setting === 'LOGO' ||
      setting === 'COMPANY LOGO'
    ) {
      logoUrl = imageUrl;

      logoAlt =
        String(
          row[columns.altText] ||
          'Company logo'
        ).trim();

      continue;
    }

    if (
      setting === 'BACKGROUND' ||
      setting === 'PAGE BACKGROUND' ||
      setting === 'HOME BACKGROUND'
    ) {
      backgroundUrl = imageUrl;
    }
  }

  return {
    found:
      Boolean(
        logoUrl ||
        backgroundUrl
      ),

    logoUrl:
      logoUrl,

    logoAlt:
      logoAlt,

    backgroundUrl:
      backgroundUrl
  };
}


/**
 * Loads clickable model cards for the first page.
 *
 * Preferred sheet:
 * MODEL_SELECTION
 *
 * Required headings:
 * Model, Image Link, Display Name, Sort Order, Active
 *
 * When MODEL_SELECTION is not available, the code falls back
 * to the active models listed in FRONT_PAGE without pictures.
 */
function getModelCards() {
  const spreadsheet =
    SpreadsheetApp.openById(SPREADSHEET_ID);

  const sheet =
    spreadsheet.getSheetByName(
      MODEL_SELECTION_SHEET
    );

  if (!sheet) {
    return getModelCardsFromFrontPage_(
      spreadsheet
    );
  }

  const data =
    sheet.getDataRange().getDisplayValues();

  if (data.length < 2) {
    return {
      found: false,
      message:
        'The MODEL_SELECTION sheet is empty.',
      models: []
    };
  }

  const headers =
    data[0].map(normaliseHeader_);

  const columns = {
    model: findColumn_(headers, [
      'model'
    ]),

    imageLink: findColumn_(headers, [
      'image link',
      'image url',
      'picture link',
      'picture url',
      'image'
    ]),

    displayName: findColumn_(headers, [
      'display name',
      'model name',
      'name',
      'title'
    ]),

    sortOrder: findColumn_(headers, [
      'sort order',
      'sequence',
      'order'
    ]),

    active: findColumn_(headers, [
      'active'
    ])
  };

  checkRequiredColumns_(
    columns,
    [
      'model',
      'imageLink',
      'displayName',
      'sortOrder',
      'active'
    ],
    MODEL_SELECTION_SHEET
  );

  const modelMap = {};

  for (
    let rowIndex = 1;
    rowIndex < data.length;
    rowIndex++
  ) {
    const row = data[rowIndex];

    if (!isActive_(row[columns.active])) {
      continue;
    }

    const model =
      String(
        row[columns.model] || ''
      ).trim();

    if (!model) {
      continue;
    }

    const key =
      normaliseValue_(model);

    const imageLink =
      String(
        row[columns.imageLink] || ''
      ).trim();

    modelMap[key] = {
      model: model,

      displayName:
        String(
          row[columns.displayName] ||
          model
        ).trim(),

      imageUrl:
        convertToDisplayImageUrl_(
          imageLink
        ),

      originalLink:
        imageLink,

      sortOrder:
        parseSortOrder_(
          row[columns.sortOrder]
        )
    };
  }

  const models =
    Object.keys(modelMap)
      .map(function (key) {
        return modelMap[key];
      })
      .sort(function (a, b) {
        if (a.sortOrder !== b.sortOrder) {
          return a.sortOrder - b.sortOrder;
        }

        return a.displayName.localeCompare(
          b.displayName
        );
      });

  return {
    found: models.length > 0,

    message:
      models.length > 0
        ? ''
        : 'No active model cards were found.',

    models: models
  };
}


/**
 * Fallback model list from FRONT_PAGE.
 * This keeps the website usable before MODEL_SELECTION
 * is created, but the cards will not have pictures.
 */
function getModelCardsFromFrontPage_(
  spreadsheet
) {
  const sheet =
    spreadsheet.getSheetByName(
      FRONT_PAGE_SHEET
    );

  if (!sheet) {
    throw new Error(
      'Neither MODEL_SELECTION nor FRONT_PAGE was found.'
    );
  }

  const data =
    sheet.getDataRange().getDisplayValues();

  if (data.length < 2) {
    return {
      found: false,
      message:
        'No active models were found.',
      models: []
    };
  }

  const headers =
    data[0].map(normaliseHeader_);

  const columns = {
    model: findColumn_(headers, ['model']),
    active: findColumn_(headers, ['active'])
  };

  checkRequiredColumns_(
    columns,
    ['model', 'active'],
    FRONT_PAGE_SHEET
  );

  const modelMap = {};

  for (
    let rowIndex = 1;
    rowIndex < data.length;
    rowIndex++
  ) {
    const row = data[rowIndex];

    if (!isActive_(row[columns.active])) {
      continue;
    }

    const model =
      String(
        row[columns.model] || ''
      ).trim();

    if (!model) {
      continue;
    }

    const key =
      normaliseValue_(model);

    if (!modelMap[key]) {
      modelMap[key] = {
        model: model,
        displayName: model,
        imageUrl: '',
        originalLink: '',
        sortOrder:
          getDefaultModelSortOrder_(model)
      };
    }
  }

  const models =
    Object.keys(modelMap)
      .map(function (key) {
        return modelMap[key];
      })
      .sort(function (a, b) {
        return a.sortOrder - b.sortOrder;
      });

  return {
    found: models.length > 0,

    message:
      models.length > 0
        ? ''
        : 'No active models were found.',

    models: models
  };
}


function getDefaultModelSortOrder_(model) {
  const order = {
    X50: 1,
    X70: 2,
    S70: 3
  };

  const key =
    normaliseValue_(model);

  return order[key] || 9999;
}


/**
 * Loads the active variants for one model and the
 * vertical comparison information from VARIANT_DIFFERENCE.
 */
function getVariantPage(model) {
  if (!model) {
    throw new Error(
      'Please select a model.'
    );
  }

  const spreadsheet =
    SpreadsheetApp.openById(SPREADSHEET_ID);

  const variantCodes =
    findActiveVariantsForModel_(
      spreadsheet,
      model
    );

  if (variantCodes.length === 0) {
    return {
      found: false,
      message:
        'No active variants were found for ' +
        model +
        '.',
      variants: []
    };
  }

  const differenceItems =
    readVariantDifferences_(
      spreadsheet,
      model
    );

  const differenceMap = {};

  differenceItems.forEach(function (item) {
    const key =
      normaliseValue_(item.variant);

    if (!differenceMap[key]) {
      differenceMap[key] = [];
    }

    differenceMap[key].push(item);
  });

  const colourMap =
    readVariantColours_(
      spreadsheet,
      model
    );

  const variants =
    variantCodes
      .map(function (variantCode) {
        const key =
          normaliseValue_(variantCode);

        const differences =
          differenceMap[key] || [];

        const colourSettings =
          colourMap[key] ||
          getDefaultVariantColour_(
            variantCode
          );

        differences.sort(sortItems_);

        let summary =
          getDefaultVariantSummary_(
            model,
            variantCode
          );

        for (
          let index = 0;
          index < differences.length;
          index++
        ) {
          if (differences[index].differenceNote) {
            summary =
              differences[index].differenceNote;
            break;
          }
        }

        if (
          normaliseValue_(model) === 'S70' &&
          key === 'AI-MY(LV)'
        ) {
          summary = '28 New Parts';
        }

        const grades =
          findActiveGradesForVariant_(
            spreadsheet,
            model,
            variantCode
          );

        return {
          code: variantCode,

          label:
            colourSettings.displayName ||
            getVariantLabel_(variantCode),

          summary: summary,
          differences: differences,
          grades: grades,
          gradeCount: grades.length,

          defaultGrade:
            grades.length === 1
              ? grades[0]
              : '',

          colourName:
            colourSettings.colourName,

          accentColour:
            colourSettings.accentColour,

          swatchColour:
            colourSettings.swatchColour ||
            colourSettings.accentColour,

          backgroundColour:
            colourSettings.backgroundColour,

          textColour:
            colourSettings.textColour,

          borderColour:
            colourSettings.borderColour,

          sortOrder:
            colourSettings.sortOrder !== undefined
              ? colourSettings.sortOrder
              : getVariantSortOrder_(
                  model,
                  variantCode
                )
        };
      })
      .sort(function (a, b) {
        if (a.sortOrder !== b.sortOrder) {
          return a.sortOrder - b.sortOrder;
        }

        return a.label.localeCompare(b.label);
      });

  const paperColours =
    readPaperColourInfo_(
      spreadsheet
    );

  return {
    found: true,
    selection: {
      model: model
    },
    variants: variants,
    paperColours: paperColours
  };
}


/**
 * Returns every active variant for one model from FRONT_PAGE.
 */
function findActiveVariantsForModel_(
  spreadsheet,
  model
) {
  const sheet =
    spreadsheet.getSheetByName(
      FRONT_PAGE_SHEET
    );

  if (!sheet) {
    throw new Error(
      'The FRONT_PAGE sheet was not found.'
    );
  }

  const data =
    sheet.getDataRange().getDisplayValues();

  if (data.length < 2) {
    throw new Error(
      'The FRONT_PAGE sheet is empty.'
    );
  }

  const headers =
    data[0].map(normaliseHeader_);

  const columns = {
    model: findColumn_(headers, ['model']),
    variant: findColumn_(headers, ['variant']),
    active: findColumn_(headers, ['active'])
  };

  checkRequiredColumns_(
    columns,
    ['model', 'variant', 'active'],
    FRONT_PAGE_SHEET
  );

  const selectedModel =
    normaliseValue_(model);

  const variantMap = {};

  for (
    let rowIndex = 1;
    rowIndex < data.length;
    rowIndex++
  ) {
    const row = data[rowIndex];

    if (
      normaliseValue_(row[columns.model]) !==
        selectedModel ||
      !isActive_(row[columns.active])
    ) {
      continue;
    }

    const variant =
      String(
        row[columns.variant] || ''
      ).trim();

    if (variant) {
      variantMap[
        normaliseValue_(variant)
      ] = variant;
    }
  }

  return Object.keys(variantMap)
    .map(function (key) {
      return variantMap[key];
    });
}


/**
 * Reads the optional VARIANT_DIFFERENCE sheet.
 * Required headings:
 * Model, Variant, Image Link, Part Name,
 * Part Number, Difference Note, Sort Order, Active
 */
function readVariantDifferences_(
  spreadsheet,
  model
) {
  const sheet =
    spreadsheet.getSheetByName(
      VARIANT_DIFFERENCE_SHEET
    );

  if (!sheet) {
    return [];
  }

  const data =
    sheet.getDataRange().getDisplayValues();

  if (data.length < 2) {
    return [];
  }

  const headers =
    data[0].map(normaliseHeader_);

  const columns = {
    model: findColumn_(headers, ['model']),
    variant: findColumn_(headers, ['variant']),

    imageLink: findColumn_(headers, [
      'image link',
      'image url',
      'picture link',
      'picture url',
      'image'
    ]),

    partName: findColumn_(headers, [
      'part name',
      'difference title',
      'title',
      'name'
    ]),

    partNumber: findColumn_(headers, [
      'part number',
      'part no',
      'part no.'
    ]),

    differenceNote: findColumn_(headers, [
      'difference note',
      'difference details',
      'difference',
      'notes',
      'note'
    ]),

    sortOrder: findColumn_(headers, [
      'sort order',
      'sequence',
      'order'
    ]),

    active: findColumn_(headers, [
      'active'
    ])
  };

  checkRequiredColumns_(
    columns,
    [
      'model',
      'variant',
      'imageLink',
      'partName',
      'partNumber',
      'differenceNote',
      'sortOrder',
      'active'
    ],
    VARIANT_DIFFERENCE_SHEET
  );

  const selectedModel =
    normaliseValue_(model);

  const items = [];

  for (
    let rowIndex = 1;
    rowIndex < data.length;
    rowIndex++
  ) {
    const row = data[rowIndex];

    if (
      normaliseValue_(row[columns.model]) !==
        selectedModel ||
      !isActive_(row[columns.active])
    ) {
      continue;
    }

    const imageLink =
      String(
        row[columns.imageLink] || ''
      ).trim();

    items.push({
      variant:
        String(
          row[columns.variant] || ''
        ).trim(),

      pictureName:
        String(
          row[columns.partName] || ''
        ).trim(),

      partNumber:
        String(
          row[columns.partNumber] || ''
        ).trim(),

      differenceNote:
        String(
          row[columns.differenceNote] || ''
        ).trim(),

      imageUrl:
        convertToDisplayImageUrl_(
          imageLink
        ),

      originalLink:
        imageLink,

      sortOrder:
        parseSortOrder_(
          row[columns.sortOrder]
        ),

      sheetRow:
        rowIndex + 1
    });
  }

  return items;
}



/**
 * Reads the colour-paper reference information.
 *
 * Sheet name:
 * PAPER_COLOUR_INFO
 *
 * Required headings:
 * Colour Name, Image Link, Brand, GSM,
 * Colour Code, Sort Order, Active
 */
function readPaperColourInfo_(
  spreadsheet
) {
  const sheet =
    spreadsheet.getSheetByName(
      PAPER_COLOUR_INFO_SHEET
    );

  if (!sheet) {
    return [];
  }

  const data =
    sheet.getDataRange().getDisplayValues();

  if (data.length < 2) {
    return [];
  }

  const headers =
    data[0].map(normaliseHeader_);

  const columns = {
    colourName: findColumn_(headers, [
      'colour name',
      'color name',
      'colour',
      'color'
    ]),

    imageLink: findColumn_(headers, [
      'image link',
      'image url',
      'picture link',
      'picture url',
      'image'
    ]),

    brand: findColumn_(headers, [
      'brand',
      'paper brand'
    ]),

    gsm: findColumn_(headers, [
      'gsm',
      'paper gsm',
      'weight'
    ]),

    colourCode: findColumn_(headers, [
      'colour code',
      'color code',
      'paper code',
      'code'
    ]),

    sortOrder: findColumn_(headers, [
      'sort order',
      'sequence',
      'order'
    ]),

    active: findColumn_(headers, [
      'active'
    ])
  };

  checkRequiredColumns_(
    columns,
    [
      'colourName',
      'imageLink',
      'brand',
      'gsm',
      'colourCode',
      'sortOrder',
      'active'
    ],
    PAPER_COLOUR_INFO_SHEET
  );

  const items = [];

  for (
    let rowIndex = 1;
    rowIndex < data.length;
    rowIndex++
  ) {
    const row = data[rowIndex];

    if (!isActive_(row[columns.active])) {
      continue;
    }

    const colourName =
      String(
        row[columns.colourName] || ''
      ).trim();

    if (!colourName) {
      continue;
    }

    const imageLink =
      String(
        row[columns.imageLink] || ''
      ).trim();

    items.push({
      colourName: colourName,

      imageUrl:
        convertToDisplayImageUrl_(
          imageLink
        ),

      originalLink:
        imageLink,

      brand:
        String(
          row[columns.brand] || ''
        ).trim(),

      gsm:
        String(
          row[columns.gsm] || ''
        ).trim(),

      colourCode:
        String(
          row[columns.colourCode] || ''
        ).trim(),

      sortOrder:
        parseSortOrder_(
          row[columns.sortOrder]
        )
    });
  }

  items.sort(function (a, b) {
    if (a.sortOrder !== b.sortOrder) {
      return a.sortOrder - b.sortOrder;
    }

    return a.colourName.localeCompare(
      b.colourName
    );
  });

  return items;
}


/**
 * Reads variant colour and display settings.
 *
 * Required headings:
 * Model, Variant, Display Name, Colour Name,
 * Custom Hex, Sort Order, Active
 */
function readVariantColours_(
  spreadsheet,
  model
) {
  const sheet =
    spreadsheet.getSheetByName(
      VARIANT_COLOUR_SHEET
    );

  if (!sheet) {
    return {};
  }

  const data =
    sheet.getDataRange().getDisplayValues();

  if (data.length < 2) {
    return {};
  }

  const headers =
    data[0].map(normaliseHeader_);

  const columns = {
    model: findColumn_(headers, ['model']),
    variant: findColumn_(headers, ['variant']),

    displayName: findColumn_(headers, [
      'display name',
      'variant name',
      'name'
    ]),

    colourName: findColumn_(headers, [
      'colour name',
      'color name',
      'colour',
      'color'
    ]),

    customHex: findColumn_(headers, [
      'custom hex',
      'hex code',
      'colour hex',
      'color hex'
    ]),

    sortOrder: findColumn_(headers, [
      'sort order',
      'sequence',
      'order'
    ]),

    active: findColumn_(headers, ['active'])
  };

  checkRequiredColumns_(
    columns,
    [
      'model',
      'variant',
      'displayName',
      'colourName',
      'customHex',
      'sortOrder',
      'active'
    ],
    VARIANT_COLOUR_SHEET
  );

  const selectedModel =
    normaliseValue_(model);

  const colourMap = {};

  for (
    let rowIndex = 1;
    rowIndex < data.length;
    rowIndex++
  ) {
    const row = data[rowIndex];

    if (
      normaliseValue_(row[columns.model]) !==
        selectedModel ||
      !isActive_(row[columns.active])
    ) {
      continue;
    }

    const variant =
      String(
        row[columns.variant] || ''
      ).trim();

    if (!variant) {
      continue;
    }

    const theme =
      getVariantColourTheme_(
        row[columns.colourName],
        row[columns.customHex]
      );

    colourMap[
      normaliseValue_(variant)
    ] = {
      displayName:
        String(
          row[columns.displayName] ||
          variant
        ).trim(),

      colourName:
        theme.colourName,

      accentColour:
        theme.accentColour,

      swatchColour:
        theme.swatchColour,

      backgroundColour:
        theme.backgroundColour,

      textColour:
        theme.textColour,

      borderColour:
        theme.borderColour,

      sortOrder:
        parseSortOrder_(
          row[columns.sortOrder]
        )
    };
  }

  return colourMap;
}


function getDefaultVariantColour_(
  variantCode
) {
  const theme =
    getVariantColourTheme_(
      'WHITE',
      ''
    );

  return {
    displayName:
      getVariantLabel_(variantCode),

    colourName:
      theme.colourName,

    accentColour:
      theme.accentColour,

    swatchColour:
      theme.swatchColour,

    backgroundColour:
      theme.backgroundColour,

    textColour:
      theme.textColour,

    borderColour:
      theme.borderColour
  };
}


function getVariantColourTheme_(
  colourName,
  customHex
) {
  const name =
    normaliseValue_(
      colourName || 'WHITE'
    );

  const custom =
    normaliseHexColour_(
      customHex
    );

  const themes = {
    WHITE: {
      colourName: 'WHITE',
      accentColour: '#94A3B8',
      swatchColour: '#FFFFFF',
      backgroundColour: '#FFFFFF',
      textColour: '#194776',
      borderColour: '#94A3B8'
    },

    GREEN: {
      colourName: 'GREEN',
      accentColour: '#22C55E',
      swatchColour: '#22C55E',
      backgroundColour: '#DCFCE7',
      textColour: '#166534',
      borderColour: '#22C55E'
    },

    BLUE: {
      colourName: 'BLUE',
      accentColour: '#3B82F6',
      swatchColour: '#3B82F6',
      backgroundColour: '#DBEAFE',
      textColour: '#1D4ED8',
      borderColour: '#3B82F6'
    }
  };

  const selected =
    themes[name] || themes.WHITE;

  /*
   * A custom hex may be used together with a normal colour name.
   * Example:
   * Colour Name = GREEN
   * Custom Hex  = #BADCB3
   *
   * The website will show "GREEN", not "CUSTOM",
   * while using the exact paper shade.
   */
  if (
    custom &&
    (
      name === 'GREEN' ||
      name === 'BLUE'
    )
  ) {
    return {
      colourName: name,
      accentColour: custom,
      swatchColour: custom,
      backgroundColour:
        selected.backgroundColour,
      textColour:
        selected.textColour,
      borderColour: custom
    };
  }

  if (
    name === 'CUSTOM' &&
    custom
  ) {
    return {
      colourName: 'CUSTOM',
      accentColour: custom,
      swatchColour: custom,
      backgroundColour: '#F8FAFC',
      textColour: '#194776',
      borderColour: custom
    };
  }

  return selected;
}


function normaliseHexColour_(value) {
  const text =
    String(value || '').trim();

  if (!text) {
    return '';
  }

  const withHash =
    text.charAt(0) === '#'
      ? text
      : '#' + text;

  return /^#[0-9A-Fa-f]{6}$/.test(
    withHash
  )
    ? withHash.toUpperCase()
    : '';
}


function getVariantLabel_(variantCode) {
  const value =
    String(variantCode || '').trim();

  const labels = {
    'AI-MY(LV)': 'AI-MY (LV)'
  };

  return labels[
    normaliseValue_(value)
  ] || value;
}


function getDefaultVariantSummary_(
  model,
  variantCode
) {
  if (normaliseValue_(model) !== 'S70') {
    return 'Select this variant to continue.';
  }

  const summaries = {
    MC: 'Standard specification',
    MC2: 'Adds 2 new brackets',
    'AI-MY(LV)': 'Adds 23 new parts',
    FL: 'Short bumper specification'
  };

  return summaries[
    normaliseValue_(variantCode)
  ] || 'Select this variant to continue.';
}


function getVariantSortOrder_(
  model,
  variantCode
) {
  const selectedModel =
    normaliseValue_(model);

  const value =
    normaliseValue_(variantCode);

  const modelOrders = {
    X50: {
      FL: 1,
      A5: 2,
      A3: 3,
      '15D': 4,
      A6: 5
    },

    X70: {
      MC3: 1
    },

    S70: {
      MC: 1,
      MC2: 2,
      'AI-MY(LV)': 3,
      FL: 4
    }
  };

  const order =
    modelOrders[selectedModel] || {};

  return order[value] || 999;
}




/**
 * Loads the active grades for a selected model and variant.
 * Grade details are read from GRADE_SELECTION when available.
 */
function getGradePage(model, variant) {
  if (!model || !variant) {
    throw new Error(
      'Please select model and variant.'
    );
  }

  const spreadsheet =
    SpreadsheetApp.openById(SPREADSHEET_ID);

  const gradeCodes =
    findActiveGradesForVariant_(
      spreadsheet,
      model,
      variant
    );

  if (gradeCodes.length === 0) {
    return {
      found: false,
      message:
        'No active grades were found for ' +
        model +
        ' → ' +
        variant +
        '.',
      grades: []
    };
  }

  const gradeDetails =
    readGradeSelection_(
      spreadsheet,
      model,
      variant
    );

  const detailMap = {};

  gradeDetails.forEach(function (item) {
    detailMap[
      normaliseValue_(item.code)
    ] = item;
  });

  const grades =
    gradeCodes
      .map(function (gradeCode) {
        const key =
          normaliseValue_(gradeCode);

        const detail =
          detailMap[key] || {};

        const underbodyItems =
          readOverviewDatabase_(
            spreadsheet,
            model,
            variant,
            gradeCode,
            'ALL'
          );

        const underbody =
          underbodyItems.length > 0
            ? underbodyItems[0]
            : {};

        return {
          code: gradeCode,
          displayName:
            detail.displayName ||
            gradeCode,
          description:
            detail.description ||
            (
              key === 'STANDARD'
                ? 'Standard grade'
                : 'Select this grade to continue.'
            ),
          imageUrl:
            detail.imageUrl ||
            underbody.imageUrl ||
            '',
          originalLink:
            detail.originalLink ||
            underbody.originalLink ||
            '',
          sortOrder:
            detail.sortOrder !== undefined
              ? detail.sortOrder
              : getGradeSortOrder_(gradeCode)
        };
      })
      .sort(function (a, b) {
        if (a.sortOrder !== b.sortOrder) {
          return a.sortOrder - b.sortOrder;
        }

        return a.displayName.localeCompare(
          b.displayName
        );
      });

  const comparisonData =
    readGradeComparison_(
      spreadsheet,
      model,
      variant,
      grades.map(function (grade) {
        return grade.code;
      })
    );

  const selectionType =
    detectGradeSelectionType_(
      grades.map(function (grade) {
        return grade.code;
      })
    );

  return {
    found: true,
    selection: {
      model: model,
      variant: variant
    },
    selectionType: selectionType,
    selectionLabel:
      selectionType === 'market'
        ? 'Market'
        : 'Grade',
    selectionHeading:
      selectionType === 'market'
        ? 'Choose Market'
        : 'Choose Grade',
    selectionDescription:
      selectionType === 'market'
        ? 'Select Vietnam or Mexico to view the correct market specification.'
        : 'Select a grade to view its underbody and modules.',
    comparisonTitle:
      selectionType === 'market'
        ? (
          'What Is Different Between ' +
          model +
          ' ' +
          variant +
          ' Vietnam and Mexico?'
        )
        : (
          'What Is Different About the ' +
          model +
          ' ' +
          variant +
          ' Grades?'
        ),
    comparisonDescription:
      selectionType === 'market'
        ? 'The market-specific parts are displayed side by side for easier comparison.'
        : 'The different parts are displayed horizontally for easier comparison.',
    grades: grades,
    comparisonColumns:
      comparisonData.columns,
    comparisons:
      comparisonData.rows
  };
}


/**
 * Finds all active grades for one model and variant
 * from FRONT_PAGE.
 */
function findActiveGradesForVariant_(
  spreadsheet,
  model,
  variant
) {
  const sheet =
    spreadsheet.getSheetByName(
      FRONT_PAGE_SHEET
    );

  if (!sheet) {
    throw new Error(
      'The FRONT_PAGE sheet was not found.'
    );
  }

  const data =
    sheet.getDataRange().getDisplayValues();

  if (data.length < 2) {
    return [];
  }

  const headers =
    data[0].map(normaliseHeader_);

  const columns = {
    model: findColumn_(headers, ['model']),
    variant: findColumn_(headers, ['variant']),
    grade: findColumn_(headers, [
      'grade',
      'variant grade'
    ]),
    active: findColumn_(headers, ['active'])
  };

  checkRequiredColumns_(
    columns,
    ['model', 'variant', 'grade', 'active'],
    FRONT_PAGE_SHEET
  );

  const selectedModel =
    normaliseValue_(model);

  const selectedVariant =
    normaliseValue_(variant);

  const gradeMap = {};

  for (
    let rowIndex = 1;
    rowIndex < data.length;
    rowIndex++
  ) {
    const row = data[rowIndex];

    if (
      normaliseValue_(row[columns.model]) !==
        selectedModel ||
      normaliseValue_(row[columns.variant]) !==
        selectedVariant ||
      !isActive_(row[columns.active])
    ) {
      continue;
    }

    const grade =
      String(
        row[columns.grade] || ''
      ).trim();

    if (grade) {
      gradeMap[
        normaliseValue_(grade)
      ] = grade;
    }
  }

  return Object.keys(gradeMap)
    .map(function (key) {
      return gradeMap[key];
    })
    .sort(function (a, b) {
      const orderA =
        getGradeSortOrder_(a);

      const orderB =
        getGradeSortOrder_(b);

      if (orderA !== orderB) {
        return orderA - orderB;
      }

      return a.localeCompare(b);
    });
}


/**
 * Reads optional grade card details from GRADE_SELECTION.
 * Required headings:
 * Model, Variant, Grade, Image Link, Display Name,
 * Description, Sort Order, Active
 */
function readGradeSelection_(
  spreadsheet,
  model,
  variant
) {
  const sheet =
    spreadsheet.getSheetByName(
      GRADE_SELECTION_SHEET
    );

  if (!sheet) {
    return [];
  }

  const data =
    sheet.getDataRange().getDisplayValues();

  if (data.length < 2) {
    return [];
  }

  const headers =
    data[0].map(normaliseHeader_);

  const columns = {
    model: findColumn_(headers, ['model']),
    variant: findColumn_(headers, ['variant']),
    grade: findColumn_(headers, ['grade']),

    imageLink: findColumn_(headers, [
      'image link',
      'image url',
      'picture link',
      'picture url',
      'image'
    ]),

    displayName: findColumn_(headers, [
      'display name',
      'grade name',
      'name',
      'title'
    ]),

    description: findColumn_(headers, [
      'description',
      'details',
      'note',
      'notes'
    ]),

    sortOrder: findColumn_(headers, [
      'sort order',
      'sequence',
      'order'
    ]),

    active: findColumn_(headers, ['active'])
  };

  checkRequiredColumns_(
    columns,
    [
      'model',
      'variant',
      'grade',
      'imageLink',
      'displayName',
      'description',
      'sortOrder',
      'active'
    ],
    GRADE_SELECTION_SHEET
  );

  const selectedModel =
    normaliseValue_(model);

  const selectedVariant =
    normaliseValue_(variant);

  const items = [];

  for (
    let rowIndex = 1;
    rowIndex < data.length;
    rowIndex++
  ) {
    const row = data[rowIndex];

    if (
      normaliseValue_(row[columns.model]) !==
        selectedModel ||
      normaliseValue_(row[columns.variant]) !==
        selectedVariant ||
      !isActive_(row[columns.active])
    ) {
      continue;
    }

    const imageLink =
      String(
        row[columns.imageLink] || ''
      ).trim();

    items.push({
      code:
        String(
          row[columns.grade] || ''
        ).trim(),

      displayName:
        String(
          row[columns.displayName] ||
          row[columns.grade] || ''
        ).trim(),

      description:
        String(
          row[columns.description] || ''
        ).trim(),

      imageUrl:
        convertToDisplayImageUrl_(
          imageLink
        ),

      originalLink:
        imageLink,

      sortOrder:
        parseSortOrder_(
          row[columns.sortOrder]
        )
    });
  }

  return items;
}



/**
 * Reads the horizontal grade-comparison table.
 *
 * Sheet name:
 * GRADE_COMPARISON
 *
 * Required headings:
 * Model, Variant, Part Name,
 * Executive Part Number, Executive Image Link,
 * Flagship Part Number, Flagship Image Link,
 * Premium Part Number, Premium Image Link,
 * Sort Order, Active
 */
function readGradeComparison_(
  spreadsheet,
  model,
  variant,
  gradeCodes
) {
  const emptyResult = {
    columns: [],
    rows: []
  };

  const sheet =
    spreadsheet.getSheetByName(
      GRADE_COMPARISON_SHEET
    );

  if (!sheet) {
    return emptyResult;
  }

  const data =
    sheet.getDataRange().getDisplayValues();

  if (data.length < 2) {
    return emptyResult;
  }

  const headers =
    data[0].map(normaliseHeader_);

  const columns = {
    model: findColumn_(headers, ['model']),
    variant: findColumn_(headers, ['variant']),

    partName: findColumn_(headers, [
      'part name',
      'comparison part',
      'comparison item',
      'name'
    ]),

    sortOrder: findColumn_(headers, [
      'sort order',
      'sequence',
      'order'
    ]),

    active: findColumn_(headers, ['active'])
  };

  checkRequiredColumns_(
    columns,
    [
      'model',
      'variant',
      'partName',
      'sortOrder',
      'active'
    ],
    GRADE_COMPARISON_SHEET
  );

  const comparisonColumns = [];

  (gradeCodes || []).forEach(function (
    gradeCode
  ) {
    const gradeText =
      String(gradeCode || '').trim();

    if (!gradeText) {
      return;
    }

    const partNameColumn =
      findColumn_(headers, [
        gradeText + ' part name',
        gradeText + ' component name',
        gradeText + ' name'
      ]);

    const partNumberColumn =
      findColumn_(headers, [
        gradeText + ' part number',
        gradeText + ' part no',
        gradeText + ' part no.'
      ]);

    const imageLinkColumn =
      findColumn_(headers, [
        gradeText + ' image link',
        gradeText + ' picture link',
        gradeText + ' image',
        gradeText + ' picture'
      ]);

    /*
     * A comparison column is included when at least one
     * market/grade-specific heading exists.
     */
    if (
      partNameColumn === -1 &&
      partNumberColumn === -1 &&
      imageLinkColumn === -1
    ) {
      return;
    }

    comparisonColumns.push({
      code: gradeText,
      label: toDisplayLabel_(gradeText),
      partNameColumn: partNameColumn,
      partNumberColumn: partNumberColumn,
      imageLinkColumn: imageLinkColumn
    });
  });

  if (comparisonColumns.length === 0) {
    return emptyResult;
  }

  const selectedModel =
    normaliseValue_(model);

  const selectedVariant =
    normaliseValue_(variant);

  const comparisons = [];

  for (
    let rowIndex = 1;
    rowIndex < data.length;
    rowIndex++
  ) {
    const row = data[rowIndex];

    if (
      normaliseValue_(row[columns.model]) !==
        selectedModel ||
      normaliseValue_(row[columns.variant]) !==
        selectedVariant ||
      !isActive_(row[columns.active])
    ) {
      continue;
    }

    const commonPartName =
      String(
        row[columns.partName] || ''
      ).trim();

    const items = [];

    comparisonColumns.forEach(function (
      comparisonColumn
    ) {
      const specificPartName =
        comparisonColumn.partNameColumn !== -1
          ? String(
              row[
                comparisonColumn.partNameColumn
              ] || ''
            ).trim()
          : '';

      const partNumber =
        comparisonColumn.partNumberColumn !== -1
          ? String(
              row[
                comparisonColumn.partNumberColumn
              ] || ''
            ).trim()
          : '';

      const imageLink =
        comparisonColumn.imageLinkColumn !== -1
          ? String(
              row[
                comparisonColumn.imageLinkColumn
              ] || ''
            ).trim()
          : '';

      items.push({
        code:
          comparisonColumn.code,

        partName:
          specificPartName ||
          commonPartName,

        partNumber:
          partNumber,

        imageUrl:
          convertToDisplayImageUrl_(
            imageLink
          ),

        originalLink:
          imageLink
      });
    });

    comparisons.push({
      partName:
        commonPartName ||
        'Different Part',

      items:
        items,

      sortOrder:
        parseSortOrder_(
          row[columns.sortOrder]
        )
    });
  }

  comparisons.sort(function (a, b) {
    if (a.sortOrder !== b.sortOrder) {
      return a.sortOrder - b.sortOrder;
    }

    return a.partName.localeCompare(
      b.partName
    );
  });

  return {
    columns:
      comparisonColumns.map(function (
        comparisonColumn
      ) {
        return {
          code:
            comparisonColumn.code,
          label:
            comparisonColumn.label
        };
      }),

    rows:
      comparisons
  };
}


function detectGradeSelectionType_(
  gradeCodes
) {
  const marketCodes = {
    VIETNAM: true,
    MEXICO: true
  };

  const normalisedCodes =
    (gradeCodes || [])
      .map(normaliseValue_)
      .filter(function (code) {
        return code !== '';
      });

  if (
    normalisedCodes.length > 0 &&
    normalisedCodes.every(function (code) {
      return marketCodes[code] === true;
    })
  ) {
    return 'market';
  }

  return 'grade';
}


function toDisplayLabel_(value) {
  const text =
    String(value || '')
      .trim()
      .toLowerCase();

  if (!text) {
    return '';
  }

  return text
    .split(/\s+/)
    .map(function (word) {
      return (
        word.charAt(0).toUpperCase() +
        word.slice(1)
      );
    })
    .join(' ');
}


function getGradeSortOrder_(grade) {
  const order = {
    STANDARD: 0,
    EXECUTIVE: 1,
    PREMIUM: 2,
    FLAGSHIP: 3,
    VIETNAM: 1,
    MEXICO: 2
  };

  return order[
    normaliseValue_(grade)
  ] !== undefined
    ? order[normaliseValue_(grade)]
    : 999;
}

/**
 * Loads the full underbody picture and the active modules
 * for one model and variant.
 */
function getVariantSelection(model, variant, grade) {
  if (!model || !variant || !grade) {
    throw new Error(
      'Please select model, variant and grade.'
    );
  }

  const spreadsheet =
    SpreadsheetApp.openById(SPREADSHEET_ID);

  const routes =
    findVariantRoutes_(
      spreadsheet,
      model,
      variant,
      grade
    );

  if (routes.length === 0) {
    return {
      found: false,
      message:
        'No active FRONT_PAGE record was found for ' +
        model +
        ' → ' +
        variant +
        ' → ' +
        grade +
        '.',
      underbody: null,
      modules: []
    };
  }

  let underbodyItems =
    readOverviewDatabase_(
      spreadsheet,
      model,
      variant,
      grade,
      'ALL'
    );

  if (underbodyItems.length === 0) {
    underbodyItems =
      readOverviewDatabase_(
        spreadsheet,
        model,
        variant,
        grade,
        'UNDERBODY'
      );
  }

  const moduleMap = {};

  routes.forEach(function (route) {
    const moduleCode =
      normaliseValue_(route.module);

    if (!moduleCode) {
      return;
    }

    moduleMap[moduleCode] = {
      code: moduleCode,
      label: getModuleLabel_(moduleCode)
    };
  });

  const moduleOrder = {
    ER: 1,
    FF: 2,
    RF: 3
  };

  const modules =
    Object.keys(moduleMap)
      .map(function (key) {
        return moduleMap[key];
      })
      .sort(function (a, b) {
        const orderA =
          moduleOrder[a.code] || 999;

        const orderB =
          moduleOrder[b.code] || 999;

        if (orderA !== orderB) {
          return orderA - orderB;
        }

        return a.code.localeCompare(b.code);
      });

  return {
    found: true,

    selection: {
      model: model,
      variant: variant,
      grade: grade
    },

    underbody:
      underbodyItems.length > 0
        ? underbodyItems[0]
        : null,

    modules:
      modules
  };
}


/**
 * Returns all active FRONT_PAGE routes for one
 * model and variant.
 */
function findVariantRoutes_(
  spreadsheet,
  model,
  variant,
  grade
) {
  const sheet =
    spreadsheet.getSheetByName(
      FRONT_PAGE_SHEET
    );

  if (!sheet) {
    throw new Error(
      'The FRONT_PAGE sheet was not found.'
    );
  }

  const data =
    sheet.getDataRange().getDisplayValues();

  if (data.length < 2) {
    throw new Error(
      'The FRONT_PAGE sheet is empty.'
    );
  }

  const headers =
    data[0].map(normaliseHeader_);

  const columns = {
    model: findColumn_(headers, ['model']),
    variant: findColumn_(headers, ['variant']),
    grade: findColumn_(headers, [
      'grade',
      'variant grade'
    ]),
    module: findColumn_(headers, ['module']),
    active: findColumn_(headers, ['active'])
  };

  checkRequiredColumns_(
    columns,
    [
      'model',
      'variant',
      'grade',
      'module',
      'active'
    ],
    FRONT_PAGE_SHEET
  );

  const selectedModel =
    normaliseValue_(model);

  const selectedVariant =
    normaliseValue_(variant);

  const selectedGrade =
    normaliseValue_(grade);

  const routes = [];

  for (
    let rowIndex = 1;
    rowIndex < data.length;
    rowIndex++
  ) {
    const row = data[rowIndex];

    const matches =
      normaliseValue_(
        row[columns.model]
      ) === selectedModel &&
      normaliseValue_(
        row[columns.variant]
      ) === selectedVariant &&
      normaliseValue_(
        row[columns.grade]
      ) === selectedGrade;

    if (
      !matches ||
      !isActive_(row[columns.active])
    ) {
      continue;
    }

    routes.push({
      module:
        String(
          row[columns.module] || ''
        ).trim()
    });
  }

  return routes;
}


function getModuleLabel_(moduleCode) {
  const labels = {
    ER: 'FRONT STRUCTURE ASSY (ER)',
    FF: 'FRONT FLOOR ASSY (FF)',
    RF: 'REAR FLOOR ASSY (RF)'
  };

  return (
    labels[moduleCode] ||
    moduleCode
  );
}


/**
 * Loads the matching overview and actual-part pictures
 * from OVERVIEW_DATABASE.
 */
function getParts(model, variant, grade, module) {
  if (!model || !variant || !grade || !module) {
    throw new Error(
      'Please select model, variant, grade and module.'
    );
  }

  const spreadsheet =
    SpreadsheetApp.openById(SPREADSHEET_ID);

  const route =
    findFrontPageRoute_(
      spreadsheet,
      model,
      variant,
      grade,
      module
    );

  if (!route) {
    return {
      found: false,
      message:
        'No active FRONT_PAGE record was found for ' +
        model +
        ' → ' +
        variant +
        ' → ' +
        grade +
        ' → ' +
        module +
        '.',
      assemblies: []
    };
  }

  const overviewItems =
    readOverviewDatabase_(
      spreadsheet,
      model,
      variant,
      grade,
      module
    );

  const taggingExamples =
    readAssemblyTaggingExamples_(
      spreadsheet,
      model,
      variant,
      module
    );

  /*
   * Get the paper colour / paper code that should be used
   * when printing Production Line and SCM tagging labels.
   * This is linked dynamically:
   * VARIANT_COLOUR -> PAPER_COLOUR_INFO
   */
  const tagPaperRequirement =
    getTagPaperRequirement_(
      spreadsheet,
      model,
      variant
    );

  const assemblyMap = {};

  overviewItems.forEach(function (item) {
    getAssembly_(
      assemblyMap,
      item.assemblyName
    ).overview.push(item);
  });

  const assemblies =
    Object.keys(assemblyMap)
      .map(function (key) {
        const assembly =
          assemblyMap[key];

        assembly.overview.sort(sortItems_);

        /*
         * Store the lowest picture Sort Order for this
         * assembly. This controls the order of the
         * assembly sections on the Results page.
         */
        assembly.sortOrder =
          assembly.overview.length > 0
            ? assembly.overview[0].sortOrder
            : 999999;

        return assembly;
      })
      .sort(function (a, b) {
        /*
         * Sort assembly sections by the spreadsheet's
         * Sort Order instead of alphabetically.
         */
        if (a.sortOrder !== b.sortOrder) {
          return a.sortOrder - b.sortOrder;
        }

        return a.assemblyName.localeCompare(
          b.assemblyName
        );
      });

  if (assemblies.length === 0) {
    return {
      found: false,
      message:
        'No overview information was found for ' +
        model +
        ' → ' +
        variant +
        ' → ' +
        grade +
        ' → ' +
        module +
        '.',
      assemblies: []
    };
  }

  const totalPictures =
    assemblies.reduce(function (
      total,
      assembly
    ) {
      return total + assembly.overview.length;
    }, 0);

  return {
    found: true,

    selection: {
      model: model,
      variant: variant,
      grade: grade,
      module: module
    },

    assemblyCount:
      assemblies.length,

    totalPictures:
      totalPictures,

    taggingCount:
      taggingExamples.length,

    taggingExamples:
      taggingExamples,

    tagPaperRequirement:
      tagPaperRequirement,

    assemblies:
      assemblies
  };
}



/**
 * Builds the printing-paper requirement for the selected variant.
 *
 * VARIANT_COLOUR supplies the required colour for each variant.
 * PAPER_COLOUR_INFO supplies Brand, GSM and Colour Code for that colour.
 */
function getTagPaperRequirement_(
  spreadsheet,
  model,
  variant
) {
  const colourMap =
    readVariantColours_(
      spreadsheet,
      model
    );

  const variantKey =
    normaliseValue_(variant);

  const colourSettings =
    colourMap[variantKey] ||
    getDefaultVariantColour_(
      variant
    );

  const colourName =
    String(
      colourSettings.colourName ||
      'WHITE'
    ).trim();

  const paperColours =
    readPaperColourInfo_(
      spreadsheet
    );

  let paperInfo = null;

  for (
    let index = 0;
    index < paperColours.length;
    index++
  ) {
    if (
      normaliseValue_(
        paperColours[index].colourName
      ) === normaliseValue_(colourName)
    ) {
      paperInfo =
        paperColours[index];

      break;
    }
  }

  return {
    found:
      Boolean(paperInfo),

    model:
      String(model || '').trim(),

    variant:
      String(variant || '').trim(),

    variantLabel:
      String(
        colourSettings.displayName ||
        getVariantLabel_(variant) ||
        variant ||
        ''
      ).trim(),

    colourName:
      colourName,

    brand:
      paperInfo
        ? String(
            paperInfo.brand || ''
          ).trim()
        : '',

    gsm:
      paperInfo
        ? String(
            paperInfo.gsm || ''
          ).trim()
        : '',

    colourCode:
      paperInfo
        ? String(
            paperInfo.colourCode || ''
          ).trim()
        : '',

    imageUrl:
      paperInfo
        ? String(
            paperInfo.imageUrl || ''
          ).trim()
        : '',

    originalLink:
      paperInfo
        ? String(
            paperInfo.originalLink || ''
          ).trim()
        : '',

    accentColour:
      colourSettings.accentColour ||
      '#94A3B8',

    swatchColour:
      colourSettings.swatchColour ||
      colourSettings.accentColour ||
      '#FFFFFF',

    backgroundColour:
      colourSettings.backgroundColour ||
      '#F8FAFC',

    textColour:
      colourSettings.textColour ||
      '#194776',

    borderColour:
      colourSettings.borderColour ||
      '#94A3B8'
  };
}


/**
 * Reads Production Line and Supply Chain Management
 * tagging examples for the selected variant and module.
 *
 * Sheet name:
 * VARIANT_TAGGING
 *
 * Required headings:
 * Model, Variant, Module, Tagging Type,
 * Image Link, Display Name, Description,
 * Sort Order, Active
 */
function readAssemblyTaggingExamples_(
  spreadsheet,
  model,
  variant,
  module
) {
  const sheet =
    spreadsheet.getSheetByName(
      VARIANT_TAGGING_SHEET
    );

  if (!sheet) {
    return [];
  }

  const data =
    sheet.getDataRange().getDisplayValues();

  if (data.length < 2) {
    return [];
  }

  const headers =
    data[0].map(normaliseHeader_);

  const columns = {
    model: findColumn_(headers, [
      'model'
    ]),

    variant: findColumn_(headers, [
      'variant'
    ]),

    module: findColumn_(headers, [
      'module',
      'module code'
    ]),

    taggingType: findColumn_(headers, [
      'tagging type',
      'tag type',
      'department',
      'type'
    ]),

    imageLink: findColumn_(headers, [
      'image link',
      'image url',
      'picture link',
      'picture url',
      'image'
    ]),

    displayName: findColumn_(headers, [
      'display name',
      'tagging name',
      'title',
      'name'
    ]),

    description: findColumn_(headers, [
      'description',
      'details',
      'note',
      'notes'
    ]),

    sortOrder: findColumn_(headers, [
      'sort order',
      'sequence',
      'order'
    ]),

    active: findColumn_(headers, [
      'active'
    ])
  };

  checkRequiredColumns_(
    columns,
    [
      'model',
      'variant',
      'module',
      'taggingType',
      'imageLink',
      'displayName',
      'description',
      'sortOrder',
      'active'
    ],
    VARIANT_TAGGING_SHEET
  );

  const selectedModel =
    normaliseValue_(model);

  const selectedVariant =
    normaliseValue_(variant);

  const selectedModule =
    normaliseValue_(module);

  const examples = [];

  for (
    let rowIndex = 1;
    rowIndex < data.length;
    rowIndex++
  ) {
    const row = data[rowIndex];

    if (
      normaliseValue_(row[columns.model]) !==
        selectedModel ||
      normaliseValue_(row[columns.variant]) !==
        selectedVariant ||
      normaliseValue_(row[columns.module]) !==
        selectedModule ||
      !isActive_(row[columns.active])
    ) {
      continue;
    }

    const taggingType =
      String(
        row[columns.taggingType] || ''
      ).trim();

    if (!taggingType) {
      continue;
    }

    const imageLink =
      String(
        row[columns.imageLink] || ''
      ).trim();

    examples.push({
      taggingType:
        taggingType,

      displayName:
        String(
          row[columns.displayName] ||
          taggingType
        ).trim(),

      description:
        String(
          row[columns.description] || ''
        ).trim(),

      imageUrl:
        convertToDisplayImageUrl_(
          imageLink
        ),

      originalLink:
        imageLink,

      sortOrder:
        parseSortOrder_(
          row[columns.sortOrder]
        ),

      sheetRow:
        rowIndex + 1
    });
  }

  examples.sort(function (a, b) {
    if (a.sortOrder !== b.sortOrder) {
      return a.sortOrder - b.sortOrder;
    }

    return a.displayName.localeCompare(
      b.displayName
    );
  });

  return examples;
}


/**
 * Finds the matching row in FRONT_PAGE.
 */
function findFrontPageRoute_(
  spreadsheet,
  model,
  variant,
  grade,
  module
) {
  const sheet =
    spreadsheet.getSheetByName(
      FRONT_PAGE_SHEET
    );

  if (!sheet) {
    throw new Error(
      'The FRONT_PAGE sheet was not found.'
    );
  }

  const data =
    sheet.getDataRange().getDisplayValues();

  if (data.length < 2) {
    throw new Error(
      'The FRONT_PAGE sheet is empty.'
    );
  }

  const headers =
    data[0].map(normaliseHeader_);

  const columns = {
    model: findColumn_(headers, ['model']),
    variant: findColumn_(headers, ['variant']),
    grade: findColumn_(headers, [
      'grade',
      'variant grade'
    ]),
    module: findColumn_(headers, ['module']),
    active: findColumn_(headers, ['active'])
  };

  checkRequiredColumns_(
    columns,
    [
      'model',
      'variant',
      'grade',
      'module',
      'active'
    ],
    FRONT_PAGE_SHEET
  );

  const selectedModel =
    normaliseValue_(model);

  const selectedVariant =
    normaliseValue_(variant);

  const selectedGrade =
    normaliseValue_(grade);

  const selectedModule =
    normaliseValue_(module);

  for (
    let rowIndex = 1;
    rowIndex < data.length;
    rowIndex++
  ) {
    const row = data[rowIndex];

    const matches =
      normaliseValue_(
        row[columns.model]
      ) === selectedModel &&
      normaliseValue_(
        row[columns.variant]
      ) === selectedVariant &&
      normaliseValue_(
        row[columns.grade]
      ) === selectedGrade &&
      normaliseValue_(
        row[columns.module]
      ) === selectedModule;

    if (!matches) {
      continue;
    }

    if (!isActive_(row[columns.active])) {
      return null;
    }

    return {
      model:
        String(
          row[columns.model] || ''
        ).trim(),

      variant:
        String(
          row[columns.variant] || ''
        ).trim(),

      grade:
        String(
          row[columns.grade] || ''
        ).trim(),

      module:
        String(
          row[columns.module] || ''
        ).trim()
    };
  }

  return null;
}


/**
 * Reads one combined OVERVIEW_DATABASE sheet.
 * Required headings:
 * Module, Image Link, Picture Name,
 * Assembly, Sort Order, Show Legend
 */
function readOverviewDatabase_(
  spreadsheet,
  model,
  variant,
  grade,
  module
) {
  const sheet =
    spreadsheet.getSheetByName(
      OVERVIEW_DATABASE_SHEET
    );

  if (!sheet) {
    throw new Error(
      'The OVERVIEW_DATABASE sheet was not found.'
    );
  }

  const data =
    sheet.getDataRange().getDisplayValues();

  if (data.length < 2) {
    return [];
  }

  const headers =
    data[0].map(normaliseHeader_);

  const columns = {
    model: findColumn_(headers, ['model']),
    variant: findColumn_(headers, ['variant']),
    grade: findColumn_(headers, ['grade']),
    module: findColumn_(headers, ['module']),

    imageLink: findColumn_(headers, [
      'image link',
      'image url',
      'picture link',
      'picture url',
      'image'
    ]),

    pictureName: findColumn_(headers, [
      'picture name',
      'name',
      'title'
    ]),

    assembly: findColumn_(headers, [
      'assembly',
      'assembly name'
    ]),

    sortOrder: findColumn_(headers, [
      'sort order',
      'sequence',
      'order'
    ]),

    showLegend: findColumn_(headers, [
      'show legend',
      'showlegend',
      'legend'
    ]),

    active: findColumn_(headers, [
      'active'
    ])
  };

  checkRequiredColumns_(
    columns,
    [
      'model',
      'variant',
      'grade',
      'module',
      'imageLink',
      'pictureName',
      'assembly',
      'sortOrder',
      'showLegend'
    ],
    OVERVIEW_DATABASE_SHEET
  );

  const selectedModel =
    normaliseValue_(model);

  const selectedVariant =
    normaliseValue_(variant);

  const selectedGrade =
    normaliseValue_(grade);

  const selectedModule =
    normaliseValue_(module);

  const items = [];

  for (
    let rowIndex = 1;
    rowIndex < data.length;
    rowIndex++
  ) {
    const row = data[rowIndex];

    if (
      normaliseValue_(
        row[columns.model]
      ) !== selectedModel ||
      normaliseValue_(
        row[columns.variant]
      ) !== selectedVariant ||
      normaliseValue_(
        row[columns.grade]
      ) !== selectedGrade ||
      normaliseValue_(
        row[columns.module]
      ) !== selectedModule
    ) {
      continue;
    }

    if (
      columns.active !== -1 &&
      !isActive_(row[columns.active])
    ) {
      continue;
    }

    const pictureName =
      String(
        row[columns.pictureName] || ''
      ).trim();

    const assemblyName =
      String(
        row[columns.assembly] ||
        pictureName ||
        'Assembly'
      ).trim();

    const imageLink =
      String(
        row[columns.imageLink] || ''
      ).trim();

    items.push({
      pictureName:
        pictureName,

      partNumber:
        '',

      assemblyName:
        assemblyName,

      imageUrl:
        convertToDisplayImageUrl_(
          imageLink
        ),

      originalLink:
        imageLink,

      sortOrder:
        parseSortOrder_(
          row[columns.sortOrder]
        ),

      /*
       * Controls whether the red-marking legend is shown
       * for this exact OVERVIEW_DATABASE picture.
       * Yes / Y / TRUE / 1 / ACTIVE = true
       * No / blank = false
       */
      showLegend:
        isActive_(
          row[columns.showLegend]
        ),

      sheetRow:
        rowIndex + 1
    });
  }

  items.sort(sortItems_);

  return items;
}


function readOverviewSheet_(
  spreadsheet,
  sheetName,
  module
) {
  if (!sheetName) {
    return [];
  }

  const sheet =
    spreadsheet.getSheetByName(sheetName);

  if (!sheet) {
    throw new Error(
      'Overview sheet "' +
      sheetName +
      '" was not found.'
    );
  }

  const data =
    sheet.getDataRange().getDisplayValues();

  if (data.length < 2) {
    return [];
  }

  const headers =
    data[0].map(normaliseHeader_);

  const columns = {
    module: findColumn_(headers, ['module']),

    imageLink: findColumn_(headers, [
      'image link',
      'image url',
      'picture link',
      'picture url',
      'image'
    ]),

    pictureName: findColumn_(headers, [
      'picture name',
      'name',
      'title'
    ]),

    assembly: findColumn_(headers, [
      'assembly',
      'assembly name'
    ]),

    sortOrder: findColumn_(headers, [
      'sort order',
      'sequence',
      'order'
    ])
  };

  checkRequiredColumns_(
    columns,
    [
      'module',
      'imageLink',
      'pictureName',
      'assembly',
      'sortOrder'
    ],
    sheetName
  );

  const selectedModule =
    normaliseValue_(module);

  const items = [];

  for (
    let rowIndex = 1;
    rowIndex < data.length;
    rowIndex++
  ) {
    const row = data[rowIndex];

    if (
      normaliseValue_(
        row[columns.module]
      ) !== selectedModule
    ) {
      continue;
    }

    const pictureName =
      String(
        row[columns.pictureName] || ''
      ).trim();

    const assemblyName =
      String(
        row[columns.assembly] ||
        pictureName ||
        'Assembly'
      ).trim();

    const imageLink =
      String(
        row[columns.imageLink] || ''
      ).trim();

    items.push({
      pictureName: pictureName,
      partNumber: '',
      assemblyName: assemblyName,

      imageUrl:
        convertToDisplayImageUrl_(
          imageLink
        ),

      originalLink:
        imageLink,

      sortOrder:
        parseSortOrder_(
          row[columns.sortOrder]
        ),

      sheetRow:
        rowIndex + 1
    });
  }

  return items;
}


/**
 * Reads one MPL or APL sheet.
 * Required headings:
 * Module, Image Link, Part Name,
 * Part Number, Assembly, Sort Order
 */
function readPartSheet_(
  spreadsheet,
  sheetName,
  module,
  listType
) {
  if (!sheetName) {
    return [];
  }

  const sheet =
    spreadsheet.getSheetByName(sheetName);

  if (!sheet) {
    throw new Error(
      listType +
      ' sheet "' +
      sheetName +
      '" was not found.'
    );
  }

  const data =
    sheet.getDataRange().getDisplayValues();

  if (data.length < 2) {
    return [];
  }

  const headers =
    data[0].map(normaliseHeader_);

  const columns = {
    module: findColumn_(headers, ['module']),

    imageLink: findColumn_(headers, [
      'image link',
      'image url',
      'picture link',
      'picture url',
      'image'
    ]),

    partName: findColumn_(headers, [
      'part name',
      'picture name',
      'component name',
      'name',
      'title'
    ]),

    partNumber: findColumn_(headers, [
      'part number',
      'part no',
      'part no.',
      'component number'
    ]),

    assembly: findColumn_(headers, [
      'assembly',
      'assembly name'
    ]),

    sortOrder: findColumn_(headers, [
      'sort order',
      'sequence',
      'order'
    ])
  };

  checkRequiredColumns_(
    columns,
    [
      'module',
      'imageLink',
      'partName',
      'partNumber',
      'assembly',
      'sortOrder'
    ],
    sheetName
  );

  const selectedModule =
    normaliseValue_(module);

  const items = [];

  for (
    let rowIndex = 1;
    rowIndex < data.length;
    rowIndex++
  ) {
    const row = data[rowIndex];

    if (
      normaliseValue_(
        row[columns.module]
      ) !== selectedModule
    ) {
      continue;
    }

    const pictureName =
      String(
        row[columns.partName] || ''
      ).trim();

    const assemblyName =
      String(
        row[columns.assembly] ||
        'Assembly'
      ).trim();

    const imageLink =
      String(
        row[columns.imageLink] || ''
      ).trim();

    items.push({
      pictureName: pictureName,

      partNumber:
        String(
          row[columns.partNumber] || ''
        ).trim(),

      assemblyName:
        assemblyName,

      imageUrl:
        convertToDisplayImageUrl_(
          imageLink
        ),

      originalLink:
        imageLink,

      sortOrder:
        parseSortOrder_(
          row[columns.sortOrder]
        ),

      sheetRow:
        rowIndex + 1
    });
  }

  return items;
}


function getAssembly_(
  assemblyMap,
  assemblyName
) {
  const safeName =
    String(assemblyName || 'Assembly').trim();

  const key =
    normaliseValue_(safeName);

  if (!assemblyMap[key]) {
    assemblyMap[key] = {
      assemblyName: safeName,
      overview: [],
      mpl: [],
      apl: [],
      other: []
    };
  }

  return assemblyMap[key];
}


function checkRequiredColumns_(
  columns,
  required,
  sheetName
) {
  const missing =
    required.filter(function (name) {
      return columns[name] === -1;
    });

  if (missing.length > 0) {
    throw new Error(
      'Missing heading(s) in sheet "' +
      sheetName +
      '": ' +
      missing.join(', ') +
      '.'
    );
  }
}


function parseSortOrder_(value) {
  const text =
    String(value || '').trim();

  if (text === '') {
    return 9999;
  }

  const number = Number(text);

  return isNaN(number)
    ? 9999
    : number;
}


function sortItems_(itemA, itemB) {
  if (
    itemA.sortOrder !==
    itemB.sortOrder
  ) {
    return (
      itemA.sortOrder -
      itemB.sortOrder
    );
  }

  return String(
    itemA.pictureName || ''
  ).localeCompare(
    String(
      itemB.pictureName || ''
    )
  );
}


function isActive_(value) {
  const activeValue =
    normaliseValue_(value);

  return [
    'YES',
    'Y',
    'TRUE',
    '1',
    'ACTIVE'
  ].indexOf(activeValue) !== -1;
}


function findColumn_(
  headers,
  possibleNames
) {
  for (
    let index = 0;
    index < possibleNames.length;
    index++
  ) {
    const position =
      headers.indexOf(
        normaliseHeader_(
          possibleNames[index]
        )
      );

    if (position !== -1) {
      return position;
    }
  }

  return -1;
}


function convertToDisplayImageUrl_(
  imageLink
) {
  const link =
    String(imageLink || '').trim();

  if (!link) {
    return '';
  }

  const fileId =
    extractDriveFileId_(link);

  if (!fileId) {
    return link;
  }

  return (
    'https://drive.google.com/thumbnail' +
    '?id=' +
    encodeURIComponent(fileId) +
    '&sz=w' +
    DRIVE_IMAGE_WIDTH
  );
}


function extractDriveFileId_(link) {
  const text =
    String(link || '').trim();

  const patterns = [
    /\/file\/d\/([a-zA-Z0-9_-]+)/,
    /[?&]id=([a-zA-Z0-9_-]+)/,
    /\/d\/([a-zA-Z0-9_-]+)/,
    /^([a-zA-Z0-9_-]{20,})$/
  ];

  for (
    let index = 0;
    index < patterns.length;
    index++
  ) {
    const match =
      text.match(patterns[index]);

    if (match && match[1]) {
      return match[1];
    }
  }

  return '';
}


function normaliseHeader_(value) {
  return String(value || '')
    .trim()
    .toLowerCase()
    .replace(/\s+/g, ' ');
}


function normaliseValue_(value) {
  return String(value || '')
    .trim()
    .toUpperCase();
}

/**
 * Includes a reusable HTML partial such as Theme.html.
 * This keeps the responsive UI design consistent across every page.
 */
function include(fileName) {
  return HtmlService
    .createHtmlOutputFromFile(fileName)
    .getContent();
}

