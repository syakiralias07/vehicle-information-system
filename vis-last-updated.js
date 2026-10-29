(function(){
  document.addEventListener('DOMContentLoaded', async function(){
    const node=document.createElement('div');
    node.style.cssText='text-align:center;font:12px Arial,sans-serif;color:#61748c;padding:18px 12px 24px';
    node.textContent='Last updated: Not published yet';
    document.body.appendChild(node);
    try {const res=await fetch('api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'getLastUpdated',args:[],plant:window.VIS_PLANT||'fif'})});
      if(!res.ok)return;const data=await res.json();
      if(data.lastUpdated){const date=new Date(data.lastUpdated.replace(' ','T')+'Z');node.textContent='Last updated: '+new Intl.DateTimeFormat('en-MY',{dateStyle:'medium',timeZone:'Asia/Kuala_Lumpur'}).format(date);}
    }catch(e){console.warn('Update date unavailable',e);}
  });
})();
