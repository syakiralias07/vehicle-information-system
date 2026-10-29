// The plant travels in each link/request, so browser tabs remain independent.
(function () {
  'use strict';
  window.visPlantUrl = function (value) {
    const url = new URL(value, window.location.href);
    if (window.VIS_PLANT && url.origin === location.origin) url.searchParams.set('plant', window.VIS_PLANT);
    return url.toString();
  };
  document.addEventListener('click', function (event) {
    const link = event.target.closest('a[href]');
    if (!link || link.hasAttribute('data-change-plant') || !window.VIS_PLANT) return;
    const url = new URL(link.href, location.href);
    if (url.origin === location.origin && /\/(?:index|VariantSelect|GradeSelect|ModuleSelect|Results|admin)\.php$/i.test(url.pathname)) link.href = window.visPlantUrl(url);
  }, true);
})();
