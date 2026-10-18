<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Report Copilot</title>
<style>
:root{--bg:#f6f7f9;--card:#fff;--text:#1c2230;--muted:#667085;--accent:#2457d6;--line:#e3e6ec;--warn:#b54708}
@media (prefers-color-scheme:dark){:root{--bg:#10131a;--card:#181c26;--text:#e8ebf2;--muted:#98a2b3;--accent:#6f93ff;--line:#2a3040;--warn:#f79009}}
*{box-sizing:border-box}body{margin:0;font:15px/1.5 system-ui,sans-serif;background:var(--bg);color:var(--text)}
header{padding:14px 20px;border-bottom:1px solid var(--line);background:var(--card);display:flex;gap:12px;align-items:center;flex-wrap:wrap}
header h1{font-size:18px;margin:0;flex:1}select,textarea,button{font:inherit;color:inherit}
select{background:var(--card);border:1px solid var(--line);border-radius:8px;padding:6px 10px}
main{max-width:860px;margin:0 auto;padding:16px}
#log{display:flex;flex-direction:column;gap:12px;min-height:50vh}
.msg{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:12px 14px;white-space:pre-wrap;word-wrap:break-word}
.msg.user{align-self:flex-end;background:var(--accent);color:#fff;border:none;max-width:85%}
.meta{margin-top:8px;font-size:13px;color:var(--muted);white-space:normal}
.badge{display:inline-block;padding:1px 8px;border-radius:99px;border:1px solid var(--line);margin-right:6px}
.warn{color:var(--warn)}
.box{margin-top:8px;padding:8px 10px;border:1px dashed var(--line);border-radius:8px;font-size:13px;white-space:pre-wrap}
.fb button{background:none;border:1px solid var(--line);border-radius:8px;cursor:pointer;padding:2px 8px;margin-right:4px}
.fb button.on{border-color:var(--accent)}
form{display:flex;gap:8px;margin-top:16px}textarea{flex:1;background:var(--card);border:1px solid var(--line);border-radius:10px;padding:10px;resize:vertical;min-height:48px}
form button{background:var(--accent);color:#fff;border:none;border-radius:10px;padding:0 18px;cursor:pointer}
form button:disabled{opacity:.6}
</style>
</head>
<body>
<header>
  <h1>Report Copilot</h1>
  <label for="client">Client</label>
  <select id="client">
    <?php foreach ($clients as $id => $name): ?>
      <option value="<?= html_escape($id) ?>" <?= $id === $client_id ? 'selected' : '' ?>><?= html_escape($name) ?></option>
    <?php endforeach; ?>
  </select>
</header>
<main>
  <div id="log" aria-live="polite"></div>
  <form id="form">
    <textarea id="q" maxlength="1000" placeholder="Ask about a market report..." required></textarea>
    <button id="send" type="submit">Send</button>
  </form>
</main>
<script type="application/json" id="hist"><?= json_encode($history, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script>
const CSRF_NAME = <?= json_encode($csrf_name) ?>, CSRF_HASH = <?= json_encode($csrf_hash) ?>;
const log = document.getElementById('log');
// All dynamic text goes in with textContent (never innerHTML) so model output cannot inject HTML/JS (XSS).
function el(tag, cls, text){ const e=document.createElement(tag); if(cls) e.className=cls; if(text!==undefined) e.textContent=text; return e; }
async function post(url, params){
  const body = new URLSearchParams(params); body.append(CSRF_NAME, CSRF_HASH);
  const r = await fetch(url, {method:'POST', body, credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest'}});
  return r.json();
}
function addUser(text){ log.appendChild(el('div','msg user',text)); }
function addBot(d){
  const box = el('div','msg'); box.appendChild(el('div','',d.answer));
  const meta = el('div','meta');
  if (d.confidence !== null && d.confidence !== undefined) meta.appendChild(el('span','badge','confidence '+Number(d.confidence).toFixed(2)));
  if (d.escalated) meta.appendChild(el('span','badge warn','escalated to analyst'));
  if (d.cached) meta.appendChild(el('span','badge','cached'));
  box.appendChild(meta);
  (d.conflicts||[]).forEach(c=>{
    const b = el('div','box warn','Sources disagree: '+c.metric+' ('+c.region+', '+c.period+')');
    (c.values||[]).forEach(v=>b.appendChild(el('div','', v.report_id+' p.'+v.page+': '+v.value+' '+v.unit+(v.scope?' - '+v.scope:''))));
    box.appendChild(b);
  });
  (d.derived_values||[]).forEach(v=>{
    const b = el('div','box', v.label+' - '+v.report_id+': '+v.result+' '+v.unit);
    b.appendChild(el('div','','Formula: '+v.formula));
    (v.assumptions||[]).forEach(a=>b.appendChild(el('div','','- '+a)));
    box.appendChild(b);
  });
  if ((d.citations||[]).length){
    const c = el('div','box'); c.appendChild(el('div','','Sources'));
    d.citations.forEach(x=>c.appendChild(el('div','', x.report_id+' p.'+x.page+' - '+x.title)));
    box.appendChild(c);
  }
  if (d.message_id){
    const fb = el('div','meta fb');
    [['\uD83D\uDC4D',1],['\uD83D\uDC4E',-1]].forEach(([label,val])=>{
      const b = el('button','',label); if (d.rating===val) b.className='on';
      b.onclick = async()=>{ const r = await post('feedback',{message_id:d.message_id,rating:val});
        if (r.ok) fb.querySelectorAll('button').forEach(x=>x.className = x===b?'on':''); };
      fb.appendChild(b);
    });
    box.appendChild(fb);
  }
  log.appendChild(box); box.scrollIntoView({block:'end'});
}
JSON.parse(document.getElementById('hist').textContent).forEach(h=>{
  let p={}; try{p=JSON.parse(h.payload||'{}')}catch(e){}
  addUser(h.question);
  addBot({answer:h.answer, confidence:h.confidence===null?null:Number(h.confidence), escalated:h.escalated==1,
          citations:p.citations, conflicts:p.conflicts, derived_values:p.derived_values,
          message_id:Number(h.id), rating:h.rating===null?null:Number(h.rating)});
});
document.getElementById('form').addEventListener('submit', async e=>{
  e.preventDefault();
  const q=document.getElementById('q'), btn=document.getElementById('send'), text=q.value.trim();
  if(!text) return;
  addUser(text); q.value=''; btn.disabled=true; btn.textContent='Thinking...';
  try{
    const d = await post('send',{client_id:document.getElementById('client').value, question:text});
    addBot(d.error ? {answer:d.error} : d);
  }catch(err){ addBot({answer:'Something went wrong. Please try again.'}); }
  btn.disabled=false; btn.textContent='Send';
});
</script>
</body>
</html>
