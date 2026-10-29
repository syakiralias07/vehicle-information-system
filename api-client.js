// Compatibility adapter: keeps the original HTML pages and their Apps Script call syntax.
// All data is requested from the selected PHP/JSON catalogue.
(function () {
  const allowed = new Set(['getHomePageSettings','getModelCards','getVariantPage','getGradePage','getVariantSelection','getParts']);
  window.google = window.google || {};
  window.google.script = window.google.script || {};
  window.google.script.run = new Proxy({}, {
    get(_target, key) {
      if (key === 'then') return undefined;
      const handler = { success: null, failure: null };
      const chain = new Proxy({}, {
        get(_unused, method) {
          if (method === 'withSuccessHandler') return fn => { handler.success = fn; return chain; };
          if (method === 'withFailureHandler') return fn => { handler.failure = fn; return chain; };
          if (!allowed.has(method)) return undefined;
          return (...args) => {
            fetch('api.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:method,args,plant:window.VIS_PLANT||'fif'})})
              .then(async r => {const body=await r.json();if(!r.ok)throw new Error(body.error||'API error');return body;})
              .then(result=>handler.success && handler.success(result))
              .catch(err=>handler.failure ? handler.failure(err) : console.error(err));
          };
        }
      });
      if (key === 'withSuccessHandler') return fn => { handler.success=fn; return chain; };
      if (key === 'withFailureHandler') return fn => { handler.failure=fn; return chain; };
    }
  });
})();
